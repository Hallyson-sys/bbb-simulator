<?php
/** Testes isolados, não modificam o save do jogador. php testes/interacoes_4.php */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
if(!function_exists('mb_strtolower')){function mb_strtolower(string $s,?string $e=null): string {return strtolower($s);}}
if(!function_exists('mb_stripos')){function mb_stripos(string $h,string $n,int $o=0,?string $e=null): int|false{return stripos($h,$n,$o);}}
$root=dirname(__DIR__);
foreach(['utilitarios.php','relacoes.php','romance.php','inteligencia_npc.php','interacoes_4.php'] as $arq)require_once $root.'/includes/logica/'.$arq;
$checks=0;function okI4(bool $pass,string $reason): void {global $checks;if(!$pass)throw new RuntimeException('FALHOU: '.$reason);$checks++;echo "[OK] $reason\n";}
function simI4(): array {
    $_SESSION=['rodada'=>4,'fase_semana'=>'interacoes_1','meu_nome'=>'Jogador','acoes_restantes'=>3,
    'relacoes_jogador'=>['Bianca'=>45,'Caio'=>-25,'Lara'=>15,'Guilherme'=>22],'evento_extra'=>[]];
    $nomes=['Jogador','Bianca','Caio','Lara','Guilherme'];$out=[];
    foreach($nomes as $n){$rel=[];foreach($nomes as $m)if($n!==$m)$rel[$m]=['amizade'=>23,'confianca'=>20,'rivalidade'=>$n==='Caio'?28:3];
        $out[]=['nome'=>$n,'personalidade'=>'Fofo','eliminado'=>false,'relacoes'=>$rel,'romances'=>[],'status'=>[], 'alianca'=>null,'popularidade'=>50];}
    $_SESSION['jogadores']=$out;return $out;
}
function postI4(string $a,string $alvo='Bianca',array $extra=[]): array {return array_merge(['acao'=>$a,'alvo'=>$alvo,'i4_assunto'=>array_key_first(i4Assuntos($a)),'i4_abordagem'=>'sincera'],$extra);}
$catalog=i4Catalogo();okI4(count($catalog)>=10,'Pelo menos dez ações completas');
foreach($catalog as $a=>$item){
    okI4(isset($item[0],$item[1],$item[2],$item[3]),'Catálogo válido: '.$a);
    okI4(count(i4Opcoes($a))===3,'Três respostas por ação: '.$a);
    $j=simI4();$extra=[];
    if($a==='i4_mediar')$extra=['alvo2'=>'Caio'];
    if($a==='i4_informacao'){
        $_SESSION['conhecimentos_jogador']=[['chave'=>'real1','titulo'=>'Algo que eu ouvi','texto'=>'Eu ouvi uma conversa no quarto.','expirado'=>false,'expira_rodada'=>5]];
        $extra=['i4_conhecimento'=>'real1'];
    }
    $before=$j;okI4(i4Iniciar($j,'Jogador','interacoes_1',postI4($a,'Bianca',$extra)),'Inicia diálogo: '.$a);
    $c=$_SESSION['i4_pendente'];okI4($j===$before && (int)$_SESSION['acoes_restantes']===3,'Cena não cobra nem altera relações antes da resposta: '.$a);
    $s=$_SESSION;okI4(!i4Iniciar($j,'Jogador','interacoes_1',postI4($a,'Lara',$extra)) && $_SESSION===$s,'Não sobrepõe diálogo: '.$a);
    okI4(i4Responder($j,'Jogador','interacoes_1','token-invalido',0)==='','Rejeita token falso: '.$a);
    okI4(i4Responder($j,'Jogador','interacoes_1',$c['token'],99)==='','Rejeita alternativa fora da lista: '.$a);
    $ev=i4Responder($j,'Jogador','interacoes_1',$c['token'],1);
    okI4($ev!=='' && empty($_SESSION['i4_pendente']) && !empty($_SESSION['i4_resultado']),'Conclui e mostra desfecho: '.$a);
    okI4(count($_SESSION['i4_historico'])===1 && i4Usada('Bianca'),'Histórico registra conversa com NPC: '.$a);
    $after=$_SESSION;okI4(i4Responder($j,'Jogador','interacoes_1',$c['token'],1)==='' && $_SESSION===$after,'Reenvio não duplica efeitos: '.$a);
    okI4(strpos($ev,'Você contou')===false && strpos($ev,'promessa')===false,'Ao Vivo preserva conteúdo reservado: '.$a);
}
$j=simI4();okI4(!i4Iniciar($j,'Jogador','interacoes_1',postI4('i4_informacao')),'Não inventa conhecimento');
$j=simI4();$j[1]['eliminado']=true;okI4(!i4Iniciar($j,'Jogador','interacoes_1',postI4('i4_pessoal')),'Não inclui eliminado');
$j=simI4();$_SESSION['modo_espectador']=true;okI4(!i4Iniciar($j,'Jogador','interacoes_1',postI4('i4_pessoal')),'Espectador não interage');
$j=simI4();okI4(!i4Iniciar($j,'Jogador','interacoes_1',postI4('i4_mediar','Bianca',['alvo2'=>'Bianca'])),'Mediação exige pessoas diferentes');
$j=simI4();okI4(!i4Iniciar($j,'Jogador','interacoes_1',postI4('i4_mediar','Bianca',['alvo2'=>'Pessoa fora da casa'])),'Mediação exige dois ativos');
$j=simI4();okI4(!i4Iniciar($j,'Jogador','interacoes_1',postI4('i4_pessoal','Bianca',['i4_abordagem'=>'hack'])),'Rejeita abordagem falsa');
$j=simI4();$_SESSION['conhecimentos_jogador']=[['chave'=>'x','titulo'=>'Vencida','texto'=>'Segredo antigo','expira_rodada'=>2]];
okI4(!i4Iniciar($j,'Jogador','interacoes_1',postI4('i4_informacao','Bianca',['i4_conhecimento'=>'x'])),'Conhecimento vencido não é compartilhável');
$j=simI4();$_SESSION['i4_acordos']['Bianca']=['semana'=>4,'ate'=>5,'aceito'=>true];
okI4(i4BonusAlvo($j,'Bianca','Jogador','voto')<0 && i4BonusAlvo($j,'Bianca','Jogador','imunidade')>0,'Pacto temporário pesa em proteção e voto');
okI4(i4BonusAlvo($j,'Caio','Jogador','voto')===0.0 && i4BonusAlvo($j,'Bianca','Lara','voto')===0.0,'Pacto não afeta outros participantes');
$_SESSION['rodada']=6;okI4(i4BonusAlvo($j,'Bianca','Jogador','voto')===0.0,'Pacto expira');
$_SESSION['rodada']=4;$j[1]['eliminado']=true;okI4(i4BonusAlvo($j,'Bianca','Jogador','imunidade')===0.0,'Pacto não beneficia eliminado');
$j=simI4();$_SESSION['casais']=['Bianca'=>'Caio','Caio'=>'Bianca'];
okI4(i4Iniciar($j,'Jogador','interacoes_1',postI4('i4_romance')),'Pode convidar alguém sem interferir no relacionamento');
$tok=$_SESSION['i4_pendente']['token'];i4Responder($j,'Jogador','interacoes_1',$tok,0);
okI4((int)($j[0]['romances']['Bianca']??0)===0 && ($_SESSION['casais']['Bianca']??'')==='Caio','Romance respeita parceiro existente');
$j=simI4();i4Iniciar($j,'Jogador','interacoes_1',postI4('i4_conversar'));
$estado=$_SESSION;i4SincronizarCena('interacoes_1');okI4($_SESSION===$estado,'Atualizar a página não recria diálogo');
i4SincronizarCena('interacoes_2');okI4(empty($_SESSION['i4_pendente']),'A cena pendente velha é descartada ao mudar de fase');
$j=simI4();i4Iniciar($j,'Jogador','interacoes_1',postI4('i4_pessoal'));
$_SESSION['rodada']=5;i4SincronizarCena('interacoes_1');okI4(empty($_SESSION['i4_pendente']),'Save de outra semana não trava interações');
$j=simI4();$j[1]['relacoes']['Caio']['rivalidade']=70;$j[1]['relacoes']['Caio']['amizade']=0;$j[1]['relacoes']['Caio']['confianca']=0;
okI4(i4PreocupacaoReal($j,'Bianca','Jogador')==='Caio','Sondagem só menciona preocupação com rivalidade registrada');
$j=simI4();okI4(i4PreocupacaoReal($j,'Bianca','Jogador')==='','Sondagem não inventa rivais quando não existem');
$j=simI4();$_SESSION['conhecimentos_jogador']=[['chave'=>'c1','texto'=>'Conhecimento real','titulo'=>'Descoberta','expira_rodada'=>5,'confiabilidade'=>'alta']];
okI4(i4Iniciar($j,'Jogador','interacoes_1',postI4('i4_informacao','Bianca',['i4_conhecimento'=>'c1'])),'Compartilhamento exige conhecimento real');
i4Responder($j,'Jogador','interacoes_1',$_SESSION['i4_pendente']['token'],1);
okI4(count($_SESSION['fofocas_casa']??[])===1 && ($_SESSION['fofocas_casa'][0]['portador']??'')==='Bianca','NPC recebe informação que pode circular em fofocas');
$j=simI4();
okI4(i4Iniciar($j,'Jogador','interacoes_1',postI4('i4_pessoal','Bianca')), 'Primeira conversa com Bianca permitida');
i4Responder($j,'Jogador','interacoes_1',$_SESSION['i4_pendente']['token'],1);
unset($_SESSION['i4_resultado']);
okI4(i4Iniciar($j,'Jogador','interacoes_1',postI4('i4_conversar','Bianca')), 'Segunda conversa com a mesma pessoa na semana permitida');
i4Responder($j,'Jogador','interacoes_1',$_SESSION['i4_pendente']['token'],1);
okI4(count($_SESSION['i4_historico'])===2, 'Historico registra ambas as conversas');
$antes=$_SESSION['i4_historico'];
i4SincronizarCena('interacoes_1');
okI4($_SESSION['i4_historico']===$antes, 'Recarregar nao remove conversas concluidas');
echo "INTERAÇÕES 4.0 — $checks verificações aprovadas.\n";
