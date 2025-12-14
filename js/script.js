/* ASCENSÃO D20 - LÓGICA ONLINE (SQLite) */

let inventario = [{ nome: "Uniforme", peso: 0 }, { nome: "Celular", peso: 0 }];
let ataques = [];

// Variáveis para edição manual de status
let modPV = 0;
let modPE = 0;
let modSAN = 0;
let modPF = 0;

const listaPericiasBase = [
    { nome: "Acrobacia", attr: "agi" }, 
    { nome: "Atletismo", attr: "for" }, 
    { nome: "Enganação", attr: "pre" },
    { nome: "Feitiçaria", attr: "pre" }, 
    { nome: "Furtividade", attr: "agi" }, 
    { nome: "História", attr: "int" },
    { nome: "Intimidação", attr: "pre" }, 
    { nome: "Intuição", attr: "pre" }, 
    { nome: "Investigação", attr: "int" },
    { nome: "Luta", attr: "for" }, 
    { nome: "Medicina", attr: "int" }, 
    { nome: "Ocultismo", attr: "int" },
    { nome: "Ofício", attr: "int" }, 
    { nome: "Percepção", attr: "pre" }, 
    { nome: "Persuasão", attr: "pre" },
    { nome: "Pontaria", attr: "agi" }, 
    { nome: "Prestidigitação", attr: "agi" }, 
    { nome: "Sobrevivência", attr: "int" },
    { nome: "Tecnologia", attr: "int" }, 
    { nome: "Vontade", attr: "pre" }, 
    { nome: "Fortitude", attr: "vig" },
    { nome: "Reflexos", attr: "agi" }
];

document.addEventListener('DOMContentLoaded', () => {
    gerarHTMLPericias();
    configurarEventos();
    
    // --- MUDANÇA PRINCIPAL: Carrega do Banco ao Iniciar ---
    carregarFichaDoBanco();

    alternarModo(); 
});

// --- COMUNICAÇÃO COM API PHP ---

function getFichaID() {
    return document.getElementById('ficha-id-db').value;
}

function carregarFichaDoBanco() {
    const id = getFichaID();
    
    fetch(`php/api_ficha.php?id=${id}`)
        .then(response => {
            if(!response.ok) throw new Error("Erro ao buscar ficha");
            return response.json();
        })
        .then(dados => {
            aplicarDadosNaFicha(dados);
        })
        .catch(err => {
            console.error(err);
            console.log("Ficha vazia ou nova.");
        });
}

function salvarFichaCloud() {
    const id = getFichaID();
    const dadosFicha = montarObjetoFicha(); // Pega o JSON atual
    
    // Mostra Feedback visual
    const btn = document.getElementById('btn-salvar-cloud');
    const msg = document.getElementById('msg-salvo');
    btn.innerText = "⏳...";

    fetch('php/api_ficha.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id_ficha: id,
            dados: dadosFicha
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.sucesso) {
            btn.innerText = "☁ SALVAR";
            msg.style.opacity = "1";
            setTimeout(() => { msg.style.opacity = "0"; }, 2000);
        } else {
            alert("Erro ao salvar: " + data.erro);
            btn.innerText = "ERRO";
        }
    })
    .catch(err => {
        console.error(err);
        alert("Erro de conexão.");
        btn.innerText = "ERRO";
    });
}

