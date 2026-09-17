<?php

/* =========================================================
   📱 FEED BBB - REAÇÃO DO PÚBLICO
   ========================================================= */


/* =========================================================
   🧱 GARANTIR ESTRUTURA
   ========================================================= */

function garantirFeedPublico()
{
    if (
        !isset($_SESSION['feed_publico']) ||
        !is_array($_SESSION['feed_publico'])
    ) {
        $_SESSION['feed_publico'] = [];
    }

    if (
        !isset($_SESSION['feed_publico_processados']) ||
        !is_array($_SESSION['feed_publico_processados'])
    ) {
        $_SESSION['feed_publico_processados'] = [];
    }
}


/* =========================================================
   👤 PERFIS FICTÍCIOS
   ========================================================= */

function perfisFeedPublico()
{
    return [

        [
            'autor' => 'Realityzera',
            'arroba' => '@realityzera'
        ],

        [
            'autor' => 'BBB Sem Filtro',
            'arroba' => '@bbbsemfiltro'
        ],

        [
            'autor' => 'Espiadinha',
            'arroba' => '@espiadinha'
        ],

        [
            'autor' => 'Central dos Surtos',
            'arroba' => '@centraldossurtos'
        ],

        [
            'autor' => 'Plantão Reality',
            'arroba' => '@plantaoreality'
        ],

        [
            'autor' => 'Fofoca Reality',
            'arroba' => '@fofocareality'
        ],

        [
            'autor' => 'Telinha Reality',
            'arroba' => '@telinhareality'
        ],

        [
            'autor' => 'Falando de BBB',
            'arroba' => '@falandodebbb'
        ],

        [
            'autor' => 'Central BBB',
            'arroba' => '@centralbbb'
        ],

        [
            'autor' => 'Viciados em Reality',
            'arroba' => '@viciadosemreality'
        ]
    ];
}


/* =========================================================
   🎲 ESCOLHER ITEM
   ========================================================= */

function escolherFeedPublico($itens)
{
    if (empty($itens)) {
        return '';
    }

    return $itens[
        array_rand($itens)
    ];
}


/* =========================================================
   🔎 BUSCAR JOGADOR
   ========================================================= */

function buscarParticipanteFeed(
    $jogadores,
    $nome
) {
    foreach ($jogadores as $j) {

        if (
            mb_strtolower(
                trim($j['nome'] ?? ''),
                'UTF-8'
            )
            ===
            mb_strtolower(
                trim($nome),
                'UTF-8'
            )
        ) {
            return $j;
        }
    }

    return null;
}


/* =========================================================
   📊 SENTIMENTO DO PÚBLICO
   ========================================================= */

function sentimentoFeedPublico(
    $jogadores,
    $nome
) {
    $popularidade =
        obterPopularidadeJogador(
            $jogadores,
            $nome
        );

    $nivel =
        nivelPopularidadePublica(
            $popularidade
        );


    if (
        $nivel === 'favorito' ||
        $nivel === 'querido'
    ) {
        return 'positivo';
    }


    if (
        $nivel === 'mal_visto' ||
        $nivel === 'cancelado'
    ) {
        return 'negativo';
    }


    /*
     * Participantes neutros realmente
     * dividem a opinião.
     */

    $sorteio = rand(1, 100);

    if ($sorteio <= 40) {
        return 'positivo';
    }

    if ($sorteio <= 80) {
        return 'negativo';
    }

    return 'misto';
}


/* =========================================================
   🗣️ COMENTÁRIO PELA PERSONALIDADE
   ========================================================= */

