<div class="ao-vivo-card">

    <h2>📢 Ao Vivo</h2>

    <form method="POST">
        <button class="btn novo" name="limpar_log">
            🧹 Limpar Ao Vivo
        </button>
    </form>

    <div class="log" id="aoVivoLog">

        <?php
        if (!empty($_SESSION['evento_extra'])) {
            $totalEventosAoVivo = count($_SESSION['evento_extra']);
            foreach ($_SESSION['evento_extra'] as $indiceEvento => $ev) {
                $classeUltimo = ($indiceEvento === $totalEventosAoVivo - 1) ? ' class="ao-vivo-ultimo" data-ao-vivo-ultimo="1"' : '';
                echo "<p{$classeUltimo}>$ev</p>";
                // Exibir tabela pública somente quando a produção anunciou Votação Aberta.
                // Nunca expor detalhes de votos em uma votação sigilosa.
                $publicos = $_SESSION['ds3_votos_publicos'] ?? [];
                if (is_string($ev) && str_contains($ev, '🗳️ VOTAÇÃO ABERTA —')
                    && function_exists('dsTipo') && dsTipo('votacao_aberta')
                    && (int)($publicos['rodada']??0)===(int)($_SESSION['rodada']??1)
                    && (!empty($publicos['detalhes']) || !empty($publicos['votos']))) {
                    echo '<div class="ds3-tabela-votos"><strong>🗳️ Votos declarados — Semana '.(int)$_SESSION['rodada'].'</strong><div class="ds3-tabela-overflow"><table><thead><tr><th>Quem votou</th><th>Em quem</th><th>Peso</th></tr></thead><tbody>';
                    foreach($publicos['detalhes'] as $voto) {
                        $quem=htmlspecialchars((string)($voto['votante']??''),ENT_QUOTES,'UTF-8');
                        $alvo=htmlspecialchars((string)($voto['voto']??''),ENT_QUOTES,'UTF-8');
                        $peso=max(1,(int)($voto['peso']??1));
                        echo "<tr><td>{$quem}</td><td>{$alvo}</td><td>{$peso}×</td></tr>";
                    }
                    if (empty($publicos['detalhes']) && !empty($publicos['votos'])) {
                        // Saves antigos guardavam somente a lista, não os pesos.
                        foreach($publicos['votos'] as $linhaAntiga) {
                            echo '<tr><td colspan="3">'.htmlspecialchars((string)$linhaAntiga,ENT_QUOTES,'UTF-8').'</td></tr>';
                        }
                    }
                    echo '</tbody></table></div></div>';
                }
            }
        } else {
            echo "<p>📡 Nenhum acontecimento ainda.</p>";
        }
        ?>

    </div>

</div>