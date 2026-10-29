<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/includes/logica/eliminacao.php';
require_once __DIR__ . '/includes/logica/historico_temporada.php';
require_once __DIR__ . '/includes/logica/relacoes.php';
require_once __DIR__ . '/includes/logica/paredao_falso.php';

if (
    !isset($_SESSION['jogadores']) ||
    !isset($_SESSION['paredao'])
) {
    header("Location: jogo.php");
    exit;
}

$jogadores = $_SESSION['jogadores'];
$paredao   = $_SESSION['paredao'];
$rodada    = $_SESSION['rodada'] ?? 1;
$meuNome   = $_SESSION['meu_nome'] ?? '';

$mostrarResultado = false;
$eliminado = "";
$ranking = [];
$fuiEliminado = false;

$meuJogadorEliminado =
    $_SESSION['meu_jogador_snapshot'] ?? null;

$ehParedaoFalso =
    !empty($_SESSION['paredao_falso_ativo']);

/*
 * Começa sempre como false.
 * Só calculamos o valor real depois que soubermos
 * quem foi o "eliminado" da noite.
 */
$souFalsoEliminado = false;


/* ==========================
   FUNÇÕES VISUAIS
========================== */
function e($texto)
{
    return htmlspecialchars(
        (string)$texto,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   🎮 ACTIONS DA ELIMINAÇÃO
   ========================================================= */
require_once __DIR__ . '/includes/actions/eliminacao.php';


/* =========================================================
   🔄 RECARREGAR ESTADO APÓS ACTION
   ========================================================= */
$jogadores =
    $_SESSION['jogadores']
    ?? $jogadores;

$ehParedaoFalso =
    !empty($_SESSION['paredao_falso_ativo']);


/* =========================================================
   📊 RECUPERAR RESULTADO
   ========================================================= */
if (
    isset($_SESSION['ultimo_ranking_eliminacao'])
) {

    $ranking =
        $_SESSION['ultimo_ranking_eliminacao'];

    if ($ehParedaoFalso) {

        $eliminado =
            $_SESSION['falso_eliminado']
            ?? '';

    } else {

        $eliminado =
            $_SESSION['eliminado']
            ?? '';
    }

    if ($eliminado !== '') {
        $mostrarResultado = true;
    }
}


/* =========================================================
   🚪 DESCOBRIR SE EU SOU O FALSO ELIMINADO
   ========================================================= */
$souFalsoEliminado =
    $ehParedaoFalso &&
    $eliminado !== '' &&
    $meuNome !== '' &&
    nomeIgual(
        $eliminado,
        $meuNome
    );


/* =========================================================
   ❌ DESCOBRIR SE EU FUI ELIMINADO DE VERDADE
   ========================================================= */
if (
    !$ehParedaoFalso &&
    $eliminado !== '' &&
    nomeIgual(
        $eliminado,
        $meuNome
    )
) {

    $fuiEliminado = true;

    if (!$meuJogadorEliminado) {

        $meuJogadorEliminado =
            $_SESSION['meu_jogador_snapshot']
            ?? [];
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Noite de Eliminação</title>
<link rel="stylesheet" href="assets/css/resultado.css">
<style>
.paredao-falso-alerta{
    margin-top:22px;
    padding:22px;
    border:1px solid rgba(255,0,204,.35);
    border-radius:18px;
    background:linear-gradient(135deg,rgba(255,0,102,.12),rgba(95,0,180,.13));
    box-shadow:0 0 30px rgba(255,0,180,.16);
    text-align:center;
}
.paredao-falso-alerta .falso-badge{
    display:inline-block;
    margin-bottom:10px;
    padding:7px 13px;
    border-radius:30px;
    background:linear-gradient(135deg,#ff0066,#8f00ff);
    font-size:11px;
    font-weight:900;
    letter-spacing:1.5px;
}
.paredao-falso-alerta h3{
    margin-bottom:8px;
    font-size:22px;
}
.paredao-falso-alerta p{
    line-height:1.6;
    opacity:.88;
}
</style>
</head>

<body>

<div class="container">

    <div class="topo">
        <div class="badge">● REC <span class="live-pulse"></span> • SEMANA <?php echo e($rodada); ?> • AO VIVO</div>
        <div class="elim-glow-mark">✦ A DECISÃO DO BRASIL ✦</div>
        <h1>NOITE DE <span>ELIMINAÇÃO</span></h1>
        <p>Três histórias em jogo. Uma despedida. O público tem a palavra.</p>
        <div class="elim-divider"><i></i><span>🔴 TRANSMISSÃO AO VIVO</span><i></i></div>
    </div>

    <?php if(!$mostrarResultado): ?>

        <div class="stage">

            <div class="box">

                <div class="elim-section-label">01 / NA BERLINDA</div>
                <h2>🔥 Quem continua na casa?</h2>
                <p class="elim-section-sub">Os emparedados aguardam a decisão do público.</p>

                <div class="paredao-grid">

                    <?php foreach($paredao as $posicao => $nome): ?>
                        <?php $jogadorParedao = buscarJogadorResultado($jogadores, $nome); ?>

                        <div class="card-paredao">
                            <div class="elim-avatar"><?=e(mb_strtoupper(mb_substr((string)$nome,0,1,'UTF-8'),'UTF-8'))?></div>
                            <div class="elim-participante-texto">
                                <div class="label">NA BERLINDA • <?=e((int)$posicao+1)?></div>
                                <h3><?php echo e($nome); ?></h3>
                                <p><?php echo e(resumoParticipanteResultado($jogadorParedao)); ?></p>
                            </div>
                            <span class="elim-card-glow">✦</span>
                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

            <div class="box">

                <div class="elim-section-label">02 / O ÚLTIMO DISCURSO</div>
                <h2>🎙️ A mensagem do apresentador</h2>
                <p class="elim-section-sub">A casa fica em silêncio. As luzes diminuem. É hora da decisão.</p>
                <div class="discurso">
                    <div class="fala">🎙️ “Boa noite, pessoal. Chegou a hora que ninguém queria que chegasse.”</div>
                    <div class="fala">“Vocês viveram coisas que só quem está aí entende. Cada relação deixou uma marca.”</div>
                    <div class="fala">“Mas lá fora, cada escolha também construiu uma história diferente para quem assistiu.”</div>
                    <div class="fala">“Quem fica hoje ainda tem a chance de mudar o próprio destino.”</div>
                    <div class="fala final-fala">“Respirem fundo... o Brasil já decidiu.”</div>
                </div>

                <form method="POST">
                    <button class="btn" name="revelar">
                        📺 DESCOBRIR QUEM SAIU <span aria-hidden="true">→</span>
                    </button>
                </form>

            </div>

        </div>

    <?php else: ?>

        <div class="box reveal-wrap">

            <span class="elim-section-label">03 / DECISÃO FINAL</span>
            <p class="suspense">O Brasil fez sua escolha.</p>
            <p class="suspense">E quem deixa a casa nesta noite é...</p>

            <div class="nomeSaiu">
                <?php echo e($eliminado); ?>
            </div>

            <?php if($souFalsoEliminado): ?>

                <p class="eliminado-sub">
                    🚨 Mas essa eliminação não é o que parece...
                </p>

                <div class="paredao-falso-alerta">
                    <div class="falso-badge">🚨 PAREDÃO FALSO</div>
                    <h3><?php echo e($eliminado); ?> NÃO está fora do jogo!</h3>
                    <p>
                        O participante foi enviado para o <b>Quarto Secreto</b>.
                        A casa acredita que houve uma eliminação, mas a temporada ainda guarda uma surpresa.
                    </p>
                </div>

            <?php else: ?>

                <p class="eliminado-sub">
                    ❌ Eliminado do BBB Simulator
                </p>

                <div class="glass-alert">
                    <?php if($fuiEliminado): ?>
                        Sua trajetória chegou ao fim. Agora é hora de ver seu desempenho na temporada.
                    <?php else: ?>
                        A casa sente o impacto. Agora, quem ficou precisa seguir o jogo.
                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </div>

        <div class="box">

            <h2>📊 Resultado da Votação</h2>

            <div class="resultado-grid">

                <?php foreach($ranking as $nome=>$pct): ?>
                    <?php $classe = corPorcentagem($pct); ?>

                    <div class="rank">
                        <div class="rank-top">
                            <strong><?php echo e($nome); ?></strong>
                            <span><?php echo number_format($pct, 2, ',', '.'); ?>%</span>
                        </div>

                        <div class="barra-fundo">
                            <div class="barra-preenchida <?php echo e($classe); ?>"
                                 style="width: <?php echo str_replace(',', '.', number_format($pct, 2, '.', '')); ?>%;">
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>

            </div>

            <p class="after-text">
                O maior percentual representa quem recebeu mais votos para sair.
                O resultado considera a trajetória dos participantes, o comportamento no jogo e a repercussão dos acontecimentos da semana.
            </p>

        </div>

        <?php if($souFalsoEliminado): ?>

            <div class="box">
                <form action="quarto_secreto.php" method="GET">
                    <button class="btn" type="submit">
                        🚪 Ir para o Quarto Secreto
                    </button>
                </form>
            </div>

        <?php elseif($fuiEliminado): ?>

            <?php
                $popularidadeFinal = limitar(
                    $meuJogadorEliminado['popularidade'] ??
                    ($_SESSION['minha_popularidade_final'] ?? 50),
                    0,
                    100
                );

                $colocacaoFinal =
                    $_SESSION['minha_colocacao_final'] ??
                    (count($_SESSION['jogadores']) + 1);
            ?>

            <div class="box">

                <h2>🧾 Suas Estatísticas Finais</h2>

                <div class="popularidade-barra">
                    <div class="popularidade-fill" style="width: <?php echo $popularidadeFinal; ?>%;"></div>
                </div>

                <p class="after-text">
                    📊 Popularidade final: <b><?php echo $popularidadeFinal; ?>/100</b> •
                    🏁 Colocação: <b><?php echo e($colocacaoFinal); ?>º lugar</b>
                </p>

                <div class="stats-grid">

                    <div class="stat-card">
                        <strong><?php echo e($rodada); ?></strong>
                        <span>Semana da eliminação</span>
                    </div>

                    <div class="stat-card">
                        <strong><?php echo estatResultado($meuJogadorEliminado, 'lider'); ?></strong>
                        <span>Provas do Líder</span>
                    </div>

                    <div class="stat-card">
                        <strong><?php echo estatResultado($meuJogadorEliminado, 'anjo'); ?></strong>
                        <span>Provas do Anjo</span>
                    </div>

                    <div class="stat-card">
                        <strong><?php echo estatResultado($meuJogadorEliminado, 'vip'); ?></strong>
                        <span>Vezes no VIP</span>
                    </div>

                    <div class="stat-card">
                        <strong><?php echo estatResultado($meuJogadorEliminado, 'xepa'); ?></strong>
                        <span>Vezes na Xepa</span>
                    </div>

                    <div class="stat-card">
                        <strong><?php echo estatResultado($meuJogadorEliminado, 'monstro'); ?></strong>
                        <span>Monstros recebidos</span>
                    </div>

                    <div class="stat-card">
                        <strong><?php echo estatResultado($meuJogadorEliminado, 'imune'); ?></strong>
                        <span>Imunidades</span>
                    </div>

                    <div class="stat-card">
                        <strong><?php echo estatResultado($meuJogadorEliminado, 'paredao'); ?></strong>
                        <span>Paredões enfrentados</span>
                    </div>

                </div>

                <form method="POST">
    <button class="btn" name="continuar">
        🎤 Ir para o Bate-Papo com o Eliminado
    </button>
</form>

                <form method="POST">
                    <button class="btn" name="novo_jogo">
                        🔄 Começar Nova Temporada
                    </button>
                </form>

            </div>

        <?php else: ?>

            <div class="box">
                <form method="POST">
                    <button class="btn" name="continuar">
                        ▶️ Continuar Temporada
                    </button>
                </form>
            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>

</body>
</html>
