<?php

/* =========================================================
   🚨 LÓGICA DO PAREDÃO
   ========================================================= */


/* =========================================================
   📊 DEFINIR TAMANHO DO PAREDÃO
   ========================================================= */

function definirTamanhoParedao($jogadores)
{
    $total = count($jogadores);
    if (function_exists('dsTipo') && dsTipo('paredao_especial') && $total >= 8) return 4;

    if ($total <= 4) {
        return 3;
    }

    if ($total <= 6) {
        return 2;
    }

    $sorteio = rand(1, 100);

    if ($sorteio <= 70) {
        return 3;
    }

    if ($sorteio <= 95) {
        return 4;
    }

    return 5;
}


/* =========================================================
   🔄 DEFINIR FASE DEPOIS DA FORMAÇÃO DO PAREDÃO
   ========================================================= */

function definirFaseDepoisDaFormacaoDoParedao(&$jogadores)
{
    if (function_exists('dsVerificarContragolpe') && dsVerificarContragolpe($jogadores)) return;

    $meuNome =
        $_SESSION['meu_nome'] ?? '';

    $paredaoAtual =
        $_SESSION['paredao'] ?? [];



    /* =====================================================
       ⚔️ 1. CONTRA-GOLPE DO BIG FONE — JOGADOR
       ===================================================== */

    $donoContraBigFone =
        $_SESSION[
            'bigfone_contragolpe_pendente'
        ] ?? '';

    if (
        $donoContraBigFone != '' &&
        nomeIgual(
            $donoContraBigFone,
            $meuNome
        ) &&
        in_array(
            $meuNome,
            $paredaoAtual
        ) &&
        !isset(
            $_SESSION[
                'bigfone_contragolpe_usado'
            ]
        ) &&
        !empty(
            candidatosContraGolpeBigFone(
                $jogadores,
                $paredaoAtual
            )
        )
    ) {

        $_SESSION[
            'poder_pos_paredao_origem'
        ] = 'bigfone';

        /*
         * Mantemos o nome da fase para não quebrar
         * o componente visual já existente.
         */
        $_SESSION['fase_semana'] =
            'contra_golpe_curinga';

        return;
    }


    /* =====================================================
       🔁 3. TROCA DO BIG FONE — JOGADOR
       ===================================================== */

    $donoTrocaBigFone =
        $_SESSION[
            'bigfone_troca_emparedado_pendente'
        ] ?? '';

    if (
        $donoTrocaBigFone != '' &&
        nomeIgual(
            $donoTrocaBigFone,
            $meuNome
        ) &&
        !isset(
            $_SESSION[
                'bigfone_troca_usada'
            ]
        ) &&
        count(
            array_filter(
                $paredaoAtual,
                function ($nome) {
                    return !nomeIgual(
                        $nome,
                        $_SESSION[
                            'indicacao_lider'
                        ] ?? ''
                    );
                }
            )
        ) > 0 &&
        !empty(
            candidatosTrocaBigFoneEntrada(
                $jogadores,
                $paredaoAtual
            )
        )
    ) {

        $_SESSION[
            'poder_pos_paredao_origem'
        ] = 'bigfone';

        /*
         * Mantemos o nome da fase para preservar
         * o HTML atual.
         */
        $_SESSION['fase_semana'] =
            'troca_curinga';

        return;
    }


    /*
     * Nenhum poder pós-paredão aguardando escolha.
     */
    unset(
        $_SESSION[
            'poder_pos_paredao_origem'
        ]
    );


    /* =====================================================
       🚗 5. BATE-VOLTA
       ===================================================== */

    if (
        iniciarBateVoltaAposParedao(
            $jogadores
        )
    ) {
        return;
    }


    /* =====================================================
       🔥 6. SEGUE PARA DISCÓRDIA
       ===================================================== */

    $_SESSION['fase_semana'] = bbbFaseAposParedao($jogadores);
}


/* =========================================================
   🛡️ VERIFICAR SE PARTICIPANTE ESTÁ IMUNE
   ========================================================= */

