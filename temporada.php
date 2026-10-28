<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/includes/logica/historico_temporada.php';
require_once __DIR__ . '/includes/logica/raio_x.php';

garantirHistoricoTemporada();

if (empty($_SESSION['jogadores']) && empty($_SESSION['historico_temporada'])) {
    header('Location: index.php');
    exit;
}

$rodadaAtual = (int)($_SESSION['rodada'] ?? 1);
$historicoPorSemana = historicoTemporadaPorRodada();
$historicoPorSemana = is_array($historicoPorSemana) ? $historicoPorSemana : [];
$totalEventos = count($_SESSION['historico_temporada']);

// Seleção por rodada sem modificar o estado da temporada.
$rodadasDisponiveis = array_unique(array_merge([$rodadaAtual], array_keys($historicoPorSemana)));
$rodadasDisponiveis = array_values(array_filter(array_map('intval', $rodadasDisponiveis),
    static fn($r) => $r >= 1 && $r <= $rodadaAtual));
rsort($rodadasDisponiveis, SORT_NUMERIC);
$rodadaSelecionada = (int)($_GET['rodada'] ?? $rodadasDisponiveis[0] ?? $rodadaAtual);
if (!in_array($rodadaSelecionada, $rodadasDisponiveis, true)) {
    $rodadaSelecionada = $rodadasDisponiveis[0] ?? $rodadaAtual;
}
$raioX = rxResumoSemanal($_SESSION['historico_temporada'], $rodadaSelecionada);

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
    <link rel="stylesheet" href="assets/css/temporada.css?v=raiox1">
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
            <a class="temporada-btn" href="#raio-x">📊 Raio-X</a>
            <a class="temporada-btn" href="jogo.php">← Voltar para a casa</a>
        </div>
    </header>

    <section class="temporada-resumo">
        <div class="temporada-resumo-card"><strong><?= $rodadaAtual ?></strong><span>Semana atual</span></div>
        <div class="temporada-resumo-card"><strong><?= count($historicoPorSemana) ?></strong><span>Semanas registradas</span></div>
        <div class="temporada-resumo-card"><strong><?= $totalEventos ?></strong><span>Acontecimentos salvos</span></div>
    </section>

    <section class="raiox-secao" id="raio-x" aria-labelledby="raiox-titulo">
        <div class="raiox-cabecalho">
            <div>
                <small>EDIÇÃO EM ANDAMENTO • O QUE A CASA VIVEU</small>
                <h2 id="raiox-titulo">📊 Raio-X da Semana</h2>
                <p>Acontecimentos públicos da semana e reputações construídas por atitudes observáveis. <strong>A aprovação do público permanece secreta.</strong></p>
            </div>
            <div class="raiox-rodadas" aria-label="Consultar rodada">
                <?php foreach ($rodadasDisponiveis as $numero): ?>
                    <a href="temporada.php?rodada=<?= (int)$numero ?>#raio-x"
                       class="raiox-rodada-link <?= $numero === $rodadaSelecionada ? 'selecionada' : '' ?>"
                       <?= $numero === $rodadaSelecionada ? 'aria-current="page"' : '' ?>>
                       Semana <?= (int)$numero ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="raiox-visao-geral">
            <article class="raiox-dado"><span>📅 Semana</span><strong><?= (int)$rodadaSelecionada ?></strong></article>
            <article class="raiox-dado"><span>📺 Acontecimentos públicos</span><strong><?= (int)$raioX['total'] ?></strong></article>
            <article class="raiox-clima">
                <span class="raiox-clima-icone"><?= eTemp($raioX['clima'][0]) ?></span>
                <div><strong><?= eTemp($raioX['clima'][1]) ?></strong><p><?= eTemp($raioX['clima'][2]) ?></p></div>
            </article>
        </div>
        <div class="raiox-blocos">
            <div class="raiox-bloco">
                <h3>🎬 VT da semana</h3>
                <p class="raiox-ajuda">Últimos fatos registrados, sem contar conversas privadas ou estratégias escondidas.</p>
                <?php if (empty($raioX['vt'])): ?>
                    <div class="raiox-vazio">Ainda não houve acontecimentos públicos suficientes para montar o VT desta rodada.</div>
                <?php else: ?>
                    <div class="raiox-vt-lista">
                        <?php foreach ($raioX['vt'] as $evento): ?>
                            <?php $iconeRX = $evento['icone']; $tituloRX = $evento['titulo']; $textoRX = $evento['texto']; ?>
                            <article class="raiox-vt-item">
                                <span class="raiox-vt-icone"><?= eTemp($iconeRX) ?></span>
                                <div><strong><?= eTemp($tituloRX) ?></strong><p><?= eTemp($textoRX) ?></p></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="raiox-bloco">
                <h3>🎭 Reputação dinâmica</h3>
                <p class="raiox-ajuda">Rótulos narrativos por fatos acumulados <strong>até esta rodada</strong>. Não são notas, popularidade nem previsão de votação.</p>
                <?php if (empty($raioX['reputacoes'])): ?>
                    <div class="raiox-vazio">A casa ainda está construindo sua história. As reputações surgem conforme as atitudes aparecem no jogo.</div>
                <?php else: ?>
                    <div class="raiox-reputacoes">
                        <?php foreach ($raioX['reputacoes'] as $reputacao): ?>
                            <article class="raiox-reputacao-card">
                                <strong class="raiox-nome"><?= eTemp($reputacao['nome']) ?></strong>
                                <?php foreach ($reputacao['rotulos'] as $rotulo): ?>
                                    <div class="raiox-rotulo"><span><?= eTemp($rotulo[0]) ?></span><strong><?= eTemp($rotulo[1]) ?></strong></div>
                                    <p><?= eTemp($rotulo[2]) ?></p>
                                <?php endforeach; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <p class="raiox-privacidade">🔒 Nenhum número de aprovação, saldo de popularidade, voto secreto, fofoca privada ou revelação antecipada do Quarto Secreto é exibido aqui.</p>
    </section>

    <?php if (empty($historicoPorSemana)): ?>
        <div class="temporada-vazio">
            <h2>A temporada está só começando 👀</h2>
            <p>Os acontecimentos importantes aparecerão aqui conforme as próximas fases forem concluídas.</p>
        </div>
    <?php else: ?>
        <main class="temporada-timeline">
            <?php foreach ($historicoPorSemana as $numeroSemana => $eventos): ?>
                <section class="temporada-rodada">
                    <h2 class="temporada-rodada-titulo">
                        Semana <?= (int)$numeroSemana ?>
                        <?php if ((int)$numeroSemana === $rodadaAtual): ?>
                            <span class="temporada-atual">semana atual</span>
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
