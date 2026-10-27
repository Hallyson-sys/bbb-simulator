<?php
/**
 * 📊 Raio-X da Semana + Reputação Dinâmica
 *
 * SOMENTE eventos públicos registrados no Histórico da Temporada.
 * Nunca acessa popularidade, ranking de eliminação, segredos, memória privada
 * dos NPCs, votos confidenciais ou informações do quarto secreto não reveladas.
 * Os cards não estimam apoio do público nem influenciam as regras do jogo.
 * Derivado sob demanda: funciona também em saves anteriores.
 */

function rxTiposPublicos(): array
{
    return [
        'lider', 'anjo', 'vip_xepa', 'monstro', 'imunidade',
        'paredao', 'eliminacao', 'retorno_paredao_falso',
        'treta', 'romance_oficial', 'big_fone', 'entrada_especial'
    ];
}

function rxEventosPublicos(array $historico, ?int $ateSemana = null, ?int $apenasSemana = null): array
{
    $tipos = rxTiposPublicos();
    $vistos = [];
    $resposta = [];
    foreach ($historico as $idx => $evento) {
        if (!is_array($evento)) continue;
        $tipo = (string)($evento['tipo'] ?? '');
        $rodada = max(1, (int)($evento['rodada'] ?? 1));
        if (!in_array($tipo, $tipos, true)) continue;
        if ($ateSemana !== null && $rodada > $ateRodada) continue;
        if ($apenasSemana !== null && $rodada !== $apenasRodada) continue;
        $chave = (string)($evento['chave'] ?? '');
        if ($chave !== '' && isset($vistos[$chave])) continue;
        if ($chave !== '') $vistos[$chave] = true;
        $evento['_posicao'] = $idx;
        $resposta[] = $evento;
    }
    usort($resposta, static function ($a, $b) {
        $rod = (int)($a['rodada'] ?? 1) <=> (int)($b['rodada'] ?? 1);
        return $rod ?: (int)($a['ordem'] ?? $a['_posicao']) <=> (int)($b['ordem'] ?? $b['_posicao']);
    });
    return $resposta;
}

function rxNomes(array $evento): array
{
    return array_values(array_unique(array_filter(array_map(
        static fn($nome) => trim((string)$nome),
        (array)($evento['participantes'] ?? [])
    ), static fn($nome) => $nome !== '')));
}

function rxEventoApresentacao(array $evento): array
{
    $tipo = (string)($evento['tipo'] ?? '');
    $nomes = rxNomes($evento);
    $p = $nomes[0] ?? 'Um participante';
    $q = $nomes[1] ?? 'outro participante';
    // Texto gerado a partir do evento PÚBLICO, e não de campos com possíveis spoilers.
    switch ($tipo) {
        case 'lider': return ['👑', 'Nova liderança', "$p venceu a Prova do Líder."];
        case 'anjo': return ['😇', 'Prova do Anjo', "$p venceu a Prova do Anjo."];
        case 'vip_xepa': return ['🍽️', 'VIP e Xepa', 'A nova divisão da casa foi definida.'];
        case 'monstro': return ['👹', 'Castigo do Monstro', "$p definiu o Castigo do Monstro da semana."];
        case 'imunidade': return ['🛡️', 'Imunidade', "$p concedeu imunidade a $q."];
        case 'paredao': return ['🔥', 'Paredão formado', 'Nomes definidos: ' . implode(', ', $nomes) . '.'];
        case 'eliminacao': return ['🚪', 'Eliminação', "$p deixou a casa."];
        case 'retorno_paredao_falso': return ['🎭', 'Retorno inesperado', "$p retornou à casa e a dinâmica secreta foi revelada."];
        case 'treta': return ['💥', 'Clima tenso', "$p e $q se envolveram em uma discussão."];
        case 'romance_oficial': return ['💕', 'Romance na casa', "$p e $q assumiram um romance."];
        case 'big_fone': return ['☎️', 'Big Fone', "$p atendeu ao Big Fone. Os detalhes do poder permanecem reservados."];
        case 'entrada_especial': return ['🏠', 'Novos participantes', implode(' e ', $nomes) . ' entraram na casa.'];
    }
    return ['📌', 'Acontecimento', ''];
}

function rxClimaDaRodada(array $eventos): array
{
    $contagem = array_count_values(array_map(static fn($e) => (string)($e['tipo'] ?? ''), $eventos));
    $treta = (int)($contagem['treta'] ?? 0);
    $paredao = (int)($contagem['paredao'] ?? 0);
    $romance = (int)($contagem['romance_oficial'] ?? 0);
    $provas = (int)($contagem['lider'] ?? 0) + (int)($contagem['anjo'] ?? 0);
    if ($treta > 0) return ['💥', 'Convivência agitada', 'Discussões marcaram a convivência nesta rodada.'];
    if ($paredao > 0) return ['🔥', 'Semana de decisões', 'A formação do Paredão movimentou a casa.'];
    if ($romance > 0) return ['💕', 'Clima de romance', 'O lado afetivo também virou assunto na casa.'];
    if ($provas > 0) return ['🏆', 'Disputa por poder', 'Provas e conquistas definiram novos papéis no jogo.'];
    if ($eventos) return ['📺', 'Casa em movimento', 'A rodada teve acontecimentos registrados na temporada.'];
    return ['🌙', 'A semana está começando', 'Os destaques aparecem conforme a rodada avança.'];
}

