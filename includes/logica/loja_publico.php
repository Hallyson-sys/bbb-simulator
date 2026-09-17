<?php

/* =========================================================
   🪙 LÓGICA DA LOJA DO PÚBLICO
   Moedas, recompensas, radar e vantagens estratégicas
   ========================================================= */

   function obterMoedasPublico()
{
    return max(0, (int)($_SESSION['moedas_publico'] ?? 0));
}


function lojaPublicoDisponivelRodada($rodadaAtual)
{
    $rodadaAtual = (int)$rodadaAtual;

    if ($rodadaAtual < 2) {
        $_SESSION['loja_publico_rodada_sorteada'] = $rodadaAtual;
        $_SESSION['loja_publico_disponivel'] = false;
        return false;
    }

    if (
        !isset($_SESSION['loja_publico_rodada_sorteada']) ||
        (int)$_SESSION['loja_publico_rodada_sorteada'] !== $rodadaAtual
    ) {
        $_SESSION['loja_publico_rodada_sorteada'] = $rodadaAtual;

        /* A loja aparece em algumas semanas, sem mudar ao atualizar a página. */
        $_SESSION['loja_publico_disponivel'] = (rand(1, 100) <= 45);
    }

    return !empty($_SESSION['loja_publico_disponivel']);
}

function adicionarMoedasPublico($qtd, $motivo = '')
{
    $qtd = (int)$qtd;

    if ($qtd <= 0) return 0;

    $_SESSION['moedas_publico'] = obterMoedasPublico() + $qtd;

    if ($motivo != '') {
        if (!isset($_SESSION['evento_extra'])) {
            $_SESSION['evento_extra'] = [];
        }

        $_SESSION['evento_extra'][] = "🪙 Você ganhou <b>+$qtd Moedas do Público</b> por $motivo.";
    }

    return $_SESSION['moedas_publico'];
}

function gastarMoedasPublico($qtd)
{
    $qtd = (int)$qtd;

    if ($qtd <= 0) return true;

    if (obterMoedasPublico() < $qtd) {
        return false;
    }

    $_SESSION['moedas_publico'] = obterMoedasPublico() - $qtd;
    return true;
}

function obterMeuJogadorMoedas($jogadores)
{
    $meuNome = $_SESSION['meu_nome'] ?? '';

    foreach ($jogadores as $j) {
        if (nomeIgual(($j['nome'] ?? ''), $meuNome)) {
            return $j;
        }
    }

    return null;
}

function rankingPopularidadePublica($jogadores)
{
    $ranking = [];

    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';

        if ($nome == '') continue;

        $ranking[] = [
            'nome' => $nome,
            'popularidade' => limitar((int)($j['popularidade'] ?? 50), 0, 100)
        ];
    }

    usort($ranking, function ($a, $b) {
        return $b['popularidade'] <=> $a['popularidade'];
    });

    return $ranking;
}

function maiorRivalDoJogadorMoedas($jogadores)
{
    $meuNome = $_SESSION['meu_nome'] ?? '';
    $relacoes = $_SESSION['relacoes_jogador'] ?? [];

    $maiorRival = '';
    $menorValor = 999;

    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';

        if ($nome == '' || nomeIgual($nome, $meuNome)) continue;

        $rel = $relacoes[$nome] ?? 0;

        if ($rel < $menorValor) {
            $menorValor = $rel;
            $maiorRival = $nome;
        }
    }

    if ($maiorRival != '') {
        return $maiorRival;
    }

    $opcoes = [];

    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';
        if ($nome != '' && !nomeIgual($nome, $meuNome)) {
            $opcoes[] = $nome;
        }
    }

    return empty($opcoes) ? '' : $opcoes[array_rand($opcoes)];
}

