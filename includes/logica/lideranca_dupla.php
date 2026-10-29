<?php
/**
 * Liderança em dupla: utilitários para decisões conjuntas.
 * Nunca altera a definição de líder titular usada pelas outras fases.
 */
function ldLideresAtuais(array $jogadores): array {
    if ((int)($_SESSION['lider_dupla_semana'] ?? 0) !== (int)($_SESSION['rodada'] ?? 1)) return [];
    $nomes = array_column($jogadores, 'nome');
    $dupla = array_values(array_unique(array_filter(array_map('strval', (array)($_SESSION['lideres_dupla'] ?? [])))));
    if (count($dupla) !== 2 || !in_array($dupla[0], $nomes, true) || !in_array($dupla[1], $nomes, true)) return [];
    if (!nomeIgual($dupla[0], (string)($_SESSION['lider'] ?? ''))) return [];
    return $dupla;
}
function ldParticipa(array $jogadores, string $nome): bool {
    foreach (ldLideresAtuais($jogadores) as $l) if (nomeIgual($l, $nome)) return true;
    return false;
}
function ldOutroLider(array $jogadores, string $nome): string {
    foreach (ldLideresAtuais($jogadores) as $l) if (!nomeIgual($l, $nome)) return $l;
    return '';
}
function ldVagasExtrasVIP(array $jogadores, int $qtdVIP): int {
    $dupla = ldLideresAtuais($jogadores);
    return max(0, min(count($jogadores) - ($dupla ? 2 : 1), $qtdVIP - ($dupla ? 1 : 0)));
}
function ldElegiveisVIP(array $jogadores): array {
    $lideres = ldLideresAtuais($jogadores);
    $nomes = [];
    foreach ($jogadores as $j) {
        $n = (string)($j['nome'] ?? '');
        if ($n !== '' && !in_array($n, $lideres, true)) $nomes[] = $n;
    }
    return $nomes;
}
function ldPreferenciasVIP(array $jogadores, string $participante, int $vagas): array {
    $scores = [];
    foreach (ldElegiveisVIP($jogadores) as $nome) {
        $score = function_exists('calcularRelacaoIA') ? (float)calcularRelacaoIA($jogadores, $participante, $nome, (string)($_SESSION['meu_nome'] ?? '')) : 0;
        $scores[$nome] = $score;
    }
    arsort($scores, SORT_NUMERIC);
    return array_slice(array_keys($scores), 0, max(0, $vagas));
}
function ldAcordoVIP(array $sua, array $outra, int $vagas): array {
    $resultado = array_values(array_intersect($sua, $outra));
    $origem = [$sua, $outra]; $i = 0;
    while (count($resultado) < $vagas && $i < 2 * (count($sua) + count($outra) + 1)) {
        $candidatos = $origem[$i % 2]; $idx = intdiv($i, 2);
        if (isset($candidatos[$idx]) && !in_array($candidatos[$idx], $resultado, true)) $resultado[] = $candidatos[$idx];
        $i++;
    }
    foreach (array_merge($sua, $outra) as $n) {
        if (count($resultado) >= $vagas) break;
        if (!in_array($n, $resultado, true)) $resultado[] = $n;
    }
    return array_slice($resultado, 0, $vagas);
}
function ldElegiveisParedao(array $jogadores): array {
    $r = []; $lideres = ldLideresAtuais($jogadores);
    foreach ($jogadores as $j) {
        $nome = (string)($j['nome'] ?? '');
        if ($nome !== '' && !in_array($nome, $lideres, true) && !estaImune($jogadores, $nome)) $r[] = $nome;
    }
    return $r;
}
/** Retorna sugestão estável durante toda a negociação. */
function ldPreferenciaParedao(array $jogadores, string $lider): string {
    $chave = 'ld_sugestao_paredao'; $semana = (int)($_SESSION['rodada'] ?? 1);
    $elegiveis = ldElegiveisParedao($jogadores);
    $s = (array)($_SESSION[$chave] ?? []);
    if ((int)($s['semana'] ?? 0) === $semana && ($s['lider'] ?? '') === $lider && in_array($s['nome'] ?? '', $elegiveis, true)) return (string)$s['nome'];
    $scores = [];
    foreach ($elegiveis as $nome) {
        $scores[$nome] = function_exists('calcularRelacaoIA') ? (float)calcularRelacaoIA($jogadores, $lider, $nome, (string)($_SESSION['meu_nome'] ?? '')) : 0;
    }
    asort($scores, SORT_NUMERIC); $nome = (string)(array_key_first($scores) ?? '');
    $_SESSION[$chave] = ['semana'=>$semana,'lider'=>$lider,'nome'=>$nome];
    return $nome;
}
function ldCompromissoParedao(array $jogadores, string $um, string $outro, string $proposto, string $preferido): string {
    $opcoes = ldElegiveisParedao($jogadores);
    $scores = [];
    foreach ($opcoes as $nome) {
        $a = function_exists('calcularRelacaoIA') ? (float)calcularRelacaoIA($jogadores,$um,$nome,(string)($_SESSION['meu_nome']??'')) : 0;
        $b = function_exists('calcularRelacaoIA') ? (float)calcularRelacaoIA($jogadores,$outro,$nome,(string)($_SESSION['meu_nome']??'')) : 0;
        $scores[$nome] = $a + $b;
        if ($nome === $proposto || $nome === $preferido) $scores[$nome] += 4;
    }
    asort($scores, SORT_NUMERIC);
    return (string)(array_key_first($scores) ?? '');
}

function ldPreferenciaDesempate(array $jogadores, string $parceiro, array $empatados, int $vagas): array {
    $scores = [];
    foreach ($empatados as $nome) {
        $scores[$nome] = function_exists('calcularRelacaoIA')
            ? (float)calcularRelacaoIA($jogadores, $parceiro, (string)$nome, (string)($_SESSION['meu_nome'] ?? ''))
            : 0;
    }
    asort($scores, SORT_NUMERIC);
    return array_slice(array_keys($scores), 0, max(0, $vagas));
}
