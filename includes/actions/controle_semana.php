<?php

require_once __DIR__ . '/../logica/consequencias_sociais.php';

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

    /* Escolher alguém para o VIP gera gratidão. */
    foreach ($selecionados as $nomeVIP) {
        aplicarConsequenciaSocial(
            $jogadores,
            $lider,
            $nomeVIP,
            'vip',
            'vip|' .
            ($_SESSION['rodada'] ?? 1) .
            '|' . $lider .
            '|' . $nomeVIP,
            "$lider colocou $nomeVIP no VIP."
        );
    }

    garantirMeuJogadorNaLista($jogadores);

    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['vip_definido'] = true;
    $_SESSION['fase_semana'] = 'anjo';

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

    if (function_exists('registrarHistoricoTemporada')) {
        registrarHistoricoTemporada(
            'vip_xepa',
            'VIP e Xepa',
            'VIP: ' . implode(', ', $vipLista) . '. Xepa: ' . implode(', ', $xepaLista) . '.',
            array_merge($vipLista, $xepaLista),
            '🍽️',
            null,
            'vip_xepa'
        );
    }

    header("Location: jogo.php");
    exit;
}


/* =========================
   👹 DEFINIR MONSTRO
========================= */

if (isset($_POST['definir_monstro'])) {

    $anjo = $_SESSION['anjo'] ?? '';
    $selecionados = $_POST['monstro'] ?? [];

    $selecionados = array_values(
        array_unique(
            array_filter(
                $selecionados,
                function ($nome) use ($anjo) {
                    return
                        trim((string)$nome) !== '' &&
                        !nomeIgual($nome, $anjo);
                }
            )
        )
    );

    if (count($selecionados) > 2) {

        $_SESSION['evento_extra'][] =
            "⚠️ Você só pode escolher até 2 participantes para o Monstro.";

        header("Location: jogo.php");
        exit;
    }

    foreach ($jogadores as &$j) {

        if (!isset($j['status']) || !is_array($j['status'])) {
            $j['status'] = [];
        }

        $j['status']['monstro'] = false;

        if (
            in_array(
                $j['nome'] ?? '',
                $selecionados,
                true
            )
        ) {
            $nomeAlvo = $j['nome'] ?? '';

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

            aplicarConsequenciaSocial(
                $jogadores,
                $anjo,
                $nomeAlvo,
                'monstro',
                'monstro|' .
                ($_SESSION['rodada'] ?? 1) .
                '|' . $anjo .
                '|' . $nomeAlvo,
                "$anjo colocou $nomeAlvo no Monstro."
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

    if (function_exists('registrarHistoricoTemporada')) {
        registrarHistoricoTemporada(
            'monstro',
            'Castigo do Monstro',
            "$anjo escolheu " . implode(' e ', $selecionados) . ' para o Monstro.',
            array_merge([$anjo], $selecionados),
            '👹',
            null,
            'monstro'
        );
    }

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

    $anjoAtual = $_SESSION['anjo'] ?? '';

    aplicarConsequenciaSocial(
        $jogadores,
        $anjoAtual,
        $imunizado,
        'imunidade',
        'imunidade_anjo|' .
        ($_SESSION['rodada'] ?? 1) .
        '|' . $anjoAtual .
        '|' . $imunizado,
        "$anjoAtual imunizou $imunizado."
    );

    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['imune'] = $imunizado;
    $_SESSION['imunizacao_anjo_feita'] = true;

    $_SESSION['evento_extra'][] =
        "🛡️ O Anjo " .
        $_SESSION['anjo'] .
        " imunizou $imunizado antes da formação do paredão.";

    if (function_exists('registrarHistoricoTemporada')) {
        registrarHistoricoTemporada(
            'imunidade',
            'Imunidade do Anjo',
            ($_SESSION['anjo'] ?? 'O Anjo') . " imunizou $imunizado.",
            [$_SESSION['anjo'] ?? '', $imunizado],
            '🛡️',
            null,
            'imunidade_anjo'
        );
    }

    $_SESSION['fase_semana'] = 'paredao';

    header("Location: jogo.php");
    exit;
}
