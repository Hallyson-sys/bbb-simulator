<?php
/**
 * TOP 4: A Última Resistência (V2).
 * O servidor mantém todos os ciclos, estados e eliminações em $_SESSION.
 * O marcador do navegador é apenas o minigame de precisão; o servidor valida
 * etapa, token, intervalo e efeitos uma única vez por resposta.
 */
function top4CriarCiclos(): array
{
    $bases = [
        ['titulo'=>'O primeiro apoio', 'local'=>'Plataforma de equilíbrio', 'texto'=>'A plataforma começa a vibrar. Encontre um ponto estável antes que o primeiro sinal toque.', 'efeito'=>'Vibração leve'],
        ['titulo'=>'Luzes e distrações', 'local'=>'Torre de luzes', 'texto'=>'As luzes piscam e a música aumenta. Sua concentração vai ser tão importante quanto sua resistência.', 'efeito'=>'Luzes piscando'],
        ['titulo'=>'A inclinação', 'local'=>'Plataforma inclinada', 'texto'=>'A estrutura se inclina lentamente. Cada ajuste de postura custa energia.', 'efeito'=>'Desnível lateral'],
        ['titulo'=>'O peso do tempo', 'local'=>'Sala das engrenagens', 'texto'=>'O cronômetro não para. Os músculos começam a queimar e a base oscila sem aviso.', 'efeito'=>'Oscilação forte'],
        ['titulo'=>'Rajadas de vento', 'local'=>'Torre aberta', 'texto'=>'Um ventilador poderoso cria rajadas inesperadas. Controlar a respiração pode salvar sua posição.', 'efeito'=>'Vento lateral'],
        ['titulo'=>'O chão parece sumir', 'local'=>'Passarela suspensa', 'texto'=>'As bordas se acendem e a faixa segura fica menor. Um movimento brusco pode custar a vaga.', 'efeito'=>'Faixa estreita'],
        ['titulo'=>'Últimos segundos', 'local'=>'Plataforma final', 'texto'=>'O silêncio da casa contrasta com o alarme. Quem ainda está de pé sente o corpo no limite.', 'efeito'=>'Fadiga máxima'],
        ['titulo'=>'A última luz', 'local'=>'Palco da grande final', 'texto'=>'A luz vermelha acende pela última vez. Quem continuar equilibrado pode garantir a vaga na final.', 'efeito'=>'Precisão decisiva'],
    ];
    foreach ($bases as $i => &$ciclo) {
        $ciclo['alvo'] = random_int(27, 73);
        $ciclo['tolerancia'] = max(8, 18 - $i);
    }
    unset($ciclo);
    return $bases;
}

function top4Preparar(array $jogadores): void
{
    $rodada = (int)($_SESSION['rodada'] ?? 1);
    $anterior = $_SESSION['top4_estado'] ?? null;
    if (is_array($anterior) && (int)($anterior['rodada'] ?? -1) === $rodada
        && ($anterior['modalidade'] ?? '') === 'ultima_resistencia_v2') return;
    if (!empty($_SESSION['top4_concluido'])) return;
    // Saves no meio do Top 4 antigo migram para a prova de resistência.
    // Nenhuma vaga extra é concedida durante a migração.
    $nomes = array_values(array_filter(array_map(static fn($j) => trim((string)($j['nome'] ?? '')), $jogadores)));
    $_SESSION['top4_estado'] = [
        'modalidade'=>'ultima_resistencia_v2', 'rodada'=>$rodada,
        'etapa'=>0, 'ciclos'=>top4CriarCiclos(), 'pontos'=>array_fill_keys($nomes, 0),
        'energia'=>array_fill_keys($nomes, 100), 'foco'=>array_fill_keys($nomes, 65),
        'ativos'=>array_fill_keys($nomes, true), 'duracao'=>array_fill_keys($nomes, 0),
        'token'=>bin2hex(random_bytes(12)), 'ultimo'=>'', 'destaques'=>[], 'terminou'=>false
    ];
    $_SESSION['top4_rodada']=$rodada;
}

