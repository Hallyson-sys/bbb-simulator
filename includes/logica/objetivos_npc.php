<?php
/**
 * Objetivos Pessoais dos NPCs V1.
 * Motivação privada, persistente no save/session. A IA continua livre para
 * escolher outra ação; os objetivos apenas ajustam probabilidades.
 * Não publicar alvos, objetivos ou progresso no Ao Vivo/Feed do jogador.
 */

function on1Semana(): int { return max(1, (int)($_SESSION['rodada'] ?? 1)); }

function on1Ativos(array $jogadores): array {
    $ativos = [];
    $meuNome = (string)($_SESSION['meu_nome'] ?? '');
    $oculto = !empty($_SESSION['paredao_falso_ativo']) ? (string)($_SESSION['falso_eliminado'] ?? '') : '';
    foreach ($jogadores as $j) {
        if (!is_array($j)) continue;
        $n = trim((string)($j['nome'] ?? ''));
        if ($n === '' || !empty($j['eliminado']) || !empty($j['fora_da_casa']) || ($oculto !== '' && $oculto === $n)) continue;
        $ativos[$n] = $j;
    }
    return $ativos;
}

function on1Relacao(array $jogadores, string $nome, string $alvo): array {
    if (function_exists('obterRelacaoCompleta')) {
        $r = obterRelacaoCompleta($jogadores, $nome, $alvo, (string)($_SESSION['meu_nome'] ?? ''));
        if (is_array($r)) return $r;
    }
    foreach ($jogadores as $j) {
        if ((string)($j['nome'] ?? '') === $nome) {
            return (array)($j['relacoes'][$alvo] ?? []);
        }
    }
    return [];
}

/**
 * Lista de intenções elegíveis, calculadas dos relacionamentos e feitos reais.
 * Não depende de aprovação do público. Não cria um alvo fictício.
 */
function on1Candidatos(array $jogadores, string $nome): array {
    $ativos = on1Ativos($jogadores);
    if (!isset($ativos[$nome])) return [];
    $j = $ativos[$nome];
    $tracos = function_exists('e3Tracos') ? e3Tracos($nome) : [];
    $perfil = (string)($j['personalidade'] ?? 'Neutro');
    $opcoes = [];
    $aliados = []; $rivais = []; $reparar = [];
    foreach ($ativos as $alvo => $outro) {
        if ($alvo === $nome) continue;
        $r = on1Relacao($jogadores, $nome, $alvo);
        $amizade = (float)($r['amizade'] ?? 0);
        $confianca = (float)($r['confianca'] ?? 0);
        $rivalidade = (float)($r['rivalidade'] ?? 0);
        $mesmoGrupo = (string)($j['alianca'] ?? '') !== '' && (string)($j['alianca'] ?? '') === (string)($outro['alianca'] ?? '');
        $apoio = $amizade * .65 + $confianca * .60 - $rivalidade * .65 + ($mesmoGrupo ? 25 : 0);
        $conflito = $rivalidade * .75 - $amizade * .30 - $confianca * .32;
        if ($apoio >= 15) $aliados[$alvo] = $apoio;
        if ($conflito >= 16) $rivais[$alvo] = $conflito;
        if ($conflito >= 10 && $conflito <= 58 && $confianca > -35) $reparar[$alvo] = $conflito;
    }
    arsort($aliados); arsort($rivais); arsort($reparar);
    if ($aliados) $opcoes[] = ['tipo'=>'proteger_aliado', 'alvo'=>(string)array_key_first($aliados), 'peso'=>25 + (int)round(($tracos['lealdade']??50)/6)];
    if ($rivais) $opcoes[] = ['tipo'=>'superar_rival','alvo'=>(string)array_key_first($rivais), 'peso'=>17 + (int)round(($tracos['reatividade']??50)/7)];
    if ($reparar && !in_array($perfil, ['Falso','Manipulador'], true)) $opcoes[] = ['tipo'=>'reconciliar','alvo'=>(string)array_key_first($reparar),'peso'=>12 + (int)round(($tracos['sociabilidade']??50)/9)];
    if ((string)($j['alianca'] ?? '') !== '') $opcoes[] = ['tipo'=>'fortalecer_alianca','alvo'=>'','peso'=>22 + (int)round(($tracos['lealdade']??50)/9)];
    else $opcoes[] = ['tipo'=>'construir_alianca','alvo'=>'','peso'=>20 + (int)round(($tracos['sociabilidade']??50)/10)];
    $stats = (array)($j['estatisticas'] ?? []);
    $opcoes[] = ['tipo'=>'vencer_prova','alvo'=>'','peso'=>22 + (int)round(($tracos['confianca']??50)/10)];
    if ((int)($stats['paredao'] ?? 0) > 0) $opcoes[] = ['tipo'=>'evitar_paredao','alvo'=>'','peso'=>27];
    if (count($ativos) <= 7) $opcoes[] = ['tipo'=>'chegar_final','alvo'=>'','peso'=>44];
    else $opcoes[] = ['tipo'=>'chegar_final','alvo'=>'','peso'=>15 + (int)round(($tracos['estrategia']??50)/12)];
    if (in_array($perfil, ['Estrategista','Líder Nato'], true)) foreach ($opcoes as &$o) {
        if (in_array($o['tipo'], ['vencer_prova','chegar_final'], true)) $o['peso'] += 10;
    }
    unset($o);
    return $opcoes;
}

