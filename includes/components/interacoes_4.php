<?php /** Painel contextual das conversas; respostas e histórico são privados do jogador. */ ?>
<?php $i4Cena = $_SESSION['i4_pendente'] ?? null; $i4Resultado = $_SESSION['i4_resultado'] ?? null;
$i4EtapaAtual=is_array($i4Cena) && ($i4Cena['acao']??'')==='i4_romance'
    ?i4EtapaRomance($jogadores,$meuNome,(string)($i4Cena['alvo']??'')):'';
// Recupera saves com conversa romântica aberta na versão antiga.
// Se o casal já existe, não apresenta a antiga fala de início de flerte.
if($i4EtapaAtual!=='' && (!isset($i4Cena['romance_etapa']) || $i4Cena['romance_etapa']!==$i4EtapaAtual)){
    if(!isset(i4Assuntos('i4_romance',$i4EtapaAtual)[$i4Cena['assunto']??'']))
        $i4Cena['assunto']=(string)array_key_first(i4AssuntosRomance($i4EtapaAtual));
    $i4Cena['romance_etapa']=$i4EtapaAtual;
    $i4Cena['fala']=i4Frase($i4Cena,$jogadores,$meuNome);
    $_SESSION['i4_pendente']=$i4Cena;
} ?>
<?php if (is_array($i4Cena)): ?>
<section class="i4-dialogo" aria-label="Conversa com participante">
    <div class="i4-kicker">● REC • CONVERSA NA CASA • SEMANA <?= i4Semana() ?></div>
    <h2><?= i4Esc(i4Catalogo()[$i4Cena['acao']][0]??'💬') ?> <?= i4Esc($i4EtapaAtual!==''?i4TituloRomance($i4EtapaAtual):(i4Catalogo()[$i4Cena['acao']][1]??'Conversa')) ?></h2>
    <p class="i4-contexto"><?= i4Esc(($_SESSION['meu_nome']??'Você').' encontra '.$i4Cena['alvo'].' '.$i4Cena['local'].'.') ?>
        <?php if (!empty($i4Cena['outro'])): ?> <?= i4Esc($i4Cena['outro']) ?> também participa da conversa.<?php endif; ?>
    </p>
    <div class="i4-fala"><strong>💬 <?= i4Esc($i4Cena['alvo']) ?></strong><p><?= i4Esc($i4Cena['fala']) ?></p></div>
    <?php if($i4Cena['acao']==='i4_informacao' && isset(i4Conhecimentos()[$i4Cena['conhecimento']])): ?>
        <p class="i4-contexto"><strong>Informação escolhida:</strong> <?= i4Esc(i4Conhecimentos()[$i4Cena['conhecimento']]['texto']??'') ?></p>
    <?php endif; ?>
    <h3>Como você responde?</h3>
    <form method="post" class="i4-respostas">
        <input type="hidden" name="i4_token" value="<?= i4Esc($i4Cena['token']) ?>">
        <?php foreach(i4Opcoes($i4Cena['acao'],$i4EtapaAtual) as $indice=>$op): ?>
            <button type="submit" name="i4_responder" value="<?= (int)$indice ?>"><?= i4Esc($op[0]) ?></button>
        <?php endforeach; ?>
    </form>
    <small>Escolha uma resposta. Só depois dela a interação será consumida; NPCs podem discordar ou recusar pedidos.</small>
</section>
<?php elseif (is_array($i4Resultado)): ?>
<section class="i4-dialogo" role="status">
    <div class="i4-kicker">● REC • DESFECHO DA CONVERSA</div>
    <h2>✅ <?= i4Esc($i4Resultado['titulo']??'Conversa concluída') ?></h2>
    <p><strong>Sua escolha:</strong> <?= i4Esc($i4Resultado['escolha']??'') ?></p>
    <div class="i4-fala"><strong><?= i4Esc($i4Resultado['npc']??'Participante') ?></strong><p><?= i4Esc($i4Resultado['texto']??'') ?></p></div>
    <form method="post"><button type="submit" name="i4_continuar" value="1" class="btn">✅ Voltar às Interações</button></form>
</section>
<?php endif; ?>
<?php if (!empty($_SESSION['i4_historico']) && is_array($_SESSION['i4_historico'])): ?>
<details class="i4-historico">
    <summary>📓 Meu histórico de conversas (privado)</summary>
    <?php foreach(array_reverse(array_slice($_SESSION['i4_historico'],-8)) as $linha): ?>
        <div class="i4-hist-item"><strong>Semana <?= (int)($linha['semana']??1) ?> • <?= i4Esc($linha['npc']??'') ?></strong>
            <?php if(!empty($linha['outro'])): ?> e <?= i4Esc($linha['outro']) ?><?php endif; ?>
            <span><?= i4Esc($linha['escolha']??'') ?></span><p><?= i4Esc($linha['resposta']??'') ?></p>
        </div>
    <?php endforeach; ?>
</details>
<?php endif; ?>
