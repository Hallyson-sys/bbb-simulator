<?php

/* =========================================================
   🎥 CONFESSIONÁRIO 2.0 — MEMÓRIAS NARRATIVAS
   Falas modulares, personalidade perceptível, contexto real
   da rodada e proteção contra repetição de tema/pessoa/frase.
   ========================================================= */

function escolherFalaNaoRepetida($opcoes, $historico)
{
    if (empty($opcoes)) return '';
    if (!is_array($historico)) $historico = [];

    $historicoLimpo = array_map(function ($fala) {
        return trim(strip_tags((string)$fala));
    }, $historico);

    shuffle($opcoes);
    foreach ($opcoes as $fala) {
        $limpa = trim(strip_tags((string)$fala));
        if (!in_array($limpa, $historicoLimpo, true)) return $fala;
    }
    return $opcoes[array_rand($opcoes)];
}

function confessionarioEscolherPonderado($pesos)
{
    $total = 0.0;
    foreach ($pesos as $peso) $total += max(0, (float)$peso);
    if ($total <= 0) return array_key_first($pesos);

    $sorte = (mt_rand() / mt_getrandmax()) * $total;
    $acumulado = 0.0;
    foreach ($pesos as $chave => $peso) {
        $acumulado += max(0, (float)$peso);
        if ($sorte <= $acumulado) return $chave;
    }
    return array_key_last($pesos);
}

function confessionarioEmListaDeNomes($nome, $valor)
{
    if (!is_array($valor)) return false;
    foreach ($valor as $item) {
        if (is_array($item)) $item = $item['nome'] ?? $item['participante'] ?? '';
        if (function_exists('nomeIgual') ? nomeIgual((string)$item, $nome) : ((string)$item === $nome)) return true;
    }
    return false;
}

function confessionarioAberturasPorPersonalidade($personalidade)
{
    $mapa = [
        'Estrategista' => [
            'Eu venho pensando nisso há alguns dias e acho que agora consigo enxergar melhor.',
            'Tem uma leitura de jogo que eu não quero ignorar.',
            'Eu tento não decidir nada no impulso, então fiquei observando antes de falar.',
            'Nesse momento eu estou olhando menos para discurso e mais para movimento.'
        ],
        'Explosivo' => [
            'Vou falar logo porque ficar engolindo coisa não combina comigo.',
            'Eu tentei deixar passar, mas não consigo fingir que não me incomoda.',
            'Hoje eu tô com isso atravessado e prefiro falar.',
            'Tem hora que a gente precisa parar de fazer média e dizer o que sente.'
        ],
        'Barraqueiro' => [
            'Se é pra falar, eu vou falar na lata.',
            'Aqui no confessionário eu não tenho motivo pra passar pano.',
            'Eu não gosto de conversinha pela metade, então vou ser bem claro(a).',
            'Tem coisa que todo mundo percebe, mas ninguém quer ser o primeiro a dizer.'
        ],
        'Emocional' => [
            'Eu sinto tudo muito forte aqui dentro e hoje isso bateu diferente.',
            'Talvez eu esteja levando pro coração, mas é impossível desligar sentimento.',
            'Eu queria separar jogo e afeto com facilidade, só que não consigo sempre.',
            'Hoje eu entrei aqui precisando colocar pra fora uma coisa que tá pesando.'
        ],
        'Manipulador' => [
            'Eu não preciso mostrar todas as cartas que tenho na mão.',
            'Às vezes o melhor movimento é deixar as pessoas confortáveis demais.',
            'Eu tô prestando atenção em quem acha que já entendeu meu jogo.',
            'Tem coisa que eu prefiro guardar até a hora certa.'
        ],
        'Falso' => [
            'Nem tudo que eu penso precisa virar assunto na casa.',
            'Eu aprendi que aqui dentro você fala uma coisa e observa outras três.',
            'Tem relações que eu mantenho bem porque ainda são úteis pro meu jogo.',
            'Eu não vou entregar de graça tudo o que eu estou enxergando.'
        ],
        'Planta' => [
            'Eu tenho observado bastante antes de me meter em qualquer coisa.',
            'Talvez eu esteja mais quieto(a), mas isso não significa que eu não esteja vendo.',
            'Eu prefiro entender o terreno antes de tomar uma posição muito dura.',
            'Essa casa corre tanto que às vezes eu escolho respirar antes de reagir.'
        ],
        'Fofo' => [
            'Eu tento cuidar das relações porque é isso que me mantém inteiro(a) aqui.',
            'Eu não gosto de machucar ninguém, mas também preciso ser honesto(a) comigo.',
            'A convivência mexe muito comigo e eu valorizo demais quem está do meu lado.',
            'Tem gente aqui que virou um porto seguro pra mim.'
        ],
        'Líder Nato' => [
            'Eu sei que minhas escolhas têm peso e não quero fugir dessa responsabilidade.',
            'Eu gosto de ter clareza sobre quem caminha comigo e quem joga contra.',
            'Quanto mais o jogo aperta, mais eu sinto que preciso me posicionar.',
            'Eu não quero só sobreviver à semana; quero entender o que ela muda no jogo.'
        ],
        'Influencer' => [
            'Eu sei que tudo aqui repercute, mas não dá pra viver pensando só em como vai parecer.',
            'Tem momentos em que você sente que a casa inteira está olhando pra mesma história.',
            'Eu percebo muito o clima e como as pessoas mudam de postura de uma hora pra outra.',
            'Hoje eu sinto que tem uma narrativa se formando aqui dentro.'
        ],
        'Neutro' => [
            'Eu tô tentando juntar as peças antes de tomar qualquer conclusão.',
            'Essa semana me fez pensar bastante sobre algumas relações.',
            'Tem coisa que parece pequena, mas aqui dentro ganha outro peso.',
            'Eu ainda estou entendendo onde cada pessoa está no meu jogo.'
        ]
    ];
    return $mapa[$personalidade] ?? $mapa['Neutro'];
}

