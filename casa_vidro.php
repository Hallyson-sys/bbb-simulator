<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/includes/logica/utilitarios.php';
require_once __DIR__ . '/includes/logica/historico_temporada.php';
require_once __DIR__ . '/includes/logica/participantes.php';
require_once __DIR__ . '/includes/logica/inicializacao.php';
require_once __DIR__ . '/includes/logica/casa_vidro.php';

if (
    empty($_SESSION['jogadores']) ||
    !is_array($_SESSION['jogadores'])
) {
    header('Location: index.php');
    exit;
}

$jogadores =
    array_values(
        $_SESSION['jogadores']
    );

$meuNome =
    trim(
        (string)(
            $_SESSION['meu_nome']
            ?? ''
        )
    );

$rodada =
    (int)(
        $_SESSION['rodada']
        ?? 1
    );

inicializarCasaVidroV2();

$estado =
    estadoCasaVidro();


/* =========================================================
   ▶️ CONTINUAR APÓS O ANÚNCIO
   ========================================================= */
if (
    isset(
        $_POST[
            'continuar_casa_vidro'
        ]
    ) &&
    $estado === 'anuncio'
) {
    $_SESSION[
        'casa_vidro_estado'
    ] = 'votacao';

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

    $_SESSION['evento_extra'][] =
        '🏠 A votação da Casa de Vidro está aberta. O resultado será revelado antes da Festa da Rodada 3.';

    registrarHistoricoTemporada(
        'casa_vidro',
        'Casa de Vidro',
        'A Casa de Vidro foi anunciada e o público começou a decidir quem entraria no jogo.',
        [],
        '🏠',
        $rodada,
        'anuncio'
    );

    header('Location: jogo.php');
    exit;
}


/* =========================================================
   🚪 CONFIRMAR ENTRADA DOS VENCEDORES
   ========================================================= */
if (
    isset(
        $_POST[
            'confirmar_entrada_casa_vidro'
        ]
    ) &&
    $estado === 'resultado'
) {
    $ok =
        integrarVencedoresCasaVidroV2(
            $jogadores,
            $meuNome
        );

    if (!$ok) {
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

        $_SESSION['evento_extra'][] =
            '⚠️ Não foi possível concluir a entrada da Casa de Vidro.';

        header('Location: jogo.php');
        exit;
    }

    header('Location: jogo.php');
    exit;
}


/* =========================================================
   🔒 PROTEGER ACESSO DIRETO
   ========================================================= */
$estado =
    estadoCasaVidro();

if (
    !in_array(
        $estado,
        [
            'anuncio',
            'resultado'
        ],
        true
    )
) {
    header('Location: jogo.php');
    exit;
}


$candidatos =
    gerarCandidatosCasaVidroV2(
        $jogadores
    );

$ranking = [];
$vencedores = [];

if ($estado === 'resultado') {
    $ranking =
        calcularResultadoCasaVidroV2();

    $vencedores =
        $_SESSION[
            'casa_vidro_vencedores'
        ]
        ?? [];

    /*
     * Na tela de resultado, ordena do mais votado
     * para o menos votado.
     */
    usort(
        $candidatos,
        function ($a, $b) use ($ranking) {
            $nomeA =
                $a['nome']
                ?? '';

            $nomeB =
                $b['nome']
                ?? '';

            return
                ($ranking[$nomeB] ?? 0)
                <=>
                ($ranking[$nomeA] ?? 0);
        }
    );
}


