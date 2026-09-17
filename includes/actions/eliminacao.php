<?php

/* =========================================================
   🚨 ACTION — ELIMINAÇÃO
   ========================================================= */


/* =========================================================
   📺 REVELAR RESULTADO
   ========================================================= */
if (
    isset($_POST['revelar']) &&
    !isset($_SESSION['ultimo_ranking_eliminacao'])
) {
    $liderAtual = $_SESSION['lider'] ?? '';

    $paredao = array_values(
        array_filter(
            $paredao,
            function ($nome) use ($liderAtual) {
                return !nomeIgual($nome, $liderAtual);
            }
        )
    );

    if (count($paredao) < 2) {
        $_SESSION['evento_extra'][] =
            "⚠️ Erro ao formar paredão: participantes insuficientes.";

        header("Location: jogo.php");
        exit;
    }

    contarParedaoResultadoUmaVez(
        $jogadores,
        $paredao,
        $rodada
    );

    $ranking =
        gerarRankingEliminacaoPorPopularidade(
            $jogadores,
            $paredao
        );

    if (empty($ranking)) {
        $_SESSION['evento_extra'][] =
            "⚠️ Erro ao calcular resultado do paredão.";

        header("Location: jogo.php");
        exit;
    }

    $nomeSaida = array_key_first($ranking);

    ajustarPopularidadePosParedaoResultado(
        $jogadores,
        $ranking,
        $nomeSaida
    );

    /* =====================================================
       🚨 DECIDIR SE É PAREDÃO FALSO
       ===================================================== */
    $ehParedaoFalso =
        prepararParedaoFalsoDaRodada(
            $jogadores,
            $rodada
        );

    if ($ehParedaoFalso) {
        $registrado =
            registrarFalsoEliminado(
                $jogadores,
                $nomeSaida,
                $ranking,
                $rodada
            );

        if (!$registrado) {
            $_SESSION['paredao_falso_ativo'] = false;
            $ehParedaoFalso = false;
        }
    }

    /* =====================================================
       ❌ ELIMINAÇÃO REAL
       ===================================================== */
    if (!$ehParedaoFalso) {
        foreach ($jogadores as $k => $j) {
            if (
                !nomeIgual(
                    $j['nome'] ?? '',
                    $nomeSaida
                )
            ) {
                continue;
            }

            if (nomeIgual($nomeSaida, $meuNome)) {
                $_SESSION['jogador_eliminado'] = true;
                $_SESSION['fase_semana'] = 'jogador_eliminado';
                $_SESSION['meu_jogador_snapshot'] = $j;
                $_SESSION['minha_popularidade_final'] =
                    $j['popularidade'] ?? 50;
                $_SESSION['minha_colocacao_final'] =
                    count($jogadores);
            }

            unset($jogadores[$k]);
            break;
        }

        $_SESSION['jogadores'] = array_values($jogadores);
        $_SESSION['eliminado'] = $nomeSaida;

        if (
            !isset($_SESSION['historico_eliminados']) ||
            !is_array($_SESSION['historico_eliminados'])
        ) {
            $_SESSION['historico_eliminados'] = [];
        }

        $_SESSION['historico_eliminados'][] = $nomeSaida;
        $_SESSION['historico_eliminados'] = array_values(
            array_unique($_SESSION['historico_eliminados'])
        );
    }

    $_SESSION['paredao'] = $paredao;
    $_SESSION['ultimo_ranking_eliminacao'] = $ranking;

    header("Location: resultado.php");
    exit;
}


/* =========================================================
   ▶️ CONTINUAR TEMPORADA
   ========================================================= */
if (isset($_POST['continuar'])) {

    /*
     * Se for Paredão Falso, o próximo passo não é
     * começar outra rodada. Primeiro vamos ao Quarto Secreto.
     */
    if (!empty($_SESSION['paredao_falso_ativo'])) {
        header("Location: quarto_secreto.php");
        exit;
    }

    $eliminadoSessao = $_SESSION['eliminado'] ?? '';

    if (
        nomeIgual($eliminadoSessao, $meuNome) ||
        isset($_SESSION['jogador_eliminado'])
    ) {
        $_SESSION['jogador_eliminado'] = true;
        $_SESSION['fase_semana'] = 'jogador_eliminado';

        header("Location: jogo.php");
        exit;
    }

    if (count($_SESSION['jogadores'] ?? []) <= 3) {
        $_SESSION['fase_semana'] = 'finalistas';

        header("Location: final.php");
        exit;
    }

    $jogadores = $_SESSION['jogadores'] ?? [];

    iniciarNovaRodadaAposEliminacao(
        $jogadores
    );

    header("Location: jogo.php");
    exit;
}


/* =========================================================
   🔄 NOVA TEMPORADA
   ========================================================= */
if (isset($_POST['novo_jogo'])) {
    session_unset();
    session_destroy();

    header("Location: index.php");
    exit;
}
