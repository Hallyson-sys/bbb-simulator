<?php

/* =========================================================
   🔄 ESTADO E TRANSIÇÕES DAS FASES
   ========================================================= */


/* =========================================================
   💬 PREPARAR AÇÕES DAS INTERAÇÕES
   ========================================================= */
function prepararAcoesInteracoes($fase)
{
    if (
        strpos((string) $fase, 'interacoes') !== false &&
        !isset($_SESSION['acoes_restantes'])
    ) {
        $_SESSION['acoes_restantes'] = 3;
    }
}


/* =========================================================
   🔥 FINALIZAR JOGO DA DISCÓRDIA
   ========================================================= */
function processarDiscordiaConcluida($fase)
{
    if (
        $fase != 'discordia' ||
        !isset($_SESSION['discordia_feito'])
    ) {
        return;
    }

    $_SESSION['evento_extra'][] =
        '🔥 O Jogo da Discórdia incendiou a casa.';

    $_SESSION['fase_semana'] = 'interacoes_3';
    $_SESSION['acoes_restantes'] = 3;

    unset($_SESSION['discordia_feito']);

    header('Location: jogo.php');
    exit;
}


/* =========================================================
   🚨 ENTRADA NA ELIMINAÇÃO
   ========================================================= */
function processarEntradaEliminacao($fase)
{
    if ($fase != 'eliminacao') {
        return;
    }

    unset(
        $_SESSION['vip_definido'],
        $_SESSION['monstro_definido'],
        $_SESSION['paredao_formado'],
        $_SESSION['votos_paredao'],
        $_SESSION['dedo_duro'],
        $_SESSION['indicacao_lider'],
        $_SESSION['indicacao_bigfone'],
        $_SESSION['bigfone_indicacao_pendente'],
        $_SESSION['bigfone_dono_poder'],
        $_SESSION['imunizacao_anjo_feita']
    );

    header('Location: resultado.php');
    exit;
}


/* =========================================================
   🚪 ENTRADA NO QUARTO SECRETO
   Protege o fluxo caso o usuário tente abrir jogo.php
   enquanto o Paredão Falso ainda está ativo.
   ========================================================= */
function processarEntradaQuartoSecreto($fase)
{
    if (
        $fase !== 'quarto_secreto' &&
        empty($_SESSION['paredao_falso_ativo'])
    ) {
        return;
    }

    if (
        !empty($_SESSION['paredao_falso_ativo']) &&
        !empty($_SESSION['falso_eliminado'])
    ) {
        header('Location: quarto_secreto.php');
        exit;
    }
}


/* =========================================================
   🎥 PREPARAR CONFESSIONÁRIO
   ========================================================= */
function prepararEstadoConfessionario(
    &$jogadores,
    $meuNome,
    $fase
) {
    if (
        $fase == 'confessionario' &&
        !isset($_SESSION['confessionario_feito'])
    ) {
        prepararConfessionarioDaRodada(
            $jogadores,
            $meuNome
        );
    }
}


/* =========================================================
   🏆 SINCRONIZAR FASES FINAIS
   ========================================================= */
function sincronizarFasesFinais(
    &$fase,
    $jogadores
) {
    $faseAtual = $_SESSION['fase_semana'] ?? '';

    /*
     * Durante um Paredão Falso há um participante
     * temporariamente fora da lista ativa. Não permite
     * que isso dispare uma final por engano.
     */
    if (!empty($_SESSION['paredao_falso_ativo'])) {
        $fase = 'quarto_secreto';
        return;
    }

    /*
     * Durante a Casa de Vidro o fluxo normal da semana
     * fica pausado até os dois vencedores entrarem.
     */
    if (!empty($_SESSION['casa_vidro_ativa'])) {
        $fase = 'casa_vidro';
        return;
    }

    if (
        $faseAtual != 'jogador_eliminado' &&
        count($jogadores) == 3 &&
        $faseAtual != 'finalistas'
    ) {
        $_SESSION['fase_semana'] = 'finalistas';
        $fase = 'finalistas';
        $faseAtual = 'finalistas';
    }

    if ($faseAtual == 'finalistas') {
        $fase = 'finalistas';
    }

    if (($_SESSION['fase_semana'] ?? '') == 'jogador_eliminado') {
        $fase = 'jogador_eliminado';
    }
}