function eCasaVidro($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Casa de Vidro — BBB Simulator
    </title>

    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;800;900&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="assets/css/casa_vidro.css"
    >
</head>

<body>

<div class="pagina-casa-vidro">

    <header class="casa-topo">

        <?php if ($estado === 'anuncio'): ?>

            <span class="casa-badge">
                🏠 DINÂMICA ESPECIAL • RODADA 3
            </span>

            <h1>
                Casa de Vidro
            </h1>

            <p>
                Quatro candidatos estão disputando
                <b>duas vagas</b> no BBB Simulator.
                O público vai decidir quem entra na casa.
            </p>

        <?php else: ?>

            <span class="casa-badge">
                📺 RESULTADO • ANTES DA FESTA
            </span>

            <h1>
                Casa de Vidro
            </h1>

            <p>
                A votação foi encerrada.
                É hora de descobrir quais dois participantes
                vão entrar na casa e aproveitar a Festa da Rodada 3.
            </p>

        <?php endif; ?>

    </header>


    <section class="casa-transmissao">

        <div class="transmissao-luz"></div>

        <span>
            ● AO VIVO
        </span>

        <?php if ($estado === 'anuncio'): ?>

            <p>
                A votação ficará aberta durante a Rodada 3.
                O resultado será revelado imediatamente antes da Festa.
            </p>

        <?php else: ?>

            <p>
                O público escolheu.
                Dois novos participantes estão prestes a atravessar a porta da casa.
            </p>

        <?php endif; ?>

    </section>


    <main class="candidatos-grid">

        <?php
        foreach (
            $candidatos
            as $indice => $candidato
        ):
        ?>

            <?php
            $nome =
                $candidato['nome']
                ?? '';

            $pct =
                (float)(
                    $ranking[$nome]
                    ?? 0
                );

            $venceu =
                in_array(
                    $nome,
                    $vencedores,
                    true
                );
            ?>


            <article
                class="
                    candidato-card
                    <?= $venceu ? 'vencedor' : '' ?>
                "
            >

                <div class="candidato-numero">

                    #<?= str_pad(
                        (string)($indice + 1),
                        2,
                        '0',
                        STR_PAD_LEFT
                    ) ?>

                </div>


                <?php
                if (
                    $estado === 'resultado' &&
                    $venceu
                ):
                ?>

                    <div class="badge-vencedor">
                        ENTRA NA CASA
                    </div>

                <?php endif; ?>


                <div class="candidato-avatar">

                    <?= eCasaVidro(
                        mb_strtoupper(
                            mb_substr(
                                $nome,
                                0,
                                1,
                                'UTF-8'
                            ),
                            'UTF-8'
                        )
                    ) ?>

                </div>


                <h2>
                    <?= eCasaVidro($nome) ?>
                </h2>


                <div class="dados-candidato">

                    <span>
                        🎂
                        <?= (int)(
                            $candidato['idade']
                            ?? 18
                        ) ?>
                        anos
                    </span>

                    <span>
                        📍
                        <?= eCasaVidro(
                            $candidato['estado']
                            ?? ''
                        ) ?>
                    </span>

                </div>


                <p class="profissao-candidato">

                    <?= eCasaVidro(
                        $candidato[
                            'profissao'
                        ]
                        ?? ''
                    ) ?>

                </p>


                <span class="personalidade-candidato">

                    <?= eCasaVidro(
                        $candidato[
                            'personalidade'
                        ]
                        ?? 'Neutro'
                    ) ?>

                </span>


                <?php if ($estado === 'resultado'): ?>

                    <div class="resultado-candidato">

                        <div class="resultado-topo">

                            <span>
                                VOTOS PARA ENTRAR
                            </span>

                            <strong>
                                <?= number_format(
                                    $pct,
                                    2,
                                    ',',
                                    '.'
                                ) ?>%
                            </strong>

                        </div>


                        <div class="barra-votos">

                            <div
                                class="barra-votos-fill"
                                style="
                                    width:
                                    <?= max(
                                        0,
                                        min(
                                            100,
                                            $pct
                                        )
                                    ) ?>%;
                                "
                            ></div>

                        </div>

                    </div>

                <?php else: ?>

                    <div class="candidato-status">
                        🗳️ EM VOTAÇÃO
                    </div>

                <?php endif; ?>

            </article>

        <?php endforeach; ?>

    </main>


    <section class="casa-acao">

        <?php if ($estado === 'anuncio'): ?>

            <div>
                <span class="casa-badge">
                    📡 VOTAÇÃO ABERTA
                </span>

                <h2>
                    A Rodada 3 continua normalmente
                </h2>

                <p>
                    O resultado será revelado antes da Festa.
                </p>
            </div>


            <form method="POST">

                <button
                    class="btn-revelar"
                    type="submit"
                    name="continuar_casa_vidro"
                    value="1"
                >
                    ▶️ CONTINUAR RODADA 3
                </button>

            </form>


        <?php else: ?>


            <div>
                <span class="casa-badge">
                    🚪 HORA DE ENTRAR
                </span>

                <h2>
                    A Festa vai ganhar dois novos participantes
                </h2>

                <p>
                    Ao abrir a porta, os dois mais votados
                    entram oficialmente e a Festa da Rodada 3 começa.
                </p>
            </div>


            <form method="POST">

                <button
                    class="btn-revelar"
                    type="submit"
                    name="confirmar_entrada_casa_vidro"
                    value="1"
                >
                    🚪 ABRIR A PORTA E COMEÇAR A FESTA
                </button>

            </form>

        <?php endif; ?>

    </section>

</div>

</body>
</html>
