<!DOCTYPE html><html lang="pt-BR"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#09091f"><title>BBB Simulator • Temporada Online</title>
<link rel="stylesheet" href="assets/css/jogo.css"><link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/interacoes_4.css?v=1"><link rel="stylesheet" href="assets/css/responsivo.css?v=34">
<link rel="stylesheet" href="assets/css/multiplayer_integrado.css?v=4">
<script src="assets/js/multiplayer_jogo.js?v=4" defer></script>
</head><body class="mp-app mp-game">
<header class="top-header"><div class="header-glow"></div><div class="header-content">
<div class="logo-area"><div class="bbb-icon">🎥</div><div class="brand-text"><h1>BBB Simulator</h1><div class="sub-info"><span id="headWeek">🔥 Semana 1</span><span class="dot"></span><span id="headCount">👥 Online</span></div></div></div>
<button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Abrir menu" aria-expanded="false">☰</button>
<div class="mobile-header-menu" id="mobileHeaderMenu" hidden><a href="multiplayer.php">🌐 Minha sala</a><a href="index.php">🏠 Modo solo</a><button type="button" id="menuCasa">👥 Participantes</button><button type="button" id="menuAoVivo">📺 Na Casa</button></div>
<a class="mp-header-link" href="multiplayer.php">🌐 Sala online</a>
</div></header>
<div id="notice" class="mp-notice mp-global-notice" role="status" aria-live="polite" hidden></div>
<div class="container">
<div class="left-col mobile-panel mobile-collapsed" id="mobileCasa" data-mobile-panel="casa">
<button class="mobile-panel-toggle" type="button" data-target="mobileCasa" aria-expanded="false"><span>👥 <strong>Participantes</strong></span><span class="mobile-panel-chevron">⌄</span></button>
<section class="left"><h2>👥 Participantes</h2><div class="players-grid" id="playersGrid"></div><div class="mp-mini-hint" id="playersStatus"></div></section></div>
<main class="center mobile-panel mobile-panel-current" id="mobileJogo" data-mobile-panel="jogo">
<div class="mobile-current-hero"><span class="mobile-current-kicker">DINÂMICA ATUAL • MULTIPLAYER</span><h2 id="phaseTitle">💬 Interações</h2><p id="phaseSubtitle">Semana 1 • Todos jogam simultaneamente</p></div>
<h2 class="desktop-panel-title">🎮 Controle da Semana</h2>
<div class="box mp-phase-info"><div class="mp-phase-stats"><span id="weekLabel">🔥 Semana 1</span><span id="phaseReady">0/0 concluíram</span></div><div class="mp-timer"><span>⏳ Tempo restante</span><strong id="timer">03:00</strong></div><div class="mp-progress"><div id="phaseProgress"></div></div></div>
<div class="box mp-timeline-box"><h3>📖 Etapas da semana</h3><div id="timeline" class="mp-timeline"></div></div>
<div id="pendingSection" class="i4-dialogo" hidden><div class="i4-kicker">● REC • CONVITES PARA VOCÊ</div><h3>📩 Alguém quer conversar</h3><div id="pendingList"></div></div>
<section id="interactionsSection" hidden><div class="box"><h3 id="actionsTitle">💬 Interações — 3 restantes</h3><p>Você pode fazer suas ações ou pular o restante. A próxima etapa começa quando todos terminarem e os convites forem respondidos, ou quando o tempo acabar.</p></div>
<div class="i4-categorias" id="categories"><div class="i4-grupos" id="categoryGroups"></div></div>
<div class="i4-selecao" id="interactionForm" hidden><div class="i4-kicker">CONVERSAS VIVAS • ESCOLHA COMO AGIR</div><h3 id="chosenTitle">Conversa</h3><label for="targetPlayer">Com quem você quer interagir?</label><select id="targetPlayer"></select>
<label for="actionSubject">Qual assunto ou atividade?</label><select id="actionSubject"></select>
<label for="actionApproach">Como você vai abordar?</label><select id="actionApproach"><option value="sincera">❤️ Sincera</option><option value="cautelosa">🧠 Cautelosa</option><option value="leve">🙂 Leve e descontraída</option></select>
<button id="interactButton" type="button" class="btn mp-main-btn">💬 Enviar convite</button><button id="cancelAction" type="button" class="btn mp-secondary">← Voltar às categorias</button><small>O outro jogador decide como responder no próprio aparelho. Convites não são aceitos automaticamente.</small></div>
<button class="btn mp-secondary mp-finish-btn" id="finishButton" type="button">⏭ Pular restante e finalizar</button><div id="waitingHint" class="i4-dialogo mp-wait" hidden><h3>⏳ Aguardando os outros jogadores...</h3><p>Você finalizou suas interações. Aguarde as respostas aos convites e o fim da rodada.</p></div>
<div id="sentSection" class="i4-historico" hidden><h3>📓 Minhas conversas</h3><div id="sentList"></div></div></section>
<section id="phaseControls" class="mp-controls" hidden></section>
<section id="spectatorSection" class="i4-dialogo" hidden><h3>📺 Modo espectador</h3><p>Você foi eliminado. Pode acompanhar o andamento da temporada, mas não participa mais das decisões.</p></section>
</main>
<aside class="right mobile-panel mobile-collapsed" id="mobileAoVivo" data-mobile-panel="aovivo">
<button class="mobile-panel-toggle" type="button" data-target="mobileAoVivo" aria-expanded="false"><span>📺 <strong>Na Casa</strong></span><span class="mobile-panel-chevron">⌄</span></button>
<h2 class="mp-live-title">📺 Na Casa • Ao Vivo</h2><div class="mp-live-tag">● REC • ACONTECENDO AGORA</div><ul id="eventsList" class="mp-events"></ul></aside>
<aside class="mp-feed-panel mobile-panel mobile-collapsed" id="mobileFeed" data-mobile-panel="feed">
<button class="mobile-panel-toggle" type="button" data-target="mobileFeed" aria-expanded="false"><span>📱 <strong>Feed BBB</strong></span><span class="mobile-panel-chevron">⌄</span></button>
<h2>📱 Feed BBB • Online</h2><p class="mp-mini-hint">Acompanhe os destaques da temporada em formato de posts. O Feed 3.0 completo do modo solo será adaptado depois.</p><div id="feedPosts" class="mp-feed-posts"></div></aside>
</div>
<nav class="mobile-bottom-nav" aria-label="Navegação online">
<button type="button" class="mobile-nav-item ativo" data-view="mobileJogo"><span>🏠</span><small>Jogo</small></button>
<button type="button" class="mobile-nav-item" data-view="mobileCasa"><span>👥</span><small>Casa</small></button>
<button type="button" class="mobile-nav-item mobile-nav-live" data-view="mobileAoVivo"><span class="mobile-live-icon"><i></i>📺</span><small>Ao Vivo</small></button>
<button type="button" class="mobile-nav-item" data-view="timeline"><span>📖</span><small>Semana</small></button>
<button type="button" class="mobile-nav-item" data-view="mobileFeed"><span>📱</span><small>Feed</small></button>
<a class="mobile-nav-item" href="multiplayer.php"><span>🌐</span><small>Sala</small></a>
</nav></body></html>
