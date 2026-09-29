<?php

/** @var string $fase */
/** @var array $jogadores */
/** @var string $meuNome */

/* =========================================================
   ▶️ AVANÇAR FASE
   ========================================================= */

if (isset($_POST['avancar_fase'])) {

    $fase = $_SESSION['fase_semana'] ?? $fase;

    $estaNoQuartoSecreto =
        !empty($_SESSION['paredao_falso_ativo']) &&
        !empty($_SESSION['falso_eliminado']) &&
        $meuNome !== '' &&
        nomeIgual(
            $_SESSION['falso_eliminado'],
            $meuNome
        );

    if ($fase == 'queridometro') {

        if (($_SESSION['rodada'] ?? 1) >= 2) {
            prepararConfessionarioDaRodada($jogadores, $meuNome);
            $_SESSION['fase_semana'] = 'confessionario';
        } else {
            $_SESSION['fase_semana'] = 'interacoes_1';
            $_SESSION['acoes_restantes'] = 3;
        }

        header("Location: jogo.php");
        exit;
    }

    /* =====================================================
       🚪 RETORNO DO PAREDÃO FALSO
       Após Interações 2 e antes da Festa.
       Funciona para NPC e jogador humano.
       ===================================================== */

    if (
        $fase === 'interacoes_2' &&
        !empty($_SESSION['paredao_falso_ativo'])
    ) {
        $rodadaAtual =
            (int)($_SESSION['rodada'] ?? 1);

        $rodadaOrigem =
            (int)(
                $_SESSION['paredao_falso_rodada']
                ?? $rodadaAtual
            );

        $rodadaRetorno =
            (int)(
                $_SESSION['paredao_falso_rodada_retorno']
                ?? ($rodadaOrigem + 1)
            );

        if ($rodadaAtual >= $rodadaRetorno) {

            if (
                !isset($_SESSION['acoes_restantes']) ||
                $_SESSION['acoes_restantes'] > 0
            ) {
                npcExecutarInteracoesDaFase(
                    $jogadores,
                    $meuNome,
                    $fase,
                    3
                );
            }

            unset($_SESSION['acoes_restantes']);

            $retornou = false;

            if (
                function_exists(
                    'retornarFalsoEliminadoParaCasa'
                )
            ) {
                $retornou =
                    retornarFalsoEliminadoParaCasa(
                        $jogadores
                    );
            }

            if ($retornou) {
                $_SESSION['jogadores'] =
                    array_values($jogadores);

                $_SESSION['fase_semana'] =
                    'festa';

                header("Location: jogo.php");
                exit;
            }

            if (
                !isset($_SESSION['evento_extra']) ||
                !is_array($_SESSION['evento_extra'])
            ) {
                $_SESSION['evento_extra'] = [];
            }

            $_SESSION['evento_extra'][] =
                "⚠️ Não foi possível concluir o retorno do Paredão Falso.";

            header("Location: jogo.php");
            exit;
        }
    }

    if (strpos($fase, 'interacoes') !== false) {

        if (
            !isset($_SESSION['acoes_restantes']) ||
            $_SESSION['acoes_restantes'] > 0
        ) {
            npcExecutarInteracoesDaFase(
                $jogadores,
                $meuNome,
                $fase,
                3
            );
        }

        unset($_SESSION['acoes_restantes']);

        if ($fase == 'interacoes_1') {

            unset($_SESSION['lider']);
            unset($_SESSION['anjo']);
            unset($_SESSION['imune']);
            unset($_SESSION['monstro']);
            unset($_SESSION['vip_definido']);
            unset($_SESSION['monstro_definido']);
            unset($_SESSION['prova_anjo_finalizada']);
            unset($_SESSION['imunizacao_anjo_feita']);
            unset($_SESSION['anjo_autoimune']);
            unset($_SESSION['anjo_autoimune_estat_contada']);
            unset($_SESSION['paredao']);
            unset($_SESSION['paredao_formado']);
            unset($_SESSION['votos_paredao']);
            unset($_SESSION['dedo_duro']);
            unset($_SESSION['indicacao_lider']);
            unset($_SESSION['indicacao_bigfone']);
            unset($_SESSION['meu_voto_paredao']);
            unset($_SESSION['bigfone_feito']);
            unset($_SESSION['bigfone_indicacao_pendente']);
            unset($_SESSION['bigfone_dono_poder']);
            unset($_SESSION['bate_volta']);
            unset($_SESSION['bate_volta_decidido']);
            unset($_SESSION['bate_volta_resultado']);
            unset($_SESSION['bigfone_poder']);
            unset($_SESSION['bigfone_anular_voto_pendente']);
            unset($_SESSION['bigfone_espiar_voto_pendente']);
            unset($_SESSION['bigfone_troca_emparedado_pendente']);
            unset($_SESSION['bigfone_contragolpe_pendente']);
            unset($_SESSION['poder_curinga']);
            unset($_SESSION['curinga_decidido_rodada']);
            unset($_SESSION['curinga_voto_duplo_ativo']);
            unset($_SESSION['curinga_anular_voto_de']);
            unset($_SESSION['curinga_espiar_voto_de']);
            unset($_SESSION['curinga_contra_golpe_usado']);
            unset($_SESSION['curinga_troca_usada']);
            unset($_SESSION['imunidade_curinga']);
            unset($_SESSION['npc_festa_feita']);
            unset($_SESSION['alvos_aliancas_semana']);
            unset($_SESSION['confessionario_feito']);
            unset($_SESSION['confessionario_falas']);
            unset($_SESSION['npc_interacoes_feitas_interacoes_1']);
            unset($_SESSION['npc_interacoes_feitas_interacoes_2']);
            unset($_SESSION['npc_interacoes_feitas_interacoes_3']);
            unset($_SESSION['queridometro_resultado']);
            unset($_SESSION['queridometro_feito']);
            unset($_SESSION['prova_anjo_tipo']);
            unset($_SESSION['prova_anjo_dados']);

            foreach ($_SESSION['jogadores'] as &$j) {
                if (
                    !isset($j['status']) ||
                    !is_array($j['status'])
                ) {
                    $j['status'] = [];
                }

                $j['status']['lider'] = false;
                $j['status']['anjo'] = false;
                $j['status']['imune'] = false;
                $j['status']['monstro'] = false;
                $j['status']['vip'] = false;
                $j['status']['xepa'] = false;
            }

            unset($j);

            $_SESSION['fase_semana'] = 'lider';

            header("Location: prova_lider.php");
            exit;
        }

        if ($fase == 'interacoes_2') {

            /*
             * A Festa é a próxima fase real da semana.
             * A Casa de Vidro NÃO vira fase_semana.
             */
            $_SESSION['fase_semana'] = 'festa';

            /*
             * Rodada 3:
             * se houve Casa de Vidro, revela o resultado
             * imediatamente antes da Festa começar.
             */
            if (
                function_exists(
                    'prepararResultadoCasaVidroAntesFesta'
                ) &&
                prepararResultadoCasaVidroAntesFesta(
                    (int)($_SESSION['rodada'] ?? 1)
                )
            ) {
                header("Location: casa_vidro.php");
                exit;
            }

            header("Location: jogo.php");
            exit;
        }

        if ($fase == 'interacoes_3') {
            aplicarDesgasteSemanalPublico($jogadores);

            $_SESSION['jogadores'] = $jogadores;
            $_SESSION['fase_semana'] = 'eliminacao';

            header("Location: resultado.php");
            exit;
        }
    }

    if ($fase == 'lider') {
        header("Location: prova_lider.php");
        exit;
    }

    if ($fase == 'vip_xepa') {

        $liderAtual = $_SESSION['lider'] ?? '';

        if (
            $liderAtual != '' &&
            !nomeIgual($liderAtual, $meuNome)
        ) {
            $_SESSION['fase_semana'] =
                isset($_SESSION['vip_definido'])
                    ? 'anjo'
                    : 'vip_xepa_revelar';

            header("Location: jogo.php");
            exit;
        }

        if (!isset($_SESSION['vip_definido'])) {
            header("Location: jogo.php");
            exit;
        }

        unset($_SESSION['anjo']);
        unset($_SESSION['imune']);
        unset($_SESSION['monstro']);
        unset($_SESSION['monstro_definido']);
        unset($_SESSION['prova_anjo_finalizada']);
        unset($_SESSION['imunizacao_anjo_feita']);
        unset($_SESSION['anjo_autoimune']);
        unset($_SESSION['anjo_autoimune_estat_contada']);
        unset($_SESSION['prova_anjo_tipo']);
        unset($_SESSION['prova_anjo_dados']);

        foreach ($_SESSION['jogadores'] as &$j) {
            if (
                !isset($j['status']) ||
                !is_array($j['status'])
            ) {
                $j['status'] = [];
            }

            $j['status']['anjo'] = false;
            $j['status']['imune'] = false;
            $j['status']['monstro'] = false;
        }

        unset($j);

        $_SESSION['fase_semana'] = 'anjo';

        header("Location: jogo.php");
        exit;
    }

    if ($fase == 'vip_xepa_revelar') {
        header("Location: jogo.php");
        exit;
    }

    if ($fase == 'anjo') {
        header("Location: prova_anjo.php");
        exit;
    }

    if ($fase == 'monstro') {

        if ($estaNoQuartoSecreto) {
            return;
        }

        header("Location: jogo.php");
        exit;
    }

    if ($fase == 'bigfone') {

        if ($estaNoQuartoSecreto) {

            $estadoBigFone =
                function_exists('prepararBigFoneDaRodada')
                    ? prepararBigFoneDaRodada()
                    : 'nao_tocou';

            if (
                $estadoBigFone === 'tocou' &&
                function_exists('npcAtendeBigFone') &&
                function_exists('finalizarAtendimentoBigFone')
            ) {
                $atendenteNPC =
                    npcAtendeBigFone(
                        $jogadores,
                        $meuNome
                    );

                if (
                    $atendenteNPC !== '' &&
                    !nomeIgual(
                        $atendenteNPC,
                        $meuNome
                    )
                ) {
                    finalizarAtendimentoBigFone(
                        $jogadores,
                        $atendenteNPC,
                        false
                    );
                } else {
                    $_SESSION['bigfone_feito'] = true;
                }

            } else {
                $_SESSION['bigfone_feito'] = true;
            }

            $_SESSION['jogadores'] =
                array_values($jogadores);

            if (
                function_exists('prepararSorteioPoderCuringa') &&
                prepararSorteioPoderCuringa($jogadores)
            ) {
                $_SESSION['fase_semana'] =
                    'poder_curinga';

            } else {
                $_SESSION['fase_semana'] =
                    'interacoes_2';

                $_SESSION['acoes_restantes'] =
                    3;
            }

            header("Location: jogo.php");
            exit;
        }

        header("Location: big_fone.php");
        exit;
    }

    if ($fase == 'poder_curinga') {
        $_SESSION['fase_semana'] =
            'interacoes_2';

        $_SESSION['acoes_restantes'] =
            3;

        header("Location: jogo.php");
        exit;
    }

    if (
        $fase == 'contra_golpe_curinga' ||
        $fase == 'troca_curinga'
    ) {
        definirFaseDepoisDaFormacaoDoParedao(
            $jogadores
        );

        header("Location: jogo.php");
        exit;
    }

    if ($fase == 'festa') {

        if (
            !isset($_SESSION['evento_extra']) ||
            !is_array($_SESSION['evento_extra'])
        ) {
            $_SESSION['evento_extra'] = [];
        }

        $_SESSION['evento_extra'][] =
            "🎉 A festa movimentou a casa com conversas, olhares, alianças e tensão.";

        if (!empty($_SESSION['anjo_autoimune'])) {
            $_SESSION['fase_semana'] = 'paredao';
        } else {
            $_SESSION['fase_semana'] = 'imunizacao_anjo';
        }

        header("Location: jogo.php");
        exit;
    }

    if ($fase == 'confessionario') {
        unset($_SESSION['confessionario_falas']);
        unset($_SESSION['confessionario_feito']);

        $_SESSION['fase_semana'] =
            'interacoes_1';

        $_SESSION['acoes_restantes'] =
            3;

        header("Location: jogo.php");
        exit;
    }

    if ($fase == 'paredao') {
        header("Location: jogo.php");
        exit;
    }
}
