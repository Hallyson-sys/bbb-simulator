<?php

/** @var string $fase */
/** @var array $jogadores */
/** @var string $meuNome */


/* =========================================================
   🎁/☎️ EFEITOS DE VOTAÇÃO SEPARADOS
   ========================================================= */

function origensAnulacaoVotoEspecial($votante)
{
    $origens = [];

    if (
        votoEstaAnuladoPeloCuringa(
            $votante
        )
    ) {
        $origens[] = 'Poder Curinga';
    }

    if (
        votoEstaAnuladoPeloBigFone(
            $votante
        )
    ) {
        $origens[] = 'Big Fone';
    }

    return $origens;
}


function pesoVotoComPoderes($votante)
{
    /*
     * Caso a mesma pessoa receba Voto Duplo pelas duas fontes,
     * o efeito não acumula para quatro votos.
     * O voto continua valendo por dois.
     */
    return max(
        pesoVotoCuringa(
            $votante
        ),
        pesoVotoBigFone(
            $votante
        )
    );
}


function origensVotoDuploEspecial($votante)
{
    $origens = [];

    if (
        pesoVotoCuringa(
            $votante
        ) > 1
    ) {
        $origens[] = 'Poder Curinga';
    }

    if (
        pesoVotoBigFone(
            $votante
        ) > 1
    ) {
        $origens[] = 'Big Fone';
    }

    return $origens;
}


/* =========================
   👑 INDICAÇÃO DO LÍDER
========================= */

if (isset($_POST['indicar_lider'])) {

    $indicadoLider = $_POST['indicado_lider'] ?? '';

    if (estaImune($jogadores, $indicadoLider)) {

        $_SESSION['evento_extra'][] =
            "🛡️ Indicação inválida: $indicadoLider está imune e não pode ir ao paredão.";

        header("Location: jogo.php");
        exit;
    }

    $_SESSION['indicacao_lider'] = $indicadoLider;

    if (
        function_exists(
            'registrarMemoriaSocialNPC'
        )
    ) {
        $liderDaVez =
            $_SESSION['lider'] ?? '';

        registrarMemoriaSocialNPC(
            $indicadoLider,
            $liderDaVez,
            'me_indicou',
            1,
            "$liderDaVez indicou $indicadoLider ao paredão.",
            'indicacao_lider|' .
            ($_SESSION['rodada'] ?? 1) .
            '|' .
            $liderDaVez .
            '|' .
            $indicadoLider
        );
    }

    $_SESSION['evento_extra'][] =
        "👑 O líder " .
        $_SESSION['lider'] .
        " indicou " .
        $_SESSION['indicacao_lider'] .
        " ao paredão.";

    header("Location: jogo.php");
    exit;
}


/* =========================
   ☎️ INDICAÇÃO DO BIG FONE
========================= */

if (isset($_POST['indicar_bigfone'])) {

    $indicadoBigfone =
        $_POST['indicado_bigfone'] ?? '';

    if (
        $indicadoBigfone == ($_SESSION['lider'] ?? '') ||
        $indicadoBigfone == ($_SESSION['indicacao_lider'] ?? '') ||
        $indicadoBigfone == $meuNome ||
        estaImune($jogadores, $indicadoBigfone)
    ) {

        $_SESSION['evento_extra'][] =
            "⚠️ Indicação inválida do Big Fone.";

        header("Location: jogo.php");
        exit;
    }

    $_SESSION['indicacao_bigfone'] =
        $indicadoBigfone;

    if (
        function_exists(
            'registrarMemoriaSocialNPC'
        )
    ) {
        $donoBigFone =
            $_SESSION['bigfone_dono_poder'] ?? '';

        registrarMemoriaSocialNPC(
            $indicadoBigfone,
            $donoBigFone,
            'me_indicou',
            1,
            "$donoBigFone indicou $indicadoBigfone pelo Big Fone.",
            'indicacao_bigfone|' .
            ($_SESSION['rodada'] ?? 1) .
            '|' .
            $donoBigFone .
            '|' .
            $indicadoBigfone
        );
    }

    unset(
        $_SESSION['bigfone_indicacao_pendente']
    );

    $_SESSION['evento_extra'][] =
        "☎️ Pelo poder do Big Fone, " .
        $_SESSION['bigfone_dono_poder'] .
        " indicou " .
        $_SESSION['indicacao_bigfone'] .
        " ao paredão.";

    header("Location: jogo.php");
    exit;
}


