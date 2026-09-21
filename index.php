<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BBB Simulator</title>

<link rel="stylesheet" href="assets/css/index.css">
<link rel="stylesheet" href="assets/css/index_save.css">

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
</head>

<body>

<div class="container">

    <div class="info">
        <div class="info-card">⭐ Viva essa experiência única</div>
        <div class="info-card">👥 Conheça novas pessoas</div>
    </div>

    <div class="card">

        <h1 class="titulo">BBB<br>SIMULATOR</h1>
        <div class="sub">PARA VENCER O JOGO, VALE TUDO</div>

        <div id="mensagemIndex" class="mensagem-index" hidden></div>

        <!-- =================================================
             💾 SAVE LOCAL
        ================================================== -->
        <section class="save-card-index" id="saveCardIndex" hidden>
            <div class="save-card-topo">
                <span class="save-icone">💾</span>

                <div>
                    <small>TEMPORADA EM ANDAMENTO</small>
                    <h2 id="saveNome">Seu jogo salvo</h2>
                </div>
            </div>

            <div class="save-resumo">
                <span id="saveRodada">🔥 Rodada -</span>
                <span id="saveParticipantes">👥 - participantes</span>
                <span id="saveData">🕒 Save recente</span>
            </div>

            <div class="save-botoes">
                <button
                    type="button"
                    class="btn-save-continuar"
                    onclick="continuarSaveBBB()"
                >
                    ▶ CONTINUAR TEMPORADA
                </button>

                <button
                    type="button"
                    class="btn-save-apagar"
                    onclick="apagarSaveBBB()"
                >
                    🗑 Apagar Save
                </button>
            </div>
        </section>

        <div class="separador-index" id="separadorIndex" hidden>
            <span>NOVA TEMPORADA</span>
        </div>

        <form
            action="config.php"
            method="POST"
            id="formEntrada"
            onsubmit="return false;"
        >

            <input
                type="text"
                name="nome"
                placeholder="Digite seu nome"
                maxlength="40"
                required
            >

            <input
                type="number"
                name="idade"
                placeholder="Digite sua idade"
                min="18"
                max="70"
                step="1"
                required
            >

            <!--
                O jogador pode escolher uma sugestão OU
                escrever qualquer profissão personalizada.
            -->
            <input
                type="text"
                name="profissao"
                list="listaProfissoes"
                placeholder="Digite ou escolha sua profissão"
                maxlength="60"
                autocomplete="off"
                required
            >

            <datalist id="listaProfissoes">
                <option value="Influencer">
                <option value="Professor(a)">
                <option value="Youtuber">
                <option value="Advogado(a)">
                <option value="Policial">
                <option value="Médico(a)">
                <option value="Enfermeiro(a)">
                <option value="Balconista">
                <option value="Desempregado">
                <option value="DJ">
                <option value="Terapeuta">
                <option value="Ator/Atriz">
                <option value="Bombeiro(a)">
                <option value="Personal Trainer">
                <option value="Maquiador(a)">
                <option value="Motorista de Aplicativo">
                <option value="Nutricionista">
                <option value="Barbeiro(a)">
                <option value="Cabeleireiro(a)">
                <option value="Cantor(a)">
                <option value="Modelo">
                <option value="Vendedor(a)">
                <option value="Engenheiro(a)">
                <option value="Arquiteto(a)">
                <option value="Empresário">
                <option value="Psicólogo">
                <option value="Tatuador(a)">
                <option value="Veterinário(a)">
                <option value="Streamer">
                <option value="Fotógrafo(a)">
                <option value="Comissário(a) de Bordo">
                <option value="Assistente Social">
                <option value="Esteticista">
                <option value="Radialista">
                <option value="Estudante">
                <option value="Cineasta">
                <option value="Roteirista">
                <option value="Designer">
                <option value="Dublador(a)">
            </datalist>

            <select name="estado" required>
                <option value="">Escolha seu estado</option>
                <option>SP</option><option>RJ</option><option>MG</option>
                <option>BA</option><option>RS</option><option>SC</option>
                <option>PR</option><option>PE</option><option>CE</option>
                <option>GO</option><option>DF</option><option>ES</option>
                <option>PA</option><option>AM</option><option>MT</option>
                <option>MS</option><option>RN</option><option>PB</option>
                <option>AL</option><option>SE</option><option>MA</option>
                <option>PI</option><option>TO</option><option>RO</option>
                <option>AC</option><option>AP</option><option>RR</option>
            </select>

            <select name="personalidade" required>
                <option value="">Escolha sua personalidade</option>
                <option>Estrategista</option>
                <option>Explosivo</option>
                <option>Planta</option>
                <option>Manipulador</option>
                <option>Emocional</option>
                <option>Barraqueiro</option>
                <option>Fofo</option>
                <option>Líder Nato</option>
                <option>Influencer</option>
                <option>Falso</option>
                <option>Neutro</option>
            </select>

            <select name="tipo_elenco" required>
                <option value="">Escolha o tipo de elenco</option>
                <option value="automatico">🎲 Elenco Aleatório</option>
                <option value="personalizado">✏️ Montar Meu Próprio Elenco</option>
            </select>

            <select name="qtd" required>
                <option value="20">20 participantes</option>
                <option value="15">15 participantes</option>
                <option value="10">10 participantes</option>
            </select>

            <button type="button" onclick="entrarNaCasa()">
                ENTRAR NA CASA →
            </button>

        </form>

    </div>

    <div class="info">
        <div class="info-card">🏆 Participe de provas</div>
        <div class="info-card">👑 Seja o campeão</div>
    </div>

</div>

<div class="entrada-casa" id="entradaCasa">

    <div class="porta-container">
        <div class="porta esquerda-porta"></div>
        <div class="porta direita-porta"></div>

        <div class="texto-entrada">
            <h1>🚪 Entrando na Casa</h1>
            <p>Mais Vigiada do Brasil</p>
        </div>
    </div>

</div>

<script src="assets/js/index_save.js"></script>

<script>
function entrarNaCasa(){

    const form = document.getElementById("formEntrada");

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const idade = Number(form.idade.value);

    if (idade < 18 || idade > 70) {
        mostrarMensagemIndex(
            "⚠️ A idade precisa estar entre 18 e 70 anos.",
            "erro"
        );
        return;
    }

    // Ao iniciar uma NOVA temporada, o save anterior deixa de valer.
    localStorage.removeItem("bbb_simulator_save_v1");

    const overlay = document.getElementById("entradaCasa");

    overlay.classList.add("ativo");

    document.body.style.pointerEvents = "none";

    setTimeout(() => {
        form.submit();
    }, 3000);
}
</script>

</body>
</html>
