<?php

/* =========================================================
   👤 PERFIL COMPLETO DOS PARTICIPANTES
   - Não expõe popularidade nem números internos de relação.
   - Reaproveita estatísticas, histórico, alianças e romances.
   ========================================================= */

function buscarParticipantePerfil($jogadores, $nome)
{
    $nome = trim((string)$nome);

    if ($nome === '') {
        return null;
    }

    foreach ((array)$jogadores as $j) {
        if (nomeIgual($j['nome'] ?? '', $nome)) {
            return $j;
        }
    }

    $eliminados = $_SESSION['participantes_eliminados_dados'] ?? [];

    if (is_array($eliminados)) {
        foreach ($eliminados as $j) {
            if (is_array($j) && nomeIgual($j['nome'] ?? '', $nome)) {
                return $j;
            }
        }
    }

    $meuSnapshot = $_SESSION['meu_jogador_snapshot'] ?? null;

    if (
        is_array($meuSnapshot) &&
        nomeIgual($meuSnapshot['nome'] ?? '', $nome)
    ) {
        return $meuSnapshot;
    }

    return null;
}

function listarParticipantesDisponiveisPerfil($jogadores)
{
    $lista = [];

    $adicionar = function ($j) use (&$lista) {
        if (!is_array($j)) {
            return;
        }

        $nome = trim((string)($j['nome'] ?? ''));

        if ($nome === '') {
            return;
        }

        foreach ($lista as $existente) {
            if (nomeIgual($existente['nome'] ?? '', $nome)) {
                return;
            }
        }

        $lista[] = $j;
    };

    foreach ((array)$jogadores as $j) {
        $adicionar($j);
    }

    foreach ((array)($_SESSION['participantes_eliminados_dados'] ?? []) as $j) {
        $adicionar($j);
    }

    if (!empty($_SESSION['meu_jogador_snapshot'])) {
        $adicionar($_SESSION['meu_jogador_snapshot']);
    }

    usort($lista, function ($a, $b) {
        return strcasecmp((string)($a['nome'] ?? ''), (string)($b['nome'] ?? ''));
    });

    return $lista;
}

function participanteEstaEliminadoPerfil($nome)
{
    foreach ((array)($_SESSION['historico_eliminados'] ?? []) as $eliminado) {
        if (nomeIgual($eliminado, $nome)) {
            return true;
        }
    }

    return false;
}

function eventosDoParticipantePerfil($nome)
{
    $eventos = [];

    foreach ((array)($_SESSION['historico_temporada'] ?? []) as $evento) {
        foreach ((array)($evento['participantes'] ?? []) as $participante) {
            if (nomeIgual($participante, $nome)) {
                $eventos[] = $evento;
                break;
            }
        }
    }

    usort($eventos, function ($a, $b) {
        $rodadaA = (int)($a['rodada'] ?? 0);
        $rodadaB = (int)($b['rodada'] ?? 0);

        if ($rodadaA !== $rodadaB) {
            return $rodadaA <=> $rodadaB;
        }

        return (int)($a['ordem'] ?? 0) <=> (int)($b['ordem'] ?? 0);
    });

    return $eventos;
}

function statusAtualParticipantePerfil($participante)
{
    $status = [];
    $mapa = [
        'lider' => ['👑', 'Líder'],
        'anjo' => ['😇', 'Anjo'],
        'imune' => ['🛡️', 'Imune'],
        'vip' => ['🟡', 'VIP'],
        'xepa' => ['🍞', 'Xepa'],
        'monstro' => ['👹', 'Monstro']
    ];

    foreach ($mapa as $chave => $dados) {
        if (!empty($participante['status'][$chave])) {
            $status[] = [
                'icone' => $dados[0],
                'texto' => $dados[1],
                'classe' => $chave
            ];
        }
    }

    return $status;
}

function estatisticasParticipantePerfil($participante)
{
    $e = is_array($participante['estatisticas'] ?? null)
        ? $participante['estatisticas']
        : [];

    return [
        ['icone' => '👑', 'rotulo' => 'Líder', 'valor' => (int)($e['lider'] ?? 0)],
        ['icone' => '😇', 'rotulo' => 'Anjo', 'valor' => (int)($e['anjo'] ?? 0)],
        ['icone' => '🟡', 'rotulo' => 'VIP', 'valor' => (int)($e['vip'] ?? 0)],
        ['icone' => '🍞', 'rotulo' => 'Xepa', 'valor' => (int)($e['xepa'] ?? 0)],
        ['icone' => '👹', 'rotulo' => 'Monstro', 'valor' => (int)($e['monstro'] ?? 0)],
        ['icone' => '🛡️', 'rotulo' => 'Imunidades', 'valor' => (int)($e['imune'] ?? 0)],
        ['icone' => '🔥', 'rotulo' => 'Paredões', 'valor' => (int)($e['paredao'] ?? 0)]
    ];
}

