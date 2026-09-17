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
        $texto = escolherFeedPublico([
            "$atendente ATENDEU O BIG FONE 😭 essa semana vai render.",
            "$atendente correu pro Big Fone e a torcida foi junto.",
            "O Big Fone caiu justamente na mão de $atendente. CINEMA."
        ]);
    } elseif ($sentimento === 'negativo') {
        $texto = escolherFeedPublico([
            "$atendente atendendo o Big Fone... era tudo que eu não queria.",
            "Justo $atendente pegou o Big Fone. Agora segura.",
            "O Big Fone tocou e $atendente ganhou poder. perigo real."
        ]);
    } else {
        $texto = escolherFeedPublico([
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

        $textoPoder = escolherFeedPublico(
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
            escolherFeedPublico([
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
        escolherFeedPublico([
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
            escolherFeedPublico([
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
   📈 POPULARIDADE: PERCEBER SUBIDAS E QUEDAS
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
        $nome = $j['nome'] ?? '';

        if ($nome == '') {
            continue;
        }

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

        if (abs($acumulado) < 4) {
            continue;
        }

        $direcao = $acumulado > 0 ? 'subiu' : 'caiu';
        $chave = "intel_pop|$rodada|$nome|$direcao";

        if (feedIntelProcessado($chave)) {
            continue;
        }

        $motivo = feedIntelMotivoPopularidadeRecente(
            $j,
            $rodada
        );

        if ($acumulado > 0) {
            if ($atual >= 90) {
                $texto = escolherFeedPublico([
                    "$nome chegou em $atual de popularidade. FAVORITO oficial da edição?",
                    "$nome bateu $atual de popularidade e a torcida já tá falando em final.",
                    "O crescimento do $nome virou coisa séria: $atual/100."
                ]);
            } elseif ($acumulado >= 8) {
                $texto = escolherFeedPublico([
                    "$nome disparou no público essa semana. Cresceu $acumulado pontos 👀",
                    "A torcida do $nome cresceu MUITO: +$acumulado de popularidade.",
                    "$nome virou a semana completamente e subiu $acumulado pontos."
                ]);
            } else {
                $texto = escolherFeedPublico([
                    "$nome tá crescendo no público e já chegou a $atual/100.",
                    "A maré virou a favor do $nome: agora tá com $atual de popularidade.",
                    "$nome vem ganhando a audiência aos poucos. $atual/100."
                ]);
            }
        } else {
            $queda = abs($acumulado);

            if ($atual <= 20) {
                $texto = escolherFeedPublico([
                    "$nome despencou pra $atual de popularidade. A rejeição tá PESADA.",
                    "$nome chegou a $atual/100 e a situação nas redes tá crítica.",
                    "A torcida contra $nome cresceu muito. Popularidade agora: $atual."
                ]);
            } elseif ($queda >= 8) {
                $texto = escolherFeedPublico([
                    "$nome perdeu $queda pontos de popularidade nessa semana. Isso não é pouca coisa.",
                    "A imagem do $nome lá fora levou um tombo: -$queda pontos.",
                    "$nome tá se queimando e já caiu $queda pontos no público."
                ]);
            } else {
                $texto = escolherFeedPublico([
                    "$nome tá perdendo força com o público. Agora está em $atual/100.",
                    "A popularidade do $nome começou a cair: $atual/100.",
                    "O público esfriou com $nome e a nota caiu pra $atual."
                ]);
            }
        }

        if ($motivo != '') {
            $texto .= " Motivo mais recente: $motivo.";
        }

        adicionarPostFeedPublico(
            $jogadores,
            $texto,
            'popularidade',
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
        escolherFeedPublico([
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
        escolherFeedPublico([
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
        escolherFeedPublico([
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
        escolherFeedPublico([
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
        escolherFeedPublico([
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
        escolherFeedPublico([
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
            escolherFeedPublico([
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
            escolherFeedPublico([
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
        escolherFeedPublico([
            "CASA DE VIDRO DECIDIDA! $a e $b estão oficialmente no BBB Simulator 😭",
            "$a e $b atravessaram a porta da Casa de Vidro. Agora o jogo mudou.",
            "O público escolheu: $a e $b entram na casa! Quero ver onde eles vão se encaixar 👀"
        ]),
        'geral',
        [$a, $b]
    );

    adicionarPostFeedPublico(
        $jogadores,
        escolherFeedPublico([
            "$a acabou de chegar e eu já quero saber em qual grupo vai entrar.",
            "$a entrou pela Casa de Vidro e já chega com torcida do lado de fora.",
            "Primeiras horas do $a na casa vão dizer MUITA coisa sobre esse jogo."
        ]),
        'geral',
        [$a]
    );

    adicionarPostFeedPublico(
        $jogadores,
        escolherFeedPublico([
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


    /* 📈 Maior movimento de popularidade */
    $deltas =
        $_SESSION['feed_intel_delta_pop'][$rodada]
        ?? [];

    if (!empty($deltas)) {
        uasort(
            $deltas,
            function ($a, $b) {
                return abs($b) <=> abs($a);
            }
        );

        $nome = array_key_first($deltas);
        $delta = $deltas[$nome] ?? 0;

        if ($nome && abs($delta) >= 4) {
            $tag =
                '#' .
                hashtagNomeFeed($nome) .
                ($delta > 0 ? 'Cresceu' : 'Caiu');

            feedIntelAdicionarTrending(
                $lista,
                $tag,
                feedIntelPostsTrending(
                    $tag,
                    $rodada,
                    17000 + (abs($delta) * 700)
                )
            );
        }
    }

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
