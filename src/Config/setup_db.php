<?php

namespace App\Config;
Use PDOException;
Use PDO;

$dbFile = __DIR__ . "/sistema.db";

try {
    // Criar/conectar ao banco SQLite
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Se o banco for novo (tamanho 0), criar tabelas e dados iniciais
    if (filesize($dbFile) === 0) {
        $sql = <<<SQL
-- Criação das tabelas principais
CREATE TABLE usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    senha TEXT NOT NULL,
    nivel TEXT CHECK(nivel IN ('admin','usuario')) DEFAULT 'usuario',
    ativo INTEGER DEFAULT 1,
    ultimo_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE configuracoes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chave TEXT UNIQUE NOT NULL,
    valor TEXT,
    descricao TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE logs_sistema (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER,
    acao TEXT NOT NULL,
    tabela_afetada TEXT,
    registro_id INTEGER,
    detalhes TEXT,
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE tabela_prestadores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome_prestador TEXT NOT NULL,
    equipes TEXT,
    servico_prestador TEXT NOT NULL,
    cpf_prestador TEXT UNIQUE NOT NULL,
    rg_prestador TEXT,
    email_prestador TEXT UNIQUE NOT NULL,
    telefone_1_prestador TEXT NOT NULL,
    telefone_2_prestador TEXT,
    cep_prestador TEXT NOT NULL,
    endereco_prestador TEXT NOT NULL,
    numero_prestador TEXT NOT NULL,
    bairro_prestador TEXT NOT NULL,
    cidade_prestador TEXT NOT NULL,
    estado TEXT NOT NULL,
    observacao TEXT,
    documento_prestador TEXT,
    foto_prestador TEXT,
    codigo_do_banco TEXT NOT NULL,
    pix_banco_prestadores TEXT,
    titular_conta TEXT NOT NULL,
    tipo_de_conta TEXT NOT NULL,
    agencia_prestadores TEXT NOT NULL,
    digito_agencia_prestadores TEXT,
    conta_prestadores TEXT NOT NULL,
    digito_conta_prestadores TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clientes (
    id_cliente INTEGER PRIMARY KEY AUTOINCREMENT,
    nome_empresa TEXT,
    cnpj TEXT,
    contato TEXT,
    endereco TEXT,
    telefone TEXT
);

CREATE TABLE agentes (
    id_agente INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT,
    funcao TEXT,
    status TEXT CHECK(status IN ('Ativo','Inativo')) DEFAULT 'Ativo'
);

CREATE TABLE atendimentos (
    id_atendimento INTEGER PRIMARY KEY AUTOINCREMENT,
    solicitante TEXT,
    motivo TEXT,
    valor_patrimonial REAL,
    id_cliente INTEGER,
    conta TEXT,
    id_validacao TEXT,
    filial TEXT,
    ordem_servico TEXT,
    cep TEXT,
    estado TEXT,
    cidade TEXT,
    endereco TEXT,
    numero TEXT,
    latitude REAL,
    longitude REAL,
    agentes_aptos TEXT,
    id_agente INTEGER,
    equipe TEXT,
    responsavel TEXT,
    estabelecimento TEXT,
    hora_solicitada TIME,
    hora_local DATETIME,
    hora_saida DATETIME,
    status_atendimento TEXT CHECK(status_atendimento IN ('Em andamento','Finalizado')),
    tipo_de_servico TEXT CHECK(tipo_de_servico IN ('Ronda','Preservação')),
    tipos_de_dados TEXT,
    estabelecida_inicio TIME,
    estabelecida_fim TIME,
    indeterminado INTEGER DEFAULT 0,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE SET NULL,
    FOREIGN KEY (id_agente) REFERENCES agentes(id_agente) ON DELETE SET NULL
);

CREATE TABLE rondas_periodicas (
    id_ronda INTEGER PRIMARY KEY AUTOINCREMENT,
    id_atendimento INTEGER,
    quantidade_rondas INTEGER,
    data_final DATE,
    pagamento TEXT CHECK(pagamento IN ('Pago','Pendente')),
    contato_no_local TEXT CHECK(contato_no_local IN ('Sim','Não')),
    nome_local TEXT,
    funcao_local TEXT,
    verificado_fiacao TEXT CHECK(verificado_fiacao IN ('Sim','Não')),
    quadro_eletrico TEXT CHECK(quadro_eletrico IN ('Sim','Não')),
    verificado_portas_entradas TEXT CHECK(verificado_portas_entradas IN ('Sim','Não')),
    local_energizado TEXT CHECK(local_energizado IN ('Sim','Não')),
    sirene_disparada TEXT CHECK(sirene_disparada IN ('Sim','Não')),
    local_violado TEXT CHECK(local_violado IN ('Sim','Não')),
    observacao TEXT,
    FOREIGN KEY (id_atendimento) REFERENCES atendimentos(id_atendimento) ON DELETE CASCADE
);

CREATE TABLE fotos_atendimentos (
    id_foto INTEGER PRIMARY KEY AUTOINCREMENT,
    id_atendimento INTEGER,
    legenda TEXT,
    caminho_foto TEXT,
    data_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_atendimento) REFERENCES atendimentos(id_atendimento) ON DELETE CASCADE
);

CREATE TABLE ocorrencias_veiculares (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cliente TEXT,
    servico TEXT,
    id_validacao TEXT,
    valor_veicular REAL,
    cep TEXT,
    estado TEXT,
    cidade TEXT,
    solicitante TEXT,
    motivo TEXT,
    endereco_da_ocorrencia TEXT,
    numero TEXT,
    latitude REAL,
    longitude REAL,
    agentes_aptos TEXT,
    prestador TEXT,
    equipe TEXT,
    tipo_de_ocorrencia TEXT,
    data_hora_evento DATETIME,
    data_hora_deslocamento DATETIME,
    data_hora_transmissao DATETIME,
    data_hora_local DATETIME,
    data_hora_inicio_atendimento DATETIME,
    data_hora_fim_atendimento DATETIME,
    franquia_hora TIME,
    franquia_km REAL,
    km_inicial_atendimento REAL,
    km_final_atendimento REAL,
    total_horas_atendimento TIME,
    total_km_percorrido REAL,
    descricao_fatos TEXT,
    gastos_adicionais REAL
);

CREATE TABLE vigilancia_veicular (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    veiculo_foi_recuperado TEXT CHECK(veiculo_foi_recuperado IN ('Sim','Não')) NOT NULL,
    condutor_e_proprietario TEXT CHECK(condutor_e_proprietario IN ('Sim','Não')) NOT NULL,
    tipo_de_equipamento_embarcado TEXT,
    placa TEXT NOT NULL,
    renavam TEXT,
    cor TEXT,
    marca TEXT,
    modelo TEXT,
    cidade TEXT,
    dados_adicionais_veiculo TEXT,
    placa_carreta TEXT,
    renavam_carreta TEXT,
    cor_carreta TEXT,
    marca_carreta TEXT,
    modelo_carreta TEXT,
    cidade_carreta TEXT,
    dados_adicionais_carreta TEXT,
    nome_do_condutor TEXT,
    cpf_condutor TEXT,
    cnh_condutor TEXT,
    telefone_condutor TEXT,
    status_do_atendimento TEXT CHECK(status_do_atendimento IN ('Em andamento','Finalizado')) NOT NULL
);

CREATE TABLE fotos_vigilancia_veicular (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vigilancia_id INTEGER,
    legenda TEXT,
    foto TEXT,
    data_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vigilancia_id) REFERENCES vigilancia_veicular(id) ON DELETE CASCADE
);

-- Dados iniciais
INSERT INTO usuarios (nome, email, senha, nivel) VALUES 
('Administrador', 'admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT INTO configuracoes (chave, valor, descricao) VALUES 
('sistema_nome', 'Sistema de Monitoramento', 'Nome do sistema'),
('sistema_versao', '1.0.0', 'Versão do sistema'),
('tema_padrao', 'claro', 'Tema padrão do sistema'),
('max_upload_size', '5242880', 'Tamanho máximo de upload em bytes'),
('timezone', 'America/Sao_Paulo', 'Fuso horário do sistema');

INSERT INTO clientes (nome_empresa, cnpj, contato, endereco, telefone) VALUES
('Empresa ABC Ltda', '12.345.678/0001-90', 'João Silva', 'Rua das Flores, 123', '(11) 99999-9999'),
('Corporação XYZ S.A.', '98.765.432/0001-10', 'Maria Santos', 'Av. Principal, 456', '(11) 88888-8888');

INSERT INTO agentes (nome, funcao, status) VALUES
('Carlos Oliveira', 'Agente de Segurança', 'Ativo'),
('Ana Costa', 'Supervisora', 'Ativo'),
('Pedro Almeida', 'Agente de Campo', 'Ativo');
SQL;

        $pdo->exec($sql);
        echo "Banco criado com sucesso em: {$dbFile}\n";
    } else {
        echo "Banco já existe e está pronto para uso: {$dbFile}\n";
    }

} catch (PDOException $e) {
    die("Erro ao conectar/criar banco: " . $e->getMessage());
}
