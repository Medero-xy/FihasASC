<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// --- SALVAR FICHA (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recebe o JSON enviado pelo Javascript
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id_ficha = $input['id_ficha'] ?? null;
    $dados = $input['dados'] ?? null; // O objeto JSON da ficha inteira
    $nome_personagem = $dados['cabecalho']['nome'] ?? 'Sem Nome';

    if (!$id_ficha || !$dados) {
        echo json_encode(['erro' => 'Dados inválidos']);
        exit;
    }

    // Atualiza no banco (garantindo que a ficha pertence ao usuário logado)
    $stmt = $pdo->prepare("UPDATE fichas SET dados_json = ?, nome_personagem = ?, data_atualizacao = CURRENT_TIMESTAMP WHERE id = ? AND usuario_id = ?");
    
    if ($stmt->execute([json_encode($dados), $nome_personagem, $id_ficha, $usuario_id])) {
        echo json_encode(['sucesso' => true]);
    } else {
        echo json_encode(['erro' => 'Erro ao salvar no banco']);
    }
}

// --- CARREGAR FICHA (GET) ---
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id_ficha = $_GET['id'] ?? null;

    if (!$id_ficha) {
        echo json_encode(['erro' => 'ID não fornecido']);
        exit;
    }

    // Busca a ficha no banco
    $stmt = $pdo->prepare("SELECT dados_json FROM fichas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id_ficha, $usuario_id]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        // Retorna o JSON que estava salvo no banco
        echo $resultado['dados_json']; 
    } else {
        http_response_code(404);
        echo json_encode(['erro' => 'Ficha não encontrada']);
    }
}
?>