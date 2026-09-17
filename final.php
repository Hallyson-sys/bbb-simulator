<?php

session_start();

require_once __DIR__ . '/includes/logica/final.php';


$jogadores =
    array_values(
        $_SESSION['jogadores']
        ?? []
    );


/*
 * A Grande Final só pode acontecer
 * quando restarem exatamente 3.
 */
if (count($jogadores) !== 3) {

    header("Location: jogo.php");
    exit;
}

/* =========================================================
   🏆 GERAR RESULTADO FINAL
   ========================================================= */

/*
 * A versão serve também para atualizar saves
 * antigos que ainda tenham o ranking aleatório.
 */
if (
    !isset($_SESSION['ranking_final']) ||
    ($_SESSION['ranking_final_versao'] ?? 0) < 2
) {

    $resultadoFinal =
        gerarResultadoFinalInteligente(
            $jogadores
        );


    $_SESSION['ranking_final'] =
        $resultadoFinal['ranking'];


    $_SESSION['percentuais_final'] =
        $resultadoFinal['percentuais'];


    $_SESSION['pontuacoes_final'] =
        $resultadoFinal['pontuacoes'];


    $_SESSION['ranking_final_versao'] =
        2;
}


$ranking =
    $_SESSION['ranking_final'];


$percentuaisFinal =
    $_SESSION['percentuais_final']
    ?? [];


$pontuacoesFinal =
    $_SESSION['pontuacoes_final']
    ?? [];

/* Ordem visual embaralhada para ninguém saber quem é 1º, 2º ou 3º antes da revelação */
if(!isset($_SESSION['ordem_visual_final'])){
    $_SESSION['ordem_visual_final'] = ["primeiro", "segundo", "terceiro"];
    shuffle($_SESSION['ordem_visual_final']);
}

$ordemVisual = $_SESSION['ordem_visual_final'];

$percentuaisFinal =
    $_SESSION['percentuais_final']
    ?? [];

function e($texto){
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

function estat($j, $campo){
    return $j['estatisticas'][$campo] ?? 0;
}

function popularidadeFinal($j){
    return $j['popularidade'] ?? 50;
}

function resumoFinalista($j){
    $partes = [];

    if(!empty($j['personalidade'])){
        $partes[] = "🎭 ".$j['personalidade'];
    }

    if(!empty($j['profissao'])){
        $partes[] = "💼 ".$j['profissao'];
    }

    if(!empty($j['estado'])){
        $partes[] = "📍 ".$j['estado'];
    }

    return empty($partes) ? "Finalista do BBB Simulator" : implode(" • ", $partes);
}

function cardFinalista($j, $id){
    $pop = popularidadeFinal($j);
?>
<div class="card-finalista card-secreto" id="<?php echo e($id); ?>">

    <div class="sigilo">RESULTADO EM SIGILO</div>

    <div class="avatar-area">
        <div class="halo"></div>
        <div class="avatar">
            <span><?php echo e(mb_substr($j['nome'] ?? 'F', 0, 1, 'UTF-8')); ?></span>
        </div>
    </div>

    <h2><?php echo e($j['nome'] ?? 'Finalista'); ?></h2>

    <p class="bio-finalista">
        <?php echo e(resumoFinalista($j)); ?>
    </p>

    <div class="popularidade-bloco">
        <div class="pop-top">
            <span>🔥 Popularidade final</span>
            <strong><?php echo e($pop); ?>/100</strong>
        </div>
        <div class="pop-barra">
            <div style="width: <?php echo e($pop); ?>%;"></div>
        </div>
    </div>

    <div class="stats">
        <span><b>👑</b> Líder <strong><?php echo estat($j,'lider'); ?></strong></span>
        <span><b>😇</b> Anjo <strong><?php echo estat($j,'anjo'); ?></strong></span>
        <span><b>🟡</b> VIP <strong><?php echo estat($j,'vip'); ?></strong></span>
        <span><b>🍞</b> Xepa <strong><?php echo estat($j,'xepa'); ?></strong></span>
        <span><b>👹</b> Monstro <strong><?php echo estat($j,'monstro'); ?></strong></span>
        <span><b>🛡️</b> Imune <strong><?php echo estat($j,'imune'); ?></strong></span>
    </div>

    <div class="resultado-tag" id="tag-<?php echo e($id); ?>">
        <span class="tag-suspense">?</span>
    </div>

</div>
<?php
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Grande Final</title>

<link rel="stylesheet" href="assets/css/final.css">
</head>

<body>

<div class="container">

    <div class="topo">
        <div class="badge">📡 Ao vivo • Votação encerrada • Resultado em sigilo</div>
        <h1>Grande Final</h1>
        <p class="sub">O Brasil votou. Três finalistas chegaram até aqui. Só um será campeão.</p>
    </div>

    <div class="palco">

        <div class="tadeu" id="fala">
            🎤 “Hoje termina uma jornada. Ninguém sabe ainda quem venceu. Respirem fundo...”
        </div>

        <div class="relogio-suspense">
            <div class="ponto ativo" id="ponto0"></div>
            <div class="ponto" id="ponto1"></div>
            <div class="ponto" id="ponto2"></div>
            <div class="ponto" id="ponto3"></div>
        </div>

        <div class="finalistas">
            <?php foreach($ordemVisual as $posicao): ?>
                <?php cardFinalista($ranking[$posicao], $posicao); ?>
            <?php endforeach; ?>
        </div>

        <div class="botoes">
        <button
    type="button"
    id="btnRevelar"
>
    📺 Começar Revelação
</button>

            <form action="index.php" method="POST">
                <button class="novo" id="btnNovo">🔄 Nova Temporada</button>
            </form>
        </div>

    </div>

</div>

<script>
const percentuaisFinal = <?php echo json_encode(
    $percentuaisFinal,
    JSON_UNESCAPED_UNICODE
); ?>;

const nomesFinalistas = {
    primeiro: <?php echo json_encode($ranking['primeiro']['nome'] ?? ''); ?>,
    segundo: <?php echo json_encode($ranking['segundo']['nome'] ?? ''); ?>,
    terceiro: <?php echo json_encode($ranking['terceiro']['nome'] ?? ''); ?>
};
</script>

<script>
window.FINAL_DATA = <?php
echo json_encode(
    [
        'nomes' => [
            'primeiro' =>
                $ranking['primeiro']['nome']
                ?? '',

            'segundo' =>
                $ranking['segundo']['nome']
                ?? '',

            'terceiro' =>
                $ranking['terceiro']['nome']
                ?? ''
        ],

        'percentuais' =>
            $percentuaisFinal
            ?? []
    ],
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
);
?>;
</script>

<script src="assets/js/final.js"></script>

</body>
</html>
