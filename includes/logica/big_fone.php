<?php

/* =========================================================
   ☎️ LÓGICA DO BIG FONE
   Sistema totalmente separado do Poder Curinga.
   ========================================================= */

require_once __DIR__ . '/utilitarios.php';

/*
 * O Big Fone também roda em uma página própria.
 * Por isso carrega aqui as dependências necessárias
 * para as decisões inteligentes dos NPCs.
 */
require_once __DIR__ . '/relacoes.php';
require_once __DIR__ . '/romance.php';
require_once __DIR__ . '/aliancas.php';
require_once __DIR__ . '/inteligencia_npc.php';


/* =========================================================
   📢 GARANTIR FEED DE EVENTOS
   ========================================================= */

function garantirEventosBigFone()
{
    if (
        !isset($_SESSION['evento_extra']) ||
        !is_array($_SESSION['evento_extra'])
    ) {
        $_SESSION['evento_extra'] = [];
    }
}


/* =========================================================
   🧹 LIMPAR ESTADO EXCLUSIVO DO BIG FONE
   Executado quando começa uma nova rodada.
   ========================================================= */

function limparEstadoBigFoneNovaRodada()
{
    $chaves = [
        'bigfone_tocou',
        'bigfone_feito',
        'bigfone_aconteceu',
        'bigfone_aconteceu_rodada',
        'bigfone_atendente',

        'bigfone_poder',
        'bigfone_dono_poder',
        'bigfone_imune',
        'bigfone_imunizado',
        'bigfone_imunidade',

        'bigfone_indicacao_pendente',
        'indicacao_bigfone',

        'bigfone_voto_duplo_ativo',

        'bigfone_anular_voto_pendente',
        'bigfone_anular_voto_de',

        'bigfone_espiar_voto_pendente',
        'bigfone_espiar_voto_de',

        'bigfone_contragolpe_pendente',
        'bigfone_contragolpe_usado',

        'bigfone_troca_emparedado_pendente',
        'bigfone_troca_usada'
    ];

    foreach ($chaves as $chave) {
        unset($_SESSION[$chave]);
    }
}


/* =========================================================
   ☎️ PREPARAR BIG FONE DA RODADA

   Retornos possíveis:
   - feito      → já foi decidido nesta rodada
   - nao_tocou  → sorteio decidiu que não tocou
   - tocou      → deve mostrar a tela do telefone
   ========================================================= */

function prepararBigFoneDaRodada()
{
    garantirEventosBigFone();

    $rodadaAtual =
        (int)($_SESSION['rodada'] ?? 1);

    /*
     * Ao detectar uma nova rodada, limpa SOMENTE estados
     * pertencentes ao Big Fone. O Poder Curinga permanece
     * completamente independente.
     */
    if (
        !isset($_SESSION['bigfone_tocou_rodada']) ||
        (int)$_SESSION['bigfone_tocou_rodada'] !== $rodadaAtual
    ) {
        limparEstadoBigFoneNovaRodada();

        $_SESSION['bigfone_tocou_rodada'] =
            $rodadaAtual;
    }

    if (isset($_SESSION['bigfone_feito'])) {
        return 'feito';
    }

    /* Chance atual: 60%. */
    if (!isset($_SESSION['bigfone_tocou'])) {
        $_SESSION['bigfone_tocou'] =
            rand(1, 100) <= 60;
    }

    if ($_SESSION['bigfone_tocou'] == false) {

        $_SESSION['evento_extra'][] =
            "☎️ O Big Fone não tocou nesta semana.";

        $_SESSION['bigfone_feito'] = true;
        $_SESSION['bigfone_aconteceu'] = false;
        $_SESSION['bigfone_aconteceu_rodada'] =
            $rodadaAtual;

        return 'nao_tocou';
    }

    return 'tocou';
}


/* =========================================================
   🔤 COMPARAÇÃO DE NOMES
   ========================================================= */

function nomeIgualBigfone($a, $b)
{
    return nomeIgual($a, $b);
}


/* =========================================================
   🎯 ESCOLHER PARTICIPANTE VÁLIDO
   Ignora bloqueados, Líder e participantes imunes.
   ========================================================= */

