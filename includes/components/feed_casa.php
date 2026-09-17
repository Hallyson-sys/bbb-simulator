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
            foreach ($_SESSION['evento_extra'] as $ev) {
                echo "<p>$ev</p>";
            }
        } else {
            echo "<p>📡 Nenhum acontecimento ainda.</p>";
        }
        ?>

    </div>

</div>