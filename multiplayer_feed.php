<?php
/**
 * BBB Simulator Multiplayer Beta 9 — Feed BBB 3.0 adaptado do modo solo.
 * Apenas acontecimentos PUBLICAMENTE registrados alimentam posts, torcidas,
 * tendências e mutirões. Nunca lê votos individuais, conversas privadas,
 * relações privadas ou resultados ainda não divulgados.
 */
declare(strict_types=1);

function mp9_hashtag(string $name): string {
    $clean=function_exists('iconv') ? iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$name) : $name;
    if ($clean===false) $clean=$name;
    $clean=preg_replace('/[^A-Za-z0-9]/','',$clean);
    return substr($clean?:'Participante',0,30);
}
function mp9_base_tags(string $type): array {
    return match($type){
        'prova','lider'=>['#ProvaDoLider','#BBBSimulator'],
        'anjo'=>['#ProvaDoAnjo','#BBBSimulator'],
        'monstro'=>['#CastigoDoMonstro','#BBBSimulator'],
        'festa'=>['#FestaBBB','#BBBSimulator'],
        'discordia'=>['#JogoDaDiscordia','#BBBSimulator'],
        'paredao'=>['#Paredao','#BBBSimulator'],
        'eliminacao'=>['#Eliminacao','#BBBSimulator'],
        'queridometro'=>['#Queridometro','#BBBSimulator'],
        'interacao'=>['#NaCasa','#BBBSimulator'],
        'vip'=>['#VipEXepa','#BBBSimulator'],
        'convivencia'=>['#RotinaDaCasa','#BBBSimulator'],
        default=>['#BBBSimulator'],
    };
}
function mp9_public_mentions(array $room,string $text): array {
    $names=[];
    foreach(($room['players']??[]) as $p){
        $name=(string)($p['name']??'');
        if($name!=='' && preg_match('/(?<![\p{L}\p{N}])'.preg_quote($name,'/').'(?![\p{L}\p{N}])/u',$text)){
            $names[]=['id'=>(string)$p['id'],'name'=>$name,'eliminated'=>!empty($p['eliminated'])];
        }
    }
    return $names;
}
function mp9_add_post(array &$room,string $source,string $type,string $text,array $tags,array $extra=[]): void {
    $room['feedBBB']['posts'][]=array_merge([
        'id'=>substr(hash('sha256',$room['code'].'|'.$source.'|'.$type),0,20),
        'sourceId'=>$source,'week'=>(int)$room['week'],'type'=>$type,
        'author'=>$type==='mutirao'?'Central de Mutirões':($type==='torcida'?'Radar das Torcidas':'Central BBB'),
        'handle'=>$type==='mutirao'?'@centralmutiroes':($type==='torcida'?'@radartorcida':'@centralbbb'),
        'text'=>$text,'tags'=>array_values(array_unique($tags)),'at'=>time(),
        // Engajamento ilustrativo, derivado de hash: nunca são cliques reais.
        'likes'=>120+(hexdec(substr(hash('sha256','likes|'.$source),0,5))%4700),
        'comments'=>18+(hexdec(substr(hash('sha256','comments|'.$source),0,4))%430),
        'shares'=>7+(hexdec(substr(hash('sha256','shares|'.$source),0,4))%390),
    ],$extra);
    if(count($room['feedBBB']['posts'])>100)$room['feedBBB']['posts']=array_slice($room['feedBBB']['posts'],-100);
}
function mp9_event_type(array $event): ?string {
    $type=(string)($event['type']??'');
    $accepted=['prova','lider','anjo','monstro','festa','discordia','paredao','eliminacao','queridometro','interacao','vip','convivencia','publico'];
    return in_array($type,$accepted,true)?$type:null;
}
function mp9_reaction(array &$room,array $event,array $mentions,string $type): void {
    if(!$mentions)return;
    $week=(int)($event['week']??$room['week']);$key=(string)($event['id']??'');
    // Mesma regra do Feed 3.0 solo: no máximo seis reações de torcida por semana.
    if(($room['feedBBB']['reactionCounts'][$week]??0)>=6)return;
    $trigger=['prova','lider','anjo','monstro','discordia','paredao','eliminacao','festa','interacao'];
    if(!in_array($type,$trigger,true))return;
    $first=$mentions[0];$name=$first['name'];$id=$first['id'];
    $tag='#Team'.mp9_hashtag($name);
    $templates=match($type){
        'prova','lider'=>["A torcida de $name está revendo os melhores momentos da prova. $tag", "A disputa de $name virou assunto entre quem acompanha a edição. $tag"],
        'anjo'=>["A torcida de $name comenta a disputa do Anjo. $tag"],
        'monstro'=>["O Castigo do Monstro envolvendo $name dividiu opiniões. $tag"],
        'discordia'=>["A participação de $name no Jogo da Discórdia dividiu as opiniões nas redes. $tag", "As torcidas discutem o posicionamento de $name no debate. $tag"],
        'paredao'=>["A semana de Paredão movimentou quem acompanha $name. $tag"],
        'eliminacao'=>["Quem acompanhou $name está revendo os momentos marcantes da temporada. $tag"],
        'festa'=>["A festa rendeu comentários entre quem acompanha $name. $tag"],
        'interacao'=>["A conversa de $name na casa gerou repercussão entre os fãs. $tag"],
        default=>[],
    };
    if(!$templates)return;
    $choice=hexdec(substr(hash('sha256',$key),0,2))%count($templates);
    mp9_add_post($room,'reacao_'.$key,'torcida',$templates[$choice],[$tag,'#BBBSimulator'],['sourceWeek'=>$week,'week'=>$week]);
    $room['feedBBB']['reactionCounts'][$week]=($room['feedBBB']['reactionCounts'][$week]??0)+1;
    if(!$first['eliminated']){
        $room['feedBBB']['fans'][$week][$id]=['id'=>$id,'name'=>$name,'tag'=>$tag,'topic'=>$type];
    }
    // Repercussão pública afeta muito pouco o termômetro simulado; não divulga
    // nem usa dados sociais privados para fabricar a torcida.
    $delta=match($type){'lider','anjo','prova'=>2,'discordia'=>-1,'eliminacao'=>0,default=>1};
    $room['feedBBB']['popularity'][$id]=max(0,min(100,($room['feedBBB']['popularity'][$id]??50)+$delta));
}
function mp9_mobilizations(array &$room): bool {
    if(!in_array($room['phase']??'',['eliminacao','final'],true))return false;
    $week=(int)($room['week']??1);$added=false;
    $nominees=array_values(array_unique((array)($room['nominees']??[])));
    if(count($nominees)<2)return false;
    foreach($nominees as $id){
        $p=mp_find_player($room,(string)$id,false);
        if(!$p||!empty($p['eliminated']))continue;
        $id=(string)$p['id'];$key="$week|$id";
        if(isset($room['feedBBB']['mutiroesDone'][$key]))continue;
        $name=$p['name'];$tag=mp9_hashtag($name);
        $ficar='#Fica'.$tag;$fora='#Fora'.$tag;
        $room['feedBBB']['mutiroesDone'][$key]=true;
        $room['feedBBB']['mutiroes'][$week][$id]=['id'=>$id,'name'=>$name,'ficar'=>$ficar,'fora'=>$fora];
        mp9_add_post($room,'mutirao_'.$key,'mutirao',"Paredão formado! Torcidas organizaram mobilizações em torno de $name: $ficar e $fora. A votação do público é simulada nesta versão.",[$ficar,$fora,'#Paredao']);
        $added=true;
        // Equivalente ao impacto leve e oculto do Feed 3.0 solo.
        $variation=(hexdec(substr(hash('sha256','mobilizacao|'.$room['code'].'|'.$key),0,2))%3)-1;
        $room['feedBBB']['popularity'][$id]=max(0,min(100,($room['feedBBB']['popularity'][$id]??50)+$variation));
    }
    return $added;
}
function mp9_sync(array &$room): bool {
    $init=!isset($room['feedBBB'])||!is_array($room['feedBBB']);
    $room['feedBBB']??=[];
    $room['feedBBB']+=['posts'=>[],'processed'=>[],'reactionCounts'=>[],'fans'=>[],
        'mutiroesDone'=>[],'mutiroes'=>[],'popularity'=>[]];
    $changed=$init;
    foreach(($room['events']??[]) as $event){
        $id=(string)($event['id']??'');$type=mp9_event_type($event);
        if($id===''||!$type||isset($room['feedBBB']['processed'][$id]))continue;
        $room['feedBBB']['processed'][$id]=true;$changed=true;
        // A fonte é o registro de acontecimentos públicos; textos privados sequer
        // entram neste fluxo. Nunca use privateLogs, choice/vote ou pending.
        $text=trim((string)($event['text']??''));
        if($text==='')continue;
        $mentions=mp9_public_mentions($room,$text);
        $tags=mp9_base_tags($type);
        if($mentions)$tags[]='#Team'.mp9_hashtag($mentions[0]['name']);
        $category=($type==='publico')?'geral':'acontecimento';
        mp9_add_post($room,'evento_'.$id,$category,$text,$tags,[
            'week'=>(int)($event['week']??$room['week']),
            'author'=>'Central BBB','handle'=>'@centralbbb','at'=>(int)($event['at']??time()),
            'headline'=>(string)($event['title']??'Aconteceu na casa'),
        ]);
        mp9_reaction($room,$event,$mentions,$type);
    }
    if(mp9_mobilizations($room))$changed=true;
    // Mantenha o índice limitado, sem duplicar publicações ao receber status.
    if(count($room['feedBBB']['processed'])>400)$room['feedBBB']['processed']=array_slice($room['feedBBB']['processed'],-300,null,true);
    return $changed;
}
function mp9_public(array $room): array {
    $feed=$room['feedBBB']??[];$week=(int)($room['week']??1);
    $posts=array_reverse(array_slice($feed['posts']??[],-80));
    $tags=[];
    foreach($feed['posts']??[] as $post){
        if((int)$post['week']!==$week)continue;
        foreach($post['tags']??[] as $tag) {
            if($tag==='#BBBSimulator')continue; // destaque para assuntos específicos da semana
            $tags[$tag]=($tags[$tag]??0)+1;
        }
    }
    foreach(($feed['mutiroes'][$week]??[]) as $m){foreach(['ficar','fora'] as $k)$tags[$m[$k]]=($tags[$m[$k]]??0)+10;}
    foreach(($feed['fans'][$week]??[]) as $fan)$tags[$fan['tag']]=($tags[$fan['tag']]??0)+4;
    arsort($tags);
    $trending=[];foreach(array_slice($tags,0,8,true) as $tag=>$count)$trending[]=['tag'=>$tag,'mentions'=>$count];
    return [
        'posts'=>$posts,'trending'=>$trending,
        'torcidas'=>array_values(array_slice($feed['fans'][$week]??[],-6,null,true)),
        'mutiroes'=>array_values($feed['mutiroes'][$week]??[]),
        'week'=>$week,
    ];
}
