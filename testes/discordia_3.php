<?php
/** Executar: php testes/discordia_3.php — usa apenas fixtures, nunca abre saves. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
if (!function_exists('mb_strtolower')) { function mb_strtolower($s, $enc=null) {return strtolower((string)$s);} }
$raiz=dirname(__DIR__);
foreach (['utilitarios.php','historico_temporada.php','relacoes.php','reta_final.php','inteligencia_npc.php','consequencias_sociais.php','discordia.php','discordia_3.php'] as $arq)
    require_once $raiz.'/includes/logica/'.$arq;
$ok=0;
function ver3(bool $cond,string $caso): void {global $ok;if(!$cond){fwrite(STDERR,"[FALHA] $caso\n");exit(1);} $ok++;echo "[OK] $caso\n";}
function elencoD3(int $quantidade=10):array {
    $p=[]; for($i=1;$i<=$quantidade;$i++){
        $nome=$i===1?'Jogador':'NPC'.$i;$rel=[];
        for($j=1;$j<=$quantidade;$j++) if($i!==$j){$alvo=$j===1?'Jogador':'NPC'.$j;$rel[$alvo]=['amizade'=>10,'rivalidade'=>9,'confianca'=>6];}
        $p[]=['nome'=>$nome,'personalidade'=>'Estrategista','relacoes'=>$rel,'status'=>[],'romances'=>[],'popularidade'=>52, 'eliminado'=>false];
    }
    return $p;
}
function resetD3(int $n=10):array {
    $_SESSION=['meu_nome'=>'Jogador','fase_semana'=>'discordia','rodada'=>5,'evento_extra'=>[], 'relacoes_jogador'=>['NPC2'=>10]];
    return elencoD3($n);
}
$j=resetD3();
ver3(d3ValidaEscolha(['alvo_discordia'=>'NPC2','intensidade'=>'leve'],$j,'Jogador','falso')['alvo']==='NPC2','Apontamento válido em tema negativo');
ver3(d3ValidaEscolha(['alvo_discordia'=>'NPC2','intensidade'=>'hack'],$j,'Jogador','falso')===null,'Intensidade adulterada rejeitada');
ver3(d3ValidaEscolha(['alvo_discordia'=>'Jogador','intensidade'=>'leve'],$j,'Jogador','falso')===null,'Não pode atacar a si mesmo');
ver3(d3ValidaEscolha(['alvo_discordia'=>'Fantasma','intensidade'=>'leve'],$j,'Jogador','falso')===null,'Alvo fora da casa rejeitado');
ver3(d3ValidaEscolha(['alvo_discordia'=>'NPC2'],$j,'Jogador','aliado')['positivo']===true,'Tema aliado produz debate positivo');
ver3(d3ValidaEscolha(['podio_2'=>'NPC2','podio_3'=>'NPC3'],$j,'Jogador','podio')!==null,'Pódio clássico continua válido');
ver3(d3ValidaEscolha(['podio_2'=>'NPC2','podio_3'=>'NPC2'],$j,'Jogador','podio')===null,'Pódio não aceita nomes duplicados');
$cats=array_keys(categoriasTemaDiscordia('filme_bbb'));
$cpost=[];$i=2;foreach($cats as $cat){$cpost['categoria_'.$cat]='NPC'.$i;$i++;}
ver3(d3ValidaEscolha($cpost,$j,'Jogador','filme_bbb')!==null,'Filme BBB mantém categorias');
$cdup=$cpost;$cdup['categoria_'.$cats[1]]='NPC2';
ver3(d3ValidaEscolha($cdup,$j,'Jogador','filme_bbb')===null,'Categorias não aceitam duplicação');
$_SESSION['historico_temporada']=[
    ['tipo'=>'confessionario','rodada'=>3,'participantes'=>['Jogador','NPC2'],'descricao'=>'Voto secreto'],
    ['tipo'=>'discussao','rodada'=>3,'participantes'=>['Jogador','NPC2'],'descricao'=>'Discutiram na cozinha'],
    ['tipo'=>'discussao','rodada'=>7,'participantes'=>['Jogador','NPC2'],'descricao'=>'Acontecimento futuro'],
    ['tipo'=>'vip_xepa','rodada'=>4,'participantes'=>['Jogador','NPC3'],'descricao'=>'VIP com outra pessoa'],
];
$f=d3FatosPublicos('Jogador','NPC2',5);
ver3(count($f)===1 && $f[0]['descricao']==='Discutiram na cozinha','Somente fatos públicos e passados aparecem');
$p=['semana'=>5,'token'=>'unico','alvo'=>'NPC2','tema'=>'falso','positivo'=>false,'fatos'=>$f];
ver3(isset(d3OpcoesArgumento($p)['fato']),'Argumento factual disponível com registro verdadeiro');
$p['fatos']=[];
ver3(!isset(d3OpcoesArgumento($p)['fato']),'Sem fatos não inventa justificativa');
ver3(isset(d3OpcoesTreplica(false)['sustentar']) && !isset(d3OpcoesTreplica(true)['sustentar']),'Tréplicas dependem do tom do tema');
$r=d3RespostaNPC($j,$p,'convivencia');
ver3(!empty($r['fala']) && !empty($r['tom']),'Resposta de NPC gerada sem revelar segredo');
$_SESSION['jogadores']=$j;
// Efeito idempotente: não dá afinidade/registro duas vezes.
$antes=(int)$_SESSION['relacoes_jogador']['NPC2'];
$a=d3AplicarReplica($j,$p,'sustentar','Jogador');
$depois=(int)$_SESSION['relacoes_jogador']['NPC2'];
ver3(!empty($a['aplicado']) && $depois < $antes,'Sustentar crítica tem consequência moderada');
$eventos=count((array)($_SESSION['historico_temporada']??[]));
$b=d3AplicarReplica($j,$p,'sustentar','Jogador');
ver3(empty($b['aplicado']) && $depois===(int)$_SESSION['relacoes_jogador']['NPC2'] && $eventos===count($_SESSION['historico_temporada']),'Reenvio não repete efeitos nem histórico');
$_SESSION['d3_pendente']=$p;
d3Sincronizar($j);
ver3(isset($_SESSION['d3_pendente']),'F5 mantém debate na mesma semana');
$_SESSION['fase_semana']='interacoes_3';d3Sincronizar($j);
ver3(empty($_SESSION['d3_pendente']),'Mudança de fase limpa debate antigo');
$_SESSION['fase_semana']='discordia';$_SESSION['d3_pendente']=$p;
$_SESSION['rodada']=6;d3Sincronizar($j);
ver3(empty($_SESSION['d3_pendente']),'Mudança de semana limpa debate antigo');
$_SESSION['fase_semana']='discordia';$_SESSION['rodada']=5;
foreach($j as &$npc) if($npc['nome']==='NPC2') $npc['eliminado']=true; unset($npc);
ver3(d3ValidaEscolha(['alvo_discordia'=>'NPC2'],$j,'Jogador','falso')===null,'Eliminado não pode ser apontado');
$_SESSION['d3_pendente']=$p; d3Sincronizar($j);
ver3(empty($_SESSION['d3_pendente']),'Eliminação invalida debate pendente');
ver3(bbbRetaFinalSemDiscordia(elencoD3(8)),'Top 8 termina Discórdia');
ver3(str_contains(d3ReacaoTestemunha(elencoD3(10),'Jogador',['alvo'=>'NPC2']),'acompanhou o debate'), 'Testemunha produz reação pública contextual');
$meuNome='Jogador';$jogadores=resetD3();$_SESSION['tema_discordia']='falso';
$_SESSION['d3_pendente']=['semana'=>5,'etapa'=>'argumento','alvo'=>'NPC2','tema'=>'falso','token'=>'abc123','positivo'=>false,'fatos'=>[]];
ob_start();include $raiz.'/includes/components/painel/jogo_discordia.php';$html=ob_get_clean();
ver3(str_contains($html,'d3_argumentar') && str_contains($html,'NPC2'),'Renderiza a tela do argumento');
$_SESSION['d3_pendente']['etapa']='treplica';$_SESSION['d3_pendente']['fala']='É sobre o jogo';$_SESSION['d3_pendente']['resposta']=['fala'=>'Eu tenho meu lado da história'];
ob_start();include $raiz.'/includes/components/painel/jogo_discordia.php';$html=ob_get_clean();
ver3(str_contains($html,'d3_treplicar') && str_contains($html,'meu lado da história'),'Renderiza resposta e opções de tréplica');
unset($_SESSION['d3_pendente']);
ob_start();include $raiz.'/includes/components/painel/jogo_discordia.php';$html=ob_get_clean();
ver3(str_contains($html,'fazer_discordia') && str_contains($html,'JOGO DA DISCÓRDIA'),'Renderiza tema clássico e abre debate');
echo "\nJOGO DA DISCÓRDIA 3.0 — $ok verificações aprovadas.\n";
