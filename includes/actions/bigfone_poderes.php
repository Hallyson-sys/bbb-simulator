<?php
/* Big Fone: escolhas do poder e poderes pós-paredão.
 * As chaves *_curinga de formulários antigos continuam aceitas somente
 * para compatibilidade com saves; não existe mais sorteio de Curinga.
 */

if (isset($_POST['usar_bigfone_imunidade_extra']) &&
    ($_SESSION['fase_semana'] ?? '') === 'bigfone_imunidade_extra' &&
    !empty($_SESSION['bigfone_imunidade_extra_pendente']) &&
    nomeIgual($_SESSION['bigfone_dono_poder'] ?? '', $meuNome)) {
    $alvo = (string)($_POST['alvo_imunidade_bigfone'] ?? '');
    $validos = candidatosImunidadeExtraBigFone($jogadores);
    if ($alvo === '' && !$validos) {
        $_SESSION['evento_extra'][] = '🛡️ Não havia participantes elegíveis para a Imunidade Extra.';
        unset($_SESSION['bigfone_imunidade_extra_pendente']);
    } elseif (in_array($alvo, $validos, true)) {
        concederImunidadeExtraBigFone($jogadores, $alvo, $meuNome);
        unset($_SESSION['bigfone_imunidade_extra_pendente']);
    } else {
        $_SESSION['evento_extra'][] = '⚠️ Escolha um participante elegível para a Imunidade Extra.';
        header('Location: jogo.php'); exit;
    }
    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['fase_semana'] = 'interacoes_2';
    $_SESSION['acoes_restantes'] = 3;
    header('Location: jogo.php'); exit;
}

if (isset($_POST['contra_golpe_curinga']) && ($_SESSION['fase_semana'] ?? '') === 'contra_golpe_curinga') {

    $origem =
        $_SESSION[
            'poder_pos_paredao_origem'
        ] ?? 'bigfone';

    $alvo =
        $_POST[
            'alvo_contra_golpe'
        ] ?? '';


    /* =========================
       ☎️ BIG FONE
       ========================= */

    if ($origem === 'bigfone') {

        $dono =
            $_SESSION[
                'bigfone_contragolpe_pendente'
            ] ?? '';

        if (
            $dono != '' &&
            nomeIgual(
                $dono,
                $meuNome
            ) &&
            $alvo != ''
        ) {

            if (
                estaImune(
                    $jogadores,
                    $alvo
                )
            ) {

                $_SESSION['evento_extra'][] =
                    "🛡️ $alvo está imune e não pode ser puxado pelo Contra-Golpe do Big Fone.";

            } else {

                $adicionado =
                    adicionarAoParedaoSeValido(
                        $_SESSION['paredao'],
                        $jogadores,
                        $alvo,
                        "contra-golpe do Big Fone"
                    );

                if ($adicionado) {

                    $_SESSION[
                        'bigfone_contragolpe_usado'
                    ] = true;

                    unset(
                        $_SESSION[
                            'bigfone_contragolpe_pendente'
                        ]
                    );

                    $_SESSION['evento_extra'][] =
                        "⚔️ Pelo Contra-Golpe do Big Fone, $meuNome puxou <b>$alvo</b> para o paredão.";
                }
            }
        }
    }


    unset(
        $_SESSION[
            'poder_pos_paredao_origem'
        ]
    );


    definirFaseDepoisDaFormacaoDoParedao(
        $jogadores
    );


    header("Location: jogo.php");
    exit;
}


/* =========================================================
   🔁 TROCAR EMPAREDADO PÓS-PAREDÃO
   ========================================================= */

