<?php
/** @var array $jogadores */
/** @var string $meuNome */

$temaAtualDiscordia = $_SESSION['tema_discordia'] ?? 'sonso';
$configDiscordia = function_exists('configuracaoTemaDiscordia')
    ? configuracaoTemaDiscordia($temaAtualDiscordia)
    : ['titulo' => $temaAtualDiscordia, 'tipo' => 'simples_negativo'];

$tipoDiscordia = $configDiscordia['tipo'] ?? 'simples_negativo';
$categoriasDiscordia = $configDiscordia['categorias'] ?? [];
$debate3 = $_SESSION['d3_pendente'] ?? null;
if (is_array($debate3) && (int)($debate3['semana'] ?? 0) === (int)($_SESSION['rodada'] ?? 1)):
    $alvo3 = htmlspecialchars((string)($debate3['alvo'] ?? ''), ENT_QUOTES, 'UTF-8');
    $token3 = htmlspecialchars((string)($debate3['token'] ?? ''), ENT_QUOTES, 'UTF-8');
    $argumentando3 = ($debate3['etapa'] ?? '') === 'argumento';
    $opcoes3 = $argumentando3 ? d3OpcoesArgumento($debate3) : d3OpcoesTreplica(!empty($debate3['positivo']));
?>
<section class="d3-palco box" aria-label="Jogo da Discórdia 3.0">
    <div class="d3-kicker">● AO VIVO • JOGO DA DISCÓRDIA • SEMANA <?= (int)$debate3['semana'] ?></div>
    <h3>🔥 Debate com <?= $alvo3 ?></h3>
    <p class="d3-descricao">Você já fez sua escolha no tema <strong><?= htmlspecialchars((string)($configDiscordia['titulo'] ?? $temaAtualDiscordia),ENT_QUOTES,'UTF-8') ?></strong>. Agora é hora de sustentar seu posicionamento.</p>
    <div class="d3-passos" aria-label="Etapas da dinâmica"><span class="d3-ok">✓ Apontamento</span><span class="<?= $argumentando3 ? 'd3-atual' : 'd3-ok' ?>">2. Argumento</span><span class="<?= !$argumentando3 ? 'd3-atual' : '' ?>">3. Tréplica</span></div>
    <?php if ($argumentando3): ?>
        <h4>🎙️ Como você quer justificar sua escolha?</h4>
        <p>Escolha o argumento que você apresentará diante da casa. A resposta do participante dependerá das relações e da personalidade dele.</p>
        <form method="POST" class="d3-formulario">
            <input type="hidden" name="d3_token" value="<?= $token3 ?>">
            <?php foreach ($opcoes3 as $id3 => $op3): ?>
                <label class="d3-opcao"><input type="radio" name="d3_argumento" value="<?= htmlspecialchars($id3,ENT_QUOTES,'UTF-8') ?>" required><span><strong><?= htmlspecialchars($op3['nome'],ENT_QUOTES,'UTF-8') ?></strong><small><?= htmlspecialchars($op3['texto'],ENT_QUOTES,'UTF-8') ?></small></span></label>
            <?php endforeach; ?>
            <button class="btn discordia-confirmar" name="d3_argumentar" value="1">🎤 Apresentar argumento</button>
        </form>
    <?php else: ?>
        <div class="d3-dialogo"><span>🎤 <?= htmlspecialchars((string)$meuNome,ENT_QUOTES,'UTF-8') ?></span><p>“<?= htmlspecialchars((string)($debate3['fala'] ?? ''),ENT_QUOTES,'UTF-8') ?>”</p></div>
        <div class="d3-dialogo d3-resposta"><span>🎙️ <?= $alvo3 ?> responde</span><p>“<?= htmlspecialchars((string)($debate3['resposta']['fala'] ?? ''),ENT_QUOTES,'UTF-8') ?>”</p></div>
        <h4>🔥 Agora é sua vez de dar a última resposta</h4>
        <p>Você pode manter a posição, reconhecer o ponto do outro participante ou encerrar sem aumentar o conflito.</p>
        <form method="POST" class="d3-formulario">
            <input type="hidden" name="d3_token" value="<?= $token3 ?>">
            <?php foreach ($opcoes3 as $id3 => $op3): ?>
                <label class="d3-opcao"><input type="radio" name="d3_replica" value="<?= htmlspecialchars($id3,ENT_QUOTES,'UTF-8') ?>" required><span><strong><?= htmlspecialchars($op3['nome'],ENT_QUOTES,'UTF-8') ?></strong><small><?= htmlspecialchars($op3['fala'],ENT_QUOTES,'UTF-8') ?></small></span></label>
            <?php endforeach; ?>
            <button class="btn discordia-confirmar" name="d3_treplicar" value="1">🔥 Encerrar debate e ver repercussões</button>
        </form>
    <?php endif; ?>
    <p class="d3-rodape">As falas são públicas. O efeito do tema, as reações dos demais NPCs e o histórico da temporada serão registrados ao encerrar.</p>
