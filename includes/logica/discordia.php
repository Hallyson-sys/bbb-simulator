<?php

/* =========================================================
   🔥 LÓGICA DO JOGO DA DISCÓRDIA
   ========================================================= */

require_once __DIR__ . '/consequencias_sociais.php';


/* =========================================================
   📋 TEMAS DISPONÍVEIS
   ========================================================= */

function temasDiscordia()
{
    return [
        "sonso",
        "falso",
        "saboneteiro",
        "aliado",
        "podio",
        "filme_bbb",
        "quem_e_quem",
        "alerta_vermelho",
        "fatura_da_casa",
        "premio_bbb",
        "superpoderes",
        "constelacao_bbb"
    ];
}


/* =========================================================
   🎨 CONFIGURAÇÃO DOS TEMAS
   ========================================================= */
function configuracaoTemaDiscordia($tema)
{
    $temas = [
        'sonso' => [
            'titulo' => '😴 Quem é o mais sonso?',
            'tipo' => 'simples_negativo'
        ],
        'falso' => [
            'titulo' => '🐍 Quem é o mais falso?',
            'tipo' => 'simples_negativo'
        ],
        'saboneteiro' => [
            'titulo' => '🧼 Quem é o mais saboneteiro?',
            'tipo' => 'simples_negativo'
        ],
        'aliado' => [
            'titulo' => '🤝 Quem é seu maior aliado?',
            'tipo' => 'simples_positivo'
        ],
        'podio' => [
            'titulo' => '🏆 Monte seu pódio',
            'tipo' => 'podio'
        ],
        'filme_bbb' => [
            'titulo' => '🎬 FILME BBB',
            'subtitulo' => 'Monte o elenco da temporada na sua visão.',
            'tipo' => 'categorias',
            'destaque' => true,
            'categorias' => [
                'protagonista' => ['emoji' => '⭐', 'nome' => 'Protagonista', 'descricao' => 'O personagem principal da temporada.', 'efeito' => 'muito_positivo', 'permite_si' => true],
                'antagonista' => ['emoji' => '😈', 'nome' => 'Antagonista', 'descricao' => 'Seu principal adversário ou vilão no jogo.', 'efeito' => 'forte_negativo', 'permite_si' => false],
                'coadjuvante' => ['emoji' => '🤝', 'nome' => 'Coadjuvante', 'descricao' => 'Quem mais complementa ou fortalece seu jogo.', 'efeito' => 'positivo', 'permite_si' => false],
                'figurante' => ['emoji' => '👤', 'nome' => 'Figurante', 'descricao' => 'Quem menos apareceu ou movimentou a temporada.', 'efeito' => 'negativo', 'permite_si' => false]
            ]
        ],
        'quem_e_quem' => [
            'titulo' => '🎭 QUEM É QUEM NA CASA?',
            'subtitulo' => 'Distribua as placas que mais combinam com cada participante.',
            'tipo' => 'categorias',
            'categorias' => [
                'duas_caras' => ['emoji' => '🎭', 'nome' => 'Duas Caras', 'descricao' => 'Muda de comportamento dependendo de com quem está.', 'efeito' => 'forte_negativo'],
                'planta' => ['emoji' => '🌱', 'nome' => 'Planta', 'descricao' => 'Participa pouco e aparece menos no jogo.', 'efeito' => 'negativo'],
                'joga_sujo' => ['emoji' => '🐍', 'nome' => 'Joga Sujo', 'descricao' => 'Usa estratégias que você considera desleais.', 'efeito' => 'forte_negativo'],
                'bomba_relogio' => ['emoji' => '💣', 'nome' => 'Bomba-Relógio', 'descricao' => 'Pode explodir e causar uma grande confusão.', 'efeito' => 'negativo']
            ]
        ],
        'alerta_vermelho' => [
            'titulo' => '🚨 ALERTA VERMELHO',
            'subtitulo' => 'Aponte os maiores perigos para o seu jogo.',
            'tipo' => 'categorias',
            'categorias' => [
                'ameaca_estrategica' => ['emoji' => '🧠', 'nome' => 'Maior Ameaça Estratégica', 'descricao' => 'É perigoso porque entende e movimenta bem o jogo.', 'efeito' => 'respeito'],
                'fofoqueiro' => ['emoji' => '🗣️', 'nome' => 'Maior Fofoqueiro', 'descricao' => 'Informações circulam rápido quando passam por essa pessoa.', 'efeito' => 'negativo'],
                'nao_confio' => ['emoji' => '🎯', 'nome' => 'Não Confio', 'descricao' => 'Você evitaria entregar informações importantes.', 'efeito' => 'forte_negativo'],
                'explode_casa' => ['emoji' => '🧨', 'nome' => 'Pode Explodir a Casa', 'descricao' => 'Tem potencial para criar a próxima grande treta.', 'efeito' => 'negativo']
            ]
        ],
        'fatura_da_casa' => [
            'titulo' => '🧾 FATURA DA CASA',
            'subtitulo' => 'Quem está devendo alguma coisa dentro do jogo?',
            'tipo' => 'categorias',
            'categorias' => [
                'deve_jogar' => ['emoji' => '🎮', 'nome' => 'Deve Jogar Mais', 'descricao' => 'Precisa aparecer e se envolver mais.', 'efeito' => 'negativo'],
                'deve_posicionar' => ['emoji' => '🤐', 'nome' => 'Deve Se Posicionar', 'descricao' => 'Foge de situações em que deveria tomar uma posição.', 'efeito' => 'negativo'],
                'deve_lealdade' => ['emoji' => '🤝', 'nome' => 'Deve Lealdade', 'descricao' => 'Na sua visão, traiu ou decepcionou alguém próximo.', 'efeito' => 'forte_negativo'],
                'deve_espelho' => ['emoji' => '🪞', 'nome' => 'Deve Olhar Para Si', 'descricao' => 'Critica nos outros algo que também faz.', 'efeito' => 'forte_negativo']
            ]
        ],
        'premio_bbb' => [
            'titulo' => '🏆 PRÊMIO BBB',
            'subtitulo' => 'Entregue os prêmios positivos da temporada.',
            'tipo' => 'categorias',
            'categorias' => [
                'melhor_jogador' => ['emoji' => '👑', 'nome' => 'Melhor Jogador', 'descricao' => 'Quem está jogando melhor na sua visão.', 'efeito' => 'muito_positivo'],
                'coracao_casa' => ['emoji' => '❤️', 'nome' => 'Coração da Casa', 'descricao' => 'Acolhe, aproxima e faz bem para a convivência.', 'efeito' => 'positivo'],
                'maior_evolucao' => ['emoji' => '📈', 'nome' => 'Maior Evolução', 'descricao' => 'Quem mais cresceu desde o começo.', 'efeito' => 'positivo'],
                'mais_autentico' => ['emoji' => '✨', 'nome' => 'Mais Autêntico', 'descricao' => 'Quem parece mais verdadeiro dentro da casa.', 'efeito' => 'muito_positivo']
            ]
        ],
        'superpoderes' => [
            'titulo' => '🦸 SUPERPODERES DA CASA',
            'subtitulo' => 'Qual poder representa melhor cada participante?',
            'tipo' => 'categorias',
            'categorias' => [
                'mestre_estrategia' => ['emoji' => '🧠', 'nome' => 'Mestre da Estratégia', 'descricao' => 'Entende bem o jogo e seus movimentos.', 'efeito' => 'muito_positivo'],
                'escudo_casa' => ['emoji' => '🛡️', 'nome' => 'Escudo da Casa', 'descricao' => 'Protege e defende quem está ao seu lado.', 'efeito' => 'positivo'],
                'detector_mentiras' => ['emoji' => '👁️', 'nome' => 'Detector de Mentiras', 'descricao' => 'Percebe quando alguma coisa está estranha.', 'efeito' => 'positivo'],
                'poder_virada' => ['emoji' => '⚡', 'nome' => 'Poder da Virada', 'descricao' => 'Pode transformar completamente o próprio jogo.', 'efeito' => 'positivo']
            ]
        ],
        'constelacao_bbb' => [
            'titulo' => '💫 CONSTELAÇÃO BBB',
            'subtitulo' => 'Transforme a casa em uma constelação.',
            'tipo' => 'categorias',
            'categorias' => [
                'sol' => ['emoji' => '☀️', 'nome' => 'Sol', 'descricao' => 'Anima e movimenta a casa.', 'efeito' => 'positivo'],
                'lua' => ['emoji' => '🌙', 'nome' => 'Lua', 'descricao' => 'Traz calma, apoio e equilíbrio.', 'efeito' => 'positivo'],
                'estrela' => ['emoji' => '⭐', 'nome' => 'Estrela', 'descricao' => 'Quem mais se destaca na temporada.', 'efeito' => 'muito_positivo'],
                'cometa' => ['emoji' => '☄️', 'nome' => 'Cometa', 'descricao' => 'Surpreendeu e cresceu muito durante o jogo.', 'efeito' => 'positivo']
            ]
        ]
    ];

    return $temas[$tema] ?? $temas['sonso'];
}

