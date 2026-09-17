<?php

/* =========================================================
   💖 LÓGICA DO QUERIDÔMETRO
   ========================================================= */

$EMOJIS_QUERIDOMETRO = [

    "❤️" => [
        "nome" => "Amor / Afinidade Forte",
        "tipo" => "positivo",
        "afinidade" => 12,
        "popularidade" => 4
    ],

    "😄" => [
        "nome" => "Gosto / Simpatia",
        "tipo" => "positivo",
        "afinidade" => 6,
        "popularidade" => 2
    ],

    "🤝" => [
        "nome" => "Aliança / Confiança",
        "tipo" => "positivo",
        "afinidade" => 10,
        "popularidade" => 3
    ],

    "🔥" => [
        "nome" => "Treta / Caótica",
        "tipo" => "neutro",
        "afinidade" => -2,
        "popularidade" => 5
    ],

    "😴" => [
        "nome" => "Planta / Apagado",
        "tipo" => "negativo",
        "afinidade" => -5,
        "popularidade" => -6
    ],

    "🐍" => [
        "nome" => "Falso / Mentiroso",
        "tipo" => "negativo",
        "afinidade" => -12,
        "popularidade" => -8
    ],

    "🎯" => [
        "nome" => "Alvo / Quero Eliminar",
        "tipo" => "negativo",
        "afinidade" => -10,
        "popularidade" => -4
    ],

    "💔" => [
        "nome" => "Chateado / Me Decepcionou",
        "tipo" => "negativo",
        "afinidade" => -15,
        "popularidade" => -3
    ],

    "🤮" => [
        "nome" => "Ranço / Relação Ruim",
        "tipo" => "negativo",
        "afinidade" => -18,
        "popularidade" => -7
    ],

    "🙄" => [
        "nome" => "Forçado / VTzeiro",
        "tipo" => "negativo",
        "afinidade" => -6,
        "popularidade" => -10
    ],

    "😡" => [
        "nome" => "Explosivo / Barraqueiro",
        "tipo" => "misto",
        "afinidade" => -4,
        "popularidade" => 3
    ]

];


/* =========================================================
   📊 INICIAR QUERIDÔMETRO
   ========================================================= */

function iniciarQueridometro()
{
    if (!isset($_SESSION['queridometro_resultado'])) {
        $_SESSION['queridometro_resultado'] = [];
    }
}


/* =========================================================
   ⚡ ESCOLHER EMOJI AUTOMATICAMENTE
   ========================================================= */

function escolherEmojiQueridometroAutomatico(
    $jogadores,
    $meuNome,
    $nomeAlvo
) {

    $relacao =
        $_SESSION['relacoes_jogador'][$nomeAlvo] ?? 0;

    $romance =
        obterRomance(
            $jogadores,
            $meuNome,
            $nomeAlvo
        );


    /* Relação muito positiva */

    if ($romance >= 60 || $relacao >= 60) {
        return "❤️";
    }


    if ($relacao >= 35) {
        return "🤝";
    }


    if ($relacao >= 12) {
        return "😄";
    }


    /* Relação muito negativa */

    if ($relacao <= -55) {
        return "🤮";
    }


    if ($relacao <= -35) {
        return "💔";
    }


    if ($relacao <= -20) {
        return "🐍";
    }


    if ($relacao <= -8) {
        return "🎯";
    }


    /* Relação neutra */

    $neutros = [
        "🔥",
        "😴",
        "🙄",
        "😡"
    ];


    return $neutros[
        array_rand($neutros)
    ];
}


/* =========================================================
   💟 REGISTRAR EMOJI DO QUERIDÔMETRO
   ========================================================= */

function registrarEmojiQueridometro(
    &$jogadores,
    $de,
    $para,
    $emoji,
    $EMOJIS_QUERIDOMETRO
) {

    /* =========================
       🛡️ VALIDAÇÕES
       ========================= */

    if (
        $de == '' ||
        $para == '' ||
        $emoji == ''
    ) {
        return;
    }


    if (
        !isset(
            $EMOJIS_QUERIDOMETRO[$emoji]
        )
    ) {
        return;
    }


    /*
     * Segurança caso a função seja chamada
     * sem iniciarQueridometro() antes.
     */
    if (!isset($_SESSION['queridometro_resultado'])) {
        $_SESSION['queridometro_resultado'] = [];
    }


    /* =========================
       📊 REGISTRAR RESULTADO
       ========================= */

    if (
        !isset(
            $_SESSION['queridometro_resultado'][$para]
        )
    ) {

        $_SESSION['queridometro_resultado'][$para] = [];
    }


    if (
        !isset(
            $_SESSION['queridometro_resultado'][$para][$emoji]
        )
    ) {

        $_SESSION['queridometro_resultado'][$para][$emoji] = 0;
    }


    $_SESSION['queridometro_resultado'][$para][$emoji]++;


    $dados =
        $EMOJIS_QUERIDOMETRO[$emoji];


    /* =========================
       ❤️ ALTERAR RELAÇÃO
       ========================= */

    alterarAfinidade(
        $jogadores,
        $de,
        $para,
        $dados['afinidade'],
        0,
        0
    );


    /*
     * Se quem enviou o emoji foi o jogador,
     * também altera a relação exibida nos cards.
     */
    if (
        nomeIgual(
            $de,
            $_SESSION['meu_nome'] ?? ''
        )
    ) {

        ajustarRelacaoJogador(
            $para,
            $dados['afinidade']
        );
    }


    /* =========================
       📈 POPULARIDADE
       ========================= */

    /*
     * Popularidade só recebe esse efeito
     * quando o próprio jogador envia
     * um emoji negativo.
     */
    if (
        nomeIgual(
            $de,
            $_SESSION['meu_nome'] ?? ''
        ) &&
        ($dados['tipo'] ?? '') == 'negativo'
    ) {

        $popularidadeAlvo = 50;


        foreach ($jogadores as $j) {

            if (
                nomeIgual(
                    $j['nome'] ?? '',
                    $para
                )
            ) {

                $popularidadeAlvo =
                    $j['popularidade'] ?? 50;

                break;
            }
        }


        /* Atacou alguém muito querido */

        if ($popularidadeAlvo >= 65) {

            $perda =
                rand(3, 5);


            alterarPopularidade(
                $jogadores,
                $de,
                -$perda
            );


            $_SESSION['evento_extra'][] =
                "📉 O público não curtiu $de atacando $para no Queridômetro.";
        }


        /* Atacou alguém rejeitado */

        elseif ($popularidadeAlvo <= 35) {

            $ganho =
                rand(3, 5);


            alterarPopularidade(
                $jogadores,
                $de,
                $ganho
            );


            $_SESSION['evento_extra'][] =
                "📈 O público gostou de $de mirar em $para no Queridômetro.";
        }
    }
}