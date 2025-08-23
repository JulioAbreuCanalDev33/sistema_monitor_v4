<?php

namespace App\Controllers;

use App\Includes\ReportGenerator;
use App\Config\Database;
use PDO;
use Exception;
use function App\Includes\flash_message;
use function App\Includes\redirect;

class RelatoriosController 
{
    public function index() 
    {
        include 'views/relatorios/index.php';
    }
    
    public function generate() 
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            flash_message('error', 'Método não permitido.');
            redirect('index.php?page=relatorios');
            exit;
        }

        // Validar entradas
        $tipo_relatorio = $_POST['tipo_relatorio'] ?? '';
        $formato = $_POST['formato'] ?? 'pdf';
        $data_inicio = $_POST['data_inicio'] ?? '';
        $data_fim = $_POST['data_fim'] ?? '';

        $reportGenerator = new ReportGenerator();
        
        try {
            switch ($tipo_relatorio) {
                case 'atendimentos':
                    $params = [
                        'data_inicio' => $data_inicio,
                        'data_fim' => $data_fim
                    ];

                    if ($formato === 'pdf') {
                        $filename = $reportGenerator->generateAtendimentosPDF($params);
                    } elseif ($formato === 'excel') {
                        $filename = $reportGenerator->generateAtendimentosExcel($params);
                    } else {
                        flash_message('error', 'Formato inválido.');
                        redirect('index.php?page=relatorios');
                        exit;
                    }
                    break;
                    
                case 'ocorrencias_veiculares':
                    if ($formato === 'pdf') {
                        $filename = $reportGenerator->generateOcorrenciasVeicularesPDF();
                    } else {
                        flash_message('warning', 'Relatório Excel para ocorrências veiculares em desenvolvimento');
                        redirect('index.php?page=relatorios');
                        exit;
                    }
                    break;
                    
                case 'prestadores':
                    flash_message('warning', 'Relatório de prestadores em desenvolvimento');
                    redirect('index.php?page=relatorios');
                    exit;
                    
                default:
                    flash_message('error', 'Tipo de relatório não encontrado');
                    redirect('index.php?page=relatorios');
                    exit;
            }
            
            // Verificar se o arquivo foi gerado
            if (isset($filename) && file_exists($filename)) {
                $this->downloadFile($filename);
            } else {
                flash_message('error', 'Erro ao gerar relatório: arquivo não encontrado');
                redirect('index.php?page=relatorios');
                exit;
            }
            
        } catch (Exception $e) {
            flash_message('error', 'Erro ao gerar relatório: ' . $e->getMessage());
            redirect('index.php?page=relatorios');
            exit;
        }
    }
    
    /**
     * Envia o arquivo para download e remove da memória
     */
    private function downloadFile($filepath) 
    {
        if (!file_exists($filepath)) {
            flash_message('error', 'Arquivo do relatório não encontrado.');
            redirect('index.php?page=relatorios');
            exit;
        }

        $filename = basename($filepath);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Mapear content-type
        $mime_types = [
            'pdf'  => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls'  => 'application/vnd.ms-excel',
            'csv'  => 'text/csv'
        ];
        
        $content_type = $mime_types[$extension] ?? 'application/octet-stream';

        // Limpar buffers
        if (ob_get_length()) {
            ob_clean();
        }

        // Headers
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');

        // Desativar buffering adicional
        flush();

        // Enviar arquivo
        readfile($filepath);

        // Remover arquivo temporário (opcional)
        // unlink($filepath); // Comente se quiser manter logs

        exit;
    }
    
    /**
     * Endpoint AJAX para dados do dashboard (compatível com SQLite)
     */
    public function dashboard_data() 
    {
        header('Content-Type: application/json');

        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            $data = [];
            
            // Estatísticas gerais
            $queries = [
                'total_atendimentos' => "SELECT COUNT(*) as total FROM atendimentos",
                'total_ocorrencias' => "SELECT COUNT(*) as total FROM ocorrencias_veiculares",
                'total_prestadores' => "SELECT COUNT(*) as total FROM tabela_prestadores",
                'total_clientes' => "SELECT COUNT(*) as total FROM clientes"
            ];
            
            foreach ($queries as $key => $query) {
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $data[$key] = (int) ($result['total'] ?? 0);
            }
            
            // Atendimentos por mês - Últimos 6 meses (SQLite)
            $query = "SELECT 
                        strftime('%Y-%m', hora_local) as mes, 
                        COUNT(*) as total 
                      FROM atendimentos 
                      WHERE hora_local >= datetime('now', '-6 months')
                      GROUP BY strftime('%Y-%m', hora_local)
                      ORDER BY mes";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $data['atendimentos_mes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($data);
        } catch (Exception $e) {
            error_log("RelatoriosController::dashboard_data error: " . $e->getMessage());
            echo json_encode([
                'error' => 'Erro ao carregar dados do dashboard'
            ]);
        }
        
        exit;
    }
}