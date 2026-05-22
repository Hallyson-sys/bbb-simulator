<?php

/* =====================================================
   BBB SIMULATOR - LOGICA_JOGO V3 IA SOCIAL OFICIAL
===================================================== */

function limitar($valor, $min = 0, $max = 100){
    return max($min, min($max, $valor));
}

function getPerfilPersonalidade($tipo){

    $perfis = [
        "Estrategista" => ["rivalidade"=>0.9,"amizade"=>0.3,"aleatorio"=>0.2],
        "Explosivo"    => ["rivalidade"=>1.0,"amizade"=>0.2,"aleatorio"=>0.6],
        "Planta"       => ["rivalidade"=>0.2,"amizade"=>0.7,"aleatorio"=>0.1],
        "Manipulador"  => ["rivalidade"=>0.8,"amizade"=>0.4,"aleatorio"=>0.3],
        "Emocional"    => ["rivalidade"=>0.9,"amizade"=>0.8,"aleatorio"=>0.5],
        "Barraqueiro"  => ["rivalidade"=>1.0,"amizade"=>0.1,"aleatorio"=>0.7],
        "Fofo"         => ["rivalidade"=>0.2,"amizade"=>1.0,"aleatorio"=>0.1],
        "Líder Nato"   => ["rivalidade"=>0.6,"amizade"=>0.6,"aleatorio"=>0.2],
        "Influencer"   => ["rivalidade"=>0.5,"amizade"=>0.5,"aleatorio"=>0.4],
        "Falso"        => ["rivalidade"=>0.9,"amizade"=>0.8,"aleatorio"=>0.3],
        "Neutro"       => ["rivalidade"=>0.5,"amizade"=>0.5,"aleatorio"=>0.5]
    ];

    return $perfis[$tipo] ?? $perfis["Neutro"];
}

/* =====================================================
   RELAÇÕES
===================================================== */

function prepararRelacoesIniciais(&$jogadores){

    foreach($jogadores as &$j){

        if(!isset($j['relacoes'])){
            $j['relacoes'] = [];
        }

        if(!isset($j['romances'])){
            $j['romances'] = [];
        }

        if(!isset($j['estatisticas'])){
            $j['estatisticas'] = [
                "lider" => 0,
                "anjo" => 0,
                "vip" => 0,
                "xepa" => 0,
                "monstro" => 0,
                "imune" => 0
            ];
        }
    }
    unset($j);

    foreach($jogadores as &$j){

        foreach($jogadores as $outro){

            if($j['nome'] == $outro['nome']) continue;

            if(!isset($j['relacoes'][$outro['nome']])){
                $j['relacoes'][$outro['nome']] = [
                    "amizade" => 0,
                    "rivalidade" => 0,
                    "confianca" => 0
                ];
            }
        }
    }
    unset($j);
}

function getRelacaoEntre($jogadores, $nomeA, $nomeB){

    foreach($jogadores as $j){
        if(($j['nome'] ?? '') == $nomeA){
            return $j['relacoes'][$nomeB] ?? [
                "amizade" => 0,
                "rivalidade" => 0,
                "confianca" => 0
            ];
        }
    }

    return [
        "amizade" => 0,
        "rivalidade" => 0,
        "confianca" => 0
    ];
}

function npcInteracaoSocial(&$jogadores){

    prepararRelacoesIniciais($jogadores);

    if(count($jogadores) < 2){
        return "";
    }

    $aIndex = array_rand($jogadores);
    $bIndex = array_rand($jogadores);

    while($aIndex == $bIndex){
        $bIndex = array_rand($jogadores);
    }

    $a = $jogadores[$aIndex];
    $b = $jogadores[$bIndex];

    $perfilA = getPerfilPersonalidade($a['personalidade'] ?? 'Neutro');
    $tipo = rand(1,100);

    if($tipo <= 30){
        alterarRelacao($jogadores, $a['nome'], $b['nome'], rand(4,10), -rand(0,3), rand(3,8));
        alterarRelacao($jogadores, $b['nome'], $a['nome'], rand(3,8), -rand(0,3), rand(2,7));
        return "🤝 {$a['nome']} se aproximou de {$b['nome']} e a afinidade entre eles cresceu.";
    }

    if($tipo <= 55){
        alterarRelacao($jogadores, $a['nome'], $b['nome'], -rand(4,10), rand(3,10), -rand(2,7));
        alterarRelacao($jogadores, $b['nome'], $a['nome'], -rand(3,8), rand(2,8), -rand(2,6));
        return "🔥 {$a['nome']} e {$b['nome']} tiveram um atrito na casa.";
    }

    if($tipo <= 75){
        alterarRelacao($jogadores, $a['nome'], $b['nome'], rand(2,7), 0, rand(5,12));
        return "👀 {$a['nome']} começou a confiar mais em {$b['nome']}.";
    }

    if($tipo <= 90){
        alterarRelacao($jogadores, $a['nome'], $b['nome'], -rand(2,6), rand(4,9), -rand(5,12));
        return "🐍 {$a['nome']} desconfiou de {$b['nome']} depois de uma conversa suspeita.";
    }

    alterarRelacao($jogadores, $a['nome'], $b['nome'], rand(6,12), -rand(0,4), rand(5,10));
    alterarRelacao($jogadores, $b['nome'], $a['nome'], rand(4,10), -rand(0,3), rand(4,9));
    return "💞 {$a['nome']} e {$b['nome']} criaram uma conexão mais forte.";
}

