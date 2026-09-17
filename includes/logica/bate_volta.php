<?php

/* =========================================================
   🚗 LÓGICA DA PROVA BATE-VOLTA
   ========================================================= */

   /* =========================
   🚗 PROVA BATE-VOLTA
   - Não acontece toda semana.
   - Não acontece a partir do Top 10.
   - Indicação do Líder não joga.
   - Se o jogador estiver no paredão e puder jogar, ele joga uma prova de sorte.
========================= */

function participantesBateVolta($paredao)
{
    $indicacaoLider = $_SESSION['indicacao_lider'] ?? '';
    $participantes = [];

    foreach ($paredao as $nome) {
        if ($nome == '') continue;

        /* O indicado do líder não participa do Bate-Volta */
        if (nomeIgual($nome, $indicacaoLider)) continue;

        $participantes[] = $nome;
    }

    return array_values(array_unique($participantes));
}

function sortearTipoBateVolta()
{
    $tipos = ['portas', 'urna', 'dado'];
    return $tipos[array_rand($tipos)];
}

function nomeTipoBateVolta($tipo)
{
    if ($tipo == 'portas') return '🚪 Portas da Sorte';
    if ($tipo == 'urna') return '🎲 Urna Misteriosa';
    if ($tipo == 'dado') return '🎯 Dado da Virada';

    return '🚗 Prova Bate-Volta';
}

function descricaoTipoBateVolta($tipo)
{
    if ($tipo == 'portas') {
        return 'Escolha uma porta. Uma delas dá uma grande vantagem na disputa.';
    }

    if ($tipo == 'urna') {
        return 'Escolha um número. Quem chegar mais perto do número secreto leva vantagem.';
    }

    if ($tipo == 'dado') {
        return 'Escolha sua aposta no dado. Se acertar a tendência do resultado, ganha vantagem.';
    }

    return 'Uma prova rápida de sorte para tentar escapar do paredão.';
}

function deveTerBateVolta($jogadores, $paredao)
{
    /* A partir do Top 10 não tem mais Bate-Volta */
    if (count($jogadores) <= 10) {
        return false;
    }

    $participantes = participantesBateVolta($paredao);

    /* Se tiver menos de 2 pessoas elegíveis, não faz sentido ter prova */
    if (count($participantes) < 2) {
        return false;
    }

    /* Chance da semana ter Bate-Volta: 45% */
    return rand(1, 100) <= 45;
}

function removerVencedorDoParedao($vencedor)
{
    if ($vencedor == '' || !isset($_SESSION['paredao']) || !is_array($_SESSION['paredao'])) {
        return;
    }

    $_SESSION['paredao'] = array_values(array_filter($_SESSION['paredao'], function ($nome) use ($vencedor) {
        return !nomeIgual($nome, $vencedor);
    }));
}

function iniciarBateVoltaAposParedao(&$jogadores)
{
    if (isset($_SESSION['bate_volta_decidido'])) {
        return false;
    }

    $_SESSION['bate_volta_decidido'] = true;

    $paredaoAtual = $_SESSION['paredao'] ?? [];

    if (!deveTerBateVolta($jogadores, $paredaoAtual)) {
        if (count($jogadores) <= 10) {
            $_SESSION['evento_extra'][] = "🚗 A partir do Top 10, não existe mais Prova Bate-Volta.";
        }
        return false;
    }

    $participantes = participantesBateVolta($paredaoAtual);

    $_SESSION['bate_volta'] = [
        'tipo' => sortearTipoBateVolta(),
        'participantes' => $participantes,
        'finalizado' => false
    ];

    $_SESSION['evento_extra'][] =
        "🚗 Teremos Prova Bate-Volta! Jogam: <b>" . implode(", ", $participantes) . "</b>.";

    $meuNome = $_SESSION['meu_nome'] ?? '';

    if ($meuNome != '' && in_array($meuNome, $participantes)) {
        $_SESSION['fase_semana'] = 'bate_volta';
        return true;
    }

    /* Se o jogador não participa, os NPCs fazem a prova automaticamente */
    $vencedor = $participantes[array_rand($participantes)];

    removerVencedorDoParedao($vencedor);

    $_SESSION['bate_volta']['finalizado'] = true;
    $_SESSION['bate_volta_resultado'] = [
        'vencedor' => $vencedor,
        'automatico' => true
    ];

    $_SESSION['evento_extra'][] =
        "🏁 " . nomeTipoBateVolta($_SESSION['bate_volta']['tipo']) . ": <b>$vencedor</b> venceu e escapou do paredão.";

    $_SESSION['evento_extra'][] =
        "🚨 Paredão final após o Bate-Volta: <b>" . implode(" x ", $_SESSION['paredao']) . "</b>.";

    return false;
}