function comentarioPersonalidadeFeed(
    $jogador,
    $sentimento
) {
    if (!$jogador) {
        return null;
    }

    $nome =
        $jogador['nome'] ?? '';

    $personalidade =
        $jogador['personalidade'] ?? 'Neutro';


    if (
        $personalidade === 'Barraqueiro' ||
        $personalidade === 'Explosivo'
    ) {

        if ($sentimento === 'positivo') {

            return escolherFeedPublico([
                "$nome nasceu pra entregar entretenimento KKKKK.",
                "Podem falar o que quiser, mas $nome entrega o programa.",
                "$nome não deixa essa casa ter UM minuto de paz 😭"
            ]);
        }

        return escolherFeedPublico([
            "$nome precisa aprender que nem toda situação precisa virar uma guerra.",
            "Toda semana $nome arruma uma confusão diferente.",
            "$nome tá passando MUITO do ponto ultimamente."
        ]);
    }


    if ($personalidade === 'Planta') {

        return escolherFeedPublico([
            "Alguém avisa $nome que o programa já começou?",
            "$nome precisa aparecer urgentemente.",
            "Eu esqueço que $nome tá nessa edição às vezes 😭"
        ]);
    }


    if (
        $personalidade === 'Estrategista' ||
        $personalidade === 'Manipulador'
    ) {

        if ($sentimento === 'positivo') {

            return escolherFeedPublico([
                "$nome tá jogando xadrez enquanto metade da casa joga dama.",
                "Pode gostar ou não, mas $nome tá JOGANDO.",
                "$nome pensando cinco passos na frente de todo mundo 👀"
            ]);
        }

        return escolherFeedPublico([
            "Eu não confio NADA nesse jogo do $nome.",
            "$nome acha que ninguém tá percebendo as movimentações.",
            "Esse jogo do $nome ainda vai voltar contra ele."
        ]);
    }


    if (
        $personalidade === 'Fofo' ||
        $personalidade === 'Emocional'
    ) {

        if ($sentimento === 'positivo') {

            return escolherFeedPublico([
                "$nome me ganhou completamente 😭",
                "Eu protejo $nome de qualquer coisa nessa casa.",
                "$nome tem meu coração e infelizmente é isso."
            ]);
        }
    }


    if ($personalidade === 'Influencer') {

        return escolherFeedPublico([
            "$nome sabe exatamente como render assunto.",
            "O VT do $nome vem forte hoje KKKKK.",
            "$nome conhece uma câmera de longe."
        ]);
    }


    if (
        $personalidade === 'Falso' &&
        $sentimento === 'negativo'
    ) {

        return escolherFeedPublico([
            "Não compro esse personagem do $nome nem um pouco.",
            "Cada dia eu confio menos no $nome.",
            "$nome muda o discurso dependendo de quem tá perto 👀"
        ]);
    }


    return null;
}


/* =========================================================
   📝 GERAR TEXTO SOBRE PARTICIPANTE
   ========================================================= */

