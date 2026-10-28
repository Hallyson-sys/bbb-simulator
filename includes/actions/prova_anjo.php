<?php

/* =========================================================
   😇 ACTION — PROVA DO ANJO 2.0
   ========================================================= */

require_once __DIR__ . '/../logica/premios_provas.php';

if (
    !isset($jogadores) ||
    !is_array($jogadores) ||
    !isset($lider) ||
    !isset($meuNome) ||
    !isset($participantes) ||
    !is_array($participantes)
) {
    return;
}

/* Permite abrir a tela de resultado depois que a prova já foi encerrada. */
if (provaAnjoFinalizadaNaRodadaAtual()) {
    if (!empty($_GET['resultado']) && !empty($_SESSION['resultado_prova_anjo'])) {
        return;
    }

    if (($_SESSION['fase_semana'] ?? '') === 'anjo') {
        $_SESSION['fase_semana'] = 'monstro';
    }

    header('Location: jogo.php');
    exit;
}

if (empty($participantes)) {
    if (!isset($_SESSION['evento_extra']) || !is_array($_SESSION['evento_extra'])) {
        $_SESSION['evento_extra'] = [];
    }

    $_SESSION['evento_extra'][] =
        '⚠️ Não havia participantes disponíveis para a Prova do Anjo.';

    $_SESSION['prova_anjo_finalizada'] = true;
    $_SESSION['prova_anjo_finalizada_rodada'] =
        (int)($_SESSION['rodada'] ?? 1);

    $_SESSION['fase_semana'] = 'monstro';

    header('Location: jogo.php');
    exit;
}

$dadosProvaAction = prepararProvaAnjo();
$provaAction = $dadosProvaAction['prova'];

/* =========================================================
   🎮 PROVAS INTERATIVAS DO ANJO 4.0
   ========================================================= */
if (isset($_POST['pn_jogar'])) {
    $tipoAtual=(int)($dadosProvaAction['tipo']??0);
    if(!pnEstaNova('anjo',$tipoAtual)||($_SESSION['fase_semana']??'')!=='anjo'){
        header('Location: jogo.php');exit;
    }
    $score=pnCorrigir('anjo',$tipoAtual,$_POST);
    if($score===null){$_SESSION['pn_feedback']='Preencha todas as etapas antes de concluir.';header('Location: prova_anjo.php');exit;}
    $dupla=$tipoAtual===10;$parceiro='';
    if($dupla){
        $parceiro=(string)($_POST['pn_parceiro']??'');
        if(!in_array($parceiro,pnParceiros($participantes,$meuNome,$lider),true)){
            $_SESSION['pn_feedback']='Selecione um parceiro disponível.';header('Location: prova_anjo.php');exit;
        }
        if(!pnParceiroAceita($jogadores,$meuNome,$parceiro)){
            $_SESSION['pn_feedback']="$parceiro recusou o convite. Convide outro participante.";
            header('Location: prova_anjo.php');exit;
        }
        $score=min(100,(int)round($score*.75+random_int(48,92)*.25));
    }
    $ranking=pnRankingAnjo($participantes,$meuNome,$score,$dupla,$parceiro);
    $ganhador=(string)($ranking[0]['nome']??'');
    $parceiroGanhador=(string)($ranking[0]['parceiro']??'');
    finalizarProvaAnjo($jogadores,$ganhador,$lider);
    if($dupla)pnRegistrarDuplaAnjo($jogadores,$ganhador,$parceiroGanhador);
    salvarResultadoVisualProvaAnjo($ranking,$provaAction,$meuNome,true);
    $_SESSION['resultado_prova_anjo']['parceiro']=$parceiroGanhador;
    $_SESSION['evento_extra'][]='🎮 '.($provaAction['titulo']??'Prova do Anjo').": $meuNome marcou $score pontos; $ganhador venceu".($parceiroGanhador!==''?" com $parceiroGanhador.":'.');
    premiarMoedasPorProva('anjo',$ganhador,$meuNome,15);
    unset($_SESSION['pn_estado_anjo']);
    header('Location: prova_anjo.php?resultado=1');exit;
}

/* =========================================================
   👑 LÍDER NÃO PARTICIPA, MAS VÊ O RESULTADO
   ========================================================= */

if ($meuNome === $lider) {
    $duplaNPC=(int)($dadosProvaAction['tipo']??0)===10;
    if($duplaNPC) {
        $ranking=pnRankingNPCsAnjo($participantes);
    } else {
        $ranking = gerarRankingProvaAnjo($participantes, $meuNome, null);
    }
    $campeaoNome = (string)($ranking[0]['nome'] ?? '');
    $parceiroCampeao=$duplaNPC?(string)($ranking[0]['parceiro']??''):'';

    if (!isset($_SESSION['evento_extra']) || !is_array($_SESSION['evento_extra'])) {
        $_SESSION['evento_extra'] = [];
    }

    $_SESSION['evento_extra'][] =
        "👑 $lider já é Líder e ficou fora da Prova do Anjo.";

    finalizarProvaAnjo($jogadores, $campeaoNome, $lider);
    if($duplaNPC)pnRegistrarDuplaAnjo($jogadores,$campeaoNome,$parceiroCampeao);
    salvarResultadoVisualProvaAnjo($ranking, $provaAction, $meuNome, false);
    if($duplaNPC)$_SESSION['resultado_prova_anjo']['parceiro']=$parceiroCampeao;

    premiarMoedasPorProva(
        'anjo',
        $campeaoNome,
        $meuNome,
        15
    );

    header('Location: prova_anjo.php?resultado=1');
    exit;
}

if (!isset($_POST['jogar'])) {
    return;
}

/* =========================================================
   🎮 RESOLVER MINIGAME
   ========================================================= */

$pontuacaoJogador = normalizarPontuacaoJogadorAnjo($_POST);
$ranking = gerarRankingProvaAnjo(
    $participantes,
    $meuNome,
    $pontuacaoJogador
);

$campeaoNome = (string)($ranking[0]['nome'] ?? '');

/* =========================================================
   🏆 FINALIZAR PROVA
   ========================================================= */

finalizarProvaAnjo(
    $jogadores,
    $campeaoNome,
    $lider
);

salvarResultadoVisualProvaAnjo(
    $ranking,
    $provaAction,
    $meuNome,
    true
);

premiarMoedasPorProva(
    'anjo',
    $campeaoNome,
    $meuNome,
    15
);

header('Location: prova_anjo.php?resultado=1');
exit;