function estaImune($jogadores, $nome)
{
    if ($nome == '') {
        return false;
    }
    foreach (['lideres_dupla'=>'lider_dupla_semana','anjos_dupla'=>'anjo_dupla_semana'] as $lista=>$semana) {
        if ((int)($_SESSION[$semana]??0)===(int)($_SESSION['rodada']??1)) {
            foreach ((array)($_SESSION[$lista]??[]) as $integrante) {
                if (nomeIgual((string)$integrante,(string)$nome) && ($lista==='lideres_dupla'||!empty($_SESSION['anjo_autoimune'])))return true;
            }
        }
    }
    if (function_exists('dsImune') && dsImune((string)$nome)) return true;


    $fontesImunidade = [

        $_SESSION['imune'] ?? '',
        ((int)(($_SESSION['ds3_imune']['rodada']??0)) === (int)($_SESSION['rodada']??1) ? ($_SESSION['ds3_imune']['nome']??'') : ''),

        $_SESSION['bigfone_imunidade_extra_alvo'] ?? '',

        $_SESSION['bigfone_imune'] ?? '',

        $_SESSION['bigfone_imunizado'] ?? '',

        $_SESSION['bigfone_imunidade'] ?? '',

        (
            !empty($_SESSION['anjo_autoimune'])
            ? ($_SESSION['anjo'] ?? '')
            : ''
        ),

        (
            ($_SESSION['bigfone_poder'] ?? '') == 'imunidade'
            ? ($_SESSION['bigfone_dono_poder'] ?? '')
            : ''
        )
    ];


    foreach (
        $fontesImunidade as $imuneSessao
    ) {

        if (
            $imuneSessao != '' &&
            nomeIgual(
                $imuneSessao,
                $nome
            )
        ) {

            return true;
        }
    }


    foreach ($jogadores as $j) {

        if (
            nomeIgual(
                ($j['nome'] ?? ''),
                $nome
            )
        ) {

            return
                !empty(
                    $j['status']['imune']
                );
        }
    }


    return false;
}


/* =========================================================
   🛡️ SINCRONIZAR TODAS AS IMUNIDADES
   ========================================================= */

function sincronizarImunidadesGlobais(&$jogadores)
{
    $imunes = [];


    $fontes = [

        ((int)(($_SESSION['ds3_imune']['rodada']??0)) === (int)($_SESSION['rodada']??1) ? ($_SESSION['ds3_imune']['nome']??'') : ''),
        $_SESSION['imune'] ?? '',

        $_SESSION['bigfone_imunidade_extra_alvo'] ?? '',

        $_SESSION['bigfone_imune'] ?? '',

        $_SESSION['bigfone_imunizado'] ?? '',

        $_SESSION['bigfone_imunidade'] ?? '',

        (
            !empty($_SESSION['anjo_autoimune'])
            ? ($_SESSION['anjo'] ?? '')
            : ''
        ),

        (
            ($_SESSION['bigfone_poder'] ?? '') == 'imunidade'
            ? ($_SESSION['bigfone_dono_poder'] ?? '')
            : ''
        )
    ];


    if ((int)($_SESSION['lider_dupla_semana']??0)===(int)($_SESSION['rodada']??1))
        $fontes=array_merge($fontes,(array)($_SESSION['lideres_dupla']??[]));
    if (!empty($_SESSION['anjo_autoimune']) && (int)($_SESSION['anjo_dupla_semana']??0)===(int)($_SESSION['rodada']??1))
        $fontes=array_merge($fontes,(array)($_SESSION['anjos_dupla']??[]));
    foreach ($fontes as $nome) {

        if (
            trim((string)$nome) != ''
        ) {

            $imunes[] =
                trim((string)$nome);
        }
    }


    foreach ($jogadores as $j) {

        if (
            !empty($j['status']['imune']) &&
            !empty($j['nome'])
        ) {

            $imunes[] =
                $j['nome'];
        }
    }


    $imunes =
        array_values(
            array_unique($imunes)
        );


    if (!empty($imunes)) {

        foreach ($jogadores as &$j) {

            foreach (
                $imunes as $nomeImune
            ) {

                if (
                    nomeIgual(
                        ($j['nome'] ?? ''),
                        $nomeImune
                    )
                ) {

                    $j['status']['imune'] =
                        true;

                    $_SESSION['imune'] =
                        $j['nome'];
                }
            }
        }

        unset($j);
    }
}


/* =========================================================
   🔒 BLINDAR PAREDÃO CONTRA IMUNES
   ========================================================= */

function blindarParedaoContraImunes(&$jogadores)
{
    if (
        !isset($_SESSION['paredao']) ||
        !is_array($_SESSION['paredao'])
    ) {
        return;
    }


    $removidos = [];


    $_SESSION['paredao'] =
        array_values(
            array_filter(
                $_SESSION['paredao'],
                function ($nome) use (
                    $jogadores,
                    &$removidos
                ) {

                    if (
                        estaImune(
                            $jogadores,
                            $nome
                        )
                    ) {

                        $removidos[] =
                            $nome;

                        return false;
                    }


                    return true;
                }
            )
        );


    foreach (
        array_unique($removidos)
        as $nomeRemovido
    ) {

        $_SESSION['evento_extra'][] =
            "🛡️ $nomeRemovido estava imune e foi removido automaticamente do paredão.";
    }
}


