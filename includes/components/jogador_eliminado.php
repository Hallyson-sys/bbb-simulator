<?php

/** @var array $jogadores */
/** @var int $rodada */

$meuFinal = $_SESSION['meu_jogador_snapshot'] ?? [];
$estatisticasFinal = $meuFinal['estatisticas'] ?? [];

$popularidadeFinal =
    $_SESSION['minha_popularidade_final']
    ?? ($meuFinal['popularidade'] ?? 50);

$colocacaoFinal =
    $_SESSION['minha_colocacao_final']
    ?? (count($jogadores) + 1);

$rodadasSobrevividas = max(
    1,
    ($rodada ?? 1) - 1
);

?>

<div class="center">

    <div class="eliminado-final-box">

        <h2>🚫 Fim de Jogo</h2>

        <p>
            Você foi eliminado da temporada em
            <b><?php echo $colocacaoFinal; ?>º lugar</b>.
            Sua trajetória chegou ao fim, mas suas estatísticas ficaram registradas.
        </p>

        <div class="popularidade-final-barra">
            <div
                class="popularidade-final-preenchimento"
                style="width: <?php echo limitar($popularidadeFinal, 0, 100); ?>%;">
            </div>
        </div>

        <p>
            📊 Popularidade final:
            <b><?php echo limitar($popularidadeFinal, 0, 100); ?>/100</b>
        </p>

        <div class="estatisticas-finais-grid">

            <div class="stat-final-card">
                <strong><?php echo $rodadasSobrevividas; ?></strong>
                <span>Rodadas sobrevividas</span>
            </div>

            <div class="stat-final-card">
                <strong><?php echo $estatisticasFinal['lider'] ?? 0; ?></strong>
                <span>Provas do Líder</span>
            </div>

            <div class="stat-final-card">
                <strong><?php echo $estatisticasFinal['anjo'] ?? 0; ?></strong>
                <span>Provas do Anjo</span>
            </div>

            <div class="stat-final-card">
                <strong><?php echo $estatisticasFinal['vip'] ?? 0; ?></strong>
                <span>Vezes no VIP</span>
            </div>

            <div class="stat-final-card">
                <strong><?php echo $estatisticasFinal['xepa'] ?? 0; ?></strong>
                <span>Vezes na Xepa</span>
            </div>

            <div class="stat-final-card">
                <strong><?php echo $estatisticasFinal['monstro'] ?? 0; ?></strong>
                <span>Monstros recebidos</span>
            </div>

            <div class="stat-final-card">
                <strong><?php echo $estatisticasFinal['imune'] ?? 0; ?></strong>
                <span>Imunidades</span>
            </div>

            <div class="stat-final-card">
                <strong><?php echo $estatisticasFinal['paredao'] ?? 0; ?></strong>
                <span>Paredões enfrentados</span>
            </div>

        </div>

        <form method="POST">
            <button class="btn novo" name="novo_jogo">
                🔄 Começar Novo Jogo
            </button>
        </form>

    </div>

</div>