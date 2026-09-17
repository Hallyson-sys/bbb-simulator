<?php

/* =========================================================
   🏠 CASA DE VIDRO
   ========================================================= */


/* =========================================================
   🧱 REGISTRAR NOMES JÁ USADOS NA TEMPORADA
   ========================================================= */
function registrarNomesTemporadaCasaVidro($jogadores)
{
    if (
        !isset($_SESSION['nomes_participantes_temporada']) ||
        !is_array($_SESSION['nomes_participantes_temporada'])
    ) {
        $_SESSION['nomes_participantes_temporada'] = [];
    }

    foreach ($jogadores as $j) {
        $nome = trim($j['nome'] ?? '');

        if ($nome === '') {
            continue;
        }

        $_SESSION['nomes_participantes_temporada'][] = $nome;
    }

    if (
        !empty($_SESSION['elenco_personalizado']) &&
        is_array($_SESSION['elenco_personalizado'])
    ) {
        foreach ($_SESSION['elenco_personalizado'] as $j) {
            $nome = trim($j['nome'] ?? '');

            if ($nome !== '') {
                $_SESSION['nomes_participantes_temporada'][] = $nome;
            }
        }
    }

    if (
        !empty($_SESSION['historico_eliminados']) &&
        is_array($_SESSION['historico_eliminados'])
    ) {
        foreach ($_SESSION['historico_eliminados'] as $nome) {
            if (trim((string)$nome) !== '') {
                $_SESSION['nomes_participantes_temporada'][] = (string)$nome;
            }
        }
    }

    $normalizados = [];
    $resultado = [];

    foreach ($_SESSION['nomes_participantes_temporada'] as $nome) {
        $nome = trim((string)$nome);

        if ($nome === '') {
            continue;
        }

        $chave = mb_strtolower($nome, 'UTF-8');

        if (isset($normalizados[$chave])) {
            continue;
        }

        $normalizados[$chave] = true;
        $resultado[] = $nome;
    }

    $_SESSION['nomes_participantes_temporada'] = $resultado;
}


/* =========================================================
   📅 PLANEJAR A RODADA DA CASA DE VIDRO
   Uma vez por temporada, normalmente na Rodada 3 ou 4.
   ========================================================= */
function garantirPlanejamentoCasaVidro($jogadores, $rodada)
{
    registrarNomesTemporadaCasaVidro($jogadores);

    if (!empty($_SESSION['casa_vidro_realizada'])) {
        return;
    }

    if (!isset($_SESSION['casa_vidro_rodada_planejada'])) {
        $rodada = (int)$rodada;

        if ($rodada <= 2) {
            $_SESSION['casa_vidro_rodada_planejada'] = rand(3, 4);
        } elseif ($rodada <= 4) {
            /* Compatibilidade se o recurso for instalado no meio da temporada. */
            $_SESSION['casa_vidro_rodada_planejada'] = $rodada;
        } else {
            /* Em save antigo, tenta encaixar na próxima rodada. */
            $_SESSION['casa_vidro_rodada_planejada'] = $rodada + 1;
        }
    }
}


/* =========================================================
   🎲 DADOS PARA GERAR CANDIDATOS
   ========================================================= */
function opcoesCasaVidro()
{
    require __DIR__ . '/../../data/opcoes_participantes.php';

    return [
        'nomes' => $nomesNPC ?? [],
        'personalidades' => $personalidades ?? [],
        'profissoes' => $profissoes ?? [],
        'estados' => $estados ?? []
    ];
}


/* =========================================================
   ⭐ BÔNUS DE APELO PÚBLICO POR PERSONALIDADE
   Não é "melhor/pior"; apenas simula perfis que tendem
   a gerar mais ou menos atenção numa votação de entrada.
   ========================================================= */
function bonusApeloCasaVidro($personalidade)
{
    $bonus = [
        'Influencer' => 8,
        'Líder Nato' => 6,
        'Barraqueiro' => 5,
        'Explosivo' => 4,
        'Estrategista' => 4,
        'Fofo' => 3,
        'Emocional' => 2,
        'Manipulador' => 1,
        'Neutro' => 0,
        'Falso' => -2,
        'Planta' => -5
    ];

    return (int)($bonus[$personalidade] ?? 0);
}


/* =========================================================
   👤 CRIAR UM CANDIDATO
   ========================================================= */
