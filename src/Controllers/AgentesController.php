<?php

namespace App\Controllers;

use App\Models\Agente;
use PDO;
use Exception;
use function App\Includes\log_error;
use function App\Includes\flash_message;
use function App\Includes\redirect;
use function App\Includes\sanitize_input;

class AgentesController 
{
    public function index() 
    {
        try {
            $agente = new Agente();
            $stmt = $agente->read();
            $agentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            include 'views/agentes/index.php';
        } catch (Exception $e) {
            log_error('AGENTES_INDEX_ERROR', $e->getMessage());
            flash_message('error', 'Erro ao carregar agentes: ' . $e->getMessage());
            include 'views/agentes/index.php'; // ainda tenta exibir
        }
    }
    
    public function create() 
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $agente = new Agente();
            
            // Sanitizar entradas
            $agente->email_agente = sanitize_input($_POST['email_agente'] ?? '');
            $agente->cpf_agente = sanitize_input($_POST['cpf_agente'] ?? '');
            
            // Verificar duplicatas
            if (!empty($agente->email_agente) && $agente->emailExists()) {
                flash_message('error', 'Email já cadastrado no sistema');
                include 'views/agentes/create.php';
                return;
            }
            
            if (!empty($agente->cpf_agente) && $agente->cpfExists()) {
                flash_message('error', 'CPF já cadastrado no sistema');
                include 'views/agentes/create.php';
                return;
            }
            
            // Preencher dados
            $agente->nome_agente = sanitize_input($_POST['nome_agente'] ?? '');
            $agente->rg_agente = sanitize_input($_POST['rg_agente'] ?? '');
            $agente->telefone_agente = sanitize_input($_POST['telefone_agente'] ?? '');
            $agente->endereco_agente = sanitize_input($_POST['endereco_agente'] ?? '');
            $agente->numero_agente = sanitize_input($_POST['numero_agente'] ?? '');
            $agente->bairro_agente = sanitize_input($_POST['bairro_agente'] ?? '');
            $agente->cidade_agente = sanitize_input($_POST['cidade_agente'] ?? '');
            $agente->estado_agente = sanitize_input($_POST['estado_agente'] ?? '');
            $agente->cep_agente = sanitize_input($_POST['cep_agente'] ?? '');
            $agente->data_nascimento = $_POST['data_nascimento'] ?? null;
            $agente->status_agente = sanitize_input($_POST['status_agente'] ?? 'Ativo');
            $agente->observacoes = sanitize_input($_POST['observacoes'] ?? '');

            try {
                if ($agente->create()) {
                    flash_message('success', 'Agente cadastrado com sucesso!');
                    redirect('index.php?page=agentes');
                    exit;
                } else {
                    flash_message('error', 'Erro ao cadastrar agente.');
                }
            } catch (Exception $e) {
                log_error('AGENTE_CREATE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao salvar agente: ' . $e->getMessage());
            }
        }
        
        // Exibir formulário
        include 'views/agentes/create.php';
    }
    
    public function edit() 
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=agentes');
            exit;
        }

        $agente = new Agente();
        $agente->id_agente = $id;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sanitizar
            $agente->email_agente = sanitize_input($_POST['email_agente'] ?? '');
            $agente->cpf_agente = sanitize_input($_POST['cpf_agente'] ?? '');

            // Verificar duplicatas (exceto o próprio)
            if (!empty($agente->email_agente) && $agente->emailExists()) {
                flash_message('error', 'Email já cadastrado no sistema.');
                include 'views/agentes/edit.php';
                return;
            }

            if (!empty($agente->cpf_agente) && $agente->cpfExists()) {
                flash_message('error', 'CPF já cadastrado no sistema.');
                include 'views/agentes/edit.php';
                return;
            }

            // Preencher dados
            $agente->nome_agente = sanitize_input($_POST['nome_agente'] ?? '');
            $agente->rg_agente = sanitize_input($_POST['rg_agente'] ?? '');
            $agente->telefone_agente = sanitize_input($_POST['telefone_agente'] ?? '');
            $agente->endereco_agente = sanitize_input($_POST['endereco_agente'] ?? '');
            $agente->numero_agente = sanitize_input($_POST['numero_agente'] ?? '');
            $agente->bairro_agente = sanitize_input($_POST['bairro_agente'] ?? '');
            $agente->cidade_agente = sanitize_input($_POST['cidade_agente'] ?? '');
            $agente->estado_agente = sanitize_input($_POST['estado_agente'] ?? '');
            $agente->cep_agente = sanitize_input($_POST['cep_agente'] ?? '');
            $agente->data_nascimento = $_POST['data_nascimento'] ?? null;
            $agente->status_agente = sanitize_input($_POST['status_agente'] ?? 'Ativo');
            $agente->observacoes = sanitize_input($_POST['observacoes'] ?? '');

            try {
                if ($agente->update()) {
                    flash_message('success', 'Agente atualizado com sucesso!');
                    redirect('index.php?page=agentes');
                    exit;
                } else {
                    flash_message('error', 'Erro ao atualizar agente.');
                }
            } catch (Exception $e) {
                log_error('AGENTE_UPDATE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao atualizar: ' . $e->getMessage());
            }
        }

        // Carregar agente para edição
        if (!$agente->readOne()) {
            flash_message('error', 'Agente não encontrado.');
            redirect('index.php?page=agentes');
            exit;
        }

        include 'views/agentes/edit.php';
    }
    
    public function delete() 
    {
        // Verificar permissão
        if ($_SESSION['user_level'] !== 'admin') {
            flash_message('error', 'Acesso negado. Apenas administradores podem excluir registros.');
            redirect('index.php?page=agentes');
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=agentes');
            exit;
        }

        $agente = new Agente();
        $agente->id_agente = $id;

        try {
            if ($agente->delete()) {
                flash_message('success', 'Agente excluído com sucesso!');
            } else {
                flash_message('error', 'Erro ao excluir agente.');
            }
        } catch (Exception $e) {
            log_error('AGENTE_DELETE_ERROR', $e->getMessage());
            flash_message('error', 'Erro ao excluir: ' . $e->getMessage());
        }

        redirect('index.php?page=agentes');
        exit;
    }
    
    public function view() 
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=agentes');
            exit;
        }

        $agente = new Agente();
        $agente->id_agente = $id;

        if (!$agente->readOne()) {
            flash_message('error', 'Agente não encontrado.');
            redirect('index.php?page=agentes');
            exit;
        }

        include 'views/agentes/view.php';
    }
}