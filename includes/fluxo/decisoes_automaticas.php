<?php

/** @var string $fase */
/** @var array $jogadores */
/** @var string $meuNome */
/** @var int $qtdVIP */

/* =========================================================
   🤖 DECISÕES AUTOMÁTICAS DOS NPCs

   IMPORTANTE:
   - VIP/Xepa de Líder NPC continua sendo revelado apenas em:
     includes/actions/vip_xepa.php
   - Monstro e imunidade do Anjo agora usam a inteligência
     central dos NPCs.
   ========================================================= */


/* =========================================================
   👹 MONSTRO AUTOMÁTICO DO ANJO NPC
   ========================================================= */

if (
    $fase == 'monstro' &&
    !isset($_SESSION['monstro_definido'])
) {
    $anjo =
        $_SESSION['anjo'] ?? '';

    if (
        $anjo != '' &&
        !nomeIgual(
            $anjo,
            $meuNome
        )
    ) {
        /* =================================================
           🧠 ESCOLHA INTELIGENTE
           ================================================= */

        if (
            function_exists(
                'escolherVariosAlvosNPCInteligentes'
            )
        ) {
            $monstros =
                escolherVariosAlvosNPCInteligentes(
                    $jogadores,
                    $anjo,
                    'monstro',
                    2,
                    [$anjo]
                );
        } else {
            /*
             * Fallback antigo por afinidade.
             */
            $afinidades = [];

            foreach ($jogadores as $j) {
                $nome =
                    $j['nome'] ?? '';

                if (
                    $nome == '' ||
                    nomeIgual(
                        $nome,
                        $anjo
                    )
                ) {
                    continue;
                }

                $afinidade =
                    calcularRelacaoIA(
                        $jogadores,
                        $anjo,
                        $nome,
                        $meuNome
                    );

                $afinidades[$nome] =
                    $afinidade;
            }

            asort($afinidades);

            $monstros =
                array_slice(
                    array_keys(
                        $afinidades
                    ),
                    0,
                    2
                );
        }

        /*
         * Segurança caso reste pouca gente.
         */
        $monstros =
            array_values(
                array_unique(
                    array_filter(
                        $monstros
                    )
                )
            );

        foreach ($jogadores as &$j) {
            $j['status']['monstro'] =
                false;

            if (
                in_array(
                    $j['nome'] ?? '',
                    $monstros,
                    true
                )
            ) {
                $j['status']['monstro'] =
                    true;

                if (
                    !isset($j['estatisticas'])
                ) {
                    $j['estatisticas'] = [];
                }

                $j['estatisticas']['monstro'] =
                    (
                        $j['estatisticas']['monstro']
                        ?? 0
                    ) + 1;

                $j['popularidade'] =
                    max(
                        0,
                        (
                            $j['popularidade']
                            ?? 50
                        )
                        -
                        rand(3, 8)
                    );

                alterarAfinidade(
                    $jogadores,
                    $j['nome'],
                    $anjo,
                    -5,
                    8,
                    -5
                );

                /*
                 * O alvo lembra quem o colocou no Monstro.
                 */
                if (
                    function_exists(
                        'registrarMemoriaSocialNPC'
                    )
                ) {
                    registrarMemoriaSocialNPC(
                        $j['nome'],
                        $anjo,
                        'me_colocou_monstro',
                        1,
                        "$anjo colocou {$j['nome']} no Monstro.",
                        'monstro|' .
                        ($_SESSION['rodada'] ?? 1) .
                        '|' .
                        $anjo .
                        '|' .
                        $j['nome']
                    );
                }
            }
        }

        unset($j);

        garantirMeuJogadorNaLista(
            $jogadores
        );

        $_SESSION['jogadores'] =
            $jogadores;

        $_SESSION['monstro'] =
            $monstros;

        $_SESSION['monstro_definido'] =
            true;

        $_SESSION['fase_semana'] =
            'bigfone';

        $_SESSION['evento_extra'][] =
            "👹 O anjo $anjo colocou no Monstro: "
            .
            implode(
                " e ",
                $monstros
            )
            .
            ".";

        header("Location: jogo.php");
        exit;
    }
}


/* =========================================================
   😇 ANJO AUTOIMUNE
   ========================================================= */