function meuParticipanteParaPerfil($jogadores, $meuNome)
{
    return buscarParticipantePerfil($jogadores, $meuNome);
}

function relacaoPercebidaComJogadorPerfil($jogadores, $participante, $meuNome)
{
    $nome = trim((string)($participante['nome'] ?? ''));

    if ($nome === '' || $meuNome === '') {
        return [
            'icone' => '😶',
            'texto' => 'Clima indefinido',
            'classe' => 'neutra',
            'descricao' => 'Ainda não há sinais claros sobre essa relação.'
        ];
    }

    if (nomeIgual($nome, $meuNome)) {
        return [
            'icone' => '⭐',
            'texto' => 'Você',
            'classe' => 'voce',
            'descricao' => 'Este é o seu participante na temporada.'
        ];
    }

    if (function_exists('estaNamorandoCom') && estaNamorandoCom($nome, $meuNome)) {
        return [
            'icone' => '💕',
            'texto' => 'Namorando',
            'classe' => 'romance',
            'descricao' => 'Existe um romance oficial entre vocês.'
        ];
    }

    $romance = 0;
    if (function_exists('obterRomance')) {
        $romance = max(
            (int)obterRomance($jogadores, $nome, $meuNome),
            (int)obterRomance($jogadores, $meuNome, $nome)
        );
    }

    if ($romance >= 45) {
        return [
            'icone' => '💘',
            'texto' => 'Interesse romântico',
            'classe' => 'romance',
            'descricao' => 'Há sinais perceptíveis de aproximação entre vocês.'
        ];
    }

    $meuParticipante = meuParticipanteParaPerfil($jogadores, $meuNome);
    $aliancaDoOutro = trim((string)($participante['alianca'] ?? ''));
    $minhaAlianca = trim((string)($meuParticipante['alianca'] ?? ''));

    if (
        $aliancaDoOutro !== '' &&
        $minhaAlianca !== '' &&
        strcasecmp($aliancaDoOutro, $minhaAlianca) === 0
    ) {
        return [
            'icone' => '🤝',
            'texto' => 'Aliado',
            'classe' => 'aliado',
            'descricao' => 'Vocês fazem parte da mesma aliança atualmente.'
        ];
    }

    $score = (int)($_SESSION['relacoes_jogador'][$nome] ?? 0);
    $relCompleta = function_exists('obterRelacaoCompleta')
        ? obterRelacaoCompleta($jogadores, $nome, $meuNome, $meuNome)
        : ['rivalidade' => 0, 'confianca' => 0];

    $rivalidade = (int)($relCompleta['rivalidade'] ?? 0);
    $confianca = (int)($relCompleta['confianca'] ?? 0);

    if ($score >= 35) {
        return [
            'icone' => '💚',
            'texto' => 'Boa relação',
            'classe' => 'positiva',
            'descricao' => 'A convivência entre vocês parece bastante positiva.'
        ];
    }

    if ($score >= 14) {
        return [
            'icone' => '🙂',
            'texto' => 'Aproximação',
            'classe' => 'positiva',
            'descricao' => 'Vocês vêm construindo uma relação positiva.'
        ];
    }

    if ($score <= -22 || $rivalidade >= 35) {
        return [
            'icone' => '🔥',
            'texto' => 'Rivalidade',
            'classe' => 'negativa',
            'descricao' => 'O clima entre vocês já demonstra rivalidade.'
        ];
    }

    if ($score <= -8) {
        return [
            'icone' => '💔',
            'texto' => 'Relação desgastada',
            'classe' => 'negativa',
            'descricao' => 'A convivência entre vocês não está das melhores.'
        ];
    }

    if ($rivalidade >= 15 || $confianca < -5) {
        return [
            'icone' => '👀',
            'texto' => 'Desconfiança',
            'classe' => 'alerta',
            'descricao' => 'Existem sinais de desconfiança entre vocês.'
        ];
    }

    return [
        'icone' => '😶',
        'texto' => 'Clima indefinido',
        'classe' => 'neutra',
        'descricao' => 'A relação ainda não tomou um rumo muito claro.'
    ];
}

function romanceOficialParticipantePerfil($nome)
{
    if (function_exists('parceiroAtual')) {
        $parceiro = trim((string)parceiroAtual($nome));
        if ($parceiro !== '') {
            return $parceiro;
        }
    }

    return '';
}
