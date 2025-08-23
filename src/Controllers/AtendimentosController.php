<?php

namespace App\Controllers;

use App\Models\Atendimento;
use App\Models\Cliente;
use App\Models\Agente;
use App\Config\Database;
use PDO;
use Exception;
use function App\Includes\log_error;
use function App\Includes\flash_message;
use function App\Includes\redirect;
use function App\Includes\sanitize_input;

class AtendimentosController 
{
    /**
     * Carregar clientes e agentes ativos
     */
    private function loadClientesAgentes() 
    {
        try {
            $database = new Database();
            $conn = $database->getConnection();

            $query = "SELECT id_cliente, nome_empresa FROM clientes ORDER BY nome_empresa";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $query = "SELECT id_agente, nome FROM agentes WHERE status = 'Ativo' ORDER BY nome";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $agentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [$clientes, $agentes];
        } catch (Exception $e) {
            log_error('LOAD_CLIENTES_AGENTES_ERROR', $e->getMessage());
            flash_message('error', 'Erro ao carregar dados para o formulário.');
            return [[], []];
        }
    }

    public function index() 
    {
        try {
            $atendimento = new Atendimento();
            $stmt = $atendimento->read();
            $atendimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            include 'views/atendimentos/index.php';
        } catch (Exception $e) {
            log_error('ATENDIMENTOS_INDEX_ERROR', $e->getMessage());
            flash_message('error', 'Erro ao carregar atendimentos: ' . $e->getMessage());
            include 'views/atendimentos/index.php';
        }
    }
    
    public function create() 
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $atendimento = new Atendimento();

            // Preencher propriedades
            $atendimento->solicitante = sanitize_input($_POST['solicitante'] ?? '');
            $atendimento->motivo = sanitize_input($_POST['motivo'] ?? '');
            $atendimento->valor_patrimonial = $_POST['valor_patrimonial'] ?? null;
            $atendimento->id_cliente = (int)($_POST['id_cliente'] ?? 0);
            $atendimento->conta = sanitize_input($_POST['conta'] ?? '');
            $atendimento->id_validacao = sanitize_input($_POST['id_validacao'] ?? '');
            $atendimento->filial = sanitize_input($_POST['filial'] ?? '');
            $atendimento->ordem_servico = sanitize_input($_POST['ordem_servico'] ?? '');
            $atendimento->cep = sanitize_input($_POST['cep'] ?? '');
            $atendimento->estado = sanitize_input($_POST['estado'] ?? '');
            $atendimento->cidade = sanitize_input($_POST['cidade'] ?? '');
            $atendimento->endereco = sanitize_input($_POST['endereco'] ?? '');
            $atendimento->numero = sanitize_input($_POST['numero'] ?? '');
            $atendimento->latitude = $_POST['latitude'] ?? null;
            $atendimento->longitude = $_POST['longitude'] ?? null;
            $atendimento->agentes_aptos = sanitize_input($_POST['agentes_aptos'] ?? '');
            $atendimento->id_agente = (int)($_POST['id_agente'] ?? 0);
            $atendimento->equipe = sanitize_input($_POST['equipe'] ?? '');
            $atendimento->responsavel = sanitize_input($_POST['responsavel'] ?? '');
            $atendimento->estabelecimento = sanitize_input($_POST['estabelecimento'] ?? '');
            $atendimento->hora_solicitada = $_POST['hora_solicitada'] ?? null;
            $atendimento->hora_local = $_POST['hora_local'] ?? null;
            $atendimento->hora_saida = $_POST['hora_saida'] ?? null;
            $atendimento->status_atendimento = $_POST['status_atendimento'] ?? '';
            $atendimento->tipo_de_servico = $_POST['tipo_de_servico'] ?? '';
            $atendimento->tipos_de_dados = sanitize_input($_POST['tipos_de_dados'] ?? '');
            $atendimento->estabelecida_inicio = $_POST['estabelecida_inicio'] ?? null;
            $atendimento->estabelecida_fim = $_POST['estabelecida_fim'] ?? null;
            $atendimento->indeterminado = !empty($_POST['indeterminado']) ? 1 : 0;

