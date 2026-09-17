<?php

require_once __DIR__ . '/utilitarios.php';

/* =========================================================
   👑 CATÁLOGO DE PROVAS DO LÍDER
   ========================================================= */
function obterProvasLider()
{
    return [
        1 => ['titulo'=>'🎯 Mira do Líder','texto'=>'Escolha um número entre 1 e 5. Quem chegar mais perto vence.','max'=>5,'tipo'=>'numero','categoria'=>'🎯 Estratégia','dificuldade'=>2],
        2 => ['titulo'=>'⚡ Reflexo BBB','texto'=>'Espere o sinal liberar e clique o mais rápido possível.','tipo'=>'reflexo','categoria'=>'⚡ Agilidade','dificuldade'=>3],
        3 => ['titulo'=>'🧠 Memória da Casa','texto'=>'Memorize a sequência de emojis. Depois, escreva o nome de cada símbolo na ordem correta.','tipo'=>'memoria','categoria'=>'🧠 Memória','dificuldade'=>3],
        4 => ['titulo'=>'🎲 Dado da Sorte','texto'=>'Escolha um número de 1 a 6. Se seu número for o sorteado, você vence.','max'=>6,'tipo'=>'numero','categoria'=>'🍀 Sorte','dificuldade'=>1],
        5 => ['titulo'=>'🏹 Alvo Premiado','texto'=>'Escolha um alvo. Um deles esconde a liderança.','max'=>5,'tipo'=>'alvo','categoria'=>'🍀 Sorte','dificuldade'=>1],
        6 => ['titulo'=>'💎 Cofre Misterioso','texto'=>'Escolha um cofre. Apenas um guarda a chave do quarto do Líder.','max'=>4,'tipo'=>'cofre','categoria'=>'🍀 Sorte','dificuldade'=>1],
        7 => ['titulo'=>'🚀 Decolagem BBB','texto'=>'Escolha uma nave. A nave certa dispara rumo à liderança.','max'=>3,'tipo'=>'nave','categoria'=>'🍀 Sorte','dificuldade'=>1],
        8 => ['titulo'=>'🎰 Slot da Sorte','texto'=>'Escolha um símbolo. Se ele aparecer no sorteio, você vence.','max'=>4,'tipo'=>'simbolo','categoria'=>'🍀 Sorte','dificuldade'=>1],
        9 => ['titulo'=>'📦 Caixa Surpresa','texto'=>'Escolha uma caixa. Uma delas contém o poder da liderança.','max'=>5,'tipo'=>'caixa','categoria'=>'🍀 Sorte','dificuldade'=>1],
        10 => ['titulo'=>'🌪️ Giro da Liderança','texto'=>'Escolha uma cor. A roleta vai decidir quem leva a liderança.','max'=>4,'tipo'=>'cor','categoria'=>'🍀 Sorte','dificuldade'=>1],
        11 => ['titulo'=>'🔥 Totem do Líder','texto'=>'Escolha um totem. O totem correto acende e garante a liderança.','max'=>5,'tipo'=>'totem','categoria'=>'🍀 Sorte','dificuldade'=>1],
        12 => ['titulo'=>'🔢 Sequência Lógica','texto'=>'Resolva três padrões. Cada acerto vale um ponto e o melhor desempenho conquista a liderança.','tipo'=>'logica','categoria'=>'🔢 Raciocínio','dificuldade'=>4],
        13 => ['titulo'=>'🎯 Caminho Estratégico','texto'=>'Passe por três decisões de risco e recompensa. A maior pontuação vence.','tipo'=>'estrategia','categoria'=>'🎯 Estratégia','dificuldade'=>4]
    ];
}

/* =========================================================
   🧠 MEMÓRIA DA CASA
   ========================================================= */
function bancoSimbolosMemoriaLider()
{
    return [
        ['emoji'=>'⭐','nome'=>'estrela','aliases'=>['estrela']],
        ['emoji'=>'🔥','nome'=>'fogo','aliases'=>['fogo','chama']],
        ['emoji'=>'💎','nome'=>'diamante','aliases'=>['diamante','joia']],
        ['emoji'=>'👁️','nome'=>'olho','aliases'=>['olho']],
        ['emoji'=>'🎯','nome'=>'alvo','aliases'=>['alvo','mira']],
        ['emoji'=>'❤️','nome'=>'coração','aliases'=>['coracao','coração']],
        ['emoji'=>'🌙','nome'=>'lua','aliases'=>['lua']],
        ['emoji'=>'☀️','nome'=>'sol','aliases'=>['sol']],
        ['emoji'=>'⚡','nome'=>'raio','aliases'=>['raio','relampago','relâmpago']],
        ['emoji'=>'👑','nome'=>'coroa','aliases'=>['coroa']],
        ['emoji'=>'🦋','nome'=>'borboleta','aliases'=>['borboleta']],
        ['emoji'=>'🔑','nome'=>'chave','aliases'=>['chave']]
    ];
}

