<?php

if (!modoTestePermitido()) {
    http_response_code(403);
    exit('Modo de testes disponível apenas localmente.');
}


/* =========================================================
   🔥 RODADA
   ========================================================= */
if (isset($_POST['dev_rodada'])) {

    $novaRodada =
        max(
            1,
            (int)($_POST['rodada'] ?? 1)
        );

    $_SESSION['rodada'] =
        $novaRodada;

    registrarEventoModoTeste(
        "Rodada alterada para $novaRodada."
    );

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   🪙 MOEDAS
   ========================================================= */
if (isset($_POST['dev_moedas'])) {

    $_SESSION['moedas_publico'] =
        max(
            0,
            (int)(
                $_POST['moedas']
                ?? 0
            )
        );

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   ➕ 100 MOEDAS
   ========================================================= */
if (isset($_POST['dev_add_moedas'])) {

    $_SESSION['moedas_publico'] =
        max(
            0,
            (int)(
                $_SESSION['moedas_publico']
                ?? 0
            )
        ) + 100;

    registrarEventoModoTeste(
        '100 moedas adicionadas.'
    );

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   🎮 TROCAR FASE
   ========================================================= */
if (isset($_POST['dev_fase'])) {

    $fase =
        $_POST['fase'] ?? '';

    $permitidas =
        fasesDisponiveisModoTeste();

    if (isset($permitidas[$fase])) {

        $_SESSION['fase_semana'] =
            $fase;

        if (
            str_contains(
                $fase,
                'interacoes'
            )
        ) {
            $_SESSION['acoes_restantes'] =
                3;
        }

        registrarEventoModoTeste(
            "Fase alterada para $fase."
        );
    }

    header('Location: jogo.php');
    exit;
}


/* =========================================================
   👑 DEFINIR LÍDER
   ========================================================= */
if (isset($_POST['dev_lider'])) {

    $nome =
        trim(
            $_POST['participante']
            ?? ''
        );

    if ($nome !== '') {

        definirStatusParticipanteModoTeste(
            $jogadores,
            'lider',
            $nome
        );

        $_SESSION['lider'] =
            $nome;

        $_SESSION['jogadores'] =
            $jogadores;

        registrarEventoModoTeste(
            "$nome foi definido como Líder."
        );
    }

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   😇 DEFINIR ANJO
   ========================================================= */
if (isset($_POST['dev_anjo'])) {

    $nome =
        trim(
            $_POST['participante']
            ?? ''
        );

    if ($nome !== '') {

        definirStatusParticipanteModoTeste(
            $jogadores,
            'anjo',
            $nome
        );

        $_SESSION['anjo'] =
            $nome;

        $_SESSION['prova_anjo_finalizada'] =
            true;

        $_SESSION['jogadores'] =
            $jogadores;

        registrarEventoModoTeste(
            "$nome foi definido como Anjo."
        );
    }

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   🛡️ DEFINIR IMUNE
   ========================================================= */
if (isset($_POST['dev_imune'])) {

    $nome =
        trim(
            $_POST['participante']
            ?? ''
        );

    if ($nome !== '') {

        definirStatusParticipanteModoTeste(
            $jogadores,
            'imune',
            $nome
        );

        $_SESSION['imune'] =
            $nome;

        $_SESSION['jogadores'] =
            $jogadores;

        registrarEventoModoTeste(
            "$nome foi definido como imune."
        );
    }

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   🚨 ADICIONAR AO PAREDÃO
   ========================================================= */
if (isset($_POST['dev_add_paredao'])) {

    $nome =
        trim(
            $_POST['participante']
            ?? ''
        );

    if (!isset($_SESSION['paredao'])) {
        $_SESSION['paredao'] = [];
    }

    if (
        $nome !== '' &&
        !in_array(
            $nome,
            $_SESSION['paredao'],
            true
        )
    ) {
        $_SESSION['paredao'][] =
            $nome;
    }

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   🧹 LIMPAR PAREDÃO
   ========================================================= */
if (isset($_POST['dev_limpar_paredao'])) {

    unset(
        $_SESSION['paredao'],
        $_SESSION['paredao_formado'],
        $_SESSION['votos_paredao']
    );

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   🚨 FORÇAR PAREDÃO FALSO
   ========================================================= */
if (isset($_POST['dev_paredao_falso'])) {

    $_SESSION['forcar_paredao_falso'] =
        true;

    registrarEventoModoTeste(
        'O próximo resultado será um Paredão Falso.'
    );

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   🏠 FORÇAR CASA DE VIDRO 2.0
   ========================================================= */
if (isset($_POST['dev_casa_vidro'])) {

    $_SESSION['rodada'] = 3;
    $_SESSION['fase_semana'] = 'queridometro';
    $_SESSION['acoes_restantes'] = 3;

    foreach (
        array_keys($_SESSION)
        as $chaveSessao
    ) {
        if (
            strpos(
                (string)$chaveSessao,
                'casa_vidro_'
            ) === 0
        ) {
            unset($_SESSION[$chaveSessao]);
        }
    }

    $_SESSION['casa_vidro_versao'] = 2;
    $_SESSION['casa_vidro_estado'] = 'nao_decidida';
    $_SESSION['forcar_casa_vidro'] = true;

    unset(
        $_SESSION['queridometro_feito'],
        $_SESSION['queridometro_resultado'],
        $_SESSION['confessionario_feito'],
        $_SESSION['confessionario_falas']
    );

    registrarEventoModoTeste(
        'Casa de Vidro 2.0 forçada para o início da Rodada 3.'
    );

    header('Location: jogo.php');
    exit;
}


/* =========================================================
   ☎️ BIG FONE
   ========================================================= */
if (isset($_POST['dev_bigfone'])) {

    $_SESSION['fase_semana'] =
        'bigfone';

    unset(
        $_SESSION['bigfone_feito']
    );

    header('Location: big_fone.php');
    exit;
}


/* =========================================================
   🏆 IR PARA FINAL
   ========================================================= */
if (isset($_POST['dev_final'])) {

    $jogadores =
        array_values(
            $_SESSION['jogadores']
            ?? []
        );

    if (count($jogadores) > 3) {

        $jogadores =
            array_slice(
                $jogadores,
                0,
                3
            );

        $_SESSION['jogadores'] =
            $jogadores;
    }

    unset(
        $_SESSION['ranking_final'],
        $_SESSION['percentuais_final'],
        $_SESSION['pontuacoes_final'],
        $_SESSION['ordem_visual_final']
    );

    $_SESSION['fase_semana'] =
        'finalistas';

    header('Location: final.php');
    exit;
}


/* =========================================================
   📣 LIMPAR AO VIVO
   ========================================================= */
if (isset($_POST['dev_limpar_ao_vivo'])) {

    $_SESSION['evento_extra'] = [];

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   📱 LIMPAR FEED
   ========================================================= */
if (isset($_POST['dev_limpar_feed'])) {

    $_SESSION['feed_publico'] = [];

    unset(
        $_SESSION['feed_publico_historico_textos'],
        $_SESSION['feed_publico_historico_contextos']
    );

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   🧹 RESETAR ESTADO DA SEMANA
   ========================================================= */
if (isset($_POST['dev_reset_semana'])) {

    limparEstadoSemanaModoTeste();

    $_SESSION['fase_semana'] =
        'queridometro';

    $_SESSION['acoes_restantes'] =
        3;

    header('Location: modo_teste.php');
    exit;
}
