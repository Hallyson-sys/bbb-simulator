<?php
/** Micro-painel opcional para consultar cenas que já aconteceram. Não executa lógica. */
$rc2SemanaAtual = (int)($_SESSION['rodada'] ?? 1);
$rc2DaSemana = array_values(array_filter(rc2Recentes(8), static function($cena) use ($rc2SemanaAtual) {
    return (int)($cena['semana'] ?? 0) === $rc2SemanaAtual;
}));
?>
<details class="rc2-painel">
    <summary>🏠 Rotina da Casa <span class="rc2-contador"><?= count($rc2DaSemana) ?> <?= count($rc2DaSemana) === 1 ? 'momento' : 'momentos' ?> nesta semana</span><span class="rc2-seta" aria-hidden="true">⌄</span></summary>
    <div class="rc2-corpo">
        <p class="rc2-ajuda">Os participantes convivem mesmo quando você está fazendo outras coisas. Novos acontecimentos podem surgir entre ações e durante a Festa.</p>
        <?php if ($rc2DaSemana): ?>
            <?php foreach (array_slice($rc2DaSemana, 0, 4) as $rc2Cena): ?>
                <div class="rc2-momento">
                    <span class="rc2-local">📍 <?= htmlspecialchars(ucfirst((string)($rc2Cena['local'] ?? 'casa')), ENT_QUOTES, 'UTF-8') ?></span>
                    <p><?= $rc2Cena['texto'] ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="rc2-vazio">Os primeiros momentos da rotina vão aparecer conforme a semana avançar.</p>
        <?php endif; ?>
    </div>
</details>
<?php
// Apenas acontecimentos observáveis; as intenções secretas não aparecem aqui.
$r4Semana = (int)($_SESSION['rodada'] ?? 1);
$r4Publicos = array_values(array_filter(
    function_exists('r4Recentes') ? r4Recentes(8) : [],
    static fn($evento) => (int)($evento['semana'] ?? 0) === $r4Semana
));
?>
<details class="rc2-painel">
    <summary>💞 Relações em Movimento <span class="rc2-contador"><?= count($r4Publicos) ?> <?= count($r4Publicos)===1 ? 'acontecimento':'acontecimentos' ?> nesta semana</span><span class="rc2-seta" aria-hidden="true">⌄</span></summary>
    <div class="rc2-corpo">
        <p class="rc2-ajuda">A convivência muda amizades, romances e rivalidades. Apenas situações observadas são exibidas aqui.</p>
        <?php if ($r4Publicos): ?>
            <?php foreach (array_slice($r4Publicos, 0, 4) as $r4Evento): ?>
                <div class="rc2-momento">
                    <span class="rc2-local">📍 <?= htmlspecialchars(ucfirst((string)($r4Evento['local'] ?? 'casa')), ENT_QUOTES, 'UTF-8') ?></span>
                    <p><?= htmlspecialchars((string)($r4Evento['texto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="rc2-vazio">Ainda não houve uma mudança marcante observada nesta semana.</p>
        <?php endif; ?>
    </div>
</details>
