<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

/* =========================================================
   🧭 NAVEGAÇÃO
   ========================================================= */
require_once __DIR__ . '/includes/actions/navegacao.php';
require_once __DIR__ . '/includes/helpers/render.php';


/* =========================================================
   🧠 LÓGICA DO JOGO
   ========================================================= */
require_once __DIR__ . '/includes/logica/utilitarios.php';
require_once __DIR__ . '/includes/logica/semana.php';
require_once __DIR__ . '/includes/logica/participantes.php';
require_once __DIR__ . '/includes/logica/inicializacao.php';
require_once __DIR__ . '/includes/logica/casa_vidro.php';
require_once __DIR__ . '/includes/logica/relacoes.php';
require_once __DIR__ . '/includes/logica/popularidade.php';
require_once __DIR__ . '/includes/logica/romance.php';
require_once __DIR__ . '/includes/logica/aliancas.php';
require_once __DIR__ . '/includes/logica/inteligencia_npc.php';
require_once __DIR__ . '/includes/logica/curinga.php';
require_once __DIR__ . '/includes/logica/big_fone.php';
require_once __DIR__ . '/includes/logica/bate_volta.php';
require_once __DIR__ . '/includes/logica/paredao.php';
require_once __DIR__ . '/includes/logica/loja_publico.php';
require_once __DIR__ . '/includes/logica/interacoes.php';
require_once __DIR__ . '/includes/logica/discordia.php';
require_once __DIR__ . '/includes/logica/festa.php';
require_once __DIR__ . '/includes/logica/confessionario.php';
require_once __DIR__ . '/includes/logica/fofoca_vt.php';
require_once __DIR__ . '/includes/logica/queridometro.php';
require_once __DIR__ . '/includes/logica/feed_publico.php';
require_once __DIR__ . '/includes/logica/feed_inteligente.php';


/* =========================================================
   🔄 FLUXO DO JOGO
   ========================================================= */
require_once __DIR__ . '/includes/fluxo/estado_fases.php';


/* =========================================================
   👥 CARREGAR E PREPARAR PARTICIPANTES
   ========================================================= */
if (!isset($_SESSION['jogadores'])) {
    header('Location: index.php');
    exit;
}

$jogadores = $_SESSION['jogadores'];
$meuNome = trim($_SESSION['meu_nome'] ?? '');

if (
    !empty($_SESSION['paredao_falso_ativo']) &&
    ($_SESSION['fase_semana'] ?? '') === 'quarto_secreto'
) {
    header('Location: quarto_secreto.php');
    exit;
}

removerParticipantesDuplicados(
    $jogadores,
    $meuNome
);

garantirEstruturaParticipantes(
    $jogadores
);

$_SESSION['jogadores'] = $jogadores;

$rodada = $_SESSION['rodada'] ?? 1;

$meuJogadorAtual = atualizarEstadoDoMeuJogador(
    $jogadores,
    $meuNome
);

garantirRelacoesIniciaisJogador(
    $jogadores,
    $meuNome
);


/* =========================================================
   📅 ESTADO DA SEMANA
   ========================================================= */
garantirEstadoSemana();

$fase = $_SESSION['fase_semana'];

garantirInicioRodadaQueridometro(
    $fase
);

/* =========================================================
   🏠 CASA DE VIDRO
   ========================================================= */

   verificarInicioCasaVidro(
    $jogadores,
    $fase,
    $rodada
);

/* =========================================================
   📣 GARANTIR O AO VIVO
   ========================================================= */
if (
    !isset($_SESSION['evento_extra']) ||
    !is_array($_SESSION['evento_extra'])
) {
    $_SESSION['evento_extra'] = [];
}


/* =========================================================
   🪙 MOEDAS DO PÚBLICO
   ========================================================= */
if (!isset($_SESSION['moedas_publico'])) {
    $_SESSION['moedas_publico'] = 50;
}

$_SESSION['moedas_publico'] = max(
    0,
    (int) $_SESSION['moedas_publico']
);


/* =========================================================
   🎥 LIMPAR FALAS ANTIGAS DO CONFESSIONÁRIO DO AO VIVO
   ========================================================= */
$_SESSION['evento_extra'] = array_values(
    array_filter(
        $_SESSION['evento_extra'],
        function ($ev) {
            return (
                mb_stripos((string) $ev, 'confessionário', 0, 'UTF-8') === false &&
                mb_stripos((string) $ev, 'confessionario', 0, 'UTF-8') === false
            );
        }
    )
);


/* =========================================================
   🎮 PREPARAÇÃO DA RODADA
   ========================================================= */
$qtdVIP = calcularQtdVIP(
    count($jogadores)
);

sincronizarImunidadesGlobais(
    $jogadores
);

$_SESSION['jogadores'] = $jogadores;

verificarBonusMarcosMoedas(
    $jogadores
);

$mostrarLojaPublico = lojaPublicoDisponivelRodada(
    $rodada
);


/* =========================================================
   🔄 TRANSIÇÕES GERAIS DE FASE
   ========================================================= */
prepararAcoesInteracoes(
    $fase
);

processarDiscordiaConcluida(
    $fase
);

