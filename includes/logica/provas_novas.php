<?php
/**
 * Provas Interativas 4.0: desafios persistidos na sessão, correção no servidor.
 * Não usa pontuação enviada pelo cliente como verdade.
 */
function pnCatalogo($classe) {
    if ($classe === 'lider') return [
        14=>['titulo'=>'🔐 Código da Liderança','texto'=>'Decifre quatro mecanismos numéricos para abrir a sala de comando.','categoria'=>'🧩 Raciocínio','dificuldade'=>4,'mecanica'=>'codigo'],
        15=>['titulo'=>'🧲 Ordem Magnética','texto'=>'Reconstrua a ordem das peças antes que a contagem termine.','categoria'=>'🧠 Atenção','dificuldade'=>3,'mecanica'=>'ordem'],
        16=>['titulo'=>'🌀 Labirinto da Casa','texto'=>'Escolha suas rotas e encontre as passagens mais eficientes.','categoria'=>'🧭 Decisão','dificuldade'=>4,'mecanica'=>'rotas'],
        17=>['titulo'=>'✨ Painel Oculto','texto'=>'Observe os símbolos e reconstrua a sequência quando as luzes se apagarem.','categoria'=>'🧠 Memória','dificuldade'=>4,'mecanica'=>'memoria'],
        18=>['titulo'=>'🎲 Tudo ou Nada','texto'=>'Gerencie o risco em quatro etapas: a coragem pode render pontos ou custar caro.','categoria'=>'🎯 Estratégia','dificuldade'=>4,'mecanica'=>'risco'],
        19=>['titulo'=>'🤝 Conexão Perfeita — Duplas','texto'=>'Escolha um parceiro e resolvam juntos os desafios da produção.','categoria'=>'🤝 Cooperação','dificuldade'=>4,'mecanica'=>'dupla']
    ];
    return [
        5=>['slug'=>'pn_cofre','titulo'=>'🔐 Cofre Secreto','texto'=>'Decifre pistas matemáticas para abrir o cofre do Anjo.','categoria'=>'Raciocínio','dificuldade'=>'Alta','mecanica'=>'codigo'],
        6=>['slug'=>'pn_cores','titulo'=>'🎨 Sequência das Cores','texto'=>'Memorize as cores e reproduza a ordem quando o painel apagar.','categoria'=>'Memória','dificuldade'=>'Alta','mecanica'=>'memoria'],
        7=>['slug'=>'pn_chaves','titulo'=>'🗝️ Caça às Chaves','texto'=>'Escolha as rotas corretas para alcançar os compartimentos.','categoria'=>'Exploração','dificuldade'=>'Média','mecanica'=>'rotas'],
        8=>['slug'=>'pn_quebra','titulo'=>'🧩 Quebra-cabeça Relâmpago','texto'=>'Reorganize peças e forme a sequência de segurança.','categoria'=>'Organização','dificuldade'=>'Média','mecanica'=>'ordem'],
        9=>['slug'=>'pn_precisao','titulo'=>'🎯 Precisão do Anjo','texto'=>'Ajuste os quatro marcadores o mais próximo possível do alvo.','categoria'=>'Precisão','dificuldade'=>'Média','mecanica'=>'precisao'],
        10=>['slug'=>'pn_dupla','titulo'=>'🤝 Anjo em Sintonia — Duplas','texto'=>'Escolha um parceiro e solucionem juntos os desafios do Anjo.','categoria'=>'Cooperação','dificuldade'=>'Alta','mecanica'=>'dupla']
    ];
}
function pnEstaNova($classe, $tipo) {return isset(pnCatalogo($classe)[(int)$tipo]);}
function pnPreparar($classe, $tipo) {
    $tipo=(int)$tipo; $rodada=(int)($_SESSION['rodada']??1);
    $chave='pn_estado_'.$classe;
    if (isset($_SESSION[$chave]) && (int)($_SESSION[$chave]['semana']??0)===$rodada && (int)($_SESSION[$chave]['tipo']??0)===$tipo) return $_SESSION[$chave];
    $meta=pnCatalogo($classe)[$tipo]; $m=$meta['mecanica'];
    $estado=['semana'=>$rodada,'tipo'=>$tipo,'mecanica'=>$m,'questoes'=>[]];
    if ($m==='codigo'||$m==='dupla') {
        for($i=0;$i<4;$i++){
            $a=random_int(5,24); $b=random_int(2,12);
            $op=$i%3;
            $resp=($op===0?$a+$b:($op===1?$a-$b:$a*2+$b));
            $texto=($op===0?"Painel ".($i+1).": $a + $b = ?":($op===1?"Fechadura ".($i+1).": $a − $b = ?":"Circuito ".($i+1).": (2 × $a) + $b = ?"));
            $estado['questoes'][]=['texto'=>$texto,'resposta'=>$resp];
        }
    } elseif($m==='ordem') {
        $pool=['⭐','💎','🔑','🔥','🌙','🦋','🎯','👑']; shuffle($pool);
        $estado['sequencia']=array_slice($pool,0,5);
        $estado['embaralhado']=$estado['sequencia'];shuffle($estado['embaralhado']);
    } elseif($m==='memoria') {
        $pool=$classe==='anjo'?['🔴','🔵','🟢','🟡']:['⭐','💎','🔑','🔥','🌙'];
        for($i=0;$i<6;$i++) $estado['sequencia'][]=$pool[array_rand($pool)];
        $estado['opcoes']=$pool;
    } elseif($m==='rotas') {
        $historias=[
            ['Você encontra três portas no corredor. Qual sinal acompanha a rota de emergência?',['Saída iluminada','Porta trancada','Alarme desligado'],0],
            ['Uma escada termina em uma bifurcação. Qual trajeto usa menos degraus?',['Doze degraus','Oito degraus','Dezesseis degraus'],1],
            ['No jardim há três trilhas. Qual indicação mantém você no percurso seguro?',['Área interditada','Solo escorregadio','Passagem sinalizada'],2],
            ['O relógio indica três caminhos para o final. Qual deles reduz o desvio?',['Caminho direto','Volta completa','Escada extra'],0]
        ];
        foreach($historias as $h) $estado['questoes'][]=['texto'=>$h[0],'opcoes'=>$h[1],'resposta'=>$h[2]];
    } elseif($m==='risco') {
        for($i=0;$i<4;$i++) $estado['questoes'][]=['texto'=>'Etapa '.($i+1).': quantos pontos você deseja arriscar?','opcoes'=>['Segurança (+15 pontos)','Equilíbrio (+28 ou −8)','Tudo ou nada (+47 ou −20)'],'sorte'=>random_int(1,100)];
    } elseif($m==='precisao') {
        for($i=0;$i<4;$i++) $estado['questoes'][]=['texto'=>'Marcador '.($i+1),'resposta'=>random_int(15,85)];
    }
    $_SESSION[$chave]=$estado;
    return $estado;
}
function pnCorrigir($classe, $tipo, $post) {
    $estado=pnPreparar($classe,$tipo); $m=$estado['mecanica']; $res=$post['pn_respostas']??[];
    if(!is_array($res)) return null;
    $pontos=0;
    if($m==='codigo'||$m==='dupla') {
        foreach($estado['questoes'] as $i=>$q){
            if(!isset($res[$i])||!preg_match('/^-?\d{1,5}$/',(string)$res[$i]))return null;
            if((int)$res[$i]===(int)$q['resposta'])$pontos+=25;
        }
    } elseif($m==='ordem'||$m==='memoria') {
        $lista=explode('|',(string)($post['pn_ordem']??''));
        if(count($lista)!==count($estado['sequencia']))return null;
        if($m==='ordem') { $original=$estado['sequencia']; $enviada=$lista; sort($original);sort($enviada);if($original!==$enviada)return null; }
        if($m==='memoria' && count(array_diff($lista,$estado['opcoes']))>0)return null;
        foreach($estado['sequencia'] as $i=>$item){if($item===$lista[$i])$pontos+=(100/count($estado['sequencia']));}
    } elseif($m==='rotas') {
        foreach($estado['questoes'] as $i=>$q){if(!isset($res[$i])||!in_array((string)$res[$i],['0','1','2'],true)) return null;if((int)$res[$i]===$q['resposta'])$pontos+=25;}
    } elseif($m==='risco') {
        $pontos=35;
        foreach($estado['questoes'] as $i=>$q){if(!isset($res[$i])||!in_array((string)$res[$i],['0','1','2'],true))return null;$v=(int)$res[$i];$pontos+=$v===0?15:($v===1?($q['sorte']<=65?28:-8):($q['sorte']<=38?47:-20));}
    } elseif($m==='precisao') {
        foreach($estado['questoes'] as $i=>$q){if(!isset($res[$i])||!preg_match('/^\d{1,3}$/',(string)$res[$i]))return null;$v=(int)$res[$i];if($v<0||$v>100)return null;$pontos+=max(0,25-abs($v-$q['resposta'])*0.75);}
    }
    return (int)round(max(0,min(100,$pontos)));
}
function pnParceiros($jogadores,$eu,$lider='') {
    $nomes=[];
    foreach($jogadores as $j){$n=(string)($j['nome']??'');if($n!==''&&$n!==$eu&&$n!==$lider)$nomes[]=$n;}
    return $nomes;
}
function pnParceiroAceita($jogadores,$eu,$parceiro) {
    // Resposta estável durante a semana, nunca rerrolada no refresh.
    $key='pn_convite_'.($_SESSION['rodada']??1).'_'.sha1($eu.'|'.$parceiro);
    if(!array_key_exists($key,$_SESSION)){
        $afinidade=(int)($_SESSION['relacoes_jogador'][$parceiro]??0);
        foreach($jogadores as $j)if(($j['nome']??'')===$parceiro){$r=$j['relacoes'][$eu]??[];$afinidade+=(int)($r['amizade']??0)+(int)($r['confianca']??0)-(int)($r['rivalidade']??0);break;}
        $_SESSION[$key]=random_int(1,100)<=max(25,min(95,75+$afinidade/7));
    }
    return (bool)$_SESSION[$key];
}
function pnRankingLider($jogadores,$eu,$score,$dupla=false,$parceiro='') {
    $candidatos=[['nome'=>$eu,'pontos'=>$score,'parceiro'=>$dupla?$parceiro:'','sorte'=>random_int(1,100000)]];
    $outros=[];
    foreach($jogadores as $j)if(($j['nome']??'')!==$eu&&(!$dupla||($j['nome']??'')!==$parceiro))$outros[]=$j;
    if($dupla)shuffle($outros);
    foreach($outros as $i=>$j){
        if($dupla && ($i%2===1 || !isset($outros[$i+1]))) continue;
        $candidatos[]=['nome'=>$j['nome'],'pontos'=>random_int(45,91),'parceiro'=>$dupla?(string)($outros[$i+1]['nome']??''):'','sorte'=>random_int(1,100000)];
    }
    usort($candidatos,fn($a,$b)=>($b['pontos']<=>$a['pontos'])?:($b['sorte']<=>$a['sorte']));
    return $candidatos;
}
function pnRankingAnjo($participantes,$eu,$score,$dupla=false,$parceiro='') {
    $candidatos=[['nome'=>$eu,'pontos'=>$score,'parceiro'=>$dupla?$parceiro:'','desempate'=>random_int(1,100000)]];
    $outros=[];foreach($participantes as $j)if(($j['nome']??'')!==$eu&&(!$dupla||($j['nome']??'')!==$parceiro))$outros[]=$j;
    if($dupla)shuffle($outros);
    foreach($outros as $i=>$j){if($dupla&&($i%2===1 || !isset($outros[$i+1])))continue;$candidatos[]=['nome'=>$j['nome'],'pontos'=>random_int(42,94),'parceiro'=>$dupla?(string)($outros[$i+1]['nome']??''):'','desempate'=>random_int(1,100000)];}
    usort($candidatos,fn($a,$b)=>($b['pontos']<=>$a['pontos'])?:($b['desempate']<=>$a['desempate']));
    foreach($candidatos as $i=>&$c){$c['posicao']=$i+1;$c['eh_jogador']=$c['nome']===$eu;unset($c['desempate']);}unset($c);
    return $candidatos;
}

