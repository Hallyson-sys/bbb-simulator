<?php

ini_set('display_errors',1);
error_reporting(E_ALL);

session_start();

/* ===============================
   VERIFICA SESSÃO
================================= */
if(!isset($_SESSION['jogadores'])){
    header("Location: index.php");
    exit;
}

$jogadores = $_SESSION['jogadores'];
$meuNome   = $_SESSION['meu_nome'] ?? 'Jogador';

/* ===============================
   LISTA DE PROVAS
================================= */
$provas = [

    1 => [
        'titulo' => '🎯 Mira do Líder',
        'texto'  => 'Escolha um número entre 1 e 5. Quem chegar mais perto vence.',
        'max'    => 5,
        'tipo'   => 'numero'
    ],

    2 => [
        'titulo' => '⚡ Reflexo BBB',
        'texto'  => 'Escolha rapidamente uma opção secreta.',
        'max'    => 3,
        'tipo'   => 'numero'
    ],

    3 => [
        'titulo' => '🧩 Memória da Casa',
        'texto'  => 'Escolha uma porta de 1 a 3.',
        'max'    => 3,
        'tipo'   => 'numero'
    ],

    4 => [
        'titulo' => '🎲 Dado da Sorte',
        'texto'  => 'Escolha um número de 1 a 6. Se seu número for o sorteado, você vence.',
        'max'    => 6,
        'tipo'   => 'numero'
    ],

    5 => [
        'titulo' => '🏹 Alvo Premiado',
        'texto'  => 'Escolha um alvo. Um deles esconde a liderança.',
        'max'    => 5,
        'tipo'   => 'alvo'
    ],

    6 => [
        'titulo' => '💎 Cofre Misterioso',
        'texto'  => 'Escolha um cofre. Apenas um guarda a chave do quarto do Líder.',
        'max'    => 4,
        'tipo'   => 'cofre'
    ],

    7 => [
        'titulo' => '🚀 Decolagem BBB',
        'texto'  => 'Escolha uma nave. A nave certa dispara rumo à liderança.',
        'max'    => 3,
        'tipo'   => 'nave'
    ],

    8 => [
        'titulo' => '🎰 Slot da Sorte',
        'texto'  => 'Escolha um símbolo. Se ele aparecer no sorteio, você vence.',
        'max'    => 4,
        'tipo'   => 'simbolo'
    ],

    9 => [
        'titulo' => '📦 Caixa Surpresa',
        'texto'  => 'Escolha uma caixa. Uma delas contém o poder da liderança.',
        'max'    => 5,
        'tipo'   => 'caixa'
    ],

    10 => [
        'titulo' => '🌪️ Giro da Liderança',
        'texto'  => 'Escolha uma cor. A roleta vai decidir quem leva a liderança.',
        'max'    => 4,
        'tipo'   => 'cor'
    ],

    11 => [
        'titulo' => '🔥 Totem do Líder',
        'texto'  => 'Escolha um totem. O totem correto acende e garante a liderança.',
        'max'    => 5,
        'tipo'   => 'totem'
    ]

];

/* ===============================
   ESCOLHE PROVA DA SEMANA
================================= */
if(!isset($_SESSION['prova_tipo'])){
    $_SESSION['prova_tipo'] = rand(1,11);
}

$tipoProva = $_SESSION['prova_tipo'];
$prova     = $provas[$tipoProva] ?? $provas[1];

/* ===============================
   FUNÇÕES
================================= */
function nomesNPCsProva($jogadores, $meuNome){
    $npcs = [];

    foreach($jogadores as $j){
        if(($j['nome'] ?? '') != $meuNome){
            $npcs[] = $j['nome'];
        }
    }

    return $npcs;
}

function sortearNPCVencedor($jogadores, $meuNome){
    $npcs = nomesNPCsProva($jogadores, $meuNome);

    if(empty($npcs)){
        return $meuNome;
    }

    return $npcs[array_rand($npcs)];
}

function disputaPorSorte($jogadores, $meuNome, $escolha, $maximo){
    $segredo = rand(1, $maximo);

    if((int)$escolha === $segredo){
        return $meuNome;
    }

    return sortearNPCVencedor($jogadores, $meuNome);
}