// Essa função agora é interna, usada pelo carregarFichaDoBanco
function aplicarDadosNaFicha(d) {
    try {
        if(d.cabecalho) {
            document.getElementById('nome-char').value = d.cabecalho.nome || "";
            document.getElementById('nome-player').value = d.cabecalho.jogador || "";
            document.getElementById('classe-char').value = d.cabecalho.classe || "combatente";
            document.getElementById('origem-char').value = d.cabecalho.origem || "";
            document.getElementById('nivel-char').value = d.cabecalho.nivel || 1;
            document.getElementById('xp-char').value = d.cabecalho.xp || 0;
            document.getElementById('grau-char').value = d.cabecalho.grau || "Grau 1";
        }
        if(d.atributos) {
            document.getElementById('attr-for').value = d.atributos.forca;
            document.getElementById('attr-agi').value = d.atributos.agilidade;
            document.getElementById('attr-int').value = d.atributos.intelecto;
            document.getElementById('attr-pre').value = d.atributos.presenca;
            document.getElementById('attr-vig').value = d.atributos.vigor;
        }
        if(d.status) {
            document.getElementById('pv-atual').value = d.status.pv;
            document.getElementById('pe-atual').value = d.status.pe;
            document.getElementById('sanidade-atual').value = d.status.san;
            document.getElementById('pf-atual').value = d.status.pf;
        }
        
        // Carrega Modificadores Manuais
        if(d.modificadores) {
            modPV = d.modificadores.pv || 0;
            modPE = d.modificadores.pe || 0;
            modSAN = d.modificadores.san || 0;
            modPF = d.modificadores.pf || 0;
        }

        if(d.bonus) {
            document.getElementById('bonus-equip').value = d.bonus.equip;
            document.getElementById('bonus-treino').value = d.bonus.treino;
            if(d.bonus.globalPericia) document.getElementById('global-treino').value = d.bonus.globalPericia;
        }

        if(d.textos) {
            document.getElementById('txt-talentos').value = d.textos.talentos || "";
            document.getElementById('txt-votos').value = d.textos.votos || "";
            document.getElementById('txt-tecnicas').value = d.textos.tecnicas || "";
            document.getElementById('txt-anotacoes').value = d.textos.anotacoes || "";
        }

        if(d.pericias) {
            for(let key in d.pericias) {
                const data = d.pericias[key];
                const check = document.getElementById(`treino-${key}`);
                const misc = document.getElementById(`misc-${key}`);
                if(check && misc) {
                    if (typeof data === 'boolean') {
                        check.checked = data;
                    } else {
                        check.checked = data.treinado;
                        misc.value = data.misc;
                    }
                }
            }
        }

        if(d.morte) {
            document.getElementById('morte-s1').checked = d.morte.s1;
            document.getElementById('morte-s2').checked = d.morte.s2;
            document.getElementById('morte-s3').checked = d.morte.s3;
            document.getElementById('morte-f1').checked = d.morte.f1;
            document.getElementById('morte-f2').checked = d.morte.f2;
            document.getElementById('morte-f3').checked = d.morte.f3;
        }
        if(d.inventario) inventario = d.inventario;
        if(d.ataques) ataques = d.ataques;

        atualizarFicha();
        renderizarInventario();
        renderizarAtaques();
        
    } catch(err) { console.error("Erro ao aplicar JSON", err); }
}

// Cria o JSON (igual ao salvar arquivo, mas retorna o objeto)
function montarObjetoFicha() {
    let periciasSalvas = {};
    listaPericiasBase.forEach(p => { 
        periciasSalvas[p.nome] = {
            treinado: document.getElementById(`treino-${p.nome}`).checked,
            misc: document.getElementById(`misc-${p.nome}`).value 
        };
    });

    let morteSalva = {
        s1: document.getElementById('morte-s1').checked,
        s2: document.getElementById('morte-s2').checked,
        s3: document.getElementById('morte-s3').checked,
        f1: document.getElementById('morte-f1').checked,
        f2: document.getElementById('morte-f2').checked,
        f3: document.getElementById('morte-f3').checked,
    };

    let modsSalvos = { pv: modPV, pe: modPE, san: modSAN, pf: modPF };

    return {
        cabecalho: {
            nome: document.getElementById('nome-char').value,
            jogador: document.getElementById('nome-player').value,
            classe: document.getElementById('classe-char').value,
            origem: document.getElementById('origem-char').value,
            nivel: document.getElementById('nivel-char').value,
            xp: document.getElementById('xp-char').value,
            grau: document.getElementById('grau-char').value
        },
        atributos: {
            forca: document.getElementById('attr-for').value,
            agilidade: document.getElementById('attr-agi').value,
            intelecto: document.getElementById('attr-int').value,
            presenca: document.getElementById('attr-pre').value,
            vigor: document.getElementById('attr-vig').value
        },
        status: {
            pv: document.getElementById('pv-atual').value,
            pe: document.getElementById('pe-atual').value,
            san: document.getElementById('sanidade-atual').value,
            pf: document.getElementById('pf-atual').value
        },
        modificadores: modsSalvos,
        bonus: {
            equip: document.getElementById('bonus-equip').value,
            treino: document.getElementById('bonus-treino').value,
            globalPericia: document.getElementById('global-treino').value
        },
        textos: {
            talentos: document.getElementById('txt-talentos').value,
            votos: document.getElementById('txt-votos').value,
            tecnicas: document.getElementById('txt-tecnicas').value,
            anotacoes: document.getElementById('txt-anotacoes').value
        },
        pericias: periciasSalvas,
        morte: morteSalva,
        inventario: inventario,
        ataques: ataques
    };
}


