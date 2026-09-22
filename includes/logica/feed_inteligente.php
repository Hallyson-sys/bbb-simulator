<?php

/* =========================================================
   🧠 FEED BBB INTELIGENTE — CAMADA EXTRA
   Trabalha em conjunto com includes/logica/feed_publico.php
   ========================================================= */


/* =========================================================
   🔐 CONTROLE DE EVENTOS INTELIGENTES
   ========================================================= */
function feedIntelProcessado($chave)
{
    if (
        !isset($_SESSION['feed_inteligente_processados']) ||
        !is_array($_SESSION['feed_inteligente_processados'])
    ) {
        $_SESSION['feed_inteligente_processados'] = [];
    }

    return !empty(
        $_SESSION['feed_inteligente_processados'][$chave]
    );
}

function feedIntelMarcar($chave)
{
    if (
        !isset($_SESSION['feed_inteligente_processados']) ||
        !is_array($_SESSION['feed_inteligente_processados'])
    ) {
        $_SESSION['feed_inteligente_processados'] = [];
    }

    $_SESSION['feed_inteligente_processados'][$chave] = true;
}


/* =========================================================
   🗣️ VARIAÇÃO DE TEXTO / MIGRAÇÃO DO FEED
   ========================================================= */

/*
 * Evita repetir exatamente as mesmas frases em sequência.
 * Guarda apenas os textos mais recentes da camada inteligente.
 */
function feedIntelEscolherTexto($opcoes)
{
    $opcoes = array_values(
        array_filter(
            (array)$opcoes,
            function ($texto) {
                return trim((string)$texto) !== '';
            }
        )
    );

    if (empty($opcoes)) {
        return '';
    }

    if (
        !isset($_SESSION['feed_intel_textos_recentes']) ||
        !is_array($_SESSION['feed_intel_textos_recentes'])
    ) {
        $_SESSION['feed_intel_textos_recentes'] = [];
    }

    $recentes = $_SESSION['feed_intel_textos_recentes'];

    $disponiveis = array_values(
        array_filter(
            $opcoes,
            function ($texto) use ($recentes) {
                return !in_array($texto, $recentes, true);
            }
        )
    );

    if (empty($disponiveis)) {
        $disponiveis = $opcoes;
    }

    $texto = $disponiveis[array_rand($disponiveis)];

    $recentes[] = $texto;

    $_SESSION['feed_intel_textos_recentes'] =
        array_slice(
            array_values(array_unique($recentes)),
            -18
        );

    return $texto;
}


/*
 * Remove, UMA única vez, posts antigos da versão que
 * mostrava números de popularidade e "Motivo mais recente".
 *
 * Assim saves já existentes migram sem precisar começar
 * uma temporada nova.
 */
function feedIntelMigrarVersao()
{
    $versaoAtual = 2;
    $versaoSalva = (int)($_SESSION['feed_inteligente_versao'] ?? 0);

    if ($versaoSalva >= $versaoAtual) {
        return;
    }

    if (
        isset($_SESSION['feed_publico']) &&
        is_array($_SESSION['feed_publico'])
    ) {
        $_SESSION['feed_publico'] = array_values(
            array_filter(
                $_SESSION['feed_publico'],
                function ($post) {
                    if (!is_array($post)) {
                        return true;
                    }

                    if (($post['categoria'] ?? '') === 'popularidade') {
                        return false;
                    }

                    $texto = (string)($post['texto'] ?? '');

                    if (
                        preg_match('/\b\d{1,3}\s*\/\s*100\b/u', $texto) ||
                        mb_stripos($texto, 'Motivo mais recente:', 0, 'UTF-8') !== false ||
                        mb_stripos($texto, 'pontos de popularidade', 0, 'UTF-8') !== false ||
                        mb_stripos($texto, 'de popularidade', 0, 'UTF-8') !== false
                    ) {
                        return false;
                    }

                    return true;
                }
            )
        );
    }

    /*
     * Libera os gatilhos antigos de reação pública para
     * os novos comentários naturais poderem ser gerados.
     */
    if (
        isset($_SESSION['feed_inteligente_processados']) &&
        is_array($_SESSION['feed_inteligente_processados'])
    ) {
        foreach (
            array_keys($_SESSION['feed_inteligente_processados'])
            as $chave
        ) {
            if (strpos((string)$chave, 'intel_pop|') === 0) {
                unset(
                    $_SESSION['feed_inteligente_processados'][$chave]
                );
            }
        }
    }

    $_SESSION['feed_intel_textos_recentes'] = [];
    $_SESSION['feed_inteligente_versao'] = $versaoAtual;
}


function feedIntelContemAlgum($texto, $termos)
{
    $texto = mb_strtolower((string)$texto, 'UTF-8');

    foreach ((array)$termos as $termo) {
        if (
            $termo !== '' &&
            mb_stripos(
                $texto,
                mb_strtolower((string)$termo, 'UTF-8'),
                0,
                'UTF-8'
            ) !== false
        ) {
            return true;
        }
    }

    return false;
}


/* =========================================================
   ☎️ BIG FONE
   ========================================================= */
function feedIntelNomePoderBigFone($tipo)
{
    $mapa = [
        'imunidade' => 'Imunidade',
        'indicacao' => 'Indicação Direta',
        'voto_duplo' => 'Voto Duplo',
        'anular_voto' => 'Anular Voto',
        'espiar_voto' => 'Espiar Voto',
        'contragolpe' => 'Contra-Golpe',
        'trocar_emparedado' => 'Troca de Emparedado'
    ];

    return $mapa[$tipo] ?? 'Poder Misterioso';
}

