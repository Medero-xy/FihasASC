<?php
session_start();
require 'php/config.php';

// 1. SEGURANÇA
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$nome_usuario = $_SESSION['usuario_nome'];

// 2. LÓGICA: Criar Nova Ficha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_ficha'])) {
    $nome_padrao = "Novo Personagem";
    $json_padrao = '{"cabecalho": {"nome": "Novo Personagem"}}'; 
    
    $stmt = $pdo->prepare("INSERT INTO fichas (usuario_id, nome_personagem, dados_json) VALUES (?, ?, ?)");
    if ($stmt->execute([$usuario_id, $nome_padrao, $json_padrao])) {
        header("Location: dashboard.php");
        exit;
    }
}

// 3. LEITURA: Buscar fichas
$stmt = $pdo->prepare("SELECT id, nome_personagem, data_atualizacao FROM fichas WHERE usuario_id = ? ORDER BY id DESC");
$stmt->execute([$usuario_id]);
$fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Personagens - Ascensão</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="img/favicon.png">
    
    <style>
        /* ESTILOS ESPECÍFICOS DO DASHBOARD (Que não estão no style.css global) */

        @font-face {
            font-family: 'Jujutsu Kaisen';
            src: url('../fonts/Jujutsu Kaisen.ttf') format('truetype');
        }
        @font-face {
            font-family: 'Girassol';
            src: url('../fonts/Girassol-Regular.ttf') format('truetype');
        }
        
        body {
            /* Garante alinhamento ao topo, diferente do login que é centrado */
            display: block; 
            height: auto;
            min-height: 100vh;
            font-family: var(--fonte-texto);
            background-color: var(--cor-fundo);
            /* Fundo degradê sutil */
            background-image: radial-gradient(circle at center, #1a0505 0%, #000000 100%);
        }

        :root {
    --cor-fundo: #0a0a0a;
    --cor-painel: #141414;
    --cor-destaque: #b91c1c; /* Vermelho Neon */
    --fonte-titulo: 'Jujutsu Kaisen', sans-serif;
    --fonte-texto: 'Girassol', sans-serif;


}

        .dash-header {
            width: 100%;
            padding: 20px 40px;
            background: rgba(20, 20, 20, 0.95);
            border-bottom: 1px solid var(--cor-borda);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(5px);
        }

        .brand-text {
            font-family: var(--fonte-titulo);
            font-size: 1.8rem;
            color: var(--cor-destaque);
            text-shadow: 0 0 10px rgba(185, 28, 28, 0.5);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .welcome-text { 
            font-family: var(--fonte-texto); 
            font-size: 1.2rem;
            color: #ccc;
        }

        .btn-logout {
            background: transparent;
            border: 1px solid var(--cor-borda);
            color: var(--cor-destaque);
            padding: 8px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-family: var(--fonte-titulo);
            font-size: 0.9rem;
            transition: 0.3s;
        }
        .btn-logout:hover { 
            background: var(--cor-destaque); 
            color: white; 
            box-shadow: 0 0 15px var(--cor-destaque);
            border-color: var(--cor-destaque);
        }

        .dash-container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
            padding-bottom: 40px;
        }

        .section-title {
            font-family: var(--fonte-titulo);
            font-size: 2rem;
            color: white;
            border-bottom: 2px solid var(--cor-borda);
            padding-bottom: 10px;
            margin-bottom: 30px;
        }

        /* GRID DE FICHAS */
        .grid-fichas {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .card-ficha {
            background: var(--cor-painel);
            border: 1px solid var(--cor-borda);
            border-radius: 8px;
            padding: 25px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 180px;
            position: relative;
            overflow: hidden;
        }

        /* Efeito de brilho vermelho ao passar o mouse */
        .card-ficha:hover {
            border-color: var(--cor-destaque);
            box-shadow: 0 0 20px rgba(185, 28, 28, 0.2);
            transform: translateY(-5px);
            background: #1a0505;
        }

        .card-title {
            font-family: var(--fonte-titulo);
            font-size: 1.4rem;
            color: white;
            margin-bottom: 10px;
            z-index: 2;
        }

        .card-ficha:hover .card-title {
            color: var(--cor-destaque);
            text-shadow: 0 0 5px black;
        }

        .card-date {
            font-family: var(--fonte-texto);
            font-size: 0.9rem;
            color: #666;
            margin-top: auto;
            z-index: 2;
        }

        /* Decoração de fundo do card (Opcional) */
        .card-ficha::after {
            content: '';
            position: absolute;
            bottom: -50px;
            right: -50px;
            width: 100px;
            height: 100px;
            background: var(--cor-destaque);
            opacity: 0.1;
            border-radius: 50%;
            filter: blur(40px);
            transition: 0.5s;
        }
        .card-ficha:hover::after {
            opacity: 0.3;
            transform: scale(1.5);
        }

        /* Botão de Nova Ficha */
        .btn-new {
            background: transparent;
            border: 2px dashed #333;
            color: #444;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 4rem;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-new:hover {
            border-color: var(--cor-destaque);
            color: var(--cor-destaque);
            background: rgba(185, 28, 28, 0.05);
            box-shadow: 0 0 15px rgba(185, 28, 28, 0.1) inset;
        }
    </style>
</head>
<body>


    <header class="dash-header">
        <div class="brand-text">ASCENSÃO</div>

<?php if (!empty($_SESSION['is_mestre']) && $_SESSION['is_mestre'] == 1): ?>
    <button class="btn btn-second" onclick="window.location.href='mestre.php'">
        Área do Mestre
    </button>
<?php endif; ?>

        <div class="user-info">
            <span class="welcome-text">Feiticeiro(a): <?php echo htmlspecialchars($nome_usuario); ?></span>
            <a href="php/logout.php" class="btn-logout">SAIR</a>
        </div>
    </header>

    <div class="dash-container">
        <h2 class="section-title">SEUS PERSONAGENS</h2>
        
        
        <div class="grid-fichas">
            <form method="POST" style="display: contents;">
                <button type="submit" name="nova_ficha" class="card-ficha btn-new" title="Criar Nova Ficha">+</button>
            </form>

            <?php foreach ($fichas as $ficha): ?>
                <a href="ficha.php?id=<?php echo $ficha['id']; ?>" class="card-ficha">
                    <span class="card-title"><?php echo htmlspecialchars($ficha['nome_personagem']); ?></span>
                    <span class="card-date">Última edição: <?php echo date('d/m/Y', strtotime($ficha['data_atualizacao'])); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

</body>
</html>