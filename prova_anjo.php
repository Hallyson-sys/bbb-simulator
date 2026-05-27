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
    if (($j['nome'] ?? '') != $lider) {
        $participantes[] = $j;
    }
}

if (count($participantes) == 0) {
    $_SESSION['evento_extra'][] = "⚠️ Não havia participantes disponíveis para a Prova do Anjo.";
    $_SESSION['prova_anjo_finalizada'] = true;
    $_SESSION['fase_semana'] = 'monstro';
    header("Location: jogo.php");
    exit;
}

/* ===============================
   PROVAS DO ANJO
================================= */

$provasAnjo = [

    1 => [
        "titulo" => "🔑 Chaves da Sorte",
        "texto" => "Escolha uma chave. A chave certa abre o colar do Anjo.",
        "max" => 20,
        "tipo" => "select",
        "label" => "Chave"
    ],

    2 => [
        "titulo" => "⚡ Quiz Turbo",
        "texto" => "Responda corretamente para tentar conquistar o Anjo.",
        "tipo" => "quiz"
    ],

    3 => [
        "titulo" => "🎁 Presente Certo",
        "texto" => "Escolha um presente. Um deles guarda o colar do Anjo.",
        "max" => 5,
        "tipo" => "botoes",
        "label" => "🎁 Presente"
    ],

    4 => [
        "titulo" => "🌟 Estrela Premiada",
        "texto" => "Escolha uma estrela. A estrela iluminada vence a prova.",
        "max" => 4,
        "tipo" => "botoes",
        "label" => "⭐ Estrela"
    ],

    5 => [
        "titulo" => "🦋 Borboleta Azul",
        "texto" => "Escolha uma borboleta. Uma delas carrega o poder do Anjo.",
        "max" => 3,
        "tipo" => "botoes",
        "label" => "🦋 Borboleta"
    ],

    6 => [
        "titulo" => "🔮 Cristal do Anjo",
        "texto" => "Escolha um cristal. O cristal correto revela o vencedor.",
        "max" => 4,
        "tipo" => "botoes",
        "label" => "🔮 Cristal"
    ],

    7 => [
        "titulo" => "☁️ Nuvem da Sorte",
        "texto" => "Escolha uma nuvem. Uma delas esconde o colar do Anjo.",
        "max" => 5,
        "tipo" => "botoes",
        "label" => "☁️ Nuvem"
    ],

    8 => [
        "titulo" => "🪽 Asas do Anjo",
        "texto" => "Escolha uma asa. A asa certa te leva até o colar.",
        "max" => 3,
        "tipo" => "botoes",
        "label" => "🪽 Asa"
    ],

    9 => [
        "titulo" => "🌈 Arco da Proteção",
        "texto" => "Escolha uma cor do arco. Uma delas ativa a proteção do Anjo.",
        "max" => 5,
        "tipo" => "botoes",
        "label" => "🌈 Cor"
    ],

    10 => [
        "titulo" => "🕯️ Luz do Anjo",
        "texto" => "Escolha uma vela. A vela acesa revela o campeão.",
        "max" => 4,
        "tipo" => "botoes",
        "label" => "🕯️ Vela"
    ]

];

/* ===============================
   FUNÇÕES
================================= */


function chanceAnjoAutoimune($totalJogadores)
{
    /* Top 10 em diante: o Anjo vira autoimune obrigatoriamente */
    if ($totalJogadores <= 10) {
        return 100;
    }

    /* Antes do Top 10: algumas semanas podem ter Anjo autoimune */
    return 30;
}

function semanaAnjoAutoimune($totalJogadores)
{
    if (!isset($_SESSION['anjo_autoimune_sorteado_rodada'])) {
        $_SESSION['anjo_autoimune_sorteado_rodada'] = $_SESSION['rodada'] ?? 1;
        $_SESSION['anjo_autoimune'] = (rand(1, 100) <= chanceAnjoAutoimune($totalJogadores));
    }

    return !empty($_SESSION['anjo_autoimune']);
}


