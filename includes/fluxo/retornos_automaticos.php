<?php

/* =========================================================
   🔄 RETORNOS AUTOMÁTICOS DAS FASES

   IMPORTANTE:
   Este arquivo NÃO faz redirect para jogo.php.
   Ele apenas sincroniza a fase salva na sessão.
   Isso evita loops de redirecionamento.
   ========================================================= */

$faseAtual = $_SESSION['fase_semana'] ?? '';


/* =========================================================
   👑 PROVA DO LÍDER FINALIZADA
   ========================================================= */

if (
    $faseAtual == 'lider' &&
    isset($_SESSION['lider']) &&
    trim((string)$_SESSION['lider']) != ''
) {

    $liderAtual = $_SESSION['lider'];

    /*
     * Se você é o Líder, vai para a escolha manual do VIP.
     * Se um NPC é o Líder, entra primeiro na tela de revelação.
     * Assim o VIP/Xepa não aparece no Ao Vivo antes do clique.
     */
    if (nomeIgual($liderAtual, $meuNome)) {
        $_SESSION['fase_semana'] = 'vip_xepa';
    } else {
        $_SESSION['fase_semana'] = 'vip_xepa_revelar';
    }

    $faseAtual = $_SESSION['fase_semana'];
}


/* =========================================================
   🧯 RECUPERAÇÃO DE SAVE ANTIGO/TRAVADO NO VIP/XEPA
   ========================================================= */

if (
    $faseAtual == 'vip_xepa' &&
    isset($_SESSION['lider']) &&
    !nomeIgual($_SESSION['lider'], $meuNome)
) {

    if (isset($_SESSION['vip_definido'])) {
        $_SESSION['fase_semana'] = 'anjo';
        $faseAtual = 'anjo';
    } else {
        $_SESSION['fase_semana'] = 'vip_xepa_revelar';
        $faseAtual = 'vip_xepa_revelar';
    }
}

/*
 * Se uma tentativa anterior já calculou o VIP, mas ficou
 * parada em vip_xepa_revelar, apenas segue para o Anjo.
 * Não recalcula nem duplica mensagens no Ao Vivo.
 */
if (
    $faseAtual == 'vip_xepa_revelar' &&
    isset($_SESSION['vip_definido'])
) {
    $_SESSION['fase_semana'] = 'anjo';
    $faseAtual = 'anjo';
}


/* =========================================================
   😇 PROVA DO ANJO FINALIZADA
   ========================================================= */

if (
    $faseAtual == 'anjo' &&
    isset($_SESSION['anjo']) &&
    isset($_SESSION['prova_anjo_finalizada']) &&
    !isset($_SESSION['monstro_definido'])
) {
    $_SESSION['fase_semana'] = 'monstro';
    $faseAtual = 'monstro';
}


/* =========================================================
   ☎️ BIG FONE FINALIZADO
   ========================================================= */

if (
    $faseAtual == 'bigfone' &&
    isset($_SESSION['bigfone_feito'])
) {

    if (
        function_exists('prepararSorteioPoderCuringa') &&
        prepararSorteioPoderCuringa($jogadores)
    ) {
        $_SESSION['fase_semana'] = 'poder_curinga';
        $faseAtual = 'poder_curinga';
    } else {
        $_SESSION['fase_semana'] = 'interacoes_2';
        $_SESSION['acoes_restantes'] = 3;
        $faseAtual = 'interacoes_2';
    }
}


/* =========================================================
   🔄 SINCRONIZAR VARIÁVEL LOCAL
   ========================================================= */

$fase = $_SESSION['fase_semana'] ?? $faseAtual;
