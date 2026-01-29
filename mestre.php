
<?php
session_start();
require 'php/config.php';

// Só mestre tem acesso
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['is_mestre']) || $_SESSION['is_mestre'] != 1) {
    header("Location: index.html");
    exit;
}

// Ações de POST (excluir / mudar usuário)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] === 'excluir_ficha') {
        $fichaId = (int)$_POST['ficha_id'];

        $stmt = $pdo->prepare("DELETE FROM fichas WHERE id = ?");
        $stmt->execute([$fichaId]);

        header("Location: mestre.php");
        exit;
    }

    if (isset($_POST['acao']) && $_POST['acao'] === 'mudar_usuario') {
        $fichaId     = (int)$_POST['ficha_id'];
        $novoUserId  = (int)$_POST['novo_usuario_id'];

        // (opcional) verificar se usuário existe
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
        $check->execute([$novoUserId]);
        if ($check->fetch()) {
            $stmt = $pdo->prepare("UPDATE fichas SET usuario_id = ? WHERE id = ?");
            $stmt->execute([$novoUserId, $fichaId]);
        }

        header("Location: mestre.php");
        exit;
    }
}

// Busca todas as fichas + dados do jogador
$sql = "SELECT 
            f.id,
            f.usuario_id,
            f.nome_personagem,
            f.dados_json,        -- aqui vem o JSON
            u.nome AS jogador_nome,
            u.id   AS jogador_id
        FROM fichas f
        LEFT JOIN usuarios u ON f.usuario_id = u.id
        ORDER BY f.id DESC";
$fichas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Busca todos usuários para poder reatribuir
$usuarios = $pdo->query("SELECT id, nome, email FROM usuarios ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Área do Mestre - Fichas</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="img/favicon.png">
    <style>
        body {
            font-family: 'Girassol', sans-serif;
            background-image: linear-gradient(45deg, black, #380202);
            color: #F2E1E1;
        }
        .master-container {
            max-width: 1100px;
            margin: 30px auto;
            background: #000;
            border-radius: 15px;
            padding: 20px 30px;
        }
        .master-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .master-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .master-table th,
        .master-table td {
            border-bottom: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        .master-table th {
            background: #700b0b;
        }
        .btn-small {
            border-radius: 12px;
            padding: 5px 12px;
            font-size: 12px;
            border: 1px solid #700b0b;
            background: #700b0b;
            color: #fff;
            cursor: pointer;
            margin-right: 4px;
        }
        .btn-small:hover {
            background: #fff;
            color: #700b0b;
        }
        select {
            background: #F2E1E1;
            border: none;
            padding: 4px;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="master-container">
        <div class="master-header">
            <h2 class="title title-second">Area do Mestre - Fichas</h2>
            <button class="btn btn-primary" onclick="window.location.href='dashboard.php'">Voltar</button>
        </div>

        <?php if (empty($fichas)): ?>
            <p>Nenhuma ficha cadastrada.</p>
        <?php else: ?>
            <table class="master-table">
                <thead>
                    <tr>
                        <th>ID</th>
        <th>Personagem</th>
        <th>Jogador</th>
        <th>Vida</th>
        <th>Energia</th>
        <th>Mudar usuário</th>
        <th>Ações</th>
                    </tr>
                </thead>
                </thead>
<tbody>
    <?php foreach ($fichas as $f): ?>
        <?php
            // Decodifica o JSON da ficha (campo dados_json)
            $pv = $pe = null;

            if (!empty($f['dados_json'])) {
                $dados = json_decode($f['dados_json'], true); // array associativo

                if (json_last_error() === JSON_ERROR_NONE && isset($dados['status'])) {
                    $pv = isset($dados['status']['pv']) ? (int)$dados['status']['pv'] : null;
                    $pe = isset($dados['status']['pe']) ? (int)$dados['status']['pe'] : null;
                }
            }

            $textoPv = ($pv !== null) ? $pv : '—';
            $textoPe = ($pe !== null) ? $pe : '—';
        ?>
        <tr>
            <td><?php echo (int)$f['id']; ?></td>
            <td><?php echo htmlspecialchars($f['nome_personagem'] ?? 'Sem nome'); ?></td>
            <td>
                <?php
                    if ($f['jogador_nome']) {
                        echo htmlspecialchars($f['jogador_nome']) . " (ID " . (int)$f['jogador_id'] . ")";
                    } else {
                        echo "<i>Sem jogador</i>";
                    }
                ?>
            </td>
            <td><?php echo $textoPv; ?></td>
            <td><?php echo $textoPe; ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="acao" value="mudar_usuario">
                    <input type="hidden" name="ficha_id" value="<?php echo (int)$f['id']; ?>">
                    <select name="novo_usuario_id">
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?php echo (int)$u['id']; ?>" <?php echo ($u['id'] == $f['jogador_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['nome']) . " (ID " . (int)$u['id'] . ")"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-small">Salvar</button>
                </form>
            </td>
            <td>
                <button class="btn-small" onclick="window.location.href='ficha.php?id=<?php echo (int)$f['id']; ?>'">
                    Editar ficha
                </button>

                <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir esta ficha?');">
                    <input type="hidden" name="acao" value="excluir_ficha">
                    <input type="hidden" name="ficha_id" value="<?php echo (int)$f['id']; ?>">
                    <button type="submit" class="btn-small" style="background:#400; border-color:#400;">Excluir</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>