<?php

/** @var string $fase */
/** @var array $jogadores */
/** @var string $meuNome */
/** @var array $EMOJIS_QUERIDOMETRO */

$sugestoesQueridometro =
    $_SESSION['queridometro_sugestao'] ?? [];

$previewQueridometroAtivo =
    !empty($_SESSION['queridometro_preview_ativo']);

$erroQueridometro =
    $_SESSION['queridometro_erro'] ?? '';

unset($_SESSION['queridometro_erro']);

?>

<?php if ($fase == 'queridometro' && !isset($_SESSION['queridometro_feito'])): ?>

    <div class="queridometro-wrapper">

        <div class="querido-header">
            <div>
                <h2>💖 Queridômetro da Casa</h2>
                <p>
                    Escolha um emoji para cada participante.
                    Você pode preencher automaticamente e revisar tudo antes de enviar.
                </p>
            </div>
        </div>

        <?php if ($previewQueridometroAtivo): ?>
            <div class="queridometro-preview-aviso">
                <span>✨</span>
                <div>
                    <strong>Sugestões automáticas preenchidas!</strong>
                    <small>
                        Os emojis abaixo foram escolhidos com base nas suas relações atuais.
                        Você pode trocar qualquer um antes de confirmar.
                    </small>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($erroQueridometro !== ''): ?>
            <div class="queridometro-erro">
                ⚠️ <?php echo htmlspecialchars($erroQueridometro); ?>
            </div>
        <?php endif; ?>

        <details class="legenda-box" open>

            <summary>📘 Ver significado dos emojis</summary>

            <div class="legenda-querido">

                <?php foreach ($EMOJIS_QUERIDOMETRO as $emoji => $dados): ?>

                    <div class="emoji-legenda">

                        <span><?php echo $emoji; ?></span>

                        <small>
                            <?php echo $dados['nome']; ?>
                        </small>

                    </div>

                <?php endforeach; ?>

            </div>

        </details>

        <form method="POST">

            <div class="queridometro-grid">

                <?php foreach ($jogadores as $j): ?>

                    <?php

                    $nome = $j['nome'] ?? '';

                    if ($nome == '' || $nome == $meuNome) {
                        continue;
                    }

                    $relacao = $_SESSION['relacoes_jogador'][$nome] ?? 0;

                    $classe = 'neutro';

                    if ($relacao >= 15) {
                        $classe = 'positivo';
                    }

                    if ($relacao <= -15) {
                        $classe = 'negativo';
                    }

                    $emojiSugerido =
                        $sugestoesQueridometro[$nome] ?? '';

                    ?>

                    <div class="card-querido <?php echo $classe; ?>">

                        <div class="topo-card-querido">

                            <div>

                                <h3>
                                    <?php echo htmlspecialchars($nome); ?>
                                </h3>

                                <span>
                                    <?php echo htmlspecialchars($j['personalidade'] ?? 'Participante'); ?>
                                </span>

                            </div>

                            <div class="valor-relacao">
                                <?php echo $relacao; ?>
                            </div>

                        </div>

                        <div class="emojis-grid">

                            <?php foreach ($EMOJIS_QUERIDOMETRO as $emoji => $dados): ?>

                                <label title="<?php echo htmlspecialchars($dados['nome']); ?>">

                                    <input
                                        type="radio"
                                        name="queridometro[<?php echo htmlspecialchars($nome, ENT_QUOTES); ?>]"
                                        value="<?php echo htmlspecialchars($emoji, ENT_QUOTES); ?>"
                                        <?php echo $emojiSugerido === $emoji ? 'checked' : ''; ?>
                                        required
                                    >

                                    <span class="emoji-btn">
                                        <?php echo $emoji; ?>
                                    </span>

                                </label>

                            <?php endforeach; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <div class="queridometro-acoes">

                <button
                    type="submit"
                    name="auto_queridometro_preview"
                    class="btn-confirmar-querido btn-auto-querido"
                    formnovalidate
                >
                    ⚡ Preencher Automaticamente
                    <small>Usa suas relações atuais, mas não envia ainda</small>
                </button>

                <button
                    type="submit"
                    name="enviar_queridometro"
                    class="btn-confirmar-querido btn-enviar-querido"
                >
                    💟 Enviar Queridômetro
                    <small>Confirma exatamente os emojis selecionados acima</small>
                </button>

            </div>

        </form>

    </div>

<?php endif; ?>


<?php if ($fase == 'queridometro' && isset($_SESSION['queridometro_feito'])): ?>

    <div class="queridometro-wrapper">

        <div class="querido-header">

            <div>

                <h2>📊 Resultado do Queridômetro</h2>

                <p>
                    Veja quais emojis cada participante recebeu nesta rodada.
                </p>

            </div>

        </div>

        <div class="resultado-querido-grid">

            <?php foreach ($jogadores as $j): ?>

                <?php

                $nomeQ = $j['nome'] ?? '';

                $resultadoQ =
                    $_SESSION['queridometro_resultado'][$nomeQ] ?? [];

                ?>

                <div class="resultado-querido-card">

                    <h3>
                        <?php echo htmlspecialchars($nomeQ); ?>
                    </h3>

                    <?php if (!empty($resultadoQ)): ?>

                        <div class="resultado-emojis">

                            <?php foreach ($resultadoQ as $emoji => $qtd): ?>

                                <div class="resultado-emoji-item">

                                    <span>
                                        <?php echo $emoji; ?>
                                    </span>

                                    <small>
                                        <?php
                                        echo $EMOJIS_QUERIDOMETRO[$emoji]['nome'] ?? '';
                                        ?>
                                    </small>

                                    <b>
                                        x<?php echo $qtd; ?>
                                    </b>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <p>Nenhum emoji recebido.</p>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

        <form method="POST">

            <button
                class="btn-confirmar-querido"
                name="avancar_fase"
            >
                ⏭️ Continuar para as Interações
            </button>

        </form>

    </div>

<?php endif; ?>
