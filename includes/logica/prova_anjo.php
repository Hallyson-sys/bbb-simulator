<?php

/* =========================================================
   😇 LÓGICA DA PROVA DO ANJO — MINIGAMES 2.0
   ========================================================= */

function obterProvasAnjo()
{
    return [
        1 => [
            'slug' => 'memoria',
            'titulo' => '🧠 Memória do Anjo',
            'texto' => 'Encontre todos os pares antes que o tempo pese contra você.',
            'categoria' => 'Memória',
            'dificuldade' => 'Média'
        ],
        2 => [
            'slug' => 'caixas',
            'titulo' => '🎁 Caixas Misteriosas',
            'texto' => 'Escolha três caixas. Elas podem aumentar, manter ou reduzir sua pontuação.',
            'categoria' => 'Sorte + estratégia',
            'dificuldade' => 'Imprevisível'
        ],
        3 => [
            'slug' => 'sequencia',
            'titulo' => '⚡ Sequência Relâmpago',
            'texto' => 'Memorize os sinais e repita a ordem correta em três rodadas cada vez mais rápidas.',
            'categoria' => 'Atenção',
            'dificuldade' => 'Alta'
        ],
        4 => [
            'slug' => 'mira',
            'titulo' => '🎯 Mira do Anjo',
            'texto' => 'Pare o marcador o mais perto possível do centro em três tentativas.',
            'categoria' => 'Precisão',
            'dificuldade' => 'Média'
        ]
    ];
}

function sincronizarEstadoProvaAnjoDaRodada()
{
    $rodadaAtual = (int)($_SESSION['rodada'] ?? 1);
    $rodadaEstado = (int)($_SESSION['prova_anjo_rodada'] ?? 0);

    if ($rodadaEstado === $rodadaAtual) {
        return;
    }

    unset(
        $_SESSION['prova_anjo_tipo'],
        $_SESSION['prova_anjo_finalizada'],
        $_SESSION['prova_anjo_finalizada_rodada'],
        $_SESSION['anjo_autoimune_sorteado_rodada'],
        $_SESSION['resultado_prova_anjo']
    );

    $_SESSION['prova_anjo_rodada'] = $rodadaAtual;
}

function obterParticipantesProvaAnjo($jogadores, $lider)
{
    $participantes = [];

    foreach ($jogadores as $j) {
        if (($j['nome'] ?? '') !== $lider) {
            $participantes[] = $j;
        }
    }

    return array_values($participantes);
}

function chanceAnjoAutoimune($totalJogadores)
{
    if ((int)$totalJogadores <= 10) {
        return 100;
    }

    return 30;
}

function semanaAnjoAutoimune($totalJogadores)
{
    $rodadaAtual = (int)($_SESSION['rodada'] ?? 1);
    $rodadaSorteada = (int)($_SESSION['anjo_autoimune_sorteado_rodada'] ?? 0);

    if ($rodadaSorteada !== $rodadaAtual) {
        $_SESSION['anjo_autoimune_sorteado_rodada'] = $rodadaAtual;
        $_SESSION['anjo_autoimune'] =
            rand(1, 100) <= chanceAnjoAutoimune($totalJogadores);
    }

    return !empty($_SESSION['anjo_autoimune']);
}

function prepararProvaAnjo()
{
    sincronizarEstadoProvaAnjoDaRodada();

    $provas = obterProvasAnjo();

    if (!isset($_SESSION['prova_anjo_tipo'])) {
        $_SESSION['prova_anjo_tipo'] = array_rand($provas);
    }

    $tipo = (int)$_SESSION['prova_anjo_tipo'];

    if (!isset($provas[$tipo])) {
        $tipo = 1;
        $_SESSION['prova_anjo_tipo'] = 1;
    }

    return [
        'tipo' => $tipo,
        'prova' => $provas[$tipo]
    ];
}

