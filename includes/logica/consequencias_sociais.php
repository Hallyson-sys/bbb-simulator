<?php

/* =========================================================
   🧠 NPCs 2.0 — FASE 3
   CONSEQUÊNCIAS SOCIAIS CENTRALIZADAS

   Objetivo:
   - decisões públicas passam a afetar relações;
   - jogador ↔ NPC e NPC ↔ NPC usam a mesma regra;
   - evita aplicar o mesmo efeito duas vezes em F5/redirect;
   - integra afinidade visível + relações internas + memória NPC.
   ========================================================= */


/* =========================================================
   📋 CONFIGURAÇÕES DOS EVENTOS
   ========================================================= */
function configuracoesConsequenciasSociais()
{
    return [
        'vip' => [
            'amizade' => 5,
            'rivalidade' => -2,
            'confianca' => 5,
            'visivel_min' => 4,
            'visivel_max' => 7,
            'direcao' => 'alvo_para_autor',
            'memoria' => 'me_colocou_vip',
            'forca_memoria' => 1
        ],

        'imunidade' => [
            'amizade' => 12,
            'rivalidade' => -6,
            'confianca' => 12,
            'visivel_min' => 10,
            'visivel_max' => 15,
            'direcao' => 'alvo_para_autor',
            'memoria' => 'me_imunizou',
            'forca_memoria' => 2
        ],

        'monstro' => [
            'amizade' => -5,
            'rivalidade' => 8,
            'confianca' => -5,
            'visivel_min' => -12,
            'visivel_max' => -7,
            'direcao' => 'alvo_para_autor',
            'memoria' => 'me_colocou_monstro',
            'forca_memoria' => 2
        ],

        'indicacao_paredao' => [
            'amizade' => -12,
            'rivalidade' => 15,
            'confianca' => -12,
            'visivel_min' => -20,
            'visivel_max' => -12,
            'direcao' => 'alvo_para_autor',
            'memoria' => 'me_indicou',
            'forca_memoria' => 3
        ],

        'indicacao_bigfone' => [
            'amizade' => -10,
            'rivalidade' => 13,
            'confianca' => -10,
            'visivel_min' => -18,
            'visivel_max' => -10,
            'direcao' => 'alvo_para_autor',
            'memoria' => 'me_indicou',
            'forca_memoria' => 3
        ],

        /* Só é aplicado se o voto for REVELADO. */
        'voto_revelado' => [
            'amizade' => -6,
            'rivalidade' => 8,
            'confianca' => -6,
            'visivel_min' => -10,
            'visivel_max' => -6,
            'direcao' => 'alvo_para_autor',
            'memoria' => 'votou_em_mim',
            'forca_memoria' => 2
        ],

        'discordia_negativa_forte' => [
            'amizade' => -15,
            'rivalidade' => 12,
            'confianca' => -10,
            'visivel_min' => -15,
            'visivel_max' => -15,
            'direcao' => 'mutua',
            'memoria' => 'me_atacou_discordia',
            'forca_memoria' => 2
        ],

        'discordia_negativa_leve' => [
            'amizade' => -6,
            'rivalidade' => 5,
            'confianca' => -4,
            'visivel_min' => -6,
            'visivel_max' => -6,
            'direcao' => 'mutua',
            'memoria' => 'me_atacou_discordia',
            'forca_memoria' => 1
        ],

        'discordia_sabonete' => [
            'amizade' => -2,
            'rivalidade' => 2,
            'confianca' => -2,
            'visivel_min' => -2,
            'visivel_max' => -2,
            'direcao' => 'mutua',
            'memoria' => 'me_atacou_discordia',
            'forca_memoria' => 1
        ],

        'discordia_aliado' => [
            'amizade' => 12,
            'rivalidade' => -5,
            'confianca' => 10,
            'visivel_min' => 12,
            'visivel_max' => 12,
            'direcao' => 'mutua',
            'memoria' => 'me_elogiou_discordia',
            'forca_memoria' => 2
        ],

        'discordia_podio_2' => [
            'amizade' => 10,
            'rivalidade' => -4,
            'confianca' => 8,
            'visivel_min' => 10,
            'visivel_max' => 10,
            'direcao' => 'mutua',
            'memoria' => 'me_elogiou_discordia',
            'forca_memoria' => 2
        ],

        'discordia_podio_3' => [
            'amizade' => 6,
            'rivalidade' => -2,
            'confianca' => 5,
            'visivel_min' => 6,
            'visivel_max' => 6,
            'direcao' => 'mutua',
            'memoria' => 'me_elogiou_discordia',
            'forca_memoria' => 1
        ]
,

        'discordia_premio_forte' => [
            'amizade' => 10,
            'rivalidade' => -4,
            'confianca' => 9,
            'visivel_min' => 9,
            'visivel_max' => 12,
            'direcao' => 'mutua',
            'memoria' => 'me_elogiou_discordia',
            'forca_memoria' => 2
        ],

        'discordia_premio' => [
            'amizade' => 6,
            'rivalidade' => -2,
            'confianca' => 5,
            'visivel_min' => 5,
            'visivel_max' => 8,
            'direcao' => 'mutua',
            'memoria' => 'me_elogiou_discordia',
            'forca_memoria' => 1
        ],

        'discordia_respeito' => [
            'amizade' => 2,
            'rivalidade' => 1,
            'confianca' => 2,
            'visivel_min' => 1,
            'visivel_max' => 3,
            'direcao' => 'mutua',
            'memoria' => 'me_elogiou_discordia',
            'forca_memoria' => 1
        ],

        'discordia_critica' => [
            'amizade' => -6,
            'rivalidade' => 5,
            'confianca' => -4,
            'visivel_min' => -8,
            'visivel_max' => -5,
            'direcao' => 'mutua',
            'memoria' => 'me_atacou_discordia',
            'forca_memoria' => 1
        ],

        'discordia_critica_forte' => [
            'amizade' => -11,
            'rivalidade' => 10,
            'confianca' => -8,
            'visivel_min' => -13,
            'visivel_max' => -9,
            'direcao' => 'mutua',
            'memoria' => 'me_atacou_discordia',
            'forca_memoria' => 2
        ]
    ];
}


