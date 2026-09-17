<?php

/* =========================================================
   📈 LÓGICA DE POPULARIDADE
   ========================================================= */
   
function alterarPopularidade(&$jogadores, $nome, $valor)
{

    foreach ($jogadores as &$j) {

        if ($j['nome'] == $nome) {

            $j['popularidade'] =
                limitar(($j['popularidade'] ?? 50) + $valor, 0, 100);
        }
    }
}


function alterarPopularidadeMotivo(&$jogadores, $nome, $min, $max, $motivo, $mostrarNoAoVivo = true)
{

    if ($nome == '') return 0;

    $valor = rand($min, $max);

    if ($valor == 0) {
        return 0;
    }

    alterarPopularidade($jogadores, $nome, $valor);

    if ($mostrarNoAoVivo) {
        if (!isset($_SESSION['evento_extra'])) {
            $_SESSION['evento_extra'] = [];
        }

        if ($valor > 0) {
            $_SESSION['evento_extra'][] = "📈 $nome ganhou $valor de popularidade: $motivo.";
        } else {
            $_SESSION['evento_extra'][] = "📉 $nome perdeu " . abs($valor) . " de popularidade: $motivo.";
        }
    }

    return $valor;
}


/* =========================
   📈 POPULARIDADE PÚBLICA AVANÇADA
   A popularidade agora representa a reação do público e influencia
   diretamente a permanência no resultado.php.
========================= */

function obterPopularidadeJogador($jogadores, $nome)
{
    foreach ($jogadores as $j) {
        if (($j['nome'] ?? '') == $nome) {
            return $j['popularidade'] ?? 50;
        }
    }

    return 50;
}

function nivelPopularidadePublica($popularidade)
{
    if ($popularidade >= 90) return "favorito";
    if ($popularidade >= 70) return "querido";
    if ($popularidade >= 50) return "neutro";
    if ($popularidade >= 30) return "mal_visto";
    return "cancelado";
}

function descricaoPopularidadePublica($popularidade)
{
    $nivel = nivelPopularidadePublica($popularidade);

    if ($nivel == "favorito") return "favorito do público";
    if ($nivel == "querido") return "querido pelo público";
    if ($nivel == "neutro") return "dividindo opiniões";
    if ($nivel == "mal_visto") return "mal visto pelo público";
    return "cancelado nas redes";
}

function registrarHistoricoPopularidade(&$jogadores, $nome, $valor, $motivo)
{
    foreach ($jogadores as &$j) {
        if (($j['nome'] ?? '') == $nome) {
            if (!isset($j['historico_popularidade']) || !is_array($j['historico_popularidade'])) {
                $j['historico_popularidade'] = [];
            }

            $j['historico_popularidade'][] = [
                "rodada" => $_SESSION['rodada'] ?? 1,
                "valor" => $valor,
                "motivo" => $motivo,
                "popularidade" => $j['popularidade'] ?? 50
            ];

            if (count($j['historico_popularidade']) > 20) {
                $j['historico_popularidade'] = array_slice($j['historico_popularidade'], -20);
            }

            break;
        }
    }
    unset($j);
}

function alterarPopularidadePublica(&$jogadores, $nome, $min, $max, $motivo, $mostrarNoAoVivo = true)
{
    if ($nome == '') return 0;

    $valor = rand($min, $max);

    if ($valor == 0) {
        return 0;
    }

    alterarPopularidade($jogadores, $nome, $valor);
    registrarHistoricoPopularidade($jogadores, $nome, $valor, $motivo);

    if ($mostrarNoAoVivo) {
        if (!isset($_SESSION['evento_extra'])) {
            $_SESSION['evento_extra'] = [];
        }

        if ($valor > 0) {
            $_SESSION['evento_extra'][] = "📈 O público reagiu bem: $nome ganhou $valor de popularidade ($motivo).";
        } else {
            $_SESSION['evento_extra'][] = "📉 O público reagiu mal: $nome perdeu " . abs($valor) . " de popularidade ($motivo).";
        }
    }

    return $valor;
}

function impactoPopularidadePorPersonalidade(&$jogadores, $nome, $tipo, $mostrarNoAoVivo = false)
{
    $personalidade = "Neutro";

    foreach ($jogadores as $j) {
        if (($j['nome'] ?? '') == $nome) {
            $personalidade = $j['personalidade'] ?? 'Neutro';
            break;
        }
    }

    $min = 0;
    $max = 0;
    $motivo = "movimentou o jogo";

    if ($tipo == "vt") {
        $min = -5;
        $max = 8;
        $motivo = "tentou render VT";
        if ($personalidade == "Influencer") {
            $min = -4;
            $max = 12;
        }
        if ($personalidade == "Planta") {
            $min = -8;
            $max = 4;
        }
        if ($personalidade == "Barraqueiro") {
            $min = -6;
            $max = 10;
        }
    }

    if ($tipo == "fofoca") {
        $min = -8;
        $max = 3;
        $motivo = "se envolveu em fofoca";
        if ($personalidade == "Manipulador" || $personalidade == "Falso") {
            $min = -12;
            $max = 2;
        }
    }

    if ($tipo == "treta") {
        $min = -8;
        $max = 6;
        $motivo = "entrou em uma treta";
        if ($personalidade == "Barraqueiro" || $personalidade == "Explosivo") {
            $min = -10;
            $max = 10;
        }
        if ($personalidade == "Fofo") {
            $min = -10;
            $max = 2;
        }
    }

    if ($tipo == "romance") {
        $min = 1;
        $max = 6;
        $motivo = "viveu um momento de romance";
        if ($personalidade == "Fofo" || $personalidade == "Emocional") {
            $max = 8;
        }
    }

    if ($tipo == "alianca") {
        $min = 1;
        $max = 4;
        $motivo = "fortaleceu uma aliança";
        if ($personalidade == "Manipulador" || $personalidade == "Falso") {
            $min = -2;
            $max = 3;
        }
    }

    if ($tipo == "planta") {
        $min = -4;
        $max = -1;
        $motivo = "ficou apagado demais";
    }

    if ($tipo == "confessionario_bom") {
        $min = 1;
        $max = 5;
        $motivo = "fez um confessionário marcante";
        if ($personalidade == "Influencer") {
            $max = 7;
        }
    }

    if ($tipo == "confessionario_ruim") {
        $min = -5;
        $max = -1;
        $motivo = "soou mal no confessionário";
    }

    if ($min == 0 && $max == 0) {
        return 0;
    }

    return alterarPopularidadePublica($jogadores, $nome, $min, $max, $motivo, $mostrarNoAoVivo);
}

