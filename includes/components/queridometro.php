<?php

/** @var string $fase */
/** @var array $jogadores */
/** @var string $meuNome */
/** @var array $EMOJIS_QUERIDOMETRO */

?>

<?php if ($fase == 'queridometro' && !isset($_SESSION['queridometro_feito'])): ?>

    <div class="queridometro-wrapper">

        <div class="querido-header">
            <div>
                <h2>💖 Queridômetro da Casa</h2>
                <p>
                    Escolha um emoji para cada participante.
                    Isso muda principalmente sua relação com eles.
                </p>
            </div>
        </div>

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

                    ?>

                    <div class="card-querido <?php echo $classe; ?>">

                        <div class="topo-card-querido">

                            <div>

                                <h3>
                                    <?php echo $nome; ?>
                                </h3>

                                <span>
                                    <?php echo $j['personalidade'] ?? 'Participante'; ?>
                                </span>

                            </div>

                            <div class="valor-relacao">
                                <?php echo $relacao; ?>
                            </div>

                        </div>

                        <div class="emojis-grid">

                            <?php foreach ($EMOJIS_QUERIDOMETRO as $emoji => $dados): ?>

                                <label title="<?php echo $dados['nome']; ?>">

                                    <input
                                        type="radio"
                                        name="queridometro[<?php echo $nome; ?>]"
                                        value="<?php echo $emoji; ?>"
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

            <button
                type="submit"
                name="auto_queridometro"
                class="btn-confirmar-querido"
                formnovalidate
            >
                ⚡ Preencher Automaticamente pelo Relacionamento
            </button>

            <button
                type="submit"
                name="enviar_queridometro"
                class="btn-confirmar-querido"
            >
                💟 Enviar Queridômetro Manualmente
            </button>

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
                        <?php echo $nomeQ; ?>
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