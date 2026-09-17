<?php

/** @var string $fase */
/** @var array $jogadores */
/** @var string $meuNome */

?>

<form method="POST">

                    <?php if ($fase == 'lider'): ?>

                        <button class="btn" name="avancar_fase">🏆 Ir para Prova do Líder</button>

                    <?php elseif ($fase == 'vip_xepa'): ?>

                        <?php if ($_SESSION['lider'] != $meuNome): ?>

                            <button class="btn" name="avancar_fase">
                                👀 Ver VIP e Xepa do Líder
                            </button>

                        <?php endif; ?>

                    <?php elseif ($fase == 'anjo'): ?>
                        <button class="btn anjo-btn" name="avancar_fase">😇 Ir para Prova do Anjo</button>

                    <?php elseif ($fase == 'monstro'): ?>
                        <?php if ($_SESSION['anjo'] != $meuNome): ?>
                            <button class="btn" name="avancar_fase">👹 Ver Monstro do Anjo</button>
                        <?php endif; ?>

                    <?php elseif ($fase == 'bigfone'): ?>

                        <button class="btn" name="avancar_fase">☎️ Momento Big Fone</button>

                    <?php elseif ($fase == 'bate_volta'): ?>

                        <?php
                        $bateVoltaAtual = $_SESSION['bate_volta'] ?? [];
                        $tipoBateVolta = $bateVoltaAtual['tipo'] ?? 'portas';
                        $participantesBV = $bateVoltaAtual['participantes'] ?? [];
                        ?>

                        <div class="box">
                            <h3>🚗 Prova Bate-Volta</h3>
                            <p><b><?php echo nomeTipoBateVolta($tipoBateVolta); ?></b></p>
                            <p><?php echo descricaoTipoBateVolta($tipoBateVolta); ?></p>
                            <p><b>Jogam:</b> <?php echo implode(", ", $participantesBV); ?></p>
                            <p>O vencedor escapa do paredão antes da eliminação.</p>
                        </div>

                        <?php if ($tipoBateVolta == 'portas'): ?>

                            <div class="box">
                                <h3>🚪 Escolha sua porta</h3>
                                <div class="participantes-escolha">
                                    <label class="participante-btn">
                                        <input type="radio" name="escolha_bate_volta" value="1" required>
                                        🚪 Porta 1
                                    </label>

                                    <label class="participante-btn">
                                        <input type="radio" name="escolha_bate_volta" value="2" required>
                                        🚪 Porta 2
                                    </label>

                                    <label class="participante-btn">
                                        <input type="radio" name="escolha_bate_volta" value="3" required>
                                        🚪 Porta 3
                                    </label>
                                </div>
                            </div>

                        <?php elseif ($tipoBateVolta == 'urna'): ?>

                            <div class="box">
                                <h3>🎲 Escolha um número da urna</h3>
                                <div class="participantes-escolha">
                                    <?php for ($nBV = 1; $nBV <= 5; $nBV++): ?>
                                        <label class="participante-btn">
                                            <input type="radio" name="escolha_bate_volta" value="<?php echo $nBV; ?>" required>
                                            Número <?php echo $nBV; ?>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>

                        <?php else: ?>

                            <div class="box">
                                <h3>🎯 Aposte no dado</h3>
                                <div class="participantes-escolha">
                                    <label class="participante-btn">
                                        <input type="radio" name="escolha_bate_volta" value="baixo" required>
                                        Baixo: 1 ou 2
                                    </label>

                                    <label class="participante-btn">
                                        <input type="radio" name="escolha_bate_volta" value="medio" required>
                                        Médio: 3 ou 4
                                    </label>

                                    <label class="participante-btn">
                                        <input type="radio" name="escolha_bate_volta" value="alto" required>
                                        Alto: 5 ou 6
                                    </label>
                                </div>
                            </div>

                        <?php endif; ?>

                        <button class="btn" name="jogar_bate_volta">
                            🚗 Jogar Bate-Volta
                        </button>

                    <?php elseif ($fase == 'discordia'): ?>

                        <?php
                        $temaAtualDiscordia = $_SESSION['tema_discordia'] ?? 'sonso';
                        $nomesTemasDiscordia = [
                            'sonso' => 'Quem é o mais sonso?',
                            'falso' => 'Quem é o mais falso?',
                            'saboneteiro' => 'Quem é o mais saboneteiro?',
                            'aliado' => 'Quem é seu maior aliado?',
                            'podio' => 'Monte seu pódio'
                        ];
                        ?>

                        <div class="box">
                            <h3>🔥 Jogo da Discórdia</h3>
                            <p><b>Tema:</b> <?php echo $nomesTemasDiscordia[$temaAtualDiscordia] ?? $temaAtualDiscordia; ?></p>
                        </div>

                        <form method="POST">

                            <?php if ($temaAtualDiscordia != 'podio'): ?>

                                <div class="box">
                                    <h3><?php echo ($temaAtualDiscordia == 'aliado') ? '🤝 Escolha seu maior aliado' : '🎯 Escolha quem você quer apontar'; ?></h3>
                                </div>

                                <div class="participantes-escolha">
                                    <?php foreach ($jogadores as $j): ?>
                                        <?php if (!nomeIgual(($j['nome'] ?? ''), $meuNome)): ?>
                                            <label class="participante-btn">
                                                <input type="radio" name="alvo_discordia" value="<?php echo $j['nome']; ?>" required>
                                                <?php echo $j['nome']; ?>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>

                                <div class="box">
                                    <h3>🎤 Como você quer falar?</h3>
                                    <div class="participantes-escolha">
                                        <label class="participante-btn">
                                            <input type="radio" name="intensidade" value="com_tudo" required>
                                            🔥 Com tudo
                                        </label>
                                        <label class="participante-btn">
                                            <input type="radio" name="intensidade" value="leve" required>
                                            😶 De leve
                                        </label>
                                        <label class="participante-btn">
                                            <input type="radio" name="intensidade" value="saboneteiro" required>
                                            🧼 Saboneteiro
                                        </label>
                                    </div>
                                </div>

                            <?php else: ?>

                                <div class="box">
                                    <h3>🏆 Monte seu pódio</h3>
                                    <p>Você fica em 1º lugar. Escolha o 2º e 3º lugar.</p>
                                </div>

                                <div class="box">
                                    <h3>🥇 1º lugar</h3>
                                    <p>⭐ <?php echo $meuNome; ?> fica automaticamente em 1º lugar no seu pódio.</p>
                                </div>

                                <h3>🥈 2º lugar</h3>
                                <div class="participantes-escolha">
                                    <?php foreach ($jogadores as $j): ?>
                                        <?php if (!nomeIgual(($j['nome'] ?? ''), $meuNome)): ?>
                                            <label class="participante-btn">
                                                <input type="radio" name="podio_2" value="<?php echo $j['nome']; ?>" required>
                                                🥈 <?php echo $j['nome']; ?>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>

                                <h3>🥉 3º lugar</h3>
                                <div class="participantes-escolha">
                                    <?php foreach ($jogadores as $j): ?>
                                        <?php if (!nomeIgual(($j['nome'] ?? ''), $meuNome)): ?>
                                            <label class="participante-btn">
                                                <input type="radio" name="podio_3" value="<?php echo $j['nome']; ?>" required>
                                                🥉 <?php echo $j['nome']; ?>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>

                                <input type="hidden" name="intensidade" value="leve">

                            <?php endif; ?>

                            <button class="btn" name="fazer_discordia">
                                🔥 Confirmar Jogo da Discórdia
                            </button>

                        </form>

                    <?php elseif ($fase == 'finalistas'): ?>

                        <div class="box">
                            <h3>🏆 Finalistas definidos!</h3>
                            <p>Depois de uma temporada intensa, os três finalistas estão prontos para a grande final.</p>
                            <p>Respirem fundo... está chegando a hora de descobrir o campeão.</p>
                        </div>

                        <button class="btn" name="ir_final">
                            🏆 Ir para Grande Final
                        </button>

                    <?php elseif ($fase == 'eliminacao'): ?>
                        <button class="btn" name="avancar_fase">📺 Ir para Eliminação</button>
                    <?php endif; ?>

                </form>