<?php
require_once __DIR__ . '/gramatica.php';
/**
 * Bate-Papo com o Eliminado — entrevistas congeladas na saída e biblioteca
 * disponível exclusivamente após eliminação real do jogador.
 * Não consulta popularidade para selecionar respostas nem inventa votos/VTs.
 */
function bpEsc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function bpNomeIgual(string $a, string $b): bool {
    return function_exists('nomeIgual') ? nomeIgual($a, $b) : strtolower(trim($a)) === strtolower(trim($b));
}

function bpSemanaSaida(string $nome): int {
    foreach ((array)($_SESSION['historico_temporada'] ?? []) as $ev) {
        if (($ev['tipo'] ?? '') !== 'eliminacao') continue;
        foreach ((array)($ev['participantes'] ?? []) as $p) {
            if (bpNomeIgual($p, $nome)) return max(1, (int)($ev['rodada'] ?? 1));
        }
    }
    return max(1, (int)($_SESSION['rodada'] ?? 1));
}

function bpEliminadoReal(string $nome): bool {
    foreach ((array)($_SESSION['historico_eliminados'] ?? []) as $saiu) {
        if (bpNomeIgual((string)$saiu, $nome)) return true;
    }
    return false;
}

function bpSnapshot(string $nome): array {
    foreach ((array)($_SESSION['participantes_eliminados_dados'] ?? []) as $j) {
        if (is_array($j) && bpNomeIgual((string)($j['nome'] ?? ''), $nome)) return $j;
    }
    $meu = (array)($_SESSION['meu_jogador_snapshot'] ?? []);
    return bpNomeIgual((string)($meu['nome'] ?? ''), $nome) ? $meu : [];
}

function bpRelacoes(array $j, string $nome): array {
    $melhor = ['', -9999.0]; $pior = ['', 9999.0]; $romance = ['', 0.0];
    foreach ((array)($j['relacoes'] ?? []) as $outro => $val) {
        if (!is_array($val) || bpNomeIgual((string)$outro, $nome)) continue;
        $nota = (float)($val['amizade'] ?? 0) + .7 * (float)($val['confianca'] ?? 0) - 1.2 * (float)($val['rivalidade'] ?? 0);
        if ($nota > $melhor[1]) $melhor = [(string)$outro, $nota];
        if ($nota < $pior[1]) $pior = [(string)$outro, $nota];
    }
    foreach ((array)($j['romances'] ?? []) as $outro => $val) {
        if (!bpNomeIgual((string)$outro, $nome) && (float)$val > $romance[1]) $romance = [(string)$outro, (float)$val];
    }
    return ['amigo' => $melhor[1] >= 20 ? $melhor[0] : '', 'rival' => $pior[1] <= -10 ? $pior[0] : '',
        'romance' => $romance[1] >= 30 ? $romance[0] : '', 'alianca' => (string)($j['alianca'] ?? '')];
}

function bpEventoPublico(string $nome, int $ate): ?array {
    // Lista positiva: evita registros de segredos, confessionários e votos não revelados.
    $tipos = ['lider','anjo','monstro','paredao','eliminacao','discussao','treta','festa','prova','vitoria_lider', 'vitoria_anjo', 'discordia'];
    $eventos = (array)($_SESSION['historico_temporada'] ?? []);
    for ($i = count($eventos) - 1; $i >= 0; $i--) {
        $ev = $eventos[$i];
        if (!in_array((string)($ev['tipo'] ?? ''), $tipos, true) || (int)($ev['rodada'] ?? 0) > $ate) continue;
        $nomes = (array)($ev['participantes'] ?? []);
        foreach ($nomes as $p) {
            if (bpNomeIgual((string)$p, $nome) && !empty($ev['descricao']))
                return ['semana' => (int)$ev['rodada'], 'descricao' => (string)$ev['descricao'], 'titulo' => (string)($ev['titulo'] ?? 'Momento da edição')];
        }
    }
    return null;
}

