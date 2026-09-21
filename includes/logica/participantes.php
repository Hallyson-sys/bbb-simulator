<?php

/* =========================================================
   👥 LÓGICA DOS PARTICIPANTES
   ========================================================= */


/* =========================================================
   🛡️ GARANTIR MEU JOGADOR NA LISTA
   ========================================================= */
function garantirMeuJogadorNaLista(&$jogadores)
{
    $meuNomeSeguro = trim($_SESSION['meu_nome'] ?? '');

    if ($meuNomeSeguro == '') {
        return;
    }

    /* =========================================================
   🚪 NÃO RECOLOCAR JOGADOR QUE ESTÁ NO QUARTO SECRETO
   ========================================================= */

$estaNoQuartoSecreto =
!empty($_SESSION['paredao_falso_ativo']) &&
!empty($_SESSION['falso_eliminado']) &&
nomeIgual(
    $_SESSION['falso_eliminado'],
    $meuNomeSeguro
);

if ($estaNoQuartoSecreto) {
return;
}

    /*
     * Depois que o jogador foi eliminado, ele NÃO pode voltar
     * para o elenco por causa do snapshot.
     *
     * Isso também é essencial para o modo espectador.
     */
    if (
        !empty($_SESSION['jogador_eliminado']) ||
        !empty($_SESSION['modo_espectador'])
    ) {
        $jogadores = array_values(
            array_filter(
                $jogadores,
                function ($j) use ($meuNomeSeguro) {
                    return !nomeIgual(
                        $j['nome'] ?? '',
                        $meuNomeSeguro
                    );
                }
            )
        );

        return;
    }

    $existe = false;

    foreach ($jogadores as $j) {
        if (nomeIgual($j['nome'] ?? '', $meuNomeSeguro)) {
            $existe = true;
            break;
        }
    }

    if (
        !$existe &&
        !empty($_SESSION['meu_jogador_snapshot']) &&
        is_array($_SESSION['meu_jogador_snapshot'])
    ) {
        array_unshift(
            $jogadores,
            $_SESSION['meu_jogador_snapshot']
        );
    }

    $jogadores = array_values($jogadores);
}


/* =========================================================
   📋 ORDENAR PARTICIPANTES PARA EXIBIÇÃO
   Jogador primeiro e demais por afinidade
   ========================================================= */
function ordenarParticipantesParaExibicao(
    &$jogadores,
    $meuNome
) {
    usort(
        $jogadores,
        function ($a, $b) use ($meuNome) {
            $nomeA = $a['nome'] ?? '';
            $nomeB = $b['nome'] ?? '';

            if (
                empty($_SESSION['modo_espectador']) &&
                nomeIgual($nomeA, $meuNome)
            ) {
                return -1;
            }

            if (
                empty($_SESSION['modo_espectador']) &&
                nomeIgual($nomeB, $meuNome)
            ) {
                return 1;
            }

            $afinidadeA =
                $_SESSION['relacoes_jogador'][$nomeA] ?? 0;

            $afinidadeB =
                $_SESSION['relacoes_jogador'][$nomeB] ?? 0;

            if ($afinidadeA != $afinidadeB) {
                return $afinidadeB <=> $afinidadeA;
            }

            return strcasecmp($nomeA, $nomeB);
        }
    );

    $jogadores = array_values($jogadores);
}
