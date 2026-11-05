<?php
/** Rodar: php testes/relacionamentos_amorosos_3.php — estado simulado, sem saves. */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$raiz=dirname(__DIR__);
foreach(['utilitarios.php','relacoes.php','romance.php','historico_temporada.php','interacoes_4.php','romance_3.php'] as $f)
    require_once $raiz.'/includes/logica/'.$f;
require_once $raiz.'/includes/helpers/render.php';
$ok=0;
function checkR3(bool $pass,string $label):void {global $ok;if(!$pass)throw new RuntimeException('FALHOU: '.$label);$ok++;echo "[OK] $label\n";}
function fixtureR3():array {
    $_SESSION=['meu_nome'=>'Hallyson','rodada'=>6,'fase_semana'=>'interacoes_1','acoes_restantes'=>2,
        'relacoes_jogador'=>['Miguel M.'=>55], 'evento_extra'=>[],
        'casais'=>['Hallyson'=>'Miguel M.','Miguel M.'=>'Hallyson']];
    $nomes=['Hallyson','Miguel M.','Bianca','Guilherme'];$j=[];
    foreach($nomes as $n){$rel=[];foreach($nomes as $x)if($x!==$n)$rel[$x]=['amizade'=>50,'confianca'=>45,'rivalidade'=>0];
        $j[]=['nome'=>$n,'romances'=>[],'relacoes'=>$rel,'status'=>[],'eliminado'=>false];}
    return $j;
}
function htmlR3(array $j):string {ob_start();render('components/romance_3',['jogadores'=>$j,'meuNome'=>'Hallyson','fase'=>'interacoes_1']);return ob_get_clean();}
$j=fixtureR3();
checkR3(rom3Parceiro($j,'Hallyson')==='Miguel M.','Namoro oficial encontrado em save antigo sem pontuação');
checkR3(str_contains(htmlR3($j),'Vida a Dois') && str_contains(htmlR3($j),'Miguel M.'),'Card do casal abre sem aviso PHP');
checkR3(!str_contains(htmlR3($j),'Admitir que tem interesse'),'Casal oficial não recebe abordagem de primeiro flerte');
checkR3(!isset(rom3Temas($j,'Hallyson','Miguel M.')['ciumes']),'Sem conflito real não inventa ciúmes');
$j[1]['relacoes']['Hallyson']['rivalidade']=40;$j[1]['relacoes']['Hallyson']['confianca']=0;
checkR3(isset(rom3Temas($j,'Hallyson','Miguel M.')['ciumes']),'Insegurança só aparece com tensão existente');
$j=fixtureR3();$antes=$j;
checkR3(rom3Iniciar($j,'Hallyson','interacoes_1','apoio'),'Conversa especial pode começar');
checkR3($j===$antes && $_SESSION['acoes_restantes']===2,'A abertura não consome interação nem altera relação');
checkR3(!rom3Iniciar($j,'Hallyson','interacoes_1','carinho'),'Não cria cenas simultâneas');
checkR3(str_contains(htmlR3($j),'Como você quer responder?'),'Opções de resposta renderizam');
$token=(string)$_SESSION['rom3_pendente']['token'];
checkR3(!rom3Responder($j,'Hallyson','interacoes_1','invalido',0),'Token inválido não altera estado');
checkR3(rom3Responder($j,'Hallyson','interacoes_1',$token,0),'Resposta válida encerra o momento');
$s=$_SESSION;$j2=$j;
checkR3(!rom3Responder($j,'Hallyson','interacoes_1',$token,0) && $j===$j2 && $_SESSION===$s,'Reenvio da resposta não duplica efeitos');
checkR3(($_SESSION['casais']['Hallyson']??'')==='Miguel M.','Momento de apoio não reinicia nem encerra namoro');
checkR3($_SESSION['acoes_restantes']===2,'Não desconta as duas interações restantes');
checkR3(str_contains(htmlR3($j),'Voltar às Interações'),'Tela de desfecho renderiza');
unset($_SESSION['rom3_resultado']);
checkR3(!rom3Iniciar($j,'Hallyson','interacoes_1','futuro'),'Uma cena especial na semana, sem bloquear as ações normais');
$_SESSION['rodada']=7;rom3Sincronizar($j,'Hallyson','interacoes_1');
checkR3(rom3Iniciar($j,'Hallyson','interacoes_1','decisao'),'Na semana seguinte pode falar do futuro do namoro');
$t=(string)$_SESSION['rom3_pendente']['token'];
checkR3(rom3Responder($j,'Hallyson','interacoes_1',$t,2) && isset($_SESSION['rom3_confirmacao']),'Escolher término solicita confirmação');
checkR3(rom3Parceiro($j,'Hallyson')==='Miguel M.','Nenhuma separação antes da confirmação');
checkR3(!rom3ConfirmarFim($j,'Hallyson','interacoes_1','invalido','confirmar'),'Confirmação com token inválido é rejeitada');
checkR3(rom3ConfirmarFim($j,'Hallyson','interacoes_1',$t,'cancelar'),'Pode cancelar término');
checkR3(rom3Parceiro($j,'Hallyson')==='Miguel M.','Cancelar mantém namoro existente');
$j=fixtureR3();rom3Iniciar($j,'Hallyson','interacoes_1','decisao');$t=(string)$_SESSION['rom3_pendente']['token'];
rom3Responder($j,'Hallyson','interacoes_1',$t,2);
checkR3(rom3ConfirmarFim($j,'Hallyson','interacoes_1',$t,'confirmar'),'Confirmar termina namoro oficial');
checkR3(rom3Parceiro($j,'Hallyson')==='' && empty($_SESSION['casais']['Miguel M.']),'Término apaga os dois lados do casal');
checkR3(str_contains(htmlR3($j),'MOMENTO CONCLUÍDO'),'Desfecho de término ainda aparece após separar');
checkR3(count($_SESSION['evento_extra'])===1,'Só o término público vai ao Ao Vivo');
$j=fixtureR3();$_SESSION['rom3_pendente']=['semana'=>5,'fase'=>'interacoes_1','par'=>'Miguel M.','token'=>str_repeat('c',32)];
rom3Sincronizar($j,'Hallyson','interacoes_1');
checkR3(empty($_SESSION['rom3_pendente']),'Diálogo de semana anterior expira sem travar save');
$j=fixtureR3();$j[1]['eliminado']=true;
checkR3(!rom3Iniciar($j,'Hallyson','interacoes_1','carinho'),'Eliminado não abre novo momento de casal');
$j=fixtureR3();$_SESSION['modo_espectador']=true;
checkR3(!rom3Iniciar($j,'Hallyson','interacoes_1','carinho'),'Modo espectador não participa das cenas da casa');
$j=fixtureR3();$_SESSION['casais']['Bianca']='Guilherme';$_SESSION['casais']['Guilherme']='Bianca';
$_SESSION['fase_semana']='interacoes_2';
rom3ProcessarNPCs($j,'Hallyson','interacoes_2');$p=$_SESSION['rom3_estado']['processados'][6]??false;
$estado=$_SESSION;$c=count((array)($_SESSION['rom3_estado']['publicos']??[]));
checkR3($p && !rom3ProcessarNPCs($j,'Hallyson','interacoes_2') && count($_SESSION['rom3_estado']['publicos'])===$c,'F5 não duplica cenas entre NPCs');
checkR3(rom3Parceiro($j,'Hallyson')==='Miguel M.','Casal do protagonista não é alterado pela simulação NPC');
// Confere o mesmo componente via render(), não por variável herdada do escopo.
$j=fixtureR3();set_error_handler(static function($n,$msg){throw new ErrorException($msg,0,$n);});
$html=htmlR3($j);
restore_error_handler();
checkR3(str_contains($html,'Momento') && str_contains($html,'namoro'),'Render real com escopo isolado não emite warnings');
echo "RELACIONAMENTOS AMOROSOS 3.0 — $ok verificações aprovadas.\n";