// --- RESTO DO CÓDIGO (LÓGICA DE DADOS, CÁLCULOS) SEGUE IGUAL ---

function gerarHTMLPericias() {
    const lista = document.getElementById('lista-pericias');
    lista.innerHTML = "";
    listaPericiasBase.forEach(p => {
        const li = document.createElement('li');
        li.className = 'pericia-item';
        li.innerHTML = `
            <input type="checkbox" class="pericia-check" id="treino-${p.nome}" onchange="atualizarFicha()">
            <button class="pericia-btn" onclick="rolarPericia('${p.nome}', '${p.attr}')">
                ${p.nome} <span style="color:#666; font-size:0.7em">(${p.attr.toUpperCase()})</span>
            </button>
            <input type="number" id="misc-${p.nome}" class="skill-misc-input" value="0" placeholder="0" onchange="atualizarFicha()">
            <span id="total-${p.nome}" class="skill-total">+0</span>
        `;
        lista.appendChild(li);
    });
}

function gerarNumeroReal(lados) {
    const array = new Uint32Array(1);
    window.crypto.getRandomValues(array);
    const randomFloat = array[0] / (0xFFFFFFFF + 1);
    return Math.floor(randomFloat * lados) + 1;
}

function configurarEventos() {
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(el => {
        if(!el.id.startsWith('novo-') && !el.id.startsWith('mod-') && !el.id.startsWith('dice-') && !el.id.endsWith('-max')) {
            el.addEventListener('change', atualizarFicha);
        }
    });

    ['for', 'agi', 'int', 'pre', 'vig'].forEach(attr => {
        const btnDiv = document.getElementById('attr-' + attr).parentElement;
        btnDiv.addEventListener('click', () => {
            if (document.getElementById('modo-switch').checked) rolarTeste(attr);
        });
    });

    const setupMaxListener = (id, type) => {
        const el = document.getElementById(id);
        if(el) {
            el.addEventListener('change', function() {
                const bases = calcularBases();
                const novoTotal = parseInt(this.value) || 0;
                
                if (type === 'pv') modPV = novoTotal - bases.pv;
                if (type === 'pe') modPE = novoTotal - bases.pe;
                if (type === 'san') modSAN = novoTotal - bases.san;
                if (type === 'pf') modPF = novoTotal - bases.pf;

                atualizarFicha();
            });
        }
    };
    setupMaxListener('pv-max', 'pv');
    setupMaxListener('pe-max', 'pe');
    setupMaxListener('sanidade-max', 'san');
    setupMaxListener('pf-max', 'pf');

    document.getElementById('box-iniciativa').addEventListener('click', () => {
        if (document.getElementById('modo-switch').checked) {
            const agi = parseInt(document.getElementById('attr-agi').value);
            executarRolagem(agi, "INICIATIVA");
        }
    });

    document.getElementById('btn-add-item').addEventListener('click', adicionarItem);
    document.getElementById('btn-add-atk').addEventListener('click', adicionarAtaque);
    document.getElementById('modo-switch').addEventListener('change', alternarModo);
    document.getElementById('modal-resultado').addEventListener('click', (e) => {
        if (e.target.id === 'modal-resultado') fecharModal();
    });
}

function alternarModo() {
    const isModoJogo = document.getElementById('modo-switch').checked;
    const inputsAtributos = document.querySelectorAll('.atributo-btn input');
    
    const inputsMaximos = [
        document.getElementById('pv-max'),
        document.getElementById('pe-max'),
        document.getElementById('sanidade-max'),
        document.getElementById('pf-max')
    ];

    if (isModoJogo) {
        document.body.classList.add('modo-jogo');
        inputsAtributos.forEach(input => input.disabled = true);
        
        inputsMaximos.forEach(input => {
            if(input) {
                input.setAttribute('readonly', true);
                input.classList.remove('editavel-destaque'); 
            }
        });

    } else {
        document.body.classList.remove('modo-jogo');
        inputsAtributos.forEach(input => input.disabled = false);

        inputsMaximos.forEach(input => {
            if(input) {
                input.removeAttribute('readonly');
                input.classList.add('editavel-destaque'); 
            }
        });
        
        atualizarFicha(); 
    }
}