function gerarComentarioParticipanteFeed(
    $jogadores,
    $nome,
    $contexto = 'geral'
) {
    $jogador =
        buscarParticipanteFeed(
            $jogadores,
            $nome
        );

    if (!$jogador) {
        return null;
    }


    $sentimento =
        sentimentoFeedPublico(
            $jogadores,
            $nome
        );


    /* =========================
       👑 LÍDER
       ========================= */

    if ($contexto === 'lider') {

        if ($sentimento === 'positivo') {

            return escolherFeedPublico([
                "$nome LÍDER EU PEDI SIM 😭😭",
                "Finalmente uma liderança do $nome!",
                "$nome ganhou o Líder e agora essa semana promete.",
                "Agora quero ver quem o $nome vai colocar no Paredão 👀"
            ]);
        }

        return escolherFeedPublico([
            "$nome Líder... essa semana vai ser longa.",
            "Logo $nome com esse poder todo? medo.",
            "Quero só observar as decisões dessa liderança do $nome 👀"
        ]);
    }


    /* =========================
       😇 ANJO
       ========================= */

    if ($contexto === 'anjo') {

        if ($sentimento === 'positivo') {

            return escolherFeedPublico([
                "$nome de Anjo, eu gostei disso.",
                "Agora quero saber QUEM $nome vai imunizar 👀",
                "O colar do Anjo caiu em boas mãos dessa vez."
            ]);
        }

        return escolherFeedPublico([
            "$nome com o Anjo... quero só ver essa imunidade.",
            "Essa escolha do Anjo ainda vai dar discussão.",
            "Quem será que $nome vai salvar? 👀"
        ]);
    }


    /* =========================
       👹 MONSTRO
       ========================= */

    if ($contexto === 'monstro') {

        return escolherFeedPublico([
            "$nome no Monstro KKKKKKK eu não aguento.",
            "A cara do $nome recebendo o Monstro foi TUDO 😭",
            "$nome começou a semana sofrendo.",
            "O Monstro não perdoou $nome."
        ]);
    }


    /* =========================
       🧱 PAREDÃO
       ========================= */

    if ($contexto === 'paredao') {

        if ($sentimento === 'positivo') {

            return escolherFeedPublico([
                "Se depender de mim $nome NÃO SAI.",
                "Já estou fechada com #Fica" . hashtagNomeFeed($nome),
                "$nome no Paredão e eu oficialmente em modo mutirão 😭"
            ]);
        }

        return escolherFeedPublico([
            "Chegou a hora do $nome, desculpa.",
            "#Fora" . hashtagNomeFeed($nome) . " e sem discussão.",
            "$nome no Paredão... agora vai.",
            "Meu voto já tem nome e é $nome."
        ]);
    }


    /* =========================
       ❌ ELIMINAÇÃO
       ========================= */

    if ($contexto === 'eliminacao') {

        if ($sentimento === 'positivo') {

            return escolherFeedPublico([
                "Ainda não acredito que $nome saiu 😭",
                "$nome vai fazer falta nessa casa.",
                "O público errou MUITO nessa eliminação."
            ]);
        }

        return escolherFeedPublico([
            "A trajetória do $nome chegou ao fim mesmo.",
            "Essa eliminação do $nome era questão de tempo.",
            "O jogo segue sem $nome."
        ]);
    }


    /* =========================
       💥 TRETA
       ========================= */

    if ($contexto === 'treta') {

        if ($sentimento === 'positivo') {

            return escolherFeedPublico([
                "$nome entregando entretenimento de novo KKKKK.",
                "Toda vez que $nome entra numa discussão rende episódio.",
                "$nome não veio passar férias nessa casa."
            ]);
        }

        return escolherFeedPublico([
            "$nome tá se queimando MUITO.",
            "Essa discussão não pegou nada bem pro $nome.",
            "$nome conseguiu piorar a própria situação em cinco minutos."
        ]);
    }


    /* =========================
       ❤️ ROMANCE
       ========================= */

    if ($contexto === 'romance') {

        return escolherFeedPublico([
            "$nome vivendo romance e eu infelizmente acompanhando tudo 👀",
            "Eu disse que não ia shippar ninguém e aí veio $nome.",
            "O romance do $nome já tem torcida nas redes."
        ]);
    }


    /* =========================
       🌐 GERAL
       ========================= */

    $comentarioPersonalidade =
        comentarioPersonalidadeFeed(
            $jogador,
            $sentimento
        );

    if (
        $comentarioPersonalidade &&
        rand(1, 100) <= 55
    ) {
        return $comentarioPersonalidade;
    }


    if ($sentimento === 'positivo') {

        return escolherFeedPublico([
            "$nome tá carregando essa edição nas costas 😭",
            "Toda vez que $nome aparece acontece alguma coisa.",
            "Não esperava gostar tanto do $nome nessa temporada.",
            "$nome tá crescendo MUITO no jogo."
        ]);
    }


    if ($sentimento === 'negativo') {

        return escolherFeedPublico([
            "Não aguento mais $nome, sério.",
            "$nome pode sair já.",
            "Como ainda tem gente defendendo $nome?",
            "Toda semana $nome consegue se complicar mais."
        ]);
    }


    return escolherFeedPublico([
        "Eu ainda não sei o que pensar sobre $nome.",
        "$nome divide demais minha opinião.",
        "Tem hora que eu gosto do $nome e cinco minutos depois mudo de ideia.",
        "O júri sobre $nome segue em deliberação."
    ]);
}


/* =========================================================
   #️⃣ LIMPAR NOME PARA HASHTAG
   ========================================================= */

function hashtagNomeFeed($nome)
{
    return preg_replace(
        '/[^\p{L}\p{N}]/u',
        '',
        $nome
    );
}


/* =========================================================
   📈 ENGAJAMENTO
   ========================================================= */

function calcularEngajamentoFeed(
    $jogadores,
    $nomes = []
) {
    $popularidadeMedia = 50;

    $valores = [];


    foreach ($nomes as $nome) {

        $valores[] =
            obterPopularidadeJogador(
                $jogadores,
                $nome
            );
    }


    if (!empty($valores)) {

        $popularidadeMedia =
            array_sum($valores)
            /
            count($valores);
    }


    /*
     * Tanto favoritos quanto participantes
     * muito rejeitados rendem assunto.
     */

    $intensidade =
        abs($popularidadeMedia - 50);


    $curtidas =
        rand(800, 4500)
        +
        (int) round(
            $intensidade * 380
        );


    $comentarios =
        rand(100, 1200)
        +
        (int) round(
            $intensidade * 70
        );


    $reposts =
        rand(50, 700)
        +
        (int) round(
            $intensidade * 35
        );


    return [
        'curtidas' => $curtidas,
        'comentarios' => $comentarios,
        'reposts' => $reposts
    ];
}


/* =========================================================
   ➕ ADICIONAR POST
   ========================================================= */

