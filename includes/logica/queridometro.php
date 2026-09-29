<?php

/* =========================================================
   💖 LÓGICA DO QUERIDÔMETRO — NPCs 2.0 FASE 3
   ========================================================= */

require_once __DIR__ . '/consequencias_sociais.php';

$EMOJIS_QUERIDOMETRO = [
    "❤️" => [
        "nome" => "Amor / Afinidade Forte",
        "tipo" => "positivo",
        "afinidade" => 12,
        "popularidade" => 4
    ],
    "😄" => [
        "nome" => "Gosto / Simpatia",
        "tipo" => "positivo",
        "afinidade" => 6,
        "popularidade" => 2
    ],
    "🤝" => [
        "nome" => "Aliança / Confiança",
        "tipo" => "positivo",
        "afinidade" => 10,
        "popularidade" => 3
    ],
    "🔥" => [
        "nome" => "Treta / Caótica",
        "tipo" => "neutro",
        "afinidade" => -2,
        "popularidade" => 5
    ],
    "😴" => [
        "nome" => "Planta / Apagado",
        "tipo" => "negativo",
        "afinidade" => -5,
        "popularidade" => -6
    ],
    "🐍" => [
        "nome" => "Falso / Mentiroso",
        "tipo" => "negativo",
        "afinidade" => -12,
        "popularidade" => -8
    ],
    "🎯" => [
        "nome" => "Alvo / Quero Eliminar",
        "tipo" => "negativo",
        "afinidade" => -10,
        "popularidade" => -4
    ],
    "💔" => [
        "nome" => "Chateado / Me Decepcionou",
        "tipo" => "negativo",
        "afinidade" => -15,
        "popularidade" => -3
    ],
    "🤮" => [
        "nome" => "Ranço / Relação Ruim",
        "tipo" => "negativo",
        "afinidade" => -18,
        "popularidade" => -7
    ],
    "🙄" => [
        "nome" => "Forçado / VTzeiro",
        "tipo" => "negativo",
        "afinidade" => -6,
        "popularidade" => -10
    ],
    "😡" => [
        "nome" => "Explosivo / Barraqueiro",
        "tipo" => "misto",
        "afinidade" => -4,
        "popularidade" => 3
    ]
];


function iniciarQueridometro()
{
    if (!isset($_SESSION['queridometro_resultado'])) {
        $_SESSION['queridometro_resultado'] = [];
    }
}


/* =========================================================
   🎲 SORTEIO PONDERADO DO EMOJI
   ========================================================= */
function sortearEmojiQueridometroPorPeso($pesos)
{
    $total = 0;
    $normalizados = [];

    foreach ($pesos as $emoji => $peso) {
        $peso = max(1, (int)$peso);
        $normalizados[$emoji] = $peso;
        $total += $peso;
    }

    $sorteio = rand(1, max(1, $total));
    $acumulado = 0;

    foreach ($normalizados as $emoji => $peso) {
        $acumulado += $peso;
        if ($sorteio <= $acumulado) {
            return $emoji;
        }
    }

    return array_key_first($normalizados);
}


/* =========================================================
   🧠 EMOJI AUTOMÁTICO BASEADO NA RELAÇÃO
   ========================================================= */

function escolherEmojiQueridometroPorRelacao(
    $relacao,
    $romance = 0
) {
    $relacao = (int)$relacao;
    $romance = (int)$romance;

    /*
     * Romance forte deixa os emojis positivos mais prováveis,
     * sem transformar a escolha em algo 100% previsível.
     */
    if ($romance >= 60) {
        $relacao += 30;
    } elseif ($romance >= 30) {
        $relacao += 15;
    }

    if ($relacao >= 60) {
        return sortearEmojiQueridometroPorPeso([
            '❤️' => 55,
            '🤝' => 25,
            '😄' => 15,
            '🔥' => 5
        ]);
    }

    if ($relacao >= 35) {
        return sortearEmojiQueridometroPorPeso([
            '🤝' => 42,
            '😄' => 30,
            '❤️' => 15,
            '🔥' => 8,
            '😴' => 5
        ]);
    }

    if ($relacao >= 12) {
        return sortearEmojiQueridometroPorPeso([
            '😄' => 40,
            '🤝' => 25,
            '🔥' => 18,
            '❤️' => 7,
            '😴' => 5,
            '🙄' => 5
        ]);
    }

    if ($relacao > -8) {
        return sortearEmojiQueridometroPorPeso([
            '🔥' => 24,
            '😴' => 18,
            '🙄' => 16,
            '😡' => 14,
            '😄' => 14,
            '🤝' => 8,
            '🎯' => 6
        ]);
    }

    if ($relacao > -20) {
        return sortearEmojiQueridometroPorPeso([
            '🎯' => 30,
            '🙄' => 22,
            '😡' => 18,
            '🐍' => 12,
            '🔥' => 10,
            '😴' => 8
        ]);
    }

    if ($relacao > -35) {
        return sortearEmojiQueridometroPorPeso([
            '🐍' => 32,
            '🎯' => 26,
            '💔' => 18,
            '🤮' => 8,
            '🙄' => 8,
            '😡' => 8
        ]);
    }

    if ($relacao > -55) {
        return sortearEmojiQueridometroPorPeso([
            '💔' => 34,
            '🐍' => 24,
            '🤮' => 20,
            '🎯' => 12,
            '😡' => 10
        ]);
    }

    return sortearEmojiQueridometroPorPeso([
        '🤮' => 48,
        '💔' => 24,
        '🐍' => 15,
        '🎯' => 8,
        '😡' => 5
    ]);
}


