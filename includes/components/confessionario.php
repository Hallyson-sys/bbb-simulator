<?php

/** @var string $fase */

?>

<?php if ($fase == 'confessionario'): ?>

    <div class="confessionario-box">

        <h3>🎥 Confessionário da Rodada</h3>

        <p>
            A partir da Semana 2, os participantes revelam pensamentos
            sobre alianças, rivalidades, romance e estratégia logo depois
            do Queridômetro.
        </p>

    </div>

    <div class="confessionario-grid">

        <?php if (!empty($_SESSION['confessionario_falas'])): ?>

            <?php foreach ($_SESSION['confessionario_falas'] as $falaConfessionario): ?>

                <div class="confessionario-fala">
                    <?php echo $falaConfessionario; ?>
                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="confessionario-fala">
                🎥 O confessionário ficou em silêncio nesta rodada.
            </div>

        <?php endif; ?>

    </div>

    <?php render('components/raio_x_estrategico'); ?>

    <?php $rx3Respondido = !empty($_SESSION['modo_espectador']) || !empty($_SESSION['rx3_quadro']['escolha']); ?>
    <form method="POST">

        <button class="btn" name="avancar_fase" <?php echo $rx3Respondido ? '' : 'disabled title="Escolha seu posicionamento primeiro" style="opacity:.45;cursor:not-allowed"'; ?>>
            ⏭️ Continuar para as Interações
        </button>

    </form>

<?php endif; ?>