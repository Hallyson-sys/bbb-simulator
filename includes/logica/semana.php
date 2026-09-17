<?php

/* =========================================================
   📅 LÓGICA DA SEMANA / RODADA
   ========================================================= */


/* =========================================================
   👑 QUANTIDADE DE PARTICIPANTES NO VIP
   ========================================================= */
function calcularQtdVIP($total)
{
    $total = (int) $total;

    if ($total >= 18) return 8;
    if ($total >= 14) return 6;
    if ($total >= 10) return 4;
    if ($total >= 7)  return 3;
    if ($total >= 5)  return 2;

    return 1;
}


/* =========================================================
   📅 GARANTIR ESTADO BÁSICO DA SEMANA
   ========================================================= */
function garantirEstadoSemana()
{
    if (!isset($_SESSION['acoes_festa'])) {
        $_SESSION['acoes_festa'] = 2;
    }

    if (!isset($_SESSION['fase_semana'])) {
        $_SESSION['fase_semana'] = 'queridometro';
    }

    if (
        !isset($_SESSION['evento_extra']) ||
        !is_array($_SESSION['evento_extra'])
    ) {
        $_SESSION['evento_extra'] = [];
    }
}


/* =========================================================
   💖 GARANTIR INÍCIO DA RODADA NO QUERIDÔMETRO
   ========================================================= */
function garantirInicioRodadaQueridometro(&$fase)
{
    if (
        $fase == 'eliminacao' &&
        !isset($_SESSION['paredao'])
    ) {
        $_SESSION['fase_semana'] = 'queridometro';

        unset($_SESSION['queridometro_feito']);
        unset($_SESSION['queridometro_resultado']);

        $_SESSION['acoes_restantes'] = 3;
        $fase = 'queridometro';
    }

    if (
        $fase == 'interacoes_1' &&
        !isset($_SESSION['queridometro_feito'])
    ) {
        $_SESSION['fase_semana'] = 'queridometro';
        $fase = 'queridometro';
    }
}
