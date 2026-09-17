<?php

session_start();

unset($_SESSION['anjo']);
unset($_SESSION['imune']);
unset($_SESSION['monstro']);
unset($_SESSION['monstro_definido']);
unset($_SESSION['prova_anjo_finalizada']);
unset($_SESSION['imunizacao_anjo_feita']);
unset($_SESSION['prova_anjo_tipo']);
unset($_SESSION['prova_anjo_dados']);

if (isset($_SESSION['jogadores'])) {

    foreach ($_SESSION['jogadores'] as &$j) {

        if (!isset($j['status'])) {
            $j['status'] = [];
        }

        $j['status']['anjo'] = false;
        $j['status']['imune'] = false;
        $j['status']['monstro'] = false;
    }

    unset($j);
}

$_SESSION['fase_semana'] = 'anjo';

header("Location: jogo.php");
exit;