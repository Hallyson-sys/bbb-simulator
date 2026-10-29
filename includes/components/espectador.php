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
            🔥 Semana atual:
            <b><?php echo (int)$rodada; ?></b>
            ·
            👥 Restam:
            <b><?php echo count($jogadores); ?></b>
        </p>
    </div>

    <div class="box" style="border:1px solid rgba(222,104,235,.4);background:linear-gradient(125deg,rgba(140,55,170,.16),rgba(44,29,91,.18));">
        <h3>🎤 Bate-Papo com os Eliminados</h3>
        <p>Reveja as entrevistas de quem já saiu. Veja primeiro os depoimentos de amigos, aliados e interesses amorosos — e confira novas respostas conforme a temporada avança.</p>
        <a class="btn" href="bate_papo.php?arquivo=1#arquivo" style="display:block;text-align:center;text-decoration:none;">📼 Abrir arquivo de entrevistas</a>
        <?php
        require_once __DIR__ . '/../logica/bate_papo_eliminado.php';
        bpPrepararArquivo();
        $recentes = array_reverse((array)($_SESSION['historico_eliminados'] ?? []));
        $recentes = array_slice($recentes, 0, 3);
        ?>
        <?php foreach ($recentes as $ex):
            $ent = $_SESSION['bp_entrevistas'][$ex] ?? null;
            if (!is_array($ent) || empty($ent['concluida'])) continue;
            [$tag] = bpDestaqueParaMim((string)$ex);
        ?>
        <p><a href="bate_papo.php?nome=<?= rawurlencode((string)$ex) ?>"><b><?= bpEsc($ex) ?></b> — <?= bpEsc($tag) ?> · Semana <?= (int)$ent['semana'] ?></a></p>
        <?php endforeach; ?>
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
