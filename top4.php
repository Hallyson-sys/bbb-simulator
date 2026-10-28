<?php
session_start();
require_once __DIR__.'/includes/logica/top4.php';
require_once __DIR__.'/includes/logica/historico_temporada.php';
$jogadores=array_values((array)($_SESSION['jogadores']??[]));
if(count($jogadores)!==4 || !empty($_SESSION['paredao_falso_ativo'])) {header('Location: jogo.php'); exit;}
if(!empty($_SESSION['top4_concluido'])) {
    if(($_GET['resultado']??'') !== '1') {header('Location: jogo.php');exit;}
    $estado=$_SESSION['top4_estado']??[];
    $vencedor=(string)($_SESSION['top4_vencedor']??'');
    $paredao=(array)($_SESSION['paredao']??[]);
    ?>
    <!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Top 4 — Resultado da Resistência</title><link rel="stylesheet" href="assets/css/top4.css?v=2"></head><body>
    <main class="semifinal"><header><span class="tag">● REC • A DECISÃO DA SEMIFINAL</span><h1>🏆 A última pessoa de pé</h1><p>Depois de uma prova de resistência que exigiu concentração e controle, a primeira vaga na Grande Final tem dono.</p></header>
    <section class="prova resultado-final"><div class="trofeu">👑</div><p class="tag">FINALISTA GARANTIDO</p><h2><?=safe4($vencedor)?></h2><p>Aguentou <strong><?= (int)($estado['duracao'][$vencedor]??0) ?> ciclos</strong> e conquistou a primeira vaga na final.</p><div class="recado">🔥 Os outros três participantes enfrentarão o último Paredão, sem Bate-Volta.</div>
    <div class="semifinalistas"><?php foreach($paredao as $nome):?><span>🔥 <?=safe4($nome)?></span><?php endforeach;?></div>
    <a class="continuar-final" href="jogo.php">➡️ SEGUIR PARA O ÚLTIMO PAREDÃO</a></section></main></body></html>
    <?php exit;
}
$_SESSION['fase_semana']='top4_prova';
top4Preparar($jogadores);
$estado=&$_SESSION['top4_estado'];
$meuNome=(string)($_SESSION['meu_nome']??'');
$participando=false;
foreach($jogadores as $j) if(($j['nome']??'')===$meuNome) $participando=true;
$erro='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['top4_responder'])) {
    if(!top4Avancar($jogadores,$estado,$meuNome,$_POST,$participando)) {
        $_SESSION['top4_erro']='Confira sua postura e trave o marcador antes de continuar.';
    }
    header('Location: '.(!empty($estado['terminou'])?'top4.php?resultado=1':'top4.php')); exit;
}
if(isset($_SESSION['top4_erro'])) {$erro=$_SESSION['top4_erro'];unset($_SESSION['top4_erro']);}
$ciclo=$estado['ciclos'][(int)$estado['etapa']];
$meuAtivo=$participando && !empty($estado['ativos'][$meuNome]);
$barraMin=max(0,(int)$ciclo['alvo']-(int)$ciclo['tolerancia']);
$barraMax=min(100,(int)$ciclo['alvo']+(int)$ciclo['tolerancia']);
function safe4($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
?>
<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><title>Top 4 — A Última Resistência</title>
<link rel="stylesheet" href="assets/css/top4.css?v=2"></head><body>
<main class="semifinal">
  <header><span class="tag">● REC &nbsp;•&nbsp; SEMIFINAL &nbsp;•&nbsp; TOP 4</span>
    <h1>⏳ A Última Resistência</h1>
    <p>Uma plataforma suspensa, oito ciclos de desgaste e somente uma vaga direta na Grande Final. Os outros três vão ao último Paredão.</p>
  </header>
  <section class="painel-tempo" aria-label="Andamento da prova">
    <div class="titulo-tempo"><span>🔴 AO VIVO • RESISTÊNCIA</span><strong>CICLO <?= (int)$estado['etapa']+1 ?> / 8</strong></div>
    <div class="trilha" aria-hidden="true"><div style="width:<?= min(100,(int)$estado['etapa']*12.5) ?>%"></div></div>
    <div class="ao-vivo-info"><span>📍 <?=safe4($ciclo['local'])?></span><span>⚠️ <?=safe4($ciclo['efeito'])?></span></div>
  </section>
  <?php if($estado['ultimo']): ?><p class="recado" role="status"><?= safe4($estado['ultimo']) ?></p><?php endif;?>
  <?php if($erro):?><p class="erro" role="alert"><?=safe4($erro)?></p><?php endif;?>
  <section class="prova">
    <div class="etapas">DESAFIO <?= (int)$estado['etapa']+1 ?> &nbsp;•&nbsp; EQUILÍBRIO & CONCENTRAÇÃO</div>
    <h2><?=safe4($ciclo['titulo'])?></h2><p class="enunciado"><?=safe4($ciclo['texto'])?></p>
    <?php if($meuAtivo):?>
      <div class="instrucoes"><strong>🎯 Trave o marcador na área iluminada.</strong><span>O ponto luminoso percorre o medidor. Quanto mais perto do centro, menos energia você perde e mais pontos ganha.</span></div>
      <div class="marcador-jogo" id="jogoEquilibrio" data-alvo="<?= (int)$ciclo['alvo'] ?>">
        <div class="regua"><div class="zona" style="left:<?=$barraMin?>%;width:<?=$barraMax-$barraMin?>%"><span>ÁREA SEGURA</span></div>
          <div class="ponteiro" id="ponteiro"><i></i></div></div>
        <div class="marcador-status" id="statusMarcador">O marcador está em movimento...</div>
        <button class="botao-travar" type="button" id="travar">🎯 TRAVAR EQUILÍBRIO</button>
        <button class="botao-repetir" type="button" id="repetir" hidden>↻ Refazer antes de confirmar</button>
      </div>
      <form method="post" id="formTop4" autocomplete="off"><input type="hidden" name="top4_responder" value="1">
        <input type="hidden" name="indice" value="<?=(int)$estado['etapa']?>">
        <input type="hidden" name="token" value="<?=safe4($estado['token'])?>">
        <input type="hidden" name="marcador" id="marcadorFinal" value="">
        <fieldset class="estrategias"><legend>🧠 Como você vai aguentar este ciclo?</legend>
          <label><input type="radio" name="postura" value="conservar" required><span><b>🌬️ Controlar a respiração</b><small>Gasta menos energia, recupera o foco. Menos pontos.</small></span></label>
          <label><input type="radio" name="postura" value="firmar" required><span><b>🦾 Firmar a postura</b><small>Equilibra resistência e pontuação.</small></span></label>
          <label><input type="radio" name="postura" value="arriscar" required><span><b>⚡ Forçar para avançar</b><small>Mais pontos, porém exige muito do corpo.</small></span></label>
        </fieldset>
        <button type="submit" id="confirmar" disabled>➡️ AGUENTAR MAIS UM CICLO</button>
      </form>
    <?php else:?>
      <div class="transmissao">📺 <?= $participando ? 'Você saiu da prova, mas ainda pode acompanhar a resistência dos semifinalistas.' : 'Você acompanha a prova decisiva pela transmissão da casa.' ?></div>
      <form method="post"><input type="hidden" name="indice" value="<?=(int)$estado['etapa']?>"><input type="hidden" name="token" value="<?=safe4($estado['token'])?>">
        <button type="submit" name="top4_responder" value="1">📺 ACOMPANHAR PRÓXIMO CICLO</button></form>
    <?php endif;?>
  </section>
  <section class="placar"><div class="placar-titulo"><h3>💓 Quem ainda está de pé?</h3><span>Estado atual</span></div>
    <div class="competidores">
    <?php foreach($estado['pontos'] as $nome=>$pts):
       $energia=(int)$estado['energia'][$nome]; $foco=(int)$estado['foco'][$nome];$ativo=!empty($estado['ativos'][$nome]); ?>
      <div class="competidor <?= $ativo?'ativo':'caiu' ?> <?=($nome===$meuNome)?'jogador':''?>">
        <div class="competidor-linha"><strong><?=safe4($nome)?><?=($nome===$meuNome)?' ⭐':''?></strong><span><?=$ativo?'● Resistindo':'✖ Caiu'?></span></div>
        <div class="energia-titulo"><span>Resistência</span><b><?=$energia?>%</b></div>
        <div class="energia-trilha"><div style="width:<?=$energia?>%"></div></div>
        <div class="detalhes"><span>🧠 Foco <?=$foco?>%</span><span>🏅 <?=$pts?> pts</span></div>
      </div>
    <?php endforeach; ?>
    </div>
  </section>
</main>
<script src="assets/js/top4_resistencia.js?v=2" defer></script>
</body></html>