function confessionarioFechosPorPersonalidade($personalidade)
{
    $mapa = [
        'Estrategista' => ['Agora eu vou observar e esperar a hora certa de mexer.', 'Não preciso anunciar meu próximo passo pra ele existir.', 'Meu foco é não deixar emoção decidir tudo por mim.'],
        'Explosivo' => ['Se vier pra cima de mim, vai encontrar resposta.', 'Eu posso até esfriar depois, mas hoje é assim que eu tô sentindo.', 'Não vou ficar me diminuindo pra manter paz falsa.'],
        'Barraqueiro' => ['E se isso virar assunto lá fora, eu sustento aqui dentro.', 'Eu prefiro uma briga franca do que um sorriso falso.', 'Se tiver que resolver, eu resolvo olhando no olho.'],
        'Emocional' => ['Eu só espero não me arrepender de sentir tanto.', 'Preciso cuidar da cabeça pra não deixar isso me engolir.', 'No fim, eu quero conseguir dormir em paz com as minhas escolhas.'],
        'Manipulador' => ['Quanto menos certeza tiverem sobre mim, melhor.', 'Por enquanto, eu deixo cada um acreditar no que quiser.', 'Informação também é poder, e eu não vou desperdiçar a minha.'],
        'Falso' => ['Na sala eu vou continuar normal; aqui eu sei o que realmente penso.', 'Nem toda distância precisa parecer distância.', 'Meu jogo não precisa ser óbvio pra funcionar.'],
        'Planta' => ['Talvez eu não faça barulho, mas eu vou prestar atenção.', 'Eu prefiro errar por esperar do que por agir no impulso.', 'Minha hora de me posicionar vai chegar.'],
        'Fofo' => ['Eu só não quero perder quem eu sou no meio disso tudo.', 'Pra mim, chegar longe sem vínculo nenhum não faria sentido.', 'Eu ainda acredito que dá pra jogar sem deixar de ter carinho.'],
        'Líder Nato' => ['Quando chegar a hora da decisão, eu vou bancar.', 'Prefiro uma escolha difícil bem explicada do que ficar em cima do muro.', 'Eu sei que daqui pra frente cada movimento fica maior.'],
        'Influencer' => ['Eu sei que o Brasil está vendo tudo, então prefiro ser coerente.', 'Quero que o que eu faço aqui combine com o que eu digo.', 'A reação das pessoas importa, mas eu ainda preciso jogar por mim.'],
        'Neutro' => ['Vou seguir observando porque essa história ainda não acabou.', 'Não quero fechar questão antes da hora.', 'A próxima semana pode mudar completamente essa leitura.']
    ];
    return $mapa[$personalidade] ?? $mapa['Neutro'];
}

