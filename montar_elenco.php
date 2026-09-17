<?php

session_start();

require_once __DIR__ . '/data/opcoes_participantes.php';


/* =========================================================
   🛡️ VERIFICAR ACESSO
   ========================================================= */

if (
    !isset($_SESSION['tipo_elenco']) ||
    $_SESSION['tipo_elenco'] !== 'personalizado'
) {
    header('Location: index.php');
    exit;
}


/* =========================================================
   📊 DADOS DA TEMPORADA
   ========================================================= */

$qtdParticipantes = (int) (
    $_SESSION['qtd_participantes'] ?? 20
);

$meuNome = trim(
    $_SESSION['meu_nome'] ?? ''
);


/* =========================================================
   👥 CARREGAR ELENCO
   ========================================================= */

if (
    !isset($_SESSION['elenco_personalizado']) ||
    !is_array($_SESSION['elenco_personalizado'])
) {

    $_SESSION['elenco_personalizado'] = [];

    if (
        isset($_SESSION['meu_jogador_snapshot']) &&
        is_array($_SESSION['meu_jogador_snapshot'])
    ) {
        $_SESSION['elenco_personalizado'][] =
            $_SESSION['meu_jogador_snapshot'];
    }
}

$elenco = &$_SESSION['elenco_personalizado'];


/* =========================================================
   🧰 FUNÇÕES AUXILIARES
   ========================================================= */

function normalizarNome($nome)
{
    return mb_strtolower(
        trim($nome),
        'UTF-8'
    );
}


function nomeJaExiste(
    $nome,
    $elenco,
    $ignorarIndice = null
) {
    $nomeNormalizado = normalizarNome($nome);

    foreach ($elenco as $indice => $participante) {

        if (
            $ignorarIndice !== null &&
            $indice === $ignorarIndice
        ) {
            continue;
        }

        if (
            normalizarNome(
                $participante['nome'] ?? ''
            ) === $nomeNormalizado
        ) {
            return true;
        }
    }

    return false;
}


function criarParticipante(
    $nome,
    $idade,
    $profissao,
    $estado,
    $personalidade
) {
    return [

        'nome' => trim($nome),

        'idade' => (int) $idade,

        'profissao' => $profissao,

        'estado' => $estado,

        'personalidade' => $personalidade,

        'popularidade' => rand(40, 60),

        'humor' => rand(40, 60),

        'status' => [

            'lider' => false,

            'anjo' => false,

            'imune' => false,

            'vip' => false,

            'xepa' => true
        ],

        'relacoes' => [],

        'romances' => [],

        'confessionarios' => []
    ];
}


/* =========================================================
   💬 MENSAGENS
   ========================================================= */

$erro = '';
$sucesso = '';



/* =========================================================
   ➕ ADICIONAR PARTICIPANTE
   ========================================================= */

if (isset($_POST['adicionar_participante'])) {

    if (count($elenco) >= $qtdParticipantes) {

        $erro = 'O elenco já está completo.';

    } else {

        $nome = trim(
            $_POST['nome_participante'] ?? ''
        );

        $idade = (int) (
            $_POST['idade_participante'] ?? 18
        );

        $profissao = trim(
            $_POST['profissao_participante'] ?? ''
        );

        $estado = trim(
            $_POST['estado_participante'] ?? ''
        );

        $personalidade = trim(
            $_POST['personalidade_participante'] ?? ''
        );


        if ($nome === '') {

            $erro = 'Digite o nome do participante.';

        } elseif ($idade < 18) {

            $erro =
                'Os participantes precisam ter pelo menos 18 anos.';

        } elseif ($idade > 100) {

            $erro = 'Digite uma idade válida.';

        } elseif ($profissao === '') {

            $erro = 'Escolha uma profissão.';

        } elseif ($estado === '') {

            $erro = 'Escolha um estado.';

        } elseif ($personalidade === '') {

            $erro = 'Escolha uma personalidade.';

        } elseif (
            nomeJaExiste(
                $nome,
                $elenco
            )
        ) {

            $erro =
                'Já existe um participante com esse nome.';

        } else {

            $elenco[] = criarParticipante(
                $nome,
                $idade,
                $profissao,
                $estado,
                $personalidade
            );

            $sucesso =
                "$nome foi adicionado ao elenco.";
        }
    }
}


