<?php
/** @var array $jogadores */
/** @var string $meuNome */
/** @var string $fase */
$in2Convite=$_SESSION['in2_convite']??null;
$in2Conversa=$_SESSION['in2_conversa']??null;
$in2Resultado=$_SESSION['in2_resultado']??null;
$in2Historico=(array)($_SESSION['in2_historico']??[]);
if (!is_array($in2Convite) && !is_array($in2Conversa) && !is_array($in2Resultado) && !$in2Historico) return;
?>
<section class="in2-raiz" id="iniciativa-npc" aria-label="Iniciativa dos NPCs">
<?php if (is_array($in2Conversa)):
    $in2Etapa=i4EtapaRomance($jogadores,$meuNome,(string)$in2Conversa['npc']);
    $in2Tipo=(string)$in2Conversa['tipo'];
    if ($in2Tipo==='casal' && $in2Etapa!=='namoro') $in2Tipo='conversa';
    if ($in2Tipo==='aproximacao' && $in2Etapa==='comprometido') $in2Tipo='conversa';
    $in2Conteudo=in2Dados((string)$in2Conversa['npc'],$in2Tipo,$in2Etapa);
?>
  <div class="in2-kicker">● REC • CONVERSA INICIADA POR NPC • SEMANA <?= in2Semana() ?></div>
  <h2><?= in2Esc($in2Conteudo[0].' '.$in2Conteudo[1]) ?></h2>
  <p class="in2-contexto"><?= in2Esc($in2Conversa['npc']) ?> procurou você <?= in2Esc($in2Conversa['local']) ?>.</p>
  <blockquote><strong>💬 <?= in2Esc($in2Conversa['npc']) ?></strong><p><?= in2Esc($in2Conteudo[2]) ?></p></blockquote>
  <h3>Como você responde?</h3>
  <div class="in2-acoes">
    <?php foreach($in2Conteudo[3] as $indice=>$rotulo): ?>
      <form method="post"><input type="hidden" name="in2_token" value="<?= in2Esc($in2Conversa['token']) ?>">
      <button type="submit" name="in2_resposta" value="<?= (int)$indice ?>"><?= in2Esc($rotulo) ?></button></form>
    <?php endforeach; ?>
  </div>
  <p class="in2-ajuda">Seu personagem só responde quando você escolhe. Esta conversa não consome suas interações normais.</p>
<?php elseif (is_array($in2Resultado)): ?>
  <div class="in2-kicker">● REC • CONVERSA ENCERRADA</div>
  <h2>💬 <?= in2Esc($in2Resultado['titulo']) ?></h2>
  <p><strong>Sua escolha:</strong> <?= in2Esc($in2Resultado['resposta']) ?></p>
  <blockquote><strong><?= in2Esc($in2Resultado['npc']) ?></strong><p><?= in2Esc($in2Resultado['texto']) ?></p></blockquote>
  <form method="post"><button type="submit" name="in2_continuar" value="1" class="in2-principal">✅ Voltar às Interações</button></form>
<?php elseif (is_array($in2Convite)): ?>
  <div class="in2-kicker">🔔 <?= !empty($in2Convite['adiado'])?'CONVITE ADIADO':'UM PARTICIPANTE QUER CONVERSAR' ?> • SEMANA <?= in2Semana() ?></div>
  <h2><?= in2Esc($in2Convite['icone'].' '.$in2Convite['titulo']) ?></h2>
  <p><strong><?= in2Esc($in2Convite['npc']) ?></strong> procurou você <?= in2Esc($in2Convite['local']) ?>.</p>
  <?php if (empty($in2Convite['adiado'])): ?>
    <blockquote><strong>💬 <?= in2Esc($in2Convite['npc']) ?></strong><p><?= in2Esc($in2Convite['fala']) ?></p></blockquote>
  <?php else: ?>
    <p class="in2-ajuda">Você pode retomar este convite enquanto estiver nesta fase, ou continuar jogando normalmente.</p>
  <?php endif; ?>
  <div class="in2-acoes in2-convites">
    <?php foreach((!empty($in2Convite['adiado'])?['retomar'=>'💬 Retomar conversa','recusar'=>'🚫 Recusar convite']:['aceitar'=>'✅ Aceitar e conversar','adiar'=>'🕒 Deixar para depois','recusar'=>'🚫 Agora não']) as $decisao=>$rotulo): ?>
      <form method="post"><input type="hidden" name="in2_token" value="<?= in2Esc($in2Convite['token']) ?>">
        <button type="submit" name="in2_convite_decisao" value="<?= in2Esc($decisao) ?>"><?= in2Esc($rotulo) ?></button>
      </form>
    <?php endforeach; ?>
  </div>
  <p class="in2-ajuda">Convite opcional: não custa interação, não força alianças e expira ao sair desta fase. Adiar ou recusar não gera punição.</p>
<?php endif; ?>
<?php if($in2Historico && !is_array($in2Conversa) && !is_array($in2Resultado)): ?>
  <details class="in2-historico"><summary>📓 Conversas iniciadas pelos NPCs (<?= count($in2Historico) ?>)</summary>
  <?php foreach(array_reverse(array_slice($in2Historico,-8)) as $item): ?>
    <div><strong>Semana <?= (int)($item['semana']??1) ?> • <?= in2Esc($item['npc']??'') ?></strong>
      <p><?= in2Esc($item['decisao']??'') ?></p><small><?= in2Esc($item['resposta']??'') ?></small></div>
  <?php endforeach; ?></details>
<?php endif; ?>
</section>
