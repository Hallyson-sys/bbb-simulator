<?php

session_start();

/*
 * Abra uma única vez:
 * http://localhost/bbb-simulator/resgatar_casa_vidro.php
 */

if (
    (int)($_SESSION['rodada'] ?? 1) === 3 &&
    !empty($_SESSION['casa_vidro_anunciada'])
) {
    $_SESSION['casa_vidro_ocorrera'] = true;
    $_SESSION['casa_vidro_sorteio_feito'] = true;
    $_SESSION['casa_vidro_anuncio_concluido'] = true;
    $_SESSION['casa_vidro_votacao_aberta'] = true;

    $_SESSION['casa_vidro_ativa'] = false;
    $_SESSION['casa_vidro_etapa'] = 'votacao';

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

    unset(
        $_SESSION['casa_vidro_fase_retorno']
    );
}

session_write_close();

header('Location: jogo.php', true, 303);
exit;
