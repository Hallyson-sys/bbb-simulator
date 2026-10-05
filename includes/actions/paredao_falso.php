<?php

/* =========================================================
   🚪 ACTION — RETORNO DO PAREDÃO FALSO
   ========================================================= */

if (isset($_POST['retornar_paredao_falso'])) {

    if (empty($_SESSION['paredao_falso_ativo'])) {
        header("Location: jogo.php");
        exit;
    }

    $jogadores = $_SESSION['jogadores'] ?? [];

    $retornou =
        retornarFalsoEliminadoParaCasa(
            $jogadores
        );

    if (!$retornou) {
        $_SESSION['evento_extra'][] =
            "⚠️ Não foi possível concluir o retorno do Paredão Falso.";

        header("Location: jogo.php");
        exit;
    }

    /*
     * O retorno encerra a rodada atual.
     * A próxima começa normalmente pelo Queridômetro.
     */
    iniciarNovaRodadaAposEliminacao(
        $jogadores
    );

    header("Location: jogo.php");
    exit;
}
