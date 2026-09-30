<?php

/* =========================================================
   😇 ACTION — PROVA DO ANJO 2.0
   ========================================================= */

require_once __DIR__ . '/../logica/premios_provas.php';

if (
    !isset($jogadores) ||
    !is_array($jogadores) ||
    !isset($lider) ||
    !isset($meuNome) ||
    !isset($participantes) ||
    !is_array($participantes)
) {
    return;
}

/* Permite abrir a tela de resultado depois que a prova já foi encerrada. */
if (provaAnjoFinalizadaNaRodadaAtual()) {
    if (!empty($_GET['resultado']) && !empty($_SESSION['resultado_prova_anjo'])) {
        return;
    }

    if (($_SESSION['fase_semana'] ?? '') === 'anjo') {
        $_SESSION['fase_semana'] = 'monstro';
    }

    header('Location: jogo.php');
    exit;
}

if (empty($participantes)) {
    if (!isset($_SESSION['evento_extra']) || !is_array($_SESSION['evento_extra'])) {
        $_SESSION['evento_extra'] = [];
    }

    $_SESSION['evento_extra'][] =
        '⚠️ Não havia participantes disponíveis para a Prova do Anjo.';

    $_SESSION['prova_anjo_finalizada'] = true;
    $_SESSION['prova_anjo_finalizada_rodada'] =
        (int)($_SESSION['rodada'] ?? 1);

    $_SESSION['fase_semana'] = 'monstro';

    header('Location: jogo.php');
    exit;
}

$dadosProvaAction = prepararProvaAnjo();
$provaAction = $dadosProvaAction['prova'];

/* =========================================================
   👑 LÍDER NÃO PARTICIPA, MAS VÊ O RESULTADO
   ========================================================= */

if ($meuNome === $lider) {
    $ranking = gerarRankingProvaAnjo($participantes, $meuNome, null);
    $campeaoNome = (string)($ranking[0]['nome'] ?? '');

    if (!isset($_SESSION['evento_extra']) || !is_array($_SESSION['evento_extra'])) {
        $_SESSION['evento_extra'] = [];
    }

    $_SESSION['evento_extra'][] =
        "👑 $lider já é Líder e ficou fora da Prova do Anjo.";

    finalizarProvaAnjo($jogadores, $campeaoNome, $lider);
    salvarResultadoVisualProvaAnjo($ranking, $provaAction, $meuNome, false);

    premiarMoedasPorProva(
        'anjo',
        $campeaoNome,
        $meuNome,
        15
    );

    header('Location: prova_anjo.php?resultado=1');
    exit;
}

if (!isset($_POST['jogar'])) {
    return;
}

/* =========================================================
   🎮 RESOLVER MINIGAME
   ========================================================= */

$pontuacaoJogador = normalizarPontuacaoJogadorAnjo($_POST);
$ranking = gerarRankingProvaAnjo(
    $participantes,
    $meuNome,
    $pontuacaoJogador
);

$campeaoNome = (string)($ranking[0]['nome'] ?? '');

/* =========================================================
   🏆 FINALIZAR PROVA
   ========================================================= */

finalizarProvaAnjo(
    $jogadores,
    $campeaoNome,
    $lider
);

salvarResultadoVisualProvaAnjo(
    $ranking,
    $provaAction,
    $meuNome,
    true
);

premiarMoedasPorProva(
    'anjo',
    $campeaoNome,
    $meuNome,
    15
);

header('Location: prova_anjo.php?resultado=1');
exit;