function feedIntelProcessarBigFone($jogadores, $rodada)
{
    if (
        empty($_SESSION['bigfone_feito']) ||
        empty($_SESSION['bigfone_atendente'])
    ) {
        return;
    }

    $atendente = $_SESSION['bigfone_atendente'];
    $poder = $_SESSION['bigfone_poder'] ?? '';

    $chave =
        'intel_bigfone|' .
        $rodada . '|' .
        $atendente . '|' .
        $poder;

    if (feedIntelProcessado($chave)) {
        return;
    }

    $sentimento = sentimentoFeedPublico(
        $jogadores,
        $atendente
    );

    if ($sentimento === 'positivo') {
        $texto = feedIntelEscolherTexto([
            "$atendente ATENDEU O BIG FONE 😭 essa semana vai render.",
            "$atendente correu pro Big Fone e a torcida foi junto.",
            "O Big Fone caiu justamente na mão de $atendente. CINEMA."
        ]);
    } elseif ($sentimento === 'negativo') {
        $texto = feedIntelEscolherTexto([
            "$atendente atendendo o Big Fone... era tudo que eu não queria.",
            "Justo $atendente pegou o Big Fone. Agora segura.",
            "O Big Fone tocou e $atendente ganhou poder. perigo real."
        ]);
    } else {
        $texto = feedIntelEscolherTexto([
            "$atendente atendeu o Big Fone e a timeline parou.",
            "O Big Fone caiu com $atendente. Quero ver onde isso vai dar.",
            "$atendente no Big Fone 👀 a semana mudou AGORA."
        ]);
    }

    adicionarPostFeedPublico(
        $jogadores,
        $texto,
        'bigfone',
        [$atendente]
    );

    if ($poder != '') {
        $nomePoder = feedIntelNomePoderBigFone($poder);

        $textos = [
            'imunidade' => [
                "$atendente ganhou IMUNIDADE no Big Fone. Agora pode respirar por enquanto.",
                "Big Fone deu imunidade pra $atendente e mexeu completamente com a semana."
            ],
            'indicacao' => [
                "$atendente com indicação direta depois do Big Fone??? quero nomes.",
                "O Big Fone colocou uma indicação nas mãos de $atendente. A casa deve estar em pânico."
            ],
            'voto_duplo' => [
                "$atendente com VOTO DUPLO??? essa votação vai ser um caos 😭",
                "O voto de $atendente vale por dois por causa do Big Fone. Façam as contas."
            ],
            'anular_voto' => [
                "$atendente pode ANULAR um voto. O confessionário acabou de ficar perigoso.",
                "Big Fone deu poder de anular voto pra $atendente e ninguém sabe se o próprio voto vai valer."
            ],
            'espiar_voto' => [
                "$atendente vai descobrir o voto de alguém 👀 fofoca premium liberada.",
                "O Big Fone deu Espiar Voto pra $atendente. Quero ver o pós-confessionário."
            ],
            'contragolpe' => [
                "$atendente com Contra-Golpe guardado... se cair no Paredão vai ter troco.",
                "Esse Contra-Golpe do Big Fone na mão de $atendente ainda vai render."
            ],
            'trocar_emparedado' => [
                "$atendente pode TROCAR um emparedado. Isso aqui virou xadrez.",
                "Troca de Emparedado com $atendente depois do Big Fone. Ninguém tá seguro."
            ]
        ];

        $textoPoder = feedIntelEscolherTexto(
            $textos[$poder] ?? [
                "$atendente ganhou $nomePoder no Big Fone. Quero ver como vai usar."
            ]
        );

        adicionarPostFeedPublico(
            $jogadores,
            $textoPoder,
            'bigfone',
            [$atendente]
        );
    }

    if (!empty($_SESSION['indicacao_bigfone'])) {
        $alvo = $_SESSION['indicacao_bigfone'];

        adicionarPostFeedPublico(
            $jogadores,
            feedIntelEscolherTexto([
                "$atendente colocou $alvo direto no Paredão pelo Big Fone. CLIMÃO.",
                "A indicação do Big Fone foi em $alvo. Essa relação não volta igual.",
                "$alvo foi direto pro Paredão pela mão de $atendente. pesado."
            ]),
            'bigfone',
            [$atendente, $alvo]
        );
    }

    feedIntelMarcar($chave);
}


/* =========================================================
   🎁 PODER CURINGA
   ========================================================= */
function feedIntelNomePoderCuringa($tipo)
{
    if (function_exists('catalogoPoderesCuringa')) {
        $catalogo = catalogoPoderesCuringa();

        if (isset($catalogo[$tipo]['nome'])) {
            return $catalogo[$tipo]['nome'];
        }
    }

    $mapa = [
        'voto_duplo' => 'Voto Duplo',
        'imunidade_extra' => 'Imunidade Extra',
        'anular_voto' => 'Anular Voto',
        'espiao' => 'Espião do Confessionário',
        'contra_golpe' => 'Contra-Golpe',
        'trocar_emparedado' => 'Troca de Emparedado'
    ];

    return $mapa[$tipo] ?? 'Poder Curinga';
}

function feedIntelProcessarCuringa($jogadores, $rodada)
{
    $poder = $_SESSION['poder_curinga'] ?? null;

    if (!$poder || !is_array($poder)) {
        return;
    }

    if (
        (int)($poder['rodada'] ?? $rodada) !==
        (int)$rodada
    ) {
        return;
    }

    $dono = $poder['dono'] ?? '';
    $tipo = $poder['tipo'] ?? '';

    if ($dono == '' || $tipo == '') {
        return;
    }

    $chave =
        'intel_curinga|' .
        $rodada . '|' .
        $dono . '|' .
        $tipo;

    if (feedIntelProcessado($chave)) {
        return;
    }

    $nomePoder = feedIntelNomePoderCuringa($tipo);

    adicionarPostFeedPublico(
        $jogadores,
        feedIntelEscolherTexto([
            "$dono ficou com o Poder Curinga: $nomePoder. Agora quero ver a movimentação 👀",
            "$nomePoder caiu na mão de $dono. Essa semana ganhou uma camada nova.",
            "O Poder Curinga é de $dono e o poder é $nomePoder. A casa que lute."
        ]),
        'curinga',
        [$dono]
    );

    if (
        in_array(
            $tipo,
            ['contra_golpe', 'trocar_emparedado'],
            true
        )
    ) {
        adicionarPostFeedPublico(
            $jogadores,
            feedIntelEscolherTexto([
                "$dono pode guardar esse Curinga até a formação do Paredão. Isso é MUITO perigoso.",
                "Esse $nomePoder do $dono pode mudar um Paredão inteiro no último segundo."
            ]),
            'curinga',
            [$dono]
        );
    }

    feedIntelMarcar($chave);
}


