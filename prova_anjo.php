<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

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

require_once __DIR__ . '/includes/actions/prova_anjo.php';

$dadosProva = prepararProvaAnjo();
$tipo = $dadosProva['tipo'];
$provaAtual = $dadosProva['prova'];
$quizAtual = $_SESSION['quiz_anjo'] ?? null;

function eAnjo($texto)
{
    return htmlspecialchars(
        (string)$texto,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Prova do Anjo</title>
    <link rel="stylesheet" href="assets/css/prova_anjo.css">
</head>

<body>

<div class="box">

    <h1>😇 Prova do Anjo</h1>

    <div class="sub">
        O vencedor conquista o colar. Em algumas semanas, o Anjo é autoimune.
    </div>

    <div class="alerta">
        👑 <b><?php echo eAnjo($lider); ?></b> é o Líder e não participa desta prova.
    </div>

    <form method="POST">

        <p>
            <b><?php echo eAnjo($provaAtual['titulo'] ?? 'Prova do Anjo'); ?></b>
        </p>

        <p>
            <?php echo eAnjo($provaAtual['texto'] ?? ''); ?>
        </p>

        <?php if ($tipo === 1): ?>

            <select name="caixa" required>
                <option value="">Escolher chave</option>

                <?php for ($i = 1; $i <= (int)($provaAtual['max'] ?? 20); $i++): ?>
                    <option value="<?php echo $i; ?>">
                        🔑 Chave <?php echo $i; ?>
                    </option>
                <?php endfor; ?>
            </select>

            <button type="submit" name="jogar" value="1">
                💙 Disputar Prova
            </button>

        <?php endif; ?>

        <?php if ($tipo === 2 && is_array($quizAtual)): ?>

            <p><?php echo eAnjo($quizAtual['p'] ?? ''); ?></p>

            <div class="ops">

                <?php foreach (($quizAtual['op'] ?? []) as $op): ?>

                    <label>
                        <input
                            type="radio"
                            name="quiz"
                            value="<?php echo eAnjo($op); ?>"
                            required
                        >
                        <?php echo eAnjo($op); ?>
                    </label>

                <?php endforeach; ?>

            </div>

            <button type="submit" name="jogar" value="1">
                💙 Disputar Prova
            </button>

        <?php endif; ?>

        <?php if ($tipo >= 3 && $tipo <= 10): ?>

            <div class="grid-botoes">
                <?php for ($i = 1; $i <= (int)($provaAtual['max'] ?? 0); $i++): ?>
                    <button type="submit" name="escolha" value="<?php echo $i; ?>">
                        <?php echo eAnjo(textoBotaoAnjo($provaAtual, $i)); ?>
                    </button>
                <?php endfor; ?>
            </div>

            <input type="hidden" name="jogar" value="1">

        <?php endif; ?>

    </form>

</div>

</body>

</html>
