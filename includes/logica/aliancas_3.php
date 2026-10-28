<?php
/** Alianças 3.0. Estado social secreto do grupo, conservado em $_SESSION e nos saves. */
function a3Rodada(): int { return (int)($_SESSION['rodada']??1); }
function a3Limitar($n): int { return max(0,min(100,(int)round($n))); }
function a3Relacao(array $jogadores,string $a,string $b): array {
    if (!function_exists('obterRelacaoCompleta')) return [];
    return obterRelacaoCompleta($jogadores,$a,$b,(string)($_SESSION['meu_nome']??''));
}
function a3Lealdade(array $jogadores,string $nome): int {
    $grupo=obterAliancaJogador($jogadores,$nome);
    if(!$grupo) return 0;
    $membros=array_values(array_filter(membrosDaAlianca($jogadores,$grupo),fn($n)=>!nomeIgual($n,$nome)));
    if(!$membros) return 0;
    $total=0;
    foreach($membros as $m) {
        $r=a3Relacao($jogadores,$nome,$m);
        $total+=(float)($r['amizade']??35)*.35+(float)($r['confianca']??35)*.58-(float)($r['rivalidade']??0)*.48;
    }
    $bonus=(int)($_SESSION['a3_ajustes'][$nome]??0);
    return a3Limitar(33+$total/count($membros)+$bonus);
}
function a3Preparar(array &$jogadores,string $meuNome): void {
    $r=a3Rodada(); if($r<2 || ($_SESSION['fase_semana']??'')!=='interacoes_1' || ($_SESSION['a3_preparada_rodada']??0)===$r) return;
    $_SESSION['a3_preparada_rodada']=$r;
    if (!empty($_SESSION['a3_convite_pendente']) &&
        (int)($_SESSION['a3_convite_pendente']['rodada']??0) !== $r) {
        unset($_SESSION['a3_convite_pendente']);
    }
    $grupos=[];
    foreach($jogadores as $j) if(!empty($j['alianca']) && empty($j['eliminado'])) $grupos[(string)$j['alianca']]=true;
    // Convite individual ao jogador: NPCs não podem colocá-lo numa aliança
    // automaticamente. O pedido fica pendente até ACEITAR ou RECUSAR.
    if (empty($_SESSION['modo_espectador']) && $meuNome !== '' &&
        !obterAliancaJogador($jogadores, $meuNome) &&
        empty($_SESSION['a3_convite_pendente']) && random_int(1,100)<=43) {
        $candidatos=[];
        foreach($jogadores as $j) {
            $nome=(string)($j['nome']??'');
            if ($nome==='' || nomeIgual($nome,$meuNome) || !empty($j['eliminado'])) continue;
            $grupo=(string)($j['alianca']??'');
            if ($grupo!=='' && tamanhoAlianca($jogadores,$grupo)>=5) continue;
            $rel=a3Relacao($jogadores,$nome,$meuNome);
            $score=(float)($rel['amizade']??30) + (float)($rel['confianca']??25) - (float)($rel['rivalidade']??0);
            if ($score>=50) $candidatos[]=['nome'=>$nome,'grupo'=>$grupo,'score'=>$score];
        }
        usort($candidatos,fn($a,$b)=>$b['score']<=>$a['score']);
        if($candidatos) {
            $c=$candidatos[random_int(0,min(2,count($candidatos)-1))];
            $_SESSION['a3_convite_pendente']=['rodada'=>$r,'autor'=>$c['nome'],'grupo'=>$c['grupo']];
        }
    }
    foreach(array_keys($grupos) as $grupo) {
        $membros=membrosDaAlianca($jogadores,$grupo);
        if(count($membros)<2) continue;
        // NPCs também discutem alvos sem depender da iniciativa do jogador.
        // Reuniões de outras alianças são privadas, não viram spoiler no Ao Vivo.
        if (count($membros)>=2 && random_int(1,100)<=34) {
            $possiveis=[];
            foreach($jogadores as $j) {
                $n=(string)($j['nome']??'');
                if(a3AlvoValido($jogadores,$grupo,$n))$possiveis[]=$n;
            }
            if($possiveis) {
                $alvo=$possiveis[array_rand($possiveis)];
                foreach($membros as $m) {
                    if($m===$meuNome || a3Lealdade($jogadores,$m)<42) continue;
                    $rAlvo=a3Relacao($jogadores,$m,$alvo);
                    $chance=min(88,max(10,a3Lealdade($jogadores,$m)*.46 + (float)($rAlvo['rivalidade']??0)*.24));
                    if(random_int(1,100)<=$chance)$_SESSION['a3_planos'][$m]=['rodada'=>$r,'alvo'=>$alvo,'grupo'=>$grupo,'forca'=>random_int(18,34)];
                }
                if(in_array($meuNome,$membros,true) && random_int(1,100)<=40)
                    $_SESSION['a3_sugestao']=['rodada'=>$r,'grupo'=>$grupo,'alvo'=>$alvo];
            }
        }
        $tensos=[];
        foreach($membros as $m) if(a3Lealdade($jogadores,$m)<30)$tensos[]=$m;
        // As dúvidas dentro de outros grupos não são conhecidas automaticamente pelo jogador.
        if($tensos) {
            $_SESSION['a3_tensoes'][$grupo]=['rodada'=>$r,'nomes'=>$tensos];
            if(in_array($meuNome,$membros,true)) {
                $_SESSION['a3_aviso']=$grupo.' está passando por um momento de desconfiança. Uma reunião pode ajudar.';
            } elseif(random_int(1,100)<=16 && count($tensos)>0) {
                // Saída de NPC é um acontecimento observável, sem revelar votos/segredos.
                $nome=$tensos[array_rand($tensos)];
                $evento=romperAlianca($jogadores,$nome,'o desgaste na convivência com o grupo');
                if($evento) $_SESSION['evento_extra'][]='🤝 Uma aliança da casa perdeu um integrante depois de divergências de convivência.';
            }
        }
    }
}
function a3AlvoValido(array $jogadores,string $grupo,string $alvo): bool {
    if($alvo==='' || !indiceJogadorPorNomeExiste3($jogadores,$alvo)) return false;
    foreach($jogadores as $j) if(nomeIgual((string)($j['nome']??''),$alvo)) {
        if(!empty($j['eliminado']) || ($j['alianca']??null)===$grupo || !empty($j['status']['lider'])) return false;
        // Pode ser indicado mais tarde; atualizamos elegibilidade no voto.
        return true;
    }
    return false;
}
function indiceJogadorPorNomeExiste3(array $jogadores,string $n): bool {return indiceJogadorPorNome($jogadores,$n)!==null;}
function a3ProporReuniao(array &$jogadores,string $meuNome,array $post): string {
    $grupo=(string)(obterAliancaJogador($jogadores,$meuNome)??'');
    if($grupo==='' || count(membrosDaAlianca($jogadores,$grupo))<2)return 'Você precisa de uma aliança com pelo menos dois integrantes.';
    if(($_SESSION['fase_semana']??'')!=='interacoes_1'&&($_SESSION['fase_semana']??'')!=='interacoes_2'&&($_SESSION['fase_semana']??'')!=='interacoes_3')return 'A reunião só pode acontecer durante as Interações.';
    if(($_SESSION['acoes_restantes']??0)<=0)return 'Você não tem mais interações disponíveis.';
    if(($_SESSION['a3_reuniao_realizada'][$grupo]??0)===a3Rodada())return 'Sua aliança já fez uma reunião estratégica nesta rodada.';
    $decisao=(string)($post['a3_decisao']??'');
    $alvo=(string)($post['a3_alvo']??'');
    $membros=membrosDaAlianca($jogadores,$grupo);
    if(!in_array($decisao,['articular','mediar','livres','afastar'],true))return 'Selecione uma decisão válida.';
    if($decisao==='articular'&&!a3AlvoValido($jogadores,$grupo,$alvo))return 'Selecione um alvo disponível e fora da aliança.';
    if($decisao==='afastar' && ($alvo===$meuNome || !in_array($alvo,$membros,true)))return 'Selecione um integrante da sua aliança.';
    $_SESSION['a3_reuniao_realizada'][$grupo]=a3Rodada();
    $_SESSION['acoes_restantes']=max(0,(int)$_SESSION['acoes_restantes']-1);
    $resultado=[];
    if($decisao==='articular') {
        foreach($membros as $m) {
            if($m===$meuNome)continue;
            $r=a3Relacao($jogadores,$m,$alvo);
            $chance=max(5,min(88,23+a3Lealdade($jogadores,$m)*.55+(float)($r['rivalidade']??0)*.3-(float)($r['confianca']??0)*.45));
            if(random_int(1,100)<=$chance) {
                $_SESSION['a3_planos'][$m]=['rodada'=>a3Rodada(),'alvo'=>$alvo,'grupo'=>$grupo,'forca'=>random_int(24,46)];
                $_SESSION['a3_ajustes'][$m]=max(-40,min(35,(int)($_SESSION['a3_ajustes'][$m]??0)+4));
                $resultado[]="$m concordou em considerar $alvo";
            } else {
                $resultado[]="$m não prometeu votar em $alvo";
            }
        }
        $texto='🎯 A aliança conversou sobre o nome de '.$alvo.'. '.implode('; ',$resultado).'. Os votos continuam individuais.';
    } elseif($decisao==='mediar') {
        foreach($membros as $m) {if($m===$meuNome) continue;$_SESSION['a3_ajustes'][$m]=max(-40,min(35,(int)($_SESSION['a3_ajustes'][$m]??0)+8));}
        unset($_SESSION['a3_tensoes'][$grupo]);
        $texto='🤝 Você ouviu as diferenças do grupo e tentou reconstruir a confiança. Nem todos esqueceram as divergências.';
    } elseif($decisao==='livres') {
        foreach($membros as $m)unset($_SESSION['a3_planos'][$m]);
        $texto='🧊 A aliança decidiu não cobrar votos iguais nesta rodada. Isso reduz a pressão, mas também enfraquece o plano coletivo.';
    } else {
        $outros=array_values(array_filter($membros,fn($m)=>!in_array($m,[$meuNome,$alvo],true)));
        $aprova=1;
        foreach($outros as $m) {
            $r=a3Relacao($jogadores,$m,$alvo);
            $chance=max(8,min(88,25+(float)($r['rivalidade']??0)*.68-(float)($r['amizade']??35)*.30));
            if(random_int(1,100)<=$chance)$aprova++;
        }
        if($aprova>=(int)floor(count($membros)/2)+1) {
            $evento=romperAlianca($jogadores,$alvo,'o grupo votou para afastá-lo');
            $texto='🚪 A maioria aceitou retirar '.$alvo.' da aliança. A decisão pode deixar ressentimentos.';
            $_SESSION['a3_ajustes'][$alvo]=max(-40,(int)($_SESSION['a3_ajustes'][$alvo]??0)-24);
        } else {
            $texto='⚠️ O grupo recusou expulsar '.$alvo.'. O pedido provocou desconforto entre os participantes.';
            foreach($membros as $m)if($m!==$meuNome)$_SESSION['a3_ajustes'][$m]=max(-40,(int)($_SESSION['a3_ajustes'][$m]??0)-5);
        }
    }
    $_SESSION['a3_historico'][]=['rodada'=>a3Rodada(),'alianca'=>$grupo,'tipo'=>$decisao,'resultado'=>$texto];
    $_SESSION['a3_historico']=array_slice($_SESSION['a3_historico'],-30);
    $_SESSION['evento_extra'][]='🤫 '.$meuNome.' reuniu integrantes da aliança em uma conversa reservada.';
    return $texto;
}
function a3BonusVoto(array $jogadores,string $npc,string $alvo,string $contexto): float {
    if(!in_array($contexto,['voto','indicacao_lider'],true))return 0;
    $bonus=0;
    $grupo=obterAliancaJogador($jogadores,$npc);
    if($grupo && mesmaAliancaNomes($jogadores,$npc,$alvo))$bonus-=min(68,12+a3Lealdade($jogadores,$npc)*.7);
    $p=$_SESSION['a3_planos'][$npc]??[];
    if($grupo && is_array($p)&&($p['grupo']??'')===$grupo&&($p['rodada']??0)===a3Rodada() && ($p['alvo']??'')===$alvo && a3AlvoValido($jogadores,$grupo,$alvo)) {
        $bonus+=min(42,(int)($p['forca']??25))*a3Lealdade($jogadores,$npc)/100;
    }
    return $bonus;
}
function a3RegistrarVotosPrivados(array $jogadores,array $detalhes): void {
    $r=a3Rodada(); if(($_SESSION['a3_votos_processados']??0)===$r)return;
    $_SESSION['a3_votos_processados']=$r;
    foreach($detalhes as $d) {
        $n=(string)($d['votante']??'');$voto=(string)($d['voto']??'');
        $p=$_SESSION['a3_planos'][$n]??[];
        if(is_array($p) && ($p['rodada']??0)===$r) {
            $cumpriu=$voto===(string)($p['alvo']??'');
            $_SESSION['a3_historico_promessas'][]=['rodada'=>$r,'npc'=>$n,'cumpriu'=>$cumpriu,'privado'=>true];
            $_SESSION['a3_ajustes'][$n]=max(-40,min(35,(int)($_SESSION['a3_ajustes'][$n]??0)+($cumpriu?3:-8)));
        }
    }
    $_SESSION['a3_historico_promessas']=array_slice($_SESSION['a3_historico_promessas']??[],-40);
}
