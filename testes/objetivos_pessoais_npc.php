<?php
/** Teste isolado: php testes/objetivos_pessoais_npc.php */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $v, ?string $enc=null): string { return strtolower($v); }
}
$raiz = dirname(__DIR__);
$_SESSION = [];
require_once $raiz.'/includes/logica/utilitarios.php';
require_once $raiz.'/includes/logica/relacoes.php';
require_once $raiz.'/includes/logica/personalidades_evolutivas.php';
require_once $raiz.'/includes/logica/objetivos_npc.php';

function on1Assert(bool $condicao, string $teste): void {
    if (!$condicao) throw new RuntimeException('FALHOU: '.$teste);
    echo '[OK] '.$teste."\n";
}
$_SESSION = ['meu_nome'=>'Jogador','rodada'=>3,'evento_extra'=>[]];
$_SESSION['jogadores'] = [
    ['nome'=>'Jogador','personalidade'=>'Neutro','status'=>[],'relacoes'=>[],'estatisticas'=>[]],
    ['nome'=>'Bianca','personalidade'=>'Estrategista','alianca'=>'Grupo A','status'=>[],'estatisticas'=>['lider'=>0,'anjo'=>0],
        'relacoes'=>[
            'Caio'=>['amizade'=>60,'confianca'=>65,'rivalidade'=>0],
            'Lara'=>['amizade'=>-30,'confianca'=>-30,'rivalidade'=>70]]],
    ['nome'=>'Caio','personalidade'=>'Emocional','alianca'=>'Grupo A','status'=>[],'estatisticas'=>['lider'=>0,'anjo'=>0],
        'relacoes'=>['Bianca'=>['amizade'=>60,'confianca'=>65,'rivalidade'=>0]]],
    ['nome'=>'Lara','personalidade'=>'Explosivo','status'=>[],'estatisticas'=>['lider'=>0,'anjo'=>0],
        'relacoes'=>['Bianca'=>['amizade'=>-30,'confianca'=>-30,'rivalidade'=>70]]],
];
$j =& $_SESSION['jogadores'];
on1Sincronizar($j);
$antigo = $_SESSION['on1_objetivos'];
on1Assert(count($antigo) === 3 && !isset($antigo['Jogador']), 'apenas NPCs ativos recebem metas');
on1Sincronizar($j);
on1Assert($_SESSION['on1_objetivos'] === $antigo, 'recarregar não altera objetivos');
on1Assert($_SESSION['evento_extra'] === [], 'objetivos secretos não aparecem no Ao Vivo');

$_SESSION['on1_objetivos']['Bianca'] = ['tipo'=>'proteger_aliado','alvo'=>'Caio','inicio'=>3,'revisado'=>3];
on1Assert(on1BonusAlvo($j,'Bianca','Caio','vip') > 0 && on1BonusAlvo($j,'Bianca','Caio','voto') < 0, 'proteção afeta VIP e voto');
on1Assert(on1BonusAlvo($j,'Bianca','Lara','voto') === 0.0, 'sem enviesar o voto contra terceiros');
$_SESSION['on1_objetivos']['Bianca'] = ['tipo'=>'superar_rival','alvo'=>'Lara','inicio'=>3,'revisado'=>3];
on1Assert(on1BonusAlvo($j,'Bianca','Lara','voto') > 0 && on1BonusAlvo($j,'Bianca','Lara','imunidade') < 0, 'rivalidade afeta estratégia em sentidos opostos');
$_SESSION['on1_objetivos']['Bianca'] = ['tipo'=>'vencer_prova','alvo'=>'','inicio'=>3,'revisado'=>3,'estatisticas_base'=>['lider'=>0,'anjo'=>0]];
on1Assert(on1BonusProva('Bianca') === 5 && on1BonusProva('Caio') === 0, 'meta de prova dá incentivo leve');
$j[1]['estatisticas']['lider'] = 1;
$_SESSION['rodada'] = 4;
on1Sincronizar($j);
on1Assert(count((array)($_SESSION['on1_historico']['Bianca'] ?? [])) === 1, 'conquista troca objetivo na semana seguinte');
$novo = $_SESSION['on1_objetivos']['Bianca'];
on1Assert((int)$novo['inicio'] === 4, 'nova meta registrada na semana correta');
$hist = $_SESSION['on1_historico'];
on1Sincronizar($j);
on1Assert($_SESSION['on1_historico'] === $hist, 'conquista não gera trocas duplicadas');

$_SESSION['on1_objetivos']['Bianca'] = ['tipo'=>'proteger_aliado','alvo'=>'Caio','inicio'=>4,'revisado'=>4];
$j[2]['eliminado'] = true;
on1Sincronizar($j);
on1Assert(($_SESSION['on1_objetivos']['Bianca']['alvo'] ?? '') !== 'Caio', 'eliminação invalida o antigo alvo');
on1Assert(!isset($_SESSION['on1_objetivos']['Caio']), 'eliminado não mantém objetivo ativo');
on1Assert($_SESSION['evento_extra'] === [], 'nenhuma estratégia privada foi publicada');
echo "Objetivos Pessoais NPC — TODOS OS TESTES PASSARAM\n";
