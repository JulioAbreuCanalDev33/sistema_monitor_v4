<?php

namespace App\Controllers;

use App\Models\VigilanciaVeicular;
use PDO;
use Exception;
use function App\Includes\log_error;
use function App\Includes\flash_message;
use function App\Includes\redirect;
use function App\Includes\sanitize_input;
use function App\Includes\upload_file;

class VigilanciaController 
{
    /**
     * Carregar vigilância atual para manter fotos
     */
    private function getCurrentVigilancia($id) 
    {
        $vigilancia = new VigilanciaVeicular();
        $vigilancia->id = $id;
        return $vigilancia->readOne() ? $vigilancia : null;
    }

    public function index() 
    {
        try {
            $vigilancia = new VigilanciaVeicular();
            $stmt = $vigilancia->read();
            $vigilancias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            include 'views/vigilancia/index.php';
        } catch (Exception $e) {
            log_error('VIGILANCIA_INDEX_ERROR', $e->getMessage());
            flash_message('error', 'Erro ao carregar vigilâncias: ' . $e->getMessage());
            include 'views/vigilancia/index.php';
        }
    }
    
    public function create() 
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            include 'views/vigilancia/create.php';
            return;
        }

        $vigilancia = new VigilanciaVeicular();

        // Preencher propriedades
        $this->fillVigilancia($vigilancia);

        // Upload de fotos
        $uploaded_photos = [];
        if (isset($_FILES['fotos_vigilancia']) && is_array($_FILES['fotos_vigilancia']['error'])) {
            for ($i = 0; $i < count($_FILES['fotos_vigilancia']['name']); $i++) {
                if ($_FILES['fotos_vigilancia']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['fotos_vigilancia']['name'][$i],
                        'type' => $_FILES['fotos_vigilancia']['type'][$i],
                        'tmp_name' => $_FILES['fotos_vigilancia']['tmp_name'][$i],
                        'error' => $_FILES['fotos_vigilancia']['error'][$i],
                        'size' => $_FILES['fotos_vigilancia']['size'][$i]
                    ];

                    $result = upload_file($file, ['jpg', 'jpeg', 'png']);
                    if ($result['success']) {
                        $uploaded_photos[] = $result['filename'];
                    } else {
                        flash_message('warning', 'Erro no upload da foto: ' . $result['message']);
                    }
                }
            }
        }

        // Salvar nomes das fotos
        if (!empty($uploaded_photos)) {
            $vigilancia->fotos_vigilancia = implode(',', $uploaded_photos);
        }

        try {
            if ($vigilancia->create()) {
                flash_message('success', 'Vigilância veicular criada com sucesso!');
                redirect('index.php?page=vigilancia');
                exit;
            } else {
                flash_message('error', 'Erro ao criar vigilância veicular.');
            }
        } catch (Exception $e) {
            log_error('VIGILANCIA_CREATE_ERROR', $e->getMessage());
            flash_message('error', 'Erro ao salvar vigilância: ' . $e->getMessage());
        }

        include 'views/vigilancia/create.php';
    }
    
    public function edit() 
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=vigilancia');
            exit;
        }

        $vigilancia = new VigilanciaVeicular();
        $vigilancia->id = $id;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Preencher propriedades
            $this->fillVigilancia($vigilancia);

            // Manter fotos existentes
            $current = $this->getCurrentVigilancia($id);
            $vigilancia->fotos_vigilancia = $current ? $current->fotos_vigilancia : '';

            // Upload de novas fotos
            $uploaded_photos = [];
            if (isset($_FILES['fotos_vigilancia']) && is_array($_FILES['fotos_vigilancia']['error'])) {
                for ($i = 0; $i < count($_FILES['fotos_vigilancia']['name']); $i++) {
                    if ($_FILES['fotos_vigilancia']['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['fotos_vigilancia']['name'][$i],
                            'type' => $_FILES['fotos_vigilancia']['type'][$i],
                            'tmp_name' => $_FILES['fotos_vigilancia']['tmp_name'][$i],
                            'error' => $_FILES['fotos_vigilancia']['error'][$i],
                            'size' => $_FILES['fotos_vigilancia']['size'][$i]
                        ];

                        $result = upload_file($file, ['jpg', 'jpeg', 'png']);
                        if ($result['success']) {
                            $uploaded_photos[] = $result['filename'];
                        } else {
                            flash_message('warning', 'Erro no upload da foto: ' . $result['message']);
                        }
                    }
                }
            }

            if (!empty($uploaded_photos)) {
                // Remover fotos antigas
                if ($current && $current->fotos_vigilancia) {
                    $old_photos = explode(',', $current->fotos_vigilancia);
                    foreach ($old_photos as $photo) {
                        $path = UPLOAD_PATH . $photo;
                        if (file_exists($path)) {
                            unlink($path);
                        }
                    }
                }
                $vigilancia->fotos_vigilancia = implode(',', $uploaded_photos);
            }

            try {
                if ($vigilancia->update()) {
                    flash_message('success', 'Vigilância veicular atualizada com sucesso!');
                    redirect('index.php?page=vigilancia');
                    exit;
                } else {
                    flash_message('error', 'Erro ao atualizar vigilância veicular.');
                }
            } catch (Exception $e) {
                log_error('VIGILANCIA_UPDATE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao atualizar vigilância: ' . $e->getMessage());
            }
        }

        if (!$vigilancia->readOne()) {
            flash_message('error', 'Vigilância veicular não encontrada.');
            redirect('index.php?page=vigilancia');
            exit;
        }

        include 'views/vigilancia/edit.php';
    }
    
    public function delete() 
    {
        if ($_SESSION['user_level'] !== 'admin') {
            flash_message('error', 'Acesso negado. Apenas administradores podem excluir registros.');
            redirect('index.php?page=vigilancia');
            exit;
        }
        
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=vigilancia');
            exit;
        }

        $vigilancia = new VigilanciaVeicular();
        $vigilancia->id = $id;

        if ($vigilancia->readOne()) {
            // Remover fotos
            if ($vigilancia->fotos_vigilancia) {
                $photos = explode(',', $vigilancia->fotos_vigilancia);
                foreach ($photos as $photo) {
                    $path = UPLOAD_PATH . $photo;
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }
            }

            try {
                if ($vigilancia->delete()) {
                    flash_message('success', 'Vigilância veicular excluída com sucesso!');
                } else {
                    flash_message('error', 'Erro ao excluir vigilância veicular.');
                }
            } catch (Exception $e) {
                log_error('VIGILANCIA_DELETE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao excluir vigilância: ' . $e->getMessage());
            }
        } else {
            flash_message('error', 'Vigilância veicular não encontrada.');
        }
        
        redirect('index.php?page=vigilancia');
        exit;
    }
    
    public function view() 
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=vigilancia');
            exit;
        }

        $vigilancia = new VigilanciaVeicular();
        $vigilancia->id = $id;
        
        if (!$vigilancia->readOne()) {
            flash_message('error', 'Vigilância veicular não encontrada.');
            redirect('index.php?page=vigilancia');
            exit;
        }
        
        include 'views/vigilancia/view.php';
    }

    /**
     * Preenche as propriedades da vigilância
     */
    private function fillVigilancia($vigilancia) 
    {
        $vigilancia->veiculo = sanitize_input($_POST['veiculo'] ?? '');
        $vigilancia->condutor = sanitize_input($_POST['condutor'] ?? '');
        $vigilancia->data_hora_inicio = $_POST['data_hora_inicio'] ?? null;
        $vigilancia->data_hora_fim = $_POST['data_hora_fim'] ?? null;
        $vigilancia->localizacao_inicial = sanitize_input($_POST['localizacao_inicial'] ?? '');
        $vigilancia->localizacao_final = sanitize_input($_POST['localizacao_final'] ?? '');
        $vigilancia->km_inicial = $_POST['km_inicial'] ?? null;
        $vigilancia->km_final = $_POST['km_final'] ?? null;
        $vigilancia->combustivel_inicial = $_POST['combustivel_inicial'] ?? null;
        $vigilancia->combustivel_final = $_POST['combustivel_final'] ?? null;
        $vigilancia->observacoes = sanitize_input($_POST['observacoes'] ?? '');
        $vigilancia->status_vigilancia = sanitize_input($_POST['status_vigilancia'] ?? 'Ativo');
        $vigilancia->responsavel = sanitize_input($_POST['responsavel'] ?? '');
    }
}