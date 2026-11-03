# BBB Simulator — Interações Clássicas (ajuste 4.0.1)

- Restaurado o menu original de **Interações**: Conversar, Fazer Fofoca, Criar Intriga, opções de aliança, Aproximar do Líder, Discutir e Fazer VT.
- Removido da tela o painel de cards da **Interações 4.0**, inclusive a grade que esticava cards fechados ao abrir outros.
- **Conversar** volta a acionar o fluxo clássico; não é convertido automaticamente em outro tipo de interação.
- A mesma pessoa pode ser escolhida mais de uma vez na mesma semana, respeitando apenas as ações restantes da fase.
- Removidos os bloqueios por NPC nas rotinas de conversas do mecanismo 4.0, preservado como compatibilidade interna.
- Um save com conversa 4.0 pendente ou desfecho aberto é liberado automaticamente na próxima entrada em `jogo.php` sem consumir ação, mantendo históricos e pactos já confirmados.
- Os pactos já firmados continuam influenciando moderadamente a IA até expirarem.
- Dilemas, Rotina da Casa, Relações em Movimento, Alianças e Modo DEV são mantidos.

**Teste rápido:** entre em Interações; confirme que os botões clássicos aparecem, converse com o mesmo NPC duas vezes (havendo ações), abra/recolha painéis de rotina/relacionamentos e avance a fase. Para o ZIP no localhost, substitua a pasta `bbb-simulator` pelos arquivos desta versão.

## Comandos de validação

```bash
php testes/interacoes_classicas.php
php testes/interacoes_4.php
php testes/dev3_regressao.php
```