</section>
<?php return; endif; ?>
?>

<div class="box discordia-cabecalho <?php echo !empty($configDiscordia['destaque']) ? 'discordia-cabecalho-filme' : ''; ?>">
    <span class="discordia-selo">🔥 JOGO DA DISCÓRDIA</span>
    <h3><?php echo htmlspecialchars($configDiscordia['titulo'] ?? $temaAtualDiscordia); ?></h3>
    <?php if (!empty($configDiscordia['subtitulo'])): ?>
        <p><?php echo htmlspecialchars($configDiscordia['subtitulo']); ?></p>
    <?php endif; ?>
</div>

<div class="d3-etapas-aviso">🔥 <strong>Discórdia 3.0:</strong> depois de apontar seus escolhidos, você apresentará um argumento, ouvirá a resposta e escolherá a tréplica.</div>
<form method="POST" class="discordia-form">

    <?php if ($tipoDiscordia === 'categorias'): ?>

        <?php if ($temaAtualDiscordia === 'filme_bbb'): ?>
            <div class="discordia-filme-abertura">
                <div class="discordia-claquete">🎬</div>
                <div>
                    <strong>Diretor por uma noite: <?php echo htmlspecialchars($meuNome); ?></strong>
                    <p>Escolha quem ocuparia cada papel se a temporada virasse um filme.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="discordia-categorias-grid <?php echo $temaAtualDiscordia === 'filme_bbb' ? 'discordia-grid-filme' : ''; ?>">
            <?php foreach ($categoriasDiscordia as $chaveCategoria => $categoria): ?>
                <?php
                $permiteSi = !empty($categoria['permite_si']);
                $efeito = $categoria['efeito'] ?? 'negativo';
                $classeEfeito = in_array($efeito, ['muito_positivo', 'positivo', 'respeito'], true)
                    ? 'discordia-card-positivo'
                    : 'discordia-card-negativo';
                ?>

                <div class="discordia-categoria-card <?php echo $classeEfeito; ?>">
                    <div class="discordia-categoria-topo">
                        <span class="discordia-categoria-emoji"><?php echo $categoria['emoji'] ?? '🎯'; ?></span>
                        <div>
                            <h4><?php echo htmlspecialchars($categoria['nome'] ?? $chaveCategoria); ?></h4>
                            <p><?php echo htmlspecialchars($categoria['descricao'] ?? ''); ?></p>
                        </div>
                    </div>

                    <label class="discordia-select-label" for="discordia_<?php echo htmlspecialchars($chaveCategoria); ?>">
                        Escolha o participante
                    </label>

                    <select
                        class="discordia-categoria-select"
                        id="discordia_<?php echo htmlspecialchars($chaveCategoria); ?>"
                        name="categoria_<?php echo htmlspecialchars($chaveCategoria); ?>"
                        required>
                        <option value="">Selecione...</option>

                        <?php foreach ($jogadores as $j): ?>
                            <?php
                            $nomeOpcao = $j['nome'] ?? '';
                            if ($nomeOpcao === '') {
                                continue;
                            }
                            if (nomeIgual($nomeOpcao, $meuNome) && !$permiteSi) {
                                continue;
                            }
                            ?>
                            <option value="<?php echo htmlspecialchars($nomeOpcao); ?>">
                                <?php echo nomeIgual($nomeOpcao, $meuNome) ? '⭐ ' . htmlspecialchars($nomeOpcao) . ' (você)' : htmlspecialchars($nomeOpcao); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="discordia-aviso-escolhas">
            💡 Cada papel deve ficar com uma pessoa diferente.
        </div>

        <input type="hidden" name="intensidade" value="leve">

    <?php elseif ($tipoDiscordia === 'podio'): ?>

        <div class="box">
            <h3>🏆 Monte seu pódio</h3>
            <p>Você fica em 1º lugar. Escolha o 2º e 3º lugar.</p>
        </div>

        <div class="box">
            <h3>🥇 1º lugar</h3>
            <p>⭐ <?php echo htmlspecialchars($meuNome); ?> fica automaticamente em 1º lugar no seu pódio.</p>
        </div>

        <h3>🥈 2º lugar</h3>
        <div class="participantes-escolha">
            <?php foreach ($jogadores as $j): ?>
                <?php if (!nomeIgual(($j['nome'] ?? ''), $meuNome)): ?>
                    <label class="participante-btn">
                        <input type="radio" name="podio_2" value="<?php echo htmlspecialchars($j['nome']); ?>" required>
                        🥈 <?php echo htmlspecialchars($j['nome']); ?>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <h3>🥉 3º lugar</h3>
        <div class="participantes-escolha">
            <?php foreach ($jogadores as $j): ?>
                <?php if (!nomeIgual(($j['nome'] ?? ''), $meuNome)): ?>
                    <label class="participante-btn">
                        <input type="radio" name="podio_3" value="<?php echo htmlspecialchars($j['nome']); ?>" required>
                        🥉 <?php echo htmlspecialchars($j['nome']); ?>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <input type="hidden" name="intensidade" value="leve">

    <?php else: ?>

        <div class="box">
            <h3>
                <?php echo $tipoDiscordia === 'simples_positivo'
                    ? '🤝 Escolha seu maior aliado'
                    : '🎯 Escolha quem você quer apontar'; ?>
            </h3>
        </div>

        <div class="participantes-escolha">
            <?php foreach ($jogadores as $j): ?>
                <?php if (!nomeIgual(($j['nome'] ?? ''), $meuNome)): ?>
                    <label class="participante-btn">
                        <input type="radio" name="alvo_discordia" value="<?php echo htmlspecialchars($j['nome']); ?>" required>
                        <?php echo htmlspecialchars($j['nome']); ?>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($tipoDiscordia !== 'simples_positivo'): ?>
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
            <input type="hidden" name="intensidade" value="leve">
        <?php endif; ?>

    <?php endif; ?>

    <button class="btn discordia-confirmar" name="fazer_discordia">
        🎤 Continuar para o debate
    </button>
</form>

<?php if ($tipoDiscordia === 'categorias'): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selects = Array.from(document.querySelectorAll('.discordia-categoria-select'));

    function atualizarOpcoes() {
        const escolhidos = selects
            .map(select => select.value)
            .filter(Boolean);

        selects.forEach(select => {
            Array.from(select.options).forEach(option => {
                if (!option.value) return;
                option.disabled = option.value !== select.value && escolhidos.includes(option.value);
            });
        });
    }

    selects.forEach(select => select.addEventListener('change', atualizarOpcoes));
    atualizarOpcoes();
});
</script>
<?php endif; ?>
