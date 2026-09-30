<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/includes/logica/utilitarios.php';
require_once __DIR__ . '/includes/logica/historico_temporada.php';
require_once __DIR__ . '/includes/logica/relacoes.php';
require_once __DIR__ . '/includes/logica/romance.php';
require_once __DIR__ . '/includes/logica/aliancas.php';
require_once __DIR__ . '/includes/logica/perfil_participante.php';

if (empty($_SESSION['jogadores']) && empty($_SESSION['participantes_eliminados_dados'])) {
    header('Location: index.php');
    exit;
}

$jogadores = is_array($_SESSION['jogadores'] ?? null)
    ? $_SESSION['jogadores']
    : [];

$meuNome = trim((string)($_SESSION['meu_nome'] ?? ''));
$nomeSolicitado = trim((string)($_GET['nome'] ?? ''));

if ($nomeSolicitado === '') {
    $nomeSolicitado = $meuNome !== ''
        ? $meuNome
        : (string)($jogadores[0]['nome'] ?? '');
}

$participante = buscarParticipantePerfil($jogadores, $nomeSolicitado);

if (!$participante) {
    header('Location: jogo.php');
    exit;
}

$nome = (string)($participante['nome'] ?? 'Participante');
$eventos = eventosDoParticipantePerfil($nome);
$estatisticas = estatisticasParticipantePerfil($participante);
$statusAtual = statusAtualParticipantePerfil($participante);
$relacaoJogador = relacaoPercebidaComJogadorPerfil(
    $jogadores,
    $participante,
    $meuNome
);
$romanceOficial = romanceOficialParticipantePerfil($nome);
$eliminado = participanteEstaEliminadoPerfil($nome);
$aliancaAtual = trim((string)($participante['alianca'] ?? ''));
$historicoAliancas = array_values(array_filter(
    (array)($participante['historico_aliancas'] ?? []),
    function ($item) {
        return trim((string)$item) !== '';
    }
));
$elencoDisponivel = listarParticipantesDisponiveisPerfil($jogadores);

function ePerfil($texto)
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ePerfil($nome) ?> - BBB Simulator</title>
    <link rel="stylesheet" href="assets/css/perfil_participante.css">