function escolherParticipanteBigFone(
    $jogadores,
    $bloqueados = []
) {
    $opcoes = [];

    foreach ($jogadores as $j) {

        $nome = $j['nome'] ?? '';

        if ($nome == '') {
            continue;
        }

        $bloqueado = false;

        foreach ($bloqueados as $nomeBloqueado) {

            if (
                nomeIgualBigfone(
                    $nome,
                    $nomeBloqueado
                )
            ) {
                $bloqueado = true;
                break;
            }
        }

        if ($bloqueado) {
            continue;
        }

        if (!empty($j['status']['lider'])) {
            continue;
        }

        if (!empty($j['status']['imune'])) {
            continue;
        }

        $opcoes[] = $nome;
    }

    if (empty($opcoes)) {
        return null;
    }

    return $opcoes[array_rand($opcoes)];
}


/* =========================================================
   🎁 REGISTRAR PODER BASE DO BIG FONE
   ========================================================= */

function registrarPoderBigFoneBase(
    $atendente,
    $poder
) {
    $_SESSION['bigfone_poder'] =
        $poder;

    $_SESSION['bigfone_dono_poder'] =
        $atendente;
}


/* =========================================================
   🎁 APLICAR PODER DO BIG FONE

   IMPORTANTE:
   Nenhum poder daqui utiliza variáveis curinga_*.
   ========================================================= */

function aplicarPoderBigFone(
    &$jogadores,
    $atendente
) {
    $poderes = [
        'imunidade',
        'indicacao',
        'voto_duplo',
        'anular_voto',
        'espiar_voto',
        'contragolpe',
        'trocar_emparedado'
    ];

    $poder =
        $poderes[array_rand($poderes)];

    registrarPoderBigFoneBase(
        $atendente,
        $poder
    );

    $meuNome =
        $_SESSION['meu_nome'] ?? '';

    $texto = '';


    /* =========================
       🛡️ IMUNIDADE
       ========================= */

    if ($poder == 'imunidade') {

        foreach ($jogadores as &$j) {

            if (
                nomeIgualBigfone(
                    $j['nome'] ?? '',
                    $atendente
                )
            ) {

                if (
                    !isset($j['status']) ||
                    !is_array($j['status'])
                ) {
                    $j['status'] = [];
                }

                $j['status']['imune'] = true;

                if (
                    !isset($j['estatisticas']) ||
                    !is_array($j['estatisticas'])
                ) {
                    $j['estatisticas'] = [];
                }

                $j['estatisticas']['imune'] =
                    ($j['estatisticas']['imune'] ?? 0)
                    + 1;
            }
        }

        unset($j);

        $texto =
            "🛡️ $atendente ganhou imunidade pelo Big Fone.";
    }


    /* =========================
       🎯 INDICAÇÃO AO PAREDÃO
       ========================= */

    if ($poder == 'indicacao') {

        $_SESSION['bigfone_indicacao_pendente'] =
            true;

        $texto =
            "🎯 $atendente ganhou o poder de indicar alguém direto ao paredão.";
    }


    /* =========================
       🗳️ VOTO DUPLO
       ========================= */

    if ($poder == 'voto_duplo') {

        $_SESSION['bigfone_voto_duplo_ativo'] =
            $atendente;

        $texto =
            "🗳️ $atendente ganhou voto duplo na próxima votação da casa pelo Big Fone.";
    }


    /* =========================
       🚫 ANULAR VOTO
       ========================= */

    if ($poder == 'anular_voto') {

        if (
            nomeIgualBigfone(
                $atendente,
                $meuNome
            )
        ) {

            $_SESSION[
                'bigfone_anular_voto_pendente'
            ] = true;

            $texto =
                "🚫 $atendente ganhou o poder de anular o voto de um participante na próxima votação.";

        } else {

            $candidatos = [];

            foreach ($jogadores as $j) {
                $nomeCandidato =
                    $j['nome'] ?? '';

                if (
                    $nomeCandidato == '' ||
                    nomeIgualBigfone(
                        $nomeCandidato,
                        $atendente
                    ) ||
                    !empty($j['status']['lider'])
                ) {
                    continue;
                }

                $candidatos[] =
                    $nomeCandidato;
            }

            $alvo =
                escolherEntreCandidatosNPCInteligente(
                    $jogadores,
                    $atendente,
                    'anular_voto',
                    $candidatos
                );

            if ($alvo != null) {

                $_SESSION[
                    'bigfone_anular_voto_de'
                ] = $alvo;

                $texto =
                    "🚫 $atendente ganhou o poder de anular voto e escolheu anular o voto de $alvo.";

            } else {

                $texto =
                    "🚫 $atendente ganhou o poder de anular voto, mas não havia alvo válido.";
            }
        }
    }


    /* =========================
       👁️ ESPIAR VOTO
       ========================= */

    if ($poder == 'espiar_voto') {

        if (
            nomeIgualBigfone(
                $atendente,
                $meuNome
            )
        ) {

            $_SESSION[
                'bigfone_espiar_voto_pendente'
            ] = true;

            $texto =
                "👁️ $atendente ganhou o poder de espiar o voto de um participante.";

        } else {

            $candidatos = [];

            foreach ($jogadores as $j) {
                $nomeCandidato =
                    $j['nome'] ?? '';

                if (
                    $nomeCandidato == '' ||
                    nomeIgualBigfone(
                        $nomeCandidato,
                        $atendente
                    ) ||
                    !empty($j['status']['lider'])
                ) {
                    continue;
                }

                $candidatos[] =
                    $nomeCandidato;
            }

            $alvo =
                escolherEntreCandidatosNPCInteligente(
                    $jogadores,
                    $atendente,
                    'espiar_voto',
                    $candidatos
                );

            if ($alvo != null) {

                $_SESSION[
                    'bigfone_espiar_voto_de'
                ] = $alvo;

                $texto =
                    "👁️ $atendente ganhou o poder de espiar voto e escolheu observar o voto de $alvo.";

            } else {

                $texto =
                    "👁️ $atendente ganhou o poder de espiar voto, mas não havia alvo válido.";
            }
        }
    }


    /* =========================
       ⚔️ CONTRA-GOLPE
       ========================= */

    if ($poder == 'contragolpe') {

        $_SESSION[
            'bigfone_contragolpe_pendente'
        ] = $atendente;

        $texto =
            "⚔️ $atendente ganhou o Contra-Golpe pelo Big Fone. Se cair no paredão, poderá puxar alguém.";
    }


    /* =========================
       🔁 TROCAR EMPAREDADO
       ========================= */

    if ($poder == 'trocar_emparedado') {

        $_SESSION[
            'bigfone_troca_emparedado_pendente'
        ] = $atendente;

        $texto =
            "🔁 $atendente ganhou o poder de trocar um emparedado pelo Big Fone. A indicação do líder não pode ser trocada.";
    }


    return $texto;
}


