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


/** Registro público único, usado por Ao Vivo, painel e Histórico. Não publica votos secretos. */
function dsRegistrarEfeito(string $chave, string $titulo, string $mensagem, array $participantes=[]): bool {
    $rodada=(int)($_SESSION['rodada']??1);
    if (!isset($_SESSION['ds3']) || !is_array($_SESSION['ds3']) || (int)($_SESSION['ds3']['rodada']??0)!==$rodada) return false;
    if (!isset($_SESSION['ds3']['efeitos']) || !is_array($_SESSION['ds3']['efeitos'])) $_SESSION['ds3']['efeitos']=[];
    if (isset($_SESSION['ds3']['efeitos'][$chave])) return false;
    $_SESSION['ds3']['efeitos'][$chave]=['titulo'=>$titulo,'mensagem'=>$mensagem,'rodada'=>$rodada];
    if (!isset($_SESSION['evento_extra']) || !is_array($_SESSION['evento_extra'])) $_SESSION['evento_extra']=[];
    $_SESSION['evento_extra'][]=$mensagem;
    if (function_exists('registrarHistoricoTemporada')) {
        registrarHistoricoTemporada('dinamica_efeito',$titulo,$mensagem,$participantes,'🎲',$rodada,'ds3_efeito|'.$chave.'|'.$rodada);
    }
    return true;
}

/** Progresso da dinâmica mostrado sem informações privadas dos NPCs. */
function dsResumoPublico(): string {
    $d=dsAtual(); $tipo=(string)($d['tipo']??''); $rodada=(int)($_SESSION['rodada']??1);
    $efeitos=(array)($d['efeitos']??[]);
    if($tipo==='imunidade_surpresa') {
        $im=$_SESSION['ds3_imune']??[];
        return (int)($im['rodada']??0)===$rodada && !empty($im['nome'])
            ? '✅ Efeito realizado: '.(string)$im['nome'].' recebeu imunidade extra e não pode ser indicado nesta semana.'
            : '⏳ Pendente: a proteção será concedida ao terminar a Festa.';
    }
    if($tipo==='carta_misteriosa') {
        $c=$_SESSION['ds3_carta']??[];
        return (int)($c['rodada']??0)===$rodada && !empty($c['nome'])
            ? '✅ Carta entregue a '.(string)$c['nome'].'. O voto terá peso dois quando for computado.'
            : '⏳ Pendente: a carta será entregue depois da Festa.';
    }
    if($tipo==='votacao_aberta') return !empty($_SESSION['ds3_votos_publicos']) && (int)($_SESSION['ds3_votos_publicos']['rodada']??0)===$rodada
        ? '✅ Votos da casa declarados: confira a tabela no Ao Vivo.'
        : '⏳ Pendente: os votos serão públicos após a votação, inclusive se houver desempate.';
    if($tipo==='voto_em_dobro') return isset($efeitos['apuracao'])
        ? '✅ Votos da casa contabilizados com peso dois. Consulte a apuração no Ao Vivo.'
        : '⏳ Pendente: o peso dobrado será aplicado na votação da casa.';
    if($tipo==='paredao_especial') return isset($efeitos['paredao'])
        ? '✅ Formação especial realizada. Consulte os nomes do Paredão no Ao Vivo.'
        : '⏳ Pendente: formação especial prevista para até quatro participantes elegíveis.';
    if($tipo==='contragolpe_especial') return isset($efeitos['contragolpe'])
        ? '✅ Contragolpe aplicado: confira quem foi puxado no Ao Vivo.'
        : '⏳ Pendente: o indicado do Líder poderá puxar alguém após a formação.';
    return '';
}

/** Publicar efeitos da apuração sem revelar votos individuais em semanas sigilosas. */
function dsRegistrarApuracao(array $detalhes, array $votos): void {
    $tipo=(string)(dsAtual()['tipo']??'');
    if(!in_array($tipo,['voto_em_dobro','carta_misteriosa'],true)) return;
    $totalVotos=count($detalhes);
    $totalPeso=array_sum(array_map(fn($d)=>(int)($d['peso']??0),$detalhes));
    if($tipo==='voto_em_dobro') {
        dsRegistrarEfeito('apuracao','Votação Turbinada',"⚡ VOTAÇÃO TURBINADA — $totalVotos voto(s) válido(s) da casa somaram $totalPeso voto(s) ponderado(s). O peso dois foi usado na apuração do Paredão.");
    } else {
        $c=$_SESSION['ds3_carta']??[]; $nome=(string)($c['nome']??'');
        if($nome==='') return;
        $pesoCarta=null;
        foreach($detalhes as $d) if(function_exists('nomeIgual') && nomeIgual($nome,(string)($d['votante']??''))) $pesoCarta=(int)($d['peso']??1);
        $mensagem=$pesoCarta!==null ? "✉️ CARTA MISTERIOSA — O voto de $nome contou com peso $pesoCarta na apuração. Resultado: $totalPeso voto(s) ponderado(s), de $totalVotos voto(s) válido(s)." : "✉️ CARTA MISTERIOSA — $nome recebeu a carta, mas não teve voto válido computado nessa formação.";
        dsRegistrarEfeito('apuracao','Carta Misteriosa: apuração',$mensagem,[$nome]);
    }
}