/* =========================================================
   📈 LEITURA INTERNA DA REAÇÃO DO PÚBLICO
   Os valores continuam secretos para quem joga.
   ========================================================= */
function feedIntelMotivoPopularidadeRecente($jogador, $rodada)
{
    $historico = $jogador['historico_popularidade'] ?? [];

    if (!is_array($historico)) {
        return '';
    }

    for ($i = count($historico) - 1; $i >= 0; $i--) {
        $item = $historico[$i] ?? [];

        if (
            (int)($item['rodada'] ?? 0) ===
            (int)$rodada
        ) {
            return trim((string)($item['motivo'] ?? ''));
        }
    }

    return '';
}


/*
 * O motivo interno é convertido em linguagem de rede social.
 * Nada de "Motivo mais recente", nota, pontos ou XX/100.
 */
function feedIntelComentarioMotivoNatural(
    $nome,
    $motivo,
    $positivo
) {
    $motivo = trim((string)$motivo);

    if ($motivo === '') {
        return '';
    }

    if (feedIntelContemAlgum($motivo, ['vt', 'entretenimento'])) {
        return feedIntelEscolherTexto(
            $positivo
                ? [
                    "o VT do $nome hoje me pegou desprevenido KKKKK",
                    "$nome entregando entretenimento sem precisar forçar, assim eu gosto",
                    "eu ri mais do que devia com o $nome hoje 😭",
                    "quando o $nome resolve aparecer no programa ele aparece MESMO"
                ]
                : [
                    "essa tentativa de VT do $nome não me pegou não",
                    "$nome tentando render e eu só olhando assim 🤨",
                    "não sei explicar, mas hoje o $nome me cansou um pouco",
                    "o VT do $nome hoje não funcionou pra mim"
                ]
        );
    }

    if (feedIntelContemAlgum($motivo, ['treta', 'brig', 'discut', 'bateu de frente'])) {
        return feedIntelEscolherTexto(
            $positivo
                ? [
                    "eu sei que foi treta mas o $nome me ganhou ali, foi mal",
                    "$nome comprou a briga e dessa vez eu fiquei do lado dele",
                    "não esperava concordar com o $nome nessa discussão e cá estamos",
                    "o $nome falou o que muita gente tava pensando 👀"
                ]
                : [
                    "cada discussão do $nome me deixa mais cansado dele",
                    "$nome passou do ponto nessa e não tem muito como defender",
                    "eu tava tentando gostar do $nome mas essa briga complicou tudo",
                    "o jeito que o $nome conduziu essa treta me deu ranço real"
                ]
        );
    }

    if (feedIntelContemAlgum($motivo, ['fofoca', 'intriga', 'fals'])) {
        return feedIntelEscolherTexto(
            $positivo
                ? [
                    "não vou mentir: a fofoca do $nome rendeu e eu tava entretido",
                    "$nome mexeu as peças e eu tô aqui assistindo com a pipoca",
                    "o jogo do $nome tá ficando perigosamente interessante",
                    "$nome resolveu movimentar a casa e eu agradeço pelo entretenimento"
                ]
                : [
                    "essa movimentação do $nome tá com uma energia tão esquisita",
                    "o $nome se enrola sozinho quando começa com essas fofocas",
                    "eu não compraria uma palavra do $nome depois dessa",
                    "a casa vai descobrir essa do $nome e vai dar MUITO ruim"
                ]
        );
    }

    if (feedIntelContemAlgum($motivo, ['romance', 'casal', 'flert', 'beijo'])) {
        return feedIntelEscolherTexto(
            $positivo
                ? [
                    "eu jurava que não ia shippar ninguém e aí veio o $nome",
                    "o lado romântico do $nome me pegou desprevenido 😭",
                    "eu tentando não me apegar ao enredo do $nome: falhando miseravelmente",
                    "$nome vivendo o próprio romcom no meio do caos da casa"
                ]
                : [
                    "esse enredo romântico do $nome não tá me convencendo muito não",
                    "eu queria comprar esse romance do $nome mas tá difícil",
                    "o clima do $nome tá mais novela das seis do que química real pra mim",
                    "não sei se sou eu, mas esse romance do $nome tá meio forçado"
                ]
        );
    }

    if (feedIntelContemAlgum($motivo, ['líder', 'lider', 'prova', 'anjo'])) {
        return feedIntelEscolherTexto(
            $positivo
                ? [
                    "depois dessa prova eu comecei a olhar o $nome com outros olhos",
                    "$nome apareceu na hora que precisava, isso conta MUITO",
                    "o $nome foi bem na prova e parece que ganhou confiança junto",
                    "tem gente que cresce quando a pressão bate e o $nome tá mostrando isso"
                ]
                : [
                    "prova nenhuma tá me fazendo esquecer as últimas do $nome",
                    "o $nome pode até estar ganhando coisa na casa mas comigo ainda não virou",
                    "não sei, o $nome hoje não me convenceu nem um pouco",
                    "esperava mais do $nome nessa altura do jogo"
                ]
        );
    }

    if (feedIntelContemAlgum($motivo, ['confessionário', 'confessionario'])) {
        return feedIntelEscolherTexto(
            $positivo
                ? [
                    "o confessionário do $nome foi simplesmente tudo pra mim",
                    "$nome falando no confessionário e eu finalmente entendendo o jogo dele",
                    "a leitura de jogo do $nome no confessionário foi muito boa",
                    "o confessionário do $nome me fez repensar umas coisas aqui 👀"
                ]
                : [
                    "o confessionário do $nome me deixou com mais dúvidas do que antes",
                    "$nome abriu a boca no confessionário e conseguiu piorar a situação",
                    "não gostei NADA do tom do $nome no confessionário",
                    "o confessionário do $nome hoje não ajudou em absolutamente nada"
                ]
        );
    }

    if (feedIntelContemAlgum($motivo, ['aliança', 'alianca', 'aliado'])) {
        return feedIntelEscolherTexto(
            $positivo
                ? [
                    "a forma como o $nome tá construindo relações tá começando a fazer sentido",
                    "$nome parece ter encontrado gente que realmente fecha com ele",
                    "o social do $nome tá encaixando aos poucos e eu tô vendo",
                    "$nome finalmente achou um grupo que combina com o jogo dele"
                ]
                : [
                    "essas alianças do $nome estão começando a me dar uma preguiça",
                    "não sei se o $nome escolheu muito bem com quem se juntar",
                    "o jogo social do $nome tá parecendo uma bomba-relógio",
                    "essa aliança do $nome ainda vai cobrar um preço, anotem"
                ]
        );
    }

    if (feedIntelContemAlgum($motivo, ['apagado', 'planta', 'discreto'])) {
        return feedIntelEscolherTexto(
            $positivo
                ? [
                    "o $nome quietinho tá começando a me intrigar, confesso",
                    "vai ver o $nome tava só esperando a hora certa de aparecer",
                    "eu ainda quero entender qual é a do $nome nesse jogo",
                    "o $nome tá na dele mas eu sinto que alguma coisa vem aí"
                ]
                : [
                    "eu esqueço que o $nome tá na casa e isso tá começando a ser um problema",
                    "$nome precisa acordar pra temporada urgentemente",
                    "alguém avisa o $nome que o programa já começou 😭",
                    "eu tô esperando o $nome entrar no jogo até agora"
                ]
        );
    }

    if (feedIntelContemAlgum($motivo, ['monstro'])) {
        return feedIntelEscolherTexto(
            $positivo
                ? [
                    "o $nome no Monstro me deu uma dó que eu não esperava",
                    "eu defendendo o $nome depois do Monstro? pois é",
                    "o Monstro acabou me fazendo prestar mais atenção no $nome",
                    "$nome sofrendo no Monstro e eu aqui criando apego"
                ]
                : [
                    "nem o Monstro conseguiu me fazer comprar o enredo do $nome",
                    "o $nome tá no Monstro e ainda assim eu continuo meio assim com ele",
                    "juro que tentei ter dó do $nome no Monstro",
                    "essa semana do $nome tá estranha do começo ao fim"
                ]
        );
    }

    return '';
}