/* =========================================================
   🗑️ REMOVER PARTICIPANTE
   ========================================================= */

if (isset($_POST['remover_participante'])) {

    $indice = (int) (
        $_POST['indice'] ?? -1
    );

    if (isset($elenco[$indice])) {

        $nomeRemover =
            $elenco[$indice]['nome'] ?? '';

        /*
         * O participante principal nunca pode ser removido.
         */
        if (
            normalizarNome($nomeRemover) ===
            normalizarNome($meuNome)
        ) {

            $erro =
                'Você não pode remover seu próprio participante.';

        } else {

            array_splice(
                $elenco,
                $indice,
                1
            );

            $sucesso =
                "$nomeRemover foi removido do elenco.";
        }
    }
}


/* =========================================================
   ✏️ SALVAR EDIÇÃO
   ========================================================= */

if (isset($_POST['salvar_edicao'])) {

    $indice = (int) (
        $_POST['indice'] ?? -1
    );

    if (isset($elenco[$indice])) {

        $nomeAtual =
            $elenco[$indice]['nome'] ?? '';

        /*
         * Não permite editar o jogador principal nessa tela.
         */
        if (
            normalizarNome($nomeAtual) ===
            normalizarNome($meuNome)
        ) {

            $erro =
                'Seu participante deve ser editado na tela inicial.';

        } else {

            $nome = trim(
                $_POST['nome_participante'] ?? ''
            );

            $idade = (int) (
                $_POST['idade_participante'] ?? 18
            );

            $profissao = trim(
                $_POST['profissao_participante'] ?? ''
            );

            $estado = trim(
                $_POST['estado_participante'] ?? ''
            );

            $personalidade = trim(
                $_POST['personalidade_participante'] ?? ''
            );


            if ($nome === '') {

                $erro =
                    'Digite o nome do participante.';

            } elseif ($idade < 18 || $idade > 100) {

                $erro =
                    'Digite uma idade válida.';

            } elseif (
                nomeJaExiste(
                    $nome,
                    $elenco,
                    $indice
                )
            ) {

                $erro =
                    'Já existe outro participante com esse nome.';

            } else {

                /*
                 * Preserva dados internos que possam existir.
                 */
                $elenco[$indice]['nome'] =
                    $nome;

                $elenco[$indice]['idade'] =
                    $idade;

                $elenco[$indice]['profissao'] =
                    $profissao;

                $elenco[$indice]['estado'] =
                    $estado;

                $elenco[$indice]['personalidade'] =
                    $personalidade;

                $sucesso =
                    "$nome foi atualizado.";
            }
        }
    }
}


/* =========================================================
   🎲 PREENCHER VAGAS RESTANTES
   ========================================================= */

if (isset($_POST['preencher_automaticamente'])) {

    $nomesDisponiveis = [];

    foreach ($nomesNPC as $nomeNPC) {

        if (
            !nomeJaExiste(
                $nomeNPC,
                $elenco
            )
        ) {
            $nomesDisponiveis[] =
                $nomeNPC;
        }
    }

    shuffle($nomesDisponiveis);


    while (
        count($elenco) < $qtdParticipantes
    ) {

        if (!empty($nomesDisponiveis)) {

            $nomeAleatorio =
                array_shift($nomesDisponiveis);

        } else {

            /*
             * Fallback caso algum dia o jogo tenha
             * mais participantes do que nomes cadastrados.
             */

            $numero =
                count($elenco) + 1;

            $nomeAleatorio =
                'Participante ' . $numero;

            while (
                nomeJaExiste(
                    $nomeAleatorio,
                    $elenco
                )
            ) {

                $numero++;

                $nomeAleatorio =
                    'Participante ' . $numero;
            }
        }


        $elenco[] = criarParticipante(

            $nomeAleatorio,

            rand(18, 55),

            $profissoes[
                array_rand($profissoes)
            ],

            $estados[
                array_rand($estados)
            ],

            $personalidades[
                array_rand($personalidades)
            ]
        );
    }

    $sucesso =
        'As vagas restantes foram preenchidas automaticamente.';
}


/* =========================================================
   🏠 INICIAR TEMPORADA
   ========================================================= */

