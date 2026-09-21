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
   ========================================================= */
   function processarEntradaQuartoSecreto($fase)
   {
       $meuNome =
           trim((string)(
               $_SESSION['meu_nome']
               ?? ''
           ));
   
       $falsoEliminado =
           trim((string)(
               $_SESSION['falso_eliminado']
               ?? ''
           ));
   
       $souEuNoQuarto =
           !empty($_SESSION['paredao_falso_ativo']) &&
           $meuNome !== '' &&
           $falsoEliminado !== '' &&
           nomeIgual(
               $falsoEliminado,
               $meuNome
           );
   
       /*
        * Se quem está escondido é um NPC,
        * o jogador continua vendo o jogo normalmente.
        */
       if (!$souEuNoQuarto) {
           return;
       }
   
       /*
        * Esses POSTs precisam chegar às actions de jogo.php
        * para que a semana continue acontecendo enquanto
        * o jogador acompanha tudo do Quarto Secreto.
        */
       $postPermitido =
           isset($_POST['avancar_fase']) ||
           isset($_POST['ver_vip_xepa']);
   
       if ($postPermitido) {
           return;
       }
   
       /*
        * Em qualquer acesso comum, mantém o jogador
        * dentro da interface do Quarto Secreto.
        */
       header('Location: quarto_secreto.php');
       exit;
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
    $faseAtual =
        $_SESSION['fase_semana'] ?? '';

    $ehMeuParedaoFalso =
        function_exists('paredaoFalsoEhDoJogador') &&
        paredaoFalsoEhDoJogador();

    /*
     * Se EU estou no Quarto Secreto,
     * mantém a tela especial.
     */
    if ($ehMeuParedaoFalso) {
        $fase = 'quarto_secreto';
        return;
    }

    /*
     * Casa de Vidro continua pausando
     * normalmente o fluxo.
     */
    if (!empty($_SESSION['casa_vidro_ativa'])) {
        $fase = 'casa_vidro';
        return;
    }

    /*
     * IMPORTANTE:
     *
     * Um NPC falso eliminado saiu apenas temporariamente
     * de $_SESSION['jogadores'].
     *
     * Para calcular Top 3/final, ele continua contando
     * como participante da temporada.
     */
    $totalRealParticipantes =
        count($jogadores);

    if (
        !empty($_SESSION['paredao_falso_ativo']) &&
        !$ehMeuParedaoFalso &&
        !empty($_SESSION['falso_eliminado_snapshot'])
    ) {
        $totalRealParticipantes++;
    }

    if (
        $faseAtual != 'jogador_eliminado' &&
        $totalRealParticipantes == 3 &&
        $faseAtual != 'finalistas'
    ) {
        $_SESSION['fase_semana'] =
            'finalistas';

        $fase = 'finalistas';
        $faseAtual = 'finalistas';
    }

    if ($faseAtual == 'finalistas') {
        $fase = 'finalistas';
    }

    if (
        ($_SESSION['fase_semana'] ?? '') ==
        'jogador_eliminado'
    ) {
        $fase = 'jogador_eliminado';
    }
}
