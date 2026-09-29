<?php

/* =========================================================
   🏠 CASA DE VIDRO 2.0
   =========================================================
   Arquitetura simples:
   - NÃO usa fase_semana = casa_vidro.
   - Usa apenas casa_vidro_estado como estado principal.
   - Anúncio: início da Rodada 3.
   - Resultado + entrada: imediatamente antes da Festa
     da Rodada 3.
   ========================================================= */


/* =========================================================
   🧹 MIGRAÇÃO / LIMPEZA DA VERSÃO ANTIGA
   ========================================================= */
function inicializarCasaVidroV2()
{
    $versao =
        (int)($_SESSION['casa_vidro_versao'] ?? 0);

    if ($versao === 2) {
        if (!isset($_SESSION['casa_vidro_estado'])) {
            $_SESSION['casa_vidro_estado'] =
                'nao_decidida';
        }

        return;
    }

    /*
     * Preserva o modo de teste caso ele tenha sido ativado
     * antes da primeira execução da versão nova.
     */
    $forcar =
        !empty($_SESSION['forcar_casa_vidro']);

    /*
     * Remove qualquer estado da implementação antiga.
     */
    foreach (array_keys($_SESSION) as $chave) {
        if (
            strpos(
                (string)$chave,
                'casa_vidro_'
            ) === 0
        ) {
            unset($_SESSION[$chave]);
        }
    }

    $_SESSION['casa_vidro_versao'] = 2;
    $_SESSION['casa_vidro_estado'] =
        'nao_decidida';

    if ($forcar) {
        $_SESSION['forcar_casa_vidro'] = true;
    }
}


/* =========================================================
   📍 ESTADO ATUAL
   ========================================================= */
function estadoCasaVidro()
{
    inicializarCasaVidroV2();

    return
        $_SESSION['casa_vidro_estado']
        ?? 'nao_decidida';
}


/* =========================================================
   🧱 NOMES QUE NÃO PODEM SER REPETIDOS
   ========================================================= */
function nomesUsadosCasaVidro($jogadores)
{
    $usados = [];

    $registrar =
        function ($nome) use (&$usados) {
            $nome = trim((string)$nome);

            if ($nome === '') {
                return;
            }

            $usados[
                mb_strtolower(
                    $nome,
                    'UTF-8'
                )
            ] = true;
        };

    foreach ($jogadores as $j) {
        $registrar($j['nome'] ?? '');
    }

    foreach (
        $_SESSION['historico_eliminados']
        ?? []
        as $nome
    ) {
        $registrar($nome);
    }

    foreach (
        $_SESSION['elenco_personalizado']
        ?? []
        as $j
    ) {
        $registrar($j['nome'] ?? '');
    }

    return $usados;
}


/* =========================================================
   🎲 OPÇÕES PARA OS CANDIDATOS
   ========================================================= */
function opcoesCasaVidroV2()
{
    $nomesNPC = [];
    $personalidades = [];
    $profissoes = [];
    $estados = [];

    $arquivo =
        __DIR__
        . '/../../data/opcoes_participantes.php';

    if (is_file($arquivo)) {
        require $arquivo;
    }

    /*
     * Fallbacks para não quebrar a dinâmica
     * caso algum save/projeto antigo não tenha a lista.
     */
    if (empty($nomesNPC)) {
        $nomesNPC = [
            'Amanda',
            'Arthur',
            'Bárbara',
            'Caio',
            'Cecília',
            'Danilo',
            'Eduarda',
            'Enzo',
            'Gabriela',
            'Heitor',
            'Isabela',
            'João',
            'Lívia',
            'Marcelo',
            'Nicole',
            'Otávio',
            'Paula',
            'Ravi',
            'Sabrina',
            'Theo'
        ];
    }

    if (empty($personalidades)) {
        $personalidades = [
            'Estrategista',
            'Explosivo',
            'Planta',
            'Manipulador',
            'Emocional',
            'Barraqueiro',
            'Fofo',
            'Líder Nato',
            'Influencer',
            'Falso',
            'Neutro'
        ];
    }

    if (empty($profissoes)) {
        $profissoes = [
            'Influencer',
            'Professor(a)',
            'Advogado(a)',
            'Enfermeiro(a)',
            'DJ',
            'Ator/Atriz',
            'Personal Trainer',
            'Maquiador(a)',
            'Cantor(a)',
            'Modelo',
            'Vendedor(a)',
            'Tatuador(a)',
            'Streamer',
            'Fotógrafo(a)'
        ];
    }

    if (empty($estados)) {
        $estados = [
            'SP',
            'RJ',
            'MG',
            'BA',
            'RS',
            'SC',
            'PR',
            'PE',
            'CE',
            'GO',
            'DF'
        ];
    }

    return [
        'nomes' => array_values($nomesNPC),
        'personalidades' =>
            array_values($personalidades),
        'profissoes' =>
            array_values($profissoes),
        'estados' =>
            array_values($estados)
    ];
}


