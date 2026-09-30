<?php

/** @var string $fase */
/** @var int $rodada */
/** @var array $jogadores */
/** @var string $meuNome */
/** @var int $qtdVIP */
/** @var array $EMOJIS_QUERIDOMETRO */

?>

<div class="center">

    <h2>🎮 Controle da Semana</h2>

    <div style="margin-bottom: 14px;">
        <a href="temporada.php" class="btn" style="display:inline-block;text-decoration:none;">📖 Temporada</a>
    </div>

    <div class="box">
        🎯 Fase atual:
        <b>
            <?php echo strtoupper(
                str_replace("_", " ", $fase)
            ); ?>
        </b>

        <br>

        🔥 Rodada:
        <?php echo $rodada; ?>
    </div>

    <?php

    render(
        'components/painel/controle_semana',
        [
            'fase' => $fase,
            'rodada' => $rodada,
            'jogadores' => $jogadores,
            'meuNome' => $meuNome,
            'qtdVIP' => $qtdVIP,
            'EMOJIS_QUERIDOMETRO' => $EMOJIS_QUERIDOMETRO
        ]
    );

    ?>

</div>