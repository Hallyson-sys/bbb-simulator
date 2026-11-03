<?php
/**
 * Interações 4.0 — Conversas Vivas & Estratégia Social.
 * Nenhuma escolha é executada automaticamente pelo protagonista.
 * Os convites são privados e NPCs podem recusar compromissos.
 * Saves antigos não precisam de migração: chaves novas são opcionais.
 */

function i4Esc($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function i4Semana(): int { return max(1,(int)($_SESSION['rodada']??1)); }
function i4Catalogo(): array {
    return [
        'i4_conversar'=>['💬','Conversar de verdade','Escolha um assunto e uma abordagem para iniciar uma conversa.','social'],
        'i4_pessoal'=>['💜','Ter uma conversa pessoal','Falar de sonhos, saudade e sentimentos sem tratar tudo como estratégia.','social'],
        'i4_romance'=>['❤️','Romance e relacionamento','Flertar, conversar sobre sentimentos ou cuidar de um namoro já existente.','social'],
        'i4_atividade'=>['🏠','Convidar para uma atividade','Compartilhar um momento na cozinha, piscina, academia ou jardim.','social'],
        'i4_sondar'=>['👀','Sondar o jogo','Perguntar como alguém está enxergando a casa, sem garantir respostas sinceras.','estrategia'],
        'i4_protecao'=>['🛡️','Pedir proteção','Negociar uma promessa de proteção, sujeita à vontade do NPC.','estrategia'],
        'i4_acordo'=>['🎯','Propor acordo individual','Tentar firmar um acordo pessoal sem criar uma aliança oficial.','estrategia'],
        'i4_informacao'=>['🤫','Compartilhar uma informação','Contar a alguém um fato que você realmente descobriu.','estrategia'],
        'i4_reconciliar'=>['🤝','Resolver desentendimento','Ouvir, justificar-se ou pedir desculpas após atritos.','conflitos'],
        'i4_satisfacao'=>['🔥','Tirar satisfação','Questionar uma atitude e exigir uma explicação sem brigar automaticamente.','conflitos'],
        'i4_mediar'=>['🧩','Mediar duas pessoas','Tentar diminuir uma tensão real entre dois participantes.','conflitos'],
    ];
}
/** O estado oficial do namoro prevalece sobre a pontuação de romance.
 * Compatível com saves antigos que registrem o casal apenas em um dos sentidos.
 */
function i4EtapaRomance(array $jogadores, string $meuNome, string $npc): string {
    $pares=(array)($_SESSION['casais']??[]);
    $meuPar=(string)($pares[$meuNome]??'');
    $parNpc=(string)($pares[$npc]??'');
    if (($meuPar===$npc || $parNpc===$meuNome) &&
        !($meuPar!=='' && $meuPar!==$npc) && !($parNpc!=='' && $parNpc!==$meuNome)) return 'namoro';
    if ($meuPar!=='' || $parNpc!=='') return 'comprometido';
    $romance=max((int)(function_exists('obterRomance')?obterRomance($jogadores,$meuNome,$npc):0),
                 (int)(function_exists('obterRomance')?obterRomance($jogadores,$npc,$meuNome):0));
    return $romance>=30 ? 'aproximacao' : 'inicio';
}
function i4TituloRomance(string $etapa): string {
    return match($etapa) {
        'namoro'=>'Cuidar do namoro',
        'comprometido'=>'Conversar sobre limites',
        'aproximacao'=>'Aprofundar a aproximação',
        default=>'Demonstrar interesse romântico',
    };
}
function i4AssuntosRomance(string $etapa): array {
    return match($etapa) {
        'namoro'=>['carinho'=>'❤️ Demonstrar carinho','apoio'=>'🤝 Cuidar um do outro','relacao'=>'💬 Conversar sobre o namoro'],
        'comprometido'=>['limites'=>'🤝 Respeitar os limites','convivencia'=>'💬 Conversar como amigos','encerrar'=>'🧊 Deixar o assunto de lado'],
        'aproximacao'=>['sentimentos'=>'💗 Conversar sobre o que sentem','encontro'=>'🌙 Ter um momento a sós','flertar'=>'😉 Flertar com carinho'],
        default=>['flertar'=>'😉 Flertar','sentimentos'=>'💗 Falar do que sente','encontro'=>'🌙 Conversar a sós'],
    };
}
function i4Assuntos(string $acao, string $etapa=''): array {
    if ($acao==='i4_conversar') return ['jogo'=>'🎯 Rumos do jogo','relacao'=>'🤝 Nossa relação','convivencia'=>'🏠 Convivência'];
    if ($acao==='i4_pessoal') return ['saudade'=>'🏡 Saudade de casa','sonhos'=>'✨ Planos e sonhos','sentimentos'=>'💭 Como está se sentindo'];
    if ($acao==='i4_atividade') return ['cozinha'=>'🍳 Cozinhar juntos','piscina'=>'🏊 Piscina','academia'=>'🏋️ Treinar','jardim'=>'🌿 Caminhar pelo jardim'];
    if ($acao==='i4_romance') return i4AssuntosRomance($etapa);
    if ($acao==='i4_sondar') return ['clima'=>'🌡️ Clima da casa','voto'=>'🗳️ Próximo Paredão','aliancas'=>'🤝 Alianças'];
    if ($acao==='i4_informacao') return ['contar'=>'🤫 Compartilhar em particular'];
    if ($acao==='i4_mediar') return ['mediar'=>'🧩 Facilitar uma conversa'];
    return ['conversa'=>'💬 Conversar em particular'];
}
function i4Abordagens(): array { return ['sincera'=>'❤️ Sincera','cautelosa'=>'🧠 Cautelosa','leve'=>'🙂 Leve e descontraída']; }
function i4Ativos(array $jogadores): array {
    $out=[]; $falso=!empty($_SESSION['paredao_falso_ativo'])?(string)($_SESSION['falso_eliminado']??''):'';
    foreach($jogadores as $j){$nome=(string)($j['nome']??'');if($nome!==''&&empty($j['eliminado'])&&empty($j['fora_da_casa'])&&$nome!==$falso)$out[$nome]=$j;}
    return $out;
}
function i4Disponivel(array $jogadores,string $meuNome,string $fase): bool {
    return str_starts_with($fase,'interacoes') && (int)($_SESSION['acoes_restantes']??0)>0 && empty($_SESSION['modo_espectador'])
        && isset(i4Ativos($jogadores)[$meuNome]) && empty($_SESSION['dj_ativo']) && empty($_SESSION['dj_resultado'])
        && empty($_SESSION['evento_convivencia_ativo']) && empty($_SESSION['evento_convivencia_resultado']);
}
function i4Usada(string $alvo): bool {return !empty($_SESSION['i4_usos'][i4Semana().'|'.$alvo]);}
function i4Conhecimentos(): array {
    $ret=[];
    foreach((array)($_SESSION['conhecimentos_jogador']??[]) as $c){
        if (!is_array($c)||!empty($c['expirado'])||((int)($c['expira_rodada']??i4Semana()))<i4Semana())continue;
        $texto=trim((string)($c['texto']??'')); if($texto==='') continue;
        $id=(string)($c['chave']??hash('sha256',$texto)); $ret[$id]=$c;
    }
    return $ret;
}
function i4Relacao(array $jogadores,string $npc,string $meuNome): array {
    $p=i4Ativos($jogadores)[$npc]??[];
    $r=(array)($p['relacoes'][$meuNome]??[]);
    $score=(int)($_SESSION['relacoes_jogador'][$npc]??0);
    $conf=(int)($r['confianca']??0);$amizade=(int)($r['amizade']??0);$rival=(int)($r['rivalidade']??0);
    $forca=$score+round(($amizade+$conf-$rival*1.2)/3);
    if(function_exists('mesmaAliancaNomes') && mesmaAliancaNomes($jogadores,$npc,$meuNome))$forca+=13;
    $tracos=function_exists('e3Tracos')?e3Tracos($npc):['sociabilidade'=>50,'lealdade'=>50,'reatividade'=>50,'estrategia'=>50];
    $forca+=round(((int)($tracos['sociabilidade']??50)-50)/4);
    $memP=function_exists('impactoPositivoMemoriaNPC')?(int)impactoPositivoMemoriaNPC($npc,$meuNome):0;
    $memN=function_exists('impactoNegativoMemoriaNPC')?(int)impactoNegativoMemoriaNPC($npc,$meuNome):0;
    $forca+=max(-15,min(15,round(($memP-$memN)/8)));
    return ['forca'=>(int)$forca,'rivalidade'=>$rival,'amizade'=>$amizade,'confianca'=>$conf,'tracos'=>$tracos,'romance'=>function_exists('obterRomance')?(int)obterRomance($jogadores,$npc,$meuNome):0];
}
/** Escolhe uma variante na criação da cena e impede repetições imediatas. */
function i4FalaVariada(string $chave,array $frases): string {
    if(!$frases)return '';
    $anteriores=(array)($_SESSION['i4_falas_recentes'][$chave]??[]);
    $opcoes=array_values(array_diff($frases,$anteriores));
    $escolhida=($opcoes?:$frases)[array_rand($opcoes?:$frases)];
    $_SESSION['i4_falas_recentes'][$chave]=array_slice(array_merge($anteriores,[$escolhida]),-2);
    return $escolhida;
}
/** Apenas uma opinião realmente sustentada por relação negativa pode ser mencionada. */
function i4PreocupacaoReal(array $jogadores,string $npc,string $meuNome): string {
    $ativos=i4Ativos($jogadores); $proprio=$ativos[$npc]??null;if(!$proprio)return '';
    $ranking=[];
    foreach((array)($proprio['relacoes']??[]) as $alvo=>$rel){
        if($alvo===$npc||$alvo===$meuNome||!isset($ativos[$alvo])||!is_array($rel))continue;
        $nivel=(int)($rel['rivalidade']??0)-(int)($rel['amizade']??0)/2-(int)($rel['confianca']??0)/3;
        if($nivel>=13)$ranking[$alvo]=$nivel;
    }
    arsort($ranking);
    return (string)(array_key_first($ranking)??'');
}

function i4Frase(array $cena,array $jogadores,string $meuNome): string {
    $npc=$cena['alvo'];$rel=i4Relacao($jogadores,$npc,$meuNome);$a=$cena['acao'];$t=$cena['assunto'];$f=$rel['forca'];
    $abertura=$f>=35?'“Eu gosto de conversar com você. Pode falar.”':($f<=-20?'“Vou ouvir, mas ainda estou com o pé atrás.”':'“Pode falar, tô te ouvindo.”');
    if($a==='i4_pessoal'){
        $map=['saudade'=>'“Tem horas que a saudade de casa bate forte. Aqui cada dia parece uma semana.”','sonhos'=>'“Eu penso no que quero construir quando essa experiência acabar. Não é só sobre o prêmio.”','sentimentos'=>'“Algumas coisas são difíceis de explicar aqui dentro. Eu tento não guardar tudo.”'];
        $bases=[$map[$t]??$abertura];
        if($t==='saudade')$bases=array_merge($bases,['“Às vezes parece que a casa inteira sente falta de alguma coisa lá fora.”','“Tem dia que a gente dá risada aqui, e tem dia que a saudade aperta.”']);
        if($t==='sonhos')$bases=array_merge($bases,['“Meu sonho é sair daqui sabendo que tive coragem de ser eu mesmo.”','“O prêmio é importante, mas eu tenho planos que não cabem só nele.”']);
        if($t==='sentimentos')$bases=array_merge($bases,['“Estou tentando me entender no meio de tanta gente diferente.”','“Às vezes queria só um momento de silêncio pra organizar a cabeça.”']);
        return i4FalaVariada('pessoal|'.$t,$bases).' '.($f<=-20?'“Mas não sei se consigo me abrir tanto com você agora.”':'“E você, como tá vivendo isso?”');
    }
    if($a==='i4_conversar'){
        $map=['jogo'=>'“O jogo mudou bastante. Tem gente se aproximando e gente se afastando.”','relacao'=>'“A nossa convivência também mudou desde o começo. Eu percebi.”','convivencia'=>'“Nessa casa, uma conversa pequena muda o clima do dia inteiro.”'];
        $bases=[$map[$t]??$abertura];
        if($t==='jogo')$bases=array_merge($bases,['“A cada prova eu sinto que as prioridades de todo mundo mudam.”','“Uma coisa é conversar aqui, outra é ter coragem na votação.”']);
        if($t==='relacao'){
            if(i4EtapaRomance($jogadores,$meuNome,$npc)==='namoro')
                $bases=['“Eu gosto do que a gente construiu, mas sei que jogar e namorar na mesma casa não é simples.”','“Quero que a gente continue se ouvindo, mesmo quando discordar sobre o jogo.”','“A gente está junto. Não quero que a correria da semana faça a gente se esquecer disso.”'];
            else $bases=array_merge($bases,['“Nem sempre sei separar o que é jogo do que a gente vive como amizade.”','“O jeito que a gente se trata no dia a dia diz muito.”']);
        }
        if($t==='convivencia')$bases=array_merge($bases,['“Eu queria que todo mundo tentasse ouvir mais antes de tirar conclusão.”','“A convivência cansa, mas também aproxima pessoas inesperadas.”']);
        return i4FalaVariada('conversa|'.$t,$bases).' '.$abertura;
    }
    if($a==='i4_sondar')return $f<0?'“Você quer saber demais sobre meu jogo. Prefiro guardar minhas prioridades por enquanto.”':'“Eu tenho minhas impressões, mas não dá pra ter certeza do que os outros vão fazer.”';
    if($a==='i4_protecao')return $f>=30?'“Eu me importo com você, mas preciso entender até onde posso me comprometer.”':'“Proteção é uma palavra grande aqui dentro. O que você espera de mim?”';
    if($a==='i4_acordo')return $f>=20?'“Talvez a gente possa combinar alguma coisa. Mas não quero ficar preso a uma promessa.”':'“Um acordo agora? Preciso confiar mais antes de dizer sim.”';
    if($a==='i4_reconciliar')return $rel['rivalidade']>=20||$f<=0?'“O que aconteceu entre a gente me incomodou. Não vai sumir só porque estamos conversando.”':'“Acho bom a gente conversar antes que um mal-entendido vire algo maior.”';
    if($a==='i4_satisfacao')return $f<=0?'“Se quer uma explicação, me escuta também. Eu tenho meu lado nessa história.”':'“Eu não queria que você ficasse com isso guardado. Podemos conversar.”';
    if($a==='i4_romance'){
        $etapa=i4EtapaRomance($jogadores,$meuNome,$npc);
        if($etapa==='namoro'){
            $tenso=$rel['rivalidade']>=30 || $rel['confianca']<12 || $f<0;
            if($t==='carinho') return i4FalaVariada('casal-carinho|'.$npc,
                $tenso?['“Eu gosto de você, mas a gente anda meio distante. Queria que conversássemos sobre isso.”','“Eu também sinto falta dos nossos momentos, só que tem umas coisas me incomodando.”']:
                       ['“Com tanta coisa acontecendo nessa casa, é bom ter um momento só nosso.”','“Eu adoro quando a gente consegue ficar juntinho sem falar de votação.”','“Você sabe que eu gosto da gente, né? Vem cá, me conta como foi seu dia.”']);
            if($t==='apoio') return i4FalaVariada('casal-apoio|'.$npc,
                ['“Tem dias em que tudo aqui pesa. Saber que a gente pode se apoiar faz diferença.”','“Quero que a gente consiga se cuidar, mesmo quando o jogo aperta.”','“Hoje eu precisava de uma conversa tranquila com você. Como você está?”']);
            return i4FalaVariada('casal-relacao|'.$npc,
                $tenso?['“A gente está namorando, mas acho que precisamos falar sobre o que está incomodando.”','“Eu quero continuar com você. Só não quero fingir que nossas diferenças não existem.”']:
                       ['“Eu gosto muito de estar com você. Só quero que a gente não deixe o jogo falar mais alto que nós.”','“Já somos um casal, mas ainda tem muita coisa pra gente descobrir juntos aqui dentro.”','“Quero entender como você está vivendo nosso namoro no meio de tudo isso.”']);
        }
        if($etapa==='comprometido')return '“Eu prefiro respeitar os relacionamentos que já existem aqui. A gente pode conversar, mas sem criar expectativas românticas.”';
        if($etapa==='aproximacao')return i4FalaVariada('romance-aprox|'.$npc,
            ['“Eu também sinto que tem alguma coisa acontecendo entre a gente. Só não quero apressar os sentimentos.”','“Gosto desses momentos com você. Será que a gente está se aproximando de verdade?”','“A gente já tem uma conexão, mas ainda quero descobrir o que isso significa.”']);
        return $f>=10?'“Você me pegou de surpresa. Não sei onde isso vai dar, mas quero te ouvir.”':'“Eu não estava esperando esse assunto. Pode falar, mas sem pressão.”';
    }
    if($a==='i4_atividade')return '“Acho que sair um pouco das conversas de jogo pode fazer bem. Que tal?”';
    if($a==='i4_informacao')return '“Você tem certeza de que quer me contar isso? Não quero que depois ache que eu inventei alguma coisa.”';
    if($a==='i4_mediar')return '“Tem um clima estranho mesmo. Você acha que essa conversa vai ajudar?”';
    return $abertura;
}
function i4Opcoes(string $a, string $etapa=''): array {
    $op=[
        'i4_conversar'=>[['❤️ Abrir o jogo e ser sincero(a)',6,6,'me_aproximou'],['🧠 Ouvir e não revelar tudo',2,2,'me_aproximou'],['😏 Questionar a resposta dele(a)',-3,-3,'me_confrontou']],
        'i4_pessoal'=>[['💜 Compartilhar algo pessoal',8,5,'me_aproximou'],['🤝 Ouvir e oferecer apoio',7,6,'me_apoiou'],['🙂 Encerrar com leveza',2,1,'me_aproximou']],
        'i4_protecao'=>[['🛡️ Pedir compromisso de proteção mútua',4,5,'fechou_pacto_comigo'],['🎯 Pedir que evite votar em você',2,3,'fechou_pacto_comigo'],['🧊 Não pressionar e deixar em aberto',1,2,'me_aproximou']],
        'i4_reconciliar'=>[['🙏 Pedir desculpas pelo que pesou',8,8,'me_pediu_desculpas'],['💬 Explicar seu lado com calma',4,4,'me_aproximou'],['🔥 Cobrar que a outra pessoa reconheça os erros',-4,-5,'me_confrontou']],
        'i4_romance'=>[['❤️ Admitir que tem interesse',5,4,'flertou_comigo'],['😉 Flertar sem pressa',3,3,'flertou_comigo'],['🤝 Respeitar o tempo dele(a)',2,3,'me_aproximou']],
        'i4_sondar'=>[['👀 Perguntar quais nomes preocupam',1,1,'me_aproximou'],['🎯 Perguntar se existe risco para você',2,2,'me_aproximou'],['🧠 Compartilhar sua leitura sem cobrar resposta',4,4,'me_aproximou']],
        'i4_acordo'=>[['🤝 Propor evitar votos um no outro',4,4,'fechou_pacto_comigo'],['🛡️ Propor ajuda quando alguém estiver ameaçado',4,5,'fechou_pacto_comigo'],['🧊 Recuar e manter liberdade de jogo',1,2,'me_aproximou']],
        'i4_informacao'=>[['🤫 Contar e pedir discrição',5,5,'me_contou_segredo'],['🧠 Contar sem pedir nada em troca',3,3,'me_contou_segredo'],['🚫 Desistir de contar',0,0,'me_aproximou']],
        'i4_satisfacao'=>[['🔥 Cobrar uma explicação diretamente',-7,-5,'me_confrontou'],['💬 Ouvir a versão e tentar entender',4,5,'me_aproximou'],['🧊 Deixar claro que precisa de distância',-3,-4,'me_confrontou']],
        'i4_atividade'=>[['😄 Aproveitar para se divertir juntos',7,4,'me_aproximou'],['💜 Usar o momento para conversar de verdade',6,5,'me_apoiou'],['🧠 Puxar um assunto de jogo no meio da atividade',2,1,'me_aproximou']],
        'i4_mediar'=>[['🤝 Ouvir os dois e buscar uma trégua',6,4,'me_apoiou'],['🧠 Pedir que cada um reconheça sua parte',4,3,'me_apoiou'],['🔥 Tomar partido e cobrar uma pessoa',-4,-4,'me_confrontou']],
    ];
    if($a==='i4_romance'){
        if($etapa==='namoro')return [
            ['❤️ Demonstrar carinho e estar presente',7,5,'me_apoiou'],
            ['💬 Conversar com sinceridade sobre vocês',5,8,'me_aproximou'],
            ['🌙 Aproveitar um momento tranquilo a dois',6,4,'me_aproximou'],
        ];
        if($etapa==='comprometido')return [
            ['🤝 Respeitar o relacionamento existente',2,3,'me_aproximou'],
            ['💬 Manter uma conversa amigável',2,2,'me_aproximou'],
            ['🧊 Encerrar o assunto com respeito',1,1,'me_aproximou'],
        ];
        if($etapa==='aproximacao')return [
            ['💗 Contar o que você está sentindo',5,4,'flertou_comigo'],
            ['😉 Flertar sem apressar a relação',3,3,'flertou_comigo'],
            ['🤝 Conversar sobre o ritmo de vocês',2,4,'me_aproximou'],
        ];
    }
    return $op[$a]??[];
}
function i4SincronizarCena(string $fase): void {
    $semana=i4Semana();
    foreach(['i4_pendente','i4_resultado'] as $chave) {
        $c=$_SESSION[$chave]??null;
        if(!is_array($c))continue;
        if((int)($c['semana']??0)!==$semana || ($chave==='i4_pendente' && (string)($c['fase']??'')!==$fase))unset($_SESSION[$chave]);
    }
}

function i4Iniciar(array $jogadores,string $meuNome,string $fase,array $post): bool {
    if(!i4Disponivel($jogadores,$meuNome,$fase) || !empty($_SESSION['i4_pendente']) || !empty($_SESSION['i4_resultado']))return false;
    $a=(string)($post['acao']??'');$catalogo=i4Catalogo();if(!isset($catalogo[$a]))return false;
    $npc=(string)($post['alvo']??'');$ativos=i4Ativos($jogadores);
    if(!isset($ativos[$npc]) || $npc===$meuNome)return false;
    $assunto=(string)($post['i4_assunto']??'');$abordagem=(string)($post['i4_abordagem']??'');
    $etapaRomance=$a==='i4_romance'?i4EtapaRomance($jogadores,$meuNome,$npc):'';
    // Formulário de versão anterior: assunto de flerte enviado antes da atualização
    // é adaptado ao namoro existente, sem exibir uma declaração de interesse nova.
    if($a==='i4_romance' && !isset(i4Assuntos($a,$etapaRomance)[$assunto])
        && isset(i4Assuntos('i4_romance')[$assunto])) $assunto=(string)array_key_first(i4AssuntosRomance($etapaRomance));
    if(!isset(i4Assuntos($a,$etapaRomance)[$assunto]) || !isset(i4Abordagens()[$abordagem]))return false;
    $outro='';
    if($a==='i4_mediar'){
        $outro=(string)($post['alvo2']??'');
        if($outro===$npc || $outro===$meuNome || !isset($ativos[$outro]))return false;
    }
    $conhecimento='';
    if($a==='i4_informacao'){
        $conhecimento=(string)($post['i4_conhecimento']??'');
        if(!isset(i4Conhecimentos()[$conhecimento]))return false;
    }
    $locais=['na varanda','na cozinha','no jardim','na piscina','no quarto','na sala'];
    $local=$locais[array_rand($locais)];
    $base=['acao'=>$a,'alvo'=>$npc,'outro'=>$outro,'assunto'=>$assunto,'abordagem'=>$abordagem,
        'conhecimento'=>$conhecimento,'romance_etapa'=>$etapaRomance,'local'=>$local,'fase'=>$fase,'semana'=>i4Semana(),
        'token'=>bin2hex(random_bytes(12))];
    $base['fala']=i4Frase($base,$jogadores,$meuNome);
    $_SESSION['i4_pendente']=$base;
    unset($_SESSION['acao_selecionada']);
    return true;
}
function i4Alterar(array &$jogadores,string $meuNome,string $npc,int $delta,int $confianca,int $rivalidade=0): void {
    // Valores têm teto e piso, inclusive no placar que a IA consulta para avaliar o protagonista.
    foreach($jogadores as &$j){
        $n=(string)($j['nome']??'');
        if($n!==$meuNome && $n!==$npc)continue;
        $outro=$n===$meuNome?$npc:$meuNome;
        if(!isset($j['relacoes'][$outro]))$j['relacoes'][$outro]=['amizade'=>0,'rivalidade'=>0,'confianca'=>0];
        $r=&$j['relacoes'][$outro];
        $r['amizade']=max(0,min(100,(int)($r['amizade']??0)+$delta));
        $r['confianca']=max(0,min(100,(int)($r['confianca']??0)+$confianca));
        $r['rivalidade']=max(0,min(100,(int)($r['rivalidade']??0)+$rivalidade));
        unset($r);
    }unset($j);
    $_SESSION['relacoes_jogador'][$npc]=max(-100,min(100,(int)($_SESSION['relacoes_jogador'][$npc]??0)+$delta));
}
/** Proteção individual por até duas semanas, sem garantir voto, VIP ou imunidade. */
function i4BonusAlvo(array $jogadores,string $npc,string $alvo,string $contexto): float {
    if($alvo!==(string)($_SESSION['meu_nome']??''))return 0.0;
    $ativos=i4Ativos($jogadores); if(!isset($ativos[$npc])||!isset($ativos[$alvo]))return 0.0;
    $ac=(array)($_SESSION['i4_acordos'][$npc]??[]);
    if(empty($ac['aceito']) || (int)($ac['ate']??0)<i4Semana())return 0.0;
    if(in_array($contexto,['voto','indicacao_lider','indicacao_bigfone','contra_golpe'],true))return -19.0;
    if(in_array($contexto,['vip','imunidade','curinga_imunidade','salvar_paredao','podio'],true))return 12.0;
    return 0.0;
}
function i4Responder(array &$jogadores,string $meuNome,string $fase,string $token,int $indice): string {
    $c=$_SESSION['i4_pendente']??null;
    if(!is_array($c)||!hash_equals((string)($c['token']??''),$token)||!i4Disponivel($jogadores,$meuNome,$fase)
        || $c['fase']!==$fase || (int)$c['semana']!==i4Semana())return '';
    $ativos=i4Ativos($jogadores);$npc=(string)$c['alvo'];if(!isset($ativos[$npc]))return '';
    $etapaRomance=$c['acao']==='i4_romance'?i4EtapaRomance($jogadores,$meuNome,$npc):'';
    // Caso um save antigo contenha uma conversa iniciada antes do namoro,
    // a resposta passa a usar o estado atual, sem reabrir uma declaração inicial.
    $opcoes=i4Opcoes((string)$c['acao'],$etapaRomance); if(!isset($opcoes[$indice]))return '';
    $outro=(string)$c['outro'];if($outro!=='' && (!isset($ativos[$outro])))return '';
    if($c['acao']==='i4_informacao'&& !isset(i4Conhecimentos()[$c['conhecimento']]))return '';
    $op=$opcoes[$indice];$r=i4Relacao($jogadores,$npc,$meuNome);$acao=(string)$c['acao'];
    $base=$r['forca']+($c['abordagem']==='sincera'?8:($c['abordagem']==='leve'?3:0));
    $delta=(int)$op[1];$conf=(int)$op[2];$mem=(string)$op[3];$rv=$delta<0?4:-2;
    $texto='';$compromisso=false;$romanceDelta=0;
    $successChance=max(10,min(90,45+$base/3));
    if($acao==='i4_protecao'||$acao==='i4_acordo'){
        if($indice===2){$texto='Vocês encerraram a conversa sem firmar nenhum compromisso.';}
        else {
            $chance=max(12,min(88,(int)round(42+$base/2 + (($r['tracos']['lealdade']??50)-50)/4)));
            $compromisso=random_int(1,100)<=$chance;
            if($compromisso){
                $_SESSION['i4_acordos'][$npc]=['tipo'=>$acao==='i4_protecao'?'protecao':'acordo','semana'=>i4Semana(),'ate'=>i4Semana()+1,'aceito'=>true];
                $texto="$npc aceitou tentar cumprir o combinado. É uma promessa, não uma garantia de voto ou imunidade.";
            }else{$delta=-3;$conf=-2;$mem='me_aproximou';$texto="$npc preferiu não prometer proteção. A decisão foi respeitada.";}
        }
    } elseif($acao==='i4_romance'){
        if($etapaRomance==='namoro'){
            // Namoro já registrado: a cena cuida do vínculo atual, nunca tenta iniciá-lo de novo.
            $tensao=$r['rivalidade']>=30 || $r['confianca']<12 || $base<0;
            if($indice===0){
                if($tensao){$delta=3;$conf=4;$romanceDelta=2;
                    $texto="$npc recebeu seu carinho, mas disse que sente falta de resolver algumas coisas entre vocês. O namoro continua, e a conversa abriu espaço para se entenderem.";}
                else{$romanceDelta=6;
                    $texto="$npc correspondeu ao carinho. Vocês aproveitaram um momento afetuoso juntos, sem a pressão do jogo.";}
            }elseif($indice===1){
                $romanceDelta=$tensao?2:4;
                $texto=$tensao?"$npc falou com sinceridade sobre o que vinha incomodando. Vocês se ouviram, sem resolver tudo de uma vez.":"$npc ouviu seu lado e compartilhou como está vivendo o namoro. A conversa fortaleceu a confiança entre vocês.";
            }else{
                $romanceDelta=$tensao?1:5;
                $texto=$tensao?"Vocês tiveram um momento tranquilo a dois. Ainda existem assuntos para resolver, mas isso não apagou o carinho.":"Você e $npc passaram um tempo juntos, curtindo a companhia um do outro como casal.";
            }
        }elseif($etapaRomance==='comprometido'){
            $romanceDelta=0;
            $texto=$indice===2?'Vocês encerraram o assunto com respeito e sem flertar.':
                "$npc respeitou os limites da conversa. Nenhum compromisso romântico foi criado ou alterado.";
        }elseif($indice!==2){
            $chance=max(12,min(85,(int)round(37+$base/3+$r['romance']/3)));
            if(random_int(1,100)<=$chance){$romanceDelta=$indice===0?11:7;
                $texto=$etapaRomance==='aproximacao'?"$npc correspondeu ao carinho e reconheceu que existe uma conexão entre vocês. Ainda não houve pedido de namoro.":"$npc correspondeu à aproximação, mas prefere descobrir aos poucos o que sente.";}
            else{$delta=0;$conf=0;$mem='me_aproximou';$texto="$npc não correspondeu ao flerte neste momento. Você respeitou o limite.";}
        }else{
            $texto=$etapaRomance==='aproximacao'?"$npc gostou de conversar sobre os sentimentos, sem transformar isso automaticamente em namoro.":"$npc agradeceu por você respeitar seu tempo. A relação continua sem nenhuma declaração automática.";
        }
    }elseif($acao==='i4_reconciliar'){
        $chance=max(12,min(90,(int)round($successChance+(($indice===0)?12:0)-$r['rivalidade']/4)));
        if($indice===2){$texto="$npc não gostou da cobrança e a conversa terminou com tensão.";}
        elseif(random_int(1,100)<=$chance){$texto="$npc reconheceu sua tentativa de resolver o atrito. A relação melhorou um pouco, sem apagar o passado.";$rv=-7;}
        else{$delta=max(0,$delta-5);$conf=0;$rv=-1;$texto="$npc ouviu seu lado, mas ainda precisa de tempo para se reaproximar.";}
    }elseif($acao==='i4_sondar'){
        $rivalReal=i4PreocupacaoReal($jogadores,$npc,$meuNome);
        $chancedeAbrir=max(8,min(78,(int)(28+$base/2)));
        if($indice!==2&&$rivalReal!==''&&random_int(1,100)<=$chancedeAbrir){
            $texto="“Com $rivalReal a convivência anda difícil. Mas isso não significa que já tenha decidido meu voto”, disse $npc. É uma opinião, não um voto confirmado.";
            if(function_exists('ecRegistrarConhecimento'))ecRegistrarConhecimento(
                'leitura','Preocupação de '.$npc,"$npc comentou em conversa privada que tem enfrentado dificuldades de convivência com $rivalReal, mas não confirmou um voto.",
                $npc,'alta',i4Semana()+1,['alvo'=>$rivalReal]
            );
        }else{$texto=$indice===2?"$npc ouviu sua análise e compartilhou apenas impressões gerais da casa.":"$npc falou sobre o clima e as próprias preocupações, sem confirmar nomes, votos secretos ou planos que você não conhecia.";}
    }elseif($acao==='i4_informacao'){
        if($indice!==2){
            $_SESSION['i4_informacoes_compartilhadas'][]=['semana'=>i4Semana(),'npc'=>$npc,'chave'=>$c['conhecimento'],'pedido_discricao'=>$indice===0];
            // O NPC agora detém a informação, que pode circular futuramente pelas fofocas.
            $conhecido=i4Conhecimentos()[$c['conhecimento']];
            $_SESSION['fofocas_casa'][]=['origem'=>$meuNome,'portador'=>$npc,
                'texto'=>(string)$conhecido['texto'],
                'verdadeira'=>(string)($conhecido['confiabilidade']??'media')==='alta',
                'rodada'=>i4Semana(),'expira_rodada'=>i4Semana()+1];
            $_SESSION['fofocas_casa']=array_slice($_SESSION['fofocas_casa'],-35);

            $_SESSION['i4_informacoes_compartilhadas']=array_slice($_SESSION['i4_informacoes_compartilhadas'],-25);
            $texto="$npc ouviu a informação que você escolheu contar. Não há garantia de que guardará segredo.";
        }else{$texto='Você decidiu guardar essa informação. Nada foi revelado.';}
    }elseif($acao==='i4_mediar'){
        if($indice<2 && random_int(1,100)<=max(12,min(80,(int)(40+$base/3)))){
            $texto="$npc e $outro aceitaram conversar com mais calma. A convivência entre eles melhorou um pouco.";
            if(function_exists('alterarAfinidade')){
                alterarAfinidade($jogadores,$npc,$outro,4,-5,3);
                alterarAfinidade($jogadores,$outro,$npc,4,-5,3);
            }
        }else{$texto="A tentativa não resolveu o atrito entre $npc e $outro. Você não pode forçar a reconciliação.";}
    }else{
        $respostas=['i4_pessoal'=>"$npc compartilhou um pouco mais de como está vivendo o confinamento.",
            'i4_conversar'=>"$npc levou sua resposta em consideração. A conversa terminou sem promessas ou votos definidos.",
            'i4_satisfacao'=> $indice===0?"$npc rebateu a cobrança e os dois ficaram mais distantes.":"$npc explicou seu ponto de vista. Vocês não precisam concordar para encerrar a conversa.",
            'i4_atividade'=>"Vocês aproveitaram o momento juntos, longe da pressão das decisões do jogo."];
        $texto=$respostas[$acao]??"$npc reagiu à sua resposta.";
    }
    i4Alterar($jogadores,$meuNome,$npc,$delta,$conf,$rv);
    if($romanceDelta>0 && function_exists('alterarRomance')){
        alterarRomance($jogadores,$meuNome,$npc,$romanceDelta);
        alterarRomance($jogadores,$npc,$meuNome,$romanceDelta>7?8:5);
    }
    if(function_exists('registrarMemoriaSocialNPC')&&($delta!==0||$conf!==0||$romanceDelta>0||$compromisso)){
        if(!$compromisso&&$mem==='fechou_pacto_comigo')$mem='me_aproximou';
        registrarMemoriaSocialNPC($npc,$meuNome,$mem,1,'Interação direta na semana '.i4Semana(), 'i4|'.$c['token']);
    }
    if(function_exists('e3Mudanca') && ($compromisso||$romanceDelta>0||abs($delta)>=6)){
        e3Mudanca($npc,$delta<0?['reatividade'=>2,'lealdade'=>-2]:['sociabilidade'=>2,'lealdade'=>2], 'i4_'.$acao, false);
    }
    $semana=i4Semana();
    $_SESSION['i4_usos'][$semana.'|'.$npc]=true;
    if($outro!=='')$_SESSION['i4_usos'][$semana.'|'.$outro]=true;
    $_SESSION['i4_historico'][]=['semana'=>$semana,'acao'=>$acao,'npc'=>$npc,'outro'=>$outro,'escolha'=>$op[0], 'resposta'=>$texto];
    $_SESSION['i4_historico']=array_slice($_SESSION['i4_historico'],-30);
    $temasPublicos=['jogo'=>'os rumos da semana','relacao'=>'a amizade entre eles','convivencia'=>'a convivência da casa',
        'saudade'=>'a saudade de casa','sonhos'=>'seus planos para o futuro','sentimentos'=>'como estão vivendo o confinamento',
        'cozinha'=>'as tarefas na cozinha','piscina'=>'a rotina na piscina','academia'=>'o treino','jardim'=>'a convivência',
        'flertar'=>'a convivência entre eles','encontro'=>'a convivência entre eles',
        'carinho'=>'um momento de carinho','apoio'=>'o cuidado um com o outro',
        'limites'=>'os limites da convivência','encerrar'=>'a convivência'];
    $tema=(string)($temasPublicos[$c['assunto']]??'a convivência');
    if($acao==='i4_conversar' && $c['assunto']==='relacao' && i4EtapaRomance($jogadores,$meuNome,$npc)==='namoro')$tema='o relacionamento deles';
    if($acao==='i4_romance' && $etapaRomance==='namoro')$tema='o relacionamento deles';
    $publico="$meuNome e $npc conversaram {$c['local']} sobre $tema.";
    if($acao==='i4_atividade')$publico="$meuNome e $npc passaram um tempo juntos {$c['local']} realizando uma atividade da casa.";
    if($acao==='i4_mediar')$publico="$meuNome tentou mediar uma conversa entre $npc e $outro {$c['local']}.";
    if(in_array($acao,['i4_protecao','i4_acordo','i4_sondar','i4_informacao'],true))$publico="$meuNome e $npc tiveram uma conversa reservada {$c['local']}. O assunto ficou entre eles.";
    $_SESSION['i4_resultado']=['titulo'=>$acao==='i4_romance'?i4TituloRomance($etapaRomance):i4Catalogo()[$acao][1],
        'npc'=>$npc,'escolha'=>$op[0],'texto'=>$texto,'semana'=>$semana];
    unset($_SESSION['i4_pendente']);
    // Retornar somente um relato observável: nunca divulgar pactos, segredos, votos ou intenções.
    return '💬 '.$publico;
}
