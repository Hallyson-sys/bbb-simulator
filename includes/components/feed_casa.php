<div class="ao-vivo-card">

    <h2>📢 Ao Vivo</h2>

    <form method="POST">
        <button class="btn novo" name="limpar_log">
            🧹 Limpar Ao Vivo
        </button>
    </form>

    <div class="log" id="aoVivoLog">

        <?php
        if (!empty($_SESSION['evento_extra'])) {
            $totalEventosAoVivo = count($_SESSION['evento_extra']);
            foreach ($_SESSION['evento_extra'] as $indiceEvento => $ev) {
                $classeUltimo = ($indiceEvento === $totalEventosAoVivo - 1) ? ' class="ao-vivo-ultimo" data-ao-vivo-ultimo="1"' : '';
                echo "<p{$classeUltimo}>$ev</p>";
            }
        } else {
            echo "<p>📡 Nenhum acontecimento ainda.</p>";
        }
        ?>

    </div>

</div>