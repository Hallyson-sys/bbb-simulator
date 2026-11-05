<?php
/** BBB Simulator — Beta 6: desafios multiplayer competitivos, autoritativos. */
declare(strict_types=1);

function mp_prova_catalogo_logica(): array {
    // Uma resposta válida por questão; o servidor nunca envia o gabarito ao navegador.
    return [
        ['q'=>'2 · 4 · 8 · 16 · ?', 'o'=>['24','32','30','18'], 'a'=>1],
        ['q'=>'3 · 6 · 12 · 24 · ?', 'o'=>['36','48','42','30'], 'a'=>1],
        ['q'=>'1 · 4 · 9 · 16 · ?', 'o'=>['20','24','25','36'], 'a'=>2],
        ['q'=>'5 · 10 · 20 · 40 · ?', 'o'=>['60','65','80','100'], 'a'=>2],
        ['q'=>'2 · 5 · 8 · 11 · ?', 'o'=>['12','13','14','16'], 'a'=>2],
        ['q'=>'1 · 1 · 2 · 3 · 5 · ?', 'o'=>['7','8','9','10'], 'a'=>1],
        ['q'=>'100 · 90 · 80 · 70 · ?', 'o'=>['60','65','50','55'], 'a'=>0],
        ['q'=>'4 · 7 · 11 · 16 · ?', 'o'=>['20','21','22','23'], 'a'=>2],
        ['q'=>'2 · 6 · 12 · 20 · ?', 'o'=>['24','28','30','32'], 'a'=>2],
        ['q'=>'3 · 9 · 27 · ?', 'o'=>['54','72','81','90'], 'a'=>2],
        ['q'=>'7 · 14 · 21 · 28 · ?', 'o'=>['30','33','35','42'], 'a'=>2],
        ['q'=>'81 · 27 · 9 · ?', 'o'=>['1','2','3','6'], 'a'=>2],
        ['q'=>'1 · 3 · 6 · 10 · ?', 'o'=>['14','15','16','18'], 'a'=>1],
        ['q'=>'50 · 45 · 35 · 20 · ?', 'o'=>['0','5','10','15'], 'a'=>0],
        ['q'=>'2 · 3 · 5 · 8 · 12 · ?', 'o'=>['15','16','17','18'], 'a'=>2],
        ['q'=>'6 · 12 · 18 · 24 · ?', 'o'=>['28','30','32','36'], 'a'=>1],
        ['q'=>'16 · 8 · 4 · 2 · ?', 'o'=>['0','1','3','4'], 'a'=>1],
        ['q'=>'4 · 9 · 14 · 19 · ?', 'o'=>['22','23','24','25'], 'a'=>2],
        ['q'=>'2 · 7 · 12 · 17 · ?', 'o'=>['19','20','22','24'], 'a'=>2],
        ['q'=>'1 · 2 · 4 · 7 · 11 · ?', 'o'=>['14','15','16','17'], 'a'=>2],
    ];
}
function mp_prova_baralhar(array $items): array {
    for($i=count($items)-1;$i>0;$i--){$j=random_int(0,$i);[$items[$i],$items[$j]]=[$items[$j],$items[$i]];}
    return $items;
}
function mp_prova_criar(array $room,string $phase): array {
    $tipos=['memoria','logica','caixas'];
    $prev=$room['lastProof']['type']??null;
    if(in_array($prev,$tipos,true))$tipos=array_values(array_diff($tipos,[$prev]));
    $type=$tipos[random_int(0,count($tipos)-1)];
    $proof=['type'=>$type,'startedAt'=>time(),'submitted'=>[]];
    if($type==='memoria'){
        $icons=mp_prova_baralhar(['⭐','🔥','💎','👑','🌙','⚡','🎯','🦋','🍀','🎲']);
        $proof['sequence']=array_slice($icons,0,5);
        $proof['symbols']=mp_prova_baralhar($icons);
        $proof['revealUntil']=time()+14;
    }elseif($type==='logica'){
        $proof['questions']=array_slice(mp_prova_baralhar(mp_prova_catalogo_logica()),0,5);
    }else{
        $prizes=mp_prova_baralhar([0,2,3,4,6,7,9,10,11,12,13,15,17,18,19,21,23,25,28,30]);
        $proof['prizes']=$prizes;
        $proof['boxes']=20;
    }
    return $proof;
}
function mp_prova_nome(string $type): string {
    return match($type){
        'memoria'=>'🧠 Memória da Casa',
        'logica'=>'🔢 Sequência Lógica',
        'caixas'=>'🎁 Caixas Misteriosas',
        default=>'🎮 Prova Online',
    };
}
function mp_prova_publica(array $room,int $self): ?array {
    $prova=$room['prova']??null;
    if(!is_array($prova)||!isset($prova['type'])||!in_array($room['phase'],['lider','anjo'],true))return null;
    $me=$room['players'][$self];
    $type=$prova['type'];
    $public=[
      'type'=>$type,'title'=>mp_prova_nome($type),
      'numberOfPlayers'=>count(mp_active($room)),'endedAt'=>(int)$room['endsAt'],
      'submitted'=>count($prova['submitted']??[]),
      'eligible'=>!($room['phase']==='anjo' && ($room['leader']??null)===$me['id']),
      'mySubmitted'=>isset($prova['submitted'][$me['id']]),
    ];
    if($type==='memoria'){
        $public['revealUntil']=(int)$prova['revealUntil'];
        $public['symbols']=$prova['symbols'];
        if(time()<(int)$prova['revealUntil'])$public['sequence']=$prova['sequence'];
    }elseif($type==='logica'){
        $public['questions']=array_map(static fn($q)=>['q'=>$q['q'],'o'=>$q['o']],$prova['questions']);
    }elseif($type==='caixas'){
        $public['boxes']=20;
    }
    return $public;
}
function mp_prova_avaliar(array $prova,array $answers,int $now): int {
    switch($prova['type']){
       case 'memoria':
          if($now<(int)$prova['revealUntil'])mp_error('Espere a sequência desaparecer antes de confirmar.',409);
          if(count($answers)!==5)mp_error('Escolha cinco símbolos na ordem que você memorizou.');
          foreach($answers as $answer)if(!is_string($answer)||!in_array($answer,$prova['symbols'],true))mp_error('Símbolo inválido.');
          if(count(array_unique($answers))!==5)mp_error('Não repita símbolos na sequência.');
          $hits=0;foreach($answers as $i=>$s)if($s===$prova['sequence'][$i])$hits++;
          return $hits*20;
       case 'logica':
          if(count($answers)!==5)mp_error('Responda às cinco questões.');
          $score=0;
          foreach($prova['questions'] as $i=>$q){
              $answer=$answers[$i]??null;
              if(!is_int($answer)||$answer<0||$answer>3)mp_error('Resposta de raciocínio inválida.');
              if($answer===$q['a'])$score+=20;
          }
          return $score;
       case 'caixas':
          if(count($answers)!==3)mp_error('Escolha exatamente três caixas.');
          foreach($answers as $answer)if(!is_int($answer)||$answer<0||$answer>19)mp_error('Número de caixa inválido.');
          if(count(array_unique($answers))!==3)mp_error('Escolha três caixas diferentes.');
          return (int)array_sum(array_map(static fn($n)=>(int)$prova['prizes'][$n],$answers));
    }
    mp_error('Prova inválida.',409);
}
function mp_prova_resultado(array &$room,string $phase): void {
    $proof=$room['prova']??null;
    $eligibles=array_values(array_filter(mp_active($room),static fn($p)=>$phase!=='anjo'||$p['id']!==($room['leader']??null)));
    if(!$eligibles){
        $room['lastProof']=null;
        $room[$phase==='lider'?'leader':'angel']=null;
        return;
    }
    $ranking=[];
    foreach($eligibles as $p){
        $submitted=$proof['submitted'][$p['id']]??null;
        if($submitted!==null)$ranking[]=['id'=>$p['id'],'name'=>$p['name'],'score'=>$submitted['score'],'submitted'=>true];
    }
    $defaulted=false;
    if(!$ranking){
       $defaulted=true;
       foreach($eligibles as $p)$ranking[]=['id'=>$p['id'],'name'=>$p['name'],'score'=>0,'submitted'=>false];
    }
    usort($ranking,static fn($a,$b)=>($b['score']<=>$a['score'])?:strcmp($a['name'],$b['name']));
    $topScore=$ranking[0]['score'];
    $ties=array_values(array_filter($ranking,static fn($r)=>$r['score']===$topScore));
    $draw=count($ties)>1;
    $winner=$draw?$ties[random_int(0,count($ties)-1)]:$ties[0];
    $room[$phase==='lider'?'leader':'angel']=$winner['id'];
    $room['lastProof']=[
        'week'=>$room['week'],'phase'=>$phase,'type'=>$proof['type']??'caixas','title'=>mp_prova_nome($proof['type']??'caixas'),
        'winner'=>$winner['id'],'ranking'=>array_slice($ranking,0,20),'tiebreakDraw'=>$draw,'noSubmissions'=>$defaulted,
    ];
    $suffix=$defaulted?' (ninguém participou; sorteio automático).':($draw?' (empate na pontuação; sorteio entre empatados).':'');
    mp_log($room,($phase==='lider'?'👑 ':'😇 ').$winner['name'].' venceu '.mp_prova_nome($proof['type']??'caixas').' com '.$winner['score'].' pontos'.$suffix);
    // Não manter o gabarito secreto na versão pública do estado após a prova.
    unset($room['prova']);
}
