<?php
namespace App\Includes;

function log_page_access(string $page): void {
    $logFile = __DIR__ . '/acessos.log';
    $conteudo = "[" . date('Y-m-d H:i:s') . "] Página: {$page}\n";
    file_put_contents($logFile, $conteudo, FILE_APPEND);
}
