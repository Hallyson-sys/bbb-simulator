let bbbSalvamentoEmAndamento = false;
let bbbUltimoDestinoSave = null;

async function obterSnapshotBBB() {
    const resposta = await fetch(
        'save_snapshot.php?_=' + Date.now(),
        {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store'
        }
    );

    if (!resposta.ok) {
        throw new Error('Falha ao obter snapshot.');
    }

    const dados = await resposta.json();

    if (!dados.ativo || !dados.snapshot) {
        throw new Error('Não existe temporada ativa para salvar.');
    }

    return dados;
}

function dispararEventoSave(detalhes) {
    document.dispatchEvent(
        new CustomEvent('bbb:save', { detail: detalhes })
    );
}

async function salvarBBBLocalmente(opcoes = {}) {
    if (bbbSalvamentoEmAndamento) return null;

    bbbSalvamentoEmAndamento = true;

    try {
        const payload = await obterSnapshotBBB();
        const dados = BBBSave2.ler();
        const seasonId = payload.season_id || payload.snapshot.id_temporada;

        let destino = opcoes.slot ? String(opcoes.slot) : null;

        if (!destino) {
            destino = BBBSave2.acharSlotDaTemporada(dados, seasonId);
        }

        if (!destino) {
            destino = BBBSave2.primeiroSlotVazio(dados);
        }

        let nome = String(opcoes.nome || '').trim();

        if (destino && dados.slots[destino]?.season_id === seasonId && !nome) {
            nome = dados.slots[destino].name || '';
        }

        const save = BBBSave2.montarSave(payload, nome);

        if (destino && ['1', '2', '3'].includes(destino)) {
            dados.slots[destino] = save;
            bbbUltimoDestinoSave = destino;
        } else {
            dados.recovery = save;
            destino = 'recovery';
            bbbUltimoDestinoSave = destino;
        }

        BBBSave2.gravar(dados);

        const detalhe = {
            ok: true,
            manual: !!opcoes.manual,
            destino,
            save
        };

        dispararEventoSave(detalhe);
        return detalhe;

    } catch (erro) {
        console.warn('Autosave indisponível no momento.', erro);

        dispararEventoSave({
            ok: false,
            manual: !!opcoes.manual,
            erro: erro.message || 'Falha ao salvar.'
        });

        return null;
    } finally {
        bbbSalvamentoEmAndamento = false;
    }
}

async function restaurarSaveBBB2(save) {
    if (!save?.snapshot) {
        throw new Error('Save inválido.');
    }

    const resposta = await fetch('restaurar_save.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ snapshot: save.snapshot })
    });

    const dados = await resposta.json();

    if (!resposta.ok || !dados.ok) {
        throw new Error(dados.erro || 'Falha ao restaurar save.');
    }

    window.location.href = dados.redirect || 'jogo.php';
}

function atualizarIndicadorSave(evento = null) {
    const texto = document.getElementById('saveStatusTexto');
    const hora = document.getElementById('saveStatusHora');
    const botao = document.getElementById('btnAbrirSave2');

    if (!texto || !hora) return;

    if (evento?.detail?.ok) {
        const save = evento.detail.save;
        const destino = evento.detail.destino;

        texto.textContent = destino === 'recovery'
            ? 'Recuperação automática'
            : `Slot ${destino}`;
        hora.textContent = 'Salvo às ' + BBBSave2.formatarData(save.saved_at, false);

        if (botao) {
            botao.classList.add('save-ok');
            setTimeout(() => botao.classList.remove('save-ok'), 900);
        }
        return;
    }

    const dados = BBBSave2.ler();
    let recente = null;
    let destinoRecente = null;

    ['1', '2', '3'].forEach((slot) => {
        const save = dados.slots[slot];
        if (!save) return;

        if (!recente || new Date(save.saved_at) > new Date(recente.saved_at)) {
            recente = save;
            destinoRecente = slot;
        }
    });

    if (dados.recovery && (!recente || new Date(dados.recovery.saved_at) > new Date(recente.saved_at))) {
        recente = dados.recovery;
        destinoRecente = 'recovery';
    }

    if (recente) {
        texto.textContent = destinoRecente === 'recovery'
            ? 'Recuperação automática'
            : `Slot ${destinoRecente}`;
        hora.textContent = 'Último save ' + BBBSave2.formatarData(recente.saved_at, false);
    }
}

function montarResumoSave(save) {
    if (!save) return '';
    const r = save.resumo || {};
    const ultimo = r.ultimo_eliminado
        ? `<span>❌ Último eliminado: ${escapeHtmlSave(r.ultimo_eliminado)}</span>`
        : '<span>❌ Nenhuma eliminação ainda</span>';

    return `
        <div class="save2-resumo">
            <span>🔥 Rodada ${Number(r.rodada || 1)}</span>
            <span>👥 ${Number(r.participantes || 0)} restantes</span>
            ${ultimo}
            <span>🕒 ${BBBSave2.formatarData(save.saved_at)}</span>
        </div>
    `;
}

function escapeHtmlSave(valor) {
    const div = document.createElement('div');
    div.textContent = String(valor ?? '');
    return div.innerHTML;
}

