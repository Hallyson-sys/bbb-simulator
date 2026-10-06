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

function normalizarTextoFeedComparacao($texto)
{
    $texto =
        mb_strtolower(
            strip_tags((string)$texto),
            'UTF-8'
        );

    $texto =
        preg_replace(
            '/[^\p{L}\p{N}\s]+/u',
            ' ',
            $texto
        );

    $texto =
        preg_replace(
            '/\s+/u',
            ' ',
            trim($texto)
        );

    return $texto;
}


function textoFeedMuitoParecidoComRecentes(
    $texto,
    $limite = 12
) {
    $textoBase =
        normalizarTextoFeedComparacao(
            $texto
        );

    if ($textoBase === '') {
        return false;
    }

    $recentes =
        array_slice(
            $_SESSION['feed_publico'] ?? [],
            -$limite
        );

    foreach ($recentes as $post) {

        $anterior =
            normalizarTextoFeedComparacao(
                $post['texto'] ?? ''
            );

        if ($anterior === '') {
            continue;
        }

        if ($anterior === $textoBase) {
            return true;
        }

        similar_text(
            $textoBase,
            $anterior,
            $percentual
        );

        /*
         * Também barra estruturas quase iguais
         * com apenas o nome do participante trocado.
         */
        if ($percentual >= 78) {
            return true;
        }
    }

    return false;
}


