<?php

/* =========================================================
   🧭 NAVEGAÇÃO GERAL
   ========================================================= */


/* =========================
   🔄 NOVO JOGO
   ========================= */

if (isset($_POST['novo_jogo'])) {

    session_unset();
    session_destroy();

    header("Location: index.php");
    exit;
}


/* =========================
   🏆 IR PARA A GRANDE FINAL
   ========================= */

if (isset($_POST['ir_final'])) {

    header("Location: final.php");
    exit;
}