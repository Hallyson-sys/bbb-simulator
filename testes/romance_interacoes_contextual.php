<?php
/** Regressão de contexto amoroso: php testes/romance_interacoes_contextual.php */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
if(!function_exists('mb_strtolower')){function mb_strtolower(string $s,?string $e=null):string{return strtolower($s);}}
if(!function_exists('mb_stripos')){function mb_stripos(string $h,string $n,int $o=0,?string $e=null):int|false{return stripos($h,$n,$o);}}
foreach(['utilitarios.php','relacoes.php','romance.php','inteligencia_npc.php','interacoes_4.php'] as $f)require_once __DIR__.'/../includes/logica/'.$f;
$passed=0;
function checkRom(bool $yes,string $description):void {global $passed;if(!$yes)throw new RuntimeException('FALHOU: '.$description);$passed++;echo "[OK] $description\n";}
function fixtureRom(): array {
    $_SESSION=['rodada'=>6,'fase_semana'=>'interacoes_1','meu_nome'=>'Jogador','acoes_restantes'=>3,'relacoes_jogador'=>['Miguel M.'=>42,'Bianca'=>35]];
    $people=['Jogador','Miguel M.','Bianca'];$out=[];
    foreach($people as $n){$r=[];foreach($people as $m)if($m!==$n)$r[$m]=['amizade'=>50,'confianca'=>45,'rivalidade'=>1];
        $out[]=['nome'=>$n,'eliminado'=>false,'relacoes'=>$r,'romances'=>[],'status'=>[], 'popularidade'=>50];}
    return $out;
}
function postRom(string $alvo='Miguel M.', string $assunto='flertar',string $acao='i4_romance'):array{
    return ['acao'=>$acao,'alvo'=>$alvo,'i4_assunto'=>$assunto,'i4_abordagem'=>'sincera'];
}
$j=fixtureRom();registrarCasal('Jogador','Miguel M.');
checkRom(estaNamorandoCom('Jogador','Miguel M.'),'O casal existe no save original');
checkRom(i4EtapaRomance($j,'Jogador','Miguel M.')==='namoro','O status de namoro prevalece sobre romance numérico zero');
checkRom(i4TituloRomance('namoro')==='Cuidar do namoro','O título já não apresenta nova conquista');
checkRom(isset(i4Assuntos('i4_romance','namoro')['carinho']) && !isset(i4Assuntos('i4_romance','namoro')['flertar']),'Namoro mostra temas adequados');
checkRom(str_contains(i4Opcoes('i4_romance','namoro')[0][0],'carinho'),'Respostas do namoro são afetivas, sem declarações iniciais');
checkRom(i4Iniciar($j,'Jogador','interacoes_1',postRom()),'O formulário antigo de flerte é convertido para assunto de casal');
$c=$_SESSION['i4_pendente'];
checkRom($c['romance_etapa']==='namoro' && $c['assunto']==='carinho','A cena é marcada como namoro e assunto coerente');
checkRom(!str_contains($c['fala'],'ir com calma') && !str_contains($c['fala'],'ficado mais próximo'),'Miguel não pede para ir com calma como se fossem desconhecidos');
$antesRom=$j[0]['romances']['Miguel M.']??0;
$evento=i4Responder($j,'Jogador','interacoes_1',$c['token'],0);
checkRom(str_contains($evento,'o relacionamento deles') && !str_contains($evento,'amizade'),'Ao Vivo descreve o namoro sem expor o conteúdo privado');
checkRom(($_SESSION['casais']['Jogador']??null)==='Miguel M.' && ($_SESSION['casais']['Miguel M.']??null)==='Jogador','A interação não recria nem termina o namoro');
checkRom(($j[0]['romances']['Miguel M.']??0)>$antesRom,'Carinho pode fortalecer o romance do casal');
checkRom(!str_contains($_SESSION['i4_resultado']['texto'],'descobrir aos poucos o que sente'),'O desfecho fala do namoro real');
$estado=$_SESSION;$estadoJog=$j;
checkRom(i4Responder($j,'Jogador','interacoes_1',$c['token'],0)==='' && $_SESSION===$estado && $estadoJog===$j,'Reenvio da resposta não duplica efeitos');
unset($_SESSION['i4_resultado']);
checkRom(i4Iniciar($j,'Jogador','interacoes_1',postRom('Miguel M.','relacao')),'Pode conversar novamente com o namorado na mesma semana');
checkRom(str_contains($_SESSION['i4_pendente']['fala'],'namoro') || str_contains($_SESSION['i4_pendente']['fala'],'jogo') || str_contains($_SESSION['i4_pendente']['fala'],'junto'),'Novo diálogo leva em conta o relacionamento');
$j=fixtureRom();$_SESSION['casais']=['Jogador'=>'Miguel M.'];
checkRom(i4EtapaRomance($j,'Jogador','Miguel M.')==='namoro','Save antigo com só um lado do casal é reconhecido');
$j=fixtureRom();$_SESSION['casais']=['Miguel M.'=>'Bianca','Bianca'=>'Miguel M.'];
checkRom(i4EtapaRomance($j,'Jogador','Miguel M.')==='comprometido','NPC comprometido com outra pessoa não está disponível para flerte');
checkRom(i4Iniciar($j,'Jogador','interacoes_1',postRom()),'Assunto antigo é tratado com limites mesmo quando NPC está comprometido');
$c=$_SESSION['i4_pendente'];
checkRom($c['assunto']==='limites' && str_contains($c['fala'],'relacionamentos'),'O diálogo recusa o flerte em respeito ao parceiro');
i4Responder($j,'Jogador','interacoes_1',$c['token'],0);
checkRom(($j[0]['romances']['Miguel M.']??0)===0,'Não aumenta romance com NPC comprometido');
$j=fixtureRom();$_SESSION['casais']=['Jogador'=>'Bianca','Bianca'=>'Jogador'];
checkRom(i4EtapaRomance($j,'Jogador','Miguel M.')==='comprometido','Jogador comprometido com outra pessoa não flerta automaticamente');
$j=fixtureRom();
checkRom(i4EtapaRomance($j,'Jogador','Miguel M.')==='inicio','Sem casal ou pontos, a interação começa como flerte');
checkRom(str_contains(i4Opcoes('i4_romance','inicio')[0][0],'interesse'),'Solteiros mantêm opções de aproximação inicial');
$j[0]['romances']['Miguel M.']=67;
checkRom(i4EtapaRomance($j,'Jogador','Miguel M.')==='aproximacao','Interesse alto sem namoro é apenas aproximação');
checkRom(i4Iniciar($j,'Jogador','interacoes_1',postRom('Miguel M.','sentimentos')),'O casal em potencial pode falar de sentimentos');
$c=$_SESSION['i4_pendente'];i4Responder($j,'Jogador','interacoes_1',$c['token'],2);
checkRom(empty($_SESSION['casais']),'Conversar sobre sentimentos não inicia namoro sem pedido');
$j=fixtureRom();registrarCasal('Jogador','Miguel M.');
checkRom(i4Iniciar($j,'Jogador','interacoes_1',postRom('Miguel M.','relacao','i4_conversar')),'Conversar de verdade continua disponível para namorados');
$c=$_SESSION['i4_pendente'];
checkRom(str_contains($c['fala'],'gente') && !str_contains($c['fala'],'como amizade'),'Fala comum sobre a relação também considera o namoro');
$evento=i4Responder($j,'Jogador','interacoes_1',$c['token'],1);
checkRom(str_contains($evento,'o relacionamento deles'),'Ao Vivo não reduz o namoro a amizade');
$j=fixtureRom();registrarCasal('Jogador','Miguel M.');
$_SESSION['i4_pendente']=['acao'=>'i4_romance','alvo'=>'Miguel M.','outro'=>'','assunto'=>'flertar','abordagem'=>'sincera',
    'local'=>'no jardim','fase'=>'interacoes_1','semana'=>6,'token'=>'cena_antiga','fala'=>'Eu quero ir com calma'];
