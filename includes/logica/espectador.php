<?php

/* =========================================================
   👁️ MODO ESPECTADOR
   O jogador eliminado acompanha a temporada sem voltar
   para o elenco. Cada clique simula uma semana completa.
   ========================================================= */

require_once __DIR__ . '/eliminacao.php';


function nomesImunesEspectador($jogadores)
{
    $nomes = [];

    foreach ($jogadores as $j) {
        if (!empty($j['status']['imune'])) {
            $nomes[] = $j['nome'] ?? '';
        }
    }

    return array_values(
        array_filter($nomes)
    );
}


function buscarIndiceEspectador($jogadores, $nome)
{
    foreach ($jogadores as $i => $j) {
        if (nomeIgual($j['nome'] ?? '', $nome)) {
            return $i;
        }
    }

    return null;
}


function escolherNomeAleatorioEspectador(
    $jogadores,
    $bloqueados = []
) {
    $opcoes = [];

    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';

        if ($nome === '') {
            continue;
        }

        $bloqueado = false;

        foreach ($bloqueados as $b) {
            if (
                $b !== '' &&
                nomeIgual($nome, $b)
            ) {
                $bloqueado = true;
                break;
            }
        }

        if (!$bloqueado) {
            $opcoes[] = $nome;
        }
    }

    if (empty($opcoes)) {
        return '';
    }

    return $opcoes[array_rand($opcoes)];
}


function escolherAlvoEspectador(
    $jogadores,
    $npc,
    $contexto,
    $bloqueados = []
) {
    if (
        function_exists(
            'escolherAlvoNPCInteligente'
        )
    ) {
        $alvo =
            escolherAlvoNPCInteligente(
                $jogadores,
                $npc,
                $contexto,
                $bloqueados
            );

        if (!empty($alvo)) {
            return $alvo;
        }
    }

    return escolherNomeAleatorioEspectador(
        $jogadores,
        array_values(
            array_unique(
                array_merge(
                    $bloqueados,
                    [$npc]
                )
            )
        )
    );
}


function escolherVencedorCargoEspectador(
    $jogadores,
    $cargo,
    $bloqueados = []
) {
    $ranking = [];

    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';

        if ($nome === '') {
            continue;
        }

        $ignorar = false;

        foreach ($bloqueados as $b) {
            if (
                $b !== '' &&
                nomeIgual($nome, $b)
            ) {
                $ignorar = true;
                break;
            }
        }

        if ($ignorar) {
            continue;
        }

        $personalidade =
            $j['personalidade']
            ?? 'Neutro';

        $score = rand(25, 100);

        if ($cargo === 'lider') {
            if ($personalidade === 'Líder Nato') {
                $score += 18;
            }

            if ($personalidade === 'Estrategista') {
                $score += 10;
            }

            if ($personalidade === 'Competitivo') {
                $score += 8;
            }
        }

        if ($cargo === 'anjo') {
            if (
                $personalidade === 'Fofo' ||
                $personalidade === 'Emocional'
            ) {
                $score += 8;
            }

            if ($personalidade === 'Estrategista') {
                $score += 6;
            }
        }

        $ranking[$nome] = $score;
    }

    if (empty($ranking)) {
        return '';
    }

    arsort($ranking);

    return array_key_first($ranking);
}


function limparSemanaEspectador(&$jogadores)
{
    $chaves = [
        'paredao',
        'eliminado',
        'ultimo_ranking_eliminacao',
        'paredao_formado',
        'votos_paredao',
        'dedo_duro',
        'indicacao_lider',
        'indicacao_bigfone',
        'meu_voto_paredao',
        'lider',
        'anjo',
        'imune',
        'monstro',
        'vip_definido',
        'monstro_definido',
        'imunizacao_anjo_feita',
        'bigfone',
        'bigfone_feito',
        'bigfone_tocou',
        'bigfone_aconteceu',
        'bigfone_indicacao_pendente',
        'bigfone_dono_poder',
        'bigfone_anular_voto_pendente',
        'bigfone_espiar_voto_pendente',
        'bigfone_troca_emparedado_pendente',
        'bigfone_contragolpe_pendente',
        'poder_curinga',
        'curinga_decidido_rodada',
        'curinga_voto_duplo_ativo',
        'curinga_anular_voto_de',
        'curinga_espiar_voto_de',
        'curinga_contra_golpe_usado',
        'curinga_troca_usada',
        'imunidade_curinga',
        'queridometro_feito',
        'queridometro_resultado',
        'npc_festa_feita',
        'acao_festa_selecionada',
        'confessionario_falas',
        'confessionario_feito',
        'discordia_feito',
        'tema_discordia',
        'bate_volta',
        'bate_volta_decidido',
        'bate_volta_resultado'
    ];

    foreach ($chaves as $chave) {
        unset($_SESSION[$chave]);
    }

    foreach ($jogadores as &$j) {
        if (
            !isset($j['status']) ||
            !is_array($j['status'])
        ) {
            $j['status'] = [];
        }

        $j['status']['lider'] = false;
        $j['status']['anjo'] = false;
        $j['status']['imune'] = false;
        $j['status']['vip'] = false;
        $j['status']['xepa'] = false;
        $j['status']['monstro'] = false;
    }

    unset($j);
}


