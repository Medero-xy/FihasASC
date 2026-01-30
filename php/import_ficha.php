<?php
session_start();
require 'config.php';

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    echo "<script>alert('Você precisa estar logado para importar fichas.'); window.location.href='../index.html';</script>";
    exit;
}

$usuarioId = (int)$_SESSION['usuario_id'];
$isMestre  = !empty($_SESSION['is_mestre']) && $_SESSION['is_mestre'] == 1;

// Verifica se veio arquivo
if (!isset($_FILES['arquivo_ficha']) || $_FILES['arquivo_ficha']['error'] !== UPLOAD_ERR_OK) {
    echo "<script>alert('Erro ao enviar arquivo.'); window.location.href='../dashboard.php';</script>";
    exit;
}

$conteudo = file_get_contents($_FILES['arquivo_ficha']['tmp_name']);
if ($conteudo === false) {
    echo "<script>alert('Não foi possível ler o arquivo.'); window.location.href='../dashboard.php';</script>";
    exit;
}

// Decodifica JSON
$dados = json_decode($conteudo, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($dados)) {
    echo "<script>alert('Arquivo JSON inválido.'); window.location.href='../dashboard.php';</script>";
    exit;
}

// =========================
// Validação básica de campos
// =========================
if (
    !isset($dados['cabecalho']['nome']) ||
    !isset($dados['status']['pv']) ||
    !isset($dados['status']['pe'])
) {
    echo "<script>alert('JSON de ficha incompatível ou incompleto.'); window.location.href='../dashboard.php';</script>";
    exit;
}

// Lê metadados, se existirem
$metaId       = null;
$metaNomePers = null;
if (isset($dados['_meta']) && is_array($dados['_meta'])) {
    if (isset($dados['_meta']['id'])) {
        $metaId = (int)$dados['_meta']['id'];
    }
    if (isset($dados['_meta']['nome_personagem'])) {
        $metaNomePers = $dados['_meta']['nome_personagem'];
    }
}

// Nome do personagem para a tabela
$nomePersonagem = $metaNomePers ?: ($dados['cabecalho']['nome'] ?? 'Personagem sem nome');

// Flag de sobrescrever vindo do modal
$sobrescrever = isset($_POST['sobrescrever']) && $_POST['sobrescrever'] === '1';

// Remove _meta antes de salvar no banco, pra manter compatibilidade
if (isset($dados['_meta'])) {
    unset($dados['_meta']);
}

$dadosJsonParaBanco = json_encode($dados, JSON_UNESCAPED_UNICODE);
if ($dadosJsonParaBanco === false) {
    echo "<script>alert('Erro ao preparar JSON para salvar no banco.'); window.location.href='../dashboard.php';</script>";
    exit;
}

// Caso 1: não tem metaId -> sempre cria nova
if ($metaId === null || $metaId <= 0) {
    $stmt = $pdo->prepare("INSERT INTO fichas (usuario_id, nome_personagem, dados_json, data_atualizacao) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
    $stmt->execute([$usuarioId, $nomePersonagem, $dadosJsonParaBanco]);

    echo "<script>alert('Ficha importada como nova (sem ID original).'); window.location.href='../dashboard.php';</script>";
    exit;
}

// Caso 2: tem metaId -> ver se existe ficha com esse id
$stmt = $pdo->prepare("SELECT id, usuario_id FROM fichas WHERE id = ?");
$stmt->execute([$metaId]);
$existente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$existente) {
    // 2a) Não existe ficha com esse id -> cria nova
    $stmtIns = $pdo->prepare("INSERT INTO fichas (usuario_id, nome_personagem, dados_json, data_atualizacao) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
    $stmtIns->execute([$usuarioId, $nomePersonagem, $dadosJsonParaBanco]);

    echo "<script>alert('Ficha importada como nova (ID original não existe mais).'); window.location.href='../dashboard.php';</script>";
    exit;
}

// 2b) Existe ficha com esse id mas é de outro usuário -> cria nova
if ((int)$existente['usuario_id'] !== $usuarioId) {
    $stmtIns = $pdo->prepare("INSERT INTO fichas (usuario_id, nome_personagem, dados_json, data_atualizacao) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
    $stmtIns->execute([$usuarioId, $nomePersonagem, $dadosJsonParaBanco]);

    echo "<script>alert('Já existe ficha com este ID para outro usuário; foi criada uma nova para você.'); window.location.href='../dashboard.php';</script>";
    exit;
}

// 2c) Existe ficha com esse id e é do MESMO usuário
if ($sobrescrever) {
    // Atualiza a ficha existente
    $stmtUp = $pdo->prepare("UPDATE fichas SET nome_personagem = ?, dados_json = ?, data_atualizacao = CURRENT_TIMESTAMP WHERE id = ?");
    $stmtUp->execute([$nomePersonagem, $dadosJsonParaBanco, $metaId]);

    echo "<script>alert('Ficha existente atualizada (sobrescrita).'); window.location.href='../dashboard.php';</script>";
    exit;
} else {
    // Cria nova
    $stmtIns = $pdo->prepare("INSERT INTO fichas (usuario_id, nome_personagem, dados_json, data_atualizacao) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
    $stmtIns->execute([$usuarioId, $nomePersonagem, $dadosJsonParaBanco]);

    echo "<script>alert('Já existia ficha com este ID. Uma nova ficha foi criada.'); window.location.href='../dashboard.php';</script>";
    exit;
}