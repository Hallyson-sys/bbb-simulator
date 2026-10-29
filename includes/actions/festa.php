<?php
require_once __DIR__ . '/../logica/relatos_ao_vivo.php';


/** @var string $fase */
/** @var array $jogadores */
/** @var string $meuNome */

if (
    !function_exists(
        'registrarMemoriaSocialNPC'
    )
) {
    require_once __DIR__ . '/../logica/inteligencia_npc.php';
}

/* Ações de estratégia da festa: formulário próprio, com validação server-side. */
if ($fase === 'festa') {
    if (!isset($_SESSION['es2_festa_rodada']) || $_SESSION['es2_festa_rodada'] !== es2Rodada()) {
        $_SESSION['es2_festa_rodada'] = es2Rodada();
        // Negociações pendentes da rodada passada não continuam em uma nova Festa.
        if(!empty($_SESSION['es2_negociacao']) && (int)($_SESSION['es2_negociacao']['rodada']??0)!==es2Rodada()) {
            unset($_SESSION['es2_negociacao']);
            if(($_SESSION['acao_festa_selecionada']??'')==='negociar_alvo') unset($_SESSION['acao_festa_selecionada']);
        }
        // Para saves iniciados antes da atualização, migrar o orçamento inicial.
        if (($_SESSION['acoes_festa'] ?? 2) === 2) $_SESSION['acoes_festa'] = 3;
    }
    if (!isset($_SESSION['es2_csrf'])) $_SESSION['es2_csrf'] = bin2hex(random_bytes(16));
}
if ($fase === 'festa' && isset($_POST['es2_festa_acao'])) {
    $op=(string)$_POST['es2_festa_acao'];
    $token=(string)($_POST['es2_token']??'');
    if (!hash_equals($_SESSION['es2_csrf']??'', $token) || (int)($_SESSION['acoes_festa']??0)<=0) {
        header('Location: jogo.php');exit;
    }
    $consumir=false; $feedback='';
    unset($_SESSION['es2_festa_feedback']);
    if ($op==='sondar') {
        $alvo=(string)($_POST['es2_npc']??'');
        $forma=(string)($_POST['es2_abordagem']??'discreta');
        if(in_array($forma,['direta','discreta','troca'],true) && es2Participante($jogadores,$alvo,$meuNome)){
            $feedback=es2SondarVoto($jogadores,$meuNome,$alvo,$forma);$consumir=true;
        }
    } elseif ($op==='negociar_inicio') {
        $nomes=$_POST['es2_convidados']??[];
        if(es2IniciarNegociacao($jogadores,$meuNome,$nomes)){
            $_SESSION['acao_festa_selecionada']='negociar_alvo';
        }else $feedback='⚠️ Escolha de um a três convidados válidos.';
    } elseif ($op==='negociar_fechar') {
        $alvo=(string)($_POST['es2_alvo']??'');
        $feedback=es2FinalizarNegociacao($jogadores,$meuNome,$alvo);
        $consumir=$feedback!=='';
    } elseif ($op==='negociar_cancelar') {
        if(!empty($_SESSION['es2_negociacao'])) {
            $consumir=true;
            $feedback='🚪 Você encerrou a articulação sem propor um alvo. A conversa consumiu uma ação da Festa.';
            $_SESSION['evento_extra'][]="🗣️ $meuNome procurou pessoas para conversar sobre votos, mas não fechou nenhum acordo.";
        }
        unset($_SESSION['es2_negociacao'],$_SESSION['acao_festa_selecionada']);
    }
    if($consumir){
        $_SESSION['acoes_festa']--;
        rc2ProcessarMomento($jogadores, $meuNome, 'festa',
            'estrategia_' . (3 - (int)$_SESSION['acoes_festa']),
            (int)$_SESSION['acoes_festa'] <= 0);
        $_SESSION['jogadores']=$jogadores;
        unset($_SESSION['acao_festa_selecionada']);
        if($_SESSION['acoes_festa']<=0){
            if(!isset($_SESSION['npc_festa_feita'])){
                foreach(gerarAcoesFestaNPC($jogadores,$meuNome,2) as $ev)$_SESSION['evento_extra'][]=$ev;
                foreach(atualizarAliancasAutomaticas($jogadores,$meuNome) as $ev)$_SESSION['evento_extra'][]=$ev;
                $_SESSION['npc_festa_feita']=true;$_SESSION['jogadores']=$jogadores;
            }
            if(function_exists('dsConcluirFesta')) dsConcluirFesta($jogadores);
            $_SESSION['fase_semana'] = bbbFaseAposFesta($jogadores);
            $_SESSION['acoes_festa']=3;
        }
    }
    if($feedback!=='')$_SESSION['es2_festa_feedback']=$feedback;
    header('Location: jogo.php'); exit;
}