function tamanhoSequenciaMemoriaLider()
{
    $rodada=(int)($_SESSION['rodada']??1);
    if($rodada<=2) return 4;
    if($rodada<=5) return 5;
    return 6;
}

function gerarSequenciaMemoriaLider()
{
    $banco=bancoSimbolosMemoriaLider();
    shuffle($banco);
    return array_values(array_slice($banco,0,min(tamanhoSequenciaMemoriaLider(),count($banco))));
}

function prepararMemoriaLider()
{
    $rodada=(int)($_SESSION['rodada']??1);
    if(!isset($_SESSION['lider_memoria_rodada']) || (int)$_SESSION['lider_memoria_rodada']!==$rodada || empty($_SESSION['lider_memoria_sequencia'])){
        $_SESSION['lider_memoria_rodada']=$rodada;
        $_SESSION['lider_memoria_sequencia']=gerarSequenciaMemoriaLider();
    }
    return $_SESSION['lider_memoria_sequencia'];
}

function normalizarRespostaMemoriaLider($texto)
{
    $texto=mb_strtolower(trim((string)$texto),'UTF-8');
    if(function_exists('iconv')){
        $ascii=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$texto);
        if($ascii!==false) $texto=$ascii;
    }
    return preg_replace('/[^a-z0-9]+/i','',$texto);
}

function respostaMemoriaLiderCorreta($resposta,$simbolo)
{
    $r=normalizarRespostaMemoriaLider($resposta);
    $aliases=$simbolo['aliases']??[];
    $aliases[]=$simbolo['nome']??'';
    foreach($aliases as $alias){
        if($r===normalizarRespostaMemoriaLider($alias)) return true;
    }
    return false;
}

function avaliarMemoriaLider($post)
{
    $seq=$_SESSION['lider_memoria_sequencia']??[];
    $respostas=$post['memoria']??[];
    if(!is_array($respostas)) $respostas=[];
    $acertos=0;
    foreach($seq as $i=>$simbolo){
        if(respostaMemoriaLiderCorreta($respostas[$i]??'',$simbolo)) $acertos++;
    }
    return ['acertos'=>$acertos,'total'=>count($seq)];
}

function chanceMemoriaNPC($jogador)
{
    $base=68;
    $p=$jogador['personalidade']??'Neutro';
    $bonus=['Estrategista'=>10,'Líder Nato'=>8,'Manipulador'=>5,'Influencer'=>2,'Neutro'=>0,'Fofo'=>0,'Emocional'=>-2,'Falso'=>-2,'Planta'=>-4,'Barraqueiro'=>-6,'Explosivo'=>-7];
    return max(45,min(88,$base+($bonus[$p]??0)));
}

function pontuacaoMemoriaNPC($jogador,$total)
{
    $chance=chanceMemoriaNPC($jogador); $acertos=0;
    for($i=0;$i<$total;$i++){ if(rand(1,100)<=$chance) $acertos++; }
    return $acertos;
}

function resolverMemoriaLider($jogadores,$meuNome,$post)
{
    $r=avaliarMemoriaLider($post); $total=(int)$r['total'];
    $ranking=[['nome'=>$meuNome,'acertos'=>(int)$r['acertos'],'desempate'=>rand(500,900)]];
    foreach($jogadores as $j){
        $nome=$j['nome']??'';
        if($nome===''||nomeIgual($nome,$meuNome)) continue;
        $ranking[]=['nome'=>$nome,'acertos'=>pontuacaoMemoriaNPC($j,$total),'desempate'=>rand(450,1050)];
    }
    usort($ranking,function($a,$b){ return $a['acertos']!==$b['acertos'] ? $b['acertos']<=>$a['acertos'] : $a['desempate']<=>$b['desempate']; });
    $vencedor=$ranking[0]['nome']??$meuNome;
    $_SESSION['resultado_prova_lider_interativa']=['tipo'=>'memoria','acertos_jogador'=>(int)$r['acertos'],'total'=>$total,'ranking'=>array_slice($ranking,0,5),'vencedor'=>$vencedor];
    return $vencedor;
}

