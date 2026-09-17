<?php

/* =========================================================
   🚀 INICIALIZAÇÃO DO JOGO
   ========================================================= */


/* =========================================================
   👥 REMOVER PARTICIPANTES DUPLICADOS
   ========================================================= */
function removerParticipantesDuplicados(
    &$jogadores,
    $meuNome
) {
    $nomesUsados = [];
    $jogadoresUnicos = [];

    foreach ($jogadores as $j) {
        $nome = trim($j['nome'] ?? '');

        if ($nome == '') {
            continue;
        }

        $chaveNome = mb_strtolower($nome, 'UTF-8');

        if (isset($nomesUsados[$chaveNome])) {
            /* Se o duplicado for o jogador, prioriza o snapshot mais recente. */
            if (nomeIgual($nome, $meuNome)) {
                $idx = $nomesUsados[$chaveNome];
                $jogadoresUnicos[$idx] =
                    $_SESSION['meu_jogador_snapshot'] ?? $j;
            }

            continue;
        }

        $nomesUsados[$chaveNome] = count($jogadoresUnicos);
        $jogadoresUnicos[] = $j;
    }

    $jogadores = array_values($jogadoresUnicos);

    garantirMeuJogadorNaLista($jogadores);
}


/* =========================================================
   🧱 GARANTIR ESTRUTURA DOS PARTICIPANTES
   Compatibilidade com saves antigos
   ========================================================= */
function garantirEstruturaParticipantes(&$jogadores)
{
    foreach ($jogadores as &$j) {

        /* =========================
           📈 POPULARIDADE
           ========================= */
        if (!isset($j['popularidade'])) {
            $j['popularidade'] = 50;
        }

        $j['popularidade'] = limitar(
            $j['popularidade'],
            0,
            100
        );

        if (
            !isset($j['historico_popularidade']) ||
            !is_array($j['historico_popularidade'])
        ) {
            $j['historico_popularidade'] = [];
        }


        /* =========================
           💕 ROMANCES
           ========================= */
        if (
            !isset($j['romances']) ||
            !is_array($j['romances'])
        ) {
            $j['romances'] = [];
        }


        /* =========================
           🎥 CONFESSIONÁRIOS
           ========================= */
        if (
            !isset($j['confessionarios']) ||
            !is_array($j['confessionarios'])
        ) {
            $j['confessionarios'] = [];
        }


        /* =========================
           🤝 ALIANÇAS
           ========================= */
        if (!array_key_exists('alianca', $j)) {
            $j['alianca'] = null;
        }

        if (
            !isset($j['historico_aliancas']) ||
            !is_array($j['historico_aliancas'])
        ) {
            $j['historico_aliancas'] = [];
        }
    }

    unset($j);
}


/* =========================================================
   ❤️ GARANTIR RELAÇÕES INICIAIS DO JOGADOR
   ========================================================= */
function garantirRelacoesIniciaisJogador(
    $jogadores,
    $meuNome
) {
    if (
        !isset($_SESSION['relacoes_jogador']) ||
        !is_array($_SESSION['relacoes_jogador'])
    ) {
        $_SESSION['relacoes_jogador'] = [];
    }

    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';

        if (
            $nome == '' ||
            nomeIgual($nome, $meuNome)
        ) {
            continue;
        }

        if (!isset($_SESSION['relacoes_jogador'][$nome])) {
            /* A afinidade começa em 0 e NÃO é limitada a 100. */
            $_SESSION['relacoes_jogador'][$nome] = 0;
        }
    }
}


/* =========================================================
   🚫 ATUALIZAR ESTADO DO JOGADOR
   ========================================================= */
function atualizarEstadoDoMeuJogador(
    $jogadores,
    $meuNome
) {
    $meuJogadorAtual = null;

    foreach ($jogadores as $j) {
        if (nomeIgual($j['nome'] ?? '', $meuNome)) {
            $meuJogadorAtual = $j;
            break;
        }
    }

    /* Enquanto estiver ativo, mantém um snapshot atualizado. */
    if ($meuJogadorAtual != null) {
        $_SESSION['meu_jogador_snapshot'] = $meuJogadorAtual;
        $_SESSION['minha_popularidade_final'] =
            $meuJogadorAtual['popularidade'] ?? 50;
    }

    $eliminadoSessao = $_SESSION['eliminado'] ?? null;
    $nomeEliminadoSessao = '';

    if (is_array($eliminadoSessao)) {
        $nomeEliminadoSessao = $eliminadoSessao['nome'] ?? '';
    } else {
        $nomeEliminadoSessao = (string) $eliminadoSessao;
    }

    if (
        $meuNome != '' &&
        (
            $meuJogadorAtual == null ||
            nomeIgual($nomeEliminadoSessao, $meuNome)
        ) &&
        ($_SESSION['fase_semana'] ?? '') != 'jogador_eliminado'
    ) {
        $_SESSION['jogador_eliminado'] = true;
        $_SESSION['fase_semana'] = 'jogador_eliminado';
        $_SESSION['minha_colocacao_final'] = count($jogadores) + 1;

        if (
            !isset($_SESSION['evento_extra']) ||
            !is_array($_SESSION['evento_extra'])
        ) {
            $_SESSION['evento_extra'] = [];
        }

        $_SESSION['evento_extra'][] =
            "🚫 $meuNome foi eliminado. Sua participação na temporada chegou ao fim.";
    }

    if (
        isset($_SESSION['jogador_eliminado']) &&
        !isset($_POST['novo_jogo'])
    ) {
        $_SESSION['fase_semana'] = 'jogador_eliminado';
    }

    return $meuJogadorAtual;
}