/* =========================================================
   🗳️ VOTO DUPLO — BIG FONE
   ========================================================= */

function pesoVotoBigFone($votante)
{
    if (
        isset($_SESSION['bigfone_voto_duplo_ativo']) &&
        nomeIgualBigfone(
            $_SESSION['bigfone_voto_duplo_ativo'],
            $votante
        )
    ) {
        return 2;
    }

    return 1;
}


/* =========================================================
   🚫 ANULAR VOTO — BIG FONE
   ========================================================= */

function votoEstaAnuladoPeloBigFone(
    $votante
) {
    return (
        isset($_SESSION['bigfone_anular_voto_de']) &&
        nomeIgualBigfone(
            $_SESSION['bigfone_anular_voto_de'],
            $votante
        )
    );
}


/* =========================================================
   👁️ REVELAR VOTO ESPIADO — BIG FONE
   ========================================================= */

function aplicarEspiaoBigFoneNoResultado(
    $votosDetalhados
) {
    $alvo =
        $_SESSION['bigfone_espiar_voto_de'] ?? '';

    $dono =
        $_SESSION['bigfone_dono_poder'] ?? '';

    if (
        $alvo == '' ||
        $dono == ''
    ) {
        return;
    }

    foreach ($votosDetalhados as $votoInfo) {

        if (
            nomeIgualBigfone(
                $votoInfo['votante'] ?? '',
                $alvo
            )
        ) {

            $_SESSION['evento_extra'][] =
                "👁️ Big Fone revelou para $dono: $alvo votou em " .
                ($votoInfo['voto'] ?? '') .
                ".";

            return;
        }
    }
}


/* =========================================================
   ⚔️ CANDIDATOS AO CONTRA-GOLPE — BIG FONE
   ========================================================= */

