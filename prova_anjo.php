<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/includes/logica/historico_temporada.php';
require_once __DIR__ . '/includes/logica/prova_anjo.php';

if (!isset($_SESSION['jogadores'])) {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['lider'])) {
    header('Location: jogo.php');
    exit;
}

$jogadores = array_values($_SESSION['jogadores']);
$lider = (string)$_SESSION['lider'];
$meuNome = (string)($_SESSION['meu_nome'] ?? '');

if (!isset($_SESSION['evento_extra']) || !is_array($_SESSION['evento_extra'])) {
    $_SESSION['evento_extra'] = [];
}

sincronizarEstadoProvaAnjoDaRodada();

$participantes = obterParticipantesProvaAnjo(
    $jogadores,
    $lider
);

/* =========================================================
   🚪 PROVA DO ANJO ENQUANTO ESTOU NO QUARTO SECRETO
   ========================================================= */

$estaNoQuartoSecreto =
    !empty($_SESSION['paredao_falso_ativo']) &&
    !empty($_SESSION['falso_eliminado']) &&
    $meuNome !== '' &&
    nomeIgual(
        $_SESSION['falso_eliminado'],
        $meuNome
    );

if (
    $estaNoQuartoSecreto &&
    ($_SESSION['fase_semana'] ?? '') === 'anjo'
) {
    $dadosQS = prepararProvaAnjo();
    $rankingQS = gerarRankingProvaAnjo($participantes, $meuNome, null);
    $campeaoNome = (string)($rankingQS[0]['nome'] ?? '');

    if ($campeaoNome !== '') {
        finalizarProvaAnjo(
            $jogadores,
            $campeaoNome,
            $lider
        );
    } else {
        $_SESSION['evento_extra'][] =
            '⚠️ Não havia participantes suficientes para a Prova do Anjo.';

        $_SESSION['prova_anjo_finalizada'] = true;
        $_SESSION['prova_anjo_finalizada_rodada'] =
            (int)($_SESSION['rodada'] ?? 1);
        $_SESSION['fase_semana'] = 'monstro';
    }

    header('Location: jogo.php');
    exit;
}

require_once __DIR__ . '/includes/actions/prova_anjo.php';

$resultadoProva = null;

if (!empty($_GET['resultado']) && !empty($_SESSION['resultado_prova_anjo'])) {
    $resultadoProva = $_SESSION['resultado_prova_anjo'];
}

if (!$resultadoProva) {
    $dadosProva = prepararProvaAnjo();
    $tipo = $dadosProva['tipo'];
    $provaAtual = $dadosProva['prova'];
} else {
    $tipo = 0;
    $provaAtual = $resultadoProva['prova'] ?? [];
}

function eAnjo($texto)
{
    return htmlspecialchars(
        (string)$texto,
        ENT_QUOTES,
        'UTF-8'
    );
}

