<?php
// http://localhost:8888/debug.php?page=login

// ✅ ESSENCIAL: autoload do Composer
require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;

// Iniciar sessão
session_start();

echo "<h1>🔧 Debug do Sistema</h1>";

// === 1. Sessão ===
echo "<h2>🔐 Sessão Atual</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// === 2. Parâmetros GET ===
echo "<h2>📬 Parâmetros GET</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

// === 3. Página solicitada ===
$page = $_GET['page'] ?? 'login';
$action = $_GET['action'] ?? 'index';

echo "<h2>🎯 Rota Atual</h2>";
echo "Página: <strong>$page</strong><br>";
echo "Ação: <strong>$action</strong><br>";

// === 4. Verificar Controller ===
$controller_class = "App\\Controllers\\" . ucfirst($page) . "Controller";
$controller_file = __DIR__ . "/src/Controllers/" . ucfirst($page) . "Controller.php";

echo "<h2>🧪 Controller</h2>";
echo "Classe: $controller_class<br>";
echo "Arquivo: $controller_file<br>";
echo "Arquivo existe: " . (file_exists($controller_file) ? "✅ SIM" : "❌ NÃO") . "<br>";
echo "Classe existe: " . (class_exists($controller_class) ? "✅ SIM" : "❌ NÃO") . "<br>";

if (class_exists($controller_class)) {
    echo "🟢 Controlador pode ser instanciado<br>";
    
    if (method_exists($controller_class, $action)) {
        echo "🟢 Método '$action' existe<br>";
    } else {
        echo "🟡 Método '$action' não existe<br>";
    }
}

// === 5. Verificar Model ===
$model_class = "App\\Models\\Usuario"; // Para login
$model_file = __DIR__ . "/src/Models/Usuario.php";

echo "<h2>📦 Model</h2>";
echo "Classe: $model_class<br>";
echo "Arquivo: $model_file<br>";
echo "Arquivo existe: " . (file_exists($model_file) ? "✅ SIM" : "❌ NÃO") . "<br>";
echo "Classe existe: " . (class_exists($model_class) ? "✅ SIM" : "❌ NÃO") . "<br>";

// === 6. Verificar View ===
$view_file = __DIR__ . "/views/login.php"; // Não é index.php

echo "<h2>👁️ View</h2>";
echo "Arquivo: $view_file<br>";
echo "View existe: " . (file_exists($view_file) ? "✅ SIM" : "❌ NÃO") . "<br>";

// === 7. Conexão com Banco ===
echo "<h2>💾 Banco de Dados (SQLite)</h2>";
try {
    $database = new Database();
    $conn = $database->getConnection();
    echo "✅ Conexão com banco: OK<br>";

    // Listar tabelas
    $stmt = $conn->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "📌 Tabelas: " . implode(', ', $tables) . "<br>";

} catch (Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
}

echo "<hr><p><small>Debug gerado em: " . date('Y-m-d H:i:s') . "</small></p>";