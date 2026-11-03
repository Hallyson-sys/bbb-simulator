<?php
/** @var string $fase */
$djAtivo = $_SESSION['dj_ativo'] ?? null;
$djResultado = $_SESSION['dj_resultado'] ?? null;
?>
<?php if (is_array($djResultado)): ?>
<section class="dj-cena dj-final" aria-label="Desfecho do dilema">
  <div class="dj-camera">● REC • CONSEQUÊNCIAS</div>
  <h2>⚖️ <?= djEsc($djResultado['titulo'] ?? 'Sua escolha') ?></h2>
  <p><?= djEsc($djResultado['texto'] ?? '') ?></p>
  <p class="dj-observacao">Algumas consequências podem continuar influenciando relações e estratégias nas próximas semanas.</p>
  <form method="post"><button type="submit" class="dj-botao dj-principal" name="dj_continuar" value="1">▶ Continuar a semana</button></form>
</section>
<?php elseif (is_array($djAtivo)): ?>
<section class="dj-cena" aria-label="Dilema de jogo">
  <div class="dj-camera">● REC • CAM <?= djEsc($djAtivo['local'] ?? 'CASA') ?> • SEMANA <?= (int)($djAtivo['semana']??1) ?></div>
  <div class="dj-cabecalho"><span class="dj-simbolo"><?= djEsc($djAtivo['icone']??'⚖️') ?></span><div><small>UMA DECISÃO QUE PODE MUDAR SEU JOGO</small><h2><?= djEsc($djAtivo['titulo'] ?? 'Dilema') ?></h2></div></div>
  <div class="dj-texto"><?= djEsc($djAtivo['texto'] ?? '') ?></div>
  <h3>Como você reage?</h3>
  <div class="dj-opcoes">
    <?php foreach ((array)($djAtivo['opcoes'] ?? []) as $i=>$op): ?>
    <form method="post">
      <input type="hidden" name="dj_token" value="<?= djEsc($djAtivo['token'] ?? '') ?>">
      <button type="submit" class="dj-botao" name="dj_escolher" value="<?= (int)$i ?>"><?= djEsc($op['rotulo'] ?? 'Escolher') ?></button>
    </form>
    <?php endforeach; ?>
  </div>
  <p class="dj-observacao">As escolhas não garantem a reação dos outros participantes. Votos e popularidade continuam ocultos.</p>
</section>
<?php else: ?>
<?php $djHistorico = (array)($_SESSION['dj_historico'] ?? []); ?>
<?php if ($djHistorico): ?>
<details class="dj-historico">
  <summary>⚖️ Minhas decisões difíceis <span><?= count($djHistorico) ?></span></summary>
  <?php foreach (array_reverse(array_slice($djHistorico, -6)) as $registro): ?>
  <article><strong><?= djEsc($registro['titulo']??'Decisão') ?></strong><small>Semana <?= (int)($registro['semana']??1) ?> • <?= djEsc($registro['npc']??'') ?></small><p><?= djEsc($registro['escolha']??'') ?></p></article>
  <?php endforeach; ?>
</details>
<?php endif; ?>
<?php endif; ?>