processarEntradaEliminacao(
    $fase
);

processarEntradaQuartoSecreto(
    $fase
);

prepararTemaDiscordia(
    $fase
);


/*
 * Retornos automáticos NÃO redirecionam.
 * Apenas sincronizam fase_semana.
 */
require_once __DIR__ . '/includes/fluxo/retornos_automaticos.php';

prepararEstadoConfessionario(
    $jogadores,
    $meuNome,
    $fase
);


/* =========================================================
   🔄 RECARREGAR ESTADO APÓS OS FLUXOS
   ========================================================= */
$jogadores = $_SESSION['jogadores'];

$fase = $_SESSION['fase_semana'] ?? $fase;

garantirMeuJogadorNaLista(
    $jogadores
);

garantirEstruturaParticipantes(
    $jogadores
);

$meuNome = trim(
    $_SESSION['meu_nome'] ?? ''
);

ordenarParticipantesParaExibicao(
    $jogadores,
    $meuNome
);

$_SESSION['jogadores'] = $jogadores;

sincronizarFasesFinais(
    $fase,
    $jogadores
);


/* =========================================================
   🎮 ACTIONS
   ========================================================= */
require_once __DIR__ . '/includes/actions/festa.php';
require_once __DIR__ . '/includes/actions/interacoes.php';
require_once __DIR__ . '/includes/actions/curinga.php';
require_once __DIR__ . '/includes/actions/paredao.php';
require_once __DIR__ . '/includes/actions/bate_volta.php';
require_once __DIR__ . '/includes/actions/discordia.php';

/*
 * IMPORTANTE:
 * VIP/Xepa de Líder NPC é processado somente aqui,
 * depois do clique em "Ver VIP e Xepa do Líder".
 */
require_once __DIR__ . '/includes/actions/vip_xepa.php';

require_once __DIR__ . '/includes/actions/avancar_fase.php';
require_once __DIR__ . '/includes/actions/queridometro.php';
require_once __DIR__ . '/includes/actions/controle_semana.php';
require_once __DIR__ . '/includes/actions/loja_publico.php';
require_once __DIR__ . '/includes/actions/feed_casa.php';


/* =========================================================
   🤖 DECISÕES AUTOMÁTICAS DOS NPCs
   ========================================================= */
require_once __DIR__ . '/includes/fluxo/decisoes_automaticas.php';

/* Recarrega o estado caso uma decisão automática tenha alterado a sessão. */
$jogadores = $_SESSION['jogadores'] ?? $jogadores;
$fase = $_SESSION['fase_semana'] ?? $fase;

/* =========================================================
   📱 ATUALIZAR REAÇÃO DO PÚBLICO
   ========================================================= */

   atualizarFeedPublico(
    $jogadores,
    $fase,
    $rodada,
    $meuNome
);

atualizarFeedPublicoInteligente(
    $jogadores,
    $fase,
    $rodada,
    $meuNome
);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>BBB Simulator</title>
    <link rel="stylesheet" href="assets/css/jogo.css">
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/feed_publico.css">
</head>

<body>

<?php
render('layout/header', [
    'rodada' => $rodada,
    'jogadores' => $jogadores
]);
?>

<div class="container">

    <?php
    render('components/participantes', [
        'jogadores' => $jogadores,
        'meuNome' => $meuNome
    ]);
    ?>

    <?php
    if ($fase == 'jogador_eliminado') {

        render('components/jogador_eliminado', [
            'jogadores' => $jogadores,
            'rodada' => $rodada
        ]);

    } else {

        render('components/painel', [
            'fase' => $fase,
            'rodada' => $rodada,
            'jogadores' => $jogadores,
            'meuNome' => $meuNome,
            'qtdVIP' => $qtdVIP ?? 0,
            'EMOJIS_QUERIDOMETRO' => $EMOJIS_QUERIDOMETRO
        ]);
    }
    ?>

    <div class="right">
        <?php
        render('components/feed_casa');
        ?>

        <?php
        render('components/loja_publico', [
            'mostrarLojaPublico' => $mostrarLojaPublico,
            'jogadores' => $jogadores,
            'rodada' => $rodada
        ]);
        ?>

    </div>

</div>

<?php
render('components/feed_publico', [
    'jogadores' => $jogadores,
    'rodada' => $rodada,
    'meuNome' => $meuNome
]);
?>

<div class="popup-bg" id="popupReset">
    <div class="popup-box">

        <h3>🔄 Novo Jogo</h3>

        <p>Deseja encerrar a temporada atual e começar tudo novamente?</p>

        <div class="popup-botoes">

            <button class="cancelar" onclick="fecharPopup()">
                Cancelar
            </button>

            <form method="POST" style="width:100%;">
                <button class="confirmar" name="novo_jogo">
                    Sim, Reiniciar
                </button>
            </form>

        </div>
    </div>
</div>

<script>
const qtdVIP = <?= $qtdVIP ?? 0 ?>;
const acaoSelecionada = <?= json_encode($_SESSION['acao_selecionada'] ?? '') ?>;
</script>

<script src="assets/js/jogo.js"></script>
<script src="assets/js/feed_publico.js"></script>

</body>
</html>