<?php

/* =========================================================
   💬 LÓGICA DE INTERAÇÕES
   ========================================================= */


/* =========================================================
   🎭 PERFIL DE PERSONALIDADE
   ========================================================= */

function perfilPersonalidadeCompleto($personalidade)
{
    $dados = [

        "Estrategista" => [
            "treta" => 45,
            "romance" => 30,
            "vt" => 60,
            "alianca" => 95,
            "emocao" => 25,
            "fofoca" => 70
        ],

        "Explosivo" => [
            "treta" => 95,
            "romance" => 35,
            "vt" => 75,
            "alianca" => 25,
            "emocao" => 85,
            "fofoca" => 50
        ],

        "Planta" => [
            "treta" => 10,
            "romance" => 20,
            "vt" => 10,
            "alianca" => 30,
            "emocao" => 20,
            "fofoca" => 10
        ],

        "Manipulador" => [
            "treta" => 60,
            "romance" => 30,
            "vt" => 85,
            "alianca" => 90,
            "emocao" => 20,
            "fofoca" => 95
        ],

        "Emocional" => [
            "treta" => 75,
            "romance" => 95,
            "vt" => 60,
            "alianca" => 70,
            "emocao" => 100,
            "fofoca" => 45
        ],

        "Barraqueiro" => [
            "treta" => 100,
            "romance" => 25,
            "vt" => 90,
            "alianca" => 20,
            "emocao" => 75,
            "fofoca" => 60
        ],

        "Fofo" => [
            "treta" => 5,
            "romance" => 80,
            "vt" => 40,
            "alianca" => 85,
            "emocao" => 90,
            "fofoca" => 15
        ],

        "Líder Nato" => [
            "treta" => 50,
            "romance" => 40,
            "vt" => 80,
            "alianca" => 85,
            "emocao" => 55,
            "fofoca" => 50
        ],

        "Influencer" => [
            "treta" => 55,
            "romance" => 70,
            "vt" => 100,
            "alianca" => 60,
            "emocao" => 60,
            "fofoca" => 65
        ],

        "Falso" => [
            "treta" => 70,
            "romance" => 50,
            "vt" => 80,
            "alianca" => 90,
            "emocao" => 35,
            "fofoca" => 100
        ],

        "Neutro" => [
            "treta" => 50,
            "romance" => 50,
            "vt" => 50,
            "alianca" => 50,
            "emocao" => 50,
            "fofoca" => 50
        ]
    ];

    return $dados[$personalidade] ?? $dados['Neutro'];
}


/* =========================================================
   🤖 GERAR AÇÕES DOS NPCs
   ========================================================= */

