<?php
session_start();
require 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Você precisa estar logado.']);
    exit;
}

$usuarioId = (int)$_SESSION['usuario_id'];
$isMestre  = !empty($_SESSION['is_mestre']) && $_SESSION['is_mestre'] == 1;

// Verifica se veio arquivo
if (!isset($_FILES['arquivo_ficha']) || $_FILES['arquivo_ficha']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['erro' => 'Erro ao enviar arquivo.']);
    exit;
}

$conteudo = file_get_contents($_FILES['arquivo_ficha']['tmp_name']);
if ($conteudo === false) {
    http_response_code(400);
    echo json_encode(['erro' => 'Não foi possível ler o arquivo.']);
    exit;
}

// Decodifica JSON
$dados = json_decode($conteudo, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($dados)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Arquivo JSON inválido.']);
    exit;
}

// Validação mínima
if (
    !isset($dados['cabecalho']['nome']) ||
    !isset($dados['status']['pv']) ||
    !isset($dados['status']['pe'])
) {
    http_response_code(400);
    echo json_encode(['erro' => 'JSON de ficha incompatível ou incompleto.']);
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

// Nome de personagem para exibição
$nomePersonagem = $metaNomePers ?: ($dados['cabecalho']['nome'] ?? 'Personagem sem nome');
$pv             = (int)$dados['status']['pv'];
$pe             = (int)$dados['status']['pe'];

// Remove _meta antes de salvar (mas ainda não vamos salvar, só deixar pronto)
if (isset($dados['_meta'])) {
    unset($dados['_meta']);
}

$dadosJsonParaBanco = json_encode($dados, JSON_UNESCAPED_UNICODE);
if ($dadosJsonParaBanco === false) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao preparar JSON.']);
    exit;
}

// Guarda o JSON e infos em sessão para a próxima etapa (confirm)
$tmpId = bin2hex(random_bytes(8)); // id temporário
if (!isset($_SESSION['import_buffer'])) {
    $_SESSION['import_buffer'] = [];
}
$_SESSION['import_buffer'][$tmpId] = [
    'usuario_id'       => $usuarioId,
    'nome_personagem'  => $nomePersonagem,
    'dados_json'       => $dadosJsonParaBanco,
    'meta_id'          => $metaId,
];

// Decide ação sugerida
$acaoSugerida   = 'criar_nova'; // default
$motivo         = '';
$fichaExiste    = false;
$proprietarioId = null;

if ($metaId !== null && $metaId > 0) {
    $stmt = $pdo->prepare("SELECT id, usuario_id FROM fichas WHERE id = ?");
    $stmt->execute([$metaId]);
    $existente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existente) {
        $fichaExiste    = true;
        $proprietarioId = (int)$existente['usuario_id'];

        if ($proprietarioId !== $usuarioId) {
            // ID já usado por outro usuário
            $acaoSugerida = 'criar_nova_por_outro_usuario';
            $motivo = 'Já existe ficha com este ID pertencendo a outro usuário. Uma nova ficha será criada para você.';
        } else {
            // ID usado pelo próprio usuário -> precisa de escolha
            $acaoSugerida = 'escolher'; // sobrescrever ou nova
            $motivo = 'Já existe uma ficha sua com este ID. Você pode sobrescrever ou criar uma nova.';
        }
    } else {
        // ID não existe mais
        $acaoSugerida = 'criar_nova_id_livre';
        $motivo = 'ID original não existe mais. Será criada uma nova ficha.';
    }
} else {
    $acaoSugerida = 'criar_nova_sem_id';
    $motivo = 'Backup sem ID original. Será criada uma nova ficha.';
}

// Resposta para o front
echo json_encode([
    'ok'            => true,
    'tmp_id'        => $tmpId,
    'acao_sugerida' => $acaoSugerida,
    'motivo'        => $motivo,
    'meta_id'       => $metaId,
    'nome_personagem' => $nomePersonagem,
    'pv'            => $pv,
    'pe'            => $pe,
]);