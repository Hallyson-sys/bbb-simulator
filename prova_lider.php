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
'texto'  => 'Escolha um número entre 1 e 5. Quem chegar mais perto vence.'
],

2 => [
'titulo' => '⚡ Reflexo BBB',
'texto'  => 'Escolha rapidamente uma opção secreta.'
],

3 => [
'titulo' => '🧩 Memória da Casa',
'texto'  => 'Escolha uma porta de 1 a 3.'
]

];

/* ===============================
   ESCOLHE PROVA DA SEMANA
================================= */
if(!isset($_SESSION['prova_tipo'])){
    $_SESSION['prova_tipo'] = rand(1,3);
}

$tipoProva = $_SESSION['prova_tipo'];
$prova     = $provas[$tipoProva];

/* ===============================
   PROCESSAR JOGADA
================================= */
if(isset($_POST['jogar'])){

    $lider = null;

    /* ===========================
       PROVA 1 - MIRA
    =========================== */
    if($tipoProva == 1){

        $alvo = rand(1,5);
        $escolha = (int)$_POST['escolha'];

        $distPlayer = abs($alvo - $escolha);

        $melhorNpc = 99;
        $npcNome   = '';

        foreach($jogadores as $j){

            if($j['nome'] == $meuNome) continue;

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
       PROVA 2 - REFLEXO
    =========================== */
    if($tipoProva == 2){

        $certa = rand(1,3);
        $escolha = (int)$_POST['escolha'];

        if($escolha == $certa){
            $lider = $meuNome;
        }else{
            $npcs = [];
            foreach($jogadores as $j){
                if($j['nome'] != $meuNome){
                    $npcs[] = $j['nome'];
                }
            }
            $lider = $npcs[array_rand($npcs)];
        }
    }

    /* ===========================
       PROVA 3 - PORTAS
    =========================== */
    if($tipoProva == 3){

        $portaPremiada = rand(1,3);
        $porta = (int)$_POST['escolha'];

        if($porta == $portaPremiada){
            $lider = $meuNome;
        }else{
            $npcs = [];
            foreach($jogadores as $j){
                if($j['nome'] != $meuNome){
                    $npcs[] = $j['nome'];
                }
            }
            $lider = $npcs[array_rand($npcs)];
        }
    }

    /* ===============================
       SALVAR LÍDER
    ================================= */
    $_SESSION['lider'] = $lider;

    foreach($_SESSION['jogadores'] as &$j){

        $j['status']['lider'] = false;
    
        if($j['nome'] == $lider){
    
            $j['status']['lider'] = true;
    
            /* ESTATÍSTICAS */
            if(!isset($j['estatisticas'])){
                $j['estatisticas'] = [];
            }
    
            $j['estatisticas']['lider'] =
            ($j['estatisticas']['lider'] ?? 0) + 1;
        }
    }

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
background:radial-gradient(circle at top,#1b1438,#050510 75%);
color:white;
min-height:100vh;
display:flex;
justify-content:center;
align-items:center;
padding:30px;
}

.box{
width:700px;
max-width:100%;
background:rgba(255,255,255,.05);
border-radius:24px;
padding:35px;
box-shadow:0 0 35px rgba(0,0,0,.45);
text-align:center;
}

h1{
font-size:34px;
margin-bottom:15px;
background:linear-gradient(90deg,#ff0077,#ffcc00);
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
border-radius:14px;
font-size:20px;
font-weight:bold;
cursor:pointer;
color:white;
background:linear-gradient(135deg,#ff0066,#6a00ff);
transition:.25s;
}

button:hover{
transform:translateY(-3px) scale(1.02);
box-shadow:0 0 18px rgba(255,0,140,.35);
}

.info{
margin-top:20px;
font-size:14px;
opacity:.7;
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

<?php
$max = ($tipoProva == 1) ? 5 : 3;

for($i=1;$i<=$max;$i++):
?>

<button type="submit" name="escolha" value="<?php echo $i; ?>">
<?php echo $i; ?>
</button>

<?php endfor; ?>

</div>

<input type="hidden" name="jogar" value="1">

</form>

<div class="info">
👑 O vencedor assume a liderança da rodada.
</div>

</div>

</body>
</html>