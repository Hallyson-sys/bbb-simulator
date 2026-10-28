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

    $disputa = disputarAtendimentoBigFone($jogadores, $meuNome);
    $atendente = $disputa['vencedor'];
    if (!$disputa['jogador_venceu']) {
        $_SESSION['evento_extra'][] = "🏃 Você tentou atender, mas $atendente chegou primeiro!";
    }
    finalizarAtendimentoBigFone(
        $jogadores,
        $atendente,
        !$disputa['jogador_venceu']
    );
    // A escolha da Imunidade Extra é feita na fase seguinte, não por sorteio.
    if ($disputa['jogador_venceu'] && !empty($_SESSION['bigfone_imunidade_extra_pendente'])) {
        $_SESSION['fase_semana'] = 'bigfone_imunidade_extra';
    }

    header("Location: jogo.php");
    exit;
}
