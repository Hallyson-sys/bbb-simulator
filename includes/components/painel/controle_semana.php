<?php

/** @var string $fase */
/** @var int $rodada */
/** @var array $jogadores */
/** @var string $meuNome */
/** @var int $qtdVIP */
/** @var array $EMOJIS_QUERIDOMETRO */

?>

<?php
/* =========================================================
   🧩 FASES QUE JÁ POSSUEM COMPONENTE PRÓPRIO
   ========================================================= */
?>

<?php if ($fase == 'queridometro'): ?>
    <?php
    render('components/queridometro', [
        'fase' => $fase,
        'jogadores' => $jogadores,
        'meuNome' => $meuNome,
        'EMOJIS_QUERIDOMETRO' => $EMOJIS_QUERIDOMETRO
    ]);
    ?>
<?php endif; ?>

<?php if ($fase == 'confessionario'): ?>
    <?php
    render('components/confessionario', [
        'fase' => $fase
    ]);
    ?>
<?php endif; ?>

<?php if (in_array($fase, ['interacoes_1', 'interacoes_2', 'interacoes_3'], true)): ?>
    <?php
    render('components/interacoes', [
        'fase' => $fase,
        'rodada' => $rodada,
        'jogadores' => $jogadores,
        'meuNome' => $meuNome
    ]);
    ?>
<?php endif; ?>

<?php if ($fase == 'festa'): ?>
    <?php
    render('components/festa', [
        'fase' => $fase,
        'jogadores' => $jogadores,
        'meuNome' => $meuNome
    ]);
    ?>
<?php endif; ?>


<?php
/* =========================================================
   👑 VIP / XEPA
   ========================================================= */
?>

<?php if (
    $fase == 'vip_xepa' &&
    ($_SESSION['lider'] ?? '') == $meuNome &&
    !isset($_SESSION['vip_definido'])
): ?>

    <div class="box">
        <h3>👑 Você é o Líder! Escolha <?php echo $qtdVIP; ?> para o VIP</h3>
    </div>

    <form method="POST">
        <div class="participantes-escolha">
            <?php foreach ($jogadores as $j): ?>
                <?php if (($j['nome'] ?? '') == $meuNome) continue; ?>

                <label class="participante-btn">
                    <input
                        type="checkbox"
                        name="vip[]"
                        value="<?php echo $j['nome']; ?>"
                        onclick="limitarVIP(this)">
                    <?php echo $j['nome']; ?>
                </label>
            <?php endforeach; ?>
        </div>

        <button class="btn" name="definir_vip">
            Confirmar VIP
        </button>
    </form>

<?php endif; ?>


<?php
/* =========================================================
   👹 MONSTRO DO ANJO
   ========================================================= */
?>

<?php if (
    $fase == 'monstro' &&
    ($_SESSION['anjo'] ?? '') == $meuNome &&
    !isset($_SESSION['monstro_definido'])
): ?>

    <div class="box">
        <h3>😇 Você é o Anjo! Escolha até 2 pessoas para o Monstro 👹</h3>
    </div>

    <form method="POST">
        <div class="participantes-escolha">
            <?php foreach ($jogadores as $j): ?>
                <?php if (($j['nome'] ?? '') != $meuNome): ?>
                    <label class="participante-btn">
                        <input
                            type="checkbox"
                            name="monstro[]"
                            value="<?php echo $j['nome']; ?>"
                            onclick="limitarMonstro(this)">
                        <?php echo $j['nome']; ?>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <button class="btn" name="definir_monstro">
            Confirmar Monstro
        </button>
    </form>

<?php endif; ?>


<?php
/* =========================================================
   🛡️ IMUNIZAÇÃO DO ANJO
   ========================================================= */
?>

