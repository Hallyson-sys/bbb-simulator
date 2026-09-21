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


/* =========================================================
   🎲 VARIEDADE DAS INTERAÇÕES DOS NPCs
   ========================================================= */

function garantirHistoricoInteracoesNPC()
{
    if (
        !isset($_SESSION['historico_interacoes_npc']) ||
        !is_array($_SESSION['historico_interacoes_npc'])
    ) {
        $_SESSION['historico_interacoes_npc'] = [];
    }
}


function registrarHistoricoInteracaoNPC(
    $npc,
    $acao,
    $alvo
) {
    garantirHistoricoInteracoesNPC();

    if (
        !isset(
            $_SESSION[
                'historico_interacoes_npc'
            ][$npc]
        ) ||
        !is_array(
            $_SESSION[
                'historico_interacoes_npc'
            ][$npc]
        )
    ) {
        $_SESSION[
            'historico_interacoes_npc'
        ][$npc] = [];
    }

    $_SESSION[
        'historico_interacoes_npc'
    ][$npc][] = [
        'acao' => (int)$acao,
        'alvo' => (string)$alvo,
        'rodada' =>
            (int)($_SESSION['rodada'] ?? 1),
        'fase' =>
            (string)($_SESSION['fase_semana'] ?? '')
    ];

    $_SESSION[
        'historico_interacoes_npc'
    ][$npc] =
        array_slice(
            $_SESSION[
                'historico_interacoes_npc'
            ][$npc],
            -7
        );
}


function escolherAcaoNPCVariada(
    $nomeNPC,
    $possiveis
) {
    garantirHistoricoInteracoesNPC();

    if (empty($possiveis)) {
        return rand(1, 4);
    }

    /*
     * A quantidade de vezes que uma ação aparece em $possiveis
     * continua representando a personalidade e a relação.
     */
    $pesos = [];

    foreach ($possiveis as $acao) {
        $acao = (int)$acao;

        if ($acao < 1 || $acao > 4) {
            continue;
        }

        $pesos[$acao] =
            ($pesos[$acao] ?? 0)
            + 100;
    }

    if (empty($pesos)) {
        return rand(1, 4);
    }

    $historico =
        $_SESSION[
            'historico_interacoes_npc'
        ][$nomeNPC]
        ?? [];

    $ultimos =
        array_reverse(
            array_slice(
                $historico,
                -4
            )
        );

    /*
     * Repetir a última ação continua possível,
     * mas fica bem menos provável.
     */
    foreach ($ultimos as $posicao => $item) {

        $acaoAnterior =
            (int)($item['acao'] ?? 0);

        if (!isset($pesos[$acaoAnterior])) {
            continue;
        }

        if ($posicao === 0) {
            $pesos[$acaoAnterior] *= 0.18;

        } elseif ($posicao === 1) {
            $pesos[$acaoAnterior] *= 0.48;

        } else {
            $pesos[$acaoAnterior] *= 0.72;
        }
    }

    /*
     * Pequena dose de imprevisibilidade:
     * às vezes um NPC faz algo fora do padrão
     * sem deixar a personalidade irrelevante.
     */
    if (rand(1, 100) <= 18) {

        for ($acao = 1; $acao <= 4; $acao++) {

            if (!isset($pesos[$acao])) {
                $pesos[$acao] =
                    rand(12, 30);
            }
        }
    }

    foreach ($pesos as $acao => $peso) {
        $pesos[$acao] =
            max(
                1,
                (int)round($peso)
            );
    }

    $total =
        array_sum($pesos);

    $sorteio =
        rand(1, $total);

    $acumulado = 0;

    foreach ($pesos as $acao => $peso) {

        $acumulado += $peso;

        if ($sorteio <= $acumulado) {
            return (int)$acao;
        }
    }

    return (int)array_key_first($pesos);
}


function gerarAcoesNPC(
    &$jogadores,
    $meuNome,
    $quantidade = 3
) {

    $eventos = [];

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


            $possiveis = [];


            /* =========================
               😡 RIVAL
               ========================= */

            if ($ehRival) {

                $possiveis = [
                    3,
                    3,
                    2
                ];


                if (
                    ($perfil['fofoca'] ?? 0) >= 60
                ) {
                    $possiveis[] = 2;
                }


                if (
                    ($perfil['treta'] ?? 0) >= 70
                ) {
                    $possiveis[] = 3;
                }
            }


            /* =========================
               🤝 ALIADO
               ========================= */

            elseif ($ehAliado) {

                $possiveis = [
                    1,
                    4,
                    4
                ];


                if (
                    ($perfil['alianca'] ?? 0) >= 60
                ) {
                    $possiveis[] = 4;
                }
            }


            /* =========================
               😶 RELAÇÃO NEUTRA
               ========================= */

            else {

                if (
                    rand(1, 100) <=
                    $perfil['alianca']
                ) {
                    $possiveis[] = 4;
                }


                if (
                    rand(1, 100) <=
                    $perfil['treta']
                ) {
                    $possiveis[] = 3;
                }


                if (
                    rand(1, 100) <=
                    $perfil['emocao']
                ) {
                    $possiveis[] = 1;
                }


                if (
                    rand(1, 100) <=
                    $perfil['fofoca']
                ) {
                    $possiveis[] = 2;
                }
            }


            if (empty($possiveis)) {

                $possiveis[] =
                    rand(1, 4);
            }


            $acao =
                escolherAcaoNPCVariada(
                    $nomeNPC,
                    $possiveis
                );


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


            /*
             * Guarda ação e alvo recentes para reduzir
             * repetições nas próximas interações.
             */
            registrarHistoricoInteracaoNPC(
                $nomeNPC,
                $acao,
                $nomeAlvo
            );


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