document.addEventListener("DOMContentLoaded", function() {
    const aoVivoLog = document.getElementById("aoVivoLog");

    if (aoVivoLog) {
        aoVivoLog.scrollTop = aoVivoLog.scrollHeight;
    }
});


function limitarMonstro(el) {
    const max = 2;
    const marcados = document.querySelectorAll('input[name="monstro[]"]:checked');

    if (marcados.length > max) {
        el.checked = false;
        alert("Você só pode escolher até 2 participantes!");
    }
}

function limitarVIP(el) {
    const max = qtdVIP;
    const marcados = document.querySelectorAll('input[name="vip[]"]:checked');

    if (marcados.length > max) {
        el.checked = false;
        alert("Você só pode escolher " + max + " participantes!");
    }
}

function abrirPopup() {
    document.getElementById("popupReset").classList.add("ativo");
}

function fecharPopup() {
    document.getElementById("popupReset").classList.remove("ativo");
}

document.getElementById("popupReset").addEventListener("click", function(e) {
    if (e.target === this) {
        fecharPopup();
    }
});

function selecionarAlvo(botao, nome) {
    document.getElementById("alvo").value = nome;

    document.querySelectorAll(".grupo-alvo .participante-btn")
        .forEach(btn => btn.classList.remove("selecionado"));

    botao.classList.add("selecionado");
}

function selecionarAlvo2(botao, nome) {
    document.getElementById("alvo2").value = nome;

    document.querySelectorAll(".grupo-alvo2 .participante-btn")
        .forEach(btn => btn.classList.remove("selecionado"));

    botao.classList.add("selecionado");
}

function selecionarAlianca(botao, nome) {
    const campo = document.getElementById("alianca_escolhida");

    if (campo) {
        campo.value = nome;
    }

    document.querySelectorAll(".grupo-alianca .participante-btn")
        .forEach(btn => btn.classList.remove("selecionado"));

    botao.classList.add("selecionado");
}

function limitarConvidadosAlianca(el) {
    const max = 5;
    const marcados = document.querySelectorAll('input[name="convidados_alianca[]"]:checked');

    if (marcados.length > max) {
        el.checked = false;
        alert("Você pode convidar no máximo 5 participantes para a aliança.");
    }
}

function executarAcao() {
    const acao = acaoSelecionada;
    const alvo = document.getElementById("alvo") ? document.getElementById("alvo").value : "";
    const alvo2 = document.getElementById("alvo2") ? document.getElementById("alvo2").value : "";
    const aliancaEscolhida = document.getElementById("alianca_escolhida") ? document.getElementById("alianca_escolhida").value : "";

    const acoesSemAlvo = ["aproximar_lider", "sair_alianca", "alianca", "entrar_alianca", "vt"];

    if (!acoesSemAlvo.includes(acao) && !alvo) {
        alert("Escolha um participante primeiro.");
        return;
    }

    if (acao === "entrar_alianca" && !aliancaEscolhida) {
        alert("Escolha uma aliança primeiro.");
        return;
    }

    if (acao === "alianca") {
        const convidados = document.querySelectorAll('input[name="convidados_alianca[]"]:checked');

        if (convidados.length < 1) {
            alert("Escolha pelo menos 1 participante para convidar.");
            return;
        }
    }

    if (acao === "intriga" && !alvo2) {
        alert("Escolha o segundo participante.");
        return;
    }

    if (acao === "intriga" && alvo === alvo2) {
        alert("Escolha dois participantes diferentes.");
        return;
    }

    document.getElementById("formAcao").submit();
}

function selecionarAlvoFesta(botao, nome) {
    document.getElementById("alvo_festa").value = nome;

    document.querySelectorAll(".grupo-festa .participante-btn")
        .forEach(btn => btn.classList.remove("selecionado"));

    botao.classList.add("selecionado");

    const opcoesRomance = document.getElementById("opcoesRomance");

    if (opcoesRomance) {
        const romance = parseInt(botao.dataset.romance || "0");
        opcoesRomance.style.display = "block";

        document.querySelectorAll(".romance-30").forEach(btn => {
            btn.style.display = romance >= 30 ? "block" : "none";
        });

        document.querySelectorAll(".romance-60").forEach(btn => {
            btn.style.display = romance >= 60 ? "block" : "none";
        });

        const texto = document.getElementById("textoRomanceLiberado");

        if (texto) {
            if (romance >= 60) {
                texto.innerHTML = "💕 Romance atual: " + romance + ". Opções de quase casal liberadas.";
            } else if (romance >= 30) {
                texto.innerHTML = "💕 Romance atual: " + romance + ". Opções de crush liberadas.";
            } else {
                texto.innerHTML = "💕 Romance atual: " + romance + ". Por enquanto, você pode flertar ou elogiar.";
            }
        }
    }
}

function selecionarAcaoRomance(acao) {
    document.getElementById("acao_festa_valor").value = acao;
    executarAcaoFesta();
}

function executarAcaoFesta() {
    const alvo = document.getElementById("alvo_festa").value;
    const acaoInput = document.getElementById("acao_festa_valor");
    const acao = acaoInput ? acaoInput.value : "";

    if (!alvo) {
        alert("Escolha um participante primeiro.");
        return;
    }

    if (acaoInput && !acao) {
        alert("Escolha uma ação romântica primeiro.");
        return;
    }

    document.getElementById("formFesta").submit();
}