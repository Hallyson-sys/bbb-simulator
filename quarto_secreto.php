<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/includes/logica/utilitarios.php';
require_once __DIR__ . '/includes/logica/relacoes.php';
require_once __DIR__ . '/includes/logica/eliminacao.php';
require_once __DIR__ . '/includes/logica/paredao_falso.php';

if (
    empty($_SESSION['paredao_falso_ativo']) ||
    empty($_SESSION['falso_eliminado']) ||
    empty($_SESSION['falso_eliminado_snapshot'])
) {
    header("Location: jogo.php");
    exit;
}

$jogadores = $_SESSION['jogadores'] ?? [];
$meuNome = $_SESSION['meu_nome'] ?? '';
$rodada = $_SESSION['rodada'] ?? 1;
$falsoEliminado = $_SESSION['falso_eliminado'];
$snapshot = $_SESSION['falso_eliminado_snapshot'];
$ranking = $_SESSION['falso_eliminado_ranking'] ?? [];

require_once __DIR__ . '/includes/actions/paredao_falso.php';

$espiadinhas = gerarEspiadinhasQuartoSecreto(
    $jogadores,
    $falsoEliminado,
    $meuNome
);

$ehMeuJogador = nomeIgual(
    $falsoEliminado,
    $meuNome
);

function eQS($texto)
{
    return htmlspecialchars(
        (string) $texto,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quarto Secreto - BBB Simulator</title>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/quarto_secreto.css">
</head>
<body>

<div class="quarto-page">

    <section class="quarto-hero">

        <span class="quarto-badge">🚨 PAREDÃO FALSO</span>

        <h1>QUARTO SECRETO</h1>

        <?php if ($ehMeuJogador): ?>
            <p>
                A casa acha que <b>você foi eliminado</b>.
                Mas sua trajetória continua — e agora você pode observar o jogo de fora.
            </p>
        <?php else: ?>
            <p>
                A casa acredita que <b><?= eQS($falsoEliminado) ?></b> foi eliminado.
                Na verdade, o participante está acompanhando tudo do Quarto Secreto.
            </p>
        <?php endif; ?>

    </section>


    <div class="quarto-grid">

        <section class="quarto-card participante-secreto">

            <span class="card-kicker">PARTICIPANTE SECRETO</span>

            <div class="avatar-secreto">
                <?= eQS(
                    mb_strtoupper(
                        mb_substr(
                            $falsoEliminado,
                            0,
                            1,
                            'UTF-8'
                        ),
                        'UTF-8'
                    )
                ) ?>
            </div>

            <h2><?= eQS($falsoEliminado) ?></h2>

            <div class="dados-secreto">
                <span>🎂 <?= (int) ($snapshot['idade'] ?? 18) ?> anos</span>
                <span>📍 <?= eQS($snapshot['estado'] ?? '-') ?></span>
            </div>

            <p><?= eQS($snapshot['profissao'] ?? 'Participante') ?></p>

            <span class="tag-personalidade">
                <?= eQS($snapshot['personalidade'] ?? 'Neutro') ?>
            </span>

            <div class="status-secreto">
                <span>📈 Popularidade</span>
                <strong><?= (int) ($snapshot['popularidade'] ?? 50) ?>/100</strong>
            </div>

        </section>


        <section class="quarto-card cameras-card">

            <div class="card-title-row">
                <div>
                    <span class="card-kicker">📺 ESPIADINHAS</span>
                    <h2>O que está acontecendo?</h2>
                </div>

                <span class="ao-vivo-dot">● AO VIVO</span>
            </div>

            <div class="camera-lista">

                <?php if (!empty($espiadinhas)): ?>

                    <?php foreach ($espiadinhas as $item): ?>
                        <div class="camera-item">
                            <?= $item ?>
                        </div>
                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="camera-item">
                        📡 A casa está mais silenciosa neste momento.
                    </div>

                <?php endif; ?>

            </div>

        </section>


        <section class="quarto-card resultado-card">

            <span class="card-kicker">📊 RESULTADO DO PAREDÃO</span>
            <h2>Como ficou a votação?</h2>

            <div class="ranking-secreto">

                <?php foreach ($ranking as $nome => $pct): ?>
                    <div class="ranking-item <?= nomeIgual($nome, $falsoEliminado) ? 'destaque' : '' ?>">
                        <span><?= eQS($nome) ?></span>
                        <strong><?= number_format((float) $pct, 2, ',', '.') ?>%</strong>
                    </div>
                <?php endforeach; ?>

            </div>

            <p class="nota-secreta">
                O público não eliminou ninguém de verdade. O maior percentual definiu quem iria para o Quarto Secreto.
            </p>

        </section>


        <section class="quarto-card retorno-card">

            <span class="card-kicker">🚪 HORA DO RETORNO</span>
            <h2>A casa não está preparada.</h2>

            <?php if ($ehMeuJogador): ?>
                <p>
                    Quando você voltar, todos descobrirão que sua eliminação era falsa.
                    Seu retorno também gera um pequeno impulso de popularidade pela repercussão da dinâmica.
                </p>
            <?php else: ?>
                <p>
                    <?= eQS($falsoEliminado) ?> vai reaparecer na casa e pegar os participantes de surpresa.
                    O retorno encerra esta rodada e inicia uma nova semana.
                </p>
            <?php endif; ?>

            <form method="POST">
                <button type="submit" name="retornar_paredao_falso">
                    🚪 VOLTAR PARA A CASA
                </button>
            </form>

        </section>

    </div>

</div>

</body>
</html>