/* =========================================================
   ⭐ APELO INTERNO NA VOTAÇÃO
   ========================================================= */
function bonusVotacaoCasaVidro($personalidade)
{
    $bonus = [
        'Influencer' => 8,
        'Líder Nato' => 6,
        'Barraqueiro' => 5,
        'Explosivo' => 4,
        'Estrategista' => 4,
        'Fofo' => 3,
        'Emocional' => 2,
        'Manipulador' => 1,
        'Neutro' => 0,
        'Falso' => -2,
        'Planta' => -5
    ];

    return
        (int)($bonus[$personalidade] ?? 0);
}


/* =========================================================
   👤 CRIAR CANDIDATO
   ========================================================= */
function criarCandidatoCasaVidroV2(
    $nome,
    $opcoes
) {
    $personalidade =
        $opcoes['personalidades'][
            array_rand(
                $opcoes['personalidades']
            )
        ];

    $profissao =
        $opcoes['profissoes'][
            array_rand(
                $opcoes['profissoes']
            )
        ];

    $estado =
        $opcoes['estados'][
            array_rand(
                $opcoes['estados']
            )
        ];

    /*
     * Popularidade continua sendo um dado INTERNO.
     * Ela não aparece para o jogador na Casa de Vidro.
     */
    $popularidadeInicial =
        rand(45, 58);

    /*
     * A força da votação é criada uma vez.
     * Assim atualizar a página não muda o resultado.
     */
    $forcaVoto =
        rand(65, 100)
        + bonusVotacaoCasaVidro(
            $personalidade
        );

    return [
        'nome' => $nome,
        'idade' => rand(18, 50),
        'profissao' => $profissao,
        'estado' => $estado,
        'personalidade' => $personalidade,
        'popularidade' =>
            $popularidadeInicial,
        'humor' => rand(48, 70),

        'status' => [
            'lider' => false,
            'anjo' => false,
            'imune' => false,
            'vip' => false,
            'xepa' => true,
            'monstro' => false
        ],

        'relacoes' => [],
        'romances' => [],
        'confessionarios' => [],
        'alianca' => null,
        'historico_aliancas' => [],
        'historico_popularidade' => [],

        'estatisticas' => [
            'lider' => 0,
            'anjo' => 0,
            'vip' => 0,
            'xepa' => 0,
            'monstro' => 0,
            'imune' => 0,
            'paredao' => 0
        ],

        'origem' => 'casa_vidro',

        /*
         * Campo privado da dinâmica.
         * É removido quando o candidato entra.
         */
        '_casa_vidro_forca_voto' =>
            max(
                20,
                $forcaVoto
            )
    ];
}


/* =========================================================
   👥 GERAR OS 4 CANDIDATOS
   ========================================================= */
function gerarCandidatosCasaVidroV2(
    $jogadores
) {
    if (
        !empty(
            $_SESSION[
                'casa_vidro_candidatos'
            ]
        ) &&
        is_array(
            $_SESSION[
                'casa_vidro_candidatos'
            ]
        )
    ) {
        return
            $_SESSION[
                'casa_vidro_candidatos'
            ];
    }

    $opcoes =
        opcoesCasaVidroV2();

    $usados =
        nomesUsadosCasaVidro(
            $jogadores
        );

    $nomesDisponiveis =
        array_values(
            array_filter(
                $opcoes['nomes'],
                function ($nome) use ($usados) {
                    $chave =
                        mb_strtolower(
                            trim((string)$nome),
                            'UTF-8'
                        );

                    return
                        !isset(
                            $usados[$chave]
                        );
                }
            )
        );

    shuffle($nomesDisponiveis);

    $candidatos = [];

    for ($i = 0; $i < 4; $i++) {

        if (!empty($nomesDisponiveis)) {
            $nome =
                array_shift(
                    $nomesDisponiveis
                );
        } else {
            $numero =
                $i + 1;

            do {
                $nome =
                    'Candidato '
                    . $numero;

                $numero++;

                $chave =
                    mb_strtolower(
                        $nome,
                        'UTF-8'
                    );

            } while (
                isset($usados[$chave])
            );
        }

        $usados[
            mb_strtolower(
                $nome,
                'UTF-8'
            )
        ] = true;

        $candidatos[] =
            criarCandidatoCasaVidroV2(
                $nome,
                $opcoes
            );
    }

    $_SESSION[
        'casa_vidro_candidatos'
    ] = $candidatos;

    return $candidatos;
}


