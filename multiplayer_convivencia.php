<?php
/** BBB Simulator Multiplayer Beta 7 — Convivência e semana (sem afetar o modo solo). */
declare(strict_types=1);

function mp7_emojis(): array {
    return [
        '❤️'=>'Amor / Afinidade', '😄'=>'Simpatia', '🤝'=>'Confiança',
        '🔥'=>'Treta', '😴'=>'Planta', '🐍'=>'Falsidade', '🎯'=>'Alvo',
        '💔'=>'Decepção', '🤮'=>'Ranço', '🙄'=>'Forçado', '😡'=>'Explosivo',
    ];
}
function mp7_festa_opcoes(): array {
    return [
        'aproximar'=>['🤝 Se aproximar','Converse com alguém na festa','festa_aproximar',true],
        'vt'=>['📺 Fazer VT','Crie um momento marcante no Ao Vivo','',false],
        'romance'=>['💘 Romance','Convide alguém para uma aproximação romântica','festa_romance',true],
        'provocar'=>['😈 Provocar','Provoque alguém, que poderá rebater','festa_provocar',true],
        'sondar'=>['🕵️ Sondar intenção de voto','Pergunte com discrição: ninguém é obrigado a revelar','festa_sondar',true],
        'negociar'=>['🗳️ Articular votos','Convide até três pessoas para considerar um alvo','festa_negociar',true],
        'dancar'=>['💃 Dançar','Convide alguém para dançar na festa','festa_dancar',true],
        'beber'=>['🍹 Exagerar na bebida','Uma escolha de risco que pode virar assunto na casa','',false],
    ];
}
function mp7_temas_discordia(): array {
    return [
        ['🎭 Jogo Duplo','Quem muda de lado conforme a conveniência?', 'jogo'],
        ['🧩 Convivência','Quem mais precisa rever suas atitudes na casa?', 'convivencia'],
        ['🤝 Confiança','Quem quebrou sua confiança nesta semana?', 'confianca'],
        ['🎬 O Filme da Semana','Quem seria o vilão da história desta semana?', 'filme'],
        ['🎯 Prioridades','Quem está escondendo o próprio jogo?', 'prioridades'],
    ];
}
function mp7_discordia_tema(array $room): array {
    $list=mp7_temas_discordia();
    return $list[((int)$room['week']-1)%count($list)];
}
function mp7_argumentos(): array {return [
    'jogo'=>'🎯 Suas atitudes no jogo me deixaram em dúvida',
    'convivencia'=>'🏠 Algumas situações de convivência me incomodaram',
    'confianca'=>'🤝 Senti falta de confiança nas nossas conversas',
    'estrategia'=>'🧠 Enxergo suas decisões como estratégicas demais',
];}
function mp7_respostas(): array {return [
    'explicar'=>'🎙️ Explicar meu ponto de vista',
    'rebater'=>'🔥 Discordar e rebater',
    'assumir'=>'🤍 Reconhecer parte da crítica',
];}
function mp7_treplicas(): array {return [
    'manter'=>'🔥 Manter meu posicionamento',
    'reconsiderar'=>'🤝 Reconhecer a resposta',
    'encerrar'=>'🎤 Encerrar o debate sem acrescentar',
];}
function mp7_festa_local(string $type): string {return match($type){
    'vt'=>'na sala','beber'=>'na área externa','dancar'=>'na pista de dança',
    'romance'=>'no jardim','provocar'=>'na cozinha','sondar','negociar'=>'no lounge',default=>'perto da piscina',
};}
function mp7_convivencia_evento(array &$room,string $phase): void {
    $lines=[
        '🍳 Na cozinha, a produção deixou ingredientes para um almoço coletivo. Os participantes podem aproveitar a ocasião para se aproximar.',
        '🏊 A piscina está aberta e a área externa virou um ponto de encontro da casa. As conversas podem mudar o clima da semana.',
        '🎲 Uma noite de jogos movimenta a sala. O grupo pode aproveitar para aliviar tensões ou observar novas afinidades.',
        '☀️ A produção anuncia uma manhã livre no jardim. É uma oportunidade para rever estratégias e recuperar energias.',
        '🎧 Música ambiente e luzes na varanda marcam mais um período de convivência dentro da casa.',
        '🍿 Na sala, a produção exibe lembranças da semana. Cada participante poderá reagir nas próximas interações.',
    ];
    $idx=(($room['week']*7)+array_search($phase,array_keys(mp_phases()),true))%count($lines);
    mp_log($room,$lines[$idx],'convivencia',['title'=>'🏠 Rotina da Casa','place'=>'casa']);
}
function mp7_concluir_queridometro(array &$room): void {
    $received=[];$valid=mp7_emojis();$total=0;
    foreach(mp_active($room) as $from){
        foreach(($from['queridometro']??[]) as $target=>$emoji){
            $target=(string)$target; // IDs numéricos em chaves de arrays PHP podem virar inteiros.
            if(!isset($valid[$emoji])||$target===$from['id']||!mp_find_player($room,$target))continue;
            $received[$target][$emoji]=($received[$target][$emoji]??0)+1;$total++;
            $key=mp_relation_key($from['id'],$target);
            $effect=match($emoji){'❤️','🤝'=>3,'😄'=>2,'🔥','😴','🙄'=>-1,'🐍','💔','🎯'=>-3,'🤮'=>-4,'😡'=>-2,default=>0};
            $room['relations'][$key]=max(-100,min(100,(int)($room['relations'][$key]??0)+$effect));
        }
    }
    $room['lastQueridometro']=['week'=>$room['week'],'received'=>$received,'total'=>$total];
    mp_log($room,'💟 O Queridômetro terminou com '.$total.' emojis enviados. Cada participante pode conferir os emojis recebidos. Os autores não são revelados.','queridometro',['title'=>'💟 Resultado do Queridômetro']);
}
function mp7_festa_concluir(array &$room): void {
    $used=0;foreach(mp_active($room) as $p)$used+=count($p['actions']);
    mp_log($room,'🎉 A festa terminou com '.$used.' ações registradas entre os participantes. Os convites sem resposta foram encerrados.','festa',['title'=>'🎉 Fim da Festa']);
}
function mp7_discordia_concluir(array &$room): void {
    $unanswered=0;
    $room['debates']??=[];foreach($room['debates'] as &$d){if(($d['status']??'')!=='concluido'){$unanswered++;$d['status']='encerrado';}}unset($d);
    mp_log($room,'🔥 O Jogo da Discórdia foi encerrado. '.($unanswered?'Alguns debates ficaram sem resposta no tempo disponível.':'Todos os debates registrados foram concluídos.'),'discordia',['title'=>'🎙️ Encerramento da Discórdia']);
}
