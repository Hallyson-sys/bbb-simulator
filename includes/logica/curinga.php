<?php

/* =========================================================
   🎁 LÓGICA DO PODER CURINGA
   ========================================================= */

/* =========================
   🎁 PODER CURINGA
   Aparece apenas em algumas semanas e cria reviravoltas no jogo.
========================= */

if (
    !function_exists(
        'escolherEntreCandidatosNPCInteligente'
    )
) {
    require_once __DIR__ . '/inteligencia_npc.php';
}

function catalogoPoderesCuringa()
{
    return [
        "voto_duplo" => [
            "nome" => "Voto Duplo",
            "emoji" => "🗳️",
            "descricao" => "O voto do dono vale por dois na votação da casa."
        ],
        "imunidade_extra" => [
            "nome" => "Imunidade Extra",
            "emoji" => "🛡️",
            "descricao" => "O dono pode imunizar uma pessoa antes da formação do paredão."
        ],
        "anular_voto" => [
            "nome" => "Anular Voto",
            "emoji" => "🚫",
            "descricao" => "O dono escolhe uma pessoa e o voto dela será anulado."
        ],
        "espiao" => [
            "nome" => "Espião do Confessionário",
            "emoji" => "👁️",
            "descricao" => "O dono escolhe alguém para descobrir em quem essa pessoa votou."
        ],
        "contra_golpe" => [
            "nome" => "Contra-Golpe",
            "emoji" => "⚡",
            "descricao" => "Se o dono cair no paredão, ele puxa mais uma pessoa."
        ],
        "trocar_emparedado" => [
            "nome" => "Troca de Emparedado",
            "emoji" => "🔁",
            "descricao" => "O dono pode trocar um emparedado por outra pessoa, mas nunca pode tirar a indicação do líder."
        ]
    ];
}

function obterPoderCuringaAtual()
{
    return $_SESSION['poder_curinga'] ?? null;
}

function nomesJogadoresAtivos($jogadores)
{
    $nomes = [];

    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';
        if ($nome != '') {
            $nomes[] = $nome;
        }
    }

    return $nomes;
}

function prepararSorteioPoderCuringa($jogadores)
{
    $rodadaAtual = $_SESSION['rodada'] ?? 1;

    if ($rodadaAtual < 2) {
        return false;
    }

    if (($_SESSION['curinga_decidido_rodada'] ?? null) == $rodadaAtual) {
        return isset($_SESSION['poder_curinga']);
    }

    $_SESSION['curinga_decidido_rodada'] = $rodadaAtual;

    unset($_SESSION['poder_curinga']);
    unset($_SESSION['curinga_voto_duplo_ativo']);
    unset($_SESSION['curinga_anular_voto_de']);
    unset($_SESSION['curinga_espiar_voto_de']);
    unset($_SESSION['curinga_contra_golpe_usado']);
    unset($_SESSION['curinga_troca_usada']);
    unset($_SESSION['imunidade_curinga']);

    /* Nem toda semana tem Poder Curinga. Chance atual: 60%. */
    if (rand(1, 100) > 60) {
        $_SESSION['evento_extra'][] = "🎁 Nesta semana, não teremos Poder Curinga.";
        return false;
    }

    $catalogo = catalogoPoderesCuringa();
    $tipos = array_keys($catalogo);
    $tipo = $tipos[array_rand($tipos)];

    $nomes = nomesJogadoresAtivos($jogadores);
    if (empty($nomes)) {
        return false;
    }

    $dono = $nomes[array_rand($nomes)];

    $_SESSION['poder_curinga'] = [
        "tipo" => $tipo,
        "dono" => $dono,
        "usado" => false,
        "rodada" => $rodadaAtual
    ];

    $_SESSION['evento_extra'][] =
        "🎁 Poder Curinga da semana: <b>" . $catalogo[$tipo]['emoji'] . " " . $catalogo[$tipo]['nome'] . "</b>. Dono do poder: <b>$dono</b>.";

    return true;
}