function alterarRelacao(&$jogadores, $nomeA, $nomeB, $amizade = 0, $rivalidade = 0, $confianca = 0){

    foreach($jogadores as &$j){

        if($j['nome'] == $nomeA){

            if(!isset($j['relacoes'][$nomeB])){
                $j['relacoes'][$nomeB] = [
                    "amizade"=>0,
                    "rivalidade"=>0,
                    "confianca"=>0
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

/* =====================================================
   AÇÕES DO JOGADOR
   Essas ações serão chamadas futuramente pelos botões
===================================================== */

function aplicarAcaoDoJogador(&$jogadores, $meuNome, $alvo, $acao){

    $evento = "";

    switch($acao){

        case "fazer_amizade":
            alterarRelacao($jogadores, $meuNome, $alvo, 15, -5, 10);
            alterarRelacao($jogadores, $alvo, $meuNome, 12, -5, 8);
            $evento = "🤝 $meuNome tentou se aproximar de $alvo.";
        break;

        case "criar_intriga":
            alterarRelacao($jogadores, $meuNome, $alvo, -10, 18, -15);
            alterarRelacao($jogadores, $alvo, $meuNome, -15, 20, -20);
            $evento = "🗣️ $meuNome criou uma intriga envolvendo $alvo.";
        break;

        case "combinar_voto":
            alterarRelacao($jogadores, $meuNome, $alvo, 10, 0, 15);
            alterarRelacao($jogadores, $alvo, $meuNome, 8, 0, 12);
            $evento = "🧠 $meuNome tentou combinar voto com $alvo.";
        break;

        case "provocar_treta":
            alterarRelacao($jogadores, $meuNome, $alvo, -20, 25, -10);
            alterarRelacao($jogadores, $alvo, $meuNome, -25, 30, -15);
            $evento = "🔥 $meuNome provocou uma treta com $alvo.";
        break;

        case "fazer_vt":
            foreach($jogadores as &$j){
                if($j['nome'] == $meuNome){
                    $j['popularidade'] = limitar($j['popularidade'] + rand(4,10));
                }
            }
            $evento = "📺 $meuNome fez VT e tentou conquistar o público.";
        break;

        case "se_aproximar_lider":
            alterarRelacao($jogadores, $meuNome, $alvo, 12, -5, 15);
            alterarRelacao($jogadores, $alvo, $meuNome, 8, -3, 10);
            $evento = "👑 $meuNome tentou se aproximar do líder $alvo.";
        break;
    }

    return $evento;
}

/* =====================================================
   STATUS
===================================================== */

function limparStatus(&$jogadores){

    foreach($jogadores as &$j){
        $j['status']['lider'] = false;
        $j['status']['anjo'] = false;
        $j['status']['imune'] = false;
    }
}

/* =====================================================
   PROVA DO LÍDER AUTOMÁTICA
===================================================== */

function provaLider(&$jogadores){

    $melhorIndex = null;
    $maiorScore = -999;

    foreach($jogadores as $i=>$j){

        $score =
            rand(1,100) +
            ($j['popularidade'] * 0.15) +
            ($j['humor'] * 0.10);

        if($j['personalidade'] == "Líder Nato"){
            $score += 15;
        }

        if($score > $maiorScore){
            $maiorScore = $score;
            $melhorIndex = $i;
        }
    }

    $jogadores[$melhorIndex]['status']['lider'] = true;

    return $jogadores[$melhorIndex];
}

/* =====================================================
   PROVA DO ANJO AUTOMÁTICA
===================================================== */

function provaAnjo(&$jogadores){

    $possiveis = [];

    foreach($jogadores as $j){
        if(empty($j['status']['lider'])){
            $possiveis[] = $j;
        }
    }

    $anjo = $possiveis[array_rand($possiveis)];

    foreach($jogadores as &$j){
        if($j['nome'] == $anjo['nome']){
            $j['status']['anjo'] = true;
        }
    }

    return $anjo;
}

function imunizar(&$jogadores, $anjo){

    $possiveis = [];

    foreach($jogadores as $j){
        if(
            $j['nome'] != $anjo['nome'] &&
            empty($j['status']['lider'])
        ){
            $possiveis[] = $j;
        }
    }

    $imune = $possiveis[array_rand($possiveis)];

    foreach($jogadores as &$j){
        if($j['nome'] == $imune['nome']){
            $j['status']['imune'] = true;
        }
    }

    return $imune;
}

/* =====================================================
   EVENTOS SOCIAIS
===================================================== */

function eventoSocial(&$jogadores){

    $aIndex = array_rand($jogadores);
    $bIndex = array_rand($jogadores);

    while($aIndex == $bIndex){
        $bIndex = array_rand($jogadores);
    }

    $a = $jogadores[$aIndex];
    $b = $jogadores[$bIndex];

    $eventos = [];

    $tipo = rand(1,6);

    if($tipo == 1){
        alterarRelacao($jogadores, $a['nome'], $b['nome'], 12, -5, 10);
        alterarRelacao($jogadores, $b['nome'], $a['nome'], 12, -5, 10);
        $eventos[] = "🤝 {$a['nome']} e {$b['nome']} criaram uma aproximação forte.";
    }

    if($tipo == 2){
        alterarRelacao($jogadores, $a['nome'], $b['nome'], -15, 20, -10);
        alterarRelacao($jogadores, $b['nome'], $a['nome'], -15, 20, -10);
        $eventos[] = "🔥 {$a['nome']} e {$b['nome']} bateram boca na casa.";
    }

    if($tipo == 3){
        alterarRelacao($jogadores, $a['nome'], $b['nome'], -8, 12, -20);
        $eventos[] = "🗣️ {$a['nome']} espalhou uma fofoca sobre {$b['nome']}.";
    }

    if($tipo == 4){
        alterarRelacao($jogadores, $a['nome'], $b['nome'], 15, -8, 18);
        $eventos[] = "💬 {$a['nome']} chamou {$b['nome']} para conversar e resolver pendências.";
    }

    if($tipo == 5){
        alterarRelacao($jogadores, $a['nome'], $b['nome'], -20, 25, -25);
        $eventos[] = "🐍 {$a['nome']} traiu a confiança de {$b['nome']}.";
    }

    if($tipo == 6){
        alterarRelacao($jogadores, $a['nome'], $b['nome'], 10, -5, 12);
        $eventos[] = "👀 {$a['nome']} e {$b['nome']} começaram uma possível aliança.";
    }

    return $eventos;
}

function festa(&$jogadores){

    $eventos = [];

    for($i=0;$i<2;$i++){
        $eventos = array_merge($eventos, eventoSocial($jogadores));
    }

    return $eventos;
}

function jogoDiscordia(&$jogadores){

    $aIndex = array_rand($jogadores);
    $bIndex = array_rand($jogadores);

    while($aIndex == $bIndex){
        $bIndex = array_rand($jogadores);
    }

    $a = $jogadores[$aIndex];
    $b = $jogadores[$bIndex];

    alterarRelacao($jogadores, $a['nome'], $b['nome'], -18, 25, -15);
    alterarRelacao($jogadores, $b['nome'], $a['nome'], -12, 18, -10);

    return "🔥 No Sincerão, {$a['nome']} apontou {$b['nome']} como falso no jogo.";
}

/* =====================================================
   VOTO INTELIGENTE
===================================================== */

function escolherVoto($jogador, $jogadores){

    $perfil = getPerfilPersonalidade($jogador['personalidade']);

    $melhorAlvo = null;
    $maiorScore = -9999;

    foreach($jogadores as $outro){

        if($outro['nome'] == $jogador['nome']) continue;
        if(!empty($outro['status']['lider'])) continue;
        if(!empty($outro['status']['imune'])) continue;

        $rel = $jogador['relacoes'][$outro['nome']] ?? [
            "amizade"=>0,
            "rivalidade"=>0,
            "confianca"=>0
        ];

        $score =
            ($rel['rivalidade'] * $perfil['rivalidade']) -
            ($rel['amizade'] * $perfil['amizade']) -
            ($rel['confianca'] * 0.2) +
            ((100 - $outro['popularidade']) * 0.15) +
            (rand(0,40) * $perfil['aleatorio']);

        if($jogador['personalidade'] == "Estrategista"){
            $score += (100 - $outro['popularidade']) * 0.25;
        }

        if($jogador['personalidade'] == "Falso"){
            $score += rand(0,20);
        }

        if($score > $maiorScore){
            $maiorScore = $score;
            $melhorAlvo = $outro;
        }
    }

    return $melhorAlvo;
}

function indicacaoLider($lider, $jogadores){
    return escolherVoto($lider, $jogadores);
}

function votacaoCasa($jogadores){

    $votos = [];

    foreach($jogadores as $j){

        if(!empty($j['status']['lider'])) continue;

        $alvo = escolherVoto($j, $jogadores);

        if($alvo){
            $nome = $alvo['nome'];
            $votos[$nome] = ($votos[$nome] ?? 0) + 1;
        }
    }

    arsort($votos);

    return [
        "votos"=>$votos,
        "maisVotado"=>array_key_first($votos)
    ];
}

/* =====================================================
   PAREDÃO
===================================================== */

function formarParedao($indicadoLider, $maisVotado, $jogadores){

    $paredao = [];

    if($indicadoLider){
        $paredao[] = $indicadoLider['nome'];
    }

    if($maisVotado){
        $paredao[] = $maisVotado;
    }

    $possiveis = [];

    foreach($jogadores as $j){

        if(
            !in_array($j['nome'], $paredao) &&
            empty($j['status']['lider']) &&
            empty($j['status']['imune'])
        ){
            $possiveis[] = $j['nome'];
        }
    }

    if(count($possiveis) > 0){
        $paredao[] = $possiveis[array_rand($possiveis)];
    }

    return array_values(array_unique($paredao));
}

/* =====================================================
   ELIMINAÇÃO INTELIGENTE
===================================================== */

function calcularRejeicao($jogador){

    $rejeicao = 100 - ($jogador['popularidade'] ?? 50);

    if($jogador['personalidade'] == "Barraqueiro"){
        $rejeicao += rand(5,15);
    }

    if($jogador['personalidade'] == "Planta"){
        $rejeicao += rand(5,12);
    }

    if($jogador['personalidade'] == "Fofo"){
        $rejeicao -= rand(5,15);
    }

    if($jogador['personalidade'] == "Manipulador"){
        $rejeicao += rand(0,10);
    }

    return limitar($rejeicao, 1, 99);
}

function eliminarJogador(&$jogadores, $paredao){

    $maiorRejeicao = -1;
    $eliminado = null;

    foreach($jogadores as $j){

        if(in_array($j['nome'], $paredao)){

            $rejeicao = calcularRejeicao($j) + rand(0,15);

            if($rejeicao > $maiorRejeicao){
                $maiorRejeicao = $rejeicao;
                $eliminado = $j['nome'];
            }
        }
    }

    foreach($jogadores as $k=>$j){
        if($j['nome'] == $eliminado){
            unset($jogadores[$k]);
            break;
        }
    }

    return $eliminado;
}

/* =====================================================
   CICLO SEMANAL
===================================================== */

function cicloDoJogo($jogadores){

    prepararRelacoesIniciais($jogadores);
    limparStatus($jogadores);

    $lider = provaLider($jogadores);
    $anjo  = provaAnjo($jogadores);
    $imune = imunizar($jogadores, $anjo);

    $eventosFesta = festa($jogadores);
    $discordia = jogoDiscordia($jogadores);

    $indicado = indicacaoLider($lider, $jogadores);
    $votacao  = votacaoCasa($jogadores);

    $paredao = formarParedao(
        $indicado,
        $votacao['maisVotado'],
        $jogadores
    );

    return [
        "jogadores"=>array_values($jogadores),
        "lider"=>$lider['nome'],
        "anjo"=>$anjo['nome'],
        "imune"=>$imune['nome'],
        "festa"=>$eventosFesta,
        "discordia"=>$discordia,
        "paredao"=>$paredao,
        "votos"=>$votacao['votos']
    ];
}
?>