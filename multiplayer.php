<!DOCTYPE html>
<html lang="pt-BR"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#09091f"><title>Multiplayer Online | BBB Simulator</title>
<link rel="stylesheet" href="assets/css/jogo.css"><link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/responsivo.css?v=34"><link rel="stylesheet" href="assets/css/multiplayer_integrado.css?v=2">
<script src="assets/js/multiplayer.js?v=2" defer></script>
</head><body class="mp-app">
<header class="top-header"><div class="header-glow"></div><div class="header-content">
<div class="logo-area"><div class="bbb-icon">🎥</div><div class="brand-text"><h1>BBB Simulator</h1><div class="sub-info"><span>🌐 Multiplayer Online</span><span class="dot"></span><span>Beta 2</span></div></div></div>
<a href="index.php" class="mp-header-link">← Modo solo</a></div></header>
<div class="mp-lobby-wrap"><div class="mobile-current-hero mp-hero"><span class="mobile-current-kicker">SALA MULTIPLAYER</span><h2>Uma casa. Vários jogadores.</h2><p>O mesmo BBB Simulator, agora com seus amigos decidindo o jogo.</p></div>
<div id="notice" class="mp-notice" hidden role="status" aria-live="polite"></div>
<section id="entry" class="center mp-lobby-card"><div class="mp-tabs"><button class="mp-tab active" id="tabCreate" type="button">✦ Criar sala</button><button class="mp-tab" id="tabJoin" type="button">↗ Entrar na sala</button></div>
<form id="roomForm" autocomplete="off"><label for="displayName">Seu nome no reality</label><input id="displayName" required minlength="2" maxlength="28" placeholder="Como vão te chamar na casa?">
<div id="createFields"><label for="capacity">Capacidade da sala</label><select id="capacity"><option value="10">10 participantes</option><option value="15">15 participantes</option><option value="20">20 participantes</option></select></div>
<div id="joinFields" hidden><label for="roomCode">Código da sala</label><input id="roomCode" maxlength="6" minlength="6" placeholder="EX.: ABC234" autocapitalize="characters" spellcheck="false"></div>
<button class="btn mp-main-btn" id="submitRoom" type="submit">CRIAR MINHA SALA →</button></form>
<p class="mp-subtext">🔐 Salas privadas por código. Não precisa criar conta nem contratar banco de dados. As partidas são temporárias nesta versão.</p></section>
<section id="room" class="center mp-lobby-card" hidden>
<div class="mobile-current-hero mp-hero"><span class="mobile-current-kicker">● REC • SALA PRIVADA</span><h2 id="roomCodeDisplay">------</h2><p id="phaseDescription">Convide seus amigos para participar.</p></div>
<div class="mp-share-row"><button type="button" id="copyCode" class="btn mp-secondary">📋 Copiar código</button><button type="button" id="shareLink" class="btn mp-secondary">🔗 Compartilhar link</button></div>
<div class="box"><h3>👥 Participantes <span id="countLabel">0/10</span></h3><p id="progressLabel">Aguardando...</p><div class="mp-progress"><div id="progressBar"></div></div><div class="mp-lobby-people" id="playersList"></div></div>
<div class="mp-lobby-actions"><button id="readyButton" type="button" class="btn mp-main-btn">✓ ESTOU PRONTO</button><div id="hostControls" hidden>
<label for="duration">Duração das interações por fase</label><select id="duration"><option value="90">1min30 — Rápido</option><option value="180" selected>3min — Normal</option><option value="300">5min — Longo</option></select>
<button id="startButton" class="btn mp-main-btn" type="button">▶ INICIAR TEMPORADA ONLINE</button><p class="mp-subtext">Nesta beta, 2 pessoas já permitem testar. O limite de vagas continua o escolhido.</p></div>
<p id="lobbyHint" class="mp-subtext">Quando todos confirmarem, o anfitrião pode iniciar.</p></div>
<button id="leaveView" type="button" class="mp-text-link">← Voltar à entrada</button></section>
<div class="mp-disclaimer">⚠️ Versão de testes: não conta com saves permanentes. Um reinício do serviço gratuito pode apagar as salas.</div></div>
</body></html>
