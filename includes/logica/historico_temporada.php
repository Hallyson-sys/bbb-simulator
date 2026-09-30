<?php

/* =========================================================
   📖 HISTÓRICO DA TEMPORADA
   - Registro estruturado e compatível com saves antigos.
   - Eventos possuem chave única para evitar duplicidade em F5/POST.
   ========================================================= */

function garantirHistoricoTemporada()
{
    if (!isset($_SESSION['historico_temporada']) || !is_array($_SESSION['historico_temporada'])) {
        $_SESSION['historico_temporada'] = [];
    }
}

function chaveHistoricoTemporada($tipo, $rodada, $participantes = [], $extra = '')
{
    $nomes = array_values(array_filter(array_map('strval', (array)$participantes)));
    sort($nomes, SORT_NATURAL | SORT_FLAG_CASE);

    return sha1(
        (string)$tipo . '|' .
        (int)$rodada . '|' .
        implode('|', $nomes) . '|' .
        (string)$extra
    );
}

function registrarHistoricoTemporada(
    $tipo,
    $titulo,
    $descricao,
    $participantes = [],
    $icone = '📌',
    $rodada = null,
    $extraChave = '',
    $dados = []
) {
    garantirHistoricoTemporada();

    $rodada = $rodada === null
        ? (int)($_SESSION['rodada'] ?? 1)
        : (int)$rodada;

    $participantes = array_values(
        array_unique(
            array_filter(
                array_map(
                    function ($nome) {
                        return trim((string)$nome);
                    },
                    (array)$participantes
                )
            )
        )
    );

    $chave = chaveHistoricoTemporada(
        $tipo,
        $rodada,
        $participantes,
        $extraChave !== '' ? $extraChave : $descricao
    );

    foreach ($_SESSION['historico_temporada'] as $evento) {
        if (($evento['chave'] ?? '') === $chave) {
            return false;
        }
    }

    $_SESSION['historico_temporada'][] = [
        'chave' => $chave,
        'rodada' => $rodada,
        'tipo' => (string)$tipo,
        'icone' => (string)$icone,
        'titulo' => trim((string)$titulo),
        'descricao' => trim((string)$descricao),
        'participantes' => $participantes,
        'dados' => is_array($dados) ? $dados : [],
        'ordem' => count($_SESSION['historico_temporada']) + 1
    ];

    return true;
}

function historicoTemporadaPorRodada()
{
    garantirHistoricoTemporada();

    $porRodada = [];

    foreach ($_SESSION['historico_temporada'] as $evento) {
        $rodada = max(1, (int)($evento['rodada'] ?? 1));

        if (!isset($porRodada[$rodada])) {
            $porRodada[$rodada] = [];
        }

        $porRodada[$rodada][] = $evento;
    }

    ksort($porRodada, SORT_NUMERIC);

    return $porRodada;
}

function nomesPorStatusHistorico($jogadores, $status)
{
    $nomes = [];

    foreach ((array)$jogadores as $j) {
        if (!empty($j['status'][$status]) && !empty($j['nome'])) {
            $nomes[] = $j['nome'];
        }
    }

    return array_values(array_unique($nomes));
}
