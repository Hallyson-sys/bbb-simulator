<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/includes/logica/prova_lider.php';

if(!isset($_SESSION['jogadores'])){
    header("Location: index.php");
    exit;
}

$jogadores=$_SESSION['jogadores'];
$meuNome=$_SESSION['meu_nome']??'Jogador';

$dadosProva=prepararProvaLider();
$tipoProva=$dadosProva['tipo'];
$prova=$dadosProva['prova'];

require_once __DIR__ . '/includes/actions/prova_lider.php';

$sequenciaMemoria=[];
$questoesLogica=[];
$questaoDesempateLogica=null;
$desempateLogicaPendente=false;
$npcsDesempateLogica=[];
$estadoEstrategia=null;
$opcoesEstrategia=[];

if((int)$tipoProva===3){
    $sequenciaMemoria=prepararMemoriaLider();
}

if((int)$tipoProva===12){
    $questoesLogica=prepararSequenciaLogicaLider();

    $desempateLogicaPendente=
        !empty($_SESSION['lider_logica_desempate_pendente']);

    if($desempateLogicaPendente){
        $questaoDesempateLogica=
            prepararQuestaoDesempateLogicaLider();

        $npcsDesempateLogica=
            $_SESSION['lider_logica_desempate_npcs']
            ?? [];
    }
}

if((int)$tipoProva===13){
    $estadoEstrategia=prepararCaminhoEstrategicoLider();
    $opcoesEstrategia=opcoesCaminhoEstrategicoLider(
        (int)($estadoEstrategia['etapa']??1)
    );
}