function finalizarProvaAnjo(&$jogadores, $campeaoNome, $lider)
{
    if (!isset($_SESSION['evento_extra'])) {
        $_SESSION['evento_extra'] = [];
    }

    $autoimune = semanaAnjoAutoimune(count($jogadores));

    foreach ($jogadores as &$j) {
        $j['status']['anjo'] = false;

        /* Só limpa a imunidade comum se ela veio de um anjo anterior.
           A imunidade final desta semana será definida abaixo. */
        if (!empty($_SESSION['imune']) && ($j['nome'] ?? '') == $_SESSION['imune']) {
            $j['status']['imune'] = false;
        }
    }
    unset($j);

    foreach ($jogadores as &$j) {
        if (($j['nome'] ?? '') == $campeaoNome) {

            $j['status']['anjo'] = true;

            if(!isset($j['estatisticas'])){
                $j['estatisticas'] = [];
            }

            $j['estatisticas']['anjo'] =
            ($j['estatisticas']['anjo'] ?? 0) + 1;

            if ($autoimune) {
                $j['status']['imune'] = true;
                $j['estatisticas']['imune'] =
                ($j['estatisticas']['imune'] ?? 0) + 1;
            }
        }
    }
    unset($j);

    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['anjo'] = $campeaoNome;
    $_SESSION['prova_anjo_finalizada'] = true;
    $_SESSION['fase_semana'] = 'monstro';

    if ($autoimune) {
        $_SESSION['imune'] = $campeaoNome;
        $_SESSION['anjo_autoimune'] = true;
        $_SESSION['imunizacao_anjo_feita'] = true;
        $_SESSION['anjo_autoimune_estat_contada'] = true;

        $_SESSION['evento_extra'][] =
        "😇 " . $campeaoNome . " venceu a Prova do Anjo e nesta semana o Anjo é autoimune.";

        $_SESSION['evento_extra'][] =
        "🛡️ " . $campeaoNome . " está imune e não precisará imunizar outra pessoa.";
    } else {
        $_SESSION['anjo_autoimune'] = false;
        unset($_SESSION['imune']);

        $_SESSION['evento_extra'][] =
        "😇 " . $campeaoNome . " venceu a Prova do Anjo e poderá imunizar alguém antes do paredão.";
    }

    unset($_SESSION['prova_anjo_tipo']);
    unset($_SESSION['caixa_certa']);
    unset($_SESSION['quiz_anjo']);
    unset($_SESSION['anjo_numero_certo']);
    unset($_SESSION['anjo_autoimune_sorteado_rodada']);
}

