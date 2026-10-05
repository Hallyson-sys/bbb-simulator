<?php
/* 🎬 EVENTOS DE CONVIVÊNCIA — cenas condicionadas por relação e contexto. */

function ecDadosParticipante($jogadores, $nome) {
    foreach ($jogadores as $j) if (($j['nome'] ?? '') === $nome) return $j;
    return [];
}
function ecFaixaRelacao($valor) {
    if ($valor >= 55) return 'muito_alta';
    if ($valor >= 20) return 'alta';
    if ($valor <= -55) return 'muito_baixa';
    if ($valor <= -20) return 'baixa';
    return 'neutra';
}
function ecBiblioteca() {
    return [
      'muito_alta' => [
        ['confidencia','🤫','Uma confidência só para você','QUARTO','{npc} espera a casa esvaziar e fala baixo: “Eu confio em você. Se eu tiver poder, você não é meu alvo.”', [['🤝 Pode contar comigo',10,7,0],['🎯 Perguntar quem é o alvo',4,3,0],['🤐 Não prometer nada',-2,0,0]]],
        ['pacto','🤝','Pacto até mais longe','ACADEMIA','{npc} propõe que vocês se protejam nas próximas semanas e troquem informações antes das votações.', [['🤝 Fechar o pacto',12,8,0],['🧠 Aceitar, mas sem prometer voto',5,2,0],['🚪 Recusar com cuidado',-6,-2,0]]],
        ['protecao','🛡️','Uma promessa de proteção','COZINHA','{npc} diz que, se ganhar poder, pretende proteger você. Em troca, quer saber se a proteção é recíproca.', [['❤️ Prometer reciprocidade',9,6,0],['🎯 Dizer que depende da semana',2,0,0],['🧊 Evitar compromisso',-5,-2,0]]],
      ],
      'alta' => [
        ['sondagem','🧠','Hora de falar de jogo','VARANDA','{npc} se aproxima: “Quero entender seu jogo. Quem você acha que está se tornando perigoso aqui dentro?”', [['🎯 Abrir parte do seu jogo',7,4,0],['🤐 Ser diplomático',2,1,0],['🔒 Não entregar informação',-3,-1,0]]],
        ['apoio','💙','Um conselho inesperado','SALA','{npc} percebe você pensativo(a) e diz: “Não deixa essa semana te desmontar. Você está melhor no jogo do que imagina.”', [['😊 Agradecer sinceramente',8,4,0],['🤝 Aproveitar para falar de estratégia',5,3,0],['😶 Mudar de assunto',-2,0,0]]],
        ['voto','🗳️','Conversa sobre voto','QUARTO','{npc} pergunta discretamente se vocês deveriam alinhar o voto para não desperdiçar força na casa.', [['🤝 Topar conversar sobre nomes',7,5,0],['🧠 Ouvir sem se comprometer',3,1,0],['🚫 Dizer que vota sozinho(a)',-5,-2,0]]],
      ],
      'neutra' => [
        ['aproximacao','👀','Uma chance de aproximação','COZINHA','{npc} fica sozinho(a) com você por alguns minutos. É uma oportunidade rara de quebrar o gelo.', [['💬 Puxar uma conversa pessoal',7,3,0],['🎮 Falar apenas do jogo',3,1,0],['🚶 Deixar o momento passar',0,0,0]]],
        ['termometro','🌡️','Medindo o terreno','SALA','{npc} pergunta como você está enxergando a casa e observa atentamente sua resposta.', [['😊 Responder com sinceridade',5,2,0],['🧠 Dar uma resposta calculada',2,0,0],['🤐 Não revelar nada',-2,0,0]]],
        ['favor','🥤','Um pequeno gesto','COZINHA','{npc} pede sua ajuda com uma tarefa da casa. Parece simples, mas convivência também constrói relações.', [['🤝 Ajudar sem pensar duas vezes',6,3,0],['🙂 Ajudar, mas manter distância',3,1,0],['🙄 Dizer que está ocupado(a)',-5,-2,0]]],
      ],
      'baixa' => [
        ['indireta','😏','Uma indireta bem direta','COZINHA','{npc} comenta em voz alta que “tem gente que muda quando ganha poder” e olha diretamente para você.', [['💥 Perguntar se foi para você',-8,-2,2],['😏 Devolver outra indireta',-10,-3,3],['🧊 Ignorar completamente',1,0,0]]],
        ['cobranca','👀','Clima de desconfiança','SALA','{npc} te chama de lado: “Eu sinto que você fala uma coisa pra mim e outra para a casa.”', [['🗣️ Se explicar',3,2,0],['🔥 Rebater a acusação',-9,-3,2],['🚶 Encerrar a conversa',-4,-1,0]]],
        ['voto_tenso','🗳️','Seu nome entrou na conversa','ACADEMIA','{npc} admite que seu nome já apareceu em conversas de voto. O clima fica imediatamente pesado.', [['🧠 Perguntar quem falou',1,1,0],['🔥 Cobrar lealdade',-8,-3,2],['🤐 Guardar a informação',2,1,0]]],
      ],
      'muito_baixa' => [
        ['confronto','🔥','O clima finalmente explodiu','SALA','{npc} te confronta na frente de outras pessoas: “Você sabe muito bem por que eu não confio em você.”', [['💥 Bater de frente',-14,-5,4],['🧠 Responder sem perder a calma',-3,1,2],['🚶 Sair da discussão',0,0,-1]]],
        ['ameaca_voto','🎯','Alvo declarado','QUARTO','{npc} não tenta esconder: “Se depender de mim, seu nome vai aparecer no próximo Paredão.”', [['🔥 Dizer que o sentimento é recíproco',-12,-4,3],['😏 Mostrar que não está intimidado(a)',-6,-1,2],['🧊 Não alimentar a rivalidade',2,1,0]]],
        ['acerto','⚡','Uma última chance de conversar','VARANDA','Depois de dias de tensão, {npc} pergunta se ainda existe alguma chance de vocês baixarem as armas.', [['🤝 Tentar uma trégua',10,5,0],['🧠 Aceitar apenas por estratégia',4,1,0],['🚫 Recusar a trégua',-8,-3,1]]],
      ],
      'festa' => [
        ['pista','🥂','Papo no meio da pista','FESTA','{npc} te puxa para um canto entre uma música e outra: “Hoje eu queria esquecer o jogo, mas preciso saber se ainda posso confiar em você.”', [['❤️ Reafirmar a confiança',9,5,0],['🙂 Manter o papo leve',4,1,0],['🎯 Transformar em conversa de jogo',2,2,0]]],
        ['danca','💃','Um convite inesperado','PISTA','{npc} estende a mão e chama você para dançar. Algumas pessoas da casa percebem a aproximação.', [['💃 Aceitar e curtir',8,3,1],['😊 Dançar sem criar expectativas',4,1,0],['🙈 Recusar gentilmente',-3,0,0]]],
        ['festa_treta','🍹','Festa, bebida e tensão','PISTA','Uma provocação de {npc} atravessa a música. O assunto é jogo e claramente foi direcionado a você.', [['🔥 Responder na hora',-10,-3,4],['🧠 Cortar com elegância',-2,1,2],['🚶 Ir para outro canto',1,0,0]]],
        ['segredo','👂','Você ouviu seu nome...','QUARTO','Ao passar pelo quarto, você escuta {npc} falando seu nome em uma conversa estratégica. A pessoa ainda não percebeu que você está por perto.', [['🤫 Continuar ouvindo',0,2,0],['🚪 Sair discretamente',1,0,0],['😳 Entrar e perguntar o que houve',-5,-2,2]]],
        ['madrugada','🌙','Conversa de madrugada','VARANDA','A festa já desacelerou quando {npc} senta ao seu lado: “Aqui dentro é difícil saber o que é jogo e o que é de verdade.”', [['❤️ Falar com sinceridade',10,5,0],['🤝 Falar sobre confiança',6,4,0],['😶 Apenas ouvir',3,2,0]]],
      ],
    ];
}
function ecTalvezGerar(&$jogadores, $meuNome, $fase) {
    if (!empty($_SESSION['evento_convivencia_ativo'])) return;
    if (!preg_match('/^interacoes_/', $fase) && $fase !== 'festa') return;
    $chave = 'ec_tentado_' . $fase . '_' . ($_SESSION['rodada'] ?? 1);
    if (!empty($_SESSION[$chave])) return;
    $_SESSION[$chave] = true;
    $chance = $fase === 'festa' ? 72 : 42;
    if (random_int(1,100) > $chance) return;
    $candidatos=[];
    foreach ($jogadores as $j) {
        $n=$j['nome']??''; if (!$n || $n===$meuNome || !empty($j['eliminado'])) continue;
        $rel=(int)($_SESSION['relacoes_jogador'][$n]??0);
        $peso=max(2, abs($rel)+15);
        $candidatos[]=[$n,$rel,$peso];
    }
    if (!$candidatos) return;
    $total=array_sum(array_column($candidatos,2)); $r=random_int(1,max(1,$total)); $escolha=$candidatos[0];
    foreach($candidatos as $c){$r-=$c[2]; if($r<=0){$escolha=$c;break;}}
    [$npc,$rel]=$escolha; $lib=ecBiblioteca();
    $grupo = $fase==='festa' ? 'festa' : ecFaixaRelacao($rel);
    if ($fase==='festa' && $rel <= -25 && random_int(1,100)<=55) $evento=$lib['festa'][2];
    else $evento=$lib[$grupo][array_rand($lib[$grupo])];
    $_SESSION['evento_convivencia_ativo']=[
      'id'=>$evento[0],'icone'=>$evento[1],'titulo'=>$evento[2],'camera'=>$evento[3],
      'texto'=>str_replace('{npc}',$npc,$evento[4]),'opcoes'=>$evento[5],
      'npc'=>$npc,'fase'=>$fase,'relacao_antes'=>$rel
    ];
}
function ecProcessarEscolha(&$jogadores,$meuNome,$indice) {
    $e=$_SESSION['evento_convivencia_ativo']??null; if(!$e) return;
    $op=$e['opcoes'][$indice]??null; if(!$op) return;
    $npc=$e['npc']; $delta=(int)$op[1]; $conf=(int)$op[2]; $pop=(int)$op[3];
    ajustarRelacaoJogador($npc,$delta);
    alterarAfinidade($jogadores,$npc,$meuNome,$delta, $delta<0?abs((int)round($delta/2)):0, $conf);
    if ($pop && function_exists('alterarPopularidadePublica')) alterarPopularidadePublica($jogadores,$meuNome,$pop,$pop,"reagiu a um momento marcante da convivência",true);
    $novo=(int)($_SESSION['relacoes_jogador'][$npc]??0);
    $msg=$delta>=7?"$npc gostou muito da sua reação.":($delta>0?"O clima com $npc melhorou.":($delta<=-7?"A tensão com $npc aumentou.":($delta<0?"$npc não gostou muito da sua resposta.":"Você preferiu não mexer nessa relação.")));
    $_SESSION['evento_convivencia_resultado']=['titulo'=>'Consequência da sua escolha','texto'=>$msg,'icone'=>$delta<0?'⚡':'✨','npc'=>$npc,'fase'=>$e['fase']];
    unset($_SESSION['evento_convivencia_ativo']);
    $_SESSION['jogadores']=$jogadores;
}
