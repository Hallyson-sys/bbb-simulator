<?php

/* =========================================================
   🏠 ACTIONS — CASA DE VIDRO
   ========================================================= */


/* =========================================================
   📊 REVELAR RESULTADO
   ========================================================= */
if (isset($_POST['revelar_resultado_casa_vidro'])) {
    calcularResultadoCasaVidro();

    header('Location: casa_vidro.php?resultado=1');
    exit;
}


/* =========================================================
   🚪 COLOCAR VENCEDORES NA CASA
   ========================================================= */
if (isset($_POST['confirmar_entrada_casa_vidro'])) {
    if (
        empty($_SESSION['casa_vidro_ranking']) ||
        empty($_SESSION['casa_vidro_vencedores'])
    ) {
        calcularResultadoCasaVidro();
    }

    $ok = integrarVencedoresCasaVidro(
        $jogadores,
        $meuNome
    );

    if (!$ok) {
        $_SESSION['evento_extra'][] =
            '⚠️ Não foi possível concluir a entrada da Casa de Vidro.';

        header('Location: casa_vidro.php');
        exit;
    }

    header('Location: jogo.php');
    exit;
}
