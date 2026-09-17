<?php

/* =========================================================
   🏆 LÓGICA DA GRANDE FINAL
   Popularidade é o fator principal.
   Trajetória ajuda.
   Existe uma pequena imprevisibilidade.
   ========================================================= */

require_once __DIR__ . '/utilitarios.php';


/* =========================================================
   📊 ESTATÍSTICA DO FINALISTA
   ========================================================= */

function estatisticaFinalista($jogador, $campo)
{
    return
        $jogador['estatisticas'][$campo]
        ?? 0;
}


/* =========================================================
   🎮 PONTUAÇÃO DE TRAJETÓRIA
   ========================================================= */

function calcularTrajetoriaFinal($jogador)
{
    /*
     * Pesos pensados para premiar desempenho
     * sem deixar as provas valerem mais que
     * a popularidade pública.
     */

    $lider =
        estatisticaFinalista(
            $jogador,
            'lider'
        );

    $anjo =
        estatisticaFinalista(
            $jogador,
            'anjo'
        );

    $paredao =
        estatisticaFinalista(
            $jogador,
            'paredao'
        );

    $imune =
        estatisticaFinalista(
            $jogador,
            'imune'
        );


    $trajetoria =
          ($lider * 10)
        + ($anjo * 7)
        + ($paredao * 5)
        + ($imune * 2);


    /*
     * Limita só a nota de trajetória.
     * Não tem relação com afinidade.
     */
    return limitar(
        $trajetoria,
        0,
        100
    );
}


/* =========================================================
   👑 PONTUAÇÃO FINAL
   ========================================================= */

function calcularPontuacaoFinal($jogador)
{
    $popularidade =
        limitar(
            (int)($jogador['popularidade'] ?? 50),
            0,
            100
        );


    $trajetoria =
        calcularTrajetoriaFinal(
            $jogador
        );


    /*
     * 82% → popularidade
     * 13% → trajetória
     * até ±5 pontos → imprevisibilidade
     *
     * Assim o favorito continua muito
     * favorecido, mas a Final não fica
     * 100% matemática.
     */
    $pontuacao =
          ($popularidade * 0.82)
        + ($trajetoria * 0.13)
        + rand(-5, 5);


    return max(
        1,
        round($pontuacao, 2)
    );
}


/* =========================================================
   🗳️ TRANSFORMAR PONTUAÇÕES EM PORCENTAGENS
   ========================================================= */

function gerarPorcentagensFinal($avaliados)
{
    $total = 0;


    foreach ($avaliados as $dados) {

        $total +=
            $dados['pontuacao'];
    }


    if ($total <= 0) {
        return [];
    }


    $percentuais = [];
    $acumulado = 0;

    $ultimo =
        count($avaliados) - 1;


    foreach ($avaliados as $i => $dados) {

        $nome =
            $dados['jogador']['nome']
            ?? 'Finalista';


        if ($i == $ultimo) {

            /*
             * O último recebe o restante
             * para fechar exatamente 100%.
             */
            $pct =
                round(
                    100 - $acumulado,
                    2
                );

        } else {

            $pct =
                round(
                    (
                        $dados['pontuacao']
                        /
                        $total
                    ) * 100,
                    2
                );


            $acumulado +=
                $pct;
        }


        $percentuais[$nome] =
            max(
                0.01,
                $pct
            );
    }


    return $percentuais;
}


/* =========================================================
   🏆 GERAR RESULTADO DA GRANDE FINAL
   ========================================================= */

function gerarResultadoFinalInteligente($jogadores)
{
    $avaliados = [];


    foreach ($jogadores as $jogador) {

        $avaliados[] = [

            'jogador' =>
                $jogador,

            'popularidade' =>
                limitar(
                    (int)(
                        $jogador['popularidade']
                        ?? 50
                    ),
                    0,
                    100
                ),

            'trajetoria' =>
                calcularTrajetoriaFinal(
                    $jogador
                ),

            'pontuacao' =>
                calcularPontuacaoFinal(
                    $jogador
                )
        ];
    }


    /*
     * Maior pontuação = melhor colocação.
     */
    usort(
        $avaliados,
        function ($a, $b) {

            /*
             * Primeiro compara a nota final.
             */
            if (
                $a['pontuacao']
                !=
                $b['pontuacao']
            ) {

                return
                    $b['pontuacao']
                    <=>
                    $a['pontuacao'];
            }


            /*
             * Empate:
             * vence quem tiver maior popularidade.
             */
            return
                $b['popularidade']
                <=>
                $a['popularidade'];
        }
    );


    /*
     * Segurança.
     */
    if (count($avaliados) < 3) {

        return [
            'ranking' => [],
            'percentuais' => [],
            'pontuacoes' => []
        ];
    }


    $ranking = [

        'primeiro' =>
            $avaliados[0]['jogador'],

        'segundo' =>
            $avaliados[1]['jogador'],

        'terceiro' =>
            $avaliados[2]['jogador']
    ];


    $percentuais =
        gerarPorcentagensFinal(
            $avaliados
        );


    $pontuacoes = [];


    foreach ($avaliados as $dados) {

        $nome =
            $dados['jogador']['nome']
            ?? 'Finalista';


        $pontuacoes[$nome] = [

            'popularidade' =>
                $dados['popularidade'],

            'trajetoria' =>
                $dados['trajetoria'],

            'pontuacao_final' =>
                $dados['pontuacao']
        ];
    }


    return [

        'ranking' =>
            $ranking,

        'percentuais' =>
            $percentuais,

        'pontuacoes' =>
            $pontuacoes
    ];
}