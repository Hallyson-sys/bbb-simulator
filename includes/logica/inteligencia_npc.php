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

    /* Memória curta para evitar ações e alvos repetitivos. */
    if (
        !isset($_SESSION['historico_acoes_npc']) ||
        !is_array($_SESSION['historico_acoes_npc'])
    ) {
        $_SESSION['historico_acoes_npc'] = [];
    }

    /*
     * Fase 2: acontecimentos que mudam o estado emocional/estratégico
     * do NPC por algumas rodadas.
     */
    if (
        !isset($_SESSION['memoria_perdas_npc']) ||
        !is_array($_SESSION['memoria_perdas_npc'])
    ) {
        $_SESSION['memoria_perdas_npc'] = [];
    }

    if (
        !isset($_SESSION['memoria_eliminacoes_processadas_npc']) ||
        !is_array($_SESSION['memoria_eliminacoes_processadas_npc'])
    ) {
        $_SESSION['memoria_eliminacoes_processadas_npc'] = [];
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
            'me_elogiou_discordia' => 0,
            'me_deu_emoji_positivo' => 0,
            'me_deu_emoji_negativo' => 0,
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
            'me_apoiou' => 0,
            'me_contou_segredo' => 0,
            'fechou_pacto_comigo' => 0,
            'me_pediu_desculpas' => 0,
            'cumpriu_tregua' => 0,

            'me_confrontou' => 0,
            'me_desrespeitou' => 0,
            'quebrou_tregua' => 0,
            'me_expos' => 0,

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
        'me_deu_emoji_negativo' => 12,
        'rompeu_comigo' => 28,
        'brigou_comigo' => 18,
        'espalhou_fofoca' => 24,
        'me_puxou_contragolpe' => 32,
        'me_colocou_paredao' => 30,
        'me_confrontou' => 18,
        'me_desrespeitou' => 16,
        'quebrou_tregua' => 26,
        'me_expos' => 22,

        /* Positivos */
        'me_elogiou_discordia' => 14,
        'me_deu_emoji_positivo' => 9,
        'me_imunizou' => 28,
        'me_colocou_vip' => 13,
        'me_defendeu' => 17,
        'me_salvou' => 25,
        'me_aproximou' => 10,
        'flertou_comigo' => 8,
        'me_apoiou' => 15,
        'me_contou_segredo' => 16,
        'fechou_pacto_comigo' => 24,
        'me_pediu_desculpas' => 18,
        'cumpriu_tregua' => 14
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
        'me_salvou',
        'quebrou_tregua',
        'fechou_pacto_comigo'
    ];

    $medios = [
        'votou_em_mim',
        'me_colocou_monstro',
        'me_atacou_discordia',
        'me_elogiou_discordia',
        'me_deu_emoji_positivo',
        'me_deu_emoji_negativo',
        'espalhou_fofoca',
        'me_colocou_vip',
        'me_defendeu',
        'me_confrontou',
        'me_desrespeitou',
        'me_expos',
        'me_apoiou',
        'me_contou_segredo',
        'me_pediu_desculpas',
        'cumpriu_tregua'
    ];

    if (in_array($tipo, $fortes, true)) {
        return 0.90;
    }

    if (in_array($tipo, $medios, true)) {
        return 0.82;
    }

    return 0.72;
}


function multiplicadorRetencaoPersonalidadeNPC($observador)
{
    $dados = buscarParticipanteInteligenciaNPC(
        $_SESSION['jogadores'] ?? [],
        $observador
    );

    $personalidade = $dados['personalidade'] ?? 'Neutro';

    $multiplicadores = [
        'Emocional' => 1.10,
        'Explosivo' => 1.08,
        'Barraqueiro' => 1.07,
        'Estrategista' => 1.04,
        'Líder Nato' => 1.03,
        'Fofo' => 1.02,
        'Manipulador' => 1.01,
        'Falso' => 0.99,
        'Influencer' => 0.97,
        'Planta' => 0.93,
        'Neutro' => 1.00
    ];

    return (float)($multiplicadores[$personalidade] ?? 1.00);
}


