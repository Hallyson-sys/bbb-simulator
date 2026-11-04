<!DOCTYPE html>
<html lang="pt-BR"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#09091f"><title>Multiplayer Online | BBB Simulator</title>
<link rel="stylesheet" href="assets/css/jogo.css"><link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/responsivo.css?v=35"><link rel="stylesheet" href="assets/css/multiplayer_integrado.css?v=3">
<script src="assets/js/multiplayer.js?v=3" defer></script>
</head><body class="mp-app">
<header class="top-header"><div class="header-glow"></div><div class="header-content">
<div class="logo-area"><div class="bbb-icon">🎥</div><div class="brand-text"><h1>BBB Simulator</h1><div class="sub-info"><span>🌐 Multiplayer Online</span><span class="dot"></span><span>Beta</span></div></div></div>
<a href="index.php" class="mp-header-link">← Voltar ao modo solo</a></div></header>
<div class="mp-lobby-wrap">
    <div class="mobile-current-hero mp-hero mp-hero-main">
        <span class="mobile-current-kicker">SALA MULTIPLAYER</span>
        <h2>Jogue com seus amigos sem perder a cara do BBB Simulator</h2>
        <p>Crie uma sala por código, entre com o seu participante e siga para a temporada online. O visual e a proposta continuam dentro da identidade do jogo.</p>
        <div class="mp-hero-tags"><span>🔐 Sala privada</span><span>👥 10, 15 ou 20 pessoas</span><span>📱 Funciona no celular</span></div>
    </div>

    <div id="notice" class="mp-notice" hidden role="status" aria-live="polite"></div>

    <section id="entry" class="center mp-lobby-card mp-entry-card">
        <div class="mp-card-head">
            <div>
                <span class="mp-mini-kicker">ENTRADA ONLINE</span>
                <h3>Entre na casa multiplayer</h3>
                <p>Escolha se você vai criar uma sala nova ou entrar com um código já existente.</p>
            </div>
        </div>

        <div class="mp-tabs">
            <button class="mp-tab active" id="tabCreate" type="button">✦ Criar sala</button>
            <button class="mp-tab" id="tabJoin" type="button">↗ Entrar com código</button>
        </div>

        <form id="roomForm" autocomplete="off">
            <label for="displayName">Nome do seu participante</label>
            <input id="displayName" required minlength="2" maxlength="28" placeholder="Ex.: Hallyson, Duda, Miguel...">
            <p class="mp-field-hint">Esse é o nome que vai aparecer para os outros jogadores dentro da casa. Nesta etapa, você está definindo apenas como seu participante será identificado no multiplayer.</p>

            <div id="createFields">
                <label for="capacity">Capacidade da sala</label>
                <select id="capacity">
                    <option value="10">10 participantes</option>
                    <option value="15">15 participantes</option>
                    <option value="20">20 participantes</option>
                </select>
                <p class="mp-field-hint">Depois de criar, você recebe o código da sala para compartilhar com seus amigos.</p>
            </div>

            <div id="joinFields" hidden>
                <label for="roomCode">Código da sala</label>
                <input id="roomCode" maxlength="6" minlength="6" placeholder="Ex.: ABC234" autocapitalize="characters" spellcheck="false">
                <p class="mp-field-hint">Digite o código que o anfitrião enviou para entrar direto na mesma sala.</p>
            </div>

            <button class="btn mp-main-btn" id="submitRoom" type="submit">CRIAR MINHA SALA →</button>
        </form>

        <div class="mp-subcard-grid">
            <div class="mp-subcard"><strong>🎮 Experiência</strong><span>Mesma proposta do BBB Simulator, agora com pessoas reais.</span></div>
            <div class="mp-subcard"><strong>⚠️ Versão beta</strong><span>As partidas ainda são temporárias e podem ser perdidas se o serviço reiniciar.</span></div>
        </div>
    </section>

    <section id="room" class="center mp-lobby-card mp-room-card" hidden>
        <div class="mobile-current-hero mp-hero">
            <span class="mobile-current-kicker">● REC • SALA PRIVADA</span>
            <h2 id="roomCodeDisplay">------</h2>
            <p id="phaseDescription">Convide seus amigos, acompanhe quem entrou e inicie a temporada quando todos estiverem prontos.</p>
        </div>
        <div class="mp-share-row"><button type="button" id="copyCode" class="btn mp-secondary">📋 Copiar código</button><button type="button" id="shareLink" class="btn mp-secondary">🔗 Compartilhar link</button></div>
        <div class="box mp-room-box"><h3>👥 Participantes <span id="countLabel">0/10</span></h3><p id="progressLabel">Aguardando...</p><div class="mp-progress"><div id="progressBar"></div></div><div class="mp-lobby-people" id="playersList"></div></div>
        <div class="mp-lobby-actions"><button id="readyButton" type="button" class="btn mp-main-btn">✓ ESTOU PRONTO</button><div id="hostControls" hidden>
<label for="duration">Duração das interações por fase</label><select id="duration"><option value="90">1min30 — Rápido</option><option value="180" selected>3min — Normal</option><option value="300">5min — Longo</option></select>
<button id="startButton" class="btn mp-main-btn" type="button">▶ INICIAR TEMPORADA ONLINE</button><p class="mp-subtext">Nesta beta, 2 pessoas já permitem testar. O limite de vagas continua o escolhido.</p></div>
<p id="lobbyHint" class="mp-subtext">Quando todos confirmarem, o anfitrião poderá iniciar.</p></div>
<button id="leaveView" type="button" class="mp-text-link">← Voltar para criar ou entrar em outra sala</button></section>
</div>
</body></html>