function integrarCasaVidroNoEspectador(
    &$jogadores,
    $meuNome,
    &$resumo
) {
    $rodada = (int)($_SESSION['rodada'] ?? 1);

    /*
     * Se a Casa de Vidro já havia sido anunciada antes da
     * eliminação do jogador, o resultado continua existindo.
     */
    if (
        $rodada >= 4 &&
        !empty($_SESSION['casa_vidro_anunciada']) &&
        empty($_SESSION['casa_vidro_realizada']) &&
        function_exists('calcularResultadoCasaVidro') &&
        function_exists('integrarVencedoresCasaVidro')
    ) {
        calcularResultadoCasaVidro();

        $vencedores =
            $_SESSION['casa_vidro_vencedores']
            ?? [];

        if (
            integrarVencedoresCasaVidro(
                $jogadores,
                $meuNome
            )
        ) {
            $resumo[] =
                "🏠 Casa de Vidro: " .
                implode(' e ', $vencedores) .
                " entraram na casa.";
        }
    }
}


function simularRodadaEspectador(
    &$jogadores,
    $meuNome
) {
    $jogadores = array_values($jogadores);

    if (count($jogadores) <= 3) {
        $_SESSION['fase_semana'] = 'finalistas';
        $_SESSION['jogadores'] = $jogadores;
        return;
    }

    $_SESSION['rodada'] =
        (int)($_SESSION['rodada'] ?? 1) + 1;

    $rodada =
        (int)$_SESSION['rodada'];

    limparSemanaEspectador($jogadores);

    $resumo = [
        "📺 Começou a Rodada $rodada."
    ];

    integrarCasaVidroNoEspectador(
        $jogadores,
        $meuNome,
        $resumo
    );

    /* =====================================================
       👑 LÍDER
       ===================================================== */
    $lider =
        escolherVencedorCargoEspectador(
            $jogadores,
            'lider'
        );

    if ($lider !== '') {
        $_SESSION['lider'] = $lider;

        $idx = buscarIndiceEspectador(
            $jogadores,
            $lider
        );

        if ($idx !== null) {
            $jogadores[$idx]['status']['lider'] = true;

            if (
                !isset($jogadores[$idx]['estatisticas']) ||
                !is_array($jogadores[$idx]['estatisticas'])
            ) {
                $jogadores[$idx]['estatisticas'] = [];
            }

            $jogadores[$idx]['estatisticas']['lider'] =
                ($jogadores[$idx]['estatisticas']['lider'] ?? 0) + 1;
        }

        $resumo[] =
            "👑 $lider venceu a Prova do Líder.";
    }

    /* =====================================================
       🟡 VIP / XEPA
       ===================================================== */
    $qtdVip =
        function_exists('calcularQtdVIP')
            ? calcularQtdVIP(count($jogadores))
            : max(1, min(4, count($jogadores) - 1));

    $vip = [];

    if (
        function_exists(
            'escolherVariosAlvosNPCInteligentes'
        ) &&
        $lider !== ''
    ) {
        $vip =
            escolherVariosAlvosNPCInteligentes(
                $jogadores,
                $lider,
                'vip',
                $qtdVip,
                [$lider]
            );
    }

    if (empty($vip)) {
        $nomes = array_values(
            array_filter(
                array_map(
                    fn($j) => $j['nome'] ?? '',
                    $jogadores
                ),
                fn($n) => $n !== '' && !nomeIgual($n, $lider)
            )
        );

        shuffle($nomes);
        $vip = array_slice($nomes, 0, $qtdVip);
    }

    foreach ($jogadores as &$j) {
        $nome = $j['nome'] ?? '';

        $ehVip =
            nomeIgual($nome, $lider) ||
            in_array($nome, $vip, true);

        $j['status']['vip'] = $ehVip;
        $j['status']['xepa'] = !$ehVip;

        if (
            !isset($j['estatisticas']) ||
            !is_array($j['estatisticas'])
        ) {
            $j['estatisticas'] = [];
        }

        if ($ehVip) {
            $j['estatisticas']['vip'] =
                ($j['estatisticas']['vip'] ?? 0) + 1;
        } else {
            $j['estatisticas']['xepa'] =
                ($j['estatisticas']['xepa'] ?? 0) + 1;
        }
    }
    unset($j);

    /* =====================================================
       😇 ANJO
       ===================================================== */
    $anjo =
        escolherVencedorCargoEspectador(
            $jogadores,
            'anjo',
            [$lider]
        );

    if ($anjo !== '') {
        $_SESSION['anjo'] = $anjo;

        $idx = buscarIndiceEspectador(
            $jogadores,
            $anjo
        );

        if ($idx !== null) {
            $jogadores[$idx]['status']['anjo'] = true;

            if (
                !isset($jogadores[$idx]['estatisticas']) ||
                !is_array($jogadores[$idx]['estatisticas'])
            ) {
                $jogadores[$idx]['estatisticas'] = [];
            }

            $jogadores[$idx]['estatisticas']['anjo'] =
                ($jogadores[$idx]['estatisticas']['anjo'] ?? 0) + 1;
        }

        $resumo[] =
            "😇 $anjo conquistou o Anjo.";
    }

    /* =====================================================
       🛡️ IMUNIDADE
       ===================================================== */
    $autoImune =
        count($jogadores) <= 10
            ? true
            : rand(1, 100) <= 30;

    if ($autoImune && $anjo !== '') {
        $imunizado = $anjo;

    } else {
        $imunizado =
            escolherAlvoEspectador(
                $jogadores,
                $anjo,
                'imunidade',
                [$lider]
            );
    }

    if ($imunizado !== '') {
        $_SESSION['imune'] = $imunizado;

        $idx = buscarIndiceEspectador(
            $jogadores,
            $imunizado
        );

        if ($idx !== null) {
            $jogadores[$idx]['status']['imune'] = true;

            if (
                !isset($jogadores[$idx]['estatisticas']) ||
                !is_array($jogadores[$idx]['estatisticas'])
            ) {
                $jogadores[$idx]['estatisticas'] = [];
            }

            $jogadores[$idx]['estatisticas']['imune'] =
                ($jogadores[$idx]['estatisticas']['imune'] ?? 0) + 1;
        }

        $resumo[] =
            $autoImune && nomeIgual($imunizado, $anjo)
                ? "🛡️ O Anjo $anjo ficou autoimune."
                : "🛡️ $anjo imunizou $imunizado.";
    }

    /* =====================================================
       👹 MONSTRO
       ===================================================== */
    $monstros = [];

    if (
        function_exists(
            'escolherVariosAlvosNPCInteligentes'
        ) &&
        $anjo !== ''
    ) {
        $monstros =
            escolherVariosAlvosNPCInteligentes(
                $jogadores,
                $anjo,
                'monstro',
                min(2, max(1, count($jogadores) - 1)),
                [$anjo]
            );
    }

    $monstros = array_values(
        array_unique(
            array_filter($monstros)
        )
    );

    foreach ($jogadores as &$j) {
        if (
            in_array(
                $j['nome'] ?? '',
                $monstros,
                true
            )
        ) {
            $j['status']['monstro'] = true;

            if (
                !isset($j['estatisticas']) ||
                !is_array($j['estatisticas'])
            ) {
                $j['estatisticas'] = [];
            }

            $j['estatisticas']['monstro'] =
                ($j['estatisticas']['monstro'] ?? 0) + 1;
        }
    }
    unset($j);

    if (!empty($monstros)) {
        $resumo[] =
            "👹 Monstro: " .
            implode(' e ', $monstros) .
            ".";
    }

    /* =====================================================
       💬 CONVIVÊNCIA DOS NPCs
       ===================================================== */
    if (function_exists('gerarAcoesNPC')) {
        $eventosInteracao =
            gerarAcoesNPC(
                $jogadores,
                $meuNome,
                1
            );

        foreach (
            array_slice(
                $eventosInteracao,
                0,
                5
            )
            as $ev
        ) {
            $resumo[] = strip_tags($ev);
        }
    }

    if (
        function_exists(
            'atualizarAliancasAutomaticas'
        )
    ) {
        $eventosAlianca =
            atualizarAliancasAutomaticas(
                $jogadores,
                $meuNome
            );

        foreach (
            array_slice(
                $eventosAlianca,
                0,
                2
            )
            as $ev
        ) {
            $resumo[] = strip_tags($ev);
        }
    }

    /* =====================================================
       ☎️ BIG FONE
       ===================================================== */
    if (
        function_exists('prepararBigFoneDaRodada') &&
        function_exists('npcAtendeBigFone') &&
        function_exists('finalizarAtendimentoBigFone')
    ) {
        $estadoBig =
            prepararBigFoneDaRodada();

        if ($estadoBig === 'tocou') {
            $atendente =
                npcAtendeBigFone(
                    $jogadores,
                    '__ESPECTADOR__'
                );

            finalizarAtendimentoBigFone(
                $jogadores,
                $atendente,
                true
            );

            $resumo[] =
                "☎️ O Big Fone tocou e $atendente atendeu.";
        }
    }

    /* =====================================================
       🚨 INDICAÇÃO DO LÍDER
       ===================================================== */
    $bloqueadosLider =
        array_merge(
            [$lider],
            nomesImunesEspectador($jogadores)
        );

    $indicacaoLider =
        escolherAlvoEspectador(
            $jogadores,
            $lider,
            'indicacao_lider',
            $bloqueadosLider
        );

    $_SESSION['indicacao_lider'] =
        $indicacaoLider;

    if ($indicacaoLider !== '') {
        $resumo[] =
            "🚨 O Líder $lider indicou $indicacaoLider ao Paredão.";
    }

    /*
     * Se o Big Fone deixou uma indicação pendente para NPC,
     * resolve automaticamente.
     */
    if (
        !empty($_SESSION['bigfone_indicacao_pendente']) &&
        !empty($_SESSION['bigfone_dono_poder'])
    ) {
        $donoBig =
            $_SESSION['bigfone_dono_poder'];

        $indicacaoBig =
            escolherAlvoEspectador(
                $jogadores,
                $donoBig,
                'indicacao_bigfone',
                array_merge(
                    [
                        $lider,
                        $indicacaoLider
                    ],
                    nomesImunesEspectador($jogadores)
                )
            );

        if ($indicacaoBig !== '') {
            $_SESSION['indicacao_bigfone'] =
                $indicacaoBig;

            $resumo[] =
                "☎️ Pelo Big Fone, $donoBig colocou $indicacaoBig no Paredão.";
        }

        unset(
            $_SESSION['bigfone_indicacao_pendente']
        );
    }

    /* =====================================================
       🗳️ VOTAÇÃO DA CASA
       ===================================================== */
    $votos = [];

    foreach ($jogadores as $votante) {
        $nomeVotante =
            $votante['nome'] ?? '';

        if (
            $nomeVotante === '' ||
            nomeIgual($nomeVotante, $lider)
        ) {
            continue;
        }

        $bloqueados = array_merge(
            [
                $nomeVotante,
                $lider,
                $indicacaoLider,
                $_SESSION['indicacao_bigfone'] ?? ''
            ],
            nomesImunesEspectador($jogadores)
        );

        $voto =
            escolherAlvoEspectador(
                $jogadores,
                $nomeVotante,
                'voto',
                $bloqueados
            );

        if ($voto === '') {
            continue;
        }

        $peso = 1;

        if (
            !empty($_SESSION['curinga_voto_duplo_ativo']) &&
            nomeIgual(
                $_SESSION['curinga_voto_duplo_ativo'],
                $nomeVotante
            )
        ) {
            $peso = 2;
        }

        $votos[$voto] =
            ($votos[$voto] ?? 0) + $peso;
    }

    arsort($votos);

    $paredao = [];

    if ($indicacaoLider !== '') {
        $paredao[] = $indicacaoLider;
    }

    if (!empty($_SESSION['indicacao_bigfone'])) {
        $paredao[] =
            $_SESSION['indicacao_bigfone'];
    }

    $limite =
        function_exists('definirTamanhoParedao')
            ? definirTamanhoParedao($jogadores)
            : 3;

    foreach (array_keys($votos) as $nome) {
        if (count($paredao) >= $limite) {
            break;
        }

        if (!in_array($nome, $paredao, true)) {
            $paredao[] = $nome;
        }
    }

    /*
     * Segurança se a votação tiver poucos candidatos válidos.
     */
    if (count($paredao) < 2) {
        foreach ($jogadores as $j) {
            $nome = $j['nome'] ?? '';

            if (
                $nome === '' ||
                nomeIgual($nome, $lider) ||
                estaImune($jogadores, $nome) ||
                in_array($nome, $paredao, true)
            ) {
                continue;
            }

            $paredao[] = $nome;

            if (count($paredao) >= 3) {
                break;
            }
        }
    }

    $paredao = array_values(
        array_unique($paredao)
    );

    /* =====================================================
       🚗 BATE-VOLTA SIMPLIFICADO
       ===================================================== */
    if (
        count($paredao) >= 3 &&
        count($jogadores) > 4 &&
        rand(1, 100) <= 50
    ) {
        $elegiveis = array_values(
            array_filter(
                $paredao,
                function ($nome) use ($indicacaoLider) {
                    return !nomeIgual(
                        $nome,
                        $indicacaoLider
                    );
                }
            )
        );

        if (!empty($elegiveis)) {
            $salvo =
                $elegiveis[
                    array_rand($elegiveis)
                ];

            $paredao = array_values(
                array_filter(
                    $paredao,
                    fn($n) => !nomeIgual($n, $salvo)
                )
            );

            $resumo[] =
                "🚗 $salvo venceu o Bate-Volta e escapou.";
        }
    }

    $_SESSION['paredao'] =
        array_values($paredao);

    $resumo[] =
        "🗳️ Paredão: " .
        implode(' x ', $paredao) .
        ".";

    /* =====================================================
       🔥 DISCÓRDIA
       ===================================================== */
    if (
        function_exists('temasDiscordia') &&
        function_exists('gerarDiscordiaNPC')
    ) {
        $temas = temasDiscordia();

        $tema =
            $temas[
                array_rand($temas)
            ];

        $eventosDiscordia =
            gerarDiscordiaNPC(
                $jogadores,
                $meuNome,
                $tema
            );

        foreach (
            array_slice(
                $eventosDiscordia,
                0,
                3
            )
            as $ev
        ) {
            $resumo[] = strip_tags($ev);
        }
    }

    /* =====================================================
       📊 ELIMINAÇÃO
       ===================================================== */
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
        $_SESSION['resumo_espectador_ultima_rodada'] =
            $resumo;

        $_SESSION['jogadores'] =
            array_values($jogadores);

        $_SESSION['fase_semana'] =
            'espectador';

        return;
    }

    $eliminado =
        array_key_first($ranking);

    ajustarPopularidadePosParedaoResultado(
        $jogadores,
        $ranking,
        $eliminado
    );

    foreach ($jogadores as $i => $j) {
        if (
            nomeIgual(
                $j['nome'] ?? '',
                $eliminado
            )
        ) {
            unset($jogadores[$i]);
            break;
        }
    }

    $jogadores =
        array_values($jogadores);

    if (
        !isset($_SESSION['historico_eliminados']) ||
        !is_array($_SESSION['historico_eliminados'])
    ) {
        $_SESSION['historico_eliminados'] = [];
    }

    $_SESSION['historico_eliminados'][] =
        $eliminado;

    $_SESSION['historico_eliminados'] =
        array_values(
            array_unique(
                $_SESSION['historico_eliminados']
            )
        );

    $_SESSION['ultimo_eliminado_espectador'] =
        $eliminado;

    $_SESSION['ultimo_ranking_espectador'] =
        $ranking;

    $resumo[] =
        "❌ $eliminado foi eliminado da temporada.";

    $_SESSION['resumo_espectador_ultima_rodada'] =
        $resumo;

    $_SESSION['jogadores'] =
        $jogadores;

    $_SESSION['eliminado'] =
        $eliminado;

    if (count($jogadores) <= 3) {
        $_SESSION['fase_semana'] =
            'finalistas';
    } else {
        $_SESSION['fase_semana'] =
            'espectador';
    }
}