/* =========================================================
   ⚡ REFLEXO BBB
   ========================================================= */
function normalizarTempoReflexoLider($tempo)
{
    $tempo=(int)$tempo;
    return ($tempo<100||$tempo>5000)?5000:$tempo;
}

function tempoReflexoNPC($jogador)
{
    $p=$jogador['personalidade']??'Neutro';
    $aj=['Explosivo'=>-35,'Líder Nato'=>-30,'Estrategista'=>-25,'Influencer'=>-15,'Barraqueiro'=>-10,'Manipulador'=>-5,'Neutro'=>0,'Emocional'=>10,'Falso'=>15,'Fofo'=>20,'Planta'=>35];
    return max(250,rand(330,850)+($aj[$p]??0)+rand(-35,35));
}

function resolverReflexoLider($jogadores,$meuNome,$tempoJogador)
{
    $tempoJogador=normalizarTempoReflexoLider($tempoJogador);
    $ranking=[['nome'=>$meuNome,'tempo'=>$tempoJogador]];
    foreach($jogadores as $j){
        $nome=$j['nome']??'';
        if($nome===''||nomeIgual($nome,$meuNome)) continue;
        $ranking[]=['nome'=>$nome,'tempo'=>tempoReflexoNPC($j)];
    }
    usort($ranking,fn($a,$b)=>$a['tempo']<=>$b['tempo']);
    $vencedor=$ranking[0]['nome']??$meuNome;
    $_SESSION['resultado_prova_lider_interativa']=['tipo'=>'reflexo','tempo_jogador'=>$tempoJogador,'ranking'=>array_slice($ranking,0,5),'vencedor'=>$vencedor];
    return $vencedor;
}

/* =========================================================
   🔢 SEQUÊNCIA LÓGICA
   ========================================================= */
function bancoQuestoesLogicaLider()
{
    return [
        1 => [
            ['texto'=>'2 → 4 → 6 → 8 → ?','opcoes'=>[10,12,14,16],'correta'=>10],
            ['texto'=>'5 → 10 → 15 → 20 → ?','opcoes'=>[22,25,30,35],'correta'=>25],
            ['texto'=>'1 → 3 → 5 → 7 → ?','opcoes'=>[8,9,10,11],'correta'=>9],
            ['texto'=>'10 → 20 → 30 → 40 → ?','opcoes'=>[45,50,55,60],'correta'=>50]
        ],
        2 => [
            ['texto'=>'3 → 6 → 12 → 24 → ?','opcoes'=>[36,42,48,54],'correta'=>48],
            ['texto'=>'64 → 32 → 16 → 8 → ?','opcoes'=>[2,4,6,12],'correta'=>4],
            ['texto'=>'2 → 5 → 8 → 11 → ?','opcoes'=>[12,13,14,15],'correta'=>14],
            ['texto'=>'1 → 4 → 9 → 16 → ?','opcoes'=>[20,24,25,36],'correta'=>25]
        ],
        3 => [
            ['texto'=>'2 → 6 → 18 → 54 → ?','opcoes'=>[108,126,144,162],'correta'=>162],
            ['texto'=>'1 → 2 → 4 → 7 → 11 → ?','opcoes'=>[14,15,16,17],'correta'=>16],
            ['texto'=>'81 → 27 → 9 → 3 → ?','opcoes'=>[0,1,2,3],'correta'=>1],
            ['texto'=>'4 → 7 → 13 → 25 → ?','opcoes'=>[37,43,49,51],'correta'=>49]
        ]
    ];
}

function nivelLogicaLider()
{
    $rodada=(int)($_SESSION['rodada']??1);
    if($rodada<=2) return 1;
    if($rodada<=5) return 2;
    return 3;
}

function prepararSequenciaLogicaLider()
{
    $rodada=(int)($_SESSION['rodada']??1);
    if(!isset($_SESSION['lider_logica_rodada']) || (int)$_SESSION['lider_logica_rodada']!==$rodada || empty($_SESSION['lider_logica_questoes'])){
        $banco=bancoQuestoesLogicaLider();
        $nivel=nivelLogicaLider();
        $pool=$banco[$nivel]??$banco[1];
        shuffle($pool);
        $_SESSION['lider_logica_rodada']=$rodada;
        $_SESSION['lider_logica_questoes']=array_values(array_slice($pool,0,3));
    }
    return $_SESSION['lider_logica_questoes'];
}