if (isset($_POST['trocar_emparedado_curinga']) && ($_SESSION['fase_semana'] ?? '') === 'troca_curinga') {

    $origem =
        $_SESSION[
            'poder_pos_paredao_origem'
        ] ?? 'bigfone';

    $sair =
        $_POST[
            'sair_paredao_curinga'
        ] ?? '';

    $entrar =
        $_POST[
            'entrar_paredao_curinga'
        ] ?? '';

    $indicacaoLider =
        $_SESSION[
            'indicacao_lider'
        ] ?? '';


    /* =========================
       ☎️ BIG FONE
       ========================= */

    if ($origem === 'bigfone') {

        $dono =
            $_SESSION[
                'bigfone_troca_emparedado_pendente'
            ] ?? '';

        if (
            $dono != '' &&
            nomeIgual(
                $dono,
                $meuNome
            )
        ) {

            if (
                nomeIgual(
                    $sair,
                    $indicacaoLider
                )
            ) {

                $_SESSION['evento_extra'][] =
                    "⚠️ O poder do Big Fone não pode tirar do paredão a pessoa indicada pelo líder.";

            } elseif (
                estaImune(
                    $jogadores,
                    $entrar
                )
            ) {

                $_SESSION['evento_extra'][] =
                    "🛡️ $entrar está imune e não pode entrar no paredão pela troca do Big Fone.";

            } elseif (
                $sair != '' &&
                $entrar != '' &&
                isset(
                    $_SESSION['paredao']
                )
            ) {

                foreach (
                    $_SESSION['paredao']
                    as $i => $nome
                ) {

                    if (
                        nomeIgual(
                            $nome,
                            $sair
                        )
                    ) {

                        $_SESSION[
                            'paredao'
                        ][$i] = $entrar;

                        break;
                    }
                }

                $_SESSION['paredao'] =
                    array_values(
                        array_unique(
                            $_SESSION[
                                'paredao'
                            ]
                        )
                    );

                blindarParedaoContraImunes(
                    $jogadores
                );

                $_SESSION[
                    'bigfone_troca_usada'
                ] = true;

                unset(
                    $_SESSION[
                        'bigfone_troca_emparedado_pendente'
                    ]
                );

                $_SESSION['evento_extra'][] =
                    "🔁 Pelo poder do Big Fone, $meuNome tirou <b>$sair</b> do paredão e colocou <b>$entrar</b>. A indicação do líder foi preservada.";
            }
        }
    }


    unset(
        $_SESSION[
            'poder_pos_paredao_origem'
        ]
    );


    definirFaseDepoisDaFormacaoDoParedao(
        $jogadores
    );


    header("Location: jogo.php");
    exit;
}


/* =========================================================
   🚫 BIG FONE — ANULAR VOTO
   ========================================================= */

if (isset($_POST['usar_bigfone_anular_voto'])) {

    $alvo =
        $_POST[
            'alvo_anular_voto_bigfone'
        ] ?? '';

    if (
        ($_SESSION[
            'bigfone_anular_voto_pendente'
        ] ?? false) &&
        nomeIgual(
            $_SESSION[
                'bigfone_dono_poder'
            ] ?? '',
            $meuNome
        ) &&
        $alvo != '' &&
        !nomeIgual(
            $alvo,
            $meuNome
        )
    ) {

        /*
         * IMPORTANTE:
         * Agora o Big Fone possui variável própria.
         */
        $_SESSION[
            'bigfone_anular_voto_de'
        ] = $alvo;

        unset(
            $_SESSION[
                'bigfone_anular_voto_pendente'
            ]
        );

        $_SESSION['evento_extra'][] =
            "🚫 Pelo poder do Big Fone, $meuNome anulou o voto de <b>$alvo</b>.";

    } else {

        $_SESSION['evento_extra'][] =
            "⚠️ Escolha válida obrigatória para anular o voto pelo Big Fone.";
    }


    header("Location: jogo.php");
    exit;
}


/* =========================================================
   🚫 NÃO USAR ANULAR VOTO DO BIG FONE
   ========================================================= */

if (isset($_POST['pular_bigfone_anular_voto'])) {

    unset(
        $_SESSION[
            'bigfone_anular_voto_pendente'
        ]
    );

    $_SESSION['evento_extra'][] =
        "🚫 $meuNome decidiu não usar o poder de anular voto do Big Fone.";


    header("Location: jogo.php");
    exit;
}


/* =========================================================
   👁️ BIG FONE — ESPIAR VOTO
   ========================================================= */

if (isset($_POST['usar_bigfone_espiar_voto'])) {

    $alvo =
        $_POST[
            'alvo_espiar_voto_bigfone'
        ] ?? '';

    if (
        ($_SESSION[
            'bigfone_espiar_voto_pendente'
        ] ?? false) &&
        nomeIgual(
            $_SESSION[
                'bigfone_dono_poder'
            ] ?? '',
            $meuNome
        ) &&
        $alvo != '' &&
        !nomeIgual(
            $alvo,
            $meuNome
        )
    ) {

        /*
         * IMPORTANTE:
         * Agora o Big Fone possui variável própria.
         */
        $_SESSION[
            'bigfone_espiar_voto_de'
        ] = $alvo;

        unset(
            $_SESSION[
                'bigfone_espiar_voto_pendente'
            ]
        );

        $_SESSION['evento_extra'][] =
            "👁️ Pelo poder do Big Fone, $meuNome escolheu espiar o voto de <b>$alvo</b>.";

    } else {

        $_SESSION['evento_extra'][] =
            "⚠️ Escolha válida obrigatória para espiar voto pelo Big Fone.";
    }


    header("Location: jogo.php");
    exit;
}


/* =========================================================
   👁️ NÃO USAR ESPIAR VOTO DO BIG FONE
   ========================================================= */

if (isset($_POST['pular_bigfone_espiar_voto'])) {

    unset(
        $_SESSION[
            'bigfone_espiar_voto_pendente'
        ]
    );

    $_SESSION['evento_extra'][] =
        "👁️ $meuNome decidiu não usar o poder de espiar voto do Big Fone.";


    header("Location: jogo.php");
    exit;
}
