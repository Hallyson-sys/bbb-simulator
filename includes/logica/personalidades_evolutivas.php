<?php
/**
 * Convivência 3.0: cinco traços móveis por NPC. A personalidade de origem não
 * muda: ela é a referência para as oscilações. Os dados vivem na sessão/save.
 * Não consultar popularidade e não publicar fatos privados no Ao Vivo.
 */

function e3Limitar($valor, $min = 12, $max = 88): int {
    return max($min, min($max, (int)round($valor)));
}

function e3Bases(string $personalidade): array {
    $bases = [
        'Estrategista' => [59, 31, 57, 77, 51],
        'Explosivo' => [57, 78, 43, 35, 63],
        'Planta' => [34, 24, 54, 36, 32],
        'Manipulador' => [66, 42, 28, 79, 63],
        'Emocional' => [43, 63, 70, 37, 68],
        'Barraqueiro' => [65, 82, 34, 37, 76],
        'Fofo' => [49, 25, 81, 38, 75],
        'Líder Nato' => [77, 47, 68, 68, 75],
        'Influencer' => [70, 48, 46, 56, 82],
        'Falso' => [61, 40, 24, 76, 61],
        'Neutro' => [50, 50, 50, 50, 50],
    ];
    return array_combine(
        ['confianca','reatividade','lealdade','estrategia','sociabilidade'],
        $bases[$personalidade] ?? $bases['Neutro']
    );
}

function e3EncontrarNPC(string $nome): ?array {
    foreach ((array)($_SESSION['jogadores'] ?? []) as $j) {
        if ((string)($j['nome'] ?? '') === $nome) return $j;
    }
    return null;
}

function e3GarantirNPC(string $nome, string $personalidade = 'Neutro'): array {
    if (!isset($_SESSION['e3_personalidades']) || !is_array($_SESSION['e3_personalidades'])) {
        $_SESSION['e3_personalidades'] = [];
    }
    $base = e3Bases($personalidade);
    if (!isset($_SESSION['e3_personalidades'][$nome]) || !is_array($_SESSION['e3_personalidades'][$nome])) {
        $_SESSION['e3_personalidades'][$nome] = [
            'original' => $personalidade,
            'tracos' => $base,
            'semana_atualizada' => (int)($_SESSION['rodada'] ?? 1),
            'limites_eventos' => [],
            'historico' => [],
            'ultima_virada_publica' => 0,
            'estatisticas_observadas' => null,
        ];
    }
    foreach ($base as $traco => $valor) {
        if (!isset($_SESSION['e3_personalidades'][$nome]['tracos'][$traco])) {
            $_SESSION['e3_personalidades'][$nome]['tracos'][$traco] = $valor;
        }
    }
    return $_SESSION['e3_personalidades'][$nome]['tracos'];
}

function e3Tracos(string $nome): array {
    $j = e3EncontrarNPC($nome);
    if (!$j || $nome === (string)($_SESSION['meu_nome'] ?? '')) return e3Bases('Neutro');
    return e3GarantirNPC($nome, (string)($j['personalidade'] ?? 'Neutro'));
}

/** Aplica efeitos sem publicá-los automaticamente. Limite semanal por tipo. */
function e3Mudanca(string $nome, array $deltas, string $origem, bool $publico = false): void {
    $j = e3EncontrarNPC($nome);
    if (!$j || $nome === (string)($_SESSION['meu_nome'] ?? '')) return;
    e3GarantirNPC($nome, (string)($j['personalidade'] ?? 'Neutro'));
    $reg =& $_SESSION['e3_personalidades'][$nome];
    $semana = (int)($_SESSION['rodada'] ?? 1);
    $chave = $semana . '|' . $origem;
    $vezes = (int)($reg['limites_eventos'][$chave] ?? 0);
    if ($vezes >= 2) { unset($reg); return; }
    $reg['limites_eventos'][$chave] = $vezes + 1;
    $antes = $reg['tracos'];
    foreach ($deltas as $traco => $delta) {
        if (isset($reg['tracos'][$traco])) {
            $reg['tracos'][$traco] = e3Limitar($reg['tracos'][$traco] + $delta);
        }
    }
    $reg['historico'][] = ['semana' => $semana, 'origem' => $origem, 'tracos' => $reg['tracos']];
    $reg['historico'] = array_slice($reg['historico'], -18);
    // Viradas públicas só a partir de algo presenciado por toda a casa.
    // Não publicar memória de voto, fofoca, segredo ou promessa privada.
    if ($publico && $semana - (int)$reg['ultima_virada_publica'] >= 2) {
        foreach (['reatividade' => 'mais reativo nas discussões',
                  'confianca' => 'mais seguro nas próprias decisões',
                  'sociabilidade' => 'mais aberto à convivência'] as $traco => $texto) {
            if (($antes[$traco] ?? 50) < 65 && $reg['tracos'][$traco] >= 65) {
                if (!isset($_SESSION['evento_extra']) || !is_array($_SESSION['evento_extra'])) $_SESSION['evento_extra'] = [];
                $_SESSION['evento_extra'][] = '🔄 Mudança de postura: ' . $nome . ' parece ' . $texto . ' depois dos últimos acontecimentos.';
                $reg['ultima_virada_publica'] = $semana;
                $reg['virada_texto'] = 'Parece ' . $texto . '.';
                break;
            }
        }
    }
    unset($reg);
}

