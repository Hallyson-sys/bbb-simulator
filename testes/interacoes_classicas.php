<?php
/**
 * Regressão de UI: restauração do painel clássico e repetição de NPC.
 * php testes/interacoes_classicas.php
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }

function render(string $component, array $props = []): void { /* componentes externos não fazem parte deste teste isolado */ }
function rx3IntencaoDisponivel(array $j, string $nome, string $fase) { return null; }
function obterAliancaJogador(array $j, string $nome) { return null; }
function gerarResumoAliancas(array $j): array { return []; }
function tamanhoAlianca(array $j, string $nome): int { return 0; }

$checks = 0;
function testeClassico(bool $pass, string $descricao): void {
    global $checks;
    if (!$pass) throw new RuntimeException('FALHOU: '.$descricao);
    $checks++;
    echo '[OK] '.$descricao."\n";
}

$fase='interacoes_1'; $rodada=1; $meuNome='Jogador';
$jogadores=[['nome'=>'Jogador','eliminado'=>false],['nome'=>'Ana','eliminado'=>false]];
$_SESSION=['acoes_restantes'=>3,'i4_usos'=>['1|Ana'=>true], 'relacoes_jogador'=>['Ana'=>20]];
$componente=__DIR__.'/../includes/components/interacoes.php';
ob_start(); include $componente; $tela=ob_get_clean();

testeClassico(str_contains($tela,'Interações — 3 restantes'), 'Cabeçalho clássico permanece');
testeClassico(str_contains($tela,'interacoes-grid') && str_contains($tela,'💬 Conversar'), 'Menu clássico apresenta Conversar');
testeClassico(str_contains($tela,'Fazer Fofoca') && str_contains($tela,'Criar Intriga'), 'Menu clássico apresenta fofoca e intriga');
testeClassico(str_contains($tela,'Aproximar do Líder') && str_contains($tela,'Fazer VT'), 'Ações clássicas extras mantidas');
testeClassico(!str_contains($tela,'Interações 4.0') && !str_contains($tela,'i4-grupos'), 'Painel e grade de cards 4.0 foram removidos');

// Simula jogador escolhendo Conversar após já ter usado uma conversa com Ana na mesma semana.
$_SESSION['acao_selecionada']='conversar';
ob_start(); include $componente; $escolha=ob_get_clean();
testeClassico(str_contains($escolha,'Escolha o participante') && str_contains($escolha,'Ana'), 'NPC pode ser selecionado novamente na mesma semana');
testeClassico(str_contains($escolha,'Executar Ação') && !str_contains($escolha,'Já conversaram esta semana'), 'Opção de conversar não desabilita NPC usado');

// Cena antiga da versão desativada não pode virar o botão clássico novamente.
require_once __DIR__.'/../includes/logica/interacoes_4.php';
i4SincronizarCena('interacoes_1');
testeClassico($_SESSION['acao_selecionada']==='conversar', 'Conversa clássica não é convertida automaticamente em Interações 4.0');

echo "INTERAÇÕES CLÁSSICAS — $checks verificações aprovadas.\n";
