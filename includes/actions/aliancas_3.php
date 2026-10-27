<?php
if(isset($_POST['a3_reunir']) && in_array((string)($_SESSION['fase_semana']??''),['interacoes_1','interacoes_2','interacoes_3'],true)) {
    $_SESSION['a3_resultado']=a3ProporReuniao($jogadores,$meuNome,$_POST);
    $_SESSION['jogadores']=$jogadores;
    header('Location: jogo.php'); exit;
}
