<?php

/* =========================================================
   🏠 ACTIONS — CASA DE VIDRO
   ========================================================= */


/* =========================================================
   ▶️ CONTINUAR A RODADA 3 APÓS O ANÚNCIO
   ========================================================= */
if (isset($_POST['continuar_rodada_casa_vidro'])) {

    /*
     * O anúncio foi visto, mas a votação continua aberta
     * em segundo plano até o início da Rodada 4.
     */
    $_SESSION['casa_vidro_anunciada'] = true;
    $_SESSION['casa_vidro_anuncio_concluido'] = true;
    $_SESSION['casa_vidro_votacao_aberta'] = true;
    $_SESSION['casa_vidro_ativa'] = false;
    $_SESSION['casa_vidro_etapa'] = 'votacao';

    /*
     * Retoma exatamente a fase que estava ativa antes
     * de abrir a Casa de Vidro.
     */
    $faseRetorno =
        $_SESSION['casa_vidro_fase_retorno']
        ?? 'queridometro';

    if (
        $faseRetorno === '' ||
        $faseRetorno === 'casa_vidro'
    ) {
        $faseRetorno = 'queridometro';
    }

    $_SESSION['fase_semana'] = $faseRetorno;

    unset($_SESSION['casa_vidro_fase_retorno']);

    /*
     * Garante que a sessão seja gravada antes do redirect.
     */
    session_write_close();

    header('Location: jogo.php', true, 303);
    exit;
}


/* =========================================================
   📊 REVELAR RESULTADO — APENAS NA RODADA 4+
   ========================================================= */
if (isset($_POST['revelar_resultado_casa_vidro'])) {

    if (
        (int)($_SESSION['rodada'] ?? 1) < 4 ||
        empty($_SESSION['casa_vidro_anunciada'])
    ) {
        if (
            !isset($_SESSION['evento_extra']) ||
            !is_array($_SESSION['evento_extra'])
        ) {
            $_SESSION['evento_extra'] = [];
        }

        $_SESSION['evento_extra'][] =
            '⚠️ A votação da Casa de Vidro ainda está aberta. O resultado será revelado no início da Rodada 4.';

        header('Location: jogo.php');
        exit;
    }

    calcularResultadoCasaVidro();

    $_SESSION['casa_vidro_etapa'] =
        'resultado_revelado';

    header('Location: casa_vidro.php?resultado=1');
    exit;
}


/* =========================================================
   🚪 COLOCAR VENCEDORES NA CASA
   ========================================================= */
if (isset($_POST['confirmar_entrada_casa_vidro'])) {

    if ((int)($_SESSION['rodada'] ?? 1) < 4) {
        header('Location: jogo.php');
        exit;
    }

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
        if (
            !isset($_SESSION['evento_extra']) ||
            !is_array($_SESSION['evento_extra'])
        ) {
            $_SESSION['evento_extra'] = [];
        }

        $_SESSION['evento_extra'][] =
            '⚠️ Não foi possível concluir a entrada da Casa de Vidro.';

        header('Location: casa_vidro.php');
        exit;
    }

    header('Location: jogo.php');
    exit;
}
