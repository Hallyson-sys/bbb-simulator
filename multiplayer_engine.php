<?php
/** Multiplayer Beta 2: estado por sala isolado do PHP session do modo solo. */
declare(strict_types=1);
function mp_error(string $message, int $status=400): never {
    http_response_code($status);
    echo json_encode(['ok'=>false,'erro'=>$message],JSON_UNESCAPED_UNICODE);
    exit;
}
function mp_dir(): string {
    $candidates=[__DIR__.'/data/multiplayer_rooms', sys_get_temp_dir().'/bbb_simulator_multiplayer_b2'];
    foreach($candidates as $dir){
        if (is_dir($dir)) return $dir;
        if (@mkdir($dir,0700,true) || is_dir($dir)) return $dir;
    }
    mp_error('Armazenamento temporário indisponível.',503);
}
function mp_code(string $value): string {
    $value=strtoupper(trim($value));
    if (!preg_match('/^[A-HJ-NP-Z2-9]{6}$/D',$value)) mp_error('Código da sala inválido.');
    return $value;
}
function mp_path(string $code): string {return mp_dir().'/'.$code.'.json';}
function mp_name(mixed $value): string {
    if (!is_string($value)||!preg_match('//u',$value)) mp_error('Digite um nome válido.');
    $value=trim((string)preg_replace('/\s+/u',' ',$value));
    if (preg_match('/[\x00-\x1F\x7F]/u',$value)||preg_match_all('/./us',$value)<2||preg_match_all('/./us',$value)>28) mp_error('Use um nome de 2 a 28 caracteres.');
    return $value;
}
function mp_new_code(): string {
    $a='ABCDEFGHJKLMNPQRSTUVWXYZ23456789';$s='';
    for($i=0;$i<6;$i++)$s.=$a[random_int(0,strlen($a)-1)];
    return $s;
}
function mp_player(string $name,string $token): array {
    return ['id'=>bin2hex(random_bytes(8)),'name'=>$name,'tokenHash'=>hash('sha256',$token),'ready'=>false,'done'=>false,'actions'=>[], 'choice'=>null,'eliminated'=>false,'role'=>[]];
}
function mp_catalog(): array {
    // Rótulos e agrupamento preservados do menu Interações 4.0 do modo solo.
    return [
        'i4_conversar'=>['💬 Conversar de verdade','social',['jogo'=>'🎯 Rumos do jogo','relacao'=>'🤝 Nossa relação','convivencia'=>'🏠 Convivência']],
        'i4_pessoal'=>['💜 Ter uma conversa pessoal','social',['saudade'=>'🏡 Saudade de casa','sonhos'=>'✨ Planos e sonhos','sentimentos'=>'💭 Como está se sentindo']],
        'i4_romance'=>['❤️ Romance e relacionamento','social',['flertar'=>'😉 Flertar','sentimentos'=>'💗 Falar do que sente','encontro'=>'🌙 Conversar a sós']],
        'i4_atividade'=>['🏠 Convidar para uma atividade','social',['cozinha'=>'🍳 Cozinhar juntos','piscina'=>'🏊 Piscina','academia'=>'🏋️ Treinar','jardim'=>'🌿 Caminhar pelo jardim']],
        'i4_sondar'=>['👀 Sondar o jogo','estrategia',['clima'=>'🌡️ Clima da casa','voto'=>'🗳️ Próximo Paredão','aliancas'=>'🤝 Alianças']],
        'i4_protecao'=>['🛡️ Pedir proteção','estrategia',['conversa'=>'🛡️ Pedir proteção mútua']],
        'i4_acordo'=>['🎯 Propor acordo individual','estrategia',['conversa'=>'🤝 Combinar uma estratégia']],
        'i4_informacao'=>['🤫 Compartilhar uma informação','estrategia',['conversa'=>'🤫 Falar em particular']],
        'i4_reconciliar'=>['🤝 Resolver desentendimento','conflitos',['conversa'=>'🕊️ Fazer as pazes']],
        'i4_satisfacao'=>['🔥 Tirar satisfação','conflitos',['conversa'=>'🔥 Conversar sobre um conflito']],
        'i4_mediar'=>['🧩 Mediar duas pessoas','conflitos',['conversa'=>'🧩 Conversar sobre tensões']],
        'conversar'=>['💬 Conversar (clássico)','aliancas',['conversa'=>'💬 Bater papo']],
        'fofoca'=>['🗣️ Fazer fofoca','aliancas',['conversa'=>'🗣️ Falar sobre a casa']],
        'discutir'=>['😡 Discutir','aliancas',['conversa'=>'🔥 Confrontar']],
        'vt'=>['🎬 Fazer VT','aliancas',['conversa'=>'🎬 Criar um momento marcante']],
        'alianca'=>['🤝 Propor aliança','aliancas',['conversa'=>'🤝 Convidar para aliança']],
    ];
}
function mp_phases(): array {
    return [
        'interacoes_1'=>['💬 Interações','Todos podem fazer até três interações, como no modo solo.'],
        'lider'=>['👑 Prova do Líder','Disputa rápida de sorte nesta versão de testes.'],
        'vip_xepa'=>['🍽️ VIP & Xepa','O Líder escolhe quem vai para o VIP.'],
        'anjo'=>['😇 Prova do Anjo','Disputa rápida de sorte nesta versão de testes.'],
        'imunidade'=>['🛡️ Imunidade do Anjo','O Anjo protege um participante.'],
        'queridometro'=>['💟 Queridômetro','Envie um emoji a alguém da casa.'],
        'interacoes_2'=>['💬 Interações','Nova rodada de três interações.'],
        'festa'=>['🎉 Festa','Converse com seus colegas durante a festa.'],
        'discordia'=>['🔥 Jogo da Discórdia','Escolha alguém e dê um posicionamento público.'],
        'paredao'=>['🔥 Formação do Paredão','O Líder indica e a casa vota em segredo.'],
        'eliminacao'=>['🚪 Eliminação','Resultado experimental do Paredão; veja quem sai.'],
        'final'=>['🏁 Final beta','Encerramento do protótipo multiplayer.'],
    ];
}
function mp_active(array $room): array {
    return array_values(array_filter($room['players'],static fn($p)=>empty($p['eliminated'])));
}
function mp_log(array &$room,string $text,string $type='publico'): void {
    $room['events'][]=['id'=>bin2hex(random_bytes(5)),'week'=>$room['week'],'text'=>$text,'at'=>time(),'type'=>$type];
    if(count($room['events'])>240)$room['events']=array_slice($room['events'],-240);
}
function mp_find_player(array $room,string $id, bool $active=true): ?array {
    foreach($room['players'] as $p) if($p['id']===$id && (!$active||!$p['eliminated'])) return $p;
    return null;
}
function mp_self(array $room,string $token): int {
    if(!preg_match('/^[a-f0-9]{48}$/D',$token))mp_error('Acesso inválido. Entre novamente.',403);
    $hash=hash('sha256',$token);
    foreach($room['players'] as $i=>$p)if(hash_equals($p['tokenHash'],$hash))return $i;
    mp_error('Você não faz parte desta sala.',403);
}
function mp_phase_time(array $room,string $phase): int {
    if(in_array($phase,['interacoes_1','interacoes_2','festa'],true))return $room['duration'];
    if($phase==='eliminacao')return 60;
    return max(60,min(150,$room['duration']));
}
function mp_set_phase(array &$room,string $phase): void {
    $room['phase']=$phase;$room['endsAt']=time()+mp_phase_time($room,$phase);$room['pending']=[];
    foreach($room['players'] as &$p){$p['done']=false;$p['actions']=[];$p['choice']=null;}
    unset($p);
    if(in_array($phase,['vip_xepa','imunidade'],true)){
        $controller=$phase==='vip_xepa'?$room['leader']:$room['angel'];
        foreach($room['players'] as &$p) if($p['id']!==$controller)$p['done']=true;
        unset($p);
    }
    if($phase==='eliminacao'||$phase==='final'){
        foreach($room['players'] as &$p)$p['done']=true;
        unset($p);
    }
    mp_log($room,'📺 A produção anunciou: '.mp_phases()[$phase][0].' • Semana '.$room['week'].'.');
}
function mp_pending_for(array $room,string $id): array {
    return array_values(array_filter($room['pending'],static fn($q)=>$q['to']===$id && $q['status']==='pendente'));
}
function mp_relation_key(string $a,string $b): string {
    $ids=[$a,$b];sort($ids,SORT_STRING);return implode(':',$ids);
}
function mp_finish_phase(array &$room): void {
    $phase=$room['phase'];
    // Convites não respondidos expiram antes da troca de fase.
    foreach($room['pending'] as &$q)if($q['status']==='pendente')$q['status']='expirou';
    unset($q);
    if(in_array($phase,['lider','anjo'],true)){
        $candidates=[];
        foreach(mp_active($room) as $p){
            if(isset($p['choice']['number']))$candidates[]=['id'=>$p['id'],'score'=>$p['choice']['score']];
        }
        if(!$candidates)foreach(mp_active($room) as $p)$candidates[]=['id'=>$p['id'],'score'=>random_int(1,100)];
        usort($candidates,static fn($a,$b)=>$b['score']<=>$a['score']);
        $winner=$candidates[0]['id'];
        $winnerName=mp_find_player($room,$winner)['name']??'Participante';
        $room[$phase==='lider'?'leader':'angel']=$winner;
        mp_log($room,($phase==='lider'?'👑 ':'😇 ').$winnerName.' venceu a prova rápida da '.($phase==='lider'?'Liderança':'Anjo').'.');
    }
    if($phase==='vip_xepa'){
        if(!$room['vip']) $room['vip']=[$room['leader']];
        mp_log($room,'🍽️ O VIP foi definido pelo Líder.');
    }
    if($phase==='imunidade'){
        if($room['imune'])mp_log($room,'🛡️ '.(mp_find_player($room,$room['imune'])['name']??'Alguém').' recebeu a imunidade.');
    }
    if($phase==='paredao'){
        $eligible=array_values(array_filter(mp_active($room),static fn($p)=>$p['id']!==$room['imune']));
        $votes=[];
        foreach($room['players'] as $p)if(!$p['eliminated']&&isset($p['choice']['vote'])){
            $id=$p['choice']['vote'];$votes[$id]=($votes[$id]??0)+1;
        }
        arsort($votes);
        $leaderPick=$room['leaderPick'];
        $nominees=[];
        if($leaderPick && mp_find_player($room,$leaderPick) && $leaderPick!==$room['imune'])$nominees[]=$leaderPick;
        foreach(array_keys($votes) as $id)if(!in_array($id,$nominees,true) && mp_find_player($room,$id) && $id!==$room['imune']){$nominees[]=$id;break;}
        foreach($eligible as $p)if(count($nominees)<2&&!in_array($p['id'],$nominees,true))$nominees[]=$p['id'];
        $room['nominees']=$nominees;
        $names=array_map(static fn($id)=>mp_find_player($room,$id)['name']??'?', $nominees);
        mp_log($room,'🔥 Paredão formado: '.implode(' × ',$names).'. Votos do confessionário permaneceram secretos.');
    }
    if($phase==='eliminacao'){
        // Resultado experimental: pontuação de convivência, não simula votação pública real.
        $names=[];$scores=[];
        foreach($room['nominees'] as $id){
            $p=mp_find_player($room,$id);if(!$p)continue;
            $score=50;
            foreach($room['relations'] as $key=>$points) if(in_array($id,explode(':',$key),true))$score+=(int)$points;
            $scores[$id]=$score;
        }
        if($scores){asort($scores,SORT_NUMERIC);$loser=array_key_first($scores);
            foreach($room['players'] as &$p)if($p['id']===$loser)$p['eliminated']=true;
            unset($p);
            $room['lastEliminated']=$loser;
            mp_log($room,'🚪 '.(mp_find_player($room,$loser,false)['name']??'Participante').' saiu da casa (resultado experimental por convivência).');
        }
    }
    $sequence=['interacoes_1','lider','vip_xepa','anjo','imunidade','queridometro','interacoes_2','festa','discordia','paredao','eliminacao'];
    $position=array_search($phase,$sequence,true);
    if($phase==='final')return;
    if($phase==='eliminacao'){
        if(count(mp_active($room))<=3 || $room['week']>=8){mp_set_phase($room,'final');return;}
        $room['week']++;
        $room['leader']=null;$room['angel']=null;$room['vip']=[];$room['imune']=null;
        $room['leaderPick']=null;$room['nominees']=[];$room['lastEliminated']=null;
        mp_set_phase($room,'interacoes_1');return;
    }
    if($position!==false)mp_set_phase($room,$sequence[$position+1]);
}
function mp_tick(array &$room): bool {
    if(in_array($room['phase'],['lobby','final'],true))return false;
    $force=time()>=$room['endsAt'];
    $allDone=true;
    foreach(mp_active($room) as $p)if(!$p['done'])$allDone=false;
    if(in_array($room['phase'],['interacoes_1','interacoes_2','festa'],true)){
        foreach($room['pending'] as $q)if($q['status']==='pendente')$allDone=false;
    }
    if($room['phase']==='eliminacao')$allDone=false;
    if($force||$allDone){mp_finish_phase($room);return true;}
    return false;
}
function mp_public(array $room,int $self): array {
    $me=$room['players'][$self];
    $pending=array_values(array_filter($room['pending'],static fn($q)=>$q['to']===$me['id']&&$q['status']==='pendente'));
    $outgoing=array_values(array_filter($room['pending'],static fn($q)=>$q['from']===$me['id']));
    $players=[];
    foreach($room['players'] as $p)$players[]=[
        'id'=>$p['id'],'name'=>$p['name'],'ready'=>$p['ready'],'done'=>$p['done'],
        'eliminated'=>$p['eliminated'],'actionsUsed'=>count($p['actions']),
        'isLeader'=>$room['leader']===$p['id'],'isAngel'=>$room['angel']===$p['id'],
        'isVIP'=>in_array($p['id'],$room['vip'],true),'isImmune'=>$room['imune']===$p['id'],
        'relation'=>(int)($room['relations'][mp_relation_key($p['id'],$me['id'])]??0),
    ];
    $state=[
        'code'=>$room['code'],'capacity'=>$room['capacity'],'phase'=>$room['phase'],'week'=>$room['week'],
        'duration'=>$room['duration'],'endsAt'=>$room['endsAt'],'hostId'=>$room['hostId'],'myId'=>$me['id'],
        'players'=>$players,'events'=>array_slice($room['events'],-60),'catalog'=>mp_catalog(),
        'phases'=>mp_phases(),'pending'=>$pending,'outgoing'=>$outgoing,
        'myChoice'=>$me['choice'],'nominees'=>$room['nominees'],'leaderId'=>$room['leader'],
        'angelId'=>$room['angel'],'vipIds'=>$room['vip'],'immuneId'=>$room['imune'],
        'leaderPick'=>$room['leaderPick'],'lastEliminated'=>$room['lastEliminated'],
    ];
    return $state;
}
function mp_transact(string $code,string $token,callable $change): array {
    $path=mp_path($code);if(!is_file($path))mp_error('Sala não encontrada (o servidor pode ter reiniciado).',404);
    $f=@fopen($path,'r+');if(!$f||!flock($f,LOCK_EX))mp_error('Sala indisponível no momento.',503);
    $room=json_decode((string)stream_get_contents($f),true);
    if(!is_array($room)||!isset($room['players']))mp_error('Estado da sala danificado.',503);
    $self=mp_self($room,$token);
    $changed=mp_tick($room);
    $changed=$change($room,$self)||$changed;
    if($changed){$room['updatedAt']=time();rewind($f);ftruncate($f,0);
        fwrite($f,json_encode($room,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR));fflush($f);}
    flock($f,LOCK_UN);fclose($f);return mp_public($room,$self);
}
function mp_input_id(array $room,mixed $value,bool $allowSelf=false): string {
    if(!is_string($value)||!preg_match('/^[a-f0-9]{16}$/D',$value)||!mp_find_player($room,$value))mp_error('Participante inválido.');
    return $value;
}
