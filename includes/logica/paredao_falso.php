<?php

/* =========================================================
   🚨 PAREDÃO FALSO
   - Pode acontecer apenas uma vez por temporada.
   - Só entra no sorteio a partir da Rodada 3.
   - Não acontece quando a temporada já está perto da final.
   ========================================================= */


/* =========================================================
   🎲 CONFIGURAÇÃO
   ========================================================= */
function chanceParedaoFalso()
{
    return 18;
}


function podeTerParedaoFalso(
    $jogadores,
    $rodada
) {
    if (!empty($_SESSION['paredao_falso_realizado'])) {
        return false;
    }

    if ((int) $rodada < 3) {
        return false;
    }

    /*
     * Evita a dinâmica perto demais da final.
     * Com 7 participantes, o falso eliminado sai
     * temporariamente e ainda ficam 6 ativos.
     */
    if (count($jogadores) < 7) {
        return false;
    }

    return true;
}


/* =========================================================
   🎯 DECIDIR SE A RODADA TERÁ PAREDÃO FALSO
   A decisão é feita uma única vez por rodada.
   ========================================================= */
function prepararParedaoFalsoDaRodada(
    $jogadores,
    $rodada
) {
    $rodada = (int) $rodada;

    if (!podeTerParedaoFalso($jogadores, $rodada)) {
        $_SESSION['paredao_falso_ativo'] = false;
        return false;
    }

    if (
        isset($_SESSION['paredao_falso_decidido_rodada']) &&
        (int) $_SESSION['paredao_falso_decidido_rodada'] === $rodada
    ) {
        return !empty($_SESSION['paredao_falso_ativo']);
    }

    $_SESSION['paredao_falso_decidido_rodada'] = $rodada;

    /*
     * Para testes locais, você pode temporariamente definir:
     * $_SESSION['forcar_paredao_falso'] = true;
     * antes de revelar o resultado.
     */
    if (!empty($_SESSION['forcar_paredao_falso'])) {
        $_SESSION['paredao_falso_ativo'] = true;
        unset($_SESSION['forcar_paredao_falso']);
        return true;
    }

    $_SESSION['paredao_falso_ativo'] =
        rand(1, 100) <= chanceParedaoFalso();

    return !empty($_SESSION['paredao_falso_ativo']);
}


/* =========================================================
   👤 REGISTRAR FALSO ELIMINADO
   Remove temporariamente da lista ativa, mas NÃO elimina.
   ========================================================= */
function registrarFalsoEliminado(
    &$jogadores,
    $nome,
    $ranking,
    $rodada
) {
    $snapshot = null;

    foreach ($jogadores as $indice => $j) {
        if (!nomeIgual($j['nome'] ?? '', $nome)) {
            continue;
        }

        $snapshot = $j;

        if (!isset($snapshot['estatisticas'])) {
            $snapshot['estatisticas'] = [];
        }

        $snapshot['estatisticas']['paredao_falso'] =
            ($snapshot['estatisticas']['paredao_falso'] ?? 0) + 1;

        unset($jogadores[$indice]);
        break;
    }

    if (!$snapshot) {
        return false;
    }

    $jogadores = array_values($jogadores);
    $_SESSION['jogadores'] = $jogadores;

    $_SESSION['paredao_falso_ativo'] = true;
    $_SESSION['paredao_falso_realizado'] = true;
    $_SESSION['paredao_falso_revelado'] = true;
    $_SESSION['paredao_falso_rodada'] = (int) $rodada;

    $_SESSION['falso_eliminado'] = $nome;
    $_SESSION['falso_eliminado_snapshot'] = $snapshot;
    $_SESSION['falso_eliminado_ranking'] = $ranking;

    $_SESSION['fase_semana'] = 'quarto_secreto';

    return true;
}


/* =========================================================
   👀 ESPIADINHAS DO QUARTO SECRETO
   ========================================================= */
