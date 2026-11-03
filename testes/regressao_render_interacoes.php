<?php
/**
 * Regressão: o render() isola cada escopo. O componente Interações precisa
 * repassar explicitamente $jogadores e $meuNome ao diálogo de Interações 4.0.
 * Executar: php testes/regressao_render_interacoes.php
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $valor, ?string $enc=null): string { return strtolower($valor); }
}
if (!function_exists('mb_stripos')) {
    function mb_stripos(string $texto, string $termo, int $offset=0, ?string $enc=null): int|false { return stripos($texto,$termo,$offset); }
}
$root = dirname(__DIR__);
foreach (['utilitarios.php', 'relacoes.php', 'romance.php', 'inteligencia_npc.php', 'interacoes_4.php'] as $arquivo) {
    require_once $root.'/includes/logica/'.$arquivo;
}
require_once $root.'/includes/helpers/render.php';

set_error_handler(static function(int $nivel, string $mensagem): never {
    throw new ErrorException($mensagem, 0, $nivel);
});

function assertRenderI4(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FALHOU: '.$message);
    echo '[OK] '.$message.PHP_EOL;
}
function elencoRenderI4(): array {
    return [
        ['nome'=>'Jogador','eliminado'=>false,'relacoes'=>['Miguel M.'=>['amizade'=>45,'confianca'=>40,'rivalidade'=>0]],'romances'=>[], 'status'=>[]],
        ['nome'=>'Miguel M.','eliminado'=>false,'relacoes'=>['Jogador'=>['amizade'=>45,'confianca'=>40,'rivalidade'=>0]],'romances'=>[], 'status'=>[]]
    ];
}
function renderCenaI4(array $jogadores): string {
    ob_start();
    try {
        // Exatamente a cadeia de templates usada durante a fase Interações.
        render('components/interacoes', [
            'fase'=>'interacoes_1','rodada'=>6,
            'jogadores'=>$jogadores,'meuNome'=>'Jogador'
        ]);
        return (string)ob_get_clean();
    } catch (Throwable $e) {
        ob_end_clean();
        throw $e;
    }
}
$_SESSION=[
    'rodada'=>6, 'fase_semana'=>'interacoes_1', 'meu_nome'=>'Jogador',
    'acoes_restantes'=>2, 'relacoes_jogador'=>['Miguel M.'=>45],
    'casais'=>['Jogador'=>'Miguel M.','Miguel M.'=>'Jogador'],
    'i4_pendente'=>[
        'acao'=>'i4_romance','alvo'=>'Miguel M.','outro'=>'',
        'assunto'=>'flertar','abordagem'=>'sincera','local'=>'no jardim',
        'fase'=>'interacoes_1','semana'=>6,'token'=>'teste-render','fala'=>'Quero ir com calma'
    ]
];
$jogadores=elencoRenderI4();
$html=renderCenaI4($jogadores);
assertRenderI4(str_contains($html,'Cuidar do namoro'),'Diálogo de namoro renderiza no painel, sem TypeError nem Warning');
assertRenderI4(str_contains($html,'Demonstrar carinho') && !str_contains($html,'Admitir que tem interesse'),'Opções consideram namoro existente');
assertRenderI4(($_SESSION['i4_pendente']['romance_etapa']??'')==='namoro','Cena do save anterior é sincronizada como namoro');
$html2=renderCenaI4($jogadores);
assertRenderI4($html===$html2,'Recarregar mantém o diálogo coerente e sem alterações duplicadas');
unset($_SESSION['i4_pendente']);
$_SESSION['i4_resultado']=['titulo'=>'Cuidar do namoro','npc'=>'Miguel M.','escolha'=>'Demonstrar carinho','texto'=>'Foi um bom momento.'];
$html=renderCenaI4($jogadores);
assertRenderI4(str_contains($html,'Voltar às Interações'),'Desfecho renderiza sem perder contexto');
restore_error_handler();
echo "RENDERIZAÇÃO DE INTERAÇÕES — 5 verificações aprovadas.\n";
