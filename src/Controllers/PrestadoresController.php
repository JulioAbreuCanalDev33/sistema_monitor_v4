<?php

namespace App\Controllers;

use App\Models\Prestador;
use App\Config\Database;
use PDO;
use Exception;
use function App\Includes\log_error;
use function App\Includes\flash_message;
use function App\Includes\redirect;
use function App\Includes\sanitize_input;
use function App\Includes\upload_file;

class PrestadoresController 
{
    /**
     * Carregar prestador atual para manter arquivos
     */
    private function getCurrentPrestador($id) 
    {
        $prestador = new Prestador();
        $prestador->id = $id;
        return $prestador->readOne() ? $prestador : null;
    }

    public function index() 
    {
        try {
            $prestador = new Prestador();
            $stmt = $prestador->read();
            $prestadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            include 'views/prestadores/index.php';
        } catch (Exception $e) {
            log_error('PRESTADORES_INDEX_ERROR', $e->getMessage());
            flash_message('error', 'Erro ao carregar prestadores: ' . $e->getMessage());
            include 'views/prestadores/index.php';
        }
    }
    
    public function create() 
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $prestador = new Prestador();
            
            // Sanitizar email e CPF
            $prestador->email_prestador = sanitize_input($_POST['email_prestador'] ?? '');
            $prestador->cpf_prestador = sanitize_input($_POST['cpf_prestador'] ?? '');
            
            // Verificar duplicatas
            if (!empty($prestador->email_prestador) && $prestador->emailExists()) {
                flash_message('error', 'Email já cadastrado no sistema.');
                include 'views/prestadores/create.php';
                return;
            }
            
            if (!empty($prestador->cpf_prestador) && $prestador->cpfExists()) {
                flash_message('error', 'CPF já cadastrado no sistema.');
                include 'views/prestadores/create.php';
                return;
            }
            
            // Preencher propriedades
            $this->fillPrestador($prestador);

            // Upload de documento
            if (isset($_FILES['documento_prestador']) && $_FILES['documento_prestador']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_file($_FILES['documento_prestador'], ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
                if ($upload_result['success']) {
                    $prestador->documento_prestador = $upload_result['filename'];
                } else {
                    flash_message('warning', 'Erro no upload do documento: ' . $upload_result['message']);
                }
            }

            // Upload de foto
            if (isset($_FILES['foto_prestador']) && $_FILES['foto_prestador']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_file($_FILES['foto_prestador'], ['jpg', 'jpeg', 'png']);
                if ($upload_result['success']) {
                    $prestador->foto_prestador = $upload_result['filename'];
                } else {
                    flash_message('warning', 'Erro no upload da foto: ' . $upload_result['message']);
                }
            }

            try {
                if ($prestador->create()) {
                    flash_message('success', 'Prestador cadastrado com sucesso!');
                    redirect('index.php?page=prestadores');
                    exit;
                } else {
                    flash_message('error', 'Erro ao cadastrar prestador.');
                }
            } catch (Exception $e) {
                log_error('PRESTADOR_CREATE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao salvar prestador: ' . $e->getMessage());
            }
        }
        
