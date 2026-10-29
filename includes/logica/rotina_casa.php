<?php
/**
 * Rotina da Casa 2.0 — acontecimentos ambientais entre NPCs.
 *
 * Regras:
 * - Somente participantes ativos na casa, sem eliminados ou quarto secreto.
 * - Nenhum voto secreto, segredo ou teor de conversa privada é revelado.
 * - Cada ação válida é um gatilho único; a página renderizada não gera eventos.
 * - Limites por fase e por semana. Estado persistido em $_SESSION (saves antigos OK).
 * - Pequenas mudanças reais nas relações, memórias e traços já existentes.
 */

function rc2Ativos(array $jogadores, string $meuNome): array {
    $ativos = [];
    $falso = (string)($_SESSION['falso_eliminado'] ?? '');
    $secreto = !empty($_SESSION['paredao_falso_ativo']);
    foreach ($jogadores as $j) {
        $n = (string)($j['nome'] ?? '');
        if ($n === '' || $n === $meuNome || !empty($j['eliminado']) || !empty($j['fora_da_casa'])
            || ($secreto && $n === $falso)) continue;
        $ativos[$n] = $j;
    }
    return $ativos;
}

/** O protagonista só participa dos momentos espontâneos enquanto está na casa. */
function rc2JogadorDisponivel(array $jogadores, string $meuNome): bool {
    if ($meuNome === '' || !empty($_SESSION['modo_espectador'])) return false;
    foreach ($jogadores as $j) {
        if (($j['nome'] ?? '') !== $meuNome) continue;
        if (!empty($j['eliminado']) || !empty($j['fora_da_casa'])) return false;
        if (!empty($_SESSION['paredao_falso_ativo']) &&
            (string)($_SESSION['falso_eliminado'] ?? '') === $meuNome) return false;
        return true;
    }
    return false;
}

/** Cenas cotidianas do protagonista: nada de alianças, flertes ou escolhas impostas. */
function rc2EscolherTipoJogador(float $relacao, string $fase, array $historico): string {
    if ($fase === 'festa') {
        $pesos = $relacao < -20
            ? ['papo_discreto'=>2,'distancia'=>5,'brinde'=>1]
            : ['festanca'=>4,'brinde'=>3,'papo_discreto'=>3];
    } elseif ($relacao < -20) {
        $pesos = ['distancia'=>6,'tarefa_tensa'=>2,'papo_discreto'=>1];
    } else {
        $pesos = ['cafe'=>3,'almoco'=>2,'limpeza'=>2,'treino'=>2,'piscina'=>3,
            'conselho'=>3,'jogo_leve'=>3,'papo_discreto'=>2];
    }
    $vistos = array_column(array_slice($historico, -5), 'tipo');
    foreach ($pesos as $tipo => &$peso) {
        if (in_array($tipo, $vistos, true)) $peso = max(1, (int)floor($peso/3));
    }
    unset($peso);
    $sorte = random_int(1, array_sum($pesos));
    foreach ($pesos as $tipo=>$peso) { $sorte -= $peso; if ($sorte <= 0) return $tipo; }
    return array_key_first($pesos);
}
function rc2TextoJogador(string $tipo, string $npc): string {
    $textos = [
        'cafe'=>['Você e %s dividiram um café na cozinha, conversando sobre a rotina.',
                 '%s passou pela cozinha enquanto você preparava café. Vocês ficaram conversando por alguns minutos.'],
        'almoco'=>['Você e %s ajudaram nas tarefas do almoço e o ambiente ficou mais leve.',
                   'Você e %s dividiram a preparação da refeição em meio a uma conversa descontraída.'],
        'limpeza'=>['Você e %s dividiram a arrumação da casa sem grandes dificuldades.',
                    '%s apareceu para ajudar enquanto você organizava a cozinha.'],
        'treino'=>['Você e %s coincidiram na academia e trocaram dicas durante o treino.',
                   '%s conversou com você durante uma pausa na academia.'],
        'piscina'=>['Você e %s passaram um tempo junto à piscina, conversando sobre o dia.',
                    '%s se aproximou enquanto você descansava perto da piscina. Vocês conversaram um pouco.'],
        'conselho'=>['%s percebeu que você estava em silêncio e ofereceu companhia.',
                     '%s te procurou para saber como você estava se sentindo naquela semana.'],
        'jogo_leve'=>['Você e %s participaram de uma brincadeira improvisada na sala.',
                      '%s entrou numa brincadeira com você e vocês se divertiram sem pensar no jogo.'],
        'papo_discreto'=>['%s chamou você para conversar num canto tranquilo da casa. O assunto ficou entre vocês.',
                          'Você e %s tiveram uma conversa reservada, sem que outros participantes ouvissem.'],
        'distancia'=>['Você e %s passaram um tempo no mesmo ambiente, mas quase não conversaram.',
                      'O distanciamento entre você e %s ficou perceptível durante a convivência.'],
        'tarefa_tensa'=>['%s reclamou da divisão das tarefas enquanto você estava por perto. O clima ficou desconfortável.',
                         'Você e %s tiveram uma breve discordância sobre a organização da casa.'],
        'festanca'=>['Você e %s se divertiram juntos na pista de dança por algumas músicas.',
                     '%s se juntou a você durante a festa e vocês entraram no clima da música.'],
        'brinde'=>['%s se aproximou para brindar com você durante a Festa.',
                   'Você e %s conversaram perto do bar e fizeram um brinde pela semana.'],
    ];
    $opcoes = $textos[$tipo] ?? $textos['cafe'];
    return sprintf($opcoes[random_int(0, count($opcoes)-1)], htmlspecialchars($npc, ENT_QUOTES, 'UTF-8'));
}

