<?php /** @var string $meuNome */ ?>
<nav class="mobile-bottom-nav" aria-label="Navegação mobile">
    <button type="button" class="mobile-nav-item ativo" data-mobile-nav="jogo" onclick="mobileIrPara('mobileJogo', this)">
        <span>🏠</span><small>Jogo</small>
    </button>
    <button type="button" class="mobile-nav-item" data-mobile-nav="casa" onclick="mobileAbrirPainel('casa', 'mobileCasa', this)">
        <span>👥</span><small>Casa</small>
    </button>
    <button type="button" class="mobile-nav-item mobile-nav-live" data-mobile-nav="aovivo" onclick="mobileAbrirAoVivo(this)">
        <span class="mobile-live-icon"><i></i>📺</span><small>Ao Vivo</small>
    </button>
    <a class="mobile-nav-item" href="temporada.php">
        <span>📖</span><small>Temporada</small>
    </a>
    <button type="button" class="mobile-nav-item" onclick="abrirFeedPublico()">
        <span>📱</span><small>Feed</small>
    </button>
    <button type="button" class="mobile-nav-item" onclick="abrirSave2()">
        <span>💾</span><small>Save</small>
    </button>
</nav>
