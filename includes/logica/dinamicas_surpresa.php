<?php
/** Reviravoltas V1: estado inteiramente serializável na sessão/save. */
function dsAtual(): array {
    $d = $_SESSION['ds3'] ?? [];
    return is_array($d) && (int)($d['rodada'] ?? -1)===(int)($_SESSION['rodada'] ?? 1) ? $d : [];
}
function dsTipo(string $tipo): bool { return (dsAtual()['tipo'] ?? '') === $tipo; }
function dsPreparar(array $jogadores): void {
    $rodada = (int)($_SESSION['rodada'] ?? 1);
    if ($rodada < 2 || ($_SESSION['fase_semana'] ?? '') !== 'interacoes_1') return;
    if ((int)($_SESSION['ds3_ultimo_sorteio'] ?? 0) === $rodada) return;
    $_SESSION['ds3_ultimo_sorteio']=$rodada;
    if (count($jogadores)<7 || $rodada - (int)($_SESSION['ds3_ultima_ocorrencia']??-10)<2 || random_int(1,100)>42) {
        $_SESSION['ds3']=['rodada'=>$rodada,'tipo'=>'']; return;
    }
    $tipos=['votacao_aberta','imunidade_surpresa','voto_em_dobro','carta_misteriosa','contragolpe_especial'];
    if (count($jogadores)>=8) $tipos[]='paredao_especial';
    $tipo=$tipos[array_rand($tipos)];
    $_SESSION['ds3']=['rodada'=>$rodada,'tipo'=>$tipo,'imunidade_aplicada'=>false];
    $_SESSION['ds3_ultima_ocorrencia']=$rodada;
    $dados=dsDescricoes()[$tipo];
    $_SESSION['evento_extra'][]='🚨 PRODUÇÃO: '.$dados['titulo'].' — '.$dados['descricao'];
    foreach(dsReacoesCasa($jogadores,$tipo) as $reacao)$_SESSION['evento_extra'][]=$reacao;
    if(function_exists('registrarHistoricoTemporada')) registrarHistoricoTemporada('dinamica_surpresa',$dados['titulo'],$dados['descricao'],[],'🚨',null,'ds3|'.$rodada);
}

/** Reações públicas simples, sensíveis à personalidade; nunca usa segredos internos. */
function dsReacoesCasa(array $jogadores,string $tipo): array {
    $participantes=[];
    foreach($jogadores as $j)if(!empty($j['nome']) && empty($j['eliminado']) && !nomeIgual((string)$j['nome'],(string)($_SESSION['meu_nome']??'')))$participantes[]=$j;
    if(!$participantes)return [];
    shuffle($participantes);
    $falas=[];
    foreach(array_slice($participantes,0,min(2,count($participantes))) as $j) {
        $nome=(string)$j['nome'];$perfil=(string)($j['personalidade']??'Neutro');
        $tema=match($tipo){
            'votacao_aberta'=>'agora vai ter que falar o voto na frente de todo mundo',
            'imunidade_surpresa'=>'uma imunidade a mais pode bagunçar todos os planos',
            'paredao_especial'=>'quatro pessoas no Paredão mudam a conta de todo mundo',
            'voto_em_dobro'=>'cada voto vai ter um peso enorme nesta semana',
            'carta_misteriosa'=>'essa carta pode mudar o rumo da votação',
            'contragolpe_especial'=>'quem for indicado ainda vai poder puxar outra pessoa',
            default=>'a produção mudou o jogo'
        };
        if(in_array($perfil,['Estrategista','Manipulador','Falso','Líder Nato'],true))$fala="Vou ter que refazer meus cálculos. $tema.";
        elseif(in_array($perfil,['Barraqueiro','Explosivo','Impulsivo'],true))$fala="Quero ver o povo segurar o que fala agora, porque $tema!";
        elseif(in_array($perfil,['Emocional','Sensível','Tímido'],true))$fala="Eu tô nervoso com isso. $tema.";
        else $fala="Caramba, não esperava por essa. $tema.";
        $falas[]="💬 $nome reagiu ao comunicado: “$fala”";
    }
    return $falas;
}

