<?php

/* =========================================================
   🚨 LÓGICA DA ELIMINAÇÃO
   Regras do Paredão, rejeição pública e estatísticas
   ========================================================= */

require_once __DIR__ . '/utilitarios.php';


/* =========================================================
   👤 BUSCAR PARTICIPANTE
   ========================================================= */

function buscarJogadorResultado($jogadores, $nome)
{
    foreach ($jogadores as $j) {

        if (
            nomeIgual(
                $j['nome'] ?? '',
                $nome
            )
        ) {
            return $j;
        }
    }

    return null;
}


/* =========================================================
   🧾 RESUMO DO PARTICIPANTE
   ========================================================= */

function resumoParticipanteResultado($jogador)
{
    if (!$jogador) {
        return "Participante";
    }

    $partes = [];

    if (!empty($jogador['personalidade'])) {
        $partes[] =
            "🎭 " . $jogador['personalidade'];
    }

    if (!empty($jogador['estado'])) {
        $partes[] =
            "📍 " . $jogador['estado'];
    }

    if (!empty($jogador['profissao'])) {
        $partes[] =
            "💼 " . $jogador['profissao'];
    }

    return empty($partes)
        ? "Participante"
        : implode(" • ", $partes);
}


/* =========================================================
   🎨 CLASSE VISUAL DA PORCENTAGEM
   ========================================================= */

function corPorcentagem($pct)
{
    if ($pct >= 60) {
        return "perigo";
    }

    if ($pct >= 35) {
        return "alerta";
    }

    return "safe";
}


/* =========================================================
   📊 ESTATÍSTICA INDIVIDUAL
   ========================================================= */

function estatResultado($jogador, $campo)
{
    return
        $jogador['estatisticas'][$campo]
        ?? 0;
}


/* =========================================================
   📈 REGISTRAR ESTATÍSTICA
   ========================================================= */

function registrarEstatisticaResultado(
    &$jogadores,
    $nome,
    $campo,
    $valor = 1
) {

    foreach ($jogadores as &$j) {

        if (
            nomeIgual(
                $j['nome'] ?? '',
                $nome
            )
        ) {

            if (
                !isset($j['estatisticas']) ||
                !is_array($j['estatisticas'])
            ) {
                $j['estatisticas'] = [];
            }

            $j['estatisticas'][$campo] =
                ($j['estatisticas'][$campo] ?? 0)
                + $valor;

            break;
        }
    }

    unset($j);
}


/* =========================================================
   🚨 CONTAR PAREDÃO UMA ÚNICA VEZ
   ========================================================= */

function contarParedaoResultadoUmaVez(
    &$jogadores,
    $paredao,
    $rodada
) {

    $chave =
        'estatisticas_paredao_contadas_rodada_'
        . $rodada;

    /*
     * Evita contar o mesmo Paredão novamente
     * caso a página seja atualizada.
     */
    if (isset($_SESSION[$chave])) {
        return;
    }

    foreach ($paredao as $nome) {

        if (trim((string)$nome) == '') {
            continue;
        }

        registrarEstatisticaResultado(
            $jogadores,
            $nome,
            'paredao',
            1
        );
    }

    $_SESSION[$chave] = true;
}


/* =========================================================
   📈 POPULARIDADE DO PARTICIPANTE
   ========================================================= */

function popularidadeJogadorResultado(
    $jogadores,
    $nome
) {

    $jogador =
        buscarJogadorResultado(
            $jogadores,
            $nome
        );

    if (!$jogador) {
        return 50;
    }

    return limitar(
        (int)($jogador['popularidade'] ?? 50),
        0,
        100
    );
}


/* =========================================================
   📢 STATUS DA POPULARIDADE
   ========================================================= */

function statusPopularidadeResultado(
    $popularidade
) {

    if ($popularidade >= 90) {
        return "Favorito absoluto";
    }

    if ($popularidade >= 75) {
        return "Muito querido";
    }

    if ($popularidade >= 60) {
        return "Bem aceito";
    }

    if ($popularidade >= 45) {
        return "Dividido pelo público";
    }

    if ($popularidade >= 30) {
        return "Mal visto";
    }

    return "Cancelado";
}