/** Base factual e cumulativa, nunca o valor de popularidade. */
function rxReputacoes(array $eventos, int $ateRodada): array
{
    $publicos = rxEventosPublicos($eventos, $ateRodada);
    $estatisticas = [];
    foreach ($publicos as $evento) {
        $tipo = (string)($evento['tipo'] ?? '');
        $nomes = rxNomes($evento);
        foreach ($nomes as $indice => $nome) {
            if (!isset($estatisticas[$nome])) {
                $estatisticas[$nome] = [
                    'nome' => $nome, 'lider' => 0, 'anjo' => 0, 'paredao' => 0,
                    'treta' => 0, 'romance' => 0, 'bigfone' => 0,
                    'imunizou' => 0, 'imunizado' => 0, 'monstro' => 0, 'retornou' => 0, 'tipos' => []
                ];
            }
            $s =& $estatisticas[$nome];
            $s['tipos'][$tipo] = true;
            if ($tipo === 'lider' && $indice === 0) ++$s['lider'];
            if ($tipo === 'anjo' && $indice === 0) ++$s['anjo'];
            if ($tipo === 'paredao') ++$s['paredao'];
            if ($tipo === 'treta') ++$s['treta'];
            if ($tipo === 'romance_oficial') ++$s['romance'];
            if ($tipo === 'big_fone' && $indice === 0) ++$s['bigfone'];
            if ($tipo === 'imunidade' && $indice === 0) ++$s['imunizou'];
            if ($tipo === 'imunidade' && $indice === 1) ++$s['imunizado'];
            if ($tipo === 'monstro' && $indice > 0) ++$s['monstro'];
            if ($tipo === 'retorno_paredao_falso' && $indice === 0) ++$s['retornou'];
            unset($s);
        }
    }
    $resultados = [];
    foreach ($estatisticas as $nome => $s) {
        $rotulos = [];
        $vitorias = $s['lider'] + $s['anjo'];
        if ($vitorias >= 2) $rotulos[] = ['🏆', 'Destaque nas provas', "Venceu $vitorias provas de Líder/Anjo até a Semana $ateRodada."];
        elseif ($vitorias === 1) $rotulos[] = ['🏅', 'Conquistou poder', 'Já venceu uma prova de Líder ou Anjo.'];
        if ($s['treta'] >= 1) $rotulos[] = ['💥', 'No centro das tretas', 'Participou de discussões registradas na casa.'];
        if ($s['paredao'] >= 2) $rotulos[] = ['🔥', 'Passagem pelos Paredões', 'Esteve em pelo menos dois Paredões.'];
        elseif ($s['paredao'] === 1) $rotulos[] = ['🔥', 'Enfrentou o Paredão', 'Já esteve em um Paredão da temporada.'];
        if ($s['romance'] >= 1) $rotulos[] = ['💕', 'Romance em destaque', 'Viveu um romance assumido na casa.'];
        if ($s['retornou'] >= 1) $rotulos[] = ['🎭', 'Retorno surpreendente', 'Participou de uma reviravolta já revelada.'];
        if ($s['imunizou'] >= 1) $rotulos[] = ['🛡️', 'Proteção no jogo', 'Usou o poder de imunidade do Anjo.'];
        if ($s['imunizado'] >= 1) $rotulos[] = ['🛡️', 'Recebeu proteção', 'Já foi imunizado por um Anjo.'];
        if ($s['monstro'] >= 1) $rotulos[] = ['👹', 'Enfrentou o Monstro', 'Já cumpriu o Castigo do Monstro.'];
        if ($s['bigfone'] >= 1) $rotulos[] = ['☎️', 'Ligação marcante', 'Atendeu ao Big Fone.'];
        if (!$rotulos) continue;
        $resultados[$nome] = [
            'nome' => $nome,
            'rotulos' => array_slice($rotulos, 0, 2)
        ];
    }
    uksort($resultados, 'strnatcasecmp'); // ordem alfabética, NUNCA ranking
    return array_values($resultados);
}

function rxResumoSemanal(array $historico, int $rodada): array
{
    $eventos = rxEventosPublicos($historico, $rodada, $rodada);
    $clima = rxClimaDaRodada($eventos);
    $envolvidos = [];
    foreach ($eventos as $evento) {
        foreach (rxNomes($evento) as $nome) $envolvidos[$nome] = true;
    }
    $vt = array_map(static function ($evento) {
        [$icone, $titulo, $texto] = rxEventoApresentacao($evento);
        return ['icone' => $icone, 'titulo' => $titulo, 'texto' => $texto];
    }, array_slice(array_reverse($eventos), 0, 5));
    return [
        'rodada' => $rodada,
        'total' => count($eventos),
        'envolvidos' => count($envolvidos),
        'clima' => $clima,
        'vt' => $vt,
        'reputacoes' => rxReputacoes($historico, $rodada)
    ];
}
