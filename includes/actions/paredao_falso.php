<?php

/* =========================================================
   🚪 ACTION — RETORNO DO PAREDÃO FALSO
   ========================================================= */

if (isset($_POST['retornar_paredao_falso'])) {

    if (empty($_SESSION['paredao_falso_ativo'])) {
        header("Location: jogo.php");
        exit;
    }


    $jogadores =
        $_SESSION['jogadores'] ?? [];

    $meuNome =
        $_SESSION['meu_nome'] ?? '';

    $falsoEliminado =
        $_SESSION['falso_eliminado'] ?? '';

    $rodadaAtual =
        (int)($_SESSION['rodada'] ?? 1);

    $rodadaOrigem =
        (int)(
            $_SESSION['paredao_falso_rodada']
            ?? $rodadaAtual
        );

    $rodadaRetorno =
        (int)(
            $_SESSION['paredao_falso_rodada_retorno']
            ?? ($rodadaOrigem + 1)
        );

    $faseAtual =
        $_SESSION['fase_semana'] ?? '';


    /* =====================================================
       🔒 SEGURANÇA

       A tela do Quarto Secreto é exclusivamente para
       quando o próprio jogador foi falsamente eliminado.
       ===================================================== */

    if (
        $falsoEliminado === '' ||
        $meuNome === '' ||
        !nomeIgual(
            $falsoEliminado,
            $meuNome
        )
    ) {
        header("Location: jogo.php");
        exit;
    }


    /* =====================================================
       ⏳ AINDA NÃO CHEGOU A HORA

       O retorno só pode acontecer:
       - na rodada seguinte;
       - imediatamente antes da Festa.
       ===================================================== */

    $retornoLiberado =
        $rodadaAtual >= $rodadaRetorno &&
        $faseAtual === 'festa';

    if (!$retornoLiberado) {

        $_SESSION['mensagem_quarto_secreto'] =
            "⏳ Ainda não é hora de voltar. Seu retorno acontecerá antes da Festa da próxima rodada.";

        header("Location: quarto_secreto.php");
        exit;
    }


    /* =====================================================
       🚪 RETORNO
       ===================================================== */

    $retornou =
        retornarFalsoEliminadoParaCasa(
            $jogadores
        );

    if (!$retornou) {

        if (
            !isset($_SESSION['evento_extra']) ||
            !is_array($_SESSION['evento_extra'])
        ) {
            $_SESSION['evento_extra'] = [];
        }

        $_SESSION['evento_extra'][] =
            "⚠️ Não foi possível concluir o retorno do Paredão Falso.";

        header("Location: quarto_secreto.php");
        exit;
    }


    /*
     * NÃO inicia outra rodada aqui.
     *
     * Nós já estamos na rodada seguinte.
     * Apenas devolvemos o participante para a casa
     * e continuamos exatamente na Festa.
     */
    $_SESSION['fase_semana'] =
        'festa';

    unset(
        $_SESSION['mensagem_quarto_secreto']
    );


    header("Location: jogo.php");
    exit;
}