function adicionarPostFeedPublico(
    $jogadores,
    $texto,
    $categoria = 'geral',
    $nomes = []
) {
    garantirFeedPublico();


    if (trim($texto) === '') {
        return;
    }


    $perfis =
        perfisFeedPublico();


    $perfil =
        $perfis[
            array_rand($perfis)
        ];


    $engajamento =
        calcularEngajamentoFeed(
            $jogadores,
            $nomes
        );


    $_SESSION['feed_publico'][] = [

        'id' => uniqid('feed_', true),

        'autor' => $perfil['autor'],

        'arroba' => $perfil['arroba'],

        'texto' => $texto,

        'categoria' => $categoria,

        'rodada' =>
            $_SESSION['rodada'] ?? 1,

        'curtidas' =>
            $engajamento['curtidas'],

        'comentarios' =>
            $engajamento['comentarios'],

        'reposts' =>
            $engajamento['reposts']
    ];


    /*
     * Evita uma sessão gigantesca
     * em temporadas muito longas.
     */

    if (
        count($_SESSION['feed_publico']) > 80
    ) {

        $_SESSION['feed_publico'] =
            array_slice(
                $_SESSION['feed_publico'],
                -80
            );
    }
}


/* =========================================================
   🔍 NOMES CITADOS NO AO VIVO
   ========================================================= */

function nomesCitadosNoEventoFeed(
    $jogadores,
    $evento
) {
    $nomes = [];


    foreach ($jogadores as $j) {

        $nome =
            trim(
                $j['nome'] ?? ''
            );


        if ($nome === '') {
            continue;
        }


        if (
            mb_stripos(
                $evento,
                $nome,
                0,
                'UTF-8'
            ) !== false
        ) {

            $nomes[] = $nome;
        }
    }


    return array_values(
        array_unique($nomes)
    );
}


/* =========================================================
   🧠 IDENTIFICAR CONTEXTO DO EVENTO
   ========================================================= */

function contextoEventoFeed($evento)
{
    $texto =
        mb_strtolower(
            $evento,
            'UTF-8'
        );


    if (
        mb_strpos($texto, 'líder') !== false ||
        mb_strpos($texto, 'lider') !== false
    ) {
        return 'lider';
    }


    if (
        mb_strpos($texto, 'anjo') !== false
    ) {
        return 'anjo';
    }


    if (
        mb_strpos($texto, 'monstro') !== false
    ) {
        return 'monstro';
    }


    if (
        mb_strpos($texto, 'paredão') !== false ||
        mb_strpos($texto, 'paredao') !== false
    ) {
        return 'paredao';
    }


    if (
        mb_strpos($texto, 'elimin') !== false
    ) {
        return 'eliminacao';
    }


    if (
        mb_strpos($texto, 'romance') !== false ||
        mb_strpos($texto, 'beij') !== false ||
        mb_strpos($texto, 'casal') !== false
    ) {
        return 'romance';
    }


    if (
        mb_strpos($texto, 'discut') !== false ||
        mb_strpos($texto, 'treta') !== false ||
        mb_strpos($texto, 'brig') !== false ||
        mb_strpos($texto, 'fofoca') !== false ||
        mb_strpos($texto, 'rival') !== false ||
        mb_strpos($texto, 'discórdia') !== false ||
        mb_strpos($texto, 'discordia') !== false
    ) {
        return 'treta';
    }


    return 'geral';
}


/* =========================================================
   📢 PROCESSAR AO VIVO
   ========================================================= */

