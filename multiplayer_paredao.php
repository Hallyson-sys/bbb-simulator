<?php
/** BBB Simulator Multiplayer Beta 8: formação, desempate, Bate e Volta e eliminação simulada.
 * Estado e gabaritos ficam no servidor; somente dados filtrados chegam ao cliente.
 */
declare(strict_types=1);

function mp8_tamanho_paredao(int $ativos, bool $bateVolta): int {
    if ($ativos<=4) return min(3,max(1,$ativos-1));
    if ($ativos<=6) return 2;
    return $bateVolta ? 4 : 3;
}
function mp8_iniciar_paredao(array &$room): void {
    $ativos=count(mp_active($room));
    $bateVolta=$ativos>10 && random_int(1,100)<=45;
    $total=mp8_tamanho_paredao($ativos,$bateVolta);
    if(($room['surprise']['type']??null)==='paredao_especial' && $ativos>=8)$total=max($total,4);
    // Uma indicação adicional do Big Fone conta dentro das vagas antes do Bate e Volta.
    $room['paredaoPlano']=['tamanho'=>$total,'bateVolta'=>$bateVolta,'semana'=>$room['week']];
    $room['leaderPick']=null;
    $room['nominees']=[];
    $room['tieState']=null;
    $room['voteSummary']=null;
    $room['bateVolta']=null;
    $room['eliminacaoSimulada']=null;
    mp_log($room,'🔥 A formação do Paredão começou. O Líder indica e os demais votam no Confessionário. Os votos ficam secretos até a apuração.','paredao',['title'=>'🔥 Formação do Paredão']);
}
function mp8_candidatos_voto(array $room,string $votante): array {
    return array_values(array_filter(mp_active($room),static fn($p)=>$p['id']!==$votante && $p['id']!==($room['leader']??null) && $p['id']!==($room['leaderPick']??null) && $p['id']!==($room['extraNominee']??null) && !mp10_is_immune($room,$p['id'])));
}
function mp8_fechar_acordos(array &$room): void {
    foreach($room['voteAgreements']??[] as &$agree){
        if(($agree['week']??null)!==$room['week']||($agree['status']??'')!=='firmado')continue;
        $votos=[];
        foreach($room['players'] as $p)if(in_array($p['id'],[$agree['a'],$agree['b']],true))$votos[$p['id']]=$p['choice']['vote']??null;
        $cumprido=count($votos)===2 && ($votos[$agree['a']]??null)===$agree['target'] && ($votos[$agree['b']]??null)===$agree['target'];
        $agree['status']=$cumprido?'cumprido':'descumprido';
        mp_private_log($room,[$agree['a'],$agree['b']],$cumprido?'🤝 O acordo de voto foi cumprido.':'⚠️ O acordo não foi cumprido por todos. Os votos individuais permanecem secretos.');
    }unset($agree);
}
function mp8_apurar_votos(array &$room): void {
    $leader=$room['leader']??null;
    $eligible=mp8_candidatos_voto($room,$leader??'');
    if(!$room['leaderPick'] || !mp_find_player($room,$room['leaderPick']) || mp10_is_immune($room,(string)$room['leaderPick']) || $room['leaderPick']===$leader){
        $choices=array_values(array_filter(mp_active($room),static fn($p)=>!mp10_is_immune($room,$p['id']) && $p['id']!==($room['extraNominee']??null)));
        if(!$choices)mp_error('Não foi possível encontrar indicado elegível.',409);
        $room['leaderPick']=$choices[random_int(0,count($choices)-1)]['id'];
        mp_log($room,'⏳ O tempo do Líder acabou. A produção definiu automaticamente a indicação de '.(mp_find_player($room,$room['leaderPick'])['name']??'um participante').'.','paredao',['title'=>'👑 Indicação automática']);
    }
    $counts=[];$voters=0;$abstained=0;$annulled=0;
    foreach(mp_active($room) as $p){
        if($p['id']===$leader)continue;
        $vote=$p['choice']['vote']??null;
        $allowed=array_column(mp8_candidatos_voto($room,$p['id']),'id');
        if($vote!==null && in_array($vote,$allowed,true) && !mp10_vote_cancelled($room,$p['id'])){
            $weight=mp10_vote_weight($room,$p['id']);$counts[$vote]=($counts[$vote]??0)+$weight;$voters++;
        }
        elseif($vote!==null && in_array($vote,$allowed,true) && mp10_vote_cancelled($room,$p['id']))$annulled++;
        else $abstained++;
    }
    mp8_fechar_acordos($room);
    mp10_vote_audit($room);
    $eligible=array_values(array_filter(mp_active($room),static fn($p)=>!mp10_is_immune($room,$p['id']) && $p['id']!==$room['leaderPick'] && $p['id']!==($room['extraNominee']??null)));
    foreach($eligible as $p)$counts[$p['id']]??=0;
    $room['voteSummary']=['week'=>$room['week'],'counts'=>$counts,'total'=>$voters,'abstentions'=>$abstained,'annulled'=>$annulled,'leaderPick'=>$room['leaderPick']];
    $room['tieState']=['votes'=>$counts,'chosen'=>array_values(array_unique(array_filter([$room['leaderPick'],$room['extraNominee']??null]))),'needs'=>max(0,($room['paredaoPlano']['tamanho']??3)-1),'options'=>[]];
    mp_log($room,'🗳️ A casa encerrou a votação com '.$voters.' votos válidos, '.$abstained.' abstenções e '.$annulled.' votos anulados. A apuração foi concluída, sem revelar quem votou em quem.','paredao',['title'=>'🗳️ Confessionário encerrado']);
    mp8_continuar_formacao($room);
}
function mp8_continuar_formacao(array &$room): void {
    for($guard=0;$guard<20;$guard++){
        $state=&$room['tieState'];
        if(count($state['chosen'])-1 >= $state['needs']){unset($state);mp8_fechar_formacao($room);return;}
        $remaining=[];
        foreach($state['votes'] as $id=>$count){
            $id=(string)$id;
            if(!in_array($id,$state['chosen'],true) && mp_find_player($room,$id) && !mp10_is_immune($room,$id))$remaining[$id]=(int)$count;
        }
        if(!$remaining){unset($state);mp8_fechar_formacao($room);return;}
        $max=max($remaining);$tied=[];
        foreach($remaining as $id=>$count)if($count===$max)$tied[]=(string)$id;
        if(count($tied)>1){
            $state['options']=$tied;
            $room['phase']='desempate';$room['endsAt']=time()+90;
            foreach($room['players'] as &$p)$p['done']=$p['id']!==($room['leader']??null);
            unset($p,$state);
            mp_log($room,'⚖️ Houve empate na votação. O Líder decidirá quem ocupa a próxima vaga do Paredão.','paredao',['title'=>'⚖️ Desempate do Líder']);
            return;
        }
        $state['chosen'][]=$tied[0];unset($state);
    }
    mp_error('Erro ao concluir a votação.',500);
}
function mp8_desempatar(array &$room, ?string $chosen): void {
    $options=$room['tieState']['options']??[];
    if(!$options)mp_error('Não há empate pendente.',409);
    if($chosen===null){$chosen=$options[random_int(0,count($options)-1)];$reason='(tempo esgotado: sorteio entre empatados)';}
    else {if(!in_array($chosen,$options,true))mp_error('Escolha um dos participantes empatados.',400);$reason='(escolha do Líder)';}
    $room['tieState']['chosen'][]=$chosen;$room['tieState']['options']=[];
    mp_log($room,'⚖️ '.(mp_find_player($room,$chosen,false)['name']??'Participante').' foi selecionado no desempate '.$reason.'.','paredao',['title'=>'⚖️ Desempate resolvido']);
    mp8_continuar_formacao($room);
}
function mp8_fechar_formacao(array &$room): void {
    $ids=array_values(array_unique($room['tieState']['chosen']??[]));
    $room['tieState']=null;$room['nominees']=$ids;
    $names=array_map(static fn($id)=>mp_find_player($room,$id,false)['name']??'Participante',$ids);
    mp_log($room,'🔥 Formação concluída: '.implode(' × ',$names).'. Confira a tabela de votos sem identificação dos eleitores.','paredao',['title'=>'🔥 Paredão formado']);
    mp10_after_formation($room);
}
function mp8_criar_bate_volta(array &$room): void {
    $eligible=array_values(array_filter($room['nominees'],static fn($id)=>$id!==($room['leaderPick']??null)));
    $types=['portas','urna','dado'];$type=$types[random_int(0,2)];
    $room['bateVolta']=['type'=>$type,'secret'=>random_int(1,6),'participants'=>$eligible,'answers'=>[]];
    mp_log($room,'🚗 Prova Bate e Volta! O indicado do Líder não participa. Os demais escolhem uma opção entre 1 e 6.','paredao',['title'=>'🚗 Bate e Volta']);
}
function mp8_fechar_bate_volta(array &$room): void {
    $state=$room['bateVolta']??[];$eligible=$state['participants']??[];
    if(!$eligible){mp_set_phase($room,'eliminacao');return;}
    $scores=[];
    foreach($eligible as $id){
        $choice=$state['answers'][$id]??null;
        $scores[$id]=$choice===null?-1:6-abs((int)$choice-(int)$state['secret']);
    }
    $max=max($scores);$winners=array_keys(array_filter($scores,static fn($n)=>$n===$max));
    $winner=(string)$winners[random_int(0,count($winners)-1)];
    $room['lastBateVolta']=['week'=>$room['week'],'type'=>$state['type'],'winner'=>$winner,'secret'=>$state['secret'],'choices'=>$state['answers'],'draw'=>count($winners)>1];
    $room['nominees']=array_values(array_filter($room['nominees'],static fn($id)=>$id!==$winner));
    mp_log($room,'🚗 '.(mp_find_player($room,$winner,false)['name']??'Participante').' venceu o Bate e Volta e escapou do Paredão. A escolha secreta era '.$state['secret'].'.','paredao',['title'=>'🏆 Resultado do Bate e Volta']);
    mp_set_phase($room,'eliminacao');
}
function mp8_preparar_eliminacao(array &$room): void {
    $nominees=array_values(array_filter($room['nominees'],static fn($id)=>mp_find_player($room,$id)!==null));
    // A criação dos mutirões precede o cálculo; efeito pequeno, sem vazamento de votos.
    if(function_exists('mp9_sync'))mp9_sync($room);
    $raw=[];
    foreach($nominees as $id){
        $relations=[];
        foreach($room['relations']??[] as $key=>$value)if(in_array($id,explode(':',(string)$key),true))$relations[]=(int)$value;
        $avg=$relations?array_sum($relations)/count($relations):0;
        $raw[$id]=max(8,min(92,50-$avg*.55-((int)($room['feedBBB']['popularity'][$id]??50)-50)*.12+random_int(-18,18)));
    }
    if(!$raw){$room['eliminacaoSimulada']=null;return;}
    $total=array_sum($raw);$percentages=[];$soFar=0;$i=0;$n=count($raw);
    foreach($raw as $id=>$score){$i++;$pct=$i===$n?round(100-$soFar,1):round($score/$total*100,1);$soFar+=$pct;$percentages[(string)$id]=$pct;}
    $max=max($percentages);$ties=array_keys(array_filter($percentages,static fn($v)=>$v===$max));
    $loser=(string)$ties[random_int(0,count($ties)-1)];
    $room['eliminacaoSimulada']=['week'=>$room['week'],'percentages'=>$percentages,'eliminated'=>$loser,'tieDraw'=>count($ties)>1];
    mp_log($room,'📺 A produção iniciou a noite de eliminação. A votação do público é simulada, com relações da casa, pequena repercussão pública e aleatoriedade. O resultado será revelado ao fim do cronômetro.','eliminacao',['title'=>'🚪 Noite de Eliminação']);
}
function mp8_fechar_eliminacao(array &$room): void {
    $result=$room['eliminacaoSimulada']??null;
    if(!$result)return;
    $id=(string)$result['eliminated'];
    mp10_false_elimination($room,$id);
    foreach($room['players'] as &$p)if($p['id']===$id)$p['eliminated']=true;
    unset($p);
    $room['lastEliminated']=$id;
    $room['lastEliminacao']=$result;
    $name=mp_find_player($room,$id,false)['name']??'Participante';
    $percentage=$result['percentages'][$id]??0;
    $speech='🎤 A sala silenciou quando o apresentador chamou os emparedados. Depois de uma semana de alianças, conflitos e decisões, o jogo precisa continuar com uma pessoa a menos. ';
    $speech.='Com '.number_format($percentage,1,',','.').'% dos votos simulados para eliminar, '.$name.' se despede da casa. Os demais continuam na disputa e '.$name.' poderá acompanhar os acontecimentos de fora.';
    mp_log($room,$speech,'eliminacao',['title'=>'🚪 Discurso e Resultado da Eliminação']);
}
function mp8_publico(array $room,string $me): array {
    $tie=$room['phase']==='desempate'?($room['tieState']['options']??[]):[];
    $bv=$room['phase']==='bate_volta'?($room['bateVolta']??null):null;
    return [
        'paredaoPlano'=>$room['paredaoPlano']??null,
        'votosApurados'=>$room['phase']==='paredao'?null:($room['voteSummary']??null),
        'desempateIds'=>$tie,
        'bateVolta'=>$bv?['type'=>$bv['type'],'participants'=>$bv['participants'],'submitted'=>count($bv['answers']),'eligible'=>in_array($me,$bv['participants'],true),'mySubmitted'=>isset($bv['answers'][$me])]:null,
        'lastBateVolta'=>$room['lastBateVolta']??null,
        'lastEliminacao'=>$room['lastEliminacao']??null,
    ];
}