/* ===============================
   PROCESSAR JOGADA
================================= */
if(isset($_POST['jogar'])){

    $lider = null;
    $escolha = $_POST['escolha'] ?? '';

    /* ===========================
       PROVA 1 - MIRA
    =========================== */
    if($tipoProva == 1){

        $alvo = rand(1,5);
        $escolhaNumero = (int)$escolha;

        $distPlayer = abs($alvo - $escolhaNumero);

        $melhorNpc = 99;
        $npcNome   = '';

        foreach($jogadores as $j){

            if(($j['nome'] ?? '') == $meuNome) continue;

            $npcEscolha = rand(1,5);
            $distNpc = abs($alvo - $npcEscolha);

            if($distNpc < $melhorNpc){
                $melhorNpc = $distNpc;
                $npcNome = $j['nome'];
            }
        }

        $lider = ($distPlayer <= $melhorNpc) ? $meuNome : $npcNome;
    }

    /* ===========================
       PROVAS DE SORTE SIMPLES
    =========================== */
    if($tipoProva >= 2 && $tipoProva <= 11){
        $lider = disputaPorSorte($jogadores, $meuNome, $escolha, $prova['max']);
    }

    if($lider == null || $lider == ''){
        $lider = sortearNPCVencedor($jogadores, $meuNome);
    }

    /* ===============================
       SALVAR LÍDER
    ================================= */
    $_SESSION['lider'] = $lider;

    foreach($_SESSION['jogadores'] as &$j){

        $j['status']['lider'] = false;

        if(($j['nome'] ?? '') == $lider){

            $j['status']['lider'] = true;

            if(!isset($j['estatisticas'])){
                $j['estatisticas'] = [];
            }

            $j['estatisticas']['lider'] =
            ($j['estatisticas']['lider'] ?? 0) + 1;
        }
    }
    unset($j);

    $_SESSION['mensagem_lider'] =
    ($lider == $meuNome)
    ? "🏆 Você venceu a Prova do Líder!"
    : "👑 $lider venceu a Prova do Líder!";

    unset($_SESSION['prova_tipo']);

    if(!isset($_SESSION['evento_extra'])){
        $_SESSION['evento_extra'] = [];
    }

    $_SESSION['evento_extra'][] =
    "👑 ".$lider." venceu a Prova do Líder.";

    $_SESSION['evento_extra'][] =
    "🗣️ \"Parabéns! O reinado começou.\"";

    header("Location: jogo.php");
    exit;
}

function textoBotaoProva($tipo, $i){
    if($tipo == 'alvo') return "🎯 Alvo $i";
    if($tipo == 'cofre') return "💎 Cofre $i";
    if($tipo == 'nave'){
        $letras = ["A","B","C","D","E"];
        return "🚀 Nave ".$letras[$i-1];
    }
    if($tipo == 'simbolo'){
        $simbolos = ["🍒","⭐","💎","🎯"];
        return $simbolos[$i-1] ?? "Símbolo $i";
    }
    if($tipo == 'caixa') return "📦 Caixa $i";
    if($tipo == 'cor'){
        $cores = ["Rosa","Azul","Dourado","Roxo"];
        return "🌪️ ".$cores[$i-1];
    }
    if($tipo == 'totem') return "🔥 Totem $i";

    return (string)$i;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Prova do Líder</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
}

body{
font-family:Arial,sans-serif;
background:
radial-gradient(circle at top left, rgba(255,0,120,.22), transparent 35%),
radial-gradient(circle at top right, rgba(255,204,0,.18), transparent 35%),
radial-gradient(circle at bottom, #1b1438, #050510 75%);
color:white;
min-height:100vh;
display:flex;
justify-content:center;
align-items:center;
padding:30px;
}

.box{
width:760px;
max-width:100%;
background:rgba(255,255,255,.06);
border:1px solid rgba(255,255,255,.12);
border-radius:28px;
padding:36px;
box-shadow:0 0 40px rgba(0,0,0,.48);
text-align:center;
backdrop-filter:blur(14px);
}

h1{
font-size:38px;
margin-bottom:15px;
background:linear-gradient(90deg,#ff0077,#ffcc00,#00d9ff);
-webkit-background-clip:text;
background-clip:text;
-webkit-text-fill-color:transparent;
color:transparent;
}

p{
font-size:18px;
opacity:.92;
margin-bottom:25px;
line-height:1.6;
}

.grid{
display:grid;
grid-template-columns:repeat(3,1fr);
gap:14px;
margin-top:15px;
}

button{
padding:18px;
border:none;
border-radius:16px;
font-size:18px;
font-weight:bold;
cursor:pointer;
color:white;
background:linear-gradient(135deg,#ff0066,#6a00ff);
transition:.25s;
min-height:62px;
}

button:hover{
transform:translateY(-3px) scale(1.02);
box-shadow:0 0 22px rgba(255,0,140,.38);
}

.info{
margin-top:22px;
font-size:14px;
opacity:.76;
line-height:1.5;
}

@media(max-width:700px){
.grid{
grid-template-columns:1fr;
}
}

</style>
</head>
<body>

<div class="box">

<h1><?php echo $prova['titulo']; ?></h1>

<p><?php echo $prova['texto']; ?></p>

<form method="POST">

<div class="grid">

<?php for($i=1;$i<=$prova['max'];$i++): ?>

<button type="submit" name="escolha" value="<?php echo $i; ?>">
<?php echo textoBotaoProva($prova['tipo'], $i); ?>
</button>

<?php endfor; ?>

</div>

<input type="hidden" name="jogar" value="1">

</form>

<div class="info">
👑 O vencedor assume a liderança da rodada.<br>
🎲 Algumas provas são de sorte, outras comparam sua escolha com a dos participantes.
</div>

</div>

</body>
</html>