function calcularBases() {
    const nivel = parseInt(document.getElementById('nivel-char').value) || 1;
    const classe = document.getElementById('classe-char').value;
    const vig = parseInt(document.getElementById('attr-vig').value) || 0;
    const pre = parseInt(document.getElementById('attr-pre').value) || 0;
    const int = parseInt(document.getElementById('attr-int').value) || 0;

    let pvBase = 10;
    if (classe === 'combatente') pvBase = 15 + (nivel * 5);
    else if (classe === 'controlador') pvBase = 12 + (nivel * 4);
    else if (classe === 'especialista') pvBase = 10 + (nivel * 3);

    let fator = Math.max(pre, int); 
    let peBase = 0;
    if (classe === 'combatente') peBase = (fator + vig) * 2 + nivel;
    else if (classe === 'especialista') peBase = (fator + vig) * 4 + nivel;
    else if (classe === 'controlador') peBase = (fator + vig) * 3 + nivel;

    const pfBase = vig + 2;
    const sanBase = 10 + pre + vig;

    return { pv: pvBase, pe: peBase, san: sanBase, pf: pfBase };
}

function calcularVitals() {
    const bases = calcularBases();

    const pvFinal = bases.pv + modPV;
    const peFinal = bases.pe + modPE;
    const pfFinal = bases.pf + modPF;
    const sanFinal = bases.san + modSAN;

    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if(el) {
            if(el.tagName === 'INPUT') el.value = val;
            else el.innerText = val; 
        }
    };

    setVal('pv-max', pvFinal);
    setVal('pe-max', peFinal);
    setVal('pf-max', pfFinal);
    setVal('sanidade-max', sanFinal);

    atualizarBarraComOverheal('pv', pvFinal);
    atualizarBarraComOverheal('pe', peFinal);
    atualizarBarraComOverheal('sanidade', sanFinal);
}

function atualizarBarraComOverheal(tipo, max) {
    const atual = parseInt(document.getElementById(tipo + '-atual').value) || 0;
    const barBase = document.getElementById('bar-' + tipo);
    const barTemp = document.getElementById('bar-' + tipo + '-temp');

    if (atual <= max) {
        barBase.style.width = Math.min((atual/max)*100, 100) + '%';
        if(barTemp) barTemp.style.width = '0%';
    } else {
        barBase.style.width = '100%';
        if(barTemp) {
            let excesso = atual - max;
            let pct = (excesso / max) * 100;
            if(pct > 100) pct = 100;
            barTemp.style.width = pct + '%';
        }
    }
}

function atualizarFicha() {
    calcularVitals();

    const forca = parseInt(document.getElementById('attr-for').value) || 0;
    const agi = parseInt(document.getElementById('attr-agi').value) || 0;
    const bEquip = parseInt(document.getElementById('bonus-equip').value) || 0;
    const bTreino = parseInt(document.getElementById('bonus-treino').value) || 0;

    document.getElementById('display-defesa').innerText = 12 + agi + bEquip + bTreino;
    const cargaMax = 8 + (2 * forca);
    document.getElementById('carga-max').innerText = cargaMax;
    recalcularPesoAtual(cargaMax);

    document.getElementById('val-iniciativa').innerText = agi;
    const deslocamento = agi >= 3 ? "12m" : "9m";
    document.getElementById('val-deslocamento').innerText = deslocamento;

    const pvAtual = parseInt(document.getElementById('pv-atual').value) || 0;
    const boxMorte = document.getElementById('box-morte');
    if (pvAtual <= 0) boxMorte.classList.add('active'); 
    else boxMorte.classList.remove('active');

    const bonusGlobal = parseInt(document.getElementById('global-treino').value) || 2; 

    listaPericiasBase.forEach(p => {
        const isTreinado = document.getElementById(`treino-${p.nome}`).checked;
        const bonusMisc = parseInt(document.getElementById(`misc-${p.nome}`).value) || 0;
        
        const total = (isTreinado ? bonusGlobal : 0) + bonusMisc;
        
        const span = document.getElementById(`total-${p.nome}`);
        span.innerText = total >= 0 ? `+${total}` : total;
        
        if (total > 0) span.style.color = "#0d720dff";
        else span.style.color = "var(--cor-destaque)";
    });
}

function alterarVital(tipo, multiplicador) {
    const elAtual = document.getElementById(tipo + '-atual');
    const elMod = document.getElementById('mod-' + tipo);
    let atual = parseInt(elAtual.value) || 0;
    let modificador = parseInt(elMod.value); 
    if (!modificador && modificador !== 0) modificador = 1;

    let novoValor = atual + (modificador * multiplicador);
    if (novoValor < 0) novoValor = 0;

    elAtual.value = novoValor;
    atualizarFicha();
}

