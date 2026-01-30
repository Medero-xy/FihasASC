<?php
session_start();
require 'config.php';

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit('Não autenticado.');
}

$usuarioId = (int)$_SESSION['usuario_id'];
$isMestre  = !empty($_SESSION['is_mestre']) && $_SESSION['is_mestre'] == 1;

// Pega ID da ficha via GET
$fichaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($fichaId <= 0) {
    http_response_code(400);
    exit('ID de ficha inválido.');
}

// Busca ficha
$stmt = $pdo->prepare("SELECT usuario_id, nome_personagem, dados_json FROM fichas WHERE id = ?");
$stmt->execute([$fichaId]);
$ficha = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ficha) {
    http_response_code(404);
    exit('Ficha não encontrada.');
}

// Permissão: dono ou mestre
if (!$isMestre && (int)$ficha['usuario_id'] !== $usuarioId) {
    http_response_code(403);
    exit('Você não tem permissão para exportar esta ficha.');
}

$nomePersonagem = $ficha['nome_personagem'] ?: 'ficha';
$slug = preg_replace('/[^a-z0-9]+/i', '_', $nomePersonagem);
$arquivo = "ficha_{$fichaId}_{$slug}.json";

// Decodifica o JSON da ficha
$dados = json_decode($ficha['dados_json'], true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($dados)) {
    http_response_code(500);
    exit('Erro ao ler os dados da ficha.');
}

// Adiciona metadados de export (NÃO será salvo no banco, só no arquivo)
$dados['_meta'] = [
    'id'              => $fichaId,
    'nome_personagem' => $nomePersonagem,
    'schema_version'  => 1,
    'exported_at'     => date('c'),
];

// Re-encode com pretty print pra ficar legível
$conteudoBackup = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if ($conteudoBackup === false) {
    http_response_code(500);
    exit('Erro ao gerar JSON de backup.');
}

// Envia como download
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$arquivo.'"');
echo $conteudoBackup;