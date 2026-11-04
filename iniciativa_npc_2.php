<?php
/**
 * Iniciativa dos NPCs 2.0 — convites espontâneos, sempre opcionais.
 * Persistência em $_SESSION: saves antigos dispensam migração.
 * A aproximação é um convite, nunca uma decisão tomada pelo protagonista.
 */

function in2Esc($valor): string { return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8'); }
function in2Semana(): int { return max(1, (int)($_SESSION['rodada'] ?? 1)); }
function in2FaseSocial(string $fase): bool { return in_array($fase, ['interacoes_1','interacoes_2','interacoes_3'], true); }
function in2SituacaoOcupada(): bool {
    foreach (['i4_pendente','i4_resultado','dj_ativo','dj_resultado','evento_convivencia_ativo','evento_convivencia_resultado','rom3_pendente','rom3_confirmacao','rom3_resultado'] as $chave)
        if (!empty($_SESSION[$chave])) return true;
    return false;
}
function in2Disponivel(array $jogadores, string $meuNome, string $fase): bool {
    if (!in2FaseSocial($fase) || !empty($_SESSION['modo_espectador']) || $meuNome==='') return false;
    if (empty(i4Ativos($jogadores)[$meuNome])) return false;
    return !(!empty($_SESSION['paredao_falso_ativo']) && (string)($_SESSION['falso_eliminado'] ?? '')===$meuNome);
}
/** Expira convites sem penalizar ninguém quando a fase ou a semana muda. */
function in2Sincronizar(array $jogadores, string $meuNome, string $fase): void {
    $forcado=(array)($_SESSION['dev_in2_forcar']??[]);
    if ($forcado && ((int)($forcado['semana']??0)!==in2Semana() || (string)($forcado['fase']??'')!==$fase))
        unset($_SESSION['dev_in2_forcar']);
    $ativos = i4Ativos($jogadores);
    foreach (['in2_convite','in2_conversa','in2_resultado'] as $chave) {
        $c = $_SESSION[$chave] ?? null;
        if (!is_array($c)) { unset($_SESSION[$chave]); continue; }
        $valida = in2Disponivel($jogadores, $meuNome, $fase)
            && (int)($c['semana'] ?? -1) === in2Semana()
            && (string)($c['fase'] ?? '') === $fase
            && isset($ativos[(string)($c['npc'] ?? '')]);
        if (!$valida) unset($_SESSION[$chave]);
    }
}
/** Avalia o vínculo real, sem criar romances ou rivais inexistentes. */
function in2Candidatos(array $jogadores, string $meuNome): array {
    $lista=[];
    foreach (i4Ativos($jogadores) as $npc=>$dados) {
        if ($npc===$meuNome) continue;
        if (!empty($_SESSION['in2_usados_npc'][in2Semana()][$npc])) continue;
        $rel=i4Relacao($jogadores,$npc,$meuNome);
        $etapa=i4EtapaRomance($jogadores,$meuNome,$npc);
        $tracos=(array)($rel['tracos']??[]);
        $sociavel=(int)($tracos['sociabilidade']??50);
        $leal=(int)($tracos['lealdade']??50);
        $reativo=(int)($tracos['reatividade']??50);
        $forca=(int)$rel['forca'];
        $tipos=['conversa'=>20+max(0,(int)(($sociavel-45)/4))];
        if ($forca>=7 || $sociavel>=58) $tipos['desabafo']=max(6, 18+(int)($forca/8)+(int)(($sociavel-50)/5));
        if ($forca>=20) $tipos['apoio']=15+(int)($forca/5)+(int)(($leal-50)/7);
        if ($forca<-8 || (int)$rel['rivalidade']>=20) $tipos['reconciliar']=17+(int)($reativo/10);
        if ($etapa==='namoro') $tipos['casal']=40+max(0,(int)($forca/5));
        elseif ($etapa==='aproximacao' && $forca>=5) $tipos['aproximacao']=12+max(0,(int)($forca/7));
        if ($forca>=10 && $etapa!=='comprometido') $tipos['acordo']=13+(int)($leal/8);
        // Objetivos pessoais orientam a iniciativa, mas não garantem uma escolha.
        $objetivo=function_exists('on1Objetivo')?(array)(on1Objetivo($npc)??[]):[];
        $objetivoTipo=(string)($objetivo['tipo']??'');
        $objetivoAlvo=(string)($objetivo['alvo']??'');
        if ($objetivoAlvo===$meuNome) {
            if ($objetivoTipo==='proteger_aliado' && isset($tipos['apoio'])) $tipos['apoio']+=20;
            if ($objetivoTipo==='reconciliar' && isset($tipos['reconciliar'])) $tipos['reconciliar']+=20;
        }
        if (in_array($objetivoTipo,['fortalecer_alianca','construir_alianca','evitar_paredao'],true)
            && isset($tipos['acordo'])) $tipos['acordo']+=12;
        foreach ($tipos as $tipo=>$peso)
            if ($peso>0) $lista[]=['npc'=>$npc,'tipo'=>$tipo,'peso'=>max(1,min(65,$peso))];
    }
    return $lista;
}
function in2Escolher(array $candidatos): ?array {
    if (!$candidatos) return null;
    $total=array_sum(array_column($candidatos,'peso'));
    $sorte=random_int(1,max(1,$total));
    foreach ($candidatos as $c) { $sorte-=(int)$c['peso']; if ($sorte<=0) return $c; }
    return end($candidatos) ?: null;
}
function in2Dados(string $npc, string $tipo, string $etapa, string $anterior=''): array {
    // Fala contextual, sem promessas implícitas de namoro ou exposição de votos.
    $dados=match($tipo) {
        'casal'=>['❤️','Uma conversa a dois',
            '“Queria passar um tempo com você. Com tanta coisa acontecendo, às vezes a gente precisa cuidar do nosso namoro.”',
            ['❤️ Demonstrar carinho e ouvir','💬 Conversar sobre como estamos','🌙 Curtir um momento tranquilo']],
        'aproximacao'=>['💕','Uma aproximação inesperada',
            '“Eu gosto de estar com você. Queria conversar um pouco, sem pressa e sem criar expectativas.”',
            ['💗 Demonstrar que também gosta da companhia','🙂 Conhecer melhor sem apressar nada','🤝 Deixar claro que prefere amizade']],
        'reconciliar'=>['🤝','Podemos conversar sobre o que aconteceu?',
            '“O clima entre a gente não anda bom. Não quero fingir que nada aconteceu. Podemos conversar?”',
            ['🙏 Ouvir e tentar resolver o atrito','💬 Explicar sua versão com calma','🧊 Dizer que ainda precisa de espaço']],
        'apoio'=>['🫂','Alguém veio te apoiar',
            '“Eu percebi que a semana tem sido pesada. Queria saber como você está e se precisa conversar.”',
            ['💜 Agradecer e se abrir','🤝 Oferecer apoio também','🙂 Agradecer e mudar de assunto']],
        'acordo'=>['🎯','Uma proposta reservada',
            '“Queria te propor uma coisa: a gente pode tentar não se prejudicar na próxima votação? Sem prometer o que não dá para cumprir.”',
            ['🤝 Aceitar tentar uma proteção mútua','🧠 Conversar sem assumir compromisso','🚫 Recusar o acordo']],
        'desabafo'=>['💭','Um desabafo inesperado',
            '“Hoje foi um daqueles dias. Estava querendo alguém para conversar, sem ficar falando só de estratégia.”',
            ['💜 Escutar e acolher','🌿 Compartilhar como você se sente','🙂 Ouvir, mas manter a conversa leve']],
        default=>['💬','Um convite para conversar',
            '“Te encontrei por aqui e queria trocar uma ideia. Como você está achando a convivência nesta semana?”',
            ['💬 Conversar com sinceridade','😄 Puxar um assunto leve','🧠 Escutar com cautela']],
    };
    if ($tipo==='conversa' && $etapa==='namoro') {
        $dados[1]='Seu parceiro quer conversar';
        $dados[2]='“A gente já conversa tanto sobre jogo, mas queria saber como você está se sentindo com tudo isso. Vamos ficar um pouco juntos?”';
    }
    if ($anterior!=='' && in_array($tipo,['conversa','apoio','desabafo','casal'],true)) {
        $dados[2] .= ' “Fiquei pensando na nossa conversa da outra vez.”';
    }
    return $dados;
}
/** Uma tentativa por fase; no máximo dois convites por semana. Sem sorteio por refresh. */
function in2TalvezGerar(array $jogadores,string $meuNome,string $fase): bool {
    if (!in2Disponivel($jogadores,$meuNome,$fase) || in2SituacaoOcupada() ||
        !empty($_SESSION['in2_convite']) || !empty($_SESSION['in2_conversa']) || !empty($_SESSION['in2_resultado'])) return false;
    $sem=in2Semana(); $chave=$sem.'|'.$fase;
    $forcado = (array)($_SESSION['dev_in2_forcar']??[]);
    $usarDev = ($forcado['fase']??'')===$fase && (int)($forcado['semana']??0)===$sem;
    if (!$usarDev && isset($_SESSION['in2_tentativas'][$chave])) return false;
    // Marca ANTES de qualquer sorteio: o F5 nunca cria outra oportunidade na mesma fase.
    $_SESSION['in2_tentativas'][$chave]=true;
    if (!$usarDev && ((int)($_SESSION['in2_contagem'][$sem]??0)>=2 || random_int(1,100)>78)) return false;
    $opcoes=in2Candidatos($jogadores,$meuNome);
    if ($usarDev && ($forcado['npc']??'')!=='') {
        $especificas=array_values(array_filter($opcoes, fn($c)=>$c['npc']===$forcado['npc']));
        if ($especificas) $opcoes=$especificas;
    }
    $escolha=in2Escolher($opcoes);
    if (!$escolha) return false;
    if ($usarDev) unset($_SESSION['dev_in2_forcar']);
    $npc=(string)$escolha['npc']; $tipo=(string)$escolha['tipo'];
    $lembranca=(array)($_SESSION['in2_lembrancas'][$npc]??[]);
    $anterior=(int)($lembranca['semana']??0)>=($sem-3)?(string)($lembranca['tema']??''):'';
    $etapa=i4EtapaRomance($jogadores,$meuNome,$npc);
    $dados=in2Dados($npc,$tipo,$etapa,$anterior);
    $locais=['na varanda','na cozinha','no jardim','na sala'];
    $_SESSION['in2_convite']=[
        'token'=>bin2hex(random_bytes(12)), 'semana'=>$sem,'fase'=>$fase,'npc'=>$npc,
        'tipo'=>$tipo, 'etapa'=>$etapa, 'titulo'=>$dados[1], 'icone'=>$dados[0],
        'fala'=>$dados[2], 'local'=>$locais[array_rand($locais)],
        'adiado'=>false,
    ];
    $_SESSION['in2_contagem'][$sem]=(int)($_SESSION['in2_contagem'][$sem]??0)+1;
    $_SESSION['in2_usados_npc'][$sem][$npc]=true;
    return true;
}
/** Aceitar, adiar ou recusar não modifica afinidade. */
function in2ResponderConvite(array $jogadores,string $meuNome,string $fase,string $token,string $decisao): bool {
    $c=$_SESSION['in2_convite']??null;
    if (!is_array($c) || !in2Disponivel($jogadores,$meuNome,$fase) || in2SituacaoOcupada() ||
        !hash_equals((string)$c['token'],$token) || $c['fase']!==$fase || (int)$c['semana']!==in2Semana() ||
        !isset(i4Ativos($jogadores)[$c['npc']])) return false;
    if ($decisao==='adiar') { $_SESSION['in2_convite']['adiado']=true; return true; }
    if ($decisao==='retomar') { $_SESSION['in2_convite']['adiado']=false; return true; }
    if ($decisao==='recusar') {
        $_SESSION['in2_historico'][]=['semana'=>in2Semana(),'npc'=>$c['npc'],'tipo'=>$c['tipo'],'decisao'=>'Você preferiu não conversar agora.','resposta'=>'Convite recusado, sem punição.'];
        $_SESSION['in2_historico']=array_slice($_SESSION['in2_historico'],-35);
        unset($_SESSION['in2_convite']); return true;
    }
    if ($decisao!=='aceitar') return false;
    $etapa=i4EtapaRomance($jogadores,$meuNome,(string)$c['npc']);
    // Se o status amoroso mudou entre o convite e a resposta, não usar fala antiga.
    if ($etapa!==$c['etapa']) {
        $novoTipo=$c['tipo']==='casal' || $c['tipo']==='aproximacao' ?
            ($etapa==='namoro'?'casal':'conversa') : $c['tipo'];
        $d=in2Dados((string)$c['npc'],$novoTipo,$etapa);
        $c['tipo']=$novoTipo; $c['etapa']=$etapa;
        $c['titulo']=$d[1]; $c['fala']=$d[2]; $c['icone']=$d[0];
    }
    $_SESSION['in2_conversa']=$c;
    unset($_SESSION['in2_convite']);
    return true;
}
/** Aplicação exatamente uma vez, com validação da fase, escolha e nonce. */
function in2Concluir(array &$jogadores,string $meuNome,string $fase,string $token,int $indice): bool {
    $c=$_SESSION['in2_conversa']??null;
    if (!is_array($c) || !in2Disponivel($jogadores,$meuNome,$fase) ||
        !hash_equals((string)$c['token'],$token) || $c['fase']!==$fase || (int)$c['semana']!==in2Semana() ||
        !isset(i4Ativos($jogadores)[$c['npc']])) return false;
    $npc=(string)$c['npc']; $tipo=(string)$c['tipo'];
    $d=in2Dados($npc,$tipo,(string)$c['etapa']);
    if (!isset($d[3][$indice])) return false;
    $rotulo=(string)$d[3][$indice];
    $rel=i4Relacao($jogadores,$npc,$meuNome);
    $delta=0; $conf=0; $rival=0; $romance=0; $memoria='me_aproximou';
    $texto='';
    switch ($tipo) {
        case 'casal':
            // Nunca criar ou desfazer um namoro por uma conversa espontânea.
            if (i4EtapaRomance($jogadores,$meuNome,$npc)==='namoro') {
                $delta=[5,3,4][$indice]; $conf=[4,6,3][$indice]; $romance=[4,2,3][$indice];
                $texto=$indice===1?'Vocês conversaram sobre o namoro e reconheceram que nem sempre concordam sobre o jogo.':
                    'Vocês aproveitaram um momento de carinho e cumplicidade. O namoro continua.';
            } else { $texto='Vocês conversaram com respeito, sem definir qualquer relacionamento.'; }
            break;
        case 'aproximacao':
            if (i4EtapaRomance($jogadores,$meuNome,$npc)==='comprometido') {
                $texto='Vocês respeitaram os relacionamentos existentes. Nenhuma aproximação romântica aconteceu.';
            } elseif ($indice===2) { $delta=1;$conf=1;$texto="$npc respeitou sua preferência por amizade."; }
            else { $delta=$indice===0?5:3;$conf=3;$romance=$indice===0?5:2;
                $texto="$npc gostou da conversa e vocês se conheceram um pouco melhor, sem assumir um namoro."; }
            break;
        case 'reconciliar':
            if ($indice===2) { $delta=0;$conf=0;$texto="$npc aceitou dar um tempo à relação. Nada foi resolvido à força."; }
            else { $chance=max(15,min(85,53+(int)($rel['forca']/3)-(int)($rel['rivalidade']/4)));
                if (random_int(1,100)<=$chance) { $delta=6;$conf=5;$rival=-5;$memoria='me_pediu_desculpas';$texto="$npc ouviu seu lado e vocês conseguiram diminuir parte do atrito."; }
                else { $delta=1;$texto="$npc ouviu você, mas ainda precisa de tempo. O conflito não foi apagado."; }
            } break;
        case 'acordo':
            if ($indice===0) {
                $chance=max(15,min(90,55+(int)($rel['forca']/4)+((int)($rel['tracos']['lealdade']??50)-50)/5));
                if (random_int(1,100)<=$chance) {
                    $delta=4;$conf=5;$memoria='fechou_pacto_comigo';
                    $_SESSION['i4_acordos'][$npc]=['tipo'=>'acordo','semana'=>in2Semana(),'ate'=>in2Semana()+1,'aceito'=>true];
                    $texto="$npc concordou em tentar proteger a parceria por um período limitado. Isso não garante voto, indicação ou imunidade.";
                } else { $texto="$npc preferiu não assumir compromisso agora. Vocês seguem livres para decidir."; }
            } elseif ($indice===1) { $delta=2;$conf=1;$texto='Vocês trocaram impressões sobre o jogo, mas não combinaram votos nem proteção.'; }
            else { $texto="$npc respeitou a recusa. Não foi feito nenhum acordo."; }
            break;
        case 'apoio':
            $delta=[5,4,1][$indice];$conf=[5,4,1][$indice];$memoria='me_apoiou';
            $texto=$indice===2?'A conversa ficou leve, sem entrar em assuntos difíceis.':
                "$npc demonstrou atenção ao que você disse. Vocês saíram da conversa mais próximos.";
            break;
        case 'desabafo':
            $delta=[5,3,1][$indice];$conf=[6,3,1][$indice];$memoria='me_apoiou';
            $texto=$indice===2?"$npc agradeceu pela escuta, sem precisar contar mais nada.":
                "$npc se sentiu mais à vontade depois de conversar com você. Nenhum segredo estratégico foi revelado.";
            break;
        default:
            $delta=[4,3,1][$indice];$conf=[3,2,1][$indice];
            $texto="$npc gostou de ter tomado a iniciativa. Vocês conversaram sobre a convivência sem fazer promessas automáticas.";
    }
    if (($delta!==0 || $conf!==0 || $rival!==0) && function_exists('i4Alterar')) i4Alterar($jogadores,$meuNome,$npc,$delta,$conf,$rival);
    if ($romance>0 && function_exists('alterarRomance')) {
        alterarRomance($jogadores,$meuNome,$npc,$romance);
        alterarRomance($jogadores,$npc,$meuNome,$romance);
    }
    if (($delta!==0||$conf!==0) && function_exists('registrarMemoriaSocialNPC')) {
        registrarMemoriaSocialNPC($npc,$meuNome,$memoria,1,
            "$meuNome respondeu a uma conversa iniciada por $npc.", 'in2|'.$token);
    }
    $_SESSION['in2_lembrancas'][$npc]=['semana'=>in2Semana(),'tema'=>$tipo,'escolha'=>$indice];
    $_SESSION['in2_historico'][]=['semana'=>in2Semana(),'npc'=>$npc,'tipo'=>$tipo,'decisao'=>$rotulo,'resposta'=>$texto];
    $_SESSION['in2_historico']=array_slice($_SESSION['in2_historico'],-35);
    $_SESSION['in2_resultado']=[
        'token'=>$token,'semana'=>in2Semana(),'fase'=>$fase,'npc'=>$npc,
        'titulo'=>$c['titulo'],'resposta'=>$rotulo,'texto'=>$texto,
    ];
    unset($_SESSION['in2_conversa']);
    $_SESSION['jogadores']=$jogadores;
    if (!isset($_SESSION['evento_extra'])||!is_array($_SESSION['evento_extra']))$_SESSION['evento_extra']=[];
    $publico="$npc procurou $meuNome para conversar {$c['local']}.";
    if ($tipo==='casal')$publico="$npc e $meuNome passaram um tempo juntos {$c['local']} conversando sobre o relacionamento.";
    if ($tipo==='acordo')$publico="$npc e $meuNome tiveram uma conversa reservada {$c['local']}.";
    $_SESSION['evento_extra'][]='💬 '.$publico;
    return true;
}