function obterBonusPersonalidadeProvaAnjo($personalidade, $slug)
{
    $personalidade = (string)$personalidade;

    $bonus = [
        'memoria' => [
            'Estrategista' => 10,
            'Manipulador' => 5,
            'Líder Nato' => 4,
            'Planta' => -3,
            'Explosivo' => -2
        ],
        'caixas' => [
            'Estrategista' => 3,
            'Manipulador' => 3,
            'Emocional' => 2,
            'Explosivo' => 2,
            'Planta' => -1
        ],
        'sequencia' => [
            'Estrategista' => 8,
            'Líder Nato' => 5,
            'Manipulador' => 4,
            'Explosivo' => 2,
            'Emocional' => -3
        ],
        'mira' => [
            'Explosivo' => 7,
            'Líder Nato' => 5,
            'Estrategista' => 3,
            'Emocional' => -2,
            'Planta' => -2
        ]
    ];

    return (int)($bonus[$slug][$personalidade] ?? 0);
}

function simularPontuacaoNPCAnjo($jogador, $slug)
{
    /*
     * A personalidade só inclina discretamente o desempenho.
     * Sorte continua pesando bastante para que a prova não fique previsível.
     */
    $base = rand(45, 88);
    $bonus = obterBonusPersonalidadeProvaAnjo(
        $jogador['personalidade'] ?? 'Neutro',
        $slug
    );

    if (rand(1, 100) <= 10) {
        $base += rand(5, 12); // momento excepcional
    }

    if (rand(1, 100) <= 8) {
        $base -= rand(6, 14); // erro inesperado
    }

    return max(18, min(100, $base + $bonus));
}

function normalizarPontuacaoJogadorAnjo($post)
{
    if ((string)($post['minigame_concluido'] ?? '') !== '1') {
        return 0;
    }

    $pontuacao = (int)($post['pontuacao_anjo'] ?? 0);
    return max(0, min(100, $pontuacao));
}

function gerarRankingProvaAnjo($participantes, $meuNome, $pontuacaoJogador = null)
{
    $dados = prepararProvaAnjo();
    $slug = (string)($dados['prova']['slug'] ?? 'memoria');
    $ranking = [];

    foreach ($participantes as $participante) {
        $nome = (string)($participante['nome'] ?? 'Participante');
        $ehJogador = $meuNome !== '' && $nome === $meuNome && $pontuacaoJogador !== null;

        $pontos = $ehJogador
            ? (int)$pontuacaoJogador
            : simularPontuacaoNPCAnjo($participante, $slug);

        $ranking[] = [
            'nome' => $nome,
            'pontos' => $pontos,
            'eh_jogador' => $ehJogador,
            'desempate' => random_int(1, 1000000)
        ];
    }

    usort($ranking, function ($a, $b) {
        if ($a['pontos'] === $b['pontos']) {
            return $b['desempate'] <=> $a['desempate'];
        }

        return $b['pontos'] <=> $a['pontos'];
    });

    foreach ($ranking as $indice => &$item) {
        $item['posicao'] = $indice + 1;
        unset($item['desempate']);
    }
    unset($item);

    return $ranking;
}

function salvarResultadoVisualProvaAnjo($ranking, $prova, $meuNome, $participou)
{
    $campeao = $ranking[0]['nome'] ?? '';
    $posicaoJogador = null;
    $pontosJogador = null;

    foreach ($ranking as $item) {
        if (($item['nome'] ?? '') === $meuNome) {
            $posicaoJogador = (int)($item['posicao'] ?? 0);
            $pontosJogador = (int)($item['pontos'] ?? 0);
            break;
        }
    }

    $_SESSION['resultado_prova_anjo'] = [
        'rodada' => (int)($_SESSION['rodada'] ?? 1),
        'campeao' => $campeao,
        'ranking' => $ranking,
        'prova' => $prova,
        'participou' => (bool)$participou,
        'posicao_jogador' => $posicaoJogador,
        'pontos_jogador' => $pontosJogador,
        'autoimune' => !empty($_SESSION['anjo_autoimune'])
    ];
}