function avaliarSequenciaLogicaLider($post)
{
    $questoes=$_SESSION['lider_logica_questoes']??[];
    $respostas=$post['logica']??[];
    if(!is_array($respostas)) $respostas=[];
    $acertos=0;
    foreach($questoes as $i=>$q){
        if((string)($respostas[$i]??'') === (string)($q['correta']??'')) $acertos++;
    }
    return ['acertos'=>$acertos,'total'=>count($questoes)];
}

function chanceLogicaNPC($jogador)
{
    $p=$jogador['personalidade']??'Neutro';
    $base=64;
    $bonus=['Estrategista'=>17,'Manipulador'=>10,'Líder Nato'=>9,'Falso'=>5,'Influencer'=>2,'Neutro'=>0,'Fofo'=>-1,'Emocional'=>-3,'Planta'=>-4,'Barraqueiro'=>-7,'Explosivo'=>-8];
    return max(38,min(90,$base+($bonus[$p]??0)));
}

function pontuacaoLogicaNPC($jogador,$total)
{
    $chance=chanceLogicaNPC($jogador); $acertos=0;
    for($i=0;$i<$total;$i++){ if(rand(1,100)<=$chance) $acertos++; }
    return $acertos;
}

function buscarJogadorProvaLider($jogadores,$nome)
{
    foreach($jogadores as $j){
        if(nomeIgual($j['nome']??'',$nome)){
            return $j;
        }
    }

    return null;
}

function prepararQuestaoDesempateLogicaLider()
{
    if(
        !empty($_SESSION['lider_logica_desempate_questao']) &&
        is_array($_SESSION['lider_logica_desempate_questao'])
    ){
        return $_SESSION['lider_logica_desempate_questao'];
    }

    $banco=bancoQuestoesLogicaLider();
    $nivel=nivelLogicaLider();
    $pool=$banco[$nivel]??$banco[1];
    $usadas=$_SESSION['lider_logica_questoes']??[];

    $textosUsados=[];
    foreach($usadas as $q){
        $textosUsados[]=(string)($q['texto']??'');
    }

    $disponiveis=array_values(array_filter(
        $pool,
        function($q) use ($textosUsados){
            return !in_array((string)($q['texto']??''),$textosUsados,true);
        }
    ));

    /*
     * Cada nível possui 4 questões e a prova normal usa 3,
     * então normalmente sobra exatamente uma para o desempate.
     */
    if(empty($disponiveis)){
        $disponiveis=$pool;
    }

    shuffle($disponiveis);

    $_SESSION['lider_logica_desempate_questao']=$disponiveis[0];

    return $_SESSION['lider_logica_desempate_questao'];
}

function resolverSequenciaLogicaLider($jogadores,$meuNome,$post)
{
    $r=avaliarSequenciaLogicaLider($post);
    $total=(int)$r['total'];

    $ranking=[
        [
            'nome'=>$meuNome,
            'acertos'=>(int)$r['acertos']
        ]
    ];

    foreach($jogadores as $j){
        $nome=$j['nome']??'';

        if($nome===''||nomeIgual($nome,$meuNome)){
            continue;
        }

        $ranking[]=[
            'nome'=>$nome,
            'acertos'=>pontuacaoLogicaNPC($j,$total)
        ];
    }

    usort(
        $ranking,
        function($a,$b){
            return $b['acertos']<=>$a['acertos'];
        }
    );

    $maiorPontuacao=(int)($ranking[0]['acertos']??0);

    $empatadosNoTopo=array_values(array_filter(
        $ranking,
        function($item) use ($maiorPontuacao){
            return (int)($item['acertos']??0)===$maiorPontuacao;
        }
    ));

    $jogadorNoTopo=false;

    foreach($empatadosNoTopo as $item){
        if(nomeIgual($item['nome']??'',$meuNome)){
            $jogadorNoTopo=true;
            break;
        }
    }

    /*
     * Se o jogador estiver empatado na MELHOR pontuação,
     * não existe mais sorteio escondido.
     * A prova abre uma 4ª questão de desempate.
     */
    if($jogadorNoTopo && count($empatadosNoTopo)>1){

        $npcsEmpatados=[];

        foreach($empatadosNoTopo as $item){
            $nome=$item['nome']??'';

            if($nome!==''&&!nomeIgual($nome,$meuNome)){
                $npcsEmpatados[]=$nome;
            }
        }

        $_SESSION['lider_logica_desempate_pendente']=true;
        $_SESSION['lider_logica_desempate_npcs']=$npcsEmpatados;
        $_SESSION['lider_logica_resultado_base']=[
            'acertos_jogador'=>(int)$r['acertos'],
            'total'=>$total,
            'ranking'=>array_slice($ranking,0,5)
        ];

        prepararQuestaoDesempateLogicaLider();

        return null;
    }

    $vencedor=$ranking[0]['nome']??$meuNome;

    $_SESSION['resultado_prova_lider_interativa']=[
        'tipo'=>'logica',
        'acertos_jogador'=>(int)$r['acertos'],
        'total'=>$total,
        'ranking'=>array_slice($ranking,0,5),
        'vencedor'=>$vencedor,
        'desempate'=>false
    ];

    return $vencedor;
}