/* =========================================================
   📉 CALCULAR REJEIÇÃO PÚBLICA
   Quanto menor a popularidade,
   maior tende a ser a rejeição.
   ========================================================= */

function calcularRejeicaoPublicaResultado(
    $jogador
) {

    $popularidade =
        limitar(
            (int)($jogador['popularidade'] ?? 50),
            0,
            100
        );

    /*
     * Base principal.
     *
     * Popularidade alta → rejeição menor.
     * Popularidade baixa → rejeição maior.
     */
    $rejeicao =
        105 - $popularidade;


    $personalidade =
        $jogador['personalidade']
        ?? 'Neutro';


    /* =========================
       🎭 PERSONALIDADE
       ========================= */

    if ($personalidade == 'Planta') {

        $rejeicao +=
            rand(3, 9);
    }


    if (
        $personalidade == 'Barraqueiro' ||
        $personalidade == 'Explosivo'
    ) {

        /*
         * Participante de treta pode dividir
         * muito a opinião pública.
         */
        $rejeicao +=
            rand(-8, 10);
    }


    if ($personalidade == 'Influencer') {

        $rejeicao +=
            rand(-7, 4);
    }


    if ($personalidade == 'Fofo') {

        $rejeicao -=
            rand(2, 8);
    }


    if (
        $personalidade == 'Manipulador' ||
        $personalidade == 'Falso'
    ) {

        $rejeicao +=
            rand(2, 10);
    }


    /* =========================
       🏠 SITUAÇÃO DA SEMANA
       ========================= */

    if (
        !empty(
            $jogador['status']['monstro']
        )
    ) {

        $rejeicao +=
            rand(2, 6);
    }


    if (
        !empty(
            $jogador['status']['xepa']
        )
    ) {

        $rejeicao +=
            rand(0, 3);
    }


    if (
        !empty(
            $jogador['status']['vip']
        )
    ) {

        $rejeicao -=
            rand(0, 3);
    }


    /* =========================
       🚨 HISTÓRICO DE PAREDÕES
       ========================= */

    $paredoes =
        $jogador['estatisticas']['paredao']
        ?? 0;


    if ($paredoes >= 2) {

        $rejeicao +=
            min(
                8,
                $paredoes * 2
            );
    }


    /*
     * Pequeno fator aleatório para evitar
     * resultados completamente previsíveis.
     */
    $rejeicao +=
        rand(-5, 5);


    return max(
        5,
        $rejeicao
    );
}


/* =========================================================
   🗳️ GERAR RESULTADO DO PAREDÃO
   ========================================================= */

function gerarRankingEliminacaoPorPopularidade(
    $jogadores,
    $paredao
) {

    $pesos = [];
    $total = 0;


    foreach ($paredao as $nome) {

        $jogador =
            buscarJogadorResultado(
                $jogadores,
                $nome
            );


        if (!$jogador) {
            continue;
        }


        $peso =
            calcularRejeicaoPublicaResultado(
                $jogador
            );


        $pesos[$nome] =
            $peso;


        $total +=
            $peso;
    }


    if (
        empty($pesos) ||
        $total <= 0
    ) {

        return [];
    }


    $ranking = [];

    $nomes =
        array_keys(
            $pesos
        );

    $acumulado = 0;


    foreach (
        $nomes as $i => $nome
    ) {

        /*
         * O último recebe o restante para
         * garantir aproximadamente 100%.
         */
        if (
            $i ==
            count($nomes) - 1
        ) {

            $pct =
                round(
                    100 - $acumulado,
                    2
                );

        } else {

            $pct =
                round(
                    (
                        $pesos[$nome]
                        /
                        $total
                    ) * 100,
                    2
                );


            $acumulado +=
                $pct;
        }


        $ranking[$nome] =
            max(
                0.01,
                $pct
            );
    }


    /*
     * Maior rejeição primeiro.
     */
    arsort(
        $ranking
    );


    return
        $ranking;
}


/* =========================================================
   ❤️ POPULARIDADE APÓS SOBREVIVER AO PAREDÃO
   ========================================================= */

