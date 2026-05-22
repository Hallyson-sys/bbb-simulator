<?php
session_start();

// destruir tudo da sessão
session_unset();
session_destroy();

// voltar pro início
header("Location: index.php");
exit;
?>