function criarCandidatoCasaVidro(
    $nome,
    $personalidades,
    $profissoes,
    $estados
) {
    $personalidade = !empty($personalidades)
        ? $personalidades[array_rand($personalidades)]
        : 'Neutro';

    $profissao = !empty($profissoes)
        ? $profissoes[array_rand($profissoes)]
        : 'Estudante';

    $estado = !empty($estados)
        ? $estados[array_rand($estados)]
        : 'SP';

    $popularidadeInicial = rand(44, 62);

    $forcaVoto =
        rand(65, 100) +
        bonusApeloCasaVidro($personalidade) +
        (int)round(($popularidadeInicial - 50) * 0.8);

    return [
        'nome' => $nome,
        'idade' => rand(18, 45),
        'profissao' => $profissao,
        'estado' => $estado,
        'personalidade' => $personalidade,
        'popularidade' => limitar($popularidadeInicial, 0, 100),
        'humor' => rand(50, 70),
        'status' => [
            'lider' => false,
            'anjo' => false,
            'imune' => false,
            'vip' => false,
            'xepa' => true,
            'monstro' => false
        ],
        'relacoes' => [],
        'romances' => [],
        'confessionarios' => [],
        'alianca' => null,
        'historico_aliancas' => [],
        'historico_popularidade' => [],
        'origem' => 'casa_vidro',
        '_casa_vidro_forca_voto' => max(25, $forcaVoto)
    ];
}


/* =========================================================
   👥 GERAR 4 CANDIDATOS ÚNICOS
   ========================================================= */
function gerarCandidatosCasaVidro($jogadores)
{
    if (
        !empty($_SESSION['casa_vidro_candidatos']) &&
        is_array($_SESSION['casa_vidro_candidatos'])
    ) {
        return $_SESSION['casa_vidro_candidatos'];
    }

    $opcoes = opcoesCasaVidro();

    $nomesUsados = [];

    foreach ($_SESSION['nomes_participantes_temporada'] ?? [] as $nome) {
        $nomesUsados[mb_strtolower(trim((string)$nome), 'UTF-8')] = true;
    }

    foreach ($jogadores as $j) {
        $nome = trim($j['nome'] ?? '');

        if ($nome !== '') {
            $nomesUsados[mb_strtolower($nome, 'UTF-8')] = true;
        }
    }

    $nomesDisponiveis = array_values(
        array_filter(
            $opcoes['nomes'],
            function ($nome) use ($nomesUsados) {
                return !isset(
                    $nomesUsados[
                        mb_strtolower(trim((string)$nome), 'UTF-8')
                    ]
                );
            }
        )
    );

    shuffle($nomesDisponiveis);

    $candidatos = [];

    for ($i = 0; $i < 4; $i++) {
        if (!empty($nomesDisponiveis)) {
            $nome = array_shift($nomesDisponiveis);
        } else {
            $numero = $i + 1;
            $nome = 'Candidato ' . $numero;

            while (
                isset(
                    $nomesUsados[
                        mb_strtolower($nome, 'UTF-8')
                    ]
                )
            ) {
                $numero++;
                $nome = 'Candidato ' . $numero;
            }
        }

        $nomesUsados[mb_strtolower($nome, 'UTF-8')] = true;

        $candidatos[] = criarCandidatoCasaVidro(
            $nome,
            $opcoes['personalidades'],
            $opcoes['profissoes'],
            $opcoes['estados']
        );
    }

    $_SESSION['casa_vidro_candidatos'] = $candidatos;

    return $candidatos;
}


/* =========================================================
   🚪 INICIAR A CASA DE VIDRO
   ========================================================= */
function iniciarCasaVidro(&$jogadores, &$fase, $rodada)
{
    if (!empty($_SESSION['casa_vidro_realizada'])) {
        return false;
    }

    if (!empty($_SESSION['casa_vidro_ativa'])) {
        $_SESSION['fase_semana'] = 'casa_vidro';
        $fase = 'casa_vidro';
        return true;
    }

    $_SESSION['casa_vidro_ativa'] = true;
    $_SESSION['casa_vidro_rodada'] = (int)$rodada;
    $_SESSION['casa_vidro_fase_retorno'] = $fase ?: 'queridometro';
    $_SESSION['fase_semana'] = 'casa_vidro';

    gerarCandidatosCasaVidro($jogadores);

    if (
        !isset($_SESSION['evento_extra']) ||
        !is_array($_SESSION['evento_extra'])
    ) {
        $_SESSION['evento_extra'] = [];
    }

    $_SESSION['evento_extra'][] =
        '🏠 A Casa de Vidro foi aberta! Quatro candidatos disputam duas vagas no BBB Simulator.';

    $fase = 'casa_vidro';

    return true;
}


/* =========================================================
   🔄 VERIFICAR SE A CASA DE VIDRO DEVE COMEÇAR
   Chamar no jogo.php logo após o Queridômetro ser preparado.
   ========================================================= */
