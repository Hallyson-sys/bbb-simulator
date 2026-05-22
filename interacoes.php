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

if(!isset($_SESSION['acoes_restantes'])){
    $_SESSION['acoes_restantes'] = 3;
}

/* ===============================
   FUNÇÕES
================================= */

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

            $j['relacoes'][$nomeB]['amizade'] =
            limitar($j['relacoes'][$nomeB]['amizade'] + $amizade);

            $j['relacoes'][$nomeB]['rivalidade'] =
            limitar($j['relacoes'][$nomeB]['rivalidade'] + $rivalidade);

            $j['relacoes'][$nomeB]['confianca'] =
            limitar($j['relacoes'][$nomeB]['confianca'] + $confianca);
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

/* ===============================
   FINALIZAR INTERAÇÕES
================================= */

if(isset($_POST['finalizar'])){

    $faseAtual = $_SESSION['fase_semana'] ?? 'interacoes_1';

    $_SESSION['interacoes_concluidas'][$faseAtual] = true;

    unset($_SESSION['acoes_restantes']);

    if($faseAtual == 'interacoes_1'){
        $_SESSION['fase_semana'] = 'lider';
    }

    if($faseAtual == 'interacoes_2'){
        $_SESSION['fase_semana'] = 'festa';
    }

    if($faseAtual == 'interacoes_3'){
        $_SESSION['fase_semana'] = 'eliminacao';
    }

    header("Location: jogo.php");
    exit;
}

/* ===============================
   PROCESSAR AÇÃO
================================= */

