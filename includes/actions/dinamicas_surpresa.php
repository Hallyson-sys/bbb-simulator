<?php
if(isset($_POST['ds3_contragolpe_escolher']) && ($_SESSION['fase_semana']??'')==='ds3_contragolpe') {
    $alvo=(string)($_POST['ds3_alvo']??'');
    $origem=(string)($_SESSION['indicacao_lider']??'');
    if($origem!==''&&nomeIgual($origem,(string)$meuNome) && dsAplicarContragolpe($jogadores,$origem,$alvo)) {
        $_SESSION['jogadores']=$jogadores;
        definirFaseDepoisDaFormacaoDoParedao($jogadores);
        $_SESSION['jogadores']=$jogadores;
    } else {
        $_SESSION['evento_extra'][]='⚠️ Escolha inválida para o Contragolpe. Selecione um participante disponível.';
    }
    header('Location: jogo.php');exit;
}
