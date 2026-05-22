<?php

ini_set('display_errors',1);
error_reporting(E_ALL);

session_start();

if(!isset($_SESSION['jogadores'])){
    header("Location: index.php");
    exit;
}

$jogadores = $_SESSION['jogadores'];
$meuNome   = $_SESSION['meu_nome'] ?? '';

if(!isset($_SESSION['evento_extra'])){
    $_SESSION['evento_extra'] = [];
}

/* Se já passou pelo Big Fone nessa semana */
if(isset($_SESSION['bigfone_feito'])){
    header("Location: jogo.php");
    exit;
}

/* Decide se toca ou não */
if(!isset($_SESSION['bigfone_tocou'])){
    $_SESSION['bigfone_tocou'] = rand(1,100) <= 60;
}

/* Se não tocou */
if($_SESSION['bigfone_tocou'] == false){

    $_SESSION['evento_extra'][] = "☎️ O Big Fone não tocou nesta semana.";
    $_SESSION['bigfone_feito'] = true;

    header("Location: jogo.php");
    exit;
}

/* Função aplicar poder */
function aplicarPoderBigFone(&$jogadores, $atendente){

    $poderes = ["imunidade", "indicacao"];
    $poder = $poderes[array_rand($poderes)];

    $texto = "";

    if($poder == "imunidade"){

        foreach($jogadores as &$j){
            if($j['nome'] == $atendente){
                $j['status']['imune'] = true;
            }
        }

        $texto = "🛡️ $atendente ganhou imunidade pelo Big Fone.";
    }

    if($poder == "indicacao"){

        $_SESSION['bigfone_indicacao_pendente'] = true;
        $_SESSION['bigfone_dono_poder'] = $atendente;

        $texto = "🎯 $atendente ganhou o poder de indicar alguém direto ao paredão.";
    }

    $_SESSION['bigfone_poder'] = $poder;

    return $texto;
}

/* NPC atende */
function npcAtendeBigFone($jogadores, $meuNome){

    $npcs = [];

    foreach($jogadores as $j){
        if($j['nome'] != $meuNome){
            $npcs[] = $j['nome'];
        }
    }

    return $npcs[array_rand($npcs)];
}

/* Jogador não atendeu */
if(isset($_POST['nao_atender'])){

    $atendente = npcAtendeBigFone($jogadores, $meuNome);

    $_SESSION['evento_extra'][] = "☎️ O Big Fone tocou!";
    $_SESSION['evento_extra'][] = "🏃 $atendente correu e atendeu antes de todo mundo.";

    $textoPoder = aplicarPoderBigFone($jogadores, $atendente);

    $_SESSION['evento_extra'][] = $textoPoder;
    $_SESSION['jogadores'] = $jogadores;

    $_SESSION['bigfone_atendente'] = $atendente;
    $_SESSION['bigfone_feito'] = true;

    header("Location: jogo.php");
    exit;
}

/* Jogador atendeu */
if(isset($_POST['atender'])){

    $atendente = $meuNome;

    $_SESSION['evento_extra'][] = "☎️ O Big Fone tocou!";
    $_SESSION['evento_extra'][] = "🏃 $atendente correu e atendeu o Big Fone.";

    $textoPoder = aplicarPoderBigFone($jogadores, $atendente);

    $_SESSION['evento_extra'][] = $textoPoder;
    $_SESSION['jogadores'] = $jogadores;

    $_SESSION['bigfone_atendente'] = $atendente;
    $_SESSION['bigfone_feito'] = true;

    header("Location: jogo.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Big Fone</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Arial,sans-serif;
    background:radial-gradient(circle at top,#240014,#050510 75%);
    color:white;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    overflow:hidden;
}

body::before{
    content:"";
    position:fixed;
    inset:-50%;
    background:radial-gradient(circle,#ff006633,transparent 55%);
    animation:mover 6s linear infinite;
    z-index:0;
}

@keyframes mover{
    0%{transform:translate(-10%,-10%);}
    50%{transform:translate(6%,6%);}
    100%{transform:translate(-10%,-10%);}
}

.box{
    position:relative;
    z-index:2;
    width:620px;
    max-width:92%;
    padding:40px;
    border-radius:28px;
    text-align:center;
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.12);
    box-shadow:0 0 45px rgba(255,0,120,.45);
    animation:pulsar .8s infinite alternate;
}

@keyframes pulsar{
    from{box-shadow:0 0 25px rgba(255,0,120,.35);}
    to{box-shadow:0 0 70px rgba(255,0,120,.8);}
}

h1{
    font-size:48px;
    margin-bottom:18px;
    color:#ff2d75;
}

p{
    font-size:20px;
    line-height:1.6;
    margin-bottom:16px;
}

.timer{
    font-size:70px;
    font-weight:bold;
    color:#ffcc00;
    margin:20px 0;
}

.botoes{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
    margin-top:24px;
}

button{
    padding:16px;
    border:none;
    border-radius:15px;
    font-size:18px;
    font-weight:bold;
    cursor:pointer;
    color:white;
    transition:.25s;
}

.atender{
    background:linear-gradient(135deg,#ff0066,#6a00ff);
}

.nao{
    background:linear-gradient(135deg,#333,#111);
}

button:hover{
    transform:scale(1.04);
}

.aviso{
    opacity:.75;
    font-size:14px;
}
</style>
</head>

<body>

<div class="box">

<h1>☎️ BIG FONE TOCOU!</h1>

<p>O telefone mais temido da casa está tocando.</p>
<p>Você tem <b>3 segundos</b> para decidir se vai atender.</p>

<div class="timer" id="timer">3</div>

<form method="POST" id="formNao">
<input type="hidden" name="nao_atender" value="1">
</form>

<div class="botoes">

<form method="POST">
<button class="atender" name="atender">🏃 Atender</button>
</form>

<form method="POST">
<button class="nao" name="nao_atender">Não atender</button>
</form>

</div>

<p class="aviso">Se o tempo acabar, um participante aleatório atenderá.</p>

</div>

<script>
let tempo = 3;
const timer = document.getElementById("timer");

const intervalo = setInterval(() => {
    tempo--;
    timer.innerText = tempo;

    if(tempo <= 0){
        clearInterval(intervalo);
        document.getElementById("formNao").submit();
    }
}, 1000);
</script>

</body>
</html>