function verificarInicioCasaVidro(&$jogadores, &$fase, $rodada)
{
    garantirPlanejamentoCasaVidro(
        $jogadores,
        $rodada
    );

    if (!empty($_SESSION['casa_vidro_realizada'])) {
        return false;
    }

    if (!empty($_SESSION['casa_vidro_ativa'])) {
        $_SESSION['fase_semana'] = 'casa_vidro';
        $fase = 'casa_vidro';

        header('Location: casa_vidro.php');
        exit;
    }

    $forcar = !empty($_SESSION['forcar_casa_vidro']);

    if ($forcar) {
        unset($_SESSION['forcar_casa_vidro']);
    }

    $rodadaPlanejada =
        (int)($_SESSION['casa_vidro_rodada_planejada'] ?? 4);

    $fasePermitida = in_array(
        $fase,
        ['queridometro', 'interacoes_1'],
        true
    );

    $deveIniciar = $forcar || (
        (int)$rodada >= $rodadaPlanejada &&
        $fasePermitida &&
        count($jogadores) >= 6
    );

    if (!$deveIniciar) {
        return false;
    }

    iniciarCasaVidro(
        $jogadores,
        $fase,
        $rodada
    );

    header('Location: casa_vidro.php');
    exit;
}


/* =========================================================
   🗳️ CALCULAR VOTAÇÃO DO PÚBLICO
   ========================================================= */
function calcularResultadoCasaVidro()
{
    if (
        !empty($_SESSION['casa_vidro_ranking']) &&
        is_array($_SESSION['casa_vidro_ranking'])
    ) {
        return $_SESSION['casa_vidro_ranking'];
    }

    $candidatos = $_SESSION['casa_vidro_candidatos'] ?? [];

    if (!is_array($candidatos) || count($candidatos) < 2) {
        return [];
    }

    $pesos = [];

    foreach ($candidatos as $candidato) {
        $nome = $candidato['nome'] ?? '';

        if ($nome === '') {
            continue;
        }

        $pesos[$nome] = max(
            1,
            (int)($candidato['_casa_vidro_forca_voto'] ?? rand(50, 100))
        );
    }

    arsort($pesos);

    $total = array_sum($pesos);

    if ($total <= 0) {
        return [];
    }

    $ranking = [];
    $soma = 0.0;
    $nomes = array_keys($pesos);
    $ultimoIndice = count($nomes) - 1;

    foreach ($nomes as $indice => $nome) {
        if ($indice === $ultimoIndice) {
            $pct = round(100 - $soma, 2);
        } else {
            $pct = round(
                ($pesos[$nome] / $total) * 100,
                2
            );
            $soma += $pct;
        }

        $ranking[$nome] = max(0, $pct);
    }

    arsort($ranking);

    $_SESSION['casa_vidro_ranking'] = $ranking;
    $_SESSION['casa_vidro_vencedores'] = array_slice(
        array_keys($ranking),
        0,
        2
    );

    return $ranking;
}


/* =========================================================
   🔎 BUSCAR CANDIDATO
   ========================================================= */
function buscarCandidatoCasaVidro($nome)
{
    foreach ($_SESSION['casa_vidro_candidatos'] ?? [] as $candidato) {
        if (nomeIgual($candidato['nome'] ?? '', $nome)) {
            return $candidato;
        }
    }

    return null;
}


/* =========================================================
   ❤️ CRIAR RELAÇÕES DOS NOVOS PARTICIPANTES
   ========================================================= */
function inicializarRelacoesEntrantesCasaVidro(
    &$jogadores,
    &$entrantes,
    $meuNome
) {
    $nomesEntrantes = array_map(
        function ($j) {
            return $j['nome'] ?? '';
        },
        $entrantes
    );

    /* Relações dos participantes que já estavam na casa com os novos. */
    foreach ($jogadores as &$morador) {
        $nomeMorador = $morador['nome'] ?? '';

        if (!isset($morador['relacoes']) || !is_array($morador['relacoes'])) {
            $morador['relacoes'] = [];
        }

        foreach ($entrantes as $novo) {
            $nomeNovo = $novo['nome'] ?? '';

            if ($nomeNovo === '' || nomeIgual($nomeMorador, $nomeNovo)) {
                continue;
            }

            if (!isset($morador['relacoes'][$nomeNovo])) {
                $morador['relacoes'][$nomeNovo] = [
                    'amizade' => rand(20, 58),
                    'rivalidade' => rand(0, 24),
                    'confianca' => rand(18, 55)
                ];
            }
        }
    }
    unset($morador);

    /* Relações dos novos com moradores antigos e entre eles mesmos. */
    foreach ($entrantes as &$novo) {
        $nomeNovo = $novo['nome'] ?? '';

        if (!isset($novo['relacoes']) || !is_array($novo['relacoes'])) {
            $novo['relacoes'] = [];
        }

        foreach ($jogadores as $morador) {
            $nomeMorador = $morador['nome'] ?? '';

            if ($nomeMorador === '' || nomeIgual($nomeNovo, $nomeMorador)) {
                continue;
            }

            $novo['relacoes'][$nomeMorador] = [
                'amizade' => rand(20, 58),
                'rivalidade' => rand(0, 24),
                'confianca' => rand(18, 55)
            ];
        }

        foreach ($entrantes as $outro) {
            $nomeOutro = $outro['nome'] ?? '';

            if ($nomeOutro === '' || nomeIgual($nomeNovo, $nomeOutro)) {
                continue;
            }

            $novo['relacoes'][$nomeOutro] = [
                'amizade' => rand(28, 65),
                'rivalidade' => rand(0, 18),
                'confianca' => rand(25, 60)
            ];
        }

        if (!nomeIgual($nomeNovo, $meuNome)) {
            if (
                !isset($_SESSION['relacoes_jogador']) ||
                !is_array($_SESSION['relacoes_jogador'])
            ) {
                $_SESSION['relacoes_jogador'] = [];
            }

            if (!isset($_SESSION['relacoes_jogador'][$nomeNovo])) {
                $_SESSION['relacoes_jogador'][$nomeNovo] = 0;
            }
        }
    }
    unset($novo);
}