function processarAoVivoNoFeed(
    $jogadores,
    $rodada,
    $meuNome
) {
    $eventos =
        $_SESSION['evento_extra'] ?? [];


    if (!is_array($eventos)) {
        return;
    }


    /*
     * Só precisamos olhar os acontecimentos
     * mais recentes.
     */

    $eventos =
        array_slice(
            $eventos,
            -15
        );


    foreach ($eventos as $evento) {

        $evento =
            trim(
                (string) $evento
            );


        if ($evento === '') {
            continue;
        }


        $chave =
            sha1(
                'ao_vivo|' .
                $rodada .
                '|' .
                $evento
            );


        if (
            isset(
                $_SESSION[
                    'feed_publico_processados'
                ][$chave]
            )
        ) {
            continue;
        }


        $nomes =
            nomesCitadosNoEventoFeed(
                $jogadores,
                $evento
            );


        $contexto =
            contextoEventoFeed(
                $evento
            );


        /*
         * Quando duas pessoas citadas já são rivais,
         * o público percebe a narrativa.
         */

        if (count($nomes) >= 2) {

            $a = $nomes[0];
            $b = $nomes[1];


            if (
                saoRivais(
                    $jogadores,
                    $a,
                    $b,
                    $meuNome
                ) ||
                saoRivais(
                    $jogadores,
                    $b,
                    $a,
                    $meuNome
                )
            ) {

                $textoRivalidade =
                    escolherFeedPublico([
                        "$a e $b não conseguem passar UMA semana sem se estranhar 😭",
                        "A rivalidade $a x $b tá virando a história principal dessa edição.",
                        "Toda vez que $a e $b ficam no mesmo ambiente eu já espero confusão 👀",
                        "$a e $b se olhando e eu sabendo que vem treta."
                    ]);


                adicionarPostFeedPublico(
                    $jogadores,
                    $textoRivalidade,
                    'rivalidade',
                    [$a, $b]
                );
            }
        }


        /*
         * Comentário individual.
         */

        if (!empty($nomes)) {

            $principal =
                $nomes[0];


            $texto =
                gerarComentarioParticipanteFeed(
                    $jogadores,
                    $principal,
                    $contexto
                );


            if ($texto) {

                adicionarPostFeedPublico(
                    $jogadores,
                    $texto,
                    $contexto,
                    $nomes
                );
            }

        } else {

            /*
             * Eventos gerais também podem render
             * comentários do público.
             */

            if (rand(1, 100) <= 50) {

                $textoGeral =
                    escolherFeedPublico([
                        "Essa edição não dá cinco minutos de paz.",
                        "O programa hoje simplesmente decidiu ENTREGAR.",
                        "Eu abri o Ao Vivo por cinco minutos e já aconteceu tudo.",
                        "Quem tá escrevendo o roteiro dessa temporada merece aumento KKKKK.",
                        "Eu só queria assistir em paz e essa casa não deixa."
                    ]);


                adicionarPostFeedPublico(
                    $jogadores,
                    $textoGeral,
                    'geral'
                );
            }
        }


        $_SESSION[
            'feed_publico_processados'
        ][$chave] = true;
    }
}


/* =========================================================
   👑 LOCALIZAR STATUS
   ========================================================= */

function participantesComStatusFeed(
    $jogadores,
    $status
) {
    $nomes = [];


    foreach ($jogadores as $j) {

        if (
            !empty(
                $j['status'][$status]
            )
        ) {

            $nome =
                $j['nome'] ?? '';


            if ($nome !== '') {
                $nomes[] = $nome;
            }
        }
    }


    return $nomes;
}


/* =========================================================
   🧱 EXTRAIR NOMES DO PAREDÃO
   ========================================================= */

function extrairNomesParedaoFeed()
{
    $fontes = [

        $_SESSION['paredao'] ?? null,

        $_SESSION['paredao_atual'] ?? null
    ];


    foreach ($fontes as $fonte) {

        if (
            !$fonte ||
            !is_array($fonte)
        ) {
            continue;
        }


        $nomes = [];


        foreach ($fonte as $item) {

            if (is_string($item)) {

                $nomes[] = $item;

            } elseif (
                is_array($item) &&
                !empty($item['nome'])
            ) {

                $nomes[] =
                    $item['nome'];
            }
        }


        if (!empty($nomes)) {

            return array_values(
                array_unique($nomes)
            );
        }
    }


    return [];
}


/* =========================================================
   ❤️ EXTRAIR ROMANCES
   Tenta suportar estruturas diferentes
   ========================================================= */

function extrairRomancesFeed($jogadores)
{
    $nomesValidos = [];

    foreach ($jogadores as $j) {

        $nome =
            $j['nome'] ?? '';

        if ($nome !== '') {

            $nomesValidos[
                mb_strtolower(
                    $nome,
                    'UTF-8'
                )
            ] = $nome;
        }
    }


    $pares = [];


    foreach ($jogadores as $j) {

        $nomeA =
            $j['nome'] ?? '';

        $romances =
            $j['romances'] ?? [];


        if (
            $nomeA === '' ||
            !is_array($romances)
        ) {
            continue;
        }


        foreach (
            $romances as $chave => $valor
        ) {

            $nomeB = null;


            if (
                is_string($chave) &&
                !is_numeric($chave)
            ) {

                $chaveNormalizada =
                    mb_strtolower(
                        $chave,
                        'UTF-8'
                    );


                if (
                    isset(
                        $nomesValidos[
                            $chaveNormalizada
                        ]
                    )
                ) {

                    $nomeB =
                        $nomesValidos[
                            $chaveNormalizada
                        ];
                }
            }


            if (
                !$nomeB &&
                is_string($valor)
            ) {

                $valorNormalizado =
                    mb_strtolower(
                        $valor,
                        'UTF-8'
                    );


                if (
                    isset(
                        $nomesValidos[
                            $valorNormalizado
                        ]
                    )
                ) {

                    $nomeB =
                        $nomesValidos[
                            $valorNormalizado
                        ];
                }
            }


            if (
                !$nomeB &&
                is_array($valor) &&
                !empty($valor['nome'])
            ) {

                $nomeB =
                    $valor['nome'];
            }


            if (
                !$nomeB ||
                $nomeA === $nomeB
            ) {
                continue;
            }


            $par = [$nomeA, $nomeB];

            sort(
                $par,
                SORT_NATURAL |
                SORT_FLAG_CASE
            );


            $chavePar =
                implode(
                    '|',
                    $par
                );


            $pares[$chavePar] =
                $par;
        }
    }


    return array_values(
        $pares
    );
}