function rc2Pares(array $ativos): array {
    $nomes = array_keys($ativos);
    $pares = [];
    for ($i = 0; $i < count($nomes); $i++) {
        for ($k = $i + 1; $k < count($nomes); $k++) {
            $pares[] = [$nomes[$i], $nomes[$k]];
        }
    }
    return $pares;
}

function rc2Cenas(): array {
    // Cada cena é pública ou mostra apenas o comportamento observável.
    // rel: mudanças leves de amizade, rivalidade e confiança (A→B e B→A).
    return [
        'cafe' => ['emoji'=>'☕','locais'=>['cozinha','área externa'], 'rel'=>[3,-1,2], 'memoria'=>'me_aproximou',
            'textos'=>['%s preparou café para %s, e os dois ficaram conversando sobre a rotina na cozinha.',
                      '%s e %s dividiram um café enquanto organizavam as tarefas do dia.',
                      '%s chamou %s para tomar café e o clima entre eles ficou mais leve.']],
        'almoco' => ['emoji'=>'🍳','locais'=>['cozinha'], 'rel'=>[4,-1,2], 'memoria'=>'me_aproximou',
            'textos'=>['%s e %s improvisaram o almoço juntos; entre risadas, aprenderam a trabalhar em equipe.',
                      '%s ajudou %s a preparar a refeição, e a parceria aproximou os dois.',
                      '%s e %s dividiram as tarefas da cozinha e terminaram o almoço sem estresse.']],
        'limpeza' => ['emoji'=>'🧹','locais'=>['cozinha','quarto','sala'], 'rel'=>[3,-1,2], 'memoria'=>'me_apoiou',
            'textos'=>['%s ajudou %s a arrumar a casa sem que ninguém precisasse pedir.',
                      '%s e %s organizaram o quarto juntos, deixando o clima mais tranquilo.',
                      '%s assumiu uma parte das tarefas para aliviar a rotina de %s.']],
        'treino' => ['emoji'=>'🏋️','locais'=>['academia','jardim'], 'rel'=>[3,-1,2], 'memoria'=>'me_aproximou',
            'textos'=>['%s e %s treinaram juntos na academia e trocaram dicas de concentração.',
                      '%s acompanhou %s num treino leve; o momento ajudou a aliviar a tensão da semana.',
                      '%s e %s aproveitaram a academia para incentivar um ao outro.']],
        'piscina' => ['emoji'=>'🏊','locais'=>['piscina'], 'rel'=>[3,-1,2], 'memoria'=>'me_aproximou',
            'textos'=>['%s e %s ficaram à beira da piscina conversando sobre a saudade de casa.',
                      '%s e %s se divertiram na piscina e passaram um tempo longe das discussões.',
                      '%s chamou %s para descansar junto à piscina e os dois acabaram rindo da rotina.']],
        'conselho' => ['emoji'=>'💙','locais'=>['varanda','jardim','quarto'], 'rel'=>[4,-2,3], 'memoria'=>'me_apoiou',
            'textos'=>['%s percebeu que %s estava em silêncio e ofereceu companhia na varanda.',
                      '%s procurou %s para dar apoio depois de um dia cansativo na casa.',
                      '%s ouviu o desabafo de %s, sem pressionar por explicações.']],
        'reconciliacao' => ['emoji'=>'🤝','locais'=>['varanda','cozinha','jardim'], 'rel'=>[6,-5,4], 'memoria'=>'cumpriu_tregua',
            'textos'=>['%s chamou %s para conversar sobre um atrito recente; os dois conseguiram baixar o tom.',
                      '%s e %s tentaram deixar uma discussão para trás numa conversa sincera.',
                      '%s se aproximou de %s e pediu para recomeçarem a convivência com mais respeito.']],
        'tarefa_tensa' => ['emoji'=>'😤','locais'=>['cozinha','quarto','sala'], 'rel'=>[-3,4,-2], 'memoria'=>'brigou_comigo',
            'textos'=>['%s cobrou %s pela divisão das tarefas e os dois trocaram algumas farpas.',
                      '%s e %s discordaram sobre a organização da casa; a conversa ficou desconfortável.',
                      '%s reclamou de uma tarefa deixada para trás por %s, e o clima pesou.']],
        'provocacao' => ['emoji'=>'🔥','locais'=>['cozinha','varanda','sala'], 'rel'=>[-4,5,-3], 'memoria'=>'brigou_comigo',
            'textos'=>['%s provocou %s por uma atitude recente e recebeu uma resposta atravessada.',
                      '%s e %s discutiram ao lembrar de uma situação mal resolvida na casa.',
                      '%s confrontou %s durante uma conversa na sala, e outros participantes perceberam a tensão.']],
        'distancia' => ['emoji'=>'🧊','locais'=>['jardim','quarto'], 'rel'=>[-1,1,-1], 'memoria'=>null,
            'textos'=>['%s e %s passaram o dia se evitando depois de uma sequência de desentendimentos.',
                      '%s preferiu não prolongar uma conversa com %s; o distanciamento ficou perceptível.',
                      '%s e %s dividiram o mesmo ambiente, mas quase não trocaram palavras.']],
        'jogo_leve' => ['emoji'=>'🎲','locais'=>['sala','jardim'], 'rel'=>[3,-1,2], 'memoria'=>'me_aproximou',
            'textos'=>['%s e %s inventaram uma brincadeira na sala e animaram quem estava por perto.',
                      '%s desafiou %s para um jogo improvisado, e os dois levaram a disputa na esportiva.',
                      '%s e %s passaram um tempo brincando e esqueceram um pouco a pressão do confinamento.']],
        'papo_discreto' => ['emoji'=>'👀','locais'=>['varanda','jardim','quarto'], 'rel'=>[1,0,1], 'memoria'=>null,
            'textos'=>['%s e %s conversaram baixinho na varanda; não foi possível ouvir o assunto.',
                      '%s chamou %s para um canto tranquilo do jardim. Os dois conversaram longe dos demais.',
                      '%s e %s foram vistos em um papo reservado, mas ninguém sabe sobre o que conversaram.']],
        'festanca' => ['emoji'=>'🎉','locais'=>['pista de dança'], 'rel'=>[4,-1,2], 'memoria'=>'me_aproximou',
            'textos'=>['%s puxou %s para a pista de dança; os dois passaram várias músicas se divertindo.',
                      '%s e %s fizeram uma coreografia improvisada e arrancaram risadas na Festa.',
                      '%s chamou %s para dançar, e o clima ficou mais descontraído.']],
        'brinde' => ['emoji'=>'🥂','locais'=>['bar','área externa'], 'rel'=>[3,-1,2], 'memoria'=>'me_aproximou',
            'textos'=>['%s e %s fizeram um brinde pela semana e ficaram conversando na área externa.',
                      '%s elogiou a postura de %s durante a Festa; os dois brindaram juntos.',
                      '%s e %s conversaram ao lado do bar e se despediram com um abraço.']],
        'festa_tensao' => ['emoji'=>'💥','locais'=>['pista de dança','bar'], 'rel'=>[-4,5,-3], 'memoria'=>'brigou_comigo',
            'textos'=>['%s cobrou uma postura de %s durante a Festa; a conversa terminou em discussão.',
                      '%s e %s trocaram indiretas perto da pista e atraíram olhares.',
                      '%s se irritou com um comentário de %s durante a Festa e os dois se afastaram.']],
        'festa_paz' => ['emoji'=>'🌙','locais'=>['área externa','piscina'], 'rel'=>[5,-3,3], 'memoria'=>'me_aproximou',
            'textos'=>['%s e %s se encontraram num canto tranquilo da Festa e resolveram conversar sem brigar.',
                      '%s pediu a %s para deixarem a tensão de lado pelo menos naquela noite.',
                      '%s e %s aproveitaram a Festa para reconstruir um pouco da confiança.']],
    ];
}