/** Sorteia somente na criação/troca do objetivo; nunca a cada renderização. */
function on1Escolher(array $jogadores, string $nome, array $anteriores = []): ?array {
    $candidatos = on1Candidatos($jogadores, $nome);
    if (!$candidatos) return null;
    $recentes = array_slice($anteriores, -2);
    $tipos = array_column($recentes, 'tipo');
    foreach ($candidatos as &$c) {
        if (in_array($c['tipo'], $tipos, true)) $c['peso'] = max(2, (int)floor($c['peso'] / 5));
    }
    unset($c);
    $total = array_sum(array_column($candidatos, 'peso'));
    $sorte = random_int(1, max(1, $total));
    $eleito = $candidatos[0];
    foreach ($candidatos as $c) { $sorte -= $c['peso']; if ($sorte <= 0) { $eleito = $c; break; } }
    $stats = [];
    foreach ((array)(on1Ativos($jogadores)[$nome]['estatisticas'] ?? []) as $k=>$v) if (is_numeric($v)) $stats[$k] = (int)$v;
    return [
        'tipo'=>$eleito['tipo'], 'alvo'=>$eleito['alvo'],
        'inicio'=>on1Semana(), 'revisado'=>on1Semana(),
        'estatisticas_base'=>$stats, 'sinal'=>null,
    ];
}

function on1Objetivo(string $nome): ?array {
    $reg = $_SESSION['on1_objetivos'][$nome] ?? null;
    if (!is_array($reg) || !isset($reg['tipo'])) return null;
    return $reg;
}

