# Fluxograma — BBB Simulator

Arquivo Mermaid para renderização (ex: https://mermaid.live). Cole o conteúdo abaixo.

```mermaid
flowchart TD
A[index.php: entrada do usuário] --> B[config.php: cria jogadores + relacionamentos + salva sessão]
B --> C[jogo.php: motor por fase_semana]

C --> Q[Queridômetro]
Q -->|usuário escolhe emojis| Q2[atualiza relacionamentos + registra resultado]
Q2 --> I1[Interações 1 (ações do usuário + geração NPC quando acaba)]
I1 --> P1[Prova do Líder (prova_lider.php)]
P1 --> VIP[vip_xepa (líder escolhe VIP ou IA escolhe)]
VIP --> PA[Prova do Anjo (prova_anjo.php)]
PA --> MON[Monstro (anjo escolhe ou IA escolhe)]
MON --> BIG[Big Fone (big_fone.php: tocou? atende? aplica poder)]
BIG --> F[Interações 2 / Festa (festa consome acoes_festa e gera NPC)]
F --> IMU[Imunização do Anjo (imunizacao_anjo)]
IMU --> Paredao[paredão (indicação líder -> Big Fone -> voto da casa)]
Paredao --> Discordia[discordia (tema -> processamento -> NPC -> interacoes_3)]
Discordia --> I3[Interações 3]
I3 --> ELIM[Eliminação -> resultado.php]
ELIM -->|você eliminado| FIM1[jogador_eliminado (tela final de stats)]
ELIM -->|não eliminado| LOOP[rodada++ e volta para interacoes_1]

C --> FINAL_TRIGGER[quando restar 3: finalistas]
FINAL_TRIGGER --> FINAL[final.php: reveal do campeão em 3 etapas]
FINAL --> END[novo jogo: index.php]
```