function pontuarBateVolta($tipo, $escolha)
{
    $score = rand(1, 100);
    $detalhe = "";

    if ($tipo == 'portas') {
        $portaPremiada = (string)rand(1, 3);

        if ((string)$escolha == $portaPremiada) {
            $score += 45;
            $detalhe = "Você escolheu a porta premiada.";
        } else {
            $score += rand(0, 18);
            $detalhe = "A porta premiada era a $portaPremiada.";
        }
    } elseif ($tipo == 'urna') {
        $numeroSecreto = rand(1, 5);
        $distancia = abs(((int)$escolha) - $numeroSecreto);

        $score += max(0, 45 - ($distancia * 15));
        $detalhe = "O número secreto era $numeroSecreto.";
    } elseif ($tipo == 'dado') {
        $dado = rand(1, 6);

        $acertou =
            ($escolha == 'baixo' && $dado <= 2) ||
            ($escolha == 'medio' && $dado >= 3 && $dado <= 4) ||
            ($escolha == 'alto' && $dado >= 5);

        if ($acertou) {
            $score += 45;
            $detalhe = "O dado caiu em $dado e sua aposta deu certo.";
        } else {
            $score += rand(0, 15);
            $detalhe = "O dado caiu em $dado.";
        }
    }

    return [
        'score' => $score,
        'detalhe' => $detalhe
    ];
}

function resolverBateVoltaJogador(&$jogadores, $escolha)
{
    if (!isset($_SESSION['bate_volta']) || empty($_SESSION['bate_volta']['participantes'])) {
        return "⚠️ Não existe Prova Bate-Volta ativa.";
    }

    $bateVolta = $_SESSION['bate_volta'];
    $tipo = $bateVolta['tipo'] ?? 'portas';
    $participantes = $bateVolta['participantes'];
    $meuNome = $_SESSION['meu_nome'] ?? '';

    if (!in_array($meuNome, $participantes)) {
        return "⚠️ Você não está entre os participantes do Bate-Volta.";
    }

    if ($escolha == '') {
        return "⚠️ Escolha uma opção para jogar o Bate-Volta.";
    }

    $placar = [];

    $resultadoJogador = pontuarBateVolta($tipo, $escolha);
    $placar[$meuNome] = $resultadoJogador['score'];

    foreach ($participantes as $nome) {
        if (nomeIgual($nome, $meuNome)) continue;

        if ($tipo == 'portas') {
            $escolhaNPC = (string)rand(1, 3);
        } elseif ($tipo == 'urna') {
            $escolhaNPC = (string)rand(1, 5);
        } else {
            $opcoes = ['baixo', 'medio', 'alto'];
            $escolhaNPC = $opcoes[array_rand($opcoes)];
        }

        $resultadoNPC = pontuarBateVolta($tipo, $escolhaNPC);
        $placar[$nome] = $resultadoNPC['score'];
    }

    arsort($placar);
    $vencedor = array_key_first($placar);

    removerVencedorDoParedao($vencedor);

    $_SESSION['bate_volta']['finalizado'] = true;
    $_SESSION['bate_volta_resultado'] = [
        'vencedor' => $vencedor,
        'placar' => $placar,
        'detalhe_jogador' => $resultadoJogador['detalhe']
    ];

    $_SESSION['evento_extra'][] =
        "🏁 " . nomeTipoBateVolta($tipo) . ": <b>$vencedor</b> venceu e escapou do paredão.";

    $_SESSION['evento_extra'][] =
        "🚨 Paredão final após o Bate-Volta: <b>" . implode(" x ", $_SESSION['paredao']) . "</b>.";

    $_SESSION['fase_semana'] = 'discordia';

    if (nomeIgual($vencedor, $meuNome)) {
        alterarPopularidadePublica($jogadores, $meuNome, 4, 6, "venceu o Bate-Volta e escapou do paredão", true);
        adicionarMoedasPublico(15, "vencer o Bate-Volta");
        return "🏆 Você venceu o Bate-Volta e escapou do paredão! " . $resultadoJogador['detalhe'];
    }

    return "🚗 Você jogou o Bate-Volta, mas quem venceu foi <b>$vencedor</b>. " . $resultadoJogador['detalhe'];
}