<?php if (
    $fase == 'imunizacao_anjo' &&
    ($_SESSION['anjo'] ?? '') == $meuNome &&
    !isset($_SESSION['imunizacao_anjo_feita'])
): ?>

    <div class="box">
        <h3>😇 Você é o Anjo! Escolha quem será imunizado</h3>
    </div>

    <form method="POST">
        <div class="participantes-escolha">
            <?php foreach ($jogadores as $j): ?>
                <?php if (($j['nome'] ?? '') != $meuNome && empty($j['status']['lider'])): ?>
                    <label class="participante-btn">
                        <input
                            type="radio"
                            name="imunizado_anjo"
                            value="<?php echo $j['nome']; ?>"
                            required>
                        <?php echo $j['nome']; ?>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <button class="btn" name="definir_imunidade_anjo">
            🛡️ Confirmar Imunidade
        </button>
    </form>

<?php endif; ?>


<?php
/* =========================================================
   🚨 PAREDÃO
   ========================================================= */
?>

<?php if (
    $fase == 'paredao' &&
    !isset($_SESSION['indicacao_lider']) &&
    ($_SESSION['lider'] ?? '') == $meuNome
): ?>

    <div class="box">
        <h3>👑 Você é o Líder! Indique alguém ao Paredão</h3>
    </div>

    <form method="POST">
        <div class="participantes-escolha">
            <?php foreach ($jogadores as $j): ?>
                <?php if (
                    ($j['nome'] ?? '') != $meuNome &&
                    !estaImune($jogadores, $j['nome'] ?? '')
                ): ?>
                    <label class="participante-btn">
                        <input
                            type="radio"
                            name="indicado_lider"
                            value="<?php echo $j['nome']; ?>"
                            required>
                        <?php echo $j['nome']; ?>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <button class="btn" name="indicar_lider">
            🚨 Confirmar Indicação
        </button>
    </form>

<?php endif; ?>

<?php if (
    $fase == 'paredao' &&
    isset($_SESSION['indicacao_lider']) &&
    isset($_SESSION['bigfone_indicacao_pendente']) &&
    ($_SESSION['bigfone_dono_poder'] ?? '') == $meuNome &&
    !isset($_SESSION['indicacao_bigfone'])
): ?>

    <div class="box">
        <h3>☎️ Poder do Big Fone! Indique alguém ao Paredão</h3>
    </div>

    <form method="POST">
        <div class="participantes-escolha">
            <?php foreach ($jogadores as $j): ?>
                <?php if (
                    ($j['nome'] ?? '') != $meuNome &&
                    ($j['nome'] ?? '') != ($_SESSION['lider'] ?? '') &&
                    ($j['nome'] ?? '') != ($_SESSION['indicacao_lider'] ?? '') &&
                    empty($j['status']['lider']) &&
                    !estaImune($jogadores, $j['nome'] ?? '')
                ): ?>
                    <label class="participante-btn">
                        <input
                            type="radio"
                            name="indicado_bigfone"
                            value="<?php echo $j['nome']; ?>"
                            required>
                        <?php echo $j['nome']; ?>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <button class="btn" name="indicar_bigfone">
            ☎️ Confirmar Indicação
        </button>
    </form>

<?php endif; ?>

<?php if (
    $fase == 'paredao' &&
    isset($_SESSION['indicacao_lider']) &&
    ($_SESSION['bigfone_anular_voto_pendente'] ?? false) &&
    nomeIgual(($_SESSION['bigfone_dono_poder'] ?? ''), $meuNome)
): ?>

    <div class="box">
        <h3>🚫 Big Fone — Anular Voto</h3>
        <p>Escolha uma pessoa para ter o voto anulado nesta votação da casa.</p>
    </div>

    <form method="POST">
        <div class="participantes-escolha">
            <?php foreach ($jogadores as $j): ?>
                <?php if (!nomeIgual(($j['nome'] ?? ''), $meuNome)): ?>
                    <label class="participante-btn">
                        <input
                            type="radio"
                            name="alvo_anular_voto_bigfone"
                            value="<?php echo $j['nome']; ?>"
                            required>
                        <?php echo $j['nome']; ?>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <button class="btn" name="usar_bigfone_anular_voto">
            🚫 Confirmar Anulação
        </button>

        <button class="btn novo" name="pular_bigfone_anular_voto">
            Não usar agora
        </button>
    </form>

<?php endif; ?>

