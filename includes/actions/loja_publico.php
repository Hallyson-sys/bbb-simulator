<?php

/** @var array $jogadores */


/* =========================================================
   🪙 USAR LOJA DO PÚBLICO
   ========================================================= */

if (isset($_POST['usar_loja_publico'])) {

    /*
     * Primeiro confirma se a Loja está realmente
     * disponível nesta rodada.
     */
    if (
        !lojaPublicoDisponivelRodada(
            $_SESSION['rodada'] ?? 1
        )
    ) {

        $_SESSION['evento_extra'][] =
            "🪙 A Loja do Público não está disponível nesta rodada.";

        header("Location: jogo.php");
        exit;
    }


    /*
     * Descobre qual item foi clicado.
     */
    $itemLoja =
        $_POST['usar_loja_publico'] ?? '';


    /*
     * Toda a regra de preço, efeitos,
     * moedas e validações está em:
     *
     * includes/logica/loja_publico.php
     */
    $resultadoLoja =
        usarItemLojaPublico(
            $jogadores,
            $itemLoja
        );


    /*
     * Mostra o resultado no Ao Vivo.
     */
    $_SESSION['evento_extra'][] =
        $resultadoLoja;


    /*
     * Salva qualquer mudança feita nos participantes,
     * por exemplo popularidade alterada pelo Mutirão
     * ou pelo Impulso de Imagem.
     */
    $_SESSION['jogadores'] =
        $jogadores;


    header("Location: jogo.php");
    exit;
}