<?php
/**
 * Executar no terminal: php testes/dev3_regressao.php
 * Os testes de jogo usam apenas fixtures isoladas; NÃO abrem nem alteram saves.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Somente CLI.'); }
$raiz = dirname(__DIR__);
// A máquina de testes pode não ter mbstring, mas as fixtures só usam ASCII.
// Este fallback existe APENAS no processo de teste, nunca no jogo real.
if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $valor, ?string $encoding=null): string { return strtolower($valor); }
    echo "Nota: mbstring indisponível neste PHP; fallback ASCII só para as fixtures.\n";
}
$_SESSION = [];
foreach ([
    'utilitarios.php','modo_teste.php','relacoes.php','aliancas.php',
    'aliancas_3.php','dinamicas_surpresa.php','paredao.php',
    'provas_novas.php','reta_final.php','top4.php','fofoca_vt.php','objetivos_npc.php','dilemas_jogo.php','interacoes_4.php','discordia.php','discordia_3.php','dev_diagnosticos.php'
] as $arquivo) require_once $raiz.'/includes/logica/'.$arquivo;

$dados = dev3Bateria([
    'jogadores'=>dev3ElencoExemplo(9),
    'meu_nome'=>'NPC1',
    'rodada'=>4,
    'fase_semana'=>'interacoes_1',
    'paredao'=>[],
], 'completo');
$problemas=0;
echo "BBB SIMULATOR — MODO DEV 3.0\n";
echo "Testes de regras e integridade do save sintético:\n";
foreach($dados['linhas'] as $item){
    $ok=$item['status']!=='falha';
    echo ($ok ? '[OK] ':'[ERRO] ').$item['codigo'].' — '.$item['titulo']."\n";
    if (!$ok) { $problemas++; echo '  '.$item['detalhe']."\n"; }
}

if (!in_array('--sem-lint', $argv, true)) {
    echo "\nAnalisando sintaxe de todos os PHPs...\n";
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz,FilesystemIterator::SKIP_DOTS));
    $total=0;
    foreach($iter as $arquivo){
        if (!$arquivo->isFile() || strtolower($arquivo->getExtension()) !== 'php') continue;
        $total++;
        $saida=[];$retorno=0;
        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($arquivo->getPathname()).' 2>&1',$saida,$retorno);
        if($retorno!==0){$problemas++;echo '[ERRO PHP] '.substr($arquivo->getPathname(),strlen($raiz)+1).' '.implode(' ',$saida)."\n";}
    }
    echo "Sintaxe verificada em $total arquivos PHP.\n";
}
echo "\nResultado: ".($problemas===0 ? 'TODOS OS TESTES PASSARAM' : "$problemas PROBLEMA(S) ENCONTRADO(S)")."\n";
exit($problemas===0?0:1);
