<?php

/** @var array $jogadores */
/** @var string $fase */
/** @var int $rodada */
/** @var string $meuNome */
/** @var int $qtdVIP */

?>

<?php if ($fase == 'vip_xepa' && ($_SESSION['lider'] ?? '') == $meuNome && !isset($_SESSION['vip_definido'])): ?>

<div class="box">

    <h3>
        👑 Você é o Líder! Escolha <?php echo $qtdVIP; ?> participantes para o VIP
    </h3>

</div>

<form method="POST">

    <div class="participantes-escolha">

        <?php foreach ($jogadores as $j): ?>

            <?php if ($j['nome'] == $meuNome) continue; ?>

            <label class="participante-btn">

                <input
                    type="checkbox"
                    name="vip[]"
                    value="<?php echo $j['nome']; ?>"
                    onclick="limitarVIP(this)"
                >

                <?php echo $j['nome']; ?>

            </label>

        <?php endforeach; ?>

    </div>

    <button class="btn" name="definir_vip">
        Confirmar VIP
    </button>

</form>

<script>

function limitarVIP(clicado){

    let limite = <?php echo $qtdVIP; ?>;

    let marcados = document.querySelectorAll('input[name="vip[]"]:checked');

    if(marcados.length > limite){
        clicado.checked = false;
        alert("Você pode escolher apenas " + limite + " participantes.");
    }

}

</script>

<?php endif; ?>