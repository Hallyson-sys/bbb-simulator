<?php
/**
 * BBB Simulator - Modo DEV 3.0
 * Diagnósticos de leitura e cenários isolados. NÃO devem modificar uma partida real.
 */

declare(strict_types=1);

function dev3Item(string $codigo, string $status, string $titulo, string $detalhe): array {
    return compact('codigo', 'status', 'titulo', 'detalhe');
}

function dev3Nomes(array $jogadores): array {
    $nomes = [];
    foreach ($jogadores as $j) {
        if (!is_array($j) || !empty($j['eliminado'])) continue;
        $n = trim((string)($j['nome'] ?? ''));
        if ($n !== '') $nomes[] = $n;
    }
    return $nomes;
}

/** Inspeção do save ativo, sem criar ou corrigir estados automaticamente. */
function dev3InspecionarSave(array $s): array {
    $out = [];
    $add = static function(string $codigo, bool $ok, string $titulo, string $detalhe, string $nivel='falha') use (&$out): void {
        $out[] = dev3Item($codigo, $ok ? 'ok' : $nivel, $titulo, $detalhe);
    };
    $jogadores = $s['jogadores'] ?? null;
    $add('SAVE_01', is_array($jogadores) && count($jogadores) > 0, 'Elenco no save', 'O save precisa conter uma lista de participantes.');
    if (!is_array($jogadores) || !$jogadores) return $out;
    $nomes = dev3Nomes($jogadores);
    $todos = array_values(array_filter(array_map(static fn($j)=>is_array($j)?trim((string)($j['nome']??'')):'', $jogadores)));
    $lower = array_map(static fn($n)=>function_exists('mb_strtolower')?mb_strtolower($n,'UTF-8'):strtolower($n),$todos);
    $add('SAVE_02', count($todos) === count($jogadores), 'Nomes preenchidos', 'Todos os participantes precisam ter nomes.');
    $add('SAVE_03', count($lower) === count(array_unique($lower)), 'Sem nomes duplicados', 'Nomes duplicados podem confundir a IA e as votações.');
    $meu = trim((string)($s['meu_nome']??''));
    $existe = in_array($meu, $todos, true);
    $add('SAVE_04', $meu !== '' && ($existe || !empty($s['modo_espectador'])), 'Identidade do jogador', 'O protagonista deve existir na casa ou estar no modo espectador.');
    $semana = (int)($s['rodada'] ?? 0);
    $add('SAVE_05', $semana >= 1, 'Semana válida', 'Semana atual: '.$semana.'.');
    $fase = (string)($s['fase_semana']??'');
    $fases = function_exists('fasesDisponiveisModoTeste') ? fasesDisponiveisModoTeste() : [];
    $extras = ['top4_prova','finalistas','final','bate_papo_eliminado','jogador_eliminado','fim','resultado','quarto_secreto','bate_volta','paredao_falso','casa_vidro','bigfone_imunidade_extra','ds3_contragolpe','vip_xepa_revelar'];
    $add('SAVE_06', $fase !== '' && (isset($fases[$fase]) || in_array($fase,$extras,true)), 'Fase reconhecida', 'Fase ativa: '.($fase ?: '(vazia)').'.', 'aviso');

    $p = $s['paredao'] ?? [];
    $validP = is_array($p) && count($p) === count(array_unique($p)) && count(array_diff($p,$todos)) === 0;
    $add('PARED_01', $validP, 'Paredão sem nomes inválidos', 'Não deve conter duplicados, eliminados ou nomes fora do elenco.');
    $formado = !empty($s['paredao_formado']);
    $add('PARED_02', !$formado || (is_array($p) && count($p)>=2), 'Paredão formado consistente', 'Se marcado como formado, deve ter pelo menos dois participantes.', 'aviso');
    $imunes = [];
    foreach ($jogadores as $j) if (is_array($j) && !empty($j['status']['imune'])) $imunes[]=(string)($j['nome']??'');
    $add('PARED_03', !$formado || !array_intersect((array)$p,$imunes), 'Imunes fora do Paredão', 'Participantes com status de imunidade não devem ficar na berlinda.');
    $lider = (string)($s['lider']??'');
    $add('PARED_04', $lider === '' || in_array($lider,$nomes,true), 'Líder pertence ao elenco ativo', 'Líder atual: '.($lider ?: 'não definido').'.', 'aviso');

    $dupla = (array)($s['lideres_dupla'] ?? []);
    $top4Vencedor=(string)($s['top4_vencedor']??'');
    $add('TOP4_01', empty($s['top4_concluido']) || ($top4Vencedor!=='' && in_array($top4Vencedor,$nomes,true)), 'Vencedor da última resistência válido', 'A prova do Top 4 concluída precisa ter vencedor ainda na disputa.');
    $add('DUPLA_01', !$dupla || (count(array_unique($dupla))===count($dupla) && !array_diff($dupla,$nomes)), 'Dupla de liderança válida', 'Os dois Líderes precisam estar ativos e não podem se repetir.');
    $add('DUPLA_02', !$dupla || $lider==='' || in_array($lider,$dupla,true), 'Líder titular integra a dupla', 'O Líder titular deve ser um dos vencedores.', 'aviso');
    $restantes = count($nomes);
    $falsoAtivo = !empty($s['paredao_falso_ativo']) && empty($s['paredao_falso_retorno_realizado']);
    if ($falsoAtivo && ($s['falso_eliminado']??'') !== '' && !in_array((string)$s['falso_eliminado'],$nomes,true)) $restantes++;
    $add('RETA_01', $restantes > 8 || !in_array($fase,['anjo','monstro','imunizacao_anjo','discordia'],true), 'Top 8 sem Anjo ou Discórdia', 'O save tem '.$restantes.' concorrentes; fase '.$fase.'.', 'aviso');
    $ds = $s['ds3']??[];
    $dsAtivo = is_array($ds) && !empty($ds['tipo']) && (int)($ds['rodada']??0)===$semana;
    $add('RETA_02', $restantes > 5 || !$dsAtivo, 'Top 5 sem dinâmica nova ativa', 'A reta final não deve manter dinâmicas especiais abertas.', 'aviso');
    $add('DS_01', !$dsAtivo || (int)($ds['rodada']??0)===$semana, 'Dinâmica da semana correta', 'A regra especial só é válida na semana em que foi sorteada.');
    $im = $s['ds3_imune']??[];
    $imAtiva = is_array($im) && (int)($im['rodada']??0)===$semana && !empty($im['nome']);
    $add('DS_02', !$imAtiva || (in_array((string)$im['nome'],$nomes,true) && in_array((string)$im['nome'],$imunes,true)), 'Imunidade Surpresa aplicada', 'O beneficiado da produção deve estar ativo e marcado como imune.');
    $carta = $s['ds3_carta']??[];
    $cartaAtiva = is_array($carta) && (int)($carta['rodada']??0)===$semana && !empty($carta['nome']);
    $add('DS_03', !$cartaAtiva || in_array((string)$carta['nome'],$nomes,true), 'Carta Misteriosa entregue a participante ativo', 'A carta não deve pertencer a alguém fora da casa.');
    $grupos = [];
    foreach ($jogadores as $j) if (is_array($j) && empty($j['eliminado']) && !empty($j['alianca'])) $grupos[(string)$j['alianca']][]=(string)($j['nome']??'');
    $excedentes = array_keys(array_filter($grupos,static fn($m)=>count($m)>5));
    $add('ALI_01', !$excedentes, 'Limite de integrantes da aliança', $excedentes?'Acima do limite: '.implode(', ',$excedentes):'Todas as alianças respeitam o limite de cinco integrantes.');
    $pendente = $s['a3_convite_pendente']??null;
    $add('ALI_02', !is_array($pendente)||empty($pendente['autor'])||in_array((string)$pendente['autor'],$nomes,true), 'Convite pendente tem autor ativo', 'Convites de eliminados precisam expirar.', 'aviso');
    $popOk=true;
    foreach ($jogadores as $j) if (is_array($j) && isset($j['popularidade'])) {
        $v=$j['popularidade']; if (!is_numeric($v) || $v < 0 || $v > 100) $popOk=false;
    }
    $add('SAVE_07', $popOk, 'Popularidade dentro dos limites internos', 'Verifica apenas se os valores estão entre 0 e 100; não os revela.');
    // Objetivos são dados privados do save; aqui só verificamos integridade.
    $metas = $s['on1_objetivos'] ?? [];
    $validos = ['proteger_aliado','superar_rival','reconciliar','construir_alianca',
        'fortalecer_alianca','vencer_prova','evitar_paredao','chegar_final'];
    $corretas = is_array($metas);
    if ($corretas) foreach ($metas as $npc => $meta) {
        if (!in_array((string)$npc, $nomes, true) || !is_array($meta) ||
            !in_array((string)($meta['tipo'] ?? ''), $validos, true) ||
            (!empty($meta['alvo']) && !in_array((string)$meta['alvo'], $nomes, true))) {
            $corretas = false; break;
        }
    }
    $add('OBJ_01', $corretas, 'Objetivos NPC sem alvos inválidos',
        'Os objetivos ativos precisam pertencer a participantes e alvos ainda na disputa.', 'aviso');
    // Interações 4.0: não deixa diálogo de outra semana travar o save.
    $i4P = $s['i4_pendente'] ?? null;
    $i4Valido = !is_array($i4P) || (
        (int)($i4P['semana']??0)===$semana && (string)($i4P['fase']??'')===$fase
        && in_array((string)($i4P['alvo']??''),$nomes,true)
        && is_string($i4P['token']??null) && strlen((string)$i4P['token'])>=16
    );
    $add('I4_01',$i4Valido,'Diálogo em andamento compatível com o save',
        'Uma conversa pendente precisa pertencer à mesma fase e semana, com participante ativo.','aviso');
    $i4Acordos = (array)($s['i4_acordos']??[]);
    $i4AcordosOk = true;
    foreach($i4Acordos as $nome=>$p) if(!is_array($p)||!isset($p['ate'],$p['semana']) || (int)$p['ate']<(int)$p['semana'] || (int)$p['ate']>(int)$p['semana']+1){$i4AcordosOk=false;break;}
    $add('I4_02',$i4AcordosOk,'Compromissos de Interações 4.0 limitados',
        'Promessas pessoais só podem durar até a semana seguinte.','aviso');
    return $out;
}