function eLider($t){
    return htmlspecialchars((string)$t,ENT_QUOTES,'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Prova do Líder</title>
<link rel="stylesheet" href="assets/css/prova_lider.css">
<link rel="stylesheet" href="assets/css/provas_interativas.css?v=2">
<link rel="stylesheet" href="assets/css/provas_interativas_premium.css?v=20260917">

<style>
/* =========================================================
   🔢 PREMIUM INLINE — SEQUÊNCIA LÓGICA
   Carregado dentro da própria página para evitar cache/path.
   ========================================================= */

body .logica-area{
    margin:26px 0 !important;
    display:flex !important;
    flex-direction:column !important;
    gap:20px !important;
}

body .logica-intro.premium{
    display:flex !important;
    justify-content:space-between !important;
    align-items:center !important;
    gap:18px !important;
    padding:18px 20px !important;
    margin-bottom:0 !important;
    border-radius:22px !important;
    background:linear-gradient(135deg,rgba(255,255,255,.07),rgba(255,255,255,.025)) !important;
    border:1px solid rgba(255,255,255,.13) !important;
    box-shadow:0 12px 30px rgba(0,0,0,.20),inset 0 0 20px rgba(255,255,255,.02) !important;
    text-align:left !important;
}

body .logica-intro.premium strong{
    display:block !important;
    margin-bottom:7px !important;
    font-size:20px !important;
    color:#ffd86b !important;
}

body .logica-intro.premium span{
    display:block !important;
    max-width:500px !important;
    font-size:13px !important;
    line-height:1.6 !important;
    color:rgba(255,255,255,.78) !important;
}

body .logica-resumo-premium{
    flex:0 0 auto !important;
    min-width:88px !important;
    padding:14px !important;
    border-radius:18px !important;
    text-align:center !important;
    background:linear-gradient(135deg,rgba(255,0,153,.20),rgba(0,210,255,.12)) !important;
    border:1px solid rgba(255,255,255,.14) !important;
    box-shadow:0 0 22px rgba(255,0,153,.10) !important;
}

body .logica-resumo-premium b{
    display:block !important;
    font-size:30px !important;
    line-height:1 !important;
    color:#fff !important;
}

body .logica-resumo-premium small{
    display:block !important;
    margin-top:5px !important;
    font-size:10px !important;
    letter-spacing:1px !important;
    text-transform:uppercase !important;
    color:rgba(255,255,255,.68) !important;
}

body .form-logica{
    display:flex !important;
    flex-direction:column !important;
    gap:18px !important;
}

body .logica-card{
    position:relative !important;
    overflow:hidden !important;
    display:block !important;
    padding:22px !important;
    margin:0 !important;
    border-radius:24px !important;
    text-align:left !important;
    background:
        radial-gradient(circle at top right,rgba(0,210,255,.07),transparent 37%),
        radial-gradient(circle at bottom left,rgba(255,0,170,.08),transparent 38%),
        linear-gradient(160deg,rgba(15,16,38,.98),rgba(14,9,30,.98)) !important;
    border:1px solid rgba(255,255,255,.10) !important;
    box-shadow:0 12px 34px rgba(0,0,0,.30),0 0 24px rgba(255,0,170,.06) !important;
}

body .logica-card-topo{
    display:flex !important;
    justify-content:space-between !important;
    align-items:center !important;
    gap:10px !important;
    margin-bottom:14px !important;
}

body .logica-numero{
    display:inline-flex !important;
    width:auto !important;
    margin:0 !important;
    padding:7px 12px !important;
    border-radius:999px !important;
    font-size:10px !important;
    font-weight:900 !important;
    letter-spacing:1.1px !important;
    opacity:1 !important;
    color:#fff !important;
    background:linear-gradient(135deg,rgba(255,0,150,.24),rgba(0,185,255,.20)) !important;
    border:1px solid rgba(255,255,255,.12) !important;
}

body .logica-badge-questao{
    display:inline-flex !important;
    align-items:center !important;
    padding:7px 11px !important;
    border-radius:999px !important;
    font-size:10px !important;
    font-weight:900 !important;
    color:#ffd86b !important;
    background:rgba(255,255,255,.055) !important;
    border:1px solid rgba(255,255,255,.10) !important;
}

body .logica-barra-progresso{
    display:grid !important;
    grid-template-columns:repeat(3,1fr) !important;
    gap:7px !important;
    margin:0 0 16px !important;
}

body .logica-barra-progresso span{
    display:block !important;
    height:6px !important;
    border-radius:999px !important;
    background:rgba(255,255,255,.08) !important;
}

body .logica-barra-progresso span.ativo{
    background:linear-gradient(90deg,#ff2aaa,#00dfff,#ffd75e) !important;
    box-shadow:0 0 12px rgba(255,40,170,.18) !important;
}

body .logica-padrao-bloco{
    display:block !important;
    margin:0 0 18px !important;
    padding:18px !important;
    border-radius:19px !important;
    text-align:center !important;
    background:linear-gradient(135deg,rgba(255,255,255,.055),rgba(255,255,255,.018)) !important;
    border:1px solid rgba(255,255,255,.075) !important;
}

body .logica-label-padrao{
    margin-bottom:10px !important;
    font-size:10px !important;
    font-weight:900 !important;
    letter-spacing:1.4px !important;
    color:rgba(255,255,255,.55) !important;
}

body .logica-padrao{
    display:block !important;
    margin:0 0 9px !important;
    font-size:30px !important;
    line-height:1.45 !important;
    font-weight:900 !important;
    letter-spacing:1.1px !important;
    text-align:center !important;
    color:#fff !important;
    background:linear-gradient(90deg,#ff9b62,#ff4ccd,#68e7ff,#ffe068) !important;
    -webkit-background-clip:text !important;
    background-clip:text !important;
    -webkit-text-fill-color:transparent !important;
}

body .logica-dica{
    display:block !important;
    font-size:12px !important;
    line-height:1.5 !important;
    text-align:center !important;
    color:rgba(255,255,255,.66) !important;
}

body .logica-opcoes{
    display:grid !important;
    grid-template-columns:repeat(2,minmax(0,1fr)) !important;
    gap:12px !important;
}

body .logica-opcao{
    position:relative !important;
    display:block !important;
    margin:0 !important;
    cursor:pointer !important;
}

body .logica-opcao input{
    position:absolute !important;
    opacity:0 !important;
    width:1px !important;
    height:1px !important;
    pointer-events:none !important;
}

body .logica-opcao span{
    box-sizing:border-box !important;
    width:100% !important;
    min-height:64px !important;
    display:flex !important;
    flex-direction:column !important;
    align-items:center !important;
    justify-content:center !important;
    gap:3px !important;
    padding:12px !important;
    border-radius:16px !important;
    color:#fff !important;
    background:linear-gradient(135deg,rgba(255,255,255,.055),rgba(255,255,255,.02)) !important;
    border:1px solid rgba(255,255,255,.11) !important;
    transition:.2s ease !important;
}

body .logica-opcao span small{
    display:block !important;
    font-size:9px !important;
    font-weight:800 !important;
    letter-spacing:1px !important;
    text-transform:uppercase !important;
    color:rgba(255,255,255,.50) !important;
}

body .logica-opcao span strong{
    display:block !important;
    font-size:18px !important;
    color:#fff !important;
}

body .logica-opcao:hover span{
    transform:translateY(-2px) !important;
    border-color:rgba(255,50,180,.40) !important;
    background:linear-gradient(135deg,rgba(255,0,160,.11),rgba(0,210,255,.08)) !important;
    box-shadow:0 8px 20px rgba(0,0,0,.20),0 0 16px rgba(255,0,160,.12) !important;
}

body .logica-opcao input:checked + span{
    transform:translateY(-1px) !important;
    border-color:rgba(255,210,80,.60) !important;
    background:linear-gradient(135deg,rgba(255,170,0,.25),rgba(255,0,140,.18)) !important;
    box-shadow:0 0 18px rgba(255,170,0,.18),0 0 20px rgba(255,0,140,.13) !important;
}

body .btn-confirmar-logica{
    width:100% !important;
    min-height:58px !important;
    margin-top:2px !important;
    padding:15px 18px !important;
    border:none !important;
    border-radius:18px !important;
    color:#fff !important;
    font-size:15px !important;
    font-weight:900 !important;
    cursor:pointer !important;
    background:linear-gradient(135deg,#ff8500,#ff28ae,#6959ff) !important;
    box-shadow:0 10px 24px rgba(0,0,0,.24),0 0 20px rgba(255,40,174,.20) !important;
}

body .desempate-logica-cabecalho{
    padding:20px !important;
    margin-bottom:0 !important;
    border-radius:22px !important;
    text-align:left !important;
    background:linear-gradient(135deg,rgba(255,190,40,.13),rgba(255,70,125,.08)) !important;
    border:1px solid rgba(255,210,90,.24) !important;
    box-shadow:0 0 24px rgba(255,190,40,.08) !important;
}

body .desempate-logica-cabecalho > span{
    display:inline-block !important;
    margin-bottom:9px !important;
    padding:7px 11px !important;
    border-radius:999px !important;
    color:#ffd76a !important;
    background:rgba(255,210,90,.12) !important;
    font-size:10px !important;
    font-weight:900 !important;
    letter-spacing:1px !important;
}

body .desempate-logica-cabecalho h2{
    margin:0 0 8px !important;
    font-size:25px !important;
}

body .desempate-logica-cabecalho p{
    margin:0 0 7px !important;
    line-height:1.55 !important;
}

body .desempate-logica-cabecalho small{
    opacity:.66 !important;
}

@media(max-width:700px){
    body .logica-intro.premium{
        flex-direction:column !important;
        align-items:stretch !important;
    }

    body .logica-resumo-premium{
        width:100% !important;
    }

    body .logica-opcoes{
        grid-template-columns:1fr !important;
    }
}

@media(max-width:500px){
    body .logica-card{
        padding:17px !important;
        border-radius:18px !important;
    }

    body .logica-card-topo{
        align-items:flex-start !important;
        flex-direction:column !important;
    }

    body .logica-padrao{
        font-size:23px !important;
    }
}
</style>

</head>
<body>

<div class="box prova-interativa-box">

<div class="prova-meta">
    <span class="prova-categoria"><?= eLider($prova['categoria']??'🎮 Prova') ?></span>
    <span class="prova-dificuldade">
        Dificuldade:
        <?php
        $nivel=(int)($prova['dificuldade']??1);
        echo str_repeat('★',$nivel).str_repeat('☆',max(0,5-$nivel));
        ?>
    </span>
</div>

<h1><?= eLider($prova['titulo']??'Prova do Líder') ?></h1>
<p class="prova-descricao"><?= eLider($prova['texto']??'') ?></p>

<?php if((int)$tipoProva===2): ?>

<div class="reflexo-area">
    <div class="reflexo-status aguardando" id="reflexoStatus">PREPARE-SE</div>
    <p class="reflexo-instrucao" id="reflexoInstrucao">
        Não clique antes do sinal. O botão será liberado automaticamente.
    </p>
    <form method="POST" id="formReflexo">
        <input type="hidden" name="jogar" value="1">
        <input type="hidden" name="tempo_reacao_ms" id="tempoReacaoMs" value="">
        <button type="button" class="btn-reflexo" id="btnReflexo" disabled>⏳ AGUARDE...</button>
    </form>
</div>

<?php elseif((int)$tipoProva===3): ?>

<div class="memoria-area">
    <div class="memoria-fase" id="memoriaFase">MEMORIZE A SEQUÊNCIA</div>
    <div class="memoria-sequencia" id="memoriaSequencia">
        <?php foreach($sequenciaMemoria as $simbolo): ?>
            <span class="memoria-emoji"><?= eLider($simbolo['emoji']??'') ?></span>
        <?php endforeach; ?>
    </div>
    <div class="memoria-contador" id="memoriaContador">5</div>

    <form method="POST" id="formMemoria" class="form-memoria escondido" autocomplete="off">
        <input type="hidden" name="jogar" value="1">
        <p class="memoria-ajuda">
            Escreva o <b>nome</b> de cada emoji na ordem em que apareceu.
            Não precisa digitar o emoji.
        </p>
        <div class="memoria-campos">
            <?php foreach($sequenciaMemoria as $i=>$simbolo): ?>
                <label class="memoria-campo">
                    <span><?= $i+1 ?>º símbolo</span>
                    <input type="text" name="memoria[<?= $i ?>]" placeholder="Ex.: estrela" required spellcheck="false">
                </label>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="btn-confirmar-memoria">🧠 CONFIRMAR SEQUÊNCIA</button>
    </form>
</div>

<?php elseif((int)$tipoProva===12): ?>

<div class="logica-area">

    <?php if($desempateLogicaPendente && is_array($questaoDesempateLogica)): ?>

        <div class="desempate-logica-cabecalho">
            <span>⚡ DESEMPATE</span>
            <h2>Empate no topo!</h2>

            <p>
                Você terminou empatado com
                <b><?= eLider(implode(', ',$npcsDesempateLogica)) ?></b>.
                Uma quarta questão vai decidir a liderança.
            </p>

            <small>
                Acerte para garantir a liderança. Se errar, um dos NPCs empatados vence.
            </small>
        </div>

        <form method="POST" class="form-logica">
            <input
                type="hidden"
                name="jogar_desempate_logica"
                value="1"
            >

            <section class="logica-card desempate-card">
                <div class="logica-card-topo">
                    <div class="logica-numero">
                        ⚡ QUESTÃO EXTRA
                    </div>

                    <div class="logica-badge-questao destaque">
                        Valendo a liderança
                    </div>
                </div>

                <div class="logica-padrao-bloco destaque-bloco">
                    <div class="logica-label-padrao">SEQUÊNCIA</div>
                    <div class="logica-padrao">
                        <?= eLider($questaoDesempateLogica['texto']??'') ?>
                    </div>
                    <div class="logica-dica">
                        Observe o padrão numérico e escolha a próxima resposta correta.
                    </div>
                </div>

                <div class="logica-opcoes">
                    <?php foreach(($questaoDesempateLogica['opcoes']??[]) as $opcao): ?>
                        <label class="logica-opcao">
                            <input
                                type="radio"
                                name="resposta_desempate_logica"
                                value="<?= eLider($opcao) ?>"
                                required
                            >
                            <span>
                                <small>Resposta</small>
                                <strong><?= eLider($opcao) ?></strong>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <button
                type="submit"
                class="btn-confirmar-logica btn-desempate-logica"
            >
                ⚡ RESPONDER DESEMPATE
            </button>
        </form>

    <?php else: ?>

        <div class="logica-intro premium">
            <div>
                <strong>🔢 Sequência Lógica Premium</strong>
                <span>
                    Resolva 3 padrões. Cada acerto vale 1 ponto e, se houver empate no topo,
                    uma questão extra define o novo Líder.
                </span>
            </div>

            <div class="logica-resumo-premium">
                <b>3</b>
                <small>questões</small>
            </div>
        </div>

        <form method="POST" class="form-logica">
            <input type="hidden" name="jogar" value="1">

            <?php foreach($questoesLogica as $i=>$questao): ?>
                <section class="logica-card">
                    <div class="logica-card-topo">
                        <div class="logica-numero">QUESTÃO <?= $i+1 ?> DE <?= count($questoesLogica) ?></div>
                        <div class="logica-badge-questao">+1 ponto</div>
                    </div>

                    <div class="logica-barra-progresso">
                        <?php for($passo=1;$passo<=count($questoesLogica);$passo++): ?>
                            <span class="<?= $passo <= ($i+1) ? 'ativo' : '' ?>"></span>
                        <?php endfor; ?>
                    </div>

                    <div class="logica-padrao-bloco">
                        <div class="logica-label-padrao">PADRÃO</div>
                        <div class="logica-padrao"><?= eLider($questao['texto']??'') ?></div>
                        <div class="logica-dica">
                            Analise a ordem dos números antes de marcar sua resposta.
                        </div>
                    </div>

                    <div class="logica-opcoes">
                        <?php foreach(($questao['opcoes']??[]) as $opcao): ?>
                            <label class="logica-opcao">
                                <input
                                    type="radio"
                                    name="logica[<?= $i ?>]"
                                    value="<?= eLider($opcao) ?>"
                                    required
                                >
                                <span>
                                    <small>Opção</small>
                                    <strong><?= eLider($opcao) ?></strong>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <button type="submit" class="btn-confirmar-logica">
                🔢 CONFIRMAR RESPOSTAS
            </button>
        </form>

    <?php endif; ?>

</div>

<?php elseif((int)$tipoProva===13 && is_array($estadoEstrategia)): ?>

<?php
$etapaEstrategia=(int)($estadoEstrategia['etapa']??1);
$pontosEstrategia=(int)($estadoEstrategia['pontos']??50);
$feedbackEstrategia=(string)($estadoEstrategia['feedback']??'');
$historicoEstrategia=$estadoEstrategia['historico']??[];
?>

<div class="estrategia-area">

    <div class="estrategia-topo">
        <div>
            <span class="estrategia-label">ETAPA <?= $etapaEstrategia ?> DE 3</span>
            <div class="estrategia-progresso">
                <?php for($passo=1;$passo<=3;$passo++): ?>
                    <span class="estrategia-ponto <?= $passo <= $etapaEstrategia ? 'ativo' : '' ?>"></span>
                <?php endfor; ?>
            </div>
        </div>

        <div class="estrategia-score">
            <small>SEUS PONTOS</small>
            <strong><?= $pontosEstrategia ?></strong>
        </div>
    </div>

    <?php if($feedbackEstrategia!==''): ?>
        <div class="estrategia-feedback">
            <?= eLider($feedbackEstrategia) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="estrategia-form">
        <input type="hidden" name="jogar_estrategia" value="1">

        <div class="estrategia-opcoes">
            <?php foreach($opcoesEstrategia as $chave=>$opcao): ?>
                <button
                    type="submit"
                    name="escolha_estrategia"
                    value="<?= eLider($chave) ?>"
                    class="estrategia-card risco-<?= eLider($opcao['risco']??'baixo') ?>"
                >
                    <span class="estrategia-emoji"><?= eLider($opcao['emoji']??'🎯') ?></span>
                    <strong><?= eLider($opcao['titulo']??'Escolha') ?></strong>
                    <small><?= eLider($opcao['descricao']??'') ?></small>
                    <em>Risco: <?= eLider(strtoupper($opcao['risco']??'baixo')) ?></em>
                </button>
            <?php endforeach; ?>
        </div>
    </form>

    <?php if(!empty($historicoEstrategia)): ?>
        <div class="estrategia-historico">
            <h3>📋 Sua trajetória</h3>
            <?php foreach($historicoEstrategia as $item): ?>
                <p><?= eLider($item['texto']??'') ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php else: ?>

<form method="POST">
    <div class="grid">
        <?php $maxOpcoes=(int)($prova['max']??0); ?>
        <?php for($i=1;$i<=$maxOpcoes;$i++): ?>
            <button type="submit" name="escolha" value="<?= $i ?>">
                <?= eLider(textoBotaoProvaLider($prova['tipo']??'numero',$i)) ?>
            </button>
        <?php endfor; ?>
    </div>
    <input type="hidden" name="jogar" value="1">
</form>

<?php endif; ?>

<div class="info">
👑 O vencedor assume a liderança da rodada.<br>
🪙 Se você vencer, recebe +15 Moedas do Público.<br>
🎮 Há provas de sorte, memória, agilidade, raciocínio e estratégia.
</div>

</div>

<script src="assets/js/provas_interativas.js"></script>
</body>
</html>
