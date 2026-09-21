<?php

/* =========================================================
   👁️ ACTIONS — MODO ESPECTADOR
   ========================================================= */

if (isset($_POST['continuar_espectador'])) {
    $_SESSION['modo_espectador'] = true;

    /*
     * Mantém o registro de que o jogador foi eliminado,
     * mas libera a temporada para continuar sem recolocá-lo.
     */
    $_SESSION['fase_semana'] =
        count($_SESSION['jogadores'] ?? []) <= 3
            ? 'finalistas'
            : 'espectador';

    if (
        !isset($_SESSION['evento_extra']) ||
        !is_array($_SESSION['evento_extra'])
    ) {
        $_SESSION['evento_extra'] = [];
    }

    $_SESSION['evento_extra'][] =
        "👁️ Você entrou no Modo Espectador e continuará acompanhando a temporada.";

    header('Location: jogo.php');
    exit;
}


if (
    isset($_POST['simular_rodada_espectador']) &&
    !empty($_SESSION['modo_espectador'])
) {
    $jogadores =
        array_values(
            $_SESSION['jogadores']
            ?? []
        );

    simularRodadaEspectador(
        $jogadores,
        $meuNome
    );

    $_SESSION['jogadores'] =
        array_values($jogadores);

    header('Location: jogo.php');
    exit;
}
