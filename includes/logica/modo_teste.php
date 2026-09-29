<?php

/* =========================================================
   🧪 MODO DE TESTES
   ========================================================= */


/* =========================================================
   🔒 PERMITIR SOMENTE AMBIENTE LOCAL
   ========================================================= */
function modoTestePermitido()
{
    $host =
        strtolower(
            (string)($_SERVER['HTTP_HOST'] ?? '')
        );

    $server =
        strtolower(
            (string)($_SERVER['SERVER_NAME'] ?? '')
        );

    $hostsLocais = [
        'localhost',
        '127.0.0.1',
        '::1'
    ];

    foreach ($hostsLocais as $local) {

        if (
            str_contains($host, $local) ||
            $server === $local
        ) {
            return true;
        }
    }

    return false;
}


/* =========================================================
   📅 FASES DISPONÍVEIS
   ========================================================= */
function fasesDisponiveisModoTeste()
{
    return [
        'queridometro' =>
            '💖 Queridômetro',

        'confessionario' =>
            '🎥 Confessionário',

        'interacoes_1' =>
            '💬 Interações 1',

        'lider' =>
            '👑 Prova do Líder',

        'vip_xepa' =>
            '🟡 VIP / Xepa',

        'anjo' =>
            '😇 Prova do Anjo',

        'monstro' =>
            '👹 Monstro',

        'bigfone' =>
            '☎️ Big Fone',

        'poder_curinga' =>
            '🎁 Poder Curinga',

        'interacoes_2' =>
            '💬 Interações 2',

        'festa' =>
            '🎉 Festa',

        'imunizacao_anjo' =>
            '🛡️ Imunização do Anjo',

        'paredao' =>
            '🚨 Formação do Paredão',

        'discordia' =>
            '🔥 Jogo da Discórdia',

        'interacoes_3' =>
            '💬 Interações 3',

        'eliminacao' =>
            '❌ Eliminação'
    ];
}


/* =========================================================
   👤 NOMES DOS PARTICIPANTES
   ========================================================= */
function nomesParticipantesModoTeste($jogadores)
{
    $nomes = [];

    foreach ($jogadores as $j) {

        $nome =
            trim(
                (string)($j['nome'] ?? '')
            );

        if ($nome !== '') {
            $nomes[] = $nome;
        }
    }

    sort(
        $nomes,
        SORT_NATURAL | SORT_FLAG_CASE
    );

    return $nomes;
}


/* =========================================================
   🧹 LIMPAR ESTADOS TEMPORÁRIOS DA SEMANA
   ========================================================= */
function limparEstadoSemanaModoTeste()
{
    $chaves = [
        'lider',
        'anjo',
        'imune',
        'monstro',

        'vip_definido',
        'monstro_definido',
        'imunizacao_anjo_feita',

        'paredao',
        'paredao_formado',
        'votos_paredao',
        'meu_voto_paredao',
        'indicacao_lider',
        'indicacao_bigfone',

        'bigfone_feito',
        'bigfone_indicacao_pendente',
        'bigfone_dono_poder',

        'poder_curinga',
        'curinga_decidido_rodada',

        'prova_anjo_finalizada',
        'prova_anjo_tipo',
        'prova_anjo_dados',

        'queridometro_feito',
        'queridometro_resultado',

        'discordia_feito',
        'tema_discordia',

        'npc_festa_feita',
        'acao_festa_selecionada',

        'ultimo_ranking_eliminacao',
        'eliminado'
    ];

    foreach ($chaves as $chave) {
        unset($_SESSION[$chave]);
    }

    if (
        isset($_SESSION['jogadores']) &&
        is_array($_SESSION['jogadores'])
    ) {

        foreach (
            $_SESSION['jogadores']
            as &$j
        ) {

            if (
                !isset($j['status']) ||
                !is_array($j['status'])
            ) {
                $j['status'] = [];
            }

            $j['status']['lider'] = false;
            $j['status']['anjo'] = false;
            $j['status']['imune'] = false;
            $j['status']['monstro'] = false;
            $j['status']['vip'] = false;
            $j['status']['xepa'] = false;
        }

        unset($j);
    }
}


/* =========================================================
   👑 DEFINIR STATUS ÚNICO
   ========================================================= */
function definirStatusParticipanteModoTeste(
    &$jogadores,
    $campo,
    $nome
) {
    foreach ($jogadores as &$j) {

        if (
            !isset($j['status']) ||
            !is_array($j['status'])
        ) {
            $j['status'] = [];
        }

        $j['status'][$campo] =
            nomeIgual(
                $j['nome'] ?? '',
                $nome
            );
    }

    unset($j);
}


/* =========================================================
   📝 EVENTO DEV
   ========================================================= */
function registrarEventoModoTeste($texto)
{
    if (
        !isset($_SESSION['evento_extra']) ||
        !is_array($_SESSION['evento_extra'])
    ) {
        $_SESSION['evento_extra'] = [];
    }

    $_SESSION['evento_extra'][] =
        "🧪 [DEV] " . $texto;
}
