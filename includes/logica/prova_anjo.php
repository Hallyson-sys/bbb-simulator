<?php

/* =========================================================
   😇 LÓGICA DA PROVA DO ANJO
   ========================================================= */

function obterProvasAnjo()
{
    return [
        1 => [
            'titulo' => '🔑 Chaves da Sorte',
            'texto' => 'Escolha uma chave. A chave certa abre o colar do Anjo.',
            'max' => 20,
            'tipo' => 'select',
            'label' => 'Chave'
        ],
        2 => [
            'titulo' => '⚡ Quiz Turbo',
            'texto' => 'Responda corretamente para tentar conquistar o Anjo.',
            'tipo' => 'quiz'
        ],
        3 => [
            'titulo' => '🎁 Presente Certo',
            'texto' => 'Escolha um presente. Um deles guarda o colar do Anjo.',
            'max' => 5,
            'tipo' => 'botoes',
            'label' => '🎁 Presente'
        ],
        4 => [
            'titulo' => '🌟 Estrela Premiada',
            'texto' => 'Escolha uma estrela. A estrela iluminada vence a prova.',
            'max' => 4,
            'tipo' => 'botoes',
            'label' => '⭐ Estrela'
        ],
        5 => [
            'titulo' => '🦋 Borboleta Azul',
            'texto' => 'Escolha uma borboleta. Uma delas carrega o poder do Anjo.',
            'max' => 3,
            'tipo' => 'botoes',
            'label' => '🦋 Borboleta'
        ],
        6 => [
            'titulo' => '🔮 Cristal do Anjo',
            'texto' => 'Escolha um cristal. O cristal correto revela o vencedor.',
            'max' => 4,
            'tipo' => 'botoes',
            'label' => '🔮 Cristal'
        ],
        7 => [
            'titulo' => '☁️ Nuvem da Sorte',
            'texto' => 'Escolha uma nuvem. Uma delas esconde o colar do Anjo.',
            'max' => 5,
            'tipo' => 'botoes',
            'label' => '☁️ Nuvem'
        ],
        8 => [
            'titulo' => '🪽 Asas do Anjo',
            'texto' => 'Escolha uma asa. A asa certa te leva até o colar.',
            'max' => 3,
            'tipo' => 'botoes',
            'label' => '🪽 Asa'
        ],
        9 => [
            'titulo' => '🌈 Arco da Proteção',
            'texto' => 'Escolha uma cor do arco. Uma delas ativa a proteção do Anjo.',
            'max' => 5,
            'tipo' => 'botoes',
            'label' => '🌈 Cor'
        ],
        10 => [
            'titulo' => '🕯️ Luz do Anjo',
            'texto' => 'Escolha uma vela. A vela acesa revela o campeão.',
            'max' => 4,
            'tipo' => 'botoes',
            'label' => '🕯️ Vela'
        ]
    ];
}

function obterPerguntasQuizAnjo()
{
    return [
        ['p' => 'Quanto é 8 + 9?', 'a' => '17', 'op' => ['16', '18', '17', '15']],
        ['p' => 'Qual letra vem depois do M?', 'a' => 'N', 'op' => ['L', 'N', 'P', 'O']],
        ['p' => '5 x 4 = ?', 'a' => '20', 'op' => ['15', '25', '20', '18']],
        ['p' => 'Qual número vem depois do 29?', 'a' => '30', 'op' => ['28', '31', '30', '39']],
        ['p' => 'Qual palavra combina com proteção?', 'a' => 'Escudo', 'op' => ['Escudo', 'Espelho', 'Fogo', 'Chuva']]
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
        $_SESSION['caixa_certa'],
        $_SESSION['quiz_anjo'],
        $_SESSION['anjo_numero_certo'],
        $_SESSION['prova_anjo_finalizada'],
        $_SESSION['prova_anjo_finalizada_rodada'],
        $_SESSION['anjo_autoimune_sorteado_rodada']
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
        $_SESSION['prova_anjo_tipo'] = rand(1, count($provas));
    }

    $tipo = (int)$_SESSION['prova_anjo_tipo'];

    if (!isset($provas[$tipo])) {
        $tipo = 1;
        $_SESSION['prova_anjo_tipo'] = 1;
    }

    if ($tipo === 1 && !isset($_SESSION['caixa_certa'])) {
        $_SESSION['caixa_certa'] = rand(1, 20);
    }

    if ($tipo >= 3 && $tipo <= 10 && !isset($_SESSION['anjo_numero_certo'])) {
        $maximo = (int)($provas[$tipo]['max'] ?? 5);
        $_SESSION['anjo_numero_certo'] = rand(1, max(1, $maximo));
    }

    if ($tipo === 2 && !isset($_SESSION['quiz_anjo'])) {
        $perguntas = obterPerguntasQuizAnjo();
        $_SESSION['quiz_anjo'] = $perguntas[array_rand($perguntas)];
    }

    return [
        'tipo' => $tipo,
        'prova' => $provas[$tipo]
    ];
}

function sortearNPCAnjo($participantes, $meuNome)
{
    if (empty($participantes)) {
        return '';
    }

    $npcs = [];

    foreach ($participantes as $p) {
        if (($p['nome'] ?? '') !== $meuNome) {
            $npcs[] = $p;
        }
    }

    if (!empty($npcs)) {
        $campeao = $npcs[array_rand($npcs)];
        return $campeao['nome'] ?? '';
    }

    $campeao = $participantes[array_rand($participantes)];
    return $campeao['nome'] ?? '';
}

function jogadorVenceuProvaAnjo($post)
{
    $dadosProva = prepararProvaAnjo();
    $tipo = (int)$dadosProva['tipo'];

    if ($tipo === 1) {
        $chave = (int)($post['caixa'] ?? 0);
        return $chave === (int)($_SESSION['caixa_certa'] ?? 0);
    }

    if ($tipo === 2) {
        $resposta = (string)($post['quiz'] ?? '');
        return $resposta === (string)($_SESSION['quiz_anjo']['a'] ?? '');
    }

    if ($tipo >= 3 && $tipo <= 10) {
        $escolha = (int)($post['escolha'] ?? 0);
        return $escolha === (int)($_SESSION['anjo_numero_certo'] ?? 0);
    }

    return false;
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

    unset(
        $_SESSION['prova_anjo_tipo'],
        $_SESSION['caixa_certa'],
        $_SESSION['quiz_anjo'],
        $_SESSION['anjo_numero_certo']
    );

    return true;
}

function textoBotaoAnjo($prova, $i)
{
    $label = $prova['label'] ?? 'Opção';

    if (($prova['titulo'] ?? '') === '🌈 Arco da Proteção') {
        $cores = ['Rosa', 'Azul', 'Dourado', 'Verde', 'Roxo'];
        return '🌈 ' . ($cores[$i - 1] ?? "Cor $i");
    }

    if (($prova['titulo'] ?? '') === '🪽 Asas do Anjo') {
        $letras = ['A', 'B', 'C'];
        return $label . ' ' . ($letras[$i - 1] ?? $i);
    }

    return $label . ' ' . $i;
}