function descobrirAlvoDaCasaMoedas($jogadores)
{
    $meuNome = $_SESSION['meu_nome'] ?? '';
    $pontuacao = [];

    foreach ($jogadores as $votante) {
        $nomeVotante = $votante['nome'] ?? '';

        if ($nomeVotante == '') continue;

        foreach ($jogadores as $alvo) {
            $nomeAlvo = $alvo['nome'] ?? '';

            if ($nomeAlvo == '' || nomeIgual($nomeAlvo, $nomeVotante)) continue;
            if (!empty($alvo['status']['lider'])) continue;
            if (estaImune($jogadores, $nomeAlvo)) continue;

            $rel = obterRelacaoCompleta($jogadores, $nomeVotante, $nomeAlvo, $meuNome);

            $score =
                (($rel['rivalidade'] ?? 0) * 2) +
                (100 - ($rel['amizade'] ?? 0)) +
                rand(0, 15);

            if (mesmaAliancaNomes($jogadores, $nomeVotante, $nomeAlvo)) {
                $score -= 40;
            }

            $pontuacao[$nomeAlvo] = ($pontuacao[$nomeAlvo] ?? 0) + $score;
        }
    }

    if (empty($pontuacao)) return '';

    arsort($pontuacao);
    return array_key_first($pontuacao);
}

function verificarBonusMarcosMoedas($jogadores)
{
    $total = count($jogadores);

    if ($total <= 10 && empty($_SESSION['bonus_moedas_top10'])) {
        $_SESSION['bonus_moedas_top10'] = true;
        adicionarMoedasPublico(30, "chegar ao Top 10");
    }

    if ($total <= 5 && empty($_SESSION['bonus_moedas_top5'])) {
        $_SESSION['bonus_moedas_top5'] = true;
        adicionarMoedasPublico(50, "chegar ao Top 5");
    }
}

function usarItemLojaPublico(&$jogadores, $item)
{
    $meuNome = $_SESSION['meu_nome'] ?? '';

    if ($meuNome == '') {
        return "⚠️ Jogador não encontrado.";
    }

    if ($item == 'radar') {
        $custo = 50;

        if (!gastarMoedasPublico($custo)) {
            return "🪙 Moedas insuficientes. O Radar do Público custa $custo moedas.";
        }

        $_SESSION['radar_publico_liberado_rodada'] = $_SESSION['rodada'] ?? 1;
        return "📊 Radar do Público liberado! A popularidade oculta dos participantes foi revelada nesta rodada.";
    }

    if ($item == 'mutirao') {
        $custo = 100;

        if (!gastarMoedasPublico($custo)) {
            return "🪙 Moedas insuficientes. O Mutirão contra Rival custa $custo moedas.";
        }

        $rival = maiorRivalDoJogadorMoedas($jogadores);

        if ($rival == '') {
            $_SESSION['moedas_publico'] += $custo;
            return "⚠️ Não foi possível encontrar um rival válido para o mutirão.";
        }

        alterarPopularidade($jogadores, $rival, -10);
        $_SESSION['jogadores'] = $jogadores;

        return "📉 Mutirão ativado! O público começou a pegar ranço de <b>$rival</b>. Popularidade dele caiu <b>-10</b>.";
    }

    if ($item == 'espionar_minha_popularidade') {
        $custo = 75;

        if (!gastarMoedasPublico($custo)) {
            return "🪙 Moedas insuficientes. Espionar sua popularidade custa $custo moedas.";
        }

        $_SESSION['popularidade_propria_liberada_rodada'] = $_SESSION['rodada'] ?? 1;
        return "🔍 Espionagem liberada! Sua popularidade aparece nesta rodada.";
    }

    if ($item == 'impulso_imagem') {
        $custo = 120;

        if (!gastarMoedasPublico($custo)) {
            return "🪙 Moedas insuficientes. Impulsionar imagem custa $custo moedas.";
        }

        alterarPopularidade($jogadores, $meuNome, 8);
        $_SESSION['jogadores'] = $jogadores;

        return "🛡️ Impulso de Imagem ativado! Sua popularidade subiu <b>+8</b>.";
    }

    if ($item == 'alvo_casa') {
        $custo = 90;

        if (!gastarMoedasPublico($custo)) {
            return "🪙 Moedas insuficientes. Descobrir o alvo da casa custa $custo moedas.";
        }

        $alvo = descobrirAlvoDaCasaMoedas($jogadores);

        if ($alvo == '') {
            $_SESSION['moedas_publico'] += $custo;
            return "⚠️ Não foi possível calcular o alvo da casa nesta rodada.";
        }

        $_SESSION['alvo_casa_revelado_rodada'] = $_SESSION['rodada'] ?? 1;
        $_SESSION['alvo_casa_revelado_nome'] = $alvo;

        return "🎯 Informação vazada: a casa está se movimentando contra <b>$alvo</b>.";
    }

    return "⚠️ Item inválido na Loja do Público.";
}