/*
 * Mantém compatibilidade com o preenchimento automático
 * do próprio jogador.
 */
function escolherEmojiQueridometroAutomatico(
    $jogadores,
    $meuNome,
    $nomeAlvo
) {
    $relacao = (int)(
        $_SESSION['relacoes_jogador'][$nomeAlvo]
        ?? 0
    );

    $romance =
        function_exists('obterRomance')
            ? obterRomance(
                $jogadores,
                $meuNome,
                $nomeAlvo
            )
            : 0;

    return escolherEmojiQueridometroPorRelacao(
        $relacao,
        $romance
    );
}


/*
 * NPC -> alvo:
 * usa a relação REAL daquele NPC com a pessoa.
 * Assim um NPC que não gosta de você tende a mandar emoji negativo,
 * enquanto aliados tendem a mandar ❤️ / 🤝 / 😄.
 */
function escolherEmojiQueridometroNPC(
    $jogadores,
    $nomeNPC,
    $nomeAlvo,
    $meuNome
) {
    $score = 0;

    if (function_exists('calcularRelacaoIA')) {
        $score = calcularRelacaoIA(
            $jogadores,
            $nomeNPC,
            $nomeAlvo,
            $meuNome
        );
    } else {
        foreach ($jogadores as $j) {
            if (!nomeIgual($j['nome'] ?? '', $nomeNPC)) {
                continue;
            }

            $rel = $j['relacoes'][$nomeAlvo] ?? [];

            $score =
                (int)($rel['amizade'] ?? 0) +
                (int)($rel['confianca'] ?? 0) -
                (int)($rel['rivalidade'] ?? 0);

            break;
        }
    }

    $romance = 0;

    foreach ($jogadores as $j) {
        if (!nomeIgual($j['nome'] ?? '', $nomeNPC)) {
            continue;
        }

        $romance =
            (int)($j['romances'][$nomeAlvo] ?? 0);

        break;
    }

    return escolherEmojiQueridometroPorRelacao(
        $score,
        $romance
    );
}


/* =========================================================
   💟 REGISTRAR EMOJI
   ========================================================= */
function registrarEmojiQueridometro(
    &$jogadores,
    $de,
    $para,
    $emoji,
    $EMOJIS_QUERIDOMETRO
) {
    if (
        $de == '' ||
        $para == '' ||
        $emoji == '' ||
        !isset($EMOJIS_QUERIDOMETRO[$emoji])
    ) {
        return;
    }

    iniciarQueridometro();

    if (!isset($_SESSION['queridometro_resultado'][$para])) {
        $_SESSION['queridometro_resultado'][$para] = [];
    }

    if (!isset($_SESSION['queridometro_resultado'][$para][$emoji])) {
        $_SESSION['queridometro_resultado'][$para][$emoji] = 0;
    }

    $_SESSION['queridometro_resultado'][$para][$emoji]++;

    $dados = $EMOJIS_QUERIDOMETRO[$emoji];
    $delta = (int)($dados['afinidade'] ?? 0);
    $tipo = $dados['tipo'] ?? 'neutro';

    /*
     * O emoji revela o sentimento de QUEM ENVIOU.
     * Por isso a direção interna é autor -> alvo.
     * O valor visível jogador/NPC acompanha a mesma mudança.
     */
    $rivalidade = 0;
    $confianca = 0;

    if ($delta > 0) {
        $confianca = max(1, (int)round($delta * 0.55));
        $rivalidade = -max(1, (int)round($delta * 0.25));
    } elseif ($delta < 0) {
        $rivalidade = max(1, (int)round(abs($delta) * 0.60));
        $confianca = -max(1, (int)round(abs($delta) * 0.35));
    }

    $tipoMemoria =
        $delta >= 4
            ? 'me_deu_emoji_positivo'
            : (
                $delta <= -4
                    ? 'me_deu_emoji_negativo'
                    : ''
            );

    aplicarConsequenciaSocialPersonalizada(
        $jogadores,
        $de,
        $para,
        $delta,
        $rivalidade,
        $confianca,
        $delta,
        'autor_para_alvo',
        $tipoMemoria,
        abs($delta) >= 12 ? 2 : 1,
        'queridometro|' .
        ($_SESSION['rodada'] ?? 1) . '|' .
        $de . '|' .
        $para,
        "$de deu $emoji para $para no Queridômetro."
    );

    /*
     * Mantém a lógica de repercussão pública que já existia:
     * só quando o jogador envia emoji negativo.
     */
    if (
        nomeIgual($de, $_SESSION['meu_nome'] ?? '') &&
        $tipo === 'negativo'
    ) {
        $popularidadeAlvo = 50;

        foreach ($jogadores as $j) {
            if (nomeIgual($j['nome'] ?? '', $para)) {
                $popularidadeAlvo = $j['popularidade'] ?? 50;
                break;
            }
        }

        if ($popularidadeAlvo >= 65) {
            $perda = rand(3, 5);
            alterarPopularidade(
                $jogadores,
                $de,
                -$perda
            );

            $_SESSION['evento_extra'][] =
                "📉 O público não curtiu $de atacando $para no Queridômetro.";

        } elseif ($popularidadeAlvo <= 35) {
            $ganho = rand(3, 5);
            alterarPopularidade(
                $jogadores,
                $de,
                $ganho
            );

            $_SESSION['evento_extra'][] =
                "📈 O público gostou de $de mirar em $para no Queridômetro.";
        }
    }
}