function bpVTConfessionarioVerificado(string $nome, array $jogadores): ?array {
    // A entrevista acontece DEPOIS da eliminação real. Só usa depoimentos
    // efetivamente gravados antes da saída, nunca textos fabricados.
    $falas = [];
    foreach ($jogadores as $npc) {
        $autor = trim((string)($npc['nome'] ?? ''));
        if ($autor === '' || bpNomeIgual($autor, $nome)) continue;
        foreach ((array)($npc['confessionarios'] ?? []) as $fala) {
            $fala = trim(strip_tags((string)$fala));
            if ($fala !== '' && stripos($fala, $nome) !== false) {
                $falas[] = ['autor' => $autor, 'fala' => $fala];
            }
        }
    }
    return $falas ? $falas[abs(crc32($nome.'|'.count($falas))) % count($falas)] : null;
}

function bpFlexionarFala(string $texto, string $nome, array $dados): string {
    $g = generoParticipanteBBB($nome, [$dados]);
    if ($g !== 'f') return $texto;
    $trocas = ['bem-vindo'=>'bem-vinda', 'pego de surpresa'=>'pega de surpresa',
        'sou grato'=>'sou grata', 'lembrado'=>'lembrada', 'parado'=>'parada',
        'ansioso'=>'ansiosa', 'quieto'=>'quieta', 'obrigado'=>'obrigada'];
    return strtr($texto, $trocas);
}

function bpPergunta(string $id, string $titulo, string $texto, array $opcoes): array {
    return ['id' => $id, 'titulo' => $titulo, 'pergunta' => $texto, 'opcoes' => $opcoes];
}
function bpOpt(string $id, string $label, string $fala): array {
    return ['id' => $id, 'label' => $label, 'fala' => $fala];
}