            try {
                if ($atendimento->create()) {
                    flash_message('success', 'Atendimento criado com sucesso!');
                    redirect('index.php?page=atendimentos');
                    exit;
                } else {
                    flash_message('error', 'Erro ao criar atendimento.');
                }
            } catch (Exception $e) {
                log_error('ATENDIMENTO_CREATE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao salvar atendimento: ' . $e->getMessage());
            }
        }

        // Carregar dados para o formulário
        [$clientes, $agentes] = $this->loadClientesAgentes();

        include 'views/atendimentos/create.php';
    }
    
    public function edit() 
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=atendimentos');
            exit;
        }

        $atendimento = new Atendimento();
        $atendimento->id_atendimento = $id;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Preencher propriedades
            $atendimento->solicitante = sanitize_input($_POST['solicitante'] ?? '');
            $atendimento->motivo = sanitize_input($_POST['motivo'] ?? '');
            $atendimento->valor_patrimonial = $_POST['valor_patrimonial'] ?? null;
            $atendimento->id_cliente = (int)($_POST['id_cliente'] ?? 0);
            $atendimento->conta = sanitize_input($_POST['conta'] ?? '');
            $atendimento->id_validacao = sanitize_input($_POST['id_validacao'] ?? '');
            $atendimento->filial = sanitize_input($_POST['filial'] ?? '');
            $atendimento->ordem_servico = sanitize_input($_POST['ordem_servico'] ?? '');
            $atendimento->cep = sanitize_input($_POST['cep'] ?? '');
            $atendimento->estado = sanitize_input($_POST['estado'] ?? '');
            $atendimento->cidade = sanitize_input($_POST['cidade'] ?? '');
            $atendimento->endereco = sanitize_input($_POST['endereco'] ?? '');
            $atendimento->numero = sanitize_input($_POST['numero'] ?? '');
            $atendimento->latitude = $_POST['latitude'] ?? null;
            $atendimento->longitude = $_POST['longitude'] ?? null;
            $atendimento->agentes_aptos = sanitize_input($_POST['agentes_aptos'] ?? '');
            $atendimento->id_agente = (int)($_POST['id_agente'] ?? 0);
            $atendimento->equipe = sanitize_input($_POST['equipe'] ?? '');
            $atendimento->responsavel = sanitize_input($_POST['responsavel'] ?? '');
            $atendimento->estabelecimento = sanitize_input($_POST['estabelecimento'] ?? '');
            $atendimento->hora_solicitada = $_POST['hora_solicitada'] ?? null;
            $atendimento->hora_local = $_POST['hora_local'] ?? null;
            $atendimento->hora_saida = $_POST['hora_saida'] ?? null;
            $atendimento->status_atendimento = $_POST['status_atendimento'] ?? '';
            $atendimento->tipo_de_servico = $_POST['tipo_de_servico'] ?? '';
            $atendimento->tipos_de_dados = sanitize_input($_POST['tipos_de_dados'] ?? '');
            $atendimento->estabelecida_inicio = $_POST['estabelecida_inicio'] ?? null;
            $atendimento->estabelecida_fim = $_POST['estabelecida_fim'] ?? null;
            $atendimento->indeterminado = !empty($_POST['indeterminado']) ? 1 : 0;

            try {
                if ($atendimento->update()) {
                    flash_message('success', 'Atendimento atualizado com sucesso!');
                    redirect('index.php?page=atendimentos');
                    exit;
                } else {
                    flash_message('error', 'Erro ao atualizar atendimento.');
                }
            } catch (Exception $e) {
                log_error('ATENDIMENTO_UPDATE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao atualizar atendimento: ' . $e->getMessage());
            }
        }

        if (!$atendimento->readOne()) {
            flash_message('error', 'Atendimento não encontrado.');
            redirect('index.php?page=atendimentos');
            exit;
        }

        // Carregar dados para o formulário
        [$clientes, $agentes] = $this->loadClientesAgentes();

        include 'views/atendimentos/edit.php';
    }
    
    public function delete() 
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=atendimentos');
            exit;
        }

        $atendimento = new Atendimento();
        $atendimento->id_atendimento = $id;

        try {
            if ($atendimento->delete()) {
                flash_message('success', 'Atendimento excluído com sucesso!');
            } else {
                flash_message('error', 'Erro ao excluir atendimento.');
            }
        } catch (Exception $e) {
            log_error('ATENDIMENTO_DELETE_ERROR', $e->getMessage());
            flash_message('error', 'Erro ao excluir atendimento: ' . $e->getMessage());
        }

        redirect('index.php?page=atendimentos');
        exit;
    }
    
    public function view() 
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=atendimentos');
            exit;
        }

        $atendimento = new Atendimento();
        $atendimento->id_atendimento = $id;

        if (!$atendimento->readOne()) {
            flash_message('error', 'Atendimento não encontrado.');
            redirect('index.php?page=atendimentos');
            exit;
        }

        include 'views/atendimentos/view.php';
    }
}