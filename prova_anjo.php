<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['jogadores'])) {
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['lider'])) {
    header("Location: jogo.php");
    exit;
}

/* ===============================
   DADOS
================================= */

$jogadores = $_SESSION['jogadores'];
$lider     = $_SESSION['lider'];
$meuNome   = $_SESSION['meu_nome'] ?? '';

if (!isset($_SESSION['evento_extra'])) {
    $_SESSION['evento_extra'] = [];
}

/* ===============================
   PARTICIPANTES DA PROVA
   O LÍDER NUNCA PARTICIPA
================================= */

$participantes = [];

foreach ($jogadores as $j) {
    if ($j['nome'] != $lider) {
        $participantes[] = $j;
    }
}

/* segurança: se não tiver gente suficiente */
if (count($participantes) == 0) {
    $_SESSION['evento_extra'][] = "⚠️ Não havia participantes disponíveis para a Prova do Anjo.";
    $_SESSION['prova_anjo_finalizada'] = true;
    $_SESSION['fase_semana'] = 'monstro';
    header("Location: jogo.php");
    exit;
}

/* ===============================
   FUNÇÃO PARA FINALIZAR A PROVA
================================= */

function finalizarProvaAnjo(&$jogadores, $campeaoNome, $lider)
{

    if (!isset($_SESSION['evento_extra'])) {
        $_SESSION['evento_extra'] = [];
    }

    /* limpa anjo e imunidade antigos */
    foreach ($jogadores as &$j) {
        $j['status']['anjo'] = false;
        $j['status']['imune'] = false;
    }
    unset($j);

    /* marca o anjo */
    foreach ($jogadores as &$j) {
        if ($j['nome'] == $campeaoNome) {

            $j['status']['anjo'] = true;
        
            if(!isset($j['estatisticas'])){
                $j['estatisticas'] = [];
            }
        
            $j['estatisticas']['anjo'] =
            ($j['estatisticas']['anjo'] ?? 0) + 1;
        }
    }
    unset($j);

    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['anjo'] = $campeaoNome;
    
    unset($_SESSION['imune']);
    
    $_SESSION['prova_anjo_finalizada'] = true;
    $_SESSION['fase_semana'] = 'monstro';
    
    $_SESSION['evento_extra'][] = "😇 " . $campeaoNome . " venceu a Prova do Anjo.";
    
    unset($_SESSION['prova_anjo_tipo']);
    unset($_SESSION['memoria_seq']);
    unset($_SESSION['caixa_certa']);
    unset($_SESSION['quiz_anjo']);
}

/* ===============================
   SE O JOGADOR FOR LÍDER
   PROVA ACONTECE AUTOMATICAMENTE
================================= */

if ($meuNome == $lider) {

    $campeao = $participantes[array_rand($participantes)];

    $_SESSION['evento_extra'][] =
        "👑 " . $lider . " já é Líder e ficou fora da Prova do Anjo.";

    finalizarProvaAnjo($jogadores, $campeao['nome'], $lider);

    header("Location: jogo.php");
    exit;
}

/* ===============================
   GERAR PROVA
================================= */

if (!isset($_SESSION['prova_anjo_tipo'])) {

    $_SESSION['prova_anjo_tipo'] = rand(1, 3);

    /* PROVA 1 - MEMÓRIA */
    if ($_SESSION['prova_anjo_tipo'] == 1) {

        $lista = ['🔥', '⭐', '🎯', '💎', '⚡', '🎮', '🚀', '🏆'];
        shuffle($lista);

        $_SESSION['memoria_seq'] = array_slice($lista, 0, 5);
    }

    /* PROVA 2 - CHAVES */
    if ($_SESSION['prova_anjo_tipo'] == 2) {

        $_SESSION['caixa_certa'] = rand(1, 20);
    }

    /* PROVA 3 - QUIZ */
    if ($_SESSION['prova_anjo_tipo'] == 3) {

        $perguntas = [

            [
                "p" => "Quanto é 8 + 9?",
                "a" => "17",
                "op" => ["16", "18", "17", "15"]
            ],

            [
                "p" => "Qual letra vem depois do M?",
                "a" => "N",
                "op" => ["L", "N", "P", "O"]
            ],

            [
                "p" => "5 x 4 = ?",
                "a" => "20",
                "op" => ["15", "25", "20", "18"]
            ]

        ];

        $_SESSION['quiz_anjo'] =
            $perguntas[array_rand($perguntas)];
    }
}

/* ===============================
   PROCESSAR RESPOSTA
================================= */