/** Registrar o paredão efetivamente formado (antes de eventual Bate-Volta). */
function dsRegistrarParedao(array $paredao): void {
    if(!dsTipo('paredao_especial')) return;
    $nomes=array_values(array_filter(array_map('strval',$paredao)));
    $n=count($nomes);
    dsRegistrarEfeito('paredao','Paredão Especial',"💥 PAREDÃO ESPECIAL — A formação colocou $n participante(s) na berlinda: ".implode(' × ',$nomes).'. A Prova Bate-Volta poderá alterar a composição.', $nomes);
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
        $falas[]="💬 $nome comentou a novidade: $fala";
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
    if (!dsTipo('imunidade_surpresa')) return;
    // Permite consertar saves antigos em que o flag foi salvo sem o beneficiário.
    if (!empty($_SESSION['ds3']['imunidade_aplicada'])
        && !empty($_SESSION['ds3_imune']['nome'])
        && (int)($_SESSION['ds3_imune']['rodada']??0)===(int)($_SESSION['rodada']??1)) return;
    $candidatos=[];
    foreach ($jogadores as $j) {
        $n=(string)($j['nome']??'');
        if($n!=='' && empty($j['eliminado']) && empty($j['status']['lider']) && !estaImune($jogadores,$n)) $candidatos[]=$n;
    }
    if (!$candidatos) {
        $_SESSION['ds3']['imunidade_aplicada']=true;
        $_SESSION['evento_extra'][]='🛡️ Imunidade Surpresa: não havia participantes elegíveis para receber o benefício.';
        return;
    }
    $n=$candidatos[array_rand($candidatos)];
    $_SESSION['ds3']['imunidade_aplicada']=true;
    $_SESSION['ds3_imune']=['rodada'=>(int)($_SESSION['rodada']??1),'nome'=>$n];
    // Atualizar também o status exibido no painel de participantes.
    foreach($jogadores as &$participante) {
        if(function_exists('nomeIgual') && nomeIgual((string)($participante['nome']??''),$n)) {
            if(!isset($participante['status']) || !is_array($participante['status'])) $participante['status']=[];
            $participante['status']['imune']=true;
        }
    }
    unset($participante);
    dsRegistrarEfeito('imunidade','Imunidade Surpresa',"🛡️ IMUNIDADE SURPRESA — A produção concedeu imunidade a $n. $n não pode ser indicado ao Paredão desta semana.",[$n]);
}
function dsImune(string $nome): bool {
    $v=$_SESSION['ds3_imune']??[];
    return is_array($v) && (int)($v['rodada']??0)===(int)($_SESSION['rodada']??1)
        && $nome!=='' && function_exists('nomeIgual') && nomeIgual($nome,(string)($v['nome']??''));
}
function dsAplicarCarta(array $jogadores): void {
    if (!dsTipo('carta_misteriosa') || !empty($_SESSION['ds3']['carta_aplicada'])) return;
    $candidatos=[];
    foreach($jogadores as $j){
        $n=(string)($j['nome']??'');
        if($n!=='' && empty($j['eliminado']) && empty($j['status']['lider']))$candidatos[]=$n;
    }
    if(!$candidatos){$_SESSION['ds3']['carta_aplicada']=true;return;}
    $n=$candidatos[array_rand($candidatos)];
    $_SESSION['ds3']['carta_aplicada']=true;
    $_SESSION['ds3_carta']=['rodada'=>(int)($_SESSION['rodada']??1),'nome'=>$n];
    dsRegistrarEfeito('carta','Carta Misteriosa',"✉️ CARTA MISTERIOSA — $n recebeu da produção um voto de peso dois para a votação desta semana.",[$n]);
}
/** Chamada por TODOS os caminhos que concluem a Festa. Efeitos idempotentes. */
function dsConcluirFesta(array &$jogadores): void {
    dsAplicarImunidade($jogadores);
    dsAplicarCarta($jogadores);
    $_SESSION['jogadores']=$jogadores;
}