function resolverDesempateLogicaLider(
    $jogadores,
    $meuNome,
    $resposta
){
    if(empty($_SESSION['lider_logica_desempate_pendente'])){
        return null;
    }

    $questao=$_SESSION['lider_logica_desempate_questao']??null;
    $npcs=$_SESSION['lider_logica_desempate_npcs']??[];
    $base=$_SESSION['lider_logica_resultado_base']??[];

    if(!$questao||!is_array($questao)){
        return null;
    }

    $acertouJogador=
        (string)$resposta===(string)($questao['correta']??'');

    /*
     * Desempate em morte súbita:
     * - acertou a 4ª questão → jogador leva a liderança;
     * - errou → um dos NPCs empatados leva a prova.
     *
     * Assim não existe outro desempate invisível depois
     * de o jogador acertar a questão extra.
     */
    if($acertouJogador){
        $vencedor=$meuNome;
    }else{
        $candidatos=[];

        foreach($npcs as $nomeNPC){
            $j=buscarJogadorProvaLider(
                $jogadores,
                $nomeNPC
            );

            $peso=50;

            if($j){
                $peso=chanceLogicaNPC($j);
            }

            $candidatos[]=[
                'nome'=>$nomeNPC,
                'peso'=>$peso+rand(0,20)
            ];
        }

        usort(
            $candidatos,
            fn($a,$b)=>$b['peso']<=>$a['peso']
        );

        $vencedor=
            $candidatos[0]['nome']
            ?? ($npcs[0]??$meuNome);
    }

    $_SESSION['resultado_prova_lider_interativa']=[
        'tipo'=>'logica',
        'acertos_jogador'=>(int)($base['acertos_jogador']??0),
        'total'=>(int)($base['total']??3),
        'ranking'=>$base['ranking']??[],
        'vencedor'=>$vencedor,
        'desempate'=>true,
        'desempate_acertou'=>$acertouJogador,
        'desempate_questao'=>$questao['texto']??''
    ];

    unset(
        $_SESSION['lider_logica_desempate_pendente'],
        $_SESSION['lider_logica_desempate_npcs'],
        $_SESSION['lider_logica_resultado_base'],
        $_SESSION['lider_logica_desempate_questao']
    );

    return $vencedor;
}

/* =========================================================
   🎯 CAMINHO ESTRATÉGICO
   ========================================================= */
function prepararCaminhoEstrategicoLider()
{
    $rodada=(int)($_SESSION['rodada']??1);
    if(!isset($_SESSION['lider_estrategia_rodada']) || (int)$_SESSION['lider_estrategia_rodada']!==$rodada){
        $_SESSION['lider_estrategia_rodada']=$rodada;
        $_SESSION['lider_estrategia_etapa']=1;
        $_SESSION['lider_estrategia_pontos']=50;
        $_SESSION['lider_estrategia_historico']=[];
        $_SESSION['lider_estrategia_feedback']='Você começa com 50 pontos. Escolha bem: decisões mais arriscadas podem render muito mais.';
    }
    return [
        'etapa'=>(int)($_SESSION['lider_estrategia_etapa']??1),
        'pontos'=>(int)($_SESSION['lider_estrategia_pontos']??50),
        'feedback'=>$_SESSION['lider_estrategia_feedback']??'',
        'historico'=>$_SESSION['lider_estrategia_historico']??[]
    ];
}