function candidatosContraGolpeBigFone(
    $jogadores,
    $paredaoAtual
) {
    $bloqueados =
        $paredaoAtual;

    if (isset($_SESSION['lider'])) {
        $bloqueados[] =
            $_SESSION['lider'];
    }

    if (
        isset($_SESSION['indicacao_lider'])
    ) {
        $bloqueados[] =
            $_SESSION['indicacao_lider'];
    }

    if (
        isset($_SESSION['indicacao_bigfone'])
    ) {
        $bloqueados[] =
            $_SESSION['indicacao_bigfone'];
    }

    $candidatos = [];

    foreach ($jogadores as $j) {

        $nome =
            $j['nome'] ?? '';

        if ($nome == '') {
            continue;
        }

        if (in_array($nome, $bloqueados)) {
            continue;
        }

        if (!empty($j['status']['lider'])) {
            continue;
        }

        if (
            function_exists('estaImune') &&
            estaImune(
                $jogadores,
                $nome
            )
        ) {
            continue;
        }

        $candidatos[] =
            $nome;
    }

    return $candidatos;
}


/* =========================================================
   ⚔️ CONTRA-GOLPE AUTOMÁTICO DE NPC — BIG FONE
   ========================================================= */

function aplicarContraGolpeBigFoneNPC(
    &$jogadores,
    &$paredaoAtual
) {
    $dono =
        $_SESSION[
            'bigfone_contragolpe_pendente'
        ] ?? '';

    if ($dono == '') {
        return "";
    }

    if (
        nomeIgualBigfone(
            $dono,
            $_SESSION['meu_nome'] ?? ''
        )
    ) {
        return "";
    }

    if (
        !in_array(
            $dono,
            $paredaoAtual
        )
    ) {
        return "";
    }

    if (
        isset(
            $_SESSION[
                'bigfone_contragolpe_usado'
            ]
        )
    ) {
        return "";
    }

    $candidatos =
        candidatosContraGolpeBigFone(
            $jogadores,
            $paredaoAtual
        );

    if (empty($candidatos)) {
        return "";
    }

    $alvo =
        escolherEntreCandidatosNPCInteligente(
            $jogadores,
            $dono,
            'contra_golpe',
            $candidatos
        );

    if ($alvo == null) {
        return "";
    }

    $paredaoAtual[] =
        $alvo;

    registrarMemoriaSocialNPC(
        $alvo,
        $dono,
        'me_puxou_contragolpe',
        2,
        "$dono puxou $alvo para o Paredão pelo Contra-Golpe do Big Fone.",
        'bigfone_contragolpe|' .
        ($_SESSION['rodada'] ?? 1) .
        "|$dono|$alvo"
    );

    $_SESSION[
        'bigfone_contragolpe_usado'
    ] = true;

    unset(
        $_SESSION[
            'bigfone_contragolpe_pendente'
        ]
    );

    return
        "⚔️ Pelo Contra-Golpe do Big Fone, $dono puxou <b>$alvo</b> para o paredão.";
}


/* =========================================================
   🔁 CANDIDATOS PARA TROCA — BIG FONE
   ========================================================= */

function candidatosTrocaBigFoneEntrada(
    $jogadores,
    $paredaoAtual
) {
    $bloqueados =
        $paredaoAtual;

    if (isset($_SESSION['lider'])) {
        $bloqueados[] =
            $_SESSION['lider'];
    }

    $candidatos = [];

    foreach ($jogadores as $j) {

        $nome =
            $j['nome'] ?? '';

        if ($nome == '') {
            continue;
        }

        if (in_array($nome, $bloqueados)) {
            continue;
        }

        if (!empty($j['status']['lider'])) {
            continue;
        }

        if (
            function_exists('estaImune') &&
            estaImune(
                $jogadores,
                $nome
            )
        ) {
            continue;
        }

        $candidatos[] =
            $nome;
    }

    return $candidatos;
}


/* =========================================================
   🔁 TROCA AUTOMÁTICA DE NPC — BIG FONE
   ========================================================= */

