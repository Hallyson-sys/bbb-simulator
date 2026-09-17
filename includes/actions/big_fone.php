<?php

/* =========================================================
   ☎️ ACTION — BIG FONE
   Processa Atender / Não atender.
   ========================================================= */


/* =========================================================
   🚶 JOGADOR NÃO ATENDE
   Um NPC corre até o telefone.
   ========================================================= */

if (isset($_POST['nao_atender'])) {

    $atendente = npcAtendeBigFone(
        $jogadores,
        $meuNome
    );

    finalizarAtendimentoBigFone(
        $jogadores,
        $atendente,
        true
    );

    header("Location: jogo.php");
    exit;
}


/* =========================================================
   🏃 JOGADOR ATENDE
   ========================================================= */

if (isset($_POST['atender'])) {

    $atendente = $meuNome;

    finalizarAtendimentoBigFone(
        $jogadores,
        $atendente,
        false
    );

    header("Location: jogo.php");
    exit;
}
