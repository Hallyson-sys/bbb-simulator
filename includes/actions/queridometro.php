<?php

/** @var array $jogadores */
/** @var string $meuNome */
/** @var array $EMOJIS_QUERIDOMETRO */


/* =========================
   💖 PROCESSAR QUERIDÔMETRO
========================= */

if (
    isset($_POST['enviar_queridometro']) ||
    isset($_POST['auto_queridometro'])
) {

    iniciarQueridometro();

    $dados =
        $_POST['queridometro'] ?? [];


    /* =========================
       ⚡ PREENCHIMENTO AUTOMÁTICO
    ========================= */

    if (isset($_POST['auto_queridometro'])) {

        $dados = [];

        foreach ($jogadores as $jAuto) {

            $nomeAuto =
                $jAuto['nome'] ?? '';

            if (
                $nomeAuto == '' ||
                nomeIgual(
                    $nomeAuto,
                    $meuNome
                )
            ) {
                continue;
            }

            $dados[$nomeAuto] =
                escolherEmojiQueridometroAutomatico(
                    $jogadores,
                    $meuNome,
                    $nomeAuto
                );
        }
    }


    /* =========================
       👤 EMOJIS DO JOGADOR
    ========================= */

    foreach ($dados as $alvo => $emoji) {

        registrarEmojiQueridometro(
            $jogadores,
            $meuNome,
            $alvo,
            $emoji,
            $EMOJIS_QUERIDOMETRO
        );
    }


    /* =========================
       🤖 EMOJIS DOS NPCs
    ========================= */

    foreach ($jogadores as $npc) {

        $nomeNPC =
            $npc['nome'] ?? '';

        if (
            $nomeNPC == '' ||
            $nomeNPC == $meuNome
        ) {
            continue;
        }


        foreach ($jogadores as $alvo) {

            $nomeAlvo =
                $alvo['nome'] ?? '';

            if (
                $nomeAlvo == '' ||
                $nomeAlvo == $nomeNPC
            ) {
                continue;
            }


            $relacao =
                $npc['relacoes'][$nomeAlvo] ?? [];

            $amizade =
                $relacao['amizade'] ?? 0;

            $rivalidade =
                $relacao['rivalidade'] ?? 0;

            $confianca =
                $relacao['confianca'] ?? 0;

            $romance =
                $npc['romances'][$nomeAlvo] ?? 0;

            $popularidadeAlvo =
                $alvo['popularidade'] ?? 50;


            $pontuacao =
                $amizade +
                $confianca +
                floor($romance / 2) -
                $rivalidade;


            /* ❤️ Coração */

            if (
                $romance >= 50 ||
                $pontuacao >= 45
            ) {

                $emoji = "❤️";
            }


            /* 🤝 Aperto de mão */

            elseif (
                $confianca >= 25 ||
                $amizade >= 30
            ) {

                $emoji = "🤝";
            }


            /* 😄 Feliz */

            elseif ($amizade >= 12) {

                $emoji = "😄";
            }


            /* 🤮 Ranço forte */

            elseif (
                $rivalidade >= 45 ||
                $pontuacao <= -35
            ) {

                $emoji = "🤮";
            }


            /* 🐍 Cobra */

            elseif (
                $rivalidade >= 30 ||
                $pontuacao <= -20
            ) {

                $emoji = "🐍";
            }


            /* 🙄 Rejeição */

            elseif ($popularidadeAlvo <= 35) {

                $emoji = "🙄";
            }


            /* 🎲 Neutro / aleatório */

            else {

                $lista = [
                    "😄",
                    "🔥",
                    "😴",
                    "🎯",
                    "😡"
                ];

                $emoji =
                    $lista[array_rand($lista)];
            }


            registrarEmojiQueridometro(
                $jogadores,
                $nomeNPC,
                $nomeAlvo,
                $emoji,
                $EMOJIS_QUERIDOMETRO
            );
        }
    }


    /* =========================
       💾 SALVAR RESULTADO
    ========================= */

    $_SESSION['jogadores'] =
        $jogadores;

    $_SESSION['queridometro_feito'] =
        true;


    if (isset($_POST['auto_queridometro'])) {

        $_SESSION['evento_extra'][] =
            "💟 O Queridômetro foi preenchido automaticamente com base nas suas relações.";

    } else {

        $_SESSION['evento_extra'][] =
            "💟 O Queridômetro movimentou a casa.";
    }


    header("Location: jogo.php");
    exit;
}