<?php
/** BBB Simulator Beta 10: estado de dinâmicas compartilhado, independente do jogo solo. */
declare(strict_types=1);
function mp10_power_labels(): array {return [
 'imunidade'=>'🛡️ Imunidade para quem atendeu',
 'imunidade_extra'=>'🛡️ Imunidade extra','voto_duplo'=>'⚡ Voto em dobro',
 'anular_voto'=>'🚫 Anular voto','espiar_voto'=>'👀 Espiar voto',
 'indicacao'=>'☎️ Indicação extra','contragolpe'=>'⚔️ Contragolpe',
 'trocar_emparedado'=>'🔄 Trocar um emparedado','trote'=>'☎️ Trote da produção',
];}
function mp10_surprise_labels(): array {return [
 'votacao_aberta'=>['🗳️ Votação Aberta','Votos individuais serão anunciados após a apuração.'],
 'imunidade_surpresa'=>['🛡️ Imunidade Surpresa','Depois da Festa, um participante elegível receberá imunidade.'],
 'voto_em_dobro'=>['⚡ Votação Turbinada','Cada voto válido da casa terá peso dois.'],
 'carta_misteriosa'=>['✉️ Carta Misteriosa','Uma carta lacrada será entregue depois da Festa. O conteúdo será revelado na entrega.'],
 'contragolpe_especial'=>['🔥 Contragolpe Especial','O indicado do Líder poderá puxar uma pessoa elegível após a votação.'],
 'paredao_especial'=>['💥 Paredão Especial','A formação terá até quatro indicados, respeitando as proteções.'],
];}
function mp10_week_start(array &$r): void {
 $r['bigFone']=['week'=>$r['week'],'ring'=>false,'answeredBy'=>null,'power'=>null,'target'=>null,'used'=>false];
 $r['surprise']=['week'=>$r['week'],'type'=>null,'revealed'=>false,'extraImmune'=>null,'cardOwner'=>null];
 $r['extraNominee']=null;$r['contragolpe']=null;$r['specialQueue']=[];
 if($r['week']<2||count(mp_active($r))<=5||$r['week']-(int)($r['lastSurpriseWeek']??-20)<2||random_int(1,100)>42)return;
 $choices=array_keys(mp10_surprise_labels());if(count(mp_active($r))<8)$choices=array_values(array_diff($choices,['paredao_especial']));
 $type=$choices[random_int(0,count($choices)-1)];$r['surprise']['type']=$type;$r['lastSurpriseWeek']=$r['week'];
 [$title,$description]=mp10_surprise_labels()[$type];
 mp_log($r,'🚨 DINÂMICA SURPRESA — '.$title.'. '.$description,'dinamica',['title'=>$title]);
}
function mp10_bigfone_start(array &$r): void {
 $r['bigFone']['ring']=count(mp_active($r))>5&&random_int(1,100)<=60;
 mp_log($r,!empty($r['bigFone']['ring'])?'☎️ O Big Fone está tocando! Quem atender primeiro pode receber um poder ou um trote.':'☎️ Nesta semana o Big Fone não tocou.','bigfone',['title'=>'☎️ Big Fone']);
}
function mp10_answer(array &$r,string $id): void {
 if($r['phase']!=='bigfone'||empty($r['bigFone']['ring']))mp_error('O telefone não está tocando.',409);
 if(!empty($r['bigFone']['answeredBy'])||time()>=$r['endsAt'])mp_error('O telefone já foi atendido ou parou de tocar.',409);
 if(!mp_find_player($r,$id))mp_error('Participante indisponível.',403);
 $powers=array_keys(mp10_power_labels());$power=$powers[random_int(0,count($powers)-1)];
 $r['bigFone']['answeredBy']=$id;$r['bigFone']['power']=$power;
 if(in_array($power,['imunidade','voto_duplo','contragolpe','trocar_emparedado','trote'],true))$r['bigFone']['used']=true;
 if($power==='imunidade'){
  $r['bigFone']['extraImmune']=$id;
  mp_log($r,'🛡️ O Big Fone concedeu imunidade a quem atendeu o telefone.','bigfone',['title'=>'🛡️ Imunidade do Big Fone']);
 }
 mp_log($r,'☎️ '.mp_find_player($r,$id)['name'].' atendeu primeiro o Big Fone. O recado foi revelado apenas para essa pessoa.','bigfone',['title'=>'☎️ Telefone atendido']);
 $description=match($power){
  'trote'=>'Nenhum poder foi concedido.',
  'imunidade'=>'Você está imune ao Paredão desta semana.',
  'voto_duplo'=>'Seu próximo voto válido terá peso dois.',
  'contragolpe'=>'Se você cair no Paredão, poderá puxar outra pessoa.',
  'trocar_emparedado'=>'Você poderá trocar um indicado da casa depois da formação, exceto a indicação do Líder.',
  default=>'Você escolherá o alvo antes do Paredão.',
 };
 mp_private_log($r,[$id],'☎️ Big Fone: '.mp10_power_labels()[$power].'. '.$description);
 mp_set_phase($r,'anjo');
}
function mp10_is_immune(array $r,string $id): bool {
 return in_array($id,[$r['leader']??null,$r['imune']??null,$r['surprise']['extraImmune']??null,$r['bigFone']['extraImmune']??null],true);
}
function mp10_needs_power(array $r): bool {
 $b=$r['bigFone']??[];
 return !empty($b['answeredBy'])&&in_array($b['power']??'', ['imunidade_extra','anular_voto','espiar_voto','indicacao'],true)&&empty($b['used'])&&mp_find_player($r,$b['answeredBy'])!==null;
}
function mp10_power_targets(array $r,string $type,string $owner): array {
 return array_values(array_filter(mp_active($r),static function($p)use($r,$type,$owner){
  $id=$p['id'];
  if($type==='espiar_voto'||$type==='anular_voto')return $id!==$owner&&$id!==($r['leader']??null);
  if($type==='indicacao'&&$id===$owner)return false;
  return !mp10_is_immune($r,$id);
 }));
}
function mp10_use_power(array &$r,string $actor,string $id): void {
 $b=$r['bigFone']??[];
 if($r['phase']!=='poder_bigfone'||!mp10_needs_power($r))mp_error('Poder indisponível.',409);
 if(($b['answeredBy']??null)!==$actor)mp_error('Somente quem atendeu pode usar.',403);
 if(!in_array($id,array_column(mp10_power_targets($r,$b['power'],$actor),'id'),true))mp_error('Alvo inválido.',400);
 $r['bigFone']['target']=$id;$r['bigFone']['used']=true;
 $name=mp_find_player($r,$id,false)['name']??'Participante';
 if($b['power']==='imunidade_extra'){
  $r['bigFone']['extraImmune']=$id;
  mp_log($r,'🛡️ Um poder do Big Fone protegeu '.$name.' nesta semana.','bigfone',['title'=>'🛡️ Imunidade extra']);
 }elseif($b['power']==='indicacao'){
  $r['extraNominee']=$id;
  mp_log($r,'☎️ Indicação extra do Big Fone: '.$name.'.','bigfone',['title'=>'☎️ Indicação extra']);
 }else{
  mp_private_log($r,[$actor],'🔐 Poder confirmado: '.mp10_power_labels()[$b['power']].' — '.$name.'. O efeito acontecerá na votação.');
  mp_log($r,'☎️ Um poder estratégico secreto foi utilizado.','bigfone',['title'=>'☎️ Poder estratégico']);
 }
 mp_set_phase($r,'paredao');
}
function mp10_post_festa(array &$r): void {
 $type=$r['surprise']['type']??null;
 if(!in_array($type,['imunidade_surpresa','carta_misteriosa'],true)||!empty($r['surprise']['revealed']))return;
 $r['surprise']['revealed']=true;
 $list=array_values(array_filter(mp_active($r),static fn($p)=>!mp10_is_immune($r,$p['id'])));
 if(!$list){mp_log($r,'🚨 Nenhum participante elegível para receber a surpresa.','dinamica');return;}
 $id=$list[random_int(0,count($list)-1)]['id'];$name=mp_find_player($r,$id,false)['name'];
 if($type==='imunidade_surpresa'){
  $r['surprise']['extraImmune']=$id;
  mp_log($r,'🛡️ Depois da Festa, '.$name.' ganhou imunidade surpresa nesta semana.','dinamica',['title'=>'🛡️ Imunidade surpresa']);
 }else{
  $r['surprise']['cardOwner']=$id;
  mp_log($r,'✉️ Depois da Festa, '.$name.' recebeu a Carta Misteriosa, com um voto de peso dois.','dinamica',['title'=>'✉️ Carta Misteriosa']);
  mp_private_log($r,[$id],'✉️ Sua Carta Misteriosa concede peso dois ao seu próximo voto válido desta semana.');
 }
}
function mp10_vote_weight(array $r,string $voter): int {
 if(($r['surprise']['type']??null)==='voto_em_dobro')return 2;
 if(($r['surprise']['type']??null)==='carta_misteriosa'&&($r['surprise']['cardOwner']??null)===$voter)return 2;
 if(($r['bigFone']['power']??null)==='voto_duplo'&&($r['bigFone']['answeredBy']??null)===$voter)return 2;
 return 1;
}
function mp10_vote_cancelled(array $r,string $id): bool {
 $b=$r['bigFone']??[];return !empty($b['used'])&&($b['power']??null)==='anular_voto'&&($b['target']??null)===$id;
}
function mp10_vote_audit(array &$r): void {
 $b=$r['bigFone']??[];
 if(!empty($b['used'])&&($b['power']??null)==='espiar_voto'&&!empty($b['answeredBy'])){
  $target=$b['target']??null;$choice=null;
  foreach($r['players'] as $p)if($p['id']===$target){$choice=$p['choice']['vote']??null;break;}
  $name=mp_find_player($r,(string)$target,false)['name']??'Participante';
  $voted=$choice?(mp_find_player($r,$choice,false)['name']??'Participante'):'ninguém (não votou)';
  mp_private_log($r,[$b['answeredBy']],'👀 Espiadinha: '.$name.' votou em '.$voted.'. Só você pode ver.');
 }
 if(($r['surprise']['type']??null)==='votacao_aberta'){
  foreach(mp_active($r) as $p){
   if($p['id']===($r['leader']??null))continue;
   $id=$p['choice']['vote']??null;
   mp_log($r,'🗳️ VOTAÇÃO ABERTA — '.$p['name'].' votou em '.($id?(mp_find_player($r,$id,false)['name']??'Participante'):'ninguém').'.','dinamica',['title'=>'🗳️ Voto anunciado']);
  }
 }
}
function mp10_after_formation(array &$r): bool {
 $queue=[];$b=$r['bigFone']??[];
 if(($r['surprise']['type']??null)==='contragolpe_especial')$queue[]=['phase'=>'contragolpe','owner'=>$r['leaderPick']];
 if(($b['power']??null)==='contragolpe'&&in_array($b['answeredBy']??null,$r['nominees']??[],true))$queue[]=['phase'=>'contragolpe','owner'=>$b['answeredBy']];
 if(($b['power']??null)==='trocar_emparedado'&&mp_find_player($r,(string)($b['answeredBy']??'')))$queue[]=['phase'=>'troca_emparedado','owner'=>$b['answeredBy']];
 $r['specialQueue']=$queue;
 return mp10_next_post_formation($r);
}
function mp10_next_post_formation(array &$r): bool {
 while($task=array_shift($r['specialQueue'])){
  $r['contragolpe']=['owner'=>$task['owner'],'target'=>null];
  if($task['phase']==='contragolpe'){
   if(mp10_contragolpe_targets($r)) {mp_set_phase($r,'contragolpe');return true;}
  } else {
   if(mp10_swap_nominees($r)&&mp10_swap_replacements($r)) {mp_set_phase($r,'troca_emparedado');return true;}
  }
 }
 mp10_go_after_formation($r);return false;
}
function mp10_contragolpe_targets(array $r): array {
 return array_values(array_filter(mp_active($r),static fn($p)=>!in_array($p['id'],$r['nominees']??[],true)&&!mp10_is_immune($r,$p['id'])));
}
function mp10_go_after_formation(array &$r): void {
 if(!empty($r['paredaoPlano']['bateVolta'])&&count($r['nominees'])>=4)mp_set_phase($r,'bate_volta');
 else mp_set_phase($r,'eliminacao');
}
function mp10_contragolpe_use(array &$r,string $actor,string $id): void {
 if($r['phase']!=='contragolpe'||$actor!==($r['contragolpe']['owner']??null))mp_error('Este contragolpe não pertence a você.',403);
 if(!in_array($id,array_column(mp10_contragolpe_targets($r),'id'),true))mp_error('Alvo inválido.',400);
 $r['contragolpe']['target']=$id;$r['nominees'][]=$id;
 mp_log($r,'🔥 Contragolpe: '.mp_find_player($r,$actor,false)['name'].' puxou '.mp_find_player($r,$id,false)['name'].' ao Paredão.','dinamica',['title'=>'🔥 Contragolpe']);
 mp10_next_post_formation($r);
}
function mp10_swap_nominees(array $r): array {
 return array_values(array_filter($r['nominees']??[],static fn($id)=>$id!==($r['leaderPick']??null)));
}
function mp10_swap_replacements(array $r): array {
 return array_values(array_filter(mp_active($r),static fn($p)=>!in_array($p['id'],$r['nominees']??[],true)&&!mp10_is_immune($r,$p['id'])));
}
function mp10_swap_use(array &$r,string $actor,string $out,string $in): void {
 if($r['phase']!=='troca_emparedado'||$actor!==($r['contragolpe']['owner']??null))mp_error('Esta troca não pertence a você.',403);
 if(!in_array($out,mp10_swap_nominees($r),true) || !in_array($in,array_column(mp10_swap_replacements($r),'id'),true))mp_error('Troca inválida ou participante protegido.',400);
 $idx=array_search($out,$r['nominees'],true);$r['nominees'][$idx]=$in;
 mp_log($r,'🔄 Poder do Big Fone: '.(mp_find_player($r,$out,false)['name']??'Participante').' saiu do Paredão e '.(mp_find_player($r,$in,false)['name']??'Participante').' ocupou sua vaga.','bigfone',['title'=>'🔄 Troca no Paredão']);
 mp10_next_post_formation($r);
}
function mp10_false_elimination(array &$r,string $id): bool {
 if(!empty($r['falseWallUsed'])||$r['week']<3||count(mp_active($r))<7||random_int(1,100)>18)return false;
 $r['falseWallUsed']=true;$r['secretRoom']=['id'=>$id,'week'=>$r['week'],'returnWeek'=>$r['week']+1];
 mp_private_log($r,[$id],'🚪 QUARTO SECRETO! Você foi falsamente eliminado. Fique de olho no Ao Vivo: seu retorno está marcado para a semana seguinte, antes da Festa. Não conte à casa.');
 return true;
}
function mp10_secret_return(array &$r): void {
 $sr=$r['secretRoom']??null;if(!is_array($sr)||($sr['returnWeek']??PHP_INT_MAX)>$r['week'])return;
 $id=$sr['id'];
 foreach($r['players'] as &$p)if($p['id']===$id)$p['eliminated']=false;
 unset($p);$r['secretRoom']=null;
 mp_log($r,'🚨 O Paredão era falso! '.(mp_find_player($r,$id,false)['name']??'Participante').' retorna do Quarto Secreto!','dinamica',['title'=>'🚪 Retorno do Quarto Secreto']);
}
function mp10_public(array $r,string $me): array {
 $b=$r['bigFone']??[];$owner=($b['answeredBy']??null)===$me;
 $type=$r['surprise']['type']??null;$s=$type?(mp10_surprise_labels()[$type]??null):null;
 $sr=$r['secretRoom']??null;
 return ['bigFone'=>[
  'ring'=>!empty($b['ring']),'answered'=>!empty($b['answeredBy']),
  'answeredBy'=>$b['answeredBy']??null,'mine'=>$owner,
  'power'=>$owner?($b['power']??null):null,
  'powerLabel'=>$owner?(mp10_power_labels()[$b['power']??'']??null):null,
  'used'=>$owner?($b['used']??false):null,
  ],
  'surprise'=>$s?['type'=>$type,'title'=>$s[0],'description'=>$s[1]]:null,
  'secretRoom'=>is_array($sr)&&($sr['id']??null)===$me?['active'=>true,'returnWeek'=>$sr['returnWeek']]:null,
  'powerTargets'=>$owner&&$r['phase']==='poder_bigfone'?array_column(mp10_power_targets($r,$b['power'],$me),'id'):[],
  'extraNomineeId'=>$r['extraNominee']??null,
  'contragolpeTargets'=>$r['phase']==='contragolpe'&&($r['contragolpe']['owner']??null)===$me?array_column(mp10_contragolpe_targets($r),'id'):[],
  'swapNominees'=>$r['phase']==='troca_emparedado'&&($r['contragolpe']['owner']??null)===$me?mp10_swap_nominees($r):[],
  'swapReplacements'=>$r['phase']==='troca_emparedado'&&($r['contragolpe']['owner']??null)===$me?array_column(mp10_swap_replacements($r),'id'):[],
 ];
}