function temaDiscordiaEhCategorias($tema)
{
    $config = configuracaoTemaDiscordia($tema);
    return ($config['tipo'] ?? '') === 'categorias';
}

function categoriasTemaDiscordia($tema)
{
    $config = configuracaoTemaDiscordia($tema);
    return $config['categorias'] ?? [];
}

function escolherAlvoDiscordiaPorPerfil(&$jogadores, $autor, $meuNome, $preferencia, $excluir = [])
{
    $alvos = [];
    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';
        if ($nome !== '' && !nomeIgual($nome, $autor) && !in_array($nome, $excluir, true)) {
            $alvos[] = $nome;
        }
    }

    if (empty($alvos)) {
        return null;
    }

    if ($preferencia === 'si') {
        return $autor;
    }

    if ($preferencia === 'aliado' || $preferencia === 'rival') {
        $alvo = escolherAlvoNPCPorRelacao($jogadores, $autor, $meuNome, $preferencia);
        if ($alvo !== null && !in_array($alvo, $excluir, true) && !nomeIgual($alvo, $autor)) {
            return $alvo;
        }
    }

    shuffle($alvos);
    return $alvos[0] ?? null;
}

function aplicarEfeitoCategoriaDiscordia(&$jogadores, $autor, $alvo, $efeito, $chaveBase, $descricao)
{
    $mapa = [
        'muito_positivo' => 'discordia_premio_forte',
        'positivo' => 'discordia_premio',
        'respeito' => 'discordia_respeito',
        'negativo' => 'discordia_critica',
        'forte_negativo' => 'discordia_critica_forte'
    ];

    $tipo = $mapa[$efeito] ?? 'discordia_critica';

    aplicarConsequenciaSocial(
        $jogadores,
        $autor,
        $alvo,
        $tipo,
        $chaveBase,
        $descricao
    );
}