$meuNome='Jogador';$jogadores=$j;
ob_start();include __DIR__.'/../includes/components/interacoes_4.php';$html=ob_get_clean();
checkRom(str_contains($html,'Cuidar do namoro') && str_contains($html,'Demonstrar carinho') && !str_contains($html,'Admitir que tem interesse'),'Cena pendente de save antigo é apresentada como namoro');
checkRom($_SESSION['i4_pendente']['romance_etapa']==='namoro' && $_SESSION['i4_pendente']['assunto']==='carinho','Cena antiga é adaptada uma vez sem perda de estado');
$j=fixtureRom();registrarCasal('Jogador','Miguel M.');$meuNome='Jogador';$acaoSelecionada='i4_romance';$jogadores=$j;
ob_start();include __DIR__.'/../includes/components/interacoes_4_form.php';$html=ob_get_clean();
checkRom(str_contains($html,'data-romance-etapa=&quot;')===false && str_contains($html,'data-romance-etapa="namoro"'),'Formulário sinaliza namorado para atualização dos temas no navegador');
checkRom(str_contains($html,'i4_estado_romance') && str_contains($html,'assunto.replaceChildren()'),'Formulário atualiza assuntos após escolher o parceiro');
echo "CONTEXTO ROMÂNTICO — $passed verificações aprovadas.\n";
