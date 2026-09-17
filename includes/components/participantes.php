<?php

/** @var array $jogadores */
/** @var string $meuNome */
/** @var int $rodada */

?>
<div class="left-col">

    <div class="left">

        <h2>👥 Participantes</h2>

        <div class="players-grid">

        <?php foreach ($jogadores as $j): ?>

            <?php

            $relacaoComVoce = 0;

            if (!nomeIgual(($j['nome'] ?? ''), $meuNome)) {
                $relacaoComVoce = $_SESSION['relacoes_jogador'][$j['nome']] ?? 0;
            }

            $classeRelacao = "neutra";

            if ($relacaoComVoce >= 30) {
                $classeRelacao = "positiva";
            } elseif ($relacaoComVoce <= -10) {
                $classeRelacao = "negativa";
            }

            ?>

            <div class="card <?php echo $classeRelacao; ?> <?php if (nomeIgual(($j['nome'] ?? ''), $meuNome)) echo 'voce'; ?>">

                <div class="avatar"></div>

                <h3>
                    <?php echo $j['nome']; ?>,
                    <?php echo $j['idade']; ?>

                    <?php
                    if (nomeIgual(($j['nome'] ?? ''), $meuNome)) {
                        echo " ⭐";
                    }
                    ?>
                </h3>

                <p>💼 <?php echo $j['profissao']; ?></p>

                <p>📍 <?php echo $j['estado']; ?></p>

                <p>🎭 <?php echo $j['personalidade']; ?></p>

                <?php if (!nomeIgual(($j['nome'] ?? ''), $meuNome)): ?>

<?php
$romanceComVoce = obterRomance($jogadores, $meuNome, $j['nome'] ?? '');
$statusRomanceCard = statusRomance(
    $romanceComVoce,
    $j['nome'] ?? '',
    $meuNome
);
?>

<p class="afinidade-card">
    ❤️ Afinidade: <?php echo $relacaoComVoce; ?>
</p>

<p class="romance-card">
    💕 Romance: <?php echo $romanceComVoce; ?>
    <?php
    if ($statusRomanceCard != '') {
        echo " — " . $statusRomanceCard;
    }
    ?>
</p>

<?php endif; ?>

<div class="status">

    <?php if (!empty($j['status']['lider'])) echo "<div class='lider'>👑 Líder</div>"; ?>
    <?php if (!empty($j['status']['anjo'])) echo "<div class='anjo'>😇 Anjo</div>"; ?>
    <?php if (!empty($j['status']['imune'])) echo "<div class='imune'>🛡️ Imune</div>"; ?>
    <?php if (!empty($j['status']['vip'])) echo "<div class='vip'>🟡 VIP</div>"; ?>
    <?php if (!empty($j['status']['xepa'])) echo "<div class='xepa'>🍞 Xepa</div>"; ?>
    <?php if (!empty($j['status']['monstro'])) echo "<div style='color:#ff4d4d;'>👹 Monstro</div>"; ?>
    <?php if (!empty($j['alianca'])) echo "<div class='alianca-status'>🤝 " . $j['alianca'] . "</div>"; ?>

</div>

            </div>

        <?php endforeach; ?>

        </div>

    </div>

    <?php
    $resumoAliancas = gerarResumoAliancas($jogadores);
    ?>

    <?php if (!empty($resumoAliancas)): ?>

    <div class="aliancas-mini-box">

        <div class="aliancas-mini-topo">
            <h3>🤝 Alianças da Casa</h3>
            <span><?php echo count($resumoAliancas); ?> grupo(s)</span>
        </div>

        <div class="aliancas-mini-lista">

            <?php foreach ($resumoAliancas as $nomeAlianca => $membros): ?>

                <div class="alianca-mini-card">

                    <div class="alianca-mini-header">
                        <strong><?php echo htmlspecialchars($nomeAlianca, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small><?php echo count($membros); ?> membro(s)</small>
                    </div>

                    <p>
                        <?php echo htmlspecialchars(
                            implode(", ", $membros),
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </p>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

    <?php endif; ?>

</div>