function verificarMorte() {
    const f1 = document.getElementById('morte-f1').checked;
    const f2 = document.getElementById('morte-f2').checked;
    const f3 = document.getElementById('morte-f3').checked;
    const pvAtual = parseInt(document.getElementById('pv-atual').value) || 0;

    if (pvAtual <= 0 && f1 && f2 && f3) {
        const modal = document.getElementById('modal-resultado');
        modal.classList.add('modal-morte'); 
        abrirModal("FIM DE JOGO", "Seu personagem faleceu.", [], "✝");
        setTimeout(() => {
             modal.onclick = function() {
                 modal.classList.add('hidden');
                 modal.classList.remove('modal-morte');
             };
        }, 100);
    }
}

function rolarTeste(attr) {
    const qtd = parseInt(document.getElementById('attr-' + attr).value);
    executarRolagem(qtd, attr.toUpperCase());
}

function rolarPericia(nome, attr) {
    if (!document.getElementById('modo-switch').checked) return;
    
    const qtd = parseInt(document.getElementById('attr-' + attr).value);
    const textoTotal = document.getElementById(`total-${nome}`).innerText;
    const bonusTotal = parseInt(textoTotal); 

    executarRolagem(qtd, nome.toUpperCase(), bonusTotal);
}

function executarRolagem(qtd, titulo, bonus = 0) {
    let resultados = [];
    let final = 0;
    let desc = "";
    if (qtd <= 0) {
        let d1 = rolarD20();
        let d2 = rolarD20();
        resultados = [d1, d2];
        final = Math.min(d1, d2) + bonus;
        desc = `Debilitado (Menor) ${bonus > 0 ? '+ ' + bonus : ''}`;
    } else {
        for(let i=0; i<qtd; i++) resultados.push(rolarD20());
        final = Math.max(...resultados) + bonus;
        desc = `${qtd}d20 (Maior) ${bonus > 0 ? '+ ' + bonus : ''}`;
    }
    abrirModal(titulo, desc, resultados, final);
    registrarAuditoria("Teste/Perícia", `${titulo}: ${desc}`, final);
}

function rolarD20() { return gerarNumeroReal(20); }

function abrirModal(titulo, desc, dados, total) {
    const modal = document.getElementById('modal-resultado');
    document.getElementById('modal-titulo').innerText = titulo;
    document.getElementById('modal-descricao').innerText = desc;
    document.getElementById('modal-dados').innerText = `[ ${dados.join(", ")} ]`;
    
    const elTotal = document.getElementById('modal-total');
    elTotal.innerText = total;
    if(total >= 20) elTotal.style.color = "#FFD700";
    else if(total === 1 || total === "✝") elTotal.style.color = "#555";
    else elTotal.style.color = "var(--cor-destaque)";

    modal.classList.remove('hidden');
}

function fecharModal() { document.getElementById('modal-resultado').classList.add('hidden'); }

function renderizarInventario() {
    const lista = document.getElementById('lista-itens');
    lista.innerHTML = "";
    let pesoTotal = 0;
    inventario.forEach((item, index) => {
        pesoTotal += item.peso;
        const li = document.createElement('li');
        li.className = 'item-row';
        li.innerHTML = `<span>${item.nome}</span><span>${item.peso} <button onclick="removerItem(${index})" style="color:red;background:none;border:none;cursor:pointer">x</button></span>`;
        lista.appendChild(li);
    });
    return pesoTotal;
}

function renderizarAtaques() {
    const lista = document.getElementById('lista-ataques');
    if(!lista) return;
    lista.innerHTML = "";
    ataques.forEach((atk, index) => {
        const div = document.createElement('div');
        div.className = 'attack-card';
        div.innerHTML = `
            <div class="attack-header">
                <span class="attack-name">${atk.nome}</span>
                <button class="btn-del-attack" onclick="removerAtaque(${index})">x</button>
            </div>
            <div class="attack-desc">${atk.desc}</div>
        `;
        lista.appendChild(div);
    });
}

function recalcularPesoAtual(max) {
    let atual = renderizarInventario();
    document.getElementById('carga-atual').innerText = atual;
    let pct = (atual / max) * 100;
    const bar = document.getElementById('bar-carga');
    if(bar) {
        bar.style.width = Math.min(pct, 100) + '%';
        bar.style.backgroundColor = atual > max ? 'red' : 'var(--cor-destaque)';
    }
}

function adicionarItem() {
    const nome = document.getElementById('novo-item-nome').value;
    const peso = parseInt(document.getElementById('novo-item-peso').value);
    if(nome && !isNaN(peso)) {
        inventario.push({nome, peso});
        atualizarFicha();
        document.getElementById('novo-item-nome').value = '';
        document.getElementById('novo-item-peso').value = '';
    }
}

