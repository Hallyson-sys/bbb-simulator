<?php

/* =========================================================
   📢 FEED DA CASA / AO VIVO
   ========================================================= */


/* =========================
   🧹 LIMPAR AO VIVO
   ========================= */

if (isset($_POST['limpar_log'])) {

    $_SESSION['evento_extra'] = [];

    header("Location: jogo.php");
    exit;
}