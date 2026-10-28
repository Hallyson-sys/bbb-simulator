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
    'bigfone' => '☎️ Big Fone'
];
$nomeFaseMobile = $rotulosFaseMobile[$fase] ?? ('🎮 ' . ucwords(str_replace('_', ' ', $fase)));
?>
<div class="center mobile-panel mobile-panel-current" id="mobileJogo" data-mobile-panel="jogo">

    <div class="mobile-current-hero">
        <span class="mobile-current-kicker">DINÂMICA ATUAL</span>
        <h2><?= htmlspecialchars($nomeFaseMobile, ENT_QUOTES, 'UTF-8') ?></h2>
        <p>Semana <?= (int)$rodada ?> • continue a dinâmica por aqui</p>
    </div>

    <h2 class="desktop-panel-title">🎮 Controle da Semana</h2>
    <?php if (function_exists('bbbRetaFinalSemAnjo') && bbbRetaFinalSemAnjo($jogadores)): ?>
      <div class="box" style="border:1px solid #be6af0;background:linear-gradient(120deg,#261035,#12192f);margin-bottom:12px;">
        🏁 <strong>Reta Final — Top 8</strong><br>
        <span style="font-size:.91em;opacity:.9">Sem Prova do Anjo e Monstro. Big Fone, Liderança e dinâmicas surpresa continuam!</span>
      </div>
    <?php endif; ?>

    <div style="margin-bottom: 14px;">
        <a href="temporada.php" class="btn" style="display:inline-block;text-decoration:none;">📖 Temporada</a>
    </div>

    <?php $dsAtual = function_exists('dsAtual') ? dsAtual() : []; ?>
    <?php if (!empty($dsAtual['tipo']) && isset(dsDescricoes()[$dsAtual['tipo']])): $aviso = dsDescricoes()[$dsAtual['tipo']]; ?>
        <section class="ds3-aviso" aria-label="Dinâmica surpresa da produção">
            <div class="ds3-selo">● REC • COMUNICADO DA PRODUÇÃO • SEMANA <?= (int)$rodada ?></div>
            <h3><?= htmlspecialchars($aviso['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars($aviso['descricao'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php $estadoDin = function_exists('dsResumoPublico') ? dsResumoPublico() : ''; ?>
            <?php if($estadoDin !== ''): ?><p class="ds3-estado" role="status"><?= htmlspecialchars($estadoDin,ENT_QUOTES,'UTF-8') ?></p><?php endif; ?>
        </section>
    <?php endif; ?>

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

    <?php if (!empty($_SESSION['es2_festa_feedback'])): ?>
    <div class="es2-feedback" role="status"><h3>🎬 Conversa na festa</h3><p><?=es2Esc($_SESSION['es2_festa_feedback'])?></p></div>
    <?php unset($_SESSION['es2_festa_feedback']); endif; ?>

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