/* =========================================================
   🎲 DECIDIR SE HAVERÁ CASA DE VIDRO
   ========================================================= */
function decidirCasaVidroRodada3(
    $jogadores,
    $rodada
) {
    inicializarCasaVidroV2();

    if ((int)$rodada !== 3) {
        return;
    }

    if (
        estadoCasaVidro()
        !== 'nao_decidida'
    ) {
        return;
    }

    /*
     * Temporadas muito pequenas não recebem
     * dois participantes novos.
     */
    if (count($jogadores) < 6) {
        $_SESSION[
            'casa_vidro_estado'
        ] = 'nao_ocorrera';

        return;
    }

    /*
     * Compatível com o modo de testes antigo.
     */
    $forcar =
        !empty(
            $_SESSION[
                'forcar_casa_vidro'
            ]
        );

    unset(
        $_SESSION[
            'forcar_casa_vidro'
        ]
    );

    $ocorrera =
        $forcar
        || rand(1, 100) <= 45;

    if (!$ocorrera) {
        $_SESSION[
            'casa_vidro_estado'
        ] = 'nao_ocorrera';

        return;
    }

    gerarCandidatosCasaVidroV2(
        $jogadores
    );

    $_SESSION[
        'casa_vidro_estado'
    ] = 'anuncio';

    $_SESSION[
        'casa_vidro_rodada'
    ] = 3;
}


/* =========================================================
   🔄 VERIFICAR FLUXO NO JOGO.PHP
   ========================================================= */
function verificarFluxoCasaVidro(
    &$jogadores,
    $fase,
    $rodada
) {
    inicializarCasaVidroV2();

    decidirCasaVidroRodada3(
        $jogadores,
        $rodada
    );

    $estado =
        estadoCasaVidro();

    /*
     * Início da Rodada 3:
     * mostra o anúncio uma vez.
     */
    if (
        (int)$rodada === 3 &&
        $estado === 'anuncio'
    ) {
        header(
            'Location: casa_vidro.php'
        );
        exit;
    }

    /*
     * Antes da Festa:
     * se o resultado já foi preparado,
     * impede que jogo.php pule a revelação.
     */
    if (
        (int)$rodada === 3 &&
        $fase === 'festa' &&
        $estado === 'resultado'
    ) {
        header(
            'Location: casa_vidro.php'
        );
        exit;
    }

    /*
     * Recuperação de save:
     * se um save foi encerrado na votação
     * e já está além da Rodada 3, não deixa
     * a dinâmica ficar eternamente pendente.
     */
    if (
        (int)$rodada > 3 &&
        in_array(
            $estado,
            [
                'anuncio',
                'votacao',
                'resultado'
            ],
            true
        )
    ) {
        $_SESSION[
            'casa_vidro_estado'
        ] = 'resultado';

        header(
            'Location: casa_vidro.php'
        );
        exit;
    }
}


/* =========================================================
   🎉 PREPARAR RESULTADO ANTES DA FESTA
   ========================================================= */
function prepararResultadoCasaVidroAntesFesta(
    $rodada
) {
    inicializarCasaVidroV2();

    if (
        (int)$rodada !== 3 ||
        estadoCasaVidro()
        !== 'votacao'
    ) {
        return false;
    }

    $_SESSION[
        'casa_vidro_estado'
    ] = 'resultado';

    return true;
}


/* =========================================================
   🗳️ CALCULAR RESULTADO
   ========================================================= */