function sortearNPCAnjo($participantes, $meuNome)
{
    $npcs = [];

    foreach ($participantes as $p) {
        if (($p['nome'] ?? '') != $meuNome) {
            $npcs[] = $p;
        }
    }

    if (count($npcs) > 0) {
        $campeao = $npcs[array_rand($npcs)];
        return $campeao['nome'];
    }

    $campeao = $participantes[array_rand($participantes)];
    return $campeao['nome'];
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

    $_SESSION['prova_anjo_tipo'] = rand(1, 10);

    if ($_SESSION['prova_anjo_tipo'] == 1) {
        $_SESSION['caixa_certa'] = rand(1, 20);
    }

    if ($_SESSION['prova_anjo_tipo'] >= 3 && $_SESSION['prova_anjo_tipo'] <= 10) {
        $tipoTemp = $_SESSION['prova_anjo_tipo'];
        $maxTemp = $provasAnjo[$tipoTemp]['max'] ?? 5;
        $_SESSION['anjo_numero_certo'] = rand(1, $maxTemp);
    }

    if ($_SESSION['prova_anjo_tipo'] == 2) {

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
            ],

            [
                "p" => "Qual número vem depois do 29?",
                "a" => "30",
                "op" => ["28", "31", "30", "39"]
            ],

            [
                "p" => "Qual palavra combina com proteção?",
                "a" => "Escudo",
                "op" => ["Escudo", "Espelho", "Fogo", "Chuva"]
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

    if ($tipo == 1) {
        $caixa = intval($_POST['caixa'] ?? 0);

        if ($caixa == ($_SESSION['caixa_certa'] ?? 0)) {
            $venceu = true;
        }
    }

    if ($tipo == 2) {
        $resp = $_POST['quiz'] ?? '';

        if ($resp == ($_SESSION['quiz_anjo']['a'] ?? '')) {
            $venceu = true;
        }
    }

    if ($tipo >= 3 && $tipo <= 10) {
        $escolha = intval($_POST['escolha'] ?? 0);

        if ($escolha == ($_SESSION['anjo_numero_certo'] ?? 0)) {
            $venceu = true;
        }
    }

    if ($venceu) {
        $campeaoNome = $meuNome;
    } else {
        $campeaoNome = sortearNPCAnjo($participantes, $meuNome);
    }

    finalizarProvaAnjo($jogadores, $campeaoNome, $lider);

    header("Location: jogo.php");
    exit;
}

$tipo = $_SESSION['prova_anjo_tipo'];
$provaAtual = $provasAnjo[$tipo] ?? $provasAnjo[1];

function textoBotaoAnjo($prova, $i)
{
    $label = $prova['label'] ?? 'Opção';

    if (($prova['titulo'] ?? '') == '🌈 Arco da Proteção') {
        $cores = ['Rosa', 'Azul', 'Dourado', 'Verde', 'Roxo'];
        return "🌈 " . ($cores[$i-1] ?? "Cor $i");
    }

    if (($prova['titulo'] ?? '') == '🪽 Asas do Anjo') {
        $letras = ['A', 'B', 'C'];
        return $label . " " . ($letras[$i-1] ?? $i);
    }

    return $label . " " . $i;
}

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
            background:
                radial-gradient(circle at top left, rgba(0, 191, 255, .22), transparent 35%),
                radial-gradient(circle at top right, rgba(255, 0, 119, .16), transparent 34%),
                radial-gradient(circle at bottom, #15365f, #050813 75%);
            color: white;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
        }

        .box {
            width: 780px;
            max-width: 100%;
            background: rgba(255, 255, 255, .06);
            padding: 36px;
            border-radius: 30px;
            border: 1px solid rgba(255,255,255,.12);
            box-shadow: 0 0 40px rgba(0, 0, 0, .48);
            animation: subir .7s ease;
            backdrop-filter: blur(14px);
        }

        h1 {
            text-align: center;
            font-size: 40px;
            margin-bottom: 12px;
            background: linear-gradient(90deg, #00bfff, #ffcc00, #ff0077);
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
            line-height:1.5;
        }

        .alerta {
            background: rgba(255, 255, 255, .07);
            padding: 15px;
            border-radius: 16px;
            margin-bottom: 22px;
            text-align: center;
            line-height:1.5;
        }

        p {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 16px;
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
            border-radius: 15px;
            font-size: 19px;
            font-weight: bold;
            cursor: pointer;
            color: white;
            background: linear-gradient(135deg, #00bfff, #004cff);
            transition: .22s;
            min-height:58px;
        }

        button:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow:0 0 20px rgba(0,191,255,.35);
        }

        .ops {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 18px;
        }

        .grid-botoes{
            display:grid;
            grid-template-columns: repeat(2, 1fr);
            gap:12px;
            margin-bottom:18px;
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

        @media(max-width:700px){
            .ops,
            .grid-botoes{
                grid-template-columns:1fr;
            }
        }
    </style>
</head>

<body>

    <div class="box">

        <h1>😇 Prova do Anjo</h1>

        <div class="sub">
            O vencedor conquista o colar. Em algumas semanas, o Anjo é autoimune.
        </div>

        <div class="alerta">
            👑 <b><?php echo $lider; ?></b> é o Líder e não participa desta prova.
        </div>

        <form method="POST">

            <p><b><?php echo $provaAtual['titulo']; ?></b></p>
            <p><?php echo $provaAtual['texto']; ?></p>

            <?php if ($tipo == 1): ?>

                <select name="caixa" required>
                    <option value="">Escolher chave</option>

                    <?php for ($i = 1; $i <= 20; $i++): ?>
                        <option value="<?php echo $i; ?>">
                            🔑 Chave <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>

                </select>

                <button name="jogar">
                    💙 Disputar Prova
                </button>

            <?php endif; ?>

            <?php if ($tipo == 2): ?>

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

                <button name="jogar">
                    💙 Disputar Prova
                </button>

            <?php endif; ?>

            <?php if ($tipo >= 3 && $tipo <= 10): ?>

                <div class="grid-botoes">
                    <?php for ($i = 1; $i <= $provaAtual['max']; $i++): ?>
                        <button type="submit" name="escolha" value="<?php echo $i; ?>">
                            <?php echo textoBotaoAnjo($provaAtual, $i); ?>
                        </button>
                    <?php endfor; ?>
                </div>

                <input type="hidden" name="jogar" value="1">

            <?php endif; ?>

        </form>

    </div>

</body>

</html>
