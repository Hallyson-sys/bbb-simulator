<?php

/* =========================================================
   🔥 LÓGICA DO JOGO DA DISCÓRDIA
   ========================================================= */

require_once __DIR__ . '/consequencias_sociais.php';


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
                escolherAlvoNPCPorRelacao(
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
                escolherAlvoNPCPorRelacao(
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

                aplicarConsequenciaSocial(
                    $jogadores,
                    $nomeNPC,
                    $segundo,
                    'discordia_podio_2',
                    'discordia_npc|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|podio2|' .
                    $nomeNPC . '|' . $segundo,
                    "$nomeNPC colocou $segundo em segundo lugar no pódio."
                );

                aplicarConsequenciaSocial(
                    $jogadores,
                    $nomeNPC,
                    $terceiro,
                    'discordia_podio_3',
                    'discordia_npc|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|podio3|' .
                    $nomeNPC . '|' . $terceiro,
                    "$nomeNPC colocou $terceiro em terceiro lugar no pódio."
                );


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
                escolherAlvoNPCPorRelacao(
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


            aplicarConsequenciaSocial(
                $jogadores,
                $nomeNPC,
                $alvo,
                'discordia_aliado',
                'discordia_npc|' .
                ($_SESSION['rodada'] ?? 1) .
                '|aliado|' .
                $nomeNPC . '|' . $alvo,
                "$nomeNPC declarou $alvo como maior aliado."
            );

            if (nomeIgual($alvo, $meuNome)) {
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
                escolherAlvoNPCPorRelacao(
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

                aplicarConsequenciaSocial(
                    $jogadores,
                    $nomeNPC,
                    $alvo,
                    'discordia_negativa_leve',
                    'discordia_npc|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|leve|' .
                    $nomeNPC . '|' . $alvo,
                    "$nomeNPC atacou $alvo de forma leve no Jogo da Discórdia."
                );

                if (nomeIgual($alvo, $meuNome)) {
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

                aplicarConsequenciaSocial(
                    $jogadores,
                    $nomeNPC,
                    $alvo,
                    'discordia_negativa_forte',
                    'discordia_npc|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|forte|' .
                    $nomeNPC . '|' . $alvo,
                    "$nomeNPC atacou $alvo com tudo no Jogo da Discórdia."
                );

                if (nomeIgual($alvo, $meuNome)) {
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

                aplicarConsequenciaSocial(
                    $jogadores,
                    $nomeNPC,
                    $alvo,
                    'discordia_sabonete',
                    'discordia_npc|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|sabonete|' .
                    $nomeNPC . '|' . $alvo,
                    "$nomeNPC sabonetou ao falar de $alvo no Jogo da Discórdia."
                );

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