function dsDescricoes(): array {
    return [
        'votacao_aberta'=>['titulo'=>'🗳️ Votação Aberta','descricao'=>'Nesta semana, os votos serão anunciados à casa depois da votação. Promessas poderão ser confrontadas publicamente.'],
        'imunidade_surpresa'=>['titulo'=>'🛡️ Imunidade Surpresa','descricao'=>'Ao final da Festa, a produção concederá uma imunidade extra a um participante elegível.'],
        'paredao_especial'=>['titulo'=>'💥 Paredão Especial','descricao'=>'Se houver candidatos suficientes, a formação terá quatro emparedados, respeitando as imunidades e o Bate-Volta.'],
        'voto_em_dobro'=>['titulo'=>'⚡ Votação Turbinada','descricao'=>'Cada voto válido da casa terá peso dois nesta semana. Poderes de voto duplo não acumulam peso adicional.'],
        'carta_misteriosa'=>['titulo'=>'✉️ Carta Misteriosa','descricao'=>'Ao final da Festa, um participante elegível receberá da produção um voto de peso dois para a votação da casa.'],
        'contragolpe_especial'=>['titulo'=>'🔥 Contragolpe Especial','descricao'=>'O indicado pelo Líder poderá puxar uma pessoa elegível ao Paredão, após a formação da votação da casa.'],
    ];
}
function dsAplicarImunidade(array &$jogadores): void {
    if (!dsTipo('imunidade_surpresa') || !empty($_SESSION['ds3']['imunidade_aplicada'])) return;
    $_SESSION['ds3']['imunidade_aplicada']=true; // idempotência até em reload
    $candidatos=[];
    foreach ($jogadores as $j) {
        $n=(string)($j['nome']??'');
        if($n!=='' && empty($j['eliminado']) && empty($j['status']['lider']) && !estaImune($jogadores,$n)) $candidatos[]=$n;
    }
    if (!$candidatos) return;
    $n=$candidatos[array_rand($candidatos)];
    $_SESSION['ds3_imune']=['rodada'=>(int)($_SESSION['rodada']??1),'nome'=>$n];
    $_SESSION['evento_extra'][]="🛡️ A produção concedeu uma imunidade surpresa a $n. $n não poderá ser indicado ao Paredão nesta rodada.";
    if (function_exists('registrarHistoricoTemporada')) registrarHistoricoTemporada('imunidade','Imunidade surpresa',"$n recebeu uma imunidade da produção.",[$n],'🛡️',null,'ds3_imune|'.($_SESSION['rodada']??1));
}
function dsImune(string $nome): bool {
    $v=$_SESSION['ds3_imune']??[];
    return is_array($v) && (int)($v['rodada']??0)===(int)($_SESSION['rodada']??1)
        && $nome!=='' && function_exists('nomeIgual') && nomeIgual($nome,(string)($v['nome']??''));
}
function dsAplicarCarta(array $jogadores): void {
    if (!dsTipo('carta_misteriosa') || !empty($_SESSION['ds3']['carta_aplicada'])) return;
    $_SESSION['ds3']['carta_aplicada']=true;
    $candidatos=[];
    foreach($jogadores as $j){
        $n=(string)($j['nome']??'');
        if($n!=='' && empty($j['eliminado']) && empty($j['status']['lider']))$candidatos[]=$n;
    }
    if(!$candidatos)return;
    $n=$candidatos[array_rand($candidatos)];
    $_SESSION['ds3_carta']=['rodada'=>(int)($_SESSION['rodada']??1),'nome'=>$n];
    $_SESSION['evento_extra'][]="✉️ $n abriu a Carta Misteriosa: o voto de $n terá peso dois na votação da casa.";
}
function dsPesoVoto(int $original, string $votante=''): int {
    if(dsTipo('voto_em_dobro')) return max(2,$original);
    $c=$_SESSION['ds3_carta']??[];
    if(dsTipo('carta_misteriosa') && is_array($c) && (int)($c['rodada']??0)===(int)($_SESSION['rodada']??1)
        && $votante!=='' && function_exists('nomeIgual') && nomeIgual($votante,(string)($c['nome']??'')))return max(2,$original);
    return $original;
}
function dsRegistrarVotosPublicos(array &$jogadores, array $detalhes): void {
    if (!dsTipo('votacao_aberta') || !empty($_SESSION['ds3_votos_revelados_rodada']) && $_SESSION['ds3_votos_revelados_rodada']==($_SESSION['rodada']??1)) return;
    $_SESSION['ds3_votos_revelados_rodada']=(int)($_SESSION['rodada']??1);
    $falas=[];
    foreach($detalhes as $d) {
        $v=(string)($d['votante']??'');$alvo=(string)($d['voto']??'');
        if ($v===''||$alvo==='') continue;
        $falas[]="$v votou em $alvo";
        if(function_exists('aplicarConsequenciaSocial')) aplicarConsequenciaSocial($jogadores,$v,$alvo,'voto_revelado','ds3_aberto|'.($_SESSION['rodada']??1).'|'.$v.'|'.$alvo,"$v teve seu voto declarado diante da casa em $alvo.");
    }
    if ($falas) {
        $_SESSION['evento_extra'][]='🗳️ VOTAÇÃO ABERTA — '.implode('; ',$falas).'.';
        $_SESSION['ds3_votos_publicos']=['rodada'=>(int)($_SESSION['rodada']??1),'votos'=>$falas];
    }
}

