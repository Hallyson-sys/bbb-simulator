<?php
/** @var array $jogadores */
/** @var string $meuNome */
/** @var string $fase */
rom3Estado();
$rom3Pendente=$_SESSION['rom3_pendente']??null;
$rom3Confirmacao=$_SESSION['rom3_confirmacao']??null;
$rom3Resultado=$_SESSION['rom3_resultado']??null;
$rom3Par=rom3Parceiro($jogadores,$meuNome);
$rom3Historico=(array)($_SESSION['rom3_estado']['historico']??[]);
if ($rom3Par==='' && !is_array($rom3Resultado)) return;
$rom3Temas=$rom3Par!==''?rom3Temas($jogadores,$meuNome,$rom3Par):[];
?>
<section class="rom3-painel" id="romance-3" aria-label="Vida a dois no BBB Simulator">
<?php if (is_array($rom3Pendente) && $rom3Par!==''):
    $rom3Dados=$rom3Temas[$rom3Pendente['tema']]??null; ?>
    <?php if ($rom3Dados): ?>
    <div class="rom3-kicker">● REC • MOMENTO A DOIS • SEMANA <?= rom3Semana() ?></div>
    <h2><?=rom3Esc($rom3Dados[0].' '.$rom3Dados[1])?></h2>
    <p><?=rom3Esc($rom3Dados[2])?></p>
    <blockquote><strong>💬 <?=rom3Esc($rom3Par)?></strong><p><?=rom3Esc($rom3Dados[3])?></p></blockquote>
    <h3>Como você quer responder?</h3>
    <div class="rom3-escolhas">
    <?php foreach($rom3Dados[4] as $indice=>$rotulo): ?>
        <form method="post"><input type="hidden" name="rom3_token" value="<?=rom3Esc($rom3Pendente['token'])?>">
            <button type="submit" name="rom3_responder" value="<?= (int)$indice ?>"><?=rom3Esc($rotulo)?></button></form>
    <?php endforeach; ?>
    </div>
    <form method="post"><input type="hidden" name="rom3_token" value="<?=rom3Esc($rom3Pendente['token'])?>">
        <button type="submit" name="rom3_cancelar" value="1">↩️ Voltar sem responder</button></form>
    <small>Você escolhe o rumo da conversa. O namoro não muda de status sem uma decisão explícita.</small>
    <?php endif; ?>
<?php elseif (is_array($rom3Confirmacao) && $rom3Par!==''): ?>
    <div class="rom3-kicker">💔 DECISÃO IMPORTANTE</div>
    <h2>Você realmente quer terminar com <?=rom3Esc($rom3Par)?>?</h2>
    <p>O término encerra o namoro oficial e altera as relações. Não significa que vocês deixem de conviver na casa.</p>
    <div class="rom3-escolhas">
        <form method="post"><input type="hidden" name="rom3_token" value="<?=rom3Esc($rom3Confirmacao['token'])?>"><button type="submit" name="rom3_confirmar" value="cancelar">❤️ Não, quero continuar namorando</button></form>
        <form method="post"><input type="hidden" name="rom3_token" value="<?=rom3Esc($rom3Confirmacao['token'])?>"><button type="submit" name="rom3_confirmar" value="confirmar">💔 Sim, confirmar o término</button></form>
    </div>
<?php elseif (is_array($rom3Resultado)): ?>
    <div class="rom3-kicker">✅ MOMENTO CONCLUÍDO</div>
    <h2>❤️ <?=rom3Esc($rom3Resultado['titulo']??'Conversa a dois')?></h2>
    <p><strong>Sua escolha:</strong> <?=rom3Esc($rom3Resultado['escolha']??'')?></p>
    <blockquote><p><?=rom3Esc($rom3Resultado['texto']??'')?></p></blockquote>
    <form method="post"><button type="submit" name="rom3_continuar" value="1">✅ Voltar às Interações</button></form>
<?php else: ?>
    <details <?=!empty($_SESSION['rom3_erro'])?'open':''?>>
        <summary>❤️ Vida a Dois — <?=rom3Esc($rom3Par)?> <span class="rom3-mini">Momentos do namoro</span></summary>
        <div class="rom3-conteudo">
            <p>Vocês já formam um casal. Escolha um momento para cultivar o relacionamento ou conversar sobre algo difícil, sem começar outro namoro do zero.</p>
            <?php if (!empty($_SESSION['rom3_estado']['usados'][rom3Semana()])): ?>
                <p class="rom3-aviso">💕 Vocês já tiveram um momento especial nesta semana. As outras conversas das Interações continuam livres.</p>
            <?php elseif (rom3Ocupado()): ?>
                <p class="rom3-aviso">Conclua o diálogo ou acontecimento em andamento antes de iniciar outro momento.</p>
            <?php else: ?>
                <div class="rom3-escolhas">
                <?php foreach($rom3Temas as $chave=>$dados): ?>
                    <form method="post"><button type="submit" name="rom3_iniciar" value="<?=rom3Esc($chave)?>"><?=rom3Esc($dados[0].' '.$dados[1])?></button></form>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <small>Momento especial: até uma cena por semana, sem descontar suas ações normais e sem limitar suas conversas com <?=rom3Esc($rom3Par)?>.</small>
        </div>
    </details>
<?php endif; ?>
<?php if (!empty($_SESSION['rom3_erro'])): ?><p class="rom3-aviso" role="alert"><?=rom3Esc($_SESSION['rom3_erro'])?></p><?php unset($_SESSION['rom3_erro']);endif; ?>
<?php if ($rom3Historico && !is_array($rom3Pendente) && !is_array($rom3Confirmacao) && !is_array($rom3Resultado)): ?>
    <details class="rom3-historico"><summary>📓 Nosso histórico de relacionamento (privado)</summary>
    <?php foreach(array_reverse(array_slice($rom3Historico,-6)) as $item): ?>
        <div><strong>Semana <?= (int)($item['semana']??1) ?> • <?=rom3Esc($item['par']??'')?></strong>
            <p><?=rom3Esc($item['escolha']??'')?></p><small><?=rom3Esc($item['resultado']??'')?></small></div>
    <?php endforeach; ?></details>
<?php endif; ?>
</section>
