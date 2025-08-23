<?php

namespace App\Controllers;

use App\Models\Usuario;
use App\Config\Database;
use Exception;
use function App\Includes\flash_message;
use function App\Includes\redirect;

class LoginController 
{
    public function index() 
    {
        if (isset($_SESSION['user_id'])) {
            redirect('index.php?page=dashboard');
        }
        include 'views/login.php';
    }
    
    public function authenticate() 
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=login');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $senha = trim($senha);

        if (empty($email) || empty($senha)) {
            flash_message('error', 'Email e senha são obrigatórios');
            redirect('index.php?page=login');
            exit;
        }
        
        $usuario = new Usuario();
        
        if ($usuario->login($email, $senha)) {
            $_SESSION['user_id'] = $usuario->id;
            $_SESSION['user_name'] = $usuario->nome;
            $_SESSION['user_email'] = $usuario->email;
            $_SESSION['user_level'] = $usuario->nivel;
            
            $this->logAction('login', 'usuarios', $usuario->id);
            
            flash_message('success', 'Login realizado com sucesso!');
            redirect('index.php?page=dashboard');
            exit;
        } else {
            flash_message('error', 'Email ou senha incorretos');
            redirect('index.php?page=login');
            exit;
        }
    }
    
    public function logout() 
    {
        if (isset($_SESSION['user_id'])) {
            $this->logAction('logout', 'usuarios', $_SESSION['user_id']);
        }
        
        session_destroy();
        flash_message('success', 'Logout realizado com sucesso!');
        redirect('index.php?page=login');
        exit;
    }
    
    private function logAction($acao, $tabela, $registro_id) 
    {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "INSERT INTO logs_sistema (usuario_id, acao, tabela_afetada, registro_id, ip_address, user_agent) 
                      VALUES (:usuario_id, :acao, :tabela, :registro_id, :ip, :user_agent)";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':usuario_id', $_SESSION['user_id']);
            $stmt->bindParam(':acao', $acao);
            $stmt->bindParam(':tabela', $tabela);
            $stmt->bindParam(':registro_id', $registro_id);
            $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
            $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao logar ação: " . $e->getMessage());
        }
    }
}