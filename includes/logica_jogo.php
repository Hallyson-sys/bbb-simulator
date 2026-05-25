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

        /* Sistema de alianças: garante compatibilidade com saves antigos */
        if(!array_key_exists('alianca', $j)){
            $j['alianca'] = null;
        }

        if(!isset($j['historico_aliancas']) || !is_array($j['historico_aliancas'])){
            $j['historico_aliancas'] = [];
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
   SISTEMA DE ALIANÇAS
   - Cria grupos automaticamente com base em amizade/confiança.
   - Rompe alianças quando a rivalidade sobe.
   - Influencia voto inteligente e eventos sociais.
===================================================== */

function nomesBaseAliancas(){
    return [
        "Fadas", "Camarote", "Pipoca Raiz", "Quarto Céu",
        "Quarto Mar", "Os Visionários", "Panelinha VIP",
        "Os Protagonistas", "Baile da Xepa", "Equipe Eclipse",
        "Laços Fortes", "Modo Turbo", "Tribo do Jogo"
    ];
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

function indiceJogadorPorNome($jogadores, $nome){
    foreach($jogadores as $i=>$j){
        if(($j['nome'] ?? '') == $nome){
            return $i;
        }
    }

    return null;
}

function obterAliancaJogador($jogadores, $nome){
    foreach($jogadores as $j){
        if(($j['nome'] ?? '') == $nome){
            return $j['alianca'] ?? null;
        }
    }

    return null;
}

function mesmaAlianca($jogadorA, $jogadorB){
    return (
        !empty($jogadorA['alianca']) &&
        !empty($jogadorB['alianca']) &&
        $jogadorA['alianca'] == $jogadorB['alianca']
    );
}

function membrosDaAlianca($jogadores, $alianca){
    $membros = [];

    foreach($jogadores as $j){
        if(($j['alianca'] ?? null) == $alianca){
            $membros[] = $j['nome'];
        }
    }

    return $membros;
}

function tamanhoAlianca($jogadores, $alianca){
    return count(membrosDaAlianca($jogadores, $alianca));
}

function registrarHistoricoAlianca(&$jogadores, $nome, $mensagem){
    foreach($jogadores as &$j){
        if(($j['nome'] ?? '') == $nome){
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
    if($nomeA == '' || $nomeB == '' || $nomeA == $nomeB) return "";

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

    alterarRelacao($jogadores, $nomeA, $nomeB, 8, -4, 10);
    alterarRelacao($jogadores, $nomeB, $nomeA, 8, -4, 10);

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
        if(($membro['nome'] ?? '') != $nome && ($membro['alianca'] ?? null) == $alianca){
            alterarRelacao($jogadores, $nome, $membro['nome'], 5, -2, 6);
            alterarRelacao($jogadores, $membro['nome'], $nome, 4, -2, 5);
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
        if(($membro['nome'] ?? '') != $nome && ($membro['alianca'] ?? null) == $alianca){
            alterarRelacao($jogadores, $nome, $membro['nome'], -8, 8, -10);
            alterarRelacao($jogadores, $membro['nome'], $nome, -6, 6, -8);
        }
    }

    return "💥 $nome rompeu com a aliança <b>$alianca</b>: $motivo.";
}

function relacaoMediaComAlianca($jogadores, $nome, $alianca){
    $total = 0;
    $qtd = 0;

    foreach($jogadores as $membro){
        if(($membro['nome'] ?? '') == $nome) continue;
        if(($membro['alianca'] ?? null) != $alianca) continue;

        $rel = getRelacaoEntre($jogadores, $nome, $membro['nome']);
        $score = ($rel['amizade'] ?? 0) + (($rel['confianca'] ?? 0) * 0.7) - (($rel['rivalidade'] ?? 0) * 1.2);

        $total += $score;
        $qtd++;
    }

    if($qtd == 0) return 0;

    return $total / $qtd;
}

function atualizarAliancasAutomaticas(&$jogadores){
    prepararRelacoesIniciais($jogadores);

    $eventos = [];

    /* 1) Rompimentos: se alguém está muito mal com o próprio grupo, sai. */
    foreach($jogadores as $j){
        $nome = $j['nome'] ?? '';
        $alianca = $j['alianca'] ?? null;

        if($nome == '' || empty($alianca)) continue;

        $media = relacaoMediaComAlianca($jogadores, $nome, $alianca);

        if($media < 8 && rand(1,100) <= 45){
            $ev = romperAlianca($jogadores, $nome, "a relação com o grupo ficou muito desgastada");
            if($ev != '') $eventos[] = $ev;
        }
    }

    /* 2) Entrada em alianças: participantes sem grupo procuram pessoas confiáveis. */
    foreach($jogadores as $j){
        $nome = $j['nome'] ?? '';
        if($nome == '' || !empty($j['alianca'])) continue;

        $melhorAlianca = null;
        $melhorScore = -999;

        foreach($jogadores as $outro){
            if(($outro['nome'] ?? '') == $nome) continue;
            if(empty($outro['alianca'])) continue;

            $rel = getRelacaoEntre($jogadores, $nome, $outro['nome']);
            $score = ($rel['amizade'] ?? 0) + ($rel['confianca'] ?? 0) - (($rel['rivalidade'] ?? 0) * 1.5);

            if($score > $melhorScore){
                $melhorScore = $score;
                $melhorAlianca = $outro['alianca'];
            }
        }

        if($melhorAlianca != null && $melhorScore >= 90 && tamanhoAlianca($jogadores, $melhorAlianca) < 5 && rand(1,100) <= 45){
            $ev = entrarEmAlianca($jogadores, $nome, $melhorAlianca);
            if($ev != '') $eventos[] = $ev;
        }
    }

    /* 3) Criação de novas alianças: duplas com alta amizade/confiança criam grupo. */
    for($i = 0; $i < count($jogadores); $i++){
        for($k = $i + 1; $k < count($jogadores); $k++){

            $a = $jogadores[$i];
            $b = $jogadores[$k];

            if(!empty($a['alianca']) || !empty($b['alianca'])) continue;

            $relAB = getRelacaoEntre($jogadores, $a['nome'], $b['nome']);
            $relBA = getRelacaoEntre($jogadores, $b['nome'], $a['nome']);

            $score = 
                ($relAB['amizade'] ?? 0) +
                ($relAB['confianca'] ?? 0) +
                ($relBA['amizade'] ?? 0) +
                ($relBA['confianca'] ?? 0) -
                (($relAB['rivalidade'] ?? 0) + ($relBA['rivalidade'] ?? 0));

            if($score >= 170 && rand(1,100) <= 35){
                $ev = criarAliancaEntre($jogadores, $a['nome'], $b['nome']);
                if($ev != '') $eventos[] = $ev;
                break 2;
            }
        }
    }

    return $eventos;
}

function escolherAlvoDoGrupo($jogadores, $alianca){
    if(empty($alianca)) return null;

    $pontuacao = [];

    foreach($jogadores as $membro){
        if(($membro['alianca'] ?? null) != $alianca) continue;

        foreach($jogadores as $alvo){
            if(($alvo['alianca'] ?? null) == $alianca) continue;
            if(($alvo['nome'] ?? '') == ($membro['nome'] ?? '')) continue;
            if(!empty($alvo['status']['lider'])) continue;
            if(!empty($alvo['status']['imune'])) continue;

            $rel = getRelacaoEntre($jogadores, $membro['nome'], $alvo['nome']);
            $score = ($rel['rivalidade'] ?? 0) + (100 - ($rel['amizade'] ?? 0)) + rand(0,10);

            $pontuacao[$alvo['nome']] = ($pontuacao[$alvo['nome']] ?? 0) + $score;
        }
    }

    if(empty($pontuacao)) return null;

    arsort($pontuacao);

    return array_key_first($pontuacao);
}

function eventoAliancaSocial(&$jogadores){
    $eventos = atualizarAliancasAutomaticas($jogadores);

    if(!empty($eventos)){
        return $eventos;
    }

    return [];
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

    $tipo = rand(1,7);

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

    if($tipo == 7){
        alterarRelacao($jogadores, $a['nome'], $b['nome'], 12, -6, 14);
        alterarRelacao($jogadores, $b['nome'], $a['nome'], 10, -4, 12);

        $eventoAlianca = criarAliancaEntre($jogadores, $a['nome'], $b['nome']);

        if($eventoAlianca != ""){
            $eventos[] = $eventoAlianca;
        }else{
            $eventos[] = "🤝 {$a['nome']} e {$b['nome']} reforçaram uma parceria estratégica.";
        }
    }

    $eventos = array_merge($eventos, eventoAliancaSocial($jogadores));

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

        /* Alianças protegem membros do mesmo grupo */
        if(mesmaAlianca($jogador, $outro)){
            $score -= 80;
        }

        /* Grupos podem mirar juntos em um alvo comum */
        if(!empty($jogador['alianca'])){
            $alvoDoGrupo = escolherAlvoDoGrupo($jogadores, $jogador['alianca']);

            if($alvoDoGrupo == ($outro['nome'] ?? '')){
                $score += 25;
            }
        }

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

    $eventosAliancas = atualizarAliancasAutomaticas($jogadores);
    $eventosFesta = festa($jogadores);
    $eventosAliancas = array_merge($eventosAliancas, atualizarAliancasAutomaticas($jogadores));
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
        "aliancas"=>$eventosAliancas,
        "festa"=>$eventosFesta,
        "discordia"=>$discordia,
        "paredao"=>$paredao,
        "votos"=>$votacao['votos']
    ];
}
?>
