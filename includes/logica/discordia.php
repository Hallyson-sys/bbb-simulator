<?php

/* =========================================================
   🔥 LÓGICA DO JOGO DA DISCÓRDIA
   ========================================================= */


/* =========================================================
   📋 TEMAS DISPONÍVEIS
   ========================================================= */

function temasDiscordia()
{
    return [
        "sonso",
        "falso",
        "saboneteiro",
        "aliado",
        "podio"
    ];
}


/* =========================================================
   🎲 PREPARAR TEMA DA DISCÓRDIA
   ========================================================= */

function prepararTemaDiscordia($fase)
{
    if (
        $fase != 'discordia' ||
        isset($_SESSION['tema_discordia'])
    ) {
        return;
    }


    $temas =
        temasDiscordia();


    $_SESSION['tema_discordia'] =
        $temas[
            array_rand($temas)
        ];
}


/* =========================================================
   🤖 GERAR DISCÓRDIA DOS NPCs
   ========================================================= */

function gerarDiscordiaNPC(
    &$jogadores,
    $meuNome,
    $tema
) {

    $eventos = [];


    atualizarRelacoesMarcantes(
        $jogadores
    );


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


        /* =========================
           🎯 POSSÍVEIS ALVOS
           ========================= */

        $alvos = [];


        foreach ($jogadores as $j) {

            if (
                ($j['nome'] ?? '') !=
                $nomeNPC
            ) {

                $alvos[] =
                    $j['nome'];
            }
        }


        if (empty($alvos)) {
            continue;
        }


        /* =====================================================
           🏆 PÓDIO
           ===================================================== */

        if ($tema == "podio") {

            $segundo =
                function_exists(
                    'escolherAlvoNPCInteligente'
                )
                ? escolherAlvoNPCInteligente(
                    $jogadores,
                    $nomeNPC,
                    'podio',
                    [$nomeNPC]
                )
                : escolherAlvoNPCPorRelacao(
                    $jogadores,
                    $nomeNPC,
                    $meuNome,
                    'aliado'
                );


            $terceiro = null;


            if ($segundo != null) {

                $restantes =
                    array_values(
                        array_filter(
                            $alvos,
                            function ($n) use ($segundo) {

                                return
                                    $n != $segundo;
                            }
                        )
                    );

            } else {

                $restantes =
                    $alvos;
            }


            $aliadoExtra =
                function_exists(
                    'escolherAlvoNPCInteligente'
                )
                ? escolherAlvoNPCInteligente(
                    $jogadores,
                    $nomeNPC,
                    'podio',
                    array_filter(
                        [$nomeNPC, $segundo]
                    )
                )
                : escolherAlvoNPCPorRelacao(
                    $jogadores,
                    $nomeNPC,
                    $meuNome,
                    'aliado'
                );


            if (
                $aliadoExtra != null &&
                $aliadoExtra != $segundo
            ) {

                $terceiro =
                    $aliadoExtra;
            }


            if ($segundo == null) {

                shuffle($restantes);

                $segundo =
                    $restantes[0] ?? '';
            }


            if ($terceiro == null) {

                $restantes =
                    array_values(
                        array_filter(
                            $alvos,
                            function ($n) use ($segundo) {

                                return
                                    $n != $segundo;
                            }
                        )
                    );


                shuffle($restantes);


                $terceiro =
                    $restantes[0] ?? '';
            }


            if (
                $segundo &&
                $terceiro
            ) {

                alterarAfinidade(
                    $jogadores,
                    $nomeNPC,
                    $segundo,
                    10,
                    -4,
                    8
                );


                alterarAfinidade(
                    $jogadores,
                    $segundo,
                    $nomeNPC,
                    6,
                    -2,
                    5
                );


                alterarAfinidade(
                    $jogadores,
                    $nomeNPC,
                    $terceiro,
                    6,
                    -2,
                    5
                );


                alterarAfinidade(
                    $jogadores,
                    $terceiro,
                    $nomeNPC,
                    4,
                    -1,
                    3
                );


                if ($segundo == $meuNome) {

                    ajustarRelacaoJogador(
                        $nomeNPC,
                        10
                    );
                }


                if ($terceiro == $meuNome) {

                    ajustarRelacaoJogador(
                        $nomeNPC,
                        6
                    );
                }


                if (
                    function_exists(
                        'registrarMemoriaSocialNPC'
                    )
                ) {
                    registrarMemoriaSocialNPC(
                        $segundo,
                        $nomeNPC,
                        'me_defendeu',
                        1,
                        "$nomeNPC colocou $segundo em seu pódio.",
                        'discordia_podio|' .
                        ($_SESSION['rodada'] ?? 1) .
                        '|' .
                        $nomeNPC .
                        '|' .
                        $segundo
                    );

                    registrarMemoriaSocialNPC(
                        $terceiro,
                        $nomeNPC,
                        'me_defendeu',
                        1,
                        "$nomeNPC colocou $terceiro em seu pódio.",
                        'discordia_podio|' .
                        ($_SESSION['rodada'] ?? 1) .
                        '|' .
                        $nomeNPC .
                        '|' .
                        $terceiro
                    );
                }

                $eventos[] =
                    "🏆 $nomeNPC montou seu pódio: 🥇 $nomeNPC, 🥈 $segundo e 🥉 $terceiro.";
            }


            continue;
        }


        /* =====================================================
           🤝 MAIOR ALIADO
           ===================================================== */

        if ($tema == "aliado") {

            $alvo =
                function_exists(
                    'escolherAlvoNPCInteligente'
                )
                ? escolherAlvoNPCInteligente(
                    $jogadores,
                    $nomeNPC,
                    'discordia_aliado',
                    [$nomeNPC]
                )
                : escolherAlvoNPCPorRelacao(
                    $jogadores,
                    $nomeNPC,
                    $meuNome,
                    'aliado'
                );


            if ($alvo == null) {

                $alvo =
                    $alvos[
                        array_rand($alvos)
                    ];
            }


            if (
                function_exists(
                    'registrarMemoriaSocialNPC'
                )
            ) {
                registrarMemoriaSocialNPC(
                    $alvo,
                    $nomeNPC,
                    'me_defendeu',
                    1,
                    "$nomeNPC declarou $alvo como aliado no Jogo da Discórdia.",
                    'discordia_aliado|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|' .
                    $nomeNPC .
                    '|' .
                    $alvo
                );
            }

            alterarAfinidade(
                $jogadores,
                $nomeNPC,
                $alvo,
                12,
                -5,
                10
            );


            alterarAfinidade(
                $jogadores,
                $alvo,
                $nomeNPC,
                8,
                -3,
                6
            );


            if ($alvo == $meuNome) {

                ajustarRelacaoJogador(
                    $nomeNPC,
                    12
                );


                $eventos[] =
                    "🤝 $nomeNPC declarou que $meuNome é seu maior aliado. Sua afinidade com $nomeNPC subiu.";

            } else {

                $eventos[] =
                    "🤝 $nomeNPC declarou que $alvo é seu maior aliado.";
            }


            continue;
        }


        /* =====================================================
           🔥 SONSO / FALSO / SABONETEIRO
           ===================================================== */

        if (
            $tema == "sonso" ||
            $tema == "falso" ||
            $tema == "saboneteiro"
        ) {

            $alvo =
                function_exists(
                    'escolherAlvoNPCInteligente'
                )
                ? escolherAlvoNPCInteligente(
                    $jogadores,
                    $nomeNPC,
                    'discordia_negativo',
                    [$nomeNPC]
                )
                : escolherAlvoNPCPorRelacao(
                    $jogadores,
                    $nomeNPC,
                    $meuNome,
                    'rival'
                );


            if ($alvo == null) {

                $alvo =
                    $alvos[
                        array_rand($alvos)
                    ];
            }


            if (
                function_exists(
                    'registrarMemoriaSocialNPC'
                )
            ) {
                registrarMemoriaSocialNPC(
                    $alvo,
                    $nomeNPC,
                    'me_atacou_discordia',
                    1,
                    "$nomeNPC atacou $alvo no Jogo da Discórdia.",
                    'discordia_ataque|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|' .
                    $nomeNPC .
                    '|' .
                    $alvo .
                    '|' .
                    $tema
                );
            }


            /* =========================
               💥 INTENSIDADE DO NPC
               ========================= */

            if (
                ($perfil['treta'] ?? 50) >= 80
            ) {

                $forca = 2;

            } elseif (
                ($perfil['treta'] ?? 50) <= 25
            ) {

                $forca = 3;

            } else {

                $forca =
                    rand(1, 3);
            }


            /*
             * Rivais tendem a bater com tudo.
             */
            if (
                saoRivais(
                    $jogadores,
                    $nomeNPC,
                    $alvo,
                    $meuNome
                )
            ) {

                $forca = 2;
            }


            /* =========================
               😶 LEVE
               ========================= */

            if ($forca == 1) {

                alterarAfinidade(
                    $jogadores,
                    $nomeNPC,
                    $alvo,
                    -6,
                    5,
                    -4
                );


                alterarAfinidade(
                    $jogadores,
                    $alvo,
                    $nomeNPC,
                    -6,
                    5,
                    -4
                );


                if ($alvo == $meuNome) {

                    ajustarRelacaoJogador(
                        $nomeNPC,
                        -6
                    );


                    $eventos[] =
                        "😶 $nomeNPC disse que $meuNome é $tema de forma mais leve. Sua afinidade com $nomeNPC caiu.";

                } else {

                    $eventos[] =
                        "😶 $nomeNPC disse que $alvo é $tema de forma mais leve.";
                }
            }


            /* =========================
               🔥 COM TUDO
               ========================= */

            if ($forca == 2) {

                alterarAfinidade(
                    $jogadores,
                    $nomeNPC,
                    $alvo,
                    -12,
                    9,
                    -8
                );


                alterarAfinidade(
                    $jogadores,
                    $alvo,
                    $nomeNPC,
                    -12,
                    9,
                    -8
                );


                if ($alvo == $meuNome) {

                    ajustarRelacaoJogador(
                        $nomeNPC,
                        -12
                    );


                    $eventos[] =
                        "🔥 $nomeNPC chamou $meuNome de $tema no Jogo da Discórdia. Sua afinidade com $nomeNPC caiu bastante.";

                } else {

                    $eventos[] =
                        "🔥 $nomeNPC chamou $alvo de $tema no Jogo da Discórdia.";
                }
            }


            /* =========================
               🧼 SABONETAR
               ========================= */

            if ($forca == 3) {

                alterarPopularidadeMotivo(
                    $jogadores,
                    $nomeNPC,
                    -3,
                    -3,
                    "saboneteou no Jogo da Discórdia",
                    false
                );


                $eventos[] =
                    "🧼 $nomeNPC sabonetou e tentou fugir da pergunta.";
            }


            registrarRelacaoMarcante(
                $jogadores,
                $nomeNPC,
                $alvo
            );


            registrarRelacaoMarcante(
                $jogadores,
                $alvo,
                $nomeNPC
            );
        }
    }


    $_SESSION['jogadores'] =
        $jogadores;


    return $eventos;
}