function opcoesCaminhoEstrategicoLider($etapa)
{
    $mapa=[
        1 => [
            'seguro'=>['emoji'=>'🛡️','titulo'=>'Rota segura','descricao'=>'Pouco risco e ganho garantido.','risco'=>'baixo'],
            'analise'=>['emoji'=>'🧠','titulo'=>'Observar antes','descricao'=>'Analisa o cenário e ganha uma vantagem moderada.','risco'=>'medio'],
            'atalho'=>['emoji'=>'⚡','titulo'=>'Pegar atalho','descricao'=>'Pode disparar na prova, mas há chance de perder pontos.','risco'=>'alto']
        ],
        2 => [
            'proteger'=>['emoji'=>'🛡️','titulo'=>'Proteger pontos','descricao'=>'Consolida sua pontuação atual.','risco'=>'baixo'],
            'bonus'=>['emoji'=>'💎','titulo'=>'Buscar bônus','descricao'=>'Tenta ampliar a vantagem com risco controlado.','risco'=>'medio'],
            'dobrar'=>['emoji'=>'🔥','titulo'=>'Dobrar a aposta','descricao'=>'Grande ganho possível, grande punição se der errado.','risco'=>'alto']
        ],
        3 => [
            'manter'=>['emoji'=>'🧠','titulo'=>'Manter estratégia','descricao'=>'Final seguro para preservar o que conquistou.','risco'=>'baixo'],
            'pressionar'=>['emoji'=>'🎯','titulo'=>'Pressionar na reta final','descricao'=>'Mais pontos com risco moderado.','risco'=>'medio'],
            'tudo'=>['emoji'=>'🔥','titulo'=>'Arriscar tudo','descricao'=>'Pode virar campeão da prova ou despencar no ranking.','risco'=>'alto']
        ]
    ];
    return $mapa[(int)$etapa]??[];
}

function aplicarEscolhaCaminhoEstrategicoLider($etapa,$escolha,$pontosAtuais)
{
    $delta=0; $texto='Escolha inválida.';

    if($etapa===1){
        if($escolha==='seguro'){ $delta=18; $texto='🛡️ Você seguiu a rota segura e ganhou 18 pontos.'; }
        elseif($escolha==='analise'){ $delta=rand(20,30); $texto="🧠 Sua leitura de jogo funcionou. Você ganhou {$delta} pontos."; }
        elseif($escolha==='atalho'){
            if(rand(1,100)<=55){ $delta=42; $texto='⚡ O atalho deu certo! Você ganhou 42 pontos.'; }
            else { $delta=-16; $texto='💥 O atalho deu errado e você perdeu 16 pontos.'; }
        }
    }

    if($etapa===2){
        if($escolha==='proteger'){ $delta=16; $texto='🛡️ Você protegeu sua vantagem e somou 16 pontos.'; }
        elseif($escolha==='bonus'){ $delta=rand(22,34); $texto="💎 Você encontrou um bônus e ganhou {$delta} pontos."; }
        elseif($escolha==='dobrar'){
            if(rand(1,100)<=50){ $delta=50; $texto='🔥 A aposta funcionou! Você ganhou 50 pontos.'; }
            else { $delta=-24; $texto='💥 A aposta falhou e você perdeu 24 pontos.'; }
        }
    }

    if($etapa===3){
        if($escolha==='manter'){ $delta=12; $texto='🧠 Você fechou a prova com segurança e ganhou 12 pontos.'; }
        elseif($escolha==='pressionar'){ $delta=rand(20,32); $texto="🎯 Você pressionou na hora certa e ganhou {$delta} pontos."; }
        elseif($escolha==='tudo'){
            if(rand(1,100)<=45){ $delta=62; $texto='🔥 DEU CERTO! O tudo ou nada rendeu 62 pontos.'; }
            else { $delta=-38; $texto='💥 O tudo ou nada deu errado e você perdeu 38 pontos.'; }
        }
    }

    $novo=max(0,$pontosAtuais+$delta);
    return ['pontos'=>$novo,'delta'=>$delta,'texto'=>$texto];
}

