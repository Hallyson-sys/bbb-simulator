<?php
$q = $_SESSION['rx3_quadro'] ?? null;
if (!is_array($q) || (int)($q['rodada'] ?? -1) !== (int)($_SESSION['rodada'] ?? 1)) return;
$escolhido = $q['escolha'] ?? null;
?>
<section class="rx3-wrap" id="raio-x-estrategico" aria-labelledby="rx3-titulo">
    <div class="rx3-topline"><span class="rx3-rec"><span class="rx3-rec-dot"></span> REC • CÂMERA DO CONFESSIONÁRIO</span><span>RODADA <?php echo (int)$q['rodada']; ?></span></div>
    <div class="rx3-header">
        <span class="rx3-overline">🎙️ CONFESSIONÁRIO 3.0</span>
        <h3 id="rx3-titulo">E agora, qual é sua postura?</h3>
        <p>Um posicionamento só seu diante das câmeras. Os outros participantes não ouvem o que você disser aqui.</p>
    </div>
    <div class="rx3-scene">
        <div class="rx3-category"><?php echo rx3Esc($q['icone'] . ' ' . $q['categoria']); ?></div>
        <h4><?php echo rx3Esc($q['titulo']); ?></h4>
        <p class="rx3-main-text"><?php echo rx3Esc($q['texto']); ?></p>
        <p class="rx3-hint"><?php echo rx3Esc($q['dica']); ?></p>
    </div>
    <?php if (!empty($_SESSION['rx3_erro'])): ?>
        <p class="rx3-error" role="alert"><?php echo rx3Esc((string)$_SESSION['rx3_erro']); ?></p>
    <?php endif; ?>
    <?php if ($escolhido === null): ?>
        <div class="rx3-choices" role="group" aria-label="Escolha seu posicionamento">
            <?php foreach ($q['opcoes'] as $op): ?>
                <form method="post" class="rx3-choice-form">
                    <input type="hidden" name="rx3_token" value="<?php echo rx3Esc($q['token']); ?>">
                    <button type="submit" name="rx3_escolher" value="<?php echo rx3Esc($op['id']); ?>" class="rx3-choice">
                        <strong><?php echo rx3Esc($op['titulo']); ?></strong>
                        <span><?php echo rx3Esc($op['descricao']); ?></span>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
        <p class="rx3-footnote">Sua escolha fica registrada na trajetória do personagem. Só atitudes tomadas na casa podem afetar diretamente os outros participantes.</p>
    <?php else: ?>
        <div class="rx3-result" role="status">
            <div class="rx3-result-kicker">✅ POSICIONAMENTO REGISTRADO</div>
            <h4><?php echo rx3Esc($escolhido['titulo']); ?></h4>
            <p><?php echo rx3Esc($escolhido['resultado']); ?></p>
            <?php if (!empty($_SESSION['rx3_intencao']) && empty($_SESSION['rx3_intencao']['aplicada']) && ($_SESSION['rx3_intencao']['rodada'] ?? 0) === $q['rodada']): ?>
                <small>💬 Nas Interações, você poderá colocar essa decisão em prática com <?php echo rx3Esc($_SESSION['rx3_intencao']['alvo']); ?>. Só então essa pessoa poderá reagir.</small>
            <?php else: ?>
                <small>🔒 É uma reflexão privada. Não alterou a afinidade nem revelou informações ocultas.</small>
            <?php endif; ?>
            <?php
            $posturas = (array)($_SESSION['rx3_posturas'] ?? []);
            if ($posturas) arsort($posturas);
            $rotulosPostura = [
                'diplomatico' => 'Diplomático', 'firme' => 'Firme', 'cauteloso' => 'Cauteloso',
                'combativo' => 'Combativo', 'afetivo' => 'Afetivo', 'estrategico' => 'Estrategista',
                'sincero' => 'Sincero', 'leal' => 'Leal', 'observador' => 'Observador',
                'assertivo' => 'Assertivo', 'sensivel' => 'Sensível', 'resiliente' => 'Resiliente',
                'leve' => 'Leve', 'competitivo' => 'Competitivo'
            ];
            $tomDominante = $posturas ? array_key_first($posturas) : null;
            ?>
            <?php if ($tomDominante): ?>
                <div class="rx3-identity">🧭 Seu estilo mais frequente nos depoimentos: <strong><?php echo rx3Esc($rotulosPostura[$tomDominante] ?? ucfirst($tomDominante)); ?></strong></div>
            <?php endif; ?>
        </div>
        <?php $historicoRx3 = array_slice((array)($_SESSION['rx3_historico'] ?? []), -5); ?>
        <?php if (count($historicoRx3) > 1): ?>
            <details class="rx3-history">
                <summary>📖 Rever meus últimos posicionamentos</summary>
                <ul>
                    <?php foreach (array_reverse($historicoRx3) as $registro): ?>
                        <li><b>Rodada <?php echo (int)($registro['rodada'] ?? 1); ?>:</b>
                        <?php echo rx3Esc((string)($registro['tema'] ?? 'Depoimento')); ?> —
                        <?php echo rx3Esc((string)($registro['resposta'] ?? '')); ?></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endif; ?>
    <?php endif; ?>
</section>