function fatorEsquecimentoMemoriaNPC(
    $tipo,
    $rodadaEvento,
    $rodadaAtual = null,
    $observador = ''
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

    $taxa = taxaRetencaoMemoriaNPC($tipo);

    if ($observador !== '') {
        $taxa *= multiplicadorRetencaoPersonalidadeNPC($observador);
        $taxa = min(0.965, max(0.58, $taxa));
    }

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
                    $rodadaEvento,
                    null,
                    $observador
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
                $ultimaRodada,
                null,
                $observador
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
            'me_deu_emoji_negativo',
            'rompeu_comigo',
            'brigou_comigo',
            'espalhou_fofoca',
            'me_puxou_contragolpe',
            'me_colocou_paredao',
            'me_confrontou',
            'me_desrespeitou',
            'quebrou_tregua',
            'me_expos'
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
            'me_elogiou_discordia',
            'me_deu_emoji_positivo',
            'me_imunizou',
            'me_colocou_vip',
            'me_defendeu',
            'me_salvou',
            'me_aproximou',
            'flertou_comigo',
            'me_apoiou',
            'me_contou_segredo',
            'fechou_pacto_comigo',
            'me_pediu_desculpas',
            'cumpriu_tregua'
        ]
    );
}


/* =========================================================
   🧠 ÚLTIMA MEMÓRIA MARCANTE ENTRE DUAS PESSOAS
   Usada pelos Eventos de Convivência para NPCs lembrarem
   de algo concreto em vez de reagirem apenas a números.
   ========================================================= */
