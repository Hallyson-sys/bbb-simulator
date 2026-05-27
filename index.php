<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>BBB Simulator</title>

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Orbitron',sans-serif;
    background:radial-gradient(circle at top,#2a003f,#050510 75%);
    color:white;
    min-height:100vh;
    overflow:hidden;
}

body::before{
    content:"";
    position:fixed;
    inset:-50%;
    background:radial-gradient(circle,#ff00cc33,transparent 55%);
    animation:mover 9s linear infinite;
    z-index:0;
    pointer-events:none;
}

@keyframes mover{
    0%{transform:translate(-10%,-10%);}
    50%{transform:translate(5%,5%);}
    100%{transform:translate(-10%,-10%);}
}

.container{
    position:relative;
    z-index:2;
    width:100vw;
    min-height:100vh;

    display:grid;
    grid-template-columns:230px 420px 230px;
    justify-content:center;
    align-items:center;
    gap:60px;
}

.info{
    width:230px;
    display:flex;
    flex-direction:column;
    gap:28px;
}

.info-card{
    width:230px;
    height:105px;

    display:flex;
    align-items:center;
    justify-content:center;

    padding:18px;
    border-radius:20px;
    text-align:center;
    font-size:14px;
    line-height:1.4;

    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.14);
    box-shadow:0 0 20px rgba(255,0,180,.18);

    transition:.3s;
}

.info-card:hover{
    transform:translateY(-6px) scale(1.04);
    box-shadow:0 0 28px rgba(255,0,180,.55);
}

.card{
    width:420px;
    max-height:88vh;
    overflow-y:auto;

    padding:30px;
    border-radius:28px;

    background:rgba(255,255,255,.06);
    backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,.14);
    box-shadow:0 0 35px rgba(255,0,180,.35);
}

.card::-webkit-scrollbar{
    display:none;
}

.titulo{
    text-align:center;
    font-size:42px;
    line-height:1.12;
    font-weight:900;
    margin-bottom:10px;

    background:linear-gradient(90deg,#00c6ff,#ff00cc,#ffcc00,#00ffee);
    background-size:300%;

    -webkit-background-clip:text;
    background-clip:text;
    -webkit-text-fill-color:transparent;

    animation:brilho 4s linear infinite;
}

@keyframes brilho{
    0%{background-position:0%;}
    100%{background-position:300%;}
}

.sub{
    text-align:center;
    font-size:12px;
    opacity:.75;
    margin-bottom:24px;
}

input,select{
    width:100%;
    padding:13px;
    margin-bottom:13px;
    border:none;
    border-radius:14px;
    background:rgba(0,0,0,.45);
    color:white;
    font-size:13px;
    outline:none;
}

select option{
    color:black;
    background:white;
}

input:focus,select:focus{
    box-shadow:0 0 14px #ff00cc;
}

button{
    width:100%;
    padding:15px;
    border:none;
    border-radius:16px;
    font-size:15px;
    font-weight:bold;
    color:white;
    cursor:pointer;
    background:linear-gradient(135deg,#ff0066,#ffcc00);
    transition:.25s;
}

button:hover{
    transform:scale(1.04);
    box-shadow:0 0 22px #ff0066;
}

.entrada-casa{
    position:fixed;
    inset:0;
    background:radial-gradient(circle at center,#1a0033,#020208 75%);
    display:flex;
    justify-content:center;
    align-items:center;
    z-index:9999;
    opacity:0;
    pointer-events:none;
    transition:.5s;
}

.entrada-casa.ativo{
    opacity:1;
    pointer-events:all;
}

.porta-container{
    position:relative;
    width:520px;
    height:360px;
    overflow:hidden;
    border-radius:25px;
    box-shadow:
        0 0 40px rgba(255,0,180,.5),
        inset 0 0 40px rgba(0,255,255,.15);
}

.porta{
    position:absolute;
    top:0;
    width:50%;
    height:100%;
    background:
        linear-gradient(135deg,#15001f,#3a005f,#120018);
    border:2px solid rgba(255,255,255,.12);
    z-index:2;
}

.esquerda-porta{
    left:0;
}

.direita-porta{
    right:0;
}

.entrada-casa.ativo .esquerda-porta{
    animation:abrirEsquerda 2.6s ease forwards;
}

.entrada-casa.ativo .direita-porta{
    animation:abrirDireita 2.6s ease forwards;
}

@keyframes abrirEsquerda{
    0%{transform:translateX(0);}
    100%{transform:translateX(-100%);}
}

@keyframes abrirDireita{
    0%{transform:translateX(0);}
    100%{transform:translateX(100%);}
}

.texto-entrada{
    position:absolute;
    inset:0;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    text-align:center;
    background:
        radial-gradient(circle,#ff00cc33,transparent 60%);
    z-index:1;
}

.texto-entrada h1{
    font-size:38px;
    margin-bottom:12px;
    background:linear-gradient(90deg,#ff00cc,#ffcc00,#00ffee);
    background-size:300%;
    -webkit-background-clip:text;
    background-clip:text;
    -webkit-text-fill-color:transparent;
    animation:brilho 3s linear infinite;
}

.texto-entrada p{
    font-size:22px;
    letter-spacing:2px;
    opacity:.9;
}

@media(max-width:1000px){

body{
    overflow-y:auto;
    overflow-x:hidden;
    min-height:100vh;
    padding:20px 0;
}

body::before{
    inset:-120%;
}

.container{

    width:100%;
    min-height:auto;

    display:flex;
    flex-direction:column;

    justify-content:flex-start;
    align-items:center;

    gap:22px;

    padding:20px 16px 40px;
}

/* CARDS LATERAIS */
.info{

    width:100%;
    max-width:420px;

    display:grid;
    grid-template-columns:1fr 1fr;

    gap:12px;
}

.info-card{

    width:100%;
    height:auto;

    min-height:85px;

    padding:14px;

    font-size:12px;

    border-radius:16px;
}

/* FORMULÁRIO */
.card{

    width:100%;
    max-width:420px;

    max-height:none;

    overflow:visible;

    padding:24px 18px;

    border-radius:24px;
}

.titulo{
    font-size:34px;
    line-height:1.05;
}

.sub{
    font-size:11px;
    margin-bottom:20px;
}

input,
select{

    padding:15px;

    font-size:16px;

    border-radius:14px;

    margin-bottom:12px;
}

button{

    padding:16px;

    font-size:16px;

    border-radius:16px;
}

/* ANIMAÇÃO PORTA */
.porta-container{

    width:92vw;
    height:260px;

    border-radius:22px;
}

.texto-entrada h1{
    font-size:28px;
}

.texto-entrada p{
    font-size:16px;
}
}
</style>
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
