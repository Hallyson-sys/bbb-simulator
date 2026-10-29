<?php
session_start();
require_once __DIR__ . '/includes/logica/bate_papo_eliminado.php';

$meuNome = (string)($_SESSION['meu_nome'] ?? '');
$fora = bpEliminadoReal($meuNome) && (!empty($_SESSION['jogador_eliminado']) || !empty($_SESSION['modo_espectador']));
if (!$fora || empty($_SESSION['jogadores'])) {
    header('Location: jogo.php'); exit;
}

bpPrepararArquivo();
$meu = $_SESSION['bp_entrevistas'][$meuNome] ?? bpPreparar($meuNome, bpSnapshot($meuNome), bpSemanaSaida($meuNome), true);
if (!$meu) { header('Location: jogo.php'); exit; }

if (empty($_SESSION['bp_form_token'])) $_SESSION['bp_form_token'] = bin2hex(random_bytes(20));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bp_resposta'])) {
    if (hash_equals($_SESSION['bp_form_token'], (string)($_POST['token'] ?? ''))) {
        bpResponderJogador((string)($_POST['pergunta'] ?? ''), (string)($_POST['bp_resposta'] ?? ''));
    }
    header('Location: bate_papo.php'); exit;
}

$meu = (array)($_SESSION['bp_entrevistas'][$meuNome] ?? $meu);
$podeArquivo = !empty($_SESSION['modo_espectador']) || !empty($meu['concluida']);
$arquivos = [];
if ($podeArquivo) {
    foreach ((array)($_SESSION['historico_eliminados'] ?? []) as $saiu) {
        $saiu = (string)$saiu;
        $e = $_SESSION['bp_entrevistas'][$saiu] ?? null;
        if (!is_array($e) || empty($e['concluida'])) continue;
        [$tag, $prioridade] = bpDestaqueParaMim($saiu);
        if (bpNomeIgual($saiu, $meuNome)) { $tag = '🎙️ Sua entrevista'; $prioridade = 4; }
        $arquivos[] = ['nome'=>$saiu,'tag'=>$tag,'prioridade'=>$prioridade,'semana'=>(int)$e['semana']];
    }
    usort($arquivos, static fn($a,$b) => ($b['prioridade'] <=> $a['prioridade']) ?: ($b['semana'] <=> $a['semana']));
}

$selecionado = $meuNome;
if ($podeArquivo && !empty($_GET['nome'])) {
    foreach ($arquivos as $v) {
        if ($v['nome'] === (string)$_GET['nome']) { $selecionado = $v['nome']; break; }
    }
}
$entrevista = (array)($_SESSION['bp_entrevistas'][$selecionado] ?? $meu);
$progresso = count((array)($entrevista['respostas'] ?? []));
$total = count((array)($entrevista['perguntas'] ?? []));
$atual = empty($entrevista['concluida']) ? ($entrevista['perguntas'][$progresso] ?? null) : null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Bate-Papo com o Eliminado • BBB Simulator</title>
<link rel="stylesheet" href="assets/css/bate_papo.css?v=1">
</head>
<body>
<main class="bp-page">
  <header class="bp-header">
    <div class="bp-eyebrow">● REC &nbsp;•&nbsp; ESTÚDIO BBB &nbsp;•&nbsp; SEMANA <?= (int)($entrevista['semana'] ?? 1) ?></div>
    <div class="bp-mic">🎙️</div>
    <h1>BATE-PAPO <span>COM O ELIMINADO</span></h1>
    <p><?= bpEsc($selecionado) ?> deixou o confinamento. É hora de revisitar o que aconteceu naquela edição.</p>
  </header>

  <?php if ($atual): ?>
    <section class="bp-card" aria-labelledby="bp-pergunta">
      <div class="bp-topline"><span>ENTREVISTA INTERATIVA</span><span>PERGUNTA <?= $progresso+1 ?> DE <?= $total ?></span></div>
      <div class="bp-progress"><div style="width:<?= $total ? round(100*$progresso/$total) : 0 ?>%"></div></div>
      <h2 id="bp-pergunta"><?= bpEsc($atual['titulo']) ?></h2>
      <p class="bp-host"><b>🎤 Apresentador:</b> “<?= bpEsc($atual['pergunta']) ?>”</p>
      <div class="bp-subtitle">Como você responde?</div>
      <form method="POST" class="bp-options">
        <input type="hidden" name="token" value="<?= bpEsc($_SESSION['bp_form_token']) ?>">
        <input type="hidden" name="pergunta" value="<?= bpEsc($atual['id']) ?>">
        <?php foreach ($atual['opcoes'] as $opt): ?>
          <button type="submit" name="bp_resposta" value="<?= bpEsc($opt['id']) ?>">
            <b><?= bpEsc($opt['label']) ?></b><small><?= bpEsc($opt['fala']) ?></small>
          </button>
        <?php endforeach; ?>
      </form>
      <p class="bp-note">Suas respostas ficam registradas na entrevista. Elas não alteram retroativamente sua eliminação.</p>
    </section>
  <?php else: ?>
    <section class="bp-card">
      <div class="bp-topline"><span>ENTREVISTA REGISTRADA</span><span>SEMANA <?= (int)$entrevista['semana'] ?></span></div>
      <h2>📺 <?= bpEsc($selecionado) ?> no estúdio</h2>
      <?php foreach ((array)$entrevista['perguntas'] as $q):
          $resp = $entrevista['respostas'][$q['id']] ?? null;
          if (!$resp) continue; ?>
        <article class="bp-depoimento">
          <div class="bp-question-title"><?= bpEsc($q['titulo']) ?></div>
          <p><b>🎤 Apresentador:</b> <?= bpEsc($q['pergunta']) ?></p>
          <p class="bp-answer"><b><?= bpEsc($selecionado) ?>:</b> “<?= bpEsc($resp['fala']) ?>”</p>
        </article>
      <?php endforeach; ?>
      <div class="bp-footer">🏁 Um capítulo se encerra, mas a edição continua.</div>
      <?php if (empty($_SESSION['modo_espectador'])): ?>
        <form method="POST" action="jogo.php">
          <button class="bp-primary" type="submit" name="continuar_espectador" value="1">👁️ Entrar no Modo Espectador</button>
        </form>
      <?php else: ?>
        <a class="bp-primary" href="jogo.php">📺 Voltar ao Modo Espectador</a>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if ($podeArquivo): ?>
    <section class="bp-card" id="arquivo">
      <div class="bp-topline"><span>📼 ARQUIVO DO ESTÚDIO</span><span><?= count($arquivos) ?> ENTREVISTAS</span></div>
      <h2>Entrevistas de quem já saiu</h2>
      <p class="bp-note">Reveja os depoimentos de seus antigos aliados, amigos e interesses amorosos. Novas entrevistas aparecem conforme os participantes são eliminados.</p>
      <div class="bp-library">
        <?php foreach ($arquivos as $v): ?>
          <a class="bp-library-card <?= $selecionado === $v['nome'] ? 'active' : '' ?>" href="bate_papo.php?nome=<?= rawurlencode($v['nome']) ?>">
            <span class="bp-library-icon">🎙️</span>
            <span><b><?= bpEsc($v['nome']) ?></b><small><?= bpEsc($v['tag']) ?> · Semana <?= $v['semana'] ?></small></span>
            <span aria-hidden="true">›</span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</main>
</body>
</html>
