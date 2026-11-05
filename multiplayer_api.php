<?php
/** BBB Simulator – Multiplayer Beta 5; interações contextuais, pausa e backup. */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
require_once __DIR__.'/multiplayer_engine.php';
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){header('Allow: POST');mp_error('Método não permitido.',405);}
if(isset($_SERVER['HTTP_ORIGIN'])){
    $a=parse_url($_SERVER['HTTP_ORIGIN'],PHP_URL_HOST);
    $b=strtolower(explode(':',$_SERVER['HTTP_HOST']??'')[0]);
    if(!is_string($a)||strtolower($a)!==$b)mp_error('Origem não autorizada.',403);
}
if(stripos($_SERVER['CONTENT_TYPE']??'','application/json')!==0)mp_error('Envie JSON.',415);
$raw=file_get_contents('php://input',false,null,0,524289);
if(!$raw||strlen($raw)>524288)mp_error('Solicitação muito grande.',413);
$data=json_decode($raw,true);if(!is_array($data))mp_error('JSON inválido.');
$action=$data['action']??'';
if($action!=='restore' && strlen($raw)>4096)mp_error('Solicitação inválida.',413);
if(random_int(1,150)===1)foreach(glob(mp_dir().'/*.json')?:[] as $f)if(filemtime($f)<time()-86400)@unlink($f);
function mp_reply(array $payload): never {echo json_encode(['ok'=>true,'serverTime'=>time()]+$payload,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
if($action==='create'){
    $name=mp_name($data['name']??null);$capacity=(int)($data['capacity']??0);
    if(!in_array($capacity,[10,15,20],true))mp_error('Escolha 10, 15 ou 20 vagas.');
    $profile=mp_profile($data['profile']??null);
    $token=bin2hex(random_bytes(24));$player=mp_player($name,$token,$profile);
    $room=[
        'code'=>'','capacity'=>$capacity,'phase'=>'lobby','week'=>1,'duration'=>180,'endsAt'=>0,
        'hostId'=>$player['id'],'players'=>[$player],'pending'=>[],'events'=>[],
        'relations'=>[],'alliances'=>[],'leader'=>null,'angel'=>null,'vip'=>[],
        'imune'=>null,'leaderPick'=>null,'nominees'=>[],'lastEliminated'=>null,
        'paused'=>false,'pauseRemaining'=>0,'privateLogs'=>[],'romanceMoments'=>[],'voteAgreements'=>[],'protectionPacts'=>[],
        'createdAt'=>time(),'updatedAt'=>time(),
    ];
    for($i=0;$i<12;$i++){
        $code=mp_new_code();$path=mp_path($code);$f=@fopen($path,'x');
        if($f!==false){@chmod($path,0600);$room['code']=$code;fwrite($f,json_encode($room,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));fclose($f);
            mp_reply(['accessToken'=>$token,'room'=>mp_public($room,0)]);
        }
    }
    mp_error('Não foi possível criar sua sala.',503);
}
if($action==='restore'){
    $backup=$data['backup']??null;
    $token=$data['token']??null;
    if(!is_array($backup)||!is_string($token)||!isset($backup['players'])||!is_array($backup['players']))mp_error('Backup inválido.',400);
    if(count($backup['players'])<1||count($backup['players'])>20||!in_array($backup['capacity']??0,[10,15,20],true))mp_error('Backup incompatível.',400);
    $code=mp_code(is_string($backup['code']??null)?$backup['code']:'');
    if(empty($backup['paused'])||!isset($backup['pauseRemaining'])||!is_int($backup['pauseRemaining'])||$backup['pauseRemaining']<0||$backup['pauseRemaining']>86400)mp_error('O backup precisa estar pausado.',400);
    $hostIndex=mp_self($backup,$token);
    if(($backup['players'][$hostIndex]['id']??'')!==($backup['hostId']??''))mp_error('Somente o anfitrião original pode restaurar.',403);
    if(!isset($backup['phase'])||!in_array($backup['phase'],array_keys(mp_phases()),true))mp_error('Fase inválida.',400);
    if(count($backup['pending']??[])>100||count($backup['events']??[])>240||count($backup['privateLogs']??[])>100)mp_error('Backup excede os limites.',413);
    // Nunca sobrescrevemos uma sala existente. Se for do anfitrião original,
    // recuperamos seu acesso sem apagar ações feitas após o último backup.
    $path=mp_path($code);
    if(is_file($path)){
        $currentFile=@fopen($path,'r');if(!$currentFile||!flock($currentFile,LOCK_SH))mp_error('Sala ocupada.',503);
        $current=json_decode((string)stream_get_contents($currentFile),true);
        flock($currentFile,LOCK_UN);fclose($currentFile);
        if(!is_array($current))mp_error('Sala existente inválida.',503);
        $currentSelf=mp_self($current,$token);
        if(($current['players'][$currentSelf]['id']??'')!==($current['hostId']??''))mp_error('Código já ocupado por outra sala.',409);
        mp_reply(['room'=>mp_public($current,$currentSelf),'accessToken'=>$token,'reused'=>true]);
    }
    $f=@fopen($path,'x');if(!$f)mp_error('Sala já existente. Abra a sala pelo código original.',409);
    @chmod($path,0600);
    $backup['updatedAt']=time();$backup['endsAt']=0;
    try{fwrite($f,json_encode($backup,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));fclose($f);}catch(Throwable $e){fclose($f);@unlink($path);mp_error('Falha ao restaurar o backup.',500);}
    mp_reply(['room'=>mp_public($backup,$hostIndex),'accessToken'=>$token]);
}
$code=mp_code(is_string($data['code']??null)?$data['code']:'');
if($action==='join'){
    $name=mp_name($data['name']??null);$profile=mp_profile($data['profile']??null);$token=bin2hex(random_bytes(24));
    $path=mp_path($code);if(!is_file($path))mp_error('Sala não encontrada.',404);
    $f=@fopen($path,'r+');if(!$f||!flock($f,LOCK_EX))mp_error('Sala indisponível.',503);
    $room=json_decode((string)stream_get_contents($f),true);if(!is_array($room))mp_error('Sala inválida.',503);
    if($room['phase']!=='lobby')mp_error('Partida já começou.',409);
    if(count($room['players'])>=$room['capacity'])mp_error('Sala lotada.',409);
    foreach($room['players'] as $p)if(strcasecmp($p['name'],$name)===0)mp_error('Nome já utilizado na sala.',409);
    $room['players'][]=mp_player($name,$token,$profile);$room['updatedAt']=time();rewind($f);ftruncate($f,0);
    fwrite($f,json_encode($room,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));fflush($f);flock($f,LOCK_UN);fclose($f);
    mp_reply(['accessToken'=>$token,'room'=>mp_public($room,count($room['players'])-1)]);
}
$token=is_string($data['token']??null)?$data['token']:'';
if($action==='export'){
    $path=mp_path($code);$f=@fopen($path,'r');if(!$f||!flock($f,LOCK_SH))mp_error('Sala indisponível.',503);
    $room=json_decode((string)stream_get_contents($f),true);flock($f,LOCK_UN);fclose($f);
    if(!is_array($room))mp_error('Sala inválida.',503);
    $self=mp_self($room,$token);
    if(($room['players'][$self]['id']??'')!==($room['hostId']??''))mp_error('Só o anfitrião pode salvar.',403);
    if(empty($room['paused']))mp_error('Pause a temporada antes de salvar.',409);
    mp_reply(['backup'=>$room]);
}

$state=mp_transact($code,$token,static function(array &$room,int $self)use($data,$action): bool{
    $me=&$room['players'][$self];$myId=$me['id'];$phase=$room['phase'];
    if($action==='status')return false;
    if($action==='export')return false;
    if($action==='pause'){
        if($myId!==$room['hostId'])mp_error('Apenas o anfitrião pode pausar.',403);
        if(in_array($phase,['lobby','final'],true))mp_error('A pausa fica disponível durante a temporada.',409);
        if(!empty($room['paused']))return false;
        $room['pauseRemaining']=max(1,$room['endsAt']-time());
        $room['paused']=true;$room['endsAt']=0;
        mp_log($room,'⏸️ O anfitrião pausou a temporada. O cronômetro foi congelado.');
        return true;
    }
    if($action==='resume'){
        if($myId!==$room['hostId'])mp_error('Apenas o anfitrião pode retomar.',403);
        if(empty($room['paused']))mp_error('A temporada não está pausada.',409);
        $room['endsAt']=time()+max(1,(int)$room['pauseRemaining']);
        $room['pauseRemaining']=0;$room['paused']=false;
        mp_log($room,'▶️ O anfitrião retomou a temporada.');
        return true;
    }
    if(!empty($room['paused']))mp_error('A temporada está pausada. Aguarde o anfitrião.',409);

    if($action==='ready'){
        if($phase!=='lobby')mp_error('A partida já começou.',409);
        $me['ready']=!$me['ready'];return true;
    }
    if($action==='start'){
        if($phase!=='lobby')mp_error('A partida já começou.',409);
        if($myId!==$room['hostId'])mp_error('Apenas o anfitrião inicia.',403);
        if(count($room['players'])<2)mp_error('São necessárias duas pessoas ou mais.',409);
        foreach($room['players'] as $p)if(!$p['ready'])mp_error('Todos devem estar prontos.',409);
        $duration=(int)($data['duration']??180);
        if(!in_array($duration,[90,180,300],true))mp_error('Duração inválida.');
        $room['duration']=$duration;mp_set_phase($room,'interacoes_1');return true;
    }
    if($phase==='lobby'||$phase==='final')mp_error('Esta fase não aceita ações.',409);
    if($me['eliminated'])mp_error('Você está no modo espectador.',403);
    if(in_array($phase,['interacoes_1','interacoes_2','festa'],true)){
        if($action==='interact'){
            if($me['done']||count($me['actions'])>=3)mp_error('Você já finalizou suas ações.',409);
            $target=mp_input_id($room,$data['targetId']??null);
            if($target===$myId)mp_error('Escolha outra pessoa.');
            $type=$data['type']??'';$catalog=mp_catalog();if(!is_string($type)||!isset($catalog[$type]))mp_error('Interação inválida.');
            $subject=$data['subject']??'';if(!is_string($subject)||!isset($catalog[$type][2][$subject]))mp_error('Assunto inválido.');
            $approach=$data['approach']??'';if(!in_array($approach,['sincera','cautelosa','leve'],true))mp_error('Abordagem inválida.');
            if(count($room['pending'])>=100)mp_error('Limite de convites desta fase alcançado.',409);
            $message=mp_small_message($data['message']??'');
            $voteTarget=null;
            if($type==='i4_acordo'&&isset($data['voteTarget'])&&$data['voteTarget']!==''){
                $voteTarget=mp_input_id($room,$data['voteTarget']);
                if(in_array($voteTarget,[$myId,$target],true))mp_error('Escolha uma terceira pessoa para o acordo.');
            }
            $request=['id'=>bin2hex(random_bytes(8)),'from'=>$myId,'to'=>$target,'type'=>$type,'label'=>$catalog[$type][0],
                'message'=>$message,'proposedTarget'=>$voteTarget,
                'subject'=>$catalog[$type][2][$subject],'approach'=>$approach,'status'=>'pendente','answer'=>null,'at'=>time()];
            $room['pending'][]=$request;$me['actions'][]=$request['id'];
            if(count($me['actions'])>=3)$me['done']=true;
            mp_log($room,'💬 '.($me['name']).' chamou '.(mp_find_player($room,$target)['name']??'?').' para uma conversa.','convite');
            return true;
        }
        if($action==='respond'){
            $id=$data['requestId']??'';$response=$data['answer']??'';
            if(!is_string($id)||!is_string($response))mp_error('Resposta inválida.');
            $reply=mp_small_message($data['reply']??'');
            foreach($room['pending'] as &$req){
                if($req['id']!==$id)continue;
                if($req['to']!==$myId||$req['status']!=='pendente')mp_error('Convite indisponível.',409);
                $allowed=array_column(mp_response_options((string)$req['type']),'value');
                if(!in_array($response,$allowed,true))mp_error('Resposta não permitida para esta interação.',400);
                $counterTarget=null;
                if($response==='contrapropor'){
                    if(!empty($req['counterDepth']))mp_error('Essa proposta já foi renegociada.',409);
                    $counterTarget=mp_input_id($room,$data['counterTarget']??null);
                    if(in_array($counterTarget,[$req['from'],$myId],true))mp_error('Escolha outra pessoa como alvo.');
                    if($counterTarget===($req['proposedTarget']??null))mp_error('Escolha um alvo diferente do proposto.',400);
                    if(count($room['pending'])>=100)mp_error('Limite de convites alcançado.',409);
                }
                $req['status']='respondido';$req['answer']=$response;$req['reply']=$reply;
                mp_apply_response($room,$req,$response,$reply);
                if($counterTarget){
                    $newReq=['id'=>bin2hex(random_bytes(8)),'from'=>$myId,'to'=>$req['from'],'type'=>'i4_acordo',
                        'label'=>'🎯 Contraproposta de acordo','subject'=>'🎯 Voto combinado',
                        'approach'=>'cautelosa','message'=>$reply,'proposedTarget'=>$counterTarget,
                        'counterDepth'=>1,'status'=>'pendente','answer'=>null,'at'=>time()];
                    $room['pending'][]=$newReq;
                    mp_private_log($room,[$myId,$req['from']],$me['name'].' sugeriu um novo alvo para um acordo de voto. A outra pessoa precisa confirmar.');
                }
                unset($req);return true;
            }unset($req);
            mp_error('Convite não encontrado.',404);
        }
        if($action==='finish'){$me['done']=true;return true;}
    }
    if($me['done'])mp_error('Você já concluiu esta fase.',409);
    if(($phase==='lider'||$phase==='anjo')&&$action==='prova'){
        $number=(int)($data['number']??0);if(!in_array($number,[1,2,3],true))mp_error('Escolha uma opção.');
        $me['choice']=['number'=>$number,'score'=>random_int(1,100)];$me['done']=true;return true;
    }
    if($phase==='vip_xepa'&&$action==='vip'){
        if($myId!==$room['leader'])mp_error('Apenas o Líder escolhe o VIP.',403);
        $ids=$data['ids']??[];if(!is_array($ids))mp_error('Seleção inválida.');
        $max=max(1,(int)floor(count(mp_active($room))/3));$selected=[$myId];
        foreach($ids as $id){if(!is_string($id)||!mp_find_player($room,$id))mp_error('Participante inválido.');
            if(!in_array($id,$selected,true))$selected[]=$id;}
        if(count($selected)>$max)mp_error('Excedeu as vagas VIP.');
        $room['vip']=$selected;$me['done']=true;return true;
    }
    if($phase==='imunidade'&&$action==='imune'){
        if($myId!==$room['angel'])mp_error('Apenas o Anjo decide.',403);
        $id=mp_input_id($room,$data['id']??null);
        if($id===$myId)mp_error('Não é possível imunizar a si nesta beta.');
        $room['imune']=$id;$me['done']=true;return true;
    }
    if($phase==='queridometro'&&$action==='queridometro'){
        $id=mp_input_id($room,$data['id']??null);if($id===$myId)mp_error('Escolha outra pessoa.');
        $emoji=$data['emoji']??'';if(!in_array($emoji,['💜','🙂','🤝','🔥','😶'],true))mp_error('Emoji inválido.');
        $me['choice']=['emoji'=>$emoji,'target'=>$id];$me['done']=true;
        mp_log($room,'💟 '.($me['name']).' enviou '.$emoji.' para '.mp_find_player($room,$id)['name'].'.');return true;
    }
    if($phase==='discordia'&&$action==='discordia'){
        $id=mp_input_id($room,$data['id']??null);if($id===$myId)mp_error('Escolha outra pessoa.');
        $reason=$data['reason']??'';
        $choices=['jogo'=>'joga dos dois lados','convivencia'=>'não sabe conviver','confianca'=>'quebrou sua confiança'];
        if(!is_string($reason)||!isset($choices[$reason]))mp_error('Motivo inválido.');
        $me['choice']=['target'=>$id,'reason'=>$reason];$me['done']=true;
        mp_log($room,'🔥 '.($me['name']).' apontou '.mp_find_player($room,$id)['name'].' no Jogo da Discórdia: “'.$choices[$reason].'”.');return true;
    }
    if($phase==='paredao'){
        if($action==='indicar'){
            if($myId!==$room['leader'])mp_error('Só o Líder pode indicar.',403);
            $id=mp_input_id($room,$data['id']??null);
            if($id===$room['leader']||$id===$room['imune'])mp_error('Não é possível indicar este participante.');
            $room['leaderPick']=$id;
            mp_log($room,'👑 O Líder indicou '.mp_find_player($room,$id)['name'].' ao Paredão.');
            return true;
        }
        if($action==='vote'){
            $id=mp_input_id($room,$data['id']??null);
            if($id===$myId||$id===$room['imune'])mp_error('Este voto não é permitido.');
            $me['choice']=['vote'=>$id];$me['done']=true;return true;
        }
    }
    mp_error('Ação indisponível nesta fase.',409);
});
mp_reply(['room'=>$state]);