function ultimaMemoriaMarcanteNPC($observador, $alvo, $tipo = 'qualquer')
{
    $m = obterMemoriaSocialNPC($observador, $alvo);
    $historico = $m['historico'] ?? [];

    if (!is_array($historico) || empty($historico)) return null;

    $negativos = [
        'votou_em_mim','me_indicou','me_colocou_monstro','me_atacou_discordia',
        'me_deu_emoji_negativo','rompeu_comigo','brigou_comigo','espalhou_fofoca',
        'me_puxou_contragolpe','me_colocou_paredao','me_confrontou',
        'me_desrespeitou','quebrou_tregua','me_expos'
    ];

    $positivos = [
        'me_elogiou_discordia','me_deu_emoji_positivo','me_imunizou','me_colocou_vip',
        'me_defendeu','me_salvou','me_aproximou','flertou_comigo','me_apoiou',
        'me_contou_segredo','fechou_pacto_comigo','me_pediu_desculpas','cumpriu_tregua'
    ];

    for ($i = count($historico) - 1; $i >= 0; $i--) {
        $evento = $historico[$i];
        $t = $evento['tipo'] ?? '';

        if ($tipo === 'negativa' && !in_array($t, $negativos, true)) continue;
        if ($tipo === 'positiva' && !in_array($t, $positivos, true)) continue;

        return $evento;
    }

    return null;
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
    sincronizarMemoriasAutomaticasNPC($jogadores);

    $ranking =
        ranquearAlvosNPCInteligente(
            $jogadores,
            $npc,
            $contexto,
            $bloqueados
        );

    $ranking = ajustarRankingAlvosPorMomentoNPC(
        $ranking,
        $jogadores,
        $npc,
        $contexto
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
    sincronizarMemoriasAutomaticasNPC($jogadores);

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

    $ranking = ajustarRankingAlvosPorMomentoNPC(
        $ranking,
        $jogadores,
        $npc,
        $contexto
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
   🎭 MEMÓRIA CURTA DE AÇÕES — NPCs 2.0
   ========================================================= */

function nomeAcaoInteracaoNPC($acao, $ehRival = false, $ehAliado = false)
{
    $acao = (int)$acao;
    if ($acao === 1) return 'conversar';
    if ($acao === 2) return $ehRival ? 'fofoca' : 'aproximacao';
    if ($acao === 3) return 'discutir';
    if ($acao === 4) return 'alianca';
    return 'outra';
}

function registrarAcaoRecenteNPC($npc, $acao, $alvo, $ehRival = false, $ehAliado = false)
{
    garantirMemoriaNPC();

    $npc = trim((string)$npc);
    $alvo = trim((string)$alvo);
    if ($npc === '' || $alvo === '') return;

    if (!isset($_SESSION['historico_acoes_npc'][$npc]) || !is_array($_SESSION['historico_acoes_npc'][$npc])) {
        $_SESSION['historico_acoes_npc'][$npc] = [];
    }

    $_SESSION['historico_acoes_npc'][$npc][] = [
        'rodada' => (int)($_SESSION['rodada'] ?? 1),
        'fase' => (string)($_SESSION['fase_semana'] ?? ''),
        'acao' => nomeAcaoInteracaoNPC($acao, $ehRival, $ehAliado),
        'alvo' => $alvo
    ];

    if (count($_SESSION['historico_acoes_npc'][$npc]) > 12) {
        $_SESSION['historico_acoes_npc'][$npc] = array_slice($_SESSION['historico_acoes_npc'][$npc], -12);
    }
}

function obterHistoricoAcoesNPC($npc, $limite = 6)
{
    garantirMemoriaNPC();
    $historico = $_SESSION['historico_acoes_npc'][$npc] ?? [];
    if (!is_array($historico)) return [];
    return array_slice($historico, -max(1, (int)$limite));
}

function contarAcaoRecenteNPC($npc, $acao, $limite = 6)
{
    $total = 0;
    foreach (obterHistoricoAcoesNPC($npc, $limite) as $item) {
        if (($item['acao'] ?? '') === $acao) $total++;
    }
    return $total;
}

function contarAlvoRecenteNPC($npc, $alvo, $limite = 5)
{
    $total = 0;
    foreach (obterHistoricoAcoesNPC($npc, $limite) as $item) {
        $historico = $item['alvo'] ?? '';
        $igual = function_exists('nomeIgual') ? nomeIgual($historico, $alvo) : ($historico === $alvo);
        if ($igual) $total++;
    }
    return $total;
}

function ultimaAcaoNPC($npc)
{
    $h = obterHistoricoAcoesNPC($npc, 1);
    return $h[0]['acao'] ?? '';
}

function ultimoAlvoNPC($npc)
{
    $h = obterHistoricoAcoesNPC($npc, 1);
    return $h[0]['alvo'] ?? '';
}

function sortearOpcaoPorPesoNPC($pesos)
{
    if (empty($pesos) || !is_array($pesos)) return null;

    $normalizados = [];
    $total = 0;
    foreach ($pesos as $chave => $peso) {
        $peso = max(1, (int)round($peso));
        $normalizados[$chave] = $peso;
        $total += $peso;
    }

    $sorteio = rand(1, max(1, $total));
    $acumulado = 0;
    foreach ($normalizados as $chave => $peso) {
        $acumulado += $peso;
        if ($sorteio <= $acumulado) return $chave;
    }
    return array_key_first($normalizados);
}

function escolherAcaoInteracaoNPC($jogadores, $npc, $alvo, $meuNome, $perfil, $ehRival = false, $ehAliado = false)
{
    garantirMemoriaNPC();
    sincronizarMemoriasAutomaticasNPC($jogadores);

    $pesos = [
        1 => 20 + (($perfil['emocao'] ?? 50) * 0.30),
        2 => 12 + (($perfil['fofoca'] ?? 50) * 0.52),
        3 => 10 + (($perfil['treta'] ?? 50) * 0.55),
        4 => 12 + (($perfil['alianca'] ?? 50) * 0.55)
    ];

    if ($ehRival) {
        $pesos[1] += 2;  $pesos[2] += 28; $pesos[3] += 38; $pesos[4] -= 24;
    } elseif ($ehAliado) {
        $pesos[1] += 28; $pesos[2] -= 18; $pesos[3] -= 30; $pesos[4] += 38;
    } else {
        $pesos[1] += 8; $pesos[4] += 8;
    }

    /* Fase 2: acontecimentos da temporada alteram a reação. */
    $pesos = ajustarPesosAcaoPorMomentoNPC(
        $pesos,
        $jogadores,
        $npc,
        $alvo,
        $perfil
    );

    /* Fase 1: evita repetição imediata. */
    foreach ([1,2,3,4] as $acao) {
        $nomeAcao = nomeAcaoInteracaoNPC($acao, $ehRival, $ehAliado);
        $vezes = contarAcaoRecenteNPC($npc, $nomeAcao, 6);
        if ($vezes >= 1) $pesos[$acao] *= pow(0.62, $vezes);
        if (ultimaAcaoNPC($npc) === $nomeAcao) $pesos[$acao] *= 0.28;
    }

    $mesmoAlvo = contarAlvoRecenteNPC($npc, $alvo, 5);
    if ($mesmoAlvo >= 2) {
        $pesos[1] *= 1.35; $pesos[4] *= 1.20;
        $pesos[2] *= 0.72; $pesos[3] *= 0.68;
    }

    foreach ($pesos as $acao => $peso) {
        $pesos[$acao] = max(2, $peso + rand(-6, 6));
    }

    return (int)sortearOpcaoPorPesoNPC($pesos);
}

function ajustarRankingAlvosPorMemoriaNPC($ranking, $npc)
{
    if (empty($ranking) || !is_array($ranking)) return $ranking;

    $ultimo = ultimoAlvoNPC($npc);
    foreach ($ranking as $nome => &$score) {
        $score -= contarAlvoRecenteNPC($npc, $nome, 5) * 13;
        $igual = $ultimo !== '' && (function_exists('nomeIgual') ? nomeIgual($ultimo, $nome) : ($ultimo === $nome));
        if ($igual) $score -= 18;
        $score += rand(0, 6);
    }
    unset($score);
    arsort($ranking);
    return $ranking;
}

/* =========================================================
   🧠 NPCs 2.0 — FASE 2
   ACONTECIMENTOS COM CONSEQUÊNCIAS
   ========================================================= */

function nomesDeValorMemoriaNPC($valor)
{
    $nomes = [];

    if (is_string($valor) || is_numeric($valor)) {
        $nome = trim((string)$valor);
        if ($nome !== '') $nomes[] = $nome;
        return $nomes;
    }

    if (!is_array($valor)) {
        return $nomes;
    }

    foreach ($valor as $item) {
        foreach (nomesDeValorMemoriaNPC($item) as $nome) {
            $nomes[] = $nome;
        }
    }

    return array_values(array_unique(array_filter($nomes)));
}


function nomeIgualMemoriaNPC($a, $b)
{
    if (function_exists('nomeIgual')) {
        return nomeIgual($a, $b);
    }

    return mb_strtolower(trim((string)$a), 'UTF-8') ===
        mb_strtolower(trim((string)$b), 'UTF-8');
}


/* =========================================================
   🔄 SINCRONIZAR ACONTECIMENTOS IMPORTANTES
   =========================================================
   Esta função transforma estados normais do jogo em memórias:
   - indicação do Líder;
   - Monstro;
   - imunidade do Anjo;
   - VIP dado pelo Líder;
   - indicação via Big Fone, quando o dono está identificado.

   As chaves únicas impedem que F5 duplique lembranças.
   ========================================================= */
function sincronizarMemoriasAutomaticasNPC($jogadores)
{
    garantirMemoriaNPC();

    $rodada = (int)($_SESSION['rodada'] ?? 1);

    /* 👑 Indicação direta do Líder */
    $lider = trim((string)($_SESSION['lider'] ?? ''));
    $indicacaoLider = trim((string)($_SESSION['indicacao_lider'] ?? ''));

    if ($lider !== '' && $indicacaoLider !== '' && !nomeIgualMemoriaNPC($lider, $indicacaoLider)) {
        registrarMemoriaSocialNPC(
            $indicacaoLider,
            $lider,
            'me_indicou',
            1,
            "$lider indicou $indicacaoLider ao Paredão.",
            "auto|indicacao_lider|$rodada|$lider|$indicacaoLider"
        );
    }

    /* 👹 Monstro */
    $anjo = trim((string)($_SESSION['anjo'] ?? ''));
    if ($anjo !== '') {
        foreach (nomesDeValorMemoriaNPC($_SESSION['monstro'] ?? []) as $nomeMonstro) {
            if ($nomeMonstro === '' || nomeIgualMemoriaNPC($nomeMonstro, $anjo)) continue;

            registrarMemoriaSocialNPC(
                $nomeMonstro,
                $anjo,
                'me_colocou_monstro',
                1,
                "$anjo colocou $nomeMonstro no Monstro.",
                "auto|monstro|$rodada|$anjo|$nomeMonstro"
            );
        }
    }

    /* 🛡️ Imunidade dada pelo Anjo */
    $imune = trim((string)($_SESSION['imune'] ?? ''));
    if ($anjo !== '' && $imune !== '' && !nomeIgualMemoriaNPC($anjo, $imune)) {
        registrarMemoriaSocialNPC(
            $imune,
            $anjo,
            'me_imunizou',
            1,
            "$anjo imunizou $imune.",
            "auto|imunidade|$rodada|$anjo|$imune"
        );
    }

    /* 🟡 VIP dado pelo Líder */
    if ($lider !== '' && !empty($_SESSION['vip_definido'])) {
        foreach ($jogadores as $j) {
            $nome = trim((string)($j['nome'] ?? ''));
            if ($nome === '' || nomeIgualMemoriaNPC($nome, $lider)) continue;

            if (!empty($j['status']['vip'])) {
                registrarMemoriaSocialNPC(
                    $nome,
                    $lider,
                    'me_colocou_vip',
                    1,
                    "$lider colocou $nome no VIP.",
                    "auto|vip|$rodada|$lider|$nome"
                );
            }
        }
    }

    /* ☎️ Indicação do Big Fone */
    $donoBigFone = trim((string)($_SESSION['bigfone_dono_poder'] ?? ''));
    $indicacaoBigFone = trim((string)($_SESSION['indicacao_bigfone'] ?? ''));

    if (
        $donoBigFone !== '' &&
        $indicacaoBigFone !== '' &&
        !nomeIgualMemoriaNPC($donoBigFone, $indicacaoBigFone)
    ) {
        registrarMemoriaSocialNPC(
            $indicacaoBigFone,
            $donoBigFone,
            'me_colocou_paredao',
            1,
            "$donoBigFone colocou $indicacaoBigFone em risco pelo Big Fone.",
            "auto|bigfone|$rodada|$donoBigFone|$indicacaoBigFone"
        );
    }

    sincronizarPerdasDeAliadosNPC($jogadores);
}


/* =========================================================
   💔 ELIMINAÇÃO DE ALIADO
   ========================================================= */
function sincronizarPerdasDeAliadosNPC($jogadores)
{
    garantirMemoriaNPC();

    $historico = $_SESSION['historico_eliminados'] ?? [];
    if (!is_array($historico) || empty($historico)) return;

    $rodadaAtual = (int)($_SESSION['rodada'] ?? 1);

    foreach ($historico as $eliminado) {
        $eliminado = trim((string)$eliminado);
        if ($eliminado === '') continue;

        if (!empty($_SESSION['memoria_eliminacoes_processadas_npc'][$eliminado])) {
            continue;
        }

        foreach ($jogadores as $npc) {
            $nomeNPC = trim((string)($npc['nome'] ?? ''));
            if ($nomeNPC === '' || nomeIgualMemoriaNPC($nomeNPC, $eliminado)) continue;

            $rel = $npc['relacoes'][$eliminado] ?? [];
            $amizade = (float)($rel['amizade'] ?? 0);
            $confianca = (float)($rel['confianca'] ?? 0);
            $rivalidade = (float)($rel['rivalidade'] ?? 0);
            $romance = (float)($npc['romances'][$eliminado] ?? 0);

            $vinculo =
                $amizade +
                $confianca -
                ($rivalidade * 1.25) +
                ($romance * 0.80);

            /* Só vira "perda de aliado" se o vínculo realmente era relevante. */
            if ($vinculo < 72) continue;

            $forca = 1;
            if ($vinculo >= 115) $forca = 2;
            if ($vinculo >= 155 || $romance >= 60) $forca = 3;

            if (!isset($_SESSION['memoria_perdas_npc'][$nomeNPC])) {
                $_SESSION['memoria_perdas_npc'][$nomeNPC] = [];
            }

            $_SESSION['memoria_perdas_npc'][$nomeNPC][] = [
                'nome' => $eliminado,
                'rodada' => max(1, $rodadaAtual - 1),
                'forca' => $forca,
                'vinculo' => $vinculo
            ];

            if (count($_SESSION['memoria_perdas_npc'][$nomeNPC]) > 6) {
                $_SESSION['memoria_perdas_npc'][$nomeNPC] =
                    array_slice($_SESSION['memoria_perdas_npc'][$nomeNPC], -6);
            }
        }

        $_SESSION['memoria_eliminacoes_processadas_npc'][$eliminado] = true;
    }
}


function impactoPerdaAliadoNPC($npc)
{
    garantirMemoriaNPC();

    $eventos = $_SESSION['memoria_perdas_npc'][$npc] ?? [];
    if (!is_array($eventos)) return 0.0;

    $rodadaAtual = (int)($_SESSION['rodada'] ?? 1);
    $total = 0.0;

    foreach ($eventos as $evento) {
        $idade = max(0, $rodadaAtual - (int)($evento['rodada'] ?? $rodadaAtual));
        $forca = max(1, (int)($evento['forca'] ?? 1));

        /* A perda pesa bastante nas duas semanas seguintes e depois diminui. */
        $total += (18 * $forca) * pow(0.68, $idade);
    }

    return $total;
}


/* =========================================================
   🎯 MEMÓRIA DOMINANTE SOBRE UMA PESSOA
   ========================================================= */
function memoriaDominanteNPC($npc, $tipo = 'negativa')
{
    garantirMemoriaNPC();

    $memorias = $_SESSION['memoria_npc'][$npc] ?? [];
    if (!is_array($memorias)) {
        return ['alvo' => null, 'impacto' => 0.0];
    }

    $melhorAlvo = null;
    $melhorImpacto = 0.0;

    foreach ($memorias as $alvo => $dados) {
        $impacto =
            $tipo === 'positiva'
                ? impactoPositivoMemoriaNPC($npc, $alvo)
                : impactoNegativoMemoriaNPC($npc, $alvo);

        if ($impacto > $melhorImpacto) {
            $melhorImpacto = $impacto;
            $melhorAlvo = $alvo;
        }
    }

    return [
        'alvo' => $melhorAlvo,
        'impacto' => $melhorImpacto
    ];
}


/* =========================================================
   🧭 MOMENTO ATUAL DO NPC
   ========================================================= */
function momentoEstrategicoNPC($jogadores, $npc)
{
    sincronizarMemoriasAutomaticasNPC($jogadores);

    $neg = memoriaDominanteNPC($npc, 'negativa');
    $pos = memoriaDominanteNPC($npc, 'positiva');

    $paredao = false;
    foreach (nomesDeValorMemoriaNPC($_SESSION['paredao'] ?? []) as $nomeParedao) {
        if (nomeIgualMemoriaNPC($nomeParedao, $npc)) {
            $paredao = true;
            break;
        }
    }

    $monstro = false;
    foreach (nomesDeValorMemoriaNPC($_SESSION['monstro'] ?? []) as $nomeMonstro) {
        if (nomeIgualMemoriaNPC($nomeMonstro, $npc)) {
            $monstro = true;
            break;
        }
    }

    return [
        'paredao' => $paredao,
        'monstro' => $monstro,
        'ressentimento_alvo' => $neg['alvo'],
        'ressentimento' => (float)$neg['impacto'],
        'gratidao_alvo' => $pos['alvo'],
        'gratidao' => (float)$pos['impacto'],
        'perda_aliado' => impactoPerdaAliadoNPC($npc)
    ];
}


/* =========================================================
   🎭 AJUSTAR AÇÃO PELO MOMENTO
   ========================================================= */
function ajustarPesosAcaoPorMomentoNPC(
    $pesos,
    $jogadores,
    $npc,
    $alvo,
    $perfil
) {
    $momento = momentoEstrategicoNPC($jogadores, $npc);

    $dadosNPC = buscarParticipanteInteligenciaNPC($jogadores, $npc);
    $personalidade = $dadosNPC['personalidade'] ?? 'Neutro';

    $ehRessentimento =
        !empty($momento['ressentimento_alvo']) &&
        nomeIgualMemoriaNPC($momento['ressentimento_alvo'], $alvo);

    $ehGratidao =
        !empty($momento['gratidao_alvo']) &&
        nomeIgualMemoriaNPC($momento['gratidao_alvo'], $alvo);

    /* Foi alvo de alguém: tende a confrontar ou falar sobre isso. */
    if ($ehRessentimento) {
        $forca = min(55, $momento['ressentimento'] * 0.28);
        $pesos[2] += $forca * 0.75;
        $pesos[3] += $forca;
        $pesos[4] -= min(25, $forca * 0.45);
    }

    /* Foi ajudado: aumenta chance de aproximação e lealdade. */
    if ($ehGratidao) {
        $forca = min(50, $momento['gratidao'] * 0.28);
        $pesos[1] += $forca * 0.70;
        $pesos[4] += $forca;
        $pesos[3] -= min(30, $forca * 0.55);
    }

    /* Emparedado: reage conforme a personalidade. */
    if (!empty($momento['paredao'])) {
        if (in_array($personalidade, ['Estrategista', 'Manipulador', 'Líder Nato', 'Falso'], true)) {
            $pesos[4] += 42;
            $pesos[1] += 18;
            $pesos[3] -= 8;
        } elseif (in_array($personalidade, ['Explosivo', 'Barraqueiro'], true)) {
            $pesos[3] += 34;
            $pesos[2] += 16;
        } else {
            $pesos[4] += 24;
            $pesos[1] += 18;
        }
    }

    /* Monstro aumenta irritação principalmente em perfis reativos. */
    if (!empty($momento['monstro'])) {
        if (in_array($personalidade, ['Explosivo', 'Barraqueiro', 'Emocional'], true)) {
            $pesos[3] += 20;
            $pesos[2] += 10;
        } else {
            $pesos[1] += 8;
            $pesos[4] += 10;
        }
    }

    /* Perdeu aliado: procura reconstruir rede social nas rodadas seguintes. */
    $perda = (float)($momento['perda_aliado'] ?? 0);
    if ($perda > 4) {
        $pesos[4] += min(38, $perda * 0.85);
        $pesos[1] += min(24, $perda * 0.55);

        if ($personalidade === 'Emocional') {
            $pesos[1] += min(18, $perda * 0.45);
        }

        if (!in_array($personalidade, ['Explosivo', 'Barraqueiro'], true)) {
            $pesos[3] -= min(12, $perda * 0.25);
        }
    }

    foreach ($pesos as $acao => $peso) {
        $pesos[$acao] = max(2, $peso);
    }

    return $pesos;
}


/* =========================================================
   🎯 AJUSTAR ALVO PELO MOMENTO
   ========================================================= */
function ajustarRankingAlvosPorMomentoNPC(
    $ranking,
    $jogadores,
    $npc,
    $contexto
) {
    if (empty($ranking) || !is_array($ranking)) return $ranking;

    $momento = momentoEstrategicoNPC($jogadores, $npc);
    $positivo = contextoPositivoNPC($contexto);

    $ressentido = $momento['ressentimento_alvo'] ?? null;
    $grato = $momento['gratidao_alvo'] ?? null;

    foreach ($ranking as $nome => &$score) {
        if ($ressentido && nomeIgualMemoriaNPC($nome, $ressentido)) {
            if (!$positivo) {
                $score += min(60, ((float)$momento['ressentimento']) * 0.22);
            } else {
                $score -= min(45, ((float)$momento['ressentimento']) * 0.18);
            }
        }

        if ($grato && nomeIgualMemoriaNPC($nome, $grato)) {
            if ($positivo) {
                $score += min(55, ((float)$momento['gratidao']) * 0.22);
            } else {
                $score -= min(50, ((float)$momento['gratidao']) * 0.20);
            }
        }
    }

    unset($score);
    arsort($ranking);
    return $ranking;
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
    sincronizarMemoriasAutomaticasNPC($jogadores);

    $dadosNPC = buscarParticipanteInteligenciaNPC($jogadores, $npc);
    if (!$dadosNPC) return null;

    $personalidade = $dadosNPC['personalidade'] ?? 'Neutro';
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

    $t = $tendencias[$personalidade] ?? $tendencias['Neutro'];
    $momento = momentoEstrategicoNPC($jogadores, $npc);

    $chanceRival = (int)$t['rival'];

    if (($momento['ressentimento'] ?? 0) >= 25) {
        $chanceRival += 15;
    }

    if (!empty($momento['paredao']) && in_array($personalidade, ['Explosivo', 'Barraqueiro'], true)) {
        $chanceRival += 12;
    }

    if (($momento['perda_aliado'] ?? 0) >= 10) {
        $chanceRival -= 10;
    }

    if (($momento['gratidao'] ?? 0) >= 25) {
        $chanceRival -= 8;
    }

    $chanceRival = max(10, min(90, $chanceRival));
    $contexto = rand(1,100) <= $chanceRival ? 'discordia_negativo' : 'interacao_aliado';

    $ranking = ranquearAlvosNPCInteligente($jogadores, $npc, $contexto, [$npc]);
    $ranking = ajustarRankingAlvosPorMemoriaNPC($ranking, $npc);
    $ranking = ajustarRankingAlvosPorMomentoNPC($ranking, $jogadores, $npc, $contexto);
    if (empty($ranking)) return null;

    $nomes = array_keys($ranking);
    $escolhido = $nomes[0];

    if (count($nomes) >= 2) {
        $top = array_slice($nomes, 0, min(3, count($nomes)));
        $melhor = (float)($ranking[$top[0]] ?? 0);
        $pesos = [];
        foreach ($top as $indice => $nome) {
            $score = (float)($ranking[$nome] ?? 0);
            $distancia = max(0, $melhor - $score);
            $pesos[$nome] = max(5, 100 - ($distancia * 3) - ($indice * 12));
        }
        $escolhido = sortearOpcaoPorPesoNPC($pesos);
    }

    registrarDecisaoNPC($npc, 'interacao', $escolhido, $ranking);
    return $escolhido;
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