/** Contragolpe extra, sem conflitar com as etapas de Big Fone/Curinga. */
function dsCandidatosContragolpe(array $jogadores): array {
    $paredao=$_SESSION['paredao']??[];$lider=(string)($_SESSION['lider']??'');
    $c=[];
    foreach($jogadores as $j){$n=(string)($j['nome']??'');
        if($n===''||!empty($j['eliminado'])||nomeIgual($n,$lider)||in_array($n,$paredao,true)||estaImune($jogadores,$n))continue;
        $c[]=$n;
    } return $c;
}
function dsVerificarContragolpe(array &$jogadores): bool {
    if(!dsTipo('contragolpe_especial')||($_SESSION['ds3_contra_resolvido']??0)===(int)($_SESSION['rodada']??1))return false;
    $origem=(string)($_SESSION['indicacao_lider']??'');
    if($origem===''||!in_array($origem,$_SESSION['paredao']??[],true))return false;
    $candidatos=dsCandidatosContragolpe($jogadores);
    if(!$candidatos){$_SESSION['ds3_contra_resolvido']=(int)($_SESSION['rodada']??1);return false;}
    if(nomeIgual($origem,(string)($_SESSION['meu_nome']??''))) {
        $_SESSION['fase_semana']='ds3_contragolpe';
        return true;
    }
    $bloqueados=array_values(array_diff(array_map(fn($j)=>$j['nome']??'',$jogadores),$candidatos));
    $alvo=function_exists('escolherAlvoInteligente') ? escolherAlvoInteligente($jogadores,$origem,$bloqueados,'voto') : null;
    if(!$alvo||!in_array($alvo,$candidatos,true))$alvo=$candidatos[array_rand($candidatos)];
    dsAplicarContragolpe($jogadores,$origem,$alvo);return false;
}
function dsAplicarContragolpe(array &$jogadores,string $origem,string $alvo): bool {
    if(($_SESSION['ds3_contra_resolvido']??0)===(int)($_SESSION['rodada']??1)||!dsTipo('contragolpe_especial')||!in_array($alvo,dsCandidatosContragolpe($jogadores),true))return false;
    $_SESSION['ds3_contra_resolvido']=(int)($_SESSION['rodada']??1);
    $paredao=$_SESSION['paredao']??[];
    if(function_exists('adicionarAoParedaoSeValido'))adicionarAoParedaoSeValido($paredao,$jogadores,$alvo,'Contragolpe Especial');
    if(!in_array($alvo,$paredao,true))return false;
    $_SESSION['paredao']=array_values(array_unique($paredao));
    $_SESSION['evento_extra'][]="🔥 Contragolpe Especial: $origem puxou $alvo para o Paredão.";
    if(function_exists('registrarHistoricoTemporada')) registrarHistoricoTemporada('contragolpe_especial','Contragolpe Especial',"$origem puxou $alvo para o Paredão.",[$origem,$alvo],'🔥',null,'ds3_contra|'.($_SESSION['rodada']??1));
    if(function_exists('aplicarConsequenciaSocial'))aplicarConsequenciaSocial($jogadores,$origem,$alvo,'indicacao_paredao','ds3_contra|'.($_SESSION['rodada']??1).'|'.$origem.'|'.$alvo,"$origem puxou $alvo para o Paredão no Contragolpe Especial.");
    return true;
}
