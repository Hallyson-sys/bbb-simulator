<?php

/** @var string $fase */
/** @var array $jogadores */
/** @var string $meuNome */

?>

<?php if ($fase == 'festa'): ?>

<?php render('components/eventos_convivencia'); ?>
<?php $ecBloqueia = !empty($_SESSION['evento_convivencia_ativo']) || !empty($_SESSION['evento_convivencia_resultado']); ?>
<?php if (!$ecBloqueia): ?>

<div class="box">
    <h3>🎉 Festa da Semana</h3>
    <p>Você possui <?php echo $_SESSION['acoes_festa']; ?> ações.</p>
</div>

<?php if(!empty($_SESSION['es2_festa_feedback'])): ?>
<div class="es2-feedback" role="status"><h3>🎬 Resultado da sua ação</h3><p><?= es2Esc($_SESSION['es2_festa_feedback']) ?></p></div>
<?php unset($_SESSION['es2_festa_feedback']); endif; ?>

<?php if ($_SESSION['acoes_festa'] > 0): ?>

    <?php $acaoFestaSelecionada = $_SESSION['acao_festa_selecionada'] ?? null; ?>

    <?php if (!$acaoFestaSelecionada): ?>

        <div class="interacoes-grid">

            <form method="POST">
                <input type="hidden" name="selecionar_acao_festa" value="aproximar">
                <button class="btn">🤝 Se aproximar</button>
            </form>

            <form method="POST">
                <input type="hidden" name="acao_festa" value="vt">
                <button class="btn">📺 Fazer VT</button>
            </form>

            <form method="POST">
                <input type="hidden" name="selecionar_acao_festa" value="romance">
                <button class="btn">💘 Romance</button>
            </form>

            <form method="POST">
                <input type="hidden" name="selecionar_acao_festa" value="provocar">
                <button class="btn">😈 Provocar</button>
            </form>

            <form method="POST">
                <input type="hidden" name="selecionar_acao_festa" value="sondar">
                <button class="btn">🕵️ Sondar intenção de voto</button>
            </form>

            <form method="POST">
                <input type="hidden" name="selecionar_acao_festa" value="negociar">
                <button class="btn">🗳️ Articular votos (até 3 convidados)</button>
            </form>

            <form method="POST">
                <input type="hidden" name="selecionar_acao_festa" value="dancar">
                <button class="btn">💃 Dançar</button>
            </form>

            <form method="POST">
                <input type="hidden" name="acao_festa" value="beber">
                <button class="btn">🍹 Exagerar na bebida</button>
            </form>

        </div>

    <?php elseif ($acaoFestaSelecionada === 'sondar'): ?>
        <div class="es2-pane"><h3>🕵️ Sondar intenção de voto</h3><p>A pessoa pode revelar uma intenção, desconversar ou até esconder seu jogo. Nenhuma resposta garante o voto definitivo.</p>
            <form method="POST">
                <input type="hidden" name="es2_festa_acao" value="sondar">
                <input type="hidden" name="es2_token" value="<?= es2Esc($_SESSION['es2_csrf']??'') ?>">
                <label>Conversar com
                    <select name="es2_npc" required><option value="">Selecione...</option><?php foreach($jogadores as $j): $n=$j['nome']??''; if(!es2Participante($jogadores,$n,$meuNome))continue; ?><option value="<?=es2Esc($n)?>"><?=es2Esc($n)?></option><?php endforeach; ?></select>
                </label>
                <label>Abordagem
                    <select name="es2_abordagem"><option value="discreta">Perguntar discretamente</option><option value="direta">Perguntar diretamente</option><option value="troca">Oferecer troca de informações</option></select>
                </label>
                <button class="btn" type="submit">🔎 Conversar sobre votos (1 ação)</button>
            </form>
        </div>
        <form method="POST"><button class="btn novo" name="cancelar_acao_festa">⬅️ Voltar</button></form>

    <?php elseif ($acaoFestaSelecionada === 'negociar'): ?>
        <div class="es2-pane"><h3>🗳️ Chamar para conversar sobre votos</h3><p>Escolha de 1 a 3 pessoas. Cada convite pode ser aceito ou recusado. Depois você sugere um alvo; ninguém é obrigado a concordar.</p>
            <form method="POST">
                <input type="hidden" name="es2_festa_acao" value="negociar_inicio"><input type="hidden" name="es2_token" value="<?=es2Esc($_SESSION['es2_csrf']??'')?>">
                <div class="es2-options">
                    <?php foreach($jogadores as $j): $n=$j['nome']??''; if(!es2Participante($jogadores,$n,$meuNome))continue; ?>
                    <label class="es2-check"><input type="checkbox" name="es2_convidados[]" value="<?=es2Esc($n)?>" onchange="es2Limitar(this)"> <?=es2Esc($n)?></label>
                    <?php endforeach; ?>
                </div>
                <button class="btn" type="submit">📩 Convidar para conversar</button>
            </form>
        </div>
        <form method="POST"><button class="btn novo" name="cancelar_acao_festa">⬅️ Voltar</button></form>

    <?php elseif ($acaoFestaSelecionada === 'negociar_alvo' && !empty($_SESSION['es2_negociacao'])): ?>
        <?php $neg=$_SESSION['es2_negociacao']; ?>
        <div class="es2-pane"><h3>🤝 Quem aceitou negociar?</h3>
            <?php if(!empty($neg['aceitos'])): ?><p>✅ Aceitaram conversar: <strong><?=es2Esc(implode(', ',$neg['aceitos']))?></strong></p><?php else: ?><p>🚫 Ninguém quis participar da conversa desta vez.</p><?php endif; ?>
            <?php if(!empty($neg['recusados'])): ?><p>🚫 Recusaram: <?=es2Esc(implode(', ',$neg['recusados']))?></p><?php endif; ?>
            <p>Agora escolha o nome que pretende propor. Cada participante aceitará ou sugerirá outra pessoa com base em sua própria estratégia.</p>
            <form method="POST">
                <input type="hidden" name="es2_festa_acao" value="negociar_fechar"><input type="hidden" name="es2_token" value="<?=es2Esc($_SESSION['es2_csrf']??'')?>">
                <label>Alvo proposto
                    <select name="es2_alvo" required><option value="">Escolha...</option>
                    <?php foreach($jogadores as $j): $n=$j['nome']??''; if(!es2Participante($jogadores,$n,$meuNome)||in_array($n,$neg['aceitos'],true))continue; ?><option value="<?=es2Esc($n)?>"><?=es2Esc($n)?></option><?php endforeach; ?>
                    </select>
                </label>
                <button class="btn" type="submit">🎯 Propor alvo e concluir (1 ação)</button>
            </form>
            <form method="POST"><input type="hidden" name="es2_festa_acao" value="negociar_cancelar"><input type="hidden" name="es2_token" value="<?=es2Esc($_SESSION['es2_csrf']??'')?>"><button type="submit" class="btn novo">Cancelar negociação</button></form>
        </div>

    <?php else: ?>

        <form method="POST" id="formFesta">

            <input type="hidden" name="acao_festa" id="acao_festa_valor" value="<?php echo ($acaoFestaSelecionada == 'romance') ? '' : $acaoFestaSelecionada; ?>">
            <input type="hidden" name="alvo_festa" id="alvo_festa">

            <div class="box">
                <h3>
                    <?php
                    if ($acaoFestaSelecionada == "romance") {
                        echo "💘 Escolha uma pessoa e depois escolha uma ação romântica";
                    } elseif ($acaoFestaSelecionada == "flertar") {
                        echo "😘 Com quem você quer flertar?";
                    } elseif ($acaoFestaSelecionada == "provocar") {
                        echo "😈 Quem você quer provocar?";
                    } elseif ($acaoFestaSelecionada == "dancar") {
                        echo "💃 Com quem você quer dançar?";
                    } else {
                        echo "🤝 De quem você quer se aproximar?";
                    }
                    ?>
                </h3>
            </div>

            <div class="participantes-escolha grupo-festa">
                <?php foreach ($jogadores as $j): ?>
                    <?php if ($j['nome'] != $meuNome): ?>

                        <?php $romanceBotao = obterRomance($jogadores, $meuNome, $j['nome'] ?? ''); ?>
                        <button type="button" class="participante-btn" data-romance="<?php echo $romanceBotao; ?>" onclick="selecionarAlvoFesta(this, '<?php echo $j['nome']; ?>')">
                            <?php echo $j['nome']; ?>
                            <?php if ($acaoFestaSelecionada == "romance"): ?>
                                <br><small>💕 Romance: <?php echo $romanceBotao; ?></small>
                            <?php endif; ?>
                        </button>

                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <?php if ($acaoFestaSelecionada == "romance"): ?>
                <div class="box" id="opcoesRomance" style="display:none;">
                    <h3>💘 Escolha a ação romântica</h3>
                    <p id="textoRomanceLiberado">Selecione uma pessoa para ver as opções liberadas pelo nível de romance.</p>
                    <div class="interacoes-grid">
                        <button type="button" class="btn" onclick="selecionarAcaoRomance('flertar')">😘 Flertar</button>
                        <button type="button" class="btn" onclick="selecionarAcaoRomance('elogiar')">💕 Elogiar</button>
                        <button type="button" class="btn romance-30" onclick="selecionarAcaoRomance('sentimentos')" style="display:none;">💬 Falar de sentimentos</button>
                        <button type="button" class="btn romance-30" onclick="selecionarAcaoRomance('noite_conversando')" style="display:none;">🌙 Conversar até amanhecer</button>
                        <button type="button" class="btn romance-60" onclick="selecionarAcaoRomance('pedir_namoro')" style="display:none;">💍 Pedir em namoro</button>
                        <button type="button" class="btn romance-60" onclick="selecionarAcaoRomance('passar_noite_quarto')" style="display:none;">🛏️ Passar a noite juntos no quarto</button>
                    </div>
                </div>
            <?php endif; ?>

            <button type="button" class="btn" onclick="executarAcaoFesta()">
                Executar Ação
            </button>

        </form>

        <form method="POST">
            <button class="btn novo" name="cancelar_acao_festa">⬅️ Voltar</button>
        </form>

    <?php endif; ?>

<?php endif; ?>

<?php endif; // !ecBloqueia ?>

<?php endif; ?>