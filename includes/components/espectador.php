<?php

/** @var array $jogadores */
/** @var int $rodada */

$resumo =
    $_SESSION['resumo_espectador_ultima_rodada']
    ?? [];

$ranking =
    $_SESSION['ultimo_ranking_espectador']
    ?? [];

$ultimoEliminado =
    $_SESSION['ultimo_eliminado_espectador']
    ?? '';

?>

<div class="center">

    <div class="box espectador-box">
        <h2>👁️ Modo Espectador</h2>

        <p>
            Sua participação acabou, mas a temporada continua.
            Agora você acompanha as decisões dos NPCs até descobrir o campeão.
        </p>

        <p>
            🔥 Rodada atual:
            <b><?php echo (int)$rodada; ?></b>
            ·
            👥 Restam:
            <b><?php echo count($jogadores); ?></b>
        </p>
    </div>

    <?php if (!empty($resumo)): ?>
        <div class="box">
            <h3>📺 Resumo da última semana</h3>

            <div class="log">
                <?php foreach ($resumo as $evento): ?>
                    <p>
                        <?php echo htmlspecialchars(
                            (string)$evento,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </p>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (
        $ultimoEliminado !== '' &&
        !empty($ranking)
    ): ?>
        <div class="box">
            <h3>🚨 Última eliminação</h3>

            <p>
                <b><?php echo htmlspecialchars(
                    $ultimoEliminado,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?></b>
                deixou a casa.
            </p>

            <?php foreach ($ranking as $nome => $pct): ?>
                <p>
                    <?php echo htmlspecialchars(
                        $nome,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                    —
                    <b>
                        <?php echo number_format(
                            (float)$pct,
                            2,
                            ',',
                            '.'
                        ); ?>%
                    </b>
                </p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (count($jogadores) > 3): ?>
        <form method="POST">
            <button
                class="btn"
                name="simular_rodada_espectador"
            >
                📺 Assistir Próxima Semana
            </button>
        </form>
    <?php else: ?>
        <div class="box">
            <h3>🏆 Finalistas definidos!</h3>
            <p>
                Restam três participantes. É hora de descobrir quem vence a temporada.
            </p>
        </div>

        <form method="POST">
            <button class="btn" name="ir_final">
                🏆 Ir para Grande Final
            </button>
        </form>
    <?php endif; ?>

    <form
        method="POST"
        onsubmit="localStorage.removeItem('bbb_simulator_save_v1')"
    >
        <button class="btn novo" name="novo_jogo">
            🔄 Encerrar e começar outra temporada
        </button>
    </form>

</div>