function calcularResultadoCasaVidroV2()
{
    if (
        !empty(
            $_SESSION[
                'casa_vidro_ranking'
            ]
        ) &&
        is_array(
            $_SESSION[
                'casa_vidro_ranking'
            ]
        )
    ) {
        return
            $_SESSION[
                'casa_vidro_ranking'
            ];
    }

    $candidatos =
        $_SESSION[
            'casa_vidro_candidatos'
        ]
        ?? [];

    if (count($candidatos) < 2) {
        return [];
    }

    $pesos = [];

    foreach ($candidatos as $candidato) {
        $nome =
            trim(
                (string)(
                    $candidato['nome']
                    ?? ''
                )
            );

        if ($nome === '') {
            continue;
        }

        $pesos[$nome] =
            max(
                1,
                (int)(
                    $candidato[
                        '_casa_vidro_forca_voto'
                    ]
                    ?? rand(50, 100)
                )
            );
    }

    arsort($pesos);

    $total =
        array_sum($pesos);

    if ($total <= 0) {
        return [];
    }

    $ranking = [];
    $soma = 0.0;
    $nomes =
        array_keys($pesos);

    $ultimo =
        count($nomes) - 1;

    foreach (
        $nomes
        as $indice => $nome
    ) {
        if ($indice === $ultimo) {
            $pct =
                round(
                    100 - $soma,
                    2
                );
        } else {
            $pct =
                round(
                    (
                        $pesos[$nome]
                        / $total
                    ) * 100,
                    2
                );

            $soma += $pct;
        }

        $ranking[$nome] =
            max(
                0,
                $pct
            );
    }

    arsort($ranking);

    $_SESSION[
        'casa_vidro_ranking'
    ] = $ranking;

    $_SESSION[
        'casa_vidro_vencedores'
    ] = array_slice(
        array_keys($ranking),
        0,
        2
    );

    return $ranking;
}


/* =========================================================
   🔎 BUSCAR CANDIDATO
   ========================================================= */
function buscarCandidatoCasaVidroV2(
    $nome
) {
    foreach (
        $_SESSION[
            'casa_vidro_candidatos'
        ]
        ?? []
        as $candidato
    ) {
        if (
            nomeIgual(
                $candidato['nome']
                ?? '',
                $nome
            )
        ) {
            return $candidato;
        }
    }

    return null;
}


/* =========================================================
   ❤️ RELAÇÕES DOS NOVOS PARTICIPANTES
   ========================================================= */
function criarRelacoesEntrantesCasaVidroV2(
    &$jogadores,
    &$entrantes,
    $meuNome
) {
    /*
     * Moradores antigos -> entrantes.
     */
    foreach ($jogadores as &$morador) {

        $nomeMorador =
            $morador['nome']
            ?? '';

        if (
            !isset($morador['relacoes']) ||
            !is_array(
                $morador['relacoes']
            )
        ) {
            $morador['relacoes'] = [];
        }

        foreach ($entrantes as $novo) {
            $nomeNovo =
                $novo['nome']
                ?? '';

            if (
                $nomeNovo === '' ||
                nomeIgual(
                    $nomeMorador,
                    $nomeNovo
                )
            ) {
                continue;
            }

            if (
                !isset(
                    $morador[
                        'relacoes'
                    ][$nomeNovo]
                )
            ) {
                $morador[
                    'relacoes'
                ][$nomeNovo] = [
                    'amizade' =>
                        rand(22, 58),
                    'rivalidade' =>
                        rand(0, 22),
                    'confianca' =>
                        rand(20, 55)
                ];
            }
        }
    }

    unset($morador);

    /*
     * Entrantes -> moradores e entre si.
     */
    foreach ($entrantes as &$novo) {

        $nomeNovo =
            $novo['nome']
            ?? '';

        if (
            !isset($novo['relacoes']) ||
            !is_array(
                $novo['relacoes']
            )
        ) {
            $novo['relacoes'] = [];
        }

        foreach ($jogadores as $morador) {

            $nomeMorador =
                $morador['nome']
                ?? '';

            if (
                $nomeMorador === '' ||
                nomeIgual(
                    $nomeNovo,
                    $nomeMorador
                )
            ) {
                continue;
            }

            if (
                !isset(
                    $novo[
                        'relacoes'
                    ][$nomeMorador]
                )
            ) {
                $novo[
                    'relacoes'
                ][$nomeMorador] = [
                    'amizade' =>
                        rand(22, 58),
                    'rivalidade' =>
                        rand(0, 22),
                    'confianca' =>
                        rand(20, 55)
                ];
            }
        }

        foreach ($entrantes as $outro) {

            $nomeOutro =
                $outro['nome']
                ?? '';

            if (
                $nomeOutro === '' ||
                nomeIgual(
                    $nomeNovo,
                    $nomeOutro
                )
            ) {
                continue;
            }

            if (
                !isset(
                    $novo[
                        'relacoes'
                    ][$nomeOutro]
                )
            ) {
                $novo[
                    'relacoes'
                ][$nomeOutro] = [
                    'amizade' =>
                        rand(28, 65),
                    'rivalidade' =>
                        rand(0, 18),
                    'confianca' =>
                        rand(25, 60)
                ];
            }
        }

        if (
            !nomeIgual(
                $nomeNovo,
                $meuNome
            )
        ) {
            if (
                !isset(
                    $_SESSION[
                        'relacoes_jogador'
                    ]
                ) ||
                !is_array(
                    $_SESSION[
                        'relacoes_jogador'
                    ]
                )
            ) {
                $_SESSION[
                    'relacoes_jogador'
                ] = [];
            }

            if (
                !isset(
                    $_SESSION[
                        'relacoes_jogador'
                    ][$nomeNovo]
                )
            ) {
                $_SESSION[
                    'relacoes_jogador'
                ][$nomeNovo] = 0;
            }
        }
    }

    unset($novo);
}


