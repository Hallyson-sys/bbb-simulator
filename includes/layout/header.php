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

        </div>

    </div>

</header>