/** Retorna a consequência de uma decisão de resistência, sem modificar o estado. */
function top4CalcularPasso(int $etapa, int $energia, int $foco, string $postura, int $erro): array
{
    $custoBase = 6 + $etapa;
    $modificadores = [
        'conservar'=>['custo'=>-5, 'foco'=>13, 'bonus'=>1],
        'firmar'=>['custo'=>0, 'foco'=>-3, 'bonus'=>9],
        'arriscar'=>['custo'=>4, 'foco'=>-10, 'bonus'=>18],
    ];
    $mod = $modificadores[$postura] ?? $modificadores['firmar'];
    $precisao = max(0, 100 - $erro * 4);
    $penalidade = ($erro > 14 ? 8 : ($erro > 7 ? 4 : 0)) + ($foco < 25 ? 4 : 0);
    $gasto = max(2, $custoBase + $mod['custo'] + $penalidade);
    $novaEnergia = max(0, $energia - $gasto);
    $novoFoco = min(100, max(0, $foco + $mod['foco'] + ($erro <= 5 ? 2 : -3)));
    $pontos = max(1, (int)round($precisao / 4) + $mod['bonus'] + ($novoFoco >= 75 ? 3 : 0));
    return ['energia'=>$novaEnergia, 'foco'=>$novoFoco, 'pontos'=>$pontos,
        'precisao'=>$precisao, 'queda'=>$novaEnergia===0];
}

function top4AplicarPasso(array &$estado, string $nome, string $postura, int $erro): array
{
    if (empty($estado['ativos'][$nome])) return [];
    $resultado = top4CalcularPasso((int)$estado['etapa'], (int)$estado['energia'][$nome],
        (int)$estado['foco'][$nome], $postura, $erro);
    $estado['energia'][$nome] = $resultado['energia'];
    $estado['foco'][$nome] = $resultado['foco'];
    $estado['pontos'][$nome] += $resultado['pontos'];
    $estado['duracao'][$nome] = (int)$estado['etapa'] + 1;
    if ($resultado['queda']) $estado['ativos'][$nome] = false;
    return $resultado;
}

function top4RodadaNPC(array $jogadores, array &$estado, string $meuNome): void
{
    $ciclo = $estado['ciclos'][(int)$estado['etapa']];
    foreach ($jogadores as $j) {
        $nome=(string)($j['nome'] ?? '');
        if ($nome === $meuNome || !array_key_exists($nome, $estado['ativos']) || !$estado['ativos'][$nome]) continue;
        $perfil = function_exists('mb_strtolower')
            ? mb_strtolower((string)($j['personalidade'] ?? ''), 'UTF-8')
            : strtolower((string)($j['personalidade'] ?? ''));
        $habilidoso = preg_match('/competitiv|estrateg|l[ií]der|atl[eé]t|inteligent|calculista/u', $perfil) === 1;
        $valores = ['conservar','firmar','arriscar'];
        $postura = $estado['energia'][$nome] < 35 || $estado['foco'][$nome] < 25
            ? 'conservar' : $valores[random_int(0, 2)];
        $erro = random_int(1, $habilidoso ? 11 : 20);
        // Mesmo em uma prova virtual os NPCs podem falhar e cair.
        if (random_int(1,100) <= (7 + (int)$estado['etapa'] * 3)) $erro += random_int(5, 13);
        top4AplicarPasso($estado, $nome, $postura, $erro);
    }
}

