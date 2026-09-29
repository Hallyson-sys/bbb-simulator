<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/includes/logica/utilitarios.php';
require_once __DIR__ . '/includes/logica/modo_teste.php';


/* =========================================================
   🔒 SOMENTE LOCALHOST
   ========================================================= */
if (!modoTestePermitido()) {

    http_response_code(403);

    exit(
        '🧪 O Modo de Testes está disponível apenas no ambiente local.'
    );
}


/* =========================================================
   🎮 EXIGIR TEMPORADA
   ========================================================= */
if (
    !isset($_SESSION['jogadores']) ||
    !is_array($_SESSION['jogadores'])
) {

    header('Location: index.php');
    exit;
}


$jogadores =
    array_values(
        $_SESSION['jogadores']
    );

$meuNome =
    $_SESSION['meu_nome']
    ?? '';

$rodada =
    (int)(
        $_SESSION['rodada']
        ?? 1
    );

$fase =
    $_SESSION['fase_semana']
    ?? 'queridometro';

$moedas =
    (int)(
        $_SESSION['moedas_publico']
        ?? 0
    );

$nomes =
    nomesParticipantesModoTeste(
        $jogadores
    );

$fases =
    fasesDisponiveisModoTeste();


require_once __DIR__ . '/includes/actions/modo_teste.php';


function eDev($texto)
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Modo de Testes — BBB Simulator
    </title>

    <link
        rel="stylesheet"
        href="assets/css/modo_teste.css"
    >

</head>

<body>