/** Sorteia um evento compatível com relação e ambiente, evitando padrões recentes. */
function rc2EscolherTipo(float $relacao, string $fase, array $ator, array $historico): string {
    $festa = $fase === 'festa';
    if ($festa) {
        $pesos = ['festanca'=>4, 'brinde'=>3, 'papo_discreto'=>2, 'festa_tensao'=>1, 'festa_paz'=>1];
        if ($relacao < -20) { $pesos['festa_tensao'] = 7; $pesos['festa_paz'] = 2; $pesos['brinde'] = 1; }
        if ($relacao > 35) { $pesos['brinde'] = 6; $pesos['festanca'] = 6; $pesos['festa_tensao'] = 0; }
    } elseif ($relacao < -20) {
        $pesos = ['tarefa_tensa'=>4,'provocacao'=>4,'distancia'=>4,'reconciliacao'=>2,'papo_discreto'=>1];
    } elseif ($relacao > 35) {
        $pesos = ['cafe'=>3,'almoco'=>3,'limpeza'=>2,'treino'=>2,'piscina'=>3,'conselho'=>4,'jogo_leve'=>3,'papo_discreto'=>1];
    } else {
        $pesos = ['cafe'=>2,'almoco'=>2,'limpeza'=>2,'treino'=>2,'piscina'=>2,'conselho'=>2,'reconciliacao'=>1,'tarefa_tensa'=>2,'jogo_leve'=>2,'papo_discreto'=>2];
    }
    $personalidade = (string)($ator['personalidade'] ?? 'Neutro');
    if (in_array($personalidade, ['Explosivo','Barraqueiro'], true)) {
        foreach (['provocacao','tarefa_tensa','festa_tensao'] as $t) if (isset($pesos[$t])) $pesos[$t] *= 2;
    }
    if (in_array($personalidade, ['Fofo','Emocional'], true)) {
        foreach (['conselho','reconciliacao','brinde','festa_paz'] as $t) if (isset($pesos[$t])) $pesos[$t] *= 2;
    }
    // Não reciclar o tipo que acabou de aparecer, salvo caso sem alternativa.
    $vistos = array_column(array_slice($historico, -5), 'tipo');
    foreach ($pesos as $tipo => &$peso) if (in_array($tipo, $vistos, true)) $peso = max(0, (int)floor($peso / 4));
    unset($peso);
    $total = array_sum($pesos);
    if ($total < 1) return $festa ? 'festanca' : 'cafe';
    $sorte = random_int(1, $total);
    foreach ($pesos as $tipo=>$peso) {
        $sorte -= $peso;
        if ($sorte <= 0) return $tipo;
    }
    return array_key_first($pesos);
}