function classePosicaoAnjo($posicao)
{
    if ((int)$posicao === 1) {
        return 'ouro';
    }

    if ((int)$posicao === 2) {
        return 'prata';
    }

    if ((int)$posicao === 3) {
        return 'bronze';
    }

    return '';
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prova do Anjo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/prova_anjo.css">
</head>

<body data-minigame="<?php echo eAnjo($provaAtual['slug'] ?? ''); ?>">

<div class="anjo-shell">

    <?php if ($resultadoProva): ?>

        <?php
            $campeao = (string)($resultadoProva['campeao'] ?? '');
            $ganhou = $campeao !== '' && $campeao === $meuNome;
            $participou = !empty($resultadoProva['participou']);
            $ranking = is_array($resultadoProva['ranking'] ?? null)
                ? $resultadoProva['ranking']
                : [];
            $autoimune = !empty($resultadoProva['autoimune']);
        ?>

        <section class="resultado-card <?php echo $ganhou ? 'resultado-vitoria' : ''; ?>">
            <div class="resultado-icone"><?php echo $ganhou ? '🏆' : '😇'; ?></div>
            <div class="eyebrow">RESULTADO OFICIAL</div>

            <h1>
                <?php if ($ganhou): ?>
                    VOCÊ É O NOVO ANJO!
                <?php else: ?>
                    TEMOS UM NOVO ANJO!
                <?php endif; ?>
            </h1>

            <p class="resultado-destaque">
                <strong><?php echo eAnjo($campeao); ?></strong>
                venceu a <span><?php echo eAnjo($provaAtual['titulo'] ?? 'Prova do Anjo'); ?></span>.
            </p>

            <?php if ($autoimune): ?>
                <div class="resultado-aviso">🛡️ Nesta semana, o Anjo também é autoimune.</div>
            <?php else: ?>
                <div class="resultado-aviso">💙 O Anjo poderá imunizar alguém antes da formação do Paredão.</div>
            <?php endif; ?>

            <?php if (!$participou): ?>
                <div class="nao-participou">👑 Você era o Líder da semana e acompanhou a disputa de fora.</div>
            <?php elseif (!$ganhou && !empty($resultadoProva['posicao_jogador'])): ?>
                <div class="seu-resultado">
                    Sua colocação: <strong><?php echo (int)$resultadoProva['posicao_jogador']; ?>º</strong>
                    · <?php echo (int)($resultadoProva['pontos_jogador'] ?? 0); ?> pontos
                </div>
            <?php endif; ?>

            <div class="ranking-box">
                <div class="ranking-topo">
                    <span>PLACAR DA PROVA</span>
                    <small>Os melhores desempenhos da disputa</small>
                </div>

                <div class="ranking-lista">
                    <?php foreach (array_slice($ranking, 0, 5) as $item): ?>
                        <div class="ranking-item <?php echo eAnjo(classePosicaoAnjo($item['posicao'] ?? 0)); ?> <?php echo !empty($item['eh_jogador']) ? 'sou-eu' : ''; ?>">
                            <span class="ranking-posicao"><?php echo (int)($item['posicao'] ?? 0); ?>º</span>
                            <span class="ranking-nome"><?php echo eAnjo($item['nome'] ?? 'Participante'); ?></span>
                            <span class="ranking-pontos"><?php echo (int)($item['pontos'] ?? 0); ?> pts</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <a class="btn-continuar" href="jogo.php">
                CONTINUAR PARA O MONSTRO →
            </a>
        </section>

    <?php else: ?>

        <header class="anjo-header">
            <div class="anjo-badge">😇 PROVA DO ANJO</div>
            <h1><?php echo eAnjo($provaAtual['titulo'] ?? 'Prova do Anjo'); ?></h1>
            <p><?php echo eAnjo($provaAtual['texto'] ?? ''); ?></p>

            <div class="info-pills">
                <span>🎮 <?php echo eAnjo($provaAtual['categoria'] ?? 'Minigame'); ?></span>
                <span>⚡ Dificuldade: <?php echo eAnjo($provaAtual['dificuldade'] ?? 'Média'); ?></span>
            </div>
        </header>

        <div class="lider-alerta">
            👑 <strong><?php echo eAnjo($lider); ?></strong> é o Líder e não participa desta prova.
        </div>

        <main class="minigame-card">

            <form method="POST" id="form-anjo">
                <input type="hidden" name="jogar" value="1">
                <input type="hidden" name="pontuacao_anjo" id="pontuacao-anjo" value="0">
                <input type="hidden" name="minigame_concluido" id="minigame-concluido" value="0">

                <?php if (($provaAtual['slug'] ?? '') === 'memoria'): ?>
                    <section class="game-area memoria-game" id="memoria-game">
                        <div class="game-status">
                            <div>
                                <span>TEMPO</span>
                                <strong id="memoria-tempo">00:00</strong>
                            </div>
                            <div>
                                <span>PARES</span>
                                <strong><b id="memoria-pares">0</b>/6</strong>
                            </div>
                            <div>
                                <span>TENTATIVAS</span>
                                <strong id="memoria-tentativas">0</strong>
                            </div>
                        </div>

                        <div class="memoria-grade" id="memoria-grade"></div>

                        <button class="btn-game principal" type="button" id="memoria-iniciar">
                            🧠 INICIAR MEMÓRIA
                        </button>
                    </section>
                <?php endif; ?>

                <?php if (($provaAtual['slug'] ?? '') === 'caixas'): ?>
                    <section class="game-area caixas-game" id="caixas-game">
                        <div class="caixas-painel">
                            <div>
                                <span>PONTUAÇÃO ATUAL</span>
                                <strong id="caixas-pontos">40</strong>
                            </div>
                            <div>
                                <span>ESCOLHAS</span>
                                <strong><b id="caixas-escolhas">0</b>/3</strong>
                            </div>
                        </div>

                        <div class="caixas-grade" id="caixas-grade">
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <button type="button" class="caixa-misteriosa" data-caixa="<?php echo $i; ?>">
                                    <span class="caixa-icone">🎁</span>
                                    <span class="caixa-numero">CAIXA <?php echo $i; ?></span>
                                    <span class="caixa-valor">?</span>
                                </button>
                            <?php endfor; ?>
                        </div>

                        <div class="game-tip">Escolha exatamente três caixas. Algumas ajudam — outras podem atrapalhar.</div>
                    </section>
                <?php endif; ?>

                <?php if (($provaAtual['slug'] ?? '') === 'sequencia'): ?>
                    <section class="game-area sequencia-game" id="sequencia-game">
                        <div class="sequencia-topo">
                            <span id="sequencia-rodada">RODADA 1 DE 3</span>
                            <strong id="sequencia-status">Prepare-se para memorizar.</strong>
                        </div>

                        <div class="sequencia-display" id="sequencia-display">
                            <span class="sequencia-placeholder">⚡</span>
                        </div>

                        <div class="sequencia-opcoes bloqueado" id="sequencia-opcoes">
                            <button type="button" data-sinal="0">🪽</button>
                            <button type="button" data-sinal="1">💎</button>
                            <button type="button" data-sinal="2">⭐</button>
                            <button type="button" data-sinal="3">💙</button>
                        </div>

                        <button class="btn-game principal" type="button" id="sequencia-iniciar">
                            ⚡ COMEÇAR SEQUÊNCIA
                        </button>
                    </section>
                <?php endif; ?>

                <?php if (($provaAtual['slug'] ?? '') === 'mira'): ?>
                    <section class="game-area mira-game" id="mira-game">
                        <div class="mira-topo">
                            <span>TENTATIVA <b id="mira-tentativa">1</b>/3</span>
                            <strong>Média: <b id="mira-media">0</b> pts</strong>
                        </div>

                        <div class="mira-pista" id="mira-pista">
                            <div class="mira-zona zona-boa"></div>
                            <div class="mira-zona zona-perfeita"></div>
                            <div class="mira-centro"></div>
                            <div class="mira-marcador" id="mira-marcador">🪽</div>
                        </div>

                        <div class="mira-legenda">
                            <span>0</span>
                            <strong>CENTRO = 100</strong>
                            <span>0</span>
                        </div>

                        <button class="btn-game principal" type="button" id="mira-botao">
                            🎯 INICIAR
                        </button>

                        <div class="mira-ultimo" id="mira-ultimo">Acerte o centro para marcar o máximo de pontos.</div>
                    </section>
                <?php endif; ?>

                <div class="finalizar-area escondido" id="finalizar-area">
                    <div class="pontuacao-final">
                        <span>SEU DESEMPENHO</span>
                        <strong><b id="pontuacao-final-texto">0</b>/100</strong>
                    </div>
                    <button class="btn-enviar" type="submit">
                        😇 FINALIZAR PROVA
                    </button>
                </div>
            </form>
        </main>

        <footer class="anjo-footer">
            <span>👑 O maior desempenho conquista o colar.</span>
            <span>🪙 Se você vencer, recebe +15 Moedas do Público.</span>
            <span>🎭 Os NPCs também têm desempenho próprio na prova.</span>
        </footer>

    <?php endif; ?>

</div>

<?php if (!$resultadoProva): ?>
    <script src="assets/js/prova_anjo.js"></script>
<?php endif; ?>

</body>
</html>