function marcarPoderCuringaUsado()
{
    if (isset($_SESSION['poder_curinga'])) {
        $_SESSION['poder_curinga']['usado'] = true;
    }
}

function aplicarImunidadeCuringa(&$jogadores, $alvo, $dono)
{
    if ($alvo == '') return "⚠️ Escolha alguém para receber a imunidade.";

    foreach ($jogadores as &$j) {
        if (nomeIgual(($j['nome'] ?? ''), $alvo)) {
            if (!empty($j['status']['lider'])) {
                unset($j);
                return "⚠️ O líder já está protegido e não precisa receber a imunidade do Poder Curinga.";
            }

            $j['status']['imune'] = true;
            $_SESSION['imune'] = $alvo;
            $_SESSION['imunidade_curinga'] = $alvo;
            marcarPoderCuringaUsado();

            unset($j);
            return "🛡️ $dono usou o Poder Curinga e imunizou <b>$alvo</b>.";
        }
    }
    unset($j);

    return "⚠️ Participante inválido para imunizar.";
}

function ativarVotoDuploCuringa($dono)
{
    $_SESSION['curinga_voto_duplo_ativo'] = $dono;
    marcarPoderCuringaUsado();

    return "🗳️ $dono ativou o Poder Curinga. Seu voto valerá por dois na votação da casa.";
}

function escolherAlvoValidoCuringa($jogadores, $bloqueados = [])
{
    $opcoes = [];

    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';

        if ($nome == '') continue;
        if (in_array($nome, $bloqueados)) continue;
        if (!empty($j['status']['lider'])) continue;
        if (estaImune($jogadores, $j['nome'] ?? '')) continue;

        $opcoes[] = $nome;
    }

    if (empty($opcoes)) return null;

    return $opcoes[array_rand($opcoes)];
}

function usarPoderCuringaAutomaticoNPC(&$jogadores)
{
    $poder = obterPoderCuringaAtual();

    if (!$poder || !empty($poder['usado'])) return "";

    $dono = $poder['dono'] ?? '';
    $tipo = $poder['tipo'] ?? '';

    if ($dono == '' || $dono == ($_SESSION['meu_nome'] ?? '')) return "";

    if ($tipo == 'voto_duplo') {
        return ativarVotoDuploCuringa($dono);
    }

    if ($tipo == 'imunidade_extra') {
        $candidatos = [];

        foreach ($jogadores as $j) {
            $nome =
                $j['nome'] ?? '';

            if ($nome == '') {
                continue;
            }

            if (!empty($j['status']['lider'])) {
                continue;
            }

            if (
                function_exists('estaImune') &&
                estaImune(
                    $jogadores,
                    $nome
                )
            ) {
                continue;
            }

            $candidatos[] =
                $nome;
        }

        $alvo =
            escolherProtegidoNPCInteligente(
                $jogadores,
                $dono,
                $candidatos,
                true
            );

        if ($alvo == null) {
            $alvo = $dono;
        }

        $resultado =
            aplicarImunidadeCuringa(
                $jogadores,
                $alvo,
                $dono
            );

        if (
            !nomeIgual($alvo, $dono)
        ) {
            registrarMemoriaSocialNPC(
                $alvo,
                $dono,
                'me_imunizou',
                2,
                "$dono imunizou $alvo com o Poder Curinga.",
                'curinga_imunidade|' .
                ($_SESSION['rodada'] ?? 1) .
                "|$dono|$alvo"
            );
        }

        return $resultado;
    }

    if ($tipo == 'anular_voto') {
        $candidatos = [];

        foreach ($jogadores as $j) {
            $nome =
                $j['nome'] ?? '';

            if (
                $nome == '' ||
                nomeIgual($nome, $dono) ||
                !empty($j['status']['lider'])
            ) {
                continue;
            }

            $candidatos[] =
                $nome;
        }

        $alvo =
            escolherEntreCandidatosNPCInteligente(
                $jogadores,
                $dono,
                'anular_voto',
                $candidatos
            );

        if ($alvo != null) {
            $_SESSION['curinga_anular_voto_de'] = $alvo;
            marcarPoderCuringaUsado();

            return "🚫 $dono usou o Poder Curinga para anular o voto de <b>$alvo</b>.";
        }
    }

    if ($tipo == 'espiao') {
        $candidatos = [];

        foreach ($jogadores as $j) {
            $nome =
                $j['nome'] ?? '';

            if (
                $nome == '' ||
                nomeIgual($nome, $dono) ||
                !empty($j['status']['lider'])
            ) {
                continue;
            }

            $candidatos[] =
                $nome;
        }

        $alvo =
            escolherEntreCandidatosNPCInteligente(
                $jogadores,
                $dono,
                'espiar_voto',
                $candidatos
            );

        if ($alvo != null) {
            $_SESSION['curinga_espiar_voto_de'] = $alvo;
            marcarPoderCuringaUsado();

            return "👁️ $dono ganhou o direito de espiar um voto no confessionário.";
        }
    }

    /* Contra-golpe e troca ficam guardados para agir depois que o paredão for formado. */
    if ($tipo == 'contra_golpe' || $tipo == 'trocar_emparedado') {
        marcarPoderCuringaUsado();
        return "🎁 $dono guardou o Poder Curinga para usar na formação do paredão.";
    }

    return "";
}

