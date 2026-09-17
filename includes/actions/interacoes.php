<?php

/** @var string $fase */
/** @var array $jogadores */
/** @var string $meuNome */


/* =========================
   🎯 SELECIONAR AÇÃO
========================= */

if (isset($_POST['selecionar_acao'])) {

    $_SESSION['acao_selecionada'] = $_POST['selecionar_acao'];

    header("Location: jogo.php");
    exit;
}


/* =========================
   ⬅️ CANCELAR AÇÃO
========================= */

if (isset($_POST['cancelar_acao'])) {

    unset($_SESSION['acao_selecionada']);

    header("Location: jogo.php");
    exit;
}


/* =========================
   💬 PROCESSAR INTERAÇÃO
========================= */

if (
    isset($_POST['acao']) &&
    strpos($fase, 'interacoes') !== false &&
    $_SESSION['acoes_restantes'] > 0
) {

    $acao = $_POST['acao'];
    $alvo = $_POST['alvo'] ?? '';
    $alvo2 = $_POST['alvo2'] ?? '';

    $evento = "";


    /* 💬 CONVERSAR */

    if ($acao == "conversar" && $alvo) {

        $_SESSION['relacoes_jogador'][$alvo] =
            ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 8;

        alterarAfinidade(
            $jogadores,
            $meuNome,
            $alvo,
            8,
            -2,
            3
        );

        alterarAfinidade(
            $jogadores,
            $alvo,
            $meuNome,
            8,
            -2,
            3
        );

        $evento =
            "💬 $meuNome conversou com $alvo e ganhou afinidade.";
    }


    /* 🗣️ FOFOCA */

    if ($acao == "fofoca" && $alvo) {

        $evento = resolverFofocaAvancada(
            $jogadores,
            $meuNome,
            $alvo,
            $meuNome
        );
    }


    /* 🔥 INTRIGA */

    if (
        $acao == "intriga" &&
        $alvo &&
        $alvo2 &&
        $alvo != $alvo2
    ) {

        if (rand(1, 100) <= 55) {

            alterarAfinidade(
                $jogadores,
                $alvo,
                $alvo2,
                rand(-15, -5),
                rand(5, 15),
                rand(-12, -5)
            );

            alterarAfinidade(
                $jogadores,
                $alvo2,
                $alvo,
                rand(-15, -5),
                rand(5, 15),
                rand(-12, -5)
            );

            alterarPopularidadePublica(
                $jogadores,
                $meuNome,
                -5,
                5,
                "criou intriga e dividiu a opinião do público",
                true
            );

            $evento =
                "🔥 $meuNome criou intriga entre $alvo e $alvo2.";

        } else {

            alterarAfinidade(
                $jogadores,
                $alvo,
                $meuNome,
                rand(-10, -5),
                rand(5, 10),
                rand(-10, -5)
            );

            alterarAfinidade(
                $jogadores,
                $alvo2,
                $meuNome,
                rand(-10, -5),
                rand(5, 10),
                rand(-10, -5)
            );

            alterarPopularidadePublica(
                $jogadores,
                $meuNome,
                -9,
                -4,
                "tentou criar intriga e foi desmascarado",
                true
            );

            $evento =
                "🔥 $meuNome tentou criar intriga, mas $alvo e $alvo2 desconfiaram.";
        }
    }


    /* 🤝 CRIAR ALIANÇA */

    if ($acao == "alianca") {

        $nomeAliancaCriada =
            $_POST['nome_alianca'] ?? '';

        $convidadosAlianca =
            $_POST['convidados_alianca'] ?? [];

        $evento = criarAliancaJogadorComConvites(
            $jogadores,
            $meuNome,
            $nomeAliancaCriada,
            $convidadosAlianca
        );

        if (
            mb_stripos(
                $evento,
                'criou a aliança',
                0,
                'UTF-8'
            ) !== false
        ) {

            adicionarMoedasPublico(
                15,
                "criar uma aliança aceita"
            );
        }
    }


    /* 🚪 ENTRAR EM ALIANÇA */

    if ($acao == "entrar_alianca") {

        $aliancaEscolhida =
            $_POST['alianca_escolhida'] ?? '';

        $evento = jogadorEntrarEmAlianca(
            $jogadores,
            $meuNome,
            $aliancaEscolhida
        );

        if (
            mb_stripos(
                $evento,
                'entrou para a aliança',
                0,
                'UTF-8'
            ) !== false
        ) {

            adicionarMoedasPublico(
                5,
                "entrar em uma aliança"
            );
        }
    }


    /* 💥 SAIR DA ALIANÇA */

    if ($acao == "sair_alianca") {

        $evento = jogadorSairDaAlianca(
            $jogadores,
            $meuNome
        );
    }


    /* 👑 APROXIMAR DO LÍDER */

    if ($acao == "aproximar_lider") {

        $lider = $_SESSION['lider'] ?? '';

        if ($lider && $lider != $meuNome) {

            ajustarRelacaoJogador(
                $lider,
                5
            );

            alterarAfinidade(
                $jogadores,
                $meuNome,
                $lider,
                5,
                -3,
                6
            );

            alterarAfinidade(
                $jogadores,
                $lider,
                $meuNome,
                5,
                -3,
                6
            );

            $evento =
                "👑 $meuNome tentou se aproximar do líder $lider e ganhou afinidade.";

        } else {

            $evento =
                "👑 Não havia líder disponível para se aproximar.";
        }
    }


    /* 🍳 COZINHAR */

    if ($acao == "cozinhar") {

        foreach ($jogadores as $j) {

            if ($j['nome'] != $meuNome) {

                alterarAfinidade(
                    $jogadores,
                    $j['nome'],
                    $meuNome,
                    rand(2, 6),
                    -1,
                    rand(1, 4)
                );
            }
        }

        $evento =
            "🍳 $meuNome cozinhou para a casa e agradou alguns participantes.";
    }


    /* 😡 DISCUTIR */

    if ($acao == "discutir" && $alvo) {

        $_SESSION['relacoes_jogador'][$alvo] =
            ($_SESSION['relacoes_jogador'][$alvo] ?? 0) - 10;

        ajustarPopularidadePorAlvo(
            $jogadores,
            $meuNome,
            $alvo,
            "comprou uma treta com alguém cancelado",
            "brigou com alguém querido pelo público"
        );

        $evento =
            "😡 $meuNome discutiu com $alvo e o clima pesou.";
    }


    /* 🎬 VT */

    if ($acao == "vt") {

        $evento = resolverVTAvancado(
            $jogadores,
            $meuNome,
            $meuNome
        );
    }


    /* 😶 FICAR QUIETO */

    if ($acao == "quieto") {

        impactoPopularidadePorPersonalidade(
            $jogadores,
            $meuNome,
            "planta",
            true
        );

        $evento =
            "😶 $meuNome ficou na sua e evitou conflitos.";
    }


    /* =========================
       ✅ FINALIZAR AÇÃO
    ========================= */

    if ($evento != "") {

        $_SESSION['evento_extra'][] =
            $evento;

        $_SESSION['jogadores'] =
            $jogadores;

        $_SESSION['acoes_restantes']--;


        /* NPCs jogam quando acabam suas ações */

        if (
            $_SESSION['acoes_restantes'] <= 0 &&
            !isset(
                $_SESSION[
                    'npc_interacoes_feitas_' . $fase
                ]
            )
        ) {

            $eventosNPC = gerarAcoesNPC(
                $jogadores,
                $meuNome,
                3
            );

            foreach ($eventosNPC as $ev) {
                $_SESSION['evento_extra'][] = $ev;
            }


            $eventosAliancas =
                atualizarAliancasAutomaticas(
                    $jogadores,
                    $meuNome
                );

            foreach ($eventosAliancas as $ev) {
                $_SESSION['evento_extra'][] = $ev;
            }


            $eventosFofocasVTs =
                gerarFofocasEVTsAutomaticos(
                    $jogadores,
                    $meuNome,
                    rand(2, 4)
                );

            foreach ($eventosFofocasVTs as $ev) {
                $_SESSION['evento_extra'][] = $ev;
            }


            $_SESSION['jogadores'] =
                $jogadores;

            $_SESSION[
                'npc_interacoes_feitas_' . $fase
            ] = true;
        }


        unset(
            $_SESSION['acao_selecionada']
        );
    }


    header("Location: jogo.php");
    exit;
}