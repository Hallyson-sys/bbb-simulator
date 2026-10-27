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
                        🔥 Semana <?= $rodada ?>
                    </span>

                    <span class="dot"></span>

                    <span>
                        👥 <?= count($jogadores) ?>
                        participantes restantes
                    </span>

                    <span class="dot"></span>

                    <span class="header-coins">
                        🪙 <?= obterMoedasPublico() ?>
                        moedas
                    </span>

                </div>

            </div>

        </div>


        <!-- =============================================
             FEED BBB
        ============================================== -->

        <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Abrir menu" aria-expanded="false">☰</button>

        <div class="mobile-header-menu" id="mobileHeaderMenu" hidden>
            <a href="temporada.php">📖 Histórico da Temporada</a>
            <a href="temporada.php#raio-x">📊 Raio-X da Semana</a>
            <?php if (!empty($_SESSION['meu_nome'])): ?>
                <a href="perfil.php?nome=<?= rawurlencode($_SESSION['meu_nome']) ?>">👤 Meu Perfil</a>
            <?php endif; ?>
            <button type="button" onclick="abrirFeedPublico(); fecharMenuMobile();">📱 Feed BBB</button>
            <button type="button" onclick="abrirSave2(); fecharMenuMobile();">💾 Saves</button>
            <button type="button" class="mobile-menu-danger" onclick="abrirPopup(); fecharMenuMobile();">🔄 Novo Jogo</button>
        </div>

        <div class="header-actions">
            <a class="header-raiox-btn header-feed-btn" href="temporada.php#raio-x" title="Ver Raio-X e Reputação Dinâmica"><span class="header-feed-icon">📊</span><span class="header-feed-info"><strong>Raio-X</strong><small>Resumo da semana</small></span><span class="header-feed-arrow">→</span></a>

            <button
                type="button"
                class="header-save-btn"
                id="btnAbrirSave2"
                onclick="abrirSave2()"
            >
                <span class="header-save-icon">💾</span>
                <span class="header-save-info">
                    <strong id="saveStatusTexto">Save</strong>
                    <small id="saveStatusHora">Preparando autosave...</small>
                </span>
            </button>

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
            // A central DEV só deve ficar visível na execução local.
            $devLocal = (bool)preg_match('/^(?:localhost|127\.0\.0\.1|\[::1\])(?::[0-9]{1,5})?$/i',
                (string)($_SERVER['HTTP_HOST'] ?? '')) &&
                in_array((string)($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1','::1'], true);
            ?>
            <?php if ($devLocal): ?>
                <a href="modo_teste.php" class="header-feed-btn header-dev-btn" title="Abrir laboratório de testes local">
                    <span class="header-feed-icon">🧪</span>
                    <span class="header-feed-info"><strong>Modo DEV</strong><small>Testar dinâmicas</small></span>
                    <span class="header-feed-arrow">→</span>
                </a>
            <?php endif; ?>

        </div>

    </div>

</header>