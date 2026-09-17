<?php

/** @var array $jogadores */
/** @var string $meuNome */
/** @var int $qtdVIP */


/* =========================
   👑 DEFINIR VIP / XEPA
========================= */

if (isset($_POST['definir_vip'])) {

    $lider = $_SESSION['lider'] ?? '';
    $selecionados = $_POST['vip'] ?? [];

    garantirMeuJogadorNaLista($jogadores);

    /* Remove vazio, duplicado e o próprio líder */
    $selecionados = array_values(
        array_unique(
            array_filter(
                $selecionados,
                function ($nome) use ($lider) {
                    return
                        trim((string)$nome) != '' &&
                        !nomeIgual($nome, $lider);
                }
            )
        )
    );

    if (count($selecionados) != $qtdVIP) {

        $_SESSION['evento_extra'][] =
            "⚠️ Você precisa escolher exatamente $qtdVIP participantes para o VIP.";

        header("Location: jogo.php");
        exit;
    }

    foreach ($jogadores as &$j) {

        $j['status']['vip'] = false;
        $j['status']['xepa'] = false;

        if (!isset($j['estatisticas'])) {
            $j['estatisticas'] = [];
        }

        /* Líder sempre está no VIP */
        if ($j['nome'] == $lider) {

            $j['status']['vip'] = true;

            $j['estatisticas']['vip'] =
                ($j['estatisticas']['vip'] ?? 0) + 1;

            continue;
        }

        if (in_array($j['nome'], $selecionados)) {

            $j['status']['vip'] = true;

            $j['estatisticas']['vip'] =
                ($j['estatisticas']['vip'] ?? 0) + 1;

        } else {

            $j['status']['xepa'] = true;

            $j['estatisticas']['xepa'] =
                ($j['estatisticas']['xepa'] ?? 0) + 1;
        }
    }

    unset($j);

    garantirMeuJogadorNaLista($jogadores);

    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['vip_definido'] = true;
    $_SESSION['fase_semana'] = 'anjo';


    /* Montar listas para o Ao Vivo */

    $vipLista = [];
    $xepaLista = [];

    foreach ($jogadores as $j) {

        if (!empty($j['status']['vip'])) {
            $vipLista[] = $j['nome'];
        }

        if (!empty($j['status']['xepa'])) {
            $xepaLista[] = $j['nome'];
        }
    }

    $_SESSION['evento_extra'][] =
        "👑 O líder $lider definiu o VIP.";

    $_SESSION['evento_extra'][] =
        "🟡 VIP: " . implode(", ", $vipLista) . ".";

    $_SESSION['evento_extra'][] =
        "🍞 Xepa: " . implode(", ", $xepaLista) . ".";

    header("Location: jogo.php");
    exit;
}


/* =========================
   👹 DEFINIR MONSTRO
========================= */

if (isset($_POST['definir_monstro'])) {

    $anjo = $_SESSION['anjo'] ?? '';
    $selecionados = $_POST['monstro'] ?? [];

    if (count($selecionados) > 2) {

        $_SESSION['evento_extra'][] =
            "⚠️ Você só pode escolher até 2 participantes para o Monstro.";

        header("Location: jogo.php");
        exit;
    }

    foreach ($jogadores as &$j) {

        $j['status']['monstro'] = false;

        if (in_array($j['nome'], $selecionados)) {

            $j['status']['monstro'] = true;

            if (!isset($j['estatisticas'])) {
                $j['estatisticas'] = [];
            }

            $j['estatisticas']['monstro'] =
                ($j['estatisticas']['monstro'] ?? 0) + 1;

            $j['popularidade'] =
                max(
                    0,
                    ($j['popularidade'] ?? 50) - rand(3, 8)
                );

            alterarAfinidade(
                $jogadores,
                $j['nome'],
                $anjo,
                -5,
                8,
                -5
            );
        }
    }

    unset($j);

    garantirMeuJogadorNaLista($jogadores);

    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['monstro'] = $selecionados;
    $_SESSION['monstro_definido'] = true;
    $_SESSION['fase_semana'] = 'bigfone';

    $_SESSION['evento_extra'][] =
        "👹 $anjo colocou no Monstro: " .
        implode(" e ", $selecionados) .
        ".";

    header("Location: jogo.php");
    exit;
}


/* =========================
   😇 DEFINIR IMUNIDADE DO ANJO
========================= */

if (isset($_POST['definir_imunidade_anjo'])) {

    $imunizado =
        $_POST['imunizado_anjo'] ?? '';

    foreach ($jogadores as &$j) {

        $j['status']['imune'] = false;

        if ($j['nome'] == $imunizado) {

            $j['status']['imune'] = true;

            if (!isset($j['estatisticas'])) {
                $j['estatisticas'] = [];
            }

            $j['estatisticas']['imune'] =
                ($j['estatisticas']['imune'] ?? 0) + 1;
        }
    }

    unset($j);

    garantirMeuJogadorNaLista($jogadores);

    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['imune'] = $imunizado;
    $_SESSION['imunizacao_anjo_feita'] = true;

    $_SESSION['evento_extra'][] =
        "🛡️ O Anjo " .
        $_SESSION['anjo'] .
        " imunizou $imunizado antes da formação do paredão.";

    $_SESSION['fase_semana'] = 'paredao';

    header("Location: jogo.php");
    exit;
}