if (isset($_POST['iniciar_temporada'])) {

    if (
        count($elenco) !== $qtdParticipantes
    ) {

        $erro =
            "Complete o elenco antes de iniciar a temporada.";

    } else {

        /*
         * Cria as relações entre TODOS os participantes
         * somente quando o elenco estiver finalizado.
         */

        foreach ($elenco as &$j1) {

            $j1['relacoes'] = [];

            foreach ($elenco as $j2) {

                if (
                    normalizarNome(
                        $j1['nome']
                    ) ===
                    normalizarNome(
                        $j2['nome']
                    )
                ) {
                    continue;
                }

                $j1['relacoes'][
                    $j2['nome']
                ] = [

                    'amizade' =>
                        rand(20, 80),

                    'rivalidade' =>
                        rand(0, 50),

                    'confianca' =>
                        rand(20, 80)
                ];
            }
        }

        unset($j1);


        /*
         * Copia o elenco personalizado para
         * o array principal do jogo.
         */

        $jogadores = $elenco;

        shuffle($jogadores);


        $_SESSION['jogadores'] =
            array_values($jogadores);

        $_SESSION['rodada'] = 1;


        /*
         * Atualiza o snapshot do jogador principal.
         */

        foreach ($jogadores as $jogador) {

            if (
                normalizarNome(
                    $jogador['nome'] ?? ''
                ) ===
                normalizarNome($meuNome)
            ) {

                $_SESSION[
                    'meu_jogador_snapshot'
                ] = $jogador;

                break;
            }
        }


        header('Location: jogo.php');
        exit;
    }
}


/* =========================================================
   ✏️ PARTICIPANTE SENDO EDITADO
   ========================================================= */

$indiceEditar = null;
$participanteEditar = null;

if (isset($_GET['editar'])) {

    $indice = (int) $_GET['editar'];

    if (isset($elenco[$indice])) {

        $nomeEditar =
            $elenco[$indice]['nome'] ?? '';

        if (
            normalizarNome($nomeEditar) !==
            normalizarNome($meuNome)
        ) {

            $indiceEditar = $indice;

            $participanteEditar =
                $elenco[$indice];
        }
    }
}


/* =========================================================
   📊 CONTADORES
   ========================================================= */

$totalAtual = count($elenco);

$vagasRestantes =
    max(
        0,
        $qtdParticipantes - $totalAtual
    );

$porcentagem =
    $qtdParticipantes > 0
        ? ($totalAtual / $qtdParticipantes) * 100
        : 0;

?>
<!DOCTYPE html>

<html lang="pt-br">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Montar Elenco - BBB Simulator</title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;800;900&display=swap"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="assets/css/montar_elenco.css"
>

</head>


<body>