/* =========================================================
   💥 ENCONTRAR RIVALIDADE MAIS FORTE
   ========================================================= */

function rivalidadeMaisForteFeed(
    $jogadores,
    $meuNome
) {
    $melhor = null;

    $melhorScore = 0;

    $total =
        count($jogadores);


    for ($i = 0; $i < $total; $i++) {

        for (
            $k = $i + 1;
            $k < $total;
            $k++
        ) {

            $a =
                $jogadores[$i]['nome'] ?? '';

            $b =
                $jogadores[$k]['nome'] ?? '';


            if (
                $a === '' ||
                $b === ''
            ) {
                continue;
            }


            $relAB =
                obterRelacaoCompleta(
                    $jogadores,
                    $a,
                    $b,
                    $meuNome
                );


            $relBA =
                obterRelacaoCompleta(
                    $jogadores,
                    $b,
                    $a,
                    $meuNome
                );


            $score =
                max(
                    $relAB['rivalidade'] ?? 0,
                    $relBA['rivalidade'] ?? 0
                );


            if (
                $relAB['score'] < 0
            ) {
                $score +=
                    abs(
                        $relAB['score']
                    );
            }


            if (
                $relBA['score'] < 0
            ) {
                $score +=
                    abs(
                        $relBA['score']
                    );
            }


            if (
                $score > $melhorScore &&
                (
                    saoRivais(
                        $jogadores,
                        $a,
                        $b,
                        $meuNome
                    ) ||
                    saoRivais(
                        $jogadores,
                        $b,
                        $a,
                        $meuNome
                    )
                )
            ) {

                $melhorScore =
                    $score;


                $melhor = [
                    'a' => $a,
                    'b' => $b,
                    'score' => $score
                ];
            }
        }
    }


    return $melhor;
}


/* =========================================================
   🎮 PROCESSAR ESTADO ATUAL
   ========================================================= */