function confessionarioAplicarAntiRepeticao(&$pesos, $meta)
{
    if (!is_array($meta)) return;
    $ultimos = array_slice($meta, -5);
    $temas = array_values(array_filter(array_map(fn($m) => $m['tema'] ?? null, $ultimos)));

    foreach ($pesos as $tema => &$peso) {
        $vezes = 0;
        foreach ($temas as $t) if ($t === $tema) $vezes++;
        if ($vezes >= 2) $peso *= 0.08;
        elseif ($vezes === 1) $peso *= 0.35;
    }
    unset($peso);

    $ultimoTema = !empty($ultimos) ? ($ultimos[count($ultimos)-1]['tema'] ?? '') : '';
    if ($ultimoTema !== '' && isset($pesos[$ultimoTema])) $pesos[$ultimoTema] *= 0.20;
}

function confessionarioAlvoFoiRecente($alvo, $meta)
{
    if (!$alvo || !is_array($meta)) return false;
    foreach (array_slice($meta, -3) as $item) {
        if (($item['alvo'] ?? '') === $alvo) return true;
    }
    return false;
}

function confessionarioTrechoNaoRecente($opcoes, $meta, $campo)
{
    if (!is_array($opcoes) || empty($opcoes)) return '';
    $recentes = [];
    if (is_array($meta)) {
        foreach (array_slice($meta, -5) as $m) {
            if (!empty($m[$campo])) $recentes[] = $m[$campo];
        }
    }
    $livres = array_values(array_filter($opcoes, fn($o) => !in_array($o, $recentes, true)));
    $pool = $livres ?: $opcoes;
    return $pool[array_rand($pool)];
}