/* =========================
   🗳️ VOTO DO JOGADOR
========================= */

if (isset($_POST['votar_paredao'])) {

    $votoParedao =
        $_POST['voto_paredao'] ?? '';

    if (
        $votoParedao == ($_SESSION['indicacao_lider'] ?? '') ||
        $votoParedao == ($_SESSION['indicacao_bigfone'] ?? '') ||
        $votoParedao == ($_SESSION['lider'] ?? '') ||
        estaImune($jogadores, $votoParedao)
    ) {

        $_SESSION['evento_extra'][] =
            "⚠️ Voto inválido. Escolha outro participante.";

        header("Location: jogo.php");
        exit;
    }

    $_SESSION['meu_voto_paredao'] =
        $votoParedao;

    if (
        function_exists(
            'registrarMemoriaSocialNPC'
        )
    ) {
        registrarMemoriaSocialNPC(
            $votoParedao,
            $meuNome,
            'votou_em_mim',
            1,
            "$meuNome votou em $votoParedao.",
            'voto|' .
            ($_SESSION['rodada'] ?? 1) .
            '|' .
            $meuNome .
            '|' .
            $votoParedao
        );
    }

    $_SESSION['evento_extra'][] =
        "🗳️ $meuNome votou no confessionário.";

    header("Location: jogo.php");
    exit;
}


/* =========================
   🚨 PROCESSAR PAREDÃO
========================= */

