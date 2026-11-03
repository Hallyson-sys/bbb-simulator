<?php
/** Testes isolados dos Dilemas 2.0. Execute: php testes/dilemas_jogo_2.php */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$raiz=dirname(__DIR__);
if (!function_exists('mb_strtolower')) { function mb_strtolower(string $v, ?string $enc=null): string { return strtolower($v); } }
$_SESSION=[];
foreach(['utilitarios.php','relacoes.php','inteligencia_npc.php','objetivos_npc.php','eventos_convivencia.php','dilemas_jogo.php'] as $arquivo) require_once $raiz.'/includes/logica/'.$arquivo;
$contagem=0;
function djTest(bool $ok,string $nome): void { global $contagem; if (!$ok) throw new RuntimeException('FALHOU: '.$nome); $contagem++; echo "[OK] $nome\n"; }
function djFixture(): array {
    $_SESSION=['rodada'=>4,'meu_nome'=>'Hallyson','fase_semana'=>'interacoes_1','evento_extra'=>[],
        'relacoes_jogador'=>['Bianca'=>55,'Caio'=>-40,'Lara'=>20,'Guilherme'=>7]];
    $jogadores=[];
    foreach(['Hallyson','Bianca','Caio','Lara','Guilherme'] as $nome) {
        $jogadores[]=['nome'=>$nome,'personalidade'=>'Neutro','eliminado'=>false,
            'alianca'=>in_array($nome,['Hallyson','Bianca','Lara'],true)?'Grupo A':'',
            'relacoes'=>[],'estatisticas'=>[],'status'=>[]];
    }
    $_SESSION['jogadores']=$jogadores;
    return $jogadores;
}
$catalogo=djBiblioteca();
djTest(count($catalogo)>=12,'Biblioteca tem pelo menos doze dilemas');
foreach($catalogo as $id=>$c) {
    djTest(!empty($c['titulo'])&&count($c['opcoes'])>=3,'Dilema completo: '.$id);
    foreach($c['opcoes'] as $op) djTest(isset($op['efeito'],$op['delta'],$op['memoria'],$op['reacao']),'Resposta com efeito real: '.$id);
}
$j=djFixture();
$c=djCandidatos($j,'Hallyson','interacoes_1');
djTest(count($c)>=6,'Candidatos contextuais em interações');
djTest(in_array('traição_suspeita',array_column($c,'id'),true),'Conflito baseado em integrantes reais da aliança');
$_SERVER['REQUEST_METHOD']='POST';$_SESSION['dev_dj_forcar']=true;
djTentarGerar($j,'Hallyson','interacoes_1');
djTest(empty($_SESSION['dj_ativo']),'POST de ação antiga não abre dilema sobreposto');
$_SERVER['REQUEST_METHOD']='GET';djTentarGerar($j,'Hallyson','interacoes_1');
$dilema=$_SESSION['dj_ativo']??null;
djTest(is_array($dilema),'DEV pode gerar um dilema real');
djTest(($_SESSION['ec_tentado_interacoes_1_4']??false)===true,'Dilema impede evento especial simultâneo');
$anterior=$_SESSION;
djTentarGerar($j,'Hallyson','interacoes_1');
djTest($_SESSION===$anterior,'Recarregar não troca a cena');
djTest(!djResolver($j,'Hallyson','interacoes_1','token_errado',0),'Token inválido não aplica escolha');
djTest(!djResolver($j,'Hallyson','interacoes_1',$dilema['token'],99),'Índice inválido não aplica escolha');
djTest(!djResolver($j,'Hallyson','festa',$dilema['token'],0),'Fase diferente não aplica escolha');
$memAntes=count((array)($_SESSION['dj_historico']??[]));
djTest(djResolver($j,'Hallyson','interacoes_1',$dilema['token'],0),'Escolha válida é aplicada');
djTest(empty($_SESSION['dj_ativo'])&&!empty($_SESSION['dj_resultado']),'Após escolha aparece desfecho');
djTest(count($_SESSION['dj_historico'])===$memAntes+1,'Histórico registra uma escolha');
$estado=$_SESSION;
djTest(!djResolver($j,'Hallyson','interacoes_1',$dilema['token'],0),'Envio repetido é rejeitado');
djTest($_SESSION===$estado,'Envio repetido não altera memória nem relações');
djTentarGerar($j,'Hallyson','interacoes_2');
djTest(count($_SESSION['dj_historico'])===1,'Nunca cria segundo dilema na mesma semana');
$j=djFixture(); $_SESSION['dj_compromissos']=['Bianca'=>['tipo'=>'protecao','semana'=>3,'ate'=>4,'aceito'=>true]];
djTest(djBonusAlvo($j,'Bianca','Hallyson','voto')<0,'Pacto protege do voto moderadamente');
djTest(djBonusAlvo($j,'Bianca','Hallyson','imunidade')>0,'Pacto favorece proteção');
djTest(djBonusAlvo($j,'Caio','Hallyson','voto')===0.0,'Não interfere em outros NPCs');
djTest(djBonusAlvo($j,'Bianca','Lara','voto')===0.0,'Não interfere em outros alvos');
$_SESSION['rodada']=5;
djTest(djBonusAlvo($j,'Bianca','Hallyson','voto')===0.0,'Pacto expira corretamente');
$_SESSION['rodada']=4;$j[1]['eliminado']=true;
djTest(djBonusAlvo($j,'Bianca','Hallyson','voto')===0.0,'Eliminados não influenciam votação');
djTest(!in_array('Bianca',array_column(djCandidatos($j,'Hallyson','festa'),'npc'),true),'Eliminados não participam de dilemas');
$j=djFixture();$_SESSION['modo_espectador']=true;$_SESSION['dev_dj_forcar']=true;
djTentarGerar($j,'Hallyson','interacoes_1');
djTest(empty($_SESSION['dj_ativo']),'Modo espectador não recebe dilemas da casa');
$j=djFixture();$_SESSION['dev_dj_forcar']=true;$_SESSION['conhecimentos_jogador']=[
    ['chave'=>'c1','texto'=>'Bianca foi vista discutindo uma possível indicação.','expirado'=>false,'tipo'=>'leitura','confiabilidade'=>'alta']
];
djTest(in_array('fofoca_festa',array_column(djCandidatos($j,'Hallyson','festa'),'id'),true),'Decisão sobre segredo só aparece quando existe informação conhecida');
$_SESSION['conhecimentos_jogador']=[];
djTest(!in_array('fofoca_festa',array_column(djCandidatos($j,'Hallyson','festa'),'id'),true),'Não inventa conhecimento secreto');
echo "DILEMAS 2.0 — $contagem verificações aprovadas.\n";
