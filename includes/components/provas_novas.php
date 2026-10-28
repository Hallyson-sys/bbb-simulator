<?php
/** @var array $pnEstado @var string $pnClasse @var array $pnParticipantes */
$pnM=$pnEstado['mecanica'];
$pnEhDupla=$pnM==='dupla';
$pnOpcoesParceiros=$pnEhDupla?pnParceiros($pnParticipantes,$meuNome,$pnClasse==='anjo'?($lider??''):''):[];
?>
<link rel="stylesheet" href="assets/css/provas_novas.css?v=1">
<div class="pn-area" data-pn-jogo="<?= htmlspecialchars($pnM,ENT_QUOTES,'UTF-8') ?>">
    <?php if(!empty($_SESSION['pn_feedback'])): ?><p class="pn-aviso"><?= htmlspecialchars((string)$_SESSION['pn_feedback']) ?></p><?php unset($_SESSION['pn_feedback']);endif; ?>
    <?php if($pnEhDupla): ?>
        <div class="pn-bloco">
            <h3>🤝 Escolha seu parceiro</h3>
            <p>O convite pode ser recusado. A afinidade e a confiança influenciam a decisão. A dupla compete como equipe. Em caso de vitória, ambos recebem o título e a proteção, com um integrante titular para conduzir as decisões.</p>
            <div class="pn-parceiros">
                <?php foreach($pnOpcoesParceiros as $pnNome): ?>
                    <label><input form="pn-form" type="radio" name="pn_parceiro" value="<?=htmlspecialchars($pnNome,ENT_QUOTES,'UTF-8')?>" required> <?=htmlspecialchars($pnNome,ENT_QUOTES,'UTF-8')?></label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    <form method="POST" id="pn-form" class="pn-bloco" autocomplete="off">
        <input type="hidden" name="pn_jogar" value="1">
        <h3>🎮 Seu desafio</h3>
        <?php if($pnM==='codigo'||$pnM==='dupla'): ?>
            <p>Resolva cada etapa. Cada resposta correta vale 25 pontos.</p>
            <?php foreach($pnEstado['questoes'] as $i=>$q): ?>
                <label class="pn-pergunta"><strong><?=htmlspecialchars($q['texto'])?></strong><input type="number" name="pn_respostas[<?=$i?>]" required inputmode="numeric" min="-100" max="200" placeholder="Sua resposta"></label>
            <?php endforeach; ?>
        <?php elseif($pnM==='rotas'||$pnM==='risco'): ?>
            <?php foreach($pnEstado['questoes'] as $i=>$q): ?>
                <fieldset class="pn-pergunta"><legend><?=htmlspecialchars($q['texto'])?></legend>
                <?php foreach($q['opcoes'] as $v=>$op): ?><label class="pn-alternativa"><input type="radio" name="pn_respostas[<?=$i?>]" value="<?=$v?>" required> <?=htmlspecialchars($op)?></label><?php endforeach; ?>
                </fieldset>
            <?php endforeach; ?>
        <?php elseif($pnM==='precisao'): ?>
            <p>Regule os quatro marcadores. O alvo está escondido e será revelado no resultado; quanto mais perto, melhor.</p>
            <?php foreach($pnEstado['questoes'] as $i=>$q): ?>
                <label class="pn-pergunta"><strong><?=htmlspecialchars($q['texto'])?></strong><input type="range" name="pn_respostas[<?=$i?>]" min="0" max="100" value="50" class="pn-slider"><span class="pn-valor">50</span></label>
            <?php endforeach; ?>
        <?php elseif($pnM==='ordem'): ?>
            <p>Toque nas peças <strong>na ordem correta</strong>. Observe a sequência por alguns segundos antes de começar!</p>
            <div class="pn-revelacao" data-pn-revelacao><?=implode(' ',array_map('htmlspecialchars',$pnEstado['sequencia']))?></div>
            <button type="button" class="pn-secundario" data-pn-esconder>🧠 Memorizei! Esconder sequência</button>
            <div class="pn-pecas" data-pn-pecas hidden>
                <?php foreach($pnEstado['embaralhado'] as $v): ?><button type="button" data-pn-peca="<?=htmlspecialchars($v,ENT_QUOTES,'UTF-8')?>"><?=htmlspecialchars($v)?></button><?php endforeach; ?>
            </div>
            <div class="pn-escolhido" data-pn-escolhido>Peças escolhidas: —</div>
            <button type="button" class="pn-secundario" data-pn-limpar>↩ Reiniciar ordem</button>
            <input type="hidden" name="pn_ordem" data-pn-ordem value="" required>
        <?php elseif($pnM==='memoria'): ?>
            <p>Observe a sequência por 7 segundos. Depois reproduza tocando nos símbolos abaixo.</p>
            <div class="pn-revelacao" data-pn-revelacao><?=implode(' ',array_map('htmlspecialchars',$pnEstado['sequencia']))?></div>
            <button type="button" class="pn-secundario" data-pn-esconder>▶ Iniciar contagem de 7 segundos</button>
            <div class="pn-pecas" data-pn-pecas hidden>
                <?php foreach($pnEstado['opcoes'] as $v): ?><button type="button" data-pn-peca="<?=htmlspecialchars($v,ENT_QUOTES,'UTF-8')?>"><?=htmlspecialchars($v)?></button><?php endforeach; ?>
            </div>
            <div class="pn-escolhido" data-pn-escolhido>Sinais reproduzidos: 0/6</div>
            <button type="button" class="pn-secundario" data-pn-limpar>↩ Refazer resposta</button>
            <input type="hidden" name="pn_ordem" data-pn-ordem value="" required>
        <?php endif; ?>
        <button type="submit" class="pn-enviar">🏁 Concluir prova e ver o placar</button>
        <p class="pn-aviso" data-pn-erro hidden>Complete o desafio antes de confirmar.</p>
    </form>
</div>
<script src="assets/js/provas_novas.js?v=1" defer></script>
