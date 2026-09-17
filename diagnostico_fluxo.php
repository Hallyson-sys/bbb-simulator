<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

/*
 * DIAGNÓSTICO SEGURO DO FLUXO
 * -----------------------------------------
 * Este arquivo NÃO carrega jogo.php,
 * NÃO carrega includes do projeto
 * e NÃO faz redirect.
 *
 * Por isso ele continua abrindo mesmo quando
 * jogo.php está preso em ERR_TOO_MANY_REDIRECTS.
 */

$mensagem = '';

if (isset($_POST['preparar_anjo'])) {

    /*
     * Preserva:
     * - jogadores
     * - líder
     * - VIP/Xepa já definidos
     * - rodada
     * - relações
     * - popularidade
     * - moedas
     *
     * Limpa apenas restos da Prova do Anjo/Monstro
     * que podem ter vindo de uma semana anterior.
     */

    unset(
        $_SESSION['anjo'],
        $_SESSION['imune'],
        $_SESSION['monstro'],
        $_SESSION['monstro_definido'],
        $_SESSION['prova_anjo_finalizada'],
        $_SESSION['imunizacao_anjo_feita'],
        $_SESSION['anjo_autoimune'],
        $_SESSION['anjo_autoimune_estat_contada'],
        $_SESSION['prova_anjo_tipo'],
        $_SESSION['memoria_seq'],
        $_SESSION['caixa_certa'],
        $_SESSION['quiz_anjo']
    );

    if (
        isset($_SESSION['jogadores']) &&
        is_array($_SESSION['jogadores'])
    ) {
        foreach ($_SESSION['jogadores'] as &$j) {

            if (!isset($j['status']) || !is_array($j['status'])) {
                $j['status'] = [];
            }

            /*
             * NÃO mexe em Líder, VIP ou Xepa.
             */
            $j['status']['anjo'] = false;
            $j['status']['imune'] = false;
            $j['status']['monstro'] = false;
        }
        unset($j);
    }

    $_SESSION['fase_semana'] = 'anjo';

    $mensagem =
        '✅ O save foi preparado para a fase ANJO sem apagar Líder, VIP, Xepa, jogadores ou relações.';
}


if (isset($_POST['preparar_revelacao_vip'])) {

    /*
     * Se o VIP já foi calculado, não volta a calcular.
     * Caso contrário, deixa o save na tela de revelação.
     */
    if (isset($_SESSION['vip_definido'])) {
        $_SESSION['fase_semana'] = 'anjo';

        $mensagem =
            '✅ VIP/Xepa já estava definido. A fase foi posicionada em ANJO.';
    } else {
        $_SESSION['fase_semana'] = 'vip_xepa_revelar';

        $mensagem =
            '✅ A fase foi posicionada em VIP_XEPA_REVELAR.';
    }
}


function valorDebug($valor)
{
    if (is_bool($valor)) {
        return $valor ? 'true' : 'false';
    }

    if ($valor === null) {
        return 'null';
    }

    if (is_array($valor)) {
        return 'array(' . count($valor) . ')';
    }

    return (string)$valor;
}


$chaves = [
    'fase_semana',
    'rodada',
    'meu_nome',
    'lider',
    'vip_definido',
    'anjo',
    'prova_anjo_finalizada',
    'monstro',
    'monstro_definido',
    'imune',
    'imunizacao_anjo_feita',
    'anjo_autoimune',
    'bigfone_feito',
    'poder_curinga',
    'curinga_decidido_rodada',
    'acoes_restantes'
];

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico de Fluxo — BBB Simulator</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 32px;
            min-height: 100vh;
            background: #101217;
            color: #f4f4f7;
            font-family: Arial, sans-serif;
        }

        .wrap {
            width: min(920px, 100%);
            margin: 0 auto;
        }

        .box {
            margin-bottom: 20px;
            padding: 24px;
            border: 1px solid #343845;
            border-radius: 18px;
            background: #191c23;
        }

        h1, h2 {
            margin-top: 0;
        }

        .ok {
            padding: 14px 16px;
            margin-bottom: 20px;
            border-radius: 12px;
            background: #183726;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #30343d;
            vertical-align: top;
        }

        th {
            width: 42%;
            color: #cfd3dc;
        }

        code {
            color: #ff85cf;
        }

        .botoes {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        button,
        a.botao {
            display: inline-block;
            padding: 12px 16px;
            border: 0;
            border-radius: 12px;
            background: #7b2cff;
            color: white;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        button.secundario {
            background: #343945;
        }

        .aviso {
            color: #ffd28a;
            line-height: 1.55;
        }
    </style>
</head>

<body>

<div class="wrap">

    <div class="box">
        <h1>🔎 Diagnóstico do Fluxo</h1>

        <p>
            Este arquivo não inclui nenhuma lógica do jogo e não faz
            redirecionamentos. Ele serve para descobrir o estado real
            da sessão enquanto <code>jogo.php</code> está em loop.
        </p>
    </div>

    <?php if ($mensagem != ''): ?>
        <div class="ok">
            <?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>


    <div class="box">
        <h2>📋 Estado atual da sessão</h2>

        <table>
            <tr>
                <th>REQUEST_METHOD</th>
                <td><?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>

            <tr>
                <th>Session ID</th>
                <td><?php echo htmlspecialchars(session_id(), ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>

            <?php foreach ($chaves as $chave): ?>
                <tr>
                    <th><?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?></th>
                    <td>
                        <?php if (array_key_exists($chave, $_SESSION)): ?>
                            <?php echo htmlspecialchars(valorDebug($_SESSION[$chave]), ENT_QUOTES, 'UTF-8'); ?>
                        <?php else: ?>
                            <em>não definido</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <tr>
                <th>jogadores</th>
                <td>
                    <?php
                    echo isset($_SESSION['jogadores']) && is_array($_SESSION['jogadores'])
                        ? count($_SESSION['jogadores']) . ' participante(s)'
                        : 'não definido';
                    ?>
                </td>
            </tr>

            <tr>
                <th>POST recebido</th>
                <td>
                    <?php
                    echo empty($_POST)
                        ? 'nenhum'
                        : htmlspecialchars(
                            implode(', ', array_keys($_POST)),
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    ?>
                </td>
            </tr>
        </table>
    </div>


    <div class="box">
        <h2>🧯 Recuperação sem apagar a temporada</h2>

        <p class="aviso">
            O primeiro botão apenas remove estados antigos de Anjo/Monstro
            e posiciona a temporada na fase <b>ANJO</b>.
            Ele preserva o Líder e o VIP/Xepa que já foram definidos.
        </p>

        <div class="botoes">

            <form method="POST">
                <button name="preparar_anjo">
                    😇 Preparar save para Prova do Anjo
                </button>
            </form>

            <form method="POST">
                <button
                    class="secundario"
                    name="preparar_revelacao_vip"
                >
                    👑 Reposicionar VIP/Xepa
                </button>
            </form>

            <a class="botao" href="jogo.php">
                🎮 Testar jogo.php
            </a>

        </div>
    </div>


    <div class="box">
        <h2>📌 Próximo diagnóstico</h2>

        <p>
            Se <code>jogo.php</code> continuar em
            <b>ERR_TOO_MANY_REDIRECTS</b> mesmo depois de preparar o save
            para ANJO, o loop está em um dos arquivos carregados pelo
            jogo e não no estado VIP/Xepa.
        </p>
    </div>

</div>

</body>
</html>