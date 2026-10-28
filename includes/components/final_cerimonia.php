<?php
$cerimonia=$_SESSION['final_cerimonia'];
$opcoes=finalOpcoesDiscurso();
$meu=(string)($_SESSION['meu_nome']??'');
$possoFalar=false;
foreach($jogadores as $j) if(($j['nome']??'')===$meu)$possoFalar=true;
function cerimE($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Grande Final — Discursos e Torcidas</title>
<link rel="stylesheet" href="assets/css/final_cerimonia.css"></head><body>
<main class="cerimonia">
<header><span class="rotulo">● REC • GRANDE FINAL • AO VIVO</span><h1>🎤 A hora de pedir votos</h1><p>Os três finalistas têm uma última oportunidade de conquistar o público. Antes do resultado, o apresentador também pergunta aos eliminados para quem vai a torcida.</p></header>
<section class="caixa"><h2>📣 Os finalistas pedem o voto</h2>
<?php foreach($jogadores as $j): $nome=(string)($j['nome']??''); ?>
<article class="depoimento"><strong><?=cerimE($nome)?><?= $nome===(string)($_SESSION['top4_vencedor']??'')?' • 🏆 Vencedor da prova Top 4':'' ?></strong>
<p><?=cerimE($nome===$meu && $possoFalar?'Você terá a chance de escolher suas palavras.':(string)($cerimonia['discursos'][$nome]['fala']??''))?></p></article>
<?php endforeach; ?></section>
<section class="caixa"><h2>🏠 A torcida de quem já saiu</h2><p class="intro">O apresentador conversa com ex-participantes. As respostas refletem as relações que eles conheciam quando deixaram o jogo.</p>
<?php if(empty($cerimonia['torcidas'])):?><p class="intro">Nenhum depoimento de eliminado disponível neste save.</p><?php endif; ?>
<?php foreach((array)$cerimonia['torcidas'] as $ex):?><article class="depoimento"><strong><?=cerimE($ex['nome'])?> <span>torce por <?=cerimE($ex['alvo'])?></span></strong><p><?=cerimE($ex['fala'])?></p></article><?php endforeach; ?></section>
<form method="post" class="caixa">
<?php if(!empty($_SESSION['final_cerimonia']['aviso'])):?><p class="intro" role="alert"><?=cerimE($_SESSION['final_cerimonia']['aviso'])?></p><?php endif;?>
<h2><?= $possoFalar?'🎯 Qual será o seu pedido de voto?':'📺 Vamos conhecer o resultado?' ?></h2>
<?php if($possoFalar):?><div class="escolhas">
<?php foreach($opcoes as $tipo=>$opcao):?><label><input type="radio" name="tom_discurso" value="<?=cerimE($tipo)?>" required><span><b><?=cerimE($opcao['titulo'])?></b><small><?=cerimE($opcao['fala'])?></small></span></label><?php endforeach;?></div>
<?php else:?>
<input type="hidden" name="tom_discurso" value="coracao">
<?php $podeTorcer=!empty($_SESSION['modo_espectador']) && in_array($meu,(array)($_SESSION['historico_eliminados']??[]),true); ?>
<?php if($podeTorcer): ?>
<p class="intro">🎤 O apresentador pergunta: "Você já saiu da casa. Quem merece levar o prêmio?"</p>
<div class="escolhas">
<?php foreach($jogadores as $j):$n=(string)($j['nome']??''); ?>
<label><input type="radio" name="torcida_espectador" value="<?=cerimE($n)?>" required <?=($_SESSION['final_cerimonia']['torcida_espectador']??'')===$n?'checked':''?>><span><b>🏆 Torcer por <?=cerimE($n)?></b><small>Seu depoimento será registrado na cerimônia. A torcida não garante a vitória.</small></span></label>
<?php endforeach; ?></div>
<?php else: ?><p class="intro">Você acompanha os discursos e as torcidas como espectador.</p><?php endif; ?>
<?php endif; ?>
<button name="final_confirmar" value="1">🎬 Encerrar votação e iniciar revelação</button>
<p class="intro">O pedido de voto pode influenciar discretamente a votação final. Os números permanecem em sigilo até o resultado.</p>
</form></main></body></html>