/* =========================================================
   🚨 ADICIONAR AO PAREDÃO COM SEGURANÇA
   ========================================================= */

function adicionarAoParedaoSeValido(
    &$paredao,
    $jogadores,
    $nome,
    $motivo = ''
) {

    if ($nome == '') {
        return false;
    }


    if (
        estaImune(
            $jogadores,
            $nome
        )
    ) {

        if ($motivo != '') {

            $_SESSION['evento_extra'][] =
                "🛡️ $nome não pôde ir ao paredão por $motivo, pois estava imune.";
        }

        return false;
    }


    if (
        !in_array(
            $nome,
            $paredao
        )
    ) {

        $paredao[] =
            $nome;

        return true;
    }


    return false;
}


/* =========================================================
   🧠 ESCOLHER ALVO INTELIGENTE
   Líder, NPCs e votação da casa
   ========================================================= */

function escolherAlvoInteligente(
    $jogadores,
    $votante,
    $bloqueados = [],
    $contexto = 'voto'
) {

    /*
     * A nova inteligência central assume a decisão
     * quando estiver carregada. Mantemos toda a lógica
     * antiga abaixo como fallback de compatibilidade.
     */
    if (
        function_exists(
            'escolherAlvoNPCInteligente'
        )
    ) {
        return
            escolherAlvoNPCInteligente(
                $jogadores,
                $votante,
                $contexto,
                $bloqueados
            );
    }


    $opcoes = [];

    $meuNome =
        $_SESSION['meu_nome'] ?? '';


    foreach ($jogadores as $j) {

        $alvo =
            $j['nome'] ?? '';


        if (
            $alvo == '' ||
            $alvo == $votante
        ) {
            continue;
        }


        if (
            in_array(
                $alvo,
                $bloqueados
            )
        ) {
            continue;
        }


        if (
            !empty(
                $j['status']['lider']
            )
        ) {
            continue;
        }


        if (
            estaImune(
                $jogadores,
                $j['nome'] ?? ''
            )
        ) {
            continue;
        }


        $relacao =
            calcularRelacaoIA(
                $jogadores,
                $votante,
                $alvo,
                $meuNome
            );


        $popularidade =
            $j['popularidade'] ?? 50;


        /*
         * Quanto pior a relação,
         * maior a chance de voto.
         */
        $peso =
            50 - $relacao;


        /*
         * Jogador rejeitado vira
         * alvo um pouco mais fácil.
         */
        $peso +=
            (50 - $popularidade)
            * 0.4;


        /* Rivalidade */

        if (
            saoRivais(
                $jogadores,
                $votante,
                $alvo,
                $meuNome
            )
        ) {

            $peso += 35;
        }


        /* Aliança social */

        if (
            saoAliados(
                $jogadores,
                $votante,
                $alvo,
                $meuNome
            )
        ) {

            $peso -= 45;
        }


        /* Aliança oficial */

        if (
            mesmaAliancaNomes(
                $jogadores,
                $votante,
                $alvo
            )
        ) {

            $peso -= 80;
        }


        /* =========================
           🤝 ALVO DO GRUPO
           ========================= */

        $aliancaVotante =
            null;


        foreach (
            $jogadores
            as $jBuscaAlianca
        ) {

            if (
                ($jBuscaAlianca['nome'] ?? '')
                == $votante
            ) {

                $aliancaVotante =
                    $jBuscaAlianca['alianca']
                    ?? null;

                break;
            }
        }


        if (!empty($aliancaVotante)) {

            $bloqueadosGrupo =
                array_values(
                    array_unique(
                        array_merge(
                            $bloqueados,
                            [$votante]
                        )
                    )
                );


            $alvoDoGrupo =
                alvoCombinadoDaAlianca(
                    $jogadores,
                    $aliancaVotante,
                    $bloqueadosGrupo
                );


            if (
                $alvoDoGrupo == $alvo
            ) {

                $peso += 45;
            }
        }


        /* Monstro vira alvo mais fácil */

        if (
            !empty(
                $j['status']['monstro']
            )
        ) {

            $peso += 15;
        }


        /* Xepa também pesa um pouco */

        if (
            !empty(
                $j['status']['xepa']
            )
        ) {

            $peso += 5;
        }


        $opcoes[$alvo] =
            $peso +
            rand(0, 10);
    }


    arsort($opcoes);


    if (empty($opcoes)) {
        return null;
    }


    return
        array_key_first($opcoes);
}