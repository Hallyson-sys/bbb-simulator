<?php

/* =========================================================
   🎉 LÓGICA DA FESTA
   Ações automáticas dos NPCs durante a festa
   ========================================================= */

if (
    !function_exists(
        'escolherAlvoInteracaoNPC'
    )
) {
    require_once __DIR__ . '/inteligencia_npc.php';
}

function gerarAcoesFestaNPC(
    &$jogadores,
    $meuNome,
    $quantidade = 2
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


            /* =================================================
               🧠 CÉREBRO CENTRAL ESCOLHE O ALVO DA FESTA
               ================================================= */

            $nomeAlvoEscolhido =
                escolherAlvoInteracaoNPC(
                    $jogadores,
                    $nomeNPC,
                    $meuNome
                );

            if ($nomeAlvoEscolhido != null) {

                $alvo =
                    buscarJogadorPorNome(
                        $jogadores,
                        $nomeAlvoEscolhido
                    );
            }


            /*
             * Segurança: se a IA não encontrou ninguém,
             * usa um alvo válido aleatório.
             */
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


            /* =================================================
               😡 SE FOR RIVAL
               ================================================= */

            if ($ehRival) {

                /*
                 * 4 = treta
                 * 3 = VT
                 */
                $possiveis = [
                    4,
                    4,
                    3
                ];


                if (
                    ($perfil['treta'] ?? 0) >= 70
                ) {

                    $possiveis[] = 4;
                }
            }


            /* =================================================
               🤝 SE FOR ALIADO
               ================================================= */

            elseif ($ehAliado) {

                /*
                 * 1 = aproximar
                 * 5 = dançar
                 */
                $possiveis = [
                    1,
                    5,
                    5
                ];


                /*
                 * Personalidade romântica pode flertar.
                 */
                if (
                    ($perfil['romance'] ?? 0) >= 60
                ) {

                    $possiveis[] = 2;
                }
            }


            /* =================================================
               😶 SE A RELAÇÃO FOR NEUTRA
               ================================================= */

            else {

                if (
                    rand(1, 100) <=
                    $perfil['romance']
                ) {

                    $possiveis[] = 2;
                }


                if (
                    rand(1, 100) <=
                    $perfil['vt']
                ) {

                    $possiveis[] = 3;
                }


                if (
                    rand(1, 100) <=
                    $perfil['treta']
                ) {

                    $possiveis[] = 4;
                }


                if (
                    rand(1, 100) <=
                    $perfil['alianca']
                ) {

                    $possiveis[] = 1;
                    $possiveis[] = 5;
                }
            }


            if (empty($possiveis)) {

                $possiveis[] =
                    rand(1, 5);
            }


            $acao =
                $possiveis[
                    array_rand($possiveis)
                ];


            /* =================================================
               🥂 1 — APROXIMAR
               ================================================= */

            if ($acao == 1) {

                alterarAfinidade(
                    $jogadores,
                    $nomeNPC,
                    $nomeAlvo,
                    rand(5, 10),
                    -3,
                    rand(3, 8)
                );


                alterarAfinidade(
                    $jogadores,
                    $nomeAlvo,
                    $nomeNPC,
                    rand(3, 7),
                    -2,
                    rand(2, 6)
                );


                if ($nomeAlvo == $meuNome) {

                    ajustarRelacaoJogador(
                        $nomeNPC,
                        8
                    );


                    $eventos[] =
                        "🥂 $nomeNPC passou parte da festa junto com $meuNome. Sua afinidade com $nomeNPC subiu.";

                } else {

                    $eventos[] =
                        "🥂 $nomeNPC passou a festa junto com $nomeAlvo.";
                }
            }


            if ($acao == 1) {
                registrarMemoriaSocialNPC(
                    $nomeAlvo,
                    $nomeNPC,
                    'me_aproximou',
                    1,
                    "$nomeNPC se aproximou de $nomeAlvo durante a festa."
                );
            }


            /* =================================================
               💕 2 — FLERTE
               ================================================= */

            if (
                $acao == 2 &&
                !$ehRival
            ) {

                alterarAfinidade(
                    $jogadores,
                    $nomeNPC,
                    $nomeAlvo,
                    rand(8, 15),
                    -2,
                    rand(5, 10)
                );


                alterarAfinidade(
                    $jogadores,
                    $nomeAlvo,
                    $nomeNPC,
                    rand(5, 10),
                    -2,
                    rand(3, 8)
                );


                alterarRomance(
                    $jogadores,
                    $nomeNPC,
                    $nomeAlvo,
                    rand(3, 8)
                );


                alterarRomance(
                    $jogadores,
                    $nomeAlvo,
                    $nomeNPC,
                    rand(2, 6)
                );


                if ($nomeAlvo == $meuNome) {

                    ajustarRelacaoJogador(
                        $nomeNPC,
                        10
                    );


                    $eventos[] =
                        "💕 $nomeNPC flertou com $meuNome na festa. Sua afinidade com $nomeNPC subiu.";

                } else {

                    $eventos[] =
                        "💕 $nomeNPC flertou com $nomeAlvo na festa.";
                }


                registrarMemoriaSocialNPC(
                    $nomeAlvo,
                    $nomeNPC,
                    'flertou_comigo',
                    1,
                    "$nomeNPC flertou com $nomeAlvo durante a festa."
                );


                /* Pode evoluir para namoro */

                $pedidoNPC =
                    tentarPedidoNamoroNPC(
                        $jogadores,
                        $nomeNPC,
                        $nomeAlvo
                    );


                if ($pedidoNPC != '') {

                    if ($nomeAlvo == $meuNome) {

                        if (
                            isset(
                                $_SESSION['casais'][$meuNome]
                            ) &&
                            $_SESSION['casais'][$meuNome] ==
                            $nomeNPC
                        ) {

                            ajustarRelacaoJogador(
                                $nomeNPC,
                                12
                            );

                        } else {

                            ajustarRelacaoJogador(
                                $nomeNPC,
                                -4
                            );
                        }
                    }


                    $eventos[] =
                        $pedidoNPC;
                }
            }


            /* =================================================
               📸 3 — VT
               ================================================= */

            if ($acao == 3) {

                $mudanca =
                    alterarPopularidadeMotivo(
                        $jogadores,
                        $nomeNPC,
                        -5,
                        10,
                        "tentou roubar a cena na festa",
                        false
                    );


                if ($mudanca >= 0) {

                    $eventos[] =
                        "📸 $nomeNPC roubou a cena na festa.";

                } else {

                    $eventos[] =
                        "📸 $nomeNPC tentou aparecer demais e virou meme.";
                }
            }


            /* =================================================
               🔥 4 — TRETA
               ================================================= */

            if ($acao == 4) {

                alterarAfinidade(
                    $jogadores,
                    $nomeNPC,
                    $nomeAlvo,
                    rand(-15, -5),
                    rand(5, 15),
                    rand(-10, -3)
                );


                alterarAfinidade(
                    $jogadores,
                    $nomeAlvo,
                    $nomeNPC,
                    rand(-15, -5),
                    rand(5, 15),
                    rand(-10, -3)
                );


                if ($nomeAlvo == $meuNome) {

                    ajustarRelacaoJogador(
                        $nomeNPC,
                        -12
                    );


                    $eventos[] =
                        "🔥 $nomeNPC discutiu com $meuNome durante a festa. Sua afinidade com $nomeNPC caiu.";

                } else {

                    $eventos[] =
                        "🔥 $nomeNPC discutiu com $nomeAlvo durante a festa.";
                }
            }


            if ($acao == 4) {
                registrarMemoriaSocialNPC(
                    $nomeAlvo,
                    $nomeNPC,
                    'brigou_comigo',
                    2,
                    "$nomeNPC discutiu com $nomeAlvo durante a festa.",
                    'festa_treta|' .
                    ($_SESSION['rodada'] ?? 1) .
                    "|$nomeNPC|$nomeAlvo|" .
                    $i
                );
            }


            /* =================================================
               🕺 5 — DANÇAR
               ================================================= */

            if ($acao == 5) {

                alterarPopularidadeMotivo(
                    $jogadores,
                    $nomeNPC,
                    1,
                    6,
                    "se destacou dançando na festa",
                    false
                );


                alterarAfinidade(
                    $jogadores,
                    $nomeNPC,
                    $nomeAlvo,
                    rand(3, 7),
                    -2,
                    3
                );


                alterarAfinidade(
                    $jogadores,
                    $nomeAlvo,
                    $nomeNPC,
                    rand(2, 5),
                    -1,
                    2
                );


                if ($nomeAlvo == $meuNome) {

                    ajustarRelacaoJogador(
                        $nomeNPC,
                        6
                    );


                    $eventos[] =
                        "🕺 $nomeNPC chamou $meuNome para dançar na festa. Sua afinidade com $nomeNPC subiu.";

                } else {

                    $eventos[] =
                        "🕺 $nomeNPC dançou com $nomeAlvo e viralizou na festa.";
                }
            }


            if ($acao == 5) {
                registrarMemoriaSocialNPC(
                    $nomeAlvo,
                    $nomeNPC,
                    'me_aproximou',
                    1,
                    "$nomeNPC chamou $nomeAlvo para dançar durante a festa."
                );
            }


            /* =================================================
               🔄 ATUALIZAR RELAÇÕES
               ================================================= */

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