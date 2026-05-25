<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

/* =========================
   🛡️ PROTEÇÃO DO MEU JOGADOR
   Evita o bug do líder sumir depois de definir VIP/Xepa.
========================= */
function garantirMeuJogadorNaLista(&$jogadores)
{
    $meuNomeSeguro = trim($_SESSION['meu_nome'] ?? '');

    if ($meuNomeSeguro == '') return;

    $existe = false;

    foreach ($jogadores as $j) {
        if (nomeIgual(($j['nome'] ?? ''), $meuNomeSeguro)) {
            $existe = true;
            break;
        }
    }

    if (!$existe && !empty($_SESSION['meu_jogador_snapshot']) && is_array($_SESSION['meu_jogador_snapshot'])) {
        array_unshift($jogadores, $_SESSION['meu_jogador_snapshot']);
    }

    $jogadores = array_values($jogadores);
}

if (!isset($_SESSION['jogadores'])) {
    header("Location: index.php");
    exit;
}

$jogadores = $_SESSION['jogadores'];
$meuNome   = trim($_SESSION['meu_nome'] ?? '');

/* REMOVER PARTICIPANTES DUPLICADOS SEM PERDER O JOGADOR */
$nomesUsados = [];
$jogadoresUnicos = [];

foreach ($jogadores as $j) {

    $nome = trim($j['nome'] ?? '');

    if ($nome == '') continue;

    $chaveNome = mb_strtolower($nome, 'UTF-8');

    /* Se o nome duplicado for o do jogador, prioriza o snapshot do jogador */
    if (isset($nomesUsados[$chaveNome])) {
        if (nomeIgual($nome, $meuNome)) {
            $idx = $nomesUsados[$chaveNome];
            $jogadoresUnicos[$idx] = $_SESSION['meu_jogador_snapshot'] ?? $j;
        }
        continue;
    }

    $nomesUsados[$chaveNome] = count($jogadoresUnicos);
    $jogadoresUnicos[] = $j;
}

$jogadores = array_values($jogadoresUnicos);
garantirMeuJogadorNaLista($jogadores);

/* =========================
   📊 POPULARIDADE OCULTA
   Todos começam com 50 e a pontuação fica salva na sessão.
   Por enquanto NÃO aparece nos cards.
========================= */
foreach ($jogadores as &$j) {
    if (!isset($j['popularidade'])) {
        $j['popularidade'] = 50;
    }

    $j['popularidade'] = limitar($j['popularidade'], 0, 100);

    /* Confessionário / romance: garante campos mesmo em saves antigos */
    if (!isset($j['romances']) || !is_array($j['romances'])) {
        $j['romances'] = [];
    }

    if (!isset($j['confessionarios']) || !is_array($j['confessionarios'])) {
        $j['confessionarios'] = [];
    }

    /* Sistema de alianças compatível com includes/logica_jogo.php */
    if (!array_key_exists('alianca', $j)) {
        $j['alianca'] = null;
    }

    if (!isset($j['historico_aliancas']) || !is_array($j['historico_aliancas'])) {
        $j['historico_aliancas'] = [];
    }
}
unset($j);

$_SESSION['jogadores'] = $jogadores;
$rodada    = $_SESSION['rodada'] ?? 1;
$meuNome   = $_SESSION['meu_nome'] ?? '';

/* =========================
   🚫 FIM DE JOGO DO JOGADOR
   Se o jogador for eliminado, o jogo para e mostra estatísticas finais.
========================= */

$meuJogadorAtual = null;

foreach ($jogadores as $j) {
    if (nomeIgual(($j['nome'] ?? ''), $meuNome)) {
        $meuJogadorAtual = $j;
        break;
    }
}

if ($meuJogadorAtual != null) {
    $_SESSION['meu_jogador_snapshot'] = $meuJogadorAtual;
    $_SESSION['minha_popularidade_final'] = $meuJogadorAtual['popularidade'] ?? 50;
}

$eliminadoSessao = $_SESSION['eliminado'] ?? null;
$nomeEliminadoSessao = '';

if (is_array($eliminadoSessao)) {
    $nomeEliminadoSessao = $eliminadoSessao['nome'] ?? '';
} else {
    $nomeEliminadoSessao = (string)$eliminadoSessao;
}

if (
    $meuNome != '' &&
    (
        $meuJogadorAtual == null ||
        nomeIgual($nomeEliminadoSessao, $meuNome)
    ) &&
    ($_SESSION['fase_semana'] ?? '') != 'jogador_eliminado'
) {
    $_SESSION['jogador_eliminado'] = true;
    $_SESSION['fase_semana'] = 'jogador_eliminado';
    $_SESSION['minha_colocacao_final'] = count($jogadores) + 1;

    if (!isset($_SESSION['evento_extra'])) {
        $_SESSION['evento_extra'] = [];
    }

    $_SESSION['evento_extra'][] = "🚫 $meuNome foi eliminado. Sua participação na temporada chegou ao fim.";
}

if (isset($_SESSION['jogador_eliminado']) && !isset($_POST['novo_jogo'])) {
    $_SESSION['fase_semana'] = 'jogador_eliminado';
}

/* GARANTIR RELAÇÕES DO JOGADOR COMEÇANDO EM 0 */
if (!isset($_SESSION['relacoes_jogador'])) {
    $_SESSION['relacoes_jogador'] = [];
}

foreach ($jogadores as $j) {
    $nomeRelacao = $j['nome'] ?? '';

    if ($nomeRelacao != '' && $nomeRelacao != $meuNome && !isset($_SESSION['relacoes_jogador'][$nomeRelacao])) {
        $_SESSION['relacoes_jogador'][$nomeRelacao] = 0;
    }
}

/* FESTA */
if (!isset($_SESSION['acoes_festa'])) {
    $_SESSION['acoes_festa'] = 2;
}

if (!isset($_SESSION['fase_semana'])) {
    $_SESSION['fase_semana'] = 'queridometro';
}

$fase = $_SESSION['fase_semana'];

/* =========================
   🔥 TEMAS DO JOGO DA DISCÓRDIA
========================= */

$temasDiscordia = [
    "sonso",
    "falso",
    "saboneteiro",
    "aliado",
    "podio"
];

if ($fase == 'eliminacao' && !isset($_SESSION['paredao'])) {
    $_SESSION['fase_semana'] = 'queridometro';
    unset($_SESSION['queridometro_feito']);
    unset($_SESSION['queridometro_resultado']);
    $_SESSION['acoes_restantes'] = 3;
    $fase = 'queridometro';
}

if (!isset($_SESSION['evento_extra'])) {
    $_SESSION['evento_extra'] = [];
}

/* Remove mensagens antigas do Confessionário do Ao Vivo.
   As falas ficam apenas na tela da fase "confessionario". */
$_SESSION['evento_extra'] = array_values(array_filter($_SESSION['evento_extra'], function ($ev) {
    return (
        mb_stripos((string)$ev, 'confessionário', 0, 'UTF-8') === false &&
        mb_stripos((string)$ev, 'confessionario', 0, 'UTF-8') === false
    );
}));

/* Começo de cada rodada: antes das interações, passa pelo Queridômetro */
if ($fase == 'interacoes_1' && !isset($_SESSION['queridometro_feito'])) {
    $_SESSION['fase_semana'] = 'queridometro';
    $fase = 'queridometro';
}

function limitar($valor, $min = 0, $max = 100)
{
    return max($min, min($max, $valor));
}

function calcularQtdVIP($total)
{
    if ($total >= 18) return 8;
    if ($total >= 14) return 6;
    if ($total >= 10) return 4;
    if ($total >= 7)  return 3;
    if ($total >= 5)  return 2;
    return 1;
}

$qtdVIP = calcularQtdVIP(count($jogadores));

/* =========================
   🤝 SISTEMA DE ALIANÇAS COMPATÍVEL COM LOGICA_JOGO.PHP
   Usa a chave $j['alianca'] dentro de cada participante.
========================= */

function nomesBaseAliancas(){
    return [
        "Fadas Sensatas", "Camarote Raiz", "Pipoca de Ouro", "Quarto Eclipse",
        "Quarto Maré", "Os Visionários", "Panelinha VIP", "Os Protagonistas",
        "Baile da Xepa", "Equipe Eclipse", "Laços Fortes", "Modo Turbo",
        "Tribo do Jogo", "Conselho Secreto", "Operação Paredão", "Tropa da Resenha",
        "Pódio Fechado", "Central da Treta", "Bonde dos Imunes", "Xadrez da Casa"
    ];
}

function indiceJogadorPorNome($jogadores, $nome){
    foreach($jogadores as $i => $j){
        if(nomeIgual(($j['nome'] ?? ''), $nome)){
            return $i;
        }
    }
    return null;
}

function nomeAliancaDisponivel($jogadores){
    $usadas = [];

    foreach($jogadores as $j){
        if(!empty($j['alianca'])){
            $usadas[] = $j['alianca'];
        }
    }

    $bases = nomesBaseAliancas();
    shuffle($bases);

    foreach($bases as $base){
        if(!in_array($base, $usadas)){
            return $base;
        }
    }

    return "Aliança ".rand(100,999);
}

function membrosDaAlianca($jogadores, $alianca){
    $membros = [];

    foreach($jogadores as $j){
        if(!empty($j['alianca']) && $j['alianca'] == $alianca){
            $membros[] = $j['nome'];
        }
    }

    return $membros;
}

function tamanhoAlianca($jogadores, $alianca){
    return count(membrosDaAlianca($jogadores, $alianca));
}

function mesmaAliancaNomes($jogadores, $nomeA, $nomeB){
    $aliancaA = null;
    $aliancaB = null;

    foreach($jogadores as $j){
        if(nomeIgual(($j['nome'] ?? ''), $nomeA)){
            $aliancaA = $j['alianca'] ?? null;
        }

        if(nomeIgual(($j['nome'] ?? ''), $nomeB)){
            $aliancaB = $j['alianca'] ?? null;
        }
    }

    return !empty($aliancaA) && !empty($aliancaB) && $aliancaA == $aliancaB;
}

function registrarHistoricoAlianca(&$jogadores, $nome, $mensagem){
    foreach($jogadores as &$j){
        if(nomeIgual(($j['nome'] ?? ''), $nome)){
            if(!isset($j['historico_aliancas']) || !is_array($j['historico_aliancas'])){
                $j['historico_aliancas'] = [];
            }

            $j['historico_aliancas'][] = $mensagem;

            if(count($j['historico_aliancas']) > 15){
                $j['historico_aliancas'] = array_slice($j['historico_aliancas'], -15);
            }

            break;
        }
    }
    unset($j);
}

function criarAliancaEntre(&$jogadores, $nomeA, $nomeB, $nomeAlianca = null){
    if(($_SESSION['rodada'] ?? 1) < 2) return "";
    if($nomeA == '' || $nomeB == '' || nomeIgual($nomeA, $nomeB)) return "";

    $idxA = indiceJogadorPorNome($jogadores, $nomeA);
    $idxB = indiceJogadorPorNome($jogadores, $nomeB);

    if($idxA === null || $idxB === null) return "";

    if(!empty($jogadores[$idxA]['alianca']) && !empty($jogadores[$idxB]['alianca'])){
        return "";
    }

    if($nomeAlianca == null){
        $nomeAlianca = !empty($jogadores[$idxA]['alianca'])
            ? $jogadores[$idxA]['alianca']
            : (!empty($jogadores[$idxB]['alianca']) ? $jogadores[$idxB]['alianca'] : nomeAliancaDisponivel($jogadores));
    }

    $jogadores[$idxA]['alianca'] = $nomeAlianca;
    $jogadores[$idxB]['alianca'] = $nomeAlianca;

    registrarHistoricoAlianca($jogadores, $nomeA, "Entrou na aliança $nomeAlianca com $nomeB.");
    registrarHistoricoAlianca($jogadores, $nomeB, "Entrou na aliança $nomeAlianca com $nomeA.");

    alterarAfinidade($jogadores, $nomeA, $nomeB, 8, -4, 10);
    alterarAfinidade($jogadores, $nomeB, $nomeA, 8, -4, 10);

    return "🤝 $nomeA e $nomeB oficializaram a aliança <b>$nomeAlianca</b>.";
}

function entrarEmAlianca(&$jogadores, $nome, $alianca){
    if($nome == '' || $alianca == '') return "";

    $idx = indiceJogadorPorNome($jogadores, $nome);
    if($idx === null) return "";

    if(($jogadores[$idx]['alianca'] ?? null) == $alianca) return "";

    $jogadores[$idx]['alianca'] = $alianca;
    registrarHistoricoAlianca($jogadores, $nome, "Entrou na aliança $alianca.");

    foreach($jogadores as $membro){
        if(!nomeIgual(($membro['nome'] ?? ''), $nome) && ($membro['alianca'] ?? null) == $alianca){
            alterarAfinidade($jogadores, $nome, $membro['nome'], 5, -2, 6);
            alterarAfinidade($jogadores, $membro['nome'], $nome, 4, -2, 5);
        }
    }

    return "🤝 $nome entrou para a aliança <b>$alianca</b>.";
}

function romperAlianca(&$jogadores, $nome, $motivo = "a confiança desmoronou"){
    if($nome == '') return "";

    $idx = indiceJogadorPorNome($jogadores, $nome);
    if($idx === null) return "";

    $alianca = $jogadores[$idx]['alianca'] ?? null;
    if(empty($alianca)) return "";

    $jogadores[$idx]['alianca'] = null;
    registrarHistoricoAlianca($jogadores, $nome, "Saiu da aliança $alianca porque $motivo.");

    foreach($jogadores as $membro){
        if(!nomeIgual(($membro['nome'] ?? ''), $nome) && ($membro['alianca'] ?? null) == $alianca){
            alterarAfinidade($jogadores, $nome, $membro['nome'], -8, 8, -10);
            alterarAfinidade($jogadores, $membro['nome'], $nome, -6, 6, -8);
        }
    }

    return "💥 $nome rompeu com a aliança <b>$alianca</b>: $motivo.";
}

function relacaoMediaComAlianca($jogadores, $nome, $alianca, $meuNome = ''){
    $total = 0;
    $qtd = 0;

    foreach($jogadores as $membro){
        if(nomeIgual(($membro['nome'] ?? ''), $nome)) continue;
        if(($membro['alianca'] ?? null) != $alianca) continue;

        $rel = obterRelacaoCompleta($jogadores, $nome, $membro['nome'], $meuNome);
        $score = ($rel['amizade'] ?? 0) + (($rel['confianca'] ?? 0) * 0.7) - (($rel['rivalidade'] ?? 0) * 1.2);
        $total += $score;
        $qtd++;
    }

    return $qtd == 0 ? 0 : ($total / $qtd);
}

function atualizarAliancasAutomaticas(&$jogadores, $meuNome = ''){
    $eventos = [];

    /* As alianças só começam a se formar a partir da Rodada 2,
       quando a casa já teve tempo de criar afinidades e rivalidades. */
    if(($_SESSION['rodada'] ?? 1) < 2){
        return $eventos;
    }

    foreach($jogadores as &$j){
        if(!array_key_exists('alianca', $j)){
            $j['alianca'] = null;
        }
        if(!isset($j['historico_aliancas']) || !is_array($j['historico_aliancas'])){
            $j['historico_aliancas'] = [];
        }
    }
    unset($j);

    /* Rompimentos */
    foreach($jogadores as $j){
        $nome = $j['nome'] ?? '';
        $alianca = $j['alianca'] ?? null;

        if($nome == '' || empty($alianca)) continue;

        $media = relacaoMediaComAlianca($jogadores, $nome, $alianca, $meuNome);

        if($media < 8 && rand(1,100) <= 35){
            $ev = romperAlianca($jogadores, $nome, "a relação com o grupo ficou muito desgastada");
            if($ev != '') $eventos[] = $ev;
        }
    }

    /* Entrada em alianças existentes */
    foreach($jogadores as $j){
        $nome = $j['nome'] ?? '';
        if($nome == '' || !empty($j['alianca'])) continue;

        $melhorAlianca = null;
        $melhorScore = -999;

        foreach($jogadores as $outro){
            if(nomeIgual(($outro['nome'] ?? ''), $nome)) continue;
            if(empty($outro['alianca'])) continue;

            $rel = obterRelacaoCompleta($jogadores, $nome, $outro['nome'], $meuNome);
            $score = ($rel['amizade'] ?? 0) + ($rel['confianca'] ?? 0) - (($rel['rivalidade'] ?? 0) * 1.5);

            if($score > $melhorScore){
                $melhorScore = $score;
                $melhorAlianca = $outro['alianca'];
            }
        }

        if($melhorAlianca != null && $melhorScore >= 90 && tamanhoAlianca($jogadores, $melhorAlianca) < 5 && rand(1,100) <= 35){
            $ev = entrarEmAlianca($jogadores, $nome, $melhorAlianca);
            if($ev != '') $eventos[] = $ev;
        }
    }

    /* Criação de novas alianças */
    for($i = 0; $i < count($jogadores); $i++){
        for($k = $i + 1; $k < count($jogadores); $k++){
            $a = $jogadores[$i];
            $b = $jogadores[$k];

            if(!empty($a['alianca']) || !empty($b['alianca'])) continue;

            $relAB = obterRelacaoCompleta($jogadores, $a['nome'], $b['nome'], $meuNome);
            $relBA = obterRelacaoCompleta($jogadores, $b['nome'], $a['nome'], $meuNome);

            $score =
                ($relAB['amizade'] ?? 0) +
                ($relAB['confianca'] ?? 0) +
                ($relBA['amizade'] ?? 0) +
                ($relBA['confianca'] ?? 0) -
                (($relAB['rivalidade'] ?? 0) + ($relBA['rivalidade'] ?? 0));

            if($score >= 170 && rand(1,100) <= 25){
                $ev = criarAliancaEntre($jogadores, $a['nome'], $b['nome']);
                if($ev != '') $eventos[] = $ev;
                break 2;
            }
        }
    }

    return $eventos;
}

function escolherAlvoDoGrupo($jogadores, $alianca, $bloqueados = []){
    if(empty($alianca)) return null;

    $pontuacao = [];

    foreach($jogadores as $membro){
        if(($membro['alianca'] ?? null) != $alianca) continue;

        foreach($jogadores as $alvo){
            $nomeAlvo = $alvo['nome'] ?? '';

            if($nomeAlvo == '') continue;
            if(($alvo['alianca'] ?? null) == $alianca) continue;
            if(nomeIgual($nomeAlvo, ($membro['nome'] ?? ''))) continue;
            if(in_array($nomeAlvo, $bloqueados)) continue;
            if(!empty($alvo['status']['lider'])) continue;
            if(!empty($alvo['status']['imune'])) continue;

            $rel = obterRelacaoCompleta($jogadores, $membro['nome'], $nomeAlvo, $_SESSION['meu_nome'] ?? '');
            $score = ($rel['rivalidade'] ?? 0) + (100 - ($rel['amizade'] ?? 0)) + rand(0,10);

            $pontuacao[$nomeAlvo] = ($pontuacao[$nomeAlvo] ?? 0) + $score;
        }
    }

    if(empty($pontuacao)) return null;

    arsort($pontuacao);
    return array_key_first($pontuacao);
}

function gerarResumoAliancas($jogadores){
    $aliancas = [];

    foreach($jogadores as $j){
        if(!empty($j['alianca'])){
            if(!isset($aliancas[$j['alianca']])){
                $aliancas[$j['alianca']] = [];
            }
            $aliancas[$j['alianca']][] = $j['nome'];
        }
    }

    ksort($aliancas);
    return $aliancas;
}

function obterAliancaJogador($jogadores, $nome){
    foreach($jogadores as $j){
        if(nomeIgual(($j['nome'] ?? ''), $nome)){
            return $j['alianca'] ?? null;
        }
    }

    return null;
}

function calcularAceitacaoNaAlianca($jogadores, $nome, $alianca, $meuNome = ''){
    if($nome == '' || $alianca == '') return 0;

    $membros = membrosDaAlianca($jogadores, $alianca);
    if(empty($membros)) return 0;

    $total = 0;
    $qtd = 0;

    foreach($membros as $membro){
        if(nomeIgual($membro, $nome)) continue;

        $rel = obterRelacaoCompleta($jogadores, $membro, $nome, $meuNome);
        $score = ($rel['amizade'] ?? 0) + (($rel['confianca'] ?? 0) * 0.8) - (($rel['rivalidade'] ?? 0) * 1.4);
        $total += $score;
        $qtd++;
    }

    if($qtd == 0) return 0;

    return round($total / $qtd);
}

function jogadorEntrarEmAlianca(&$jogadores, $nome, $alianca){
    if(($_SESSION['rodada'] ?? 1) < 2){
        return "⏳ As alianças ainda não começaram oficialmente. Elas só abrem a partir da Rodada 2.";
    }

    if($nome == '' || $alianca == ''){
        return "⚠️ Escolha uma aliança válida.";
    }

    if(!empty(obterAliancaJogador($jogadores, $nome))){
        return "⚠️ $nome já está em uma aliança. Para entrar em outra, primeiro precisa sair da atual.";
    }

    if(tamanhoAlianca($jogadores, $alianca) <= 0){
        return "⚠️ Essa aliança não existe mais.";
    }

    if(tamanhoAlianca($jogadores, $alianca) >= 5){
        return "🚫 A aliança <b>$alianca</b> recusou a entrada de $nome porque o grupo já está cheio.";
    }

    $aceitacao = calcularAceitacaoNaAlianca($jogadores, $nome, $alianca, $_SESSION['meu_nome'] ?? '');
    $chance = max(15, min(90, $aceitacao));

    if(rand(1,100) <= $chance){
        $ev = entrarEmAlianca($jogadores, $nome, $alianca);
        alterarPopularidadePublica($jogadores, $nome, 1, 4, "entrou em uma aliança estratégica", true);
        return $ev != '' ? $ev : "🤝 $nome entrou para a aliança <b>$alianca</b>.";
    }

    foreach(membrosDaAlianca($jogadores, $alianca) as $membro){
        alterarAfinidade($jogadores, $membro, $nome, -4, 3, -6);
        alterarAfinidade($jogadores, $nome, $membro, -3, 2, -4);
    }

    alterarPopularidadePublica($jogadores, $nome, -3, 0, "tentou entrar em uma aliança e foi recusado", true);
    return "🚫 $nome tentou entrar na aliança <b>$alianca</b>, mas o grupo recusou por falta de confiança.";
}

function jogadorSairDaAlianca(&$jogadores, $nome){
    $alianca = obterAliancaJogador($jogadores, $nome);

    if(empty($alianca)){
        return "⚠️ $nome não está em nenhuma aliança no momento.";
    }

    $ev = romperAlianca($jogadores, $nome, "decidiu jogar sozinho e não seguir mais o grupo");
    alterarPopularidadePublica($jogadores, $nome, -5, 3, "rompeu com uma aliança e dividiu o público", true);

    return $ev != '' ? $ev : "💥 $nome saiu da aliança <b>$alianca</b>.";
}

function alvoCombinadoDaAlianca($jogadores, $alianca, $bloqueados = []){
    if(empty($alianca)) return null;

    if(!isset($_SESSION['alvos_aliancas_semana'])){
        $_SESSION['alvos_aliancas_semana'] = [];
    }

    if(isset($_SESSION['alvos_aliancas_semana'][$alianca])){
        $alvoSalvo = $_SESSION['alvos_aliancas_semana'][$alianca];
        if(!in_array($alvoSalvo, $bloqueados)){
            return $alvoSalvo;
        }
    }

    $alvo = escolherAlvoDoGrupo($jogadores, $alianca, $bloqueados);

    if($alvo != null){
        $_SESSION['alvos_aliancas_semana'][$alianca] = $alvo;
    }

    return $alvo;
}

$EMOJIS_QUERIDOMETRO = [

    "❤️" => [
        "nome" => "Amor / Afinidade Forte",
        "tipo" => "positivo",
        "afinidade" => 12,
        "popularidade" => 4
    ],

    "😄" => [
        "nome" => "Gosto / Simpatia",
        "tipo" => "positivo",
        "afinidade" => 6,
        "popularidade" => 2
    ],

    "🤝" => [
        "nome" => "Aliança / Confiança",
        "tipo" => "positivo",
        "afinidade" => 10,
        "popularidade" => 3
    ],

    "🔥" => [
        "nome" => "Treta / Caótica",
        "tipo" => "neutro",
        "afinidade" => -2,
        "popularidade" => 5
    ],

    "😴" => [
        "nome" => "Planta / Apagado",
        "tipo" => "negativo",
        "afinidade" => -5,
        "popularidade" => -6
    ],

    "🐍" => [
        "nome" => "Falso / Mentiroso",
        "tipo" => "negativo",
        "afinidade" => -12,
        "popularidade" => -8
    ],

    "🎯" => [
        "nome" => "Alvo / Quero Eliminar",
        "tipo" => "negativo",
        "afinidade" => -10,
        "popularidade" => -4
    ],

    "💔" => [
        "nome" => "Chateado / Me Decepcionou",
        "tipo" => "negativo",
        "afinidade" => -15,
        "popularidade" => -3
    ],

    "🤮" => [
        "nome" => "Ranço / Relação Ruim",
        "tipo" => "negativo",
        "afinidade" => -18,
        "popularidade" => -7
    ],

    "🙄" => [
        "nome" => "Forçado / VTzeiro",
        "tipo" => "negativo",
        "afinidade" => -6,
        "popularidade" => -10
    ],

    "😡" => [
        "nome" => "Explosivo / Barraqueiro",
        "tipo" => "misto",
        "afinidade" => -4,
        "popularidade" => 3
    ]

];

function iniciarQueridometro()
{
    if (!isset($_SESSION['queridometro_resultado'])) {
        $_SESSION['queridometro_resultado'] = [];
    }
}

function escolherEmojiQueridometroAutomatico($jogadores, $meuNome, $nomeAlvo)
{

    $relacao = $_SESSION['relacoes_jogador'][$nomeAlvo] ?? 0;
    $romance = obterRomance($jogadores, $meuNome, $nomeAlvo);

    if ($romance >= 60 || $relacao >= 60) {
        return "❤️";
    }

    if ($relacao >= 35) {
        return "🤝";
    }

    if ($relacao >= 12) {
        return "😄";
    }

    if ($relacao <= -55) {
        return "🤮";
    }

    if ($relacao <= -35) {
        return "💔";
    }

    if ($relacao <= -20) {
        return "🐍";
    }

    if ($relacao <= -8) {
        return "🎯";
    }

    $neutros = ["🔥", "😴", "🙄", "😡"];
    return $neutros[array_rand($neutros)];
}

