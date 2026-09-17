<?php

/** @var string $fase */
/** @var array $jogadores */
/** @var string $meuNome */

?>

<?php if ($fase == 'festa'): ?>

<div class="box">
    <h3>🎉 Festa da Semana</h3>
    <p>Você possui <?php echo $_SESSION['acoes_festa']; ?> ações.</p>
</div>

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
                <input type="hidden" name="selecionar_acao_festa" value="dancar">
                <button class="btn">💃 Dançar</button>
            </form>

            <form method="POST">
                <input type="hidden" name="acao_festa" value="beber">
                <button class="btn">🍹 Exagerar na bebida</button>
            </form>

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

<?php endif; ?>