<?php if (
    $fase == 'paredao' &&
    isset($_SESSION['indicacao_lider']) &&
    ($_SESSION['bigfone_espiar_voto_pendente'] ?? false) &&
    nomeIgual(($_SESSION['bigfone_dono_poder'] ?? ''), $meuNome)
): ?>

    <div class="box">
        <h3>👁️ Big Fone — Espiar Voto</h3>
        <p>Escolha uma pessoa. Depois da votação, você descobrirá em quem ela votou.</p>
    </div>

    <form method="POST">
        <div class="participantes-escolha">
            <?php foreach ($jogadores as $j): ?>
                <?php if (!nomeIgual(($j['nome'] ?? ''), $meuNome)): ?>
                    <label class="participante-btn">
                        <input
                            type="radio"
                            name="alvo_espiar_voto_bigfone"
                            value="<?php echo $j['nome']; ?>"
                            required>
                        <?php echo $j['nome']; ?>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <button class="btn" name="usar_bigfone_espiar_voto">
            👁️ Confirmar Espionagem
        </button>

        <button class="btn novo" name="pular_bigfone_espiar_voto">
            Não usar agora
        </button>
    </form>

<?php endif; ?>

<?php if (
    $fase == 'paredao' &&
    isset($_SESSION['indicacao_lider']) &&
    !isset($_SESSION['bigfone_indicacao_pendente']) &&
    !($_SESSION['bigfone_anular_voto_pendente'] ?? false) &&
    !($_SESSION['bigfone_espiar_voto_pendente'] ?? false) &&
    ($_SESSION['lider'] ?? '') != $meuNome &&
    !isset($_SESSION['meu_voto_paredao'])
): ?>

    <div class="box">
        <h3>🗳️ Votação da Casa</h3>
        <p>Escolha em quem você quer votar para o paredão.</p>
    </div>

    <form method="POST">
        <div class="participantes-escolha">
            <?php foreach ($jogadores as $j): ?>
                <?php if (
                    ($j['nome'] ?? '') != $meuNome &&
                    empty($j['status']['lider']) &&
                    !estaImune($jogadores, $j['nome'] ?? '') &&
                    ($j['nome'] ?? '') != ($_SESSION['indicacao_lider'] ?? '') &&
                    ($j['nome'] ?? '') != ($_SESSION['indicacao_bigfone'] ?? '')
                ): ?>
                    <label class="participante-btn">
                        <input
                            type="radio"
                            name="voto_paredao"
                            value="<?php echo $j['nome']; ?>"
                            required>
                        <?php echo $j['nome']; ?>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <button class="btn" name="votar_paredao">
            🗳️ Confirmar Voto
        </button>
    </form>

<?php endif; ?>


<?php
/* =========================================================
   🎁 PODER CURINGA
   ========================================================= */
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
                    <h3>🎁 Poder Curinga</h3>
                    <p><b><?php echo $dadosCuringa['nome']; ?></b></p>
                </div>
            </div>

            <p><?php echo $dadosCuringa['descricao']; ?></p>
            <span class="curinga-dono">Dono do poder: <?php echo $donoCuringa; ?></span>
        </div>

        <?php if (nomeIgual($donoCuringa, $meuNome)): ?>
            <form method="POST">
                <?php if (in_array($tipoCuringa, ['imunidade_extra', 'anular_voto', 'espiao'], true)): ?>
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
                                    <input
                                        type="radio"
                                        name="alvo_curinga"
                                        value="<?php echo $nomeOpcao; ?>"
                                        required>
                                    <?php echo $nomeOpcao; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <button class="btn" name="usar_poder_curinga">
                    🎁 Usar Poder Curinga
                </button>
            </form>
        <?php else: ?>
            <div class="box">
                <p>
                    Esse poder pertence a <b><?php echo $donoCuringa; ?></b>.
                    Ao continuar, o jogo decide automaticamente como o NPC vai usar.
                </p>

                <form method="POST">
                    <button class="btn" name="pular_poder_curinga">
                        ⏭️ Continuar
                    </button>
                </form>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="box">
            <h3>🎁 Poder Curinga</h3>
            <p>Não há um poder válido carregado nesta rodada.</p>
        </div>

        <form method="POST">
            <button class="btn" name="pular_poder_curinga">
                ⏭️ Continuar Semana
            </button>
        </form>
    <?php endif; ?>