/** Um objetivo só muda por transição semanal, eliminação do alvo ou marco concreto. */
function on1Sincronizar(array $jogadores): void {
    if (!isset($_SESSION['on1_objetivos']) || !is_array($_SESSION['on1_objetivos'])) $_SESSION['on1_objetivos'] = [];
    if (!isset($_SESSION['on1_historico']) || !is_array($_SESSION['on1_historico'])) $_SESSION['on1_historico'] = [];
    $ativos = on1Ativos($jogadores);
    $meuNome = (string)($_SESSION['meu_nome'] ?? '');
    $semana = on1Semana();
    foreach ($ativos as $nome=>$j) {
        if ($nome === $meuNome) continue;
        $atual = on1Objetivo($nome);
        $trocar = false; $motivo = '';
        if (!$atual) { $trocar = true; $motivo = 'inicial'; }
        else {
            $alvo = (string)($atual['alvo'] ?? '');
            $tipo = (string)$atual['tipo'];
            $marco = in_array($tipo, ['chegar_final','vencer_prova'], true) && $semana > (int)($atual['inicio'] ?? $semana);
            $base = (array)($atual['estatisticas_base'] ?? []);
            $stats = (array)($j['estatisticas'] ?? []);
            if ($alvo !== '' && !isset($ativos[$alvo])) { $trocar = true; $motivo = 'alvo saiu da casa'; }
            elseif ($marco && $tipo === 'vencer_prova' && (
                (int)($stats['lider'] ?? 0) > (int)($base['lider'] ?? 0) ||
                (int)($stats['anjo'] ?? 0) > (int)($base['anjo'] ?? 0)
            )) { $trocar = true; $motivo = 'conquista em prova'; }
            elseif ($marco && $tipo === 'chegar_final' && count($ativos) <= 3) { $trocar = true; $motivo = 'alcançou a final'; }
            elseif ($semana > (int)($atual['revisado'] ?? $atual['inicio'] ?? $semana)) {
                $agoraAlianca = (string)($j['alianca'] ?? '');
                if (($tipo === 'construir_alianca' && $agoraAlianca !== '') ||
                    ($tipo === 'fortalecer_alianca' && $agoraAlianca === '')) {
                    $trocar = true; $motivo = 'mudança de aliança';
                } elseif ($tipo === 'reconciliar' && $alvo !== '') {
                    $r = on1Relacao($jogadores, $nome, $alvo);
                    if ((float)($r['rivalidade'] ?? 0) <= 9 && (float)($r['amizade'] ?? 0) >= 15) {
                        $trocar = true; $motivo = 'reaproximação';
                    }
                }
                $sinal = (array)($atual['sinal'] ?? []);
                if (!$trocar && in_array((string)($sinal['tipo']??''), ['rompeu_comigo','me_indicou','me_colocou_paredao','brigou_comigo'], true)
                    && (string)($sinal['alvo']??'') === $alvo && $alvo !== '') {
                    $trocar = true; $motivo = 'decepção com o alvo';
                }
                if (!$trocar && $semana - (int)($atual['inicio'] ?? $semana) >= 3) { $trocar = true; $motivo = 'nova prioridade'; }
                $_SESSION['on1_objetivos'][$nome]['revisado'] = $semana;
            }
        }
        if (!$trocar) continue;
        if ($atual) {
            $_SESSION['on1_historico'][$nome][] = ['tipo'=>$atual['tipo'], 'alvo'=>$atual['alvo']??'', 'inicio'=>$atual['inicio']??$semana,'fim'=>$semana,'motivo'=>$motivo];
            $_SESSION['on1_historico'][$nome] = array_slice($_SESSION['on1_historico'][$nome], -12);
        }
        $novo = on1Escolher($jogadores, $nome, (array)($_SESSION['on1_historico'][$nome] ?? []));
        if ($novo) $_SESSION['on1_objetivos'][$nome] = $novo;
    }
    // Eliminados têm histórico preservado, mas nenhuma meta ativa na IA.
    foreach (array_keys($_SESSION['on1_objetivos']) as $nome) {
        if (!isset($ativos[$nome])) unset($_SESSION['on1_objetivos'][$nome]);
    }
}

/** Apenas o observador conhece sua lembrança. Ninguém sabe quem votou sem revelação. */
function on1RegistrarMemoria(string $observador, string $alvo, string $tipo): void {
    if (!isset($_SESSION['on1_objetivos'][$observador]) || !is_array($_SESSION['on1_objetivos'][$observador])) return;
    if ($observador === $alvo || $alvo === '') return;
    $_SESSION['on1_objetivos'][$observador]['sinal'] = ['tipo'=>$tipo, 'alvo'=>$alvo, 'semana'=>on1Semana()];
}

/** Ajuste de pontuação moderado; não escolhe alvos, não substitui as regras. */
function on1BonusAlvo(array $jogadores, string $npc, string $alvo, string $contexto): float {
    $obj = on1Objetivo($npc);
    if (!$obj || $npc === $alvo) return 0.0;
    $ativos = on1Ativos($jogadores);
    if (!isset($ativos[$npc]) || !isset($ativos[$alvo])) return 0.0;
    $tipo = (string)$obj['tipo'];
    $foco = (string)($obj['alvo'] ?? '');
    $positivo = in_array($contexto, ['vip','imunidade','curinga_imunidade','salvar_paredao','festa_aliado','discordia_aliado','podio','interacao_aliado'], true);
    $negativo = in_array($contexto, ['voto','indicacao_lider','indicacao_bigfone','discordia_negativo','interacao_rival','festa_rival','contra_golpe'], true);
    if (($tipo === 'proteger_aliado' || $tipo === 'reconciliar') && $foco !== '' && $alvo === $foco) {
        return $positivo ? ($tipo === 'proteger_aliado' ? 20.0 : 13.0) : ($negativo ? -20.0 : 0.0);
    }
    if ($tipo === 'superar_rival' && $foco !== '' && $alvo === $foco) {
        return $negativo ? 19.0 : ($positivo ? -13.0 : 0.0);
    }
    $grupo = (string)($ativos[$npc]['alianca'] ?? '');
    if ($tipo === 'fortalecer_alianca' && $grupo !== '' && (string)($ativos[$alvo]['alianca'] ?? '') === $grupo) {
        return $positivo ? 11.0 : ($negativo ? -12.0 : 0.0);
    }
    if ($tipo === 'construir_alianca' && $grupo === '' && $positivo) {
        $r = on1Relacao($jogadores, $npc, $alvo);
        if ((float)($r['confianca'] ?? 0) > 7 && (float)($r['rivalidade'] ?? 0) < 22) return 8.0;
    }
    // Objetivos sem pessoa definida também mudam escolhas concretas.
    if ($tipo === 'evitar_paredao') {
        $r = on1Relacao($jogadores, $npc, $alvo);
        if ($positivo && (float)($r['confianca'] ?? 0) > 20) return 5.0;
        if ($negativo && (float)($r['rivalidade'] ?? 0) > 20) return 6.0;
    }
    if ($tipo === 'chegar_final' && $negativo && in_array($contexto, ['voto','indicacao_lider'], true)) {
        $vitorias = (int)($ativos[$alvo]['estatisticas']['lider'] ?? 0);
        return (float)min(10, max(0, $vitorias * 3));
    }
    return 0.0;
}

