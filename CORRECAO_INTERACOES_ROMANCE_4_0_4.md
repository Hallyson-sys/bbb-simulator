# BBB Simulator — correção de renderização das Interações (4.0.4)

## Erro corrigido

Ao abrir uma conversa romântica pendente, o jogo apresentava os avisos `Undefined variable $jogadores` e `Undefined variable $meuNome`, seguidos de `TypeError: i4EtapaRomance(): Argument #1 must be of type array, null given`.

**Causa:** `render()` usa `extract($dados)` em um escopo isolado. O componente `includes/components/interacoes.php` chamava `render('components/interacoes_4')` sem repassar `jogadores` e `meuNome`. O diálogo romântico usa ambos para reconhecer o namoro oficial.

**Correção:** passar explicitamente as duas variáveis à chamada aninhada. Não foram alteradas as regras de namoro, o estado do save ou os limites de interação.

## Testes

- `php testes/regressao_render_interacoes.php` — reproduz a cadeia completa de renderização com um casal já estabelecido, inclusive um diálogo iniciado na versão anterior; testa o recarregamento e a tela de desfecho.
- `php testes/romance_interacoes_contextual.php` — cenas de romance e compatibilidade.
- `php testes/interacoes_4.php` — ações sociais e estratégicas.
- `php testes/dev3_regressao.php` — cenários do Modo DEV e análise de sintaxe do projeto.

## Instalação

Descompacte o projeto sobre sua pasta local, preservando saves locais. Para uma atualização mínima, substitua apenas `includes/components/interacoes.php` (a alteração funcional está nesse arquivo).
