<?php

session_start();

if (!isset($_SESSION['jogadores'])) {
    header("Location: index.php");
    exit;
}

header("Location: jogo.php");
exit;