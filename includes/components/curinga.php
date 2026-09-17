<?php

/** @var string $fase */
/** @var array $jogadores */
/** @var string $meuNome */

?>

<?php if ($fase == 'poder_curinga'): ?>
                    <?php
                    $poderAtual = obterPoderCuringaAtual();
                    $catalogoCuringa = catalogoPoderesCuringa();
                    $tipoCuringa = $poderAtual['tipo'] ?? '';
                    $donoCuringa = $poderAtual['dono'] ?? '';
                    $dadosCuringa = $catalogoCuringa[$tipoCuringa] ?? null;
                    ?>

                    <?php if ($poderAtual && $dadosCuringa): ?>
                        <div class="curinga-box">
                            <div class="curinga-topo">
                                <div class="curinga-icone"><?php echo $dadosCuringa['emoji']; ?></div>
                                <div>
                                    <h3>☎️ Poder do Big Fone</h3>
                                    <p><b><?php echo $dadosCuringa['nome']; ?></b></p>
                                </div>
                            </div>

                            <p><?php echo $dadosCuringa['descricao']; ?></p>
                            <span class="curinga-dono">Dono do poder: <?php echo $donoCuringa; ?></span>
                        </div>

                        <?php if (nomeIgual($donoCuringa, $meuNome)): ?>

                            <form method="POST">

                                <?php if (in_array($tipoCuringa, ['imunidade_extra', 'anular_voto', 'espiao'])): ?>
                                    <div class="box">
                                        <h3>🎯 Escolha o alvo do poder</h3>
                                        <div class="participantes-escolha">
                                            <?php foreach ($jogadores as $j): ?>
                                                <?php
                                                $nomeOpcao = $j['nome'] ?? '';

                                                if ($nomeOpcao == '') continue;
                                                if ($tipoCuringa != 'imunidade_extra' && nomeIgual($nomeOpcao, $meuNome)) continue;
                                                if ($tipoCuringa == 'imunidade_extra' && !empty($j['status']['lider'])) continue;
                                                ?>

                                                <label class="participante-btn">
                                                    <input type="radio" name="alvo_curinga" value="<?php echo $nomeOpcao; ?>" required>
                                                    <?php echo $nomeOpcao; ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <button class="btn" name="usar_poder_curinga">
                                    ☎️ Usar Poder do Big Fone
                                </button>
                            </form>

                        <?php else: ?>

                            <div class="box">
                                <p>Esse poder pertence a <b><?php echo $donoCuringa; ?></b>. Ao continuar, o jogo decide automaticamente como o NPC vai usar.</p>
                                <form method="POST">
                                    <button class="btn" name="pular_poder_curinga">
                                        ⏭️ Continuar
                                    </button>
                                </form>
                            </div>

                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($fase == 'contra_golpe_curinga'): ?>
                    <?php $candidatosContra = candidatosContraGolpeCuringa($jogadores, $_SESSION['paredao'] ?? []); ?>

                    <div class="curinga-box">
                        <div class="curinga-topo">
                            <div class="curinga-icone">⚡</div>
                            <div>
                                <h3>Contra-Golpe do Big Fone</h3>
                                <p>Você caiu no paredão e pode puxar alguém junto.</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="participantes-escolha">
                            <?php foreach ($candidatosContra as $nomeCandidato): ?>
                                <label class="participante-btn">
                                    <input type="radio" name="alvo_contra_golpe" value="<?php echo $nomeCandidato; ?>" required>
                                    <?php echo $nomeCandidato; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <button class="btn" name="contra_golpe_curinga">
                            ⚡ Confirmar Contra-Golpe
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($fase == 'troca_curinga'): ?>
                    <?php
                    $indicacaoLiderCuringa = $_SESSION['indicacao_lider'] ?? '';
                    $paredaoAtualCuringa = $_SESSION['paredao'] ?? [];
                    $saidasCuringa = array_values(array_filter($paredaoAtualCuringa, function ($nome) use ($indicacaoLiderCuringa) {
                        return !nomeIgual($nome, $indicacaoLiderCuringa);
                    }));
                    $entradasCuringa = candidatosTrocaCuringaEntrada($jogadores, $paredaoAtualCuringa);
                    ?>

                    <div class="curinga-box">
                        <div class="curinga-topo">
                            <div class="curinga-icone">🔁</div>
                            <div>
                                <h3>Troca de Emparedado</h3>
                                <p>Escolha quem sai do paredão e quem entra. A indicação do líder não pode ser retirada.</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="box">
                            <h3>🚪 Quem sai do paredão?</h3>
                            <div class="participantes-escolha">
                                <?php foreach ($saidasCuringa as $nomeSaida): ?>
                                    <label class="participante-btn">
                                        <input type="radio" name="sair_paredao_curinga" value="<?php echo $nomeSaida; ?>" required>
                                        <?php echo $nomeSaida; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="box">
                            <h3>🚨 Quem entra no paredão?</h3>
                            <div class="participantes-escolha">
                                <?php foreach ($entradasCuringa as $nomeEntrada): ?>
                                    <label class="participante-btn">
                                        <input type="radio" name="entrar_paredao_curinga" value="<?php echo $nomeEntrada; ?>" required>
                                        <?php echo $nomeEntrada; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button class="btn" name="trocar_emparedado_curinga">
                            🔁 Confirmar Troca
                        </button>
                    </form>
                <?php endif; ?>