function provaAnjoFinalizadaNaRodadaAtual()
{
    $rodadaAtual = (int)($_SESSION['rodada'] ?? 1);
    $rodadaFinalizada = (int)($_SESSION['prova_anjo_finalizada_rodada'] ?? 0);

    return
        !empty($_SESSION['prova_anjo_finalizada']) &&
        $rodadaFinalizada === $rodadaAtual;
}

function finalizarProvaAnjo(&$jogadores, $campeaoNome, $lider)
{
    if (!isset($_SESSION['evento_extra']) || !is_array($_SESSION['evento_extra'])) {
        $_SESSION['evento_extra'] = [];
    }

    $campeaoNome = trim((string)$campeaoNome);

    if ($campeaoNome === '') {
        $_SESSION['evento_extra'][] =
            '⚠️ Não foi possível definir o vencedor da Prova do Anjo.';
        return false;
    }

    $autoimune = semanaAnjoAutoimune(count($jogadores));

    foreach ($jogadores as &$j) {
        if (!isset($j['status']) || !is_array($j['status'])) {
            $j['status'] = [];
        }

        $j['status']['anjo'] = false;

        if (
            !empty($_SESSION['imune']) &&
            ($j['nome'] ?? '') === ($_SESSION['imune'] ?? '')
        ) {
            $j['status']['imune'] = false;
        }
    }
    unset($j);

    foreach ($jogadores as &$j) {
        if (($j['nome'] ?? '') !== $campeaoNome) {
            continue;
        }

        if (!isset($j['status']) || !is_array($j['status'])) {
            $j['status'] = [];
        }

        if (!isset($j['estatisticas']) || !is_array($j['estatisticas'])) {
            $j['estatisticas'] = [];
        }

        $j['status']['anjo'] = true;
        $j['estatisticas']['anjo'] =
            ($j['estatisticas']['anjo'] ?? 0) + 1;

        if ($autoimune) {
            $j['status']['imune'] = true;
            $j['estatisticas']['imune'] =
                ($j['estatisticas']['imune'] ?? 0) + 1;
        }
    }
    unset($j);

    $_SESSION['jogadores'] = array_values($jogadores);
    $_SESSION['anjo'] = $campeaoNome;

    if (function_exists('registrarHistoricoTemporada')) {
        registrarHistoricoTemporada(
            'anjo',
            'Prova do Anjo',
            $autoimune
                ? "$campeaoNome venceu a Prova do Anjo e conquistou autoimunidade."
                : "$campeaoNome venceu a Prova do Anjo.",
            [$campeaoNome],
            '😇',
            null,
            'anjo'
        );
    }

    $_SESSION['prova_anjo_finalizada'] = true;
    $_SESSION['prova_anjo_finalizada_rodada'] = (int)($_SESSION['rodada'] ?? 1);
    $_SESSION['fase_semana'] = 'monstro';

    if ($autoimune) {
        $_SESSION['imune'] = $campeaoNome;
        $_SESSION['anjo_autoimune'] = true;
        $_SESSION['imunizacao_anjo_feita'] = true;
        $_SESSION['anjo_autoimune_estat_contada'] = true;

        $_SESSION['evento_extra'][] =
            "😇 $campeaoNome venceu a Prova do Anjo e nesta semana o Anjo é autoimune.";

        $_SESSION['evento_extra'][] =
            "🛡️ $campeaoNome está imune e não precisará imunizar outra pessoa.";
    } else {
        $_SESSION['anjo_autoimune'] = false;

        unset(
            $_SESSION['imune'],
            $_SESSION['imunizacao_anjo_feita'],
            $_SESSION['anjo_autoimune_estat_contada']
        );

        $_SESSION['evento_extra'][] =
            "😇 $campeaoNome venceu a Prova do Anjo e poderá imunizar alguém antes do paredão.";
    }

    return true;
}