/* =========================================================
   🚪 COLOCAR OS 2 VENCEDORES NA CASA
   ========================================================= */
function integrarVencedoresCasaVidroV2(
    &$jogadores,
    $meuNome
) {
    inicializarCasaVidroV2();

    if (
        estadoCasaVidro()
        !== 'resultado'
    ) {
        return false;
    }

    $ranking =
        calcularResultadoCasaVidroV2();

    $vencedores =
        $_SESSION[
            'casa_vidro_vencedores'
        ]
        ?? [];

    if (
        empty($ranking) ||
        count($vencedores) < 2
    ) {
        return false;
    }

    $entrantes = [];

    foreach ($vencedores as $nome) {

        /*
         * Não duplica se o botão for enviado duas vezes.
         */
        $jaExiste = false;

        foreach ($jogadores as $j) {
            if (
                nomeIgual(
                    $j['nome'] ?? '',
                    $nome
                )
            ) {
                $jaExiste = true;
                break;
            }
        }

        if ($jaExiste) {
            continue;
        }

        $candidato =
            buscarCandidatoCasaVidroV2(
                $nome
            );

        if (!$candidato) {
            continue;
        }

        unset(
            $candidato[
                '_casa_vidro_forca_voto'
            ]
        );

        /*
         * O percentual da votação não é usado como
         * barra de popularidade visível.
         */
        $candidato['popularidade'] =
            limitar(
                (int)(
                    $candidato[
                        'popularidade'
                    ]
                    ?? 50
                )
                + rand(1, 5),
                0,
                100
            );

        $candidato['origem'] =
            'casa_vidro';

        $entrantes[] =
            $candidato;
    }

    /*
     * Se os dois já tiverem sido adicionados por um duplo
     * envio, apenas finaliza o estado.
     */
    if (empty($entrantes)) {
        $_SESSION[
            'casa_vidro_estado'
        ] = 'finalizada';

        return true;
    }

    if (count($entrantes) < 2) {
        return false;
    }

    criarRelacoesEntrantesCasaVidroV2(
        $jogadores,
        $entrantes,
        $meuNome
    );

    foreach ($entrantes as $novo) {
        $jogadores[] = $novo;
    }

    if (
        function_exists(
            'removerParticipantesDuplicados'
        )
    ) {
        removerParticipantesDuplicados(
            $jogadores,
            $meuNome
        );
    }

    if (
        function_exists(
            'garantirEstruturaParticipantes'
        )
    ) {
        garantirEstruturaParticipantes(
            $jogadores
        );
    }

    if (
        function_exists(
            'garantirMeuJogadorNaLista'
        )
    ) {
        garantirMeuJogadorNaLista(
            $jogadores
        );
    }

    $_SESSION['jogadores'] =
        array_values(
            $jogadores
        );

    $_SESSION[
        'casa_vidro_estado'
    ] = 'finalizada';

    $_SESSION[
        'casa_vidro_finalizada_rodada'
    ] =
        (int)(
            $_SESSION['rodada']
            ?? 3
        );

    if (
        !isset(
            $_SESSION['evento_extra']
        ) ||
        !is_array(
            $_SESSION['evento_extra']
        )
    ) {
        $_SESSION['evento_extra'] = [];
    }

    $nomesEntrantes =
        array_values(
            array_map(
                function ($j) {
                    return
                        $j['nome']
                        ?? '';
                },
                $entrantes
            )
        );

    $_SESSION['evento_extra'][] =
        '🏠 '
        . implode(
            ' e ',
            $nomesEntrantes
        )
        . ' entraram oficialmente na casa pela Casa de Vidro durante a Festa da Rodada 3.';

    return true;
}
