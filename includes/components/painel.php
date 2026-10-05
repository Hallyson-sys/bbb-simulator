<?php

/** @var string $fase */
/** @var int $rodada */
/** @var array $jogadores */
/** @var string $meuNome */
/** @var int $qtdVIP */
/** @var array $EMOJIS_QUERIDOMETRO */

?>

<?php
$rotulosFaseMobile = [
    'lider' => '👑 Prova do Líder',
    'anjo' => '😇 Prova do Anjo',
    'queridometro' => '💟 Queridômetro',
    'discordia' => '🔥 Jogo da Discórdia',
    'paredao' => '🔥 Formação do Paredão',
    'bate_volta' => '🏃 Bate-Volta',
    'festa' => '🎉 Festa',
    'interacoes' => '💬 Vida na Casa',
    'vip_xepa' => '🍽️ VIP & Xepa',
    'monstro' => '👹 Castigo do Monstro',
    'curinga' => '🃏 Poder Curinga'
];
$nomeFaseMobile = $rotulosFaseMobile[$fase] ?? ('🎮 ' . ucwords(str_replace('_', ' ', $fase)));
?>
<div class="center mobile-panel mobile-panel-current" id="mobileJogo" data-mobile-panel="jogo">

    <div class="mobile-current-hero">
        <span class="mobile-current-kicker">DINÂMICA ATUAL</span>
        <h2><?= htmlspecialchars($nomeFaseMobile, ENT_QUOTES, 'UTF-8') ?></h2>
        <p>Rodada <?= (int)$rodada ?> • continue a dinâmica por aqui</p>
    </div>

    <h2 class="desktop-panel-title">🎮 Controle da Semana</h2>

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