/** Recuperação de saves desta mesma semana com benefício já recebido, sem sortear de novo. */
function dsSincronizarBeneficios(): void {
    $d=dsAtual();$rodada=(int)($_SESSION['rodada']??1);
    if(($d['tipo']??'')==='imunidade_surpresa') {
        $im=$_SESSION['ds3_imune']??[];
        if((int)($im['rodada']??0)===$rodada && !empty($im['nome'])) {
            $nome=(string)$im['nome'];
            dsRegistrarEfeito('imunidade','Imunidade Surpresa',"🛡️ IMUNIDADE SURPRESA — A produção concedeu imunidade a $nome. $nome não pode ser indicado ao Paredão desta semana.",[$nome]);
        }
    }
    if(($d['tipo']??'')==='carta_misteriosa') {
        $c=$_SESSION['ds3_carta']??[];
        if((int)($c['rodada']??0)===$rodada && !empty($c['nome'])) {
            $nome=(string)$c['nome'];
            dsRegistrarEfeito('carta','Carta Misteriosa',"✉️ CARTA MISTERIOSA — $nome recebeu da produção um voto de peso dois para a votação desta semana.",[$nome]);
        }
    }
}

function dsPesoVoto(int $original, string $votante=''): int {
    if(dsTipo('voto_em_dobro')) return max(2,$original);
    $c=$_SESSION['ds3_carta']??[];
    if(dsTipo('carta_misteriosa') && is_array($c) && (int)($c['rodada']??0)===(int)($_SESSION['rodada']??1)
        && $votante!=='' && function_exists('nomeIgual') && nomeIgual($votante,(string)($c['nome']??'')))return max(2,$original);
    return $original;
}
function dsRegistrarVotosPublicos(array &$jogadores, array $detalhes): void {
    if (!dsTipo('votacao_aberta')) return;
    $rodada=(int)($_SESSION['rodada']??1);
    if((int)($_SESSION['ds3_votos_revelados_rodada']??0)===$rodada) return;
    $linhas=[];$falas=[];
    foreach($detalhes as $d) {
        $v=(string)($d['votante']??'');$alvo=(string)($d['voto']??'');
        if ($v===''||$alvo==='') continue;
        $peso=max(1,(int)($d['peso']??1));
        $linhas[]=['votante'=>$v,'voto'=>$alvo,'peso'=>$peso];
        $falas[]="$v votou em $alvo".($peso>1 ? " (peso $peso)" : '');
    }
    $_SESSION['ds3_votos_revelados_rodada']=$rodada;
    $_SESSION['ds3_votos_publicos']=['rodada'=>$rodada,'votos'=>$falas,'detalhes'=>$linhas];
    if(!$linhas) return;
    // O registro fica antes da etapa de desempate. Assim o Líder já vê a votação aberta.
    dsRegistrarEfeito('votacao_aberta','Votação Aberta',
        '🗳️ VOTAÇÃO ABERTA — Todos os votos válidos foram declarados à casa. Veja a tabela completa de quem votou em quem no Ao Vivo.',
        array_values(array_unique(array_merge(array_column($linhas,'votante'),array_column($linhas,'voto')))));
    if(function_exists('registrarHistoricoTemporada')) {
        registrarHistoricoTemporada('votacao_aberta','Votos públicos da casa',
            'Votação Aberta — '.implode('; ',$falas).'.',
            array_values(array_unique(array_merge(array_column($linhas,'votante'),array_column($linhas,'voto')))),
            '🗳️',$rodada,'ds3_votos_detalhados|'.$rodada, ['votos'=>$linhas]);
    }
    foreach($linhas as $d) {
        if(function_exists('aplicarConsequenciaSocial')) aplicarConsequenciaSocial($jogadores,$d['votante'],$d['voto'],'voto_revelado',
            'ds3_aberto|'.$rodada.'|'.$d['votante'].'|'.$d['voto'],
            $d['votante'].' declarou seu voto em '.$d['voto'].' diante da casa.');
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
    $paredao=$_SESSION['paredao']??[];
    if(function_exists('adicionarAoParedaoSeValido'))adicionarAoParedaoSeValido($paredao,$jogadores,$alvo,'Contragolpe Especial');
    if(!in_array($alvo,$paredao,true))return false;
    $_SESSION['ds3_contra_resolvido']=(int)($_SESSION['rodada']??1);
    $_SESSION['paredao']=array_values(array_unique($paredao));
    dsRegistrarEfeito('contragolpe','Contragolpe Especial',"🔥 CONTRAGOLPE ESPECIAL — $origem puxou $alvo para o Paredão.",[$origem,$alvo]);
    if(function_exists('aplicarConsequenciaSocial'))aplicarConsequenciaSocial($jogadores,$origem,$alvo,'indicacao_paredao','ds3_contra|'.($_SESSION['rodada']??1).'|'.$origem.'|'.$alvo,"$origem puxou $alvo para o Paredão no Contragolpe Especial.");
    return true;
}
