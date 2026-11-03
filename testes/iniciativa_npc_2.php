<?php
/** php testes/iniciativa_npc_2.php — cenários isolados, sem saves do jogador. */
declare(strict_types=1);
if (PHP_SAPI!=='cli') exit('Somente CLI.');
$_SESSION=[];
$raiz=dirname(__DIR__);
require_once $raiz.'/includes/logica_jogo.php';
require_once $raiz.'/includes/logica/relacoes.php';
require_once $raiz.'/includes/logica/romance.php';
require_once $raiz.'/includes/logica/interacoes_4.php';
require_once $raiz.'/includes/logica/iniciativa_npc_2.php';
$ok=0;
function prova(bool $cond,string $texto):void { global $ok; if(!$cond) {fwrite(STDERR,"[FALHA] $texto\n");exit(1);} ++$ok;echo "[OK] $texto\n"; }
function elencoIn2():array {
    $nomes=['Jogador','Miguel','Bianca','Caio'];$out=[];
    foreach($nomes as $n){$rels=[]; foreach($nomes as $m)if($m!==$n) $rels[$m]=['amizade'=>34,'rivalidade'=>2,'confianca'=>30];
        $out[]=['nome'=>$n,'eliminado'=>false,'status'=>[],'relacoes'=>$rels,'romances'=>[]];}
    return $out;
}
function simularConvite(array $jogadores,string $npc,string $tipo='conversa',string $fase='interacoes_1'):array {
    $e=i4EtapaRomance($jogadores,'Jogador',$npc);$d=in2Dados($npc,$tipo,$e);
    return ['token'=>bin2hex(random_bytes(12)),'semana'=>in2Semana(),'fase'=>$fase,'npc'=>$npc,'tipo'=>$tipo,
        'etapa'=>$e,'titulo'=>$d[1],'icone'=>$d[0],'fala'=>$d[2],'local'=>'no jardim','adiado'=>false];
}
$jogadores=elencoIn2();$fase='interacoes_1';
$_SESSION['rodada']=5;$_SESSION['meu_nome']='Jogador';$_SESSION['fase_semana']=$fase;
$_SESSION['casais']=['Jogador'=>'Miguel','Miguel'=>'Jogador'];
$_SESSION['relacoes_jogador']=['Miguel'=>38,'Bianca'=>20,'Caio'=>18];
prova(in2Disponivel($jogadores,'Jogador',$fase),'Jogador disponível nas Interações');
prova(!in2Disponivel($jogadores,'Jogador','paredao'),'Não gerar convites no Paredão');
prova(i4EtapaRomance($jogadores,'Jogador','Miguel')==='namoro','Detecta casal existente');
$d=in2Dados('Miguel','casal','namoro');
prova(str_contains($d[2],'namoro')&&str_contains($d[1],'dois'),'Falas de casal sem reiniciar flerte');
prova(!str_contains(implode(' ', $d[3]),'Admitir que tem interesse'),'Sem opção de novo interesse para namorados');
$_SESSION['in2_convite']=simularConvite($jogadores,'Miguel','casal');
$token=$_SESSION['in2_convite']['token'];
prova(!in2ResponderConvite($jogadores,'Jogador',$fase,'invalido','aceitar'),'Token inválido bloqueado');
prova(in2ResponderConvite($jogadores,'Jogador',$fase,$token,'adiar'),'Pode adiar');
prova(!empty($_SESSION['in2_convite']['adiado']),'Adiamento persistente');
prova(in2ResponderConvite($jogadores,'Jogador',$fase,$token,'retomar'),'Pode retomar');
prova(in2ResponderConvite($jogadores,'Jogador',$fase,$token,'aceitar'),'Pode aceitar convite');
prova(empty($_SESSION['in2_convite']) && !empty($_SESSION['in2_conversa']),'Convite vira conversa única');
prova(!in2ResponderConvite($jogadores,'Jogador',$fase,$token,'aceitar'),'Replay do convite bloqueado');
prova(!in2Concluir($jogadores,'Jogador',$fase,$token,95),'Resposta inválida bloqueada');
prova(!in2Concluir($jogadores,'Jogador',$fase,'token-alterado',0),'Replay com token errado bloqueado');
$antes=$_SESSION['relacoes_jogador']['Miguel'];
prova(in2Concluir($jogadores,'Jogador',$fase,$token,0),'Diálogo com namorado concluído');
prova($_SESSION['relacoes_jogador']['Miguel']>$antes,'Afinidade alterada conforme escolha');
prova(($_SESSION['casais']['Jogador']??'')==='Miguel','Namoro não foi recriado nem desfeito');
prova(!in2Concluir($jogadores,'Jogador',$fase,$token,0),'Resposta repetida não aplica efeito');
prova(count($_SESSION['in2_historico'])===1,'Histórico registra uma única conclusão');
prova(count($_SESSION['evento_extra'])===1 && str_contains($_SESSION['evento_extra'][0],'relacionamento'),'Ao Vivo observável e coerente com casal');
prova(!isset($_SESSION['acoes_restantes']),'Convite não consome interação comum');
unset($_SESSION['in2_resultado']);
$_SESSION['in2_convite']=simularConvite($jogadores,'Bianca','acordo');
$token=$_SESSION['in2_convite']['token'];
prova(in2ResponderConvite($jogadores,'Jogador',$fase,$token,'aceitar'),'NPC pode propor acordo');
prova(in2Concluir($jogadores,'Jogador',$fase,$token,2),'Jogador pode recusar acordo');
prova(empty($_SESSION['i4_acordos']['Bianca']),'Recusa não cria pacto');
unset($_SESSION['in2_resultado']);
$_SESSION['in2_convite']=simularConvite($jogadores,'Bianca','desabafo');
$token=$_SESSION['in2_convite']['token'];
prova(in2ResponderConvite($jogadores,'Jogador',$fase,$token,'recusar'),'Recusa de conversa livre');
prova(empty($_SESSION['in2_convite']),'Recusa exclui convite');
$_SESSION['in2_convite']=simularConvite($jogadores,'Caio','conversa');
$_SESSION['fase_semana']='interacoes_2';
in2Sincronizar($jogadores,'Jogador','interacoes_2');
prova(empty($_SESSION['in2_convite']),'Mudança de fase expira convite sem punição');
$_SESSION['in2_convite']=simularConvite($jogadores,'Caio','conversa','interacoes_2');
foreach($jogadores as &$j) if($j['nome']==='Caio')$j['eliminado']=true;unset($j);
in2Sincronizar($jogadores,'Jogador','interacoes_2');
prova(empty($_SESSION['in2_convite']),'Eliminado não pode convidar');
$_SESSION['modo_espectador']=true;
prova(!in2Disponivel($jogadores,'Jogador','interacoes_2'),'Modo espectador bloqueia convites');
unset($_SESSION['modo_espectador']);
$_SESSION['in2_tentativas']=[];$_SESSION['in2_contagem']=[];$_SESSION['in2_usados_npc']=[];
$gerou=in2TalvezGerar($jogadores,'Jogador','interacoes_2');
prova(!empty($_SESSION['in2_tentativas']['5|interacoes_2']),'Tentativa é marcada no primeiro acesso');
$antesConvite=$_SESSION['in2_convite']??null;
prova(!in2TalvezGerar($jogadores,'Jogador','interacoes_2'),'Refresh não gera novo convite');
prova(($_SESSION['in2_convite']??null)===$antesConvite,'Refresh preserva estado e token');
prova($gerou?count((array)$antesConvite)>0:empty($antesConvite),'Sorteio gera convite válido ou nenhum');
echo "\n$ok verificações concluídas.\n";
