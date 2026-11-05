<?php
/** Multiplayer Beta 10: estado por sala isolado do PHP session do modo solo. */
declare(strict_types=1);
require_once __DIR__.'/multiplayer_provas.php';
require_once __DIR__.'/multiplayer_convivencia.php';
require_once __DIR__.'/multiplayer_paredao.php';
require_once __DIR__.'/multiplayer_feed.php';
require_once __DIR__.'/multiplayer_especiais.php';
require_once __DIR__.'/multiplayer_final.php';
function mp_error(string $message, int $status=400): never {
    http_response_code($status);
    echo json_encode(['ok'=>false,'erro'=>$message],JSON_UNESCAPED_UNICODE);
    exit;
}
function mp_dir(): string {
    // Salas temporárias fora da pasta pública do Apache.
    $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).'/bbb_simulator_multiplayer_b3';
    if (!is_dir($dir) && !@mkdir($dir,0700,true) && !is_dir($dir)) {
        mp_error('Armazenamento temporário indisponível.',503);
    }
    return $dir;
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
function mp_profile(mixed $data): array {
    if (!is_array($data)) mp_error('Preencha a ficha do seu participante antes de entrar na sala.');
    $age=filter_var($data['idade']??null,FILTER_VALIDATE_INT);
    if ($age===false || $age<18 || $age>70) mp_error('A idade do personagem deve estar entre 18 e 70 anos.');
    $job=is_string($data['profissao']??null)?trim($data['profissao']):'';
    if (!preg_match('//u',$job) || preg_match('/[\x00-\x1f\x7f]/u',$job) || preg_match_all('/./us',$job)>60 || preg_match_all('/./us',$job)<2) mp_error('Digite uma profissão entre 2 e 60 caracteres.');
    $state=strtoupper(trim((string)($data['estado']??'')));
    $states=explode(' ','AC AL AP AM BA CE DF ES GO MA MT MS MG PA PB PR PE PI RJ RN RS RO RR SC SP SE TO');
    if (!in_array($state,$states,true)) mp_error('Escolha um estado válido.');
    $personality=trim((string)($data['personalidade']??''));
    $valid=['Estrategista','Explosivo','Planta','Manipulador','Emocional','Barraqueiro','Fofo','Líder Nato','Influencer','Falso','Neutro'];
    if (!in_array($personality,$valid,true)) mp_error('Escolha uma personalidade disponível.');
    return ['idade'=>$age,'profissao'=>$job,'estado'=>$state,'personalidade'=>$personality];
}
function mp_player(string $name,string $token,array $profile=[]): array {
    return ['id'=>bin2hex(random_bytes(8)),'name'=>$name,'profile'=>$profile,'tokenHash'=>hash('sha256',$token),'ready'=>false,'done'=>false,'actions'=>[], 'choice'=>null,'eliminated'=>false,'role'=>[]];
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
/** Cada tipo de interação oferece respostas coerentes com a situação. */
function mp_response_options(string $type): array {
    $options=match($type) {
        'alianca'=>[['aceitar','🤝 Aceitar a aliança'],['ponderar','💭 Pedir tempo'],['recusar','🚫 Recusar o convite']],
        'i4_acordo'=>[['aceitar','🎯 Combinar o voto'],['contrapropor','🔄 Sugerir outro alvo'],['recusar','🚫 Não fazer acordo']],
        'i4_protecao'=>[['aceitar','🛡️ Prometer proteção'],['ponderar','💭 Não prometer nada'],['recusar','🚫 Recusar']],
        'i4_romance'=>[['retribuir','💗 Corresponder'],['amistoso','🌷 Manter na amizade'],['recusar','🧊 Não demonstrar interesse']],
        'i4_satisfacao','discutir'=>[['dialogar','🕊️ Conversar e esclarecer'],['rebater','🔥 Rebater a crítica'],['recusar','🚶 Encerrar o assunto']],
        'i4_reconciliar'=>[['aceitar','🤍 Fazer as pazes'],['ponderar','💭 Ainda tenho dúvidas'],['recusar','🧊 Não reconciliar']],
        'i4_atividade'=>[['aceitar','✨ Participar'],['ponderar','⏳ Combinar depois'],['recusar','🚫 Não participar']],
        'i4_sondar','i4_informacao'=>[['conversar','🗣️ Compartilhar impressões'],['ponderar','🤐 Falar com cautela'],['recusar','🔒 Não revelar']],
        'i4_pessoal'=>[['conversar','💜 Se abrir também'],['ponderar','👂 Apenas ouvir'],['recusar','🧊 Não conversar agora']],
        'festa_aproximar','festa_dancar'=>[['aceitar','💜 Participar'],['ponderar','🙂 Talvez depois'],['recusar','🚫 Recusar']],
        'festa_romance'=>[['retribuir','💘 Corresponder'],['amistoso','🌷 Ficar na amizade'],['recusar','🧊 Recusar']],
        'festa_provocar'=>[['dialogar','🕊️ Conversar'],['rebater','🔥 Rebater'],['recusar','🚶 Sair da conversa']],
        'festa_sondar'=>[['conversar','🗳️ Compartilhar apenas uma impressão'],['ponderar','🤐 Desconversar'],['recusar','🔒 Não revelar']],
        'festa_negociar'=>[['aceitar','🤝 Aceitar considerar o acordo'],['ponderar','💭 Pensar melhor'],['recusar','🚫 Recusar']],
        default=>[['aceitar','💬 Entrar na conversa'],['ponderar','🙂 Responder com cautela'],['recusar','🚫 Recusar']],
    };
    return array_map(static fn($x)=>['value'=>$x[0],'label'=>$x[1]],$options);
}
function mp_small_message(mixed $value,int $limit=160): string {
    if ($value===null || $value==='') return '';
    if(!is_string($value)||!preg_match('//u',$value))mp_error('Mensagem inválida.');
    $value=trim((string)preg_replace('/\s+/u',' ',$value));
    if(preg_match('/[\x00-\x1F\x7F]/u',$value) || preg_match_all('/./us',$value)>$limit)mp_error('A mensagem deve ter até '.$limit.' caracteres.');
    return $value;
}
function mp_private_log(array &$room,array $recipients,string $text): void {
    $room['privateLogs']??=[];
    $room['privateLogs'][]=['to'=>array_values(array_unique($recipients)),'text'=>$text,'at'=>time(),'week'=>$room['week'],'id'=>bin2hex(random_bytes(6))];
    if(count($room['privateLogs'])>100)$room['privateLogs']=array_slice($room['privateLogs'],-100);
}
function mp_apply_response(array &$room,array $req,string $answer,string $reply): void {
    $from=mp_find_player($room,$req['from'],false)['name']??'Participante';
    $to=mp_find_player($room,$req['to'],false)['name']??'Participante';
    $key=mp_relation_key($req['from'],$req['to']);
    $delta=match($answer){'aceitar','retribuir','dialogar','conversar'=>7,'amistoso','ponderar','contrapropor'=>2,'rebater'=>-6,'recusar'=>-4,default=>0};
    if(in_array($req['type'],['i4_satisfacao','discutir'],true) && $answer==='rebater')$delta=-9;
    $room['relations'][$key]=max(-100,min(100,(int)($room['relations'][$key]??0)+$delta));
    if($req['type']==='alianca' && $answer==='aceitar'){
        $room['alliances'][$key]=true;
        mp_private_log($room,[$req['from'],$req['to']],"🤝 $from e $to formaram uma aliança.");
    }
    if(in_array($req['type'],['i4_romance','festa_romance'],true) && $answer==='retribuir'){
        $room['romanceMoments'][$key]=min(20,((int)($room['romanceMoments'][$key]??0))+1);
        mp_private_log($room,[$req['from'],$req['to']],"💗 O interesse entre $from e $to foi correspondido. Isso não significa namoro oficial.");
    }
    if(in_array($req['type'],['i4_acordo','festa_negociar'],true)){
        $target=$req['proposedTarget']??null;
        $targetName=$target?(mp_find_player($room,$target,false)['name']??'um participante'):null;
        if($answer==='contrapropor'){
            mp_private_log($room,[$req['from'],$req['to']],"🔄 $to pediu para rever a proposta de voto de $from. Nenhum acordo foi fechado.");
        }elseif($answer==='aceitar' && $targetName){
            $room['voteAgreements']??=[];
            $room['voteAgreements'][]=['week'=>$room['week'],'a'=>$req['from'],'b'=>$req['to'],'target'=>$target,'status'=>'firmado'];
            mp_private_log($room,[$req['from'],$req['to']],"🎯 $from e $to combinaram votar em $targetName nesta semana. A decisão no confessionário permanece individual.");
        }
    }
    if($req['type']==='i4_protecao' && $answer==='aceitar'){
        $room['protectionPacts'][$key]=$room['week'];
    }
    if(str_starts_with($req['type'],'festa_')){
        $loc=mp7_festa_local(substr($req['type'],6));
        $verb=match($req['type']){
            'festa_romance'=>'uma aproximação romântica','festa_provocar'=>'um confronto',
            'festa_sondar'=>'o próximo voto','festa_negociar'=>'um possível acordo de voto',
            'festa_dancar'=>'dançar juntos',default=>'a convivência na festa'};
        $outcome=match($answer){'recusar'=>'recusou o convite','rebater'=>'respondeu de forma firme',
            'retribuir'=>'correspondeu à aproximação','aceitar'=>'aceitou a proposta',
            'conversar'=>'conversou sem se comprometer com o voto','ponderar'=>'preferiu pensar melhor',
            'amistoso'=>'preferiu ficar na amizade',default=>'participou da conversa'};
        mp_log($room,"🎉 Durante a festa $loc, $from procurou $to para falar sobre $verb. $to $outcome.",'festa',
          ['title'=>'🎉 Movimento na Festa','place'=>$loc]);
    } else mp_story_log($room,$req,$from,$to,$answer);
    if($reply!=='')mp_private_log($room,[$req['from'],$req['to']],"✉️ Resposta particular de $to para $from: “$reply”");
}
function mp_phases(): array {
    return [
        'interacoes_1'=>['💬 Interações','Todos podem fazer até três interações, como no modo solo.'],
        'lider'=>['👑 Prova do Líder','Todos jogam o mesmo desafio em tempo real: memória, lógica ou caixas.'],
        'vip_xepa'=>['🍽️ VIP & Xepa','O Líder escolhe quem vai para o VIP.'],
        'bigfone'=>['☎️ Big Fone','Atenda primeiro quando tocar: o servidor valida quem chegou antes.'],
        'anjo'=>['😇 Prova do Anjo','Prova igual para todos; o Líder não participa da disputa pelo Anjo.'],
        'monstro'=>['👹 Monstro do Anjo','O Anjo escolhe até duas pessoas para o castigo do Monstro.'],
        'imunidade'=>['🛡️ Imunidade do Anjo','O Anjo protege um participante.'],
        'queridometro'=>['💟 Queridômetro','Escolha um emoji para cada participante da casa.'],
        'interacoes_2'=>['💬 Interações','Nova rodada de três interações.'],
        'festa'=>['🎉 Festa','Três ações especiais: romance, VT, provocar, dançar, sondar ou negociar votos.'],
        'discordia'=>['🔥 Jogo da Discórdia','Apontamento, argumento, resposta e tréplica entre jogadores reais.'],
        'poder_bigfone'=>['☎️ Poder do Big Fone','A pessoa que atendeu escolhe o alvo de seu poder, em sigilo quando necessário.'],
        'paredao'=>['🔥 Formação do Paredão','O Líder indica e a casa vota em segredo.'],
        'contragolpe'=>['🔥 Contragolpe','O dono do contragolpe poderá puxar alguém elegível ao Paredão.'],
        'troca_emparedado'=>['🔄 Troca de Emparedado','O dono do poder poderá trocar um indicado da casa por alguém elegível.'],
        'desempate'=>['⚖️ Desempate do Líder','O Líder decide entre os participantes empatados na votação.'],
        'bate_volta'=>['🚗 Prova Bate e Volta','Indicados elegíveis disputam uma chance de escapar.'],
        'eliminacao'=>['🚪 Noite de Eliminação','Resultado da votação simulada do público com suspense e porcentagens.'],
        'top4_resistencia'=>['🏆 A Última Resistência','Top 4: oito ciclos de equilíbrio, foco e energia.'],
        'final_paredao'=>['🔥 Último Paredão','Os três não vencedores da resistência disputam duas vagas na final.'],
        'final_cerimonia'=>['🎤 Grande Final','Pedidos de voto, entrevistas e torcidas dos eliminados.'],
        'final'=>['🏆 Campeão','Coroação do vencedor e resultado do público simulado.'],
    ];
}
function mp_active(array $room): array {
    return array_values(array_filter($room['players'],static fn($p)=>empty($p['eliminated'])));
}
function mp_story_log(array &$room,array $request,string $from,string $to,string $answer): void {
    $topic=trim((string)($request['subject']??'a convivência'));
    $id=(string)($request['id']??'');
    $places=['jardim','área externa','sala','cozinha','piscina','academia','quarto'];
    $topicClean=trim((string)preg_replace('/^[^\p{L}\p{N}]+/u','',$topic));
    $topicPhrase=match($topicClean) {
        'Rumos do jogo'=>'os rumos do jogo',
        'Nossa relação'=>'a relação dos dois',
        'Convivência'=>'a convivência na casa',
        'Saudade de casa'=>'a saudade de casa',
        'Planos e sonhos'=>'seus planos e sonhos',
        'Como está se sentindo'=>'como estavam se sentindo',
        'Próximo Paredão'=>'o próximo Paredão',
        'Clima da casa'=>'o clima da casa',
        'Alianças'=>'as alianças',
        'Cozinhar juntos'=>'cozinhar juntos',
        'Piscina'=>'passar um tempo na piscina',
        'Treinar'=>'treinar',
        'Caminhar pelo jardim'=>'passear pelo jardim',
        default=>'o assunto "'.$topicClean.'"'
    };
    $subjectRaw=strtolower($topicClean);
    $place=$places[hexdec(substr(hash('sha256',$id),0,2))%count($places)];
    foreach (['cozinha'=>'cozinha','piscina'=>'piscina','treinar'=>'academia','jardim'=>'jardim','caminhar'=>'jardim'] as $needle=>$p) {
        if (str_contains($subjectRaw,$needle)) { $place=$p;break; }
    }
    $placePhrase=in_array($place,['jardim','quarto'],true)?'no '.$place:'na '.$place;
    $context=match($request['type']??'') {
        'i4_romance'=>'O encontro teve um clima mais pessoal, mas ninguém assumiu um compromisso público.',
        'i4_sondar','i4_acordo','i4_protecao','i4_informacao'=>'Os dois trocaram impressões sobre a estratégia, sem revelar para a casa detalhes de acordos privados.',
        'i4_satisfacao','discutir'=>'O clima ficou tenso enquanto expunham seus pontos de vista.',
        'alianca'=>'O assunto foi parceria de jogo; a aproximação poderá influenciar as próximas decisões.',
        default=>'A conversa movimentou a convivência e poderá influenciar a relação dos dois.'
    };
    $positive=in_array($answer,['aceitar','retribuir','conversar','dialogar'],true);
    if ($answer==='recusar') {
        $title='🧊 Convite recusado';
        $story="$from procurou $to $placePhrase para falar sobre $topicPhrase, mas $to preferiu não participar. A tentativa movimentou os bastidores da casa.";
    } elseif ($answer==='rebater') {
        $title='🔥 Clima de tensão';
        $story="Em um encontro $placePhrase, $from abordou $to sobre $topicPhrase. $to rebateu o assunto e os dois saíram da conversa sem chegar a um entendimento.";
    } elseif ($answer==='contrapropor') {
        $title='🎯 Estratégia em negociação';
        $story="$from e $to se encontraram $placePhrase para discutir $topicPhrase. $to propôs rever a ideia original; nenhum compromisso foi firmado naquele momento.";
    } elseif ($answer==='amistoso') {
        $title='💜 Conversa sincera';
        $story="$from e $to conversaram $placePhrase sobre $topicPhrase. $to preferiu manter a aproximação na amizade, sem criar expectativas românticas.";
    } elseif ($answer==='ponderar') {
        $title='👀 Assunto em aberto';
        $story="$from e $to se encontraram $placePhrase para falar sobre $topicPhrase. $to ouviu a proposta com cautela, mas não prometeu nada naquele momento.";
    } else {
        $title=$positive?'💬 Conversa na casa':'🎥 Papo reservado';
        $approach=match($request['approach']??'') {'cautelosa'=>'de maneira cautelosa','leve'=>'em um clima descontraído',default=>'com sinceridade'};
        $story="Durante um encontro $placePhrase, $from e $to conversaram sobre $topicPhrase. $to participou da conversa, que começou $approach. $context";
    }
    mp_log($room,$story,'interacao',['title'=>$title,'place'=>$place,'topic'=>$topicClean]);
}
function mp_log(array &$room,string $text,string $type='publico',array $metadata=[]): void {
    $room['events'][]=array_merge(['id'=>bin2hex(random_bytes(5)),'week'=>$room['week'],'text'=>$text,'at'=>time(),'type'=>$type],$metadata);
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
    if(in_array($phase,['eliminacao','final_paredao'],true))return 60;
    if($phase==='final_cerimonia')return 120;
    if($phase==='top4_resistencia')return 55;
    if($phase==='bigfone')return 40;
    if(in_array($phase,['poder_bigfone','contragolpe','troca_emparedado'],true))return 75;
    if(in_array($phase,['desempate','bate_volta'],true))return 90;
    if(in_array($phase,['lider','anjo'],true))return max(120,min(240,(int)$room['duration']));
    return max(60,min(150,$room['duration']));
}
function mp_set_phase(array &$room,string $phase): void {
    if($phase==='interacoes_1'){mp11_reta_final($room);mp10_week_start($room);}
    if($phase==='festa')mp10_secret_return($room);
    $room['phase']=$phase;$room['endsAt']=time()+mp_phase_time($room,$phase);$room['pending']=[];
    if(in_array($phase,['lider','anjo'],true))$room['prova']=mp_prova_criar($room,$phase);
    else unset($room['prova']);
    foreach($room['players'] as &$p){$p['done']=false;$p['actions']=[];$p['choice']=null;}
    unset($p);
    if($phase==='monstro')$room['monsterTasks']=[];
    if($phase==='bigfone')mp10_bigfone_start($room);
    if($phase==='paredao')mp8_iniciar_paredao($room);
    if($phase==='bate_volta')mp8_criar_bate_volta($room);
    if($phase==='eliminacao'||$phase==='final_paredao')mp8_preparar_eliminacao($room);
    if($phase==='top4_resistencia')mp11_start_top4($room);
    if($phase==='final_cerimonia')mp11_start_final($room);
    if($phase==='queridometro'){
        foreach($room['players'] as &$p)$p['queridometro']=[];
        unset($p);
    }
    if($phase==='discordia'){$room['debates']=[];$room['discordiaTema']=mp7_discordia_tema($room);}
    if(in_array($phase,['vip_xepa','imunidade','monstro'],true)){
        $controller=$phase==='vip_xepa'?$room['leader']:$room['angel'];
        foreach($room['players'] as &$p) if($p['id']!==$controller)$p['done']=true;
        unset($p);
    }
    if($phase==='anjo'){
        foreach($room['players'] as &$p)if($p['id']===($room['leader']??null))$p['done']=true;
        unset($p);
    }
    if($phase==='bigfone' && empty($room['bigFone']['ring'])){
        foreach($room['players'] as &$p)$p['done']=true;
        unset($p);
    }
    if($phase==='poder_bigfone'){
        foreach($room['players'] as &$p)$p['done']=$p['id']!==($room['bigFone']['answeredBy']??null);
        unset($p);
    }
    if($phase==='contragolpe'||$phase==='troca_emparedado'){
        foreach($room['players'] as &$p)$p['done']=$p['id']!==($room['contragolpe']['owner']??null);
        unset($p);
    }
    if($phase==='desempate'){
        foreach($room['players'] as &$p)$p['done']=$p['id']!==($room['leader']??null);
        unset($p);
    }
    if($phase==='bate_volta'){
        $eligible=$room['bateVolta']['participants']??[];
        foreach($room['players'] as &$p)$p['done']=!in_array($p['id'],$eligible,true);
        unset($p);
    }
    if(in_array($phase,['eliminacao','final','final_paredao'],true)){
        foreach($room['players'] as &$p)$p['done']=true;
        unset($p);
    }
    mp_log($room,'📺 A produção anunciou: '.mp_phases()[$phase][0].' • Semana '.$room['week'].'.');
    if(in_array($phase,['interacoes_1','interacoes_2','festa'],true))mp7_convivencia_evento($room,$phase);
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
    if(in_array($phase,['lider','anjo'],true))mp_prova_resultado($room,$phase);
    if($phase==='vip_xepa'){
        if(!$room['vip']) $room['vip']=[$room['leader']];
        $vipNames=array_map(static fn($id)=>mp_find_player($room,$id,false)['name']??'Participante',$room['vip']);
        mp_log($room,'🍽️ O Líder definiu o VIP: '.implode(', ',$vipNames).'. Os demais participantes ficam na Xepa.','vip',['title'=>'🍽️ Divisão VIP e Xepa']);
    }
    if($phase==='monstro'){
        if(empty($room['monstro']))mp_log($room,'👹 O Anjo não escolheu Monstros nesta rodada.','monstro',['title'=>'👹 Castigo do Monstro']);
        else{
            $nomes=array_map(static fn($id)=>mp_find_player($room,$id,false)['name']??'Participante',$room['monstro']);
            mp_log($room,'👹 O Anjo escolheu '.implode(' e ',$nomes).' para cumprir o Castigo do Monstro nesta semana.','monstro',['title'=>'👹 Monstros da Semana']);
        }
    }
    if($phase==='queridometro')mp7_concluir_queridometro($room);
    if($phase==='festa'){mp7_festa_concluir($room);mp10_post_festa($room);}
    if($phase==='discordia')mp7_discordia_concluir($room);
    if($phase==='imunidade'){
        if($room['imune'])mp_log($room,'🛡️ '.(mp_find_player($room,$room['imune'])['name']??'Alguém').' recebeu a imunidade.');
    }
    if($phase==='paredao'){mp8_apurar_votos($room);return;}
    if($phase==='bigfone'){mp_set_phase($room,mp11_count($room)<=8?'queridometro':'anjo');return;}
    if($phase==='poder_bigfone'){
        if(mp10_needs_power($room))mp_log($room,'⌛ O poder do Big Fone expirou sem um alvo confirmado.','bigfone');
        mp_set_phase($room,'paredao');return;
    }
    if($phase==='contragolpe'||$phase==='troca_emparedado'){
        mp_log($room,'⌛ O tempo da dinâmica especial acabou; nenhuma decisão foi confirmada.','dinamica');
        mp10_next_post_formation($room);return;
    }
    if($phase==='desempate'){mp8_desempatar($room,null);return;}
    if($phase==='bate_volta'){mp8_fechar_bate_volta($room);return;}
    if($phase==='top4_resistencia'){mp11_finish_top4_cycle($room);return;}
    if($phase==='final_paredao'){mp8_fechar_eliminacao($room);mp_set_phase($room,'final_cerimonia');return;}
    if($phase==='final_cerimonia'){mp11_final_result($room);mp_set_phase($room,'final');return;}
    if($phase==='eliminacao')mp8_fechar_eliminacao($room);
    $sequence=['interacoes_1','lider','vip_xepa','bigfone','anjo','monstro','imunidade','queridometro','interacoes_2','festa','discordia','poder_bigfone','paredao','desempate','bate_volta','eliminacao'];
    $position=array_search($phase,$sequence,true);
    if($phase==='final')return;
    if($phase==='eliminacao'){
        if(mp11_count($room)===4 && empty($room['secretRoom'])){mp_set_phase($room,'top4_resistencia');return;}
        if(mp11_count($room)<=3){mp_set_phase($room,'final_cerimonia');return;}
        $room['week']++;
        $room['leader']=null;$room['angel']=null;$room['vip']=[];$room['imune']=null;$room['monstro']=[];$room['monsterTasks']=[];
        $room['leaderPick']=null;$room['nominees']=[];$room['lastEliminated']=null;
        mp_set_phase($room,'interacoes_1');return;
    }
    if($phase==='vip_xepa' && mp11_count($room)<=5){mp_set_phase($room,'queridometro');return;}
    if($phase==='bigfone' && mp11_count($room)<=8){mp_set_phase($room,'queridometro');return;}
    if($phase==='festa' && mp11_count($room)<=8){mp_set_phase($room,mp10_needs_power($room)?'poder_bigfone':'paredao');return;}
    if($phase==='discordia'){mp_set_phase($room,mp10_needs_power($room)?'poder_bigfone':'paredao');return;}
    if($position!==false)mp_set_phase($room,$sequence[$position+1]);
}
function mp_tick(array &$room): bool {
    if(in_array($room['phase'],['lobby','final'],true))return false;
    if(in_array($room['phase'],['lider','anjo'],true) && !isset($room['prova'])){
        $room['prova']=mp_prova_criar($room,$room['phase']);
        if($room['phase']==='anjo')foreach($room['players'] as &$p){if($p['id']===($room['leader']??null))$p['done']=true;}
        unset($p);
        if($room['endsAt']<time()+35 && empty($room['paused']))$room['endsAt']=time()+120;
        return true;
    }
    if(!empty($room['paused']))return false;
    $force=time()>=$room['endsAt'];
    $allDone=true;
    foreach(mp_active($room) as $p)if(!$p['done'])$allDone=false;
    if(in_array($room['phase'],['interacoes_1','interacoes_2','festa'],true)){

        foreach($room['pending'] as $q)if($q['status']==='pendente')$allDone=false;
    }
    if($room['phase']==='discordia'){
        foreach(($room['debates']??[]) as $d)if(($d['status']??'')!=='concluido'&&($d['status']??'')!=='encerrado')$allDone=false;
    }
    if(in_array($room['phase'],['eliminacao','final_paredao','final_cerimonia'],true))$allDone=false;
    if($room['phase']==='top4_resistencia'){
        foreach(($room['top4State']['standing']??[]) as $id=>$standing)if($standing&&!isset($room['top4State']['answers'][$id]))$allDone=false;
    }
    if($force||$allDone){mp_finish_phase($room);return true;}
    return false;
}
function mp_public(array $room,int $self): array {
    $me=$room['players'][$self];
    $pending=array_values(array_filter($room['pending'],static fn($q)=>$q['to']===$me['id']&&$q['status']==='pendente'));
    $outgoing=array_values(array_filter($room['pending'],static fn($q)=>$q['from']===$me['id']));
    foreach($pending as &$inv){
        $inv['options']=!empty($inv['counterDepth'])?[
            ['value'=>'aceitar','label'=>'🤝 Aceitar o novo alvo'],
            ['value'=>'recusar','label'=>'🚫 Recusar a contraproposta']
        ]:mp_response_options((string)$inv['type']);
        if($inv['type']==='i4_acordo' && empty($inv['counterDepth'])){
            $available=false;
            foreach(mp_active($room) as $p)if(!in_array($p['id'],[$inv['from'],$inv['to'],$inv['proposedTarget']??null],true)){$available=true;break;}
            if(!$available)$inv['options']=array_values(array_filter($inv['options'],static fn($o)=>$o['value']!=='contrapropor'));
        }
    }
    unset($inv);
    $contacts=[];foreach($room['players'] as $p){if($p['id']===$me['id'])continue;
        $k=mp_relation_key($p['id'],$me['id']);
        if(!empty($room['alliances'][$k])||!empty($room['romanceMoments'][$k])||isset($room['protectionPacts'][$k]))
            $contacts[]=['id'=>$p['id'],'name'=>$p['name'],'alliance'=>!empty($room['alliances'][$k]),'romanceMoments'=>(int)($room['romanceMoments'][$k]??0),'protection'=>isset($room['protectionPacts'][$k])];
    }
    $agreements=array_values(array_filter($room['voteAgreements']??[],static fn($p)=>$p['a']===$me['id']||$p['b']===$me['id']));
    $privateLogs=array_values(array_filter($room['privateLogs']??[],static fn($p)=>in_array($me['id'],$p['to'],true))); 
    $players=[];
    foreach($room['players'] as $p)$players[]=[
        'id'=>$p['id'],'name'=>$p['name'],'profile'=>$p['profile']??[],'ready'=>$p['ready'],'done'=>$p['done'],
        'eliminated'=>$p['eliminated'],'actionsUsed'=>count($p['actions']),
        'isLeader'=>$room['leader']===$p['id'],'isAngel'=>$room['angel']===$p['id'],
        'isVIP'=>in_array($p['id'],$room['vip'],true),'isImmune'=>$room['imune']===$p['id'],
        'isMonster'=>in_array($p['id'],$room['monstro']??[],true),
        'isExtraImmune'=>in_array($p['id'],[$room['surprise']['extraImmune']??null,$room['bigFone']['extraImmune']??null],true),
        'monsterTaskDone'=>isset($room['monsterTasks'][$p['id']]),
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
        'paused'=>(bool)($room['paused']??false),'pauseRemaining'=>(int)($room['pauseRemaining']??0),
        'contacts'=>$contacts,'agreements'=>$agreements,'privateLogs'=>array_slice($privateLogs,-20),
        'proof'=>mp_prova_publica($room,$self),'lastProof'=>$room['lastProof']??null,
        'monsterIds'=>$room['monstro']??[],
        'emojis'=>mp7_emojis(),'myQueridometro'=>$me['queridometro']??[],
        'queridometroResult'=>($room['lastQueridometro']['week']??0)===$room['week'] && $room['phase']!=='queridometro' ? $room['lastQueridometro'] : null,
        'festaOptions'=>mp7_festa_opcoes(),
        'discordiaTema'=>$room['discordiaTema']??mp7_discordia_tema($room),
        'discordiaArgumentos'=>mp7_argumentos(),'discordiaRespostas'=>mp7_respostas(),'discordiaTreplicas'=>mp7_treplicas(),
        'debates'=>array_values($room['debates']??[]),
        'paredao'=>mp8_publico($room,$me['id']),
        'feedBBB'=>mp9_public($room),
        'specials'=>mp10_public($room,$me['id']),
        'finalStage'=>mp11_public($room,$me['id']),
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
    $changed=mp9_sync($room)||$changed;
    if($changed){$room['updatedAt']=time();rewind($f);ftruncate($f,0);
        fwrite($f,json_encode($room,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR));fflush($f);}
    flock($f,LOCK_UN);fclose($f);return mp_public($room,$self);
}
function mp_input_id(array $room,mixed $value,bool $allowSelf=false): string {
    if(!is_string($value)||!preg_match('/^[a-f0-9]{16}$/D',$value)||!mp_find_player($room,$value))mp_error('Participante inválido.');
    return $value;
}
