<?php
namespace App\Includes;

function log_page_access(string $page, string $action = 'index'): void {
    $logFile = __DIR__ . '/acessos.log';
    $conteudo = "[" . date('Y-m-d H:i:s') . "] Página: {$page} | Ação: {$action}\n";
    file_put_contents($logFile, $conteudo, FILE_APPEND);
}

function checkPagePermission(string $page): void {
    // Lógica de permissão
    $permissoesNegadas = ['configuracoes']; // exemplo

    if (in_array($page, $permissoesNegadas)) {
        header('HTTP/1.1 403 Forbidden');
        exit('Você não tem permissão para acessar esta página.');
    }
}