if (isset($_POST['jogar'])) {

    $tipo   = $_SESSION['prova_anjo_tipo'];
    $venceu = false;

    /* PROVA 1 - MEMÓRIA */
    if ($tipo == 1) {

        $resp = trim($_POST['resposta'] ?? '');
        $certa = implode('', $_SESSION['memoria_seq']);

        if ($resp == $certa) {
            $venceu = true;
        }
    }

    /* PROVA 2 - CHAVES */
    if ($tipo == 2) {

        $caixa = intval($_POST['caixa'] ?? 0);

        if ($caixa == $_SESSION['caixa_certa']) {
            $venceu = true;
        }
    }

    /* PROVA 3 - QUIZ */
    if ($tipo == 3) {

        $resp = $_POST['quiz'] ?? '';

        if ($resp == $_SESSION['quiz_anjo']['a']) {
            $venceu = true;
        }
    }

    /* ===========================
       DEFINIR CAMPEÃO
       Se você vencer e não for líder, você vira Anjo.
       Se errar, NPC ganha.
    =========================== */

    if ($venceu) {
        $campeaoNome = $meuNome;
    } else {

        $npcs = [];

        foreach ($participantes as $p) {
            if ($p['nome'] != $meuNome) {
                $npcs[] = $p;
            }
        }

        if (count($npcs) > 0) {
            $campeao = $npcs[array_rand($npcs)];
        } else {
            $campeao = $participantes[array_rand($participantes)];
        }

        $campeaoNome = $campeao['nome'];
    }

    finalizarProvaAnjo($jogadores, $campeaoNome, $lider);

    header("Location: jogo.php");
    exit;
}

$tipo = $_SESSION['prova_anjo_tipo'];

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Prova do Anjo</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: radial-gradient(circle at top, #15365f, #050813 75%);
            color: white;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
        }

        .box {
            width: 760px;
            max-width: 100%;
            background: rgba(255, 255, 255, .05);
            padding: 35px;
            border-radius: 28px;
            box-shadow: 0 0 35px rgba(0, 0, 0, .45);
            animation: subir .7s ease;
        }

        h1 {
            text-align: center;
            font-size: 38px;
            margin-bottom: 12px;
            background: linear-gradient(90deg, #ff0077, #ffcc00);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }

        .sub {
            text-align: center;
            margin-bottom: 25px;
            opacity: .9;
            font-size: 17px;
        }

        .alerta {
            background: rgba(255, 255, 255, .06);
            padding: 14px;
            border-radius: 14px;
            margin-bottom: 20px;
            text-align: center;
        }

        p {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        input,
        select {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            margin-bottom: 15px;
        }

        button {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 14px;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            color: white;
            background: linear-gradient(135deg, #00bfff, #004cff);
            transition: .2s;
        }

        button:hover {
            transform: scale(1.02);
        }

        .ops {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 18px;
        }

        label {
            background: rgba(255, 255, 255, .07);
            padding: 14px;
            border-radius: 12px;
            cursor: pointer;
            transition: .2s;
        }

        label:hover {
            background: rgba(255, 255, 255, .12);
        }

        @keyframes subir {
            from {
                opacity: 0;
                transform: translateY(25px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>

    <div class="box">

        <h1>😇 Prova do Anjo</h1>

        <div class="sub">
            O vencedor conquista o colar e imuniza alguém.
        </div>

        <div class="alerta">
            👑 <b><?php echo $lider; ?></b> é o Líder e não participa desta prova.
        </div>

        <form method="POST">

            <?php if ($tipo == 1): ?>

                <p>🧠 <b>Memória Relâmpago</b></p>

                <p>Memorize a sequência:</p>

                <p style="font-size:34px;text-align:center;">
                    <?php echo implode(' ', $_SESSION['memoria_seq']); ?>
                </p>

                <p>Digite exatamente igual, sem espaços:</p>

                <input type="text" name="resposta" required>

            <?php endif; ?>

            <?php if ($tipo == 2): ?>

                <p>🔑 <b>Chaves da Sorte</b></p>

                <p>Escolha uma caixa de 1 até 20:</p>

                <select name="caixa" required>
                    <option value="">Escolher</option>

                    <?php for ($i = 1; $i <= 20; $i++): ?>
                        <option value="<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>

                </select>

            <?php endif; ?>

            <?php if ($tipo == 3): ?>

                <p>⚡ <b>Quiz Turbo</b></p>

                <p><?php echo $_SESSION['quiz_anjo']['p']; ?></p>

                <div class="ops">

                    <?php foreach ($_SESSION['quiz_anjo']['op'] as $op): ?>

                        <label>
                            <input type="radio"
                                name="quiz"
                                value="<?php echo $op; ?>"
                                required>
                            <?php echo $op; ?>
                        </label>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

            <button name="jogar">
                💙 Disputar Prova
            </button>

        </form>

    </div>

</body>

</html>