function gerarAcoesNPC(
    &$jogadores,
    $meuNome,
    $quantidade = 3
) {

    $eventos = [];

    if (function_exists('sincronizarMemoriasAutomaticasNPC')) {
        sincronizarMemoriasAutomaticasNPC($jogadores);
    }

    atualizarRelacoesMarcantes($jogadores);


    foreach ($jogadores as $npc) {

        $nomeNPC =
            $npc['nome'] ?? '';

        $perfil =
            perfilPersonalidadeCompleto(
                $npc['personalidade'] ?? 'Neutro'
            );


        if (
            $nomeNPC == '' ||
            $nomeNPC == $meuNome
        ) {
            continue;
        }


        for ($i = 0; $i < $quantidade; $i++) {

            unset($alvo);


            $alvos = array_values(
                array_filter(
                    $jogadores,
                    function ($j) use ($nomeNPC) {

                        return
                            ($j['nome'] ?? '') != $nomeNPC;
                    }
                )
            );


            if (empty($alvos)) {
                continue;
            }


            $nomeAlvoEscolhido = null;


            /*
             * A inteligência central escolhe quem mais faz sentido
             * para este NPC procurar nesta interação.
             */
            if (
                function_exists(
                    'escolherAlvoInteracaoNPC'
                )
            ) {
                $nomeAlvoEscolhido =
                    escolherAlvoInteracaoNPC(
                        $jogadores,
                        $nomeNPC,
                        $meuNome
                    );
            }


            /*
             * Fallback simples caso a IA central ainda não esteja
             * carregada em algum fluxo antigo.
             */
            if (
                $nomeAlvoEscolhido == null
            ) {
                $nomeAlvoEscolhido =
                    $alvos[
                        array_rand($alvos)
                    ]['nome'] ?? null;
            }


            if (
                $nomeAlvoEscolhido != null
            ) {
                $alvo =
                    buscarJogadorPorNome(
                        $jogadores,
                        $nomeAlvoEscolhido
                    );
            }


            if (
                !isset($alvo) ||
                $alvo == null
            ) {
                $alvo =
                    $alvos[
                        array_rand($alvos)
                    ];
            }


            $nomeAlvo =
                $alvo['nome'] ?? '';


            $relacaoAlvo =
                calcularRelacaoIA(
                    $jogadores,
                    $nomeNPC,
                    $nomeAlvo,
                    $meuNome
                );


            $ehRival =
                saoRivais(
                    $jogadores,
                    $nomeNPC,
                    $nomeAlvo,
                    $meuNome
                );


            $ehAliado =
                saoAliados(
                    $jogadores,
                    $nomeNPC,
                    $nomeAlvo,
                    $meuNome
                );


            /* NPCs 2.0: personalidade + relação + memória recente. */
            if (function_exists('escolherAcaoInteracaoNPC')) {
                $acao = escolherAcaoInteracaoNPC(
                    $jogadores,
                    $nomeNPC,
                    $nomeAlvo,
                    $meuNome,
                    $perfil,
                    $ehRival,
                    $ehAliado
                );
            } else {
                if ($ehRival) {
                    $fallback = [2, 3, 3];
                } elseif ($ehAliado) {
                    $fallback = [1, 4, 4];
                } else {
                    $fallback = [1, 2, 3, 4];
                }
                $acao = $fallback[array_rand($fallback)];
            }


            /* =================================================
               💬 1 — CONVERSAR
               ================================================= */

            if ($acao == 1) {

                alterarAfinidade(
                    $jogadores,
                    $nomeNPC,
                    $nomeAlvo,
                    5,
                    -2,
                    3
                );


                alterarAfinidade(
                    $jogadores,
                    $nomeAlvo,
                    $nomeNPC,
                    3,
                    -1,
                    2
                );


                if ($nomeAlvo == $meuNome) {

                    ajustarRelacaoJogador(
                        $nomeNPC,
                        5
                    );


                    $eventos[] =
                        "💬 $nomeNPC conversou com $meuNome. Sua afinidade com $nomeNPC subiu.";

                } else {

                    $eventos[] =
                        "💬 $nomeNPC conversou com $nomeAlvo.";
                }
            }


            /* =================================================
               🐍 2 — FOFOCA / APROXIMAÇÃO
               ================================================= */

            if ($acao == 2) {

                if ($ehRival) {

                    alterarAfinidade(
                        $jogadores,
                        $nomeNPC,
                        $nomeAlvo,
                        -6,
                        6,
                        -5
                    );


                    alterarAfinidade(
                        $jogadores,
                        $nomeAlvo,
                        $nomeNPC,
                        -5,
                        5,
                        -4
                    );


                    if ($nomeAlvo == $meuNome) {

                        ajustarRelacaoJogador(
                            $nomeNPC,
                            -6
                        );


                        $eventos[] =
                            "🐍 $nomeNPC espalhou comentários contra $meuNome. A rivalidade aumentou.";

                    } else {

                        impactoPopularidadePorPersonalidade(
                            $jogadores,
                            $nomeNPC,
                            "fofoca",
                            false
                        );


                        $eventos[] =
                            "🐍 $nomeNPC espalhou comentários contra $nomeAlvo.";
                    }

                } else {

                    alterarAfinidade(
                        $jogadores,
                        $nomeNPC,
                        $nomeAlvo,
                        8,
                        -3,
                        6
                    );


                    alterarAfinidade(
                        $jogadores,
                        $nomeAlvo,
                        $nomeNPC,
                        5,
                        -2,
                        4
                    );


                    if ($nomeAlvo == $meuNome) {

                        ajustarRelacaoJogador(
                            $nomeNPC,
                            8
                        );


                        $eventos[] =
                            "🤝 $nomeNPC tentou se aproximar de $meuNome. Sua afinidade com $nomeNPC subiu.";

                    } else {

                        $eventos[] =
                            "🤝 $nomeNPC tentou se aproximar de $nomeAlvo.";
                    }
                }
            }


            /* =================================================
               🔥 3 — DISCUTIR
               ================================================= */

            if ($acao == 3) {

                alterarAfinidade(
                    $jogadores,
                    $nomeNPC,
                    $nomeAlvo,
                    -8,
                    8,
                    -5
                );


                alterarAfinidade(
                    $jogadores,
                    $nomeAlvo,
                    $nomeNPC,
                    -8,
                    8,
                    -5
                );


                if ($nomeAlvo == $meuNome) {

                    ajustarRelacaoJogador(
                        $nomeNPC,
                        -8
                    );


                    $eventos[] =
                        "🔥 $nomeNPC teve um atrito com $meuNome. Sua afinidade com $nomeNPC caiu.";

                } else {

                    impactoPopularidadePorPersonalidade(
                        $jogadores,
                        $nomeNPC,
                        "treta",
                        false
                    );


                    $eventos[] =
                        "🔥 $nomeNPC teve um atrito com $nomeAlvo.";
                }
            }


            /* =================================================
               👀 4 — APROXIMAÇÃO / ALIANÇA
               ================================================= */

            if ($acao == 4) {

                alterarAfinidade(
                    $jogadores,
                    $nomeNPC,
                    $nomeAlvo,
                    10,
                    -4,
                    8
                );


                alterarAfinidade(
                    $jogadores,
                    $nomeAlvo,
                    $nomeNPC,
                    6,
                    -2,
                    5
                );


                if ($nomeAlvo == $meuNome) {

                    ajustarRelacaoJogador(
                        $nomeNPC,
                        10
                    );


                    $eventos[] =
                        "👀 $nomeNPC começou uma possível aliança com $meuNome. Sua afinidade com $nomeNPC subiu bastante.";

                } else {

                    impactoPopularidadePorPersonalidade(
                        $jogadores,
                        $nomeNPC,
                        "alianca",
                        false
                    );


                    $eventos[] =
                        "👀 $nomeNPC começou uma possível aliança com $nomeAlvo.";
                }


                if (
                    rand(1, 100) <= 35
                ) {

                    $eventoAlianca =
                        criarAliancaEntre(
                            $jogadores,
                            $nomeNPC,
                            $nomeAlvo
                        );


                    if ($eventoAlianca != '') {

                        $eventos[] =
                            $eventoAlianca;
                    }
                }
            }


            /* Registra a ação para reduzir repetições futuras. */
            if (function_exists('registrarAcaoRecenteNPC')) {
                registrarAcaoRecenteNPC(
                    $nomeNPC,
                    $acao,
                    $nomeAlvo,
                    $ehRival,
                    $ehAliado
                );
            }

            /*
             * Fase 2: o alvo também guarda uma lembrança social
             * do que acabou de acontecer. Essa memória continua
             * influenciando decisões nas próximas rodadas.
             */
            if (function_exists('registrarMemoriaSocialNPC')) {
                if ($acao == 1) {
                    registrarMemoriaSocialNPC(
                        $nomeAlvo,
                        $nomeNPC,
                        'me_aproximou',
                        1,
                        "$nomeNPC conversou e se aproximou de $nomeAlvo."
                    );
                }

                if ($acao == 2 && $ehRival) {
                    registrarMemoriaSocialNPC(
                        $nomeAlvo,
                        $nomeNPC,
                        'espalhou_fofoca',
                        1,
                        "$nomeNPC espalhou comentários contra $nomeAlvo."
                    );
                }

                if ($acao == 2 && !$ehRival) {
                    registrarMemoriaSocialNPC(
                        $nomeAlvo,
                        $nomeNPC,
                        'me_aproximou',
                        1,
                        "$nomeNPC tentou se aproximar de $nomeAlvo."
                    );
                }

                if ($acao == 3) {
                    registrarMemoriaSocialNPC(
                        $nomeAlvo,
                        $nomeNPC,
                        'brigou_comigo',
                        1,
                        "$nomeNPC teve um atrito com $nomeAlvo."
                    );
                }

                if ($acao == 4) {
                    registrarMemoriaSocialNPC(
                        $nomeAlvo,
                        $nomeNPC,
                        'me_aproximou',
                        2,
                        "$nomeNPC propôs uma aproximação estratégica com $nomeAlvo."
                    );
                }
            }


            /* Atualiza aliados/rivais */

            registrarRelacaoMarcante(
                $jogadores,
                $nomeNPC,
                $nomeAlvo
            );


            registrarRelacaoMarcante(
                $jogadores,
                $nomeAlvo,
                $nomeNPC
            );
        }
    }


    $_SESSION['jogadores'] =
        $jogadores;


    return $eventos;
}


/* =========================================================
   🤖 EXECUTAR INTERAÇÕES AUTOMÁTICAS DA FASE
   ========================================================= */

function npcExecutarInteracoesDaFase(
    &$jogadores,
    $meuNome,
    $fase,
    $quantidade = 3
) {

    $chave =
        'npc_interacoes_feitas_' . $fase;


    /*
     * Impede a IA de executar as ações
     * novamente ao atualizar a página.
     */
    if (isset($_SESSION[$chave])) {
        return;
    }


    $eventosNPC =
        gerarAcoesNPC(
            $jogadores,
            $meuNome,
            $quantidade
        );


    foreach ($eventosNPC as $ev) {

        $_SESSION['evento_extra'][] =
            $ev;
    }


    /*
     * Alianças também podem mudar
     * automaticamente depois das interações.
     */
    $eventosAliancas =
        atualizarAliancasAutomaticas(
            $jogadores,
            $meuNome
        );


    foreach ($eventosAliancas as $ev) {

        $_SESSION['evento_extra'][] =
            $ev;
    }


    $_SESSION['jogadores'] =
        $jogadores;


    $_SESSION[$chave] =
        true;
}