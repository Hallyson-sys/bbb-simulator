<?php
session_start();

$jogadores = array_values($_SESSION['jogadores'] ?? []);

if(count($jogadores) < 3){
    header("Location: jogo.php");
    exit;
}

/*
   Mantém o ranking final salvo.
   Assim, se atualizar a página, o campeão não muda.
*/
if(!isset($_SESSION['ranking_final'])){
    shuffle($jogadores);

    $_SESSION['ranking_final'] = [
        "primeiro" => $jogadores[0],
        "segundo"  => $jogadores[1],
        "terceiro" => $jogadores[2]
    ];
}

$ranking = $_SESSION['ranking_final'];

/* Ordem visual embaralhada para ninguém saber quem é 1º, 2º ou 3º antes da revelação */
if(!isset($_SESSION['ordem_visual_final'])){
    $_SESSION['ordem_visual_final'] = ["primeiro", "segundo", "terceiro"];
    shuffle($_SESSION['ordem_visual_final']);
}

$ordemVisual = $_SESSION['ordem_visual_final'];

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

<style>
@import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600;700;800;900&display=swap');

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    min-height:100vh;
    color:white;
    font-family:'Inter', Arial, sans-serif;
    padding:28px;
    overflow-x:hidden;
    background:
        radial-gradient(circle at 18% 12%, rgba(255,0,140,.32), transparent 28%),
        radial-gradient(circle at 85% 5%, rgba(0,217,255,.22), transparent 30%),
        radial-gradient(circle at bottom, rgba(255,215,0,.14), transparent 28%),
        linear-gradient(145deg,#050510,#190021 50%,#05050b);
}

body::before{
    content:"";
    position:fixed;
    inset:0;
    pointer-events:none;
    background:
        linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
    background-size:44px 44px;
    mask-image:linear-gradient(to bottom, rgba(0,0,0,.9), transparent);
}

body::after{
    content:"";
    position:fixed;
    inset:-80px;
    pointer-events:none;
    background:conic-gradient(from 180deg, transparent, rgba(255,0,140,.08), transparent, rgba(0,217,255,.08), transparent);
    filter:blur(35px);
    animation:girar 18s linear infinite;
    opacity:.8;
}

.container{
    width:min(1440px,100%);
    margin:auto;
    position:relative;
    z-index:2;
}

.topo{
    text-align:center;
    margin-bottom:24px;
}

.badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 16px;
    border-radius:999px;
    background:rgba(255,255,255,.07);
    border:1px solid rgba(255,255,255,.14);
    color:#dfddff;
    font-size:13px;
    margin-bottom:14px;
    box-shadow:0 0 34px rgba(255,0,140,.22);
}

