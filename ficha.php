<?php
session_start();
require 'php/config.php';

// Verifica se está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit;
}

// Verifica se tem ID na URL
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$ficha_id   = $_GET['id'];
$usuario_id = $_SESSION['usuario_id'];
$is_mestre  = isset($_SESSION['is_mestre']) ? (int)$_SESSION['is_mestre'] : 0;

if ($is_mestre === 1) {
    // Mestre pode abrir qualquer ficha que exista
    $stmt = $pdo->prepare("SELECT id FROM fichas WHERE id = ?");
    $stmt->execute([$ficha_id]);
} else {
    // Jogador comum só pode abrir fichas dele
    $stmt = $pdo->prepare("SELECT id FROM fichas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$ficha_id, $usuario_id]);
}

if (!$stmt->fetch()) {
    header("Location: dashboard.php"); // Ficha não existe ou não é sua
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fichas Ascensão</title>
    <link rel="stylesheet" href="css/ficha.css">
    <link rel="icon" type="image/png" href="img/favicon.png">
</head>
<body>
    <input type="hidden" id="ficha-id-db" value="<?php echo $ficha_id; ?>">

    <div class="toolbar">
        <button onclick="window.location.href='dashboard.php'" class="btn-tool" style="background:#444">⬅ VOLTAR</button>
        <button id="btn-salvar-cloud" onclick="salvarFichaCloud()" class="btn-tool">☁ SALVAR</button>
        <span id="msg-salvo" style="color:#0f0; margin-left:10px; opacity:0; transition:opacity 0.5s;">SALVO COM SUCESSO!</span>
    </div>

    <div class="switch-container">
        <span class="switch-label">EDITAR</span>
        <label class="switch">
            <input type="checkbox" id="modo-switch" onchange="alternarModo()" checked>
            <span class="slider round"></span>
        </label>
        <span class="switch-label">JOGAR</span>
    </div>

    <div class="header-ficha">
        <div class="header-row top-row">
            <div class="input-group">
                <label>NOME DO PERSONAGEM</label>
                <input type="text" id="nome-char" placeholder="Yuji Itadori">
            </div>
            <div class="input-group">
                <label>JOGADOR</label>
                <input type="text" id="nome-player" placeholder="Seu Nome">
            </div>
        </div>
        
        <div class="header-row bottom-row">
            <div class="input-group">
                <label>CLASSE</label>
                <select id="classe-char" onchange="calcularVitals()">
                    <option value="combatente">Combatente</option>
                    <option value="especialista">Especialista</option>
                    <option value="controlador">Controlador</option>
                </select>
            </div>
            <div class="input-group">
                <label>ORIGEM</label>
                <input type="text" id="origem-char" placeholder="Ex: Recipiente">
            </div>
            <div class="input-group">
                <label>NÍVEL</label>
                <input type="number" id="nivel-char" value="1" onchange="calcularVitals()">
            </div>
            <div class="input-group">
                <label>XP</label>
                <input type="number" id="xp-char" value="0">
            </div>
            <div class="input-group">
                <label>GRAU</label>
                <input type="text" id="grau-char" value="Grau 4">
            </div>
        </div>
    </div>

    <div class="container-ficha">
        
        <div class="coluna-esquerda">
            
            <h2 class="titulo-secao">ATRIBUTOS</h2>
            <div class="pentagrama-container"> 
                <div class="atributo-btn pos-agi" title="Agilidade"><input type="number" id="attr-agi" value="1"></div>
                 <div class="atributo-btn pos-int" title="Intelecto"><input type="number" id="attr-int" value="1"></div>
                 <div class="atributo-btn pos-vig" title="Vigor"><input type="number" id="attr-vig" value="1"></div>
                 <div class="atributo-btn pos-pre" title="Presença"><input type="number" id="attr-pre" value="1"></div>
                 <div class="atributo-btn pos-for" title="Força"><input type="number" id="attr-for" value="1"></div>
            </div>

            <div class="box-pericias">
                <div style="display:flex; justify-content:center; align-items:center; border-bottom:1px solid #333; padding-bottom:10px; margin-bottom:10px;">
                    <h2 class="titulo-secao" style="border:none; margin:0; padding:0;">PERÍCIAS</h2>
                    <span style="font-size:0.8rem; color:#888; margin-left:15px;">BÔNUS TREINO:</span>
                    <input type="number" id="global-treino" class="input-treino-global" value="2" onchange="atualizarFicha()">
                </div>
                
                <ul id="lista-pericias" class="lista-pericias"></ul>
            </div>
        </div>

        <div class="painel-status">
            
            <div class="status-vital">
                
                <div class="bar-group">
                    <div class="bar-header">
                        <span>VIDA (PV)</span>
                        <div class="vital-row-content">
                            <div class="bar-values">
                                <input type="number" id="pv-atual" value="20"> / 
                                <input type="number" id="pv-max" value="15" readonly style="width:40px; background:transparent; border:none; color:inherit; font:inherit; text-align:center;">
                            </div>
                            <div class="vital-controls">
                                <button class="btn-mini btn-dano" onclick="alterarVital('pv', -1)">-</button>
                                <input type="number" id="mod-pv" class="input-line" placeholder="">
                                <button class="btn-mini btn-cura" onclick="alterarVital('pv', 1)">+</button>
                            </div>
                        </div>
                    </div>
                    <div class="progress-bg">
                        <div class="progress-fill red" id="bar-pv"></div>
                        <div class="progress-temp" id="bar-pv-temp"></div>
                    </div>
                </div>

                <div id="box-morte" class="death-door-container">
                    <div class="death-title">☠ PORTAS DA MORTE ☠</div>
                    <div class="death-row">
                        <span>SUCESSOS</span>
                        <div>
                            <input type="checkbox" class="death-check check-success" id="morte-s1">
                            <input type="checkbox" class="death-check check-success" id="morte-s2">
                            <input type="checkbox" class="death-check check-success" id="morte-s3">
                        </div>
                    </div>
                    <div class="death-row">
                        <span>FALHAS</span>
                        <div>
                            <input type="checkbox" class="death-check check-fail" id="morte-f1" onchange="verificarMorte()">
                            <input type="checkbox" class="death-check check-fail" id="morte-f2" onchange="verificarMorte()">
                            <input type="checkbox" class="death-check check-fail" id="morte-f3" onchange="verificarMorte()">
                        </div>
                    </div>
                </div>

                <div class="bar-group">
                    <div class="bar-header">
                        <span>ENERGIA (PE)</span>
                        <div class="vital-row-content">
                            <div class="bar-values">
                                <input type="number" id="pe-atual" value="5"> / 
                                <input type="number" id="pe-max" value="10" readonly style="width:40px; background:transparent; border:none; color:inherit; font:inherit; text-align:center;">
                            </div>
                            <div class="vital-controls">
                                <button class="btn-mini btn-dano" onclick="alterarVital('pe', -1)">-</button>
                                <input type="number" id="mod-pe" class="input-line" placeholder="">
                                <button class="btn-mini btn-cura" onclick="alterarVital('pe', 1)">+</button>
                            </div>
                        </div>
                    </div>
                    <div class="progress-bg">
                        <div class="progress-fill blue" id="bar-pe"></div>
                        <div class="progress-temp" id="bar-pe-temp"></div>
                    </div>
                </div>

                <div class="bar-group">
                    <div class="bar-header">
                        <span>INTEGRIDADE DE ALMA</span>
                        <div class="vital-row-content">
                            <div class="bar-values">
                                <input type="number" id="sanidade-atual" value="12"> / 
                                <input type="number" id="sanidade-max" value="10" readonly style="width:40px; background:transparent; border:none; color:inherit; font:inherit; text-align:center;">
                            </div>
                            <div class="vital-controls">
                                <button class="btn-mini btn-dano" onclick="alterarVital('sanidade', -1)">-</button>
                                <input type="number" id="mod-sanidade" class="input-line" placeholder="">
                                <button class="btn-mini btn-cura" onclick="alterarVital('sanidade', 1)">+</button>
                            </div>
                        </div>
                    </div>
                    <div class="progress-bg">
                        <div class="progress-fill purple" id="bar-sanidade"></div>
                        <div class="progress-temp" id="bar-sanidade-temp"></div>
                    </div>
                </div>

                <div class="bar-group">
                    <div class="bar-header">
                        <span>FÔLEGO (PF)</span>
                        <div class="vital-row-content">
                            <div class="bar-values">
                                <input type="number" id="pf-atual" value="3"> / 
                                <input type="number" id="pf-max" value="3" readonly style="width:40px; background:transparent; border:none; color:inherit; font:inherit; text-align:center;">
                            </div>
                             <div class="vital-controls">
                                <button class="btn-mini btn-dano" onclick="alterarVital('pf', -1)">-</button>
                                <input type="number" id="mod-pf" class="input-line" placeholder="">
                                <button class="btn-mini btn-cura" onclick="alterarVital('pf', 1)">+</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="combat-stats">
                    <div class="stat-box interactive-stat" id="box-iniciativa" title="Clique para rolar">
                        <span class="stat-label">INICIATIVA</span>
                        <span id="val-iniciativa" class="stat-value">0</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">DESLOCAMENTO</span>
                        <span id="val-deslocamento" class="stat-value">9m</span>
                    </div>
                </div>

                <div class="status-row-grid">
                     <div class="mini-status">
                        <label>DEFESA</label>
                        <span id="display-defesa" class="valor-destaque">12</span>
                    </div>
                    <div class="config-defesa">
                        <input type="number" id="bonus-equip" class="input-line" placeholder="Equip" title="Bônus Equipamento" style="width:100%">
                        <input type="number" id="bonus-treino" class="input-line" placeholder="Treino" title="Bônus Treino" style="width:100%">
                     </div>
                </div>

            </div>

            <div class="attacks-box">
                <h2 class="titulo-secao">ATAQUES</h2>
                <div id="lista-ataques"></div>
                <div class="add-item-form" style="flex-direction:column;">
                    <input type="text" id="novo-atk-nome" placeholder="Nome (Ex: Punho Divergente)" class="input-nome" style="width:100%">
                    <textarea id="novo-atk-desc" placeholder="Dano, Crítico, Efeito..." class="input-nome" style="width:100%; height:60px; resize:none; font-family:var(--fonte-texto)"></textarea>
                    <button id="btn-add-atk" class="btn-acao" style="margin-top:5px; width:100%">ADICIONAR ATAQUE</button>
                </div>
            </div>

            <div class="inventario-box">
                <h2 class="titulo-secao">INVENTARIO</h2>
                <p>Carga: <span id="carga-atual">0</span> / <span id="carga-max">10</span></p>
                <div class="carga-bar-container">
                    <div class="carga-atual-fill" id="bar-carga"></div>
                </div>
                <ul class="lista-itens" id="lista-itens"></ul>
                <div class="add-item-form">
                    <input type="text" id="novo-item-nome" placeholder="Item" class="input-nome">
                    <input type="number" id="novo-item-peso" placeholder="Peso" class="input-peso">
                    <button id="btn-add-item" class="btn-acao">+</button>
                </div>
            </div>

        </div> </div> <div class="container-textos">
        <div class="box-textos">
            <div>
                <h2 class="titulo-secao">TALENTOS E HABILIDADES</h2>
                <textarea id="txt-talentos" class="area-texto-custom" placeholder="Habilidades..."></textarea>
            </div>
            <div>
                <h2 class="titulo-secao">VOTOS DE RESTRIÇÃO</h2>
                <textarea id="txt-votos" class="area-texto-custom" placeholder="Pactos..."></textarea>
            </div>
            <div>
                <h2 class="titulo-secao">TÉCNICAS E FEITIÇOS</h2>
                <textarea id="txt-tecnicas" class="area-texto-custom" placeholder="Técnicas..."></textarea>
            </div>
            <div>
                <h2 class="titulo-secao">ANOTAÇÕES</h2>
                <textarea id="txt-anotacoes" class="area-texto-custom" placeholder="Notas..."></textarea>
            </div>
        </div>
    </div>

    <div id="modal-resultado" class="modal-overlay hidden">
        <div class="modal-box">
            <span class="btn-fechar" onclick="fecharModal()">&times;</span>
            <h2 id="modal-titulo">SISTEMA</h2>
            <hr class="linha-modal">
            <div class="modal-conteudo">
                <p id="modal-descricao">Rolou: 3 Dados</p>
                <p id="modal-dados" class="valores-dados">[ ... ]</p>
            </div>
            <div class="modal-footer">
                <span class="label-total">TOTAL:</span>
                <span id="modal-total" class="valor-total">0</span>
            </div>
        </div>
    </div>


<div id="dice-widget" class="dice-widget minimized">
        
        <div class="dice-icon-container" onclick="toggleDiceTray()">
            <div class="dice-svg-mask"></div> </div>

        <div class="dice-tray-content">
            <div class="tray-header" id="tray-drag-area">
                <span>ROLAGEM RÁPIDA</span>
                <button class="btn-close-tray" onclick="toggleDiceTray()">×</button>
            </div>

            <div class="tray-controls">
                <div class="tray-input-group">
                    <label>QTD</label>
                    <input type="number" id="dice-qtd" value="1" min="1" class="input-line" style="width:30px">
                </div>
                <div class="tray-input-group">
                    <label>DESVANTAGEM?</label>
                    <input type="checkbox" id="dice-disadv" class="death-check check-fail">
                </div>
            </div>

            <div class="dice-grid">
                <div class="dice-btn" onclick="rolarDadoCinematico(20)">
                    <img src="img/d20.png" alt="D20">
                    <span>D20</span>
                </div>
                <div class="dice-btn" onclick="rolarDadoCinematico(12)">
                    <img src="img/d12.png" alt="D12">
                    <span>D12</span>
                </div>
                <div class="dice-btn" onclick="rolarDadoCinematico(10)">
                    <img src="img/d10.png" alt="D10">
                    <span>D10</span>
                </div>
                <div class="dice-btn" onclick="rolarDadoCinematico(8)">
                    <img src="img/d8.png" alt="D8">
                    <span>D8</span>
                </div>
                <div class="dice-btn" onclick="rolarDadoCinematico(6)">
                    <img src="img/d6.png" alt="D6">
                    <span>D6</span>
                </div>
                <div class="dice-btn" onclick="rolarDadoCinematico(4)">
                    <img src="img/d4.png" alt="D4">
                    <span>D4</span>
                </div>
            </div>
        </div>
    </div>

    <div id="modal-cinematico" class="modal-overlay hidden">
        <div class="modal-animacao-box">
            <img src="" id="gif-rolagem" class="gif-display">
            
            <div id="resultado-cinematico" class="resultado-overlay hidden">
                <span id="valor-cinematico">20</span>
                <small id="detalhe-cinematico">CRÍTICO</small>
            </div>

            <button class="btn-fechar-cine" onclick="fecharModalCine()">×</button>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>