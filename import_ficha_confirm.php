<?php
session_start();
require 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Você precisa estar logado.']);
    exit;
}

$usuarioId = (int)$_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);
    exit;
}

$tmpId = $_POST['tmp_id'] ?? null;
$acao  = $_POST['acao']  ?? null; // 'criar' ou 'sobrescrever'

if (!$tmpId || !$acao) {
    http_response_code(400);
    echo json_encode(['erro' => 'Parâmetros inválidos.']);
    exit;
}

if (empty($_SESSION['import_buffer'][$tmpId])) {
    http_response_code(400);
    echo json_encode(['erro' => 'Sessão de importação não encontrada ou expirada.']);
    exit;
}

$buf = $_SESSION['import_buffer'][$tmpId];
// Opcional: garantir que o mesmo usuário está finalizando
if ($buf['usuario_id'] !== $usuarioId) {
    http_response_code(403);
    echo json_encode(['erro' => 'Sessão de importação não pertence a este usuário.']);
    exit;
}

// Dados prontos para banco
$nomePersonagem = $buf['nome_personagem'];
$dadosJson      = $buf['dados_json'];
$metaId         = $buf['meta_id'];

unset($_SESSION['import_buffer'][$tmpId]); // limpa buffer desta importação

// Ação escolhida
if ($acao === 'criar') {
    $stmt = $pdo->prepare("INSERT INTO fichas (usuario_id, nome_personagem, dados_json, data_atualizacao) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
    $stmt->execute([$usuarioId, $nomePersonagem, $dadosJson]);

    echo json_encode(['ok' => true, 'mensagem' => 'Nova ficha criada a partir do backup.']);
    exit;
}

if ($acao === 'sobrescrever') {
    if ($metaId === null || $metaId <= 0) {
        http_response_code(400);
        echo json_encode(['erro' => 'Não há ID original para sobrescrever.']);
        exit;
    }

    // Confirma se a ficha com esse ID ainda é do mesmo usuário
    $stmt = $pdo->prepare("SELECT id, usuario_id FROM fichas WHERE id = ?");
    $stmt->execute([$metaId]);
    $existente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existente) {
        // Se já sumiu, cai pra criar nova
        $stmtIns = $pdo->prepare("INSERT INTO fichas (usuario_id, nome_personagem, dados_json, data_atualizacao) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        $stmtIns->execute([$usuarioId, $nomePersonagem, $dadosJson]);
        echo json_encode(['ok' => true, 'mensagem' => 'Ficha original não existe mais; foi criada uma nova.']);
        exit;
    }

    if ((int)$existente['usuario_id'] !== $usuarioId) {
        // Segurança extra: nunca sobrescrever ficha de outro
        $stmtIns = $pdo->prepare("INSERT INTO fichas (usuario_id, nome_personagem, dados_json, data_atualizacao) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        $stmtIns->execute([$usuarioId, $nomePersonagem, $dadosJson]);
        echo json_encode(['ok' => true, 'mensagem' => 'ID pertence a outro usuário; foi criada nova ficha para você.']);
        exit;
    }

    // Atualiza ficha existente
    $stmtUp = $pdo->prepare("UPDATE fichas SET nome_personagem = ?, dados_json = ?, data_atualizacao = CURRENT_TIMESTAMP WHERE id = ?");
    $stmtUp->execute([$nomePersonagem, $dadosJson, $metaId]);

    echo json_encode(['ok' => true, 'mensagem' => 'Ficha existente sobrescrita com sucesso.']);
    exit;
}

http_response_code(400);
echo json_encode(['erro' => 'Ação inválida.']);