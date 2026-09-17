<?php

/* =========================================================
   💬 FOFOCAS + 🎬 VTs AVANÇADOS
   ========================================================= */

if (
    !function_exists(
        'escolherAlvoNPCInteligente'
    )
) {
    require_once __DIR__ . '/inteligencia_npc.php';
}


/* =========================================================
   📚 GARANTIR HISTÓRICOS
   ========================================================= */

function garantirHistoricoFofocasEVTs()
{
    if (
        !isset($_SESSION['historico_fofocas']) ||
        !is_array($_SESSION['historico_fofocas'])
    ) {
        $_SESSION['historico_fofocas'] = [];
    }

    if (
        !isset($_SESSION['historico_vts']) ||
        !is_array($_SESSION['historico_vts'])
    ) {
        $_SESSION['historico_vts'] = [];
    }
}


/* =========================================================
   🗂️ REGISTRAR HISTÓRICO LIMITADO
   ========================================================= */

function registrarHistoricoLimitado(
    $chave,
    $item,
    $limite = 30
) {

    garantirHistoricoFofocasEVTs();

    $_SESSION[$chave][] = $item;

    if (count($_SESSION[$chave]) > $limite) {

        $_SESSION[$chave] =
            array_slice(
                $_SESSION[$chave],
                -$limite
            );
    }
}


/* =========================================================
   🎯 ESCOLHER ALVO ALEATÓRIO VÁLIDO
   ========================================================= */

function escolherAlvoAleatorioValido(
    $jogadores,
    $bloqueados = []
) {

    $opcoes = [];

    foreach ($jogadores as $j) {

        $nome = $j['nome'] ?? '';

        if ($nome == '') {
            continue;
        }

        if (in_array($nome, $bloqueados)) {
            continue;
        }

        $opcoes[] = $nome;
    }


    if (empty($opcoes)) {
        return '';
    }


    return $opcoes[
        array_rand($opcoes)
    ];
}


/* =========================================================
   🐍 TEMA ALEATÓRIO DE FOFOCA
   ========================================================= */

function temaFofocaAleatorio(
    $fofoqueiro,
    $alvo
) {

    $temas = [

        "disse que $alvo está se escondendo no jogo",

        "comentou que $alvo só aparece quando tem câmera por perto",

        "espalhou que $alvo está combinando votos escondido",

        "falou que $alvo está se aproximando do líder por interesse",

        "disse que $alvo está usando amizades como estratégia",

        "soltou que $alvo já tem alvo definido para o próximo paredão",

        "comentou que $alvo está fazendo personagem para o público",

        "disse que $alvo está se fazendo de vítima",

        "falou que $alvo promete lealdade para todo mundo",

        "espalhou que $alvo está jogando dos dois lados"

    ];


    return $temas[
        array_rand($temas)
    ];
}


/* =========================================================
   💬 RESOLVER FOFOCA
   ========================================================= */

function resolverFofocaAvancada(
    &$jogadores,
    $fofoqueiro,
    $alvo,
    $meuNome = ''
) {

    if (
        $fofoqueiro == '' ||
        $alvo == '' ||
        nomeIgual($fofoqueiro, $alvo)
    ) {

        return
            "⚠️ Escolha um participante válido para a fofoca.";
    }


    garantirHistoricoFofocasEVTs();


    $tema =
        temaFofocaAleatorio(
            $fofoqueiro,
            $alvo
        );


    $chanceEspalhar =
        rand(45, 80);

    $chanceDescobrir =
        rand(35, 70);


    $fofocaPegou =
        rand(1, 100) <= $chanceEspalhar;

    $alvoDescobriu =
        rand(1, 100) <= $chanceDescobrir;


    $testemunhasAfetadas = [];


    /* =====================================================
       👥 EFEITO NAS OUTRAS PESSOAS
       ===================================================== */

    foreach ($jogadores as $j) {

        $nome =
            $j['nome'] ?? '';


        if (
            $nome == '' ||
            nomeIgual($nome, $fofoqueiro) ||
            nomeIgual($nome, $alvo)
        ) {
            continue;
        }


        if (
            $fofocaPegou &&
            rand(1, 100) <= 55
        ) {

            alterarAfinidade(
                $jogadores,
                $nome,
                $alvo,
                rand(-9, -3),
                rand(2, 7),
                rand(-8, -2)
            );


            $testemunhasAfetadas[] =
                $nome;
        }


        if (
            $alvoDescobriu &&
            rand(1, 100) <= 35
        ) {

            alterarAfinidade(
                $jogadores,
                $nome,
                $fofoqueiro,
                rand(-5, -1),
                rand(1, 5),
                rand(-6, -1)
            );
        }
    }


    /* =====================================================
       🐍 FOFOCA PEGOU
       ===================================================== */

    if ($fofocaPegou) {

        alterarAfinidade(
            $jogadores,
            $fofoqueiro,
            $alvo,
            rand(-5, -2),
            rand(2, 6),
            rand(-5, -2)
        );


        /*
         * Se o alvo da fofoca for o jogador,
         * atualiza também a relação visível.
         */
        if (
            nomeIgual(
                $alvo,
                $meuNome
            )
        ) {

            ajustarRelacaoJogador(
                $fofoqueiro,
                -8
            );
        }


        /*
         * Se quem fez a fofoca foi o jogador,
         * mostra a reação do público.
         */
        if (
            nomeIgual(
                $fofoqueiro,
                $meuNome
            )
        ) {

            alterarPopularidadePublica(
                $jogadores,
                $fofoqueiro,
                -4,
                6,
                "movimentou a casa com uma fofoca",
                true
            );

        } else {

            impactoPopularidadePorPersonalidade(
                $jogadores,
                $fofoqueiro,
                "fofoca",
                false
            );
        }


        $evento =
            "💬 A fofoca se espalhou: <b>$fofoqueiro</b> $tema.";
    }


    /* =====================================================
       🫢 FOFOCA NÃO PEGOU
       ===================================================== */

    else {

        alterarAfinidade(
            $jogadores,
            $alvo,
            $fofoqueiro,
            rand(-6, -2),
            rand(2, 7),
            rand(-8, -3)
        );


        alterarPopularidadePublica(
            $jogadores,
            $fofoqueiro,
            -8,
            -3,
            "tentou espalhar fofoca, mas a casa não comprou",
            nomeIgual(
                $fofoqueiro,
                $meuNome
            )
        );


        $evento =
            "🫢 <b>$fofoqueiro</b> tentou espalhar que $tema, mas a fofoca não pegou muito.";
    }


    /* =====================================================
       😡 ALVO DESCOBRIU
       ===================================================== */

    if ($alvoDescobriu) {

        alterarAfinidade(
            $jogadores,
            $alvo,
            $fofoqueiro,
            rand(-12, -5),
            rand(5, 12),
            rand(-10, -4)
        );


        $evento .=
            " 😡 <b>$alvo</b> descobriu e ficou muito incomodado.";


        registrarMemoriaSocialNPC(
            $alvo,
            $fofoqueiro,
            'espalhou_fofoca',
            2,
            "$fofoqueiro espalhou uma fofoca sobre $alvo e $alvo descobriu.",
            'fofoca_descoberta|' .
            ($_SESSION['rodada'] ?? 1) .
            "|$fofoqueiro|$alvo|" .
            count(
                $_SESSION['historico_fofocas']
                ?? []
            )
        );


        /*
         * Fofoca dentro da própria aliança
         * pode causar rompimento.
         */
        if (
            mesmaAliancaNomes(
                $jogadores,
                $fofoqueiro,
                $alvo
            ) &&
            rand(1, 100) <= 35
        ) {

            $rompimento =
                romperAlianca(
                    $jogadores,
                    $alvo,
                    "descobriu uma fofoca interna envolvendo $fofoqueiro"
                );


            if ($rompimento != '') {

                $evento .=
                    "<br>" .
                    $rompimento;
            }
        }
    }


    /* Atualizar relações importantes */

    registrarRelacaoMarcante(
        $jogadores,
        $fofoqueiro,
        $alvo
    );


    registrarRelacaoMarcante(
        $jogadores,
        $alvo,
        $fofoqueiro
    );


    /* Salvar no histórico */

    registrarHistoricoLimitado(
        'historico_fofocas',
        [

            'rodada' =>
                $_SESSION['rodada'] ?? 1,

            'fofoqueiro' =>
                $fofoqueiro,

            'alvo' =>
                $alvo,

            'pegou' =>
                $fofocaPegou,

            'descobriu' =>
                $alvoDescobriu,

            'tema' =>
                strip_tags($tema)

        ]
    );


    return $evento;
}


/* =========================================================
   🎬 RESOLVER VT
   ========================================================= */

function resolverVTAvancado(
    &$jogadores,
    $nome,
    $meuNome = ''
) {

    if ($nome == '') {

        return
            "⚠️ Participante inválido para fazer VT.";
    }


    $modelos = [

        [
            'tipo' => 'emocionante',

            'texto' =>
                "🎬 <b>$nome</b> fez um VT emocionante falando sobre sua trajetória no jogo.",

            'min' => 4,
            'max' => 6
        ],

        [
            'tipo' => 'engracado',

            'texto' =>
                "😂 <b>$nome</b> protagonizou um momento engraçado e virou assunto entre o público.",

            'min' => 2,
            'max' => 6
        ],

        [
            'tipo' => 'forcado',

            'texto' =>
                "🙄 <b>$nome</b> tentou fazer VT, mas parte do público achou forçado.",

            'min' => -5,
            'max' => -3
        ],

        [
            'tipo' => 'vilao',

            'texto' =>
                "🐍 <b>$nome</b> entregou um VT de vilão, movimentou o jogo e dividiu opiniões.",

            'min' => -6,
            'max' => 6
        ],

        [
            'tipo' => 'vitima',

            'texto' =>
                "🥺 <b>$nome</b> fez um VT de vítima e o público ficou dividido.",

            'min' => -2,
            'max' => 5
        ],

        [
            'tipo' => 'protagonista',

            'texto' =>
                "🌟 <b>$nome</b> roubou a cena e ganhou narrativa de protagonista na edição.",

            'min' => 4,
            'max' => 6
        ]

    ];


    $dadosParticipanteVT =
        buscarParticipanteInteligenciaNPC(
            $jogadores,
            $nome
        );

    $personalidadeVT =
        $dadosParticipanteVT['personalidade']
        ?? 'Neutro';

    /*
     * Para o jogador, mantém variedade livre.
     * Para NPC, o tipo de VT acompanha a personalidade.
     */
    if (
        nomeIgual(
            $nome,
            $meuNome
        )
    ) {
        $modelo =
            $modelos[
                array_rand($modelos)
            ];

    } else {

        $tipoDesejado =
            escolherTipoVTNPCInteligente(
                $personalidadeVT
            );

        $modelo = null;

        foreach ($modelos as $modeloPossivel) {
            if (
                ($modeloPossivel['tipo'] ?? '')
                === $tipoDesejado
            ) {
                $modelo =
                    $modeloPossivel;

                break;
            }
        }

        if ($modelo === null) {
            $modelo =
                $modelos[
                    array_rand($modelos)
                ];
        }
    }


    /* Reação do público */

    $valor =
        alterarPopularidadePublica(
            $jogadores,
            $nome,
            $modelo['min'],
            $modelo['max'],
            "VT " . $modelo['tipo'],
            nomeIgual(
                $nome,
                $meuNome
            )
        );


    /* =====================================================
       🙄 VT FORÇADO
       ===================================================== */

    if ($modelo['tipo'] == 'forcado') {

        foreach ($jogadores as $j) {

            $outro =
                $j['nome'] ?? '';


            if (
                $outro != '' &&
                !nomeIgual($outro, $nome) &&
                rand(1, 100) <= 25
            ) {

                alterarAfinidade(
                    $jogadores,
                    $outro,
                    $nome,
                    -3,
                    2,
                    -2
                );
            }
        }
    }


    /* =====================================================
       🌟 VT PROTAGONISTA / EMOCIONANTE
       ===================================================== */

    if (
        $modelo['tipo'] == 'protagonista' ||
        $modelo['tipo'] == 'emocionante'
    ) {

        foreach ($jogadores as $j) {

            $outro =
                $j['nome'] ?? '';


            if (
                $outro != '' &&
                !nomeIgual($outro, $nome) &&
                rand(1, 100) <= 20
            ) {

                alterarAfinidade(
                    $jogadores,
                    $outro,
                    $nome,
                    2,
                    -1,
                    2
                );
            }
        }
    }


    /* Histórico */

    registrarHistoricoLimitado(
        'historico_vts',
        [

            'rodada' =>
                $_SESSION['rodada'] ?? 1,

            'nome' =>
                $nome,

            'tipo' =>
                $modelo['tipo'],

            'popularidade' =>
                $valor

        ]
    );


    /* =====================================================
       📈 VT POSITIVO
       ===================================================== */

    if ($valor > 0) {

        if (
            nomeIgual(
                $nome,
                $meuNome
            ) &&
            in_array(
                $modelo['tipo'],
                [
                    'emocionante',
                    'protagonista',
                    'engracado'
                ]
            )
        ) {

            adicionarMoedasPublico(
                10,
                "fazer um VT que agradou o público"
            );
        }


        return
            $modelo['texto'] .
            " 📈 Popularidade +" .
            $valor .
            ".";
    }


    /* =====================================================
       📉 VT NEGATIVO
       ===================================================== */

    if ($valor < 0) {

        return
            $modelo['texto'] .
            " 📉 Popularidade " .
            $valor .
            ".";
    }


    return
        $modelo['texto'] .
        " ➖ O público ficou neutro.";
}


/* =========================================================
   🤖 FOFOCAS E VTs AUTOMÁTICOS
   ========================================================= */

function gerarFofocasEVTsAutomaticos(
    &$jogadores,
    $meuNome,
    $quantidade = 3
) {

    $eventos = [];

    $nomes =
        nomesJogadoresAtivos(
            $jogadores
        );

    if (count($nomes) < 2) {
        return $eventos;
    }

    for (
        $i = 0;
        $i < $quantidade;
        $i++
    ) {

        $tipo =
            rand(1, 100);


        /* =====================================================
           💬 FOFOCA — personalidade + alvo inteligente
           ===================================================== */

        if ($tipo <= 60) {

            $pesosFofoqueiro = [];

            foreach ($jogadores as $j) {

                $nome =
                    $j['nome'] ?? '';

                if ($nome == '') {
                    continue;
                }

                $personalidade =
                    $j['personalidade']
                    ?? 'Neutro';

                $pesoPorPersonalidade = [
                    'Estrategista' => 55,
                    'Explosivo' => 55,
                    'Planta' => 25,
                    'Manipulador' => 95,
                    'Emocional' => 40,
                    'Barraqueiro' => 70,
                    'Fofo' => 20,
                    'Líder Nato' => 45,
                    'Influencer' => 70,
                    'Falso' => 100,
                    'Neutro' => 45
                ];

                $pesosFofoqueiro[$nome] =
                    max(
                        1,
                        ($pesoPorPersonalidade[
                            $personalidade
                        ] ?? 45)
                        +
                        rand(-8, 8)
                    );
            }

            $total =
                array_sum(
                    $pesosFofoqueiro
                );

            $fofoqueiro = '';

            if ($total > 0) {

                $sorteio =
                    rand(1, $total);

                $acumulado = 0;

                foreach (
                    $pesosFofoqueiro
                    as $nome => $peso
                ) {

                    $acumulado +=
                        $peso;

                    if (
                        $sorteio <=
                        $acumulado
                    ) {
                        $fofoqueiro =
                            $nome;

                        break;
                    }
                }
            }

            if ($fofoqueiro == '') {
                $fofoqueiro =
                    $nomes[
                        array_rand($nomes)
                    ];
            }

            $alvo =
                escolherAlvoNPCInteligente(
                    $jogadores,
                    $fofoqueiro,
                    'fofoca',
                    [$fofoqueiro]
                );

            if ($alvo != null) {

                $eventos[] =
                    resolverFofocaAvancada(
                        $jogadores,
                        $fofoqueiro,
                        $alvo,
                        $meuNome
                    );
            }
        }


        /* =====================================================
           🎬 VT — quem gosta de câmera aparece mais
           ===================================================== */

        else {

            $pesosVT = [];

            foreach ($jogadores as $j) {

                $nome =
                    $j['nome'] ?? '';

                if ($nome == '') {
                    continue;
                }

                $personalidade =
                    $j['personalidade']
                    ?? 'Neutro';

                $pesoPorPersonalidade = [
                    'Estrategista' => 50,
                    'Explosivo' => 65,
                    'Planta' => 22,
                    'Manipulador' => 68,
                    'Emocional' => 55,
                    'Barraqueiro' => 70,
                    'Fofo' => 45,
                    'Líder Nato' => 62,
                    'Influencer' => 100,
                    'Falso' => 72,
                    'Neutro' => 50
                ];

                $pesosVT[$nome] =
                    max(
                        1,
                        ($pesoPorPersonalidade[
                            $personalidade
                        ] ?? 50)
                        +
                        rand(-8, 8)
                    );
            }

            $totalVT =
                array_sum($pesosVT);

            $nomeVT = '';

            if ($totalVT > 0) {

                $sorteioVT =
                    rand(1, $totalVT);

                $acumuladoVT = 0;

                foreach (
                    $pesosVT
                    as $nome => $peso
                ) {

                    $acumuladoVT +=
                        $peso;

                    if (
                        $sorteioVT <=
                        $acumuladoVT
                    ) {

                        $nomeVT =
                            $nome;

                        break;
                    }
                }
            }

            if ($nomeVT == '') {
                $nomeVT =
                    $nomes[
                        array_rand($nomes)
                    ];
            }

            $eventos[] =
                resolverVTAvancado(
                    $jogadores,
                    $nomeVT,
                    $meuNome
                );
        }
    }

    $_SESSION['jogadores'] =
        $jogadores;

    return $eventos;
}
