<?php
// Processa SOMENTE o formulário próprio do quadro, sem acionar avanços de fase.
if (isset($_POST['rx3_escolher'])) {
    $ok = rx3Escolher((string)$_POST['rx3_escolher'], (string)($_POST['rx3_token'] ?? ''));
    if (!$ok) $_SESSION['rx3_erro'] = 'Essa escolha não pôde ser registrada. Atualize a página e tente novamente.';
    header('Location: jogo.php#raio-x-estrategico');
    exit;
}