function reaplicarImunidadeCuringa(&$jogadores)
{
    $imunizado = $_SESSION['imunidade_curinga'] ?? '';

    if ($imunizado == '') return;

    foreach ($jogadores as &$j) {
        if (nomeIgual(($j['nome'] ?? ''), $imunizado)) {
            if (empty($j['status']['lider'])) {
                $j['status']['imune'] = true;
            }
            break;
        }
    }
    unset($j);
}

function votoEstaAnuladoPeloCuringa($votante)
{
    return isset($_SESSION['curinga_anular_voto_de']) && nomeIgual($_SESSION['curinga_anular_voto_de'], $votante);
}

function pesoVotoCuringa($votante)
{
    if (isset($_SESSION['curinga_voto_duplo_ativo']) && nomeIgual($_SESSION['curinga_voto_duplo_ativo'], $votante)) {
        return 2;
    }

    return 1;
}

function aplicarEspiaoCuringaNoResultado($votosDetalhados)
{
    $alvo = $_SESSION['curinga_espiar_voto_de'] ?? '';
    $poder = obterPoderCuringaAtual();

    if ($alvo == '' || !$poder) return;

    foreach ($votosDetalhados as $votoInfo) {
        if (nomeIgual($votoInfo['votante'] ?? '', $alvo)) {
            $_SESSION['evento_extra'][] =
                "👁️ Poder Curinga revelou para " . $poder['dono'] . ": $alvo votou em " . $votoInfo['voto'] . ".";
            return;
        }
    }
}

function candidatosContraGolpeCuringa($jogadores, $paredaoAtual)
{
    $bloqueados = $paredaoAtual;

    if (isset($_SESSION['lider'])) {
        $bloqueados[] = $_SESSION['lider'];
    }

    if (isset($_SESSION['indicacao_lider'])) {
        $bloqueados[] = $_SESSION['indicacao_lider'];
    }

    if (isset($_SESSION['indicacao_bigfone'])) {
        $bloqueados[] = $_SESSION['indicacao_bigfone'];
    }

    $candidatos = [];

    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';

        if ($nome == '') continue;
        if (in_array($nome, $bloqueados)) continue;
        if (!empty($j['status']['lider'])) continue;
        if (estaImune($jogadores, $j['nome'] ?? '')) continue;

        $candidatos[] = $nome;
    }

    return $candidatos;
}

