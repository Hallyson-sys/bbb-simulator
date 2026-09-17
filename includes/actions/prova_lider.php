<?php

/* =========================================================
   👑 ACTION — PROVA DO LÍDER V2
   ========================================================= */

require_once __DIR__ . '/../logica/premios_provas.php';

/* =========================================================
   🛡️ GARANTIR DADOS
   ========================================================= */
if (
    !isset($jogadores) ||
    !is_array($jogadores) ||
    !isset($meuNome) ||
    !isset($tipoProva) ||
    !isset($prova) ||
    !is_array($prova)
) {
    if (isset($_POST['jogar']) || isset($_POST['jogar_estrategia'])) {
        $_SESSION['evento_extra'][] =
            "⚠️ Não foi possível processar a Prova do Líder.";

        header("Location: jogo.php");
        exit;
    }

    return;
}

/* =========================================================
   🎯 CAMINHO ESTRATÉGICO — MULTIETAPAS
   ========================================================= */
if (isset($_POST['jogar_estrategia'])) {

    if ((int)$tipoProva !== 13) {
        header("Location: prova_lider.php");
        exit;
    }

    $estado = prepararCaminhoEstrategicoLider();
    $etapa = (int)($estado['etapa'] ?? 1);
    $pontos = (int)($estado['pontos'] ?? 50);
    $escolha = (string)($_POST['escolha_estrategia'] ?? '');

    $opcoesValidas = opcoesCaminhoEstrategicoLider($etapa);

    if ($escolha === '' || !isset($opcoesValidas[$escolha])) {
        $_SESSION['lider_estrategia_feedback'] =
            '⚠️ Escolha uma opção válida antes de continuar.';

        header("Location: prova_lider.php");
        exit;
    }

    $resultado = aplicarEscolhaCaminhoEstrategicoLider(
        $etapa,
        $escolha,
        $pontos
    );

    $_SESSION['lider_estrategia_pontos'] =
        (int)$resultado['pontos'];

    $_SESSION['lider_estrategia_feedback'] =
        (string)$resultado['texto'];

    if (
        !isset($_SESSION['lider_estrategia_historico']) ||
        !is_array($_SESSION['lider_estrategia_historico'])
    ) {
        $_SESSION['lider_estrategia_historico'] = [];
    }

    $_SESSION['lider_estrategia_historico'][] = [
        'etapa' => $etapa,
        'escolha' => $escolha,
        'pontos' => (int)$resultado['pontos'],
        'delta' => (int)$resultado['delta'],
        'texto' => (string)$resultado['texto']
    ];

    /* Etapas 1 e 2 continuam na própria prova. */
    if ($etapa < 3) {
        $_SESSION['lider_estrategia_etapa'] = $etapa + 1;

        header("Location: prova_lider.php");
        exit;
    }

    /* Etapa 3 finaliza a disputa. */
    $lider = finalizarCaminhoEstrategicoLider(
        $jogadores,
        $meuNome,
        (int)$resultado['pontos']
    );

    registrarResumoProvaLiderInterativa();

    registrarLiderDaRodada(
        $jogadores,
        $lider,
        $meuNome
    );

    premiarMoedasPorProva(
        'lider',
        $lider,
        $meuNome,
        15
    );

    header("Location: jogo.php");
    exit;
}

/* =========================================================
   🔢 DESEMPATE DA SEQUÊNCIA LÓGICA
   ========================================================= */
if (isset($_POST['jogar_desempate_logica'])) {

    if (
        (int)$tipoProva !== 12 ||
        empty($_SESSION['lider_logica_desempate_pendente'])
    ) {
        header("Location: prova_lider.php");
        exit;
    }

    $lider=resolverDesempateLogicaLider(
        $jogadores,
        $meuNome,
        $_POST['resposta_desempate_logica']??''
    );

    if($lider===null||$lider===''){
        $_SESSION['evento_extra'][]=
            "⚠️ Não foi possível concluir o desempate da Sequência Lógica.";

        header("Location: prova_lider.php");
        exit;
    }

    registrarResumoProvaLiderInterativa();

    registrarLiderDaRodada(
        $jogadores,
        $lider,
        $meuNome
    );

    premiarMoedasPorProva(
        'lider',
        $lider,
        $meuNome,
        15
    );

    header("Location: jogo.php");
    exit;
}


/* =========================================================
   OUTRAS PROVAS
   ========================================================= */
if (!isset($_POST['jogar'])) {
    return;
}

$lider = '';

/* ⚡ REFLEXO */
if ((int)$tipoProva === 2) {
    $lider = resolverReflexoLider(
        $jogadores,
        $meuNome,
        $_POST['tempo_reacao_ms'] ?? 5000
    );
}

/* 🧠 MEMÓRIA */
elseif ((int)$tipoProva === 3) {
    $lider = resolverMemoriaLider(
        $jogadores,
        $meuNome,
        $_POST
    );
}

/* 🔢 SEQUÊNCIA LÓGICA */
elseif ((int)$tipoProva === 12) {
    $lider = resolverSequenciaLogicaLider(
        $jogadores,
        $meuNome,
        $_POST
    );

    /*
     * Empate no topo:
     * volta para prova_lider.php e mostra a 4ª questão.
     */
    if(
        $lider===null &&
        !empty($_SESSION['lider_logica_desempate_pendente'])
    ){
        header("Location: prova_lider.php");
        exit;
    }
}

/* 🎯 / 🍀 PROVAS TRADICIONAIS */
else {
    $lider = resolverProvaLider(
        $jogadores,
        $meuNome,
        $tipoProva,
        $_POST['escolha'] ?? '',
        $prova
    );
}

if (in_array((int)$tipoProva, [2, 3, 12], true)) {
    registrarResumoProvaLiderInterativa();
}

registrarLiderDaRodada(
    $jogadores,
    $lider,
    $meuNome
);

premiarMoedasPorProva(
    'lider',
    $lider,
    $meuNome,
    15
);

header("Location: jogo.php");
exit;