/* =========================================================
   📣 REAÇÃO DO PÚBLICO — SEM EXPOR NÚMEROS
   ========================================================= */
function feedIntelProcessarPopularidade($jogadores, $rodada)
{
    if (
        !isset($_SESSION['feed_intel_pop_snapshot']) ||
        !is_array($_SESSION['feed_intel_pop_snapshot'])
    ) {
        $_SESSION['feed_intel_pop_snapshot'] = [];
    }

    if (
        !isset($_SESSION['feed_intel_delta_pop']) ||
        !is_array($_SESSION['feed_intel_delta_pop'])
    ) {
        $_SESSION['feed_intel_delta_pop'] = [];
    }

    if (!isset($_SESSION['feed_intel_delta_pop'][$rodada])) {
        $_SESSION['feed_intel_delta_pop'][$rodada] = [];
    }

    foreach ($jogadores as $j) {
        $nome = trim((string)($j['nome'] ?? ''));

        if ($nome === '') {
            continue;
        }

        /*
         * Continua existindo apenas para a lógica interna.
         * O valor nunca é escrito no texto do Feed.
         */
        $atual = (int)($j['popularidade'] ?? 50);

        if (!array_key_exists($nome, $_SESSION['feed_intel_pop_snapshot'])) {
            $_SESSION['feed_intel_pop_snapshot'][$nome] = $atual;
            continue;
        }

        $anterior =
            (int)$_SESSION['feed_intel_pop_snapshot'][$nome];

        if ($atual === $anterior) {
            continue;
        }

        $delta = $atual - $anterior;

        $_SESSION['feed_intel_delta_pop'][$rodada][$nome] =
            ($_SESSION['feed_intel_delta_pop'][$rodada][$nome] ?? 0)
            + $delta;

        $_SESSION['feed_intel_pop_snapshot'][$nome] = $atual;

        $acumulado =
            (int)$_SESSION['feed_intel_delta_pop'][$rodada][$nome];

        /*
         * Mudanças pequenas ficam invisíveis.
         * Isso mantém suspense e evita spam.
         */
        if (abs($acumulado) < 4) {
            continue;
        }

        $direcao =
            $acumulado > 0
                ? 'reacao_positiva'
                : 'reacao_negativa';

        $chave =
            "intel_pop|$rodada|$nome|$direcao";

        if (feedIntelProcessado($chave)) {
            continue;
        }

        $motivo =
            feedIntelMotivoPopularidadeRecente(
                $j,
                $rodada
            );

        /*
         * Tenta primeiro transformar o acontecimento real
         * em uma reação espontânea.
         */
        $texto =
            feedIntelComentarioMotivoNatural(
                $nome,
                $motivo,
                $acumulado > 0
            );

        if ($texto === '') {
            if ($acumulado > 0) {
                if ($acumulado >= 8) {
                    $texto = feedIntelEscolherTexto([
                        "eu não dava nada pro $nome e agora tô começando a defender 😭",
                        "$nome virou a semana pra mim, não esperava MESMO",
                        "do nada eu percebi que tô torcendo pro $nome e isso me assustou",
                        "o $nome tá me ganhando de um jeito que eu não tava preparado",
                        "comecei a edição meio assim com o $nome e agora entendi tudo",
                        "tem alguma coisa no jogo do $nome que começou a encaixar muito",
                        "eu fui de 'tanto faz' pra 'ninguém mexe com $nome' rápido demais",
                        "$nome tá conquistando espaço sem eu nem perceber"
                    ]);
                } else {
                    $texto = feedIntelEscolherTexto([
                        "$nome tá começando a me ganhar aos poucos",
                        "não sei o que aconteceu mas hoje eu gostei mais do $nome",
                        "eu ainda não sou torcida do $nome mas tô prestando atenção 👀",
                        "$nome vem melhorando no meu conceito sem fazer alarde",
                        "acho que finalmente comecei a entender o jeito do $nome",
                        "o $nome tá crescendo em mim aos poucos, infelizmente KKKKK",
                        "cada dia eu fico um pouquinho mais curioso com o jogo do $nome",
                        "eu jurava que não ia ligar pro $nome e olha eu aqui"
                    ]);
                }
            } else {
                $queda = abs($acumulado);

                if ($queda >= 8) {
                    $texto = feedIntelEscolherTexto([
                        "eu tentei defender o $nome mas tá ficando impossível",
                        "$nome perdeu completamente a mão essa semana pra mim",
                        "cada dia fica mais difícil comprar o jogo do $nome",
                        "não sei o que aconteceu com o $nome mas eu desisti de passar pano",
                        "o $nome conseguiu me perder muito rápido nessa semana",
                        "eu tava gostando do $nome e agora só consigo revirar o olho",
                        "a sequência de decisões do $nome tá acabando comigo",
                        "$nome precisa se reencontrar porque a situação tá feia nas redes"
                    ]);
                } else {
                    $texto = feedIntelEscolherTexto([
                        "não sei explicar mas o $nome começou a me cansar",
                        "eu gostava mais do $nome uns dias atrás, confesso",
                        "o $nome tá me deixando com um pé atrás ultimamente",
                        "cada aparição do $nome tá me fazendo questionar mais",
                        "tô começando a perder a paciência com o $nome",
                        "o $nome ainda pode me ganhar de volta, mas hoje não rolou",
                        "alguma coisa no jogo do $nome começou a me incomodar",
                        "eu tô tentando entender o $nome mas tá difícil"
                    ]);
                }
            }
        }

        adicionarPostFeedPublico(
            $jogadores,
            $texto,
            'reacao_publico',
            [$nome]
        );

        feedIntelMarcar($chave);
    }
}