<?php endif; ?>


<?php if ($fase == 'contra_golpe_curinga'): ?>
    <?php
    $origemPoderPosParedao = $_SESSION['poder_pos_paredao_origem'] ?? 'curinga';
    $rotuloOrigemPoder = $origemPoderPosParedao === 'bigfone' ? 'Big Fone' : 'Poder Curinga';
    $candidatosContra = candidatosContraGolpeCuringa(
        $jogadores,
        $_SESSION['paredao'] ?? []
    );
    ?>

    <div class="curinga-box">
        <div class="curinga-topo">
            <div class="curinga-icone">⚡</div>
            <div>
                <h3>Contra-Golpe — <?php echo $rotuloOrigemPoder; ?></h3>
                <p>Você caiu no paredão e pode puxar alguém junto.</p>
            </div>
        </div>
    </div>

    <?php if (!empty($candidatosContra)): ?>
        <form method="POST">
            <div class="participantes-escolha">
                <?php foreach ($candidatosContra as $nomeCandidato): ?>
                    <label class="participante-btn">
                        <input
                            type="radio"
                            name="alvo_contra_golpe"
                            value="<?php echo $nomeCandidato; ?>"
                            required>
                        <?php echo $nomeCandidato; ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <button class="btn" name="contra_golpe_curinga">
                ⚡ Confirmar Contra-Golpe
            </button>
        </form>
    <?php else: ?>
        <div class="box">
            <p>Não existe nenhum participante válido para o Contra-Golpe.</p>
        </div>

        <form method="POST">
            <button class="btn" name="avancar_fase">
                ⏭️ Continuar
            </button>
        </form>
    <?php endif; ?>
<?php endif; ?>


<?php if ($fase == 'troca_curinga'): ?>
    <?php
    $origemPoderPosParedao = $_SESSION['poder_pos_paredao_origem'] ?? 'curinga';
    $rotuloOrigemPoder = $origemPoderPosParedao === 'bigfone' ? 'Big Fone' : 'Poder Curinga';
    $indicacaoLiderCuringa = $_SESSION['indicacao_lider'] ?? '';
    $paredaoAtualCuringa = $_SESSION['paredao'] ?? [];

    $saidasCuringa = array_values(
        array_filter(
            $paredaoAtualCuringa,
            function ($nome) use ($indicacaoLiderCuringa) {
                return !nomeIgual($nome, $indicacaoLiderCuringa);
            }
        )
    );

    $entradasCuringa = candidatosTrocaCuringaEntrada(
        $jogadores,
        $paredaoAtualCuringa
    );
    ?>

    <div class="curinga-box">
        <div class="curinga-topo">
            <div class="curinga-icone">🔁</div>
            <div>
                <h3>Troca de Emparedado — <?php echo $rotuloOrigemPoder; ?></h3>
                <p>Escolha quem sai do paredão e quem entra. A indicação do líder não pode ser retirada.</p>
            </div>
        </div>
    </div>

    <?php if (!empty($saidasCuringa) && !empty($entradasCuringa)): ?>
        <form method="POST">
            <div class="box">
                <h3>🚪 Quem sai do paredão?</h3>

                <div class="participantes-escolha">
                    <?php foreach ($saidasCuringa as $nomeSaida): ?>
                        <label class="participante-btn">
                            <input
                                type="radio"
                                name="sair_paredao_curinga"
                                value="<?php echo $nomeSaida; ?>"
                                required>
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
                            <input
                                type="radio"
                                name="entrar_paredao_curinga"
                                value="<?php echo $nomeEntrada; ?>"
                                required>
                            <?php echo $nomeEntrada; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button class="btn" name="trocar_emparedado_curinga">
                🔁 Confirmar Troca
            </button>
        </form>
    <?php else: ?>
        <div class="box">
            <p>Não existe uma troca válida disponível neste momento.</p>
        </div>

        <form method="POST">
            <button class="btn" name="avancar_fase">
                ⏭️ Continuar
            </button>
        </form>
    <?php endif; ?>
