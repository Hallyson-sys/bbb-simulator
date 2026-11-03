# BBB Simulator — Iniciativa dos NPCs 2.0

## O que mudou

- Os NPCs podem procurar o protagonista nas três fases de Interações.
- O convite aparece acima das categorias existentes (Social, Estratégia, Conflitos e Clássicas) e permite aceitar, adiar/retomar ou recusar.
- Ao aceitar, o jogador escolhe sua resposta; as consequências podem modificar afinidade, confiança, romance e Memória Social.
- Cenas possíveis: conversas cotidianas, desabafos, apoio, reconciliação, aproximação romântica, cuidado do namoro oficial e propostas de proteção.
- As falas respeitam o romance oficial: um casal não é tratado como se ainda estivesse se conhecendo.
- Promessas só são criadas se o jogador aceitar e o NPC concordar; usam o mecanismo existente de acordos e expiram. Não garantem votos.
- As iniciativas não descontam interações normais, não criam casais nem alianças automaticamente, e não revelam informações estratégicas ao Ao Vivo.
- As cenas consideram Personalidades Evolutivas, Memória Social e Objetivos Pessoais dos NPCs; uma iniciativa pode continuar sendo mencionada em outro encontro próximo.

## Frequência e segurança

- Uma tentativa de sorteio por fase; no máximo 2 convites naturais por semana, sem repetição do mesmo NPC na semana.
- Sorteio de 78% por fase social quando não há cena especial ou conversa em andamento. O jogador pode avançar normalmente ignorando convites pendentes; expiram sem penalidade quando a fase muda.
- Bloqueio de replay por token, fase e semana. A resposta e os efeitos são gravados uma única vez.
- Se o NPC foi eliminado ou o jogador deixou a casa, convites pendentes são descartados.
- Não há migração obrigatória de save: as novas chaves em sessão são opcionais e antigas continuam válidas.

## Modo DEV 3.0

Em localhost, abra uma fase de Interações, vá ao Modo DEV e escolha **Forçar convite de NPC**.
Essa ação modifica o save atual: use uma partida de teste. Os diagnósticos já existentes permanecem não destrutivos.

## Testes pelo terminal

```bash
php testes/iniciativa_npc_2.php
php testes/dev3_regressao.php
```

O primeiro script usa fixtures isoladas (não altera saves); o segundo executa a bateria DEV 3.0 e a análise de sintaxe PHP. Também foram testados os caminhos de aceitar, adiar, responder e continuar numa sessão HTTP local.

## Arquivos principais

- `includes/logica/iniciativa_npc_2.php`
- `includes/actions/iniciativa_npc_2.php`
- `includes/components/iniciativa_npc_2.php`
- `assets/css/iniciativa_npc_2.css`
- `jogo.php`, `includes/components/interacoes.php`, `includes/actions/avancar_fase.php`
- `modo_teste.php` e `includes/actions/modo_teste.php`
