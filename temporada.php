<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/includes/logica/historico_temporada.php';

garantirHistoricoTemporada();

if (empty($_SESSION['jogadores']) && empty($_SESSION['historico_temporada'])) {
    header('Location: index.php');
    exit;
}

$rodadaAtual = (int)($_SESSION['rodada'] ?? 1);
$historicoPorRodada = historicoTemporadaPorRodada();
$totalEventos = count($_SESSION['historico_temporada']);

function eTemp($texto)
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temporada - BBB Simulator</title>
    <link rel="stylesheet" href="assets/css/temporada.css">
</head>
<body>
<div class="temporada-page">
    <header class="temporada-topo">
        <div class="temporada-titulo">
            <small>BBB Simulator</small>
            <h1>📖 Histórico da Temporada</h1>
            <p>Uma linha do tempo com as provas, decisões, paredões e acontecimentos marcantes da sua temporada.</p>
        </div>
        <div class="temporada-acoes">
            <a class="temporada-btn" href="jogo.php">← Voltar para a casa</a>
        </div>
    </header>

    <section class="temporada-resumo">
        <div class="temporada-resumo-card"><strong><?= $rodadaAtual ?></strong><span>Rodada atual</span></div>
        <div class="temporada-resumo-card"><strong><?= count($historicoPorRodada) ?></strong><span>Rodadas registradas</span></div>
        <div class="temporada-resumo-card"><strong><?= $totalEventos ?></strong><span>Acontecimentos salvos</span></div>
    </section>

    <?php if (empty($historicoPorRodada)): ?>
        <div class="temporada-vazio">
            <h2>A temporada está só começando 👀</h2>
            <p>Os acontecimentos importantes aparecerão aqui conforme as próximas fases forem concluídas.</p>
        </div>
    <?php else: ?>
        <main class="temporada-timeline">
            <?php foreach ($historicoPorRodada as $numeroRodada => $eventos): ?>
                <section class="temporada-rodada">
                    <h2 class="temporada-rodada-titulo">
                        Rodada <?= (int)$numeroRodada ?>
                        <?php if ((int)$numeroRodada === $rodadaAtual): ?>
                            <span class="temporada-atual">rodada atual</span>
                        <?php endif; ?>
                    </h2>
                    <div class="temporada-eventos">
                        <?php foreach ($eventos as $evento): ?>
                            <article class="temporada-evento">
                                <div class="temporada-icone"><?= eTemp($evento['icone'] ?? '📌') ?></div>
                                <div>
                                    <h3><?= eTemp($evento['titulo'] ?? 'Acontecimento') ?></h3>
                                    <p><?= eTemp($evento['descricao'] ?? '') ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </main>
    <?php endif; ?>
</div>
</body>
</html>