function registrarEmojiQueridometro(&$jogadores, $de, $para, $emoji, $EMOJIS_QUERIDOMETRO)
{

    if ($de == '' || $para == '' || $emoji == '') return;
    if (!isset($EMOJIS_QUERIDOMETRO[$emoji])) return;

    if (!isset($_SESSION['queridometro_resultado'][$para])) {
        $_SESSION['queridometro_resultado'][$para] = [];
    }

    if (!isset($_SESSION['queridometro_resultado'][$para][$emoji])) {
        $_SESSION['queridometro_resultado'][$para][$emoji] = 0;
    }

    $_SESSION['queridometro_resultado'][$para][$emoji]++;

    $dados = $EMOJIS_QUERIDOMETRO[$emoji];

    /* O Queridômetro muda principalmente RELACIONAMENTO */
    alterarAfinidade($jogadores, $de, $para, $dados['afinidade'], 0, 0);

    if ($de == ($_SESSION['meu_nome'] ?? '')) {
        ajustarRelacaoJogador($para, $dados['afinidade']);
    }

    /* Popularidade só muda quando o jogador ataca alguém muito querido ou alguém cancelado */
    if ($de == ($_SESSION['meu_nome'] ?? '') && ($dados['tipo'] ?? '') == 'negativo') {

        $popularidadeAlvo = 50;

        foreach ($jogadores as $j) {
            if (($j['nome'] ?? '') == $para) {
                $popularidadeAlvo = $j['popularidade'] ?? 50;
                break;
            }
        }

        if ($popularidadeAlvo >= 65) {
            $perda = rand(3, 5);
            alterarPopularidade($jogadores, $de, -$perda);
            $_SESSION['evento_extra'][] = "📉 O público não curtiu $de atacando $para no Queridômetro.";
        } elseif ($popularidadeAlvo <= 35) {
            $ganho = rand(3, 5);
            alterarPopularidade($jogadores, $de, $ganho);
            $_SESSION['evento_extra'][] = "📈 O público gostou de $de mirar em $para no Queridômetro.";
        }
    }
}

function alterarAfinidade(&$jogadores, $nomeA, $nomeB, $amizade = 0, $rivalidade = 0, $confianca = 0)
{

    foreach ($jogadores as &$j) {

        if ($j['nome'] == $nomeA) {

            if (!isset($j['relacoes'][$nomeB])) {
                $j['relacoes'][$nomeB] = [
                    "amizade" => 0,
                    "rivalidade" => 0,
                    "confianca" => 0
                ];
            }

            $j['relacoes'][$nomeB]['amizade'] =
                limitar($j['relacoes'][$nomeB]['amizade'] + $amizade);

            $j['relacoes'][$nomeB]['rivalidade'] =
                limitar($j['relacoes'][$nomeB]['rivalidade'] + $rivalidade);

            $j['relacoes'][$nomeB]['confianca'] =
                limitar($j['relacoes'][$nomeB]['confianca'] + $confianca);
        }
    }
}

function alterarRomance(&$jogadores, $nomeA, $nomeB, $valor)
{

    foreach ($jogadores as &$j) {

        if ($j['nome'] == $nomeA) {

            if (!isset($j['romances'][$nomeB])) {
                $j['romances'][$nomeB] = 0;
            }

            $j['romances'][$nomeB] =
                limitar($j['romances'][$nomeB] + $valor, 0, 100);
        }
    }
}

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

function obterPopularidadeJogador($jogadores, $nome){
    foreach($jogadores as $j){
        if(($j['nome'] ?? '') == $nome){
            return $j['popularidade'] ?? 50;
        }
    }

    return 50;
}

function nivelPopularidadePublica($popularidade){
    if($popularidade >= 90) return "favorito";
    if($popularidade >= 70) return "querido";
    if($popularidade >= 50) return "neutro";
    if($popularidade >= 30) return "mal_visto";
    return "cancelado";
}

function descricaoPopularidadePublica($popularidade){
    $nivel = nivelPopularidadePublica($popularidade);

    if($nivel == "favorito") return "favorito do público";
    if($nivel == "querido") return "querido pelo público";
    if($nivel == "neutro") return "dividindo opiniões";
    if($nivel == "mal_visto") return "mal visto pelo público";
    return "cancelado nas redes";
}

function registrarHistoricoPopularidade(&$jogadores, $nome, $valor, $motivo){
    foreach($jogadores as &$j){
        if(($j['nome'] ?? '') == $nome){
            if(!isset($j['historico_popularidade']) || !is_array($j['historico_popularidade'])){
                $j['historico_popularidade'] = [];
            }

            $j['historico_popularidade'][] = [
                "rodada" => $_SESSION['rodada'] ?? 1,
                "valor" => $valor,
                "motivo" => $motivo,
                "popularidade" => $j['popularidade'] ?? 50
            ];

            if(count($j['historico_popularidade']) > 20){
                $j['historico_popularidade'] = array_slice($j['historico_popularidade'], -20);
            }

            break;
        }
    }
    unset($j);
}

function alterarPopularidadePublica(&$jogadores, $nome, $min, $max, $motivo, $mostrarNoAoVivo = true){
    if($nome == '') return 0;

    $valor = rand($min, $max);

    if($valor == 0){
        return 0;
    }

    alterarPopularidade($jogadores, $nome, $valor);
    registrarHistoricoPopularidade($jogadores, $nome, $valor, $motivo);

    if($mostrarNoAoVivo){
        if(!isset($_SESSION['evento_extra'])){
            $_SESSION['evento_extra'] = [];
        }

        if($valor > 0){
            $_SESSION['evento_extra'][] = "📈 O público reagiu bem: $nome ganhou $valor de popularidade ($motivo).";
        }else{
            $_SESSION['evento_extra'][] = "📉 O público reagiu mal: $nome perdeu ".abs($valor)." de popularidade ($motivo).";
        }
    }

    return $valor;
}

function impactoPopularidadePorPersonalidade(&$jogadores, $nome, $tipo, $mostrarNoAoVivo = false){
    $personalidade = "Neutro";

    foreach($jogadores as $j){
        if(($j['nome'] ?? '') == $nome){
            $personalidade = $j['personalidade'] ?? 'Neutro';
            break;
        }
    }

    $min = 0;
    $max = 0;
    $motivo = "movimentou o jogo";

    if($tipo == "vt"){
        $min = -5; $max = 8; $motivo = "tentou render VT";
        if($personalidade == "Influencer"){ $min = -4; $max = 12; }
        if($personalidade == "Planta"){ $min = -8; $max = 4; }
        if($personalidade == "Barraqueiro"){ $min = -6; $max = 10; }
    }

    if($tipo == "fofoca"){
        $min = -8; $max = 3; $motivo = "se envolveu em fofoca";
        if($personalidade == "Manipulador" || $personalidade == "Falso"){ $min = -12; $max = 2; }
    }

    if($tipo == "treta"){
        $min = -8; $max = 6; $motivo = "entrou em uma treta";
        if($personalidade == "Barraqueiro" || $personalidade == "Explosivo"){ $min = -10; $max = 10; }
        if($personalidade == "Fofo"){ $min = -10; $max = 2; }
    }

    if($tipo == "romance"){
        $min = 1; $max = 6; $motivo = "viveu um momento de romance";
        if($personalidade == "Fofo" || $personalidade == "Emocional"){ $max = 8; }
    }

    if($tipo == "alianca"){
        $min = 1; $max = 4; $motivo = "fortaleceu uma aliança";
        if($personalidade == "Manipulador" || $personalidade == "Falso"){ $min = -2; $max = 3; }
    }

    if($tipo == "planta"){
        $min = -4; $max = -1; $motivo = "ficou apagado demais";
    }

    if($tipo == "confessionario_bom"){
        $min = 1; $max = 5; $motivo = "fez um confessionário marcante";
        if($personalidade == "Influencer"){ $max = 7; }
    }

    if($tipo == "confessionario_ruim"){
        $min = -5; $max = -1; $motivo = "soou mal no confessionário";
    }

    if($min == 0 && $max == 0){
        return 0;
    }

    return alterarPopularidadePublica($jogadores, $nome, $min, $max, $motivo, $mostrarNoAoVivo);
}

function aplicarImpactoPublicoConfessionario(&$jogadores, $nome, $tipo){
    if($tipo == "vt" || $tipo == "sonho_vitoria"){
        return impactoPopularidadePorPersonalidade($jogadores, $nome, "confessionario_bom", false);
    }

    if($tipo == "romance" || $tipo == "desabafo"){
        return impactoPopularidadePorPersonalidade($jogadores, $nome, "romance", false);
    }

    if($tipo == "rival" || $tipo == "vinganca" || $tipo == "falsidade"){
        return impactoPopularidadePorPersonalidade($jogadores, $nome, "treta", false);
    }

    if($tipo == "estrategia"){
        return alterarPopularidadePublica($jogadores, $nome, -3, 4, "mostrou estratégia no confessionário", false);
    }

    if($tipo == "neutro" || $tipo == "observacao"){
        return alterarPopularidadePublica($jogadores, $nome, -1, 2, "teve um confessionário discreto", false);
    }

    return 0;
}

function aplicarDesgasteSemanalPublico(&$jogadores){
    $chave = 'desgaste_publico_rodada_'.($_SESSION['rodada'] ?? 1);

    if(isset($_SESSION[$chave])){
        return;
    }

    foreach($jogadores as $j){
        $nome = $j['nome'] ?? '';
        if($nome == '') continue;

        $personalidade = $j['personalidade'] ?? 'Neutro';

        if($personalidade == 'Planta'){
            alterarPopularidadePublica($jogadores, $nome, -2, 0, "passou a semana apagado", false);
        }

        if(!empty($j['status']['monstro'])){
            alterarPopularidadePublica($jogadores, $nome, -3, -1, "sofreu desgaste com o Monstro", false);
        }

        if(!empty($j['status']['xepa']) && rand(1,100) <= 25){
            alterarPopularidadePublica($jogadores, $nome, -1, 1, "teve pouca visibilidade na Xepa", false);
        }

        if(!empty($j['status']['vip']) && rand(1,100) <= 20){
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


function perfilPersonalidadeCompleto($personalidade)
{

    $dados = [

        "Estrategista" => [
            "treta" => 45,
            "romance" => 30,
            "vt" => 60,
            "alianca" => 95,
            "emocao" => 25,
            "fofoca" => 70
        ],

        "Explosivo" => [
            "treta" => 95,
            "romance" => 35,
            "vt" => 75,
            "alianca" => 25,
            "emocao" => 85,
            "fofoca" => 50
        ],

        "Planta" => [
            "treta" => 10,
            "romance" => 20,
            "vt" => 10,
            "alianca" => 30,
            "emocao" => 20,
            "fofoca" => 10
        ],

        "Manipulador" => [
            "treta" => 60,
            "romance" => 30,
            "vt" => 85,
            "alianca" => 90,
            "emocao" => 20,
            "fofoca" => 95
        ],

        "Emocional" => [
            "treta" => 75,
            "romance" => 95,
            "vt" => 60,
            "alianca" => 70,
            "emocao" => 100,
            "fofoca" => 45
        ],

        "Barraqueiro" => [
            "treta" => 100,
            "romance" => 25,
            "vt" => 90,
            "alianca" => 20,
            "emocao" => 75,
            "fofoca" => 60
        ],

        "Fofo" => [
            "treta" => 5,
            "romance" => 80,
            "vt" => 40,
            "alianca" => 85,
            "emocao" => 90,
            "fofoca" => 15
        ],

        "Líder Nato" => [
            "treta" => 50,
            "romance" => 40,
            "vt" => 80,
            "alianca" => 85,
            "emocao" => 55,
            "fofoca" => 50
        ],

        "Influencer" => [
            "treta" => 55,
            "romance" => 70,
            "vt" => 100,
            "alianca" => 60,
            "emocao" => 60,
            "fofoca" => 65
        ],

        "Falso" => [
            "treta" => 70,
            "romance" => 50,
            "vt" => 80,
            "alianca" => 90,
            "emocao" => 35,
            "fofoca" => 100
        ],

        "Neutro" => [
            "treta" => 50,
            "romance" => 50,
            "vt" => 50,
            "alianca" => 50,
            "emocao" => 50,
            "fofoca" => 50
        ]
    ];

    return $dados[$personalidade] ?? $dados['Neutro'];
}



function ajustarRelacaoJogador($nome, $valor)
{

    if ($nome == '') return;

    if (!isset($_SESSION['relacoes_jogador'])) {
        $_SESSION['relacoes_jogador'] = [];
    }

    $_SESSION['relacoes_jogador'][$nome] =
        max(-100, min(100, ($_SESSION['relacoes_jogador'][$nome] ?? 0) + $valor));
}


function calcularRelacaoIA($jogadores, $de, $para, $meuNome)
{

    if ($de == '' || $para == '') return 0;

    /* Quando a IA está mirando no jogador, usa o placar visível do card */
    if ($para == $meuNome) {
        return $_SESSION['relacoes_jogador'][$de] ?? 0;
    }

    foreach ($jogadores as $j) {

        if (($j['nome'] ?? '') == $de) {

            $rel = $j['relacoes'][$para] ?? [];

            $amizade = $rel['amizade'] ?? 0;
            $rivalidade = $rel['rivalidade'] ?? 0;
            $confianca = $rel['confianca'] ?? 0;

            return (int)round($amizade + ($confianca * 0.5) - ($rivalidade * 1.2));
        }
    }

    return 0;
}

function obterRelacaoCompleta($jogadores, $de, $para, $meuNome)
{

    $rel = [
        "amizade" => 0,
        "rivalidade" => 0,
        "confianca" => 0,
        "score" => 0
    ];

    if ($de == '' || $para == '') return $rel;

    foreach ($jogadores as $j) {

        if (($j['nome'] ?? '') == $de) {

            $dados = $j['relacoes'][$para] ?? [];

            $rel['amizade'] = $dados['amizade'] ?? 0;
            $rel['rivalidade'] = $dados['rivalidade'] ?? 0;
            $rel['confianca'] = $dados['confianca'] ?? 0;

            break;
        }
    }

    /* Quando envolve o jogador, usa também o placar que aparece no card */
    if ($para == $meuNome) {
        $rel['score'] = $_SESSION['relacoes_jogador'][$de] ?? 0;
    } else {
        $rel['score'] = (int)round(
            $rel['amizade'] +
                ($rel['confianca'] * 0.5) -
                ($rel['rivalidade'] * 1.2)
        );
    }

    return $rel;
}

function saoAliados($jogadores, $nomeA, $nomeB, $meuNome)
{
    $rel = obterRelacaoCompleta($jogadores, $nomeA, $nomeB, $meuNome);

    return (
        $rel['score'] >= 35 ||
        ($rel['amizade'] >= 45 && $rel['confianca'] >= 30)
    );
}

function saoRivais($jogadores, $nomeA, $nomeB, $meuNome)
{
    $rel = obterRelacaoCompleta($jogadores, $nomeA, $nomeB, $meuNome);

    return (
        $rel['score'] <= -25 ||
        $rel['rivalidade'] >= 35 ||
        ($rel['amizade'] <= 10 && $rel['rivalidade'] >= 20)
    );
}

function listarAliadosNPC($jogadores, $nomeNPC, $meuNome)
{
    $aliados = [];

    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';

        if ($nome == '' || $nome == $nomeNPC) continue;

        if (saoAliados($jogadores, $nomeNPC, $nome, $meuNome)) {
            $aliados[] = $nome;
        }
    }

    return $aliados;
}

function listarRivaisNPC($jogadores, $nomeNPC, $meuNome)
{
    $rivais = [];

    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';

        if ($nome == '' || $nome == $nomeNPC) continue;

        if (saoRivais($jogadores, $nomeNPC, $nome, $meuNome)) {
            $rivais[] = $nome;
        }
    }

    return $rivais;
}

function buscarJogadorPorNome($jogadores, $nome)
{
    foreach ($jogadores as $j) {
        if (($j['nome'] ?? '') == $nome) {
            return $j;
        }
    }

    return null;
}

function escolherAlvoNPCPorRelacao($jogadores, $nomeNPC, $meuNome, $tipo = 'qualquer')
{

    $opcoes = [];

    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';

        if ($nome == '' || $nome == $nomeNPC) continue;

        $rel = obterRelacaoCompleta($jogadores, $nomeNPC, $nome, $meuNome);

        if ($tipo == 'aliado' && saoAliados($jogadores, $nomeNPC, $nome, $meuNome)) {
            $opcoes[$nome] = $rel['score'] + rand(1, 15);
        }

        if ($tipo == 'rival' && saoRivais($jogadores, $nomeNPC, $nome, $meuNome)) {
            $opcoes[$nome] = abs($rel['score']) + ($rel['rivalidade'] ?? 0) + rand(1, 15);
        }

        if ($tipo == 'qualquer') {
            $opcoes[$nome] = rand(1, 100);
        }
    }

    if (empty($opcoes)) {
        return null;
    }

    arsort($opcoes);

    return array_key_first($opcoes);
}

function registrarRelacaoMarcante(&$jogadores, $nomeA, $nomeB)
{

    if ($nomeA == '' || $nomeB == '' || $nomeA == $nomeB) return;

    foreach ($jogadores as &$j) {

        if (($j['nome'] ?? '') == $nomeA) {

            if (!isset($j['relacoes'][$nomeB])) {
                $j['relacoes'][$nomeB] = [
                    "amizade" => 0,
                    "rivalidade" => 0,
                    "confianca" => 0
                ];
            }

            $amizade = $j['relacoes'][$nomeB]['amizade'] ?? 0;
            $rivalidade = $j['relacoes'][$nomeB]['rivalidade'] ?? 0;
            $confianca = $j['relacoes'][$nomeB]['confianca'] ?? 0;

            if (!isset($j['marcadores'])) {
                $j['marcadores'] = [];
            }

            if (!isset($j['marcadores']['aliados'])) {
                $j['marcadores']['aliados'] = [];
            }

            if (!isset($j['marcadores']['rivais'])) {
                $j['marcadores']['rivais'] = [];
            }

            if ($amizade >= 50 && $confianca >= 35 && !in_array($nomeB, $j['marcadores']['aliados'])) {
                $j['marcadores']['aliados'][] = $nomeB;
            }

            if (($rivalidade >= 45 || $amizade <= 5) && !in_array($nomeB, $j['marcadores']['rivais'])) {
                $j['marcadores']['rivais'][] = $nomeB;
            }
        }
    }
    unset($j);
}

function atualizarRelacoesMarcantes(&$jogadores)
{

    $nomes = [];

    foreach ($jogadores as $j) {
        if (($j['nome'] ?? '') != '') {
            $nomes[] = $j['nome'];
        }
    }

    foreach ($nomes as $nomeA) {
        foreach ($nomes as $nomeB) {
            if ($nomeA != $nomeB) {
                registrarRelacaoMarcante($jogadores, $nomeA, $nomeB);
            }
        }
    }
}


function npcExecutarInteracoesDaFase(&$jogadores, $meuNome, $fase, $quantidade = 3)
{

    $chave = 'npc_interacoes_feitas_' . $fase;

    if (isset($_SESSION[$chave])) return;

    $eventosNPC = gerarAcoesNPC($jogadores, $meuNome, $quantidade);

    foreach ($eventosNPC as $ev) {
        $_SESSION['evento_extra'][] = $ev;
    }

    $eventosAliancas = atualizarAliancasAutomaticas($jogadores, $meuNome);
    foreach ($eventosAliancas as $ev) {
        $_SESSION['evento_extra'][] = $ev;
    }

    $_SESSION['jogadores'] = $jogadores;
    $_SESSION[$chave] = true;
}


/* =========================
   💕 SISTEMA DE ROMANCE / CRUSH / CASAL
========================= */

if (!isset($_SESSION['casais'])) {
    $_SESSION['casais'] = [];
}

function obterRomance($jogadores, $nomeA, $nomeB)
{
    foreach ($jogadores as $j) {
        if (($j['nome'] ?? '') == $nomeA) {
            return $j['romances'][$nomeB] ?? 0;
        }
    }
    return 0;
}

function statusRomance($romance, $nome, $meuNome)
{
    if (isset($_SESSION['casais'][$meuNome]) && $_SESSION['casais'][$meuNome] == $nome) {
        return "💍 Namorando";
    }

    if ($romance >= 60) {
        return "💘 Quase casal";
    }

    if ($romance >= 30) {
        return "💕 Crush";
    }

    if ($romance > 0) {
        return "💗 Interesse";
    }

    return "";
}

function parceiroAtual($nome)
{
    return $_SESSION['casais'][$nome] ?? '';
}

function estaNamorandoCom($nomeA, $nomeB)
{
    return isset($_SESSION['casais'][$nomeA]) && $_SESSION['casais'][$nomeA] == $nomeB;
}

function registrarCasal($nomeA, $nomeB)
{
    $_SESSION['casais'][$nomeA] = $nomeB;
    $_SESSION['casais'][$nomeB] = $nomeA;
}

function terminarCasal($nomeA, $nomeB)
{
    if (isset($_SESSION['casais'][$nomeA]) && $_SESSION['casais'][$nomeA] == $nomeB) {
        unset($_SESSION['casais'][$nomeA]);
    }

    if (isset($_SESSION['casais'][$nomeB]) && $_SESSION['casais'][$nomeB] == $nomeA) {
        unset($_SESSION['casais'][$nomeB]);
    }
}

function chanceAceitarNamoro($afinidade, $romance)
{
    $chance = 25;

    if ($afinidade >= 20) $chance += 20;
    if ($afinidade >= 40) $chance += 15;
    if ($romance >= 60) $chance += 25;
    if ($romance >= 80) $chance += 10;

    return min(90, max(10, $chance));
}

function aplicarCiumesSeTiverCasal(&$jogadores, $meuNome, $alvoFlerte, &$eventoExtra)
{
    $parceiro = parceiroAtual($meuNome);

    if ($parceiro != '' && $parceiro != $alvoFlerte) {
        ajustarRelacaoJogador($parceiro, -8);
        alterarRomance($jogadores, $meuNome, $parceiro, -8);
        alterarRomance($jogadores, $parceiro, $meuNome, -8);
        alterarAfinidade($jogadores, $parceiro, $meuNome, -8, 8, -5);

        $eventoExtra[] = "💔 $parceiro viu o clima de flerte e ficou com ciúmes de $meuNome.";
    }
}

