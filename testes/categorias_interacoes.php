<?php
/** Regressão visual/estrutural do menu: php testes/categorias_interacoes.php */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__.'/../includes/logica/interacoes_4.php';
function render(string $component, array $props=[]): void {}
function rx3IntencaoDisponivel(array $j, string $nome, string $fase) { return null; }
function obterAliancaJogador(array $j, string $nome) { return null; }
function gerarResumoAliancas(array $j): array { return []; }
function tamanhoAlianca(array $j, string $nome): int { return 0; }
$checks=0;
function verificaCategoria(bool $ok, string $nome): void {
    global $checks;
    if(!$ok)throw new RuntimeException('Falha: '.$nome);
    $checks++;
    echo "[OK] $nome\n";
}
$fase='interacoes_1';$rodada=2;$meuNome='Jogador';
$jogadores=[['nome'=>'Jogador','eliminado'=>false],['nome'=>'Ana','eliminado'=>false],['nome'=>'Caio','eliminado'=>false]];
$_SESSION=['rodada'=>2,'fase_semana'=>'interacoes_1','acoes_restantes'=>3,
    'i4_usos'=>['2|Ana'=>true],'relacoes_jogador'=>['Ana'=>20,'Caio'=>10]];
ob_start();include __DIR__.'/../includes/components/interacoes.php';$html=ob_get_clean();
verificaCategoria(str_contains($html,'Interações — 3 restantes'), 'Painel clássico acima do menu permanece');
verificaCategoria(!str_contains($html,'Interações 4.0 — Conversas Vivas') && !str_contains($html,'<section class="i4-menu">'), 'Cabeçalho/moldura extra removidos');
verificaCategoria(substr_count($html,'name="i4-categorias"')===4, 'Quatro categorias vinculadas ao mesmo grupo exclusivo');
foreach(['Social','Estratégia','Conflitos','Alianças e ações clássicas'] as $grupo) {
    verificaCategoria(str_contains($html,$grupo), 'Categoria mantida: '.$grupo);
}
foreach(['i4_conversar','i4_pessoal','i4_romance','i4_atividade','i4_sondar','i4_protecao','i4_acordo','i4_reconciliar','i4_satisfacao','i4_mediar','conversar','fofoca','intriga','discutir','vt'] as $acao) {
    verificaCategoria(str_contains($html,'value="'.$acao.'"'), 'Ação disponível: '.$acao);
}
$css=file_get_contents(__DIR__.'/../assets/css/interacoes_4.css');
verificaCategoria(str_contains($css,'align-items:start') && str_contains($css,'align-self:start'), 'Card fechado não cresce junto com o aberto');
verificaCategoria(substr_count($html,'<details class="i4-grupo"')===4, 'Nenhuma categoria duplicada');

// Mesmo um NPC já registrado no histórico continua selecionável nas ações novas.
$acaoSelecionada='i4_pessoal';
ob_start();include __DIR__.'/../includes/components/interacoes_4_form.php';$form=ob_get_clean();
verificaCategoria(str_contains($form,'value="Ana"') && !str_contains($form,'Já conversaram esta semana'), 'NPC usado continua disponível em conversa pessoal');
verificaCategoria(!str_contains($form,'disabled'), 'Nenhum bloqueio por NPC na lista de alvos');
$_SESSION['acao_selecionada']='conversar';
i4SincronizarCena('interacoes_1');
verificaCategoria($_SESSION['acao_selecionada']==='conversar','Conversar clássico continua clássico após atualizar o save');
echo "CATEGORIAS — $checks verificações aprovadas.\n";
