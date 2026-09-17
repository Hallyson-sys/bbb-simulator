<?php

/** @var string $fase */

?>

<?php if ($fase == 'confessionario'): ?>

    <div class="confessionario-box">

        <h3>🎥 Confessionário da Rodada</h3>

        <p>
            A partir da Rodada 2, os participantes revelam pensamentos
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

    <form method="POST">

        <button class="btn" name="avancar_fase">
            ⏭️ Continuar para as Interações
        </button>

    </form>

<?php endif; ?>