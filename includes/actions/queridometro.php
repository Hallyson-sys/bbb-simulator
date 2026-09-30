<?php

/** @var array $jogadores */
/** @var string $meuNome */
/** @var array $EMOJIS_QUERIDOMETRO */


/* =========================================================
   💖 ACTION — PROCESSAR QUERIDÔMETRO
   ========================================================= */

/* =========================================================
   ⚡ APENAS PREENCHER AUTOMATICAMENTE

   Gera sugestões com base nas relações do jogador, mas NÃO
   registra nenhum emoji, NÃO faz os NPCs votarem e NÃO avança
   a fase. As escolhas ficam visíveis para revisão manual.
   ========================================================= */
if (
    isset($_POST['auto_queridometro_preview']) ||
    isset($_POST['auto_queridometro']) // compatibilidade com botão antigo
) {
    $sugestoes = [];

    foreach ($jogadores as $jAuto) {
        $nomeAuto = $jAuto['nome'] ?? '';

        if (
            $nomeAuto === '' ||
            nomeIgual($nomeAuto, $meuNome)
        ) {
            continue;
        }

        $sugestoes[$nomeAuto] =
            escolherEmojiQueridometroAutomatico(
                $jogadores,
                $meuNome,
                $nomeAuto
            );
    }

    $_SESSION['queridometro_sugestao'] = $sugestoes;
    $_SESSION['queridometro_preview_ativo'] = true;

    header("Location: jogo.php");
    exit;
}


/* =========================================================
   💟 ENVIAR QUERIDÔMETRO DEFINITIVAMENTE
   ========================================================= */
if (isset($_POST['enviar_queridometro'])) {

    $dados = $_POST['queridometro'] ?? [];

    /* =====================================================
       ✅ VALIDAR SE TODOS RECEBERAM UMA ESCOLHA VÁLIDA
       ===================================================== */
    $faltando = [];

    foreach ($jogadores as $jValidar) {
        $nomeValidar = $jValidar['nome'] ?? '';

        if (
            $nomeValidar === '' ||
            nomeIgual($nomeValidar, $meuNome)
        ) {
            continue;
        }

        $emojiEscolhido = $dados[$nomeValidar] ?? '';

        if (
            $emojiEscolhido === '' ||
            !isset($EMOJIS_QUERIDOMETRO[$emojiEscolhido])
        ) {
            $faltando[] = $nomeValidar;
        }
    }

    if (!empty($faltando)) {
        $_SESSION['queridometro_erro'] =
            'Escolha um emoji para todos os participantes antes de enviar.';

        header("Location: jogo.php");
        exit;
    }

    iniciarQueridometro();


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

       Cada NPC usa a PRÓPRIA relação com o alvo.
       ===================================================== */
    foreach ($jogadores as $npc) {
        $nomeNPC = $npc['nome'] ?? '';

        if (
            $nomeNPC === '' ||
            nomeIgual($nomeNPC, $meuNome)
        ) {
            continue;
        }

        foreach ($jogadores as $alvo) {
            $nomeAlvo = $alvo['nome'] ?? '';

            if (
                $nomeAlvo === '' ||
                nomeIgual($nomeAlvo, $nomeNPC)
            ) {
                continue;
            }

            $emoji = escolherEmojiQueridometroNPC(
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


    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['queridometro_feito'] = true;

    /* A prévia não deve continuar salva depois da confirmação. */
    unset($_SESSION['queridometro_sugestao']);
    unset($_SESSION['queridometro_preview_ativo']);
    unset($_SESSION['queridometro_erro']);

    $_SESSION['evento_extra'][] =
        "💟 O Queridômetro movimentou a casa.";

    header("Location: jogo.php");
    exit;
}
