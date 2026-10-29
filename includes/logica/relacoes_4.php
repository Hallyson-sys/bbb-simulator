<?php
/**
 * Relações Sociais 4.0 — decisões autônomas entre NPCs.
 *
 * O estado vive em $_SESSION['r4_estado'] para continuar compatível com os saves.
 * Só há mudanças de estado em gatilhos de fase; renderizar ou atualizar a página
 * não gera novos acontecimentos. Assuntos e acordos privados não são publicados.
 */

function r4Semana(): int { return max(1, (int)($_SESSION['rodada'] ?? 1)); }
function r4Par(string $a, string $b): string {
    $nomes = [$a, $b]; sort($nomes, SORT_STRING);
    return implode('|', $nomes);
}
function r4Estado(): array {
    if (!isset($_SESSION['r4_estado']) || !is_array($_SESSION['r4_estado'])) {
        $_SESSION['r4_estado'] = [];
    }
    $_SESSION['r4_estado'] += ['executados'=>[], 'historico'=>[], 'subgrupos'=>[], 'tensoes_casal'=>[]];
    return $_SESSION['r4_estado'];
}
function r4NPCs(array $jogadores, string $meuNome): array {
    $fora = (string)($_SESSION['falso_eliminado'] ?? '');
    $falso = !empty($_SESSION['paredao_falso_ativo']);
    $ativos = [];
    foreach ($jogadores as $j) {
        $n = (string)($j['nome'] ?? '');
        if ($n === '' || $n === $meuNome || !empty($j['eliminado']) || !empty($j['fora_da_casa']) ||
            ($falso && $n === $fora)) continue;
        $ativos[$n] = $j;
    }
    return $ativos;
}
function r4Relacao(array $jogadores, string $a, string $b): array {
    foreach ($jogadores as $j) {
        if (($j['nome'] ?? '') === $a) {
            $r = (array)($j['relacoes'][$b] ?? []);
            return [(int)($r['amizade'] ?? 0), (int)($r['rivalidade'] ?? 0), (int)($r['confianca'] ?? 0)];
        }
    }
    return [0,0,0];
}
function r4Aproximacao(array &$jogadores, string $a, string $b, int $amizade, int $rivalidade, int $confianca): void {
    if (!function_exists('alterarAfinidade')) return;
    alterarAfinidade($jogadores, $a, $b, $amizade, $rivalidade, $confianca);
    alterarAfinidade($jogadores, $b, $a, $amizade, $rivalidade, $confianca);
}
function r4GrupoComum(array $ativos, string $a, string $b): string {
    $ga = (string)($ativos[$a]['alianca'] ?? '');
    return ($ga !== '' && $ga === (string)($ativos[$b]['alianca'] ?? '')) ? $ga : '';
}
function r4Registrar(string $tipo, string $a, string $b, string $local, string $texto): void {
    r4Estado();
    $semana = r4Semana();
    $registro = ['semana'=>$semana, 'tipo'=>$tipo, 'pessoas'=>[$a,$b], 'local'=>$local, 'texto'=>$texto];
    $_SESSION['r4_estado']['historico'][] = $registro;
    $_SESSION['r4_estado']['historico'] = array_slice($_SESSION['r4_estado']['historico'], -36);
    if (!isset($_SESSION['evento_extra']) || !is_array($_SESSION['evento_extra'])) $_SESSION['evento_extra'] = [];
    $_SESSION['evento_extra'][] = '💞 ' . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    if (function_exists('registrarHistoricoTemporada')) {
        registrarHistoricoTemporada('evolucao_social', 'Relações em movimento', $texto, [$a,$b], '💞', $semana,
            'r4|' . $tipo . '|' . r4Par($a,$b) . '|' . count($_SESSION['r4_estado']['historico']));
    }
}
/** Vínculos internos criados por aproximação recorrente; não são uma aliança nova. */
function r4SubgrupoAtivo(array $jogadores, string $a, string $b): bool {
    $registro = $_SESSION['r4_estado']['subgrupos'][r4Par($a,$b)] ?? null;
    if (!is_array($registro) || (int)($registro['ate'] ?? 0) < r4Semana()) return false;
    foreach ($jogadores as $j) {
        if (($j['nome'] ?? '') === $a) {
            if (!empty($j['eliminado']) || !empty($j['fora_da_casa'])) return false;
            $ga = (string)($j['alianca'] ?? '');
        }
        if (($j['nome'] ?? '') === $b) {
            if (!empty($j['eliminado']) || !empty($j['fora_da_casa'])) return false;
            $gb = (string)($j['alianca'] ?? '');
        }
    }
    return isset($ga,$gb) && $ga !== '' && $ga === $gb && $ga === (string)($registro['grupo'] ?? '');
}
/** Efeito moderado, nunca obriga um NPC a votar em determinada pessoa. */
function r4BonusVoto(array $jogadores, string $npc, string $alvo, string $contexto): float {
    if ($npc === $alvo || !in_array($contexto, ['voto','indicacao_lider'], true)) return 0;
    $bonus = r4SubgrupoAtivo($jogadores, $npc, $alvo) ? -10.0 : 0.0;
    if (function_exists('estaNamorandoCom') && estaNamorandoCom($npc,$alvo)) $bonus -= 15.0;
    return $bonus;
}
/** Conviver numa cena da Rotina 2.0 pode fortalecer/abalar vínculos persistentes. */
function r4AposRotina(array &$jogadores, string $a, string $b, string $tipo): void {
    $positivos = ['cafe','almoco','limpeza','treino','piscina','conselho','reconciliacao','jogo_leve','festanca','brinde','festa_paz'];
    $negativos = ['tarefa_tensa','provocacao','distancia','festa_tensao'];
    if (!in_array($tipo, array_merge($positivos,$negativos), true)) return;
    r4Estado();
    $chave = r4Semana() . '|' . r4Par($a,$b);
    $reg =& $_SESSION['r4_estado'];
    $contador = (int)($reg['aproximacoes'][$chave] ?? 0);
    if ($contador >= 2) { unset($reg); return; }
    $reg['aproximacoes'][$chave] = $contador + 1;
    if (in_array($tipo, $positivos, true)) {
        // Um flerte já existente ganha continuidade; amizades não viram romance por obrigação.
        if (function_exists('obterRomance') && function_exists('alterarRomance') &&
            min(obterRomance($jogadores,$a,$b), obterRomance($jogadores,$b,$a)) >= 25 &&
            !parceiroAtual($a) && !parceiroAtual($b)) {
            alterarRomance($jogadores,$a,$b,2);
            alterarRomance($jogadores,$b,$a,2);
        }
        if ($tipo === 'reconciliacao') unset($reg['tensoes_casal'][r4Par($a,$b)]);
    } elseif (function_exists('estaNamorandoCom') && estaNamorandoCom($a,$b)) {
        $reg['tensoes_casal'][r4Par($a,$b)] = min(5,(int)($reg['tensoes_casal'][r4Par($a,$b)]??0)+1);
    }
    unset($reg);
}
/** Escolhe um acontecimento coerente com o estado real do par. */
function r4Opcoes(array $jogadores, array $ativos, string $a, string $b): array {
    [$ab,$rab,$cab] = r4Relacao($jogadores,$a,$b);
    [$ba,$rba,$cba] = r4Relacao($jogadores,$b,$a);
    $amizade = ($ab+$ba)/2;
    $rival = ($rab+$rba)/2;
    $conf = ($cab+$cba)/2;
    $mesmo = r4GrupoComum($ativos,$a,$b);
    $chave = r4Par($a,$b);
    $op = [];
    if ($rival >= 22 && $conf < 30) {
        $op['afastamento'] = 5 + min(10, (int)($rival/6));
        $op['tregua'] = 3;
    } else {
        $op['apoio'] = 4 + max(0,(int)($amizade/15));
        $op['aproximacao'] = 4;
        if ($rival > 8) $op['tregua'] = 2;
    }
    if ($mesmo !== '') {
        if ($rival >= 28 && $conf <= 9) $op['crise_grupo'] = 8;
        elseif ($amizade >= 35 && $conf >= 22 && !r4SubgrupoAtivo($jogadores,$a,$b)) $op['parceria'] = 7;
    }
    $romance = function_exists('obterRomance') ? min((int)obterRomance($jogadores,$a,$b),(int)obterRomance($jogadores,$b,$a)) : 0;
    $casal = function_exists('estaNamorandoCom') && estaNamorandoCom($a,$b);
    if ($casal) {
        if ($conf < 5 && $rival > 32 && (int)($_SESSION['r4_estado']['tensoes_casal'][$chave] ?? 0) >= 2) $op['fim_casal'] = 8;
        else $op['casal_apoio'] = 5;
    } elseif ($romance >= 42 && $amizade >= 22 && $conf >= 14 && !parceiroAtual($a) && !parceiroAtual($b)) {
        $op['clima'] = 6;
        if ($romance >= 77 && $conf >= 30) $op['pedido'] = 3;
    }
    // Convivência 3.0: a personalidade evolutiva muda a chance de agir,
    // mas não sobrepõe as condições reais da relação.
    if (function_exists('e3Tracos')) {
        $t = e3Tracos($a);
        if (($t['reatividade'] ?? 50) >= 66) {
            foreach (['afastamento','crise_grupo'] as $chave) {
                if (isset($op[$chave])) $op[$chave] += 3;
            }
        }
        if (($t['sociabilidade'] ?? 50) >= 67) {
            foreach (['apoio','aproximacao','tregua','clima'] as $chave) {
                if (isset($op[$chave])) $op[$chave] += 2;
            }
        }
        if (($t['lealdade'] ?? 50) >= 70 && isset($op['parceria'])) $op['parceria'] += 3;
    }
    return $op;
}
function r4Ponderar(array $pesos): ?string {
    $total = array_sum($pesos);
    if ($total <= 0) return null;
    $sorte = random_int(1,$total);
    foreach ($pesos as $k=>$v) { $sorte -= (int)$v; if ($sorte <= 0) return $k; }
    return array_key_first($pesos);
}
/** Uma oportunidade de evolução por etapa social, com registro idempotente. */
function r4ProcessarFase(array &$jogadores, string $meuNome, string $fase): bool {
    if (!in_array($fase,['interacoes_1','festa'],true) || !empty($_SESSION['modo_espectador'])) return false;
    r4Estado();
    $semana = r4Semana();
    $gatilho = $semana . '|' . $fase;
    if (array_key_exists($gatilho, $_SESSION['r4_estado']['executados'])) return false;
    // Marcar antes do sorteio: F5, saves e dois POSTs não repetem o mesmo gatilho.
    $_SESSION['r4_estado']['executados'][$gatilho] = true;
    $_SESSION['r4_estado']['executados'] = array_slice($_SESSION['r4_estado']['executados'], -28, null, true);
    if ($semana < 2 || random_int(1,100) > ($fase === 'festa' ? 65 : 58)) return false;
    $ativos = r4NPCs($jogadores,$meuNome);
    if (count($ativos) < 2) return false;
    $nomes = array_keys($ativos);
    $candidatos = [];
    $recentes = array_column(array_slice($_SESSION['r4_estado']['historico'], -5), 'pessoas');
    // O protagonista pode aparecer em até uma evolução social por semana.
    // Apenas ações observáveis e cotidianas: jamais assumir romances, alianças ou votos por ele.
    $comJogador = function_exists('rc2JogadorDisponivel')
        && rc2JogadorDisponivel($jogadores, $meuNome)
        && empty($_SESSION['r4_estado']['protagonista_semana'][$semana])
        && random_int(1,100) <= 35;
    if ($comJogador) {
        foreach ($nomes as $npc) {
            if (in_array([$meuNome,$npc],$recentes,true)) continue;
            $rel = (int)($_SESSION['relacoes_jogador'][$npc] ?? 0);
            $tipos = $rel <= -22 ? ['afastamento'=>7, 'apoio'=>1]
                : ($rel >= 22 ? ['apoio'=>6, 'aproximacao'=>4] : ['aproximacao'=>5, 'apoio'=>3]);
            foreach ($tipos as $tipo=>$peso) {
                $candidatos[] = ['a'=>$meuNome,'b'=>$npc,'tipo'=>$tipo,'peso'=>$peso];
            }
        }
        if (!$candidatos) $comJogador = false;
    }
    if (!$comJogador) for ($i=0; $i<count($nomes); $i++) {
        for ($k=$i+1; $k<count($nomes); $k++) {
            $a=$nomes[$i]; $b=$nomes[$k];
            if (in_array([$a,$b], $recentes, true)) continue;
            foreach (r4Opcoes($jogadores,$ativos,$a,$b) as $tipo=>$peso) {
                $candidatos[] = ['a'=>$a,'b'=>$b,'tipo'=>$tipo,'peso'=>max(1,(int)$peso)];
            }
        }
    }
    if (!$candidatos) return false;
    // Relações já construídas merecem mais atenção do que diálogos neutros.
    // Só priorizamos essas cenas quando a situação realmente existe no save.
    $marcantes = array_values(array_filter($candidatos, static fn($o) => in_array(
        $o['tipo'], ['parceria','clima','pedido','fim_casal','crise_grupo','tregua'], true
    )));
    if (!$comJogador && $marcantes && random_int(1,100) <= 65) $candidatos = $marcantes;
    $sorte = random_int(1,array_sum(array_column($candidatos,'peso')));
    $s = $candidatos[0];
    foreach ($candidatos as $o) { $sorte -= $o['peso']; if ($sorte <= 0) { $s = $o; break; } }
    $a=$s['a']; $b=$s['b']; $tipo=$s['tipo'];
    $local = $fase === 'festa' ? ['pista de dança','área externa','bar'][random_int(0,2)] : ['varanda','piscina','cozinha','jardim'][random_int(0,3)];
    $texto = '';
    if ($comJogador) {
        // A IA age em relação ao jogador; não inventar promessas nem sentimentos dele.
        if ($tipo === 'apoio') {
            alterarAfinidade($jogadores,$b,$a,3,-1,3);
            $texto="$b se aproximou de você no $local para oferecer companhia após os acontecimentos da semana.";
        } elseif ($tipo === 'aproximacao') {
            alterarAfinidade($jogadores,$b,$a,3,-1,2);
            $texto="$b procurou você no $local e a conversa casual deixou o clima mais leve entre vocês.";
        } else {
            alterarAfinidade($jogadores,$b,$a,-2,2,-2);
            $texto="O distanciamento de $b em relação a você ficou perceptível durante a convivência no $local.";
        }
        if (function_exists('ajustarRelacaoJogador')) {
            ajustarRelacaoJogador($b, $tipo === 'afastamento' ? -2 : 2);
        }
        $_SESSION['r4_estado']['protagonista_semana'][$semana] = true;
    } else switch ($tipo) {
        case 'apoio':
            r4Aproximacao($jogadores,$a,$b,4,-1,3);
            $texto="$a e $b passaram um tempo no $local e se apoiaram após uma semana intensa.";
            break;
        case 'aproximacao':
            r4Aproximacao($jogadores,$a,$b,5,-1,2);
            $texto="$a e $b encontraram um assunto em comum no $local e parecem mais à vontade juntos.";
            break;
        case 'afastamento':
            r4Aproximacao($jogadores,$a,$b,-3,3,-3);
            $texto="Um desentendimento no $local fez $a e $b se afastarem, sem chegar a uma discussão aberta.";
            break;
        case 'tregua':
            r4Aproximacao($jogadores,$a,$b,3,-5,4);
            $texto="$a e $b conversaram no $local para aliviar um atrito da convivência. Ainda há coisas a resolver.";
            break;
        case 'parceria':
            r4Aproximacao($jogadores,$a,$b,2,-1,4);
            $_SESSION['r4_estado']['subgrupos'][r4Par($a,$b)] = ['ate'=>$semana+2,'grupo'=>r4GrupoComum($ativos,$a,$b)];
            $texto="$a e $b têm passado mais tempo juntos, fortalecendo a parceria nos momentos de convivência.";
            break;
        case 'crise_grupo':
            r4Aproximacao($jogadores,$a,$b,-3,4,-5);
            unset($_SESSION['r4_estado']['subgrupos'][r4Par($a,$b)]);
            $texto="$a e $b discordaram durante uma conversa do grupo. O clima entre eles ficou mais tenso.";
            break;
        case 'clima':
            alterarRomance($jogadores,$a,$b,5);
            alterarRomance($jogadores,$b,$a,5);
            r4Aproximacao($jogadores,$a,$b,2,-1,2);
            $texto="$a e $b se aproximaram durante um momento tranquilo no $local. Os dois ficaram juntos por um tempo.";
            break;
        case 'pedido':
            registrarCasal($a,$b);
            r4Aproximacao($jogadores,$a,$b,5,-3,6);
            $texto="$a e $b decidiram assumir o relacionamento depois de semanas de aproximação.";
            break;
        case 'casal_apoio':
            r4Aproximacao($jogadores,$a,$b,4,-2,3);
            unset($_SESSION['r4_estado']['tensoes_casal'][r4Par($a,$b)]);
            $texto="$a e $b tiveram um momento de carinho e apoio no $local, apesar da pressão do jogo.";
            break;
        case 'fim_casal':
            terminarCasal($a,$b);
            unset($_SESSION['r4_estado']['tensoes_casal'][r4Par($a,$b)]);
            $texto="$a e $b decidiram encerrar o relacionamento após vários desentendimentos.";
            break;
    }
    if ($texto === '') return false;
    if (function_exists('registrarRelacaoMarcante')) {
        registrarRelacaoMarcante($jogadores,$a,$b);
        registrarRelacaoMarcante($jogadores,$b,$a);
    }
    r4Registrar($tipo,$a,$b,$local,$texto);
    $_SESSION['jogadores'] = $jogadores;
    return true;
}
function r4Recentes(int $limite=4): array {
    return array_reverse(array_slice((array)($_SESSION['r4_estado']['historico']??[]),-max(1,min(8,$limite))));
}
