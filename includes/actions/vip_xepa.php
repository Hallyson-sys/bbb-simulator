<?php

/* =========================================================
   👑 ACTION — REVELAR VIP E XEPA DO LÍDER NPC

   Esta action só roda quando o usuário clica em
   "Ver VIP e Xepa do Líder".
   ========================================================= */

if (!isset($_POST['ver_vip_xepa'])) {
    return;
}

$faseAtual = $_SESSION['fase_semana'] ?? ($fase ?? '');
$lider = $_SESSION['lider'] ?? '';


/* =========================================================
   🛡️ VALIDAÇÕES
   ========================================================= */

if (
    !in_array($faseAtual, ['vip_xepa_revelar', 'vip_xepa'], true) ||
    $lider == ''
) {
    header('Location: jogo.php');
    exit;
}

/* Se você é o Líder, a escolha continua sendo manual. */
if (nomeIgual($lider, $meuNome)) {
    $_SESSION['fase_semana'] = 'vip_xepa';
    header('Location: jogo.php');
    exit;
}

/* Evita calcular ou registrar o mesmo VIP duas vezes. */
if (isset($_SESSION['vip_definido'])) {
    $_SESSION['fase_semana'] = 'anjo';
    header('Location: jogo.php');
    exit;
}

if (!isset($_SESSION['evento_extra']) || !is_array($_SESSION['evento_extra'])) {
    $_SESSION['evento_extra'] = [];
}


/* =========================================================
   🧠 NPC ESCOLHE O VIP PELA RELAÇÃO COM A CASA
   ========================================================= */

$afinidades = [];

foreach ($jogadores as $j) {

    $nome = $j['nome'] ?? '';

    if ($nome == '' || nomeIgual($nome, $lider)) {
        continue;
    }

    $afinidade = calcularRelacaoIA(
        $jogadores,
        $lider,
        $nome,
        $meuNome
    );

    $afinidades[$nome] = $afinidade;
}

arsort($afinidades);

$vipEscolhidos = array_slice(
    array_keys($afinidades),
    0,
    max(0, (int)$qtdVIP)
);


/* =========================================================
   🟡 APLICAR VIP / 🍞 XEPA
   ========================================================= */

foreach ($jogadores as &$j) {

    if (!isset($j['status']) || !is_array($j['status'])) {
        $j['status'] = [];
    }

    if (!isset($j['estatisticas']) || !is_array($j['estatisticas'])) {
        $j['estatisticas'] = [];
    }

    $j['status']['vip'] = false;
    $j['status']['xepa'] = false;

    $nome = $j['nome'] ?? '';

    /* O próprio Líder sempre está no VIP. */
    if (nomeIgual($nome, $lider)) {
        $j['status']['vip'] = true;
        $j['estatisticas']['vip'] = ($j['estatisticas']['vip'] ?? 0) + 1;
        continue;
    }

    if (in_array($nome, $vipEscolhidos, true)) {
        $j['status']['vip'] = true;
        $j['estatisticas']['vip'] = ($j['estatisticas']['vip'] ?? 0) + 1;
    } else {
        $j['status']['xepa'] = true;
        $j['estatisticas']['xepa'] = ($j['estatisticas']['xepa'] ?? 0) + 1;
    }
}

unset($j);

if (function_exists('garantirMeuJogadorNaLista')) {
    garantirMeuJogadorNaLista($jogadores);
}

$_SESSION['jogadores'] = array_values($jogadores);
$_SESSION['vip_definido'] = true;


/* =========================================================
   📢 ENVIAR RESULTADO PARA O AO VIVO
   ========================================================= */

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
    "🟡 VIP: " . implode(', ', $vipLista) . ".";

$_SESSION['evento_extra'][] =
    "🍞 Xepa: " . implode(', ', $xepaLista) . ".";


/* =========================================================
   😇 PREPARAR A PRÓXIMA FASE
   ========================================================= */

/* Remove possíveis restos da Prova do Anjo anterior. */
unset(
    $_SESSION['anjo'],
    $_SESSION['imune'],
    $_SESSION['monstro'],
    $_SESSION['monstro_definido'],
    $_SESSION['prova_anjo_finalizada'],
    $_SESSION['imunizacao_anjo_feita']
);

foreach ($jogadores as &$j) {

    if (!isset($j['status']) || !is_array($j['status'])) {
        $j['status'] = [];
    }

    $j['status']['anjo'] = false;
    $j['status']['imune'] = false;
    $j['status']['monstro'] = false;
}

unset($j);

$_SESSION['jogadores'] = array_values($jogadores);
$_SESSION['fase_semana'] = 'anjo';

header('Location: jogo.php');
exit;