function adicionarAtaque() {
    const nome = document.getElementById('novo-atk-nome').value;
    const desc = document.getElementById('novo-atk-desc').value;
    if(nome) {
        ataques.push({nome, desc});
        renderizarAtaques();
        document.getElementById('novo-atk-nome').value = '';
        document.getElementById('novo-atk-desc').value = '';
    }
}

function removerItem(i) { inventario.splice(i, 1); atualizarFicha(); }
function removerAtaque(i) { ataques.splice(i, 1); renderizarAtaques(); }


// =================================================
// LÓGICA DO WIDGET DE DADOS
// =================================================

function toggleDiceTray() {
    const widget = document.getElementById('dice-widget');
    if (!widget.classList.contains('dragging')) {
        widget.classList.toggle('minimized');
    }
}

const widget = document.getElementById('dice-widget');
const dragHeader = document.getElementById('tray-drag-area');
let isDragging = false;
let startX, startY, initialLeft, initialTop;

widget.addEventListener('mousedown', iniciarArrasto);

function iniciarArrasto(e) {
    const isMinimized = widget.classList.contains('minimized');
    const target = e.target;
    
    if (!isMinimized && !target.closest('#tray-drag-area')) return;

    isDragging = false; 
    startX = e.clientX;
    startY = e.clientY;
    
    const rect = widget.getBoundingClientRect();
    initialLeft = rect.left;
    initialTop = rect.top;

    document.addEventListener('mousemove', moverArrasto);
    document.addEventListener('mouseup', pararArrasto);
}

function moverArrasto(e) {
    const dx = e.clientX - startX;
    const dy = e.clientY - startY;

    if (Math.abs(dx) > 5 || Math.abs(dy) > 5) {
        isDragging = true;
        widget.classList.add('dragging');
        widget.style.left = `${initialLeft + dx}px`;
        widget.style.top = `${initialTop + dy}px`;
        widget.style.bottom = 'auto'; 
    }
}

function pararArrasto() {
    document.removeEventListener('mousemove', moverArrasto);
    document.removeEventListener('mouseup', pararArrasto);
    
    setTimeout(() => {
        widget.classList.remove('dragging');
    }, 100);
}

function rolarDadoCinematico(lados) {
    const qtd = parseInt(document.getElementById('dice-qtd').value) || 1;
    const isDesvantagem = document.getElementById('dice-disadv').checked;
    
    let resultados = [];
    for(let i=0; i<qtd; i++) {
        resultados.push(gerarNumeroReal(lados));
    }
    
    let total = Math.max(...resultados); 
    let detalhe = `Maior de [${resultados}]`;

    if (isDesvantagem) {
        total = Math.min(...resultados);
        detalhe = `Menor de [${resultados}]`;
    } else if (qtd === 1) {
        detalhe = "Normal";
    }

    const modal = document.getElementById('modal-cinematico');
    const gif = document.getElementById('gif-rolagem');
    const overlayRes = document.getElementById('resultado-cinematico');
    const valFinal = document.getElementById('valor-cinematico');
    const txtDetalhe = document.getElementById('detalhe-cinematico');

    valFinal.innerText = "";       
    txtDetalhe.innerText = "";     
    overlayRes.classList.add('hidden'); 


    gif.src = ""; 
    setTimeout(() => {
        gif.src = "public_assets_dice201.gif?t=" + new Date().getTime();
    }, 10);
    
    modal.classList.remove('hidden');

    setTimeout(() => {
        valFinal.innerText = total;
        txtDetalhe.innerText = detalhe;
        
        if (total === lados) valFinal.style.color = "#FFD700"; 
        else if (total === 1) valFinal.style.color = "#160101ff"; 
        else valFinal.style.color = "var(--cor-destaque)";

        overlayRes.classList.remove('hidden'); 
        registrarAuditoria("Rolagem Manual", `D${lados} (${detalhe})`, total);
    }, 1800); 
}

function fecharModalCine() {
    document.getElementById('modal-cinematico').classList.add('hidden');
}

function registrarAuditoria(tipo, detalhe, resultado) {
    const logData = {
        data: new Date().toLocaleString(),
        personagem: document.getElementById('nome-char').value || "Desconhecido",
        acao: tipo,
        dados: detalhe,
        final: resultado
    };
    
    console.log("📝 AUDITORIA:", logData);
}