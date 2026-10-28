<?php
/** Cerimônia de encerramento com pedidos de voto e torcida de ex-participantes. */
function finalOpcoesDiscurso(): array {
    return [
        'coracao'=>['titulo'=>'❤️ Jogar com o coração','fala'=>'Eu vivi tudo intensamente. Acertei, errei, fiz amizades e aprendi. Se minha verdade chegou até vocês, peço o voto de vocês!'],
        'estrategia'=>['titulo'=>'🎯 Defender meu jogo','fala'=>'Eu tomei decisões difíceis, construí meu caminho e enfrentei as consequências. Quero que o Brasil reconheça a minha trajetória!'],
        'superacao'=>['titulo'=>'🔥 Relembrar minha superação','fala'=>'Eu pensei que não aguentaria várias vezes, mas cheguei até aqui. Cada prova e cada Paredão fizeram parte dessa história. Conto com vocês!'],
        'gratidao'=>['titulo'=>'🤝 Agradecer e pedir confiança','fala'=>'Nada disso teria sentido sem quem esteve comigo na casa e quem acompanhou de fora. Obrigado por cada semana. Se mereci, quero o voto de vocês!'],
    ];
}
function finalEliminadosTorcendo(array $finalistas): array {
    $nomes=array_column($finalistas,'nome');
    $lista=(array)($_SESSION['historico_eliminados']??[]);
    $snapshots=(array)($_SESSION['participantes_eliminados_dados']??[]);
    $torcidas=[];$vistos=[];
    foreach($lista as $exNome) {
        $exNome=(string)$exNome;
        if($exNome===''||in_array($exNome,$nomes,true)||in_array($exNome,$vistos,true)) continue;
        $vistos[]=$exNome;
        $dados=is_array($snapshots[$exNome]??null)?$snapshots[$exNome]:[];
        $pontuacoes=[];
        foreach($finalistas as $finalista) {
            $nome=(string)($finalista['nome']??'');$rel=(array)($dados['relacoes'][$nome]??[]);
            $am=(float)($rel['amizade']??0);$riv=(float)($rel['rivalidade']??0);$conf=(float)($rel['confianca']??0);
            $score=$am + .7*$conf - 1.2*$riv;
            if($nome===(string)($_SESSION['meu_nome']??'')){
                // Relação conhecida na saída, quando não existe matriz NPC↔jogador no snapshot.
                $score += 0.5*(float)($_SESSION['relacoes_jogador'][$exNome]??0);
            }
            $pontuacoes[$nome]=$score;
        }
        // Em caso de empate, o desempate fica estável entre reloads.
        uksort($pontuacoes,static function($a,$b)use($pontuacoes,$exNome){
            return ($pontuacoes[$b]<=>$pontuacoes[$a])?:((crc32($exNome.$b)%1000)<=>(crc32($exNome.$a)%1000));
        });
        $alvo=(string)array_key_first($pontuacoes);
        if($alvo==='')continue;
        $score=(float)$pontuacoes[$alvo];
        // Conjuntos variados com contexto da relação na saída e história pessoal.
        $frasesPositivas=[
          '"A gente se apoiou quando as coisas apertaram. Eu quero ver %s levantando esse troféu."',
          '"Tem muita coisa que não passou na edição, mas eu sei como %s foi comigo. Minha torcida é essa."',
          '"Eu me vi em algumas atitudes de %s. Tomara que leve esse prêmio."',
          '"Sei quantas vezes %s teve que engolir o choro e continuar. Hoje eu fico na torcida."',
          '"Minha aposta é %s! Convivi com essa pessoa e conheço a determinação que tem."',
          '"Quando eu precisei, %s estava lá. Não esqueço quem me acolheu."',
          '"Posso ter saído, mas a amizade que construí com %s continua. Vai com tudo!"',
          '"A gente teve conversas que me marcaram. Quero ver %s vencer, de coração."',
          '"A torcida aqui de fora é por %s. Eu sei como foi difícil chegar tão longe."',
          '"Sempre falei que %s tinha muito jogo pela frente. Agora quero ver ganhar."',
          '"Não vou fingir neutralidade: meu voto é de %s. É alguém que admiro."',
          '"Eu reconheço lealdade quando vejo, e %s foi leal comigo em momentos importantes."',
        ];
        $frasesRessalva=[
          '"Não concordei com tudo lá dentro, mas hoje vou torcer por %s."',
          '"Se eu pudesse escolher, talvez a final fosse outra. Entre quem chegou, fico com %s."',
          '"Tive altos e baixos com essa pessoa. Mesmo assim, acho que %s merece essa chance."',
          '"Não vou falar que foi fácil conviver, não. Mas meu voto agora é em %s."',
          '"Jogo é jogo. Deixando as diferenças de lado, quero que %s ganhe."',
          '"Eu tenho críticas, sim. Só que preciso reconhecer o caminho de %s até aqui."',
          '"Se a pergunta é quem eu prefiro entre os finalistas, minha resposta é %s."',
          '"Nunca pensei que diria isso no começo do programa, mas hoje torço por %s."',
          '"Não somos melhores amigos, mas eu respeito como %s resistiu à pressão."',
          '"O público vai decidir. Se dependesse de mim, o troféu seria de %s."',
          '"Não foi uma relação perfeita, mas %s me surpreendeu em alguns momentos."',
          '"Talvez a gente converse melhor aqui fora. Por enquanto, minha torcida fica com %s."',
        ];
        $frases=$score>=5?$frasesPositivas:$frasesRessalva;
        // Escala determinística com dispersão para não repetir a mesma frase
        // entre os eliminados na mesma cerimônia, inclusive em reloads.
        $indices=array_keys($frases);
        $indice=(abs(crc32($exNome.'|'.$alvo)) + count($torcidas)*5)%count($indices);
        $indiceUsado=$indice;
        $textosUsados=array_column($torcidas,'fala');
        for($tentativa=0;$tentativa<count($frases);$tentativa++) {
            $indiceUsado=($indice+$tentativa)%count($frases);
            if(!in_array(sprintf($frases[$indiceUsado],$alvo),$textosUsados,true))break;
        }
        $torcidas[]=['nome'=>$exNome,'alvo'=>$alvo,'fala'=>sprintf($frases[$indiceUsado],$alvo)];
    }
    return $torcidas;
}
function finalPrepararCerimonia(array $jogadores): void {
    if(isset($_SESSION['final_cerimonia']) && is_array($_SESSION['final_cerimonia']))return;
    // Saves antigos em que todos os finalistas já saturaram a escala em 100.
    // Corrige uma vez antes da cerimônia, preservando a diferença de trajetória.
    if(count($jogadores)===3 && count(array_filter($jogadores,fn($j)=>(int)($j['popularidade']??50)>=98))===3) {
        foreach($jogadores as &$j){
            $n=(string)($j['nome']??''); $est=(array)($j['estatisticas']??[]);
            $desempenho=min(14, 2*(int)($est['lider']??0)+(int)($est['anjo']??0)+(int)($est['paredao']??0));
            $j['popularidade']=min(95,72+$desempenho+abs(crc32($n.'|'.($_SESSION['rodada']??1)))%8);
        }
        unset($j);
        $_SESSION['jogadores']=$jogadores;
    }
    $opcoes=finalOpcoesDiscurso();$chaves=array_keys($opcoes);
    $discursos=[];
    foreach($jogadores as $j){
        $nome=(string)($j['nome']??'');
        $tipo=$chaves[abs(crc32($nome.'|'.($_SESSION['rodada']??1)))%count($chaves)];
        $discursos[$nome]=['tipo'=>$tipo,'fala'=>$opcoes[$tipo]['fala']];
    }
    $_SESSION['final_cerimonia']=['discursos'=>$discursos,'torcidas'=>finalEliminadosTorcendo($jogadores),'concluida'=>false];
}
function finalRegistrarTorcidaEspectador(array $jogadores, string $alvo): bool {
    $meu=(string)($_SESSION['meu_nome']??'');
    if($meu==='' || empty($_SESSION['modo_espectador']) ||
        !in_array($meu,(array)($_SESSION['historico_eliminados']??[]),true) ||
        !empty($_SESSION['final_cerimonia']['concluida']))return false;
    if(!in_array($alvo,array_column($jogadores,'nome'),true))return false;
    $frases=[
        '"Agora que estou aqui fora, minha torcida é por %s. Quero ver até onde essa trajetória vai!"',
        '"A gente viveu muita coisa juntos, e meu apoio na final vai pra %s."',
        '"Já não estou na disputa, mas ainda tenho minha torcida. Quero ver %s campeão!"',
        '"O meu jogo acabou, mas eu ainda tenho uma pessoa pra apoiar: %s."',
    ];
    $frase=$frases[abs(crc32($meu.$alvo))%count($frases)];
    foreach($_SESSION['final_cerimonia']['torcidas'] as &$v){
        if(($v['nome']??'')===$meu) {
            $v['alvo']=$alvo; $v['fala']=sprintf($frase,$alvo);
            $_SESSION['final_cerimonia']['torcida_espectador']=$alvo;
            return true;
        }
    }
    unset($v);
    $_SESSION['final_cerimonia']['torcidas'][]=['nome'=>$meu,'alvo'=>$alvo,'fala'=>sprintf($frase,$alvo)];
    $_SESSION['final_cerimonia']['torcida_espectador']=$alvo;
    return true;
}
function finalConfirmarDiscurso(array $jogadores, string $tom): bool {
    if(!empty($_SESSION['final_cerimonia']['concluida']))return false;
    $opcoes=finalOpcoesDiscurso();
    if(!isset($opcoes[$tom]))return false;
    $meu=(string)($_SESSION['meu_nome']??'');
    $pessoaEmFinal=false;
    foreach($jogadores as $j)if((string)($j['nome']??'')===$meu)$pessoaEmFinal=true;
    if($pessoaEmFinal)$_SESSION['final_cerimonia']['discursos'][$meu]=['tipo'=>$tom,'fala'=>$opcoes[$tom]['fala']];
    foreach($jogadores as &$j) {
        $nome=(string)($j['nome']??'');
        $tipo=(string)($_SESSION['final_cerimonia']['discursos'][$nome]['tipo']??'coracao');
        $estat=(array)($j['estatisticas']??[]);
        $bonus=1;
        if($tipo==='estrategia')$bonus+=min(2,(int)($estat['lider']??0));
        elseif($tipo==='superacao')$bonus+=min(2,(int)($estat['paredao']??0));
        elseif($tipo==='gratidao')$bonus+=1;
        else $bonus+=1;
        // Impacto discreto, sem expor percentuais ou permitir reenvio.
        $j['popularidade']=min(100,max(0,(int)($j['popularidade']??50)+$bonus));
    }
    unset($j);
    $_SESSION['jogadores']=$jogadores;
    unset($_SESSION['ranking_final'], $_SESSION['ranking_final_versao'], $_SESSION['pontuacoes_final'], $_SESSION['percentuais_final'], $_SESSION['ordem_visual_final']);
    $_SESSION['final_cerimonia']['concluida']=true;
    return true;
}
