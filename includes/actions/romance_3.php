<?php
/** Ações de Relacionamentos 3.0 — validadas com nonce e PRG. */
if (isset($_POST['rom3_iniciar'])) {
    if (!rom3Iniciar($jogadores,$meuNome,$fase,(string)$_POST['rom3_iniciar']))
        $_SESSION['rom3_erro']='Não foi possível iniciar o momento agora. Verifique se existe outra conversa em andamento.';
    header('Location: jogo.php#romance-3');exit;
}
if (isset($_POST['rom3_cancelar'])) {
    $p=$_SESSION['rom3_pendente']??null;
    if (is_array($p) && hash_equals((string)($p['token']??''),(string)($_POST['rom3_token']??''))
        && (int)($p['semana']??0)===rom3Semana() && (string)($p['fase']??'')===$fase)
        unset($_SESSION['rom3_pendente']);
    header('Location: jogo.php#romance-3');exit;
}
if (isset($_POST['rom3_responder'])) {
    $idx=filter_var($_POST['rom3_responder'],FILTER_VALIDATE_INT);
    if ($idx===false || !rom3Responder($jogadores,$meuNome,$fase,(string)($_POST['rom3_token']??''),(int)$idx))
        $_SESSION['rom3_erro']='Essa resposta não está mais disponível.';
    header('Location: jogo.php#romance-3');exit;
}
if (isset($_POST['rom3_confirmar'])) {
    if (!rom3ConfirmarFim($jogadores,$meuNome,$fase,(string)($_POST['rom3_token']??''),(string)$_POST['rom3_confirmar']))
        $_SESSION['rom3_erro']='Não foi possível confirmar essa decisão.';
    header('Location: jogo.php#romance-3');exit;
}
if (isset($_POST['rom3_continuar'])) {
    $r=$_SESSION['rom3_resultado']??null;
    if (is_array($r) && (int)($r['semana']??0)===rom3Semana() && (string)($r['fase']??'')===$fase)
        unset($_SESSION['rom3_resultado']);
    header('Location: jogo.php#romance-3');exit;
}
// Nunca prosseguir com outra ação enquanto a cena está aberta.
if (($_SERVER['REQUEST_METHOD']??'GET')==='POST'
    && (!empty($_SESSION['rom3_pendente']) || !empty($_SESSION['rom3_confirmacao']) || !empty($_SESSION['rom3_resultado']))) {
    header('Location: jogo.php#romance-3');exit;
}
