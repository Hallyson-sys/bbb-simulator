<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>BBB Simulator</title>

<link rel="stylesheet" href="assets/css/index.css">

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

        <form action="config.php" method="POST" id="formEntrada" onsubmit="return false;">

            <input type="text" name="nome" placeholder="Digite seu nome" required>

            <input type="number" name="idade" placeholder="Digite sua idade" required>

            <select name="profissao" required>
                <option value="">Escolha sua profissão</option>
                <option>Influencer</option>
                <option>Professor(a)</option>
                <option>Youtuber</option>
                <option>Advogado(a)</option>
                <option>Policial</option>
                <option>Médico(a)</option>
                <option>Enfermeiro(a)</option>
                <option>Balconista</option>
                <option>Desempregado</option>
                <option>DJ</option>
                <option>Terapeuta</option>
                <option>Ator/Atriz</option>
                <option>Bombeiro(a)</option>
                <option>Personal Trainer</option>
                <option>Maquiador(a)</option>
                <option>Motorista de Aplicativo</option>
                <option>Nutricionista</option>
                <option>Barbeiro(a)</option>
                <option>Cabeleleiro(a)</option>
                <option>Cantor(a)</option>
                <option>Modelo</option>
                <option>Vendedor(a)</option>
                <option>Engenheiro(a)</option>
                <option>Arquiteto(a)</option>
                <option>Empresário</option>
                <option>Psicólogo</option>
                <option>Tatuador(a)</option>
                <option>Veterinário(a)</option>
                <option>Streamer</option>
                <option>Fotográfo(a)</option>
                <option>Comissário(a) de Bordo</option>
                <option>Assistente Social</option>
                <option>Esteticista</option>
                <option>Radialista</option>
            </select>

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

<script>
function entrarNaCasa(){

    const overlay = document.getElementById("entradaCasa");

    overlay.classList.add("ativo");

    // trava clique
    document.body.style.pointerEvents = "none";

    // envia depois da animação
    setTimeout(() => {
        document.getElementById("formEntrada").submit();
    }, 3000);
}
</script>

</body>
</html>