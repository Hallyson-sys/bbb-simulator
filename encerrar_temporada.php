<?php

session_start();

session_unset();
session_destroy();

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=UTF-8');

    echo json_encode(
        ['ok' => true],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

header('Location: index.php');
exit;