function pontuacaoEstrategiaNPC($jogador)
{
    $p=$jogador['personalidade']??'Neutro';
    $base=100;
    $ajuste=['Estrategista'=>22,'Manipulador'=>16,'Líder Nato'=>13,'Falso'=>8,'Influencer'=>4,'Neutro'=>0,'Fofo'=>-2,'Emocional'=>-4,'Planta'=>-7,'Barraqueiro'=>-6,'Explosivo'=>-10];
    $variacao=rand(-24,32);
    if($p==='Explosivo' || $p==='Barraqueiro') $variacao=rand(-38,48);
    return max(35,$base+($ajuste[$p]??0)+$variacao);
}

function finalizarCaminhoEstrategicoLider($jogadores,$meuNome,$pontosJogador)
{
    $ranking=[['nome'=>$meuNome,'pontos'=>(int)$pontosJogador,'desempate'=>rand(500,900)]];
    foreach($jogadores as $j){
        $nome=$j['nome']??'';
        if($nome===''||nomeIgual($nome,$meuNome)) continue;
        $ranking[]=['nome'=>$nome,'pontos'=>pontuacaoEstrategiaNPC($j),'desempate'=>rand(450,1050)];
    }
    usort($ranking,function($a,$b){ return $a['pontos']!==$b['pontos'] ? $b['pontos']<=>$a['pontos'] : $a['desempate']<=>$b['desempate']; });
    $vencedor=$ranking[0]['nome']??$meuNome;
    $_SESSION['resultado_prova_lider_interativa']=['tipo'=>'estrategia','pontos_jogador'=>(int)$pontosJogador,'ranking'=>array_slice($ranking,0,5),'vencedor'=>$vencedor];
    return $vencedor;
}

/* =========================================================
   📺 RESUMO DAS PROVAS INTERATIVAS
   ========================================================= */
function registrarResumoProvaLiderInterativa()
{
    $r=$_SESSION['resultado_prova_lider_interativa']??null;
    if(!$r||!is_array($r)) return;
    if(!isset($_SESSION['evento_extra'])||!is_array($_SESSION['evento_extra'])) $_SESSION['evento_extra']=[];

    $tipo=$r['tipo']??'';
    if($tipo==='memoria'){
        $_SESSION['evento_extra'][]="🧠 Na Memória da Casa, você acertou <b>".(int)($r['acertos_jogador']??0)."/".(int)($r['total']??0)."</b> símbolos.";
    } elseif($tipo==='reflexo'){
        $t=(int)($r['tempo_jogador']??5000);
        $_SESSION['evento_extra'][]=$t>=5000?"⚡ Seu tempo na Prova de Reflexo não foi registrado corretamente.":"⚡ Seu tempo na Prova de Reflexo foi de <b>{$t} ms</b>.";
    } elseif($tipo==='logica'){
        $_SESSION['evento_extra'][]="🔢 Na Sequência Lógica, você acertou <b>".(int)($r['acertos_jogador']??0)."/".(int)($r['total']??0)."</b> questões.";

        if(!empty($r['desempate'])){
            $_SESSION['evento_extra'][]=
                !empty($r['desempate_acertou'])
                ? "⚡ Houve empate no topo e você <b>acertou a questão extra</b> do desempate."
                : "⚡ Houve empate no topo, mas você <b>errou a questão extra</b> do desempate.";
        }
    } elseif($tipo==='estrategia'){
        $_SESSION['evento_extra'][]="🎯 No Caminho Estratégico, você terminou com <b>".(int)($r['pontos_jogador']??0)." pontos</b>.";
    }
}

/* =========================================================
   🎲 PREPARAR PROVA DA RODADA
   ========================================================= */
function prepararProvaLider()
{
    $provas=obterProvasLider();
    if(!isset($_SESSION['prova_tipo'])) $_SESSION['prova_tipo']=rand(1,count($provas));
    $tipo=(int)$_SESSION['prova_tipo'];
    if(!isset($provas[$tipo])){ $tipo=1; $_SESSION['prova_tipo']=1; }
    if($tipo===3) prepararMemoriaLider();
    if($tipo===12) prepararSequenciaLogicaLider();
    if($tipo===13) prepararCaminhoEstrategicoLider();
    return ['tipo'=>$tipo,'prova'=>$provas[$tipo]];
}

/* =========================================================
   PROVAS TRADICIONAIS
   ========================================================= */
function nomesNPCsProvaLider($jogadores,$meuNome)
{
    $npcs=[];
    foreach($jogadores as $j){
        $nome=$j['nome']??'';
        if($nome!==''&&!nomeIgual($nome,$meuNome)) $npcs[]=$nome;
    }
    return $npcs;
}

