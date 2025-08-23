<?php

namespace App\Controllers;

use App\Models\OcorrenciaVeicular;
use App\Config\Database;
use PDO;
use Exception;
use function App\Includes\log_error;
use function App\Includes\flash_message;
use function App\Includes\redirect;
use function App\Includes\sanitize_input;

class OcorrenciasController 
{
    /**
     * Carregar prestadores para selects
     */
    private function loadPrestadores() 
    {
        try {
            $database = new Database();
            $conn = $database->getConnection();

            $query = "SELECT id, nome_prestador FROM tabela_prestadores ORDER BY nome_prestador";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            log_error('LOAD_PRESTADORES_ERROR', $e->getMessage());
            flash_message('error', 'Erro ao carregar prestadores.');
            return [];
        }
    }

    public function index() 
    {
        try {
            $ocorrencia = new OcorrenciaVeicular();
            $stmt = $ocorrencia->read();
            $ocorrencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            include 'views/ocorrencias/index.php';
        } catch (Exception $e) {
            log_error('OCORRENCIAS_INDEX_ERROR', $e->getMessage());
            flash_message('error', 'Erro ao carregar ocorrências: ' . $e->getMessage());
            include 'views/ocorrencias/index.php';
        }
    }
    
    public function create() 
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ocorrencia = new OcorrenciaVeicular();

            // Preencher dados
            $this->fillOcorrencia($ocorrencia);

            try {
                if ($ocorrencia->create()) {
                    flash_message('success', 'Ocorrência veicular criada com sucesso!');
                    redirect('index.php?page=ocorrencias');
                    exit;
                } else {
                    flash_message('error', 'Erro ao criar ocorrência veicular.');
                }
            } catch (Exception $e) {
                log_error('OCORRENCIA_CREATE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao salvar ocorrência: ' . $e->getMessage());
            }
        }
        
        $prestadores = $this->loadPrestadores();
        include 'views/ocorrencias/create.php';
    }
    
    public function edit() 
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=ocorrencias');
            exit;
        }

        $ocorrencia = new OcorrenciaVeicular();
        $ocorrencia->id = $id;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->fillOcorrencia($ocorrencia);

            try {
                if ($ocorrencia->update()) {
                    flash_message('success', 'Ocorrência veicular atualizada com sucesso!');
                    redirect('index.php?page=ocorrencias');
                    exit;
                } else {
                    flash_message('error', 'Erro ao atualizar ocorrência veicular.');
                }
            } catch (Exception $e) {
                log_error('OCORRENCIA_UPDATE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao atualizar ocorrência: ' . $e->getMessage());
            }
        }
        
        if (!$ocorrencia->readOne()) {
            flash_message('error', 'Ocorrência veicular não encontrada.');
            redirect('index.php?page=ocorrencias');
            exit;
        }
        
        $prestadores = $this->loadPrestadores();
        include 'views/ocorrencias/edit.php';
    }
    
    public function delete() 
    {
        // Verificar permissão
        if ($_SESSION['user_level'] !== 'admin') {
            flash_message('error', 'Acesso negado. Apenas administradores podem excluir registros.');
            redirect('index.php?page=ocorrencias');
            exit;
        }
        
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=ocorrencias');
            exit;
        }

        $ocorrencia = new OcorrenciaVeicular();
        $ocorrencia->id = $id;
        
        try {
            if ($ocorrencia->delete()) {
                flash_message('success', 'Ocorrência veicular excluída com sucesso!');
            } else {
                flash_message('error', 'Erro ao excluir ocorrência veicular.');
            }
        } catch (Exception $e) {
            log_error('OCORRENCIA_DELETE_ERROR', $e->getMessage());
            flash_message('error', 'Erro ao excluir ocorrência: ' . $e->getMessage());
        }
        
        redirect('index.php?page=ocorrencias');
        exit;
    }
    
    public function view() 
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=ocorrencias');
            exit;
        }

        $ocorrencia = new OcorrenciaVeicular();
        $ocorrencia->id = $id;
        
        if (!$ocorrencia->readOne()) {
            flash_message('error', 'Ocorrência veicular não encontrada.');
            redirect('index.php?page=ocorrencias');
            exit;
        }
        
        include 'views/ocorrencias/view.php';
    }

    /**
     * Preenche as propriedades da ocorrência a partir do POST
     */
    private function fillOcorrencia($ocorrencia) 
    {
        $ocorrencia->cliente = sanitize_input($_POST['cliente'] ?? '');
        $ocorrencia->servico = sanitize_input($_POST['servico'] ?? '');
        $ocorrencia->id_validacao = sanitize_input($_POST['id_validacao'] ?? '');
        $ocorrencia->valor_veicular = $_POST['valor_veicular'] ?? null;
        $ocorrencia->cep = sanitize_input($_POST['cep'] ?? '');
        $ocorrencia->estado = sanitize_input($_POST['estado'] ?? '');
        $ocorrencia->cidade = sanitize_input($_POST['cidade'] ?? '');
        $ocorrencia->solicitante = sanitize_input($_POST['solicitante'] ?? '');
        $ocorrencia->motivo = sanitize_input($_POST['motivo'] ?? '');
        $ocorrencia->endereco_da_ocorrencia = sanitize_input($_POST['endereco_da_ocorrencia'] ?? '');
        $ocorrencia->numero = sanitize_input($_POST['numero'] ?? ''); // Corrigido: sem acento
        $ocorrencia->latitude = $_POST['latitude'] ?? null;
        $ocorrencia->longitude = $_POST['longitude'] ?? null;
        $ocorrencia->agentes_aptos = sanitize_input($_POST['agentes_aptos'] ?? '');
        $ocorrencia->prestador = sanitize_input($_POST['prestador'] ?? '');
        $ocorrencia->equipe = sanitize_input($_POST['equipe'] ?? '');
        $ocorrencia->tipo_de_ocorrencia = sanitize_input($_POST['tipo_de_ocorrencia'] ?? '');
        $ocorrencia->data_hora_evento = $_POST['data_hora_evento'] ?? null;
        $ocorrencia->data_hora_deslocamento = $_POST['data_hora_deslocamento'] ?? null;
        $ocorrencia->data_hora_transmissao = $_POST['data_hora_transmissao'] ?? null;
        $ocorrencia->data_hora_local = $_POST['data_hora_local'] ?? null;
        $ocorrencia->data_hora_inicio_atendimento = $_POST['data_hora_inicio_atendimento'] ?? null;
        $ocorrencia->data_hora_fim_atendimento = $_POST['data_hora_fim_atendimento'] ?? null;
        $ocorrencia->franquia_hora = $_POST['franquia_hora'] ?? null;
        $ocorrencia->franquia_km = $_POST['franquia_km'] ?? null;
        $ocorrencia->km_inicial_atendimento = $_POST['km_inicial_atendimento'] ?? null;
        $ocorrencia->km_final_atendimento = $_POST['km_final_atendimento'] ?? null;
        $ocorrencia->total_horas_atendimento = $_POST['total_horas_atendimento'] ?? null;
        $ocorrencia->total_km_percorrido = $_POST['total_km_percorrido'] ?? null;
        $ocorrencia->descricao_fatos = sanitize_input($_POST['descricao_fatos'] ?? '');
        $ocorrencia->gastos_adicionais = $_POST['gastos_adicionais'] ?? null;
    }
}