function renderizarSlotsSave2() {
    const container = document.getElementById('save2Slots');
    if (!container) return;

    const dados = BBBSave2.ler();

    container.innerHTML = ['1', '2', '3'].map((slot) => {
        const save = dados.slots[slot];

        if (!save) {
            return `
                <article class="save2-slot vazio">
                    <div class="save2-slot-topo">
                        <span class="save2-numero">SLOT ${slot}</span>
                        <span class="save2-vazio-label">VAZIO</span>
                    </div>
                    <input id="save2Nome${slot}" maxlength="60" value="" placeholder="Nome da temporada (opcional)">
                    <p>Nenhuma temporada foi salva neste espaço.</p>
                    <button type="button" class="save2-btn principal" onclick="salvarNoSlotBBB('${slot}')">💾 Salvar aqui</button>
                </article>
            `;
        }

        return `
            <article class="save2-slot">
                <div class="save2-slot-topo">
                    <span class="save2-numero">SLOT ${slot}</span>
                    <span class="save2-ocupado-label">SALVO</span>
                </div>
                <input id="save2Nome${slot}" maxlength="60" value="${escapeHtmlSave(save.name || '')}" aria-label="Nome do save ${slot}">
                ${montarResumoSave(save)}
                <div class="save2-acoes">
                    <button type="button" class="save2-btn principal" onclick="salvarNoSlotBBB('${slot}')">💾 Salvar agora</button>
                    <button type="button" class="save2-btn" onclick="carregarSlotBBB('${slot}')">▶ Carregar</button>
                    <button type="button" class="save2-btn perigo" onclick="excluirSlotBBB('${slot}')">🗑</button>
                </div>
            </article>
        `;
    }).join('');

    const recovery = document.getElementById('save2Recovery');
    if (recovery) {
        if (dados.recovery) {
            recovery.hidden = false;
            recovery.innerHTML = `
                <div>
                    <strong>🛟 Recuperação automática</strong>
                    <small>Usada quando os 3 slots já estavam ocupados.</small>
                    ${montarResumoSave(dados.recovery)}
                </div>
                <button type="button" class="save2-btn" onclick="carregarRecoveryBBB()">▶ Recuperar</button>
            `;
        } else {
            recovery.hidden = true;
            recovery.innerHTML = '';
        }
    }
}

function abrirSave2() {
    renderizarSlotsSave2();
    document.getElementById('popupSave2')?.classList.add('ativo');
}

function fecharSave2() {
    document.getElementById('popupSave2')?.classList.remove('ativo');
}

async function salvarNoSlotBBB(slot) {
    const dados = BBBSave2.ler();
    const existente = dados.slots[String(slot)];

    let payload;
    try {
        payload = await obterSnapshotBBB();
    } catch (erro) {
        alert('Não foi possível preparar o save agora.');
        return;
    }

    const atualId = payload.season_id || payload.snapshot.id_temporada;

    if (existente && existente.season_id !== atualId) {
        const confirmar = confirm(
            `O Slot ${slot} já contém “${existente.name || 'outra temporada'}”.\n\nDeseja sobrescrever esse save?`
        );
        if (!confirmar) return;
    }

    const input = document.getElementById(`save2Nome${slot}`);
    const nome = String(input?.value || '').trim();

    // Reutiliza o payload já buscado para não salvar um estado diferente
    const store = BBBSave2.ler();
    const save = BBBSave2.montarSave(payload, nome);
    store.slots[String(slot)] = save;

    if (store.recovery?.season_id === save.season_id) {
        store.recovery = null;
    }

    BBBSave2.gravar(store);
    bbbUltimoDestinoSave = String(slot);

    dispararEventoSave({
        ok: true,
        manual: true,
        destino: String(slot),
        save
    });

    mostrarToastSave2('✅ Jogo salvo com sucesso!');
    renderizarSlotsSave2();
}

async function carregarSlotBBB(slot) {
    const save = BBBSave2.ler().slots[String(slot)];
    if (!save) return;

    if (!confirm(`Carregar “${save.name || 'esta temporada'}”? O estado atual não salvo será substituído.`)) {
        return;
    }

    try {
        await restaurarSaveBBB2(save);
    } catch (erro) {
        alert('Não foi possível carregar esse save.');
    }
}

async function carregarRecoveryBBB() {
    const save = BBBSave2.ler().recovery;
    if (!save) return;

    try {
        await restaurarSaveBBB2(save);
    } catch (erro) {
        alert('Não foi possível recuperar esse jogo.');
    }
}

function excluirSlotBBB(slot) {
    const save = BBBSave2.ler().slots[String(slot)];
    if (!save) return;

    if (!confirm(`Excluir definitivamente “${save.name || 'este save'}”?`)) {
        return;
    }

    BBBSave2.removerSlot(String(slot));
    renderizarSlotsSave2();
    atualizarIndicadorSave();
}

function mostrarToastSave2(texto) {
    const toast = document.getElementById('save2Toast');
    if (!toast) return;

    toast.textContent = texto;
    toast.classList.add('mostrar');
    clearTimeout(window.__save2ToastTimer);
    window.__save2ToastTimer = setTimeout(() => {
        toast.classList.remove('mostrar');
    }, 2200);
}

document.addEventListener('bbb:save', (evento) => {
    atualizarIndicadorSave(evento);

    if (evento.detail?.manual && evento.detail?.ok) {
        mostrarToastSave2('✅ Jogo salvo com sucesso!');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    atualizarIndicadorSave();

    salvarBBBLocalmente();

    setInterval(() => {
        salvarBBBLocalmente();
    }, 15000);
});

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') {
        salvarBBBLocalmente();
    }
});

window.salvarBBBLocalmente = salvarBBBLocalmente;
window.abrirSave2 = abrirSave2;
window.fecharSave2 = fecharSave2;
window.salvarNoSlotBBB = salvarNoSlotBBB;
window.carregarSlotBBB = carregarSlotBBB;
window.carregarRecoveryBBB = carregarRecoveryBBB;
window.excluirSlotBBB = excluirSlotBBB;
