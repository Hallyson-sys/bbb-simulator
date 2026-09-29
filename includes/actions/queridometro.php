<?php

/** @var array $jogadores */
/** @var string $meuNome */
/** @var array $EMOJIS_QUERIDOMETRO */


/* =========================================================
   💖 ACTION — PROCESSAR QUERIDÔMETRO
   ========================================================= */

if (
    isset($_POST['enviar_queridometro']) ||
    isset($_POST['auto_queridometro'])
) {
    iniciarQueridometro();

    $dados =
        $_POST['queridometro']
        ?? [];


    /* =====================================================
       ⚡ PREENCHIMENTO AUTOMÁTICO DO JOGADOR
       ===================================================== */

    if (isset($_POST['auto_queridometro'])) {
        $dados = [];

        foreach ($jogadores as $jAuto) {
            $nomeAuto =
                $jAuto['nome'] ?? '';

            if (
                $nomeAuto === '' ||
                nomeIgual($nomeAuto, $meuNome)
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


    /* =====================================================
       👤 EMOJIS ENVIADOS PELO JOGADOR
       ===================================================== */

    foreach ($dados as $alvo => $emoji) {
        registrarEmojiQueridometro(
            $jogadores,
            $meuNome,
            $alvo,
            $emoji,
            $EMOJIS_QUERIDOMETRO
        );
    }


    /* =====================================================
       🤖 NPCs TAMBÉM ENVIAM EMOJIS

       Agora cada NPC usa a PRÓPRIA relação com o alvo.
       Isso substitui decisões baseadas principalmente em
       popularidade ou sorte aleatória.
       ===================================================== */

    foreach ($jogadores as $npc) {
        $nomeNPC =
            $npc['nome'] ?? '';

        if (
            $nomeNPC === '' ||
            nomeIgual($nomeNPC, $meuNome)
        ) {
            continue;
        }

        foreach ($jogadores as $alvo) {
            $nomeAlvo =
                $alvo['nome'] ?? '';

            if (
                $nomeAlvo === '' ||
                nomeIgual($nomeAlvo, $nomeNPC)
            ) {
                continue;
            }

            $emoji =
                escolherEmojiQueridometroNPC(
                    $jogadores,
                    $nomeNPC,
                    $nomeAlvo,
                    $meuNome
                );

            registrarEmojiQueridometro(
                $jogadores,
                $nomeNPC,
                $nomeAlvo,
                $emoji,
                $EMOJIS_QUERIDOMETRO
            );
        }
    }


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