/* =========================================================
   🔒 CONTROLE CONTRA EFEITO DUPLICADO
   ========================================================= */
function consequenciaSocialJaAplicada($chave)
{
    if ($chave === '') {
        return false;
    }

    if (
        !isset($_SESSION['consequencias_sociais_aplicadas']) ||
        !is_array($_SESSION['consequencias_sociais_aplicadas'])
    ) {
        $_SESSION['consequencias_sociais_aplicadas'] = [];
    }

    return !empty(
        $_SESSION['consequencias_sociais_aplicadas'][$chave]
    );
}


function marcarConsequenciaSocialAplicada($chave)
{
    if ($chave === '') {
        return;
    }

    if (
        !isset($_SESSION['consequencias_sociais_aplicadas']) ||
        !is_array($_SESSION['consequencias_sociais_aplicadas'])
    ) {
        $_SESSION['consequencias_sociais_aplicadas'] = [];
    }

    $_SESSION['consequencias_sociais_aplicadas'][$chave] = true;

    /* Evita crescimento infinito em temporadas longas. */
    if (count($_SESSION['consequencias_sociais_aplicadas']) > 500) {
        $_SESSION['consequencias_sociais_aplicadas'] = array_slice(
            $_SESSION['consequencias_sociais_aplicadas'],
            -350,
            null,
            true
        );
    }
}


/* =========================================================
   🎲 VALOR VISÍVEL
   ========================================================= */
function sortearDeltaVisivelConsequencia($min, $max)
{
    $min = (int)$min;
    $max = (int)$max;

    if ($min > $max) {
        [$min, $max] = [$max, $min];
    }

    if ($min === $max) {
        return $min;
    }

    return rand($min, $max);
}


/* =========================================================
   ❤️ ATUALIZAR AFINIDADE VISÍVEL QUANDO O JOGADOR PARTICIPA
   ========================================================= */
function aplicarDeltaVisivelEntreJogadorEParticipante(
    $autor,
    $alvo,
    $delta
) {
    $meuNome = trim((string)($_SESSION['meu_nome'] ?? ''));

    if ($meuNome === '' || $delta === 0) {
        return;
    }

    $outro = '';

    if (nomeIgual($autor, $meuNome) && !nomeIgual($alvo, $meuNome)) {
        $outro = $alvo;
    } elseif (nomeIgual($alvo, $meuNome) && !nomeIgual($autor, $meuNome)) {
        $outro = $autor;
    }

    if ($outro !== '' && function_exists('ajustarRelacaoJogador')) {
        ajustarRelacaoJogador(
            $outro,
            $delta
        );
    }
}


/* =========================================================
   🧠 REGISTRAR MEMÓRIA NO NPC ALVO
   ========================================================= */
function registrarMemoriaDaConsequenciaSocial(
    $autor,
    $alvo,
    $tipoMemoria,
    $forca,
    $descricao,
    $chave
) {
    if (
        $tipoMemoria === '' ||
        !function_exists('registrarMemoriaSocialNPC')
    ) {
        return;
    }

    /*
     * A memória pertence ao alvo da ação.
     * Se o alvo for o jogador, registrarMemoriaSocialNPC simplesmente
     * não terá utilidade na IA, mas continua seguro.
     */
    registrarMemoriaSocialNPC(
        $alvo,
        $autor,
        $tipoMemoria,
        max(1, (int)$forca),
        $descricao,
        $chave !== '' ? 'mem|' . $chave : ''
    );
}