/* =========================================================
   🎲 PREPARAR TEMA DA DISCÓRDIA
   ========================================================= */

function prepararTemaDiscordia($fase)
{
    if (
        $fase != 'discordia' ||
        isset($_SESSION['tema_discordia'])
    ) {
        return;
    }


    $temas =
        temasDiscordia();


    $_SESSION['tema_discordia'] =
        $temas[
            array_rand($temas)
        ];
}


/* =========================================================
   🤖 GERAR DISCÓRDIA DOS NPCs
   ========================================================= */

function gerarDiscordiaNPC(
    &$jogadores,
    $meuNome,
    $tema
) {

    $eventos = [];


    atualizarRelacoesMarcantes(
        $jogadores
    );


    foreach ($jogadores as $npc) {

        $nomeNPC =
            $npc['nome'] ?? '';


        $perfil =
            perfilPersonalidadeCompleto(
                $npc['personalidade'] ?? 'Neutro'
            );


        if (
            $nomeNPC == '' ||
            $nomeNPC == $meuNome
        ) {
            continue;
        }


        /* =========================
           🎯 POSSÍVEIS ALVOS
           ========================= */

        $alvos = [];


        foreach ($jogadores as $j) {

            if (
                ($j['nome'] ?? '') !=
                $nomeNPC
            ) {

                $alvos[] =
                    $j['nome'];
            }
        }


        if (empty($alvos)) {
            continue;
        }


        /* =====================================================
           🎬/🎭 TEMAS COM CATEGORIAS
           ===================================================== */
        if (temaDiscordiaEhCategorias($tema)) {
            $configTema = configuracaoTemaDiscordia($tema);
            $categorias = $configTema['categorias'] ?? [];
            $usados = [];
            $escolhas = [];

            foreach ($categorias as $chaveCategoria => $categoria) {
                $efeito = $categoria['efeito'] ?? 'negativo';
                $permiteSi = !empty($categoria['permite_si']);

                if ($tema === 'filme_bbb' && $chaveCategoria === 'protagonista') {
                    $alvo = $nomeNPC;
                } elseif (in_array($efeito, ['muito_positivo', 'positivo'], true)) {
                    $alvo = escolherAlvoDiscordiaPorPerfil(
                        $jogadores,
                        $nomeNPC,
                        $meuNome,
                        'aliado',
                        $usados
                    );
                } elseif ($efeito === 'respeito') {
                    $alvo = escolherAlvoDiscordiaPorPerfil(
                        $jogadores,
                        $nomeNPC,
                        $meuNome,
                        rand(1, 100) <= 55 ? 'rival' : 'aliado',
                        $usados
                    );
                } else {
                    $alvo = escolherAlvoDiscordiaPorPerfil(
                        $jogadores,
                        $nomeNPC,
                        $meuNome,
                        'rival',
                        $usados
                    );
                }

                if ($alvo === null && $permiteSi) {
                    $alvo = $nomeNPC;
                }

                if ($alvo === null) {
                    $alvo = escolherAlvoDiscordiaPorPerfil(
                        $jogadores,
                        $nomeNPC,
                        $meuNome,
                        'aleatorio',
                        $usados
                    );
                }

                if ($alvo === null) {
                    continue;
                }

                $escolhas[$chaveCategoria] = $alvo;

                if (!nomeIgual($alvo, $nomeNPC)) {
                    $usados[] = $alvo;

                    aplicarEfeitoCategoriaDiscordia(
                        $jogadores,
                        $nomeNPC,
                        $alvo,
                        $efeito,
                        'discordia_npc|' .
                        ($_SESSION['rodada'] ?? 1) .
                        '|' . $tema . '|' . $chaveCategoria . '|' .
                        $nomeNPC . '|' . $alvo,
                        $nomeNPC . ' colocou ' . $alvo . ' como ' . ($categoria['nome'] ?? $chaveCategoria) . ' no Jogo da Discórdia.'
                    );

                    registrarRelacaoMarcante($jogadores, $nomeNPC, $alvo);
                    registrarRelacaoMarcante($jogadores, $alvo, $nomeNPC);
                }
            }

            if (!empty($escolhas)) {
                $partes = [];
                foreach ($escolhas as $chaveCategoria => $alvo) {
                    $cat = $categorias[$chaveCategoria] ?? [];
                    $partes[] = ($cat['emoji'] ?? '•') . ' ' .
                        ($cat['nome'] ?? $chaveCategoria) . ': ' . $alvo;
                }

                $eventos[] = ($configTema['titulo'] ?? '🔥 Jogo da Discórdia') .
                    ' — ' . $nomeNPC . ': ' . implode(' | ', $partes) . '.';
            }

            continue;
        }


        /* =====================================================
           🏆 PÓDIO
           ===================================================== */

        if ($tema == "podio") {

            $segundo =
                escolherAlvoNPCPorRelacao(
                    $jogadores,
                    $nomeNPC,
                    $meuNome,
                    'aliado'
                );


            $terceiro = null;


            if ($segundo != null) {

                $restantes =
                    array_values(
                        array_filter(
                            $alvos,
                            function ($n) use ($segundo) {

                                return
                                    $n != $segundo;
                            }
                        )
                    );

            } else {

                $restantes =
                    $alvos;
            }


            $aliadoExtra =
                escolherAlvoNPCPorRelacao(
                    $jogadores,
                    $nomeNPC,
                    $meuNome,
                    'aliado'
                );


            if (
                $aliadoExtra != null &&
                $aliadoExtra != $segundo
            ) {

                $terceiro =
                    $aliadoExtra;
            }


            if ($segundo == null) {

                shuffle($restantes);

                $segundo =
                    $restantes[0] ?? '';
            }


            if ($terceiro == null) {

                $restantes =
                    array_values(
                        array_filter(
                            $alvos,
                            function ($n) use ($segundo) {

                                return
                                    $n != $segundo;
                            }
                        )
                    );


                shuffle($restantes);


                $terceiro =
                    $restantes[0] ?? '';
            }


            if (
                $segundo &&
                $terceiro
            ) {

                aplicarConsequenciaSocial(
                    $jogadores,
                    $nomeNPC,
                    $segundo,
                    'discordia_podio_2',
                    'discordia_npc|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|podio2|' .
                    $nomeNPC . '|' . $segundo,
                    "$nomeNPC colocou $segundo em segundo lugar no pódio."
                );

                aplicarConsequenciaSocial(
                    $jogadores,
                    $nomeNPC,
                    $terceiro,
                    'discordia_podio_3',
                    'discordia_npc|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|podio3|' .
                    $nomeNPC . '|' . $terceiro,
                    "$nomeNPC colocou $terceiro em terceiro lugar no pódio."
                );


                $eventos[] =
                    "🏆 $nomeNPC montou seu pódio: 🥇 $nomeNPC, 🥈 $segundo e 🥉 $terceiro.";
            }


            continue;
        }


        /* =====================================================
           🤝 MAIOR ALIADO
           ===================================================== */

        if ($tema == "aliado") {

            $alvo =
                escolherAlvoNPCPorRelacao(
                    $jogadores,
                    $nomeNPC,
                    $meuNome,
                    'aliado'
                );


            if ($alvo == null) {

                $alvo =
                    $alvos[
                        array_rand($alvos)
                    ];
            }


            aplicarConsequenciaSocial(
                $jogadores,
                $nomeNPC,
                $alvo,
                'discordia_aliado',
                'discordia_npc|' .
                ($_SESSION['rodada'] ?? 1) .
                '|aliado|' .
                $nomeNPC . '|' . $alvo,
                "$nomeNPC declarou $alvo como maior aliado."
            );

            if (nomeIgual($alvo, $meuNome)) {
                $eventos[] =
                    "🤝 $nomeNPC declarou que $meuNome é seu maior aliado. Sua afinidade com $nomeNPC subiu.";
            } else {
                $eventos[] =
                    "🤝 $nomeNPC declarou que $alvo é seu maior aliado.";
            }


            continue;
        }


        /* =====================================================
           🔥 SONSO / FALSO / SABONETEIRO
           ===================================================== */

        if (
            $tema == "sonso" ||
            $tema == "falso" ||
            $tema == "saboneteiro"
        ) {

            $alvo =
                escolherAlvoNPCPorRelacao(
                    $jogadores,
                    $nomeNPC,
                    $meuNome,
                    'rival'
                );


            if ($alvo == null) {

                $alvo =
                    $alvos[
                        array_rand($alvos)
                    ];
            }


            /* =========================
               💥 INTENSIDADE DO NPC
               ========================= */

            if (
                ($perfil['treta'] ?? 50) >= 80
            ) {

                $forca = 2;

            } elseif (
                ($perfil['treta'] ?? 50) <= 25
            ) {

                $forca = 3;

            } else {

                $forca =
                    rand(1, 3);
            }


            /*
             * Rivais tendem a bater com tudo.
             */
            if (
                saoRivais(
                    $jogadores,
                    $nomeNPC,
                    $alvo,
                    $meuNome
                )
            ) {

                $forca = 2;
            }


            /* =========================
               😶 LEVE
               ========================= */

            if ($forca == 1) {

                aplicarConsequenciaSocial(
                    $jogadores,
                    $nomeNPC,
                    $alvo,
                    'discordia_negativa_leve',
                    'discordia_npc|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|leve|' .
                    $nomeNPC . '|' . $alvo,
                    "$nomeNPC atacou $alvo de forma leve no Jogo da Discórdia."
                );

                if (nomeIgual($alvo, $meuNome)) {
                    $eventos[] =
                        "😶 $nomeNPC disse que $meuNome é $tema de forma mais leve. Sua afinidade com $nomeNPC caiu.";
                } else {
                    $eventos[] =
                        "😶 $nomeNPC disse que $alvo é $tema de forma mais leve.";
                }
            }


            /* =========================
               🔥 COM TUDO
               ========================= */

            if ($forca == 2) {

                aplicarConsequenciaSocial(
                    $jogadores,
                    $nomeNPC,
                    $alvo,
                    'discordia_negativa_forte',
                    'discordia_npc|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|forte|' .
                    $nomeNPC . '|' . $alvo,
                    "$nomeNPC atacou $alvo com tudo no Jogo da Discórdia."
                );

                if (nomeIgual($alvo, $meuNome)) {
                    $eventos[] =
                        "🔥 $nomeNPC chamou $meuNome de $tema no Jogo da Discórdia. Sua afinidade com $nomeNPC caiu bastante.";
                } else {
                    $eventos[] =
                        "🔥 $nomeNPC chamou $alvo de $tema no Jogo da Discórdia.";
                }
            }


            /* =========================
               🧼 SABONETAR
               ========================= */

            if ($forca == 3) {

                aplicarConsequenciaSocial(
                    $jogadores,
                    $nomeNPC,
                    $alvo,
                    'discordia_sabonete',
                    'discordia_npc|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|sabonete|' .
                    $nomeNPC . '|' . $alvo,
                    "$nomeNPC sabonetou ao falar de $alvo no Jogo da Discórdia."
                );

                alterarPopularidadeMotivo(
                    $jogadores,
                    $nomeNPC,
                    -3,
                    -3,
                    "saboneteou no Jogo da Discórdia",
                    false
                );

                $eventos[] =
                    "🧼 $nomeNPC sabonetou e tentou fugir da pergunta.";
            }


            registrarRelacaoMarcante(
                $jogadores,
                $nomeNPC,
                $alvo
            );


            registrarRelacaoMarcante(
                $jogadores,
                $alvo,
                $nomeNPC
            );
        }
    }


    $_SESSION['jogadores'] =
        $jogadores;


    return $eventos;
}