/* =========================================================
   🤝 ALIANÇA MAIS FORTE
   ========================================================= */
function feedIntelAliancaMaisForte($jogadores, $meuNome)
{
    $melhor = null;
    $melhorScore = -999;
    $total = count($jogadores);

    for ($i = 0; $i < $total; $i++) {
        for ($k = $i + 1; $k < $total; $k++) {
            $a = $jogadores[$i]['nome'] ?? '';
            $b = $jogadores[$k]['nome'] ?? '';

            if ($a == '' || $b == '') {
                continue;
            }

            if (
                !saoAliados($jogadores, $a, $b, $meuNome) &&
                !saoAliados($jogadores, $b, $a, $meuNome)
            ) {
                continue;
            }

            $relAB = obterRelacaoCompleta(
                $jogadores,
                $a,
                $b,
                $meuNome
            );

            $relBA = obterRelacaoCompleta(
                $jogadores,
                $b,
                $a,
                $meuNome
            );

            $score =
                ($relAB['score'] ?? 0) +
                ($relBA['score'] ?? 0) +
                (($relAB['confianca'] ?? 0) * .30) +
                (($relBA['confianca'] ?? 0) * .30);

            if ($score > $melhorScore) {
                $melhorScore = $score;
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

function feedIntelProcessarAlianca($jogadores, $rodada, $meuNome)
{
    $alianca = feedIntelAliancaMaisForte(
        $jogadores,
        $meuNome
    );

    if (!$alianca || ($alianca['score'] ?? 0) < 70) {
        return;
    }

    $a = $alianca['a'];
    $b = $alianca['b'];

    $par = [$a, $b];
    sort($par, SORT_NATURAL | SORT_FLAG_CASE);

    $chave =
        'intel_alianca|' .
        $rodada . '|' .
        implode('|', $par);

    if (feedIntelProcessado($chave)) {
        return;
    }

    adicionarPostFeedPublico(
        $jogadores,
        feedIntelEscolherTexto([
            "$a e $b estão fechadíssimos no jogo. Quero ver até onde essa dupla vai.",
            "A aliança de $a e $b tá ficando impossível de ignorar.",
            "$a e $b parecem confiar MUITO um no outro. Isso pode virar força ou problema.",
            "Se mexer com $a provavelmente mexe com $b também. A dupla tá formada."
        ]),
        'alianca',
        [$a, $b]
    );

    feedIntelMarcar($chave);
}


/* =========================================================
   ❤️ ROMANCE MAIS FORTE
   ========================================================= */
function feedIntelRomanceMaisForte($jogadores)
{
    if (!function_exists('obterRomance')) {
        return null;
    }

    $melhor = null;
    $melhorScore = 0;
    $total = count($jogadores);

    for ($i = 0; $i < $total; $i++) {
        for ($k = $i + 1; $k < $total; $k++) {
            $a = $jogadores[$i]['nome'] ?? '';
            $b = $jogadores[$k]['nome'] ?? '';

            if ($a == '' || $b == '') {
                continue;
            }

            $score = max(
                (int)obterRomance($jogadores, $a, $b),
                (int)obterRomance($jogadores, $b, $a)
            );

            if ($score > $melhorScore) {
                $melhorScore = $score;
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

function feedIntelProcessarRomance($jogadores)
{
    $romance = feedIntelRomanceMaisForte($jogadores);

    if (!$romance || ($romance['score'] ?? 0) < 30) {
        return;
    }

    $a = $romance['a'];
    $b = $romance['b'];

    $par = [$a, $b];
    sort($par, SORT_NATURAL | SORT_FLAG_CASE);

    /* Um post por casal, não um por rodada. */
    $chave =
        'intel_romance|' .
        implode('|', $par);

    if (feedIntelProcessado($chave)) {
        return;
    }

    adicionarPostFeedPublico(
        $jogadores,
        feedIntelEscolherTexto([
            "$a e $b achando que ninguém tá percebendo 👀",
            "Eu disse que não ia shippar ninguém e aí vieram $a e $b.",
            "Já existe torcida pra $a e $b e eu infelizmente faço parte.",
            "$a e $b entregando migalhas e a internet construindo um casamento."
        ]),
        'romance',
        [$a, $b]
    );

    feedIntelMarcar($chave);
}


/* =========================================================
   ❌ ELIMINAÇÃO
   ========================================================= */
function feedIntelProcessarEliminacao($jogadores, $rodada)
{
    $eliminado = $_SESSION['eliminado'] ?? '';

    if (is_array($eliminado)) {
        $nome = trim((string)($eliminado['nome'] ?? ''));
    } else {
        $nome = trim((string)$eliminado);
    }

    if ($nome == '') {
        return;
    }

    $chave = "intel_eliminacao|$rodada|$nome";

    if (feedIntelProcessado($chave)) {
        return;
    }

    $texto = gerarComentarioParticipanteFeed(
        $jogadores,
        $nome,
        'eliminacao'
    );

    if (!$texto) {
        $texto = "$nome foi eliminado. A casa muda a partir de agora.";
    }

    adicionarPostFeedPublico(
        $jogadores,
        $texto,
        'eliminacao',
        [$nome]
    );

    adicionarPostFeedPublico(
        $jogadores,
        feedIntelEscolherTexto([
            "Depois da saída de $nome, quero ver quem vai ocupar esse espaço no jogo.",
            "A eliminação do $nome muda alianças, alvos e até o clima da casa.",
            "Sai $nome e começa oficialmente uma nova fase dessa edição."
        ]),
        'eliminacao',
        [$nome]
    );

    feedIntelMarcar($chave);
}



/* =========================================================
   🚨 PAREDÃO FALSO
   ========================================================= */
function feedIntelProcessarParedaoFalso(
    $jogadores,
    $rodada
) {
    $pendente =
        $_SESSION['paredao_falso_feed_pendente']
        ?? null;

    if (
        !$pendente ||
        !is_array($pendente) ||
        empty($pendente['nome'])
    ) {
        return;
    }

    $nome =
        $pendente['nome'];

    $rodadaOrigem =
        (int) (
            $pendente['rodada_origem']
            ?? max(1, $rodada - 1)
        );

    $chave =
        "intel_paredao_falso|$rodadaOrigem|$nome";

    if (feedIntelProcessado($chave)) {
        unset(
            $_SESSION['paredao_falso_feed_pendente']
        );
        return;
    }

    adicionarPostFeedPublico(
        $jogadores,
        feedIntelEscolherTexto([
            "PAREDÃO FALSO! $nome estava no Quarto Secreto esse tempo todo 😭",
            "$nome VOLTOU PRA CASA. Era Paredão Falso e ninguém tava preparado.",
            "A porta abriu e $nome reapareceu. Isso aqui virou filme.",
            "O público mandou $nome pro Quarto Secreto e agora o jogo recomeça com CAOS."
        ]),
        'eliminacao',
        [$nome]
    );

    adicionarPostFeedPublico(
        $jogadores,
        feedIntelEscolherTexto([
            "Quero ver a cara da casa descobrindo que $nome viu tudo do Quarto Secreto 👀",
            "Depois desse retorno do $nome, alianças e rivalidades vão mudar MUITO.",
            "$nome ganhou uma segunda entrada na casa e agora sabe que a eliminação era falsa."
        ]),
        'geral',
        [$nome]
    );

    $_SESSION['paredao_falso_trending'] = [
        'nome' => $nome,
        'ate_rodada' => (int) $rodada
    ];

    feedIntelMarcar($chave);

    unset(
        $_SESSION['paredao_falso_feed_pendente']
    );
}





/* =========================================================
   🏠 CASA DE VIDRO — ANÚNCIO / VOTAÇÃO
   ========================================================= */
function feedIntelProcessarAnuncioCasaVidro(
    $jogadores,
    $rodada
) {
    $pendente =
        $_SESSION['casa_vidro_anuncio_feed_pendente']
        ?? null;

    if (
        !$pendente ||
        !is_array($pendente) ||
        empty($pendente['candidatos']) ||
        !is_array($pendente['candidatos'])
    ) {
        return;
    }

    $candidatos = array_values(
        array_filter(
            $pendente['candidatos'],
            function ($nome) {
                return trim((string)$nome) !== '';
            }
        )
    );

    if (count($candidatos) < 2) {
        unset($_SESSION['casa_vidro_anuncio_feed_pendente']);
        return;
    }

    $chave =
        'intel_casa_vidro_anuncio|'
        . (int)($pendente['rodada'] ?? 3)
        . '|'
        . implode('|', $candidatos);

    if (feedIntelProcessado($chave)) {
        unset($_SESSION['casa_vidro_anuncio_feed_pendente']);
        return;
    }

    $lista = implode(', ', $candidatos);

    adicionarPostFeedPublico(
        $jogadores,
        feedIntelEscolherTexto([
            "CASA DE VIDRO NA RODADA 3! Os candidatos são $lista. Eu já escolhi meus favoritos 😭",
            "A Casa de Vidro abriu: $lista disputam DUAS vagas. Essa votação vai render muito.",
            "Quatro nomes e só duas vagas: $lista. Quero ver como a internet vai se dividir 👀"
        ]),
        'geral',
        []
    );

    $primeiro = $candidatos[0] ?? '';
    $segundo = $candidatos[1] ?? '';

    if ($primeiro !== '') {
        adicionarPostFeedPublico(
            $jogadores,
            feedIntelEscolherTexto([
                "Já vi gente fechando torcida pro $primeiro e a votação acabou de abrir KKKKK.",
                "$primeiro mal apareceu na Casa de Vidro e já virou assunto.",
                "A campanha por #" . hashtagNomeFeed($primeiro) . "NaCasa começou cedo 👀"
            ]),
            'geral',
            []
        );
    }

    if ($segundo !== '') {
        adicionarPostFeedPublico(
            $jogadores,
            feedIntelEscolherTexto([
                "$segundo também chegou forte na Casa de Vidro. Essa disputa tá aberta.",
                "Quero ver se o público compra $segundo até o fim da Rodada 3.",
                "A torcida do $segundo já apareceu nas redes e nem chegamos no resultado."
            ]),
            'geral',
            []
        );
    }

    $_SESSION['casa_vidro_votacao_trending'] = [
        'candidatos' => $candidatos,
        'ate_rodada' => 3
    ];

    feedIntelMarcar($chave);

    unset($_SESSION['casa_vidro_anuncio_feed_pendente']);
}

/* =========================================================
   🏠 CASA DE VIDRO
   ========================================================= */
function feedIntelProcessarCasaVidro(
    $jogadores,
    $rodada
) {
    $pendente =
        $_SESSION['casa_vidro_feed_pendente']
        ?? null;

    if (
        !$pendente ||
        !is_array($pendente) ||
        empty($pendente['vencedores']) ||
        !is_array($pendente['vencedores'])
    ) {
        return;
    }

    $vencedores = array_values(
        array_filter(
            $pendente['vencedores'],
            function ($nome) {
                return trim((string)$nome) !== '';
            }
        )
    );

    if (count($vencedores) < 2) {
        unset($_SESSION['casa_vidro_feed_pendente']);
        return;
    }

    $rodadaOrigem =
        (int)($pendente['rodada'] ?? $rodada);

    $chave =
        'intel_casa_vidro|'
        . $rodadaOrigem
        . '|'
        . implode('|', $vencedores);

    if (feedIntelProcessado($chave)) {
        unset($_SESSION['casa_vidro_feed_pendente']);
        return;
    }

    $a = $vencedores[0];
    $b = $vencedores[1];

    adicionarPostFeedPublico(
        $jogadores,
        feedIntelEscolherTexto([
            "CASA DE VIDRO DECIDIDA! $a e $b estão oficialmente no BBB Simulator 😭",
            "$a e $b atravessaram a porta da Casa de Vidro. Agora o jogo mudou.",
            "O público escolheu: $a e $b entram na casa! Quero ver onde eles vão se encaixar 👀"
        ]),
        'geral',
        [$a, $b]
    );

    adicionarPostFeedPublico(
        $jogadores,
        feedIntelEscolherTexto([
            "$a acabou de chegar e eu já quero saber em qual grupo vai entrar.",
            "$a entrou pela Casa de Vidro e já chega com torcida do lado de fora.",
            "Primeiras horas do $a na casa vão dizer MUITA coisa sobre esse jogo."
        ]),
        'geral',
        [$a]
    );

    adicionarPostFeedPublico(
        $jogadores,
        feedIntelEscolherTexto([
            "$b entrou e agora quero ver quem vai se aproximar primeiro 👀",
            "$b na casa depois da Casa de Vidro. Essa temporada acabou de ganhar uma peça nova.",
            "O público colocou $b no jogo. Agora precisa entregar!"
        ]),
        'geral',
        [$b]
    );

    $_SESSION['casa_vidro_trending'] = [
        'vencedores' => [$a, $b],
        'ate_rodada' => (int)$rodada
    ];

    feedIntelMarcar($chave);

    unset($_SESSION['casa_vidro_feed_pendente']);
}


/* =========================================================
   🚀 ATUALIZAÇÃO INTELIGENTE
   ========================================================= */
function atualizarFeedPublicoInteligente(
    $jogadores,
    $fase,
    $rodada,
    $meuNome
) {
    feedIntelMigrarVersao();

    feedIntelProcessarBigFone(
        $jogadores,
        $rodada
    );

    feedIntelProcessarCuringa(
        $jogadores,
        $rodada
    );

    feedIntelProcessarPopularidade(
        $jogadores,
        $rodada
    );

    feedIntelProcessarAlianca(
        $jogadores,
        $rodada,
        $meuNome
    );

    feedIntelProcessarRomance(
        $jogadores
    );

    feedIntelProcessarParedaoFalso(
        $jogadores,
        $rodada
    );

    feedIntelProcessarAnuncioCasaVidro(
        $jogadores,
        $rodada
    );

    feedIntelProcessarCasaVidro(
        $jogadores,
        $rodada
    );

    feedIntelProcessarEliminacao(
        $jogadores,
        $rodada
    );
}


/* =========================================================
   🔥 TRENDING TOPICS INTELIGENTES
   Mantém os assuntos antigos e acrescenta novos.
   ========================================================= */
function feedIntelAdicionarTrending(&$lista, $tag, $posts)
{
    if ($tag == '' || $tag == '#') {
        return;
    }

    if (
        !isset($lista[$tag]) ||
        $posts > $lista[$tag]
    ) {
        $lista[$tag] = $posts;
    }
}

function feedIntelPostsTrending($tag, $rodada, $base = 14000)
{
    $hash = abs(
        crc32($tag . '|' . $rodada)
    );

    return $base + ($hash % 18000);
}

function gerarTrendingTopicsFeedInteligente(
    $jogadores,
    $rodada,
    $meuNome
) {
    $lista = [];

    /* Mantém os trends já calculados pelo Feed original. */
    if (function_exists('gerarTrendingTopicsFeed')) {
        foreach (
            gerarTrendingTopicsFeed(
                $jogadores,
                $rodada,
                $meuNome
            )
            as $topic
        ) {
            $tag = $topic['tag'] ?? '';
            $posts = (int)($topic['posts'] ?? 0);

            feedIntelAdicionarTrending(
                $lista,
                $tag,
                $posts
            );
        }
    }

    /* ☎️ Big Fone */
    if (
        !empty($_SESSION['bigfone_feito']) &&
        !empty($_SESSION['bigfone_atendente'])
    ) {
        $nome = $_SESSION['bigfone_atendente'];

        $tag =
            '#' .
            hashtagNomeFeed($nome) .
            'NoBigFone';

        feedIntelAdicionarTrending(
            $lista,
            $tag,
            feedIntelPostsTrending(
                $tag,
                $rodada,
                26000
            )
        );
    }

    /* 🎁 Curinga */
    $curinga = $_SESSION['poder_curinga'] ?? null;

    if (
        is_array($curinga) &&
        !empty($curinga['dono']) &&
        (int)($curinga['rodada'] ?? $rodada) === (int)$rodada
    ) {
        $tag =
            '#' .
            hashtagNomeFeed($curinga['dono']) .
            'Curinga';

        feedIntelAdicionarTrending(
            $lista,
            $tag,
            feedIntelPostsTrending(
                $tag,
                $rodada,
                22000
            )
        );
    }

    /* 🚨 Paredão Falso */
    $paredaoFalsoTrend =
        $_SESSION['paredao_falso_trending']
        ?? null;

    if (
        is_array($paredaoFalsoTrend) &&
        !empty($paredaoFalsoTrend['nome']) &&
        (int)($paredaoFalsoTrend['ate_rodada'] ?? 0) >= (int)$rodada
    ) {
        $nome =
            $paredaoFalsoTrend['nome'];

        $tag = '#ParedaoFalso';

        feedIntelAdicionarTrending(
            $lista,
            $tag,
            feedIntelPostsTrending(
                $tag,
                $rodada,
                30000
            )
        );

        $tagRetorno =
            '#' .
            hashtagNomeFeed($nome) .
            'Voltou';

        feedIntelAdicionarTrending(
            $lista,
            $tagRetorno,
            feedIntelPostsTrending(
                $tagRetorno,
                $rodada,
                28000
            )
        );
    }



    /* 🗳️ Casa de Vidro — votação da Rodada 3 */
    $casaVidroVotacao =
        $_SESSION['casa_vidro_votacao_trending']
        ?? null;

    if (
        is_array($casaVidroVotacao) &&
        !empty($casaVidroVotacao['candidatos']) &&
        is_array($casaVidroVotacao['candidatos']) &&
        (int)($casaVidroVotacao['ate_rodada'] ?? 0) >= (int)$rodada
    ) {
        $tagCasaVotacao = '#CasaDeVidro';

        feedIntelAdicionarTrending(
            $lista,
            $tagCasaVotacao,
            feedIntelPostsTrending(
                $tagCasaVotacao,
                $rodada,
                30000
            )
        );

        foreach (
            array_slice(
                $casaVidroVotacao['candidatos'],
                0,
                4
            )
            as $indiceCasa => $nomeCasa
        ) {
            $tagCandidato =
                '#'
                . hashtagNomeFeed($nomeCasa)
                . 'NaCasa';

            feedIntelAdicionarTrending(
                $lista,
                $tagCandidato,
                feedIntelPostsTrending(
                    $tagCandidato,
                    $rodada,
                    21000 - ($indiceCasa * 1200)
                )
            );
        }
    }


    /* 🏠 Casa de Vidro — vencedores */
    $casaVidroTrend =
        $_SESSION['casa_vidro_trending']
        ?? null;

    if (
        is_array($casaVidroTrend) &&
        !empty($casaVidroTrend['vencedores']) &&
        is_array($casaVidroTrend['vencedores']) &&
        (int)($casaVidroTrend['ate_rodada'] ?? 0) >= (int)$rodada
    ) {
        $tagCasa = '#CasaDeVidro';

        feedIntelAdicionarTrending(
            $lista,
            $tagCasa,
            feedIntelPostsTrending(
                $tagCasa,
                $rodada,
                31000
            )
        );

        foreach (
            array_slice(
                $casaVidroTrend['vencedores'],
                0,
                2
            )
            as $nomeCasa
        ) {
            $tagEntrou =
                '#'
                . hashtagNomeFeed($nomeCasa)
                . 'NaCasa';

            feedIntelAdicionarTrending(
                $lista,
                $tagEntrou,
                feedIntelPostsTrending(
                    $tagEntrou,
                    $rodada,
                    27500
                )
            );
        }
    }


    /* Reações de público não viram Trending baseado em números secretos. */

    /* 🤝 Aliança forte */
    $alianca = feedIntelAliancaMaisForte(
        $jogadores,
        $meuNome
    );

    if ($alianca && ($alianca['score'] ?? 0) >= 70) {
        $tag =
            '#' .
            hashtagNomeFeed($alianca['a']) .
            'E' .
            hashtagNomeFeed($alianca['b']);

        feedIntelAdicionarTrending(
            $lista,
            $tag,
            feedIntelPostsTrending(
                $tag,
                $rodada,
                14500
            )
        );
    }

    arsort($lista);

    $resultado = [];

    foreach (
        array_slice($lista, 0, 5, true)
        as $tag => $posts
    ) {
        $resultado[] = [
            'tag' => $tag,
            'posts' => $posts
        ];
    }

    return $resultado;
}