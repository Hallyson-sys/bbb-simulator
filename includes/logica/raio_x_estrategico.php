<?php
/**
 * 🎙️ Confessionário — quadro privado de posicionamento.
 * Usa fatos observáveis/afinidades, NUNCA popularidade, votos ocultos ou segredos de NPCs.
 * A escolha gravada às câmeras NÃO é ouvida automaticamente pela casa.
 * Efeitos sociais ocorrem somente quando o jogador decide agir nas Interações.
 */

function rx3Esc(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rx3ParticipantesValidos(array $jogadores, string $meuNome): array {
    $lista = [];
    foreach ($jogadores as $j) {
        $nome = trim((string)($j['nome'] ?? ''));
        if ($nome === '' || $nome === $meuNome || !empty($j['eliminado'])) continue;
        $lista[] = $nome;
    }
    return $lista;
}

function rx3Opcao(string $id, string $titulo, string $descricao, string $tom, string $resultado, ?array $pratica = null): array {
    return compact('id', 'titulo', 'descricao', 'tom', 'resultado', 'pratica');
}

function rx3BancoCenas(?string $amigo, ?string $rival, ?string $lider): array {
    $cenas = [];
    if ($rival !== null) {
        $n = $rival;
        $cenas['tensao'] = [
            'icone' => '🔥', 'categoria' => 'TENSÃO NA CASA', 'titulo' => 'Uma relação em alerta',
            'texto' => "A convivência com {$n} está desgastada. Você percebe o afastamento, mas não pode afirmar o que essa pessoa pensa ou planeja.",
            'dica' => 'Uma impressão não é uma prova. Como você pretende lidar com isso?', 'alvo' => $n,
            'opcoes' => [
                rx3Opcao('conversar', '💬 Quero esclarecer isso', 'Buscar uma conversa antes de tirar conclusões.', 'diplomatico', 'Você decidiu tentar abrir espaço para uma conversa franca.', ['efeito' => 'dialogo', 'amizade' => 6, 'rivalidade' => -3, 'confianca' => 4, 'memoria' => 'me_aproximou']),
                rx3Opcao('firme', '🎯 Vou impor meus limites', 'Ser direto sem transformar suspeita em acusação.', 'firme', 'Você escolheu marcar sua posição sem afirmar o que não sabe.', ['efeito' => 'limites', 'amizade' => -2, 'rivalidade' => 3, 'confianca' => 0, 'memoria' => 'me_confrontou']),
                rx3Opcao('distancia', '🧊 Prefiro manter distância', 'Não alimentar a rivalidade por enquanto.', 'cauteloso', 'Você decidiu observar de longe e não ampliar o conflito.'),
                rx3Opcao('indireta', '😏 Se vier provocação, vou responder', 'Adotar uma postura mais confrontadora.', 'combativo', 'Você está disposto a rebater provocações. Isso pode aumentar a tensão se colocado em prática.', ['efeito' => 'provocacao', 'amizade' => -6, 'rivalidade' => 7, 'confianca' => -5, 'memoria' => 'me_confrontou'])
            ]
        ];
    }
    if ($amigo !== null) {
        $n = $amigo;
        $cenas['confianca'] = [
            'icone' => '🤝', 'categoria' => 'CONFIANÇA', 'titulo' => 'Até onde vai essa parceria?',
            'texto' => "Sua relação com {$n} vem sendo positiva. Ainda assim, vocês continuam competindo pelo mesmo prêmio.",
            'dica' => 'Confiança se constrói em atitudes; promessas no confessionário não chegam à casa.', 'alvo' => $n,
            'opcoes' => [
                rx3Opcao('valorizar', '❤️ Quero fortalecer essa amizade', 'Demonstrar gratidão cara a cara.', 'afetivo', 'Você decidiu valorizar essa conexão fora das câmeras também.', ['efeito' => 'apoio', 'amizade' => 6, 'rivalidade' => -2, 'confianca' => 5, 'memoria' => 'me_apoiou']),
                rx3Opcao('pacto', '🤝 Quero conversar sobre proteção mútua', 'Tentar firmar um compromisso, sem garantia de aceitação.', 'estrategico', 'Você considerou transformar confiança em uma conversa estratégica.', ['efeito' => 'pacto', 'amizade' => 3, 'rivalidade' => -1, 'confianca' => 5, 'memoria' => 'fechou_pacto_comigo']),
                rx3Opcao('cautela', '👀 Gosto dessa pessoa, mas tenho limites', 'Preservar a amizade sem se comprometer com votos.', 'cauteloso', 'Você quer manter a relação sem prometer nada no jogo.'),
                rx3Opcao('sinceridade', '💭 Quero saber se estamos na mesma página', 'Abrir uma conversa sincera sobre expectativas.', 'sincero', 'Você pretende alinhar expectativas, sem supor que a outra pessoa concorda.', ['efeito' => 'dialogo', 'amizade' => 4, 'rivalidade' => 0, 'confianca' => 4, 'memoria' => 'me_aproximou'])
            ]
        ];
        $cenas['prioridade'] = [
            'icone' => '🎯', 'categoria' => 'PRIORIDADES', 'titulo' => 'Quem merece sua confiança?',
            'texto' => "Entre tanta disputa, {$n} é uma das pessoas com quem você mais se entende atualmente. Isso não significa que vocês tenham o mesmo plano.",
            'dica' => 'Você pode mudar sua estratégia sem saber o que acontece na cabeça dos outros.', 'alvo' => $n,
            'opcoes' => [
                rx3Opcao('proteger', '🛡️ Quero proteger essa conexão', 'Tentar reforçar o compromisso de apoio.', 'leal', 'Você pretende demonstrar lealdade com atitudes.', ['efeito' => 'apoio', 'amizade' => 5, 'rivalidade' => -2, 'confianca' => 4, 'memoria' => 'me_apoiou']),
                rx3Opcao('jogo', '♟️ Amizade não é passe livre', 'Separar carinho das futuras decisões.', 'estrategico', 'Você decidiu não transformar afinidade em promessa de voto.'),
                rx3Opcao('escutar', '👂 Primeiro quero ouvir mais', 'Dar espaço para que essa pessoa mostre suas intenções.', 'observador', 'Você prefere reunir mais informações antes de agir.', ['efeito' => 'dialogo', 'amizade' => 3, 'rivalidade' => 0, 'confianca' => 3, 'memoria' => 'me_aproximou'])
            ]
        ];
    }
    // O Confessionário acontece ANTES da nova Prova do Líder.
    // Não mencionar como "Líder desta semana" quem venceu a rodada passada.
    $cenas['provas'] = [
        'icone' => '🏆', 'categoria' => 'PRÓXIMOS DESAFIOS', 'titulo' => 'Entre competir e conviver',
        'texto' => 'Novas provas ainda estão por vir. Você não sabe quem vencerá nem como o poder será usado.',
        'dica' => 'Qual postura você deseja adotar diante das próximas oportunidades?', 'alvo' => null,
        'opcoes' => [
            rx3Opcao('foco', '🎯 Vou me dedicar às provas', 'Levar a competição a sério sem prometer vitória.', 'competitivo', 'Você decidiu investir mais energia nos próximos desafios.'),
            rx3Opcao('social', '🤝 Quero equilibrar prova e convivência', 'Não deixar o jogo social em segundo plano.', 'diplomatico', 'Você pretende cuidar das relações enquanto disputa o poder.'),
            rx3Opcao('resiliencia', '💪 Se perder, continuo no jogo', 'Aceitar resultados e ajustar os planos.', 'resiliente', 'Você decidiu não deixar uma derrota definir sua trajetória.'),
            rx3Opcao('prudente', '👀 Quero observar antes de me expor', 'Evitar promessas que você ainda não pode cumprir.', 'cauteloso', 'Você escolheu analisar o ambiente antes de se comprometer.')
        ]
    ];
    $cenas['leitura'] = [
        'icone' => '👀', 'categoria' => 'LEITURA DE JOGO', 'titulo' => 'Nem tudo é o que parece',
        'texto' => 'A casa está cheia de conversas e interpretações. Você ainda não tem elementos para dizer com certeza o que todo mundo pretende.',
        'dica' => 'Seu posicionamento pode ser prudente, espontâneo ou estratégico.', 'alvo' => null,
        'opcoes' => [
            rx3Opcao('observar', '🔎 Vou prestar atenção nas atitudes', 'Valorizar fatos acima de boatos.', 'observador', 'Você decidiu não tirar conclusões precipitadas.'),
            rx3Opcao('agir', '🎯 Quero jogar com mais iniciativa', 'Não esperar os outros definirem tudo.', 'estrategico', 'Você escolheu assumir mais responsabilidade pelas próximas decisões.'),
            rx3Opcao('espontaneo', '💬 Vou continuar sendo eu mesmo', 'Não ensaiar cada conversa.', 'sincero', 'Você decidiu priorizar sua espontaneidade.'),
            rx3Opcao('neutralidade', '🧊 Não preciso tomar partido agora', 'Evitar brigas que não te envolvem.', 'cauteloso', 'Você prefere guardar suas avaliações por enquanto.')
        ]
    ];
    $cenas['emocao'] = [
        'icone' => '💭', 'categoria' => 'SENTIMENTOS', 'titulo' => 'O peso de viver o jogo',
        'texto' => 'Convivência, provas e votações exigem decisões difíceis. Como você quer encarar a pressão desta rodada?',
        'dica' => 'Não existe resposta certa; esse é o tom que você escolhe para seu personagem.', 'alvo' => null,
        'opcoes' => [
            rx3Opcao('vulneravel', '❤️ Admitir que também sinto medo', 'Não esconder o lado humano.', 'sensivel', 'Você se permitiu reconhecer a pressão sem fingir estar sempre bem.'),
            rx3Opcao('resiliencia', '💪 Dizer que estou aprendendo', 'Reconhecer os obstáculos e seguir.', 'resiliente', 'Você escolheu encarar os desafios como parte da trajetória.'),
            rx3Opcao('pratico', '♟️ Focar nas próximas decisões', 'Pensar no que está sob seu controle.', 'estrategico', 'Você prefere transformar a ansiedade em planejamento.'),
            rx3Opcao('leveza', '🌙 Preciso viver mais o momento', 'Dar espaço para vínculos e descanso.', 'leve', 'Você decidiu não reduzir toda a experiência a cálculo.')
        ]
    ];
    $cenas['recado'] = [
        'icone' => '🎙️', 'categoria' => 'RECADO ÀS CÂMERAS', 'titulo' => 'Qual mensagem você deixa hoje?',
        'texto' => 'Você tem alguns segundos para se posicionar diante das câmeras. As outras pessoas da casa não escutam esta gravação.',
        'dica' => 'O depoimento constrói sua trajetória; não é uma promessa pública nem uma mensagem enviada aos NPCs.', 'alvo' => null,
        'opcoes' => [
            rx3Opcao('coragem', '🔥 Não vou fugir das minhas decisões', 'Falar sobre ter coragem de assumir escolhas.', 'firme', 'Você registrou um posicionamento de coragem e responsabilidade.'),
            rx3Opcao('respeito', '🤝 Quero jogar sem perder o respeito', 'Reconhecer limites na convivência.', 'diplomatico', 'Você escolheu destacar respeito e diálogo.'),
            rx3Opcao('estrategia', '🧠 Estou pensando no longo prazo', 'Assumir que veio competir.', 'estrategico', 'Você declarou que quer planejar seus próximos movimentos.'),
            rx3Opcao('afeto', '❤️ As relações também importam', 'Valorizar os vínculos construídos.', 'afetivo', 'Você escolheu dar espaço às conexões humanas.')
        ]
    ];
    // Temas adicionais: não assumem dados secretos, voto de NPC nem popularidade.
    $novos = [
        ['rotina','☕','ROTINA DA CASA','Pequenos hábitos, grandes diferenças','A convivência diária também constrói afinidades. Como você quer lidar com costumes que te incomodam?',
            [['tolerar','😊 Respirar e relevar','leve','Você prefere não transformar pequenos incômodos em conflito.'],['conversar','💬 Conversar com calma','diplomatico','Você decidiu comunicar seus limites de maneira respeitosa.'],['afastar','🧊 Dar um tempo','cauteloso','Você escolheu preservar seu espaço sem discutir.']]],
        ['saudade','🏠','SAUDADE','Quando o jogo silencia','Há momentos em que a saudade e a pressão da convivência pesam, mesmo quando o jogo parece tranquilo.',
            [['desabafar','💭 Admitir a saudade','sensivel','Você reconheceu uma parte íntima da experiência.'],['acolher','🤝 Buscar apoio','afetivo','Você decidiu se apoiar nas conexões que construiu.'],['persistir','💪 Transformar em força','resiliente','Você reafirmou por que continua no jogo.']]],
        ['arrependimentos','🔄','REVISÃO DE ATITUDES','Se pudesse voltar atrás...','Pense em uma decisão de convivência que você faria de outro jeito. Nenhuma reação dos outros é certa.',
            [['aprender','🧠 Reconhecer que errou','sincero','Você aceitou que algumas atitudes merecem revisão.'],['sustentar','🎯 Defender sua decisão','firme','Você reconheceu os custos mas manteve sua posição.'],['conversar','💬 Buscar reparar uma relação','diplomatico','Você quer conversar pessoalmente antes de tirar conclusões.']]],
        ['vulnerabilidade','💗','VULNERABILIDADE','Ter coragem de sentir','As emoções mudam rapidamente dentro da casa. Como você lida quando se sente desamparado?',
            [['mostrar','❤️ Não esconder a emoção','sensivel','Você permitiu que o depoimento fosse mais vulnerável.'],['desacelerar','🌙 Buscar tranquilidade','leve','Você decidiu respeitar seu próprio ritmo.'],['proteger','🧊 Guardar o que sente','cauteloso','Você prefere processar os sentimentos antes de falar.']]],
        ['independencia','♟️','INDEPENDÊNCIA','Meu voto é meu','Você se aproxima de pessoas, mas precisa manter capacidade de decidir sozinho nas votações.',
            [['autonomia','🎯 Não prometer o que não sei','firme','Você escolheu não antecipar seu voto.'],['negociar','🤝 Ouvir seus aliados','diplomatico','Você continua aberto a conversas sem garantir apoio.'],['analisar','👀 Reavaliar os riscos','estrategico','Você pretende avaliar cada votação no seu momento.']]],
        ['confronto','🔥','LIMITES','Até onde vale uma discussão?','Quando a casa está tensa, uma resposta impulsiva pode mudar a convivência.',
            [['esfriar','🧊 Não alimentar o conflito','cauteloso','Você prefere se afastar da tensão.'],['argumentar','💬 Falar diretamente','sincero','Você quer enfrentar o assunto sem provocar.'],['rebater','🔥 Se provocarem, vou responder','combativo','Você se sente disposto a defender sua posição.']]],
        ['convivencia','🎉','LAÇOS DA CASA','Nem tudo é voto','Mesmo competindo, ainda existem festas, boas conversas e experiências que você pode aproveitar.',
            [['aproveitar','😊 Quero viver mais o momento','leve','Você quer aproveitar a experiência para além das provas.'],['priorizar','🏆 Não perder o foco','competitivo','Você decidiu equilibrar diversão e competição.'],['aproximar','🤝 Conhecer gente diferente','afetivo','Você se permitiu ampliar os vínculos.']]],
        ['futuro','🌟','VISÃO DE FUTURO','O que você quer deixar aqui?','Você ainda não sabe como a semana terminará. Mas pode decidir o que deseja construir no jogo.',
            [['respeito','🤍 Deixar boas relações','diplomatico','Você escolheu valorizar o respeito como parte da trajetória.'],['marca','🔥 Ser lembrado por decisões','firme','Você quer assumir seus movimentos sem fingimento.'],['crescer','🧠 Sair aprendendo','resiliente','Você reconheceu o aprendizado dessa convivência.']]]
    ];
    foreach ($novos as [$id,$icone,$categoria,$titulo,$texto,$escolhas]) {
        $opcoes=[];
        foreach ($escolhas as [$oid,$rotulo,$tom,$resultado])
            $opcoes[]=rx3Opcao($oid,$rotulo,'Seu posicionamento fica privado; só suas ações na casa poderão ter consequências sociais.',$tom,$resultado);
        $cenas[$id]=['icone'=>$icone,'categoria'=>$categoria,'titulo'=>$titulo,'texto'=>$texto,'dica'=>'O que você declara às câmeras não é ouvido automaticamente por outros participantes.','alvo'=>null,'opcoes'=>$opcoes];
    }
    return $cenas;
}

function rx3Preparar(array $jogadores, string $meuNome): void {
    if (($_SESSION['fase_semana'] ?? '') !== 'confessionario' || !empty($_SESSION['modo_espectador'])) return;
    $rodada = max(1, (int)($_SESSION['rodada'] ?? 1));
    if (isset($_SESSION['rx3_quadro']['rodada']) && (int)$_SESSION['rx3_quadro']['rodada'] === $rodada) return;
    $nomes = rx3ParticipantesValidos($jogadores, $meuNome);
    $pos = $neg = null;
    $max = 19; $min = -19;
    foreach ($nomes as $nome) {
        $afin = (int)($_SESSION['relacoes_jogador'][$nome] ?? 0);
        if ($afin > $max) { $max = $afin; $pos = $nome; }
        if ($afin < $min) { $min = $afin; $neg = $nome; }
    }
    $cenas = rx3BancoCenas($pos, $neg, null);
    $anteriores = array_slice((array)($_SESSION['rx3_temas_recentes'] ?? []), -2);
    $chaves = array_keys($cenas);
    $disponiveis = array_values(array_diff($chaves, $anteriores));
    if (!$disponiveis) $disponiveis = $chaves;
    $tipo = $disponiveis[array_rand($disponiveis)];
    $quadro = $cenas[$tipo];
    $quadro['tipo'] = $tipo;
    $quadro['rodada'] = $rodada;
    $quadro['token'] = bin2hex(random_bytes(16));
    $quadro['escolha'] = null;
    $_SESSION['rx3_quadro'] = $quadro;
    $_SESSION['rx3_temas_recentes'] = array_slice(array_merge((array)($_SESSION['rx3_temas_recentes'] ?? []), [$tipo]), -8);
}

function rx3Escolher(string $id, string $token): bool {
    if (($_SESSION['fase_semana'] ?? '') !== 'confessionario') return false;
    $q =& $_SESSION['rx3_quadro'];
    if (!is_array($q) || (int)($q['rodada'] ?? -1) !== (int)($_SESSION['rodada'] ?? 1) ||
        !hash_equals((string)($q['token'] ?? ''), $token) || $q['escolha'] !== null) return false;
    foreach ($q['opcoes'] as $opcao) {
        if ($opcao['id'] !== $id) continue;
        $q['escolha'] = [
            'id' => $id, 'titulo' => $opcao['titulo'], 'tom' => $opcao['tom'],
            'resultado' => $opcao['resultado']
        ];
        $registro = ['rodada' => (int)$q['rodada'], 'tema' => $q['categoria'],
            'alvo' => $q['alvo'], 'resposta' => $opcao['titulo'], 'tom' => $opcao['tom']];
        $_SESSION['rx3_historico'] = array_slice(array_merge((array)($_SESSION['rx3_historico'] ?? []), [$registro]), -40);
        $tom = $opcao['tom'];
        if (!isset($_SESSION['rx3_posturas']) || !is_array($_SESSION['rx3_posturas'])) $_SESSION['rx3_posturas'] = [];
        $_SESSION['rx3_posturas'][$tom] = min(99, (int)($_SESSION['rx3_posturas'][$tom] ?? 0) + 1);
        if (!empty($opcao['pratica']) && !empty($q['alvo'])) {
            $_SESSION['rx3_intencao'] = [
                'rodada' => (int)$q['rodada'], 'alvo' => $q['alvo'], 'rotulo' => $opcao['titulo'],
                'dados' => $opcao['pratica'], 'aplicada' => false, 'token' => bin2hex(random_bytes(16))
            ];
        } else {
            unset($_SESSION['rx3_intencao']);
        }
        unset($_SESSION['rx3_erro']);
        return true;
    }
    return false;
}

function rx3IntencaoDisponivel(array $jogadores, string $meuNome, string $fase): ?array {
    if (!preg_match('/^interacoes_/', $fase) || !empty($_SESSION['modo_espectador'])) return null;
    $i = $_SESSION['rx3_intencao'] ?? null;
    if (!is_array($i) || !empty($i['aplicada']) || (int)($i['rodada'] ?? -1) !== (int)($_SESSION['rodada'] ?? 1)) return null;
    if (!in_array((string)($i['alvo'] ?? ''), rx3ParticipantesValidos($jogadores, $meuNome), true)) return null;
    return $i;
}

function rx3AplicarIntencao(array &$jogadores, string $meuNome, string $fase, string $token): string {
    $i = rx3IntencaoDisponivel($jogadores, $meuNome, $fase);
    if (!$i || !hash_equals((string)$i['token'], $token) || (int)($_SESSION['acoes_restantes'] ?? 0) <= 0) return '';
    $nome = $i['alvo'];
    $d = $i['dados'];
    $amizade = max(-8, min(8, (int)($d['amizade'] ?? 0)));
    $rivalidade = max(-8, min(8, (int)($d['rivalidade'] ?? 0)));
    $confianca = max(-8, min(8, (int)($d['confianca'] ?? 0)));
    alterarAfinidade($jogadores, $nome, $meuNome, $amizade, $rivalidade, $confianca);
    alterarAfinidade($jogadores, $meuNome, $nome, $amizade, $rivalidade, $confianca);
    ajustarRelacaoJogador($nome, $amizade + $confianca - $rivalidade);
    $frases = [
        'dialogo' => "💬 {$meuNome} procurou {$nome} para conversar depois de refletir sobre o próprio jogo.",
        'limites' => "🎯 {$meuNome} conversou com {$nome} e deixou seus limites mais claros.",
        'provocacao' => "🔥 {$meuNome} provocou {$nome} durante uma conversa e o clima ficou tenso.",
        'apoio' => "🤝 {$meuNome} demonstrou apoio a {$nome} numa conversa na casa.",
        'pacto' => "🤝 {$meuNome} propôs a {$nome} uma conversa sobre apoio mútuo, sem uma promessa de voto garantida."
    ];
    $efeito = (string)($d['efeito'] ?? 'dialogo');
    $descricao = $frases[$efeito] ?? $frases['dialogo'];
    if (function_exists('registrarMemoriaSocialNPC')) {
        $tipo = (string)($d['memoria'] ?? 'me_aproximou');
        registrarMemoriaSocialNPC($nome, $meuNome, $tipo, 2, $descricao,
            'rx3|' . (int)$i['rodada'] . '|' . $nome . '|' . $i['rotulo']);
    }
    $_SESSION['rx3_intencao']['aplicada'] = true;
    return $descricao;
}
