<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/includes/logica/utilitarios.php';
require_once __DIR__ . '/includes/logica/modo_teste.php';
require_once __DIR__ . '/includes/logica/relacoes.php';
require_once __DIR__ . '/includes/logica/aliancas.php';
require_once __DIR__ . '/includes/logica/aliancas_3.php';
require_once __DIR__ . '/includes/logica/dinamicas_surpresa.php';
require_once __DIR__ . '/includes/logica/paredao.php';


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

// Um token para todos os formulários de diagnóstico (somente localhost).
if (empty($_SESSION['dev_csrf'])) {
    $_SESSION['dev_csrf'] = bin2hex(random_bytes(24));
}
$devToken = (string)$_SESSION['dev_csrf'];


require_once __DIR__ . '/includes/actions/modo_teste.php';

$dsOpcoes = dsDescricoes();
$dsInfo = dsAtual();
$dsAtiva = (string)($dsInfo['tipo'] ?? '');
$devGrupo = (string)(obterAliancaJogador($jogadores, $meuNome) ?? '');
$devMembros = $devGrupo !== '' ? membrosDaAlianca($jogadores, $devGrupo) : [];
$devCandidatosCrise = array_values(array_filter($devMembros, fn($n) => !nomeIgual($n, $meuNome)));
$devAviso = (string)($_SESSION['dev_aviso'] ?? '');
unset($_SESSION['dev_aviso']);


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
            <span>🔥 Semana</span>
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


    <?php if ($devAviso !== ''): ?><div class="dev-flash" role="status"><?= eDev($devAviso) ?></div><?php endif; ?>

    <main class="dev-grid">


        <!-- =============================================
             TEMPORADA
        ============================================== -->

        <section class="dev-card">

            <h2>
                🔥 Temporada
            </h2>

            <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                <label>
                    Semana
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
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

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
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

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
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

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
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

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
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

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
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                <button
                    name="dev_limpar_paredao"
                    class="danger"
                >
                    🧹 Limpar Paredão
                </button>

            </form>

        </section>


        <!-- =============================================
             DINÂMICAS SURPRESA 3.0 — TESTE REAL
        ============================================== -->

        <section class="dev-card dev-card-wide" id="dev-dinamicas">
            <h2>🎲 Laboratório de dinâmicas surpresa</h2>
            <p class="dev-help">Força a regra desta semana sem depender de sorteio. Em seguida, avance até a fase correspondente. As ações DEV modificam a temporada aberta: faça um save de teste antes.</p>
            <div class="dev-status-grid">
                <div class="dev-state"><small>Regra atual</small><strong><?= eDev($dsOpcoes[$dsAtiva]['titulo'] ?? 'Nenhuma dinâmica ativa') ?></strong></div>
                <div class="dev-state"><small>Semana</small><strong><?= (int)($dsInfo['rodada'] ?? $rodada) ?></strong></div>
                <div class="dev-state"><small>Imunidade extra</small><strong><?= eDev((int)($_SESSION['ds3_imune']['rodada'] ?? 0) === $rodada ? ($_SESSION['ds3_imune']['nome'] ?? 'Não concedida') : 'Não concedida') ?></strong></div>
                <div class="dev-state"><small>Carta Misteriosa</small><strong><?= eDev((int)($_SESSION['ds3_carta']['rodada'] ?? 0) === $rodada ? ($_SESSION['ds3_carta']['nome'] ?? 'Não entregue') : 'Não entregue') ?></strong></div>
            </div>
            <div class="dev-tool-columns">
                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">
                    <label for="dev_tipo">Escolha a dinâmica que deseja testar</label>
                    <select name="tipo" id="dev_tipo" required>
                        <?php foreach ($dsOpcoes as $codigo => $desc): ?>
                            <option value="<?= eDev($codigo) ?>" <?= $dsAtiva === $codigo ? 'selected' : '' ?>><?= eDev($desc['titulo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button name="dev_dinamica" value="1">🚨 Ativar para esta semana</button>
                </form>
                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">
                    <label>Testar benefício imediatamente</label>
                    <p class="dev-help">Disponível apenas para Imunidade Surpresa e Carta Misteriosa. As outras regras funcionam durante a formação do Paredão.</p>
                    <button name="dev_aplicar_dinamica" value="1" <?= !in_array($dsAtiva, ['imunidade_surpresa','carta_misteriosa'], true) ? 'disabled' : '' ?>>⚡ Conceder efeito agora</button>
                </form>
            </div>
            <form method="POST" class="dev-inline-form">
                <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">
                <button name="dev_limpar_dinamica" value="1" class="danger">🧹 Desativar regra de teste</button>
            </form>
            <div class="dev-tip"><strong>Quando testar:</strong> Votação Aberta, Paredão Especial e Votação Turbinada → Formação do Paredão; Contragolpe → indicação do Líder; Imunidade/Carta → após a Festa ou botão de teste acima. Desativar uma regra não desfaz votos nem Paredões já finalizados.</div>
        </section>

        <section class="dev-card dev-card-wide" id="dev-aliancas">
            <h2>🤝 Laboratório de Alianças 3.0</h2>
            <?php if ($devGrupo === '' || count($devMembros) < 2): ?>
                <p class="dev-help">Seu personagem precisa estar em uma aliança com pelo menos 2 membros para testar crises, lealdade e reuniões. Forme uma aliança no jogo e retorne aqui.</p>
            <?php else: ?>
                <p class="dev-help"><strong><?= eDev($devGrupo) ?></strong> • Lealdade interna estimada dos integrantes (visível apenas no modo DEV):</p>
                <div class="dev-aliados">
                    <?php foreach ($devMembros as $membro): ?>
                    <div class="dev-aliado"><span><?= eDev($membro) ?></span><strong><?= a3Lealdade($jogadores, $membro) ?> / 100</strong></div>
                    <?php endforeach; ?>
                </div>
                <div class="dev-tool-columns">
                    <form method="POST">
                        <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">
                        <label for="dev_membro_crise">Preparar uma crise de lealdade</label>
                        <select name="membro_crise" id="dev_membro_crise" required>
                            <?php foreach ($devCandidatosCrise as $membro): ?>
                            <option value="<?= eDev($membro) ?>"><?= eDev($membro) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button name="dev_crise_alianca" value="1">💥 Gerar tensão interna</button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">
                        <label>Repetir reunião estratégica nesta semana</label>
                        <p class="dev-help">Libera novamente a reunião e leva para Interações 1, sem zerar os acordos anteriores.</p>
                        <button name="dev_reuniao_alianca" value="1">🎙️ Testar reunião</button>
                    </form>
                </div>
                <div class="dev-tip">Crises não forçam um voto nem revelam segredos: afetam a lealdade e liberam cenários para observar a reação dos NPCs.</div>
            <?php endif; ?>
        </section>

        <section class="dev-card dev-card-wide" id="dev-eventos">
            <h2>🎬 Eventos de Convivência</h2>
            <p class="dev-help">Preparar uma cena especial para a próxima visita a Interações ou Festa. A cena continua respeitando as relações e personagens disponíveis.</p>
            <form method="POST" class="dev-inline-form">
                <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">
                <select name="dev_cena_fase" aria-label="Fase da cena especial"><option value="interacoes_1">💬 Interações</option><option value="festa">🎉 Festa</option></select>
                <button name="dev_cena_especial" value="1">🎥 Gerar evento para testar</button>
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
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                    <button
                        name="dev_paredao_falso"
                    >
                        🚨 Forçar próximo
                        Paredão Falso
                    </button>

                </form>


                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                    <button
                        name="dev_casa_vidro"
                    >
                        🏠 Preparar Casa de Vidro
                    </button>

                </form>


                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                    <button
                        name="dev_bigfone"
                    >
                        ☎️ Ir para Big Fone
                    </button>

                </form>


                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

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
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                    <button
                        name="dev_limpar_ao_vivo"
                    >
                        📣 Limpar Ao Vivo
                    </button>

                </form>


                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                    <button
                        name="dev_limpar_feed"
                    >
                        📱 Limpar Feed BBB
                    </button>

                </form>


                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

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