<?php endif; ?>


<?php
/* =========================================================
   🚗 PROVA BATE-VOLTA
   ========================================================= */
?>

<?php if ($fase == 'bate_volta'): ?>
    <?php
    $bateVoltaAtual = $_SESSION['bate_volta'] ?? [];
    $tipoBateVolta = $bateVoltaAtual['tipo'] ?? 'portas';
    $participantesBV = $bateVoltaAtual['participantes'] ?? [];
    ?>

    <div class="box">
        <h3>🚗 Prova Bate-Volta</h3>
        <p><b><?php echo nomeTipoBateVolta($tipoBateVolta); ?></b></p>
        <p><?php echo descricaoTipoBateVolta($tipoBateVolta); ?></p>
        <p><b>Jogam:</b> <?php echo implode(', ', $participantesBV); ?></p>
        <p>O vencedor escapa do paredão antes da eliminação.</p>
    </div>

    <form method="POST">
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
                            <input
                                type="radio"
                                name="escolha_bate_volta"
                                value="<?php echo $nBV; ?>"
                                required>
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
    </form>
<?php endif; ?>


<?php
/* =========================================================
   🔥 JOGO DA DISCÓRDIA
   ========================================================= */
?>

<?php if ($fase == 'discordia'): ?>
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
        <p>
            <b>Tema:</b>
            <?php echo $nomesTemasDiscordia[$temaAtualDiscordia] ?? $temaAtualDiscordia; ?>
        </p>
    </div>

    <form method="POST">
        <?php if ($temaAtualDiscordia != 'podio'): ?>
            <div class="box">
                <h3>
                    <?php echo ($temaAtualDiscordia == 'aliado')
                        ? '🤝 Escolha seu maior aliado'
                        : '🎯 Escolha quem você quer apontar'; ?>
                </h3>
            </div>

            <div class="participantes-escolha">
                <?php foreach ($jogadores as $j): ?>
                    <?php if (!nomeIgual(($j['nome'] ?? ''), $meuNome)): ?>
                        <label class="participante-btn">
                            <input
                                type="radio"
                                name="alvo_discordia"
                                value="<?php echo $j['nome']; ?>"
                                required>
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
                            <input
                                type="radio"
                                name="podio_2"
                                value="<?php echo $j['nome']; ?>"
                                required>
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
                            <input
                                type="radio"
                                name="podio_3"
                                value="<?php echo $j['nome']; ?>"
                                required>
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
<?php endif; ?>


<?php if ($fase == 'casa_vidro'): ?>
    <div class="box">
        <h3>🏠 Casa de Vidro</h3>
        <p>Quatro candidatos disputam duas vagas para entrar oficialmente na temporada.</p>
    </div>

    <form method="GET" action="casa_vidro.php">
        <button class="btn" type="submit">
            🏠 Ir para Casa de Vidro
        </button>
    </form>
<?php endif; ?>


<?php
/* =========================================================
   ▶️ BOTÕES DE NAVEGAÇÃO DAS FASES
   ========================================================= */
?>

<?php if ($fase == 'lider'): ?>
    <div class="box">
        <h3>🏆 Prova do Líder</h3>
        <p>É hora de disputar a liderança da semana.</p>
    </div>

    <form method="POST">
        <button class="btn" name="avancar_fase">
            🏆 Ir para Prova do Líder
        </button>
    </form>
<?php endif; ?>


<?php if (
    $fase == 'vip_xepa_revelar' &&
    ($_SESSION['lider'] ?? '') != $meuNome &&
    !isset($_SESSION['vip_definido'])
): ?>
    <div class="box">
        <h3>👑 VIP e Xepa</h3>
        <p>O Líder tomou sua decisão. Clique para descobrir quem foi para o VIP e quem ficou na Xepa.</p>
    </div>

    <form method="POST">
        <button class="btn" name="ver_vip_xepa">
            👀 Ver VIP e Xepa do Líder
        </button>
    </form>
<?php endif; ?>