function bpMontarPerguntas(string $nome, array $j, int $semana): array {
    $r = bpRelacoes($j, $nome);
    $stats = (array)($j['estatisticas'] ?? []);
    $lider = (int)($stats['lider'] ?? 0); $paredoes = (int)($stats['paredao'] ?? 0);
    $p = [];
    $p[] = bpPergunta('saida', '🚪 A saída', "$nome, seja bem-vindo ao estúdio! Você esperava deixar a casa na Semana $semana?", [
        bpOpt('surpresa','😳 Fui pego de surpresa','Cara, não vou mentir: eu achava que ainda tinha muita coisa para viver lá dentro.'),
        bpOpt('calma','💜 Estou em paz com minha trajetória','Claro que queria ficar, mas saio de cabeça erguida. Vivi tudo com intensidade.'),
        bpOpt('jogo','🎯 Faltou estratégia','Acho que algumas escolhas pesaram. Agora aqui fora consigo olhar para meu jogo de outro jeito.'),
        bpOpt('firme','🔥 Ainda tinha muito para mostrar','Eu queria continuar. Não acho que minha história terminava agora, mas respeito o resultado.')
    ]);
    if ($r['amigo'] !== '') {
        $amigo = $r['amigo'];
        $p[] = bpPergunta('amizade','🤝 Laços da casa',"Você se aproximou de $amigo. Como vai guardar essa relação?", [
            bpOpt('carinho','❤️ Quero levar a amizade para fora',"Com $amigo vivi momentos importantes. Quero muito manter essa amizade aqui fora."),
            bpOpt('cuidado','👀 Ainda preciso conversar',"Eu gosto de $amigo, mas tem coisa que a gente só entende depois de sair da casa."),
            bpOpt('gratidao','🙏 Sou grato pela companhia',"A companhia de $amigo fez diferença nos meus dias. Foi alguém importante para mim.")
        ]);
    } elseif ($r['alianca'] !== '') {
        $p[] = bpPergunta('alianca','🤝 Jogo em grupo',"Você fez parte de {$r['alianca']}. O que ficou dessa experiência?", [
            bpOpt('apoio','🤝 O grupo me ajudou', 'Jogar junto foi importante. Ter com quem contar fez diferença em várias semanas.'),
            bpOpt('prudencia','👀 Confiei com cautela', 'Aprendi que aliança ajuda, mas a gente também precisa pensar por conta própria.'),
            bpOpt('foco','🎯 Eu tinha meu jogo', 'Gostava do grupo, mas sempre tentei manter minhas próprias prioridades.')
        ]);
    }
    if ($r['rival'] !== '') {
        $rival = $r['rival'];
        $p[] = bpPergunta('rival','🔥 Conflitos e diferenças',"Sua relação com $rival teve momentos difíceis. Como você vê isso agora?", [
            bpOpt('superar','🕊️ Deixar para trás',"Com $rival tivemos atritos, mas eu não quero carregar isso aqui fora."),
            bpOpt('limites','🧊 Manter distância',"Respeito $rival, mas prefiro manter uma certa distância depois de tudo."),
            bpOpt('jogo','🎯 Era jogo','A gente discordava muito, mas lá dentro tudo ganha outra proporção. Faz parte.')
        ]);
    }
    if ($r['romance'] !== '') {
        $par = $r['romance'];
        $p[] = bpPergunta('romance','💞 Assuntos do coração',"Rolou uma aproximação com $par. Você acha que isso pode continuar aqui fora?", [
            bpOpt('esperanca','💗 Quero conversar aqui fora',"Eu e $par criamos uma conexão. Quero ver com calma o que acontece fora da casa."),
            bpOpt('incerto','💭 Ainda não sei',"Foi intenso com $par, mas aqui fora é outra realidade. Prefiro não prometer nada."),
            bpOpt('carinho','🤍 Guardar com carinho',"O que eu vivi com $par foi verdadeiro naquele momento, independentemente do futuro.")
        ]);
    }
    $p[] = bpPergunta('jogo','🎯 Estratégia e escolhas', $lider > 0
        ? "Você conquistou $lider liderança(s). Que marca você acredita ter deixado no jogo?"
        : "Sem depender de uma liderança, o que você acha que mais definiu seu jogo?", [
        bpOpt('estrategia','🧠 Minhas decisões','Eu tentei entender as pessoas antes de agir. Nem sempre acertei, mas cada decisão foi pensada.'),
        bpOpt('convivencia','🤝 Minha convivência','O mais importante foi a forma como convivi. O jogo muda, mas as relações ficam.'),
        bpOpt('coragem','🔥 Ter me posicionado','Eu fui me posicionando, mesmo sabendo que poderia desagradar algumas pessoas.'),
        bpOpt('aprendizado','🌱 Aprendi com os erros','Mudaria algumas coisas, mas hoje sei mais sobre mim do que quando entrei.')
    ]);
    if ($paredoes > 0) {
        $p[] = bpPergunta('paredao','🔥 O peso da berlinda',"Você enfrentou $paredoes Paredão(ões). Qual foi a parte mais difícil?", [
            bpOpt('ansiedade','😰 A espera','Não saber o que estava acontecendo lá fora me deixava muito ansioso. Essa espera é pesada.'),
            bpOpt('saudade','💭 Pensar em quem ficou','O pior era imaginar quem eu teria de deixar lá dentro e todas as conversas que ficariam pela metade.'),
            bpOpt('forca','💪 Continuar firme','Cada Paredão me fez entender que precisava aproveitar o tempo que ainda tinha.')
        ]);
    }
    $evento = bpEventoPublico($nome, $semana);
    if ($evento) {
        $p[] = bpPergunta('vt','📺 Relembrando a edição',"A produção separou um registro público da Semana {$evento['semana']}: “{$evento['descricao']}”. O que você lembra desse momento?", [
            bpOpt('marcante','✨ Foi marcante','Esse foi um dos momentos que me fizeram perceber o tamanho dessa experiência.'),
            bpOpt('contexto','🧩 Existia muita coisa envolvida','Quem viu de fora pegou um pedaço da história. Lá dentro a emoção era muito maior.'),
            bpOpt('saudade','🥹 Já bate saudade','Ver isso agora dá um aperto no peito. Parece que foi ontem.')
        ]);
    }
    $p[] = bpPergunta('convivencia', '🏠 Viver o confinamento', 'Como foi dividir a casa e a rotina com pessoas tão diferentes?', [
        bpOpt('aprendizado','🌱 Aprendi muito', 'Conviver assim me fez perceber coisas sobre mim que eu nem imaginava.'),
        bpOpt('dificuldade','😮‍💨 Foi intenso', 'Teve dia em que eu só queria ficar quieto. Não é fácil viver tudo ali vinte e quatro horas por dia.'),
        bpOpt('amizades','💜 Ficam as relações', 'O maior presente foi conhecer pessoas que talvez eu nunca encontrasse aqui fora.')
    ]);
    $p[] = bpPergunta('arrependimentos', '💭 Olhando para trás', 'Se pudesse voltar a uma decisão da sua trajetória, mudaria alguma coisa?', [
        bpOpt('sim','🎯 Algumas escolhas, sim', 'Eu mudaria certas atitudes. Lá dentro a gente decide na emoção e só depois entende o peso.'),
        bpOpt('nao','💪 Faria tudo de novo', 'Eu assumo minhas escolhas. Foram elas que construíram a minha história naquela casa.'),
        bpOpt('aprender','🧠 Faria com mais calma', 'Não sei se mudaria o resultado, mas pensaria com mais calma antes de tomar algumas decisões.')
    ]);
    $p[] = bpPergunta('legado','🏆 O que fica',"Para encerrar: que lembrança você gostaria que as pessoas levassem da sua participação?", [
        bpOpt('verdade','💜 Minha verdade','Queria ser lembrado por ter sido eu mesmo, mesmo nos dias mais difíceis.'),
        bpOpt('jogo','🎯 Minha coragem de jogar','Não entrei para ficar parado. Espero que lembrem que eu tentei jogar até o fim.'),
        bpOpt('afeto','🤝 As pessoas que conheci','O prêmio era o objetivo, mas conhecer tanta gente diferente foi uma parte enorme da história.'),
        bpOpt('crescer','🌱 Como eu evoluí','Entrei de um jeito e estou saindo de outro. Vou levar muito aprendizado daqui.')
    ]);
    // 5 a 7 perguntas, equilibrando blocos contextuais e mantendo o encerramento.
    if (count($p) > 7) {
        $fim = array_pop($p); $p = array_slice($p, 0, 6); $p[] = $fim;
    }
    return $p;
}

