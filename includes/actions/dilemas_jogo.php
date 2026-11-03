<?php
/** Ações do Dilema: POST autenticado e processado uma única vez. */
if (isset($_POST['dj_escolher'])) {
    $token = (string)($_POST['dj_token'] ?? '');
    $indice = filter_var($_POST['dj_escolher'], FILTER_VALIDATE_INT);
    if ($indice !== false) {
        djResolver($jogadores, $meuNome, $fase, $token, (int)$indice);
    }
    header('Location: jogo.php'); exit;
}
if (isset($_POST['dj_continuar'])) {
    if (!empty($_SESSION['dj_resultado'])) unset($_SESSION['dj_resultado']);
    header('Location: jogo.php'); exit;
}
// Enquanto há dilema/resultado, não processar submissões de outras ações.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)
    && (!empty($_SESSION['dj_ativo']) || !empty($_SESSION['dj_resultado']))) {
    header('Location: jogo.php'); exit;
}