</head>
<body>
<div class="perfil-page">

    <header class="perfil-topo">
        <div>
            <small>BBB Simulator • Perfil</small>
            <h1>👤 Perfil do Participante</h1>
        </div>

        <div class="perfil-topo-acoes">
            <a href="temporada.php" class="perfil-btn secundario">📖 Temporada</a>
            <a href="jogo.php" class="perfil-btn">← Voltar para a casa</a>
        </div>
    </header>

    <nav class="perfil-elenco" aria-label="Participantes da temporada">
        <?php foreach ($elencoDisponivel as $p): ?>
            <?php
                $nomeElenco = (string)($p['nome'] ?? '');
                $ativo = nomeIgual($nomeElenco, $nome);
                $fora = participanteEstaEliminadoPerfil($nomeElenco);
            ?>
            <a
                href="perfil.php?nome=<?= rawurlencode($nomeElenco) ?>"
                class="perfil-elenco-item <?= $ativo ? 'ativo' : '' ?> <?= $fora ? 'eliminado' : '' ?>"
            >
                <?= ePerfil($nomeElenco) ?>
                <?php if ($fora): ?><span>❌</span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <main class="perfil-conteudo">

        <section class="perfil-hero">
            <div class="perfil-avatar">
                <span><?= ePerfil(mb_strtoupper(mb_substr($nome, 0, 1))) ?></span>
            </div>

            <div class="perfil-identidade">
                <div class="perfil-nome-linha">
                    <h2><?= ePerfil($nome) ?></h2>
                    <?php if (nomeIgual($nome, $meuNome)): ?>
                        <span class="perfil-voce">⭐ VOCÊ</span>
                    <?php endif; ?>
                    <?php if ($eliminado): ?>
                        <span class="perfil-eliminado">❌ ELIMINADO</span>
                    <?php else: ?>
                        <span class="perfil-na-casa">● NA CASA</span>
                    <?php endif; ?>
                </div>

                <div class="perfil-dados-basicos">
                    <span>🎂 <?= (int)($participante['idade'] ?? 0) ?> anos</span>
                    <span>💼 <?= ePerfil($participante['profissao'] ?? 'Não informado') ?></span>
                    <span>📍 <?= ePerfil($participante['estado'] ?? 'Não informado') ?></span>
                    <span>🎭 <?= ePerfil($participante['personalidade'] ?? 'Neutro') ?></span>
                </div>

                <?php if (!empty($statusAtual) && !$eliminado): ?>
                    <div class="perfil-status-lista">
                        <?php foreach ($statusAtual as $status): ?>
                            <span class="perfil-status <?= ePerfil($status['classe']) ?>">
                                <?= ePerfil($status['icone']) ?> <?= ePerfil($status['texto']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="perfil-grid-principal">

            <div class="perfil-card perfil-card-estatisticas">
                <div class="perfil-card-titulo">
                    <div>
                        <small>TRAJETÓRIA</small>
                        <h3>🏆 Números da temporada</h3>
                    </div>
                </div>

                <div class="perfil-estatisticas-grid">
                    <?php foreach ($estatisticas as $estatistica): ?>
                        <div class="perfil-estatistica">
                            <span class="perfil-estatistica-icone"><?= ePerfil($estatistica['icone']) ?></span>
                            <strong><?= (int)$estatistica['valor'] ?>x</strong>
                            <small><?= ePerfil($estatistica['rotulo']) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="perfil-card perfil-card-relacao">
                <div class="perfil-card-titulo">
                    <div>
                        <small>CONVIVÊNCIA</small>
                        <h3>💬 Relação com você</h3>
                    </div>
                </div>

                <div class="perfil-relacao-box <?= ePerfil($relacaoJogador['classe']) ?>">
                    <div class="perfil-relacao-icone"><?= ePerfil($relacaoJogador['icone']) ?></div>
                    <div>
                        <strong><?= ePerfil($relacaoJogador['texto']) ?></strong>
                        <p><?= ePerfil($relacaoJogador['descricao']) ?></p>
                    </div>
                </div>

                <p class="perfil-privacidade">
                    👁️ O perfil mostra apenas sinais perceptíveis da convivência. Valores internos de afinidade, confiança e popularidade continuam ocultos.
                </p>
            </div>

        </section>

        <section class="perfil-grid-social">

            <div class="perfil-card">
                <div class="perfil-card-titulo">
                    <div>
                        <small>JOGO SOCIAL</small>
                        <h3>🤝 Alianças</h3>
                    </div>
                </div>

                <?php if ($aliancaAtual !== '' && !$eliminado): ?>
                    <div class="perfil-destaque-social">
                        <span>Aliança atual</span>
                        <strong><?= ePerfil($aliancaAtual) ?></strong>
                    </div>
                <?php else: ?>
                    <p class="perfil-vazio-mini">Nenhuma aliança atual registrada.</p>
                <?php endif; ?>

                <?php if (!empty($historicoAliancas)): ?>
                    <div class="perfil-historico-social">
                        <h4>Histórico de alianças</h4>
                        <?php foreach (array_slice(array_reverse($historicoAliancas), 0, 8) as $item): ?>
                            <div class="perfil-social-item">🤝 <?= ePerfil(strip_tags((string)$item)) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="perfil-card">
                <div class="perfil-card-titulo">
                    <div>
                        <small>VIDA NA CASA</small>
                        <h3>💕 Romance</h3>
                    </div>
                </div>

                <?php if ($romanceOficial !== ''): ?>
                    <div class="perfil-destaque-social romance">
                        <span>Romance oficial</span>
                        <strong>💕 <?= ePerfil($romanceOficial) ?></strong>
                    </div>
                <?php else: ?>
                    <p class="perfil-vazio-mini">Nenhum romance oficial registrado até agora.</p>
                <?php endif; ?>

                <div class="perfil-romance-nota">
                    Crushes e interesses internos não são exibidos como números. Eles só aparecem quando ficam perceptíveis no jogo.
                </div>
            </div>

        </section>

        <section class="perfil-card perfil-trajetoria">
            <div class="perfil-card-titulo">
                <div>
                    <small>DO INÍCIO ATÉ AGORA</small>
                    <h3>📖 Acontecimentos marcantes</h3>
                </div>
                <span class="perfil-contador-eventos"><?= count($eventos) ?> registro(s)</span>
            </div>

            <?php if (empty($eventos)): ?>
                <div class="perfil-sem-eventos">
                    <span>🌙</span>
                    <h4>A trajetória ainda está começando</h4>
                    <p>Provas, paredões, alianças, romances e outros momentos importantes aparecerão aqui.</p>
                </div>
            <?php else: ?>
                <div class="perfil-timeline">
                    <?php foreach ($eventos as $evento): ?>
                        <article class="perfil-evento">
                            <div class="perfil-evento-icone"><?= ePerfil($evento['icone'] ?? '📌') ?></div>
                            <div class="perfil-evento-conteudo">
                                <div class="perfil-evento-topo">
                                    <strong><?= ePerfil($evento['titulo'] ?? 'Acontecimento') ?></strong>
                                    <span>Rodada <?= (int)($evento['rodada'] ?? 1) ?></span>
                                </div>
                                <p><?= ePerfil($evento['descricao'] ?? '') ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>
</div>
</body>
</html>
