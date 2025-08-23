<?php

namespace App\Controllers;

use App\Models\Usuario;
use PDO;
use Exception;
use function App\Includes\log_access_denied;
use function App\Includes\log_error;
use function App\Includes\flash_message;
use function App\Includes\redirect;
use function App\Includes\sanitize_input;
use function App\Includes\log_activity;

class UsuariosController 
{
    public function index() 
    {
        // Verificar se é admin
        if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'admin') {
            log_access_denied('usuarios', 'Acesso negado ao listar usuários');
            redirect('index.php?page=dashboard');
            exit;
        }
        
        try {
            $usuario = new Usuario();
            $stmt = $usuario->read();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            include 'views/usuarios/index.php';
        } catch (Exception $e) {
            log_error('USUARIOS_INDEX_ERROR', $e->getMessage());
            flash_message('error', 'Erro ao carregar usuários: ' . $e->getMessage());
            include 'views/usuarios/index.php';
        }
    }
    
    public function create() 
    {
        // Verificar se é admin
        if ($_SESSION['user_level'] !== 'admin') {
            flash_message('error', 'Acesso negado. Apenas administradores podem criar usuários.');
            redirect('index.php?page=dashboard');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = new Usuario();
            
            // Sanitizar email
            $usuario->email = sanitize_input($_POST['email'] ?? '');
            
            // Verificar duplicata
            if (!empty($usuario->email) && $usuario->emailExists()) {
                flash_message('error', 'Email já cadastrado no sistema.');
                include 'views/usuarios/create.php';
                return;
            }
            
            // Preencher propriedades
            $usuario->nome = sanitize_input($_POST['nome'] ?? '');
            $senha = $_POST['senha'] ?? '';
            $senha = trim($senha);
            
            if (empty($senha)) {
                flash_message('error', 'Senha é obrigatória.');
                include 'views/usuarios/create.php';
                return;
            }
            
            $usuario->senha = password_hash($senha, PASSWORD_DEFAULT);
            $usuario->nivel = sanitize_input($_POST['nivel'] ?? 'usuario');
            $usuario->ativo = (int)($_POST['status'] ?? 1); // 1 = ativo, 0 = inativo

            try {
                if ($usuario->create()) {
                    log_activity('Usuário criado', "Novo usuário: {$usuario->nome} ({$usuario->email})");
                    flash_message('success', 'Usuário cadastrado com sucesso!');
                    redirect('index.php?page=usuarios');
                    exit;
                } else {
                    flash_message('error', 'Erro ao cadastrar usuário.');
                }
            } catch (Exception $e) {
                log_error('USUARIO_CREATE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao salvar usuário: ' . $e->getMessage());
            }
        }
        
        include 'views/usuarios/create.php';
    }
    
    public function edit() 
    {
        $id = (int)($_GET['id'] ?? 0);
        
        // Verificar se é admin ou se está editando próprio perfil
        if ($_SESSION['user_level'] !== 'admin' && $_SESSION['user_id'] != $id) {
            flash_message('error', 'Acesso negado.');
            redirect('index.php?page=dashboard');
            exit;
        }

        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=usuarios');
            exit;
        }

        $usuario = new Usuario();
        $usuario->id = $id;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sanitizar email
            $usuario->email = sanitize_input($_POST['email'] ?? '');
            
            // Verificar duplicata (exceto próprio)
            if (!empty($usuario->email)) {
                $exists = $this->emailExistsExcluding($usuario->email, $id);
                if ($exists) {
                    flash_message('error', 'Email já cadastrado no sistema.');
                    include 'views/usuarios/edit.php';
                    return;
                }
            }
            
            // Preencher propriedades
            $usuario->nome = sanitize_input($_POST['nome'] ?? '');
            
            // Só alterar senha se informada
            $senha = $_POST['senha'] ?? '';
            $senha = trim($senha);
            if (!empty($senha)) {
                $usuario->senha = password_hash($senha, PASSWORD_DEFAULT);
            }
            
            // Só admin pode alterar nível e status
            if ($_SESSION['user_level'] === 'admin') {
                $usuario->nivel = sanitize_input($_POST['nivel'] ?? 'usuario');
                $usuario->ativo = (int)($_POST['status'] ?? 1);
            }
            
            try {
                if ($usuario->update()) {
                    log_activity('Usuário atualizado', "Usuário editado: {$usuario->nome} ({$usuario->email})");
                    flash_message('success', 'Usuário atualizado com sucesso!');
                    redirect('index.php?page=usuarios');
                    exit;
                } else {
                    flash_message('error', 'Erro ao atualizar usuário.');
                }
            } catch (Exception $e) {
                log_error('USUARIO_UPDATE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao atualizar usuário: ' . $e->getMessage());
            }
        }
        
        if (!$usuario->readOne()) {
            flash_message('error', 'Usuário não encontrado.');
            redirect('index.php?page=usuarios');
            exit;
        }
        
        include 'views/usuarios/edit.php';
    }
    
    public function delete() 
    {
        if ($_SESSION['user_level'] !== 'admin') {
            flash_message('error', 'Acesso negado. Apenas administradores podem excluir usuários.');
            redirect('index.php?page=usuarios');
            exit;
        }
        
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=usuarios');
            exit;
        }

        // Não permitir excluir próprio usuário
        if ($id == $_SESSION['user_id']) {
            flash_message('error', 'Você não pode excluir seu próprio usuário.');
            redirect('index.php?page=usuarios');
            exit;
        }
        
        $usuario = new Usuario();
        $usuario->id = $id;
        
        // Buscar dados para log
        if ($usuario->readOne()) {
            $nome_usuario = $usuario->nome;
            $email_usuario = $usuario->email;
            
            try {
                if ($usuario->delete()) {
                    log_activity('Usuário excluído', "Usuário removido: {$nome_usuario} ({$email_usuario})");
                    flash_message('success', 'Usuário excluído com sucesso!');
                } else {
                    flash_message('error', 'Erro ao excluir usuário.');
                }
            } catch (Exception $e) {
                log_error('USUARIO_DELETE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao excluir usuário: ' . $e->getMessage());
            }
        } else {
            flash_message('error', 'Usuário não encontrado.');
        }
        
        redirect('index.php?page=usuarios');
        exit;
    }
    
    public function view() 
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=usuarios');
            exit;
        }

        // Verificar permissão
        if ($_SESSION['user_level'] !== 'admin' && $_SESSION['user_id'] != $id) {
            flash_message('error', 'Acesso negado.');
            redirect('index.php?page=dashboard');
            exit;
        }
        
        $usuario = new Usuario();
        $usuario->id = $id;
        
        if (!$usuario->readOne()) {
            flash_message('error', 'Usuário não encontrado.');
            redirect('index.php?page=usuarios');
            exit;
        }
        
        include 'views/usuarios/view.php';
    }
    
    public function perfil() 
    {
        $id = (int)($_SESSION['user_id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'Sessão expirada. Faça login novamente.');
            redirect('index.php?page=login');
            exit;
        }

        $usuario = new Usuario();
        $usuario->id = $id;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sanitizar email
            $usuario->email = sanitize_input($_POST['email'] ?? '');
            
            // Verificar duplicata (exceto próprio)
            if (!empty($usuario->email)) {
                $exists = $this->emailExistsExcluding($usuario->email, $id);
                if ($exists) {
                    flash_message('error', 'Email já cadastrado no sistema.');
                    include 'views/usuarios/perfil.php';
                    return;
                }
            }
            
            // Preencher propriedades
            $usuario->nome = sanitize_input($_POST['nome'] ?? '');
            
            // Só alterar senha se informada
            $senha = $_POST['senha'] ?? '';
            $senha = trim($senha);
            if (!empty($senha)) {
                $usuario->senha = password_hash($senha, PASSWORD_DEFAULT);
            }
            
            try {
                if ($usuario->updateProfile()) {
                    // Atualizar sessão
                    $_SESSION['user_name'] = $usuario->nome;
                    $_SESSION['user_email'] = $usuario->email;
                    
                    log_activity('Perfil atualizado', 'Usuário atualizou próprio perfil');
                    flash_message('success', 'Perfil atualizado com sucesso!');
                    redirect('index.php?page=usuarios&action=perfil');
                    exit;
                } else {
                    flash_message('error', 'Erro ao atualizar perfil.');
                }
            } catch (Exception $e) {
                log_error('PERFIL_UPDATE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao atualizar perfil: ' . $e->getMessage());
            }
        }
        
        if (!$usuario->readOne()) {
            flash_message('error', 'Erro ao carregar perfil.');
            redirect('index.php?page=dashboard');
            exit;
        }
        
        include 'views/usuarios/perfil.php';
    }

    /**
     * Verifica se email existe, exceto para o ID fornecido
     */
    private function emailExistsExcluding($email, $id) 
    {
        $usuario = new Usuario();
        $usuario->email = $email;
        $usuario->id = $id;
        return $usuario->emailExists();
    }
}