/* =========================================================
   🚪 COLOCAR OS 2 VENCEDORES NA CASA
   ========================================================= */
function integrarVencedoresCasaVidro(&$jogadores, $meuNome)
{
    $ranking = $_SESSION['casa_vidro_ranking'] ?? [];
    $vencedores = $_SESSION['casa_vidro_vencedores'] ?? [];

    if (
        !is_array($ranking) ||
        !is_array($vencedores) ||
        count($vencedores) < 2
    ) {
        return false;
    }

    $entrantes = [];

    foreach ($vencedores as $nome) {
        $candidato = buscarCandidatoCasaVidro($nome);

        if (!$candidato) {
            continue;
        }

        /* Remove dado interno usado apenas na votação. */
        unset($candidato['_casa_vidro_forca_voto']);

        $pct = (float)($ranking[$nome] ?? 25);
        $popularidadeAntes = (int)($candidato['popularidade'] ?? 50);

        $novaPopularidade = limitar(
            (int)round(
                max(
                    $popularidadeAntes,
                    48 + (($pct - 20) * 0.9)
                )
            ),
            0,
            100
        );

        $candidato['popularidade'] = $novaPopularidade;

        $candidato['historico_popularidade'][] = [
            'rodada' => (int)($_SESSION['rodada'] ?? 1),
            'antes' => $popularidadeAntes,
            'variacao' => $novaPopularidade - $popularidadeAntes,
            'motivo' => 'entrou pela Casa de Vidro',
            'depois' => $novaPopularidade
        ];

        $entrantes[] = $candidato;
    }

    if (count($entrantes) < 2) {
        return false;
    }

    inicializarRelacoesEntrantesCasaVidro(
        $jogadores,
        $entrantes,
        $meuNome
    );

    foreach ($entrantes as $novo) {
        $jogadores[] = $novo;
    }

    removerParticipantesDuplicados(
        $jogadores,
        $meuNome
    );

    garantirEstruturaParticipantes(
        $jogadores
    );

    garantirMeuJogadorNaLista(
        $jogadores
    );

    registrarNomesTemporadaCasaVidro(
        $jogadores
    );

    $_SESSION['jogadores'] = array_values($jogadores);

    $_SESSION['casa_vidro_realizada'] = true;
    $_SESSION['casa_vidro_ativa'] = false;
    $_SESSION['casa_vidro_finalizada_rodada'] = (int)($_SESSION['rodada'] ?? 1);

    $nomesVencedores = array_values(
        array_map(
            function ($j) {
                return $j['nome'] ?? '';
            },
            $entrantes
        )
    );

    $_SESSION['casa_vidro_feed_pendente'] = [
        'vencedores' => $nomesVencedores,
        'ranking' => $ranking,
        'rodada' => (int)($_SESSION['rodada'] ?? 1)
    ];

    if (
        !isset($_SESSION['evento_extra']) ||
        !is_array($_SESSION['evento_extra'])
    ) {
        $_SESSION['evento_extra'] = [];
    }

    $_SESSION['evento_extra'][] =
        '🏠 ' . implode(' e ', $nomesVencedores) .
        ' foram escolhidos pelo público e entraram oficialmente no BBB Simulator pela Casa de Vidro.';

    $faseRetorno =
        $_SESSION['casa_vidro_fase_retorno'] ?? 'queridometro';

    if ($faseRetorno === 'casa_vidro' || $faseRetorno === '') {
        $faseRetorno = 'queridometro';
    }

    $_SESSION['fase_semana'] = $faseRetorno;

    unset(
        $_SESSION['casa_vidro_fase_retorno']
    );

    return true;
}
