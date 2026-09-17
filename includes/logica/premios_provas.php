<?php

/* =========================================================
   🪙 PRÊMIOS EM MOEDAS POR PROVAS
   ========================================================= */

require_once __DIR__ . '/loja_publico.php';


/* =========================================================
   🏆 PREMIAR VITÓRIA EM PROVA
   - Só premia o jogador principal.
   - Cada prova só paga uma vez por rodada.
   ========================================================= */
function premiarMoedasPorProva(
    $tipoProva,
    $vencedor,
    $meuNome,
    $quantidade = 15
) {
    $tipoProva = trim((string)$tipoProva);
    $vencedor = trim((string)$vencedor);
    $meuNome = trim((string)$meuNome);
    $quantidade = max(0, (int)$quantidade);

    if (
        $tipoProva === '' ||
        $vencedor === '' ||
        $meuNome === '' ||
        $quantidade <= 0
    ) {
        return false;
    }

    /* Compatibilidade mesmo se nomeIgual não estiver carregada. */
    if (function_exists('nomeIgual')) {
        $souEu = nomeIgual($vencedor, $meuNome);
    } else {
        $souEu =
            mb_strtolower($vencedor, 'UTF-8') ===
            mb_strtolower($meuNome, 'UTF-8');
    }

    if (!$souEu) {
        return false;
    }

    $nomesProvas = [
        'lider' => 'Prova do Líder',
        'anjo'  => 'Prova do Anjo'
    ];

    if (!isset($nomesProvas[$tipoProva])) {
        return false;
    }

    $rodada = (int)($_SESSION['rodada'] ?? 1);

    /*
     * Esta chave impede ganhar moedas novamente
     * ao atualizar a página ou reenviar o formulário.
     */
    $chaveBonus =
        'bonus_moedas_' .
        $tipoProva .
        '_rodada_' .
        $rodada;

    if (!empty($_SESSION[$chaveBonus])) {
        return false;
    }

    $_SESSION[$chaveBonus] = true;

    adicionarMoedasPublico(
        $quantidade,
        'vencer a ' . $nomesProvas[$tipoProva]
    );

    return true;
}
