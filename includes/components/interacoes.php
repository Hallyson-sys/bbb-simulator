<?php

/** @var string $fase */
/** @var int $rodada */
/** @var array $jogadores */
/** @var string $meuNome */

?>

<?php if (strpos($fase, 'interacoes') !== false): ?>

<?php render('components/eventos_convivencia'); ?>
<?php $ecBloqueia = !empty($_SESSION['evento_convivencia_ativo']) || !empty($_SESSION['evento_convivencia_resultado']); ?>
<?php if (!$ecBloqueia): ?>

    <?php if (!empty($_SESSION['es2_resultado_interacao'])): ?>
        <div class="box" role="status" style="border:1px solid rgba(0,210,165,.6);">
            <h3>📩 Resultado dos convites</h3>
            <p><?php echo strip_tags((string)$_SESSION['es2_resultado_interacao']); ?></p>
        </div>
        <?php unset($_SESSION['es2_resultado_interacao']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['es2_erro_interacao'])): ?>
        <div class="box" role="alert" style="border:1px solid rgba(255,180,60,.75);">
            <strong>⚠️ <?php echo htmlspecialchars((string)$_SESSION['es2_erro_interacao'], ENT_QUOTES, 'UTF-8'); ?></strong>
        </div>
        <?php unset($_SESSION['es2_erro_interacao']); ?>
    <?php endif; ?>
    <div class="box">
        <h3>💬 Interações — <?php echo $_SESSION['acoes_restantes']; ?> restantes</h3>
        <p>Você pode fazer suas ações ou continuar a semana quando quiser.</p>
    </div>

                    <?php if ($_SESSION['acoes_restantes'] > 0): ?>

                        <?php
                        $acaoSelecionada = $_SESSION['acao_selecionada'] ?? null;
                        ?>

                        <?php if (!$acaoSelecionada && ($rx3Intencao = rx3IntencaoDisponivel($jogadores, $meuNome, $fase))): ?>
                            <div class="rx3-action">
                                <h4>🎙️ Colocar seu posicionamento em prática</h4>
                                <p>No Confessionário, você decidiu: <strong><?php echo rx3Esc($rx3Intencao['rotulo']); ?></strong>.<br>
                                Você pode conversar com <?php echo rx3Esc($rx3Intencao['alvo']); ?> agora. Isso consome uma interação e <strong>pode mudar a relação</strong> entre vocês.</p>
                                <form method="POST">
                                    <input type="hidden" name="acao" value="rx3_aplicar">
                                    <input type="hidden" name="rx3_token" value="<?php echo rx3Esc($rx3Intencao['token']); ?>">
                                    <button type="submit">💬 Conversar com <?php echo rx3Esc($rx3Intencao['alvo']); ?></button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <?php if (!$acaoSelecionada): ?>

                            <!-- ETAPA 1: ESCOLHER AÇÃO -->

                            <div class="interacoes-grid">

                                <?php
                                $acoes = [
                                    ["conversar", "💬 Conversar"],
                                    ["fofoca", "🗣️ Fazer Fofoca"],
                                    ["intriga", "🔥 Criar Intriga"]
                                ];

                                $minhaAliancaAtual = obterAliancaJogador($jogadores, $meuNome);
                                $aliancasExistentesInteracao = gerarResumoAliancas($jogadores);

                                if (($rodada ?? 1) >= 2) {
                                    if (empty($minhaAliancaAtual)) {
                                        $acoes[] = ["alianca", "🤝 Criar Aliança"];

                                        if (!empty($aliancasExistentesInteracao)) {
                                            $acoes[] = ["entrar_alianca", "🚪 Entrar em Aliança"];
                                        }
                                    } else {
                                        if (tamanhoAlianca($jogadores,$minhaAliancaAtual)<5) $acoes[]=["recrutar_alianca","📩 Convidar para minha aliança"];
                                        $acoes[] = ["sair_alianca", "💥 Sair da Aliança"];
                                    }
                                }

                                $acoes[] = ["aproximar_lider", "👑 Aproximar do Líder"];
                                $acoes[] = ["discutir", "😡 Discutir"];
                                $acoes[] = ["vt", "🎬 Fazer VT"];
                                ?>

                                <?php foreach ($acoes as $a): ?>
                                    <form method="POST">
                                        <input type="hidden" name="selecionar_acao" value="<?php echo $a[0]; ?>">
                                        <button class="btn"><?php echo $a[1]; ?></button>
                                    </form>
                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <!-- ETAPA 2: ESCOLHER PARTICIPANTE -->

                            <form method="POST" id="formAcao">

                                <input type="hidden" name="acao" value="<?php echo $acaoSelecionada; ?>">
                                <input type="hidden" name="alvo" id="alvo">
                                <input type="hidden" name="alvo2" id="alvo2">
                                <input type="hidden" name="alianca_escolhida" id="alianca_escolhida">

                                <?php if (!in_array($acaoSelecionada, ["aproximar_lider", "sair_alianca", "entrar_alianca", "alianca", "recrutar_alianca", "vt"])): ?>

                                    <div class="box">
                                        <h3>
                                            <?php echo ($acaoSelecionada == "intriga") ? "Escolha o primeiro participante" : "Escolha o participante"; ?>
                                        </h3>
                                    </div>

                                    <div class="participantes-escolha grupo-alvo">

                                        <?php foreach ($jogadores as $j): ?>
                                            <?php if ($j['nome'] != $meuNome): ?>

                                                <button type="button" class="participante-btn" onclick="selecionarAlvo(this, '<?php echo $j['nome']; ?>')">
                                                    <?php echo $j['nome']; ?>
                                                </button>

                                            <?php endif; ?>
                                        <?php endforeach; ?>

                                    </div>

                                <?php endif; ?>

                                <?php if ($acaoSelecionada == "alianca"): ?>

                                    <div class="box">
                                        <h3>🤝 Criar uma nova aliança</h3>
                                        <p>Escolha um nome e convide até 5 participantes. Cada um pode aceitar ou recusar dependendo da afinidade, confiança e rivalidade com você.</p>
                                    </div>

                                    <div class="box">
                                        <h3>🏷️ Nome da Aliança</h3>
                                        <input
                                            type="text"
                                            name="nome_alianca"
                                            id="nome_alianca"
                                            maxlength="35"
                                            placeholder="Ex: Bonde dos Imunes"
                                            style="width:100%;padding:14px;border:none;border-radius:16px;margin-top:10px;font-size:15px;color:white;background:rgba(0,0,0,.35);outline:none;">
                                        <small style="display:block;margin-top:8px;color:rgba(255,255,255,.68);">Se deixar vazio, o jogo gera um nome automaticamente.</small>
                                    </div>

                                    <div class="box">
                                        <h3>📩 Convidar participantes</h3>
                                        <p>Escolha de 1 até 5 pessoas. Participantes que já estão em outra aliança não entram no convite.</p>
                                    </div>

                                    <div class="participantes-escolha grupo-convidados-alianca">
                                        <?php foreach ($jogadores as $j): ?>
                                            <?php if (!nomeIgual(($j['nome'] ?? ''), $meuNome)): ?>
                                                <?php
                                                $nomeConvite = $j['nome'] ?? '';
                                                $aliancaAtualConvite = $j['alianca'] ?? null;
                                                $relConvite = $_SESSION['relacoes_jogador'][$nomeConvite] ?? 0;
                                                $desabilitadoConvite = !empty($aliancaAtualConvite);
                                                ?>

                                                <label class="participante-btn <?php echo $desabilitadoConvite ? 'desabilitado-alianca' : ''; ?>" style="<?php echo $desabilitadoConvite ? 'opacity:.45;cursor:not-allowed;' : ''; ?>">
                                                    <input
                                                        type="checkbox"
                                                        name="convidados_alianca[]"
                                                        value="<?php echo htmlspecialchars($nomeConvite, ENT_QUOTES, 'UTF-8'); ?>"
                                                        onclick="limitarConvidadosAlianca(this)"
                                                        <?php echo $desabilitadoConvite ? 'disabled' : ''; ?>>
                                                    🤝 <?php echo $nomeConvite; ?>
                                                    <br><small>
                                                        ❤️ Afinidade: <?php echo $relConvite; ?>
                                                        <?php if ($desabilitadoConvite): ?> • Já está em <?php echo $aliancaAtualConvite; ?><?php endif; ?>
                                                    </small>
                                                </label>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>

                                <?php endif; ?>

                                <?php if ($acaoSelecionada === "recrutar_alianca"): ?>
                                    <div class="box"><h3>📩 Reforçar minha aliança</h3><p>Convide participantes sem aliança. Eles podem aceitar ou recusar; conflitos com os membros atuais também influenciam a decisão. Máximo de cinco integrantes no grupo.</p></div>
                                    <div class="participantes-escolha grupo-convidados-alianca">
                                    <?php foreach($jogadores as $j): $n=(string)($j['nome']??''); if($n===$meuNome || !empty($j['alianca']) || !empty($j['eliminado']))continue; ?>
                                        <label class="participante-btn"><input type="checkbox" name="convidados_alianca[]" value="<?php echo es2Esc($n); ?>" onclick="limitarConvidadosAlianca(this)"> 📩 <?php echo es2Esc($n); ?></label>
                                    <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($acaoSelecionada == "entrar_alianca"): ?>

                                    <div class="box">
                                        <h3>🚪 Escolha uma aliança para tentar entrar</h3>
                                        <p>O grupo pode aceitar ou recusar dependendo da confiança que tem em você.</p>
                                    </div>

                                    <div class="participantes-escolha grupo-alianca">
                                        <?php foreach (gerarResumoAliancas($jogadores) as $nomeAliancaOpcao => $membrosAliancaOpcao): ?>
                                            <button type="button" class="participante-btn" onclick="selecionarAlianca(this, '<?php echo htmlspecialchars($nomeAliancaOpcao, ENT_QUOTES, 'UTF-8'); ?>')">
                                                🤝 <?php echo $nomeAliancaOpcao; ?>
                                                <br><small><?php echo implode(', ', $membrosAliancaOpcao); ?></small>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>

                                <?php endif; ?>

                                <?php if ($acaoSelecionada == "sair_alianca"): ?>

                                    <div class="box">
                                        <h3>💥 Sair da Aliança</h3>
                                        <p>Você está prestes a romper com sua aliança atual. Isso pode afetar confiança, afinidade e votos futuros.</p>
                                    </div>

                                <?php endif; ?>

                                <?php if ($acaoSelecionada == "vt"): ?>

                                    <div class="box">
                                        <h3>🎬 Fazer VT</h3>
                                        <p>Você vai tentar criar um momento marcante para o público. Pode virar VT emocionante, engraçado, protagonista, forçado ou até dividir opiniões.</p>
                                    </div>

                                <?php endif; ?>

                                <?php if ($acaoSelecionada == "intriga"): ?>

                                    <div class="box">
                                        <h3>Escolha o segundo participante</h3>
                                    </div>

                                    <div class="participantes-escolha grupo-alvo2">

                                        <?php foreach ($jogadores as $j): ?>
                                            <?php if ($j['nome'] != $meuNome): ?>

                                                <button type="button" class="participante-btn" onclick="selecionarAlvo2(this, '<?php echo $j['nome']; ?>')">
                                                    <?php echo $j['nome']; ?>
                                                </button>

                                            <?php endif; ?>
                                        <?php endforeach; ?>

                                    </div>

                                <?php endif; ?>

                                <button type="button" class="btn" onclick="executarAcao()">
                                    Executar Ação
                                </button>

                            </form>

                            <form method="POST">
                                <button class="btn novo" name="cancelar_acao">⬅️ Voltar</button>
                            </form>

                        <?php endif; ?>

                    <?php else: ?>

                        <div class="box">
                            <h3>✅ Suas ações acabaram!</h3>
                            <p>Os outros participantes também movimentaram o jogo. Veja os acontecimentos no Ao Vivo.</p>
                        </div>

                    <?php endif; ?>

                    <form method="POST">
                        <button class="btn" name="avancar_fase">
                            <?php echo ($_SESSION['acoes_restantes'] > 0) ? '⏭️ Pular Interações / Continuar Semana' : '⏭️ Continuar Semana'; ?>
                        </button>
                    </form>

                <?php endif; // !ecBloqueia ?>

<?php endif; ?>