/** A opinião do parceiro pode divergir. Não usa popularidade nem informação secreta do jogador. */
function pnPreferenciaParceiro($jogadores,$parceiro,$natureza) {
    $rodada=(int)($_SESSION['rodada']??1);
    $chave='pn_preferencia_'.$natureza;
    if(isset($_SESSION[$chave]) && (int)($_SESSION[$chave]['semana']??0)===$rodada && ($_SESSION[$chave]['parceiro']??'')===$parceiro)
        return (string)($_SESSION[$chave]['nome']??'');
    $r=[];
    foreach($jogadores as $j){
        $nome=(string)($j['nome']??'');
        if($nome===''||$nome===$parceiro||!empty($j['status']['lider']))continue;
        if($natureza==='lider' && function_exists('estaImune')&&estaImune($jogadores,$nome))continue;
        $ligacao=0;
        foreach($jogadores as $d)if(($d['nome']??'')===$parceiro){$rel=(array)($d['relacoes'][$nome]??[]);$ligacao=(int)($rel['amizade']??0)+(int)($rel['confianca']??0)-(int)($rel['rivalidade']??0);break;}
        $r[$nome]=$ligacao;
    }
    if(!$r)return '';
    if($natureza==='lider')asort($r);else arsort($r);
    $nomes=array_keys($r);$primeiros=array_slice($nomes,0,min(3,count($nomes)));
    $nome=$primeiros[array_rand($primeiros)];
    $_SESSION[$chave]=['semana'=>$rodada,'parceiro'=>$parceiro,'nome'=>$nome];
    return $nome;
}

function pnRankingNPCsAnjo($participantes) {
    $lista=array_values($participantes);shuffle($lista);$ranking=[];
    foreach($lista as $i=>$j){
        if($i%2!==0||!isset($lista[$i+1]))continue;
        $ranking[]=['nome'=>(string)($j['nome']??''),'parceiro'=>(string)($lista[$i+1]['nome']??''),
            'pontos'=>random_int(48,96),'eh_jogador'=>false,'desempate'=>random_int(1,100000)];
    }
    usort($ranking,fn($a,$b)=>($b['pontos']<=>$a['pontos'])?:($b['desempate']<=>$a['desempate']));
    foreach($ranking as $i=>&$j){$j['posicao']=$i+1;unset($j['desempate']);}unset($j);
    return $ranking;
}
