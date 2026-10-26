<?php
/* Convivência & Estratégia 2.0 — acordos por rodada, convites e falas variadas. */
function es2Esc($txt) { return htmlspecialchars((string)$txt, ENT_QUOTES, 'UTF-8'); }
function es2Rodada() { return (int)($_SESSION['rodada'] ?? 1); }
function es2Participante($jogadores, $nome, $excluir='') {
    foreach ($jogadores as $j) {
        if (($j['nome'] ?? '') === $nome && $nome !== $excluir && empty($j['eliminado'])) return $j;
    }
    return null;
}
function es2AcordoAtivo($npc) {
    $a = $_SESSION['es2_acordos_voto'][$npc] ?? null;
    return is_array($a) && ($a['rodada'] ?? 0) === es2Rodada() && !empty($a['alvo']) ? $a : null;
}
function es2BonusVoto($jogadores, $npc, $alvo, $contexto) {
    if ($contexto !== 'voto' && $contexto !== 'indicacao_lider') return 0;
    $a = es2AcordoAtivo($npc);
    if (!$a || ($a['alvo'] ?? '') !== $alvo) return 0;
    if (!es2Participante($jogadores, $alvo, $npc) || mesmaAliancaNomes($jogadores, $npc, $alvo)) return 0;
    $perfil = es2Participante($jogadores, $npc);
    $p = $perfil['personalidade'] ?? 'Neutro';
    $fator = in_array($p, ['Falso','Manipulador'], true) ? 0.48 : (in_array($p,['Estrategista','Líder Nato'],true) ? 1.2 : 1.0);
    return (float)($a['forca'] ?? 38) * $fator;
}
function es2ReacaoConvite($jogadores,$nome,$meuNome) {
    $chance = calcularChanceAceitarConviteAlianca($jogadores,$nome,$meuNome,$meuNome);
    $p=es2Participante($jogadores,$nome)['personalidade']??'Neutro';
    if (in_array($p,['Falso','Manipulador'],true)) $chance+=8;
    return random_int(1,100)<=max(6,min(92,$chance));
}
function es2RelatoConversa($jogadores, $meuNome, $alvo) {
    $rel = obterRelacaoCompleta($jogadores,$alvo,$meuNome,$meuNome);
    $amizade=(float)($rel['amizade']??0);
    $rival=(float)($rel['rivalidade']??0);
    $aliados=mesmaAliancaNomes($jogadores,$meuNome,$alvo);
    $locais=['à beira da piscina','na cozinha','no jardim','na varanda','perto da academia','no quarto','na sala'];
    $local=$locais[array_rand($locais)];
    if ($rival>35) $temas=['sobre a tensão após os últimos acontecimentos','sobre as indiretas que vêm surgindo','para esclarecer um mal-entendido','sobre o desgaste na convivência','sobre diferenças no jeito de jogar'];
    elseif ($aliados) $temas=['sobre como proteger os membros do grupo','sobre os próximos desafios da aliança','sobre quem merece confiança nesta semana','sobre manter o grupo unido','sobre como evitar um Paredão entre aliados'];
    elseif ($amizade>45) $temas=['sobre saudade de casa e os rumos do jogo','sobre as amizades que construíram','sobre quem pode surpreender na próxima prova','sobre a convivência dentro da casa','sobre as expectativas para a próxima festa'];
    else $temas=['sobre os últimos acontecimentos na casa','sobre como estão se adaptando à convivência','sobre a próxima prova','sobre o clima entre os participantes','sobre as surpresas desta semana'];
    $tema=$temas[array_rand($temas)];
    $inicio=["💬 $meuNome encontrou $alvo $local e os dois conversaram $tema.","🗣️ $meuNome puxou $alvo para um papo $local, $tema.","🏠 $meuNome e $alvo passaram alguns minutos $local, $tema."];
    $fim=$rival>35 ? ['O clima ficou cauteloso.','A conversa não apagou totalmente a tensão.','Nenhum dos dois quis se comprometer demais.'] : ($aliados?['Saíram com a sensação de que o grupo precisa se apoiar.','Reforçaram a importância da confiança.','O assunto ficou entre os dois.']:['A conversa ajudou a quebrar o gelo.','Os dois voltaram às atividades da casa.','Foi um momento tranquilo de convivência.']);
    // Evita repetir a mesma composição imediatamente.
    $historico=$_SESSION['es2_conversas_recentes']??[];
    $alternativas=[]; foreach($inicio as $a) foreach($fim as $b) $alternativas[]=$a.' '.$b;
    $disponiveis=array_values(array_diff($alternativas,$historico));
    $texto=($disponiveis?:$alternativas)[array_rand($disponiveis?:$alternativas)];
    $_SESSION['es2_conversas_recentes']=array_slice(array_merge($historico,[$texto]),-12);
    return $texto;
}
function es2SondarVoto($jogadores,$meuNome,$npc,$abordagem) {
    if (!es2Participante($jogadores,$npc,$meuNome)) return '';
    $rel=obterRelacaoCompleta($jogadores,$npc,$meuNome,$meuNome);
    $conf=(float)($rel['confianca']??0);
    $rival=(float)($rel['rivalidade']??0);
    $perfil=es2Participante($jogadores,$npc);
    $pers=$perfil['personalidade']??'Neutro';
    $chance=max(7,min(88,27+$conf*.42-$rival*.48+(mesmaAliancaNomes($jogadores,$npc,$meuNome)?23:0)+($abordagem==='troca'?8:0)-($abordagem==='direta'?6:0)));
    $decisao=random_int(1,100);
    $_SESSION['evento_extra'][]="👀 $meuNome e $npc conversaram discretamente sobre a formação do próximo Paredão durante a festa.";
    if ($decisao<=$chance) {
        // Intenção momentânea: não lê voto definitivo nem força decisão.
        $ranking=ranquearAlvosNPCInteligente($jogadores,$npc,'voto',[$meuNome]);
        $alvo=array_key_first($ranking);
        if ($alvo && !mesmaAliancaNomes($jogadores,$npc,$alvo)) {
            if (function_exists('ecRegistrarConhecimento')) ecRegistrarConhecimento('intencao_voto','🎯 Sondagem na festa',"$npc disse estar considerando $alvo como alvo nesta rodada.","Conversa particular com $npc",'media',es2Rodada(),['envolvido'=>$npc]);
            return "🗣️ $npc baixou a voz: “O nome de $alvo está passando pela minha cabeça, mas ainda posso mudar de ideia.” Você registrou essa intenção em O que eu sei.";
        }
    }
    if (in_array($pers,['Falso','Manipulador'],true) && random_int(1,100)<=50) return "🎭 $npc disse que ainda não decidiu em quem votar. Você não sabe se foi sinceridade ou estratégia.";
    return $rival>=30?"🧊 $npc desconversou: “Não tenho por que te contar meu voto agora.”":"🤐 $npc preferiu não se comprometer: “Ainda estou observando; não quero prometer nada.”";
}
function es2ConvidarMembros(&$jogadores,$meuNome,$nomes) {
    $grupo=obterAliancaJogador($jogadores,$meuNome);
    if (!$grupo) return '⚠️ Você precisa participar de uma aliança para convidar alguém.';
    $nomes=is_array($nomes)?array_values(array_unique($nomes)):[];
    $nomes=array_slice($nomes,0,5);
    if (!$nomes) return '⚠️ Escolha ao menos um participante.';
    $aceitos=[];$recusados=[];
    foreach($nomes as $nome){
        if(!es2Participante($jogadores,$nome,$meuNome)||obterAliancaJogador($jogadores,$nome)||tamanhoAlianca($jogadores,$grupo)>=5) continue;
        $chance=calcularChanceAceitarConviteAlianca($jogadores,$nome,$meuNome,$meuNome);
        $media=relacaoMediaComAlianca($jogadores,$nome,$grupo,$meuNome);
        $chance=max(5,min(91,$chance+($media-35)*.22));
        if(random_int(1,100)<=$chance){
            entrarEmAlianca($jogadores,$nome,$grupo);
            $aceitos[]=$nome;
            if(function_exists('registrarMemoriaSocialNPC'))registrarMemoriaSocialNPC($nome,$meuNome,'fechou_pacto_comigo',2,"$meuNome convidou $nome para a aliança $grupo.",'es2_convite|'.es2Rodada().'|'.$nome);
        }else { $recusados[]=$nome; alterarAfinidade($jogadores,$nome,$meuNome,-2,2,-3); }
    }
    $msg='📩 Você conversou sobre reforçar a aliança “'.es2Esc($grupo).'”.';
    if($aceitos)$msg.=' 🤝 Aceitaram: '.es2Esc(implode(', ',$aceitos)).'.';
    if($recusados)$msg.=' 🚫 Preferiram ficar de fora: '.es2Esc(implode(', ',$recusados)).'.';
    if(!$aceitos&&!$recusados)$msg.=' Não havia vagas ou candidatos disponíveis.';
    return $msg;
}
function es2IniciarNegociacao($jogadores,$meuNome,$convidados) {
    if(!empty($_SESSION['es2_negociacao']) && (int)($_SESSION['es2_negociacao']['rodada']??0)===es2Rodada()) return false;
    $convidados=is_array($convidados)?array_values(array_unique($convidados)):[];
    if(!$convidados||count($convidados)>3) return false;
    $aceitos=[];$recusados=[];
    foreach($convidados as $nome){
        if(!es2Participante($jogadores,$nome,$meuNome)) return false;
        if(es2ReacaoConvite($jogadores,$nome,$meuNome))$aceitos[]=$nome;
        else $recusados[]=$nome;
    }
    $_SESSION['es2_negociacao']=['rodada'=>es2Rodada(),'aceitos'=>$aceitos,'recusados'=>$recusados];
    return true;
}
function es2FinalizarNegociacao(&$jogadores,$meuNome,$alvo){
    $neg=$_SESSION['es2_negociacao']??null;
    if(!is_array($neg)||($neg['rodada']??0)!==es2Rodada())return '';
    if(!es2Participante($jogadores,$alvo,$meuNome))return '';
    if(in_array($alvo,$neg['aceitos'],true))return '';
    unset($_SESSION['es2_negociacao']);
    $respostas=[];
    foreach($neg['aceitos'] as $npc){
        $rel=obterRelacaoCompleta($jogadores,$npc,$alvo,$meuNome);
        $rival=(float)($rel['rivalidade']??0);
        $conf=(float)($rel['confianca']??0);
        $ch=max(6,min(90,44+$rival*.35-$conf*.3-(mesmaAliancaNomes($jogadores,$npc,$alvo)?65:0)+(mesmaAliancaNomes($jogadores,$npc,$meuNome)?16:0)));
        if(!mesmaAliancaNomes($jogadores,$npc,$alvo) && random_int(1,100)<=$ch) {
            $_SESSION['es2_acordos_voto'][$npc]=['alvo'=>$alvo,'rodada'=>es2Rodada(),'forca'=>random_int(42,65),'articulador'=>$meuNome];
            $respostas[]=es2Esc($npc).' concordou em considerar '.es2Esc($alvo).' como alvo.';
            if(function_exists('registrarMemoriaSocialNPC')) registrarMemoriaSocialNPC($npc,$meuNome,'fechou_pacto_comigo',1,"$meuNome combinou discutir um voto contra $alvo com $npc.",'es2_voto|'.es2Rodada().'|'.$npc.'|'.$alvo);
        }else{
            $alternativas=[];
            foreach($jogadores as $j){$n=$j['nome']??''; if($n&&$n!==$npc&&$n!==$meuNome&&$n!==$alvo&&empty($j['eliminado'])&&!mesmaAliancaNomes($jogadores,$npc,$n))$alternativas[$n]=pontuarAlvoNPCInteligente($jogadores,$npc,$n,'voto',[]);}
            arsort($alternativas);
            $sugestao=array_key_first($alternativas);
            $respostas[]=es2Esc($npc).' não topou esse nome'.($sugestao?' e sugeriu '.es2Esc($sugestao).' como alternativa.':'.');
        }
    }
    $texto='🗳️ Vocês discutiram possíveis votos na festa. '.implode(' ',$respostas);
    if(!$neg['aceitos'])$texto='🗳️ Ninguém aceitou participar da conversa estratégica desta vez.';
    $texto.=' Mesmo quem concordou ainda pode mudar de ideia até a votação.';
    $_SESSION['evento_extra'][]="🤫 $meuNome conversou com algumas pessoas sobre possíveis votos durante a festa.";
    return $texto;
}

/* Lê apenas o registro interno das cédulas. Não publica promessas cumpridas ou quebradas.
 * A verdade só pode ser revelada por mecânicas que autorizem ver votos. */
function es2RegistrarResultadoAcordos($votosDetalhados){
    if(!is_array($votosDetalhados)) return;
    $r=es2Rodada();
    foreach($votosDetalhados as $v){
        $npc=(string)($v['votante']??'');
        $a=es2AcordoAtivo($npc);
        if(!$a)continue;
        $_SESSION['es2_historico_acordos'][]=[
           'rodada'=>$r,'npc'=>$npc,'alvo_prometido'=>$a['alvo'],
           'cumpriu'=>($a['alvo']===(string)($v['voto']??'')),
           'privado'=>true
        ];
    }
    $_SESSION['es2_historico_acordos']=array_slice($_SESSION['es2_historico_acordos']??[],-30);
}