/** Memória é subjetiva: só evolui quem viveu/guardou o acontecimento. */
function e3RegistrarMemoria(string $observador, string $tipo, int $forca = 1): void {
    $efeitos = [
        'me_indicou' => ['confianca'=>-3,'reatividade'=>5,'lealdade'=>-5,'estrategia'=>3],
        'me_colocou_paredao' => ['confianca'=>-3,'reatividade'=>5,'lealdade'=>-4],
        'me_puxou_contragolpe' => ['reatividade'=>6,'lealdade'=>-5,'estrategia'=>3],
        'me_colocou_monstro' => ['confianca'=>-2,'reatividade'=>4],
        'me_atacou_discordia' => ['reatividade'=>5,'sociabilidade'=>-2],
        'brigou_comigo' => ['reatividade'=>5,'sociabilidade'=>-3],
        'rompeu_comigo' => ['confianca'=>-5,'lealdade'=>-7,'estrategia'=>3],
        'quebrou_tregua' => ['lealdade'=>-6,'reatividade'=>5],
        'espalhou_fofoca' => ['confianca'=>-4,'lealdade'=>-4,'estrategia'=>3],
        'me_confrontou' => ['reatividade'=>3,'sociabilidade'=>-2],
        'me_expos' => ['reatividade'=>5,'confianca'=>-4],
        'me_desrespeitou' => ['reatividade'=>5,'sociabilidade'=>-3],
        'me_imunizou' => ['confianca'=>3,'lealdade'=>6,'sociabilidade'=>2],
        'me_salvou' => ['lealdade'=>6,'confianca'=>4],
        'me_defendeu' => ['lealdade'=>5,'confianca'=>3],
        'me_colocou_vip' => ['lealdade'=>3,'confianca'=>2],
        'me_elogiou_discordia' => ['confianca'=>3,'sociabilidade'=>3],
        'me_apoiou' => ['confianca'=>3,'sociabilidade'=>3,'lealdade'=>3],
        'me_contou_segredo' => ['lealdade'=>4,'estrategia'=>2],
        'fechou_pacto_comigo' => ['lealdade'=>5,'estrategia'=>2],
        'cumpriu_tregua' => ['lealdade'=>4,'reatividade'=>-3],
        'me_pediu_desculpas' => ['reatividade'=>-3,'sociabilidade'=>3],
        'me_aproximou' => ['sociabilidade'=>3,'confianca'=>2],
        'flertou_comigo' => ['sociabilidade'=>3],
    ];
    if (!isset($efeitos[$tipo])) return;
    $mult = $forca >= 3 ? 1.4 : 1.0;
    $mudancas = [];
    foreach ($efeitos[$tipo] as $traco=>$delta) $mudancas[$traco] = (int)round($delta * $mult);
    $publicos = ['me_indicou','me_colocou_paredao','me_colocou_monstro','me_atacou_discordia','brigou_comigo','me_elogiou_discordia'];
    e3Mudanca($observador, $mudancas, 'memoria:' . $tipo, in_array($tipo, $publicos, true));
}

