<?php

session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$entrada = json_decode(
    file_get_contents('php://input'),
    true
);

$snapshot =
    $entrada['snapshot']
    ?? null;

if (
    !is_array($snapshot) ||
    empty($snapshot['jogadores']) ||
    !is_array($snapshot['jogadores']) ||
    empty($snapshot['meu_nome'])
) {
    http_response_code(422);

    echo json_encode(
        [
            'ok' => false,
            'erro' => 'Save inválido.'
        ],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

/*
 * Segurança básica: só aceitamos estruturas JSON.
 * Nenhum objeto PHP serializado é restaurado.
 */
session_unset();

foreach ($snapshot as $chave => $valor) {
    if (!is_string($chave)) {
        continue;
    }

    $_SESSION[$chave] = $valor;
}

$_SESSION['save_restaurado_em'] = time();

$fase = $_SESSION['fase_semana'] ?? '';
$total = count($_SESSION['jogadores'] ?? []);

$redirect = 'jogo.php';

if (
    !empty($_SESSION['paredao_falso_ativo']) &&
    $fase === 'quarto_secreto'
) {
    $redirect = 'quarto_secreto.php';

} elseif (
    $fase === 'finalistas' ||
    $total === 3
) {
    $redirect = 'final.php';

} elseif (
    $fase === 'eliminacao' &&
    !empty($_SESSION['paredao'])
) {
    $redirect = 'resultado.php';

} elseif (
    $fase === 'casa_vidro' &&
    !empty($_SESSION['casa_vidro_ativa'])
) {
    $redirect = 'casa_vidro.php';
}

echo json_encode(
    [
        'ok' => true,
        'redirect' => $redirect
    ],
    JSON_UNESCAPED_UNICODE
);