function processarEstadoNoFeed(
    $jogadores,
    $rodada,
    $meuNome
) {

    /* =========================
       👑 LÍDER
       ========================= */

    $lideres =
        participantesComStatusFeed(
            $jogadores,
            'lider'
        );


    foreach ($lideres as $nome) {

        $chave =
            "lider|$rodada|$nome";


        if (
            !isset(
                $_SESSION[
                    'feed_publico_processados'
                ][$chave]
            )
        ) {

            $texto =
                gerarComentarioParticipanteFeed(
                    $jogadores,
                    $nome,
                    'lider'
                );


            adicionarPostFeedPublico(
                $jogadores,
                $texto,
                'lider',
                [$nome]
            );


            $_SESSION[
                'feed_publico_processados'
            ][$chave] = true;
        }
    }


    /* =========================
       😇 ANJO
       ========================= */

    $anjos =
        participantesComStatusFeed(
            $jogadores,
            'anjo'
        );


    foreach ($anjos as $nome) {

        $chave =
            "anjo|$rodada|$nome";


        if (
            !isset(
                $_SESSION[
                    'feed_publico_processados'
                ][$chave]
            )
        ) {

            $texto =
                gerarComentarioParticipanteFeed(
                    $jogadores,
                    $nome,
                    'anjo'
                );


            adicionarPostFeedPublico(
                $jogadores,
                $texto,
                'anjo',
                [$nome]
            );


            $_SESSION[
                'feed_publico_processados'
            ][$chave] = true;
        }
    }


    /* =========================
       👹 MONSTRO
       ========================= */

    $monstros =
        participantesComStatusFeed(
            $jogadores,
            'monstro'
        );


    foreach ($monstros as $nome) {

        $chave =
            "monstro|$rodada|$nome";


        if (
            !isset(
                $_SESSION[
                    'feed_publico_processados'
                ][$chave]
            )
        ) {

            $texto =
                gerarComentarioParticipanteFeed(
                    $jogadores,
                    $nome,
                    'monstro'
                );


            adicionarPostFeedPublico(
                $jogadores,
                $texto,
                'monstro',
                [$nome]
            );


            $_SESSION[
                'feed_publico_processados'
            ][$chave] = true;
        }
    }


    /* =========================
       🧱 PAREDÃO
       ========================= */

    $paredao =
        extrairNomesParedaoFeed();


    if (!empty($paredao)) {

        $paredaoOrdenado =
            $paredao;

        sort($paredaoOrdenado);


        $chave =
            'paredao|' .
            $rodada .
            '|' .
            implode(
                '|',
                $paredaoOrdenado
            );


        if (
            !isset(
                $_SESSION[
                    'feed_publico_processados'
                ][$chave]
            )
        ) {

            foreach (
                array_slice(
                    $paredao,
                    0,
                    2
                )
                as $nome
            ) {

                $texto =
                    gerarComentarioParticipanteFeed(
                        $jogadores,
                        $nome,
                        'paredao'
                    );


                adicionarPostFeedPublico(
                    $jogadores,
                    $texto,
                    'paredao',
                    [$nome]
                );
            }


            $_SESSION[
                'feed_publico_processados'
            ][$chave] = true;
        }
    }


    /* =========================
       ❤️ ROMANCES
       ========================= */

    $romances =
        extrairRomancesFeed(
            $jogadores
        );


    foreach ($romances as $par) {

        [$a, $b] = $par;


        $chave =
            'romance|' .
            implode(
                '|',
                $par
            );


        if (
            isset(
                $_SESSION[
                    'feed_publico_processados'
                ][$chave]
            )
        ) {
            continue;
        }


        $texto =
            escolherFeedPublico([
                "$a e $b achando que ninguém tá percebendo 👀",
                "Eu disse que não ia shippar ninguém e aí vieram $a e $b.",
                "Já existe torcida pra $a e $b e eu infelizmente faço parte.",
                "$a e $b entregando migalhas e a internet construindo um casamento."
            ]);


        adicionarPostFeedPublico(
            $jogadores,
            $texto,
            'romance',
            [$a, $b]
        );


        $_SESSION[
            'feed_publico_processados'
        ][$chave] = true;
    }


    /* =========================
       💥 RIVALIDADE
       ========================= */

    $rivalidade =
        rivalidadeMaisForteFeed(
            $jogadores,
            $meuNome
        );


    if ($rivalidade) {

        $a =
            $rivalidade['a'];

        $b =
            $rivalidade['b'];


        $par = [$a, $b];

        sort($par);


        $chave =
            'rivalidade|' .
            implode(
                '|',
                $par
            );


        if (
            !isset(
                $_SESSION[
                    'feed_publico_processados'
                ][$chave]
            )
        ) {

            $texto =
                escolherFeedPublico([
                    "$a e $b se odeiam num nível que já virou entretenimento.",
                    "Essa rivalidade $a x $b ainda vai render MUITO.",
                    "Toda vez que $a encontra $b o clima muda na hora 👀",
                    "$a e $b são incapazes de fingir que se suportam KKKKK."
                ]);


            adicionarPostFeedPublico(
                $jogadores,
                $texto,
                'rivalidade',
                [$a, $b]
            );


            $_SESSION[
                'feed_publico_processados'
            ][$chave] = true;
        }
    }
}


/* =========================================================
   🚀 ATUALIZAR FEED
   ========================================================= */

function atualizarFeedPublico(
    $jogadores,
    $fase,
    $rodada,
    $meuNome
) {
    garantirFeedPublico();


    /* =========================
       🎬 PRIMEIRO POST
       ========================= */

    if (
        empty(
            $_SESSION['feed_publico']
        )
    ) {

        adicionarPostFeedPublico(
            $jogadores,
            escolherFeedPublico([
                "Começou! Quero ver quem vai entregar entretenimento nessa edição 👀",
                "Primeiro dia e eu já estou escolhendo meus favoritos.",
                "Prometi que não ia me estressar com reality esse ano. Mentira.",
                "Nova temporada oficialmente aberta. Que comece o caos."
            ]),
            'inicio'
        );
    }


    processarAoVivoNoFeed(
        $jogadores,
        $rodada,
        $meuNome
    );


    processarEstadoNoFeed(
        $jogadores,
        $rodada,
        $meuNome
    );
}


/* =========================================================
   🔥 TRENDING TOPICS
   ========================================================= */