function escolherFeedPublico($itens)
{
    if (empty($itens)) {
        return '';
    }

    $itens =
        array_values(
            array_filter(
                $itens,
                fn($item) =>
                    trim((string)$item) !== ''
            )
        );

    if (empty($itens)) {
        return '';
    }

    /*
     * Primeiro tenta usar algo realmente diferente
     * do que apareceu nos posts recentes.
     */
    $novos =
        array_values(
            array_filter(
                $itens,
                fn($item) =>
                    !textoFeedMuitoParecidoComRecentes(
                        $item,
                        14
                    )
            )
        );

    if (!empty($novos)) {
        return $novos[
            array_rand($novos)
        ];
    }

    /*
     * Se todas as opções já foram usadas,
     * escolhe qualquer uma para não impedir o Feed.
     */
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
    /*
     * A popularidade continua influenciando o tom do público,
     * mas não determina uma opinião única.
     *
     * Assim o Feed dá pistas da repercussão sem entregar
     * quem é favorito ou rejeitado de forma óbvia.
     */
    $popularidade =
        obterPopularidadeJogador(
            $jogadores,
            $nome
        );

    $nivel =
        nivelPopularidadePublica(
            $popularidade
        );

    $sorteio = rand(1, 100);

    if (
        $nivel === 'favorito' ||
        $nivel === 'querido'
    ) {
        if ($sorteio <= 58) return 'positivo';
        if ($sorteio <= 80) return 'misto';
        return 'negativo';
    }

    if (
        $nivel === 'mal_visto' ||
        $nivel === 'cancelado'
    ) {
        if ($sorteio <= 58) return 'negativo';
        if ($sorteio <= 80) return 'misto';
        return 'positivo';
    }

    if ($sorteio <= 36) return 'positivo';
    if ($sorteio <= 72) return 'negativo';

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
                "$nome acorda e escolhe entretenimento, não tem jeito KKKKK.",
                "eu reclamo mas quando $nome começa uma treta eu largo tudo pra assistir",
                "$nome é incapaz de deixar essa casa em paz e eu agradeço por isso 😭",
                "a edição nem precisa procurar VT quando $nome tá acordado",
                "$nome pode ser muita coisa, planta com certeza não é"
            ]);
        }

        if ($sentimento === 'misto') {
            return escolherFeedPublico([
                "eu nunca sei se quero defender $nome ou mandar ficar quieto KKKKK",
                "$nome me estressa e cinco minutos depois me faz rir, complicado",
                "com $nome eu vivo numa relação de amor e ódio assistindo",
                "não sei se $nome tá entregando entretenimento ou só caos mesmo"
            ]);
        }

        return escolherFeedPublico([
            "$nome precisa descobrir que nem toda conversa é final de campeonato",
            "alguém esconde o megafone do $nome pelo amor de deus",
            "o problema do $nome é achar que toda faísca precisa virar incêndio",
            "$nome entrou numa de comprar briga por absolutamente tudo",
            "eu já tô cansado só de imaginar a próxima discussão do $nome"
        ]);
    }

    if ($personalidade === 'Planta') {
        return escolherFeedPublico([
            "gente, $nome tá na casa ainda né? dúvida sincera 😭",
            "quando $nome aparece eu lembro que tem mais um participante nessa edição",
            "$nome precisa arrumar uma historinha urgentemente",
            "a câmera encontrou $nome hoje, acontecimento histórico",
            "eu queria muito ter uma opinião sobre $nome mas tá difícil"
        ]);
    }

    if (
        $personalidade === 'Estrategista' ||
        $personalidade === 'Manipulador'
    ) {
        if ($sentimento === 'positivo') {
            return escolherFeedPublico([
                "$nome olhando tudo em silêncio e eu tenho CERTEZA que tá calculando alguma coisa",
                "o jogo do $nome é daqueles que você só entende três capítulos depois",
                "$nome não dá ponto sem nó, isso eu já entendi",
                "eu amo participante que entra pra jogar e $nome claramente entrou",
                "$nome observando a casa inteira como se fosse planilha KKKKK"
            ]);
        }

        if ($sentimento === 'misto') {
            return escolherFeedPublico([
                "eu respeito o jogo do $nome mas também não confio nem um pouco 👀",
                "$nome tá jogando muito ou se enrolando bonito, ainda não decidi",
                "tem horas que o $nome é genial e tem horas que eu fico ???",
                "o jogo do $nome me deixa com um pé atrás mas eu tô acompanhando"
            ]);
        }

        return escolherFeedPublico([
            "eu não compraria nem água na mão do $nome dentro dessa casa",
            "$nome acha que ninguém percebe as movimentações e isso que me pega",
            "cada conversa do $nome parece ter uma segunda intenção",
            "o jogo do $nome tá ficando complicado de defender",
            "eu sinto que uma hora esse plano todo do $nome vai estourar"
        ]);
    }

    if (
        $personalidade === 'Fofo' ||
        $personalidade === 'Emocional'
    ) {
        if ($sentimento === 'positivo') {
            return escolherFeedPublico([
                "$nome me pegou no emocional e eu nem vi quando aconteceu 😭",
                "eu fui assistir reality e agora tô aqui protegendo $nome da internet inteira",
                "$nome tem um jeitinho que me desmonta, infelizmente",
                "toda vez que $nome fica mal eu viro advogada de graça",
                "não era pra eu me apegar ao $nome desse jeito"
            ]);
        }

        if ($sentimento === 'misto') {
            return escolherFeedPublico([
                "$nome sente TUDO em 4D e eu não sei se admiro ou me preocupo",
                "eu gosto do $nome mas às vezes queria entrar na TV e mandar respirar",
                "$nome vive cada situação como se fosse o último episódio"
            ]);
        }
    }

    if ($personalidade === 'Influencer') {
        return escolherFeedPublico([
            "$nome encontra uma câmera mais rápido que eu encontro meu celular",
            "pode falar o que quiser, $nome entende perfeitamente como esse programa funciona",
            "$nome já nasceu sabendo onde fica o enquadramento bom KKKKK",
            "o timing de câmera do $nome é assustador de tão bom",
            "$nome consegue transformar qualquer cozinha em palco"
        ]);
    }

    if (
        $personalidade === 'Falso' &&
        $sentimento === 'negativo'
    ) {
        return escolherFeedPublico([
            "cada grupo recebe uma versão diferente do $nome né? interessante 👀",
            "eu tô começando a decorar quantos discursos diferentes $nome tem",
            "$nome muda de assunto e de opinião na mesma velocidade",
            "meu problema com $nome é que nada parece 100% espontâneo",
            "eu quero muito ver quando essas conversas do $nome se cruzarem"
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

    if ($contexto === 'lider') {
        if ($sentimento === 'positivo') {
            return escolherFeedPublico([
                "$nome de Líder... agora eu quero ver coragem nas decisões 👀",
                "essa liderança do $nome tem potencial pra bagunçar a semana inteira",
                "não vou mentir, fiquei feliz vendo $nome ganhar o Líder",
                "$nome ganhou o Líder e eu já tô pensando em cinquenta cenários",
                "a cara do $nome percebendo que virou Líder KKKKK muito bom"
            ]);
        }

        if ($sentimento === 'misto') {
            return escolherFeedPublico([
                "$nome Líder é exatamente o tipo de coisa que pode dar MUITO certo ou muito errado",
                "não sei como me sinto sobre esse reinado do $nome ainda",
                "essa liderança do $nome vai me fazer acompanhar cada conversa da casa"
            ]);
        }

        return escolherFeedPublico([
            "$nome com esse poder na mão... eu vou só assistir de longe",
            "não era a liderança que eu esperava, mas agora quero ver o estrago",
            "$nome Líder e eu imediatamente pensando em quem vai se complicar",
            "essa semana com $nome no comando tem tudo pra ser caótica"
        ]);
    }

    if ($contexto === 'anjo') {
        return escolherFeedPublico([
            "$nome pegou o Anjo e agora começou a parte boa: quem vai receber essa imunidade?",
            "quero ver se $nome vai seguir coração ou estratégia com esse colar 👀",
            "$nome de Anjo pode mudar completamente esse Paredão",
            "a cara da casa quando $nome ganhou o Anjo disse muita coisa KKKKK",
            "essa imunidade na mão do $nome vai render conversa até domingo"
        ]);
    }

    if ($contexto === 'monstro') {
        return escolherFeedPublico([
            "$nome no Monstro e a cara de derrota foi instantânea 😭",
            "eu sei que não devia rir mas $nome recebendo o Monstro me pegou KKKKK",
            "$nome já percebeu que a semana vai ser longa",
            "o Monstro chegou e encontrou $nome sem dificuldade nenhuma",
            "$nome tentando fingir que tá tudo bem com o Monstro: cinema"
        ]);
    }

    if ($contexto === 'paredao') {
        if ($sentimento === 'positivo') {
            return escolherFeedPublico([
                "$nome no Paredão e eu já fiquei nervoso por antecedência",
                "eu não tava preparado pra ver $nome sentado nesse sofá não 😭",
                "vou acompanhar esse Paredão com o coração na mão por causa do $nome",
                "a casa colocou $nome nessa situação e agora quero ver a resposta daqui de fora"
            ]);
        }

        if ($sentimento === 'misto') {
            return escolherFeedPublico([
                "$nome no Paredão é o teste que eu precisava pra decidir o que acho desse jogo",
                "esse Paredão do $nome vai mostrar muita coisa",
                "não sei se $nome sai ou volta maior, mas vai ser interessante"
            ]);
        }

        return escolherFeedPublico([
            "$nome no Paredão... bom, agora a conversa ficou séria",
            "eu tava esperando o jogo do $nome chegar nesse ponto",
            "esse Paredão pode mudar completamente a trajetória do $nome",
            "não vou fingir surpresa vendo $nome nesse sofá"
        ]);
    }

    if ($contexto === 'eliminacao') {
        if ($sentimento === 'positivo') {
            return escolherFeedPublico([
                "a casa vai ficar muito diferente sem $nome, isso é fato",
                "eu ainda tô processando a saída do $nome 😭",
                "$nome deixou história nessa edição, gostando ou não",
                "não achei que ia sentir tanto essa eliminação do $nome"
            ]);
        }

        if ($sentimento === 'misto') {
            return escolherFeedPublico([
                "a trajetória do $nome foi uma montanha-russa até o fim",
                "$nome saiu e eu ainda não decidi se vou sentir falta KKKKK",
                "fim de jogo pro $nome, mas assunto não vai faltar"
            ]);
        }

        return escolherFeedPublico([
            "acabou a trajetória do $nome e sinceramente já tava com cara de despedida",
            "$nome saiu e agora quero ver como a casa vai se reorganizar",
            "essa eliminação muda bastante o jogo daqui pra frente",
            "fim de linha pro $nome nessa edição"
        ]);
    }

    if ($contexto === 'treta') {
        if ($sentimento === 'positivo') {
            return escolherFeedPublico([
                "$nome entrou na discussão e eu automaticamente aumentei o volume",
                "não tem jeito, quando $nome começa a falar eu sei que vem episódio",
                "$nome entregou mais uma cena que vai render assunto até amanhã",
                "eu tava quase indo dormir e aí $nome resolveu começar uma treta"
            ]);
        }

        if ($sentimento === 'misto') {
            return escolherFeedPublico([
                "eu entendi o ponto do $nome mas também entendi quem ficou irritado KKKKK",
                "$nome tinha razão em partes e exagerou em outras, pronto falei",
                "essa discussão do $nome me deixou mais confuso do que antes"
            ]);
        }

        return escolherFeedPublico([
            "$nome entrou nessa discussão de um jeito que me fez passar nervoso daqui",
            "tem briga que ajuda e tem briga que só cansa... essa do $nome foi complicada",
            "eu queria entender qual era o objetivo do $nome nessa discussão",
            "$nome podia ter parado uns cinco minutos antes e tava tudo certo"
        ]);
    }

    if ($contexto === 'romance') {
        return escolherFeedPublico([
            "eu prometi que não ia shippar ninguém e aí $nome resolveu aparecer assim",
            "o clima envolvendo $nome tá tão óbvio que até a câmera já entendeu",
            "$nome tentando agir naturalmente perto do crush é meu entretenimento favorito",
            "eu só observando o romance do $nome crescer sem admitir que tô investido 👀",
            "se isso envolvendo $nome não virar assunto na festa eu sou uma geladeira"
        ]);
    }

    $comentarioPersonalidade =
        comentarioPersonalidadeFeed(
            $jogador,
            $sentimento
        );

    if (
        $comentarioPersonalidade &&
        rand(1, 100) <= 62
    ) {
        return $comentarioPersonalidade;
    }

    if ($sentimento === 'positivo') {
        return escolherFeedPublico([
            "eu não esperava me divertir tanto acompanhando o $nome",
            "$nome começou quieto no meu radar e agora eu reparo em tudo que faz",
            "tem alguma coisa no jeito do $nome jogar que me prende muito",
            "$nome tá criando uma trajetória bem mais interessante do que eu esperava",
            "não sei quando aconteceu mas eu comecei a torcer pelas cenas do $nome",
            "$nome aparecendo na tela e eu já sei que vou prestar atenção"
        ]);
    }

    if ($sentimento === 'negativo') {
        return escolherFeedPublico([
            "eu tento dar uma chance pro $nome e aí acontece mais uma coisa dessas",
            "o jogo do $nome não tá descendo pra mim ultimamente",
            "cada episódio eu fico mais confuso com as escolhas do $nome",
            "eu queria muito entender o que o $nome tá tentando fazer aqui",
            "$nome me testa como telespectador em níveis inacreditáveis",
            "não sei se é implicância minha mas ultimamente tudo do $nome me irrita"
        ]);
    }

    return escolherFeedPublico([
        "eu ainda tô formando minha opinião sobre $nome e isso já virou rotina",
        "tem dia que eu gosto muito do $nome e no outro eu fico ???",
        "$nome é um dos participantes que eu simplesmente não consigo definir",
        "minha opinião sobre $nome muda a cada edição, socorro",
        "eu observo $nome e continuo sem saber de que lado eu tô",
        "$nome é literalmente um grande 'vamos ver' pra mim"
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
    $nomes = [],
    $categoria = 'geral'
) {
    /*
     * O engajamento não usa mais popularidade diretamente.
     * Assim curtidas/reposts não funcionam como um "medidor
     * secreto" de quem está bem ou mal com o público.
     */
    $baseCategoria = [
        'lider' => 1.15,
        'anjo' => 1.00,
        'monstro' => 1.05,
        'paredao' => 1.35,
        'eliminacao' => 1.45,
        'treta' => 1.30,
        'rivalidade' => 1.30,
        'romance' => 1.18,
        'geral' => 0.90
    ];

    $multiplicador =
        $baseCategoria[$categoria]
        ?? 1.00;

    /*
     * Mais pessoas citadas = assunto naturalmente
     * mais comentado, sem indicar aprovação/rejeição.
     */
    $multiplicador +=
        min(
            0.20,
            max(0, count($nomes) - 1) * 0.07
        );

    $curtidas =
        (int)round(
            rand(900, 7200)
            * $multiplicador
        );

    $comentarios =
        (int)round(
            rand(120, 1850)
            * $multiplicador
        );

    $reposts =
        (int)round(
            rand(70, 1200)
            * $multiplicador
        );

    return [
        'curtidas' => max(1, $curtidas),
        'comentarios' => max(1, $comentarios),
        'reposts' => max(1, $reposts)
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

    $texto = trim((string)$texto);

    /* Corrige artigo/pronome conforme o gênero dos participantes citados. */
    if (function_exists('ajustarGeneroTextoParticipanteBBB')) {
        $nomesValidos = array_values(array_filter(array_map('strval', (array)$nomes)));
        $ajustarPronomes = count($nomesValidos) === 1;
        foreach ($nomesValidos as $nomeCitado) {
            $texto = ajustarGeneroTextoParticipanteBBB(
                $texto,
                $nomeCitado,
                $jogadores,
                $ajustarPronomes
            );
        }
    }

    if ($texto === '') {
        return false;
    }

    /*
     * Segunda proteção antirrepetição.
     * Também cobre textos montados dinamicamente,
     * como rivalidades e acontecimentos do Ao Vivo.
     */
    if (
        textoFeedMuitoParecidoComRecentes(
            $texto,
            10
        )
    ) {
        return false;
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
            $nomes,
            $categoria
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

    if (
        count($_SESSION['feed_publico']) > 80
    ) {
        $_SESSION['feed_publico'] =
            array_slice(
                $_SESSION['feed_publico'],
                -80
            );
    }

    return true;
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
         * Rivalidade recorrente vira narrativa do público,
         * mas com textos mais variados e menos "frase pronta".
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
                        "eu vejo $a e $b no mesmo cômodo e já espero o pior KKKKK",
                        "$a e $b têm uma habilidade impressionante de transformar qualquer assunto em climão",
                        "não precisa nem tocar música de suspense, basta colocar $a e $b perto",
                        "a tensão entre $a e $b já virou personagem dessa edição",
                        "$a falou, $b respondeu e eu só pensei: lá vamos nós de novo",
                        "o silêncio entre $a e $b consegue ser mais barulhento que muita discussão"
                    ]);

                adicionarPostFeedPublico(
                    $jogadores,
                    $textoRivalidade,
                    'rivalidade',
                    [$a, $b]
                );
            }
        }

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

            if (rand(1, 100) <= 46) {

                $textoGeral =
                    escolherFeedPublico([
                        "fui pegar água e quando voltei a casa já tava em outro assunto completamente diferente",
                        "essa edição não sabe o significado da palavra intervalo",
                        "eu abro o Ao Vivo por dez minutos e saio com três fofocas novas",
                        "o elenco decidiu que hoje ninguém dorme pelo visto",
                        "tem dias que essa casa parece um grupo de WhatsApp ao vivo",
                        "a produção nem precisa inventar dinâmica, eles se viram sozinhos KKKKK",
                        "eu só queria colocar o episódio de fundo e agora tô prestando atenção em tudo",
                        "cada vez que eu acho que a casa acalmou alguém resolve conversar"
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

            if (
                trim((string)$tag) === '' ||
                $tag === '#'
            ) {
                return;
            }

            if (
                !isset($topics[$tag]) ||
                $peso > $topics[$tag]
            ) {
                $topics[$tag] = $peso;
            }
        };

    /*
     * O Trending agora é guiado por acontecimentos visíveis.
     * Não usa ranking de popularidade, favorito ou rejeição.
     */

    foreach (
        participantesComStatusFeed(
            $jogadores,
            'lider'
        )
        as $nome
    ) {
        $adicionar(
            '#' .
            hashtagNomeFeed($nome) .
            'Líder',
            98
        );
    }

    foreach (
        participantesComStatusFeed(
            $jogadores,
            'anjo'
        )
        as $nome
    ) {
        $adicionar(
            '#' .
            hashtagNomeFeed($nome) .
            'Anjo',
            76
        );
    }

    foreach (
        extrairNomesParedaoFeed()
        as $nome
    ) {
        /*
         * Todos os emparedados podem virar assunto,
         * sem denunciar qual deles está melhor ou pior.
         */
        $variacao =
            abs(
                crc32(
                    'paredao|' .
                    $rodada .
                    '|' .
                    $nome
                )
            ) % 7;

        $adicionar(
            '#' .
            hashtagNomeFeed($nome) .
            'NoParedão',
            88 + $variacao
        );
    }

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
            84
        );
    }

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
            80
        );
    }

    $adicionar(
        '#BBBSimulator',
        58
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
            ($peso * 165)
            +
            ($hash % 7000);

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