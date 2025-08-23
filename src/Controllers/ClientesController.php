<?php

namespace App\Controllers;

use App\Models\Cliente;
use PDO;
use Exception;
use function App\Includes\log_error;
use function App\Includes\flash_message;
use function App\Includes\redirect;
use function App\Includes\sanitize_input;

class ClientesController 
{
    public function index() 
    {
        try {
            $cliente = new Cliente();
            $stmt = $cliente->read();
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            include 'views/clientes/index.php';
        } catch (Exception $e) {
            log_error('CLIENTES_INDEX_ERROR', $e->getMessage());
            flash_message('error', 'Erro ao carregar clientes: ' . $e->getMessage());
            include 'views/clientes/index.php';
        }
    }
    
    public function create() 
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cliente = new Cliente();
            
            // Sanitizar CNPJ
            $cliente->cnpj = sanitize_input($_POST['cnpj'] ?? '');
            
            // Verificar duplicata
            if (!empty($cliente->cnpj) && $cliente->cnpjExists()) {
                flash_message('error', 'CNPJ já cadastrado no sistema.');
                include 'views/clientes/create.php';
                return;
            }
            
            // Preencher propriedades
            $cliente->nome_empresa = sanitize_input($_POST['nome_empresa'] ?? '');
            $cliente->contato = sanitize_input($_POST['contato'] ?? '');
            $cliente->endereco = sanitize_input($_POST['endereco'] ?? '');
            $cliente->telefone = sanitize_input($_POST['telefone'] ?? '');

            try {
                if ($cliente->create()) {
                    flash_message('success', 'Cliente cadastrado com sucesso!');
                    redirect('index.php?page=clientes');
                    exit;
                } else {
                    flash_message('error', 'Erro ao cadastrar cliente.');
                }
            } catch (Exception $e) {
                log_error('CLIENTE_CREATE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao salvar cliente: ' . $e->getMessage());
            }
        }
        
        include 'views/clientes/create.php';
    }
    
    public function edit() 
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=clientes');
            exit;
        }

        $cliente = new Cliente();
        $cliente->id_cliente = $id;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sanitizar CNPJ
            $cliente->cnpj = sanitize_input($_POST['cnpj'] ?? '');
            
            // Verificar duplicata (exceto o próprio)
            if (!empty($cliente->cnpj) && $cliente->cnpjExists()) {
                flash_message('error', 'CNPJ já cadastrado no sistema.');
                include 'views/clientes/edit.php';
                return;
            }
            
            // Preencher propriedades
            $cliente->nome_empresa = sanitize_input($_POST['nome_empresa'] ?? '');
            $cliente->contato = sanitize_input($_POST['contato'] ?? '');
            $cliente->endereco = sanitize_input($_POST['endereco'] ?? '');
            $cliente->telefone = sanitize_input($_POST['telefone'] ?? '');

            try {
                if ($cliente->update()) {
                    flash_message('success', 'Cliente atualizado com sucesso!');
                    redirect('index.php?page=clientes');
                    exit;
                } else {
                    flash_message('error', 'Erro ao atualizar cliente.');
                }
            } catch (Exception $e) {
                log_error('CLIENTE_UPDATE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao atualizar cliente: ' . $e->getMessage());
            }
        }
        
        // Carregar cliente
        if (!$cliente->readOne()) {
            flash_message('error', 'Cliente não encontrado.');
            redirect('index.php?page=clientes');
            exit;
        }
        
        include 'views/clientes/edit.php';
    }
    
    public function delete() 
    {
        // Verificar permissão
        if ($_SESSION['user_level'] !== 'admin') {
            flash_message('error', 'Acesso negado. Apenas administradores podem excluir registros.');
            redirect('index.php?page=clientes');
            exit;
        }
        
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=clientes');
            exit;
        }

        $cliente = new Cliente();
        $cliente->id_cliente = $id;
        
        try {
            if ($cliente->delete()) {
                flash_message('success', 'Cliente excluído com sucesso!');
            } else {
                flash_message('error', 'Erro ao excluir cliente.');
            }
        } catch (Exception $e) {
            log_error('CLIENTE_DELETE_ERROR', $e->getMessage());
            flash_message('error', 'Erro ao excluir cliente: ' . $e->getMessage());
        }
        
        redirect('index.php?page=clientes');
        exit;
    }
    
    public function view() 
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=clientes');
            exit;
        }

        $cliente = new Cliente();
        $cliente->id_cliente = $id;
        
        if (!$cliente->readOne()) {
            flash_message('error', 'Cliente não encontrado.');
            redirect('index.php?page=clientes');
            exit;
        }
        
        include 'views/clientes/view.php';
    }
}