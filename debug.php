<?php

use App\Config\Database;


// Arquivo de debug para testar roteamento
session_start();

echo "<h1>Debug do Sistema</h1>";

// Verificar sessão
echo "<h2>Sessão:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Verificar parâmetros GET
echo "<h2>Parâmetros GET:</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

// Verificar se arquivos existem
$page = $_GET['page'] ?? 'clientes';
$controller_file = "controllers/" . ucfirst($page) . "Controller.php";

echo "<h2>Verificação de Arquivos:</h2>";
echo "Página solicitada: " . $page . "<br>";
echo "Arquivo do controlador: " . $controller_file . "<br>";
echo "Arquivo existe: " . (file_exists($controller_file) ? "SIM" : "NÃO") . "<br>";

if (file_exists($controller_file)) {
    require_once $controller_file;
    $controller_class = ucfirst($page) . "Controller";
    echo "Classe do controlador: " . $controller_class . "<br>";
    echo "Classe existe: " . (class_exists($controller_class) ? "SIM" : "NÃO") . "<br>";
    
    if (class_exists($controller_class)) {
        $controller = new $controller_class();
        echo "Controlador instanciado: SIM<br>";
        
        $action = $_GET['action'] ?? 'index';
        echo "Ação solicitada: " . $action . "<br>";
        echo "Método existe: " . (method_exists($controller, $action) ? "SIM" : "NÃO") . "<br>";
    }
}

// Verificar modelo
$model_file = "models/" . ucfirst($page) . ".php";
if ($page == 'clientes') {
    $model_file = "models/Cliente.php";
}

echo "<br>Arquivo do modelo: " . $model_file . "<br>";
echo "Modelo existe: " . (file_exists($model_file) ? "SIM" : "NÃO") . "<br>";

// Verificar view
$view_file = "views/" . $page . "/index.php";
echo "<br>Arquivo da view: " . $view_file . "<br>";
echo "View existe: " . (file_exists($view_file) ? "SIM" : "NÃO") . "<br>";

// Testar conexão com banco
echo "<h2>Teste de Conexão com Banco:</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "Conexão com banco: OK<br>";
        
        // Testar se tabela existe
        $stmt = $conn->query("SHOW TABLES LIKE 'clientes'");
        if ($stmt->rowCount() > 0) {
            echo "Tabela 'clientes' existe: SIM<br>";
            
            // Contar registros
            $stmt = $conn->query("SELECT COUNT(*) as total FROM clientes");
            $result = $stmt->fetch();
            echo "Total de clientes: " . $result['total'] . "<br>";
        } else {
            echo "Tabela 'clientes' existe: NÃO<br>";
        }
    } else {
        echo "Conexão com banco: FALHOU<br>";
    }
} catch (Exception $e) {
    echo "Erro na conexão: " . $e->getMessage() . "<br>";
}

// Verificar includes
echo "<h2>Verificação de Includes:</h2>";
$includes = ['includes/functions.php', 'includes/permissions.php', 'includes/logger.php'];
foreach ($includes as $include) {
    echo $include . ": " . (file_exists($include) ? "OK" : "FALTANDO") . "<br>";
}
?>

