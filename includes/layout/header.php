<?php

/** @var int $rodada */
/** @var array $jogadores */

$totalPostsFeed = count(
    $_SESSION['feed_publico'] ?? []
);

?>

<header class="top-header">

    <div class="header-glow"></div>

    <div class="header-content">

        <!-- =============================================
             LOGO / INFORMAÇÕES
        ============================================== -->

        <div class="logo-area">

            <div class="bbb-icon">
                🎥
            </div>

            <div class="brand-text">

                <h1>BBB Simulator</h1>

                <div class="sub-info">

                    <span>
                        🔥 Rodada <?= $rodada ?>
                    </span>

                    <span class="dot"></span>

                    <span>
                        👥 <?= count($jogadores) ?>
                        participantes restantes
                    </span>

                    <span class="dot"></span>

                    <span>
                        🪙 <?= obterMoedasPublico() ?>
                        moedas
                    </span>

                </div>

            </div>

        </div>


        <!-- =============================================
             FEED BBB
        ============================================== -->

        <div class="header-actions">

            <button
                type="button"
                class="header-feed-btn"
                onclick="abrirFeedPublico()"
            >

                <span class="header-feed-icon">
                    📱
                </span>

                <span class="header-feed-info">

                    <strong>
                        Feed BBB
                    </strong>

                    <small>
                        <?= $totalPostsFeed ?>
                        <?= $totalPostsFeed == 1 ? 'post' : 'posts' ?>
                        do público
                    </small>

                </span>

                <span class="header-feed-live"></span>

                <span class="header-feed-arrow">
                    →
                </span>

            </button>

            <?php
$hostAtual =
    strtolower(
        $_SERVER['HTTP_HOST']
        ?? ''
    );

$ehLocal =
    str_contains(
        $hostAtual,
        'localhost'
    ) ||
    str_contains(
        $hostAtual,
        '127.0.0.1'
    );
?>

<?php if ($ehLocal): ?>

    <a
        href="modo_teste.php"
        style="
            margin-left:auto;
            text-decoration:none;
            padding:10px 14px;
            border-radius:12px;
            color:#fff;
            font-weight:800;
            background:linear-gradient(
                90deg,
                #d00074,
                #7535ea
            );
        "
    >
        🧪 DEV
    </a>

<?php endif; ?>

        </div>

    </div>

</header>