function gerarTrendingTopicsFeed(
    $jogadores,
    $rodada,
    $meuNome
) {
    $topics = [];


    $adicionar =
        function (
            $tag,
            $peso
        ) use (&$topics) {

            if ($tag === '#') {
                return;
            }


            if (
                !isset(
                    $topics[$tag]
                ) ||
                $peso >
                $topics[$tag]
            ) {

                $topics[$tag] =
                    $peso;
            }
        };


    /* =========================
       👑 LÍDER
       ========================= */

    $lideres =
        participantesComStatusFeed(
            $jogadores,
            'lider'
        );


    foreach ($lideres as $nome) {

        $adicionar(
            '#' .
            hashtagNomeFeed($nome) .
            'Líder',
            95
        );
    }


    /* =========================
       🧱 PAREDÃO
       ========================= */

    foreach (
        extrairNomesParedaoFeed()
        as $nome
    ) {

        $popularidade =
            obterPopularidadeJogador(
                $jogadores,
                $nome
            );


        if ($popularidade >= 50) {

            $adicionar(
                '#Fica' .
                hashtagNomeFeed($nome),
                100 + $popularidade
            );

        } else {

            $adicionar(
                '#Fora' .
                hashtagNomeFeed($nome),
                100 +
                (100 - $popularidade)
            );
        }
    }


    /* =========================
       ⭐ FAVORITO
       ========================= */

    $ordenados =
        $jogadores;


    usort(
        $ordenados,
        function ($a, $b) {

            return
                ($b['popularidade'] ?? 50)
                <=>
                ($a['popularidade'] ?? 50);
        }
    );


    if (!empty($ordenados)) {

        $favorito =
            $ordenados[0];


        $adicionar(
            '#' .
            hashtagNomeFeed(
                $favorito['nome'] ?? ''
            ) .
            'Merece',
            80 +
            ($favorito[
                'popularidade'
            ] ?? 50)
        );
    }


    /* =========================
       📉 REJEIÇÃO
       ========================= */

    $piores =
        $jogadores;


    usort(
        $piores,
        function ($a, $b) {

            return
                ($a['popularidade'] ?? 50)
                <=>
                ($b['popularidade'] ?? 50);
        }
    );


    if (
        !empty($piores) &&
        ($piores[0]['popularidade'] ?? 50)
        <= 35
    ) {

        $adicionar(
            '#Fora' .
            hashtagNomeFeed(
                $piores[0]['nome'] ?? ''
            ),
            85 +
            (
                100 -
                (
                    $piores[0][
                        'popularidade'
                    ] ?? 50
                )
            )
        );
    }


    /* =========================
       💥 RIVALIDADE
       ========================= */

    $rivalidade =
        rivalidadeMaisForteFeed(
            $jogadores,
            $meuNome
        );


    if ($rivalidade) {

        $adicionar(
            '#' .
            hashtagNomeFeed(
                $rivalidade['a']
            ) .
            'X' .
            hashtagNomeFeed(
                $rivalidade['b']
            ),
            85
        );
    }


    /* =========================
       ❤️ ROMANCE
       ========================= */

    $romances =
        extrairRomancesFeed(
            $jogadores
        );


    if (!empty($romances)) {

        [$a, $b] =
            $romances[0];


        $adicionar(
            '#' .
            hashtagNomeFeed($a) .
            'E' .
            hashtagNomeFeed($b),
            78
        );
    }


    /* =========================
       📺 GERAL
       ========================= */

    $adicionar(
        '#BBBSimulator',
        55
    );


    arsort($topics);


    $resultado = [];


    foreach (
        array_slice(
            $topics,
            0,
            5,
            true
        )
        as $tag => $peso
    ) {

        /*
         * Número determinístico.
         * Não muda toda vez que der F5.
         */

        $hash =
            abs(
                crc32(
                    $tag .
                    '|' .
                    $rodada
                )
            );


        $posts =
            3000
            +
            ($peso * 170)
            +
            ($hash % 9000);


        $resultado[] = [
            'tag' => $tag,
            'posts' => $posts
        ];
    }


    return $resultado;
}


/* =========================================================
   🔢 FORMATAR NÚMEROS
   ========================================================= */

function formatarNumeroFeed($numero)
{
    $numero =
        (int) $numero;


    if ($numero >= 1000000) {

        return
            number_format(
                $numero / 1000000,
                1,
                ',',
                '.'
            )
            . ' mi';
    }


    if ($numero >= 1000) {

        return
            number_format(
                $numero / 1000,
                1,
                ',',
                '.'
            )
            . ' mil';
    }


    return
        number_format(
            $numero,
            0,
            ',',
            '.'
        );
}