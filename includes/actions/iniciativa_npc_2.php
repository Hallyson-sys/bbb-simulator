<?php
/** Roteamento PRG exclusivo da iniciativa NPC, sem interferir nos diálogos I4. */
if (isset($_POST['in2_convite_decisao'])) {
    in2ResponderConvite($jogadores,$meuNome,$fase,
        (string)($_POST['in2_token']??''),(string)$_POST['in2_convite_decisao']);
    header('Location: jogo.php#iniciativa-npc'); exit;
}
if (isset($_POST['in2_resposta'])) {
    $indice=filter_var($_POST['in2_resposta'],FILTER_VALIDATE_INT);
    if ($indice!==false) in2Concluir($jogadores,$meuNome,$fase,(string)($_POST['in2_token']??''),(int)$indice);
    header('Location: jogo.php#iniciativa-npc'); exit;
}
if (isset($_POST['in2_continuar'])) {
    unset($_SESSION['in2_resultado']);
    header('Location: jogo.php#iniciativa-npc'); exit;
}
// Uma conversa já aceita é modal em relação às outras decisões.
if (($_SERVER['REQUEST_METHOD']??'GET')==='POST' && (!empty($_SESSION['in2_conversa']) || !empty($_SESSION['in2_resultado']))) {
    header('Location: jogo.php#iniciativa-npc'); exit;
}
