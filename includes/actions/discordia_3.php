<?php
/** @var array $jogadores */
/** @var string $meuNome */

// Todas as ações de Discórdia passam por validação e pelo estado de sessão.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return;
$postD3 = isset($_POST['fazer_discordia']) || isset($_POST['d3_argumentar']) || isset($_POST['d3_treplicar']);
if (!$postD3) return;
if (($_SESSION['fase_semana'] ?? '') !== 'discordia' || bbbRetaFinalSemDiscordia($jogadores)
    || !empty($_SESSION['discordia_feito']) || !d3Ativo($jogadores,$meuNome)) {
    header('Location: jogo.php'); exit;
}
$p = $_SESSION['d3_pendente'] ?? null;

if (isset($_POST['fazer_discordia'])) {
    // Um convite já iniciado não pode ser substituído por outro POST da página.
    if (is_array($p)) { header('Location: jogo.php'); exit; }
    $tema = (string)($_SESSION['tema_discordia'] ?? 'sonso');
    $escolha = d3ValidaEscolha($_POST,$jogadores,$meuNome,$tema);
    if ($escolha === null) {
        $_SESSION['evento_extra'][] = '⚠️ Confira sua escolha do Jogo da Discórdia.';
        header('Location: jogo.php'); exit;
    }
    $token = bin2hex(random_bytes(16));
    $_SESSION['d3_pendente'] = [
        'semana'=>d3Semana(),'token'=>$token,'etapa'=>'argumento', 'tema'=>$tema,
        'dados'=>$escolha['dados'],'alvo'=>$escolha['alvo'], 'positivo'=>$escolha['positivo'],
        'fatos'=>d3FatosPublicos($meuNome,$escolha['alvo'],d3Semana()),
    ];
    header('Location: jogo.php'); exit;
}

if (!is_array($p) || !hash_equals((string)($p['token'] ?? ''),(string)($_POST['d3_token'] ?? ''))
    || (int)($p['semana'] ?? 0) !== d3Semana()) { header('Location: jogo.php'); exit; }

if (isset($_POST['d3_argumentar'])) {
    if (($p['etapa'] ?? '') !== 'argumento') { header('Location: jogo.php'); exit; }
    $argumento = (string)($_POST['d3_argumento'] ?? '');
    $opcoes = d3OpcoesArgumento($p);
    if (!isset($opcoes[$argumento])) { header('Location: jogo.php'); exit; }
    $p['argumento'] = $argumento;
    $p['fala'] = (string)$opcoes[$argumento]['texto'];
    $p['resposta'] = d3RespostaNPC($jogadores,$p,$argumento);
    $p['etapa'] = 'treplica';
    $_SESSION['d3_pendente'] = $p;
    header('Location: jogo.php'); exit;
}

if (isset($_POST['d3_treplicar'])) {
    if (($p['etapa'] ?? '') !== 'treplica') { header('Location: jogo.php'); exit; }
    $replica = (string)($_POST['d3_replica'] ?? '');
    $opcoes = d3OpcoesTreplica(!empty($p['positivo']));
    if (!isset($opcoes[$replica])) { header('Location: jogo.php'); exit; }
    d3AplicarReplica($jogadores,$p,$replica,$meuNome);
    $_SESSION['jogadores'] = $jogadores;
    $ultima = [
        'semana'=>d3Semana(),'alvo'=>$p['alvo'], 'tema'=>$p['tema'],
        'argumento'=>$p['fala'], 'resposta'=>$p['resposta']['fala'],
        'treplica'=>$opcoes[$replica]['fala'],
    ];
    $_SESSION['d3_ultimo_embate'] = $ultima;
    $_SESSION['evento_extra'][] = '🎤 '.$meuNome.' confrontou '.$p['alvo'].' no Jogo da Discórdia. '.$p['alvo'].' respondeu; '.$meuNome.' concluiu: “'.$opcoes[$replica]['fala'].'”';
    // Reutiliza o motor original para o efeito do tema, dinâmica dos demais NPCs e avanço.
    // Os campos válidos vieram da 1ª etapa, nunca do formulário de tréplica.
    $_POST = $p['dados'];
    $_POST['fazer_discordia'] = '1';
    // Destrava apenas esta finalização; F5 não reproduz POST após PRG.
    unset($_SESSION['d3_pendente']);
    return;
}