if (isset($_POST['acao_festa']) && $fase == 'festa') {

$acao = $_POST['acao_festa'];
$alvo = $_POST['alvo_festa'] ?? '';

$evento = "";
$flertouCorrespondido = false;
$aprofundouRomance = false;

/* 🤝 Aproximar */
if ($acao == 'aproximar' && $alvo) {

    $_SESSION['relacoes_jogador'][$alvo] =
        ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 5;

    alterarAfinidade($jogadores, $meuNome, $alvo, 5, -2, 3);
    alterarAfinidade($jogadores, $alvo, $meuNome, 5, -2, 3);

    $evento =
        es3RelatoAoVivo("festa_aproximar", $meuNome, $alvo, "festa");
}

/* 📺 VT */
if ($acao == 'vt') {

    $valor = impactoPopularidadePorPersonalidade($jogadores, $meuNome, "vt", true);

    if ($valor >= 0) {
        $evento =
            es3RelatoAoVivo("vt", $meuNome, "", "festa");
    } else {
        $evento =
            es3RelatoAoVivo("vt", $meuNome, "", "festa");
    }
}

/* 😘 FLERTE */
if ($acao == 'flertar' && $alvo) {

    $afinidade = $_SESSION['relacoes_jogador'][$alvo] ?? 0;

    if ($afinidade >= 15) {

        if (rand(1, 100) <= 70) {

            $_SESSION['relacoes_jogador'][$alvo] =
                ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 8;

            alterarRomance($jogadores, $meuNome, $alvo, 10);
            alterarRomance($jogadores, $alvo, $meuNome, 8);
            aplicarCiumesSeTiverCasal($jogadores, $meuNome, $alvo, $_SESSION['evento_extra']);

            alterarAfinidade($jogadores, $meuNome, $alvo, 8, -2, 5);
            alterarAfinidade($jogadores, $alvo, $meuNome, 5, -2, 3);

            impactoPopularidadePorPersonalidade($jogadores, $meuNome, "romance", true);
            $flertouCorrespondido = true;

            $evento =
                es3RelatoAoVivo("flertar", $meuNome, $alvo, "festa");
        } else {

            $_SESSION['relacoes_jogador'][$alvo] =
                ($_SESSION['relacoes_jogador'][$alvo] ?? 0) - 5;

            alterarAfinidade($jogadores, $meuNome, $alvo, -5, 3, -3);
            alterarAfinidade($jogadores, $alvo, $meuNome, -5, 3, -3);

            alterarPopularidadePublica($jogadores, $meuNome, -4, 0, "levou um fora na festa", true);

            $evento =
                es3RelatoAoVivo("flertar", $meuNome, $alvo, "festa") . " O clima ficou desconfortável.";
        }
    } else {

        $_SESSION['relacoes_jogador'][$alvo] =
            ($_SESSION['relacoes_jogador'][$alvo] ?? 0) - 3;

        alterarAfinidade($jogadores, $meuNome, $alvo, -3, 2, -2);

        $evento =
            es3RelatoAoVivo("flertar", $meuNome, $alvo, "festa") . " O interesse não foi correspondido.";
    }
}

/* 😈 PROVOCAR */
if ($acao == 'provocar' && $alvo) {

    $_SESSION['relacoes_jogador'][$alvo] =
        ($_SESSION['relacoes_jogador'][$alvo] ?? 0) - 8;

    alterarAfinidade($jogadores, $meuNome, $alvo, -8, 8, -5);
    alterarAfinidade($jogadores, $alvo, $meuNome, -8, 8, -5);

    $popularidadeAlvo = 50;

    foreach ($jogadores as $j) {

        if ($j['nome'] == $alvo) {
            $popularidadeAlvo = $j['popularidade'] ?? 50;
        }
    }

    if ($popularidadeAlvo < 40) {

        alterarPopularidadeMotivo($jogadores, $meuNome, 3, 8, "provocou alguém cancelado pelo público");
    } else {

        alterarPopularidadeMotivo($jogadores, $meuNome, -8, -3, "provocou alguém querido pelo público");
    }

    $evento =
        es3RelatoAoVivo("discutir", $meuNome, $alvo, "festa");
}

/* 💃 DANÇAR */
if ($acao == 'dancar' && $alvo) {

    $_SESSION['relacoes_jogador'][$alvo] =
        ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 6;

    alterarAfinidade($jogadores, $meuNome, $alvo, rand(3, 7), -2, 3);
    alterarAfinidade($jogadores, $alvo, $meuNome, rand(3, 7), -2, 3);

    alterarRomance($jogadores, $meuNome, $alvo, 5);
    alterarRomance($jogadores, $alvo, $meuNome, 3);

    $evento =
        es3RelatoAoVivo("dancar", $meuNome, $alvo, "festa");
}

/* 💕 ELOGIAR */
if ($acao == 'elogiar' && $alvo) {

    $_SESSION['relacoes_jogador'][$alvo] =
        ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 4;

    alterarRomance($jogadores, $meuNome, $alvo, 6);
    alterarRomance($jogadores, $alvo, $meuNome, 4);
    alterarAfinidade($jogadores, $meuNome, $alvo, 4, -2, 4);
    alterarAfinidade($jogadores, $alvo, $meuNome, 3, -1, 3);

    $evento = es3RelatoAoVivo("flertar", $meuNome, $alvo, "festa");
}

/* 💬 CONVERSAR SOBRE SENTIMENTOS */
if ($acao == 'sentimentos' && $alvo) {

    $romanceAtual = obterRomance($jogadores, $meuNome, $alvo);

    if ($romanceAtual >= 30) {
        $_SESSION['relacoes_jogador'][$alvo] =
            ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 6;

        alterarRomance($jogadores, $meuNome, $alvo, 10);
        alterarRomance($jogadores, $alvo, $meuNome, 8);
        alterarAfinidade($jogadores, $meuNome, $alvo, 6, -3, 8);
        alterarAfinidade($jogadores, $alvo, $meuNome, 5, -2, 6);
        $aprofundouRomance = true;

        $evento = es3RelatoAoVivo("festa_aproximar", $meuNome, $alvo, "festa");
    } else {
        alterarRomance($jogadores, $meuNome, $alvo, 2);
        $evento = es3RelatoAoVivo("festa_aproximar", $meuNome, $alvo, "festa");
    }
}

/* 🌙 PASSAR A NOITE CONVERSANDO */
if ($acao == 'noite_conversando' && $alvo) {

    $romanceAtual = obterRomance($jogadores, $meuNome, $alvo);

    if ($romanceAtual >= 30) {
        $_SESSION['relacoes_jogador'][$alvo] =
            ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 5;

        alterarRomance($jogadores, $meuNome, $alvo, 12);
        alterarRomance($jogadores, $alvo, $meuNome, 10);
        alterarAfinidade($jogadores, $meuNome, $alvo, 5, -2, 7);
        alterarAfinidade($jogadores, $alvo, $meuNome, 5, -2, 7);
        $aprofundouRomance = true;

        $evento = es3RelatoAoVivo("festa_aproximar", $meuNome, $alvo, "festa");
    } else {
        $evento = es3RelatoAoVivo("festa_aproximar", $meuNome, $alvo, "festa");
    }
}

/* 💍 PEDIR EM NAMORO */
if ($acao == 'pedir_namoro' && $alvo) {

    $romanceAtual = obterRomance($jogadores, $meuNome, $alvo);
    $afinidadeAtual = $_SESSION['relacoes_jogador'][$alvo] ?? 0;

    if (parceiroAtual($meuNome) != '') {
        $evento = "💍 $meuNome já está namorando com " . parceiroAtual($meuNome) . ".";
    } elseif (parceiroAtual($alvo) != '') {
        $evento = "💍 $alvo já está em um casal com " . parceiroAtual($alvo) . ".";
    } elseif ($romanceAtual < 60) {
        $evento = "💍 $meuNome pensou em pedir $alvo em namoro, mas o romance ainda precisa chegar em 60.";
    } else {
        $chance = chanceAceitarNamoro($afinidadeAtual, $romanceAtual);

        if (rand(1, 100) <= $chance) {
            registrarCasal($meuNome, $alvo);
            impactoTorcidaOculto($jogadores, $meuNome, 'casal');
            impactoTorcidaOculto($jogadores, $alvo, 'casal');
            alterarRomance($jogadores, $meuNome, $alvo, 10);
            alterarRomance($jogadores, $alvo, $meuNome, 10);
            alterarAfinidade($jogadores, $meuNome, $alvo, 8, -4, 10);
            alterarAfinidade($jogadores, $alvo, $meuNome, 8, -4, 10);
            ajustarRelacaoJogador($alvo, 8);

            $evento = "💍 $meuNome pediu $alvo em namoro... e $alvo aceitou! Nasce um casal na casa.";

            if (function_exists('registrarHistoricoTemporada')) {
                registrarHistoricoTemporada(
                    'romance_oficial',
                    'Romance oficial',
                    "$meuNome pediu $alvo em namoro, e o pedido foi aceito.",
                    [$meuNome, $alvo],
                    '💕',
                    null,
                    'namoro'
                );
            }
        } else {
            alterarRomance($jogadores, $meuNome, $alvo, -6);
            alterarRomance($jogadores, $alvo, $meuNome, -4);
            ajustarRelacaoJogador($alvo, -4);

            $evento = "💔 $meuNome pediu $alvo em namoro, mas $alvo preferiu ir com calma.";
        }
    }
}

/* 🛏️ PASSAR A NOITE JUNTOS NO QUARTO */
if ($acao == 'passar_noite_quarto' && $alvo) {

    $romanceAtual = obterRomance($jogadores, $meuNome, $alvo);

    if ($romanceAtual >= 60) {
        $_SESSION['relacoes_jogador'][$alvo] =
            ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 7;

        alterarRomance($jogadores, $meuNome, $alvo, 14);
        alterarRomance($jogadores, $alvo, $meuNome, 12);
        alterarAfinidade($jogadores, $meuNome, $alvo, 7, -3, 8);
        alterarAfinidade($jogadores, $alvo, $meuNome, 7, -3, 8);
        alterarPopularidadePublica($jogadores, $meuNome, -5, 6, "viveu um momento íntimo de romance no quarto", true);

        $aprofundouRomance = true;
        $evento = "🛏️ $meuNome e $alvo passaram a noite juntos no quarto, conversando e fortalecendo o romance.";
    } else {
        $evento = "🛏️ $meuNome tentou passar a noite junto com $alvo, mas o romance ainda precisa chegar em 60.";
    }
}

/* 🍹 BEBIDA */
if ($acao == 'beber') {

    $valor = alterarPopularidadePublica($jogadores, $meuNome, -12, 8, "exagerou na bebida durante a festa", true);

    if ($valor >= 0) {

        $evento =
            "🍹 $meuNome exagerou na bebida e virou assunto na internet.";
    } else {

        $evento =
            "🍹 $meuNome bebeu demais e acabou pagando mico na festa.";
    }
}

/* =========================================================
   🧠 NPCs LEMBRAM DO QUE O JOGADOR FEZ NA FESTA
   ========================================================= */

if ($alvo != '') {

    $chaveMemoriaFesta =
        'festa_jogador|' .
        ($_SESSION['rodada'] ?? 1) .
        '|' .
        $acao .
        '|' .
        $alvo .
        '|' .
        ($_SESSION['acoes_festa'] ?? 0);

    if ($acao == 'provocar') {

        registrarMemoriaSocialNPC(
            $alvo,
            $meuNome,
            'brigou_comigo',
            2,
            "$meuNome provocou $alvo durante a festa.",
            $chaveMemoriaFesta
        );
    }

    if (
        in_array(
            $acao,
            [
                'aproximar',
                'dancar',
                'elogiar'
            ],
            true
        )
    ) {

        registrarMemoriaSocialNPC(
            $alvo,
            $meuNome,
            'me_aproximou',
            1,
            "$meuNome teve um momento positivo com $alvo durante a festa.",
            $chaveMemoriaFesta
        );
    }

    if ($acao == 'flertar' && $flertouCorrespondido) {

        registrarMemoriaSocialNPC(
            $alvo,
            $meuNome,
            'flertou_comigo',
            1,
            "$meuNome flertou com $alvo e o clima foi correspondido.",
            $chaveMemoriaFesta
        );
    }

    if (in_array($acao, ['sentimentos', 'noite_conversando', 'passar_noite_quarto'], true) && $aprofundouRomance) {

        registrarMemoriaSocialNPC(
            $alvo,
            $meuNome,
            'me_aproximou',
            2,
            "$meuNome fortaleceu a relação com $alvo durante a festa.",
            $chaveMemoriaFesta
        );
    }

    if (
        $acao == 'pedir_namoro' &&
        mb_stripos(
            $evento,
            'aceitou',
            0,
            'UTF-8'
        ) !== false
    ) {

        registrarMemoriaSocialNPC(
            $alvo,
            $meuNome,
            'me_aproximou',
            3,
            "$meuNome pediu $alvo em namoro e o pedido foi aceito.",
            $chaveMemoriaFesta
        );
    }
}


$_SESSION['evento_extra'][] = $evento;
$_SESSION['jogadores'] = $jogadores;
$_SESSION['acoes_festa']--;
rc2ProcessarMomento($jogadores, $meuNome, 'festa',
    'acao_' . (3 - (int)$_SESSION['acoes_festa']),
    (int)$_SESSION['acoes_festa'] <= 0);

unset($_SESSION['acao_festa_selecionada']);

if ($_SESSION['acoes_festa'] <= 0) {

    if (!isset($_SESSION['npc_festa_feita'])) {
        $eventosNPC = gerarAcoesFestaNPC($jogadores, $meuNome, 2);

        foreach ($eventosNPC as $ev) {
            $_SESSION['evento_extra'][] = $ev;
        }

        $eventosAliancas = atualizarAliancasAutomaticas($jogadores, $meuNome);
        foreach ($eventosAliancas as $ev) {
            $_SESSION['evento_extra'][] = $ev;
        }

        $_SESSION['npc_festa_feita'] = true;
        $_SESSION['jogadores'] = $jogadores;
    }

    if(function_exists('dsConcluirFesta')) dsConcluirFesta($jogadores);

    $_SESSION['fase_semana'] = bbbFaseAposFesta($jogadores);
    $_SESSION['acoes_festa'] = 3;
}

header("Location: jogo.php");
exit;

} // fecha o if principal de acao_festa


/* SELECIONAR AÇÃO DA FESTA */
if (isset($_POST['selecionar_acao_festa'])) {

    $_SESSION['acao_festa_selecionada'] =
        $_POST['selecionar_acao_festa'];

    header("Location: jogo.php");
    exit;
}


/* CANCELAR AÇÃO DA FESTA */
if (isset($_POST['cancelar_acao_festa'])) {

    unset($_SESSION['acao_festa_selecionada'], $_SESSION['es2_negociacao']);

    header("Location: jogo.php");
    exit;
}