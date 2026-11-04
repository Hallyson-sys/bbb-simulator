<?php
/** Relacionamentos Amorosos 3.0 — continuidade dos casais oficiais.
 * O protagonista decide as próprias falas e o término; nenhum GET aplica escolhas.
 * Estado novo somente em $_SESSION, compatível com saves anteriores.
 */
function rom3Esc(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function rom3Semana(): int { return max(1, (int)($_SESSION['rodada'] ?? 1)); }
function rom3Fase(string $f): bool { return in_array($f, ['interacoes_1','interacoes_2','interacoes_3'],true); }
function rom3Par(string $a, string $b): string { $v=[$a,$b]; sort($v,SORT_STRING); return implode('|',$v); }
function rom3Estado(): void {
    if (!isset($_SESSION['rom3_estado']) || !is_array($_SESSION['rom3_estado'])) $_SESSION['rom3_estado']=[];
    $_SESSION['rom3_estado'] += ['usados'=>[], 'historico'=>[], 'processados'=>[], 'publicos'=>[], 'tensoes'=>[]];
}
function rom3Parceiro(array $jogadores, string $meuNome): string {
    $ativos=i4Ativos($jogadores);
    if ($meuNome==='' || !isset($ativos[$meuNome])) return '';
    $par=(string)($_SESSION['casais'][$meuNome] ?? '');
    // Saves antigos podem registrar só o lado do NPC, mas não aceitar relações conflitantes.
    if ($par==='') foreach((array)($_SESSION['casais']??[]) as $p=>$alvo)
        if ((string)$alvo===$meuNome && (string)($p)!==$meuNome) {$par=(string)$p;break;}
    if ($par==='' || !isset($ativos[$par]) || $par===$meuNome) return '';
    $volta=(string)($_SESSION['casais'][$par]??'');
    return $volta==='' || $volta===$meuNome ? $par : '';
}
function rom3Ativo(array $jogadores, string $meuNome, string $fase): bool {
    return rom3Fase($fase) && empty($_SESSION['modo_espectador'])
        && rom3Parceiro($jogadores,$meuNome)!=='';
}
function rom3Tensao(array $jogadores, string $a, string $b): int {
    $r1=(array)(i4Ativos($jogadores)[$a]['relacoes'][$b]??[]);
    $r2=(array)(i4Ativos($jogadores)[$b]['relacoes'][$a]??[]);
    $rival=max((int)($r1['rivalidade']??0),(int)($r2['rivalidade']??0));
    $confianca=min((int)($r1['confianca']??0),(int)($r2['confianca']??0));
    $s=(int)($_SESSION['r4_estado']['tensoes_casal'][rom3Par($a,$b)]??0);
    $s+=max(0,(int)($_SESSION['rom3_estado']['tensoes'][rom3Par($a,$b)]??0));
    if ($rival>=22) $s+=1;
    if ($rival>=35 && $confianca<10) $s+=1;
    return max(0,min(7,$s));
}
function rom3Temas(array $jogadores, string $a, string $b): array {
    $temas=[
        'carinho'=>['❤️','Ter um momento de carinho',"$b encontra um momento tranquilo para ficar com você longe da correria da casa.",
            '“Eu gosto de estar com você. Queria que a gente aproveitasse um pouco o nosso tempo juntos.”',
            ['💕 Retribuir o carinho','💬 Perguntar como está se sentindo','🌙 Aproveitar a companhia sem muita conversa']],
        'apoio'=>['🫂','Apoiar durante o jogo',"A pressão da semana pesa, e você percebe que $b gostaria de receber apoio.",
            '“Eu sei que a casa mexe com a gente. Às vezes só queria ter certeza de que posso contar com você.”',
            ['🤝 Oferecer apoio sincero','🎯 Lembrar que cada um também tem seu jogo','💜 Escutar sem prometer nada']],
        'futuro'=>['🌙','Sonhos e vida fora da casa',"Você e $b têm tempo para conversar sobre a vida depois do programa.",
            '“Você também pensa em como vão ser as coisas quando a gente sair daqui? Não quero fazer promessas vazias.”',
            ['💞 Contar que deseja continuar se conhecendo','💬 Conversar sem criar expectativas','😌 Focar no presente']],
        'estrategia'=>['🎯','Amor e estratégia',"Uma decisão importante da semana faz vocês refletirem sobre como separar amor e jogo.",
            '“Eu gosto de nós dois, mas sei que, aqui dentro, nem sempre vamos querer votar ou jogar do mesmo jeito.”',
            ['🤝 Respeitar as estratégias individuais','❤️ Dizer que quer proteger a relação','🧠 Pedir transparência, sem exigir votos iguais']],
        'confianca'=>['💬','Conversar sobre confiança',"Você percebe que existe um assunto pendente entre você e $b.",
            '“Quero conversar de verdade. A gente pode discordar sem fingir que está tudo bem o tempo todo.”',
            ['🫂 Escutar e acolher o que sente','💬 Expor seus incômodos com calma','🧊 Pedir um tempo para organizar as ideias']],
    ];
    if (rom3Tensao($jogadores,$a,$b)>=2) {
        $temas['ciumes']=['💔','Conversar sobre inseguranças',"Alguns atritos recentes deixaram o clima entre você e $b mais delicado.",
            '“Eu senti que a gente se afastou um pouco. Não quero te acusar de nada, só entender o que está acontecendo.”',
            ['💜 Reconhecer os sentimentos de ambos','🗣️ Explicar o que incomodou sem acusar','🧊 Dizer que precisa de espaço']];
        $temas['reconciliar']=['🤝','Tentar uma reconciliação',"Depois de momentos difíceis, você e $b encontram uma oportunidade para se ouvir.",
            '“Eu não quero fingir que não aconteceu nada. Se você quiser, podemos tentar resolver isso com calma.”',
            ['🙏 Pedir desculpas pela sua parte','🤝 Propor recomeçar a conversa','⏳ Admitir que ainda precisa de tempo']];
    }
    $temas['decisao']=['💍','Conversar sobre o futuro do namoro',"Você chama $b para uma conversa séria sobre como ambos estão vivendo o namoro.",
        '“Eu quero que a gente seja sincero sobre o que sente. Podemos conversar sem fazer disso uma briga.”',
        ['❤️ Reafirmar que deseja continuar','💬 Pedir mais espaço no relacionamento','💔 Dizer que está pensando em terminar']];
    return $temas;
}
/** Toda cena aberta tem token não reutilizável, contexto fixo e semana/fase. */
function rom3Sincronizar(array $jogadores,string $meuNome,string $fase): void {
    rom3Estado();
    foreach (['rom3_pendente','rom3_confirmacao','rom3_resultado'] as $chave) {
        $p=$_SESSION[$chave]??null;
        if (!is_array($p)) {unset($_SESSION[$chave]);continue;}
        if ((int)($p['semana']??0)!==rom3Semana() || (string)($p['fase']??'')!==$fase
            || !rom3Fase($fase) || !empty($_SESSION['modo_espectador'])) {unset($_SESSION[$chave]);continue;}
        if ($chave!=='rom3_resultado' && rom3Parceiro($jogadores,$meuNome)!==(string)($p['par']??'')) unset($_SESSION[$chave]);
    }
}
function rom3Ocupado(): bool {
    foreach (['i4_pendente','i4_resultado','in2_conversa','in2_resultado','dj_ativo','dj_resultado',
        'evento_convivencia_ativo','evento_convivencia_resultado','rom3_pendente','rom3_confirmacao','rom3_resultado'] as $key)
        if (!empty($_SESSION[$key])) return true;
    return false;
}
function rom3Iniciar(array $jogadores,string $meuNome,string $fase,string $tema): bool {
    rom3Estado(); $par=rom3Parceiro($jogadores,$meuNome);
    if (!rom3Ativo($jogadores,$meuNome,$fase) || rom3Ocupado() || $par==='' ||
        !empty($_SESSION['rom3_estado']['usados'][rom3Semana()])) return false;
    $temas=rom3Temas($jogadores,$meuNome,$par);
    if (!isset($temas[$tema])) return false;
    $_SESSION['rom3_pendente']=['token'=>bin2hex(random_bytes(16)),'semana'=>rom3Semana(),
        'fase'=>$fase,'par'=>$par,'tema'=>$tema];
    return true;
}
function rom3Ajustar(array &$jogadores,string $meuNome,string $par,int $amizade,int $conf,int $rival,int $romance): void {
    if (function_exists('alterarAfinidade')) {
        alterarAfinidade($jogadores,$par,$meuNome,$amizade,$rival,$conf);
        alterarAfinidade($jogadores,$meuNome,$par,(int)round($amizade*.7),(int)round($rival*.7),(int)round($conf*.7));
    }
    if ($romance && function_exists('alterarRomance')) {
        alterarRomance($jogadores,$par,$meuNome,$romance);
        alterarRomance($jogadores,$meuNome,$par,$romance);
    }
    if (function_exists('ajustarRelacaoJogador') && (string)($_SESSION['meu_nome']??'')===$meuNome) ajustarRelacaoJogador($par,$amizade);
}
function rom3RegistrarPrivado(string $par,string $tema,string $rotulo,string $texto): void {
    rom3Estado();
    $_SESSION['rom3_estado']['historico'][]=['semana'=>rom3Semana(),'par'=>$par,'tema'=>$tema,'escolha'=>$rotulo,'resultado'=>$texto];
    $_SESSION['rom3_estado']['historico']=array_slice($_SESSION['rom3_estado']['historico'],-30);
    $_SESSION['rom3_estado']['usados'][rom3Semana()]=true;
    if (count($_SESSION['rom3_estado']['usados'])>12) $_SESSION['rom3_estado']['usados']=array_slice($_SESSION['rom3_estado']['usados'],-12,null,true);
}
function rom3Resultado(array $p,string $rotulo,string $texto): void {
    unset($_SESSION['rom3_pendente'],$_SESSION['rom3_confirmacao']);
    $_SESSION['rom3_resultado']=['semana'=>rom3Semana(),'fase'=>$p['fase'],'par'=>$p['par'],
        'titulo'=>$p['tema']==='decisao'?'Conversa sobre o namoro':'Momento a dois',
        'escolha'=>$rotulo,'texto'=>$texto];
}
function rom3Responder(array &$jogadores,string $meuNome,string $fase,string $token,int $indice): bool {
    $p=$_SESSION['rom3_pendente']??null;
    if (!is_array($p) || !rom3Ativo($jogadores,$meuNome,$fase) ||
        rom3Parceiro($jogadores,$meuNome)!==($p['par']??'') ||
        (int)($p['semana']??0)!==rom3Semana() || $p['fase']!==$fase ||
        !hash_equals((string)$p['token'],$token) || $indice<0 || $indice>2) return false;
    $par=(string)$p['par'];$tema=(string)$p['tema'];
    $d=rom3Temas($jogadores,$meuNome,$par)[$tema]??null;
    if (!$d) return false;
    if ($tema==='decisao' && $indice===2) {
        // Nenhum término num clique isolado. Só confirmar em uma segunda tela.
        $_SESSION['rom3_confirmacao']=$p;
        unset($_SESSION['rom3_pendente']);
        return true;
    }
    $efeitos=[
        'carinho'=>[[5,4,-1,6],[4,5,0,4],[3,2,0,3]],
        'apoio'=>[[4,6,-1,4],[2,2,0,1],[3,5,0,2]],
        'futuro'=>[[4,4,-1,4],[3,4,0,2],[3,2,0,2]],
        'estrategia'=>[[3,5,-1,2],[4,3,0,4],[3,6,-1,2]],
        'confianca'=>[[3,6,-2,3],[2,4,-1,2],[0,0,0,0]],
        'ciumes'=>[[4,5,-4,3],[2,3,-2,1],[0,-2,1,-1]],
        'reconciliar'=>[[5,5,-5,4],[4,4,-3,3],[1,1,-1,0]],
        'decisao'=>[[4,5,-2,4],[1,2,0,1],[0,0,0,0]],
    ];
    [$a,$c,$r,$rom]=$efeitos[$tema][$indice];
    $textos=[
        'carinho'=>['Vocês trocaram carinho e lembraram que o namoro também tem espaço fora da estratégia.',"$par se sentiu ouvido e compartilhou como está vivendo o relacionamento.",'Vocês aproveitaram a companhia um do outro, sem precisar de promessas.'],
        'apoio'=>["$par agradeceu pelo apoio. Vocês conversaram sem tentar decidir a votação um pelo outro.",'Vocês combinaram respeitar a autonomia no jogo, sem fazer acordos que não podem garantir.',"$par valorizou a escuta. O apoio não depende de combinar votos."],
        'futuro'=>['Vocês falaram sobre a possibilidade de continuar se conhecendo depois do programa, sem assumir compromissos automáticos.','A conversa foi sincera e respeitou as incertezas dos dois.','Vocês escolheram aproveitar o presente sem apressar o futuro.'],
        'estrategia'=>['Vocês concordaram que podem jogar de formas diferentes e continuar se respeitando.','O casal reafirmou o carinho sem prometer imunidade ou voto.','Vocês falaram com franqueza sobre a importância de não esconder sentimentos, sem revelar estratégias desconhecidas.'],
        'confianca'=>['Vocês se ouviram e encontraram pontos de entendimento. Ainda podem existir dúvidas.',"$par acolheu suas preocupações e respondeu com calma.","$par respeitou seu pedido de espaço. O assunto fica pendente, sem término automático."],
        'ciumes'=>['A conversa trouxe mais segurança, sem transformar insegurança em acusação de traição.',"$par ouviu seu ponto e explicou o que vinha incomodando.","$par respeitou seu espaço, mas a tensão entre vocês ainda precisa ser resolvida."],
        'reconciliar'=>['Vocês reconheceram parte do que fizeram e conseguiram aliviar o desgaste.','Vocês decidiram tentar reconstruir a confiança aos poucos.','Vocês concordaram que uma reconciliação exige tempo. Nada foi resolvido à força.'],
        'decisao'=>['Vocês reafirmaram o desejo de continuar juntos. O namoro permanece.','Vocês conversaram sobre limites e espaço individual. O namoro permanece.',''],
    ];
    $texto=$textos[$tema][$indice];
    // A personalidade e a memória do casal ajustam o desfecho, sem transformar
    // a escolha em sucesso automático ou impor votos/relacionamentos novos.
    $estado=rom3Tensao($jogadores,$meuNome,$par);
    $perfil=function_exists('i4Relacao')?i4Relacao($jogadores,$par,$meuNome):[];
    $reatividade=(int)($perfil['tracos']['reatividade']??50);
    $lealdade=(int)($perfil['tracos']['lealdade']??50);
    if ($estado>=3 && $reatividade>=62 && in_array($tema,['carinho','reconciliar','confianca'],true) && $indice!==2) {
        $a=max(0,(int)floor($a/2)); $c=max(0,(int)floor($c/2));
        $rom=max(0,(int)floor($rom/2));
        $texto.=" $par ainda está sensível aos atritos recentes e prefere reconstruir as coisas aos poucos.";
    } elseif ($lealdade>=72 && $estado<=1 && in_array($tema,['apoio','carinho','estrategia'],true)) {
        $c+=1;
        $texto.=" $par demonstrou que valoriza a parceria entre vocês.";
    }
    rom3Ajustar($jogadores,$meuNome,$par,$a,$c,$r,$rom);
    $chave=rom3Par($meuNome,$par);
    if (in_array($tema,['reconciliar','ciumes','confianca'],true)) {
        if ($indice===2) $_SESSION['rom3_estado']['tensoes'][$chave]=min(4,(int)($_SESSION['rom3_estado']['tensoes'][$chave]??0)+1);
        else $_SESSION['rom3_estado']['tensoes'][$chave]=max(0,(int)($_SESSION['rom3_estado']['tensoes'][$chave]??0)-2);
        if ($indice!==2 && isset($_SESSION['r4_estado']['tensoes_casal'][$chave]))
            $_SESSION['r4_estado']['tensoes_casal'][$chave]=max(0,(int)$_SESSION['r4_estado']['tensoes_casal'][$chave]-1);
    }
    if (function_exists('registrarMemoriaSocialNPC')) {
        $tipo=$indice===2 && in_array($tema,['confianca','ciumes'],true)?'conversou_comigo':'me_apoiou';
        registrarMemoriaSocialNPC($par,$meuNome,$tipo,2,'Conversaram sobre o namoro na semana '.rom3Semana(),
            'rom3|'.rom3Semana().'|'.$meuNome.'|'.$par);
    }
    $rotulo=$d[4][$indice];
    rom3RegistrarPrivado($par,$tema,$rotulo,$texto);
    rom3Resultado($p,$rotulo,$texto);
    $_SESSION['jogadores']=$jogadores;
    return true;
}
function rom3ConfirmarFim(array &$jogadores,string $meuNome,string $fase,string $token,string $decisao): bool {
    $p=$_SESSION['rom3_confirmacao']??null;
    if (!is_array($p) || (int)($p['semana']??0)!==rom3Semana() || $p['fase']!==$fase ||
        !hash_equals((string)$p['token'],$token) || rom3Parceiro($jogadores,$meuNome)!==($p['par']??'')) return false;
    if (!in_array($decisao,['confirmar','cancelar'],true)) return false;
    $par=(string)$p['par'];
    if ($decisao==='confirmar') {
        terminarCasal($meuNome,$par);
        rom3Ajustar($jogadores,$meuNome,$par,-5,-8,3,-15);
        $texto="Você e $par encerraram o namoro depois de uma conversa séria. Os dois continuam no jogo e poderão desenvolver suas histórias separadamente.";
        // Uma separação pública é observável; não publicar o conteúdo da conversa privada.
        $_SESSION['evento_extra'][]="💔 $meuNome e $par encerraram o namoro.";
        if (function_exists('registrarHistoricoTemporada')) registrarHistoricoTemporada('romance_termino', 'Fim de relacionamento',
            "$meuNome e $par encerraram o relacionamento.",[$meuNome,$par],'💔',null,'rom3|fim');
    } else {
        $texto="Você decidiu não terminar o namoro com $par agora. Nenhuma decisão foi tomada por você automaticamente.";
    }
    $rotulo=$decisao==='confirmar'?'💔 Confirmar o término':'❤️ Continuar namorando';
    rom3RegistrarPrivado($par,'decisao',$rotulo,$texto);
    rom3Resultado($p,$rotulo,$texto);
    $_SESSION['jogadores']=$jogadores;
    return true;
}
/** Só entre casais de NPCs, uma tentativa semanal. Nunca força romance do protagonista. */
function rom3ProcessarNPCs(array &$jogadores,string $meuNome,string $fase): bool {
    if ($fase!=='interacoes_2' || !empty($_SESSION['modo_espectador'])) return false;
    rom3Estado();$sem=rom3Semana();
    if (isset($_SESSION['rom3_estado']['processados'][$sem])) return false;
    $_SESSION['rom3_estado']['processados'][$sem]=true;
    $ativos=i4Ativos($jogadores);$candidatos=[];
    foreach ((array)($_SESSION['casais']??[]) as $a=>$b) {
        $a=(string)$a;$b=(string)$b;
        if ($a==='' || $b==='' || $a===$b || $a===$meuNome || $b===$meuNome ||
            !isset($ativos[$a],$ativos[$b]) || (string)($_SESSION['casais'][$b]??'')!==$a || strcmp($a,$b)>0) continue;
        $ja=false;
        foreach ((array)($_SESSION['r4_estado']['historico']??[]) as $r) {
            if ((int)($r['semana']??0)===$sem && rom3Par((string)($r['pessoas'][0]??''),(string)($r['pessoas'][1]??''))===rom3Par($a,$b)) {$ja=true;break;}
        }
        if (!$ja) $candidatos[]=[$a,$b];
    }
    if (!$candidatos || random_int(1,100)>68) return false;
    [$a,$b]=$candidatos[array_rand($candidatos)];
    $chave=rom3Par($a,$b);$t=rom3Tensao($jogadores,$a,$b);
    $rel=(array)($ativos[$a]['relacoes'][$b]??[]);
    $rival=(int)($rel['rivalidade']??0);$conf=(int)($rel['confianca']??0);
    $tipo='apoio';
    $tracos=function_exists('e3Tracos')?(array)e3Tracos($a):[];
    $reativo=(int)($tracos['reatividade']??50);
    $leal=(int)($tracos['lealdade']??50);
    if ($t>=3 && $rival>=34 && $conf < 5 && random_int(1,100)<=($leal>=70?12:30)) $tipo='termino';
    elseif ($t>=2) $tipo=random_int(1,100)<=max(25,min(85,62+(int)(($leal-50)/4)-(int)(($reativo-50)/5)))?'reconciliacao':'desentendimento';
    elseif (random_int(1,100)<=25) $tipo='conversa';
    if ($tipo==='termino') {
        terminarCasal($a,$b);
        rom3Ajustar($jogadores,$a,$b,-4,-5,3,-12);
        $texto="💔 $a e $b decidiram terminar o namoro depois de um período de desgaste.";
        $_SESSION['rom3_estado']['tensoes'][$chave]=0;
    } elseif ($tipo==='reconciliacao') {
        rom3Ajustar($jogadores,$a,$b,3,4,-3,3);
        $_SESSION['rom3_estado']['tensoes'][$chave]=max(0,(int)($_SESSION['rom3_estado']['tensoes'][$chave]??0)-2);
        $_SESSION['r4_estado']['tensoes_casal'][$chave]=max(0,(int)($_SESSION['r4_estado']['tensoes_casal'][$chave]??0)-1);
        $texto="🤝 $a e $b tiveram uma conversa tranquila na varanda para resolver parte de um desentendimento do namoro.";
    } elseif ($tipo==='desentendimento') {
        rom3Ajustar($jogadores,$a,$b,-2,-2,2,-1);
        $_SESSION['rom3_estado']['tensoes'][$chave]=min(4,(int)($_SESSION['rom3_estado']['tensoes'][$chave]??0)+1);
        $texto="💬 $a e $b discordaram durante a convivência e preferiram dar um tempo para conversar melhor.";
    } elseif ($tipo==='conversa') {
        rom3Ajustar($jogadores,$a,$b,2,3,0,2);
        $texto="🌙 $a e $b conversaram no jardim sobre como equilibrar o namoro e a estratégia dentro da casa.";
    } else {
        rom3Ajustar($jogadores,$a,$b,3,3,-1,3);
        $texto="❤️ $a e $b passaram um tempo juntos no jardim e demonstraram apoio durante a semana.";
    }
    $_SESSION['rom3_estado']['publicos'][]=['semana'=>$sem,'tipo'=>$tipo,'texto'=>$texto,'pessoas'=>[$a,$b],
        'local'=>in_array($tipo,['desentendimento','reconciliacao'],true)?'varanda':'jardim'];
    $_SESSION['rom3_estado']['publicos']=array_slice($_SESSION['rom3_estado']['publicos'],-20);
    $_SESSION['evento_extra'][]=$texto;
    if (function_exists('registrarHistoricoTemporada')) registrarHistoricoTemporada('romance_3', 'Vida amorosa na casa', $texto,
        [$a,$b],$tipo==='termino'?'💔':'❤️',null,'rom3|'.$tipo.'|'.$chave);
    $_SESSION['jogadores']=$jogadores;
    return true;
}