function sortearNPCVencedorLider($jogadores,$meuNome)
{
    $npcs=nomesNPCsProvaLider($jogadores,$meuNome);
    if(empty($npcs)) return $meuNome;
    return $npcs[array_rand($npcs)];
}

function disputaPorSorteLider($jogadores,$meuNome,$escolha,$maximo)
{
    $segredo=rand(1,(int)$maximo);
    return (int)$escolha===$segredo ? $meuNome : sortearNPCVencedorLider($jogadores,$meuNome);
}

function resolverMiraDoLider($jogadores,$meuNome,$escolha)
{
    $alvo=rand(1,5); $dj=abs($alvo-(int)$escolha); $melhor=99; $melhorNPC='';
    foreach($jogadores as $j){
        $nome=$j['nome']??'';
        if($nome===''||nomeIgual($nome,$meuNome)) continue;
        $d=abs($alvo-rand(1,5));
        if($d<$melhor){ $melhor=$d; $melhorNPC=$nome; }
    }
    if($dj<=$melhor) return $meuNome;
    return $melhorNPC!==''?$melhorNPC:sortearNPCVencedorLider($jogadores,$meuNome);
}

function resolverProvaLider($jogadores,$meuNome,$tipoProva,$escolha,$prova)
{
    if((int)$tipoProva===1) return resolverMiraDoLider($jogadores,$meuNome,$escolha);
    if($tipoProva>=4&&$tipoProva<=11) return disputaPorSorteLider($jogadores,$meuNome,$escolha,$prova['max']??3);
    return sortearNPCVencedorLider($jogadores,$meuNome);
}

/* =========================================================
   👑 REGISTRAR NOVO LÍDER
   ========================================================= */
function registrarLiderDaRodada(&$jogadores,$lider,$meuNome)
{
    $_SESSION['lider']=$lider;
    foreach($jogadores as &$j){
        if(!isset($j['status'])) $j['status']=[];
        $j['status']['lider']=false;
        if(nomeIgual($j['nome']??'',$lider)){
            $j['status']['lider']=true;
            if(!isset($j['estatisticas'])||!is_array($j['estatisticas'])) $j['estatisticas']=[];
            $j['estatisticas']['lider']=($j['estatisticas']['lider']??0)+1;
        }
    }
    unset($j);
    $_SESSION['jogadores']=array_values($jogadores);
    $_SESSION['mensagem_lider']=nomeIgual($lider,$meuNome)?"🏆 Você venceu a Prova do Líder!":"👑 $lider venceu a Prova do Líder!";

    unset(
        $_SESSION['prova_tipo'],
        $_SESSION['lider_memoria_rodada'],
        $_SESSION['lider_memoria_sequencia'],
        $_SESSION['lider_logica_rodada'],
        $_SESSION['lider_logica_questoes'],
        $_SESSION['lider_logica_desempate_pendente'],
        $_SESSION['lider_logica_desempate_npcs'],
        $_SESSION['lider_logica_resultado_base'],
        $_SESSION['lider_logica_desempate_questao'],
        $_SESSION['lider_estrategia_rodada'],
        $_SESSION['lider_estrategia_etapa'],
        $_SESSION['lider_estrategia_pontos'],
        $_SESSION['lider_estrategia_historico'],
        $_SESSION['lider_estrategia_feedback']
    );

    if(!isset($_SESSION['evento_extra'])||!is_array($_SESSION['evento_extra'])) $_SESSION['evento_extra']=[];
    $_SESSION['evento_extra'][]="👑 $lider venceu a Prova do Líder.";
    $_SESSION['evento_extra'][]='🗣️ "Parabéns! O reinado começou."';
}

function textoBotaoProvaLider($tipo,$i)
{
    if($tipo==='alvo') return "🎯 Alvo $i";
    if($tipo==='cofre') return "💎 Cofre $i";
    if($tipo==='nave'){ $l=['A','B','C','D','E']; return "🚀 Nave ".($l[$i-1]??$i); }
    if($tipo==='simbolo'){ $s=['🍒','⭐','💎','🎯']; return $s[$i-1]??"Símbolo $i"; }
    if($tipo==='caixa') return "📦 Caixa $i";
    if($tipo==='cor'){ $c=['Rosa','Azul','Dourado','Roxo']; return "🌪️ ".($c[$i-1]??"Cor $i"); }
    if($tipo==='totem') return "🔥 Totem $i";
    return (string)$i;
}
