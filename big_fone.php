<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

/* =========================================================
   🧠 DEPENDÊNCIAS DA IA DOS NPCs
   O Big Fone é uma página separada de jogo.php,
   então precisa carregar o cérebro dos NPCs também.
   ========================================================= */

require_once __DIR__ . '/includes/logica/utilitarios.php';
require_once __DIR__ . '/includes/logica/relacoes.php';
require_once __DIR__ . '/includes/logica/popularidade.php';
require_once __DIR__ . '/includes/logica/romance.php';
require_once __DIR__ . '/includes/logica/aliancas.php';
require_once __DIR__ . '/includes/logica/inteligencia_npc.php';


/* =========================================================
   ☎️ LÓGICA DO BIG FONE
   ========================================================= */

require_once __DIR__ . '/includes/logica/big_fone.php';


/* =========================================================
   ☎️ VERIFICAR SESSÃO
   ========================================================= */

if (!isset($_SESSION['jogadores'])) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   👥 DADOS DA TEMPORADA
   ========================================================= */

$jogadores = $_SESSION['jogadores'];
$meuNome = $_SESSION['meu_nome'] ?? '';


/* =========================================================
   📞 PREPARAR O BIG FONE
   ========================================================= */

$estadoBigFone = prepararBigFoneDaRodada();

/*
 * Se ele já aconteceu ou se não tocou nesta semana,
 * o jogo principal assume novamente o fluxo das fases.
 */
if ($estadoBigFone !== 'tocou') {
    header("Location: jogo.php");
    exit;
}


/* =========================================================
   🎮 ACTIONS
   ========================================================= */

require_once __DIR__ . '/includes/actions/big_fone.php';

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Big Fone</title>

    <link rel="stylesheet" href="assets/css/big_fone.css">
</head>

<body>

<div class="box">

    <h1>☎️ BIG FONE TOCOU!</h1>

    <p>O telefone mais temido da casa está tocando.</p>
    <p>Você tem <b>3 segundos</b> para decidir se vai atender.</p>

    <div class="timer" id="timer">3</div>

    <!-- Usado automaticamente quando o tempo termina. -->
    <form method="POST" id="formNao">
        <input type="hidden" name="nao_atender" value="1">
    </form>

    <div class="botoes">

        <form method="POST">
            <button type="submit" class="atender" name="atender" value="1">
                🏃 Atender
            </button>
        </form>

        <form method="POST">
            <button type="submit" class="nao" name="nao_atender" value="1">
                Não atender
            </button>
        </form>

    </div>

    <p class="aviso">
        Se o tempo acabar, um participante aleatório atenderá.
    </p>

</div>

<script>
let tempo = 3;
const timer = document.getElementById('timer');
const formNao = document.getElementById('formNao');

const intervalo = setInterval(() => {
    tempo--;

    if (timer) {
        timer.innerText = tempo;
    }

    if (tempo <= 0) {
        clearInterval(intervalo);

        if (formNao) {
            formNao.submit();
        }
    }
}, 1000);
</script>

</body>
</html>