/** Avanço de semana é idempotente e compatível com saves anteriores. */
function e3SincronizarSemana(array $jogadores): void {
    $semana = (int)($_SESSION['rodada'] ?? 1);
    foreach ($jogadores as $j) {
        $nome = (string)($j['nome'] ?? '');
        if ($nome === '' || $nome === (string)($_SESSION['meu_nome'] ?? '') || !empty($j['eliminado'])) continue;
        $original = (string)($j['personalidade'] ?? 'Neutro');
        e3GarantirNPC($nome, $original);
        $reg =& $_SESSION['e3_personalidades'][$nome];
        $ultima = (int)($reg['semana_atualizada'] ?? $semana);
        if ($semana > $ultima) {
            $base = e3Bases((string)($reg['original'] ?? $original));
            // Desaceleração natural dos extremos; as experiências ainda importam.
            foreach ($base as $traco => $valor) {
                $distancia = $reg['tracos'][$traco] - $valor;
                $reg['tracos'][$traco] = e3Limitar($reg['tracos'][$traco] - (($distancia > 0) ? min(2, $distancia) : max(-2, $distancia)));
            }
            $reg['semana_atualizada'] = $semana;
            $reg['limites_eventos'] = [];
        }
        // Conquistas e castigos são objetivos; snapshots evitam somar no refresh.
        $stats = (array)($j['estatisticas'] ?? []);
        $atual = [];
        foreach (['lider','anjo','monstro','paredao'] as $campo) $atual[$campo] = (int)($stats[$campo] ?? 0);
        if (is_array($reg['estatisticas_observadas'])) {
            $velho = $reg['estatisticas_observadas'];
            if ($atual['lider'] > (int)($velho['lider']??0)) e3Mudanca($nome, ['confianca'=>4,'estrategia'=>2], 'conquista:lider');
            if ($atual['anjo'] > (int)($velho['anjo']??0)) e3Mudanca($nome, ['confianca'=>3,'sociabilidade'=>2], 'conquista:anjo');
            if ($atual['monstro'] > (int)($velho['monstro']??0)) e3Mudanca($nome, ['reatividade'=>3], 'castigo:monstro');
        }
        $reg['estatisticas_observadas'] = $atual;
        unset($reg);
    }
}

function e3AjustarPerfilIA(array $perfil, string $nome): array {
    $t = e3Tracos($nome);
    // Pequenos multiplicadores sobre o perfil original; nunca removem autonomia.
    $ajustes = [
        'rivalidade' => ($t['reatividade']-50)/200,
        'lealdade' => ($t['lealdade']-50)/170,
        'grupo' => ($t['estrategia']-50)/230,
        'ameaca' => ($t['estrategia']-50)/160,
        'relacao' => ($t['sociabilidade']-50)/260,
        'memoria' => ($t['reatividade']-50)/280,
    ];
    foreach ($ajustes as $campo => $delta) {
        if (isset($perfil[$campo])) $perfil[$campo] = max(0.2, $perfil[$campo] * (1+$delta));
    }
    return $perfil;
}

function e3AjustarPerfilInteracoes(array $perfil, string $nome): array {
    $t = e3Tracos($nome);
    $somas = [
        'treta' => ($t['reatividade']-50)*0.65,
        'alianca' => ($t['lealdade']-50)*0.45 + ($t['estrategia']-50)*0.18,
        'fofoca' => ($t['estrategia']-50)*0.32,
        'emocao' => ($t['reatividade']-50)*0.20,
        'vt' => ($t['confianca']-50)*0.22,
        'romance' => ($t['sociabilidade']-50)*0.34,
    ];
    foreach ($somas as $chave => $delta) if (isset($perfil[$chave])) $perfil[$chave] = e3Limitar($perfil[$chave]+$delta, 4, 100);
    return $perfil;
}

function e3PosturaConfessionario(string $nome): ?string {
    $j=e3EncontrarNPC($nome);
    if (!$j || $nome === (string)($_SESSION['meu_nome']??'')) return null;
    $t=e3Tracos($nome);
    if ($t['reatividade'] >= 69) return 'Tenho percebido que estou reagindo no impulso. Preciso pensar melhor antes de responder.';
    if ($t['lealdade'] <= 32) return 'Depois de algumas decepções, está difícil prometer confiança a qualquer pessoa.';
    if ($t['confianca'] <= 31) return 'Nem sempre consigo mostrar segurança, mas estou tentando encontrar meu lugar aqui.';
    if ($t['confianca'] >= 74) return 'Eu não entrei tão seguro, mas hoje consigo bancar melhor as minhas decisões.';
    if ($t['sociabilidade'] >= 78) return 'Tenho me aberto mais para as pessoas. Isso mudou a forma como vivo essa casa.';
    if ($t['estrategia'] >= 80) return 'Hoje eu escuto, penso nos riscos e só depois decido o que vale a pena falar.';
    return null;
}