if (
    $fase == 'paredao' &&
    !isset($_SESSION['paredao_formado'])
) {

    $lider =
        $_SESSION['lider'] ?? '';

    reaplicarImunidadeCuringa(
        $jogadores
    );

    sincronizarImunidadesGlobais(
        $jogadores
    );

    $_SESSION['jogadores'] =
        $jogadores;


    /* =========================
       🏆 TOP 4
    ========================= */

    if (
        count($jogadores) == 4 &&
        $lider != ''
    ) {

        $paredaoTop4 = [];

        foreach ($jogadores as $j) {

            $nomeTop4 =
                $j['nome'] ?? '';

            if (
                $nomeTop4 != $lider &&
                !estaImune(
                    $jogadores,
                    $nomeTop4
                )
            ) {

                $paredaoTop4[] =
                    $nomeTop4;
            }
        }

        $_SESSION['paredao'] =
            array_values(
                $paredaoTop4
            );

        blindarParedaoContraImunes(
            $jogadores
        );

        $_SESSION['paredao_formado'] =
            true;


        unset(
            $_SESSION['indicacao_lider']
        );

        unset(
            $_SESSION['indicacao_bigfone']
        );

        unset(
            $_SESSION['meu_voto_paredao']
        );

        unset(
            $_SESSION['votos_paredao']
        );

        unset(
            $_SESSION['dedo_duro']
        );


        $_SESSION['evento_extra'][] =
            "🏆 Reta final! O líder $lider está salvo do paredão.";

        $_SESSION['evento_extra'][] =
            "🚨 Está formado o paredão final: " .
            implode(
                " x ",
                $_SESSION['paredao']
            ) .
            ".";


        $_SESSION['fase_semana'] =
            'eliminacao';


        header("Location: jogo.php");
        exit;
    }


    /* =========================
       1️⃣ INDICAÇÃO DO LÍDER
    ========================= */

    if (
        !isset(
            $_SESSION['indicacao_lider']
        )
    ) {

        if ($lider == $meuNome) {

            /*
             * Jogador é o líder.
             * Espera a escolha feita pelo HTML.
             */

            goto fim_paredao;
        }


        /* Líder NPC */

        $alvo =
            escolherAlvoInteligente(
                $jogadores,
                $lider,
                [],
                'indicacao_lider'
            );

        if ($alvo) {

            $_SESSION['indicacao_lider'] =
                $alvo;

            if (
                function_exists(
                    'registrarMemoriaSocialNPC'
                )
            ) {
                registrarMemoriaSocialNPC(
                    $alvo,
                    $lider,
                    'me_indicou',
                    1,
                    "$lider indicou $alvo ao paredão.",
                    'indicacao_lider|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|' .
                    $lider .
                    '|' .
                    $alvo
                );
            }

            $_SESSION['evento_extra'][] =
                "👑 O líder $lider indicou $alvo ao paredão.";

        } else {

            goto fim_paredao;
        }
    }


    /* =========================
       2️⃣ BIG FONE
    ========================= */

    if (
        isset(
            $_SESSION[
                'bigfone_indicacao_pendente'
            ]
        ) &&
        isset(
            $_SESSION[
                'bigfone_dono_poder'
            ]
        ) &&
        !isset(
            $_SESSION[
                'indicacao_bigfone'
            ]
        )
    ) {

        $dono =
            $_SESSION[
                'bigfone_dono_poder'
            ];


        if ($dono == $meuNome) {

            /*
             * Poder pertence ao jogador.
             * Espera a escolha no HTML.
             */

            goto fim_paredao;
        }


        $bloqueados = [];

        $bloqueados[] =
            $_SESSION[
                'indicacao_lider'
            ];

        $bloqueados[] =
            $lider;


        $alvo =
            escolherAlvoInteligente(
                $jogadores,
                $dono,
                $bloqueados,
                'indicacao_bigfone'
            );


        if ($alvo) {

            $_SESSION[
                'indicacao_bigfone'
            ] = $alvo;

            if (
                function_exists(
                    'registrarMemoriaSocialNPC'
                )
            ) {
                registrarMemoriaSocialNPC(
                    $alvo,
                    $dono,
                    'me_indicou',
                    1,
                    "$dono indicou $alvo pelo Big Fone.",
                    'indicacao_bigfone|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|' .
                    $dono .
                    '|' .
                    $alvo
                );
            }

            unset(
                $_SESSION[
                    'bigfone_indicacao_pendente'
                ]
            );


            $_SESSION['evento_extra'][] =
                "☎️ Pelo poder do Big Fone, $dono indicou $alvo ao paredão.";

        } else {

            unset(
                $_SESSION[
                    'bigfone_indicacao_pendente'
                ]
            );
        }
    }


    /* =========================
       3️⃣ VOTO DO JOGADOR
    ========================= */

    if (
        $lider != $meuNome &&
        !isset(
            $_SESSION[
                'meu_voto_paredao'
            ]
        )
    ) {

        /*
         * Jogador não é líder.
         * Espera ele votar.
         */

        goto fim_paredao;
    }


    /* =========================
       🗳️ VOTAÇÃO DA CASA
    ========================= */

    $votos = [];
    $votosDetalhados = [];


    foreach ($jogadores as $j) {

        $votante =
            $j['nome'] ?? '';

        if (empty($votante)) {
            continue;
        }

        if (
            !empty(
                $j['status']['lider']
            )
        ) {
            continue;
        }


        $bloqueados = [
            $votante
        ];


        if (
            isset(
                $_SESSION[
                    'indicacao_lider'
                ]
            )
        ) {

            $bloqueados[] =
                $_SESSION[
                    'indicacao_lider'
                ];
        }


        if (
            isset(
                $_SESSION[
                    'indicacao_bigfone'
                ]
            )
        ) {

            $bloqueados[] =
                $_SESSION[
                    'indicacao_bigfone'
                ];
        }


        /* =========================
           👤 VOTO DO JOGADOR
        ========================= */

        if (
            $votante == $meuNome &&
            isset(
                $_SESSION[
                    'meu_voto_paredao'
                ]
            )
        ) {

            $voto =
                $_SESSION[
                    'meu_voto_paredao'
                ];


            if (
                $voto ==
                    ($_SESSION['indicacao_lider'] ?? '') ||
                $voto ==
                    ($_SESSION['indicacao_bigfone'] ?? '') ||
                $voto == $lider ||
                estaImune(
                    $jogadores,
                    $voto
                )
            ) {

                continue;
            }


            $origensAnulacao =
                origensAnulacaoVotoEspecial(
                    $votante
                );

            if (!empty($origensAnulacao)) {

                $_SESSION['evento_extra'][] =
                    "🚫 " .
                    implode(
                        " + ",
                        $origensAnulacao
                    ) .
                    ": o voto de $votante foi anulado.";

                continue;
            }


            if (
                !isset(
                    $votos[$voto]
                )
            ) {

                $votos[$voto] = 0;
            }


            $pesoVoto =
                pesoVotoComPoderes(
                    $votante
                );


            $votos[$voto] +=
                $pesoVoto;


            $votosDetalhados[] = [

                "votante" =>
                    $votante,

                "voto" =>
                    $voto,

                "peso" =>
                    $pesoVoto

            ];


            if ($pesoVoto > 1) {

                $origensPeso =
                    origensVotoDuploEspecial(
                        $votante
                    );

                $_SESSION['evento_extra'][] =
                    "🗳️ " .
                    implode(
                        " + ",
                        $origensPeso
                    ) .
                    ": o voto de $votante valeu por $pesoVoto.";
            }


            continue;
        }


        /* =========================
           🤖 VOTO DOS NPCs
        ========================= */

        $voto =
            escolherAlvoInteligente(
                $jogadores,
                $votante,
                $bloqueados,
                'voto'
            );


        if ($voto) {

            if (
                function_exists(
                    'registrarMemoriaSocialNPC'
                )
            ) {
                registrarMemoriaSocialNPC(
                    $voto,
                    $votante,
                    'votou_em_mim',
                    1,
                    "$votante votou em $voto.",
                    'voto|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|' .
                    $votante .
                    '|' .
                    $voto
                );
            }


            $origensAnulacao =
                origensAnulacaoVotoEspecial(
                    $votante
                );

            if (!empty($origensAnulacao)) {

                $_SESSION['evento_extra'][] =
                    "🚫 " .
                    implode(
                        " + ",
                        $origensAnulacao
                    ) .
                    ": o voto de $votante foi anulado.";

                continue;
            }


            if (
                !isset(
                    $votos[$voto]
                )
            ) {

                $votos[$voto] = 0;
            }


            $pesoVoto =
                pesoVotoComPoderes(
                    $votante
                );


            $votos[$voto] +=
                $pesoVoto;


            $votosDetalhados[] = [

                "votante" =>
                    $votante,

                "voto" =>
                    $voto,

                "peso" =>
                    $pesoVoto

            ];


            if ($pesoVoto > 1) {

                $origensPeso =
                    origensVotoDuploEspecial(
                        $votante
                    );

                $_SESSION['evento_extra'][] =
                    "🗳️ " .
                    implode(
                        " + ",
                        $origensPeso
                    ) .
                    ": o voto de $votante valeu por $pesoVoto.";
            }
        }
    }


    /* =========================
       📊 RESULTADO DOS VOTOS
    ========================= */

    arsort(
        $votos
    );


    $_SESSION['votos_paredao'] =
        $votos;


    if (
        !empty(
            $votosDetalhados
        )
    ) {

        $_SESSION['dedo_duro'] =
            $votosDetalhados[
                array_rand(
                    $votosDetalhados
                )
            ];
    }


    aplicarEspiaoCuringaNoResultado(
        $votosDetalhados
    );

    aplicarEspiaoBigFoneNoResultado(
        $votosDetalhados
    );


    $maisVotados =
        array_keys(
            $votos
        );


    $paredao = [];


    if (
        isset(
            $_SESSION[
                'indicacao_lider'
            ]
        )
    ) {

        adicionarAoParedaoSeValido(
            $paredao,
            $jogadores,
            $_SESSION[
                'indicacao_lider'
            ],
            "indicação do líder"
        );
    }


    if (
        isset(
            $_SESSION[
                'indicacao_bigfone'
            ]
        )
    ) {

        adicionarAoParedaoSeValido(
            $paredao,
            $jogadores,
            $_SESSION[
                'indicacao_bigfone'
            ],
            "poder do Big Fone"
        );
    }


    foreach (
        $maisVotados as $nome
    ) {

        adicionarAoParedaoSeValido(
            $paredao,
            $jogadores,
            $nome,
            "votação da casa"
        );
    }


    $limiteParedao =
        definirTamanhoParedao(
            $jogadores
        );


    $_SESSION['paredao'] =
        array_slice(
            $paredao,
            0,
            $limiteParedao
        );


    blindarParedaoContraImunes(
        $jogadores
    );


    /* =====================================================
       ☎️ PODERES DO BIG FONE — NPC
       ===================================================== */

    $eventoContraBigFoneNPC =
        aplicarContraGolpeBigFoneNPC(
            $jogadores,
            $_SESSION['paredao']
        );

    if (
        $eventoContraBigFoneNPC != ""
    ) {

        $_SESSION['evento_extra'][] =
            $eventoContraBigFoneNPC;
    }


    $eventoTrocaBigFoneNPC =
        aplicarTrocaEmparedadoBigFoneNPC(
            $jogadores,
            $_SESSION['paredao']
        );

    if (
        $eventoTrocaBigFoneNPC != ""
    ) {

        $_SESSION['evento_extra'][] =
            $eventoTrocaBigFoneNPC;
    }


    /* =====================================================
       🎁 PODER CURINGA — NPC
       ===================================================== */

    $eventoContraGolpeNPC =
        aplicarContraGolpeCuringaNPC(
            $jogadores,
            $_SESSION['paredao']
        );

    if (
        $eventoContraGolpeNPC != ""
    ) {

        $_SESSION['evento_extra'][] =
            $eventoContraGolpeNPC;
    }


    $eventoTrocaNPC =
        aplicarTrocaEmparedadoCuringaNPC(
            $jogadores,
            $_SESSION['paredao']
        );

    if (
        $eventoTrocaNPC != ""
    ) {

        $_SESSION['evento_extra'][] =
            $eventoTrocaNPC;
    }


    $_SESSION['paredao'] =
        array_values(
            array_unique(
                $_SESSION['paredao']
            )
        );


    blindarParedaoContraImunes(
        $jogadores
    );


    $_SESSION['paredao_formado'] =
        true;


    /* =========================
       📺 AO VIVO
    ========================= */

    $_SESSION['evento_extra'][] =
        "🗳️ Resultado da Votação da Casa:";


    foreach (
        $votos as $nome => $qtd
    ) {

        $_SESSION['evento_extra'][] =
            "📊 $nome recebeu $qtd voto(s).";
    }


    if (
        isset(
            $_SESSION['dedo_duro']
        )
    ) {

        $_SESSION['evento_extra'][] =
            "🕵️ Dedo-duro: " .
            $_SESSION['dedo_duro']['votante'] .
            " votou em " .
            $_SESSION['dedo_duro']['voto'] .
            ".";
    }


    $_SESSION['evento_extra'][] =
        "🚨 Está formado o paredão: " .
        implode(
            " x ",
            $_SESSION['paredao']
        ) .
        ".";


    definirFaseDepoisDaFormacaoDoParedao(
        $jogadores
    );


    header("Location: jogo.php");
    exit;
}


/* =========================
   ⏸️ AGUARDAR ESCOLHA DO JOGADOR
========================= */

fim_paredao: