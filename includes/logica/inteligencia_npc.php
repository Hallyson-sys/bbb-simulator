<?php

/* =========================================================
   🧠 INTELIGÊNCIA CENTRAL DOS NPCs
   Personalidade + relações + alianças + romance + memória
   + popularidade + situação estratégica.
   ========================================================= */


/* =========================================================
   🧱 GARANTIR MEMÓRIA SOCIAL
   ========================================================= */

function garantirMemoriaNPC()
{
    if (
        !isset($_SESSION['memoria_npc']) ||
        !is_array($_SESSION['memoria_npc'])
    ) {
        $_SESSION['memoria_npc'] = [];
    }

    if (
        !isset($_SESSION['memoria_npc_eventos']) ||
        !is_array($_SESSION['memoria_npc_eventos'])
    ) {
        $_SESSION['memoria_npc_eventos'] = [];
    }

    if (
        !isset($_SESSION['ultimas_decisoes_npc']) ||
        !is_array($_SESSION['ultimas_decisoes_npc'])
    ) {
        $_SESSION['ultimas_decisoes_npc'] = [];
    }
}


/* =========================================================
   🧠 PERFIL ESTRATÉGICO POR PERSONALIDADE
   ========================================================= */

function perfilInteligenciaNPC($personalidade)
{
    $perfis = [

        'Estrategista' => [
            'relacao' => 1.00,
            'rivalidade' => 1.00,
            'ameaca' => 1.65,
            'grupo' => 1.65,
            'memoria' => 1.05,
            'lealdade' => 1.35,
            'romance' => 0.85,
            'alvo_facil' => 0.35,
            'aleatorio' => 6
        ],

        'Explosivo' => [
            'relacao' => 1.25,
            'rivalidade' => 1.75,
            'ameaca' => 0.35,
            'grupo' => 0.55,
            'memoria' => 1.60,
            'lealdade' => 0.75,
            'romance' => 0.90,
            'alvo_facil' => 0.80,
            'aleatorio' => 15
        ],

        'Planta' => [
            'relacao' => 0.70,
            'rivalidade' => 0.65,
            'ameaca' => 0.25,
            'grupo' => 1.75,
            'memoria' => 0.65,
            'lealdade' => 1.35,
            'romance' => 1.15,
            'alvo_facil' => 1.20,
            'aleatorio' => 10
        ],

        'Manipulador' => [
            'relacao' => 0.75,
            'rivalidade' => 0.90,
            'ameaca' => 1.85,
            'grupo' => 1.30,
            'memoria' => 0.85,
            'lealdade' => 0.70,
            'romance' => 0.55,
            'alvo_facil' => 0.45,
            'aleatorio' => 8
        ],

        'Emocional' => [
            'relacao' => 1.40,
            'rivalidade' => 1.20,
            'ameaca' => 0.25,
            'grupo' => 0.75,
            'memoria' => 1.75,
            'lealdade' => 1.35,
            'romance' => 1.75,
            'alvo_facil' => 0.65,
            'aleatorio' => 12
        ],

        'Barraqueiro' => [
            'relacao' => 1.20,
            'rivalidade' => 1.90,
            'ameaca' => 0.30,
            'grupo' => 0.45,
            'memoria' => 1.55,
            'lealdade' => 0.60,
            'romance' => 0.75,
            'alvo_facil' => 0.80,
            'aleatorio' => 16
        ],

        'Fofo' => [
            'relacao' => 1.25,
            'rivalidade' => 0.65,
            'ameaca' => 0.15,
            'grupo' => 1.05,
            'memoria' => 0.95,
            'lealdade' => 1.70,
            'romance' => 1.70,
            'alvo_facil' => 0.85,
            'aleatorio' => 9
        ],

        'Líder Nato' => [
            'relacao' => 1.00,
            'rivalidade' => 1.00,
            'ameaca' => 1.30,
            'grupo' => 1.45,
            'memoria' => 1.00,
            'lealdade' => 1.20,
            'romance' => 0.85,
            'alvo_facil' => 0.50,
            'aleatorio' => 7
        ],

        'Influencer' => [
            'relacao' => 0.90,
            'rivalidade' => 0.90,
            'ameaca' => 1.00,
            'grupo' => 0.95,
            'memoria' => 0.85,
            'lealdade' => 0.95,
            'romance' => 1.15,
            'alvo_facil' => 0.75,
            'aleatorio' => 12
        ],

        'Falso' => [
            'relacao' => 0.65,
            'rivalidade' => 0.85,
            'ameaca' => 1.55,
            'grupo' => 1.15,
            'memoria' => 0.80,
            'lealdade' => 0.45,
            'romance' => 0.50,
            'alvo_facil' => 0.55,
            'aleatorio' => 10
        ],

        'Neutro' => [
            'relacao' => 1.00,
            'rivalidade' => 1.00,
            'ameaca' => 0.70,
            'grupo' => 1.00,
            'memoria' => 1.00,
            'lealdade' => 1.00,
            'romance' => 1.00,
            'alvo_facil' => 0.80,
            'aleatorio' => 10
        ]
    ];

    return $perfis[$personalidade]
        ?? $perfis['Neutro'];
}


/* =========================================================
   🔎 BUSCAR PARTICIPANTE
   ========================================================= */

function buscarParticipanteInteligenciaNPC(
    $jogadores,
    $nome
) {
    foreach ($jogadores as $j) {
        if (
            function_exists('nomeIgual')
                ? nomeIgual(
                    $j['nome'] ?? '',
                    $nome
                )
                : (($j['nome'] ?? '') === $nome)
        ) {
            return $j;
        }
    }

    return null;
}


/* =========================================================
   🧠 REGISTRAR MEMÓRIA
   O "observador" é quem guardará a lembrança sobre o "alvo".
   Ex.: Camila foi indicada por Nathan:
   observador = Camila / alvo = Nathan / tipo = me_indicou
   ========================================================= */

function registrarMemoriaSocialNPC(
    $observador,
    $alvo,
    $tipo,
    $forca = 1,
    $descricao = '',
    $chaveUnica = ''
) {
    garantirMemoriaNPC();

    $observador = trim((string)$observador);
    $alvo = trim((string)$alvo);
    $tipo = trim((string)$tipo);
    $forca = max(1, (int)$forca);

    if (
        $observador === '' ||
        $alvo === '' ||
        $tipo === '' ||
        (
            function_exists('nomeIgual') &&
            nomeIgual($observador, $alvo)
        )
    ) {
        return false;
    }

    if ($chaveUnica !== '') {
        if (
            !empty(
                $_SESSION[
                    'memoria_npc_eventos'
                ][$chaveUnica]
            )
        ) {
            return false;
        }

        $_SESSION[
            'memoria_npc_eventos'
        ][$chaveUnica] = true;
    }

    if (
        !isset(
            $_SESSION[
                'memoria_npc'
            ][$observador]
        )
    ) {
        $_SESSION[
            'memoria_npc'
        ][$observador] = [];
    }

    if (
        !isset(
            $_SESSION[
                'memoria_npc'
            ][$observador][$alvo]
        )
    ) {
        $_SESSION[
            'memoria_npc'
        ][$observador][$alvo] = [
            'votou_em_mim' => 0,
            'me_indicou' => 0,
            'me_colocou_monstro' => 0,
            'me_atacou_discordia' => 0,
            'rompeu_comigo' => 0,
            'brigou_comigo' => 0,
            'espalhou_fofoca' => 0,
            'me_puxou_contragolpe' => 0,
            'me_colocou_paredao' => 0,

            'me_imunizou' => 0,
            'me_colocou_vip' => 0,
            'me_defendeu' => 0,
            'me_salvou' => 0,
            'me_aproximou' => 0,
            'flertou_comigo' => 0,

            'ultima_rodada' => 0,
            'historico' => []
        ];
    }

    $memoria =&
        $_SESSION[
            'memoria_npc'
        ][$observador][$alvo];

    if (!isset($memoria[$tipo])) {
        $memoria[$tipo] = 0;
    }

    $memoria[$tipo] += $forca;
    $memoria['ultima_rodada'] =
        (int)($_SESSION['rodada'] ?? 1);

    if ($descricao !== '') {
        $memoria['historico'][] = [
            'rodada' =>
                (int)($_SESSION['rodada'] ?? 1),
            'tipo' => $tipo,
            'forca' => $forca,
            'descricao' => $descricao
        ];

        if (
            count($memoria['historico']) > 12
        ) {
            $memoria['historico'] =
                array_slice(
                    $memoria['historico'],
                    -12
                );
        }
    }

    unset($memoria);

    return true;
}


/* =========================================================
   🧠 OBTER MEMÓRIA
   ========================================================= */

function obterMemoriaSocialNPC(
    $observador,
    $alvo
) {
    garantirMemoriaNPC();

    return
        $_SESSION[
            'memoria_npc'
        ][$observador][$alvo]
        ?? [];
}


/* =========================================================
   🧮 MEMÓRIA COM ESQUECIMENTO GRADUAL

   Eventos leves perdem força mais rápido.
   Eventos fortes (indicação, traição, contra-golpe)
   permanecem relevantes por mais rodadas.
   ========================================================= */

function pesoBaseMemoriaNPC($tipo)
{
    $pesos = [
        /* Negativos */
        'votou_em_mim' => 16,
        'me_indicou' => 34,
        'me_colocou_monstro' => 22,
        'me_atacou_discordia' => 19,
        'rompeu_comigo' => 28,
        'brigou_comigo' => 18,
        'espalhou_fofoca' => 24,
        'me_puxou_contragolpe' => 32,
        'me_colocou_paredao' => 30,

        /* Positivos */
        'me_imunizou' => 28,
        'me_colocou_vip' => 13,
        'me_defendeu' => 17,
        'me_salvou' => 25,
        'me_aproximou' => 10,
        'flertou_comigo' => 8
    ];

    return (float)($pesos[$tipo] ?? 0);
}


function taxaRetencaoMemoriaNPC($tipo)
{
    /*
     * Quanto maior, mais lentamente o NPC esquece.
     */
    $fortes = [
        'me_indicou',
        'rompeu_comigo',
        'me_puxou_contragolpe',
        'me_colocou_paredao',
        'me_imunizou',
        'me_salvou'
    ];

    $medios = [
        'votou_em_mim',
        'me_colocou_monstro',
        'me_atacou_discordia',
        'espalhou_fofoca',
        'me_colocou_vip',
        'me_defendeu'
    ];

    if (in_array($tipo, $fortes, true)) {
        return 0.90;
    }

    if (in_array($tipo, $medios, true)) {
        return 0.82;
    }

    return 0.72;
}


function fatorEsquecimentoMemoriaNPC(
    $tipo,
    $rodadaEvento,
    $rodadaAtual = null
) {
    if ($rodadaAtual === null) {
        $rodadaAtual =
            (int)($_SESSION['rodada'] ?? 1);
    }

    $idade = max(
        0,
        (int)$rodadaAtual -
        (int)$rodadaEvento
    );

    $taxa =
        taxaRetencaoMemoriaNPC($tipo);

    return max(
        0.05,
        pow($taxa, $idade)
    );
}


function impactoMemoriaHistoricaNPC(
    $observador,
    $alvo,
    $tipos
) {
    $m =
        obterMemoriaSocialNPC(
            $observador,
            $alvo
        );

    $historico =
        $m['historico'] ?? [];

    $total = 0.0;

    if (
        is_array($historico) &&
        !empty($historico)
    ) {
        foreach ($historico as $evento) {
            $tipo =
                $evento['tipo'] ?? '';

            if (
                !in_array(
                    $tipo,
                    $tipos,
                    true
                )
            ) {
                continue;
            }

            $forca =
                max(
                    1,
                    (int)($evento['forca'] ?? 1)
                );

            $rodadaEvento =
                (int)($evento['rodada'] ?? 1);

            $total +=
                pesoBaseMemoriaNPC($tipo)
                * $forca
                * fatorEsquecimentoMemoriaNPC(
                    $tipo,
                    $rodadaEvento
                );
        }

        return $total;
    }

    /*
     * Compatibilidade com saves da V1:
     * se ainda não existir histórico detalhado,
     * usa os contadores agregados.
     */
    $ultimaRodada =
        (int)($m['ultima_rodada'] ?? 1);

    foreach ($tipos as $tipo) {
        $quantidade =
            (int)($m[$tipo] ?? 0);

        if ($quantidade <= 0) {
            continue;
        }

        $total +=
            pesoBaseMemoriaNPC($tipo)
            * $quantidade
            * fatorEsquecimentoMemoriaNPC(
                $tipo,
                $ultimaRodada
            );
    }

    return $total;
}


function impactoNegativoMemoriaNPC(
    $observador,
    $alvo
) {
    return impactoMemoriaHistoricaNPC(
        $observador,
        $alvo,
        [
            'votou_em_mim',
            'me_indicou',
            'me_colocou_monstro',
            'me_atacou_discordia',
            'rompeu_comigo',
            'brigou_comigo',
            'espalhou_fofoca',
            'me_puxou_contragolpe',
            'me_colocou_paredao'
        ]
    );
}


function impactoPositivoMemoriaNPC(
    $observador,
    $alvo
) {
    return impactoMemoriaHistoricaNPC(
        $observador,
        $alvo,
        [
            'me_imunizou',
            'me_colocou_vip',
            'me_defendeu',
            'me_salvou',
            'me_aproximou',
            'flertou_comigo'
        ]
    );
}


/* =========================================================
   💕 ROMANCE ENTRE DOIS PARTICIPANTES
   ========================================================= */

function romanceInteligenciaNPC(
    $jogadores,
    $nomeA,
    $nomeB
) {
    if (!function_exists('obterRomance')) {
        return 0;
    }

    return max(
        (int)obterRomance(
            $jogadores,
            $nomeA,
            $nomeB
        ),
        (int)obterRomance(
            $jogadores,
            $nomeB,
            $nomeA
        )
    );
}


/* =========================================================
   🎭 DEFINIR SE O CONTEXTO PROCURA ALVO POSITIVO
   ========================================================= */

function contextoPositivoNPC($contexto)
{
    return in_array(
        $contexto,
        [
            'vip',
            'imunidade',
            'curinga_imunidade',
            'salvar_paredao',
            'festa_aliado',
            'discordia_aliado',
            'podio',
            'interacao_aliado'
        ],
        true
    );
}


/* =========================================================
   🚫 VALIDAR ALVO
   ========================================================= */

function alvoValidoInteligenciaNPC(
    $jogadores,
    $npc,
    $alvo,
    $contexto,
    $bloqueados = []
) {
    if (
        $alvo === '' ||
        (
            function_exists('nomeIgual') &&
            nomeIgual($npc, $alvo)
        )
    ) {
        return false;
    }

    foreach ($bloqueados as $bloqueado) {
        if (
            $bloqueado !== '' &&
            (
                function_exists('nomeIgual')
                    ? nomeIgual($bloqueado, $alvo)
                    : $bloqueado === $alvo
            )
        ) {
            return false;
        }
    }

    $jogador =
        buscarParticipanteInteligenciaNPC(
            $jogadores,
            $alvo
        );

    if (!$jogador) {
        return false;
    }

    /*
     * Em decisões de ataque/voto, Líder e imunes
     * não são alvos válidos.
     */
    if (
        in_array(
            $contexto,
            [
                'voto',
                'indicacao_lider',
                'indicacao_bigfone'
            ],
            true
        )
    ) {
        if (
            !empty(
                $jogador['status']['lider']
            )
        ) {
            return false;
        }

        if (
            function_exists('estaImune') &&
            estaImune(
                $jogadores,
                $alvo
            )
        ) {
            return false;
        }
    }

    return true;
}


/* =========================================================
   🧮 PONTUAR UM ALVO
   Quanto maior o score, mais provável a escolha.
   ========================================================= */

function pontuarAlvoNPCInteligente(
    $jogadores,
    $npc,
    $alvo,
    $contexto = 'voto',
    $bloqueados = []
) {
    if (
        !alvoValidoInteligenciaNPC(
            $jogadores,
            $npc,
            $alvo,
            $contexto,
            $bloqueados
        )
    ) {
        return -999999;
    }

    $meuNome =
        $_SESSION['meu_nome'] ?? '';

    $dadosNPC =
        buscarParticipanteInteligenciaNPC(
            $jogadores,
            $npc
        );

    $dadosAlvo =
        buscarParticipanteInteligenciaNPC(
            $jogadores,
            $alvo
        );

    if (
        !$dadosNPC ||
        !$dadosAlvo
    ) {
        return -999999;
    }

    $personalidade =
        $dadosNPC['personalidade']
        ?? 'Neutro';

    $perfil =
        perfilInteligenciaNPC(
            $personalidade
        );

    $relacao =
        function_exists(
            'obterRelacaoCompleta'
        )
        ? obterRelacaoCompleta(
            $jogadores,
            $npc,
            $alvo,
            $meuNome
        )
        : [
            'amizade' => 0,
            'rivalidade' => 0,
            'confianca' => 0,
            'score' => 0
        ];

    $amizade =
        (float)($relacao['amizade'] ?? 0);

    $rivalidade =
        (float)($relacao['rivalidade'] ?? 0);

    $confianca =
        (float)($relacao['confianca'] ?? 0);

    $scoreRelacao =
        (float)($relacao['score'] ?? 0);

    $popularidade =
        (float)($dadosAlvo['popularidade'] ?? 50);

    $romance =
        romanceInteligenciaNPC(
            $jogadores,
            $npc,
            $alvo
        );

    $mesmaAlianca =
        function_exists('mesmaAliancaNomes')
        &&
        mesmaAliancaNomes(
            $jogadores,
            $npc,
            $alvo
        );

    $aliadoSocial =
        function_exists('saoAliados')
        &&
        saoAliados(
            $jogadores,
            $npc,
            $alvo,
            $meuNome
        );

    $rivalSocial =
        function_exists('saoRivais')
        &&
        saoRivais(
            $jogadores,
            $npc,
            $alvo,
            $meuNome
        );

    $namorando =
        function_exists('estaNamorandoCom')
        &&
        (
            estaNamorandoCom(
                $npc,
                $alvo
            )
            ||
            estaNamorandoCom(
                $alvo,
                $npc
            )
        );

    $negMemoria =
        impactoNegativoMemoriaNPC(
            $npc,
            $alvo
        );

    $posMemoria =
        impactoPositivoMemoriaNPC(
            $npc,
            $alvo
        );

    $positivo =
        contextoPositivoNPC(
            $contexto
        );

    /* =====================================================
       🤝 CONTEXTOS POSITIVOS
       VIP, imunidade, aliado e pódio.
       ===================================================== */

    if ($positivo) {
        $score = 30;

        $score +=
            max(0, $scoreRelacao)
            * 0.65
            * $perfil['relacao'];

        $score +=
            max(0, $amizade)
            * 0.32
            * $perfil['lealdade'];

        $score +=
            max(0, $confianca)
            * 0.42
            * $perfil['lealdade'];

        if ($aliadoSocial) {
            $score +=
                28
                * $perfil['lealdade'];
        }

        if ($mesmaAlianca) {
            $score +=
                42
                * $perfil['grupo'];
        }

        if ($romance > 0) {
            $score +=
                $romance
                * 0.55
                * $perfil['romance'];
        }

        if ($namorando) {
            $score +=
                65
                * $perfil['romance'];
        }

        $score +=
            $posMemoria
            * $perfil['memoria'];

        $score -=
            $negMemoria
            * 0.65
            * $perfil['memoria'];

        /*
         * Estrategistas e Líderes Natos valorizam
         * aliados fortes, mas sem transformar
         * popularidade no único critério.
         */
        if (
            in_array(
                $personalidade,
                [
                    'Estrategista',
                    'Líder Nato',
                    'Manipulador'
                ],
                true
            )
        ) {
            $score +=
                max(
                    0,
                    $popularidade - 50
                )
                * 0.20;
        }

        if (
            in_array(
                $contexto,
                [
                    'imunidade',
                    'curinga_imunidade',
                    'salvar_paredao'
                ],
                true
            )
        ) {
            $score +=
                $romance
                * 0.25
                * $perfil['romance'];

            if ($mesmaAlianca) {
                $score += 18;
            }

            /*
             * Ao salvar alguém do Paredão, NPCs mais
             * estratégicos também protegem aliados fortes.
             */
            if ($contexto === 'salvar_paredao') {
                $score +=
                    max(
                        0,
                        $popularidade - 55
                    )
                    * 0.18
                    * $perfil['grupo'];
            }
        }

        if (
            $contexto === 'podio' &&
            $mesmaAlianca
        ) {
            $score += 15;
        }

        $score +=
            rand(
                0,
                (int)$perfil['aleatorio']
            );

        return $score;
    }


    /* =====================================================
       ⚔️ CONTEXTOS NEGATIVOS / ESTRATÉGICOS
       ===================================================== */

    $score = 45;

    /*
     * Relação ruim pesa bastante.
     */
    $score +=
        max(0, -$scoreRelacao)
        * 0.80
        * $perfil['relacao'];

    $score +=
        max(0, $rivalidade)
        * 0.70
        * $perfil['rivalidade'];

    /*
     * Amizade e confiança protegem.
     */
    $score -=
        max(0, $amizade)
        * 0.28
        * $perfil['lealdade'];

    $score -=
        max(0, $confianca)
        * 0.30
        * $perfil['lealdade'];

    if ($rivalSocial) {
        $score +=
            35
            * $perfil['rivalidade'];
    }

    if ($aliadoSocial) {
        $score -=
            28
            * $perfil['lealdade'];
    }

    /*
     * Aliança oficial protege, mas Manipulador/Falso
     * podem considerar uma traição contra ameaça enorme.
     */
    if ($mesmaAlianca) {
        $penalidadeAlianca =
            65
            * $perfil['lealdade'];

        if (
            in_array(
                $personalidade,
                ['Manipulador', 'Falso'],
                true
            )
            &&
            $popularidade >= 82
            &&
            (int)($_SESSION['rodada'] ?? 1) >= 4
        ) {
            $penalidadeAlianca *= 0.35;
        }

        $score -=
            $penalidadeAlianca;
    }

    /*
     * Romance/namoro oferece proteção extra.
     */
    if ($romance > 0) {
        $score -=
            $romance
            * 0.55
            * $perfil['romance'];
    }

    if ($namorando) {
        $score -=
            90
            * $perfil['romance'];
    }

    /*
     * Memória: vingança e gratidão.
     */
    $score +=
        $negMemoria
        * $perfil['memoria'];

    $score -=
        $posMemoria
        * 0.80
        * $perfil['memoria'];

    /*
     * Ameaça ao prêmio:
     * estrategistas, manipuladores e líderes natos
     * observam popularidade alta.
     */
    $score +=
        max(
            0,
            $popularidade - 55
        )
        * 0.80
        * $perfil['ameaca'];

    /*
     * Outros perfis tendem a acompanhar alvos
     * já fragilizados/rejeitados.
     */
    $score +=
        max(
            0,
            50 - $popularidade
        )
        * 0.45
        * $perfil['alvo_facil'];

    /*
     * Alvo combinado de aliança.
     */
    $aliancaNPC =
        $dadosNPC['alianca'] ?? null;

    if (
        !empty($aliancaNPC) &&
        function_exists(
            'alvoCombinadoDaAlianca'
        ) &&
        in_array(
            $contexto,
            [
                'voto',
                'indicacao_lider',
                'indicacao_bigfone'
            ],
            true
        )
    ) {
        $alvoGrupo =
            alvoCombinadoDaAlianca(
                $jogadores,
                $aliancaNPC,
                array_values(
                    array_unique(
                        array_merge(
                            $bloqueados,
                            [$npc]
                        )
                    )
                )
            );

        if (
            $alvoGrupo !== null &&
            (
                function_exists('nomeIgual')
                    ? nomeIgual(
                        $alvoGrupo,
                        $alvo
                    )
                    : $alvoGrupo === $alvo
            )
        ) {
            $score +=
                36
                * $perfil['grupo'];
        }
    }

    /*
     * Contextos específicos.
     */
    if (
        $contexto === 'monstro'
    ) {
        $score +=
            $rivalidade
            * 0.30;

        $score +=
            $negMemoria
            * 0.25;
    }

    if (
        $contexto === 'discordia_negativo'
    ) {
        $score +=
            $rivalidade
            * 0.45
            * $perfil['rivalidade'];

        $score +=
            max(
                0,
                -$scoreRelacao
            )
            * 0.30;
    }

    if (
        in_array(
            $contexto,
            [
                'indicacao_lider',
                'indicacao_bigfone'
            ],
            true
        )
    ) {
        $score +=
            max(
                0,
                $popularidade - 60
            )
            * 0.30
            * $perfil['ameaca'];
    }

    /*
     * Monstro e Xepa podem deixar a pessoa
     * mais exposta na votação.
     */
    if (
        $contexto === 'voto' &&
        !empty(
            $dadosAlvo['status']['monstro']
        )
    ) {
        $score += 8;
    }

    if (
        $contexto === 'voto' &&
        !empty(
            $dadosAlvo['status']['xepa']
        )
    ) {
        $score += 3;
    }

    /*
     * Poderes e acontecimentos especiais.
     */
    if ($contexto === 'anular_voto') {
        $score +=
            max(0, 45 - $confianca)
            * 0.35;

        $score +=
            max(0, $popularidade - 60)
            * 0.25
            * $perfil['ameaca'];
    }

    if ($contexto === 'espiar_voto') {
        $score +=
            max(0, 55 - $confianca)
            * 0.45;

        $score +=
            max(0, $popularidade - 55)
            * 0.30
            * $perfil['ameaca'];
    }

    if ($contexto === 'contra_golpe') {
        $score +=
            $rivalidade
            * 0.45
            * $perfil['rivalidade'];

        $score +=
            $negMemoria
            * 0.30;
    }

    if ($contexto === 'troca_emparedado') {
        $score +=
            max(0, $popularidade - 55)
            * 0.55
            * $perfil['ameaca'];

        $score +=
            $rivalidade
            * 0.25;
    }

    if ($contexto === 'fofoca') {
        $score +=
            max(0, 55 - $confianca)
            * 0.35;

        $score +=
            $rivalidade
            * 0.35
            * $perfil['rivalidade'];

        $score +=
            max(0, $popularidade - 60)
            * 0.20
            * $perfil['ameaca'];
    }

    if ($contexto === 'festa_rival') {
        $score +=
            $rivalidade
            * 0.35;

        $score +=
            $negMemoria
            * 0.20;
    }

    $score +=
        rand(
            0,
            (int)$perfil['aleatorio']
        );

    return $score;
}


/* =========================================================
   📊 RANQUEAR ALVOS
   ========================================================= */

function ranquearAlvosNPCInteligente(
    $jogadores,
    $npc,
    $contexto = 'voto',
    $bloqueados = []
) {
    $ranking = [];

    foreach ($jogadores as $j) {
        $alvo =
            $j['nome'] ?? '';

        if ($alvo === '') {
            continue;
        }

        $score =
            pontuarAlvoNPCInteligente(
                $jogadores,
                $npc,
                $alvo,
                $contexto,
                $bloqueados
            );

        if ($score <= -999000) {
            continue;
        }

        $ranking[$alvo] =
            round(
                $score,
                2
            );
    }

    arsort($ranking);

    return $ranking;
}


/* =========================================================
   🧠 REGISTRAR DECISÃO PARA DEPURAÇÃO / TCC
   ========================================================= */

function registrarDecisaoNPC(
    $npc,
    $contexto,
    $escolhido,
    $ranking
) {
    garantirMemoriaNPC();

    $_SESSION['ultimas_decisoes_npc'][] = [
        'rodada' =>
            (int)($_SESSION['rodada'] ?? 1),
        'npc' => $npc,
        'contexto' => $contexto,
        'escolhido' => $escolhido,
        'top' =>
            array_slice(
                $ranking,
                0,
                3,
                true
            )
    ];

    if (
        count(
            $_SESSION['ultimas_decisoes_npc']
        ) > 50
    ) {
        $_SESSION['ultimas_decisoes_npc'] =
            array_slice(
                $_SESSION['ultimas_decisoes_npc'],
                -50
            );
    }
}


/* =========================================================
   🎯 ESCOLHER UM ALVO
   ========================================================= */

function escolherAlvoNPCInteligente(
    $jogadores,
    $npc,
    $contexto = 'voto',
    $bloqueados = []
) {
    $ranking =
        ranquearAlvosNPCInteligente(
            $jogadores,
            $npc,
            $contexto,
            $bloqueados
        );

    if (empty($ranking)) {
        return null;
    }

    $nomes =
        array_keys($ranking);

    /*
     * Na maior parte das vezes escolhe o primeiro.
     * Uma pequena margem permite decisões menos robóticas.
     */
    $escolhido = $nomes[0];

    if (
        count($nomes) >= 2 &&
        rand(1, 100) <= 18
    ) {
        $primeiroScore =
            (float)$ranking[$nomes[0]];

        $segundoScore =
            (float)$ranking[$nomes[1]];

        /*
         * Só troca para o segundo colocado
         * quando os scores estão relativamente próximos.
         */
        if (
            abs(
                $primeiroScore -
                $segundoScore
            ) <= 18
        ) {
            $escolhido =
                $nomes[1];
        }
    }

    registrarDecisaoNPC(
        $npc,
        $contexto,
        $escolhido,
        $ranking
    );

    return $escolhido;
}


/* =========================================================
   👥 ESCOLHER VÁRIOS ALVOS
   ========================================================= */

function escolherVariosAlvosNPCInteligentes(
    $jogadores,
    $npc,
    $contexto,
    $quantidade,
    $bloqueados = []
) {
    $quantidade =
        max(
            0,
            (int)$quantidade
        );

    if ($quantidade <= 0) {
        return [];
    }

    $ranking =
        ranquearAlvosNPCInteligente(
            $jogadores,
            $npc,
            $contexto,
            $bloqueados
        );

    $escolhidos =
        array_slice(
            array_keys($ranking),
            0,
            $quantidade
        );

    if (!empty($escolhidos)) {
        registrarDecisaoNPC(
            $npc,
            $contexto,
            implode(', ', $escolhidos),
            $ranking
        );
    }

    return $escolhidos;
}


/* =========================================================
   💬 ALVO PARA INTERAÇÕES
   Decide se o NPC tende a procurar aliado ou rival
   de acordo com personalidade e momento.
   ========================================================= */

function escolherAlvoInteracaoNPC(
    $jogadores,
    $npc,
    $meuNome = ''
) {
    $dadosNPC =
        buscarParticipanteInteligenciaNPC(
            $jogadores,
            $npc
        );

    if (!$dadosNPC) {
        return null;
    }

    $personalidade =
        $dadosNPC['personalidade']
        ?? 'Neutro';

    $tendencias = [
        'Estrategista' => ['aliado' => 60, 'rival' => 40],
        'Explosivo' => ['aliado' => 25, 'rival' => 75],
        'Planta' => ['aliado' => 75, 'rival' => 25],
        'Manipulador' => ['aliado' => 55, 'rival' => 45],
        'Emocional' => ['aliado' => 60, 'rival' => 40],
        'Barraqueiro' => ['aliado' => 20, 'rival' => 80],
        'Fofo' => ['aliado' => 85, 'rival' => 15],
        'Líder Nato' => ['aliado' => 60, 'rival' => 40],
        'Influencer' => ['aliado' => 55, 'rival' => 45],
        'Falso' => ['aliado' => 50, 'rival' => 50],
        'Neutro' => ['aliado' => 50, 'rival' => 50]
    ];

    $t =
        $tendencias[$personalidade]
        ?? $tendencias['Neutro'];

    $contexto =
        rand(1, 100)
        <= $t['rival']
        ? 'discordia_negativo'
        : 'interacao_aliado';

    return
        escolherAlvoNPCInteligente(
            $jogadores,
            $npc,
            $contexto,
            [$npc]
        );
}


/* =========================================================
   🎯 ESCOLHER ENTRE CANDIDATOS JÁ VALIDADOS
   Útil para Big Fone, Curinga e trocas de Paredão.
   ========================================================= */

function escolherEntreCandidatosNPCInteligente(
    $jogadores,
    $npc,
    $contexto,
    $candidatos
) {
    $candidatos =
        array_values(
            array_unique(
                array_filter(
                    $candidatos,
                    function ($nome) {
                        return trim((string)$nome) !== '';
                    }
                )
            )
        );

    if (empty($candidatos)) {
        return null;
    }

    $ranking = [];

    foreach ($candidatos as $alvo) {
        $score =
            pontuarAlvoNPCInteligente(
                $jogadores,
                $npc,
                $alvo,
                $contexto,
                []
            );

        if ($score <= -999000) {
            continue;
        }

        $ranking[$alvo] =
            round($score, 2);
    }

    if (empty($ranking)) {
        return
            $candidatos[
                array_rand($candidatos)
            ];
    }

    arsort($ranking);

    $nomes =
        array_keys($ranking);

    $escolhido =
        $nomes[0];

    /*
     * Preserva uma pequena imprevisibilidade quando
     * os dois melhores candidatos têm pontuações próximas.
     */
    if (
        count($nomes) >= 2 &&
        rand(1, 100) <= 15 &&
        abs(
            (float)$ranking[$nomes[0]]
            -
            (float)$ranking[$nomes[1]]
        ) <= 15
    ) {
        $escolhido =
            $nomes[1];
    }

    registrarDecisaoNPC(
        $npc,
        $contexto,
        $escolhido,
        $ranking
    );

    return $escolhido;
}


/* =========================================================
   🛡️ ESCOLHER QUEM PROTEGER
   Pode decidir proteger a si mesmo quando a regra permitir.
   ========================================================= */

function escolherProtegidoNPCInteligente(
    $jogadores,
    $npc,
    $candidatos,
    $permitirAuto = true
) {
    $candidatos =
        array_values(
            array_unique(
                $candidatos
            )
        );

    if (empty($candidatos)) {
        return null;
    }

    $dadosNPC =
        buscarParticipanteInteligenciaNPC(
            $jogadores,
            $npc
        );

    $personalidade =
        $dadosNPC['personalidade']
        ?? 'Neutro';

    $chanceAutoPorPerfil = [
        'Estrategista' => 58,
        'Explosivo' => 52,
        'Planta' => 68,
        'Manipulador' => 72,
        'Emocional' => 45,
        'Barraqueiro' => 50,
        'Fofo' => 30,
        'Líder Nato' => 55,
        'Influencer' => 58,
        'Falso' => 70,
        'Neutro' => 50
    ];

    $chanceAuto =
        $chanceAutoPorPerfil[$personalidade]
        ?? 50;

    $popularidadeNPC =
        (int)($dadosNPC['popularidade'] ?? 50);

    if ($popularidadeNPC <= 35) {
        $chanceAuto += 12;
    }

    if ($popularidadeNPC >= 75) {
        $chanceAuto -= 8;
    }

    $autoDisponivel = false;

    foreach ($candidatos as $candidato) {
        if (
            function_exists('nomeIgual')
                ? nomeIgual($candidato, $npc)
                : $candidato === $npc
        ) {
            $autoDisponivel = true;
            break;
        }
    }

    if (
        $permitirAuto &&
        $autoDisponivel &&
        rand(1, 100) <=
            max(10, min(90, $chanceAuto))
    ) {
        registrarDecisaoNPC(
            $npc,
            'curinga_imunidade',
            $npc,
            [$npc => 999]
        );

        return $npc;
    }

    $outros =
        array_values(
            array_filter(
                $candidatos,
                function ($nome) use ($npc) {
                    return function_exists('nomeIgual')
                        ? !nomeIgual($nome, $npc)
                        : $nome !== $npc;
                }
            )
        );

    if (empty($outros)) {
        return $autoDisponivel
            ? $npc
            : null;
    }

    return escolherEntreCandidatosNPCInteligente(
        $jogadores,
        $npc,
        'curinga_imunidade',
        $outros
    );
}


/* =========================================================
   ☎️ DISPOSIÇÃO PARA CORRER AO BIG FONE
   Retorna um peso, não uma decisão absoluta.
   ========================================================= */

function pesoAtenderBigFoneNPC(
    $jogadores,
    $nomeNPC
) {
    $dados =
        buscarParticipanteInteligenciaNPC(
            $jogadores,
            $nomeNPC
        );

    if (!$dados) {
        return 10;
    }

    $personalidade =
        $dados['personalidade']
        ?? 'Neutro';

    $pesos = [
        'Estrategista' => 70,
        'Explosivo' => 92,
        'Planta' => 28,
        'Manipulador' => 82,
        'Emocional' => 58,
        'Barraqueiro' => 95,
        'Fofo' => 48,
        'Líder Nato' => 84,
        'Influencer' => 90,
        'Falso' => 78,
        'Neutro' => 60
    ];

    $peso =
        $pesos[$personalidade]
        ?? 60;

    $popularidade =
        (int)($dados['popularidade'] ?? 50);

    /*
     * Quem está mal com o público tende a buscar
     * uma reviravolta; quem já está muito forte
     * pode ser um pouco mais cauteloso.
     */
    if ($popularidade <= 35) {
        $peso += 12;
    } elseif ($popularidade >= 80) {
        $peso -= 8;
    }

    return max(
        5,
        $peso + rand(-8, 8)
    );
}


/* =========================================================
   🎬 PERFIL DE VT DO NPC
   ========================================================= */

function pesosVTNPCInteligente(
    $personalidade
) {
    $base = [
        'emocionante' => 15,
        'engracado' => 18,
        'forcado' => 10,
        'vilao' => 12,
        'vitima' => 10,
        'protagonista' => 18
    ];

    $ajustes = [
        'Estrategista' => [
            'protagonista' => 16,
            'vilao' => 8
        ],
        'Explosivo' => [
            'vilao' => 20,
            'engracado' => 10,
            'forcado' => 5
        ],
        'Planta' => [
            'forcado' => 18,
            'vitima' => 8,
            'protagonista' => -8
        ],
        'Manipulador' => [
            'vilao' => 24,
            'protagonista' => 12
        ],
        'Emocional' => [
            'emocionante' => 26,
            'vitima' => 18
        ],
        'Barraqueiro' => [
            'vilao' => 24,
            'engracado' => 12
        ],
        'Fofo' => [
            'emocionante' => 20,
            'engracado' => 14,
            'vilao' => -8
        ],
        'Líder Nato' => [
            'protagonista' => 24,
            'emocionante' => 10
        ],
        'Influencer' => [
            'protagonista' => 26,
            'engracado' => 22,
            'forcado' => 8
        ],
        'Falso' => [
            'vilao' => 18,
            'forcado' => 16,
            'protagonista' => 10
        ]
    ];

    foreach (
        ($ajustes[$personalidade] ?? [])
        as $tipo => $ajuste
    ) {
        $base[$tipo] =
            max(
                1,
                ($base[$tipo] ?? 1)
                + $ajuste
            );
    }

    return $base;
}


function escolherTipoVTNPCInteligente(
    $personalidade
) {
    $pesos =
        pesosVTNPCInteligente(
            $personalidade
        );

    $total =
        array_sum($pesos);

    if ($total <= 0) {
        return 'protagonista';
    }

    $sorteio =
        rand(1, $total);

    $acumulado = 0;

    foreach ($pesos as $tipo => $peso) {
        $acumulado += $peso;

        if ($sorteio <= $acumulado) {
            return $tipo;
        }
    }

    return array_key_first($pesos);
}