function aplicarImpactoPublicoConfessionario(&$jogadores, $nome, $tipo)
{
    if ($tipo == "vt" || $tipo == "sonho_vitoria") {
        return impactoPopularidadePorPersonalidade($jogadores, $nome, "confessionario_bom", false);
    }

    if ($tipo == "romance" || $tipo == "desabafo") {
        return impactoPopularidadePorPersonalidade($jogadores, $nome, "romance", false);
    }

    if ($tipo == "rival" || $tipo == "vinganca" || $tipo == "falsidade") {
        return impactoPopularidadePorPersonalidade($jogadores, $nome, "treta", false);
    }

    if ($tipo == "estrategia") {
        return alterarPopularidadePublica($jogadores, $nome, -3, 4, "mostrou estratégia no confessionário", false);
    }

    if ($tipo == "neutro" || $tipo == "observacao") {
        return alterarPopularidadePublica($jogadores, $nome, -1, 2, "teve um confessionário discreto", false);
    }

    return 0;
}

function aplicarDesgasteSemanalPublico(&$jogadores)
{
    $chave = 'desgaste_publico_rodada_' . ($_SESSION['rodada'] ?? 1);

    if (isset($_SESSION[$chave])) {
        return;
    }

    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';
        if ($nome == '') continue;

        $personalidade = $j['personalidade'] ?? 'Neutro';

        if ($personalidade == 'Planta') {
            alterarPopularidadePublica($jogadores, $nome, -2, 0, "passou a semana apagado", false);
        }

        if (!empty($j['status']['monstro'])) {
            alterarPopularidadePublica($jogadores, $nome, -3, -1, "sofreu desgaste com o Monstro", false);
        }

        if (!empty($j['status']['xepa']) && rand(1, 100) <= 25) {
            alterarPopularidadePublica($jogadores, $nome, -1, 1, "teve pouca visibilidade na Xepa", false);
        }

        if (!empty($j['status']['vip']) && rand(1, 100) <= 20) {
            alterarPopularidadePublica($jogadores, $nome, -1, 2, "apareceu mais no VIP", false);
        }
    }

    $_SESSION[$chave] = true;
}


function ajustarPopularidadePorAlvo(&$jogadores, $autor, $alvo, $motivoPositivo, $motivoNegativo)
{

    $popularidadeAlvo = 50;

    foreach ($jogadores as $j) {
        if (($j['nome'] ?? '') == $alvo) {
            $popularidadeAlvo = $j['popularidade'] ?? 50;
            break;
        }
    }

    if ($popularidadeAlvo <= 35) {
        return alterarPopularidadeMotivo($jogadores, $autor, 3, 8, $motivoPositivo);
    }

    if ($popularidadeAlvo >= 65) {
        return alterarPopularidadeMotivo($jogadores, $autor, -8, -3, $motivoNegativo);
    }

    return alterarPopularidadeMotivo($jogadores, $autor, -3, 4, "o público ficou dividido com a atitude contra $alvo");
}


function garantirPopularidade(&$jogadores)
{
    foreach ($jogadores as &$j) {
        if (!isset($j['popularidade'])) {
            $j['popularidade'] = 50;
        }
        $j['popularidade'] = limitar($j['popularidade'], 0, 100);
        if (!isset($j['historico_popularidade']) || !is_array($j['historico_popularidade'])) {
            $j['historico_popularidade'] = [];
        }
    }
    unset($j);
}

function impactoTorcidaOculto(&$jogadores, $nome, $tipo)
{
    $impactos = [
        'vt_bom' => rand(3, 10),
        'vt_ruim' => -rand(3, 10),
        'treta_boa' => rand(1, 6),
        'treta_ruim' => -rand(1, 6),
        'romance' => rand(1, 5),
        'casal' => rand(3, 8),
        'planta' => -rand(0, 3),
        'monstro' => -rand(3, 8),
        'lider' => 0,
        'anjo' => 0
    ];

    if (isset($impactos[$tipo])) {
        alterarPopularidade($jogadores, $nome, $impactos[$tipo]);
    }
}