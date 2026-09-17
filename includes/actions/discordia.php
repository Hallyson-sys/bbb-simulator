<?php

/** @var array $jogadores */
/** @var string $meuNome */


/* =========================
   🔥 PROCESSAR JOGO DA DISCÓRDIA
========================= */

if (isset($_POST['fazer_discordia'])) {

    $tema =
        $_SESSION['tema_discordia']
        ?? ($_POST['tema_discordia'] ?? 'sonso');

    $intensidade =
        $_POST['intensidade'] ?? 'leve';

    $evento = "";


    /* =========================
       😡 TEMAS NEGATIVOS
       Sonso / Falso / Saboneteiro
    ========================= */

    if (
        $tema == "sonso" ||
        $tema == "falso" ||
        $tema == "saboneteiro"
    ) {

        $alvo =
            $_POST['alvo_discordia'] ?? '';

        if ($alvo != '') {

            /* 🔥 COM TUDO */

            if ($intensidade == "com_tudo") {

                ajustarRelacaoJogador(
                    $alvo,
                    -15
                );

                alterarAfinidade(
                    $jogadores,
                    $meuNome,
                    $alvo,
                    -15,
                    12,
                    -10
                );

                alterarAfinidade(
                    $jogadores,
                    $alvo,
                    $meuNome,
                    -15,
                    12,
                    -10
                );

                ajustarPopularidadePorAlvo(
                    $jogadores,
                    $meuNome,
                    $alvo,
                    "bateu de frente com alguém rejeitado pelo público",
                    "passou do ponto contra alguém querido"
                );

                $evento =
                    "🔥 $meuNome chamou $alvo de $tema COM TUDO no Jogo da Discórdia. Afinidade com $alvo caiu 15 pontos.";
            }


            /* 😶 LEVE */

            elseif ($intensidade == "leve") {

                ajustarRelacaoJogador(
                    $alvo,
                    -6
                );

                alterarAfinidade(
                    $jogadores,
                    $meuNome,
                    $alvo,
                    -6,
                    5,
                    -4
                );

                alterarAfinidade(
                    $jogadores,
                    $alvo,
                    $meuNome,
                    -6,
                    5,
                    -4
                );

                alterarPopularidadePublica(
                    $jogadores,
                    $meuNome,
                    -3,
                    4,
                    "participou do Jogo da Discórdia sem exagerar",
                    true
                );

                $evento =
                    "😶 $meuNome chamou $alvo de $tema de forma mais leve. Afinidade com $alvo caiu 6 pontos.";
            }


            /* 🧼 SABONETAR */

            else {

                ajustarRelacaoJogador(
                    $alvo,
                    -2
                );

                alterarAfinidade(
                    $jogadores,
                    $meuNome,
                    $alvo,
                    -2,
                    2,
                    -2
                );

                alterarPopularidadeMotivo(
                    $jogadores,
                    $meuNome,
                    -5,
                    -5,
                    "saboneteou no Jogo da Discórdia"
                );

                $evento =
                    "🧼 $meuNome sabonetou ao falar sobre $alvo. Afinidade com $alvo caiu 2 pontos.";
            }
        }
    }


    /* =========================
       🤝 TEMA POSITIVO — ALIADO
    ========================= */

    if ($tema == "aliado") {

        $alvo =
            $_POST['alvo_discordia'] ?? '';

        if ($alvo != '') {

            ajustarRelacaoJogador(
                $alvo,
                12
            );

            alterarAfinidade(
                $jogadores,
                $meuNome,
                $alvo,
                12,
                -5,
                10
            );

            alterarAfinidade(
                $jogadores,
                $alvo,
                $meuNome,
                12,
                -5,
                10
            );

            alterarPopularidadePublica(
                $jogadores,
                $meuNome,
                1,
                4,
                "defendeu um aliado no Jogo da Discórdia",
                true
            );

            $evento =
                "🤝 $meuNome declarou que $alvo é seu maior aliado. Afinidade com $alvo subiu 12 pontos.";
        }
    }


    /* =========================
       🏆 PÓDIO
    ========================= */

    if ($tema == "podio") {

        $primeiro =
            $meuNome;

        $segundo =
            $_POST['podio_2'] ?? '';

        $terceiro =
            $_POST['podio_3'] ?? '';


        if (
            $segundo != '' &&
            $terceiro != '' &&
            $segundo != $terceiro &&
            !nomeIgual(
                $segundo,
                $primeiro
            ) &&
            !nomeIgual(
                $terceiro,
                $primeiro
            )
        ) {

            ajustarRelacaoJogador(
                $segundo,
                10
            );

            ajustarRelacaoJogador(
                $terceiro,
                6
            );


            alterarAfinidade(
                $jogadores,
                $meuNome,
                $segundo,
                10,
                -4,
                8
            );

            alterarAfinidade(
                $jogadores,
                $segundo,
                $meuNome,
                10,
                -4,
                8
            );


            alterarAfinidade(
                $jogadores,
                $meuNome,
                $terceiro,
                6,
                -2,
                5
            );

            alterarAfinidade(
                $jogadores,
                $terceiro,
                $meuNome,
                6,
                -2,
                5
            );


            $evento =
                "🏆 $meuNome montou seu pódio: 🥇 $primeiro, 🥈 $segundo e 🥉 $terceiro. Afinidades subiram.";

        } else {

            $evento =
                "⚠️ O 2º e o 3º lugar precisam ser participantes diferentes.";

            $_SESSION['evento_extra'][] =
                $evento;

            header("Location: jogo.php");
            exit;
        }
    }


    /* =========================
       ⚠️ GARANTIA
    ========================= */

    if ($evento == "") {

        $evento =
            "🔥 O Jogo da Discórdia aconteceu, mas nenhuma escolha válida foi registrada.";
    }


    /* =========================
       📺 AO VIVO
    ========================= */

    $_SESSION['evento_extra'][] =
        $evento;


    /* NPCs também participam */

    $eventosNPC =
        gerarDiscordiaNPC(
            $jogadores,
            $meuNome,
            $tema
        );


    foreach ($eventosNPC as $ev) {

        $_SESSION['evento_extra'][] =
            $ev;
    }


    $_SESSION['jogadores'] =
        $jogadores;

    $_SESSION['discordia_feito'] =
        true;

    unset(
        $_SESSION['tema_discordia']
    );


    header("Location: jogo.php");
    exit;
}