/**
 * Executa um evento espontâneo em um gatilho de ação real.
 * O mesmo gatilho não volta a executar mesmo após refresh/post repetido.
 */
function rc2ProcessarMomento(array &$jogadores, string $meuNome, string $fase, string $gatilho, bool $garantir = false): bool {
    if (!(str_starts_with($fase, 'interacoes') || $fase === 'festa')) return false;
    if (!isset($_SESSION['rc2_estado']) || !is_array($_SESSION['rc2_estado'])) $_SESSION['rc2_estado'] = [];
    $semana = (int)($_SESSION['rodada'] ?? 1);
    $chave = $semana . '|' . $fase;
    $reg =& $_SESSION['rc2_estado'];
    if (!isset($reg['gatilhos']) || !is_array($reg['gatilhos'])) $reg['gatilhos'] = [];
    $chaveGatilho = $chave . '|' . $gatilho;
    if (isset($reg['gatilhos'][$chaveGatilho])) { unset($reg); return false; }
    $reg['gatilhos'][$chaveGatilho] = true;
    if (!isset($reg['por_fase']) || !is_array($reg['por_fase'])) $reg['por_fase'] = [];
    if (!isset($reg['por_semana']) || !is_array($reg['por_semana'])) $reg['por_semana'] = [];
    if (!isset($reg['historico']) || !is_array($reg['historico'])) $reg['historico'] = [];
    if ((int)($reg['por_fase'][$chave] ?? 0) >= 2 || (int)($reg['por_semana'][$semana] ?? 0) >= 7) { unset($reg); return false; }
    if (!$garantir && random_int(1, 100) > ($fase === 'festa' ? 55 : 50)) { unset($reg); return false; }

    $ativos = rc2Ativos($jogadores, $meuNome);
    if (count($ativos) < 1) { unset($reg); return false; }
    $pares = rc2Pares($ativos);
    // Não repetir a mesma dupla logo em seguida.
    $recentes = array_column(array_slice($reg['historico'], -7), 'par');
    $alternativos = array_values(array_filter($pares, static function($par) use($recentes) {
        $ordenado = $par; sort($ordenado, SORT_STRING);
        return !in_array(implode('|',$ordenado),$recentes,true);
    }));
    if ($alternativos) $pares = $alternativos;
    // Até duas aparições do protagonista por semana e uma por fase; cerca de 35% dos momentos.
    // Nunca entra em acontecimentos automáticos se estiver eliminado ou no Quarto Secreto.
    $comJogador = rc2JogadorDisponivel($jogadores, $meuNome)
        && (int)($reg['protagonista_semana'][$semana] ?? 0) < 2
        && empty($reg['protagonista_fase'][$chave])
        && (count($pares) === 0 || random_int(1,100) <= 35);
    if ($comJogador) {
        $possiveis = array_keys($ativos);
        $naoRecentes = array_values(array_filter($possiveis, static function($nome) use ($meuNome, $recentes) {
            $ch = [$meuNome, $nome]; sort($ch, SORT_STRING);
            return !in_array(implode('|', $ch), $recentes, true);
        }));
        if ($naoRecentes) $possiveis = $naoRecentes;
        $a = $meuNome;
        $b = $possiveis[array_rand($possiveis)];
        $rela = (float)($_SESSION['relacoes_jogador'][$b] ?? 0);
        $tipo = rc2EscolherTipoJogador($rela, $fase, $reg['historico']);
    } else {
        if (!$pares) { unset($reg); return false; }
        [$a, $b] = $pares[array_rand($pares)];
        $rela = 0.0;
        if (function_exists('calcularRelacaoIA')) {
            $rela = ((float)calcularRelacaoIA($jogadores,$a,$b,$meuNome) + (float)calcularRelacaoIA($jogadores,$b,$a,$meuNome)) / 2;
        }
        // Alianças existentes protegem convivência, mas não devem ser reveladas na narração.
        if (($ativos[$a]['alianca'] ?? null) && ($ativos[$a]['alianca'] ?? null) === ($ativos[$b]['alianca'] ?? null)) $rela += 15;
        $tipo = rc2EscolherTipo($rela, $fase, $ativos[$a], $reg['historico']);
    }
    $cenas = rc2Cenas();
    $cena = $cenas[$tipo];
    $indice = random_int(0, count($cena['textos']) - 1);
    $conteudo = $comJogador
        ? rc2TextoJogador($tipo, $b)
        : sprintf($cena['textos'][$indice], htmlspecialchars($a, ENT_QUOTES, 'UTF-8'), htmlspecialchars($b, ENT_QUOTES, 'UTF-8'));
    if (!$comJogador && function_exists('generoParticipanteBBB')
        && generoParticipanteBBB($a, $jogadores) === 'f'
        && generoParticipanteBBB($b, $jogadores) === 'f') {
        $conteudo = str_replace(['os dois', 'juntos', 'entre eles'], ['as duas', 'juntas', 'entre elas'], $conteudo);
    }
    $local = $cena['locais'][array_rand($cena['locais'])];
    $texto = $cena['emoji'] . ' <strong>' . htmlspecialchars(ucfirst($local), ENT_QUOTES, 'UTF-8') . ':</strong> ' . $conteudo;

    [$amizade, $rivalidade, $confianca] = $cena['rel'];
    if ($comJogador) {
        // Só as percepções do NPC mudam automaticamente. Não escolher sentimentos pelo jogador.
        if (function_exists('alterarAfinidade')) alterarAfinidade($jogadores, $b, $a, $amizade, $rivalidade, $confianca);
        if (function_exists('ajustarRelacaoJogador')) {
            ajustarRelacaoJogador($b, max(-3, min(3, (int)round(($amizade - $rivalidade + $confianca) / 3))));
        }
    } elseif (function_exists('alterarAfinidade')) {
        alterarAfinidade($jogadores, $a, $b, $amizade, $rivalidade, $confianca);
        alterarAfinidade($jogadores, $b, $a, $amizade, $rivalidade, $confianca);
    }
    $numero = (int)($reg['por_semana'][$semana] ?? 0) + 1;
    // NPCs guardam apenas o que realmente presenciaram; não atribuir favores inexistentes ao protagonista.
    if ($comJogador && function_exists('registrarMemoriaSocialNPC') && in_array($tipo, ['cafe','almoco','treino','piscina','jogo_leve','festanca','brinde','brinde'],true)) {
        registrarMemoriaSocialNPC($b,$a,'me_aproximou',1,"$b passou um momento de convivência com $a no $local.",
            'rc2|' . $semana . '|' . $fase . '|' . $numero . '|jogador');
    }
    if (!$comJogador && !empty($cena['memoria']) && function_exists('registrarMemoriaSocialNPC')) {
        $memoria = $cena['memoria'];
        registrarMemoriaSocialNPC($b,$a,$memoria,1,"$a e $b viveram um momento de convivência no $local.",'rc2|' . $semana . '|' . $fase . '|' . $numero . '|b');
        // Em cenas de apoio, quem recebeu a ajuda guarda a lembrança;
        // não presumir que quem ajudou também recebeu o mesmo favor.
        if (!in_array($tipo, ['limpeza', 'conselho'], true)) {
            registrarMemoriaSocialNPC($a,$b,$memoria,1,"$a e $b viveram um momento de convivência no $local.",'rc2|' . $semana . '|' . $fase . '|' . $numero . '|a');
        }
    }
    // Relações 4.0 acompanha o mesmo acontecimento, sem inventar outro evento.
    if (!$comJogador && function_exists('r4AposRotina')) r4AposRotina($jogadores, $a, $b, $tipo);
    if (function_exists('registrarRelacaoMarcante')) {
        registrarRelacaoMarcante($jogadores, $a, $b);
        registrarRelacaoMarcante($jogadores, $b, $a);
    }
    if (!isset($_SESSION['evento_extra']) || !is_array($_SESSION['evento_extra'])) $_SESSION['evento_extra'] = [];
    $_SESSION['evento_extra'][] = $texto;
    $reg['por_fase'][$chave] = (int)($reg['por_fase'][$chave] ?? 0) + 1;
    $reg['por_semana'][$semana] = $numero;
    if ($comJogador) {
        $reg['protagonista_semana'][$semana] = (int)($reg['protagonista_semana'][$semana] ?? 0) + 1;
        $reg['protagonista_fase'][$chave] = true;
    }
    $ordenado = [$a,$b]; sort($ordenado,SORT_STRING);
    $reg['historico'][] = ['semana'=>$semana,'fase'=>$fase,'tipo'=>$tipo,'par'=>implode('|',$ordenado),'local'=>$local,'texto'=>$texto];
    $reg['historico'] = array_slice($reg['historico'], -36);
    $reg['gatilhos'] = array_slice($reg['gatilhos'], -90, null, true);
    $_SESSION['jogadores'] = $jogadores;
    unset($reg);
    return true;
}

function rc2Recentes(int $limite = 3): array {
    $historico = (array)($_SESSION['rc2_estado']['historico'] ?? []);
    return array_reverse(array_slice($historico, -max(1,min(8,$limite))));
}