        include 'views/prestadores/create.php';
    }
    
    public function edit() 
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=prestadores');
            exit;
        }

        $prestador = new Prestador();
        $prestador->id = $id;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sanitizar
            $prestador->email_prestador = sanitize_input($_POST['email_prestador'] ?? '');
            $prestador->cpf_prestador = sanitize_input($_POST['cpf_prestador'] ?? '');

            // Verificar duplicatas (exceto o próprio)
            if (!empty($prestador->email_prestador)) {
                $exists = $this->emailExistsExcluding($prestador->email_prestador, $id);
                if ($exists) {
                    flash_message('error', 'Email já cadastrado no sistema.');
                    include 'views/prestadores/edit.php';
                    return;
                }
            }

            if (!empty($prestador->cpf_prestador)) {
                $exists = $this->cpfExistsExcluding($prestador->cpf_prestador, $id);
                if ($exists) {
                    flash_message('error', 'CPF já cadastrado no sistema.');
                    include 'views/prestadores/edit.php';
                    return;
                }
            }

            // Preencher dados
            $this->fillPrestador($prestador);

            // Manter arquivos existentes
            $current = $this->getCurrentPrestador($id);
            if ($current) {
                $prestador->documento_prestador = $current->documento_prestador;
                $prestador->foto_prestador = $current->foto_prestador;
            }

            // Upload de documento
            if (isset($_FILES['documento_prestador']) && $_FILES['documento_prestador']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_file($_FILES['documento_prestador'], ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
                if ($upload_result['success']) {
                    // Remover antigo
                    if ($current && $current->documento_prestador) {
                        $file_path = UPLOAD_PATH . $current->documento_prestador;
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }
                    $prestador->documento_prestador = $upload_result['filename'];
                } else {
                    flash_message('warning', 'Erro no upload do documento: ' . $upload_result['message']);
                }
            }

            // Upload de foto
            if (isset($_FILES['foto_prestador']) && $_FILES['foto_prestador']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_file($_FILES['foto_prestador'], ['jpg', 'jpeg', 'png']);
                if ($upload_result['success']) {
                    // Remover antigo
                    if ($current && $current->foto_prestador) {
                        $file_path = UPLOAD_PATH . $current->foto_prestador;
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }
                    $prestador->foto_prestador = $upload_result['filename'];
                } else {
                    flash_message('warning', 'Erro no upload da foto: ' . $upload_result['message']);
                }
            }

            try {
                if ($prestador->update()) {
                    flash_message('success', 'Prestador atualizado com sucesso!');
                    redirect('index.php?page=prestadores');
                    exit;
                } else {
                    flash_message('error', 'Erro ao atualizar prestador.');
                }
            } catch (Exception $e) {
                log_error('PRESTADOR_UPDATE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao atualizar prestador: ' . $e->getMessage());
            }
        }

        if (!$prestador->readOne()) {
            flash_message('error', 'Prestador não encontrado.');
            redirect('index.php?page=prestadores');
            exit;
        }

        include 'views/prestadores/edit.php';
    }
    
    public function delete() 
    {
        if ($_SESSION['user_level'] !== 'admin') {
            flash_message('error', 'Acesso negado. Apenas administradores podem excluir registros.');
            redirect('index.php?page=prestadores');
            exit;
        }
        
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=prestadores');
            exit;
        }

        $prestador = new Prestador();
        $prestador->id = $id;

        if ($prestador->readOne()) {
            // Remover arquivos
            if ($prestador->documento_prestador) {
                $path = UPLOAD_PATH . $prestador->documento_prestador;
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            if ($prestador->foto_prestador) {
                $path = UPLOAD_PATH . $prestador->foto_prestador;
                if (file_exists($path)) {
                    unlink($path);
                }
            }

            try {
                if ($prestador->delete()) {
                    flash_message('success', 'Prestador excluído com sucesso!');
                } else {
                    flash_message('error', 'Erro ao excluir prestador.');
                }
            } catch (Exception $e) {
                log_error('PRESTADOR_DELETE_ERROR', $e->getMessage());
                flash_message('error', 'Erro ao excluir prestador: ' . $e->getMessage());
            }
        } else {
            flash_message('error', 'Prestador não encontrado.');
        }
        
        redirect('index.php?page=prestadores');
        exit;
    }
    
    public function view() 
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            flash_message('error', 'ID inválido.');
            redirect('index.php?page=prestadores');
            exit;
        }

        $prestador = new Prestador();
        $prestador->id = $id;
        
        if (!$prestador->readOne()) {
            flash_message('error', 'Prestador não encontrado.');
            redirect('index.php?page=prestadores');
            exit;
        }
        
        include 'views/prestadores/view.php';
    }

    /**
     * Preenche as propriedades do prestador
     */
    private function fillPrestador($prestador) 
    {
        $prestador->nome_prestador = sanitize_input($_POST['nome_prestador'] ?? '');
        $prestador->equipes = sanitize_input($_POST['equipes'] ?? '');
        $prestador->servico_prestador = sanitize_input($_POST['servico_prestador'] ?? '');
        $prestador->rg_prestador = sanitize_input($_POST['rg_prestador'] ?? '');
        $prestador->telefone_1_prestador = sanitize_input($_POST['telefone_1_prestador'] ?? '');
        $prestador->telefone_2_prestador = sanitize_input($_POST['telefone_2_prestador'] ?? '');
        $prestador->cep_prestador = sanitize_input($_POST['cep_prestador'] ?? '');
        $prestador->endereco_prestador = sanitize_input($_POST['endereco_prestador'] ?? '');
        $prestador->numero_prestador = sanitize_input($_POST['numero_prestador'] ?? '');
        $prestador->bairro_prestador = sanitize_input($_POST['bairro_prestador'] ?? '');
        $prestador->cidade_prestador = sanitize_input($_POST['cidade_prestador'] ?? '');
        $prestador->estado_prestador = sanitize_input($_POST['estado_prestador'] ?? '');
        $prestador->observacao = sanitize_input($_POST['observacao'] ?? '');
        $prestador->codigo_do_banco = sanitize_input($_POST['codigo_do_banco'] ?? '');
        $prestador->pix_banco_prestadores = sanitize_input($_POST['pix_banco_prestadores'] ?? '');
        $prestador->titular_conta = sanitize_input($_POST['titular_conta'] ?? '');
        $prestador->tipo_de_conta = sanitize_input($_POST['tipo_de_conta'] ?? '');
        $prestador->agencia_prestadores = sanitize_input($_POST['agencia_prestadores'] ?? '');
        $prestador->digito_agencia_prestadores = sanitize_input($_POST['digito_agencia_prestadores'] ?? '');
        $prestador->conta_prestadores = sanitize_input($_POST['conta_prestadores'] ?? '');
        $prestador->digito_conta_prestadores = sanitize_input($_POST['digito_conta_prestadores'] ?? '');
    }

    /**
     * Verifica se email existe, exceto para o ID fornecido
     */
    private function emailExistsExcluding($email, $id) 
    {
        $prestador = new Prestador();
        $prestador->email_prestador = $email;
        $prestador->id = $id;
        return $prestador->emailExists();
    }

    /**
     * Verifica se CPF existe, exceto para o ID fornecido
     */
    private function cpfExistsExcluding($cpf, $id) 
    {
        $prestador = new Prestador();
        $prestador->cpf_prestador = $cpf;
        $prestador->id = $id;
        return $prestador->cpfExists();
    }
}