<?php if (
    $fase == 'vip_xepa' &&
    ($_SESSION['lider'] ?? '') != $meuNome &&
    !isset($_SESSION['vip_definido'])
): ?>
    <!-- Compatibilidade com saves antigos: o fluxo novo converte esta fase antes de renderizar. -->
    <div class="box">
        <h3>👑 VIP e Xepa</h3>
        <p>O Líder tomou sua decisão. Clique para descobrir quem foi para o VIP e quem ficou na Xepa.</p>
    </div>

    <form method="POST">
        <button class="btn" name="ver_vip_xepa">
            👀 Ver VIP e Xepa do Líder
        </button>
    </form>
<?php endif; ?>


<?php if ($fase == 'anjo'): ?>
    <div class="box">
        <h3>😇 Prova do Anjo</h3>
        <p>É hora de descobrir quem conquista o colar do Anjo nesta semana.</p>
    </div>

    <form method="POST">
        <button class="btn anjo-btn" name="avancar_fase">
            😇 Ir para Prova do Anjo
        </button>
    </form>
<?php endif; ?>


<?php if (
    $fase == 'monstro' &&
    ($_SESSION['anjo'] ?? '') != $meuNome
): ?>
    <div class="box">
        <h3>👹 Castigo do Monstro</h3>
        <p>Veja quem recebeu o Castigo do Monstro escolhido pelo Anjo.</p>
    </div>

    <form method="POST">
        <button class="btn" name="avancar_fase">
            👹 Ver Monstro do Anjo
        </button>
    </form>
<?php endif; ?>


<?php if ($fase == 'bigfone'): ?>
    <div class="box">
        <h3>☎️ Momento Big Fone</h3>
        <p>O telefone pode tocar e mudar completamente os rumos da semana.</p>
    </div>

    <form method="POST">
        <button class="btn" name="avancar_fase">
            ☎️ Ir para o Big Fone
        </button>
    </form>
<?php endif; ?>


<?php if ($fase == 'finalistas'): ?>
    <div class="box">
        <h3>🏆 Finalistas definidos!</h3>
        <p>Depois de uma temporada intensa, os três finalistas estão prontos para a grande final.</p>
        <p>Respirem fundo... está chegando a hora de descobrir o campeão.</p>
    </div>

    <form method="POST">
        <button class="btn" name="ir_final">
            🏆 Ir para Grande Final
        </button>
    </form>
<?php endif; ?>


<?php if ($fase == 'eliminacao'): ?>
    <div class="box">
        <h3>📺 Noite de Eliminação</h3>
        <p>O paredão está definido. É hora de descobrir quem deixa a casa.</p>
    </div>

    <form method="POST">
        <button class="btn" name="avancar_fase">
            📺 Ir para Eliminação
        </button>
    </form>
<?php endif; ?>


<?php
/* =========================================================
   🧯 SEGURANÇA CONTRA FASE SEM TELA
   ========================================================= */

$fasesConhecidas = [
    'queridometro',
    'confessionario',
    'interacoes_1',
    'interacoes_2',
    'interacoes_3',
    'lider',
    'vip_xepa',
    'vip_xepa_revelar',
    'anjo',
    'monstro',
    'bigfone',
    'casa_vidro',
    'poder_curinga',
    'festa',
    'imunizacao_anjo',
    'paredao',
    'contra_golpe_curinga',
    'troca_curinga',
    'bate_volta',
    'discordia',
    'finalistas',
    'eliminacao',
    'jogador_eliminado'
];
?>

<?php if (!in_array($fase, $fasesConhecidas, true)): ?>
    <div class="box">
        <h3>⚠️ Fase sem interface</h3>
        <p>
            A fase <b><?php echo htmlspecialchars((string)$fase, ENT_QUOTES, 'UTF-8'); ?></b>
            ainda não possui uma tela configurada no Controle da Semana.
        </p>
    </div>
<?php endif; ?>


<?php if ($fase != 'jogador_eliminado'): ?>
    <button type="button" class="btn novo" onclick="abrirPopup()">
        🔄 Novo Jogo
    </button>
<?php endif; ?>