function aplicarContraGolpeCuringaNPC(&$jogadores, &$paredaoAtual)
{
    $poder = obterPoderCuringaAtual();

    if (!$poder || ($poder['tipo'] ?? '') != 'contra_golpe') return "";

    $dono = $poder['dono'] ?? '';

    if ($dono == '' || $dono == ($_SESSION['meu_nome'] ?? '')) return "";
    if (!in_array($dono, $paredaoAtual)) return "";
    if (isset($_SESSION['curinga_contra_golpe_usado'])) return "";

    $candidatos = candidatosContraGolpeCuringa($jogadores, $paredaoAtual);
    if (empty($candidatos)) return "";

    $alvo =
        escolherEntreCandidatosNPCInteligente(
            $jogadores,
            $dono,
            'contra_golpe',
            $candidatos
        );

    if ($alvo == null) {
        return "";
    }

    $paredaoAtual[] = $alvo;

    registrarMemoriaSocialNPC(
        $alvo,
        $dono,
        'me_puxou_contragolpe',
        2,
        "$dono puxou $alvo para o Paredão com o Contra-Golpe do Poder Curinga.",
        'curinga_contragolpe|' .
        ($_SESSION['rodada'] ?? 1) .
        "|$dono|$alvo"
    );

    $_SESSION['curinga_contra_golpe_usado'] = true;
    return "⚡ Pelo Contra-Golpe do Poder Curinga, $dono puxou <b>$alvo</b> para o paredão.";
}

function candidatosTrocaCuringaEntrada($jogadores, $paredaoAtual)
{
    $bloqueados = $paredaoAtual;

    if (isset($_SESSION['lider'])) {
        $bloqueados[] = $_SESSION['lider'];
    }

    $candidatos = [];

    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';

        if ($nome == '') continue;
        if (in_array($nome, $bloqueados)) continue;
        if (!empty($j['status']['lider'])) continue;
        if (estaImune($jogadores, $j['nome'] ?? '')) continue;

        $candidatos[] = $nome;
    }

    return $candidatos;
}

function aplicarTrocaEmparedadoCuringaNPC(&$jogadores, &$paredaoAtual)
{
    $poder = obterPoderCuringaAtual();

    if (!$poder || ($poder['tipo'] ?? '') != 'trocar_emparedado') return "";

    $dono = $poder['dono'] ?? '';

    if ($dono == '' || $dono == ($_SESSION['meu_nome'] ?? '')) return "";
    if (isset($_SESSION['curinga_troca_usada'])) return "";

    $indicacaoLider = $_SESSION['indicacao_lider'] ?? '';

    $saidas = array_values(array_filter($paredaoAtual, function ($nome) use ($indicacaoLider) {
        return !nomeIgual($nome, $indicacaoLider);
    }));

    $entradas = candidatosTrocaCuringaEntrada($jogadores, $paredaoAtual);

    if (empty($saidas) || empty($entradas)) return "";

    $sair =
        escolherEntreCandidatosNPCInteligente(
            $jogadores,
            $dono,
            'salvar_paredao',
            $saidas
        );

    $entrar =
        escolherEntreCandidatosNPCInteligente(
            $jogadores,
            $dono,
            'troca_emparedado',
            $entradas
        );

    if (
        $sair == null ||
        $entrar == null
    ) {
        return "";
    }

    foreach ($paredaoAtual as $i => $nome) {
        if (nomeIgual($nome, $sair)) {
            $paredaoAtual[$i] = $entrar;
            break;
        }
    }

    $paredaoAtual = array_values(array_unique($paredaoAtual));

    registrarMemoriaSocialNPC(
        $sair,
        $dono,
        'me_salvou',
        2,
        "$dono tirou $sair do Paredão usando o Poder Curinga.",
        'curinga_troca_salvou|' .
        ($_SESSION['rodada'] ?? 1) .
        "|$dono|$sair"
    );

    registrarMemoriaSocialNPC(
        $entrar,
        $dono,
        'me_colocou_paredao',
        2,
        "$dono colocou $entrar no Paredão usando o Poder Curinga.",
        'curinga_troca_entrou|' .
        ($_SESSION['rodada'] ?? 1) .
        "|$dono|$entrar"
    );

    $_SESSION['curinga_troca_usada'] = true;
    return "🔁 Pelo Poder Curinga, $dono tirou <b>$sair</b> do paredão e colocou <b>$entrar</b>. A indicação do líder foi preservada.";
}