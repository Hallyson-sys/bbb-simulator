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

    if (function_exists('registrarHistoricoTemporada')) {
        registrarHistoricoTemporada(
            'paredao',
            'Paredão',
            'Paredão formado por ' . implode(', ', $paredao) . '.',
            $paredao,
            '🔥',
            $rodada,
            'paredao_final'
        );
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
    $ehParedaoFalso = !empty($_SESSION['top4_concluido'])
        && (int)($_SESSION['top4_rodada'] ?? 0) === (int)$rodada
        ? false
        : prepararParedaoFalsoDaRodada($jogadores, $rodada);

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
        } elseif (
            function_exists('registrarHistoricoTemporada') &&
            nomeIgual($nomeSaida, $meuNome)
        ) {
            /*
             * Só revela a natureza falsa da eliminação no histórico
             * quando o próprio jogador foi enviado ao Quarto Secreto.
             * Para NPCs, a surpresa permanece oculta até o retorno.
             */
            registrarHistoricoTemporada(
                'paredao_falso',
                'Paredão Falso',
                "$nomeSaida deixou a casa em um Paredão Falso e foi para o Quarto Secreto.",
                [$nomeSaida],
                '🎭',
                $rodada,
                'paredao_falso_saida'
            );
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

            if (!isset($_SESSION['participantes_eliminados_dados']) || !is_array($_SESSION['participantes_eliminados_dados'])) {
                $_SESSION['participantes_eliminados_dados'] = [];
            }

            $_SESSION['participantes_eliminados_dados'][$nomeSaida] = $j;

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

        // Entrevista do NPC é congelada na saída; o jogador não vê enquanto
        // estiver confinado. Para o próprio jogador, perguntas ficam pendentes.
        require_once __DIR__ . '/../logica/bate_papo_eliminado.php';
        bpPreparar((string)$nomeSaida,
            (array)($_SESSION['participantes_eliminados_dados'][$nomeSaida] ?? []),
            (int)$rodada, nomeIgual((string)$nomeSaida, (string)$meuNome),
            nomeIgual((string)$nomeSaida, (string)$meuNome)
                ? bpVTConfessionarioVerificado((string)$meuNome, $jogadores) : null);

        if (function_exists('registrarHistoricoTemporada')) {
            registrarHistoricoTemporada(
                'eliminacao',
                'Eliminação',
                "$nomeSaida foi eliminado da temporada.",
                [$nomeSaida],
                '❌',
                $rodada,
                'eliminacao'
            );
        }
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
    if (
        !empty($_SESSION['paredao_falso_ativo']) &&
        !empty($_SESSION['falso_eliminado']) &&
        $meuNome !== '' &&
        nomeIgual($_SESSION['falso_eliminado'], $meuNome)
    ) {
        /* Só o próprio falso eliminado sabe que existe Quarto Secreto. */
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

        header("Location: bate_papo.php");
        exit;
    }

    // A semifinal também pode ser acompanh pelo modo espectador.
    if (!empty($_SESSION['modo_espectador']) && count($_SESSION['jogadores'] ?? []) <= 3) {
        $_SESSION['fase_semana'] = 'finalistas';
        header('Location: final.php');
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

    if (count($jogadores) === 4 && empty($_SESSION['paredao_falso_ativo'])) {
        $_SESSION['fase_semana'] = 'top4_prova';
        $_SESSION['evento_extra'][] = '🏆 TOP 4 — Uma prova decisiva dará a primeira vaga na Grande Final. Os três perdedores irão ao último Paredão!';
        header('Location: top4.php');
        exit;
    }

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