function gerarConfessionarioInteligente(&$jogadores, $meuNome)
{
    $falas = [];
    atualizarRelacoesMarcantes($jogadores);
    if (function_exists('sincronizarMemoriasAutomaticasNPC')) sincronizarMemoriasAutomaticasNPC($jogadores);

    foreach ($jogadores as $jogadorBase) {
        $nome = $jogadorBase['nome'] ?? '';
        if ($nome === '') continue;

        $personalidade = $jogadorBase['personalidade'] ?? 'Neutro';
        $perfil = perfilPersonalidadeCompleto($personalidade);
        $historicoFalas = $jogadorBase['confessionarios'] ?? [];
        $historicoMeta = $jogadorBase['confessionario_meta'] ?? [];

        $melhorAliado = $maiorRival = $maiorRomance = $alvoEstrategico = $pessoaFalsa = $pessoaDecepcao = null;
        $scores = ['aliado'=>-999,'rival'=>-999,'romance'=>-999,'estrategia'=>-999,'falsa'=>-999,'decepcao'=>-999];

        foreach ($jogadores as $alvoDados) {
            $alvo = $alvoDados['nome'] ?? '';
            if ($alvo === '' || (function_exists('nomeIgual') ? nomeIgual($alvo,$nome) : $alvo === $nome)) continue;
            $rel = obterRelacaoCompleta($jogadores, $nome, $alvo, $meuNome);
            $romance = obterRomance($jogadores, $nome, $alvo);
            $pop = (int)($alvoDados['popularidade'] ?? 50);

            $calc = [
                'aliado' => ($rel['amizade'] ?? 0) + ($rel['confianca'] ?? 0) - ($rel['rivalidade'] ?? 0),
                'rival' => ($rel['rivalidade'] ?? 0) + (100 - ($rel['amizade'] ?? 0)) - ($rel['confianca'] ?? 0),
                'romance' => $romance,
                'estrategia' => (100 - $pop) + ($rel['rivalidade'] ?? 0) - (($rel['confianca'] ?? 0) * .35),
                'falsa' => ($rel['rivalidade'] ?? 0) + (100 - ($rel['confianca'] ?? 0)),
                'decepcao' => (100 - ($rel['confianca'] ?? 0)) + max(0, 40 - ($rel['amizade'] ?? 0)) + ($rel['rivalidade'] ?? 0)
            ];
            foreach ($calc as $k=>$v) {
                if ($v > $scores[$k]) {
                    $scores[$k] = $v;
                    if ($k==='aliado') $melhorAliado=$alvo;
                    elseif ($k==='rival') $maiorRival=$alvo;
                    elseif ($k==='romance') $maiorRomance=$alvo;
                    elseif ($k==='estrategia') $alvoEstrategico=$alvo;
                    elseif ($k==='falsa') $pessoaFalsa=$alvo;
                    elseif ($k==='decepcao') $pessoaDecepcao=$alvo;
                }
            }
        }

        $memNeg = function_exists('memoriaNarrativaDominanteGeralNPC') ? memoriaNarrativaDominanteGeralNPC($nome, 'negativa') : null;
        $memPos = function_exists('memoriaNarrativaDominanteGeralNPC') ? memoriaNarrativaDominanteGeralNPC($nome, 'positiva') : null;

        $noParedao = confessionarioEmListaDeNomes($nome, $_SESSION['paredao'] ?? []);
        $noMonstro = confessionarioEmListaDeNomes($nome, $_SESSION['monstro'] ?? []);
        $lider = !empty($jogadorBase['status']['lider']);
        $anjo = !empty($jogadorBase['status']['anjo']);

        $pesos = [
            'observacao' => 8,
            'sonho_vitoria' => 5,
            'estrategia' => ($alvoEstrategico ? 5 : 0) + (($perfil['alianca'] ?? 50) / 22),
            'aliado' => ($melhorAliado && $scores['aliado'] >= 55) ? 5 + (($perfil['alianca'] ?? 50)/18) : 0,
            'rival' => ($maiorRival && $scores['rival'] >= 65) ? 5 + (($perfil['treta'] ?? 50)/16) : 0,
            'romance' => ($maiorRomance && $scores['romance'] >= 28) ? 4 + (($perfil['romance'] ?? 50)/18) : 0,
            'falsidade' => ($pessoaFalsa && $scores['falsa'] >= 80) ? 5 : 0,
            'decepcao' => ($pessoaDecepcao && $scores['decepcao'] >= 82) ? 5 : 0,
            'medo_paredao' => $noParedao ? 16 : (($perfil['emocao'] ?? 50) >= 65 ? 3 : 0),
            'desabafo' => 2 + (($perfil['emocao'] ?? 50)/24),
            'lideranca' => $lider ? 12 : 0,
            'anjo' => $anjo ? 8 : 0,
            'monstro' => $noMonstro ? 10 : 0,
            'memoria_negativa' => ($memNeg && ($memNeg['impacto'] ?? 0) >= 12) ? min(18, 4 + (($memNeg['impacto'] ?? 0)/9)) : 0,
            'memoria_positiva' => ($memPos && ($memPos['impacto'] ?? 0) >= 10) ? min(14, 3 + (($memPos['impacto'] ?? 0)/11)) : 0,
            'vt' => (($perfil['vt'] ?? 50) >= 70) ? 4 : 1
        ];
        /* Evita falar da mesma pessoa em confessionários consecutivos sem motivo muito forte. */
        $alvosPorTema = [
            'aliado'=>$melhorAliado, 'rival'=>$maiorRival, 'romance'=>$maiorRomance,
            'estrategia'=>$alvoEstrategico, 'falsidade'=>$pessoaFalsa, 'decepcao'=>$pessoaDecepcao,
            'memoria_negativa'=>$memNeg['alvo'] ?? null, 'memoria_positiva'=>$memPos['alvo'] ?? null
        ];
        foreach ($alvosPorTema as $temaAlvo => $nomeAlvoTema) {
            if ($nomeAlvoTema && confessionarioAlvoFoiRecente($nomeAlvoTema, $historicoMeta) && isset($pesos[$temaAlvo])) {
                $pesos[$temaAlvo] *= in_array($temaAlvo, ['memoria_negativa','memoria_positiva'], true) ? 0.55 : 0.38;
            }
        }

        confessionarioAplicarAntiRepeticao($pesos, $historicoMeta);
        $tema = confessionarioEscolherPonderado($pesos);

        $alvoTema = null;
        $miolo = [];

        switch ($tema) {
            case 'memoria_negativa':
                $alvoTema = $memNeg['alvo'] ?? null;
                $lembranca = ($alvoTema && !empty($memNeg['evento'])) ? textoMemoriaNarrativaNPC($memNeg['evento'], $alvoTema, 'confessionario') : '';
                $miolo = [
                    "$lembranca Eu posso seguir convivendo, mas não vou fingir que isso não mudou meu jogo.",
                    "$lembranca Aqui tudo tem consequência, e eu ainda estou processando o que isso significa pra nós dois.",
                    "$lembranca Não é sobre ficar remoendo por esporte; é sobre aprender com o que aconteceu.",
                    "$lembranca Desde então eu fico muito mais atento(a) quando $alvoTema fala de lealdade.",
                    "$lembranca Eu não preciso comprar uma guerra agora, mas também não vou apagar isso da minha cabeça."
                ];
                break;
            case 'memoria_positiva':
                $alvoTema = $memPos['alvo'] ?? null;
                $lembranca = ($alvoTema && !empty($memPos['evento'])) ? textoMemoriaNarrativaNPC($memPos['evento'], $alvoTema, 'confessionario') : '';
                $miolo = [
                    "$lembranca Por isso hoje eu penso duas vezes antes de colocar $alvoTema em risco.",
                    "$lembranca Aqui dentro a gente descobre rápido quem aparece quando você precisa.",
                    "$lembranca Isso criou uma confiança que eu não quero jogar fora por qualquer movimento.",
                    "$lembranca Eu sei que jogo muda, mas gratidão também faz parte das minhas decisões.",
                    "$lembranca É o tipo de atitude que faz eu querer proteger essa relação um pouco mais."
                ];
                break;
            case 'rival':
                $alvoTema = $maiorRival;
                $miolo = [
                    "Com $maiorRival eu já não consigo ter uma conversa sem sentir uma segunda intenção. A relação ficou pesada.",
                    "$maiorRival entrou de vez no meu radar. Não quero transformar tudo em briga, mas também não vou facilitar o jogo dessa pessoa.",
                    "Eu percebo que eu e $maiorRival estamos caminhando para lados opostos da casa. Uma hora essa conta vai chegar.",
                    "Quando $maiorRival se movimenta, eu presto atenção. Hoje é uma das pessoas que mais me deixa em alerta.",
                    "Eu já tentei relativizar algumas coisas com $maiorRival, só que a confiança simplesmente não acompanha."
                ];
                break;
            case 'aliado':
                $alvoTema = $melhorAliado;
                $miolo = [
                    "$melhorAliado é uma das poucas pessoas com quem eu consigo baixar a guarda de verdade. Isso aqui vale ouro.",
                    "Eu e $melhorAliado construímos uma troca que não nasceu de um dia pro outro. Quero cuidar dessa parceria.",
                    "Se eu tiver que escolher quem eu quero perto numa semana difícil, $melhorAliado aparece muito alto na minha lista.",
                    "Com $melhorAliado eu consigo falar de jogo sem sentir que cada palavra vai voltar contra mim.",
                    "Eu sei que aliança nenhuma é eterna, mas hoje $melhorAliado é alguém que eu realmente quero proteger."
                ];
                break;
            case 'romance':
                $alvoTema = $maiorRomance;
                $miolo = [
                    "Com $maiorRomance eu esqueço por alguns minutos que tem câmera, voto e estratégia em volta. Isso me assusta e me faz bem ao mesmo tempo.",
                    "Eu não sei dar nome ainda ao que está acontecendo com $maiorRomance, mas já passou da fase de ser só uma conversa legal.",
                    "$maiorRomance virou alguém que muda meu humor aqui dentro. Eu tento não misturar tudo, só que é difícil.",
                    "Tem um carinho crescendo com $maiorRomance e eu não quero transformar isso numa estratégia porque seria injusto com o que eu tô sentindo.",
                    "Eu fico me perguntando se lá fora essa conexão com $maiorRomance faria sentido também."
                ];
                break;
            case 'estrategia':
                $alvoTema = $alvoEstrategico;
                $miolo = [
                    "$alvoEstrategico está crescendo no meu radar. Não necessariamente porque eu não gosto, mas porque pode virar um problema de jogo.",
                    "Eu tô olhando os números e as relações. $alvoEstrategico consegue circular bem e isso pode ficar perigoso mais pra frente.",
                    "Meu próximo passo não precisa ser atacar $alvoEstrategico agora. Às vezes é melhor deixar a pessoa confortável e entender de onde vêm os votos.",
                    "Eu sinto que uma movimentação errada com $alvoEstrategico pode reorganizar a casa inteira. Por isso eu não quero agir no impulso.",
                    "Tem gente jogando só a semana atual; eu tô tentando pensar no que acontece se $alvoEstrategico chegar muito longe."
                ];
                break;
            case 'falsidade':
                $alvoTema = $pessoaFalsa;
                $miolo = [
                    "Eu ainda não consigo comprar completamente o discurso de $pessoaFalsa. A postura muda dependendo de quem está no quarto.",
                    "Com $pessoaFalsa eu sinto que sempre falta uma parte da história. Isso me deixa muito desconfiado(a).",
                    "Eu vejo $pessoaFalsa tentando ficar bem com lados que não conversam entre si. Pode ser habilidade; pra mim já acendeu alerta.",
                    "O problema pra mim não é $pessoaFalsa jogar. É eu nunca saber qual versão dessa pessoa está falando comigo.",
                    "Eu prefiro alguém que diga que é meu adversário do que ficar tentando adivinhar o que $pessoaFalsa realmente pensa."
                ];
                break;
            case 'decepcao':
                $alvoTema = $pessoaDecepcao;
                $miolo = [
                    "Eu esperava mais de $pessoaDecepcao. Acho que por isso doeu mais do que doeria vindo de outra pessoa.",
                    "Minha relação com $pessoaDecepcao perdeu uma camada de confiança que eu não sei se volta tão cedo.",
                    "Não tô com vontade de brigar com $pessoaDecepcao. É mais uma sensação de ter entendido que eu entreguei confiança demais.",
                    "Talvez $pessoaDecepcao veja tudo só como jogo, mas algumas atitudes tiveram peso pra mim.",
                    "Eu ainda gosto de $pessoaDecepcao, e talvez seja justamente por isso que essa decepção incomoda tanto."
                ];
                break;
            case 'medo_paredao':
                $miolo = [
                    "Estar no Paredão muda até o barulho da casa. Qualquer conversa baixa parece que é sobre você.",
                    "Eu tento parecer tranquilo(a), mas a verdade é que dá medo imaginar que essa pode ser minha última semana aqui.",
                    "Agora eu fico revendo cada escolha e pensando se teve alguma hora em que eu perdi a mão no jogo.",
                    "Eu não quero passar os próximos dias implorando pra ficar. Quero continuar sendo eu, só que é impossível não sentir o peso.",
                    "Bater no Paredão faz você descobrir quem chega perto por carinho e quem chega só porque a câmera tá ali."
                ];
                break;
            case 'lideranca':
                $miolo = [
                    "Ser Líder é ótimo até você perceber que precisa colocar nome e sobrenome nas suas escolhas. O poder vem com consequência.",
                    "Com a liderança eu consigo proteger gente importante, mas também sei que qualquer indicação pode virar uma rivalidade que dura semanas.",
                    "Eu queria aproveitar o quarto e a segurança, mas minha cabeça já está na formação do Paredão.",
                    "O mais difícil de ser Líder não é escolher um alvo; é saber quem vai interpretar essa escolha como uma declaração de guerra.",
                    "Essa liderança pode reposicionar meu jogo inteiro. Eu preciso usar sem me achar invencível."
                ];
                break;
            case 'anjo':
                $miolo = [
                    "O Anjo parece um presente, mas escolher quem proteger mexe com várias relações ao mesmo tempo.",
                    "Eu tô feliz com o Anjo, só que agora todo mundo quer entender o que minha imunidade vai dizer sobre minhas prioridades.",
                    "Dar imunidade pra alguém é quase tão revelador quanto votar. Eu preciso pensar no vínculo e no jogo.",
                    "Esse poder me dá chance de mostrar lealdade, mas também pode criar expectativa demais em quem ficar de fora."
                ];
                break;
            case 'monstro':
                $miolo = [
                    "O Monstro cansa o corpo, mas o que mais fica na cabeça é pensar por que meu nome foi escolhido.",
                    "Eu sei que Monstro faz parte, só que é impossível não ler um pouco de jogo por trás da escolha.",
                    "Eu tô tentando levar o Monstro na esportiva, mas claro que eu registrei quem me colocou aqui.",
                    "Esse castigo me deixou mais atento(a). Às vezes uma escolha aparentemente pequena mostra prioridade e distância."
                ];
                break;
            case 'desabafo':
                $miolo = [
                    "Hoje eu acordei cansado(a) de interpretar olhar, silêncio e conversa interrompida. A cabeça não desliga aqui.",
                    "Tem dias em que eu só queria sentar com alguém sem pensar se a conversa vai virar estratégia depois.",
                    "A saudade bate nos momentos mais aleatórios. Aí você lembra que não tem pra onde fugir da intensidade dessa casa.",
                    "Eu não imaginava o quanto seria difícil continuar reconhecendo a mim mesmo(a) no meio de tanta pressão.",
                    "Eu tô bem, mas não tô inteiro(a) todos os dias. Acho importante admitir isso também."
                ];
                break;
            case 'vt':
                $miolo = [
                    "Eu sei que eu apareço e gosto de viver as coisas com intensidade. Se chamarem isso de VT, tudo bem, mas pelo menos eu tô vivendo o programa.",
                    "Eu não entrei aqui pra passar pela temporada sem deixar marca. Quero que o público consiga lembrar do meu jogo.",
                    "Tem gente que confunde se posicionar com querer câmera. Eu prefiro correr esse risco do que desaparecer da edição.",
                    "Eu gosto de comunicar o que eu sinto e sei que isso chama atenção. Só não quero virar personagem de mim mesmo(a).",
                    "A câmera registra, mas não inventa tudo. Se eu tô rendendo assunto, é porque alguma coisa eu tô movimentando."
                ];
                break;
            case 'sonho_vitoria':
                $miolo = [
                    "Às vezes eu fecho o olho e imagino a final. Parece distante, mas foi pra tentar chegar naquele último dia que eu entrei.",
                    "Eu não quero chegar longe só por sobreviver. Quero olhar pra trás e sentir que construí uma trajetória aqui.",
                    "Quanto mais rodadas passam, mais real fica a possibilidade de ir até o fim. Isso me dá força e medo ao mesmo tempo.",
                    "Meu sonho continua sendo ouvir meu nome naquela final, só que agora eu entendo o preço de chegar lá.",
                    "Eu entrei querendo vencer. Hoje eu quero vencer sem perder completamente quem eu era quando entrei."
                ];
                break;
            default:
                $miolo = [
                    "A casa muda de humor muito rápido. Ontem eu achava que entendia os grupos; hoje já vejo rachaduras em lugares que pareciam sólidos.",
                    "Eu tô prestando atenção em quem me procura quando eu tenho poder e em quem continua perto quando eu não tenho nada pra oferecer.",
                    "As conversas mais importantes nem sempre são as mais barulhentas. Tem silêncio aqui que diz muita coisa.",
                    "Eu sinto que essa semana está reorganizando as relações. Tem proximidade nascendo e outras coisas esfriando sem ninguém admitir.",
                    "Cada rodada muda a forma como eu enxergo alguém. O difícil é não tomar uma decisão definitiva por causa de um único dia."
                ];
                break;
        }

        $corposRecentes = [];
        foreach (array_slice($historicoMeta, -8) as $m) {
            if (!empty($m['corpo'])) $corposRecentes[] = $m['corpo'];
        }
        $corpo = escolherFalaNaoRepetida($miolo, $corposRecentes);
        if ($corpo === '') continue;

        $aberturas = confessionarioAberturasPorPersonalidade($personalidade);
        $fechos = confessionarioFechosPorPersonalidade($personalidade);
        $inicio = confessionarioTrechoNaoRecente($aberturas, $historicoMeta, 'abertura');
        $fim = confessionarioTrechoNaoRecente($fechos, $historicoMeta, 'fecho');

        /* Nem toda fala precisa ter a mesma estrutura de três partes. */
        $estrutura = rand(1, 100);
        if ($estrutura <= 25) $texto = $corpo . ' ' . $fim;
        elseif ($estrutura <= 45) $texto = $inicio . ' ' . $corpo;
        else $texto = $inicio . ' ' . $corpo . ' ' . $fim;

        if (function_exists('flexionarTextoFalanteBBB')) {
            $texto = flexionarTextoFalanteBBB($texto, $nome, $jogadores);
        }

        $emojis = [
            'memoria_negativa'=>'🧠','memoria_positiva'=>'🤝','rival'=>'🔥','aliado'=>'🤝','romance'=>'💘',
            'estrategia'=>'♟️','falsidade'=>'🎭','decepcao'=>'💔','medo_paredao'=>'🚨','lideranca'=>'👑',
            'anjo'=>'😇','monstro'=>'👹','desabafo'=>'💭','vt'=>'📺','sonho_vitoria'=>'🏆','observacao'=>'👀'
        ];
        $emoji = $emojis[$tema] ?? '🎥';
        $falaHtml = $emoji . ' <b>' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '</b>: “' . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '”';
        $falas[] = $falaHtml;

        foreach ($jogadores as &$jAtualizar) {
            if (($jAtualizar['nome'] ?? '') !== $nome) continue;
            if (!isset($jAtualizar['confessionarios']) || !is_array($jAtualizar['confessionarios'])) $jAtualizar['confessionarios'] = [];
            if (!isset($jAtualizar['confessionario_meta']) || !is_array($jAtualizar['confessionario_meta'])) $jAtualizar['confessionario_meta'] = [];

            $jAtualizar['confessionarios'][] = $texto;
            $jAtualizar['confessionario_meta'][] = [
                'rodada' => (int)($_SESSION['rodada'] ?? 1),
                'tema' => $tema,
                'alvo' => $alvoTema,
                'personalidade' => $personalidade,
                'abertura' => $inicio,
                'corpo' => $corpo,
                'fecho' => $fim
            ];
            if (count($jAtualizar['confessionarios']) > 36) $jAtualizar['confessionarios'] = array_slice($jAtualizar['confessionarios'], -36);
            if (count($jAtualizar['confessionario_meta']) > 18) $jAtualizar['confessionario_meta'] = array_slice($jAtualizar['confessionario_meta'], -18);
            break;
        }
        unset($jAtualizar);

        if (!isset($_SESSION['historico_confessionarios_narrativos']) || !is_array($_SESSION['historico_confessionarios_narrativos'])) {
            $_SESSION['historico_confessionarios_narrativos'] = [];
        }
        $_SESSION['historico_confessionarios_narrativos'][] = [
            'rodada' => (int)($_SESSION['rodada'] ?? 1),
            'nome' => $nome,
            'tema' => $tema,
            'alvo' => $alvoTema,
            'fala' => $texto
        ];
        if (count($_SESSION['historico_confessionarios_narrativos']) > 160) {
            $_SESSION['historico_confessionarios_narrativos'] = array_slice($_SESSION['historico_confessionarios_narrativos'], -160);
        }

        if (function_exists('aplicarImpactoPublicoConfessionario')) {
            $tipoImpacto = in_array($tema, ['rival','memoria_negativa','falsidade'], true) ? 'rival' : ($tema === 'vt' ? 'vt' : $tema);
            aplicarImpactoPublicoConfessionario($jogadores, $nome, $tipoImpacto);
        }
    }

    shuffle($falas);
    return $falas;
}

function prepararConfessionarioDaRodada(&$jogadores, $meuNome)
{
    if (isset($_SESSION['confessionario_feito'])) return;
    $_SESSION['confessionario_falas'] = gerarConfessionarioInteligente($jogadores, $meuNome);
    $_SESSION['confessionario_feito'] = true;
    $_SESSION['jogadores'] = $jogadores;
}
