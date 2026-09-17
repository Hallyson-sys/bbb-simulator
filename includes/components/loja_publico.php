<?php if (!empty($mostrarLojaPublico)): ?>

<div class="loja-publico-card">

    <div class="loja-publico-box">

        <div class="loja-publico-top">

            <div>
                <h3>🪙 Loja do Público</h3>

                <p>
                    Use moedas para desbloquear vantagens estratégicas
                    sem mostrar a popularidade nos cards.
                </p>
            </div>

            <strong>
                <?php echo obterMoedasPublico(); ?> moedas
            </strong>

        </div>

        <?php
        $minhaPopularidadeAtual = 50;
        $meuJogadorMoedas = obterMeuJogadorMoedas($jogadores);

        if ($meuJogadorMoedas != null) {
            $minhaPopularidadeAtual = limitar(
                $meuJogadorMoedas['popularidade'] ?? 50,
                0,
                100
            );
        }
        ?>

        <?php if (($_SESSION['popularidade_propria_liberada_rodada'] ?? 0) == $rodada): ?>

            <div class="loja-revelacao">
                🔍 Sua popularidade atual:
                <b><?php echo $minhaPopularidadeAtual; ?>/100</b>
            </div>

        <?php endif; ?>


        <?php if (
            ($_SESSION['alvo_casa_revelado_rodada'] ?? 0) == $rodada
            && !empty($_SESSION['alvo_casa_revelado_nome'])
        ): ?>

            <div class="loja-revelacao">
                🎯 Alvo provável da casa:
                <b><?php echo $_SESSION['alvo_casa_revelado_nome']; ?></b>
            </div>

        <?php endif; ?>


        <?php if (($_SESSION['radar_publico_liberado_rodada'] ?? 0) == $rodada): ?>

            <div class="radar-publico">

                <h4>📊 Radar do Público</h4>

                <?php foreach (rankingPopularidadePublica($jogadores) as $pos => $rank): ?>

                    <div class="radar-linha">

                        <span>
                            <?php echo ($pos + 1); ?>º
                            <?php echo $rank['nome']; ?>
                        </span>

                        <strong>
                            <?php echo $rank['popularidade']; ?>/100
                        </strong>

                    </div>

                    <div class="radar-barra">
                        <div
                            style="width: <?php echo $rank['popularidade']; ?>%;"
                        ></div>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <?php if (empty($_SESSION['jogador_eliminado'])): ?>

            <form method="POST" class="loja-publico-grid">

                <button
                    type="submit"
                    name="usar_loja_publico"
                    value="radar"
                >
                    📊 Radar<br>
                    <small>50 moedas</small>
                </button>

                <button
                    type="submit"
                    name="usar_loja_publico"
                    value="mutirao"
                >
                    📉 Mutirão Rival<br>
                    <small>100 moedas</small>
                </button>

                <button
                    type="submit"
                    name="usar_loja_publico"
                    value="espionar_minha_popularidade"
                >
                    🔍 Minha Popularidade<br>
                    <small>75 moedas</small>
                </button>

                <button
                    type="submit"
                    name="usar_loja_publico"
                    value="impulso_imagem"
                >
                    🛡️ Impulsionar<br>
                    <small>120 moedas</small>
                </button>

                <button
                    type="submit"
                    name="usar_loja_publico"
                    value="alvo_casa"
                >
                    🎯 Alvo da Casa<br>
                    <small>90 moedas</small>
                </button>

            </form>

        <?php endif; ?>

    </div>

</div>

<?php endif; ?>