/* =========================================================
   ⚙️ APLICAR CONSEQUÊNCIA CONFIGURADA
   ========================================================= */
function aplicarConsequenciaSocial(
    &$jogadores,
    $autor,
    $alvo,
    $tipo,
    $chaveUnica = '',
    $descricao = ''
) {
    $autor = trim((string)$autor);
    $alvo = trim((string)$alvo);
    $tipo = trim((string)$tipo);

    if (
        $autor === '' ||
        $alvo === '' ||
        $tipo === '' ||
        nomeIgual($autor, $alvo)
    ) {
        return false;
    }

    $configs = configuracoesConsequenciasSociais();

    if (!isset($configs[$tipo])) {
        return false;
    }

    if ($chaveUnica === '') {
        $chaveUnica =
            $tipo . '|' .
            ($_SESSION['rodada'] ?? 1) . '|' .
            $autor . '|' .
            $alvo;
    }

    if (consequenciaSocialJaAplicada($chaveUnica)) {
        return false;
    }

    $c = $configs[$tipo];

    $amizade = (int)($c['amizade'] ?? 0);
    $rivalidade = (int)($c['rivalidade'] ?? 0);
    $confianca = (int)($c['confianca'] ?? 0);
    $direcao = $c['direcao'] ?? 'alvo_para_autor';

    if (function_exists('alterarAfinidade')) {
        if ($direcao === 'autor_para_alvo' || $direcao === 'mutua') {
            alterarAfinidade(
                $jogadores,
                $autor,
                $alvo,
                $amizade,
                $rivalidade,
                $confianca
            );
        }

        if ($direcao === 'alvo_para_autor' || $direcao === 'mutua') {
            alterarAfinidade(
                $jogadores,
                $alvo,
                $autor,
                $amizade,
                $rivalidade,
                $confianca
            );
        }
    }

    $deltaVisivel = sortearDeltaVisivelConsequencia(
        $c['visivel_min'] ?? 0,
        $c['visivel_max'] ?? 0
    );

    aplicarDeltaVisivelEntreJogadorEParticipante(
        $autor,
        $alvo,
        $deltaVisivel
    );

    if ($descricao === '') {
        $descricao = "$autor gerou a consequência $tipo em $alvo.";
    }

    registrarMemoriaDaConsequenciaSocial(
        $autor,
        $alvo,
        $c['memoria'] ?? '',
        $c['forca_memoria'] ?? 1,
        $descricao,
        $chaveUnica
    );

    marcarConsequenciaSocialAplicada($chaveUnica);

    return true;
}


/* =========================================================
   💖 CONSEQUÊNCIA PERSONALIZADA
   Usada principalmente pelo Queridômetro.
   ========================================================= */
function aplicarConsequenciaSocialPersonalizada(
    &$jogadores,
    $autor,
    $alvo,
    $amizade,
    $rivalidade,
    $confianca,
    $deltaVisivel,
    $direcao,
    $tipoMemoria,
    $forcaMemoria,
    $chaveUnica,
    $descricao = ''
) {
    $autor = trim((string)$autor);
    $alvo = trim((string)$alvo);

    if (
        $autor === '' ||
        $alvo === '' ||
        nomeIgual($autor, $alvo)
    ) {
        return false;
    }

    if (consequenciaSocialJaAplicada($chaveUnica)) {
        return false;
    }

    if (function_exists('alterarAfinidade')) {
        if ($direcao === 'autor_para_alvo' || $direcao === 'mutua') {
            alterarAfinidade(
                $jogadores,
                $autor,
                $alvo,
                (int)$amizade,
                (int)$rivalidade,
                (int)$confianca
            );
        }

        if ($direcao === 'alvo_para_autor' || $direcao === 'mutua') {
            alterarAfinidade(
                $jogadores,
                $alvo,
                $autor,
                (int)$amizade,
                (int)$rivalidade,
                (int)$confianca
            );
        }
    }

    aplicarDeltaVisivelEntreJogadorEParticipante(
        $autor,
        $alvo,
        (int)$deltaVisivel
    );

    registrarMemoriaDaConsequenciaSocial(
        $autor,
        $alvo,
        $tipoMemoria,
        $forcaMemoria,
        $descricao,
        $chaveUnica
    );

    marcarConsequenciaSocialAplicada($chaveUnica);

    return true;
}
