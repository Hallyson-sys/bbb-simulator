<?php

/* =========================================================
   😇 ACTION — PROVA DO ANJO
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


if (provaAnjoFinalizadaNaRodadaAtual()) {
    if (($_SESSION['fase_semana'] ?? '') === 'anjo') {
        $_SESSION['fase_semana'] = 'monstro';
    }

    header('Location: jogo.php');
    exit;
}


if (empty($participantes)) {
    if (
        !isset($_SESSION['evento_extra']) ||
        !is_array($_SESSION['evento_extra'])
    ) {
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


/* =========================================================
   👑 LÍDER NÃO PARTICIPA
   ========================================================= */

if ($meuNome === $lider) {
    $campeaoNome =
        sortearNPCAnjo(
            $participantes,
            $meuNome
        );

    if (
        !isset($_SESSION['evento_extra']) ||
        !is_array($_SESSION['evento_extra'])
    ) {
        $_SESSION['evento_extra'] = [];
    }

    $_SESSION['evento_extra'][] =
        "👑 $lider já é Líder e ficou fora da Prova do Anjo.";

    finalizarProvaAnjo(
        $jogadores,
        $campeaoNome,
        $lider
    );

    /*
     * Normalmente será NPC neste caso.
     * A função só paga se o campeão for o jogador.
     */
    premiarMoedasPorProva(
        'anjo',
        $campeaoNome,
        $meuNome,
        15
    );

    header('Location: jogo.php');
    exit;
}


if (!isset($_POST['jogar'])) {
    return;
}


/* =========================================================
   😇 RESOLVER PROVA
   ========================================================= */

$venceu =
    jogadorVenceuProvaAnjo(
        $_POST
    );

if ($venceu) {
    $campeaoNome = $meuNome;
} else {
    $campeaoNome =
        sortearNPCAnjo(
            $participantes,
            $meuNome
        );
}


/* =========================================================
   🏆 FINALIZAR PROVA
   ========================================================= */

finalizarProvaAnjo(
    $jogadores,
    $campeaoNome,
    $lider
);


/* =========================================================
   🪙 BÔNUS POR VENCER A PROVA
   Somente o jogador principal recebe.
   ========================================================= */

premiarMoedasPorProva(
    'anjo',
    $campeaoNome,
    $meuNome,
    15
);


header('Location: jogo.php');
exit;
