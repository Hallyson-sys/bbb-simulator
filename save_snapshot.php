<?php

session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (empty($_SESSION['id_temporada'])) {
    try {
        $_SESSION['id_temporada'] = 'bbb_' . bin2hex(random_bytes(8));
    } catch (Throwable $e) {
        $_SESSION['id_temporada'] = 'bbb_' . uniqid('', true);
    }
}

if (
    empty($_SESSION['jogadores']) ||
    !is_array($_SESSION['jogadores'])
) {
    echo json_encode(
        [
            'ativo' => false
        ],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$snapshot = $_SESSION;

/*
 * Remove marcadores puramente técnicos que não precisam
 * ser persistidos no navegador.
 */
unset(
    $snapshot['save_restaurado_em']
);

$historicoEliminados = $_SESSION['historico_eliminados'] ?? [];
$ultimoEliminado = '';

if (is_array($historicoEliminados) && !empty($historicoEliminados)) {
    $ultimoEliminado = (string)end($historicoEliminados);
}

$resumo = [
    'nome' => $_SESSION['meu_nome'] ?? 'Jogador',
    'rodada' => (int)($_SESSION['rodada'] ?? 1),
    'participantes' => count($_SESSION['jogadores']),
    'fase' => $_SESSION['fase_semana'] ?? '',
    'ultimo_eliminado' => $ultimoEliminado,
    'modo_espectador' => !empty($_SESSION['modo_espectador'])
];

echo json_encode(
    [
        'ativo' => true,
        'season_id' => $_SESSION['id_temporada'],
        'resumo' => $resumo,
        'snapshot' => $snapshot
    ],
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
);