<div class="dev-page">

    <header class="dev-header">

        <div>

            <span class="dev-badge">
                🧪 DESENVOLVIMENTO
            </span>

            <h1>
                Central de Testes
            </h1>

            <p>
                BBB Simulator • Ferramentas locais
            </p>

        </div>

        <a
            href="jogo.php"
            class="voltar"
        >
            ← Voltar ao jogo
        </a>

    </header>


    <div class="dev-resumo">

        <div>
            <span>🔥 Rodada</span>
            <strong><?= $rodada ?></strong>
        </div>

        <div>
            <span>🎮 Fase</span>
            <strong>
                <?= eDev(
                    $fases[$fase]
                    ?? $fase
                ) ?>
            </strong>
        </div>

        <div>
            <span>👥 Jogadores</span>
            <strong>
                <?= count($jogadores) ?>
            </strong>
        </div>

        <div>
            <span>🪙 Moedas</span>
            <strong>
                <?= $moedas ?>
            </strong>
        </div>

    </div>


    <main class="dev-grid">


        <!-- =============================================
             TEMPORADA
        ============================================== -->

        <section class="dev-card">

            <h2>
                🔥 Temporada
            </h2>

            <form method="POST">

                <label>
                    Rodada
                </label>

                <input
                    type="number"
                    name="rodada"
                    min="1"
                    value="<?= $rodada ?>"
                >

                <button
                    name="dev_rodada"
                >
                    Aplicar rodada
                </button>

            </form>


            <form method="POST">

                <label>
                    Moedas
                </label>

                <input
                    type="number"
                    name="moedas"
                    min="0"
                    value="<?= $moedas ?>"
                >

                <button
                    name="dev_moedas"
                >
                    Aplicar moedas
                </button>

            </form>

            <form method="POST">

                <button
                    name="dev_add_moedas"
                >
                    🪙 +100 moedas
                </button>

            </form>

        </section>


        <!-- =============================================
             FASES
        ============================================== -->

        <section class="dev-card dev-card-wide">

            <h2>
                🎮 Ir para fase
            </h2>

            <div class="fase-grid">

                <?php
                foreach (
                    $fases
                    as $valor => $label
                ):
                ?>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="fase"
                            value="<?= eDev($valor) ?>"
                        >

                        <button
                            name="dev_fase"
                            class="<?=
                                $fase === $valor
                                    ? 'ativo'
                                    : ''
                            ?>"
                        >
                            <?= eDev($label) ?>
                        </button>

                    </form>

                <?php endforeach; ?>

            </div>

        </section>


        <!-- =============================================
             PODERES / STATUS
        ============================================== -->

        <section class="dev-card">

            <h2>
                👑 Status
            </h2>

            <?php
            $statusForms = [
                'dev_lider' =>
                    '👑 Definir Líder',

                'dev_anjo' =>
                    '😇 Definir Anjo',

                'dev_imune' =>
                    '🛡️ Definir Imune'
            ];
            ?>

            <?php
            foreach (
                $statusForms
                as $acao => $titulo
            ):
            ?>

                <form method="POST">

                    <label>
                        <?= eDev($titulo) ?>
                    </label>

                    <select
                        name="participante"
                        required
                    >

                        <option value="">
                            Escolher participante
                        </option>

                        <?php foreach ($nomes as $nome): ?>

                            <option
                                value="<?= eDev($nome) ?>"
                            >
                                <?= eDev($nome) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <button
                        name="<?= eDev($acao) ?>"
                    >
                        Aplicar
                    </button>

                </form>

            <?php endforeach; ?>

        </section>


        <!-- =============================================
             PAREDÃO
        ============================================== -->

        <section class="dev-card">

            <h2>
                🚨 Paredão
            </h2>

            <form method="POST">

                <select
                    name="participante"
                    required
                >

                    <option value="">
                        Participante
                    </option>

                    <?php foreach ($nomes as $nome): ?>

                        <option
                            value="<?= eDev($nome) ?>"
                        >
                            <?= eDev($nome) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <button
                    name="dev_add_paredao"
                >
                    + Adicionar
                </button>

            </form>


            <div class="paredao-atual">

                <?php
                $paredaoAtual =
                    $_SESSION['paredao']
                    ?? [];
                ?>

                <?php if ($paredaoAtual): ?>

                    <?php foreach ($paredaoAtual as $nome): ?>

                        <span>
                            🚨 <?= eDev($nome) ?>
                        </span>

                    <?php endforeach; ?>

                <?php else: ?>

                    <small>
                        Nenhum participante no Paredão.
                    </small>

                <?php endif; ?>

            </div>


            <form method="POST">

                <button
                    name="dev_limpar_paredao"
                    class="danger"
                >
                    🧹 Limpar Paredão
                </button>

            </form>

        </section>


        <!-- =============================================
             DINÂMICAS
        ============================================== -->

        <section class="dev-card dev-card-wide">

            <h2>
                ⚡ Dinâmicas especiais
            </h2>

            <div class="acoes-grid">

                <form method="POST">

                    <button
                        name="dev_paredao_falso"
                    >
                        🚨 Forçar próximo
                        Paredão Falso
                    </button>

                </form>


                <form method="POST">

                    <button
                        name="dev_casa_vidro"
                    >
                        🏠 Preparar Casa de Vidro
                    </button>

                </form>


                <form method="POST">

                    <button
                        name="dev_bigfone"
                    >
                        ☎️ Ir para Big Fone
                    </button>

                </form>


                <form method="POST">

                    <button
                        name="dev_final"
                        class="especial"
                    >
                        🏆 Simular Grande Final
                    </button>

                </form>

            </div>

        </section>


        <!-- =============================================
             UTILIDADES
        ============================================== -->

        <section class="dev-card dev-card-wide">

            <h2>
                🛠 Utilidades
            </h2>

            <div class="acoes-grid">

                <form method="POST">

                    <button
                        name="dev_limpar_ao_vivo"
                    >
                        📣 Limpar Ao Vivo
                    </button>

                </form>


                <form method="POST">

                    <button
                        name="dev_limpar_feed"
                    >
                        📱 Limpar Feed BBB
                    </button>

                </form>


                <form method="POST">

                    <button
                        name="dev_reset_semana"
                        class="danger"
                    >
                        ♻️ Resetar semana
                    </button>

                </form>


                <a
                    href="jogo.php"
                    class="button-link"
                >
                    🎮 Voltar ao jogo
                </a>

            </div>

        </section>

    </main>

</div>

</body>
</html>