h1{
    font-family:'Bebas Neue', Arial, sans-serif;
    font-size:clamp(58px,10vw,128px);
    letter-spacing:3px;
    line-height:.88;
    background:linear-gradient(90deg,#fff,#ffd700,#ff4fb6,#00d9ff);
    background-size:250% auto;
    -webkit-background-clip:text;
    background-clip:text;
    color:transparent;
    animation:brilhoTexto 5s ease infinite;
    text-shadow:0 0 60px rgba(255,215,0,.12);
}

.sub{
    margin-top:10px;
    color:#d8d4f0;
    font-size:18px;
}

.palco{
    position:relative;
    overflow:hidden;
    border-radius:34px;
    padding:24px;
    background:linear-gradient(145deg,rgba(255,255,255,.10),rgba(255,255,255,.035));
    border:1px solid rgba(255,255,255,.14);
    box-shadow:0 30px 95px rgba(0,0,0,.45);
    backdrop-filter:blur(18px);
}

.palco::before{
    content:"";
    position:absolute;
    inset:0;
    pointer-events:none;
    background:
        radial-gradient(circle at 50% -20%, rgba(255,215,0,.18), transparent 36%),
        linear-gradient(90deg, transparent, rgba(255,255,255,.08), transparent);
}

.tadeu{
    position:relative;
    z-index:2;
    min-height:76px;
    display:flex;
    align-items:center;
    justify-content:center;
    text-align:center;
    padding:18px 22px;
    border-radius:24px;
    background:rgba(0,0,0,.34);
    border:1px solid rgba(255,255,255,.12);
    font-size:clamp(20px,3vw,30px);
    font-weight:900;
    margin-bottom:24px;
    box-shadow:inset 0 0 35px rgba(255,255,255,.04), 0 0 35px rgba(255,0,140,.14);
}

.relogio-suspense{
    display:flex;
    justify-content:center;
    gap:8px;
    margin-bottom:22px;
}

.ponto{
    width:12px;
    height:12px;
    border-radius:50%;
    background:rgba(255,255,255,.22);
    box-shadow:0 0 12px rgba(255,255,255,.18);
}

.ponto.ativo{
    background:#ffd700;
    box-shadow:0 0 18px rgba(255,215,0,.8);
}

.finalistas{
    position:relative;
    z-index:2;
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:22px;
    align-items:stretch;
}

.card-finalista{
    position:relative;
    overflow:hidden;
    min-height:560px;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    padding:24px;
    border-radius:30px;
    background:
        radial-gradient(circle at top, rgba(255,0,140,.18), transparent 34%),
        linear-gradient(160deg,rgba(16,24,61,.92),rgba(20,0,31,.94));
    border:1px solid rgba(255,255,255,.13);
    box-shadow:0 22px 55px rgba(0,0,0,.38), 0 0 30px rgba(255,0,140,.18);
    transition:.45s ease;
}

.card-finalista::before{
    content:"";
    position:absolute;
    width:180px;
    height:180px;
    right:-70px;
    top:-70px;
    border-radius:50%;
    background:rgba(255,255,255,.06);
}

.card-finalista::after{
    content:"";
    position:absolute;
    inset:0;
    pointer-events:none;
    background:linear-gradient(120deg, transparent, rgba(255,255,255,.06), transparent);
    transform:translateX(-120%);
}

.card-finalista.revelando::after{
    animation:varrer 1.1s ease;
}

.sigilo{
    align-self:center;
    width:max-content;
    max-width:100%;
    padding:8px 12px;
    border-radius:999px;
    color:#d8d8f6;
    background:rgba(0,0,0,.24);
    border:1px solid rgba(255,255,255,.12);
    font-size:11px;
    letter-spacing:1.4px;
    font-weight:900;
    margin-bottom:12px;
}

.avatar-area{
    position:relative;
    width:112px;
    height:112px;
    margin:0 auto 16px;
}

.halo{
    position:absolute;
    inset:-9px;
    border-radius:50%;
    background:conic-gradient(#ffd700,#ff008c,#00d9ff,#ffd700);
    filter:blur(.2px);
    animation:girar 5s linear infinite;
}

.avatar{
    position:absolute;
    inset:0;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:radial-gradient(circle,#1a1a25 0 35%,#05050a 70%);
    border:6px solid rgba(5,5,10,.95);
    box-shadow:0 0 35px rgba(255,215,0,.34);
}

.avatar span{
    font-size:42px;
    font-weight:900;
    color:#ffd700;
}

.card-finalista h2{
    position:relative;
    z-index:2;
    text-align:center;
    font-size:clamp(28px,3vw,42px);
    color:#fff;
    margin-bottom:10px;
}

.bio-finalista{
    min-height:58px;
    color:#d8d8f2;
    font-size:14px;
    line-height:1.55;
    text-align:center;
}

.popularidade-bloco{
    margin-top:18px;
    padding:14px;
    border-radius:18px;
    background:rgba(0,0,0,.18);
    border:1px solid rgba(255,255,255,.10);
}

.pop-top{
    display:flex;
    justify-content:space-between;
    gap:12px;
    color:#eeeaff;
    font-size:13px;
    margin-bottom:10px;
}

.pop-top strong{
    color:#ffd700;
}

.pop-barra{
    height:10px;
    border-radius:999px;
    background:rgba(255,255,255,.10);
    overflow:hidden;
}

.pop-barra div{
    height:100%;
    max-width:100%;
    border-radius:999px;
    background:linear-gradient(90deg,#ff008c,#ffd700,#00d9ff);
}

.stats{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:9px;
    margin-top:20px;
}

.stats span{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    background:rgba(255,255,255,.075);
    border:1px solid rgba(255,255,255,.08);
    padding:10px;
    border-radius:14px;
    font-size:13px;
    color:#f2efff;
}

.stats span b{
    margin-right:4px;
}

.stats strong{
    color:#ffd700;
    font-size:15px;
}

.resultado-tag{
    margin-top:20px;
    min-height:74px;
    display:flex;
    align-items:center;
    justify-content:center;
    text-align:center;
    font-weight:900;
}

.tag-suspense{
    width:56px;
    height:56px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.12);
    color:#d6d3ee;
    font-size:28px;
}

.revelado-terceiro{
    opacity:.72;
    filter:grayscale(.45);
    transform:scale(.96);
}

.revelado-terceiro .resultado-tag{
    color:#cd7f32;
    font-size:32px;
    text-shadow:0 0 18px rgba(205,127,50,.55);
}

.revelado-segundo{
    box-shadow:0 22px 70px rgba(192,192,192,.28), 0 0 42px rgba(230,230,255,.30);
    border-color:rgba(230,230,255,.34);
}

.revelado-segundo .resultado-tag{
    color:#e8e8ff;
    font-size:34px;
    text-shadow:0 0 22px rgba(230,230,255,.65);
}

.revelado-campeao{
    transform:scale(1.045);
    border-color:rgba(255,215,0,.58);
    box-shadow:0 25px 95px rgba(255,215,0,.32), 0 0 70px rgba(255,0,140,.28);
}

.revelado-campeao .resultado-tag{
    color:#ffd700;
    font-size:40px;
    text-shadow:0 0 25px rgba(255,215,0,.9), 0 0 80px rgba(255,215,0,.35);
}

.botoes{
    position:relative;
    z-index:3;
    margin-top:28px;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:14px;
}

button{
    width:min(520px,100%);
    padding:18px 26px;
    border:none;
    border-radius:20px;
    color:white;
    font-size:19px;
    font-weight:900;
    cursor:pointer;
    background:linear-gradient(135deg,#ff008c,#7a00ff,#00d9ff);
    box-shadow:0 18px 48px rgba(255,0,140,.25);
    transition:.25s ease;
}

button:hover{
    transform:translateY(-2px) scale(1.01);
    box-shadow:0 22px 65px rgba(0,217,255,.24);
}

.novo{
    display:none;
    background:linear-gradient(135deg,#151515,#303030);
    box-shadow:0 18px 48px rgba(0,0,0,.35);
}

.confete{
    position:fixed;
    top:-20px;
    width:10px;
    height:16px;
    background:#ffd700;
    z-index:20;
    animation:cair 3.8s linear forwards;
}

@keyframes cair{
    to{
        transform:translateY(110vh) rotate(760deg);
        opacity:0;
    }
}

@keyframes varrer{
    from{transform:translateX(-120%);}
    to{transform:translateX(120%);}
}

@keyframes girar{
    to{transform:rotate(360deg);}
}

@keyframes brilhoTexto{
    0%,100%{background-position:0% center;}
    50%{background-position:100% center;}
}

@media(max-width:1050px){
    .finalistas{
        grid-template-columns:1fr;
    }

    .card-finalista{
        min-height:auto;
    }

    .revelado-campeao{
        transform:scale(1);
    }
}

@media(max-width:560px){
    body{
        padding:16px;
    }

    .palco{
        padding:16px;
        border-radius:24px;
    }

    .stats{
        grid-template-columns:1fr;
    }
}
</style>
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
            <button id="btnRevelar" onclick="revelar()">📺 Começar Revelação</button>

            <form action="index.php" method="POST">
                <button class="novo" id="btnNovo">🔄 Nova Temporada</button>
            </form>
        </div>

    </div>

</div>

<script>
let etapa = 0;

function atualizarPontos(){
    for(let i = 0; i <= 3; i++){
        const p = document.getElementById('ponto' + i);
        if(p){
            p.classList.toggle('ativo', i <= etapa);
        }
    }
}

function efeitoRevelacao(id){
    const card = document.getElementById(id);
    if(!card) return;

    card.classList.add('revelando');

    setTimeout(() => {
        card.classList.remove('revelando');
    }, 1200);

    card.scrollIntoView({behavior:'smooth', block:'center'});
}

function soltarConfete(){
    const cores = ['#ffd700', '#ff008c', '#00d9ff', '#7a00ff', '#ffffff', '#00ff99'];

    for(let i = 0; i < 90; i++){
        const c = document.createElement('div');
        c.className = 'confete';
        c.style.left = Math.random() * 100 + 'vw';
        c.style.background = cores[Math.floor(Math.random() * cores.length)];
        c.style.animationDelay = (Math.random() * .9) + 's';
        c.style.transform = 'rotate(' + (Math.random() * 360) + 'deg)';
        document.body.appendChild(c);

        setTimeout(() => c.remove(), 4800);
    }
}

function revelar(){
    etapa++;
    atualizarPontos();

    const fala = document.getElementById('fala');
    const btn = document.getElementById('btnRevelar');

    if(etapa === 1){
        fala.innerHTML = '🎤 “O terceiro lugar fez história, resistiu, lutou... mas hoje para por aqui.”';
        document.getElementById('tag-terceiro').innerHTML = '🥉 3º LUGAR';
        document.getElementById('terceiro').classList.add('revelado-terceiro');
        efeitoRevelacao('terceiro');
        btn.innerHTML = '🥈 Revelar 2º Lugar';
        return;
    }

    if(etapa === 2){
        fala.innerHTML = '🎤 “Entre o sonho e a vitória, alguém ficou muito perto. Em segundo lugar...”';
        document.getElementById('tag-segundo').innerHTML = '🥈 2º LUGAR';
        document.getElementById('segundo').classList.add('revelado-segundo');
        efeitoRevelacao('segundo');
        btn.innerHTML = '👑 Revelar Campeão';
        return;
    }

    if(etapa === 3){
        fala.innerHTML = '🎤 “O público decidiu. O grande campeão da temporada é...”';
        document.getElementById('tag-primeiro').innerHTML = '👑 CAMPEÃO';
        document.getElementById('primeiro').classList.add('revelado-campeao');
        efeitoRevelacao('primeiro');
        soltarConfete();
        btn.style.display = 'none';
        document.getElementById('btnNovo').style.display = 'inline-block';
    }
}
</script>

</body>
</html>
