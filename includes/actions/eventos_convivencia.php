<?php
if (isset($_POST['evento_convivencia_escolha'])) {
    ecProcessarEscolha($jogadores,$meuNome,(int)$_POST['evento_convivencia_escolha']);
}
if (isset($_POST['evento_convivencia_fechar_resultado'])) unset($_SESSION['evento_convivencia_resultado']);
