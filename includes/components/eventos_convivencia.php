<?php
$ecAtivo=$_SESSION['evento_convivencia_ativo']??null;
$ecResultado=$_SESSION['evento_convivencia_resultado']??null;
?>
<?php if ($ecResultado): ?>
<div class="ec-cena ec-resultado">
  <div class="ec-camera"><span>● REC</span> • MOMENTO DA CASA</div>
  <div class="ec-conteudo"><div class="ec-icone"><?=htmlspecialchars($ecResultado['icone'])?></div><h3><?=htmlspecialchars($ecResultado['titulo'])?></h3><p><?=htmlspecialchars($ecResultado['texto'])?></p>
  <form method="post"><button class="ec-opcao ec-principal" name="evento_convivencia_fechar_resultado" value="1">CONTINUAR</button></form></div>
</div>
<?php elseif ($ecAtivo): ?>
<div class="ec-cena">
  <div class="ec-moldura ec-moldura-tl"></div><div class="ec-moldura ec-moldura-tr"></div>
  <div class="ec-camera"><span>● REC</span> • CAM <?=str_pad((string)random_int(2,8),2,'0',STR_PAD_LEFT)?> • <?=htmlspecialchars($ecAtivo['camera'])?></div>
  <div class="ec-conteudo">
    <div class="ec-npc"><div class="ec-avatar"><?=htmlspecialchars(mb_substr($ecAtivo['npc'],0,1))?></div><div><strong><?=htmlspecialchars($ecAtivo['npc'])?></strong><small>Momento especial</small></div></div>
    <div class="ec-titulo"><span><?=htmlspecialchars($ecAtivo['icone'])?></span><h3><?=htmlspecialchars($ecAtivo['titulo'])?></h3></div>
    <p class="ec-texto"><?=htmlspecialchars($ecAtivo['texto'])?></p>
    <div class="ec-opcoes"><?php foreach($ecAtivo['opcoes'] as $i=>$op): ?><form method="post"><button class="ec-opcao" name="evento_convivencia_escolha" value="<?=$i?>"><?=htmlspecialchars($op[0])?></button></form><?php endforeach;?></div>
  </div>
  <div class="ec-moldura ec-moldura-bl"></div><div class="ec-moldura ec-moldura-br"></div>
</div>
<?php endif; ?>
