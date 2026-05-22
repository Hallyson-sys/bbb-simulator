<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
session_start();

if(!isset($_SESSION['jogadores'])){
    header("Location: index.php");
    exit;
}

$jogadores = $_SESSION['jogadores'];
$meuNome = $_SESSION['meu_nome'] ?? '';

if(!isset($_SESSION['evento_extra'])){
    $_SESSION['evento_extra'] = [];
}

function limitar($valor, $min = 0, $max = 100){
    return max($min, min($max, $valor));
}

function alterarAfinidade(&$jogadores, $nomeA, $nomeB, $amizade = 0, $rivalidade = 0, $confianca = 0){
    foreach($jogadores as &$j){
        if($j['nome'] == $nomeA){
            if(!isset($j['relacoes'][$nomeB])){
                $j['relacoes'][$nomeB] = [
                    "amizade" => 50,
                    "rivalidade" => 0,
                    "confianca" => 50
                ];
            }

            $j['relacoes'][$nomeB]['amizade'] = limitar($j['relacoes'][$nomeB]['amizade'] + $amizade);
            $j['relacoes'][$nomeB]['rivalidade'] = limitar($j['relacoes'][$nomeB]['rivalidade'] + $rivalidade);
            $j['relacoes'][$nomeB]['confianca'] = limitar($j['relacoes'][$nomeB]['confianca'] + $confianca);
        }
    }
}

function alterarPopularidade(&$jogadores, $nome, $valor){
    foreach($jogadores as &$j){
        if($j['nome'] == $nome){
            $j['popularidade'] = limitar(($j['popularidade'] ?? 50) + $valor);
        }
    }
}

$temas = [
    "sonso" => "😴 Quem é o mais sonso?",
    "falso" => "🐍 Quem é o mais falso?",
    "saboneteiro" => "🧼 Quem é o mais saboneteiro?",
    "aliado" => "🤝 Quem é seu maior aliado?",
    "podio" => "🏆 Monte seu pódio"
];

if(!isset($_SESSION['tema_discordia'])){
    $chaves = array_keys($temas);
    $_SESSION['tema_discordia'] = $chaves[array_rand($chaves)];
}

$tema = $_SESSION['tema_discordia'];

