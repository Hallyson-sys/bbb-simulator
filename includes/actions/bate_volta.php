<?php

/** @var array $jogadores */


/* =========================
   🚗 PROCESSAR PROVA BATE-VOLTA
========================= */

if (isset($_POST['jogar_bate_volta'])) {

    $escolhaBateVolta =
        $_POST['escolha_bate_volta'] ?? '';

    $eventoBateVolta =
        resolverBateVoltaJogador(
            $jogadores,
            $escolhaBateVolta
        );

    $_SESSION['evento_extra'][] =
        $eventoBateVolta;

    $_SESSION['jogadores'] =
        $jogadores;

    header("Location: jogo.php");
    exit;
}