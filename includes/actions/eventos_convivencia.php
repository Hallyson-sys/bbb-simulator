<?php
if (isset($_POST['evento_convivencia_escolha'])) {
    ecProcessarEscolha($jogadores,$meuNome,(int)$_POST['evento_convivencia_escolha']);
}
if (isset($_POST['evento_convivencia_fechar_resultado'])) unset($_SESSION['evento_convivencia_resultado']);

if (isset($_POST['evento_conhecimento_acao'], $_POST['evento_conhecimento_chave'])) {
    ecProcessarAcaoConhecimento(
        $jogadores,
        $meuNome,
        (string)$_POST['evento_conhecimento_chave'],
        (string)$_POST['evento_conhecimento_acao'],
        (string)($_POST['evento_conhecimento_destino'] ?? '')
    );
}
