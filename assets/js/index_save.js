function mostrarMensagemIndex(texto, tipo = '') {
    const box = document.getElementById('mensagemIndex');
    if (!box) return;

    box.textContent = texto;
    box.className = 'mensagem-index' + (tipo ? ' ' + tipo : '');
    box.hidden = false;
}

function resumoSaveIndex(save) {
    const r = save.resumo || {};
    const ultimo = r.ultimo_eliminado
        ? `<span>❌ ${escapeIndex(r.ultimo_eliminado)}</span>`
        : '<span>❌ Sem eliminações</span>';

    return `
        <div class="save-resumo">
            <span>🔥 Rodada ${Number(r.rodada || 1)}</span>
            <span>👥 ${Number(r.participantes || 0)} restantes</span>
            ${ultimo}
            <span>🕒 ${BBBSave2.formatarData(save.saved_at)}</span>
        </div>
    `;
}

function escapeIndex(valor) {
    const div = document.createElement('div');
    div.textContent = String(valor ?? '');
    return div.innerHTML;
}

function mostrarSavesNoIndex() {
    const dados = BBBSave2.ler();
    const container = document.getElementById('saveSlotsIndex');
    const area = document.getElementById('savesIndex');
    const separador = document.getElementById('separadorIndex');

    if (!container || !area) return;

    const ocupados = ['1', '2', '3'].filter((slot) => dados.slots[slot]);
    const temRecovery = !!dados.recovery;

    if (!ocupados.length && !temRecovery) {
        area.hidden = true;
        if (separador) separador.hidden = true;
        return;
    }

    container.innerHTML = ocupados.map((slot) => {
        const save = dados.slots[slot];
        return `
            <article class="save-card-index">
                <div class="save-card-topo">
                    <span class="save-icone">💾</span>
                    <div>
                        <small>SLOT ${slot}</small>
                        <h2>${escapeIndex(save.name || BBBSave2.nomePadrao(save.resumo))}</h2>
                    </div>
                </div>

                ${resumoSaveIndex(save)}

                <div class="save-botoes">
                    <button type="button" class="btn-save-continuar" onclick="continuarSaveBBB('${slot}')">
                        ▶ CONTINUAR
                    </button>
                    <button type="button" class="btn-save-apagar" onclick="apagarSaveBBB('${slot}')">
                        🗑 Excluir
                    </button>
                </div>
            </article>
        `;
    }).join('');

    if (temRecovery) {
        const save = dados.recovery;
        container.insertAdjacentHTML('beforeend', `
            <article class="save-card-index save-recuperacao">
                <div class="save-card-topo">
                    <span class="save-icone">🛟</span>
                    <div>
                        <small>RECUPERAÇÃO AUTOMÁTICA</small>
                        <h2>${escapeIndex(save.name || 'Temporada recuperável')}</h2>
                    </div>
                </div>
                ${resumoSaveIndex(save)}
                <div class="save-botoes">
                    <button type="button" class="btn-save-continuar" onclick="continuarSaveBBB('recovery')">▶ RECUPERAR</button>
                    <button type="button" class="btn-save-apagar" onclick="apagarSaveBBB('recovery')">🗑 Excluir</button>
                </div>
            </article>
        `);
    }

    area.hidden = false;
    if (separador) separador.hidden = false;
}

async function continuarSaveBBB(slot) {
    const dadosLocais = BBBSave2.ler();
    const save = slot === 'recovery'
        ? dadosLocais.recovery
        : dadosLocais.slots[String(slot)];

    if (!save?.snapshot) {
        mostrarMensagemIndex('⚠️ Não foi possível encontrar um save válido.', 'erro');
        return;
    }

    try {
        const resposta = await fetch('restaurar_save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ snapshot: save.snapshot })
        });

        const dados = await resposta.json();

        if (!resposta.ok || !dados.ok) {
            throw new Error(dados.erro || 'Falha ao restaurar save.');
        }

        window.location.href = dados.redirect || 'jogo.php';
    } catch (erro) {
        console.error(erro);
        mostrarMensagemIndex('⚠️ Não foi possível restaurar a temporada salva.', 'erro');
    }
}

function apagarSaveBBB(slot) {
    const dados = BBBSave2.ler();
    const save = slot === 'recovery'
        ? dados.recovery
        : dados.slots[String(slot)];

    if (!save) return;

    if (!confirm(`Excluir definitivamente “${save.name || 'este save'}”?`)) {
        return;
    }

    BBBSave2.removerSlot(slot);
    mostrarSavesNoIndex();
}

(function carregarMensagensIndex() {
    const params = new URLSearchParams(window.location.search);
    const erro = params.get('erro');

    if (erro === 'idade') {
        mostrarMensagemIndex('⚠️ Para entrar na temporada, a idade precisa estar entre 18 e 70 anos.', 'erro');
    }
    if (erro === 'nome') {
        mostrarMensagemIndex('⚠️ Digite um nome válido.', 'erro');
    }
    if (erro === 'profissao') {
        mostrarMensagemIndex('⚠️ Digite ou escolha uma profissão.', 'erro');
    }
})();

mostrarSavesNoIndex();