function ajustarPopularidadePosParedaoResultado(
    &$jogadores,
    $ranking,
    $eliminado
) {

    foreach ($jogadores as &$j) {

        $nome =
            $j['nome'] ?? '';


        if (
            $nome == '' ||
            !isset($ranking[$nome])
        ) {
            continue;
        }


        if (!isset($j['popularidade'])) {
            $j['popularidade'] = 50;
        }


        /*
         * O eliminado não recebe bônus.
         */
        if (
            nomeIgual(
                $nome,
                $eliminado
            )
        ) {
            continue;
        }


        /*
         * Sobreviver ao Paredão melhora
         * um pouco a imagem pública.
         */
        $ganho =
            rand(3, 7);


        /*
         * Sobreviver com menos de 20%
         * de rejeição gera um bônus extra.
         */
        if (
            ($ranking[$nome] ?? 100)
            < 20
        ) {

            $ganho +=
                rand(2, 5);
        }


        $j['popularidade'] =
            limitar(
                $j['popularidade']
                + $ganho,
                0,
                100
            );
    }

    unset($j);
}

/* =========================================================
   🧹 LIMPAR DADOS DA SEMANA APÓS O RESULTADO
   Usado tanto por eliminação normal quanto pelo Paredão Falso.
   ========================================================= */
function limparEstadoSemanalAposEliminacao()
{
    $chavesSemana = [
        'paredao',
        'eliminado',
        'ultimo_ranking_eliminacao',
        'paredao_formado',
        'votos_paredao',
        'dedo_duro',
        'indicacao_lider',
        'indicacao_bigfone',
        'meu_voto_paredao',
        'lider',
        'anjo',
        'imune',
        'monstro',
        'vip_definido',
        'monstro_definido',
        'imunizacao_anjo_feita',
        'prova_tipo',
        'prova_anjo_tipo',
        'prova_anjo_finalizada',
        'bigfone',
        'bigfone_feito',
        'bigfone_atendente',
        'bigfone_poder',
        'bigfone_indicacao_pendente',
        'bigfone_dono_poder',
        'bigfone_anular_voto_pendente',
        'bigfone_espiar_voto_pendente',
        'bigfone_troca_emparedado_pendente',
        'bigfone_contragolpe_pendente',
        'bigfone_voto_duplo_ativo',
        'bigfone_anular_voto_de',
        'bigfone_espiar_voto_de',
        'bigfone_contragolpe_usado',
        'bigfone_troca_usada',
        'poder_curinga',
        'curinga_decidido_rodada',
        'curinga_voto_duplo_ativo',
        'curinga_anular_voto_de',
        'curinga_espiar_voto_de',
        'curinga_contra_golpe_usado',
        'curinga_troca_usada',
        'imunidade_curinga',
        'queridometro_feito',
        'queridometro_resultado',
        'npc_festa_feita',
        'acao_festa_selecionada',
        'confessionario_falas',
        'confessionario_feito',
        'discordia_feito',
        'tema_discordia',
        'bate_volta',
        'bate_volta_decidido',
        'bate_volta_resultado'
    ];

    foreach ($chavesSemana as $chave) {
        unset($_SESSION[$chave]);
    }
}


/* =========================================================
   📅 PREPARAR NOVA RODADA
   ========================================================= */
function iniciarNovaRodadaAposEliminacao(&$jogadores)
{
    $_SESSION['rodada'] =
        ($_SESSION['rodada'] ?? 1) + 1;

    limparEstadoSemanalAposEliminacao();

    foreach ($jogadores as &$j) {
        if (
            !isset($j['status']) ||
            !is_array($j['status'])
        ) {
            $j['status'] = [];
        }

        $j['status']['lider'] = false;
        $j['status']['anjo'] = false;
        $j['status']['imune'] = false;
        $j['status']['vip'] = false;
        $j['status']['xepa'] = false;
        $j['status']['monstro'] = false;
    }

    unset($j);

    $_SESSION['jogadores'] = array_values($jogadores);
    $_SESSION['fase_semana'] = 'interacoes_1';
    $_SESSION['acoes_restantes'] = 3;
}