if (
    $fase == 'imunizacao_anjo' &&
    !empty($_SESSION['anjo_autoimune'])
) {
    $anjoAuto =
        $_SESSION['anjo'] ?? '';

    foreach ($jogadores as &$j) {
        if (
            nomeIgual(
                $j['nome'] ?? '',
                $anjoAuto
            )
        ) {
            $j['status']['imune'] =
                true;

            if (
                !isset($j['estatisticas'])
            ) {
                $j['estatisticas'] = [];
            }

            if (
                empty(
                    $_SESSION[
                        'anjo_autoimune_estat_contada'
                    ]
                )
            ) {
                $j['estatisticas']['imune'] =
                    (
                        $j['estatisticas']['imune']
                        ?? 0
                    ) + 1;
            }
        }
    }

    unset($j);

    $_SESSION[
        'anjo_autoimune_estat_contada'
    ] = true;

    $_SESSION['imune'] =
        $anjoAuto;

    $_SESSION[
        'imunizacao_anjo_feita'
    ] = true;

    $_SESSION['jogadores'] =
        $jogadores;

    $_SESSION['evento_extra'][] =
        "🛡️ O Anjo $anjoAuto é autoimune nesta semana e não imuniza outra pessoa.";

    $_SESSION['fase_semana'] =
        'paredao';

    header("Location: jogo.php");
    exit;
}


/* =========================================================
   🛡️ IMUNIZAÇÃO AUTOMÁTICA DO ANJO NPC
   ========================================================= */

if (
    $fase == 'imunizacao_anjo' &&
    !isset($_SESSION['imunizacao_anjo_feita'])
) {
    $anjo =
        $_SESSION['anjo'] ?? '';

    if (
        $anjo != '' &&
        !nomeIgual(
            $anjo,
            $meuNome
        )
    ) {
        $liderAtual =
            $_SESSION['lider'] ?? '';

        /* =================================================
           🧠 ESCOLHA INTELIGENTE
           ================================================= */

        if (
            function_exists(
                'escolherAlvoNPCInteligente'
            )
        ) {
            $bloqueados =
                array_values(
                    array_filter(
                        [
                            $anjo,
                            $liderAtual
                        ]
                    )
                );

            $imunizado =
                escolherAlvoNPCInteligente(
                    $jogadores,
                    $anjo,
                    'imunidade',
                    $bloqueados
                );
        } else {
            /*
             * Fallback antigo:
             * maior afinidade.
             */
            $afinidades = [];

            foreach ($jogadores as $j) {
                $nome =
                    $j['nome'] ?? '';

                if (
                    $nome == '' ||
                    nomeIgual(
                        $nome,
                        $anjo
                    ) ||
                    !empty(
                        $j['status']['lider']
                    )
                ) {
                    continue;
                }

                $afinidade =
                    calcularRelacaoIA(
                        $jogadores,
                        $anjo,
                        $nome,
                        $meuNome
                    );

                $afinidades[$nome] =
                    $afinidade;
            }

            arsort($afinidades);

            $imunizado =
                array_key_first(
                    $afinidades
                );
        }

        if ($imunizado != null) {
            foreach ($jogadores as &$j) {
                $j['status']['imune'] =
                    false;

                if (
                    nomeIgual(
                        $j['nome'] ?? '',
                        $imunizado
                    )
                ) {
                    $j['status']['imune'] =
                        true;

                    if (
                        !isset(
                            $j['estatisticas']
                        )
                    ) {
                        $j['estatisticas'] = [];
                    }

                    $j['estatisticas']['imune'] =
                        (
                            $j['estatisticas']['imune']
                            ?? 0
                        ) + 1;
                }
            }

            unset($j);

            /*
             * O imunizado lembra positivamente do Anjo.
             */
            if (
                function_exists(
                    'registrarMemoriaSocialNPC'
                )
            ) {
                registrarMemoriaSocialNPC(
                    $imunizado,
                    $anjo,
                    'me_imunizou',
                    1,
                    "$anjo imunizou $imunizado.",
                    'imunidade_anjo|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|' .
                    $anjo .
                    '|' .
                    $imunizado
                );
            }

            garantirMeuJogadorNaLista(
                $jogadores
            );

            $_SESSION['jogadores'] =
                $jogadores;

            $_SESSION['imune'] =
                $imunizado;

            $_SESSION[
                'imunizacao_anjo_feita'
            ] = true;

            $_SESSION['evento_extra'][] =
                "🛡️ O Anjo $anjo imunizou $imunizado antes da formação do paredão.";

            $_SESSION['fase_semana'] =
                'paredao';

            header("Location: jogo.php");
            exit;
        }
    }
}