if(isset($_POST['acao']) && $_SESSION['acoes_restantes'] > 0){

    $acao = $_POST['acao'];
    $alvo = $_POST['alvo'] ?? '';
    $alvo2 = $_POST['alvo2'] ?? '';

    $evento = "";

    if($acao == "conversar" && $alvo){

        alterarAfinidade($jogadores, $meuNome, $alvo, 5, -2, 3);
        alterarAfinidade($jogadores, $alvo, $meuNome, 5, -2, 3);

        $evento = "💬 $meuNome conversou com $alvo e ganhou afinidade.";
    }

    if($acao == "fofoca" && $alvo){

        $acreditou = rand(1,100) <= 60;

        if($acreditou){

            foreach($jogadores as $j){

                if($j['nome'] != $meuNome && $j['nome'] != $alvo){
                    alterarAfinidade($jogadores, $j['nome'], $alvo, rand(-10,-3), rand(3,8), rand(-8,-3));
                }
            }

            $evento = "🗣️ $meuNome espalhou uma fofoca sobre $alvo, e parte da casa acreditou.";
        }else{

            alterarPopularidade($jogadores, $meuNome, rand(-5,-2));

            $evento = "🗣️ $meuNome tentou fazer fofoca sobre $alvo, mas a casa não acreditou.";
        }
    }

    if($acao == "intriga" && $alvo && $alvo2 && $alvo != $alvo2){

        $funcionou = rand(1,100) <= 55;

        if($funcionou){

            alterarAfinidade($jogadores, $alvo, $alvo2, rand(-15,-5), rand(5,15), rand(-12,-5));
            alterarAfinidade($jogadores, $alvo2, $alvo, rand(-15,-5), rand(5,15), rand(-12,-5));

            $evento = "🔥 $meuNome criou intriga entre $alvo e $alvo2.";
        }else{

            alterarAfinidade($jogadores, $alvo, $meuNome, rand(-10,-5), rand(5,10), rand(-10,-5));
            alterarAfinidade($jogadores, $alvo2, $meuNome, rand(-10,-5), rand(5,10), rand(-10,-5));

            $evento = "🔥 $meuNome tentou criar intriga, mas $alvo e $alvo2 desconfiaram.";
        }
    }

    if($acao == "alianca" && $alvo){

        $chance = rand(1,100);

        if($chance <= 65){

            alterarAfinidade($jogadores, $meuNome, $alvo, 10, -5, 10);
            alterarAfinidade($jogadores, $alvo, $meuNome, 10, -5, 10);

            $evento = "🤝 $meuNome criou uma aliança secreta com $alvo.";
        }else{

            alterarAfinidade($jogadores, $alvo, $meuNome, -3, 2, -5);

            $evento = "🤝 $meuNome tentou criar aliança com $alvo, mas foi recusado.";
        }
    }

    if($acao == "aproximar_lider"){

        $lider = $_SESSION['lider'] ?? '';

        if($lider && $lider != $meuNome){

            $ganho = rand(5,10);

            alterarAfinidade($jogadores, $meuNome, $lider, $ganho, -3, 6);
            alterarAfinidade($jogadores, $lider, $meuNome, $ganho, -3, 6);

            $evento = "👑 $meuNome tentou se aproximar do líder $lider.";
        }else{
            $evento = "👑 Não há líder disponível para se aproximar agora.";
        }
    }

    if($acao == "fazer_vt"){

        $mudanca = rand(-3,10);

        alterarPopularidade($jogadores, $meuNome, $mudanca);

        if($mudanca >= 0){
            $evento = "📺 $meuNome fez VT e ganhou popularidade com o público.";
        }else{
            $evento = "📺 $meuNome tentou fazer VT, mas o público achou forçado.";
        }
    }

    if($acao == "ficar_na_sua"){

        $perda = rand(0,3);

        alterarPopularidade($jogadores, $meuNome, -$perda);

        $evento = "😶 $meuNome ficou na sua e evitou conflitos.";
    }

    if($evento != ""){

        $_SESSION['evento_extra'][] = $evento;
        $_SESSION['jogadores'] = $jogadores;
        $_SESSION['acoes_restantes']--;
    }

    header("Location: interacoes.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Interações</title>

<style>
*{
margin:0;
padding:0;
box-sizing:border-box;
}

body{
font-family:Arial,sans-serif;
background:radial-gradient(circle at top,#22173f,#050510 75%);
color:white;
min-height:100vh;
padding:30px;
}

.container{
max-width:900px;
margin:auto;
}

.box{
background:rgba(255,255,255,.05);
padding:18px;
border-radius:18px;
box-shadow:0 0 25px rgba(0,0,0,.35);
margin-bottom:14px;
}

h1{
text-align:center;
font-size:38px;
margin-bottom:10px;
}

.sub{
text-align:center;
opacity:.85;
margin-bottom:25px;
}

form{
margin-bottom:18px;
}

label{
display:block;
margin-bottom:8px;
font-weight:bold;
}

select{
width:100%;
padding:10px;
border:none;
border-radius:12px;
font-size:16px;
margin-bottom:12px;
}

button{
width:100%;
padding:11px;
border:none;
border-radius:12px;
font-size:14px;
font-weight:bold;
cursor:pointer;
color:white;
background:linear-gradient(135deg,#ff0066,#6a00ff);
transition:.2s;
}

button:hover{
transform:scale(1.02);
}

.finalizar{
background:linear-gradient(135deg,#444,#111);
}

.acoes{
display:grid;
grid-template-columns:1fr 1fr;
gap:15px;
}

@media(max-width:700px){
.acoes{
grid-template-columns:1fr;
}
}
</style>
</head>

<body>

<div class="container">

<div class="box">
<h1>💬 Interações Diárias</h1>

<div class="sub">
Você tem até <b><?php echo $_SESSION['acoes_restantes']; ?></b> ações restantes.
</div>
</div>

<?php if($_SESSION['acoes_restantes'] > 0): ?>

<div class="acoes">

<!-- CONVERSAR -->
<div class="box">
<form method="POST">
<input type="hidden" name="acao" value="conversar">
<label>💬 Conversar com alguém</label>

<select name="alvo" required>
<option value="">Escolha participante</option>
<?php foreach($jogadores as $j): if($j['nome'] != $meuNome): ?>
<option value="<?php echo $j['nome']; ?>"><?php echo $j['nome']; ?></option>
<?php endif; endforeach; ?>
</select>

<button>Conversar</button>
</form>
</div>

<!-- FOFOCA -->
<div class="box">
<form method="POST">
<input type="hidden" name="acao" value="fofoca">
<label>🗣️ Fazer fofoca</label>

<select name="alvo" required>
<option value="">Alvo da fofoca</option>
<?php foreach($jogadores as $j): if($j['nome'] != $meuNome): ?>
<option value="<?php echo $j['nome']; ?>"><?php echo $j['nome']; ?></option>
<?php endif; endforeach; ?>
</select>

<button>Fazer Fofoca</button>
</form>
</div>

<!-- INTRIGA -->
<div class="box">
<form method="POST">
<input type="hidden" name="acao" value="intriga">
<label>🔥 Criar intriga entre dois</label>

<select name="alvo" required>
<option value="">Participante 1</option>
<?php foreach($jogadores as $j): if($j['nome'] != $meuNome): ?>
<option value="<?php echo $j['nome']; ?>"><?php echo $j['nome']; ?></option>
<?php endif; endforeach; ?>
</select>

<select name="alvo2" required>
<option value="">Participante 2</option>
<?php foreach($jogadores as $j): if($j['nome'] != $meuNome): ?>
<option value="<?php echo $j['nome']; ?>"><?php echo $j['nome']; ?></option>
<?php endif; endforeach; ?>
</select>

<button>Criar Intriga</button>
</form>
</div>

<!-- ALIANÇA -->
<div class="box">
<form method="POST">
<input type="hidden" name="acao" value="alianca">
<label>🤝 Criar aliança secreta</label>

<select name="alvo" required>
<option value="">Escolha aliado</option>
<?php foreach($jogadores as $j): if($j['nome'] != $meuNome): ?>
<option value="<?php echo $j['nome']; ?>"><?php echo $j['nome']; ?></option>
<?php endif; endforeach; ?>
</select>

<button>Propor Aliança</button>
</form>
</div>

<!-- LÍDER -->
<div class="box">
<form method="POST">
<input type="hidden" name="acao" value="aproximar_lider">
<label>👑 Se aproximar do líder</label>
<button>Agradar Líder</button>
</form>
</div>

<!-- VT -->
<div class="box">
<form method="POST">
<input type="hidden" name="acao" value="fazer_vt">
<label>📺 Fazer VT para o público</label>
<button>Fazer VT</button>
</form>
</div>

<!-- FICAR NA SUA -->
<div class="box">
<form method="POST">
<input type="hidden" name="acao" value="ficar_na_sua">
<label>😶 Ficar na sua</label>
<button>Evitar conflitos</button>
</form>
</div>

</div>

<?php else: ?>

<div class="box">
<h2>✅ Você usou suas 3 ações de hoje.</h2>
<p>Agora siga para a próxima fase da semana.</p>
</div>

<?php endif; ?>

<form method="POST">
<button class="finalizar" name="finalizar">
➡️ Continuar Semana
</button>
</form>

</div>

</body>
</html>