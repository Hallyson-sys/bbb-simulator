<?php
/**
 * BBB Simulator — Multiplayer Beta 11: reta final e cerimônia compartilhada.
 * Inspirado na Última Resistência, nos pedidos de voto e nas torcidas do modo solo.
 * A simulação de público permanece explicitamente fictícia.
 */
declare(strict_types=1);

function mp11_count(array $room): int {
    $n=count(mp_active($room));
    $secret=$room['secretRoom']??null;
    if(is_array($secret)&&mp_find_player($room,(string)($secret['id']??''),true)===null
       && mp_find_player($room,(string)($secret['id']??''),false)!==null)$n++;
    return $n;
}
function mp11_reta_final(array &$room): void {
    $count=mp11_count($room);
    if($count<=8 && $count>=4 && empty($room['top8Announced'])){
        $room['top8Announced']=true;
        mp_log($room,'🏁 TOP 8! A partir de agora não há Prova do Anjo, Monstro nem Jogo da Discórdia. As interações e a Prova do Líder continuam!','reta_final',['title'=>'🏁 Reta Final • Top 8']);
    }
    if($count<=5 && $count>=4 && empty($room['top5Announced'])){
        $room['top5Announced']=true;
        mp_log($room,'🏆 TOP 5! As dinâmicas surpresa terminam. Quando a casa chegar ao Top 4, acontece a Última Resistência, valendo uma vaga direta na final.','reta_final',['title'=>'🏆 Contagem regressiva']);
    }
}
function mp11_cycle_data(int $index): array {
    $base=[
      ['O primeiro apoio','Plataforma de equilíbrio','Vibração leve'],
      ['Luzes e distrações','Torre de luzes','Luzes piscando'],
      ['A inclinação','Plataforma inclinada','Desnível lateral'],
      ['O peso do tempo','Sala das engrenagens','Oscilação forte'],
      ['Rajadas de vento','Torre aberta','Vento lateral'],
      ['O chão parece sumir','Passarela suspensa','Faixa estreita'],
      ['Últimos segundos','Plataforma final','Fadiga máxima'],
      ['A última luz','Palco da grande final','Precisão decisiva'],
    ];
    [$title,$place,$effect]=$base[$index];
    $target=random_int(27,73);$tolerance=max(8,18-$index);
    return ['title'=>$title,'place'=>$place,'effect'=>$effect,'target'=>$target,
       'min'=>max(0,$target-$tolerance),'max'=>min(100,$target+$tolerance)];
}
function mp11_start_top4(array &$room): void {
    $players=array_column(mp_active($room),'id');
    if(count($players)!==4)mp_error('Última Resistência exige quatro participantes ativos.',409);
    $state=['cycle'=>0,'cycles'=>[], 'standing'=>array_fill_keys($players,true),
      'energy'=>array_fill_keys($players,100),'focus'=>array_fill_keys($players,65),
      'score'=>array_fill_keys($players,0),'survived'=>array_fill_keys($players,0),
      'answers'=>[],'last'=>null,'winner'=>null];
    for($i=0;$i<8;$i++)$state['cycles'][]=mp11_cycle_data($i);
    $room['top4State']=$state;$room['endsAt']=time()+55;
    mp_log($room,'🏆 TOP 4 — A Última Resistência começou! Quatro semifinalistas, oito ciclos de desgaste e uma vaga direta na final. Quem não ganhar enfrenta o último Paredão.','top4',['title'=>'🏆 A Última Resistência']);
}
function mp11_top4_answer(array &$room,string $id,mixed $posture,mixed $marker): void {
    if($room['phase']!=='top4_resistencia')mp_error('A resistência não está em andamento.',409);
    $st=&$room['top4State'];$cycle=(int)$st['cycle'];
    if(empty($st['standing'][$id]))mp_error('Você já deixou a plataforma. Aguarde o resultado.',409);
    if(array_key_exists($id,$st['answers']))mp_error('Você já respondeu este ciclo.',409);
    if(!is_string($posture)||!in_array($posture,['conservar','firmar','arriscar'],true)||!is_int($marker)||$marker<0||$marker>100)
        mp_error('Escolha uma postura e um marcador válido de 0 a 100.',400);
    $st['answers'][$id]=['posture'=>$posture,'marker'=>$marker];
    foreach($room['players'] as &$p)if($p['id']===$id){$p['done']=true;break;}
    unset($p);
}
function mp11_top4_step(int $round,int $energy,int $focus,string $posture,int $error): array {
    $mods=['conservar'=>[-5,13,1],'firmar'=>[0,-3,9],'arriscar'=>[4,-10,18]];
    [$cost,$focusDiff,$bonus]=$mods[$posture];
    $penalty=($error>14?8:($error>7?4:0))+($focus<25?4:0);
    $nextEnergy=max(0,$energy-max(2,6+$round+$cost+$penalty));
    $nextFocus=max(0,min(100,$focus+$focusDiff+($error<=5?2:-3)));
    $precision=max(0,100-$error*4);
    $points=max(1,(int)round($precision/4)+$bonus+($nextFocus>=75?3:0));
    return [$nextEnergy,$nextFocus,$points];
}
function mp11_finish_top4_cycle(array &$room): void {
    $st=&$room['top4State'];$i=(int)$st['cycle'];
    if($i>=8||!isset($st['cycles'][$i]))return;
    $target=(int)$st['cycles'][$i]['target'];$history=[];
    foreach($st['standing'] as $id=>$standing){
        if(!$standing)continue;
        $answer=$st['answers'][$id]??null;
        if($answer===null){$st['standing'][$id]=false;$history[]=['id'=>(string)$id,'points'=>0,'fallen'=>true,'absent'=>true];continue;}
        $error=abs((int)$answer['marker']-$target);
        [$energy,$focus,$points]=mp11_top4_step($i,(int)$st['energy'][$id],(int)$st['focus'][$id],$answer['posture'],$error);
        $st['energy'][$id]=$energy;$st['focus'][$id]=$focus;
        $st['score'][$id]+=$points;$st['survived'][$id]=$i+1;
        if($energy<=0)$st['standing'][$id]=false;
        $history[]=['id'=>(string)$id,'points'=>$points,'fallen'=>!$st['standing'][$id],'absent'=>false];
    }
    $st['last']=['cycle'=>$i+1,'target'=>$target,'results'=>$history];
    $st['cycle']++;
    $fallen=array_values(array_filter($history,static fn($r)=>$r['fallen']));
    $message='🎯 Ciclo '.($i+1).'/8 concluído. O ponto ideal estava na posição '.$target.'.';
    foreach($fallen as $f)$message.=' '.(mp_find_player($room,$f['id'],false)['name']??'Participante').($f['absent']?' perdeu o tempo e saiu da plataforma.':' não resistiu ao desgaste e caiu.');
    mp_log($room,$message,'top4',['title'=>'🏆 Última Resistência • Ciclo '.($i+1)]);
    $standing=array_keys(array_filter($st['standing']));
    if(count($standing)<=1||$st['cycle']>=8){mp11_finish_top4($room);return;}
    $st['answers']=[];$room['endsAt']=time()+55;
    foreach($room['players'] as &$p)$p['done']=empty($st['standing'][$p['id']]);
    unset($p);
}
function mp11_finish_top4(array &$room): void {
    $st=&$room['top4State'];$ids=array_keys($st['score']);
    $draw=[];foreach($ids as $id)$draw[$id]=random_int(1,1000000);
    usort($ids,static function($a,$b)use($st,$draw){
        return ($st['survived'][$b]<=>$st['survived'][$a])
            ?:((int)$st['standing'][$b]<=>(int)$st['standing'][$a])
            ?:($st['score'][$b]<=>$st['score'][$a])
            ?:($st['energy'][$b]<=>$st['energy'][$a])
            ?:($draw[$b]<=>$draw[$a]);
    });
    $winner=$ids[0];$st['winner']=$winner;$room['top4Winner']=$winner;
    $room['nominees']=array_values(array_filter($ids,static fn($id)=>$id!==$winner));
    $room['leaderPick']=null;$room['leader']=null;$room['angel']=null;$room['imune']=$winner;
    $room['paredaoPlano']=['tamanho'=>3,'bateVolta'=>false,'semana'=>$room['week'],'final'=>true];
    $room['specialQueue']=[];
    $name=mp_find_player($room,$winner,false)['name']??'Participante';
    mp_log($room,'🏆 '.$name.' venceu A Última Resistência, conquistou a primeira vaga na Grande Final! Os outros três semifinalistas enfrentam o último Paredão, sem Bate e Volta.','top4',['title'=>'🏆 Primeiro finalista']);
    mp_set_phase($room,'final_paredao');
}
function mp11_speeches(): array {
    return [
      'coracao'=>['title'=>'❤️ Jogar com o coração','text'=>'Eu vivi tudo intensamente. Acertei, errei, fiz amizades e aprendi. Se minha verdade chegou até vocês, peço o voto de vocês!'],
      'estrategia'=>['title'=>'🎯 Defender meu jogo','text'=>'Eu tomei decisões difíceis, construí meu caminho e enfrentei as consequências. Quero que o Brasil reconheça a minha trajetória!'],
      'superacao'=>['title'=>'🔥 Relembrar minha superação','text'=>'Eu pensei que não aguentaria várias vezes, mas cheguei até aqui. Cada prova e cada Paredão fizeram parte dessa história. Conto com vocês!'],
      'gratidao'=>['title'=>'🤝 Agradecer e pedir confiança','text'=>'Nada disso teria sentido sem quem esteve comigo na casa e quem acompanhou de fora. Obrigado por cada semana. Se mereci, quero o voto de vocês!'],
    ];
}
function mp11_start_final(array &$room): void {
    $finalists=array_column(mp_active($room),'id');
    $room['finalState']=['finalists'=>$finalists,'speeches'=>[], 'support'=>[],
       'result'=>null,'startedAt'=>time()];
    foreach($room['players'] as &$p)$p['done']=$p['eliminated'];
    unset($p);
    mp_log($room,'🏆 COMEÇOU A GRANDE FINAL! Os três finalistas vão pedir o voto do público simulado. Os eliminados podem escolher quem apoiar e declarar sua torcida na cerimônia.','final',['title'=>'🏆 Grande Final • Cerimônia']);
}
function mp11_speech(array &$room,string $id,string $tone): void {
    if($room['phase']!=='final_cerimonia'||!in_array($id,$room['finalState']['finalists']??[],true))mp_error('Você não é finalista.',403);
    if(!isset(mp11_speeches()[$tone]))mp_error('Escolha um discurso válido.');
    if(isset($room['finalState']['speeches'][$id]))mp_error('Seu discurso já foi confirmado.',409);
    $room['finalState']['speeches'][$id]=$tone;
    foreach($room['players'] as &$p)if($p['id']===$id){$p['done']=true;break;}
    unset($p);
    $name=mp_find_player($room,$id,false)['name']??'Participante';
    mp_log($room,'🎤 '.$name.' pediu o voto do público: “'.mp11_speeches()[$tone]['text'].'”','final',['title'=>'🎤 Pedido de voto']);
}
function mp11_support(array &$room,string $id,string $candidate): void {
    if($room['phase']!=='final_cerimonia')mp_error('A torcida só pode ser escolhida na cerimônia.',409);
    $player=mp_find_player($room,$id,false);
    if(!$player||!$player['eliminated'])mp_error('Somente ex-participantes podem declarar torcida.',403);
    if(!in_array($candidate,$room['finalState']['finalists']??[],true))mp_error('Escolha um finalista válido.');
    if(isset($room['finalState']['support'][$id]))mp_error('Você já declarou torcida.',409);
    $room['finalState']['support'][$id]=$candidate;
    mp_log($room,'📣 Entrevistado pelo apresentador, '.$player['name'].' declarou: “Minha torcida na final vai para '.(mp_find_player($room,$candidate,false)['name']??'um finalista').'!”','final',['title'=>'📣 Torcida dos eliminados']);
}
/** O apresentador encerra entrevistas incompletas sem travar a final por ausência. */
function mp11_complete_ceremony(array &$room): void {
    $st=&$room['finalState'];$finalists=$st['finalists']??[];
    $types=array_keys(mp11_speeches());
    foreach($finalists as $id){
        if(isset($st['speeches'][$id]))continue;
        $tone=$types[abs(crc32($id.'|'.$room['week']))%count($types)];
        $st['speeches'][$id]=$tone;
        mp_log($room,'🎤 A produção exibiu um pedido de voto gravado de '.(mp_find_player($room,$id,false)['name']??'finalista').': “'.mp11_speeches()[$tone]['text'].'”','final',['title'=>'🎤 Mensagem de finalista']);
    }
    foreach($room['players'] as $p){
        if(!$p['eliminated']||isset($st['support'][$p['id']])||!$finalists)continue;
        $chosen=null;$best=-PHP_INT_MAX;
        foreach($finalists as $id){
            $relation=(int)($room['relations'][mp_relation_key($p['id'],$id)]??0);
            $score=$relation*100+(abs(crc32($p['id'].'|'.$id))%100);
            if($score>$best){$best=$score;$chosen=$id;}
        }
        if(!$chosen)continue;
        $st['support'][$p['id']]=$chosen;
        mp_log($room,'📣 Em entrevista à produção, '.$p['name'].' revelou sua torcida por '.(mp_find_player($room,$chosen,false)['name']??'um finalista').'.','final',['title'=>'📣 Torcida dos eliminados']);
    }
}
function mp11_final_result(array &$room): void {
    $st=&$room['finalState'];if(!empty($st['result']))return;
    mp11_complete_ceremony($room);
    $finalists=$st['finalists']??array_column(mp_active($room),'id');
    $scores=[];
    foreach($finalists as $id){
        $pop=(int)($room['feedBBB']['popularity'][$id]??50);
        $relations=[];
        foreach($room['relations']??[] as $pair=>$value)if(in_array($id,explode(':',(string)$pair),true))$relations[]=(int)$value;
        $avg=$relations?array_sum($relations)/count($relations):0;
        $supportCount=count(array_filter($st['support'],static fn($v)=>$v===$id));
        $tone=$st['speeches'][$id]??'coracao';
        $speechBonus=$tone==='estrategia'?4:($tone==='superacao'?3:($tone==='gratidao'?3:2));
        $scores[$id]=max(8,45+$pop*.6+$avg*.18+$supportCount*5+$speechBonus+random_int(-12,12));
    }
    arsort($scores,SORT_NUMERIC);$ids=array_keys($scores);$winner=$ids[0];
    $total=array_sum($scores);$pct=[];$soFar=0;$i=0;$n=count($scores);
    foreach($scores as $id=>$v){$i++;$p=$i===$n?round(100-$soFar,1):round($v/$total*100,1);$pct[$id]=$p;$soFar+=$p;}
    $st['result']=['winner'=>$winner,'percentages'=>$pct,'ranking'=>$ids,'simulated'=>true];
    $room['champion']=$winner;
    $name=mp_find_player($room,$winner,false)['name']??'Finalista';
    $parts=[];foreach($pct as $id=>$value)$parts[]=(mp_find_player($room,$id,false)['name']??'Finalista').': '.number_format($value,1,',','.').'%';
    mp_log($room,'🏆 GRAND FINAL! Depois das declarações dos finalistas e torcidas dos eliminados, '.$name.' é campeão do BBB Simulator Online! Resultado do público SIMULADO: '.implode(' • ',$parts).'.','final',['title'=>'🏆 CAMPEÃO DO BBB SIMULATOR']);
}
function mp11_public(array $room,string $self): array {
    $stage=['remaining'=>mp11_count($room),'top8'=>!empty($room['top8Announced']),
      'top5'=>!empty($room['top5Announced']),'champion'=>$room['champion']??null];
    $top4=$room['top4State']??null;
    if(is_array($top4)){
        $i=(int)$top4['cycle'];$current=$top4['cycles'][$i]??null;
        $stage['top4']=['cycle'=>$i+1,'total'=>8,'title'=>$current['title']??'Resultado da resistência',
          'place'=>$current['place']??'Palco','effect'=>$current['effect']??'',
          'zone'=>$current?['min'=>$current['min'],'max'=>$current['max']]:null,
          'standing'=>$top4['standing'],'score'=>$top4['score'],'energy'=>$top4['energy'],
          'focus'=>$top4['focus'],'answered'=>isset($top4['answers'][$self]),
          'last'=>$top4['last'],'winner'=>$top4['winner']];
    }
    $fin=$room['finalState']??null;
    if(is_array($fin)){
        $stage['ceremony']=['finalists'=>$fin['finalists'],'speeches'=>$fin['speeches'],
         'support'=>$fin['support'],'tones'=>mp11_speeches(),
         'mySpeech'=>$fin['speeches'][$self]??null,'mySupport'=>$fin['support'][$self]??null,
         'result'=>$room['phase']==='final'?($fin['result']??null):null];
    }
    return $stage;
}
