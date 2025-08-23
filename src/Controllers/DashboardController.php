<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class DashboardController 
{
    public function index() 
    {
        try {
            $stats = $this->getStatistics();
        } catch (\Exception $e) {
            // Em caso de erro, inicializa com zeros
            error_log("DashboardController::getStatistics() error: " . $e->getMessage());
            $stats = [
                'total_atendimentos'     => 0,
                'atendimentos_andamento' => 0,
                'total_ocorrencias'      => 0,
                'total_vigilancia'       => 0,
                'total_prestadores'      => 0,
                'total_clientes'         => 0,
                'atendimentos_mes'       => []
            ];
        }

        include 'views/dashboard.php';
    }
    
    /**
     * Retorna estatísticas para o dashboard (compatível com SQLite)
     */
    private function getStatistics() 
    {
        $database = new Database();
        $conn = $database->getConnection();
        
        $stats = [];
        
        // Total de atendimentos
        $query = "SELECT COUNT(*) as total FROM atendimentos";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['total_atendimentos'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Atendimentos em andamento
        $query = "SELECT COUNT(*) as total FROM atendimentos WHERE status_atendimento = 'Em andamento'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['atendimentos_andamento'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de ocorrências veiculares
        $query = "SELECT COUNT(*) as total FROM ocorrencias_veiculares";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['total_ocorrencias'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de vigilância veicular
        $query = "SELECT COUNT(*) as total FROM vigilancia_veicular";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['total_vigilancia'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de prestadores
        $query = "SELECT COUNT(*) as total FROM tabela_prestadores";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['total_prestadores'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de clientes
        $query = "SELECT COUNT(*) as total FROM clientes";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['total_clientes'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Atendimentos por mês (últimos 6 meses) - SQLite version
        $query = "SELECT 
                    strftime('%Y-%m', hora_local) as mes, 
                    COUNT(*) as total 
                  FROM atendimentos 
                  WHERE hora_local >= datetime('now', '-6 months')
                  GROUP BY strftime('%Y-%m', hora_local)
                  ORDER BY mes";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['atendimentos_mes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
}