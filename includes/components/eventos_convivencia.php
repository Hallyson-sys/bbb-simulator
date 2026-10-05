<?php
$ecAtivo=$_SESSION['evento_convivencia_ativo']??null;
$ecResultado=$_SESSION['evento_convivencia_resultado']??null;
$ecMemoria=function_exists('ecConhecimentosAtivos')?ecConhecimentosAtivos():[];
?>
<?php if ($ecResultado): ?>
<div class="ec-cena ec-resultado"><div class="ec-camera"><span>● REC</span> • MOMENTO DA CASA</div><div class="ec-conteudo"><div class="ec-icone"><?=htmlspecialchars($ecResultado['icone'])?></div><h3><?=htmlspecialchars($ecResultado['titulo'])?></h3><p><?=htmlspecialchars($ecResultado['texto'])?></p><form method="post"><button class="ec-opcao ec-principal" name="evento_convivencia_fechar_resultado" value="1">CONTINUAR</button></form></div></div>
<?php elseif ($ecAtivo): ?>
<div class="ec-cena"><div class="ec-moldura ec-moldura-tl"></div><div class="ec-moldura ec-moldura-tr"></div><div class="ec-camera"><span>● REC</span> • CAM <?=str_pad((string)random_int(2,8),2,'0',STR_PAD_LEFT)?> • <?=htmlspecialchars($ecAtivo['camera'])?></div><div class="ec-conteudo"><div class="ec-npc"><div class="ec-avatar"><?=htmlspecialchars(mb_substr($ecAtivo['npc'],0,1))?></div><div><strong><?=htmlspecialchars($ecAtivo['npc'])?></strong><small>Momento especial</small></div></div><div class="ec-titulo"><span><?=htmlspecialchars($ecAtivo['icone'])?></span><h3><?=htmlspecialchars($ecAtivo['titulo'])?></h3></div><p class="ec-texto"><?=htmlspecialchars($ecAtivo['texto'])?></p><div class="ec-opcoes"><?php foreach($ecAtivo['opcoes'] as $i=>$op): ?><form method="post"><button class="ec-opcao" name="evento_convivencia_escolha" value="<?=$i?>"><?=htmlspecialchars($op[0])?></button></form><?php endforeach;?></div></div><div class="ec-moldura ec-moldura-bl"></div><div class="ec-moldura ec-moldura-br"></div></div>
<?php endif; ?>
<?php if (!$ecAtivo && !$ecResultado): ?>
<details class="ec-memoria" <?=!empty($ecMemoria)?'':'data-vazia="1"'?>>
 <summary>🧠 O que eu sei <span><?=count($ecMemoria)?> informação<?=count($ecMemoria)===1?'':'ões'?></span></summary>
 <div class="ec-memoria-lista">
 <?php if(!$ecMemoria): ?><p class="ec-memoria-vazia">Você ainda não descobriu nenhuma informação estratégica nesta rodada. Segredos podem surgir em conversas, festas e fofocas.</p><?php endif; ?>
 <?php foreach(array_reverse($ecMemoria) as $c): ?><article class="ec-segredo"><div class="ec-segredo-topo"><strong><?=htmlspecialchars($c['titulo'])?></strong><span class="ec-confianca ec-<?=htmlspecialchars($c['confiabilidade'])?>"><?=($c['confiabilidade']==='alta'?'Alta':($c['confiabilidade']==='baixa'?'Baixa':'Média'))?> confiança</span></div><p><?=htmlspecialchars($c['texto'])?></p><small>🔎 <?=htmlspecialchars($c['fonte'])?> • Rodada <?=intval($c['rodada'])?><?=!empty($c['expira_rodada'])?' • válida até esta rodada':''?></small></article><?php endforeach; ?>
 </div>
</details>
<?php endif; ?>