function aplicarTrocaEmparedadoBigFoneNPC(
    &$jogadores,
    &$paredaoAtual
) {
    $dono =
        $_SESSION[
            'bigfone_troca_emparedado_pendente'
        ] ?? '';

    if ($dono == '') {
        return "";
    }

    if (
        nomeIgualBigfone(
            $dono,
            $_SESSION['meu_nome'] ?? ''
        )
    ) {
        return "";
    }

    if (
        isset(
            $_SESSION[
                'bigfone_troca_usada'
            ]
        )
    ) {
        return "";
    }

    $indicacaoLider =
        $_SESSION['indicacao_lider'] ?? '';

    $saidas =
        array_values(
            array_filter(
                $paredaoAtual,
                function ($nome) use (
                    $indicacaoLider
                ) {
                    return !nomeIgualBigfone(
                        $nome,
                        $indicacaoLider
                    );
                }
            )
        );

    $entradas =
        candidatosTrocaBigFoneEntrada(
            $jogadores,
            $paredaoAtual
        );

    if (
        empty($saidas) ||
        empty($entradas)
    ) {
        return "";
    }

    /*
     * O NPC tenta salvar alguém próximo/útil e,
     * no lugar, colocar um rival ou ameaça.
     */
    $sair =
        escolherEntreCandidatosNPCInteligente(
            $jogadores,
            $dono,
            'salvar_paredao',
            $saidas
        );

    $entrar =
        escolherEntreCandidatosNPCInteligente(
            $jogadores,
            $dono,
            'troca_emparedado',
            $entradas
        );

    if (
        $sair == null ||
        $entrar == null
    ) {
        return "";
    }

    foreach (
        $paredaoAtual
        as $i => $nome
    ) {

        if (
            nomeIgualBigfone(
                $nome,
                $sair
            )
        ) {
            $paredaoAtual[$i] =
                $entrar;

            break;
        }
    }

    $paredaoAtual =
        array_values(
            array_unique(
                $paredaoAtual
            )
        );

    registrarMemoriaSocialNPC(
        $sair,
        $dono,
        'me_salvou',
        2,
        "$dono tirou $sair do Paredão usando a Troca do Big Fone.",
        'bigfone_troca_salvou|' .
        ($_SESSION['rodada'] ?? 1) .
        "|$dono|$sair"
    );

    registrarMemoriaSocialNPC(
        $entrar,
        $dono,
        'me_colocou_paredao',
        2,
        "$dono colocou $entrar no Paredão usando a Troca do Big Fone.",
        'bigfone_troca_entrou|' .
        ($_SESSION['rodada'] ?? 1) .
        "|$dono|$entrar"
    );

    $_SESSION[
        'bigfone_troca_usada'
    ] = true;

    unset(
        $_SESSION[
            'bigfone_troca_emparedado_pendente'
        ]
    );

    return
        "🔁 Pelo poder do Big Fone, $dono tirou <b>$sair</b> do paredão e colocou <b>$entrar</b>. A indicação do líder foi preservada.";
}


/* =========================================================
   🤖 NPC ATENDE O BIG FONE
   ========================================================= */

function npcAtendeBigFone(
    $jogadores,
    $meuNome
) {
    $pesos = [];

    foreach ($jogadores as $j) {

        $nome =
            $j['nome'] ?? '';

        if (
            $nome == '' ||
            nomeIgualBigfone(
                $nome,
                $meuNome
            )
        ) {
            continue;
        }

        $pesos[$nome] =
            pesoAtenderBigFoneNPC(
                $jogadores,
                $nome
            );
    }

    if (empty($pesos)) {
        return $meuNome;
    }

    $total =
        array_sum($pesos);

    if ($total <= 0) {
        $nomes =
            array_keys($pesos);

        return
            $nomes[
                array_rand($nomes)
            ];
    }

    $sorteio =
        rand(1, $total);

    $acumulado = 0;

    foreach ($pesos as $nome => $peso) {
        $acumulado += $peso;

        if ($sorteio <= $acumulado) {
            registrarDecisaoNPC(
                $nome,
                'atender_bigfone',
                $nome,
                $pesos
            );

            return $nome;
        }
    }

    return array_key_first($pesos);
}


/* =========================================================
   ☎️ FINALIZAR ATENDIMENTO
   ========================================================= */

function finalizarAtendimentoBigFone(
    &$jogadores,
    $atendente,
    $atendeuAntesDeTodoMundo = false
) {
    garantirEventosBigFone();

    $_SESSION['evento_extra'][] =
        "☎️ O Big Fone tocou!";

    if ($atendeuAntesDeTodoMundo) {

        $_SESSION['evento_extra'][] =
            "🏃 $atendente correu e atendeu antes de todo mundo.";

    } else {

        $_SESSION['evento_extra'][] =
            "🏃 $atendente correu e atendeu o Big Fone.";
    }

    $textoPoder =
        aplicarPoderBigFone(
            $jogadores,
            $atendente
        );

    if ($textoPoder != '') {
        $_SESSION['evento_extra'][] =
            $textoPoder;
    }

    $_SESSION['jogadores'] =
        $jogadores;

    $_SESSION['bigfone_atendente'] =
        $atendente;

    $_SESSION['bigfone_feito'] =
        true;

    $_SESSION['bigfone_aconteceu'] =
        true;

    $_SESSION['bigfone_aconteceu_rodada'] =
        $_SESSION['rodada'] ?? 1;
}