/** Troca temporária de sessão APENAS durante testes sintéticos; restauração garantida. */
function dev3Sandbox(array $fake, callable $teste): mixed {
    $original = $_SESSION;
    try {
        $_SESSION = $fake;
        return $teste();
    } finally {
        $_SESSION = $original;
    }
}

function dev3ElencoExemplo(int $n): array {
    $out=[];
    for($i=1;$i<=$n;$i++) $out[]=['nome'=>'NPC'.$i,'status'=>['imune'=>false,'lider'=>false], 'alianca'=>''];
    return $out;
}

/** Testes de regressão isolados, sem sobrescrever os dados da temporada. */
function dev3TestesSinteticos(): array {
    $out=[];
    $caso=static function(string $id,string $nome,callable $fn) use (&$out):void {
        try { $ok=(bool)$fn(); $out[]=dev3Item($id,$ok?'ok':'falha',$nome,$ok?'Cenário isolado passou.':'O resultado não correspondeu à regra esperada.'); }
        catch (Throwable $e) { $out[]=dev3Item($id,'falha',$nome,'Exceção '.get_class($e).': '.$e->getMessage()); }
    };
    $base=static fn(int $n)=>['jogadores'=>dev3ElencoExemplo($n),'meu_nome'=>'NPC1','rodada'=>4,'fase_semana'=>'interacoes_1','evento_extra'=>[]];
    $caso('AUTO_01','Top 9 ainda disputa Anjo',fn()=>dev3Sandbox($base(9),fn()=>!bbbRetaFinalSemAnjo($_SESSION['jogadores'])));
    $caso('AUTO_02','Top 8 termina Anjo e Discórdia',fn()=>dev3Sandbox($base(8),fn()=>bbbRetaFinalSemAnjo($_SESSION['jogadores']) && bbbRetaFinalSemDiscordia($_SESSION['jogadores'])));
    $caso('AUTO_03','VIP do Top 8 avança ao Big Fone',fn()=>dev3Sandbox($base(8),fn()=>bbbFaseAposVip($_SESSION['jogadores'])==='bigfone'));
    $caso('AUTO_04','Paredão Top 8 avança para Interações',fn()=>dev3Sandbox($base(8),fn()=>bbbFaseAposParedao($_SESSION['jogadores'])==='interacoes_3'));
    $caso('AUTO_05','Paredão Top 9 mantém Discórdia',fn()=>dev3Sandbox($base(9),fn()=>bbbFaseAposParedao($_SESSION['jogadores'])==='discordia'));
    $caso('AUTO_06','Falso eliminado ainda conta para Top 8',function()use($base){
        $s=$base(7);$s['paredao_falso_ativo']=true;$s['falso_eliminado']='NPC8';
        return dev3Sandbox($s,fn()=>bbbParticipantesRestantes($_SESSION['jogadores'])===8);
    });
    $caso('AUTO_07','Sem dinâmica surpresa no Top 5',function()use($base){
        $s=$base(5);$s['ds3']=['rodada'=>4,'tipo'=>'votacao_aberta'];
        return dev3Sandbox($s,fn()=>dsAtual()===[]);
    });
    $caso('AUTO_08','Votação Turbinada altera peso',function()use($base){
        $s=$base(9);$s['ds3']=['rodada'=>4,'tipo'=>'voto_em_dobro'];
        return dev3Sandbox($s,fn()=>dsPesoVoto(1,'NPC1')===2);
    });
    $caso('AUTO_09','Carta dobra somente o voto do beneficiado',function()use($base){
        $s=$base(9);$s['ds3']=['rodada'=>4,'tipo'=>'carta_misteriosa'];$s['ds3_carta']=['rodada'=>4,'nome'=>'NPC2'];
        return dev3Sandbox($s,fn()=>dsPesoVoto(1,'NPC2')===2 && dsPesoVoto(1,'NPC3')===1);
    });
    $caso('AUTO_10','Carta de semana anterior não altera votos',function()use($base){
        $s=$base(9);$s['ds3']=['rodada'=>4,'tipo'=>'carta_misteriosa'];$s['ds3_carta']=['rodada'=>3,'nome'=>'NPC2'];
        return dev3Sandbox($s,fn()=>dsPesoVoto(1,'NPC2')===1);
    });
    $caso('AUTO_11','Imunidade registrada é reconhecida',function()use($base){
        $s=$base(9);$s['ds3']=['rodada'=>4,'tipo'=>'imunidade_surpresa'];$s['ds3_imune']=['rodada'=>4,'nome'=>'NPC2'];
        return dev3Sandbox($s,fn()=>dsImune('NPC2') && !dsImune('NPC3'));
    });
    $caso('AUTO_12','Registro de efeitos não duplica',function()use($base){
        $s=$base(9);$s['ds3']=['rodada'=>4,'tipo'=>'votacao_aberta'];
        return dev3Sandbox($s,function(){
            $a=dsRegistrarEfeito('teste','Teste','Teste publicado');
            $b=dsRegistrarEfeito('teste','Teste','Teste publicado');
            return $a && !$b && count($_SESSION['evento_extra'])===1;
        });
    });
    $caso('AUTO_13','Paredão Especial tem quatro vagas',function()use($base){
        $s=$base(9);$s['ds3']=['rodada'=>4,'tipo'=>'paredao_especial'];
        return dev3Sandbox($s,fn()=>definirTamanhoParedao($_SESSION['jogadores'])===4);
    });
    $caso('AUTO_14','Paredão normal mantém faixa padrão de vagas',function()use($base){
        $s=$base(9);$s['ds3']=['rodada'=>4,'tipo'=>''];
        return dev3Sandbox($s,fn()=>in_array(definirTamanhoParedao($_SESSION['jogadores']),[3,4,5],true));
    });
    $caso('AUTO_15','Top 8 preserva proteção especial',function()use($base){
        $s=$base(8);$s['fase_semana']='anjo';$s['jogadores'][2]['status']['imune']=true;
        return dev3Sandbox($s,function(){
            $j=$_SESSION['jogadores'];bbbSincronizarRetaFinal($j);
            return ($_SESSION['fase_semana']??'')==='bigfone' && !empty($j[2]['status']['imune']);
        });
    });
    $caso('AUTO_16','Save sintético íntegro sem erros críticos',function()use($base){
        $s=$base(9);$s['fase_semana']='interacoes_1';
        $checks=dev3InspecionarSave($s);
        return count(array_filter($checks,static fn($r)=>$r['status']==='falha'))===0;
    });
    $caso('AUTO_17','Colíder imune na mesma semana',function()use($base){
        $s=$base(9);$s['lideres_dupla']=['NPC1','NPC2'];$s['lider_dupla_semana']=4;
        return dev3Sandbox($s,fn()=>estaImune($_SESSION['jogadores'],'NPC2') && !estaImune($_SESSION['jogadores'],'NPC3'));
    });
    $caso('AUTO_18','Aliança reduz chance de atacar aliado',function()use($base){
        $s=$base(9);$s['jogadores'][0]['alianca']='Os Testes';$s['jogadores'][1]['alianca']='Os Testes';
        return dev3Sandbox($s,fn()=>a3BonusVoto($_SESSION['jogadores'],'NPC1','NPC2','voto')<0);
    });
    $caso('AUTO_19','Resistência: conservar poupa energia',function(){
        $c=top4CalcularPasso(3,80,65,'conservar',2);
        $r=top4CalcularPasso(3,80,65,'arriscar',2);
        return $c['energia']>$r['energia'] && $r['pontos']>$c['pontos'];
    });
    $caso('AUTO_20','Top 4 rejeita a mesma resposta repetida',function()use($base){
        $s=$base(4);$s['fase_semana']='top4_prova';
        return dev3Sandbox($s,function(){
            $j=$_SESSION['jogadores']; top4Preparar($j);
            $e=$_SESSION['top4_estado'];
            $entrada=['indice'=>0,'token'=>$e['token'],'postura'=>'conservar','marcador'=>$e['ciclos'][0]['alvo']];
            $primeira=top4Avancar($j,$e,'NPC1',$entrada,true);
            $segunda=top4Avancar($j,$e,'NPC1',$entrada,true);
            return $primeira && !$segunda && $e['etapa']===1;
        });
    });
    $caso('AUTO_21','Top 4 impede envio sem token válido',function()use($base){
        $s=$base(4);$s['fase_semana']='top4_prova';
        return dev3Sandbox($s,function(){
            $j=$_SESSION['jogadores'];top4Preparar($j);$e=$_SESSION['top4_estado'];
            $feito=top4Avancar($j,$e,'NPC1',['indice'=>0,'token'=>'invalido','postura'=>'firmar','marcador'=>50],true);
            return !$feito && $e['etapa']===0;
        });
    });
    $caso('AUTO_22','Geração de fofocas tem lista de participantes ativos',function()use($base){
        $s=$base(4);$s['jogadores'][2]['eliminado']=true;
        return function_exists('nomesJogadoresAtivos') && dev3Sandbox($s,function(){
            $nomes=nomesJogadoresAtivos($_SESSION['jogadores']);
            return count($nomes)===3 && !in_array('NPC3',$nomes,true);
        });
    });
    // Objetivos individuais: testes de idempotência, objetivos reais e privacidade.
    $caso('AUTO_23','Objetivos NPC são estáveis ao atualizar a página',function()use($base){
        $s=$base(9);
        return dev3Sandbox($s,function(){
            on1Sincronizar($_SESSION['jogadores']);
            $antes=$_SESSION['on1_objetivos'];
            on1Sincronizar($_SESSION['jogadores']);
            return $antes === $_SESSION['on1_objetivos'] && !isset($antes['NPC1']);
        });
    });
    $caso('AUTO_24','Objetivo pessoal afeta o voto sem garanti-lo',function()use($base){
        $s=$base(9);
        $s['on1_objetivos']['NPC2']=['tipo'=>'superar_rival','alvo'=>'NPC3','inicio'=>4,'revisado'=>4];
        return dev3Sandbox($s,fn()=>on1BonusAlvo($_SESSION['jogadores'],'NPC2','NPC3','voto')>0
            && on1BonusAlvo($_SESSION['jogadores'],'NPC2','NPC4','voto')===0.0);
    });
    $caso('AUTO_25','Aliado protegido recebe preferência, não imunidade automática',function()use($base){
        $s=$base(9);
        $s['on1_objetivos']['NPC2']=['tipo'=>'proteger_aliado','alvo'=>'NPC3','inicio'=>4,'revisado'=>4];
        return dev3Sandbox($s,fn()=>on1BonusAlvo($_SESSION['jogadores'],'NPC2','NPC3','imunidade')>0
            && on1BonusAlvo($_SESSION['jogadores'],'NPC2','NPC3','voto')<0);
    });
    $caso('AUTO_26','Objetivos abandonam alvos eliminados',function()use($base){
        $s=$base(9);
        $s['on1_objetivos']['NPC2']=['tipo'=>'proteger_aliado','alvo'=>'NPC3','inicio'=>3,'revisado'=>3];
        $s['jogadores'][2]['eliminado']=true;
        return dev3Sandbox($s,function(){
            on1Sincronizar($_SESSION['jogadores']);
            $novo=on1Objetivo('NPC2');
            return !isset($_SESSION['on1_objetivos']['NPC3']) &&
                (string)($novo['alvo']??'') !== 'NPC3';
        });
    });
    $caso('AUTO_27','Objetivos não geram vazamento no Ao Vivo',function()use($base){
        $s=$base(9); $s['evento_extra']=[];
        return dev3Sandbox($s,function(){
            on1Sincronizar($_SESSION['jogadores']);
            return empty($_SESSION['evento_extra']) && empty($_SESSION['feed_posts']);
        });
    });
    // Dilemas 2.0: cenários isolados sem efeitos no save real.
    $caso('AUTO_28','Biblioteca de dilemas contém respostas e consequências',function(){
        $c=djBiblioteca();
        foreach($c as $cena) if (count($cena['opcoes']??[]) < 3 || empty($cena['titulo'])) return false;
        return count($c)>=12;
    });
    $caso('AUTO_29','Dilemas só incluem pessoas ativas',function()use($base){
        $s=$base(7); $s['jogadores'][2]['eliminado']=true;
        return dev3Sandbox($s,function(){
            $c=djCandidatos($_SESSION['jogadores'],'NPC1','interacoes_1');
            foreach($c as $item) if(in_array('NPC3',[(string)$item['npc'],(string)$item['outro']],true)) return false;
            return count($c)>0;
        });
    });
    $caso('AUTO_30','Promessa aceita protege sem garantir o voto',function()use($base){
        $s=$base(7); $s['dj_compromissos']['NPC2']=['tipo'=>'protecao','aceito'=>true,'ate'=>4,'semana'=>3];
        return dev3Sandbox($s,function(){
            $j=$_SESSION['jogadores'];
            return djBonusAlvo($j,'NPC2','NPC1','voto')<0
                && djBonusAlvo($j,'NPC2','NPC1','imunidade')>0
                && djBonusAlvo($j,'NPC3','NPC1','voto')===0.0;
        });
    });
    $caso('AUTO_31','Compromisso expira sem favorecer outra semana',function()use($base){
        $s=$base(7);$s['rodada']=5;$s['dj_compromissos']['NPC2']=['tipo'=>'protecao','aceito'=>true,'ate'=>4,'semana'=>3];
        return dev3Sandbox($s,fn()=>djBonusAlvo($_SESSION['jogadores'],'NPC2','NPC1','voto')===0.0);
    });
    $caso('AUTO_32','Dilema forçado não duplica ao atualizar',function()use($base){
        $s=$base(7);$s['dev_dj_forcar']=true;
        return dev3Sandbox($s,function(){
            $_SERVER['REQUEST_METHOD']='GET';
            $j=$_SESSION['jogadores'];djTentarGerar($j,'NPC1','interacoes_1');
            $primeiro=$_SESSION['dj_ativo']??null;
            djTentarGerar($j,'NPC1','interacoes_1');
            return is_array($primeiro) && $primeiro===$_SESSION['dj_ativo'] && (int)$_SESSION['dj_gerado_semana']===4;
        });
    });
    // Interações 4.0 — cenas isoladas, não mexem na temporada real.
    $caso('AUTO_33','Interações 4.0 possuem respostas para cada ação',function(){
        foreach(i4Catalogo() as $tipo=>$item) if(count(i4Opcoes($tipo))!==3 || empty($item[1]))return false;
        return count(i4Catalogo())>=10;
    });
    $caso('AUTO_34','Interação não cobra ação antes de responder',function()use($base){
        $s=$base(7);$s['acoes_restantes']=3;
        return dev3Sandbox($s,function(){
            $j=$_SESSION['jogadores'];$antes=$j;
            return i4Iniciar($j,'NPC1','interacoes_1',['acao'=>'i4_pessoal','alvo'=>'NPC2','i4_assunto'=>'saudade','i4_abordagem'=>'leve'])
                && (int)$_SESSION['acoes_restantes']===3 && $j===$antes;
        });
    });
    $caso('AUTO_35','Respostas duplicadas não alteram relações novamente',function()use($base){
        $s=$base(7);$s['acoes_restantes']=3;
        return dev3Sandbox($s,function(){
            $j=$_SESSION['jogadores']; i4Iniciar($j,'NPC1','interacoes_1',['acao'=>'i4_conversar','alvo'=>'NPC2','i4_assunto'=>'jogo','i4_abordagem'=>'sincera']);
            $t=$_SESSION['i4_pendente']['token']??'';
            if($t==='' || !i4Responder($j,'NPC1','interacoes_1',$t,0))return false;
            $antes=$j;$ses=$_SESSION;
            return i4Responder($j,'NPC1','interacoes_1',$t,0)==='' && $antes===$j && $ses===$_SESSION;
        });
    });
    $caso('AUTO_36','Eliminados não recebem conversas do jogador',function()use($base){
        $s=$base(7);$s['acoes_restantes']=3;$s['jogadores'][1]['eliminado']=true;
        return dev3Sandbox($s,fn()=>!i4Iniciar($_SESSION['jogadores'],'NPC1','interacoes_1',
            ['acao'=>'i4_pessoal','alvo'=>'NPC2','i4_assunto'=>'saudade','i4_abordagem'=>'leve']));
    });
    $caso('AUTO_37','Proteção negociada expira sem voto automático',function()use($base){
        $s=$base(7);$s['i4_acordos']['NPC2']=['tipo'=>'protecao','aceito'=>true,'semana'=>4,'ate'=>5];
        return dev3Sandbox($s,function(){
            $j=$_SESSION['jogadores'];$v=i4BonusAlvo($j,'NPC2','NPC1','voto')<0
                && i4BonusAlvo($j,'NPC2','NPC1','imunidade')>0;
            $_SESSION['rodada']=6;
            return $v && i4BonusAlvo($j,'NPC2','NPC1','voto')===0.0;
        });
    });
    $caso('AUTO_38','Conversas antigas não travam a fase seguinte',function()use($base){
        $s=$base(7);$s['i4_pendente']=['semana'=>3,'fase'=>'interacoes_1','alvo'=>'NPC2','token'=>str_repeat('a',24)];
        return dev3Sandbox($s,function(){i4SincronizarCena('interacoes_2');return empty($_SESSION['i4_pendente']);});
    });
    // 🔥 Discórdia 3.0 — regressões não destrutivas.
    $caso('AUTO_39','Discórdia 3.0 valida apontamentos e bloqueia nomes inexistentes',function()use($base){
        $s=$base(9);$s['fase_semana']='discordia';
        return dev3Sandbox($s,function(){
            $j=$_SESSION['jogadores'];
            return d3ValidaEscolha(['alvo_discordia'=>'NPC2','intensidade'=>'leve'],$j,'NPC1','falso')!==null
                && d3ValidaEscolha(['alvo_discordia'=>'Inexistente','intensidade'=>'leve'],$j,'NPC1','falso')===null;
        });
    });
    $caso('AUTO_40','Discórdia 3.0 preserva todos os temas clássicos',function()use($base){
        return dev3Sandbox($base(9),fn()=>count(temasDiscordia())>=12
            && d3ValidaEscolha(['podio_2'=>'NPC2','podio_3'=>'NPC3'],$_SESSION['jogadores'],'NPC1','podio')!==null);
    });
    $caso('AUTO_41','Argumentos com fatos exigem evento público real',function()use($base){
        $s=$base(9);$s['historico_temporada']=[
            ['tipo'=>'confessionario','rodada'=>4,'participantes'=>['NPC1','NPC2'],'descricao'=>'segredo privado'],
            ['tipo'=>'discussao','rodada'=>4,'participantes'=>['NPC1','NPC2'],'descricao'=>'Discutiram na cozinha'],
        ];
        return dev3Sandbox($s,function(){
            $f=d3FatosPublicos('NPC1','NPC2',4);
            return count($f)===1 && $f[0]['descricao']==='Discutiram na cozinha'
                && !isset(d3OpcoesArgumento(['alvo'=>'NPC2','positivo'=>false,'fatos'=>[]])['fato']);
        });
    });
    $caso('AUTO_42','Tréplica só oferece escolhas compatíveis com o tema',function()use($base){
        return dev3Sandbox($base(9),fn()=>isset(d3OpcoesTreplica(false)['sustentar'])
            && !isset(d3OpcoesTreplica(true)['sustentar']) && isset(d3OpcoesTreplica(true)['reforcar']));
    });
    $caso('AUTO_43','Top 8 bloqueia Jogo da Discórdia 3.0',function()use($base){
        return dev3Sandbox($base(8),fn()=>bbbRetaFinalSemDiscordia($_SESSION['jogadores']));
    });
    $caso('AUTO_44','A etapa pendente expira na troca de fase',function()use($base){
        $s=$base(9);$s['d3_pendente']=['semana'=>4,'alvo'=>'NPC2','etapa'=>'argumento','token'=>'teste'];
        return dev3Sandbox($s,function(){d3Sincronizar($_SESSION['jogadores']);return empty($_SESSION['d3_pendente']);});
    });
    return $out;
}

function dev3Bateria(array $estado, string $escopo='completo'): array {
    $linhas=[];
    if ($escopo!=='simulacoes') $linhas=array_merge($linhas,dev3InspecionarSave($estado));
    if ($escopo!=='save') $linhas=array_merge($linhas,dev3TestesSinteticos());
    $resumo=['ok'=>0,'aviso'=>0,'falha'=>0];
    foreach($linhas as $l) $resumo[$l['status']]++;
    return ['linhas'=>$linhas,'resumo'=>$resumo,'escopo'=>$escopo,'semana'=>(int)($estado['rodada']??0),'executado_em'=>date('d/m/Y H:i:s')];
}