function gerarEspiadinhasQuartoSecreto(
    $jogadores,
    $falsoEliminado,
    $meuNome
) {
    $observacoes = [];

    $snapshot =
        $_SESSION['falso_eliminado_snapshot'] ?? null;

    $base = $jogadores;

    if (
        is_array($snapshot) &&
        !empty($snapshot['nome'])
    ) {
        $jaExiste = false;

        foreach ($base as $j) {
            if (nomeIgual($j['nome'] ?? '', $snapshot['nome'])) {
                $jaExiste = true;
                break;
            }
        }

        if (!$jaExiste) {
            $base[] = $snapshot;
        }
    }

    /* Aliado mais forte do falso eliminado. */
    $melhorAliado = null;
    $melhorAliadoScore = -9999;

    /* Rival mais forte do falso eliminado. */
    $maiorRival = null;
    $maiorRivalScore = -9999;

    foreach ($base as $alvo) {
        $nomeAlvo = $alvo['nome'] ?? '';

        if (
            $nomeAlvo === '' ||
            nomeIgual($nomeAlvo, $falsoEliminado)
        ) {
            continue;
        }

        $rel = obterRelacaoCompleta(
            $base,
            $falsoEliminado,
            $nomeAlvo,
            $meuNome
        );

        $scoreAliado =
            ($rel['amizade'] ?? 0) +
            ($rel['confianca'] ?? 0) -
            ($rel['rivalidade'] ?? 0);

        $scoreRival =
            ($rel['rivalidade'] ?? 0) +
            max(0, 40 - ($rel['amizade'] ?? 0)) +
            max(0, 40 - ($rel['confianca'] ?? 0));

        if ($scoreAliado > $melhorAliadoScore) {
            $melhorAliadoScore = $scoreAliado;
            $melhorAliado = $nomeAlvo;
        }

        if ($scoreRival > $maiorRivalScore) {
            $maiorRivalScore = $scoreRival;
            $maiorRival = $nomeAlvo;
        }
    }

    if ($melhorAliado) {
        $observacoes[] =
            "🤝 <b>$melhorAliado</b> parece ser a pessoa mais próxima de <b>$falsoEliminado</b> dentro da casa.";
    }

    if ($maiorRival) {
        $observacoes[] =
            "🔥 <b>$maiorRival</b> é o nome que concentra a maior tensão com <b>$falsoEliminado</b>.";
    }

    /* Pessoa mais popular entre quem ficou. */
    $maisPopular = null;
    $maiorPopularidade = -1;

    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';
        $pop = (int) ($j['popularidade'] ?? 50);

        if ($nome !== '' && $pop > $maiorPopularidade) {
            $maiorPopularidade = $pop;
            $maisPopular = $nome;
        }
    }

    if ($maisPopular) {
        $observacoes[] =
            "📈 <b>$maisPopular</b> aparece como um dos nomes mais fortes da temporada neste momento.";
    }

    /* Reaproveita acontecimentos recentes do Ao Vivo. */
    $eventos = $_SESSION['evento_extra'] ?? [];

    if (is_array($eventos)) {
        $recentes = array_slice($eventos, -3);

        foreach ($recentes as $evento) {
            $evento = trim((string) $evento);

            if ($evento !== '') {
                $observacoes[] = "📺 $evento";
            }
        }
    }

    return array_slice(
        array_values(array_unique($observacoes)),
        0,
        6
    );
}


/* =========================================================
   🚪 RETORNAR PARA A CASA
   ========================================================= */
function retornarFalsoEliminadoParaCasa(&$jogadores)
{
    if (empty($_SESSION['paredao_falso_ativo'])) {
        return false;
    }

    $snapshot =
        $_SESSION['falso_eliminado_snapshot'] ?? null;

    $nome =
        $_SESSION['falso_eliminado'] ?? '';

    if (
        !$snapshot ||
        !is_array($snapshot) ||
        $nome === ''
    ) {
        return false;
    }

    $existe = false;

    foreach ($jogadores as $j) {
        if (nomeIgual($j['nome'] ?? '', $nome)) {
            $existe = true;
            break;
        }
    }

    if (!$existe) {
        if (!isset($snapshot['popularidade'])) {
            $snapshot['popularidade'] = 50;
        }

        /* O retorno rende repercussão positiva. */
        $snapshot['popularidade'] = limitar(
            $snapshot['popularidade'] + rand(4, 8),
            0,
            100
        );

        if (!isset($snapshot['status']) || !is_array($snapshot['status'])) {
            $snapshot['status'] = [];
        }

        $snapshot['status']['lider'] = false;
        $snapshot['status']['anjo'] = false;
        $snapshot['status']['imune'] = false;
        $snapshot['status']['vip'] = false;
        $snapshot['status']['xepa'] = false;
        $snapshot['status']['monstro'] = false;
        $snapshot['status']['retorno_paredao_falso'] = true;

        $jogadores[] = $snapshot;
    }

    $_SESSION['jogadores'] = array_values($jogadores);

    $_SESSION['evento_extra'][] =
        "🚪 PAREDÃO FALSO! <b>$nome</b> voltou do Quarto Secreto para a casa.";

    $_SESSION['paredao_falso_ativo'] = false;
    $_SESSION['paredao_falso_retorno_realizado'] = true;
    $_SESSION['paredao_falso_ultimo_retorno'] = $nome;
    $_SESSION['paredao_falso_feed_pendente'] = [
        'nome' => $nome,
        'rodada_origem' =>
            (int) ($_SESSION['paredao_falso_rodada'] ?? $_SESSION['rodada'] ?? 1)
    ];

    unset(
        $_SESSION['falso_eliminado'],
        $_SESSION['falso_eliminado_snapshot'],
        $_SESSION['falso_eliminado_ranking'],
        $_SESSION['paredao_falso_revelado']
    );

    return true;
}