if(isset($_POST['confirmar_discordia'])){

    $modo = $_POST['modo'] ?? 'leve';
    $alvo = $_POST['alvo'] ?? '';
    $podio2 = $_POST['podio2'] ?? '';
    $podio3 = $_POST['podio3'] ?? '';

    if($tema == "podio"){
        if($podio2 && $podio3 && $podio2 != $podio3){
            alterarAfinidade($jogadores, $podio2, $meuNome, 8, -2, 6);
            alterarAfinidade($jogadores, $podio3, $meuNome, 5, -1, 4);

            $_SESSION['evento_extra'][] =
            "🏆 $meuNome montou seu pódio: 1º $meuNome, 2º $podio2 e 3º $podio3.";
        }
    }else{
        if($alvo){
            if($tema == "aliado"){
                alterarAfinidade($jogadores, $alvo, $meuNome, 12, -5, 10);

                $_SESSION['evento_extra'][] =
                "🤝 $meuNome escolheu $alvo como seu maior aliado no Jogo da Discórdia.";
            }else{
                if($modo == "com_tudo"){
                    alterarAfinidade($jogadores, $alvo, $meuNome, -15, 12, -10);
                    alterarPopularidade($jogadores, $meuNome, rand(1,6));

                    $_SESSION['evento_extra'][] =
                    "🔥 $meuNome foi com tudo e escolheu $alvo em: ".$temas[$tema].".";
                }elseif($modo == "leve"){
                    alterarAfinidade($jogadores, $alvo, $meuNome, -6, 5, -4);

                    $_SESSION['evento_extra'][] =
                    "😶 $meuNome pegou leve, mas escolheu $alvo em: ".$temas[$tema].".";
                }else{
                    alterarPopularidade($jogadores, $meuNome, -5);

                    $_SESSION['evento_extra'][] =
                    "🧼 $meuNome tentou sabonetar no Jogo da Discórdia.";
                }
            }
        }
    }

    foreach($jogadores as $npc){
        if($npc['nome'] == $meuNome) continue;

        $nomeNpc = $npc['nome'];
        $opcoes = [];

        foreach($jogadores as $j){
            if($j['nome'] != $nomeNpc){
                $opcoes[] = $j['nome'];
            }
        }

        shuffle($opcoes);

        if($tema == "podio"){
            $_SESSION['evento_extra'][] =
            "🏆 $nomeNpc montou seu pódio: 1º $nomeNpc, 2º ".$opcoes[0]." e 3º ".$opcoes[1].".";
        }elseif($tema == "aliado"){
            alterarAfinidade($jogadores, $opcoes[0], $nomeNpc, 8, -3, 6);

            $_SESSION['evento_extra'][] =
            "🤝 $nomeNpc escolheu ".$opcoes[0]." como maior aliado.";
        }else{
            alterarAfinidade($jogadores, $opcoes[0], $nomeNpc, -8, 7, -5);

            $_SESSION['evento_extra'][] =
            "🔥 $nomeNpc escolheu ".$opcoes[0]." em: ".$temas[$tema].".";
        }
    }

    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['discordia_feito'] = true;
    $_SESSION['fase_semana'] = 'interacoes_3';
    $_SESSION['acoes_restantes'] = 3;

    unset($_SESSION['tema_discordia']);

    header("Location: jogo.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Jogo da Discórdia</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;}

body{
font-family:Arial,sans-serif;
min-height:100vh;
background:radial-gradient(circle at top,#31104d,#070713 75%);
color:white;
display:flex;
align-items:center;
justify-content:center;
padding:30px;
overflow-x:hidden;
}

body::before{
content:"";
position:fixed;
inset:-40%;
background:radial-gradient(circle,#ff006633,transparent 55%);
animation:mover 8s linear infinite;
z-index:0;
}

@keyframes mover{
0%{transform:translate(-10%,-10%);}
50%{transform:translate(5%,5%);}
100%{transform:translate(-10%,-10%);}
}

.container{
position:relative;
z-index:2;
width:100%;
max-width:950px;
background:rgba(255,255,255,.06);
border:1px solid rgba(255,255,255,.12);
border-radius:28px;
padding:32px;
box-shadow:0 0 45px rgba(255,0,120,.35);
backdrop-filter:blur(14px);
}

h1{
text-align:center;
font-size:42px;
margin-bottom:12px;
background:linear-gradient(90deg,#ff0066,#ffcc00,#00ffee,#a000ff);
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

.tema{
text-align:center;
font-size:25px;
font-weight:bold;
margin-bottom:28px;
padding:18px;
border-radius:18px;
background:rgba(0,0,0,.35);
box-shadow:0 0 20px rgba(255,0,140,.22);
}

.subtitulo{
font-size:20px;
margin:20px 0 12px;
}

.grid{
display:grid;
grid-template-columns:repeat(4,1fr);
gap:12px;
margin-bottom:22px;
}

.card-radio{
display:block;
padding:15px;
border-radius:16px;
background:rgba(255,255,255,.06);
border:1px solid rgba(255,255,255,.12);
text-align:center;
font-weight:bold;
cursor:pointer;
transition:.25s;
}

.card-radio:hover{
transform:translateY(-4px);
background:linear-gradient(135deg,#ff0066,#6a00ff);
box-shadow:0 0 22px rgba(255,0,140,.45);
}

.card-radio input{
display:none;
}

.card-radio:has(input:checked){
background:linear-gradient(135deg,#ff0066,#6a00ff);
box-shadow:0 0 25px rgba(255,0,140,.65);
transform:scale(1.03);
}

.modos{
display:grid;
grid-template-columns:repeat(3,1fr);
gap:14px;
margin-bottom:25px;
}

.modo{
padding:18px;
border-radius:18px;
background:rgba(0,0,0,.35);
border:1px solid rgba(255,255,255,.12);
text-align:center;
cursor:pointer;
transition:.25s;
}

.modo strong{
display:block;
font-size:18px;
margin-bottom:6px;
}

.modo span{
font-size:13px;
opacity:.8;
}

.modo input{
display:none;
}

.modo:hover{
transform:translateY(-4px);
box-shadow:0 0 22px rgba(255,0,140,.4);
}

.modo:has(input:checked){
background:linear-gradient(135deg,#ff0066,#6a00ff);
box-shadow:0 0 25px rgba(255,0,140,.65);
}

.btn{
width:100%;
padding:18px;
border:none;
border-radius:18px;
font-size:20px;
font-weight:bold;
color:white;
cursor:pointer;
background:linear-gradient(135deg,#ff0066,#ffcc00);
transition:.25s;
}

.btn:hover{
transform:scale(1.03);
box-shadow:0 0 25px rgba(255,0,100,.55);
}

.voltar{
display:block;
text-align:center;
text-decoration:none;
color:white;
margin-top:12px;
padding:15px;
border-radius:16px;
background:linear-gradient(135deg,#333,#111);
}

@media(max-width:850px){
.grid{grid-template-columns:1fr 1fr;}
.modos{grid-template-columns:1fr;}
}
</style>
</head>

<body>

<div class="container">

<h1>🔥 Jogo da Discórdia</h1>

<div class="tema">
<?php echo $temas[$tema]; ?>
</div>

<form method="POST">

<?php if($tema != "podio"): ?>

<div class="subtitulo">Escolha um participante:</div>

<div class="grid">
<?php foreach($jogadores as $j): ?>
<?php if($j['nome'] != $meuNome): ?>

<label class="card-radio">
<input type="radio" name="alvo" value="<?php echo $j['nome']; ?>" required>
<?php echo $j['nome']; ?>
</label>

<?php endif; ?>
<?php endforeach; ?>
</div>

<div class="subtitulo">Como você quer falar?</div>

<div class="modos">

<label class="modo">
<input type="radio" name="modo" value="com_tudo" required>
<strong>🔥 Com tudo</strong>
<span>Você se posiciona sem medo.</span>
</label>

<label class="modo">
<input type="radio" name="modo" value="leve" required>
<strong>😶 De leve</strong>
<span>Você fala, mas pega leve.</span>
</label>

<label class="modo">
<input type="radio" name="modo" value="saboneteiro" required>
<strong>🧼 Saboneteiro</strong>
<span>Você foge da pergunta.</span>
</label>

</div>

<?php else: ?>

<div class="subtitulo">🏆 Seu pódio começa com você em 1º lugar.</div>

<div class="subtitulo">Escolha o 2º lugar:</div>

<div class="grid">
<?php foreach($jogadores as $j): ?>
<?php if($j['nome'] != $meuNome): ?>

<label class="card-radio">
<input type="radio" name="podio2" value="<?php echo $j['nome']; ?>" required>
<?php echo $j['nome']; ?>
</label>

<?php endif; ?>
<?php endforeach; ?>
</div>

<div class="subtitulo">Escolha o 3º lugar:</div>

<div class="grid">
<?php foreach($jogadores as $j): ?>
<?php if($j['nome'] != $meuNome): ?>

<label class="card-radio">
<input type="radio" name="podio3" value="<?php echo $j['nome']; ?>" required>
<?php echo $j['nome']; ?>
</label>

<?php endif; ?>
<?php endforeach; ?>
</div>

<input type="hidden" name="modo" value="leve">

<?php endif; ?>

<button class="btn" name="confirmar_discordia">
🔥 Confirmar no Ao Vivo
</button>

</form>

<a class="voltar" href="jogo.php">⬅️ Voltar</a>

</div>

</body>
</html>