function tentarPedidoNamoroNPC(&$jogadores, $nomeNPC, $nomeAlvo)
{
    if ($nomeNPC == '' || $nomeAlvo == '') return "";
    if (parceiroAtual($nomeNPC) != '' || parceiroAtual($nomeAlvo) != '') return "";

    $romance = min(
        obterRomance($jogadores, $nomeNPC, $nomeAlvo),
        obterRomance($jogadores, $nomeAlvo, $nomeNPC)
    );

    if ($romance < 60) return "";
    if (rand(1, 100) > 25) return "";

    $afinidade = 50;
    foreach ($jogadores as $j) {
        if (($j['nome'] ?? '') == $nomeAlvo) {
            $afinidade = $j['relacoes'][$nomeNPC]['amizade'] ?? 50;
        }
    }

    $chance = chanceAceitarNamoro($afinidade, $romance);

    if (rand(1, 100) <= $chance) {
        registrarCasal($nomeNPC, $nomeAlvo);
        impactoTorcidaOculto($jogadores, $nomeNPC, 'casal');
        impactoTorcidaOculto($jogadores, $nomeAlvo, 'casal');
        alterarRomance($jogadores, $nomeNPC, $nomeAlvo, 8);
        alterarRomance($jogadores, $nomeAlvo, $nomeNPC, 8);
        return "💍 $nomeNPC pediu $nomeAlvo em namoro, e o pedido foi aceito!";
    }

    alterarRomance($jogadores, $nomeNPC, $nomeAlvo, -5);
    alterarRomance($jogadores, $nomeAlvo, $nomeNPC, -5);
    return "💔 $nomeNPC tentou pedir $nomeAlvo em namoro, mas recebeu um 'vamos com calma'.";
}
if (isset($_POST['novo_jogo'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

/* IR PARA A GRANDE FINAL */
if (isset($_POST['ir_final'])) {
    header("Location: final.php");
    exit;
}

/* LIMPAR AO VIVO */
if (isset($_POST['limpar_log'])) {
    $_SESSION['evento_extra'] = [];
    header("Location: jogo.php");
    exit;
}

/* SELECIONAR AÇÃO */
if (isset($_POST['selecionar_acao'])) {
    $_SESSION['acao_selecionada'] = $_POST['selecionar_acao'];
    header("Location: jogo.php");
    exit;
}

/* SELECIONAR AÇÃO DA FESTA */
if (isset($_POST['selecionar_acao_festa'])) {

    $_SESSION['acao_festa_selecionada'] =
        $_POST['selecionar_acao_festa'];

    header("Location: jogo.php");
    exit;
}

/* CANCELAR AÇÃO DA FESTA */
if (isset($_POST['cancelar_acao_festa'])) {

    unset($_SESSION['acao_festa_selecionada']);

    header("Location: jogo.php");
    exit;
}

/* CANCELAR AÇÃO */
if (isset($_POST['cancelar_acao'])) {
    unset($_SESSION['acao_selecionada']);
    header("Location: jogo.php");
    exit;
}

/* DEFINIR VIP */
if (isset($_POST['definir_vip'])) {

    $lider = $_SESSION['lider'] ?? '';
    $selecionados = $_POST['vip'] ?? [];

    garantirMeuJogadorNaLista($jogadores);

    /* Segurança: remove vazio, duplicado e o próprio líder da lista manual */
    $selecionados = array_values(array_unique(array_filter($selecionados, function ($nome) use ($lider) {
        return trim((string)$nome) != '' && !nomeIgual($nome, $lider);
    })));

    if (count($selecionados) != $qtdVIP) {
        $_SESSION['evento_extra'][] = "⚠️ Você precisa escolher exatamente $qtdVIP participantes para o VIP.";
        header("Location: jogo.php");
        exit;
    }

    foreach ($jogadores as &$j) {

        $j['status']['vip'] = false;
        $j['status']['xepa'] = false;

        if (!isset($j['estatisticas'])) {
            $j['estatisticas'] = [];
        }

        if ($j['nome'] == $lider) {
            $j['status']['vip'] = true;
            $j['estatisticas']['vip'] = ($j['estatisticas']['vip'] ?? 0) + 1;
            continue;
        }

        if (in_array($j['nome'], $selecionados)) {
            $j['status']['vip'] = true;
            $j['estatisticas']['vip'] = ($j['estatisticas']['vip'] ?? 0) + 1;
        } else {
            $j['status']['xepa'] = true;
            $j['estatisticas']['xepa'] = ($j['estatisticas']['xepa'] ?? 0) + 1;
        }
    }
    unset($j);

    garantirMeuJogadorNaLista($jogadores);
    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['vip_definido'] = true;
    $_SESSION['fase_semana'] = 'anjo';

    $vipLista = [];
    $xepaLista = [];

    foreach ($jogadores as $j) {
        if (!empty($j['status']['vip'])) {
            $vipLista[] = $j['nome'];
        }

        if (!empty($j['status']['xepa'])) {
            $xepaLista[] = $j['nome'];
        }
    }

    $_SESSION['evento_extra'][] = "👑 O líder $lider definiu o VIP.";
    $_SESSION['evento_extra'][] = "🟡 VIP: " . implode(", ", $vipLista) . ".";
    $_SESSION['evento_extra'][] = "🍞 Xepa: " . implode(", ", $xepaLista) . ".";

    header("Location: jogo.php");
    exit;
}

/* VIP AUTOMÁTICO DO NPC */
if ($fase == 'vip_xepa' && !isset($_SESSION['vip_definido'])) {

    $lider = $_SESSION['lider'] ?? '';

    if ($lider != $meuNome) {

        $afinidades = [];

        foreach ($jogadores as $j) {
            if ($j['nome'] != $lider) {
                $afinidade = calcularRelacaoIA($jogadores, $lider, $j['nome'], $meuNome);
                $afinidades[$j['nome']] = $afinidade;
            }
        }

        arsort($afinidades);

        $vipEscolhidos = array_slice(array_keys($afinidades), 0, $qtdVIP);

        foreach ($jogadores as &$j) {

            $j['status']['vip'] = false;
            $j['status']['xepa'] = false;

            if ($j['nome'] == $lider) {
                $j['status']['vip'] = true;
                continue;
            }

            if (in_array($j['nome'], $vipEscolhidos)) {
                $j['status']['vip'] = true;
            } else {
                $j['status']['xepa'] = true;
            }
        }
        unset($j);

        garantirMeuJogadorNaLista($jogadores);
        $_SESSION['jogadores'] = $jogadores;
        $_SESSION['vip_definido'] = true;

        $vipLista = [];
        $xepaLista = [];

        foreach ($jogadores as $j) {
            if (!empty($j['status']['vip'])) {
                $vipLista[] = $j['nome'];
            }

            if (!empty($j['status']['xepa'])) {
                $xepaLista[] = $j['nome'];
            }
        }

        $_SESSION['evento_extra'][] = "👑 O líder $lider definiu o VIP.";
        $_SESSION['evento_extra'][] = "🟡 VIP: " . implode(", ", $vipLista) . ".";
        $_SESSION['evento_extra'][] = "🍞 Xepa: " . implode(", ", $xepaLista) . ".";
    }
}

/* DEFINIR MONSTRO PELO JOGADOR */
if (isset($_POST['definir_monstro'])) {

    $anjo = $_SESSION['anjo'] ?? '';
    $selecionados = $_POST['monstro'] ?? [];

    if (count($selecionados) > 2) {
        $_SESSION['evento_extra'][] = "⚠️ Você só pode escolher até 2 participantes para o Monstro.";
        header("Location: jogo.php");
        exit;
    }

    foreach ($jogadores as &$j) {

        $j['status']['monstro'] = false;

        if (in_array($j['nome'], $selecionados)) {
            $j['status']['monstro'] = true;
            if (!isset($j['estatisticas'])) {
                $j['estatisticas'] = [];
            }

            $j['estatisticas']['monstro'] =
                ($j['estatisticas']['monstro'] ?? 0) + 1;
            $j['popularidade'] = max(0, ($j['popularidade'] ?? 50) - rand(3, 8));
            alterarAfinidade($jogadores, $j['nome'], $anjo, -5, 8, -5);
        }
    }
    unset($j);

    garantirMeuJogadorNaLista($jogadores);
    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['monstro'] = $selecionados;
    $_SESSION['monstro_definido'] = true;
    $_SESSION['fase_semana'] = 'bigfone';

    $_SESSION['evento_extra'][] =
        "👹 $anjo colocou no Monstro: " . implode(" e ", $selecionados) . ".";

    header("Location: jogo.php");
    exit;
}

/* MONSTRO AUTOMÁTICO DO NPC */
if ($fase == 'monstro' && !isset($_SESSION['monstro_definido'])) {

    $anjo = $_SESSION['anjo'] ?? '';

    if ($anjo != $meuNome) {

        $afinidades = [];

        foreach ($jogadores as $j) {
            if ($j['nome'] != $anjo) {
                $afinidade = calcularRelacaoIA($jogadores, $anjo, $j['nome'], $meuNome);
                $afinidades[$j['nome']] = $afinidade;
            }
        }

        asort($afinidades);

        $monstros = array_slice(array_keys($afinidades), 0, 2);

        foreach ($jogadores as &$j) {

            $j['status']['monstro'] = false;

            if (in_array($j['nome'], $monstros)) {
                $j['status']['monstro'] = true;
                if (!isset($j['estatisticas'])) {
                    $j['estatisticas'] = [];
                }

                $j['estatisticas']['monstro'] =
                    ($j['estatisticas']['monstro'] ?? 0) + 1;
                $j['popularidade'] = max(0, ($j['popularidade'] ?? 50) - rand(3, 8));
                alterarAfinidade($jogadores, $j['nome'], $anjo, -5, 8, -5);
            }
        }
        unset($j);

        garantirMeuJogadorNaLista($jogadores);
        $_SESSION['jogadores'] = $jogadores;
        $_SESSION['monstro'] = $monstros;
        $_SESSION['monstro_definido'] = true;
        $_SESSION['fase_semana'] = 'bigfone';

        $_SESSION['evento_extra'][] =
            "👹 O anjo $anjo colocou no Monstro: " . implode(" e ", $monstros) . ".";

        header("Location: jogo.php");
        exit;
    }
}

/* PREPARAR AÇÕES */
if (strpos($fase, 'interacoes') !== false && !isset($_SESSION['acoes_restantes'])) {
    $_SESSION['acoes_restantes'] = 3;
}

function gerarAcoesNPC(&$jogadores, $meuNome, $quantidade = 3)
{
    $eventos = [];

    atualizarRelacoesMarcantes($jogadores);

    foreach ($jogadores as $npc) {

        $nomeNPC = $npc['nome'] ?? '';
        $perfil = perfilPersonalidadeCompleto($npc['personalidade'] ?? 'Neutro');

        if ($nomeNPC == '' || $nomeNPC == $meuNome) continue;

        for ($i = 0; $i < $quantidade; $i++) {

            unset($alvo);

            $alvos = array_values(array_filter($jogadores, function ($j) use ($nomeNPC) {
                return ($j['nome'] ?? '') != $nomeNPC;
            }));

            if (empty($alvos)) continue;

            $nomeAlvoEscolhido = null;

            $chanceRival = 25 + (int)(($perfil['treta'] ?? 50) / 5);
            $chanceAliado = 20 + (int)(($perfil['alianca'] ?? 50) / 6);

            if (rand(1, 100) <= $chanceRival) {
                $nomeAlvoEscolhido = escolherAlvoNPCPorRelacao($jogadores, $nomeNPC, $meuNome, 'rival');
            }

            if ($nomeAlvoEscolhido == null && rand(1, 100) <= $chanceAliado) {
                $nomeAlvoEscolhido = escolherAlvoNPCPorRelacao($jogadores, $nomeNPC, $meuNome, 'aliado');
            }

            /* Pequena chance de a IA mirar no jogador */
            if ($nomeAlvoEscolhido == null && rand(1, 100) <= 8) {
                foreach ($alvos as $possivelAlvo) {
                    if (($possivelAlvo['nome'] ?? '') == $meuNome) {
                        $nomeAlvoEscolhido = $meuNome;
                        break;
                    }
                }
            }

            if ($nomeAlvoEscolhido != null) {
                $alvo = buscarJogadorPorNome($jogadores, $nomeAlvoEscolhido);
            }

            if (!isset($alvo) || $alvo == null) {
                $alvo = $alvos[array_rand($alvos)];
            }

            $nomeAlvo = $alvo['nome'] ?? '';

            $relacaoAlvo = calcularRelacaoIA($jogadores, $nomeNPC, $nomeAlvo, $meuNome);
            $ehRival = saoRivais($jogadores, $nomeNPC, $nomeAlvo, $meuNome);
            $ehAliado = saoAliados($jogadores, $nomeNPC, $nomeAlvo, $meuNome);

            $possiveis = [];

            if ($ehRival) {
                $possiveis = [3, 3, 2];

                if (($perfil['fofoca'] ?? 0) >= 60) {
                    $possiveis[] = 2;
                }

                if (($perfil['treta'] ?? 0) >= 70) {
                    $possiveis[] = 3;
                }
            } elseif ($ehAliado) {
                $possiveis = [1, 4, 4];

                if (($perfil['alianca'] ?? 0) >= 60) {
                    $possiveis[] = 4;
                }
            } else {
                if (rand(1, 100) <= $perfil['alianca']) {
                    $possiveis[] = 4;
                }

                if (rand(1, 100) <= $perfil['treta']) {
                    $possiveis[] = 3;
                }

                if (rand(1, 100) <= $perfil['emocao']) {
                    $possiveis[] = 1;
                }

                if (rand(1, 100) <= $perfil['fofoca']) {
                    $possiveis[] = 2;
                }
            }

            if (empty($possiveis)) {
                $possiveis[] = rand(1, 4);
            }

            $acao = $possiveis[array_rand($possiveis)];

            if ($acao == 1) {
                alterarAfinidade($jogadores, $nomeNPC, $nomeAlvo, 5, -2, 3);
                alterarAfinidade($jogadores, $nomeAlvo, $nomeNPC, 3, -1, 2);

                if ($nomeAlvo == $meuNome) {
                    ajustarRelacaoJogador($nomeNPC, 5);
                    $eventos[] = "💬 $nomeNPC conversou com $meuNome. Sua afinidade com $nomeNPC subiu.";
                } else {
                    $eventos[] = "💬 $nomeNPC conversou com $nomeAlvo.";
                }
            }

            if ($acao == 2) {
                if ($ehRival) {
                    alterarAfinidade($jogadores, $nomeNPC, $nomeAlvo, -6, 6, -5);
                    alterarAfinidade($jogadores, $nomeAlvo, $nomeNPC, -5, 5, -4);

                    if ($nomeAlvo == $meuNome) {
                        ajustarRelacaoJogador($nomeNPC, -6);
                        $eventos[] = "🐍 $nomeNPC espalhou comentários contra $meuNome. A rivalidade aumentou.";
                    } else {
                        impactoPopularidadePorPersonalidade($jogadores, $nomeNPC, "fofoca", false);
                        $eventos[] = "🐍 $nomeNPC espalhou comentários contra $nomeAlvo.";
                    }
                } else {
                    alterarAfinidade($jogadores, $nomeNPC, $nomeAlvo, 8, -3, 6);
                    alterarAfinidade($jogadores, $nomeAlvo, $nomeNPC, 5, -2, 4);

                    if ($nomeAlvo == $meuNome) {
                        ajustarRelacaoJogador($nomeNPC, 8);
                        $eventos[] = "🤝 $nomeNPC tentou se aproximar de $meuNome. Sua afinidade com $nomeNPC subiu.";
                    } else {
                        $eventos[] = "🤝 $nomeNPC tentou se aproximar de $nomeAlvo.";
                    }
                }
            }

            if ($acao == 3) {
                alterarAfinidade($jogadores, $nomeNPC, $nomeAlvo, -8, 8, -5);
                alterarAfinidade($jogadores, $nomeAlvo, $nomeNPC, -8, 8, -5);

                if ($nomeAlvo == $meuNome) {
                    ajustarRelacaoJogador($nomeNPC, -8);
                    $eventos[] = "🔥 $nomeNPC teve um atrito com $meuNome. Sua afinidade com $nomeNPC caiu.";
                } else {
                    impactoPopularidadePorPersonalidade($jogadores, $nomeNPC, "treta", false);
                    $eventos[] = "🔥 $nomeNPC teve um atrito com $nomeAlvo.";
                }
            }

            if ($acao == 4) {
                alterarAfinidade($jogadores, $nomeNPC, $nomeAlvo, 10, -4, 8);
                alterarAfinidade($jogadores, $nomeAlvo, $nomeNPC, 6, -2, 5);

                if ($nomeAlvo == $meuNome) {
                    ajustarRelacaoJogador($nomeNPC, 10);
                    $eventos[] = "👀 $nomeNPC começou uma possível aliança com $meuNome. Sua afinidade com $nomeNPC subiu bastante.";
                } else {
                    impactoPopularidadePorPersonalidade($jogadores, $nomeNPC, "alianca", false);
                    $eventos[] = "👀 $nomeNPC começou uma possível aliança com $nomeAlvo.";
                }

                if(rand(1,100) <= 35){
                    $eventoAlianca = criarAliancaEntre($jogadores, $nomeNPC, $nomeAlvo);
                    if($eventoAlianca != ''){
                        $eventos[] = $eventoAlianca;
                    }
                }
            }

            registrarRelacaoMarcante($jogadores, $nomeNPC, $nomeAlvo);
            registrarRelacaoMarcante($jogadores, $nomeAlvo, $nomeNPC);
        }
    }

    $_SESSION['jogadores'] = $jogadores;

    return $eventos;
}


function gerarAcoesFestaNPC(&$jogadores, $meuNome, $quantidade = 2)
{

    $eventos = [];

    atualizarRelacoesMarcantes($jogadores);

    foreach ($jogadores as $npc) {

        $nomeNPC = $npc['nome'] ?? '';
        $perfil = perfilPersonalidadeCompleto($npc['personalidade'] ?? 'Neutro');

        if ($nomeNPC == '' || $nomeNPC == $meuNome) continue;

        for ($i = 0; $i < $quantidade; $i++) {

            unset($alvo);

            $alvos = array_values(array_filter($jogadores, function ($j) use ($nomeNPC) {
                return ($j['nome'] ?? '') != $nomeNPC;
            }));

            if (empty($alvos)) continue;

            $nomeAlvoEscolhido = null;

            if (rand(1, 100) <= (25 + (int)(($perfil['treta'] ?? 50) / 6))) {
                $nomeAlvoEscolhido = escolherAlvoNPCPorRelacao($jogadores, $nomeNPC, $meuNome, 'rival');
            }

            if ($nomeAlvoEscolhido == null && rand(1, 100) <= (25 + (int)(($perfil['alianca'] ?? 50) / 6))) {
                $nomeAlvoEscolhido = escolherAlvoNPCPorRelacao($jogadores, $nomeNPC, $meuNome, 'aliado');
            }

            if ($nomeAlvoEscolhido == null && rand(1, 100) <= 8) {
                foreach ($alvos as $possivelAlvo) {
                    if (($possivelAlvo['nome'] ?? '') == $meuNome) {
                        $nomeAlvoEscolhido = $meuNome;
                        break;
                    }
                }
            }

            if ($nomeAlvoEscolhido != null) {
                $alvo = buscarJogadorPorNome($jogadores, $nomeAlvoEscolhido);
            }

            if (!isset($alvo) || $alvo == null) {
                $alvo = $alvos[array_rand($alvos)];
            }

            $nomeAlvo = $alvo['nome'] ?? '';

            $ehRival = saoRivais($jogadores, $nomeNPC, $nomeAlvo, $meuNome);
            $ehAliado = saoAliados($jogadores, $nomeNPC, $nomeAlvo, $meuNome);

            $possiveis = [];

            if ($ehRival) {
                $possiveis = [4, 4, 3];

                if (($perfil['treta'] ?? 0) >= 70) {
                    $possiveis[] = 4;
                }
            } elseif ($ehAliado) {
                $possiveis = [1, 5, 5];

                if (($perfil['romance'] ?? 0) >= 60) {
                    $possiveis[] = 2;
                }
            } else {
                if (rand(1, 100) <= $perfil['romance']) {
                    $possiveis[] = 2;
                }

                if (rand(1, 100) <= $perfil['vt']) {
                    $possiveis[] = 3;
                }

                if (rand(1, 100) <= $perfil['treta']) {
                    $possiveis[] = 4;
                }

                if (rand(1, 100) <= $perfil['alianca']) {
                    $possiveis[] = 1;
                    $possiveis[] = 5;
                }
            }

            if (empty($possiveis)) {
                $possiveis[] = rand(1, 5);
            }

            $acao = $possiveis[array_rand($possiveis)];

            /* APROXIMAR */
            if ($acao == 1) {

                alterarAfinidade($jogadores, $nomeNPC, $nomeAlvo, rand(5, 10), -3, rand(3, 8));
                alterarAfinidade($jogadores, $nomeAlvo, $nomeNPC, rand(3, 7), -2, rand(2, 6));

                if ($nomeAlvo == $meuNome) {
                    ajustarRelacaoJogador($nomeNPC, 8);
                    $eventos[] = "🥂 $nomeNPC passou parte da festa junto com $meuNome. Sua afinidade com $nomeNPC subiu.";
                } else {
                    $eventos[] = "🥂 $nomeNPC passou a festa junto com $nomeAlvo.";
                }
            }

            /* FLERTE */
            if ($acao == 2 && !$ehRival) {

                alterarAfinidade($jogadores, $nomeNPC, $nomeAlvo, rand(8, 15), -2, rand(5, 10));
                alterarAfinidade($jogadores, $nomeAlvo, $nomeNPC, rand(5, 10), -2, rand(3, 8));
                alterarRomance($jogadores, $nomeNPC, $nomeAlvo, rand(3, 8));
                alterarRomance($jogadores, $nomeAlvo, $nomeNPC, rand(2, 6));

                if ($nomeAlvo == $meuNome) {
                    ajustarRelacaoJogador($nomeNPC, 10);
                    $eventos[] = "💕 $nomeNPC flertou com $meuNome na festa. Sua afinidade com $nomeNPC subiu.";
                } else {
                    $eventos[] = "💕 $nomeNPC flertou com $nomeAlvo na festa.";
                }

                $pedidoNPC = tentarPedidoNamoroNPC($jogadores, $nomeNPC, $nomeAlvo);
                if ($pedidoNPC != '') {
                    if ($nomeAlvo == $meuNome) {
                        if (isset($_SESSION['casais'][$meuNome]) && $_SESSION['casais'][$meuNome] == $nomeNPC) {
                            ajustarRelacaoJogador($nomeNPC, 12);
                        } else {
                            ajustarRelacaoJogador($nomeNPC, -4);
                        }
                    }
                    $eventos[] = $pedidoNPC;
                }
            }

            /* VT */
            if ($acao == 3) {

                $mudanca = alterarPopularidadeMotivo($jogadores, $nomeNPC, -5, 10, "tentou roubar a cena na festa", false);

                if ($mudanca >= 0) {
                    $eventos[] = "📸 $nomeNPC roubou a cena na festa.";
                } else {
                    $eventos[] = "📸 $nomeNPC tentou aparecer demais e virou meme.";
                }
            }

            /* TRETA */
            if ($acao == 4) {

                alterarAfinidade($jogadores, $nomeNPC, $nomeAlvo, rand(-15, -5), rand(5, 15), rand(-10, -3));
                alterarAfinidade($jogadores, $nomeAlvo, $nomeNPC, rand(-15, -5), rand(5, 15), rand(-10, -3));

                if ($nomeAlvo == $meuNome) {
                    ajustarRelacaoJogador($nomeNPC, -12);
                    $eventos[] = "🔥 $nomeNPC discutiu com $meuNome durante a festa. Sua afinidade com $nomeNPC caiu.";
                } else {
                    $eventos[] = "🔥 $nomeNPC discutiu com $nomeAlvo durante a festa.";
                }
            }

            /* DANÇA */
            if ($acao == 5) {

                alterarPopularidadeMotivo($jogadores, $nomeNPC, 1, 6, "se destacou dançando na festa", false);
                alterarAfinidade($jogadores, $nomeNPC, $nomeAlvo, rand(3, 7), -2, 3);
                alterarAfinidade($jogadores, $nomeAlvo, $nomeNPC, rand(2, 5), -1, 2);

                if ($nomeAlvo == $meuNome) {
                    ajustarRelacaoJogador($nomeNPC, 6);
                    $eventos[] = "🕺 $nomeNPC chamou $meuNome para dançar na festa. Sua afinidade com $nomeNPC subiu.";
                } else {
                    $eventos[] = "🕺 $nomeNPC dançou com $nomeAlvo e viralizou na festa.";
                }
            }

            registrarRelacaoMarcante($jogadores, $nomeNPC, $nomeAlvo);
            registrarRelacaoMarcante($jogadores, $nomeAlvo, $nomeNPC);
        }
    }

    $_SESSION['jogadores'] = $jogadores;

    return $eventos;
}


/* =========================
   🎥 CONFESSIONÁRIO INTELIGENTE
   Gera falas com base em afinidade, rivalidade, confiança, romance e personalidade.
========================= */

function escolherFalaNaoRepetida($opcoes, $historico)
{

    if (empty($opcoes)) {
        return "";
    }

    if (!is_array($historico)) {
        $historico = [];
    }

    $historicoLimpo = array_map(function ($fala) {
        return trim(strip_tags((string)$fala));
    }, $historico);

    shuffle($opcoes);

    foreach ($opcoes as $fala) {
        $falaLimpa = trim(strip_tags((string)$fala));

        if (!in_array($falaLimpa, $historicoLimpo)) {
            return $fala;
        }
    }

    /* Se já usou todas, permite repetir para não travar o jogo */
    return $opcoes[array_rand($opcoes)];
}

/* =========================
   🎥 CONFESSIONÁRIO INTELIGENTE TURBINADO
   Mais categorias + sistema anti-repetição.
========================= */

function gerarConfessionarioInteligente(&$jogadores, $meuNome)
{

    $falas = [];

    atualizarRelacoesMarcantes($jogadores);

    foreach ($jogadores as $jogador) {

        $nome = $jogador['nome'] ?? '';
        if ($nome == '') continue;

        $personalidade = $jogador['personalidade'] ?? 'Neutro';
        $perfil = perfilPersonalidadeCompleto($personalidade);

        $historicoConfessionario = $jogador['confessionarios'] ?? [];

        $melhorAliado = null;
        $melhorAliadoScore = -999;
        $maiorRival = null;
        $maiorRivalScore = -999;
        $maiorRomance = null;
        $maiorRomanceScore = -999;
        $alvoEstrategico = null;
        $alvoEstrategicoScore = -999;
        $pessoaFalsa = null;
        $pessoaFalsaScore = -999;
        $pessoaDecepcao = null;
        $pessoaDecepcaoScore = -999;

        foreach ($jogadores as $alvo) {

            $nomeAlvo = $alvo['nome'] ?? '';
            if ($nomeAlvo == '' || $nomeAlvo == $nome) continue;

            $rel = obterRelacaoCompleta($jogadores, $nome, $nomeAlvo, $meuNome);
            $popularidadeAlvo = $alvo['popularidade'] ?? 50;
            $romance = obterRomance($jogadores, $nome, $nomeAlvo);

            $scoreAliado = $rel['amizade'] + $rel['confianca'] - $rel['rivalidade'];
            $scoreRival = $rel['rivalidade'] + (100 - $rel['amizade']) - $rel['confianca'];
            $scoreEstrategico = (100 - $popularidadeAlvo) + $rel['rivalidade'] - ($rel['confianca'] * 0.4);
            $scoreFalsidade = $rel['rivalidade'] + (100 - $rel['confianca']);
            $scoreDecepcao = (100 - $rel['confianca']) + max(0, 40 - $rel['amizade']) + $rel['rivalidade'];

            if ($scoreAliado > $melhorAliadoScore) {
                $melhorAliadoScore = $scoreAliado;
                $melhorAliado = $nomeAlvo;
            }

            if ($scoreRival > $maiorRivalScore) {
                $maiorRivalScore = $scoreRival;
                $maiorRival = $nomeAlvo;
            }

            if ($romance > $maiorRomanceScore) {
                $maiorRomanceScore = $romance;
                $maiorRomance = $nomeAlvo;
            }

            if ($scoreEstrategico > $alvoEstrategicoScore) {
                $alvoEstrategicoScore = $scoreEstrategico;
                $alvoEstrategico = $nomeAlvo;
            }

            if ($scoreFalsidade > $pessoaFalsaScore) {
                $pessoaFalsaScore = $scoreFalsidade;
                $pessoaFalsa = $nomeAlvo;
            }

            if ($scoreDecepcao > $pessoaDecepcaoScore) {
                $pessoaDecepcaoScore = $scoreDecepcao;
                $pessoaDecepcao = $nomeAlvo;
            }
        }

        $tiposPossiveis = [];

        if ($maiorRomanceScore >= 30 && $maiorRomance) {
            $tiposPossiveis = array_merge($tiposPossiveis, array_fill(0, max(1, (int)(($perfil['romance'] ?? 50) / 18)), 'romance'));
        }

        if ($maiorRivalScore >= 75 && $maiorRival) {
            $tiposPossiveis = array_merge($tiposPossiveis, array_fill(0, max(1, (int)(($perfil['treta'] ?? 50) / 18)), 'rival'));
            $tiposPossiveis[] = 'vinganca';
        }

        if ($melhorAliadoScore >= 65 && $melhorAliado) {
            $tiposPossiveis = array_merge($tiposPossiveis, array_fill(0, max(1, (int)(($perfil['alianca'] ?? 50) / 18)), 'aliado'));
        }

        if ($pessoaFalsaScore >= 85 && $pessoaFalsa) {
            $tiposPossiveis[] = 'falsidade';
        }

        if ($pessoaDecepcaoScore >= 90 && $pessoaDecepcao) {
            $tiposPossiveis[] = 'decepcao';
        }

        if ($alvoEstrategico != null && ($personalidade == 'Estrategista' || $personalidade == 'Manipulador' || $personalidade == 'Falso' || ($perfil['alianca'] ?? 0) >= 80)) {
            $tiposPossiveis[] = 'estrategia';
        }

        if (($perfil['vt'] ?? 50) >= 45) {
            $tiposPossiveis[] = 'vt';
        }

        if (($perfil['emocao'] ?? 50) >= 55) {
            $tiposPossiveis[] = 'medo_paredao';
            $tiposPossiveis[] = 'desabafo';
        }

        $tiposPossiveis[] = 'observacao';
        $tiposPossiveis[] = 'sonho_vitoria';
        $tiposPossiveis[] = 'neutro';

        $tipo = $tiposPossiveis[array_rand($tiposPossiveis)];

        if ($tipo == 'romance' && $maiorRomance) {
            $opcoes = [
                "💘 <b>$nome</b>: \"Eu tento disfarçar, mas está ficando difícil esconder que eu tenho um carinho diferente por $maiorRomance.\"",
                "💕 <b>$nome</b>: \"Quando $maiorRomance chega perto, eu esqueço por alguns segundos que isso aqui também é um jogo.\"",
                "😍 <b>$nome</b>: \"Não sei se é só convivência ou se está virando sentimento, mas $maiorRomance mexe comigo.\"",
                "🌙 <b>$nome</b>: \"Às vezes uma conversa boba com $maiorRomance muda meu dia inteiro aqui dentro.\"",
                "🫶 <b>$nome</b>: \"Eu não quero me precipitar, mas minha conexão com $maiorRomance está ficando cada vez mais forte.\"",
                "💗 <b>$nome</b>: \"Se isso é estratégia ou sentimento, eu ainda não sei. Só sei que $maiorRomance virou alguém especial.\""
            ];

            alterarPopularidadeMotivo($jogadores, $nome, 0, 3, "rendeu um momento romântico no confessionário", false);
        } elseif ($tipo == 'rival' && $maiorRival) {
            $opcoes = [
                "🐍 <b>$nome</b>: \"Eu não consigo confiar em $maiorRival. Tem alguma coisa ali que não me passa verdade.\"",
                "🔥 <b>$nome</b>: \"Se eu ganhar poder nessa casa, $maiorRival precisa se preocupar.\"",
                "🎯 <b>$nome</b>: \"Meu alvo hoje tem nome: $maiorRival. Não vou fingir que está tudo bem.\"",
                "😤 <b>$nome</b>: \"Toda vez que $maiorRival fala, eu sinto que tem um jogo escondido por trás.\"",
                "⚡ <b>$nome</b>: \"Minha paciência com $maiorRival está acabando, e eu acho que a casa já percebeu.\"",
                "🧨 <b>$nome</b>: \"Eu estou tentando manter a calma, mas $maiorRival sabe exatamente como me tirar do sério.\""
            ];

            alterarPopularidadeMotivo($jogadores, $nome, -2, 4, "foi direto no confessionário sobre um rival", false);
        } elseif ($tipo == 'aliado' && $melhorAliado) {
            $opcoes = [
                "🤝 <b>$nome</b>: \"Eu confio muito em $melhorAliado. É alguém que eu quero levar longe no jogo.\"",
                "❤️ <b>$nome</b>: \"$melhorAliado é uma das pessoas que mais me passa segurança aqui dentro.\"",
                "🛡️ <b>$nome</b>: \"Se depender de mim, $melhorAliado não vai sozinho nessa casa.\"",
                "👥 <b>$nome</b>: \"Minha troca com $melhorAliado é muito verdadeira. Aqui dentro isso vale ouro.\"",
                "🔐 <b>$nome</b>: \"Eu conto coisas para $melhorAliado que não conto para mais ninguém nessa casa.\"",
                "🌟 <b>$nome</b>: \"Ter $melhorAliado por perto me deixa mais forte para enfrentar o jogo.\""
            ];

            alterarAfinidade($jogadores, $nome, $melhorAliado, 3, -1, 4);
            alterarAfinidade($jogadores, $melhorAliado, $nome, 2, -1, 3);
        } elseif ($tipo == 'estrategia' && $alvoEstrategico) {
            $opcoes = [
                "🧠 <b>$nome</b>: \"Eu estou observando $alvoEstrategico. Às vezes, eliminar a pessoa certa muda o jogo inteiro.\"",
                "🎮 <b>$nome</b>: \"Aqui ninguém sobrevive só sendo legal. Eu preciso pensar nos próximos passos, e $alvoEstrategico está no meu radar.\"",
                "👀 <b>$nome</b>: \"Tem gente que acha que eu não percebo, mas eu estou calculando tudo. $alvoEstrategico pode virar alvo.\"",
                "♟️ <b>$nome</b>: \"Esse jogo é como xadrez. Se eu mexer uma peça errada, posso cair junto.\"",
                "📌 <b>$nome</b>: \"Meu plano agora é parecer tranquilo enquanto observo quem está se aproximando de quem.\"",
                "🗺️ <b>$nome</b>: \"Eu já estou pensando duas rodadas à frente. Quem não fizer isso vai ser engolido pelo jogo.\""
            ];
        } elseif ($tipo == 'falsidade' && $pessoaFalsa) {
            $opcoes = [
                "🐍 <b>$nome</b>: \"Tem gente aqui que fala uma coisa na minha frente e outra pelas costas. $pessoaFalsa me deixa com um pé atrás.\"",
                "🎭 <b>$nome</b>: \"Algumas máscaras estão começando a cair, e a de $pessoaFalsa está escorregando.\"",
                "👀 <b>$nome</b>: \"Eu percebo os olhares, os cochichos e as mudanças de postura. $pessoaFalsa não me engana tanto assim.\"",
                "🕵️ <b>$nome</b>: \"Eu ainda não tenho certeza, mas sinto que $pessoaFalsa joga dos dois lados.\"",
                "😒 <b>$nome</b>: \"O problema não é jogar. O problema é fingir que não joga. E $pessoaFalsa faz muito isso.\""
            ];
        } elseif ($tipo == 'vinganca' && $maiorRival) {
            $opcoes = [
                "⚔️ <b>$nome</b>: \"Eu não esqueço quem tentou me prejudicar. $maiorRival ainda vai ouvir meu nome nessa casa.\"",
                "🎯 <b>$nome</b>: \"Se eu ganhar poder, algumas contas vão ser cobradas, principalmente com $maiorRival.\"",
                "🔥 <b>$nome</b>: \"$maiorRival acha que eu esqueci. Não esqueci. Só estou esperando o momento certo.\"",
                "💣 <b>$nome</b>: \"Eu posso até sorrir na sala, mas no jogo eu sei exatamente quem me feriu.\"",
                "🧊 <b>$nome</b>: \"A melhor resposta para $maiorRival vai ser no jogo, não no grito.\""
            ];
        } elseif ($tipo == 'decepcao' && $pessoaDecepcao) {
            $opcoes = [
                "💔 <b>$nome</b>: \"Eu esperava mais de $pessoaDecepcao. Talvez eu tenha confiado rápido demais.\"",
                "😞 <b>$nome</b>: \"Confiei em quem não deveria, e isso está começando a pesar.\"",
                "🥀 <b>$nome</b>: \"Algumas alianças não eram tão verdadeiras quanto pareciam. $pessoaDecepcao me mostrou isso.\"",
                "🌧️ <b>$nome</b>: \"O pior não é ser votado. É sentir que alguém que você protegia não faria o mesmo por você.\"",
                "🫤 <b>$nome</b>: \"Eu não estou com raiva, estou decepcionado. E isso às vezes é pior.\""
            ];
        } elseif ($tipo == 'medo_paredao') {
            $opcoes = [
                "😰 <b>$nome</b>: \"Eu tento parecer tranquilo, mas estou sentindo que meu nome está circulando pela casa.\"",
                "🚨 <b>$nome</b>: \"Essa semana pode mudar tudo. Estou com medo de bater no paredão.\"",
                "😟 <b>$nome</b>: \"Não sei em quem confiar. Qualquer voto pode cair em mim.\"",
                "🫣 <b>$nome</b>: \"Às vezes eu entro no quarto e sinto que a conversa muda. Isso me deixa alerta.\"",
                "⏳ <b>$nome</b>: \"A pior parte é esperar. Você nunca sabe se está seguro de verdade.\"",
                "🫥 <b>$nome</b>: \"Eu sinto que estou pisando em ovos. Qualquer escolha errada pode me colocar no paredão.\""
            ];
        } elseif ($tipo == 'desabafo') {
            $opcoes = [
                "😭 <b>$nome</b>: \"Hoje bateu um cansaço. Essa casa exige muito da cabeça da gente.\"",
                "💭 <b>$nome</b>: \"Tem dias em que eu me sinto forte, e tem dias em que eu só queria respirar sem pensar em voto.\"",
                "🏠 <b>$nome</b>: \"A saudade de casa aparece do nada, principalmente quando o clima pesa aqui dentro.\"",
                "🌫️ <b>$nome</b>: \"Eu estou tentando não me perder no meio de tanta estratégia, treta e julgamento.\"",
                "🤍 <b>$nome</b>: \"Eu entrei achando que seria só jogo, mas aqui tudo fica intenso muito rápido.\""
            ];
        } elseif ($tipo == 'vt') {
            $opcoes = [
                "📺 <b>$nome</b>: \"Eu vim para jogar, me entregar e aparecer. Quem não quer ser visto nem deveria estar aqui.\"",
                "✨ <b>$nome</b>: \"O público ainda vai entender meu jeito. Eu sei que posso crescer muito nessa casa.\"",
                "🎥 <b>$nome</b>: \"Cada dia aqui é uma chance de mostrar quem eu sou de verdade.\"",
                "😎 <b>$nome</b>: \"Eu não nasci para passar despercebido. Se a câmera me procura, eu entrego.\"",
                "🌟 <b>$nome</b>: \"Tem gente que chama de VT. Eu chamo de protagonismo.\"",
                "🔥 <b>$nome</b>: \"Se for para jogar, eu vou jogar aparecendo. Planta eu não sou.\""
            ];

            alterarPopularidadeMotivo($jogadores, $nome, -2, 5, "tentou se vender bem no confessionário", false);
        } elseif ($tipo == 'observacao') {
            $opcoes = [
                "☕ <b>$nome</b>: \"A cozinha está mais movimentada que o jogo hoje. É ali que muita coisa nasce.\"",
                "🛏️ <b>$nome</b>: \"Tem gente dormindo enquanto o jogo acontece. Depois não entende quando vira alvo.\"",
                "🎉 <b>$nome</b>: \"A festa mostrou lados que eu ainda não conhecia de algumas pessoas.\"",
                "👀 <b>$nome</b>: \"Eu observo muito mais do que as pessoas imaginam.\"",
                "🛋️ <b>$nome</b>: \"Na sala todo mundo sorri, mas eu sinto tensão no ar.\"",
                "🚿 <b>$nome</b>: \"Até conversa de banheiro aqui pode virar estratégia. Nada passa despercebido.\""
            ];
        } elseif ($tipo == 'sonho_vitoria') {
            $opcoes = [
                "🏆 <b>$nome</b>: \"Eu me imagino chegando na final todos os dias. Esse sonho me mantém firme.\"",
                "✨ <b>$nome</b>: \"Não entrei aqui para ser coadjuvante. Quero deixar minha marca.\"",
                "🎯 <b>$nome</b>: \"Meu objetivo continua o mesmo: vencer. Todo o resto é caminho.\"",
                "👑 <b>$nome</b>: \"Eu sei que ainda falta muito, mas eu consigo me ver no último dia.\"",
                "💫 <b>$nome</b>: \"Se eu sobreviver às próximas semanas, ninguém mais me segura.\"",
                "📣 <b>$nome</b>: \"Eu quero que o público olhe para mim e veja alguém que merece chegar longe.\""
            ];
        } else {
            $opcoes = [
                "😶 <b>$nome</b>: \"Hoje eu prefiro observar. Às vezes, o silêncio entrega mais do que uma briga.\"",
                "💭 <b>$nome</b>: \"Essa casa muda muito rápido. Quem é aliado hoje pode ser rival amanhã.\"",
                "🏠 <b>$nome</b>: \"Eu estou tentando entender meu lugar no jogo sem me perder no caminho.\"",
                "🌪️ <b>$nome</b>: \"Quando eu acho que entendi a casa, alguma coisa muda de novo.\"",
                "🧩 <b>$nome</b>: \"Cada pessoa aqui é uma peça diferente. O difícil é descobrir onde cada uma se encaixa.\"",
                "🕯️ <b>$nome</b>: \"Tem horas que o melhor movimento é não se mexer.\""
            ];
        }

        $fala = escolherFalaNaoRepetida($opcoes, $historicoConfessionario);

        if ($fala == '') {
            continue;
        }

        $falas[] = $fala;

        foreach ($jogadores as &$jAtualizar) {
            if (($jAtualizar['nome'] ?? '') == $nome) {
                if (!isset($jAtualizar['confessionarios']) || !is_array($jAtualizar['confessionarios'])) {
                    $jAtualizar['confessionarios'] = [];
                }

                $jAtualizar['confessionarios'][] = strip_tags($fala);

                aplicarImpactoPublicoConfessionario($jogadores, $nome, $tipo);

                /* Evita deixar a sessão enorme com muitas rodadas */
                if (count($jAtualizar['confessionarios']) > 30) {
                    $jAtualizar['confessionarios'] = array_slice($jAtualizar['confessionarios'], -30);
                }

                break;
            }
        }
        unset($jAtualizar);
    }

    shuffle($falas);

    return $falas;
}
function prepararConfessionarioDaRodada(&$jogadores, $meuNome)
{

    if (isset($_SESSION['confessionario_feito'])) return;

    $_SESSION['confessionario_falas'] = gerarConfessionarioInteligente($jogadores, $meuNome);
    $_SESSION['confessionario_feito'] = true;
    $_SESSION['jogadores'] = $jogadores;

    /* O Confessionário deve aparecer apenas na tela da fase confessionario,
       então não enviamos mensagem para o Ao Vivo aqui. */
}


/* PROCESSAR INTERAÇÃO */
if (isset($_POST['acao']) && strpos($fase, 'interacoes') !== false && $_SESSION['acoes_restantes'] > 0) {

    $acao = $_POST['acao'];
    $alvo = $_POST['alvo'] ?? '';
    $alvo2 = $_POST['alvo2'] ?? '';
    $evento = "";

    if ($acao == "conversar" && $alvo) {

        $_SESSION['relacoes_jogador'][$alvo] =
            ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 8;

        alterarAfinidade($jogadores, $meuNome, $alvo, 8, -2, 3);
        alterarAfinidade($jogadores, $alvo, $meuNome, 8, -2, 3);

        $evento = "💬 $meuNome conversou com $alvo e ganhou afinidade.";
    }

    if ($acao == "fofoca" && $alvo) {
        if (rand(1, 100) <= 60) {
            foreach ($jogadores as $j) {
                if ($j['nome'] != $meuNome && $j['nome'] != $alvo) {
                    alterarAfinidade($jogadores, $j['nome'], $alvo, rand(-10, -3), rand(3, 8), rand(-8, -3));
                }
            }

            if(rand(1,100) <= 45){
                impactoPopularidadePorPersonalidade($jogadores, $meuNome, "fofoca", true);
            }else{
                alterarPopularidadePublica($jogadores, $meuNome, 1, 4, "movimentou o jogo com uma fofoca que o público comprou", true);
            }

            $evento = "🗣️ $meuNome espalhou uma fofoca sobre $alvo, e parte da casa acreditou.";
        } else {
            alterarPopularidadePublica($jogadores, $meuNome, -8, -3, "a casa não acreditou na fofoca", true);
            $evento = "🗣️ $meuNome tentou fazer fofoca sobre $alvo, mas a casa não acreditou.";
        }
    }

    if ($acao == "intriga" && $alvo && $alvo2 && $alvo != $alvo2) {
        if (rand(1, 100) <= 55) {
            alterarAfinidade($jogadores, $alvo, $alvo2, rand(-15, -5), rand(5, 15), rand(-12, -5));
            alterarAfinidade($jogadores, $alvo2, $alvo, rand(-15, -5), rand(5, 15), rand(-12, -5));
            alterarPopularidadePublica($jogadores, $meuNome, -5, 5, "criou intriga e dividiu a opinião do público", true);
            $evento = "🔥 $meuNome criou intriga entre $alvo e $alvo2.";
        } else {
            alterarAfinidade($jogadores, $alvo, $meuNome, rand(-10, -5), rand(5, 10), rand(-10, -5));
            alterarAfinidade($jogadores, $alvo2, $meuNome, rand(-10, -5), rand(5, 10), rand(-10, -5));
            alterarPopularidadePublica($jogadores, $meuNome, -9, -4, "tentou criar intriga e foi desmascarado", true);
            $evento = "🔥 $meuNome tentou criar intriga, mas $alvo e $alvo2 desconfiaram.";
        }
    }

    if ($acao == "alianca" && $alvo) {
        if(($rodada ?? 1) < 2){
            $evento = "⏳ As alianças só começam oficialmente a partir da Rodada 2.";
        }
        elseif (rand(1, 100) <= 65) {

            $_SESSION['relacoes_jogador'][$alvo] =
                ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 10;

            alterarAfinidade($jogadores, $meuNome, $alvo, 10, -5, 10);
            alterarAfinidade($jogadores, $alvo, $meuNome, 10, -5, 10);

            $eventoAlianca = criarAliancaEntre($jogadores, $meuNome, $alvo);
            impactoPopularidadePorPersonalidade($jogadores, $meuNome, "alianca", true);
            $evento = ($eventoAlianca != '') ? $eventoAlianca : "🤝 $meuNome reforçou uma aliança secreta com $alvo.";
        } else {

            $_SESSION['relacoes_jogador'][$alvo] =
                ($_SESSION['relacoes_jogador'][$alvo] ?? 0) - 2;

            alterarAfinidade($jogadores, $alvo, $meuNome, -3, 2, -5);
            alterarPopularidadePublica($jogadores, $meuNome, -3, 0, "tentou forçar uma aliança e pegou mal", true);
            $evento = "🤝 $meuNome tentou criar aliança com $alvo, mas foi recusado.";
        }
    }

    if ($acao == "entrar_alianca") {
        $aliancaEscolhida = $_POST['alianca_escolhida'] ?? '';
        $evento = jogadorEntrarEmAlianca($jogadores, $meuNome, $aliancaEscolhida);
    }

    if ($acao == "sair_alianca") {
        $evento = jogadorSairDaAlianca($jogadores, $meuNome);
    }

    if ($acao == "aproximar_lider") {
        $lider = $_SESSION['lider'] ?? '';

        if ($lider && $lider != $meuNome) {

            ajustarRelacaoJogador($lider, 5);

            alterarAfinidade($jogadores, $meuNome, $lider, 5, -3, 6);
            alterarAfinidade($jogadores, $lider, $meuNome, 5, -3, 6);

            $evento = "👑 $meuNome tentou se aproximar do líder $lider e ganhou afinidade.";
        } else {
            $evento = "👑 Não havia líder disponível para se aproximar.";
        }
    }

    if ($acao == "cozinhar") {
        foreach ($jogadores as $j) {
            if ($j['nome'] != $meuNome) {
                alterarAfinidade($jogadores, $j['nome'], $meuNome, rand(2, 6), -1, rand(1, 4));
            }
        }
        $evento = "🍳 $meuNome cozinhou para a casa e agradou alguns participantes.";
    }

    if ($acao == "discutir" && $alvo) {
        $_SESSION['relacoes_jogador'][$alvo] =
            ($_SESSION['relacoes_jogador'][$alvo] ?? 0) - 10;
        ajustarPopularidadePorAlvo(
            $jogadores,
            $meuNome,
            $alvo,
            "comprou uma treta com alguém cancelado",
            "brigou com alguém querido pelo público"
        );
        $evento = "😡 $meuNome discutiu com $alvo e o clima pesou.";
    }

    if ($acao == "vt") {
        $mudanca = impactoPopularidadePorPersonalidade($jogadores, $meuNome, "vt", true);
        $evento = ($mudanca >= 0)
            ? "📺 $meuNome fez VT e ganhou popularidade."
            : "📺 $meuNome tentou fazer VT, mas o público achou forçado.";
    }

    if ($acao == "quieto") {
        impactoPopularidadePorPersonalidade($jogadores, $meuNome, "planta", true);
        $evento = "😶 $meuNome ficou na sua e evitou conflitos.";
    }

    if ($evento != "") {
        $_SESSION['evento_extra'][] = $evento;
        $_SESSION['jogadores'] = $jogadores;
        $_SESSION['acoes_restantes']--;

        if ($_SESSION['acoes_restantes'] <= 0 && !isset($_SESSION['npc_interacoes_feitas_' . $fase])) {
            $eventosNPC = gerarAcoesNPC($jogadores, $meuNome, 3);

            foreach ($eventosNPC as $ev) {
                $_SESSION['evento_extra'][] = $ev;
            }

            $eventosAliancas = atualizarAliancasAutomaticas($jogadores, $meuNome);
            foreach ($eventosAliancas as $ev) {
                $_SESSION['evento_extra'][] = $ev;
            }

            $_SESSION['jogadores'] = $jogadores;
            $_SESSION['npc_interacoes_feitas_' . $fase] = true;
        }

        unset($_SESSION['acao_selecionada']);
    }

    header("Location: jogo.php");
    exit;
}

/* =========================
   🎉 PROCESSAR FESTA
========================= */
if (isset($_POST['acao_festa']) && $fase == 'festa') {

    $acao = $_POST['acao_festa'];
    $alvo = $_POST['alvo_festa'] ?? '';

    $evento = "";

    /* 🤝 Aproximar */
    if ($acao == 'aproximar' && $alvo) {

        $_SESSION['relacoes_jogador'][$alvo] =
            ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 5;

        alterarAfinidade($jogadores, $meuNome, $alvo, 5, -2, 3);
        alterarAfinidade($jogadores, $alvo, $meuNome, 5, -2, 3);

        $evento =
            "🤝 $meuNome se aproximou de $alvo durante a festa.";
    }

    /* 📺 VT */
    if ($acao == 'vt') {

        $valor = impactoPopularidadePorPersonalidade($jogadores, $meuNome, "vt", true);

        if ($valor >= 0) {
            $evento =
                "📺 $meuNome roubou as câmeras e viralizou na festa.";
        } else {
            $evento =
                "📺 $meuNome tentou fazer VT, mas o público achou vergonha alheia.";
        }
    }

    /* 😘 FLERTE */
    if ($acao == 'flertar' && $alvo) {

        $afinidade = $_SESSION['relacoes_jogador'][$alvo] ?? 0;

        if ($afinidade >= 15) {

            if (rand(1, 100) <= 70) {

                $_SESSION['relacoes_jogador'][$alvo] =
                    ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 8;

                alterarRomance($jogadores, $meuNome, $alvo, 10);
                alterarRomance($jogadores, $alvo, $meuNome, 8);
                aplicarCiumesSeTiverCasal($jogadores, $meuNome, $alvo, $_SESSION['evento_extra']);

                alterarAfinidade($jogadores, $meuNome, $alvo, 8, -2, 5);
                alterarAfinidade($jogadores, $alvo, $meuNome, 5, -2, 3);

                impactoPopularidadePorPersonalidade($jogadores, $meuNome, "romance", true);

                $evento =
                    "😘 $alvo correspondeu ao flerte de $meuNome durante a festa.";
            } else {

                $_SESSION['relacoes_jogador'][$alvo] =
                    ($_SESSION['relacoes_jogador'][$alvo] ?? 0) - 5;

                alterarAfinidade($jogadores, $meuNome, $alvo, -5, 3, -3);
                alterarAfinidade($jogadores, $alvo, $meuNome, -5, 3, -3);

                alterarPopularidadePublica($jogadores, $meuNome, -4, 0, "levou um fora na festa", true);

                $evento =
                    "💔 $alvo rejeitou o flerte de $meuNome e o climão tomou conta.";
            }
        } else {

            $_SESSION['relacoes_jogador'][$alvo] =
                ($_SESSION['relacoes_jogador'][$alvo] ?? 0) - 3;

            alterarAfinidade($jogadores, $meuNome, $alvo, -3, 2, -2);

            $evento =
                "💔 $meuNome tentou flertar com $alvo, mas não havia conexão suficiente.";
        }
    }

    /* 😈 PROVOCAR */
    if ($acao == 'provocar' && $alvo) {

        $_SESSION['relacoes_jogador'][$alvo] =
            ($_SESSION['relacoes_jogador'][$alvo] ?? 0) - 8;

        alterarAfinidade($jogadores, $meuNome, $alvo, -8, 8, -5);
        alterarAfinidade($jogadores, $alvo, $meuNome, -8, 8, -5);

        $popularidadeAlvo = 50;

        foreach ($jogadores as $j) {

            if ($j['nome'] == $alvo) {
                $popularidadeAlvo = $j['popularidade'] ?? 50;
            }
        }

        if ($popularidadeAlvo < 40) {

            alterarPopularidadeMotivo($jogadores, $meuNome, 3, 8, "provocou alguém cancelado pelo público");
        } else {

            alterarPopularidadeMotivo($jogadores, $meuNome, -8, -3, "provocou alguém querido pelo público");
        }

        $evento =
            "😈 $meuNome provocou $alvo durante a festa e o clima pesou.";
    }

    /* 💃 DANÇAR */
    if ($acao == 'dancar' && $alvo) {

        $_SESSION['relacoes_jogador'][$alvo] =
            ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 6;

        alterarAfinidade($jogadores, $meuNome, $alvo, rand(3, 7), -2, 3);
        alterarAfinidade($jogadores, $alvo, $meuNome, rand(3, 7), -2, 3);

        alterarRomance($jogadores, $meuNome, $alvo, 5);
        alterarRomance($jogadores, $alvo, $meuNome, 3);

        $evento =
            "💃 $meuNome dançou juntinho com $alvo na festa e o clima ficou mais próximo.";
    }

    /* 💕 ELOGIAR */
    if ($acao == 'elogiar' && $alvo) {

        $_SESSION['relacoes_jogador'][$alvo] =
            ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 4;

        alterarRomance($jogadores, $meuNome, $alvo, 6);
        alterarRomance($jogadores, $alvo, $meuNome, 4);
        alterarAfinidade($jogadores, $meuNome, $alvo, 4, -2, 4);
        alterarAfinidade($jogadores, $alvo, $meuNome, 3, -1, 3);

        $evento = "💕 $meuNome elogiou $alvo e o romance aumentou.";
    }

    /* 💬 CONVERSAR SOBRE SENTIMENTOS */
    if ($acao == 'sentimentos' && $alvo) {

        $romanceAtual = obterRomance($jogadores, $meuNome, $alvo);

        if ($romanceAtual >= 30) {
            $_SESSION['relacoes_jogador'][$alvo] =
                ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 6;

            alterarRomance($jogadores, $meuNome, $alvo, 10);
            alterarRomance($jogadores, $alvo, $meuNome, 8);
            alterarAfinidade($jogadores, $meuNome, $alvo, 6, -3, 8);
            alterarAfinidade($jogadores, $alvo, $meuNome, 5, -2, 6);

            $evento = "💬 $meuNome e $alvo conversaram sobre sentimentos e ficaram ainda mais próximos.";
        } else {
            alterarRomance($jogadores, $meuNome, $alvo, 2);
            $evento = "💬 $meuNome tentou conversar sobre sentimentos com $alvo, mas ainda faltava clima.";
        }
    }

    /* 🌙 PASSAR A NOITE CONVERSANDO */
    if ($acao == 'noite_conversando' && $alvo) {

        $romanceAtual = obterRomance($jogadores, $meuNome, $alvo);

        if ($romanceAtual >= 30) {
            $_SESSION['relacoes_jogador'][$alvo] =
                ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 5;

            alterarRomance($jogadores, $meuNome, $alvo, 12);
            alterarRomance($jogadores, $alvo, $meuNome, 10);
            alterarAfinidade($jogadores, $meuNome, $alvo, 5, -2, 7);
            alterarAfinidade($jogadores, $alvo, $meuNome, 5, -2, 7);

            $evento = "🌙 $meuNome e $alvo passaram a noite conversando baixinho e o romance cresceu.";
        } else {
            $evento = "🌙 $meuNome tentou passar mais tempo com $alvo, mas ainda não tinha intimidade suficiente.";
        }
    }

    /* 💍 PEDIR EM NAMORO */
    if ($acao == 'pedir_namoro' && $alvo) {

        $romanceAtual = obterRomance($jogadores, $meuNome, $alvo);
        $afinidadeAtual = $_SESSION['relacoes_jogador'][$alvo] ?? 0;

        if (parceiroAtual($meuNome) != '') {
            $evento = "💍 $meuNome já está namorando com " . parceiroAtual($meuNome) . ".";
        } elseif (parceiroAtual($alvo) != '') {
            $evento = "💍 $alvo já está em um casal com " . parceiroAtual($alvo) . ".";
        } elseif ($romanceAtual < 60) {
            $evento = "💍 $meuNome pensou em pedir $alvo em namoro, mas o romance ainda precisa chegar em 60.";
        } else {
            $chance = chanceAceitarNamoro($afinidadeAtual, $romanceAtual);

            if (rand(1, 100) <= $chance) {
                registrarCasal($meuNome, $alvo);
                impactoTorcidaOculto($jogadores, $meuNome, 'casal');
                impactoTorcidaOculto($jogadores, $alvo, 'casal');
                alterarRomance($jogadores, $meuNome, $alvo, 10);
                alterarRomance($jogadores, $alvo, $meuNome, 10);
                alterarAfinidade($jogadores, $meuNome, $alvo, 8, -4, 10);
                alterarAfinidade($jogadores, $alvo, $meuNome, 8, -4, 10);
                ajustarRelacaoJogador($alvo, 8);

                $evento = "💍 $meuNome pediu $alvo em namoro... e $alvo aceitou! Nasce um casal na casa.";
            } else {
                alterarRomance($jogadores, $meuNome, $alvo, -6);
                alterarRomance($jogadores, $alvo, $meuNome, -4);
                ajustarRelacaoJogador($alvo, -4);

                $evento = "💔 $meuNome pediu $alvo em namoro, mas $alvo preferiu ir com calma.";
            }
        }
    }

    /* 🛏️ PASSAR A NOITE JUNTOS NO QUARTO */
    if ($acao == 'passar_noite_quarto' && $alvo) {

        $romanceAtual = obterRomance($jogadores, $meuNome, $alvo);

        if ($romanceAtual >= 60) {
            $_SESSION['relacoes_jogador'][$alvo] =
                ($_SESSION['relacoes_jogador'][$alvo] ?? 0) + 7;

            alterarRomance($jogadores, $meuNome, $alvo, 14);
            alterarRomance($jogadores, $alvo, $meuNome, 12);
            alterarAfinidade($jogadores, $meuNome, $alvo, 7, -3, 8);
            alterarAfinidade($jogadores, $alvo, $meuNome, 7, -3, 8);
            alterarPopularidadePublica($jogadores, $meuNome, -5, 6, "viveu um momento íntimo de romance no quarto", true);

            $evento = "🛏️ $meuNome e $alvo passaram a noite juntos no quarto, conversando e fortalecendo o romance.";
        } else {
            $evento = "🛏️ $meuNome tentou passar a noite junto com $alvo, mas o romance ainda precisa chegar em 60.";
        }
    }

    /* 🍹 BEBIDA */
    if ($acao == 'beber') {

        $valor = alterarPopularidadePublica($jogadores, $meuNome, -12, 8, "exagerou na bebida durante a festa", true);

        if ($valor >= 0) {

            $evento =
                "🍹 $meuNome exagerou na bebida e virou assunto na internet.";
        } else {

            $evento =
                "🍹 $meuNome bebeu demais e acabou pagando mico na festa.";
        }
    }

    $_SESSION['evento_extra'][] = $evento;
    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['acoes_festa']--;

    unset($_SESSION['acao_festa_selecionada']);

    if ($_SESSION['acoes_festa'] <= 0) {

        if (!isset($_SESSION['npc_festa_feita'])) {
            $eventosNPC = gerarAcoesFestaNPC($jogadores, $meuNome, 2);

            foreach ($eventosNPC as $ev) {
                $_SESSION['evento_extra'][] = $ev;
            }

            $eventosAliancas = atualizarAliancasAutomaticas($jogadores, $meuNome);
            foreach ($eventosAliancas as $ev) {
                $_SESSION['evento_extra'][] = $ev;
            }

            $_SESSION['npc_festa_feita'] = true;
            $_SESSION['jogadores'] = $jogadores;
        }

        $_SESSION['fase_semana'] = 'imunizacao_anjo';
        $_SESSION['acoes_festa'] = 2;
    }

    header("Location: jogo.php");
    exit;
}

/* =========================
   😇 IMUNIZAÇÃO DO ANJO
========================= */

if (isset($_POST['definir_imunidade_anjo'])) {

    $imunizado = $_POST['imunizado_anjo'] ?? '';

    foreach ($jogadores as &$j) {
        $j['status']['imune'] = false;

        if ($j['nome'] == $imunizado) {
            $j['status']['imune'] = true;

            if (!isset($j['estatisticas'])) {
                $j['estatisticas'] = [];
            }

            $j['estatisticas']['imune'] =
                ($j['estatisticas']['imune'] ?? 0) + 1;
        }
    }
    unset($j);

    garantirMeuJogadorNaLista($jogadores);
    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['imune'] = $imunizado;
    $_SESSION['imunizacao_anjo_feita'] = true;

    $_SESSION['evento_extra'][] =
        "🛡️ O Anjo " . $_SESSION['anjo'] . " imunizou $imunizado antes da formação do paredão.";

    $_SESSION['fase_semana'] = 'paredao';

    header("Location: jogo.php");
    exit;
}

if ($fase == 'imunizacao_anjo' && !isset($_SESSION['imunizacao_anjo_feita'])) {

    $anjo = $_SESSION['anjo'] ?? '';

    if ($anjo != $meuNome) {

        $afinidades = [];

        foreach ($jogadores as $j) {
            if ($j['nome'] != $anjo && empty($j['status']['lider'])) {
                $afinidade = calcularRelacaoIA($jogadores, $anjo, $j['nome'], $meuNome);
                $afinidades[$j['nome']] = $afinidade;
            }
        }

        arsort($afinidades);

        $imunizado = array_key_first($afinidades);

        foreach ($jogadores as &$j) {
            $j['status']['imune'] = false;

            if ($j['nome'] == $imunizado) {
                if ($j['nome'] == $imunizado) {
                    $j['status']['imune'] = true;

                    if (!isset($j['estatisticas'])) {
                        $j['estatisticas'] = [];
                    }

                    $j['estatisticas']['imune'] =
                        ($j['estatisticas']['imune'] ?? 0) + 1;
                }
                $j['status']['imune'] = true;
            }
        }
        unset($j);

        garantirMeuJogadorNaLista($jogadores);
        $_SESSION['jogadores'] = $jogadores;
        $_SESSION['imune'] = $imunizado;
        $_SESSION['imunizacao_anjo_feita'] = true;

        $_SESSION['evento_extra'][] =
            "🛡️ O Anjo $anjo imunizou $imunizado antes da formação do paredão.";

        $_SESSION['fase_semana'] = 'paredao';

        header("Location: jogo.php");
        exit;
    }
}

/* =========================
   🚨 PAREDÃO V1 OFICIAL
========================= */

function escolherAlvoInteligente($jogadores, $votante, $bloqueados = [])
{

    $opcoes = [];
    $meuNome = $_SESSION['meu_nome'] ?? '';

    foreach ($jogadores as $j) {

        $alvo = $j['nome'] ?? '';

        if ($alvo == '' || $alvo == $votante) continue;
        if (in_array($alvo, $bloqueados)) continue;
        if (!empty($j['status']['lider'])) continue;
        if (!empty($j['status']['imune'])) continue;

        $relacao = calcularRelacaoIA($jogadores, $votante, $alvo, $meuNome);
        $popularidade = $j['popularidade'] ?? 50;

        /* Quanto pior a relação, maior a chance de voto/indicação */
        $peso = 50 - $relacao;

        /* Jogador muito popular assusta um pouco, mas odiado vira alvo fácil */
        $peso += (50 - $popularidade) * 0.4;

        if (saoRivais($jogadores, $votante, $alvo, $meuNome)) {
            $peso += 35;
        }

        if (saoAliados($jogadores, $votante, $alvo, $meuNome)) {
            $peso -= 45;
        }

        if (mesmaAliancaNomes($jogadores, $votante, $alvo)) {
            $peso -= 80;
        }

        $aliancaVotante = null;
        foreach($jogadores as $jBuscaAlianca){
            if(($jBuscaAlianca['nome'] ?? '') == $votante){
                $aliancaVotante = $jBuscaAlianca['alianca'] ?? null;
                break;
            }
        }

        if(!empty($aliancaVotante)){
            $bloqueadosGrupo = array_values(array_unique(array_merge($bloqueados, [$votante])));
            $alvoDoGrupo = alvoCombinadoDaAlianca($jogadores, $aliancaVotante, $bloqueadosGrupo);
            if($alvoDoGrupo == $alvo){
                $peso += 45;
            }
        }

        if (!empty($j['status']['monstro'])) {
            $peso += 15;
        }

        if (!empty($j['status']['xepa'])) {
            $peso += 5;
        }

        $opcoes[$alvo] = $peso + rand(0, 10);
    }

    arsort($opcoes);

    if (empty($opcoes)) {
        return null;
    }

    return array_key_first($opcoes);
}


/* INDICAÇÃO DO LÍDER */
if (isset($_POST['indicar_lider'])) {

    $_SESSION['indicacao_lider'] = $_POST['indicado_lider'];

    $_SESSION['evento_extra'][] =
        "👑 O líder " . $_SESSION['lider'] . " indicou " . $_SESSION['indicacao_lider'] . " ao paredão.";

    header("Location: jogo.php");
    exit;
}

/* INDICAÇÃO DO BIG FONE */
if (isset($_POST['indicar_bigfone'])) {

    $indicadoBigfone = $_POST['indicado_bigfone'] ?? '';

    if (
        $indicadoBigfone == ($_SESSION['lider'] ?? '') ||
        $indicadoBigfone == ($_SESSION['indicacao_lider'] ?? '') ||
        $indicadoBigfone == $meuNome
    ) {
        $_SESSION['evento_extra'][] = "⚠️ Indicação inválida do Big Fone.";
        header("Location: jogo.php");
        exit;
    }

    $_SESSION['indicacao_bigfone'] = $indicadoBigfone;
    unset($_SESSION['bigfone_indicacao_pendente']);

    $_SESSION['evento_extra'][] =
        "☎️ Pelo poder do Big Fone, " . $_SESSION['bigfone_dono_poder'] . " indicou " . $_SESSION['indicacao_bigfone'] . " ao paredão.";

    header("Location: jogo.php");
    exit;
}

/* VOTO DO JOGADOR NO PAREDÃO */
if (isset($_POST['votar_paredao'])) {

    $votoParedao = $_POST['voto_paredao'] ?? '';

    if (
        $votoParedao == ($_SESSION['indicacao_lider'] ?? '') ||
        $votoParedao == ($_SESSION['indicacao_bigfone'] ?? '') ||
        $votoParedao == ($_SESSION['lider'] ?? '')
    ) {
        $_SESSION['evento_extra'][] = "⚠️ Voto inválido. Escolha outro participante.";
        header("Location: jogo.php");
        exit;
    }

    $_SESSION['meu_voto_paredao'] = $votoParedao;

    $_SESSION['evento_extra'][] =
        "🗳️ $meuNome votou no confessionário.";

    header("Location: jogo.php");
    exit;
}

/* PROCESSAR PAREDÃO */
if ($fase == 'paredao' && !isset($_SESSION['paredao_formado'])) {

    $lider = $_SESSION['lider'] ?? '';

    /* TOP 4: líder está salvo e os outros 3 vão direto ao paredão */
    if (count($jogadores) == 4 && $lider != '') {

        $paredaoTop4 = [];

        foreach ($jogadores as $j) {
            if (($j['nome'] ?? '') != $lider) {
                $paredaoTop4[] = $j['nome'];
            }
        }

        $_SESSION['paredao'] = array_values($paredaoTop4);
        $_SESSION['paredao_formado'] = true;

        unset($_SESSION['indicacao_lider']);
        unset($_SESSION['indicacao_bigfone']);
        unset($_SESSION['meu_voto_paredao']);
        unset($_SESSION['votos_paredao']);
        unset($_SESSION['dedo_duro']);

        $_SESSION['evento_extra'][] = "🏆 Reta final! O líder $lider está salvo do paredão.";
        $_SESSION['evento_extra'][] = "🚨 Está formado o paredão final: " . implode(" x ", $_SESSION['paredao']) . ".";

        $_SESSION['fase_semana'] = 'eliminacao';

        header("Location: jogo.php");
        exit;
    }

    /* 1) PRIMEIRO: indicação do líder */
    if (!isset($_SESSION['indicacao_lider'])) {

        if ($lider == $meuNome) {
            // Se o jogador é líder, espera ele escolher no HTML.
            goto fim_paredao;
        }

        // Líder NPC indica automaticamente.
        $alvo = escolherAlvoInteligente($jogadores, $lider);

        if ($alvo) {
            $_SESSION['indicacao_lider'] = $alvo;
            $_SESSION['evento_extra'][] =
                "👑 O líder $lider indicou $alvo ao paredão.";
        } else {
            goto fim_paredao;
        }
    }

    /* 2) DEPOIS: poder do Big Fone, se existir */
    if (
        isset($_SESSION['bigfone_indicacao_pendente']) &&
        isset($_SESSION['bigfone_dono_poder']) &&
        !isset($_SESSION['indicacao_bigfone'])
    ) {

        $dono = $_SESSION['bigfone_dono_poder'];

        if ($dono == $meuNome) {
            // Se o poder é do jogador, espera ele escolher no HTML.
            goto fim_paredao;
        }

        $bloqueados = [];
        $bloqueados[] = $_SESSION['indicacao_lider'];
        $bloqueados[] = $lider;

        $alvo = escolherAlvoInteligente($jogadores, $dono, $bloqueados);

        if ($alvo) {
            $_SESSION['indicacao_bigfone'] = $alvo;
            unset($_SESSION['bigfone_indicacao_pendente']);

            $_SESSION['evento_extra'][] =
                "☎️ Pelo poder do Big Fone, $dono indicou $alvo ao paredão.";
        } else {
            unset($_SESSION['bigfone_indicacao_pendente']);
        }
    }

    /* 3) SÓ AGORA: votação da casa */
    if (
        $lider != $meuNome &&
        !isset($_SESSION['meu_voto_paredao'])
    ) {
        // Se o jogador não é líder, espera o voto dele no confessionário.
        goto fim_paredao;
    }

    /* Votação da casa */
    $votos = [];
    $votosDetalhados = [];

    foreach ($jogadores as $j) {

        $votante = $j['nome'] ?? '';

        if (empty($votante)) continue;
        if (!empty($j['status']['lider'])) continue;

        $bloqueados = [$votante];

        if (isset($_SESSION['indicacao_lider'])) {
            $bloqueados[] = $_SESSION['indicacao_lider'];
        }

        if (isset($_SESSION['indicacao_bigfone'])) {
            $bloqueados[] = $_SESSION['indicacao_bigfone'];
        }

        if ($votante == $meuNome && isset($_SESSION['meu_voto_paredao'])) {

            $voto = $_SESSION['meu_voto_paredao'];

            if (
                $voto == ($_SESSION['indicacao_lider'] ?? '') ||
                $voto == ($_SESSION['indicacao_bigfone'] ?? '') ||
                $voto == $lider
            ) {
                continue;
            }

            if (!isset($votos[$voto])) {
                $votos[$voto] = 0;
            }

            $votos[$voto]++;

            $votosDetalhados[] = [
                "votante" => $votante,
                "voto" => $voto
            ];

            continue;
        }

        $voto = escolherAlvoInteligente($jogadores, $votante, $bloqueados);

        if ($voto) {
            if (!isset($votos[$voto])) {
                $votos[$voto] = 0;
            }

            $votos[$voto]++;
            $votosDetalhados[] = [
                "votante" => $votante,
                "voto" => $voto
            ];
        }
    }

    arsort($votos);

    $_SESSION['votos_paredao'] = $votos;

    if (!empty($votosDetalhados)) {
        $_SESSION['dedo_duro'] = $votosDetalhados[array_rand($votosDetalhados)];
    }

    $maisVotados = array_keys($votos);
    $paredao = [];

    if (isset($_SESSION['indicacao_lider'])) {
        $paredao[] = $_SESSION['indicacao_lider'];
    }

    if (isset($_SESSION['indicacao_bigfone'])) {
        $paredao[] = $_SESSION['indicacao_bigfone'];
    }

    foreach ($maisVotados as $nome) {
        if (!in_array($nome, $paredao)) {
            $paredao[] = $nome;
        }

        $limiteParedao = (count($jogadores) <= 6) ? 2 : 3;

        if (count($paredao) >= $limiteParedao) {
            break;
        }
    }

    $limiteParedao = (count($jogadores) <= 6) ? 2 : 3;
    $_SESSION['paredao'] = array_slice($paredao, 0, $limiteParedao);
    $_SESSION['paredao_formado'] = true;

    $_SESSION['evento_extra'][] = "🗳️ Resultado da Votação da Casa:";

    foreach ($votos as $nome => $qtd) {
        $_SESSION['evento_extra'][] = "📊 $nome recebeu $qtd voto(s).";
    }

    if (isset($_SESSION['dedo_duro'])) {
        $_SESSION['evento_extra'][] =
            "🕵️ Dedo-duro: " . $_SESSION['dedo_duro']['votante'] . " votou em " . $_SESSION['dedo_duro']['voto'] . ".";
    }

    $_SESSION['evento_extra'][] =
        "🚨 Está formado o paredão: " . implode(" x ", $_SESSION['paredao']) . ".";

    $_SESSION['fase_semana'] = 'discordia';

    header("Location: jogo.php");
    exit;
}

fim_paredao:

/* AVANÇAR FASE */
if (isset($_POST['avancar_fase'])) {

    $fase = $_SESSION['fase_semana'];

    if ($fase == 'queridometro') {

        /* Confessionário começa somente a partir da Rodada 2, logo depois do Queridômetro */
        if (($_SESSION['rodada'] ?? 1) >= 2) {
            prepararConfessionarioDaRodada($jogadores, $meuNome);
            $_SESSION['fase_semana'] = 'confessionario';
        } else {
            $_SESSION['fase_semana'] = 'interacoes_1';
            $_SESSION['acoes_restantes'] = 3;
        }

        header("Location: jogo.php");
        exit;
    }

    if (strpos($fase, 'interacoes') !== false) {

        if (!isset($_SESSION['acoes_restantes']) || $_SESSION['acoes_restantes'] > 0) {
            npcExecutarInteracoesDaFase($jogadores, $meuNome, $fase, 3);
        }

        unset($_SESSION['acoes_restantes']);

        if ($fase == 'interacoes_1') {

            /* NOVA SEMANA: limpar líder e poderes antigos antes da Prova do Líder */
            unset($_SESSION['lider']);
            unset($_SESSION['anjo']);
            unset($_SESSION['imune']);
            unset($_SESSION['monstro']);
            unset($_SESSION['vip_definido']);
            unset($_SESSION['monstro_definido']);
            unset($_SESSION['prova_anjo_finalizada']);
            unset($_SESSION['imunizacao_anjo_feita']);
            unset($_SESSION['paredao']);
            unset($_SESSION['paredao_formado']);
            unset($_SESSION['votos_paredao']);
            unset($_SESSION['dedo_duro']);
            unset($_SESSION['indicacao_lider']);
            unset($_SESSION['indicacao_bigfone']);
            unset($_SESSION['meu_voto_paredao']);
            unset($_SESSION['bigfone_feito']);
            unset($_SESSION['bigfone_indicacao_pendente']);
            unset($_SESSION['bigfone_dono_poder']);
            unset($_SESSION['npc_festa_feita']);
            unset($_SESSION['alvos_aliancas_semana']);
            unset($_SESSION['confessionario_feito']);
            unset($_SESSION['confessionario_falas']);
            unset($_SESSION['npc_interacoes_feitas_interacoes_1']);
            unset($_SESSION['npc_interacoes_feitas_interacoes_2']);
            unset($_SESSION['npc_interacoes_feitas_interacoes_3']);
            unset($_SESSION['queridometro_resultado']);
            unset($_SESSION['queridometro_feito']);

            foreach ($_SESSION['jogadores'] as &$j) {
                $j['status']['lider'] = false;
                $j['status']['anjo'] = false;
                $j['status']['imune'] = false;
                $j['status']['monstro'] = false;
                $j['status']['vip'] = false;
                $j['status']['xepa'] = false;
            }
            unset($j);

            $_SESSION['fase_semana'] = 'lider';
            header("Location: prova_lider.php");
            exit;
        }

        if ($fase == 'interacoes_2') {
            $_SESSION['fase_semana'] = 'festa';
            header("Location: jogo.php");
            exit;
        }

        if ($fase == 'interacoes_3') {
            aplicarDesgasteSemanalPublico($jogadores);
            $_SESSION['jogadores'] = $jogadores;
            $_SESSION['fase_semana'] = 'eliminacao';
            header("Location: resultado.php");
            exit;
        }
    }

    if ($fase == 'lider') {
        header("Location: prova_lider.php");
        exit;
    }

    if ($fase == 'vip_xepa') {

        unset($_SESSION['anjo']);
        unset($_SESSION['imune']);
        unset($_SESSION['monstro']);
        unset($_SESSION['monstro_definido']);
        unset($_SESSION['prova_anjo_finalizada']);

        foreach ($_SESSION['jogadores'] as &$j) {
            $j['status']['anjo'] = false;
            $j['status']['imune'] = false;
            $j['status']['monstro'] = false;
        }

        $_SESSION['fase_semana'] = 'anjo';

        header("Location: jogo.php");
        exit;
    }

    if ($fase == 'anjo') {
        header("Location: prova_anjo.php");
        exit;
    }

    if ($fase == 'monstro') {
        header("Location: jogo.php");
        exit;
    }

    if ($fase == 'bigfone') {
        header("Location: big_fone.php");
        exit;
    }

    if ($fase == 'festa') {
        $_SESSION['evento_extra'][] = "🎉 A festa movimentou a casa com conversas, olhares, alianças e tensão.";
        $_SESSION['fase_semana'] = 'imunizacao_anjo';
        header("Location: jogo.php");
        exit;
    }

    if ($fase == 'confessionario') {
        unset($_SESSION['confessionario_falas']);
        unset($_SESSION['confessionario_feito']);
        $_SESSION['fase_semana'] = 'interacoes_1';
        $_SESSION['acoes_restantes'] = 3;
        header("Location: jogo.php");
        exit;
    }

    if ($fase == 'paredao') {
        header("Location: jogo.php");
        exit;
    }
}

if (
    $fase == 'discordia' &&
    isset($_SESSION['discordia_feito'])
) {

    $_SESSION['evento_extra'][] =
        "🔥 O Jogo da Discórdia incendiou a casa.";

    $_SESSION['fase_semana'] = 'interacoes_3';

    $_SESSION['acoes_restantes'] = 3;

    unset($_SESSION['discordia_feito']);

    header("Location: jogo.php");
    exit;
}

if ($fase == 'eliminacao') {

    unset($_SESSION['vip_definido']);
    unset($_SESSION['monstro_definido']);

    unset($_SESSION['paredao_formado']);
    unset($_SESSION['votos_paredao']);
    unset($_SESSION['dedo_duro']);

    unset($_SESSION['indicacao_lider']);
    unset($_SESSION['indicacao_bigfone']);

    unset($_SESSION['bigfone_indicacao_pendente']);
    unset($_SESSION['bigfone_dono_poder']);

    unset($_SESSION['imunizacao_anjo_feita']);

    header("Location: resultado.php");
    exit;
}

/* =========================
   🔥 JOGO DA DISCÓRDIA
========================= */

if ($fase == 'discordia' && !isset($_SESSION['tema_discordia'])) {

    $_SESSION['tema_discordia'] =
        $temasDiscordia[array_rand($temasDiscordia)];
}

function gerarDiscordiaNPC(&$jogadores, $meuNome, $tema)
{

    $eventos = [];

    atualizarRelacoesMarcantes($jogadores);

    foreach ($jogadores as $npc) {

        $nomeNPC = $npc['nome'] ?? '';
        $perfil = perfilPersonalidadeCompleto($npc['personalidade'] ?? 'Neutro');

        if ($nomeNPC == '' || $nomeNPC == $meuNome) continue;

        $alvos = [];

        foreach ($jogadores as $j) {
            if (($j['nome'] ?? '') != $nomeNPC) {
                $alvos[] = $j['nome'];
            }
        }

        if (empty($alvos)) continue;

        if ($tema == "podio") {

            $segundo = escolherAlvoNPCPorRelacao($jogadores, $nomeNPC, $meuNome, 'aliado');
            $terceiro = null;

            if ($segundo != null) {
                $restantes = array_values(array_filter($alvos, function ($n) use ($segundo) {
                    return $n != $segundo;
                }));
            } else {
                $restantes = $alvos;
            }

            $aliadoExtra = escolherAlvoNPCPorRelacao($jogadores, $nomeNPC, $meuNome, 'aliado');

            if ($aliadoExtra != null && $aliadoExtra != $segundo) {
                $terceiro = $aliadoExtra;
            }

            if ($segundo == null) {
                shuffle($restantes);
                $segundo = $restantes[0] ?? '';
            }

            if ($terceiro == null) {
                $restantes = array_values(array_filter($alvos, function ($n) use ($segundo) {
                    return $n != $segundo;
                }));
                shuffle($restantes);
                $terceiro = $restantes[0] ?? '';
            }

            if ($segundo && $terceiro) {

                alterarAfinidade($jogadores, $nomeNPC, $segundo, 10, -4, 8);
                alterarAfinidade($jogadores, $segundo, $nomeNPC, 6, -2, 5);

                alterarAfinidade($jogadores, $nomeNPC, $terceiro, 6, -2, 5);
                alterarAfinidade($jogadores, $terceiro, $nomeNPC, 4, -1, 3);

                if ($segundo == $meuNome) {
                    ajustarRelacaoJogador($nomeNPC, 10);
                }

                if ($terceiro == $meuNome) {
                    ajustarRelacaoJogador($nomeNPC, 6);
                }

                $eventos[] =
                    "🏆 $nomeNPC montou seu pódio: 🥇 $nomeNPC, 🥈 $segundo e 🥉 $terceiro.";
            }

            continue;
        }

        if ($tema == "aliado") {
            $alvo = escolherAlvoNPCPorRelacao($jogadores, $nomeNPC, $meuNome, 'aliado');

            if ($alvo == null) {
                $alvo = $alvos[array_rand($alvos)];
            }

            alterarAfinidade($jogadores, $nomeNPC, $alvo, 12, -5, 10);
            alterarAfinidade($jogadores, $alvo, $nomeNPC, 8, -3, 6);

            if ($alvo == $meuNome) {
                ajustarRelacaoJogador($nomeNPC, 12);
                $eventos[] = "🤝 $nomeNPC declarou que $meuNome é seu maior aliado. Sua afinidade com $nomeNPC subiu.";
            } else {
                $eventos[] = "🤝 $nomeNPC declarou que $alvo é seu maior aliado.";
            }

            continue;
        }

        if ($tema == "sonso" || $tema == "falso" || $tema == "saboneteiro") {

            $alvo = escolherAlvoNPCPorRelacao($jogadores, $nomeNPC, $meuNome, 'rival');

            if ($alvo == null) {
                $alvo = $alvos[array_rand($alvos)];
            }

            if ($perfil['treta'] >= 80) {
                $forca = 2;
            } elseif ($perfil['treta'] <= 25) {
                $forca = 3;
            } else {
                $forca = rand(1, 3);
            }

            if (saoRivais($jogadores, $nomeNPC, $alvo, $meuNome)) {
                $forca = 2;
            }

            if ($forca == 1) {
                alterarAfinidade($jogadores, $nomeNPC, $alvo, -6, 5, -4);
                alterarAfinidade($jogadores, $alvo, $nomeNPC, -6, 5, -4);

                if ($alvo == $meuNome) {
                    ajustarRelacaoJogador($nomeNPC, -6);
                    $eventos[] = "😶 $nomeNPC disse que $meuNome é $tema de forma mais leve. Sua afinidade com $nomeNPC caiu.";
                } else {
                    $eventos[] = "😶 $nomeNPC disse que $alvo é $tema de forma mais leve.";
                }
            }

            if ($forca == 2) {
                alterarAfinidade($jogadores, $nomeNPC, $alvo, -12, 9, -8);
                alterarAfinidade($jogadores, $alvo, $nomeNPC, -12, 9, -8);

                if ($alvo == $meuNome) {
                    ajustarRelacaoJogador($nomeNPC, -12);
                    $eventos[] = "🔥 $nomeNPC chamou $meuNome de $tema no Jogo da Discórdia. Sua afinidade com $nomeNPC caiu bastante.";
                } else {
                    $eventos[] = "🔥 $nomeNPC chamou $alvo de $tema no Jogo da Discórdia.";
                }
            }

            if ($forca == 3) {
                alterarPopularidadeMotivo($jogadores, $nomeNPC, -3, -3, "saboneteou no Jogo da Discórdia", false);

                $eventos[] =
                    "🧼 $nomeNPC sabonetou e tentou fugir da pergunta.";
            }

            registrarRelacaoMarcante($jogadores, $nomeNPC, $alvo);
            registrarRelacaoMarcante($jogadores, $alvo, $nomeNPC);
        }
    }

    $_SESSION['jogadores'] = $jogadores;

    return $eventos;
}


/* =========================
   PROCESSAR QUERIDÔMETRO
========================= */

if (isset($_POST['enviar_queridometro']) || isset($_POST['auto_queridometro'])) {

    iniciarQueridometro();

    $dados = $_POST['queridometro'] ?? [];

    if (isset($_POST['auto_queridometro'])) {
        $dados = [];

        foreach ($jogadores as $jAuto) {
            $nomeAuto = $jAuto['nome'] ?? '';

            if ($nomeAuto == '' || nomeIgual($nomeAuto, $meuNome)) continue;

            $dados[$nomeAuto] = escolherEmojiQueridometroAutomatico($jogadores, $meuNome, $nomeAuto);
        }
    }

    foreach ($dados as $alvo => $emoji) {

        registrarEmojiQueridometro(
            $jogadores,
            $meuNome,
            $alvo,
            $emoji,
            $EMOJIS_QUERIDOMETRO
        );
    }

    /* NPCS TAMBÉM ENVIAM EMOJIS COM BASE NAS RELAÇÕES REAIS */
    foreach ($jogadores as $npc) {

        $nomeNPC = $npc['nome'] ?? '';

        if ($nomeNPC == '' || $nomeNPC == $meuNome) continue;

        foreach ($jogadores as $alvo) {

            $nomeAlvo = $alvo['nome'] ?? '';

            if ($nomeAlvo == '' || $nomeAlvo == $nomeNPC) continue;

            $relacao = $npc['relacoes'][$nomeAlvo] ?? [];
            $amizade = $relacao['amizade'] ?? 0;
            $rivalidade = $relacao['rivalidade'] ?? 0;
            $confianca = $relacao['confianca'] ?? 0;
            $romance = $npc['romances'][$nomeAlvo] ?? 0;
            $popularidadeAlvo = $alvo['popularidade'] ?? 50;

            $pontuacao = $amizade + $confianca + floor($romance / 2) - $rivalidade;

            if ($romance >= 50 || $pontuacao >= 45) {
                $emoji = "❤️";
            } elseif ($confianca >= 25 || $amizade >= 30) {
                $emoji = "🤝";
            } elseif ($amizade >= 12) {
                $emoji = "😄";
            } elseif ($rivalidade >= 45 || $pontuacao <= -35) {
                $emoji = "🤮";
            } elseif ($rivalidade >= 30 || $pontuacao <= -20) {
                $emoji = "🐍";
            } elseif ($popularidadeAlvo <= 35) {
                $emoji = "🙄";
            } else {
                $lista = ["😄", "🔥", "😴", "🎯", "😡"];
                $emoji = $lista[array_rand($lista)];
            }

            registrarEmojiQueridometro(
                $jogadores,
                $nomeNPC,
                $nomeAlvo,
                $emoji,
                $EMOJIS_QUERIDOMETRO
            );
        }
    }

    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['queridometro_feito'] = true;

    if (isset($_POST['auto_queridometro'])) {
        $_SESSION['evento_extra'][] = "💟 O Queridômetro foi preenchido automaticamente com base nas suas relações.";
    } else {
        $_SESSION['evento_extra'][] = "💟 O Queridômetro movimentou a casa.";
    }

    header("Location: jogo.php");
    exit;
}

/* PROCESSAR JOGO DA DISCÓRDIA */
if (isset($_POST['fazer_discordia'])) {

    $tema = $_SESSION['tema_discordia'] ?? ($_POST['tema_discordia'] ?? 'sonso');
    $intensidade = $_POST['intensidade'] ?? 'leve';

    $evento = "";

    /* TEMAS NEGATIVOS */
    if (
        $tema == "sonso" ||
        $tema == "falso" ||
        $tema == "saboneteiro"
    ) {

        $alvo = $_POST['alvo_discordia'] ?? '';

        if ($alvo != '') {

            if ($intensidade == "com_tudo") {

                ajustarRelacaoJogador($alvo, -15);

                alterarAfinidade($jogadores, $meuNome, $alvo, -15, 12, -10);
                alterarAfinidade($jogadores, $alvo, $meuNome, -15, 12, -10);

                ajustarPopularidadePorAlvo($jogadores, $meuNome, $alvo, "bateu de frente com alguém rejeitado pelo público", "passou do ponto contra alguém querido");

                $evento =
                    "🔥 $meuNome chamou $alvo de $tema COM TUDO no Jogo da Discórdia. Afinidade com $alvo caiu 15 pontos.";
            } elseif ($intensidade == "leve") {

                ajustarRelacaoJogador($alvo, -6);

                alterarAfinidade($jogadores, $meuNome, $alvo, -6, 5, -4);
                alterarAfinidade($jogadores, $alvo, $meuNome, -6, 5, -4);

                alterarPopularidadePublica($jogadores, $meuNome, -3, 4, "participou do Jogo da Discórdia sem exagerar", true);

                $evento =
                    "😶 $meuNome chamou $alvo de $tema de forma mais leve. Afinidade com $alvo caiu 6 pontos.";
            } else {

                ajustarRelacaoJogador($alvo, -2);

                alterarAfinidade($jogadores, $meuNome, $alvo, -2, 2, -2);
                alterarPopularidadeMotivo($jogadores, $meuNome, -5, -5, "saboneteou no Jogo da Discórdia");

                $evento =
                    "🧼 $meuNome sabonetou ao falar sobre $alvo. Afinidade com $alvo caiu 2 pontos.";
            }
        }
    }

    /* TEMA POSITIVO */
    if ($tema == "aliado") {

        $alvo = $_POST['alvo_discordia'] ?? '';

        if ($alvo != '') {

            ajustarRelacaoJogador($alvo, 12);

            alterarAfinidade($jogadores, $meuNome, $alvo, 12, -5, 10);
            alterarAfinidade($jogadores, $alvo, $meuNome, 12, -5, 10);

            alterarPopularidadePublica($jogadores, $meuNome, 1, 4, "defendeu um aliado no Jogo da Discórdia", true);

            $evento =
                "🤝 $meuNome declarou que $alvo é seu maior aliado. Afinidade com $alvo subiu 12 pontos.";
        }
    }

    /* PÓDIO */
    if ($tema == "podio") {

        $primeiro = $meuNome;
        $segundo = $_POST['podio_2'] ?? '';
        $terceiro = $_POST['podio_3'] ?? '';

        if (
            $segundo != '' &&
            $terceiro != '' &&
            $segundo != $terceiro &&
            !nomeIgual($segundo, $primeiro) &&
            !nomeIgual($terceiro, $primeiro)
        ) {

            ajustarRelacaoJogador($segundo, 10);
            ajustarRelacaoJogador($terceiro, 6);

            alterarAfinidade($jogadores, $meuNome, $segundo, 10, -4, 8);
            alterarAfinidade($jogadores, $segundo, $meuNome, 10, -4, 8);

            alterarAfinidade($jogadores, $meuNome, $terceiro, 6, -2, 5);
            alterarAfinidade($jogadores, $terceiro, $meuNome, 6, -2, 5);

            $evento =
                "🏆 $meuNome montou seu pódio: 🥇 $primeiro, 🥈 $segundo e 🥉 $terceiro. Afinidades subiram.";
        } else {
            $evento = "⚠️ O 2º e o 3º lugar precisam ser participantes diferentes.";
            $_SESSION['evento_extra'][] = $evento;
            header("Location: jogo.php");
            exit;
        }
    }

    if ($evento == "") {
        $evento = "🔥 O Jogo da Discórdia aconteceu, mas nenhuma escolha válida foi registrada.";
    }

    $_SESSION['evento_extra'][] = $evento;
    $eventosNPC = gerarDiscordiaNPC($jogadores, $meuNome, $tema);

    foreach ($eventosNPC as $ev) {
        $_SESSION['evento_extra'][] = $ev;
    }
    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['discordia_feito'] = true;
    unset($_SESSION['tema_discordia']);

    header("Location: jogo.php");
    exit;
}

/* RETORNOS AUTOMÁTICOS */
if ($_SESSION['fase_semana'] == 'lider' && isset($_SESSION['lider'])) {
    $_SESSION['fase_semana'] = 'vip_xepa';
}

if (
    $_SESSION['fase_semana'] == 'anjo' &&
    isset($_SESSION['anjo']) &&
    isset($_SESSION['prova_anjo_finalizada']) &&
    !isset($_SESSION['monstro_definido'])
) {
    $_SESSION['fase_semana'] = 'monstro';
}

if ($_SESSION['fase_semana'] == 'bigfone' && isset($_SESSION['bigfone_feito'])) {
    $_SESSION['fase_semana'] = 'interacoes_2';
    $_SESSION['acoes_restantes'] = 3;
}

$fase = $_SESSION['fase_semana'];

if ($fase == 'confessionario' && !isset($_SESSION['confessionario_feito'])) {
    prepararConfessionarioDaRodada($jogadores, $meuNome);
}

$jogadores = $_SESSION['jogadores'];
garantirMeuJogadorNaLista($jogadores);
garantirPopularidade($jogadores);
foreach($jogadores as &$j){
    if(!array_key_exists('alianca', $j)){
        $j['alianca'] = null;
    }
    if(!isset($j['historico_aliancas']) || !is_array($j['historico_aliancas'])){
        $j['historico_aliancas'] = [];
    }
}
unset($j);
$_SESSION['jogadores'] = $jogadores;
$meuNome = trim($_SESSION['meu_nome'] ?? '');

/* =========================
   DEIXAR JOGADOR EM PRIMEIRO
   Comparação segura: ignora espaços e maiúsculas/minúsculas
========================= */

function nomeIgual($a, $b)
{
    return mb_strtolower(trim((string)$a), 'UTF-8') === mb_strtolower(trim((string)$b), 'UTF-8');
}

usort($jogadores, function ($a, $b) use ($meuNome) {

    if (nomeIgual(($a['nome'] ?? ''), $meuNome)) return -1;
    if (nomeIgual(($b['nome'] ?? ''), $meuNome)) return 1;

    $afinidadeA = $_SESSION['relacoes_jogador'][$a['nome'] ?? ''] ?? 0;
    $afinidadeB = $_SESSION['relacoes_jogador'][$b['nome'] ?? ''] ?? 0;

    if ($afinidadeA == $afinidadeB) {
        return strcmp($a['nome'] ?? '', $b['nome'] ?? '');
    }

    return $afinidadeB <=> $afinidadeA;
});

if (($_SESSION['fase_semana'] ?? '') != 'jogador_eliminado' && count($jogadores) == 3 && ($_SESSION['fase_semana'] ?? '') != 'finalistas') {
    $_SESSION['fase_semana'] = 'finalistas';
    $fase = 'finalistas';
}

if (($_SESSION['fase_semana'] ?? '') == 'finalistas') {
    $fase = 'finalistas';
}

if (($_SESSION['fase_semana'] ?? '') == 'jogador_eliminado') {
    $fase = 'jogador_eliminado';
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>BBB Simulator</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;900&family=Poppins:wght@400;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg: #050512;
            --panel: rgba(18, 18, 38, .78);
            --glass: rgba(255, 255, 255, .055);
            --stroke: rgba(255, 255, 255, .12);
            --pink: #ff007a;
            --purple: #7a00ff;
            --cyan: #00e5ff;
            --gold: #ffd84d;
            --green: #62ff9a;
            --danger: #ff4d6d;
            --orange: #ffb14d;
            --muted: rgba(255, 255, 255, .72);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', Arial, sans-serif;
            min-height: 100vh;
            color: white;
            overflow-x: hidden;
            background:
                radial-gradient(circle at 12% 4%, rgba(255, 0, 122, .30), transparent 28%),
                radial-gradient(circle at 90% 18%, rgba(0, 229, 255, .18), transparent 28%),
                radial-gradient(circle at 50% 100%, rgba(122, 0, 255, .22), transparent 34%),
                linear-gradient(135deg, #050512, #09091f 48%, #03030a);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background:
                linear-gradient(rgba(255, 255, 255, .035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .035) 1px, transparent 1px);
            background-size: 46px 46px;
            mask-image: radial-gradient(circle at center, black, transparent 78%);
        }

        body::after {
            content: "";
            position: fixed;
            inset: -35%;
            z-index: 0;
            pointer-events: none;
            background: conic-gradient(from 180deg, transparent, rgba(255, 0, 122, .15), transparent, rgba(0, 229, 255, .13), transparent);
            animation: girarFundo 16s linear infinite;
            filter: blur(55px);
            opacity: .85;
        }

        /* =========================
   HEADER MODERNO
========================= */

        .top-header {
            position: relative;
            overflow: hidden;

            padding: 28px 35px;

            background:
                linear-gradient(135deg,
                    rgba(18, 18, 40, .95),
                    rgba(10, 10, 25, .95));

            border-bottom: 1px solid rgba(255, 255, 255, .08);

            box-shadow:
                0 10px 35px rgba(0, 0, 0, .35),
                0 0 40px rgba(255, 0, 140, .08);

            margin-bottom: 10px;
        }

        .header-glow {
            position: absolute;
            width: 400px;
            height: 400px;

            background:
                radial-gradient(circle,
                    rgba(255, 0, 140, .18),
                    transparent 70%);

            top: -220px;
            right: -120px;

            pointer-events: none;
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .bbb-icon {
            width: 70px;
            height: 70px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 34px;

            border-radius: 22px;

            background:
                linear-gradient(135deg,
                    #ff0080,
                    #6a00ff);

            box-shadow:
                0 0 25px rgba(255, 0, 130, .35);

            animation: pulseGlow 2s infinite alternate;
        }

        .logo-area h1 {
            font-size: 42px;
            font-weight: 900;
            letter-spacing: 1px;

            background:
                linear-gradient(90deg,
                    #ffffff,
                    #ff4dc4,
                    #7a5cff);

            background-clip: text;
            -webkit-background-clip: text;

            color: transparent;
            -webkit-text-fill-color: transparent;
        }

        .sub-info {
            display: flex;
            align-items: center;
            gap: 12px;

            margin-top: 8px;

            font-size: 15px;
            font-weight: 600;

            opacity: .92;
        }

        .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;

            background: #ff4dc4;
        }

        @keyframes pulseGlow {

            from {
                transform: scale(1);
                box-shadow:
                    0 0 20px rgba(255, 0, 130, .25);
            }

            to {
                transform: scale(1.05);
                box-shadow:
                    0 0 35px rgba(255, 0, 130, .55);
            }

        }

        @keyframes girarFundo {
            to {
                transform: rotate(360deg);
            }
        }

        header {
            position: sticky;
            top: 0;
            z-index: 10;
            padding: 22px 20px 24px;
            text-align: center;
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            line-height: 1.45;
            font-weight: 900;
            letter-spacing: .5px;
            background: rgba(5, 5, 18, .72);
            border-bottom: 1px solid rgba(255, 255, 255, .10);
            box-shadow: 0 12px 35px rgba(0, 0, 0, .32);
            backdrop-filter: blur(18px);
        }

        .container {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 360px 1fr 360px;
            /* layout antigo: participantes | controle | ao vivo */
            gap: 20px;
            padding: 20px;
            align-items: start;
        }

        .left-col{
            min-width:0;
            display:flex;
            flex-direction:column;
            gap:16px;
        }

        .left,
        .center,
        .right {
            position: relative;
            overflow: hidden;
            background: linear-gradient(180deg, rgba(255, 255, 255, .075), rgba(255, 255, 255, .035));
            border: 1px solid var(--stroke);
            padding: 18px;
            border-radius: 24px;
            box-shadow: 0 18px 45px rgba(0, 0, 0, .34), inset 0 1px 0 rgba(255, 255, 255, .08);
            backdrop-filter: blur(18px);
        }

        .left::before,
        .center::before,
        .right::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 4px;
            background: linear-gradient(90deg, var(--pink), var(--purple), var(--cyan), var(--gold));
            opacity: .95;
        }

        .left {
            max-height: 540px;
            overflow-y: auto;
            padding-right: 10px;
            scrollbar-width: thin;
            scrollbar-color: #ff00c8 rgba(10, 8, 30, .75);
        }

        .left::-webkit-scrollbar {
            width: 12px;
        }

        .left::-webkit-scrollbar-track {
            background: linear-gradient(180deg, rgba(255, 255, 255, .06), rgba(0, 217, 255, .05), rgba(255, 0, 140, .06));
            border-radius: 999px;
            margin: 18px 0;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, .45);
        }

        .left::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #ffd43b 0%, #ff008c 38%, #9d00ff 68%, #00d9ff 100%);
            border-radius: 999px;
            border: 3px solid rgba(13, 9, 31, .96);
            box-shadow: 0 0 12px rgba(255, 0, 200, .55), 0 0 18px rgba(0, 217, 255, .35);
        }

        .left::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #fff06a 0%, #ff2eb3 40%, #b84cff 70%, #43f2ff 100%);
        }

        .left::-webkit-scrollbar-button {
            width: 0;
            height: 0;
            display: none;
        }

        .right {
            max-height: calc(100vh - 165px);
            display: flex;
            flex-direction: column;
        }

        h2 {
            font-size: 25px;
            margin-bottom: 18px;
            letter-spacing: .2px;
            text-shadow: 0 0 20px rgba(255, 0, 122, .25);
        }

        .participantes-box::-webkit-scrollbar {
            width: 8px;
        }

        .participantes-box::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 20px;
        }

        .participantes-box::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #ff0080, #00d4ff);
            border-radius: 20px;
        }

        .participantes-box {
            overflow-y: auto;
            max-height: 540px;
            padding-right: 10px;

            scrollbar-width: thin;
            scrollbar-color: #ff00a8 rgba(255, 255, 255, 0.08);
        }

        .participantes-box::-webkit-scrollbar {
            width: 10px;
        }

        .participantes-box::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.06);
            border-radius: 999px;
            margin: 12px 0;
        }

        .participantes-box::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #ff008c, #9d00ff, #00d9ff);
            border-radius: 999px;
            border: 2px solid rgba(10, 10, 25, 0.95);
        }

        .participantes-box::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #ffd43b, #ff008c, #00d9ff);
        }

        .players-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .card {
            min-height: 178px;
            padding: 12px 9px;
            border-radius: 19px;
            background: linear-gradient(160deg, rgba(15, 24, 57, .96), rgba(7, 12, 31, .96));
            position: relative;
            transition: .25s ease;
            box-shadow: 0 12px 26px rgba(0, 0, 0, .32);
            isolation: isolate;
        }

        .card::before {
            content: "";
            position: absolute;
            inset: -2px;
            border-radius: 21px;
            background: linear-gradient(145deg, var(--gold), var(--pink), var(--purple), var(--cyan));
            z-index: -1;
            opacity: .72;
        }

        .card:hover {
            transform: translateY(-5px) scale(1.015);
            box-shadow: 0 18px 35px rgba(0, 0, 0, .42), 0 0 25px rgba(255, 0, 122, .22);
        }

        .voce {
            box-shadow: 0 0 0 2px rgba(255, 216, 77, .45), 0 0 26px rgba(255, 216, 77, .42);
        }

        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin: 0 auto 9px;
            background:
                radial-gradient(circle at 50% 35%, rgba(255, 255, 255, .18), transparent 12%),
                radial-gradient(circle, #050505 48%, #111 52%);
            border: 3px solid var(--gold);
            box-shadow: 0 0 20px rgba(255, 216, 77, .34), inset 0 0 12px rgba(255, 255, 255, .04);
        }

        .card h3 {
            text-align: center;
            font-size: 14px;
            line-height: 1.25;
            margin-bottom: 8px;
            font-weight: 800;
        }

        .card p {
            font-size: 11px;
            margin: 4px 0;
            line-height: 1.25;
            color: rgba(255, 255, 255, .92);
        }

        .status {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid rgba(255, 255, 255, .10);
            font-size: 11px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .lider,
        .anjo,
        .imune,
        .vip,
        .xepa,
        .status div {
            font-weight: 800;
            text-shadow: 0 0 12px rgba(255, 255, 255, .08);
        }

        .lider {
            color: var(--gold);
        }

        .anjo {
            color: #70d6ff;
        }

        .imune {
            color: var(--green);
        }

        .vip {
            color: var(--gold);
        }

        .xepa {
            color: var(--orange);
        }

        .box {
            background: linear-gradient(180deg, rgba(255, 255, 255, .075), rgba(255, 255, 255, .035));
            padding: 15px;
            border-radius: 18px;
            margin-bottom: 15px;
            line-height: 1.75;
            border: 1px solid rgba(255, 255, 255, .08);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .06), 0 12px 28px rgba(0, 0, 0, .18);
        }

        .box h3 {
            font-size: 18px;
            margin-bottom: 6px;
        }

        .box p {
            color: var(--muted);
        }

        .btn {
            width: 100%;
            padding: 14px 16px;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            color: white;
            background: linear-gradient(135deg, var(--pink), var(--purple));
            transition: .22s ease;
            margin-bottom: 10px;
            box-shadow: 0 12px 24px rgba(122, 0, 255, .25), inset 0 1px 0 rgba(255, 255, 255, .18);
        }

        .btn:hover {
            transform: translateY(-3px) scale(1.01);
            box-shadow: 0 16px 30px rgba(255, 0, 122, .38), 0 0 22px rgba(122, 0, 255, .28);
        }

        .btn:active {
            transform: scale(.98);
        }

        .novo {
            background: linear-gradient(135deg, #444, #101010);
        }

        .anjo-btn {
            background: linear-gradient(135deg, #00c6ff, #0066ff);
        }

        select {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 16px;
            margin-bottom: 12px;
            font-size: 14px;
            color: white;
            background: rgba(0, 0, 0, .35);
            outline: none;
        }

        select option {
            background: #111;
            color: white;
        }

        .interacoes-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .interacao-card {
            background: rgba(255, 255, 255, .05);
            padding: 14px;
            border-radius: 18px;
        }

        .interacao-card label {
            display: block;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .interacao-card .btn {
            padding: 11px;
            font-size: 13px;
        }

        .log {
            flex: 1;
            max-height: 720px;
            overflow-y: auto;
            scrollbar-width: none;
            padding-right: 2px;
        }

        .log::-webkit-scrollbar {
            display: none;
        }

        .log p {
            position: relative;
            background: linear-gradient(180deg, rgba(255, 255, 255, .075), rgba(255, 255, 255, .04));
            padding: 13px 13px 13px 16px;
            border-radius: 16px;
            margin-bottom: 11px;
            line-height: 1.5;
            font-size: 14px;
            border: 1px solid rgba(255, 255, 255, .06);
            box-shadow: 0 10px 22px rgba(0, 0, 0, .18);
        }

        .log p::before {
            content: "";
            position: absolute;
            left: 0;
            top: 14px;
            bottom: 14px;
            width: 3px;
            border-radius: 10px;
            background: linear-gradient(var(--pink), var(--cyan));
        }

        .popup-bg {
            position: fixed;
            inset: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(0, 0, 0, .0);
            backdrop-filter: blur(0);
            opacity: 0;
            pointer-events: none;
            transition: .35s;
            z-index: 9999;
            padding: 20px;
        }

        .popup-bg.ativo {
            opacity: 1;
            pointer-events: all;
            background: rgba(0, 0, 0, .78);
            backdrop-filter: blur(8px);
        }

        .popup-box {
            width: 430px;
            max-width: 100%;
            background: linear-gradient(180deg, #201738, #0d0d18);
            border-radius: 28px;
            padding: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, .12);
            box-shadow: 0 0 45px rgba(255, 0, 122, .25), 0 18px 45px rgba(0, 0, 0, .55);
            transform: scale(.75) translateY(60px);
            opacity: 0;
            transition: .45s cubic-bezier(.17, .89, .32, 1.28);
        }

        .popup-bg.ativo .popup-box {
            transform: scale(1) translateY(0);
            opacity: 1;
        }

        .popup-box h3 {
            font-size: 28px;
            margin-bottom: 15px;
            background: linear-gradient(90deg, var(--pink), var(--gold));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }

        .popup-box p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
            opacity: .92;
        }

        .popup-botoes {
            display: flex;
            gap: 12px;
        }

        .popup-botoes button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            color: white;
        }

        .cancelar {
            background: #444;
        }

        .confirmar {
            background: linear-gradient(135deg, var(--pink), var(--purple));
        }

        .participantes-escolha {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .participante-btn {
            padding: 10px 14px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, .14);
            background: rgba(0, 0, 0, .30);
            color: white;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 18px rgba(0, 0, 0, .22);
            transition: .2s;
        }

        .participante-btn input {
            accent-color: var(--pink);
        }

        .participante-btn:hover {
            transform: translateY(-3px);
            background: linear-gradient(135deg, var(--pink), var(--purple));
            box-shadow: 0 0 20px rgba(255, 0, 122, .34);
        }

        .participante-btn.selecionado {
            background: linear-gradient(135deg, var(--pink), var(--purple));
            box-shadow: 0 0 20px rgba(255, 0, 130, .5);
        }

        .card.positiva {
            box-shadow: 0 0 22px rgba(0, 255, 120, .45);
        }

        .card.positiva h3 {
            color: #62ff9a;
            text-shadow: 0 0 12px rgba(0, 255, 120, .65);
        }

        .card.negativa {
            box-shadow: 0 0 22px rgba(255, 60, 90, .45);
        }

        .card.negativa h3 {
            color: #ff4d6d;
            text-shadow: 0 0 12px rgba(255, 60, 90, .65);
        }

        .card.neutra h3 {
            color: white;
        }

        .afinidade-card,
        .romance-card {
            margin-top: 6px;
            font-weight: 800;
        }

        .afinidade-card {
            color: #ff6fae;
        }

        .romance-card {
            color: #ff9fdf;
            text-shadow: 0 0 10px rgba(255, 0, 150, .45);
        }

        .alianca-card,
        .alianca-status{
            color:#7dfcff;
            font-weight:900;
            text-shadow:0 0 12px rgba(0,217,255,.45);
        }

        .aliancas-mini-box{
            position:relative;
            overflow:hidden;
            padding:14px;
            border-radius:22px;
            background:
                linear-gradient(180deg,rgba(255,255,255,.075),rgba(255,255,255,.035)),
                radial-gradient(circle at 15% 0%,rgba(255,0,140,.18),transparent 35%),
                radial-gradient(circle at 95% 10%,rgba(0,217,255,.16),transparent 38%);
            border:1px solid rgba(255,255,255,.11);
            box-shadow:0 14px 30px rgba(0,0,0,.28), inset 0 1px 0 rgba(255,255,255,.08);
            backdrop-filter:blur(18px);
        }

        .aliancas-mini-box::before{
            content:"";
            position:absolute;
            inset:0 0 auto 0;
            height:4px;
            background:linear-gradient(90deg,var(--pink),var(--purple),var(--cyan),var(--gold));
            opacity:.95;
        }

        .aliancas-mini-topo{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            margin-bottom:10px;
        }

        .aliancas-mini-topo h3{
            margin:0;
            font-size:18px;
            line-height:1.2;
        }

        .aliancas-mini-topo span{
            flex-shrink:0;
            font-size:10px;
            font-weight:900;
            padding:6px 8px;
            border-radius:999px;
            color:#7dfcff;
            background:rgba(0,217,255,.10);
            border:1px solid rgba(0,217,255,.18);
        }

        .aliancas-mini-lista{
            display:grid;
            gap:9px;
            max-height:170px;
            overflow-y:auto;
            padding-right:6px;
            scrollbar-width:thin;
            scrollbar-color:#00d9ff rgba(255,255,255,.06);
        }

        .aliancas-mini-lista::-webkit-scrollbar{
            width:7px;
        }

        .aliancas-mini-lista::-webkit-scrollbar-track{
            background:rgba(255,255,255,.06);
            border-radius:999px;
        }

        .aliancas-mini-lista::-webkit-scrollbar-thumb{
            background:linear-gradient(180deg,#ff008c,#7a00ff,#00d9ff);
            border-radius:999px;
        }

        .alianca-mini-card{
            padding:10px 11px;
            border-radius:16px;
            background:linear-gradient(135deg,rgba(0,217,255,.09),rgba(255,0,140,.07));
            border:1px solid rgba(255,255,255,.09);
        }

        .alianca-mini-header{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:8px;
            margin-bottom:5px;
        }

        .alianca-mini-header strong{
            color:#7dfcff;
            font-size:14px;
            line-height:1.2;
            text-shadow:0 0 12px rgba(0,217,255,.35);
        }

        .alianca-mini-header small{
            flex-shrink:0;
            color:rgba(255,255,255,.68);
            font-size:10px;
            font-weight:800;
        }

        .alianca-mini-card p{
            margin:0;
            color:rgba(255,255,255,.88);
            font-size:12px;
            line-height:1.35;
        }

        @media(max-width:1200px) {
            .container {
                grid-template-columns: 1fr;
            }

            .left,
            .right {
                max-height: none;
            }

            .interacoes-grid {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width:760px) {
            header {
                font-size: 22px;
            }

            .container {
                padding: 16px;
                gap: 16px;
            }

            .left,
            .center,
            .right {
                padding: 18px;
                border-radius: 22px;
            }

            .players-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .popup-botoes {
                flex-direction: column;
            }
        }

        /* ======================================
   💖 QUERIDÔMETRO COMPACTO
====================================== */

        .queridometro-wrapper {
            width: 100%;
            max-width: 100%;
            margin: auto;
            padding: 8px 4px 14px;
        }

        .querido-header {
            margin-bottom: 14px;
            padding: 16px 18px;
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(255, 0, 140, 0.14), rgba(0, 217, 255, 0.08));
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .querido-header h2 {
            color: white;
            font-size: 28px;
            margin-bottom: 6px;
        }

        .querido-header p {
            color: #d5d5e6;
            font-size: 15px;
        }

        .legenda-box {
            margin-bottom: 18px;
            padding: 14px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.045);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .legenda-box summary {
            cursor: pointer;
            font-weight: 800;
            color: white;
            margin-bottom: 12px;
        }

        .legenda-querido {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
            gap: 10px;
        }

        .emoji-legenda {
            background: rgba(0, 0, 0, 0.22);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 9px 11px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .emoji-legenda span {
            font-size: 22px;
        }

        .emoji-legenda small {
            color: #d5d5e6;
            font-size: 11px;
            line-height: 1.25;
        }

        .queridometro-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(245px, 1fr));
            gap: 14px;
            max-height: 560px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .queridometro-grid::-webkit-scrollbar {
            width: 8px;
        }

        .queridometro-grid::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.06);
            border-radius: 999px;
        }

        .queridometro-grid::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #ff008c, #9d00ff, #00d9ff);
            border-radius: 999px;
        }

        .card-querido {
            background: linear-gradient(145deg, rgba(15, 15, 35, 0.96), rgba(5, 5, 15, 0.96));
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 22px;
            padding: 15px;
            box-shadow: 0 0 24px rgba(0, 0, 0, 0.28);
            transition: 0.25s;
        }

        .card-querido:hover {
            transform: translateY(-3px);
        }

        .card-querido.positivo {
            border-color: rgba(0, 255, 120, 0.45);
            box-shadow: 0 0 20px rgba(0, 255, 120, 0.10);
        }

        .card-querido.negativo {
            border-color: rgba(255, 0, 80, 0.45);
            box-shadow: 0 0 20px rgba(255, 0, 80, 0.10);
        }

        .topo-card-querido {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 13px;
        }

        .topo-card-querido h3 {
            color: white;
            margin: 0;
            font-size: 21px;
        }

        .topo-card-querido span {
            color: #bdbde0;
            font-size: 12px;
        }

        .valor-relacao {
            min-width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.08);
            color: white;
            font-weight: 900;
            font-size: 14px;
        }

        .emojis-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .emojis-grid input {
            display: none;
        }

        .emoji-btn {
            height: 48px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            background: rgba(255, 255, 255, 0.045);
            border: 1px solid rgba(255, 255, 255, 0.09);
            cursor: pointer;
            transition: 0.22s;
        }

        .emoji-btn:hover {
            transform: scale(1.06);
            background: rgba(255, 255, 255, 0.09);
        }

        .emojis-grid input:checked+.emoji-btn {
            background: linear-gradient(135deg, #ff008c, #7a00ff);
            border-color: transparent;
            transform: scale(1.08);
            box-shadow: 0 0 18px rgba(255, 0, 140, 0.50);
        }

        .btn-confirmar-querido {
            margin-top: 20px;
            width: 100%;
            min-height: 58px;
            border: none;
            border-radius: 18px;
            background: linear-gradient(135deg, #ff008c, #7a00ff);
            color: white;
            font-size: 18px;
            font-weight: 900;
            cursor: pointer;
            transition: 0.25s;
        }

        .btn-confirmar-querido:hover {
            transform: scale(1.01);
        }

        .resultado-querido-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(245px, 1fr));
            gap: 14px;
            max-height: 560px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .resultado-querido-card {
            padding: 15px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .resultado-querido-card h3 {
            margin-bottom: 12px;
            color: white;
        }

        .resultado-emojis {
            display: grid;
            gap: 8px;
        }

        .resultado-emoji-item {
            display: grid;
            grid-template-columns: 35px 1fr 40px;
            align-items: center;
            gap: 8px;
            background: rgba(0, 0, 0, 0.22);
            border-radius: 12px;
            padding: 8px;
        }

        .resultado-emoji-item span {
            font-size: 24px;
        }

        .resultado-emoji-item small {
            color: #dcdcf0;
            font-size: 11px;
        }

        .resultado-emoji-item b {
            color: #ffd43b;
        }

        /* Scrollbar geral da página */
        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(10, 10, 25, 0.95);
            border-radius: 20px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #ff008c, #7a00ff, #00d9ff);
            border-radius: 20px;
            border: 3px solid rgba(10, 10, 25, 0.95);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #ffd43b, #ff008c, #00d9ff);
        }

        /* Firefox */
        html {
            scrollbar-width: thin;
            scrollbar-color: #ff008c rgba(10, 10, 25, 0.95);
        }

        @media(max-width: 768px) {

            .queridometro-grid,
            .resultado-querido-grid {
                grid-template-columns: 1fr;
                max-height: none;
                overflow-y: visible;
            }

            .legenda-querido {
                grid-template-columns: 1fr;
            }
        }


        /* =========================
   🚫 TELA DE JOGADOR ELIMINADO
========================= */

        .eliminado-final-box {
            background: linear-gradient(145deg, rgba(20, 5, 25, .95), rgba(5, 8, 20, .98));
            border: 1px solid rgba(255, 0, 120, .35);
            box-shadow: 0 0 35px rgba(255, 0, 120, .18);
            border-radius: 26px;
            padding: 28px;
            margin-top: 20px;
            text-align: center;
        }

        .eliminado-final-box h2 {
            font-size: 34px;
            margin-bottom: 12px;
            background: linear-gradient(90deg, #ff008c, #ffd43b);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .eliminado-final-box p {
            color: #ddd;
            line-height: 1.6;
        }

        .estatisticas-finais-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 14px;
            margin: 24px 0;
        }

        .stat-final-card {
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .09);
            border-radius: 18px;
            padding: 16px;
        }

        .stat-final-card strong {
            display: block;
            font-size: 26px;
            color: #fff;
            margin-bottom: 4px;
        }

        .stat-final-card span {
            color: #aaa;
            font-size: 13px;
        }

        .popularidade-final-barra {
            width: 100%;
            height: 18px;
            background: rgba(255, 255, 255, .08);
            border-radius: 999px;
            overflow: hidden;
            margin: 16px 0 8px;
        }

        .popularidade-final-preenchimento {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #ff008c, #7a00ff, #00d9ff);
            box-shadow: 0 0 18px rgba(255, 0, 140, .5);
        }



        /* =========================
   🎥 CONFESSIONÁRIO
========================= */

        .confessionario-box {
            position: relative;
            overflow: hidden;
            border-radius: 26px;
            padding: 22px;
            margin-bottom: 18px;
            background:
                radial-gradient(circle at top left, rgba(255, 0, 140, .20), transparent 34%),
                radial-gradient(circle at bottom right, rgba(0, 217, 255, .12), transparent 35%),
                linear-gradient(145deg, rgba(255, 255, 255, .08), rgba(255, 255, 255, .035));
            border: 1px solid rgba(255, 255, 255, .12);
            box-shadow: 0 18px 42px rgba(0, 0, 0, .28), 0 0 35px rgba(255, 0, 140, .08);
        }

        .confessionario-box::before {
            content: "🎥";
            position: absolute;
            right: 18px;
            top: 14px;
            font-size: 48px;
            opacity: .12;
        }

        .confessionario-box h3 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .confessionario-box p {
            color: rgba(255, 255, 255, .78);
        }

        .confessionario-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            max-height: 520px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .confessionario-grid::-webkit-scrollbar {
            width: 8px;
        }

        .confessionario-grid::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, .06);
            border-radius: 999px;
        }

        .confessionario-grid::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #ff008c, #7a00ff, #00d9ff);
            border-radius: 999px;
        }

        .confessionario-fala {
            position: relative;
            padding: 16px 16px 16px 18px;
            border-radius: 18px;
            background: rgba(0, 0, 0, .24);
            border: 1px solid rgba(255, 255, 255, .09);
            line-height: 1.65;
            box-shadow: 0 10px 22px rgba(0, 0, 0, .18);
        }

        .confessionario-fala::before {
            content: "";
            position: absolute;
            left: 0;
            top: 16px;
            bottom: 16px;
            width: 4px;
            border-radius: 999px;
            background: linear-gradient(#ff008c, #00d9ff);
        }
    </style>
</head>

<body>

    <header class="top-header">

        <div class="header-glow"></div>

        <div class="header-content">

            <div class="logo-area">
                <div class="bbb-icon">🎥</div>

                <div>
                    <h1>BBB Simulator</h1>

                    <div class="sub-info">
                        <span>🔥 Rodada <?php echo $rodada; ?></span>
                        <span class="dot"></span>
                        <span>👥 <?php echo count($jogadores); ?> participantes restantes</span>
                    </div>
                </div>
            </div>

        </div>

    </header>

    <div class="container">

        <div class="left-col">
            <div class="left">
                <h2>👥 Participantes</h2>

                <div class="players-grid">
                <?php foreach ($jogadores as $j): ?>
                    <?php
                    $relacaoComVoce = 0;

                    if (!nomeIgual(($j['nome'] ?? ''), $meuNome)) {
                        $relacaoComVoce = $_SESSION['relacoes_jogador'][$j['nome']] ?? 0;
                    }

                    $classeRelacao = "neutra";

                    if ($relacaoComVoce >= 30) {
                        $classeRelacao = "positiva";
                    } elseif ($relacaoComVoce <= -10) {
                        $classeRelacao = "negativa";
                    }
                    ?>
                    <div class="card <?php echo $classeRelacao; ?> <?php if (nomeIgual(($j['nome'] ?? ''), $meuNome)) echo 'voce'; ?>">

                        <div class="avatar"></div>

                        <h3>
                            <?php echo $j['nome']; ?>, <?php echo $j['idade']; ?>
                            <?php if (nomeIgual(($j['nome'] ?? ''), $meuNome)) echo " ⭐"; ?>
                        </h3>

                        <p>💼 <?php echo $j['profissao']; ?></p>
                        <p>📍 <?php echo $j['estado']; ?></p>
                        <p>🎭 <?php echo $j['personalidade']; ?></p>
                        <?php if (!empty($j['alianca'])): ?>
                            <p class="alianca-card">🤝 Aliança: <?php echo $j['alianca']; ?></p>
                        <?php endif; ?>
                        <?php if (!nomeIgual(($j['nome'] ?? ''), $meuNome)): ?>
                            <?php
                            $romanceComVoce = obterRomance($jogadores, $meuNome, $j['nome'] ?? '');
                            $statusRomanceCard = statusRomance($romanceComVoce, $j['nome'] ?? '', $meuNome);
                            ?>
                            <p class="afinidade-card">
                                ❤️ Afinidade: <?php echo $relacaoComVoce; ?>
                            </p>
                            <p class="romance-card">
                                💕 Romance: <?php echo $romanceComVoce; ?> <?php if ($statusRomanceCard != '') echo '— ' . $statusRomanceCard; ?>
                            </p>
                        <?php endif; ?>

                        <div class="status">
                            <?php if (!empty($j['status']['lider'])) echo "<div class='lider'>👑 Líder</div>"; ?>
                            <?php if (!empty($j['status']['anjo'])) echo "<div class='anjo'>😇 Anjo</div>"; ?>
                            <?php if (!empty($j['status']['imune'])) echo "<div class='imune'>🛡️ Imune</div>"; ?>
                            <?php if (!empty($j['status']['vip'])) echo "<div class='vip'>🟡 VIP</div>"; ?>
                            <?php if (!empty($j['status']['xepa'])) echo "<div class='xepa'>🍞 Xepa</div>"; ?>
                            <?php if (!empty($j['status']['monstro'])) echo "<div style='color:#ff4d4d;'>👹 Monstro</div>"; ?>
                            <?php if (!empty($j['alianca'])) echo "<div class='alianca-status'>🤝 ".$j['alianca']."</div>"; ?>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php
            $resumoAliancas = gerarResumoAliancas($jogadores);
            ?>

            <?php if (!empty($resumoAliancas)): ?>
                <div class="aliancas-mini-box">
                    <div class="aliancas-mini-topo">
                        <h3>🤝 Alianças da Casa</h3>
                        <span><?php echo count($resumoAliancas); ?> grupo(s)</span>
                    </div>

                    <div class="aliancas-mini-lista">
                        <?php foreach ($resumoAliancas as $nomeAlianca => $membros): ?>
                            <div class="alianca-mini-card">
                                <div class="alianca-mini-header">
                                    <strong><?php echo htmlspecialchars($nomeAlianca, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small><?php echo count($membros); ?> membro(s)</small>
                                </div>

                                <p><?php echo htmlspecialchars(implode(", ", $membros), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="center">

            <h2>🎮 Controle da Semana</h2>

            <div class="box">
                🎯 Fase atual: <b><?php echo strtoupper(str_replace("_", " ", $fase)); ?></b><br>
                🔥 Rodada: <?php echo $rodada; ?>
            </div>

            <?php if ($fase == 'jogador_eliminado'): ?>

                <?php
                $meuFinal = $_SESSION['meu_jogador_snapshot'] ?? [];
                $estatisticasFinal = $meuFinal['estatisticas'] ?? [];
                $popularidadeFinal = $_SESSION['minha_popularidade_final'] ?? ($meuFinal['popularidade'] ?? 50);
                $colocacaoFinal = $_SESSION['minha_colocacao_final'] ?? (count($jogadores) + 1);
                $rodadasSobrevividas = max(1, ($rodada ?? 1) - 1);
                ?>

                <div class="eliminado-final-box">

                    <h2>🚫 Fim de Jogo</h2>

                    <p>
                        Você foi eliminado da temporada em <b><?php echo $colocacaoFinal; ?>º lugar</b>.
                        Sua trajetória chegou ao fim, mas suas estatísticas ficaram registradas.
                    </p>

                    <div class="popularidade-final-barra">
                        <div class="popularidade-final-preenchimento" style="width: <?php echo limitar($popularidadeFinal, 0, 100); ?>%;"></div>
                    </div>

                    <p>📊 Popularidade final: <b><?php echo limitar($popularidadeFinal, 0, 100); ?>/100</b></p>

                    <div class="estatisticas-finais-grid">

                        <div class="stat-final-card">
                            <strong><?php echo $rodadasSobrevividas; ?></strong>
                            <span>Rodadas sobrevividas</span>
                        </div>

                        <div class="stat-final-card">
                            <strong><?php echo $estatisticasFinal['lider'] ?? 0; ?></strong>
                            <span>Provas do Líder</span>
                        </div>

                        <div class="stat-final-card">
                            <strong><?php echo $estatisticasFinal['anjo'] ?? 0; ?></strong>
                            <span>Provas do Anjo</span>
                        </div>

                        <div class="stat-final-card">
                            <strong><?php echo $estatisticasFinal['vip'] ?? 0; ?></strong>
                            <span>Vezes no VIP</span>
                        </div>

                        <div class="stat-final-card">
                            <strong><?php echo $estatisticasFinal['xepa'] ?? 0; ?></strong>
                            <span>Vezes na Xepa</span>
                        </div>

                        <div class="stat-final-card">
                            <strong><?php echo $estatisticasFinal['monstro'] ?? 0; ?></strong>
                            <span>Monstros recebidos</span>
                        </div>

                        <div class="stat-final-card">
                            <strong><?php echo $estatisticasFinal['imune'] ?? 0; ?></strong>
                            <span>Imunidades</span>
                        </div>

                        <div class="stat-final-card">
                            <strong><?php echo $estatisticasFinal['paredao'] ?? 0; ?></strong>
                            <span>Paredões enfrentados</span>
                        </div>

                    </div>

                    <form method="POST">
                        <button class="btn novo" name="novo_jogo">
                            🔄 Começar Novo Jogo
                        </button>
                    </form>

                </div>

            <?php else: ?>

                <?php if ($fase == 'vip_xepa' && ($_SESSION['lider'] ?? '') == $meuNome && !isset($_SESSION['vip_definido'])): ?>

                    <div class="box">
                        <h3>👑 Você é o Líder! Escolha <?php echo $qtdVIP; ?> para o VIP</h3>
                    </div>

                    <form method="POST">

                        <div class="participantes-escolha">

                            <?php foreach ($jogadores as $j): ?>

                                <?php
                                if ($j['nome'] == $meuNome) continue;
                                ?>

                                <label class="participante-btn">
                                    <input type="checkbox"
                                        name="vip[]"
                                        value="<?php echo $j['nome']; ?>"
                                        onclick="limitarVIP(this)">

                                    <?php echo $j['nome']; ?>
                                </label>

                            <?php endforeach; ?>

                        </div>


                        <button class="btn" name="definir_vip">
                            Confirmar VIP
                        </button>

                    </form>

                <?php endif; ?>

                <?php if ($fase == 'monstro' && $_SESSION['anjo'] == $meuNome && !isset($_SESSION['monstro_definido'])): ?>

                    <div class="box">
                        <h3>😇 Você é o Anjo! Escolha até 2 pessoas para o Monstro 👹</h3>
                    </div>

                    <form method="POST">

                        <div class="participantes-escolha">

                            <?php foreach ($jogadores as $j): ?>
                                <?php if ($j['nome'] != $meuNome): ?>

                                    <label class="participante-btn">
                                        <input type="checkbox" name="monstro[]" value="<?php echo $j['nome']; ?>" onclick="limitarMonstro(this)">
                                        <?php echo $j['nome']; ?>
                                    </label>

                                <?php endif; ?>
                            <?php endforeach; ?>

                        </div>

                        <button class="btn" name="definir_monstro">
                            Confirmar Monstro
                        </button>

                    </form>

                <?php endif; ?>

                <?php if ($fase == 'imunizacao_anjo' && ($_SESSION['anjo'] ?? '') == $meuNome && !isset($_SESSION['imunizacao_anjo_feita'])): ?>

                    <div class="box">
                        <h3>😇 Você é o Anjo! Escolha quem será imunizado</h3>
                    </div>

                    <form method="POST">

                        <div class="participantes-escolha">
                            <?php foreach ($jogadores as $j): ?>
                                <?php if ($j['nome'] != $meuNome && empty($j['status']['lider'])): ?>

                                    <label class="participante-btn">
                                        <input type="radio" name="imunizado_anjo" value="<?php echo $j['nome']; ?>" required>
                                        <?php echo $j['nome']; ?>
                                    </label>

                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <button class="btn" name="definir_imunidade_anjo">
                            🛡️ Confirmar Imunidade
                        </button>

                    </form>

                <?php endif; ?>

                <?php if ($fase == 'paredao' && !isset($_SESSION['indicacao_lider']) && ($_SESSION['lider'] ?? '') == $meuNome): ?>

                    <div class="box">
                        <h3>👑 Você é o Líder! Indique alguém ao Paredão</h3>
                    </div>

                    <form method="POST">

                        <div class="participantes-escolha">
                            <?php foreach ($jogadores as $j): ?>
                                <?php if ($j['nome'] != $meuNome && empty($j['status']['imune'])): ?>

                                    <label class="participante-btn">
                                        <input type="radio" name="indicado_lider" value="<?php echo $j['nome']; ?>" required>
                                        <?php echo $j['nome']; ?>
                                    </label>

                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <button class="btn" name="indicar_lider">🚨 Confirmar Indicação</button>

                    </form>

                <?php endif; ?>

                <?php if (
                    $fase == 'paredao' &&
                    isset($_SESSION['indicacao_lider']) &&
                    isset($_SESSION['bigfone_indicacao_pendente']) &&
                    ($_SESSION['bigfone_dono_poder'] ?? '') == $meuNome &&
                    !isset($_SESSION['indicacao_bigfone'])
                ): ?>

                    <div class="box">
                        <h3>☎️ Poder do Big Fone! Indique alguém ao Paredão</h3>
                    </div>

                    <form method="POST">

                        <div class="participantes-escolha">
                            <?php foreach ($jogadores as $j): ?>
                                <?php if (
                                    $j['nome'] != $meuNome &&
                                    $j['nome'] != ($_SESSION['lider'] ?? '') &&
                                    $j['nome'] != ($_SESSION['indicacao_lider'] ?? '') &&
                                    empty($j['status']['lider']) &&
                                    empty($j['status']['imune'])
                                ): ?>

                                    <label class="participante-btn">
                                        <input type="radio" name="indicado_bigfone" value="<?php echo $j['nome']; ?>" required>
                                        <?php echo $j['nome']; ?>
                                    </label>

                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <button class="btn" name="indicar_bigfone">☎️ Confirmar Indicação</button>

                    </form>

                <?php endif; ?>

                <?php if (
                    $fase == 'paredao' &&
                    isset($_SESSION['indicacao_lider']) &&
                    !isset($_SESSION['bigfone_indicacao_pendente']) &&
                    ($_SESSION['lider'] ?? '') != $meuNome &&
                    !isset($_SESSION['meu_voto_paredao'])
                ): ?>

                    <div class="box">
                        <h3>🗳️ Votação da Casa</h3>
                        <p>Escolha em quem você quer votar para o paredão.</p>
                    </div>

                    <form method="POST">

                        <div class="participantes-escolha">
                            <?php foreach ($jogadores as $j): ?>
                                <?php if (
                                    $j['nome'] != $meuNome &&
                                    empty($j['status']['lider']) &&
                                    empty($j['status']['imune']) &&
                                    $j['nome'] != ($_SESSION['indicacao_lider'] ?? '') &&
                                    $j['nome'] != ($_SESSION['indicacao_bigfone'] ?? '')
                                ): ?>

                                    <label class="participante-btn">
                                        <input type="radio" name="voto_paredao" value="<?php echo $j['nome']; ?>" required>
                                        <?php echo $j['nome']; ?>
                                    </label>

                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <button class="btn" name="votar_paredao">
                            🗳️ Confirmar Voto
                        </button>

                    </form>

                <?php endif; ?>


                <?php if ($fase == 'confessionario'): ?>

                    <div class="confessionario-box">
                        <h3>🎥 Confessionário da Rodada</h3>
                        <p>A partir da Rodada 2, os participantes revelam pensamentos sobre alianças, rivalidades, romance e estratégia logo depois do Queridômetro.</p>
                    </div>

                    <div class="confessionario-grid">

                        <?php if (!empty($_SESSION['confessionario_falas'])): ?>
                            <?php foreach ($_SESSION['confessionario_falas'] as $falaConfessionario): ?>
                                <div class="confessionario-fala">
                                    <?php echo $falaConfessionario; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="confessionario-fala">
                                🎥 O confessionário ficou em silêncio nesta rodada.
                            </div>
                        <?php endif; ?>

                    </div>

                    <form method="POST">
                        <button class="btn" name="avancar_fase">
                            ⏭️ Continuar para as Interações
                        </button>
                    </form>

                <?php endif; ?>

                <?php if ($fase == 'festa'): ?>

                    <div class="box">
                        <h3>🎉 Festa da Semana</h3>
                        <p>Você possui <?php echo $_SESSION['acoes_festa']; ?> ações.</p>
                    </div>

                    <?php if ($_SESSION['acoes_festa'] > 0): ?>

                        <?php $acaoFestaSelecionada = $_SESSION['acao_festa_selecionada'] ?? null; ?>

                        <?php if (!$acaoFestaSelecionada): ?>

                            <div class="interacoes-grid">

                                <form method="POST">
                                    <input type="hidden" name="selecionar_acao_festa" value="aproximar">
                                    <button class="btn">🤝 Se aproximar</button>
                                </form>

                                <form method="POST">
                                    <input type="hidden" name="acao_festa" value="vt">
                                    <button class="btn">📺 Fazer VT</button>
                                </form>

                                <form method="POST">
                                    <input type="hidden" name="selecionar_acao_festa" value="romance">
                                    <button class="btn">💘 Romance</button>
                                </form>

                                <form method="POST">
                                    <input type="hidden" name="selecionar_acao_festa" value="provocar">
                                    <button class="btn">😈 Provocar</button>
                                </form>

                                <form method="POST">
                                    <input type="hidden" name="selecionar_acao_festa" value="dancar">
                                    <button class="btn">💃 Dançar</button>
                                </form>

                                <form method="POST">
                                    <input type="hidden" name="acao_festa" value="beber">
                                    <button class="btn">🍹 Exagerar na bebida</button>
                                </form>

                            </div>

                        <?php else: ?>

                            <form method="POST" id="formFesta">

                                <input type="hidden" name="acao_festa" id="acao_festa_valor" value="<?php echo ($acaoFestaSelecionada == 'romance') ? '' : $acaoFestaSelecionada; ?>">
                                <input type="hidden" name="alvo_festa" id="alvo_festa">

                                <div class="box">
                                    <h3>
                                        <?php
                                        if ($acaoFestaSelecionada == "romance") {
                                            echo "💘 Escolha uma pessoa e depois escolha uma ação romântica";
                                        } elseif ($acaoFestaSelecionada == "flertar") {
                                            echo "😘 Com quem você quer flertar?";
                                        } elseif ($acaoFestaSelecionada == "provocar") {
                                            echo "😈 Quem você quer provocar?";
                                        } elseif ($acaoFestaSelecionada == "dancar") {
                                            echo "💃 Com quem você quer dançar?";
                                        } else {
                                            echo "🤝 De quem você quer se aproximar?";
                                        }
                                        ?>
                                    </h3>
                                </div>

                                <div class="participantes-escolha grupo-festa">
                                    <?php foreach ($jogadores as $j): ?>
                                        <?php if ($j['nome'] != $meuNome): ?>

                                            <?php $romanceBotao = obterRomance($jogadores, $meuNome, $j['nome'] ?? ''); ?>
                                            <button type="button" class="participante-btn" data-romance="<?php echo $romanceBotao; ?>" onclick="selecionarAlvoFesta(this, '<?php echo $j['nome']; ?>')">
                                                <?php echo $j['nome']; ?>
                                                <?php if ($acaoFestaSelecionada == "romance"): ?>
                                                    <br><small>💕 Romance: <?php echo $romanceBotao; ?></small>
                                                <?php endif; ?>
                                            </button>

                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($acaoFestaSelecionada == "romance"): ?>
                                    <div class="box" id="opcoesRomance" style="display:none;">
                                        <h3>💘 Escolha a ação romântica</h3>
                                        <p id="textoRomanceLiberado">Selecione uma pessoa para ver as opções liberadas pelo nível de romance.</p>
                                        <div class="interacoes-grid">
                                            <button type="button" class="btn" onclick="selecionarAcaoRomance('flertar')">😘 Flertar</button>
                                            <button type="button" class="btn" onclick="selecionarAcaoRomance('elogiar')">💕 Elogiar</button>
                                            <button type="button" class="btn romance-30" onclick="selecionarAcaoRomance('sentimentos')" style="display:none;">💬 Falar de sentimentos</button>
                                            <button type="button" class="btn romance-30" onclick="selecionarAcaoRomance('noite_conversando')" style="display:none;">🌙 Conversar até amanhecer</button>
                                            <button type="button" class="btn romance-60" onclick="selecionarAcaoRomance('pedir_namoro')" style="display:none;">💍 Pedir em namoro</button>
                                            <button type="button" class="btn romance-60" onclick="selecionarAcaoRomance('passar_noite_quarto')" style="display:none;">🛏️ Passar a noite juntos no quarto</button>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <button type="button" class="btn" onclick="executarAcaoFesta()">
                                    Executar Ação
                                </button>

                            </form>

                            <form method="POST">
                                <button class="btn novo" name="cancelar_acao_festa">⬅️ Voltar</button>
                            </form>

                        <?php endif; ?>

                    <?php endif; ?>

                <?php endif; ?>

                <?php if ($fase == 'queridometro' && !isset($_SESSION['queridometro_feito'])): ?>

                    <div class="queridometro-wrapper">

                        <div class="querido-header">
                            <div>
                                <h2>💖 Queridômetro da Casa</h2>
                                <p>Escolha um emoji para cada participante. Isso muda principalmente sua relação com eles.</p>
                            </div>
                        </div>

                        <details class="legenda-box" open>
                            <summary>📘 Ver significado dos emojis</summary>

                            <div class="legenda-querido">
                                <?php foreach ($EMOJIS_QUERIDOMETRO as $emoji => $dados): ?>
                                    <div class="emoji-legenda">
                                        <span><?php echo $emoji; ?></span>
                                        <small><?php echo $dados['nome']; ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </details>

                        <form method="POST">

                            <div class="queridometro-grid">

                                <?php foreach ($jogadores as $j): ?>

                                    <?php
                                    $nome = $j['nome'] ?? '';

                                    if ($nome == '' || $nome == $meuNome) continue;

                                    $relacao = $_SESSION['relacoes_jogador'][$nome] ?? 0;

                                    $classe = 'neutro';

                                    if ($relacao >= 15) {
                                        $classe = 'positivo';
                                    }

                                    if ($relacao <= -15) {
                                        $classe = 'negativo';
                                    }
                                    ?>

                                    <div class="card-querido <?php echo $classe; ?>">

                                        <div class="topo-card-querido">
                                            <div>
                                                <h3><?php echo $nome; ?></h3>
                                                <span><?php echo $j['personalidade'] ?? 'Participante'; ?></span>
                                            </div>

                                            <div class="valor-relacao">
                                                <?php echo $relacao; ?>
                                            </div>
                                        </div>

                                        <div class="emojis-grid">
                                            <?php foreach ($EMOJIS_QUERIDOMETRO as $emoji => $dados): ?>
                                                <label title="<?php echo $dados['nome']; ?>">
                                                    <input
                                                        type="radio"
                                                        name="queridometro[<?php echo $nome; ?>]"
                                                        value="<?php echo $emoji; ?>"
                                                        required>

                                                    <span class="emoji-btn">
                                                        <?php echo $emoji; ?>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                            <button type="submit" name="auto_queridometro" class="btn-confirmar-querido" formnovalidate>
                                ⚡ Preencher Automaticamente pelo Relacionamento
                            </button>

                            <button type="submit" name="enviar_queridometro" class="btn-confirmar-querido">
                                💟 Enviar Queridômetro Manualmente
                            </button>

                        </form>

                    </div>

                <?php endif; ?>

                <?php if ($fase == 'queridometro' && isset($_SESSION['queridometro_feito'])): ?>

                    <div class="queridometro-wrapper">

                        <div class="querido-header">
                            <div>
                                <h2>📊 Resultado do Queridômetro</h2>
                                <p>Veja quais emojis cada participante recebeu nesta rodada.</p>
                            </div>
                        </div>

                        <div class="resultado-querido-grid">

                            <?php foreach ($jogadores as $j): ?>
                                <?php
                                $nomeQ = $j['nome'] ?? '';
                                $resultadoQ = $_SESSION['queridometro_resultado'][$nomeQ] ?? [];
                                ?>

                                <div class="resultado-querido-card">
                                    <h3><?php echo $nomeQ; ?></h3>

                                    <?php if (!empty($resultadoQ)): ?>
                                        <div class="resultado-emojis">
                                            <?php foreach ($resultadoQ as $emoji => $qtd): ?>
                                                <div class="resultado-emoji-item">
                                                    <span><?php echo $emoji; ?></span>
                                                    <small><?php echo $EMOJIS_QUERIDOMETRO[$emoji]['nome'] ?? ''; ?></small>
                                                    <b>x<?php echo $qtd; ?></b>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p>Nenhum emoji recebido.</p>
                                    <?php endif; ?>
                                </div>

                            <?php endforeach; ?>

                        </div>

                        <form method="POST">
                            <button class="btn-confirmar-querido" name="avancar_fase">
                                ⏭️ Continuar para as Interações
                            </button>
                        </form>

                    </div>

                <?php endif; ?>

                <?php if (strpos($fase, 'interacoes') !== false): ?>

                    <div class="box">
                        <h3>💬 Interações — <?php echo $_SESSION['acoes_restantes']; ?> restantes</h3>
                        <p>Você pode fazer suas ações ou continuar a semana quando quiser.</p>
                    </div>

                    <?php if ($_SESSION['acoes_restantes'] > 0): ?>

                        <?php
                        $acaoSelecionada = $_SESSION['acao_selecionada'] ?? null;
                        ?>

                        <?php if (!$acaoSelecionada): ?>

                            <!-- ETAPA 1: ESCOLHER AÇÃO -->

                            <div class="interacoes-grid">

                                <?php
                                $acoes = [
                                    ["conversar", "💬 Conversar"],
                                    ["fofoca", "🗣️ Fazer Fofoca"],
                                    ["intriga", "🔥 Criar Intriga"]
                                ];

                                $minhaAliancaAtual = obterAliancaJogador($jogadores, $meuNome);
                                $aliancasExistentesInteracao = gerarResumoAliancas($jogadores);

                                if(($rodada ?? 1) >= 2){
                                    if(empty($minhaAliancaAtual)){
                                        $acoes[] = ["alianca", "🤝 Criar Aliança"];

                                        if(!empty($aliancasExistentesInteracao)){
                                            $acoes[] = ["entrar_alianca", "🚪 Entrar em Aliança"];
                                        }
                                    }else{
                                        $acoes[] = ["sair_alianca", "💥 Sair da Aliança"];
                                    }
                                }

                                $acoes[] = ["aproximar_lider", "👑 Aproximar do Líder"];
                                $acoes[] = ["discutir", "😡 Discutir"];
                                ?>

                                <?php foreach ($acoes as $a): ?>
                                    <form method="POST">
                                        <input type="hidden" name="selecionar_acao" value="<?php echo $a[0]; ?>">
                                        <button class="btn"><?php echo $a[1]; ?></button>
                                    </form>
                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <!-- ETAPA 2: ESCOLHER PARTICIPANTE -->

                            <form method="POST" id="formAcao">

                                <input type="hidden" name="acao" value="<?php echo $acaoSelecionada; ?>">
                                <input type="hidden" name="alvo" id="alvo">
                                <input type="hidden" name="alvo2" id="alvo2">
                                <input type="hidden" name="alianca_escolhida" id="alianca_escolhida">

                                <?php if (!in_array($acaoSelecionada, ["aproximar_lider", "sair_alianca", "entrar_alianca"])): ?>

                                    <div class="box">
                                        <h3>
                                            <?php echo ($acaoSelecionada == "intriga") ? "Escolha o primeiro participante" : "Escolha o participante"; ?>
                                        </h3>
                                    </div>

                                    <div class="participantes-escolha grupo-alvo">

                                        <?php foreach ($jogadores as $j): ?>
                                            <?php if ($j['nome'] != $meuNome): ?>

                                                <button type="button" class="participante-btn" onclick="selecionarAlvo(this, '<?php echo $j['nome']; ?>')">
                                                    <?php echo $j['nome']; ?>
                                                </button>

                                            <?php endif; ?>
                                        <?php endforeach; ?>

                                    </div>

                                <?php endif; ?>

                                <?php if ($acaoSelecionada == "entrar_alianca"): ?>

                                    <div class="box">
                                        <h3>🚪 Escolha uma aliança para tentar entrar</h3>
                                        <p>O grupo pode aceitar ou recusar dependendo da confiança que tem em você.</p>
                                    </div>

                                    <div class="participantes-escolha grupo-alianca">
                                        <?php foreach (gerarResumoAliancas($jogadores) as $nomeAliancaOpcao => $membrosAliancaOpcao): ?>
                                            <button type="button" class="participante-btn" onclick="selecionarAlianca(this, '<?php echo htmlspecialchars($nomeAliancaOpcao, ENT_QUOTES, 'UTF-8'); ?>')">
                                                🤝 <?php echo $nomeAliancaOpcao; ?>
                                                <br><small><?php echo implode(', ', $membrosAliancaOpcao); ?></small>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>

                                <?php endif; ?>

                                <?php if ($acaoSelecionada == "sair_alianca"): ?>

                                    <div class="box">
                                        <h3>💥 Sair da Aliança</h3>
                                        <p>Você está prestes a romper com sua aliança atual. Isso pode afetar confiança, afinidade e votos futuros.</p>
                                    </div>

                                <?php endif; ?>

                                <?php if ($acaoSelecionada == "intriga"): ?>

                                    <div class="box">
                                        <h3>Escolha o segundo participante</h3>
                                    </div>

                                    <div class="participantes-escolha grupo-alvo2">

                                        <?php foreach ($jogadores as $j): ?>
                                            <?php if ($j['nome'] != $meuNome): ?>

                                                <button type="button" class="participante-btn" onclick="selecionarAlvo2(this, '<?php echo $j['nome']; ?>')">
                                                    <?php echo $j['nome']; ?>
                                                </button>

                                            <?php endif; ?>
                                        <?php endforeach; ?>

                                    </div>

                                <?php endif; ?>

                                <button type="button" class="btn" onclick="executarAcao()">
                                    Executar Ação
                                </button>

                            </form>

                            <form method="POST">
                                <button class="btn novo" name="cancelar_acao">⬅️ Voltar</button>
                            </form>

                        <?php endif; ?>

                    <?php else: ?>

                        <div class="box">
                            <h3>✅ Suas ações acabaram!</h3>
                            <p>Os outros participantes também movimentaram o jogo. Veja os acontecimentos no Ao Vivo.</p>
                        </div>

                    <?php endif; ?>

                    <form method="POST">
                        <button class="btn" name="avancar_fase">
                            <?php echo ($_SESSION['acoes_restantes'] > 0) ? '⏭️ Pular Interações / Continuar Semana' : '⏭️ Continuar Semana'; ?>
                        </button>
                    </form>

                <?php endif; ?>

                <form method="POST">

                    <?php if ($fase == 'lider'): ?>

                        <button class="btn" name="avancar_fase">🏆 Ir para Prova do Líder</button>

                    <?php elseif ($fase == 'vip_xepa'): ?>

                        <?php if ($_SESSION['lider'] != $meuNome): ?>

                            <button class="btn" name="avancar_fase">
                                👀 Ver VIP e Xepa do Líder
                            </button>

                        <?php endif; ?>

                    <?php elseif ($fase == 'anjo'): ?>
                        <button class="btn anjo-btn" name="avancar_fase">😇 Ir para Prova do Anjo</button>

                    <?php elseif ($fase == 'monstro'): ?>
                        <?php if ($_SESSION['anjo'] != $meuNome): ?>
                            <button class="btn" name="avancar_fase">👹 Ver Monstro do Anjo</button>
                        <?php endif; ?>

                    <?php elseif ($fase == 'bigfone'): ?>

                        <button class="btn" name="avancar_fase">☎️ Momento Big Fone</button>

                    <?php elseif ($fase == 'discordia'): ?>

                        <?php
                        $temaAtualDiscordia = $_SESSION['tema_discordia'] ?? 'sonso';
                        $nomesTemasDiscordia = [
                            'sonso' => 'Quem é o mais sonso?',
                            'falso' => 'Quem é o mais falso?',
                            'saboneteiro' => 'Quem é o mais saboneteiro?',
                            'aliado' => 'Quem é seu maior aliado?',
                            'podio' => 'Monte seu pódio'
                        ];
                        ?>

                        <div class="box">
                            <h3>🔥 Jogo da Discórdia</h3>
                            <p><b>Tema:</b> <?php echo $nomesTemasDiscordia[$temaAtualDiscordia] ?? $temaAtualDiscordia; ?></p>
                        </div>

                        <form method="POST">

                            <?php if ($temaAtualDiscordia != 'podio'): ?>

                                <div class="box">
                                    <h3><?php echo ($temaAtualDiscordia == 'aliado') ? '🤝 Escolha seu maior aliado' : '🎯 Escolha quem você quer apontar'; ?></h3>
                                </div>

                                <div class="participantes-escolha">
                                    <?php foreach ($jogadores as $j): ?>
                                        <?php if (!nomeIgual(($j['nome'] ?? ''), $meuNome)): ?>
                                            <label class="participante-btn">
                                                <input type="radio" name="alvo_discordia" value="<?php echo $j['nome']; ?>" required>
                                                <?php echo $j['nome']; ?>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>

                                <div class="box">
                                    <h3>🎤 Como você quer falar?</h3>
                                    <div class="participantes-escolha">
                                        <label class="participante-btn">
                                            <input type="radio" name="intensidade" value="com_tudo" required>
                                            🔥 Com tudo
                                        </label>
                                        <label class="participante-btn">
                                            <input type="radio" name="intensidade" value="leve" required>
                                            😶 De leve
                                        </label>
                                        <label class="participante-btn">
                                            <input type="radio" name="intensidade" value="saboneteiro" required>
                                            🧼 Saboneteiro
                                        </label>
                                    </div>
                                </div>

                            <?php else: ?>

                                <div class="box">
                                    <h3>🏆 Monte seu pódio</h3>
                                    <p>Você fica em 1º lugar. Escolha o 2º e 3º lugar.</p>
                                </div>

                                <div class="box">
                                    <h3>🥇 1º lugar</h3>
                                    <p>⭐ <?php echo $meuNome; ?> fica automaticamente em 1º lugar no seu pódio.</p>
                                </div>

                                <h3>🥈 2º lugar</h3>
                                <div class="participantes-escolha">
                                    <?php foreach ($jogadores as $j): ?>
                                        <?php if (!nomeIgual(($j['nome'] ?? ''), $meuNome)): ?>
                                            <label class="participante-btn">
                                                <input type="radio" name="podio_2" value="<?php echo $j['nome']; ?>" required>
                                                🥈 <?php echo $j['nome']; ?>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>

                                <h3>🥉 3º lugar</h3>
                                <div class="participantes-escolha">
                                    <?php foreach ($jogadores as $j): ?>
                                        <?php if (!nomeIgual(($j['nome'] ?? ''), $meuNome)): ?>
                                            <label class="participante-btn">
                                                <input type="radio" name="podio_3" value="<?php echo $j['nome']; ?>" required>
                                                🥉 <?php echo $j['nome']; ?>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>

                                <input type="hidden" name="intensidade" value="leve">

                            <?php endif; ?>

                            <button class="btn" name="fazer_discordia">
                                🔥 Confirmar Jogo da Discórdia
                            </button>

                        </form>

                    <?php elseif ($fase == 'finalistas'): ?>

                        <div class="box">
                            <h3>🏆 Finalistas definidos!</h3>
                            <p>Depois de uma temporada intensa, os três finalistas estão prontos para a grande final.</p>
                            <p>Respirem fundo... está chegando a hora de descobrir o campeão.</p>
                        </div>

                        <button class="btn" name="ir_final">
                            🏆 Ir para Grande Final
                        </button>

                    <?php elseif ($fase == 'eliminacao'): ?>
                        <button class="btn" name="avancar_fase">📺 Ir para Eliminação</button>
                    <?php endif; ?>

                </form>

            <?php endif; ?>

            <button type="button" class="btn novo" onclick="abrirPopup()">
                🔄 Novo Jogo
            </button>

        </div>

        <div class="right">
            <h2>📢 Ao Vivo</h2>

            <form method="POST">
                <button class="btn novo" name="limpar_log">
                    🧹 Limpar Ao Vivo
                </button>
            </form>

            <div class="log" id="aoVivoLog">

                <?php
                if (!empty($_SESSION['evento_extra'])) {
                    foreach ($_SESSION['evento_extra'] as $ev) {
                        echo "<p>$ev</p>";
                    }
                } else {
                    echo "<p>📡 Nenhum acontecimento ainda.</p>";
                }
                ?>

            </div>
        </div>

    </div>

    <div class="popup-bg" id="popupReset">
        <div class="popup-box">

            <h3>🔄 Novo Jogo</h3>

            <p>Deseja encerrar a temporada atual e começar tudo novamente?</p>

            <div class="popup-botoes">

                <button class="cancelar" onclick="fecharPopup()">Cancelar</button>

                <form method="POST" style="width:100%;">
                    <button class="confirmar" name="novo_jogo">Sim, Reiniciar</button>
                </form>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const aoVivoLog = document.getElementById("aoVivoLog");

            if (aoVivoLog) {
                aoVivoLog.scrollTop = aoVivoLog.scrollHeight;
            }
        });


        function limitarMonstro(el) {
            const max = 2;
            const marcados = document.querySelectorAll('input[name="monstro[]"]:checked');

            if (marcados.length > max) {
                el.checked = false;
                alert("Você só pode escolher até 2 participantes!");
            }
        }

        function limitarVIP(el) {
            const max = <?php echo $qtdVIP; ?>;
            const marcados = document.querySelectorAll('input[name="vip[]"]:checked');

            if (marcados.length > max) {
                el.checked = false;
                alert("Você só pode escolher " + max + " participantes!");
            }
        }

        function abrirPopup() {
            document.getElementById("popupReset").classList.add("ativo");
        }

        function fecharPopup() {
            document.getElementById("popupReset").classList.remove("ativo");
        }

        document.getElementById("popupReset").addEventListener("click", function(e) {
            if (e.target === this) {
                fecharPopup();
            }
        });

        function selecionarAlvo(botao, nome) {
            document.getElementById("alvo").value = nome;

            document.querySelectorAll(".grupo-alvo .participante-btn")
                .forEach(btn => btn.classList.remove("selecionado"));

            botao.classList.add("selecionado");
        }

        function selecionarAlvo2(botao, nome) {
            document.getElementById("alvo2").value = nome;

            document.querySelectorAll(".grupo-alvo2 .participante-btn")
                .forEach(btn => btn.classList.remove("selecionado"));

            botao.classList.add("selecionado");
        }

        function executarAcao() {
            const acao = "<?php echo $_SESSION['acao_selecionada'] ?? ''; ?>";
            const alvo = document.getElementById("alvo").value;
            const alvo2 = document.getElementById("alvo2").value;

            if (acao !== "aproximar_lider" && !alvo) {
                alert("Escolha um participante primeiro.");
                return;
            }

            if (acao === "intriga" && !alvo2) {
                alert("Escolha o segundo participante.");
                return;
            }

            if (acao === "intriga" && alvo === alvo2) {
                alert("Escolha dois participantes diferentes.");
                return;
            }

            document.getElementById("formAcao").submit();
        }

        function selecionarAlvoFesta(botao, nome) {
            document.getElementById("alvo_festa").value = nome;

            document.querySelectorAll(".grupo-festa .participante-btn")
                .forEach(btn => btn.classList.remove("selecionado"));

            botao.classList.add("selecionado");

            const opcoesRomance = document.getElementById("opcoesRomance");

            if (opcoesRomance) {
                const romance = parseInt(botao.dataset.romance || "0");
                opcoesRomance.style.display = "block";

                document.querySelectorAll(".romance-30").forEach(btn => {
                    btn.style.display = romance >= 30 ? "block" : "none";
                });

                document.querySelectorAll(".romance-60").forEach(btn => {
                    btn.style.display = romance >= 60 ? "block" : "none";
                });

                const texto = document.getElementById("textoRomanceLiberado");

                if (texto) {
                    if (romance >= 60) {
                        texto.innerHTML = "💕 Romance atual: " + romance + ". Opções de quase casal liberadas.";
                    } else if (romance >= 30) {
                        texto.innerHTML = "💕 Romance atual: " + romance + ". Opções de crush liberadas.";
                    } else {
                        texto.innerHTML = "💕 Romance atual: " + romance + ". Por enquanto, você pode flertar ou elogiar.";
                    }
                }
            }
        }

        function selecionarAcaoRomance(acao) {
            document.getElementById("acao_festa_valor").value = acao;
            executarAcaoFesta();
        }

        function executarAcaoFesta() {
            const alvo = document.getElementById("alvo_festa").value;
            const acaoInput = document.getElementById("acao_festa_valor");
            const acao = acaoInput ? acaoInput.value : "";

            if (!alvo) {
                alert("Escolha um participante primeiro.");
                return;
            }

            if (acaoInput && !acao) {
                alert("Escolha uma ação romântica primeiro.");
                return;
            }

            document.getElementById("formFesta").submit();
        }
    </script>

</body>

</html>
