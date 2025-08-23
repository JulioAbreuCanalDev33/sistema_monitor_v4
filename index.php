<?php
/**
 * Arquivo principal de roteamento
 */

// 1. Iniciar sessão IMEDIATAMENTE, no escopo global
session_start();
var_dump($_SESSION);
// 2. Autoload Composer
require_once __DIR__ . '/vendor/autoload.php';

// 3. Definições gerais
define('BASE_URL', 'http://localhost/sistema_monitoramento/');
define('UPLOAD_PATH', __DIR__ . '/assets/uploads/');

// 4. Incluir funções e logs
require_once __DIR__ . '/src/Includes/log_page_access.php';
require_once __DIR__ . '/src/Includes/functions.php'; // Adicione isso se ainda não estiver incluído

use function App\Includes\log_page_access;
use function App\Includes\checkPagePermission;

// 5. Log de acesso
$page = $_GET['page'] ?? 'login';
$action = $_GET['action'] ?? 'index';

log_page_access($page, $action);

// 6. Verificar login (exceto para login)
if ($page !== 'login' && !isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

// 7. Verificar permissão (se logado)
if (isset($_SESSION['user_id'])) {
    checkPagePermission($page);
}

// 8. Roteamento principal
switch ($page) {
    case 'login':
        $controller = new \App\Controllers\LoginController();
        
        if ($action === 'logout') {
            $controller->logout();
        } elseif ($action === 'authenticate') {
            $controller->authenticate();
            exit;
        } else {
            $controller->index();
        }
        break;

    case 'dashboard':
        $controller = new \App\Controllers\DashboardController();
        $controller->index();
        break;

    case 'usuarios':
        $controller = new \App\Controllers\UsuariosController();
        switch ($action) {
            case 'create': $controller->create(); break;
            case 'edit': $controller->edit(); break;
            case 'view': $controller->view(); break;
            case 'delete': $controller->delete(); break;
            case 'perfil': $controller->perfil(); break;
            default: $controller->index();
        }
        break;

    case 'clientes':
        $controller = new \App\Controllers\ClientesController();
        switch ($action) {
            case 'create': $controller->create(); break;
            case 'edit': $controller->edit(); break;
            case 'view': $controller->view(); break;
            case 'delete': $controller->delete(); break;
            default: $controller->index();
        }
        break;

    case 'agentes':
        $controller = new \App\Controllers\AgentesController();
        switch ($action) {
            case 'create': $controller->create(); break;
            case 'edit': $controller->edit(); break;
            case 'view': $controller->view(); break;
            case 'delete': $controller->delete(); break;
            default: $controller->index();
        }
        break;

    case 'prestadores':
        $controller = new \App\Controllers\PrestadoresController();
        switch ($action) {
            case 'create': $controller->create(); break;
            case 'edit': $controller->edit(); break;
            case 'view': $controller->view(); break;
            case 'delete': $controller->delete(); break;
            default: $controller->index();
        }
        break;

    case 'atendimentos':
        $controller = new \App\Controllers\AtendimentosController();
        switch ($action) {
            case 'create': $controller->create(); break;
            case 'edit': $controller->edit(); break;
            case 'view': $controller->view(); break;
            case 'delete': $controller->delete(); break;
            default: $controller->index();
        }
        break;

    case 'ocorrencias':
        $controller = new \App\Controllers\OcorrenciasController();
        switch ($action) {
            case 'create': $controller->create(); break;
            case 'edit': $controller->edit(); break;
            case 'view': $controller->view(); break;
            case 'delete': $controller->delete(); break;
            default: $controller->index();
        }
        break;

    case 'vigilancia':
        $controller = new \App\Controllers\VigilanciaController();
        switch ($action) {
            case 'create': $controller->create(); break;
            case 'edit': $controller->edit(); break;
            case 'view': $controller->view(); break;
            case 'delete': $controller->delete(); break;
            default: $controller->index();
        }
        break;

    case 'relatorios':
        $controller = new \App\Controllers\RelatoriosController();
        switch ($action) {
            case 'generate': $controller->generate(); break;
            default: $controller->index();
        }
        break;

    case 'logs':
        $controller = new \App\Controllers\LogsController();
        switch ($action) {
            case 'clean': $controller->clean(); break;
            case 'export': $controller->export(); break;
            case 'stats': $controller->stats(); break;
            case 'test': $controller->test(); break;
            default: $controller->index();
        }
        break;

    default:
        include 'views/404.php';
        break;
}