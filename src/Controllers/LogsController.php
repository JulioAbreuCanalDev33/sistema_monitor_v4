<?php

namespace App\Controllers;

use App\Includes\Logger;
use Exception;
use function App\Includes\log_access_denied;
use function App\Includes\log_page_access;
use function App\Includes\log_info;
use function App\Includes\log_warning;
use function App\Includes\log_error;
use function App\Includes\log_critical;
use function App\Includes\log_auth;
use function App\Includes\log_crud;
use function App\Includes\log_upload;
use function App\Includes\flash_message;
use function App\Includes\redirect;

class LogsController 
{
    private $logger;

    public function __construct() 
    {
        // Garantir que a sessão está ativa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->logger = new Logger();

        // Verificar se usuário está logado
        if (!isset($_SESSION['user_id'])) {
            log_access_denied('logs', 'Usuário não autenticado');
            header('Location: index.php?page=login');
            exit;
        }

        // Verificar se é admin
        if ($_SESSION['user_level'] !== 'admin') {
            log_access_denied('logs', 'Acesso negado: usuário não é admin (ID: ' . $_SESSION['user_id'] . ')');
            header('Location: index.php?page=dashboard');
            exit;
        }
    }

    public function index() 
    {
        log_page_access('logs', 'index');

        try {
            include 'views/logs/index.php';
        } catch (Exception $e) {
            log_error('LOGS_INDEX_ERROR', $e->getMessage());
            include 'views/logs/index.php'; // tenta carregar mesmo com erro
        }
    }

    public function clean() 
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        try {
            $removed = $this->logger->cleanOldLogs(30);
            log_info('LOGS_CLEANED', "Removidos $removed logs antigos");

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'removed' => $removed,
                'message' => "$removed logs antigos foram removidos"
            ]);
        } catch (Exception $e) {
            log_error('LOGS_CLEAN_ERROR', $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao limpar logs: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    public function export() 
    {
        $level = $_GET['level'] ?? '';
        $user_id = $_GET['user_id'] ?? '';
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';
        $limit = max(1, min((int)($_GET['limit'] ?? 1000), 5000));

        try {
            $logs = $this->logger->readLogs($limit, $level, $user_id, $date_from, $date_to);

            $filename = 'logs_' . date('Y-m-d_H-i-s') . '.csv';

            // Limpar qualquer saída anterior
            if (ob_get_length()) {
                ob_clean();
            }

            // Headers para download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Expires: 0');

            // Abrir fluxo de saída
            $output = fopen('php://output', 'w');
            if (!$output) {
                throw new Exception("Falha ao abrir fluxo de saída para CSV");
            }

            // BOM UTF-8 para compatibilidade com Excel
            fputs($output, "\xEF\xBB\xBF");

            // Cabeçalho do CSV
            fputcsv($output, [
                'Data/Hora',
                'Nível',
                'ID Usuário',
                'Nome Usuário',
                'Nível Usuário',
                'IP',
                'Ação',
                'Detalhes',
                'User Agent',
                'URI',
                'Método'
            ]);

            // Dados
            foreach ($logs as $log) {
                fputcsv($output, [
                    $log['timestamp'] ?? '',
                    $log['level'] ?? '',
                    $log['user_id'] ?? '',
                    $log['user_name'] ?? '',
                    $log['user_level'] ?? '',
                    $log['ip'] ?? '',
                    $log['action'] ?? '',
                    $log['details'] ?? '',
                    $log['user_agent'] ?? '',
                    $log['request_uri'] ?? '',
                    $log['request_method'] ?? ''
                ]);
            }

            fclose($output);
            log_info('LOGS_EXPORTED', "Exportados " . count($logs) . " logs");
        } catch (Exception $e) {
            log_error('LOGS_EXPORT_ERROR', $e->getMessage());

            // Se headers já foram enviados, não podemos redirecionar
            if (headers_sent()) {
                error_log("Erro no export (headers já enviados): " . $e->getMessage());
            } else {
                // Ainda podemos redirecionar
                flash_message('error', 'Erro ao exportar logs: ' . $e->getMessage());
                redirect('index.php?page=logs');
            }
        }
        exit;
    }

    public function stats() 
    {
        $days = max(1, min((int)($_GET['days'] ?? 7), 365));

        try {
            $stats = $this->logger->getLogStats($days);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($stats);
        } catch (Exception $e) {
            log_error('LOGS_STATS_ERROR', $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao gerar estatísticas']);
        }
        exit;
    }

    public function view() 
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            flash_message('error', 'ID de log inválido.');
            redirect('index.php?page=logs');
            exit;
        }

        //log_page_access('logs', 'view', ['log_id' => $id]);

        // Futuramente: carregar log específico
        // $log = $this->logger->getLogById($id);

        redirect('index.php?page=logs');
        exit;
    }

    public function test() 
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        try {
            // Logs de teste
            log_info('LOG_TEST', 'Teste de log INFO');
            log_warning('LOG_TEST', 'Teste de log WARNING');
            log_error('LOG_TEST', 'Teste de log ERROR');
            log_critical('LOG_TEST', 'Teste de log CRITICAL');

            log_auth('LOGIN_TEST', 'teste@exemplo.com', true, 'Login bem-sucedido');
            log_auth('LOGIN_TEST', 'teste@exemplo.com', false, 'Falha no login');

            log_crud('CREATE', 'test_table', 123, 'Registro criado');
            log_crud('UPDATE', 'test_table', 123, 'Registro atualizado');
            log_crud('DELETE', 'test_table', 123, 'Registro excluído');

            log_upload('foto.jpg', 204800, true, 'Upload bem-sucedido');
            log_upload('malware.exe', 0, false, 'Arquivo bloqueado');

            log_access_denied('painel_secreto', 'Tentativa de acesso não autorizada');

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Logs de teste gerados com sucesso!'
            ]);
        } catch (Exception $e) {
            log_error('LOG_TEST_ERROR', $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao gerar logs de teste: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}
// Não use ?> no final de arquivos PHP puros