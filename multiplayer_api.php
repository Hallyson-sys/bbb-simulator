<?php
/** BBB Simulator – Multiplayer Beta 2; protótipo em PHP + JSON temporário. */
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
$raw=file_get_contents('php://input',false,null,0,4097);
if(!$raw||strlen($raw)>4096)mp_error('Solicitação inválida.',400);
$data=json_decode($raw,true);if(!is_array($data))mp_error('JSON inválido.');
$action=$data['action']??'';
if(random_int(1,150)===1)foreach(glob(mp_dir().'/*.json')?:[] as $f)if(filemtime($f)<time()-86400)@unlink($f);
function mp_reply(array $payload): never {echo json_encode(['ok'=>true,'serverTime'=>time()]+$payload,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
if($action==='create'){
    $name=mp_name($data['name']??null);$capacity=(int)($data['capacity']??0);
    if(!in_array($capacity,[10,15,20],true))mp_error('Escolha 10, 15 ou 20 vagas.');
    $token=bin2hex(random_bytes(24));$player=mp_player($name,$token);
    $room=[
        'code'=>'','capacity'=>$capacity,'phase'=>'lobby','week'=>1,'duration'=>180,'endsAt'=>0,
        'hostId'=>$player['id'],'players'=>[$player],'pending'=>[],'events'=>[],
        'relations'=>[],'alliances'=>[],'leader'=>null,'angel'=>null,'vip'=>[],
        'imune'=>null,'leaderPick'=>null,'nominees'=>[],'lastEliminated'=>null,
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
$code=mp_code(is_string($data['code']??null)?$data['code']:'');
if($action==='join'){
    $name=mp_name($data['name']??null);$token=bin2hex(random_bytes(24));
    $path=mp_path($code);if(!is_file($path))mp_error('Sala não encontrada.',404);
    $f=@fopen($path,'r+');if(!$f||!flock($f,LOCK_EX))mp_error('Sala indisponível.',503);
    $room=json_decode((string)stream_get_contents($f),true);if(!is_array($room))mp_error('Sala inválida.',503);
    if($room['phase']!=='lobby')mp_error('Partida já começou.',409);
    if(count($room['players'])>=$room['capacity'])mp_error('Sala lotada.',409);
    foreach($room['players'] as $p)if(strcasecmp($p['name'],$name)===0)mp_error('Nome já utilizado na sala.',409);
    $room['players'][]=mp_player($name,$token);$room['updatedAt']=time();rewind($f);ftruncate($f,0);
    fwrite($f,json_encode($room,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));fflush($f);flock($f,LOCK_UN);fclose($f);
    mp_reply(['accessToken'=>$token,'room'=>mp_public($room,count($room['players'])-1)]);
}
$token=is_string($data['token']??null)?$data['token']:'';
$state=mp_transact($code,$token,static function(array &$room,int $self)use($data,$action): bool{
    $me=&$room['players'][$self];$myId=$me['id'];$phase=$room['phase'];
    if($action==='status')return false;
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
            $request=['id'=>bin2hex(random_bytes(8)),'from'=>$myId,'to'=>$target,'type'=>$type,'label'=>$catalog[$type][0],
                'subject'=>$catalog[$type][2][$subject],'approach'=>$approach,'status'=>'pendente','answer'=>null,'at'=>time()];
            $room['pending'][]=$request;$me['actions'][]=$request['id'];
            if(count($me['actions'])>=3)$me['done']=true;
            mp_log($room,'💬 '.($me['name']).' chamou '.(mp_find_player($room,$target)['name']??'?').' para uma conversa.');
            return true;
        }
        if($action==='respond'){
            $id=$data['requestId']??'';$response=$data['answer']??'';
            if(!in_array($response,['aceitar','conversar','recusar'],true))mp_error('Resposta inválida.');
            foreach($room['pending'] as &$req){
                if($req['id']!==$id)continue;
                if($req['to']!==$myId||$req['status']!=='pendente')mp_error('Convite indisponível.',409);
                $req['status']='respondido';$req['answer']=$response;
                $delta=$response==='aceitar'?7:($response==='conversar'?2:-4);
                if(in_array($req['type'],['i4_satisfacao','discutir'],true) && $response==='aceitar')$delta=-2;
                $key=mp_relation_key($myId,$req['from']);
                $room['relations'][$key]=max(-100,min(100,($room['relations'][$key]??0)+$delta));
                if($req['type']==='alianca'&&$response==='aceitar')$room['alliances'][$key]=true;
                $actor=mp_find_player($room,$req['from']);
                mp_log($room,'📺 '.($me['name']).' '.($response==='recusar'?'recusou':($response==='aceitar'?'aceitou':'respondeu com cautela')).' a conversa de '.($actor['name']??'outro participante').'.');
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
