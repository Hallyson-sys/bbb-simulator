<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/includes/logica/utilitarios.php';
require_once __DIR__ . '/includes/logica/participantes.php';
require_once __DIR__ . '/includes/logica/inicializacao.php';
require_once __DIR__ . '/includes/logica/relacoes.php';
require_once __DIR__ . '/includes/logica/casa_vidro.php';

if (!isset($_SESSION['jogadores'])) {
    header('Location: index.php');
    exit;
}

if (
    empty($_SESSION['casa_vidro_ativa']) &&
    empty($_SESSION['casa_vidro_realizada'])
) {
    header('Location: jogo.php');
    exit;
}

$jogadores = $_SESSION['jogadores'];
$meuNome = trim($_SESSION['meu_nome'] ?? '');
$rodada = (int)($_SESSION['rodada'] ?? 1);

$candidatos = gerarCandidatosCasaVidro($jogadores);

require_once __DIR__ . '/includes/actions/casa_vidro.php';

$ranking = $_SESSION['casa_vidro_ranking'] ?? [];
$vencedores = $_SESSION['casa_vidro_vencedores'] ?? [];
$mostrarResultado = !empty($ranking);

function eCasaVidro($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

/* Após revelar, ordena visualmente pelo resultado. */
if ($mostrarResultado) {
    usort(
        $candidatos,
        function ($a, $b) use ($ranking) {
            return
                ($ranking[$b['nome'] ?? ''] ?? 0)
                <=>
                ($ranking[$a['nome'] ?? ''] ?? 0);
        }
    );
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casa de Vidro — BBB Simulator</title>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/casa_vidro.css">
</head>

<body>

<div class="pagina-casa-vidro">

    <header class="casa-topo">
        <span class="casa-badge">🏠 DINÂMICA ESPECIAL • RODADA <?= eCasaVidro($rodada) ?></span>

        <h1>Casa de Vidro</h1>

        <?php if (!$mostrarResultado): ?>
            <p>
                Quatro candidatos estão diante do público.
                Apenas <b>dois</b> vão atravessar a porta e entrar no BBB Simulator.
            </p>
        <?php else: ?>
            <p>
                A votação foi encerrada. O Brasil escolheu os dois novos participantes da temporada.
            </p>
        <?php endif; ?>
    </header>


    <section class="casa-transmissao">
        <div class="transmissao-luz"></div>
        <span>● AO VIVO</span>
        <p>
            <?php if (!$mostrarResultado): ?>
                A votação está movimentando as redes. Quem merece entrar na casa?
            <?php else: ?>
                Resultado oficial da votação da Casa de Vidro.
            <?php endif; ?>
        </p>
    </section>


    <main class="candidatos-grid">

        <?php foreach ($candidatos as $indice => $candidato): ?>
            <?php
                $nome = $candidato['nome'] ?? '';
                $pct = (float)($ranking[$nome] ?? 0);
                $venceu = in_array($nome, $vencedores, true);
            ?>

            <article class="candidato-card <?= $venceu ? 'vencedor' : '' ?>">

                <div class="candidato-numero">
                    #<?= str_pad((string)($indice + 1), 2, '0', STR_PAD_LEFT) ?>
                </div>

                <?php if ($venceu): ?>
                    <div class="badge-vencedor">ENTRA NA CASA</div>
                <?php endif; ?>

                <div class="candidato-avatar">
                    <?= eCasaVidro(
                        mb_strtoupper(
                            mb_substr($nome, 0, 1, 'UTF-8'),
                            'UTF-8'
                        )
                    ) ?>
                </div>

                <h2><?= eCasaVidro($nome) ?></h2>

                <div class="dados-candidato">
                    <span>🎂 <?= (int)($candidato['idade'] ?? 18) ?> anos</span>
                    <span>📍 <?= eCasaVidro($candidato['estado'] ?? '') ?></span>
                </div>

                <p class="profissao-candidato">
                    <?= eCasaVidro($candidato['profissao'] ?? '') ?>
                </p>

                <span class="personalidade-candidato">
                    <?= eCasaVidro($candidato['personalidade'] ?? 'Neutro') ?>
                </span>

                <?php if ($mostrarResultado): ?>
                    <div class="resultado-candidato">
                        <div class="resultado-topo">
                            <span>VOTOS PARA ENTRAR</span>
                            <strong><?= number_format($pct, 2, ',', '.') ?>%</strong>
                        </div>

                        <div class="barra-votos">
                            <div
                                class="barra-votos-fill"
                                style="width: <?= max(0, min(100, $pct)) ?>%;"
                            ></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="candidato-status">
                        📡 Em votação
                    </div>
                <?php endif; ?>

            </article>
        <?php endforeach; ?>

    </main>


    <section class="casa-acao">

        <?php if (!$mostrarResultado): ?>
            <div class="casa-box">
                <span>🗳️</span>
                <div>
                    <strong>O público está decidindo</strong>
                    <p>
                        O resultado leva em conta o apelo inicial de cada candidato,
                        personalidade e reação simulada do público.
                    </p>
                </div>
            </div>

            <form method="POST">
                <button class="btn-revelar" name="revelar_resultado_casa_vidro">
                    📺 ENCERRAR VOTAÇÃO E REVELAR RESULTADO
                </button>
            </form>

        <?php else: ?>
            <div class="casa-box resultado-final">
                <span>🎉</span>
                <div>
                    <strong>
                        <?= eCasaVidro(implode(' e ', $vencedores)) ?> entram no BBB Simulator!
                    </strong>
                    <p>
                        Os dois escolhidos agora passam a fazer parte oficialmente do elenco.
                    </p>
                </div>
            </div>

            <form method="POST">
                <button class="btn-entrar" name="confirmar_entrada_casa_vidro">
                    🚪 ABRIR A PORTA DA CASA
                </button>
            </form>
        <?php endif; ?>

    </section>

</div>

</body>
</html>