/** Esforço ligeiramente maior em provas, sem vitória automática. */
function on1BonusProva(string $nome): int {
    $obj = on1Objetivo($nome);
    return $obj && $obj['tipo'] === 'vencer_prova' ? 5 : 0;
}

/** Postura diante do alvo em encontros espontâneos: apenas peso, nunca ação obrigatória. */
function on1PesosInteracoes(array $pesos, string $npc, string $alvo): array {
    $obj = on1Objetivo($npc);
    if (!$obj) return $pesos;
    $tipo = (string)$obj['tipo'];
    $foco = (string)($obj['alvo'] ?? '');
    if ($foco !== '' && $foco === $alvo) {
        if (in_array($tipo, ['proteger_aliado','reconciliar'], true)) {
            $pesos[1] = ($pesos[1] ?? 0) + 18;
            $pesos[4] = ($pesos[4] ?? 0) + 12;
            $pesos[3] = max(2, ($pesos[3] ?? 0) - 13);
        } elseif ($tipo === 'superar_rival') {
            $pesos[3] = ($pesos[3] ?? 0) + 18;
            $pesos[2] = ($pesos[2] ?? 0) + 7;
        }
    } elseif ($tipo === 'construir_alianca' || $tipo === 'fortalecer_alianca') {
        $pesos[4] = ($pesos[4] ?? 0) + 9;
    }
    return $pesos;
}

/** Ajuste de compromisso interno nas alianças: pequeno e limitado. */
function on1BonusLealdade(string $nome): int {
    $obj = on1Objetivo($nome);
    if (!$obj) return 0;
    return match ($obj['tipo']) {
        'fortalecer_alianca' => 7,
        'construir_alianca' => 4,
        'proteger_aliado' => 3,
        'superar_rival' => -2,
        default => 0,
    };
}

/** Subtexto genérico: não expõe nomes ou pactos privados no Confessionário. */
function on1FalaConfessionario(string $nome): ?string {
    $obj = on1Objetivo($nome);
    if (!$obj) return null;
    return match ($obj['tipo']) {
        'proteger_aliado' => 'Aqui dentro eu quero cuidar de quem esteve perto de mim, mas sem deixar de jogar.',
        'superar_rival' => 'Tem situação que me desafia. Eu não vou fugir dos conflitos, só preciso escolher meu momento.',
        'reconciliar' => 'Nem toda história precisa acabar em briga. Quero entender se ainda existe espaço para conversar.',
        'construir_alianca' => 'Percebi que, sozinho, tudo fica mais difícil. Estou tentando construir confiança aos poucos.',
        'fortalecer_alianca' => 'Um grupo só é forte quando existe parceria de verdade, e não apenas promessa.',
        'vencer_prova' => 'Eu preciso mostrar para mim mesmo que consigo fazer diferença nas provas.',
        'evitar_paredao' => 'Eu quero atravessar a próxima formação de Paredão sem ficar à mercê dos outros.',
        'chegar_final' => 'Quanto mais perto da final, mais eu penso no que ainda preciso fazer para chegar lá.',
        default => null,
    };
}
