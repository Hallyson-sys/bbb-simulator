const BBB_SAVE_KEY = "bbb_simulator_save_v1";

function mostrarMensagemIndex(texto, tipo = "") {
    const box = document.getElementById("mensagemIndex");

    if (!box) return;

    box.textContent = texto;
    box.className = "mensagem-index" + (tipo ? " " + tipo : "");
    box.hidden = false;
}

function obterSaveBBB() {
    try {
        const bruto = localStorage.getItem(BBB_SAVE_KEY);

        if (!bruto) return null;

        const save = JSON.parse(bruto);

        if (!save || !save.snapshot || !save.resumo) {
            return null;
        }

        return save;
    } catch (erro) {
        console.error("Falha ao ler save:", erro);
        return null;
    }
}

function formatarDataSave(dataIso) {
    if (!dataIso) {
        return "🕒 Save recente";
    }

    const data = new Date(dataIso);

    if (Number.isNaN(data.getTime())) {
        return "🕒 Save recente";
    }

    return "🕒 " + data.toLocaleString("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        hour: "2-digit",
        minute: "2-digit"
    });
}

function mostrarSaveNoIndex() {
    const save = obterSaveBBB();

    if (!save) return;

    const card = document.getElementById("saveCardIndex");
    const separador = document.getElementById("separadorIndex");

    if (!card) return;

    const resumo = save.resumo || {};

    document.getElementById("saveNome").textContent =
        resumo.modo_espectador
            ? `${resumo.nome || "Jogador"} · modo espectador`
            : (resumo.nome || "Temporada salva");

    document.getElementById("saveRodada").textContent =
        `🔥 Rodada ${resumo.rodada || 1}`;

    document.getElementById("saveParticipantes").textContent =
        `👥 ${resumo.participantes || 0} participantes`;

    document.getElementById("saveData").textContent =
        formatarDataSave(save.saved_at);

    card.hidden = false;

    if (separador) {
        separador.hidden = false;
    }
}

async function continuarSaveBBB() {
    const save = obterSaveBBB();

    if (!save) {
        mostrarMensagemIndex(
            "⚠️ Não foi possível encontrar um save válido.",
            "erro"
        );
        return;
    }

    try {
        const resposta = await fetch("restaurar_save.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            credentials: "same-origin",
            body: JSON.stringify({
                snapshot: save.snapshot
            })
        });

        const dados = await resposta.json();

        if (!resposta.ok || !dados.ok) {
            throw new Error(dados.erro || "Falha ao restaurar save.");
        }

        window.location.href = dados.redirect || "jogo.php";

    } catch (erro) {
        console.error(erro);

        mostrarMensagemIndex(
            "⚠️ Não foi possível restaurar a temporada salva.",
            "erro"
        );
    }
}

async function apagarSaveBBB() {
    if (!confirm("Apagar o save desta temporada?")) {
        return;
    }

    localStorage.removeItem(BBB_SAVE_KEY);

    try {
        await fetch("encerrar_temporada.php?ajax=1", {
            method: "POST",
            credentials: "same-origin"
        });
    } catch (erro) {
        console.warn("Sessão não pôde ser encerrada agora.", erro);
    }

    window.location.reload();
}

(function carregarMensagensIndex() {
    const params = new URLSearchParams(window.location.search);
    const erro = params.get("erro");

    if (erro === "idade") {
        mostrarMensagemIndex(
            "⚠️ Para entrar na temporada, a idade precisa estar entre 18 e 70 anos.",
            "erro"
        );
    }

    if (erro === "nome") {
        mostrarMensagemIndex(
            "⚠️ Digite um nome válido.",
            "erro"
        );
    }

    if (erro === "profissao") {
        mostrarMensagemIndex(
            "⚠️ Digite ou escolha uma profissão.",
            "erro"
        );
    }
})();

mostrarSaveNoIndex();