/** Aplica uma rodada de forma idempotente. O token muda após cada rodada. */
function top4Avancar(array $jogadores, array &$estado, string $meuNome, array $entrada, bool $participando): bool
{
    if (!empty($estado['terminou']) || (int)($entrada['indice'] ?? -1) !== (int)$estado['etapa']
        || !hash_equals((string)$estado['token'], (string)($entrada['token'] ?? ''))) return false;
    if ($participando && !empty($estado['ativos'][$meuNome])) {
        $postura=(string)($entrada['postura']??'');
        $marcador=filter_var($entrada['marcador']??null, FILTER_VALIDATE_INT);
        if (!in_array($postura, ['conservar','firmar','arriscar'], true)
            || $marcador === false || $marcador < 0 || $marcador > 100) return false;
        $alvo=(int)$estado['ciclos'][$estado['etapa']]['alvo'];
        $resultado=top4AplicarPasso($estado, $meuNome, $postura, abs($marcador-$alvo));
        $estado['ultimo'] = $resultado['queda']
            ? '💔 Seu corpo chegou ao limite e você deixou a plataforma. Acompanhe os demais semifinalistas.'
            : ($resultado['precisao'] >= 72
                ? '✨ Movimento preciso! Você se manteve firme enquanto a plataforma oscilava.'
                : '💨 Você se segurou, mas o movimento exigiu um esforço extra.');
    } else {
        $estado['ultimo']='📺 A transmissão segue mostrando quem ainda aguenta a prova.';
    }
    $emPeAntes=array_keys(array_filter($estado['ativos']));
    top4RodadaNPC($jogadores, $estado, $meuNome);
    $ativos=array_keys(array_filter($estado['ativos']));
    $caidos=array_values(array_diff($emPeAntes, $ativos));
    if ($caidos) $estado['ultimo'] .= ' 💥 '.implode(' e ', $caidos).' não aguentou a plataforma e caiu!';
    $estado['destaques'][]=['ciclo'=>(int)$estado['etapa']+1, 'de_pe'=>$ativos];
    $estado['etapa']++;
    $estado['token']=bin2hex(random_bytes(12));
    if (count($ativos) <= 1 || $estado['etapa'] >= count($estado['ciclos'])) {
        top4Concluir($jogadores, $estado);
    }
    return true;
}

function top4Concluir(array $jogadores, array &$estado): void
{
    if (!empty($estado['terminou'])) return;
    $estado['terminou']=true;
    $nomes=array_keys($estado['pontos']);
    // Quem resistiu mais ciclos vence; entre sobreviventes, a precisão e
    // a energia restante decidem. O sorteio é somente desempate absoluto.
    $desempate=[]; foreach ($nomes as $n) $desempate[$n]=random_int(1,10000);
    usort($nomes, static function($a,$b)use($estado,$desempate){
        return ((int)$estado['duracao'][$b] <=> (int)$estado['duracao'][$a])
            ?: ((int)(bool)$estado['ativos'][$b] <=> (int)(bool)$estado['ativos'][$a])
            ?: ((int)$estado['pontos'][$b] <=> (int)$estado['pontos'][$a])
            ?: ((int)$estado['energia'][$b] <=> (int)$estado['energia'][$a])
            ?: ($desempate[$b] <=> $desempate[$a]);
    });
    $vencedor=(string)($nomes[0]??'');
    $estado['vencedor']=$vencedor;
    $_SESSION['top4_concluido']=true;
    $_SESSION['top4_vencedor']=$vencedor;
    $_SESSION['top4_rodada']=(int)($_SESSION['rodada']??1);
    $perdedores=[];
    foreach($jogadores as &$j) {
        $nome=(string)($j['nome']??'');
        $j['status']['lider']=false;
        $j['status']['imune']=($nome===$vencedor);
        if($nome!==$vencedor)$perdedores[]=$nome;
    }
    unset($j);
    $_SESSION['jogadores']=$jogadores;
    $_SESSION['paredao']=$perdedores;
    $_SESSION['lider']='';
    $_SESSION['fase_semana']='eliminacao';
    $_SESSION['ds3']=['rodada'=>(int)($_SESSION['rodada']??1),'tipo'=>''];
    if (!isset($_SESSION['evento_extra']) || !is_array($_SESSION['evento_extra'])) $_SESSION['evento_extra']=[];
    $_SESSION['evento_extra'][]='🏆 TOP 4 — '.$vencedor.' resistiu por '.(int)$estado['duracao'][$vencedor]
        .' ciclos e garantiu a primeira vaga na final! '.implode(', ', $perdedores).' disputarão o último Paredão.';
    if(function_exists('registrarHistoricoTemporada')) {
        registrarHistoricoTemporada('prova_top4','A Última Resistência',
            $vencedor.' venceu a prova de resistência. '.implode(', ',$perdedores).' foram ao Paredão.',
            array_map('strval',array_keys($estado['pontos'])),'🏆',(int)($_SESSION['rodada']??1),'top4_prova');
    }
}