function bpPreparar(string $nome, array $snapshot, ?int $semana = null, bool $jogador = false, ?array $vtReal = null): ?array {
    if ($nome === '' || empty($snapshot) || !bpEliminadoReal($nome)) return null;
    if (!isset($_SESSION['bp_entrevistas']) || !is_array($_SESSION['bp_entrevistas'])) $_SESSION['bp_entrevistas'] = [];
    if (isset($_SESSION['bp_entrevistas'][$nome])) return $_SESSION['bp_entrevistas'][$nome];
    $semana = max(1, (int)($semana ?? bpSemanaSaida($nome)));
    $questoes = bpMontarPerguntas($nome, $snapshot, $semana);
    if ($jogador && !empty($vtReal['autor']) && !empty($vtReal['fala'])) {
        $novo = bpPergunta('revelacao','📼 O que você não viu',
            'A produção recuperou este depoimento de ' . (string)$vtReal['autor'] .
            ', gravado antes da sua saída: “' . (string)$vtReal['fala'] . '”. Como você reage?', [
            bpOpt('decepcao','😔 Demonstrar surpresa','Eu não sabia que essa pessoa pensava assim. Preciso processar o que acabei de ouvir.'),
            bpOpt('compreender','🤝 Entender o lado dela','Lá dentro todo mundo lida com pressão. Não quero julgar sem conversar aqui fora.'),
            bpOpt('confrontar','🔥 Discordar da fala','Não concordo com essa versão. Eu também tenho meu lado dessa história.'),
            bpOpt('humor','😂 Reagir com humor','Caramba, quanta coisa acontecia enquanto eu estava em outro canto da casa!')
        ]);
        // Não exceder sete perguntas, mantendo começo e encerramento.
        if (count($questoes) >= 7) array_splice($questoes, -2, 1);
        array_splice($questoes, -1, 0, [$novo]);
    }
    foreach ($questoes as &$q) {
        $q['pergunta'] = bpFlexionarFala((string)$q['pergunta'], $nome, $snapshot);
        foreach ($q['opcoes'] as &$opt) {
            $opt['fala'] = bpFlexionarFala((string)$opt['fala'], $nome, $snapshot);
        }
        unset($opt);
    }
    unset($q);
    $entrevista = ['nome'=>$nome, 'semana'=>$semana, 'jogador'=>$jogador, 'perguntas'=>$questoes, 'respostas'=>[], 'concluida'=>false,
        'dados'=>['estatisticas'=>(array)($snapshot['estatisticas']??[]), 'personalidade'=>(string)($snapshot['personalidade']??'')]];
    if (!$jogador) {
        $seed = (int)sprintf('%u', crc32($nome.'|'.$semana));
        foreach ($questoes as $pos=>$q) {
            $ops = $q['opcoes'];
            $op = $ops[($seed + $pos * 7) % count($ops)];
            $entrevista['respostas'][$q['id']] = ['id'=>$op['id'],'fala'=>$op['fala']];
        }
        $entrevista['concluida'] = true;
    }
    $_SESSION['bp_entrevistas'][$nome] = $entrevista;
    return $entrevista;
}

