<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'includes/logica_jogo.php';

if(!isset($_SESSION['jogadores']) || !isset($_SESSION['paredao'])){
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
$meuJogadorEliminado = $_SESSION['meu_jogador_snapshot'] ?? null;

/* ==========================
   FUNÇÕES VISUAIS
========================== */

function e($texto){
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

function nomeIgualResultado($a, $b){
    return mb_strtolower(trim((string)$a), 'UTF-8') === mb_strtolower(trim((string)$b), 'UTF-8');
}

function buscarJogadorResultado($jogadores, $nome){
    foreach($jogadores as $j){
        if(nomeIgualResultado(($j['nome'] ?? ''), $nome)){
            return $j;
        }
    }

    return null;
}

function resumoParticipanteResultado($jogador){
    if(!$jogador) return "Participante";

    $partes = [];

    if(!empty($jogador['personalidade'])){
        $partes[] = "🎭 ".$jogador['personalidade'];
    }

    if(!empty($jogador['estado'])){
        $partes[] = "📍 ".$jogador['estado'];
    }

    if(!empty($jogador['profissao'])){
        $partes[] = "💼 ".$jogador['profissao'];
    }

    return empty($partes) ? "Participante" : implode(" • ", $partes);
}

function corPorcentagem($pct){
    if($pct >= 60) return "perigo";
    if($pct >= 35) return "alerta";
    return "safe";
}

function estatResultado($jogador, $campo){
    return $jogador['estatisticas'][$campo] ?? 0;
}

function limitarResultado($valor, $min = 0, $max = 100){
    return max($min, min($max, $valor));
}

/* Se já revelou e voltou por refresh, mantém informação */
if(isset($_SESSION['ultimo_ranking_eliminacao']) && isset($_SESSION['eliminado'])){
    $ranking = $_SESSION['ultimo_ranking_eliminacao'];
    $eliminado = $_SESSION['eliminado'];
}

/* ==========================
   REVELAR ELIMINADO
========================== */

if(isset($_POST['revelar'])){

    /* Segurança: líder nunca deve aparecer no paredão */
    $liderAtual = $_SESSION['lider'] ?? '';

    $paredao = array_values(array_filter($paredao, function($nome) use ($liderAtual){
        return !nomeIgualResultado($nome, $liderAtual);
    }));

    if(count($paredao) < 2){
        $_SESSION['evento_extra'][] = "⚠️ Erro ao formar paredão: participantes insuficientes.";
        header("Location: jogo.php");
        exit;
    }

    shuffle($paredao);

    $qtdParedao = count($paredao);

    if($qtdParedao == 2){

        $p1 = rand(5100,8000) / 100;
        $p2 = round(100 - $p1, 2);

        $ranking = [
            $paredao[0] => $p1,
            $paredao[1] => $p2
        ];

    }else{

        $p1 = rand(4500,7500) / 100;
        $resto = 100 - $p1;

        $maxP2 = max(1001, (int)($resto * 100) - 1);
        $p2 = rand(1000, $maxP2) / 100;
        $p3 = round(100 - $p1 - $p2, 2);

        $ranking = [
            $paredao[0] => $p1,
            $paredao[1] => $p2,
            $paredao[2] => $p3
        ];
    }

    arsort($ranking);

    $eliminado = array_key_first($ranking);

    foreach($jogadores as $k => $j){

        if(nomeIgualResultado(($j['nome'] ?? ''), $eliminado)){

            if(nomeIgualResultado($eliminado, $meuNome)){
                $fuiEliminado = true;
                $meuJogadorEliminado = $j;

                $_SESSION['jogador_eliminado'] = true;
                $_SESSION['fase_semana'] = 'jogador_eliminado';
                $_SESSION['meu_jogador_snapshot'] = $j;
                $_SESSION['minha_popularidade_final'] = $j['popularidade'] ?? 50;
                $_SESSION['minha_colocacao_final'] = count($jogadores);
            }

            unset($jogadores[$k]);
        }
    }

    $_SESSION['jogadores'] = array_values($jogadores);
    $_SESSION['eliminado'] = $eliminado;
    $_SESSION['ultimo_ranking_eliminacao'] = $ranking;

    $mostrarResultado = true;
}

/* ==========================
   CONTINUAR
========================== */

if(isset($_POST['continuar'])){

    $eliminadoSessao = $_SESSION['eliminado'] ?? '';

    if(nomeIgualResultado($eliminadoSessao, $meuNome) || isset($_SESSION['jogador_eliminado'])){
        $_SESSION['jogador_eliminado'] = true;
        $_SESSION['fase_semana'] = 'jogador_eliminado';
        header("Location: jogo.php");
        exit;
    }

    if(count($_SESSION['jogadores']) <= 3){
        header("Location: final.php");
        exit;
    }

    $_SESSION['rodada'] = ($_SESSION['rodada'] ?? 1) + 1;
    $_SESSION['fase_semana'] = 'interacoes_1';
    $_SESSION['acoes_restantes'] = 3;

    unset($_SESSION['paredao']);
    unset($_SESSION['eliminado']);
    unset($_SESSION['ultimo_ranking_eliminacao']);
    unset($_SESSION['paredao_formado']);
    unset($_SESSION['votos_paredao']);
    unset($_SESSION['dedo_duro']);
    unset($_SESSION['indicacao_lider']);
    unset($_SESSION['indicacao_bigfone']);
    unset($_SESSION['bigfone_indicacao_pendente']);
    unset($_SESSION['bigfone_dono_poder']);
    unset($_SESSION['vip_definido']);
    unset($_SESSION['monstro_definido']);
    unset($_SESSION['bigfone_feito']);
    unset($_SESSION['queridometro_feito']);
    unset($_SESSION['queridometro_resultado']);
    unset($_SESSION['npc_festa_feita']);

    foreach($_SESSION['jogadores'] as &$j){
        $j['status']['lider'] = false;
        $j['status']['anjo'] = false;
        $j['status']['imune'] = false;
        $j['status']['vip'] = false;
        $j['status']['xepa'] = false;
        $j['status']['monstro'] = false;
    }
    unset($j);

    header("Location: jogo.php");
    exit;
}

/* NOVA TEMPORADA */
if(isset($_POST['novo_jogo'])){
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

if(nomeIgualResultado($eliminado, $meuNome)){
    $fuiEliminado = true;
    if(!$meuJogadorEliminado){
        $meuJogadorEliminado = $_SESSION['meu_jogador_snapshot'] ?? [];
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Noite de Eliminação</title>

<style>

@import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600;700;800&display=swap');

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Inter',Arial,sans-serif;
    background:
        radial-gradient(circle at top left, rgba(255,0,140,.24), transparent 34%),
        radial-gradient(circle at top right, rgba(0,217,255,.18), transparent 32%),
        linear-gradient(145deg,#070716,#12051f 45%,#05050b);
    color:white;
    min-height:100vh;
    padding:28px;
    overflow-x:hidden;
}

body::before{
    content:"";
    position:fixed;
    inset:0;
    background:
        linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
    background-size:42px 42px;
    mask-image:linear-gradient(to bottom, rgba(0,0,0,.8), transparent);
    pointer-events:none;
}

.container{
    width:min(1120px,100%);
    margin:auto;
    position:relative;
    z-index:2;
}

.topo{
    text-align:center;
    margin-bottom:26px;
}

.badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 16px;
    border:1px solid rgba(255,255,255,.12);
    border-radius:999px;
    background:rgba(255,255,255,.06);
    color:#d8d8ff;
    font-size:13px;
    margin-bottom:14px;
    box-shadow:0 0 28px rgba(255,0,140,.16);
}

.topo h1{
    font-family:'Bebas Neue',Arial,sans-serif;
    font-size:clamp(48px,8vw,92px);
    letter-spacing:2px;
    line-height:.92;
    background:linear-gradient(90deg,#fff,#ff4fb6,#00d9ff);
    -webkit-background-clip:text;
    background-clip:text;
    color:transparent;
    text-shadow:0 0 42px rgba(255,0,140,.16);
}

.topo p{
    margin-top:12px;
    color:#c8c8e6;
    font-size:16px;
}

.stage{
    display:grid;
    grid-template-columns:1.05fr .95fr;
    gap:22px;
    align-items:stretch;
}

.box{
    background:linear-gradient(145deg,rgba(255,255,255,.085),rgba(255,255,255,.035));
    border:1px solid rgba(255,255,255,.12);
    border-radius:28px;
    padding:24px;
    box-shadow:0 24px 65px rgba(0,0,0,.38);
    backdrop-filter:blur(16px);
    margin-bottom:22px;
}

.box h2{
    font-size:24px;
    margin-bottom:18px;
}

.paredao-grid{
    display:grid;
    gap:14px;
}

.card-paredao{
    position:relative;
    overflow:hidden;
    border-radius:22px;
    padding:18px;
    background:linear-gradient(135deg,rgba(255,0,140,.12),rgba(0,217,255,.08));
    border:1px solid rgba(255,255,255,.12);
}

.card-paredao::after{
    content:"";
    position:absolute;
    width:110px;
    height:110px;
    border-radius:50%;
    background:rgba(255,255,255,.06);
    right:-38px;
    top:-40px;
}

.card-paredao .label{
    font-size:12px;
    color:#ffb7dd;
    text-transform:uppercase;
    letter-spacing:1.2px;
    margin-bottom:8px;
}

.card-paredao h3{
    font-size:27px;
    margin-bottom:8px;
}

.card-paredao p{
    color:#cfd0ef;
    font-size:13px;
    line-height:1.45;
}

.discurso{
    display:grid;
    gap:13px;
}

.fala{
    padding:14px 16px;
    border-radius:16px;
    background:rgba(0,0,0,.18);
    border:1px solid rgba(255,255,255,.08);
    color:#ededff;
    line-height:1.55;
    animation:subir .55s ease both;
}

.fala:nth-child(2){animation-delay:.08s;}
.fala:nth-child(3){animation-delay:.16s;}
.fala:nth-child(4){animation-delay:.24s;}
.fala:nth-child(5){animation-delay:.32s;}

.btn{
    width:100%;
    margin-top:20px;
    padding:18px 22px;
    border:none;
    border-radius:18px;
    font-size:18px;
    font-weight:800;
    cursor:pointer;
    color:white;
    background:linear-gradient(135deg,#ff008c,#7a00ff,#00d9ff);
    box-shadow:0 14px 40px rgba(255,0,140,.24);
    transition:.25s;
}

.btn:hover{
    transform:translateY(-2px) scale(1.01);
    box-shadow:0 18px 55px rgba(0,217,255,.24);
}

.reveal-wrap{
    text-align:center;
    animation:aparecer .9s ease both;
}

.suspense{
    color:#cfcfff;
    font-size:20px;
    margin-bottom:10px;
}

.nomeSaiu{
    font-family:'Bebas Neue',Arial,sans-serif;
    font-size:clamp(62px,11vw,128px);
    letter-spacing:2px;
    margin:8px 0 4px;
    color:#ff4d8d;
    text-shadow:
        0 0 22px rgba(255,0,100,.9),
        0 0 80px rgba(255,0,140,.35);
    animation:pulse 1.1s infinite alternate;
}

.eliminado-sub{
    color:#f7c2d7;
    font-size:18px;
    margin-bottom:18px;
}

.resultado-grid{
    display:grid;
    gap:14px;
    margin-top:6px;
}

.rank{
    padding:16px;
    border-radius:18px;
    background:rgba(255,255,255,.055);
    border:1px solid rgba(255,255,255,.09);
}

.rank-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:10px;
}

.rank strong{
    font-size:18px;
}

.rank span{
    font-weight:800;
    font-size:18px;
}

.barra-fundo{
    width:100%;
    height:14px;
    background:rgba(255,255,255,.12);
    border-radius:999px;
    overflow:hidden;
    box-shadow:inset 0 0 10px rgba(0,0,0,.35);
}

.barra-preenchida{
    height:100%;
    max-width:100%;
    min-width:4px;
    border-radius:999px;
    transition:width 1.1s ease;
}

.barra-preenchida.perigo{
    background:linear-gradient(90deg,#ff2a6d,#ff8a00);
    box-shadow:0 0 18px rgba(255,42,109,.5);
}

.barra-preenchida.alerta{
    background:linear-gradient(90deg,#ffd43b,#ff008c);
    box-shadow:0 0 18px rgba(255,212,59,.4);
}

.barra-preenchida.safe{
    background:linear-gradient(90deg,#00d9ff,#00ff99);
    box-shadow:0 0 18px rgba(0,217,255,.4);
}

.after-text{
    color:#d7d7ee;
    line-height:1.6;
    margin-top:12px;
}

.glass-alert{
    margin-top:20px;
    padding:18px;
    border-radius:20px;
    background:rgba(255,0,100,.09);
    border:1px solid rgba(255,0,140,.22);
    color:#ffd9eb;
}

.stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
    gap:14px;
    margin-top:18px;
}

.stat-card{
    padding:18px;
    border-radius:20px;
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.10);
    box-shadow:0 14px 32px rgba(0,0,0,.22);
}

.stat-card strong{
    display:block;
    font-size:28px;
    margin-bottom:5px;
}

.stat-card span{
    color:#cfcfe8;
    font-size:13px;
}

.popularidade-barra{
    width:100%;
    height:18px;
    border-radius:999px;
    background:rgba(255,255,255,.10);
    overflow:hidden;
    margin:16px 0 8px;
}

.popularidade-fill{
    height:100%;
    border-radius:999px;
    background:linear-gradient(90deg,#ff008c,#7a00ff,#00d9ff);
    box-shadow:0 0 18px rgba(255,0,140,.45);
}

@keyframes aparecer{
    from{opacity:0; transform:translateY(24px) scale(.98);}
    to{opacity:1; transform:translateY(0) scale(1);}
}

@keyframes subir{
    from{opacity:0; transform:translateY(16px);}
    to{opacity:1; transform:translateY(0);}
}

@keyframes pulse{
    from{transform:scale(1);}
    to{transform:scale(1.035);}
}

@media(max-width:850px){
    body{
        padding:18px;
    }

    .stage{
        grid-template-columns:1fr;
    }

    .box{
        padding:20px;
        border-radius:22px;
    }
}

</style>
</head>

<body>

<div class="container">

    <div class="topo">
        <div class="badge">📡 Transmissão ao vivo • Rodada <?php echo e($rodada); ?></div>
        <h1>Noite de Eliminação</h1>
        <p>O público decidiu. Um participante deixa a casa agora.</p>
    </div>

    <?php if(!$mostrarResultado): ?>

        <div class="stage">

            <div class="box">

                <h2>🚨 Paredão da Semana</h2>

                <div class="paredao-grid">

                    <?php foreach($paredao as $nome): ?>
                        <?php $jogadorParedao = buscarJogadorResultado($jogadores, $nome); ?>

                        <div class="card-paredao">
                            <div class="label">Emparedado</div>
                            <h3><?php echo e($nome); ?></h3>
                            <p><?php echo e(resumoParticipanteResultado($jogadorParedao)); ?></p>
                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

            <div class="box">

                <h2>🎤 Discurso do Tadeu</h2>

                <div class="discurso">
                    <div class="fala">🗣️ “Boa noite, brothers e sisters.”</div>
                    <div class="fala">🗣️ “Hoje termina a caminhada de um de vocês dentro da casa.”</div>
                    <div class="fala">🗣️ “Lá fora, o público viu cada escolha, cada silêncio e cada movimento.”</div>
                    <div class="fala">🗣️ “Quem fica, ganha mais uma chance de reescrever a própria história.”</div>
                    <div class="fala">🗣️ “Quem sai, descobre agora o peso da decisão do Brasil.”</div>
                </div>

                <form method="POST">
                    <button class="btn" name="revelar">
                        📺 Revelar Eliminado
                    </button>
                </form>

            </div>

        </div>

    <?php else: ?>

        <div class="box reveal-wrap">

            <p class="suspense">🗣️ “Quando eu terminar, seremos um a menos...”</p>
            <p class="suspense">🗣️ “Quem sai hoje é...”</p>

            <div class="nomeSaiu">
                <?php echo e($eliminado); ?>
            </div>

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
            </p>

        </div>

        <?php if($fuiEliminado): ?>

            <?php
                $popularidadeFinal = limitarResultado($meuJogadorEliminado['popularidade'] ?? ($_SESSION['minha_popularidade_final'] ?? 50), 0, 100);
                $colocacaoFinal = $_SESSION['minha_colocacao_final'] ?? (count($_SESSION['jogadores']) + 1);
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
                        <span>Rodada da eliminação</span>
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