<div class="pagina-elenco">


    <!-- =====================================================
         CABEÇALHO
    ====================================================== -->

    <header class="cabecalho-elenco">

        <div class="marca">
            BBB SIMULATOR
        </div>

        <span class="etapa">
            CRIAÇÃO DA TEMPORADA
        </span>

        <h1>
            Monte seu próprio elenco
        </h1>

        <p>
            Escolha quem vai entrar na casa mais
            vigiada do Brasil.
        </p>

    </header>



    <!-- =====================================================
         PROGRESSO
    ====================================================== -->

    <section class="painel-progresso">

        <div class="progresso-topo">

            <div>

                <span>
                    PARTICIPANTES
                </span>

                <strong>
                    <?= $totalAtual ?>
                    /
                    <?= $qtdParticipantes ?>
                </strong>

            </div>

            <div class="vagas">

                <?php if ($vagasRestantes > 0): ?>

                    <?= $vagasRestantes ?>
                    vaga<?= $vagasRestantes != 1 ? 's' : '' ?>
                    restante<?= $vagasRestantes != 1 ? 's' : '' ?>

                <?php else: ?>

                    ✓ ELENCO COMPLETO

                <?php endif; ?>

            </div>

        </div>


        <div class="barra-progresso">

            <div
                class="barra-preenchida"
                style="width:
                <?= min(100, $porcentagem) ?>%"
            ></div>

        </div>

    </section>



    <!-- =====================================================
         MENSAGENS
    ====================================================== -->

    <?php if ($erro !== ''): ?>

        <div class="mensagem erro">
            ⚠️ <?= htmlspecialchars($erro) ?>
        </div>

    <?php endif; ?>


    <?php if ($sucesso !== ''): ?>

        <div class="mensagem sucesso">
            ✓ <?= htmlspecialchars($sucesso) ?>
        </div>

    <?php endif; ?>



    <main class="conteudo-elenco">


        <!-- =================================================
             LISTA
        ================================================== -->

        <section class="area-participantes">

            <div class="titulo-secao">

                <div>

                    <span>
                        CAST OFICIAL
                    </span>

                    <h2>
                        Participantes
                    </h2>

                </div>

            </div>



            <div class="grid-participantes">

                <?php foreach ($elenco as $indice => $participante): ?>

                    <?php

                    $ehMeuJogador =
                        normalizarNome(
                            $participante['nome'] ?? ''
                        )
                        ===
                        normalizarNome($meuNome);

                    ?>

                    <article
                        class="card-participante
                        <?= $ehMeuJogador
                            ? 'meu-participante'
                            : ''
                        ?>"
                    >

                        <div class="numero-participante">
                            #
                            <?= str_pad(
                                $indice + 1,
                                2,
                                '0',
                                STR_PAD_LEFT
                            ) ?>
                        </div>


                        <?php if ($ehMeuJogador): ?>

                            <div class="badge-voce">
                                VOCÊ
                            </div>

                        <?php endif; ?>


                        <div class="avatar">
                            <?= mb_strtoupper(
                                mb_substr(
                                    $participante['nome'],
                                    0,
                                    1,
                                    'UTF-8'
                                ),
                                'UTF-8'
                            ) ?>
                        </div>


                        <h3>
                            <?= htmlspecialchars(
                                $participante['nome']
                            ) ?>
                        </h3>


                        <div class="dados-participante">

                            <span>
                                🎂
                                <?= (int)
                                    $participante['idade']
                                ?>
                                anos
                            </span>

                            <span>
                                📍
                                <?= htmlspecialchars(
                                    $participante['estado']
                                ) ?>
                            </span>

                        </div>


                        <p class="profissao">
                            <?= htmlspecialchars(
                                $participante['profissao']
                            ) ?>
                        </p>


                        <span class="personalidade">
                            <?= htmlspecialchars(
                                $participante[
                                    'personalidade'
                                ]
                            ) ?>
                        </span>


                        <?php if (!$ehMeuJogador): ?>

                            <div class="acoes-card">

                                <a
                                    href="?editar=<?= $indice ?>"
                                    class="btn-editar"
                                >
                                    ✏️ Editar
                                </a>


                                <form
                                    method="POST"
                                    onsubmit="
                                    return confirm(
                                    'Remover este participante?'
                                    );
                                    "
                                >

                                    <input
                                        type="hidden"
                                        name="indice"
                                        value="<?= $indice ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="remover_participante"
                                        class="btn-remover"
                                    >
                                        🗑️
                                    </button>

                                </form>

                            </div>

                        <?php endif; ?>

                    </article>

                <?php endforeach; ?>


                <?php for (
                    $i = $totalAtual;
                    $i < $qtdParticipantes;
                    $i++
                ): ?>

                    <article class="card-vazio">

                        <div class="icone-vaga">
                            +
                        </div>

                        <span>
                            VAGA
                            <?= $i + 1 ?>
                        </span>

                        <p>
                            Aguardando participante
                        </p>

                    </article>

                <?php endfor; ?>

            </div>

        </section>



        <!-- =================================================
             FORMULÁRIO
        ================================================== -->

        <aside class="painel-formulario">


            <?php if ($participanteEditar): ?>

                <span class="mini-titulo">
                    EDITAR PARTICIPANTE
                </span>

                <h2>
                    Alterar perfil
                </h2>

                <p>
                    Atualize as informações deste
                    participante.
                </p>

            <?php else: ?>

                <span class="mini-titulo">
                    NOVO PARTICIPANTE
                </span>

                <h2>
                    Adicionar ao elenco
                </h2>

                <p>
                    Crie manualmente cada integrante
                    da temporada.
                </p>

            <?php endif; ?>


            <?php if ($totalAtual < $qtdParticipantes || $participanteEditar): ?>

                <form
                    method="POST"
                    class="form-participante"
                >

                    <?php if ($participanteEditar): ?>

                        <input
                            type="hidden"
                            name="indice"
                            value="<?= $indiceEditar ?>"
                        >

                    <?php endif; ?>


                    <label>

                        Nome

                        <input
                            type="text"
                            name="nome_participante"
                            placeholder="Nome do participante"
                            maxlength="40"
                            required
                            value="<?= htmlspecialchars(
                                $participanteEditar[
                                    'nome'
                                ] ?? ''
                            ) ?>"
                        >

                    </label>


                    <label>

                        Idade

                        <input
                            type="number"
                            name="idade_participante"
                            min="18"
                            max="100"
                            placeholder="18"
                            required
                            value="<?= htmlspecialchars(
                                $participanteEditar[
                                    'idade'
                                ] ?? ''
                            ) ?>"
                        >

                    </label>


                    <label>

                        Profissão

                        <select
                            name="profissao_participante"
                            required
                        >

                            <option value="">
                                Escolha uma profissão
                            </option>

                            <?php foreach ($profissoes as $opcao): ?>

                                <option
                                    value="<?= htmlspecialchars(
                                        $opcao
                                    ) ?>"
                                    <?= (
                                        ($participanteEditar[
                                            'profissao'
                                        ] ?? '') === $opcao
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    <?= htmlspecialchars(
                                        $opcao
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </label>


                    <div class="linha-dupla">

                        <label>

                            Estado

                            <select
                                name="estado_participante"
                                required
                            >

                                <option value="">
                                    Estado
                                </option>

                                <?php foreach ($estados as $opcao): ?>

                                    <option
                                        value="<?= $opcao ?>"
                                        <?= (
                                            ($participanteEditar[
                                                'estado'
                                            ] ?? '') === $opcao
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        <?= $opcao ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </label>


                        <label>

                            Personalidade

                            <select
                                name="personalidade_participante"
                                required
                            >

                                <option value="">
                                    Personalidade
                                </option>

                                <?php foreach ($personalidades as $opcao): ?>

                                    <option
                                        value="<?= htmlspecialchars(
                                            $opcao
                                        ) ?>"
                                        <?= (
                                            ($participanteEditar[
                                                'personalidade'
                                            ] ?? '') === $opcao
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        <?= htmlspecialchars(
                                            $opcao
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </label>

                    </div>


                    <?php if ($participanteEditar): ?>

                        <button
                            type="submit"
                            name="salvar_edicao"
                            class="btn-principal"
                        >
                            SALVAR ALTERAÇÕES
                        </button>


                        <a
                            href="montar_elenco.php"
                            class="btn-cancelar"
                        >
                            Cancelar edição
                        </a>

                    <?php else: ?>

                        <button
                            type="submit"
                            name="adicionar_participante"
                            class="btn-principal"
                        >
                            + ADICIONAR PARTICIPANTE
                        </button>

                    <?php endif; ?>

                </form>

            <?php else: ?>

                <div class="elenco-finalizado">

                    <div>
                        ✓
                    </div>

                    <strong>
                        Elenco completo!
                    </strong>

                    <p>
                        Todos os participantes
                        já foram definidos.
                    </p>

                </div>

            <?php endif; ?>



            <!-- =============================================
                 PREENCHIMENTO AUTOMÁTICO
            ============================================== -->

            <?php if (
                $totalAtual < $qtdParticipantes &&
                !$participanteEditar
            ): ?>

                <div class="separador">

                    <span>
                        OU
                    </span>

                </div>


                <form method="POST">

                    <button
                        type="submit"
                        name="preencher_automaticamente"
                        class="btn-automatico"
                    >
                        🎲 PREENCHER
                        <?= $vagasRestantes ?>
                        VAGA<?= $vagasRestantes != 1 ? 'S' : '' ?>
                        AUTOMATICAMENTE
                    </button>

                </form>

            <?php endif; ?>



            <!-- =============================================
                 INICIAR TEMPORADA
            ============================================== -->

            <div class="iniciar-jogo">

                <form method="POST">

                    <button
                        type="submit"
                        name="iniciar_temporada"
                        class="btn-iniciar"
                        <?= $totalAtual !== $qtdParticipantes
                            ? 'disabled'
                            : ''
                        ?>
                    >
                        🏠 ENTRAR NA CASA
                    </button>

                </form>


                <?php if ($totalAtual !== $qtdParticipantes): ?>

                    <small>
                        Complete todas as vagas
                        para começar a temporada.
                    </small>

                <?php else: ?>

                    <small class="pronto">
                        O elenco está pronto.
                        A temporada pode começar!
                    </small>

                <?php endif; ?>

            </div>


        </aside>

    </main>

</div>


</body>
</html>