function bpPrepararArquivo(): void {
    foreach ((array)($_SESSION['historico_eliminados']??[]) as $nome) {
        $nome = (string)$nome;
        if (isset($_SESSION['bp_entrevistas'][$nome])) continue;
        $j = bpSnapshot($nome);
        if (!$j) continue;
        bpPreparar($nome, $j, bpSemanaSaida($nome), bpNomeIgual($nome,(string)($_SESSION['meu_nome']??'')));
    }
}

function bpResponderJogador(string $id, string $opcao): bool {
    $nome = (string)($_SESSION['meu_nome'] ?? '');
    if (!bpEliminadoReal($nome) || !empty($_SESSION['modo_espectador'])) return false;
    $e = &$_SESSION['bp_entrevistas'][$nome];
    if (!is_array($e ?? null) || !empty($e['concluida'])) return false;
    $pos = count($e['respostas']); $q = $e['perguntas'][$pos] ?? null;
    if (!$q || ($q['id'] ?? '') !== $id) return false;
    foreach ($q['opcoes'] as $opt) {
        if ($opt['id'] === $opcao) {
            $e['respostas'][$id] = ['id'=>$opcao,'fala'=>$opt['fala']];
            $e['concluida'] = count($e['respostas']) === count($e['perguntas']);
            return true;
        }
    }
    return false;
}

function bpDestaqueParaMim(string $nome): array {
    $snap = bpSnapshot($nome);
    $meu = (string)($_SESSION['meu_nome'] ?? '');
    $romance = (float)($snap['romances'][$meu]??0);
    if ($romance >= 30) return ['💞 Romance / interesse', 3];
    $r = (array)($snap['relacoes'][$meu]??[]);
    $score = (float)($r['amizade']??0) + .7*(float)($r['confianca']??0) - 1.2*(float)($r['rivalidade']??0);
    $meuSnapshot = bpSnapshot($meu);
    if (!empty($snap['alianca']) && !empty($meuSnapshot['alianca']) && $snap['alianca']===$meuSnapshot['alianca']) return ['🤝 Aliado', 2];
    if ($score >= 20) return ['💜 Amizade', 2];
    return ['🎤 Ex-participante', 0];
}
