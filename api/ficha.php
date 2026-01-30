<?php
require '../php/config.php';

header('Content-Type: application/json; charset=utf-8');

// Token simples para proteger o endpoint
$TOKEN_ESPERADO = 'COLOQUE_AQUI_UM_TOKEN_BEM_GRANDE'; // troque por algo forte

$token = $_GET['token'] ?? '';
if ($token !== $TOKEN_ESPERADO) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'erro' => 'forbidden']);
    exit;
}

// id da ficha
$fichaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($fichaId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'id_invalido']);
    exit;
}

$stmt = $pdo->prepare("SELECT nome_personagem, dados_json FROM fichas WHERE id = ?");
$stmt->execute([$fichaId]);
$ficha = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ficha) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'erro' => 'nao_encontrada']);
    exit;
}

$dados = json_decode($ficha['dados_json'], true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($dados)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'json_invalido']);
    exit;
}

$nome = $dados['cabecalho']['nome'] ?? $ficha['nome_personagem'] ?? 'SemNome';
$status = $dados['status'] ?? [];
$pv = isset($status['pv']) ? (int)$status['pv'] : 0;
$pe = isset($status['pe']) ? (int)$status['pe'] : 0;

echo json_encode([
    'ok'              => true,
    'id'              => $fichaId,
    'nome_personagem' => $nome,
    'pv'              => $pv,
    'pe'              => $pe,
]);