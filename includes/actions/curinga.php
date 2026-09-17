<?php

/** @var array $jogadores */
/** @var string $meuNome */


/* =========================================================
   🎁 PROCESSAR PODER CURINGA
   ========================================================= */

if (isset($_POST['usar_poder_curinga'])) {

    $poder =
        obterPoderCuringaAtual();

    if (!$poder) {

        $_SESSION['evento_extra'][] =
            "⚠️ Não existe Poder Curinga ativo nesta rodada.";

        header("Location: jogo.php");
        exit;
    }

    $dono =
        $poder['dono'] ?? '';

    $tipo =
        $poder['tipo'] ?? '';

    if (!nomeIgual($dono, $meuNome)) {

        $_SESSION['evento_extra'][] =
            "⚠️ Apenas o dono do Poder Curinga pode usar esse poder.";

        header("Location: jogo.php");
        exit;
    }

    $eventoCuringa = "";


    /* =========================
       🗳️ VOTO DUPLO
       ========================= */

    if ($tipo == 'voto_duplo') {

        $eventoCuringa =
            ativarVotoDuploCuringa(
                $dono
            );
    }


    /* =========================
       🛡️ IMUNIDADE EXTRA
       ========================= */

    if ($tipo == 'imunidade_extra') {

        $alvo =
            $_POST['alvo_curinga']
            ?? $dono;

        $eventoCuringa =
            aplicarImunidadeCuringa(
                $jogadores,
                $alvo,
                $dono
            );
    }


    /* =========================
       🚫 ANULAR VOTO
       ========================= */

    if ($tipo == 'anular_voto') {

        $alvo =
            $_POST['alvo_curinga']
            ?? '';

        if (
            $alvo == '' ||
            nomeIgual(
                $alvo,
                $dono
            )
        ) {

            $eventoCuringa =
                "⚠️ Escolha outro participante para ter o voto anulado.";

        } else {

            $_SESSION[
                'curinga_anular_voto_de'
            ] = $alvo;

            marcarPoderCuringaUsado();

            $eventoCuringa =
                "🚫 $dono usou o Poder Curinga para anular o voto de <b>$alvo</b>.";
        }
    }


    /* =========================
       👁️ ESPIÃO
       ========================= */

    if ($tipo == 'espiao') {

        $alvo =
            $_POST['alvo_curinga']
            ?? '';

        if (
            $alvo == '' ||
            nomeIgual(
                $alvo,
                $dono
            )
        ) {

            $eventoCuringa =
                "⚠️ Escolha outro participante para espionar o voto.";

        } else {

            $_SESSION[
                'curinga_espiar_voto_de'
            ] = $alvo;

            marcarPoderCuringaUsado();

            $eventoCuringa =
                "👁️ $dono usou o Poder Curinga para espionar o voto de <b>$alvo</b>.";
        }
    }


    /* =========================
       ⚡ CONTRA-GOLPE
       ========================= */

    if ($tipo == 'contra_golpe') {

        marcarPoderCuringaUsado();

        $eventoCuringa =
            "⚡ $dono ativou o Contra-Golpe do Poder Curinga. Se cair no paredão, poderá puxar alguém junto.";
    }


    /* =========================
       🔁 TROCAR EMPAREDADO
       ========================= */

    if ($tipo == 'trocar_emparedado') {

        marcarPoderCuringaUsado();

        $eventoCuringa =
            "🔁 $dono ativou a Troca do Poder Curinga. Depois da formação do paredão, poderá trocar um emparedado, exceto a indicação do líder.";
    }


    if ($eventoCuringa != "") {

        $_SESSION['evento_extra'][] =
            $eventoCuringa;
    }


    garantirMeuJogadorNaLista(
        $jogadores
    );

    $_SESSION['jogadores'] =
        $jogadores;

    $_SESSION['fase_semana'] =
        'interacoes_2';

    $_SESSION['acoes_restantes'] =
        3;


    header("Location: jogo.php");
    exit;
}


/* =========================================================
   ⏭️ NPC USA O CURINGA
   ========================================================= */

if (isset($_POST['pular_poder_curinga'])) {

    $eventoNPC =
        usarPoderCuringaAutomaticoNPC(
            $jogadores
        );

    if ($eventoNPC != "") {

        $_SESSION['evento_extra'][] =
            $eventoNPC;
    }


    garantirMeuJogadorNaLista(
        $jogadores
    );

    $_SESSION['jogadores'] =
        $jogadores;

    $_SESSION['fase_semana'] =
        'interacoes_2';

    $_SESSION['acoes_restantes'] =
        3;


    header("Location: jogo.php");
    exit;
}


/* =========================================================
   ⚡ CONTRA-GOLPE PÓS-PAREDÃO
   A mesma tela pode servir para:
   - Poder Curinga
   - Big Fone

   A origem fica em:
   $_SESSION['poder_pos_paredao_origem']
   ========================================================= */

if (isset($_POST['contra_golpe_curinga'])) {

    $origem =
        $_SESSION[
            'poder_pos_paredao_origem'
        ] ?? 'curinga';

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


    /* =========================
       🎁 PODER CURINGA
       ========================= */

    if ($origem === 'curinga') {

        $poder =
            obterPoderCuringaAtual();

        if (
            $poder &&
            ($poder['tipo'] ?? '')
                == 'contra_golpe' &&
            nomeIgual(
                $poder['dono'] ?? '',
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
                    "🛡️ $alvo está imune e não pode ser puxado pelo Contra-Golpe do Poder Curinga.";

            } else {

                $adicionado =
                    adicionarAoParedaoSeValido(
                        $_SESSION['paredao'],
                        $jogadores,
                        $alvo,
                        "contra-golpe do Poder Curinga"
                    );

                if ($adicionado) {

                    $_SESSION[
                        'curinga_contra_golpe_usado'
                    ] = true;

                    $_SESSION['evento_extra'][] =
                        "⚡ Pelo Contra-Golpe do Poder Curinga, $meuNome puxou <b>$alvo</b> para o paredão.";
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

if (isset($_POST['trocar_emparedado_curinga'])) {

    $origem =
        $_SESSION[
            'poder_pos_paredao_origem'
        ] ?? 'curinga';

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


    /* =========================
       🎁 PODER CURINGA
       ========================= */

    if ($origem === 'curinga') {

        $poder =
            obterPoderCuringaAtual();

        if (
            $poder &&
            ($poder['tipo'] ?? '')
                == 'trocar_emparedado' &&
            nomeIgual(
                $poder['dono'] ?? '',
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
                    "⚠️ O Poder Curinga não pode tirar do paredão a pessoa indicada pelo líder.";

            } elseif (
                estaImune(
                    $jogadores,
                    $entrar
                )
            ) {

                $_SESSION['evento_extra'][] =
                    "🛡️ $entrar está imune e não pode entrar no paredão pela troca do Poder Curinga.";

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
                    'curinga_troca_usada'
                ] = true;

                $_SESSION['evento_extra'][] =
                    "🔁 Pelo Poder Curinga, $meuNome tirou <b>$sair</b> do paredão e colocou <b>$entrar</b>. A indicação do líder foi preservada.";
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
