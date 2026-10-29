# BBB Simulator — Rotina da Casa 2.0

## O que foi adicionado

- Acontecimentos espontâneos entre NPCs na cozinha, piscina, jardim, quartos, varanda, academia e Festa.
- 16 categorias de situação com 3 narrativas cada (48 variações textuais), incluindo refeições, tarefas, treino, conversas, reconciliações, tensões, brincadeiras, danças e brinde.
- Seleção baseada no clima da relação, na personalidade e no contexto da Festa. O registro tenta não repetir a mesma dupla ou categoria nos últimos eventos.
- As cenas alteram realmente amizade, rivalidade, confiança e, quando apropriado, a Memória Social. O sistema de personalidades evolutivas continua recebendo os efeitos das memórias.
- Registro no **Ao Vivo** com local e descrição; painel recolhível **🏠 Rotina da Casa** em Interações e Festa.
- Limites: até **2 momentos por fase** e **7 por semana**, com chances moderadas ao entrar na fase ou realizar ações. A conclusão da fase pode gerar um momento mesmo quando o jogador pula ações.
- Sem exibir popularidade ou votos secretos. Conversas reservadas aparecem sem revelar conteúdo.

## Como testar

1. Continue uma temporada (saves antigos funcionam) e entre em Interações.
2. Observe o painel **🏠 Rotina da Casa** e o **Ao Vivo**. Ao interagir ou avançar, novos acontecimentos podem surgir.
3. Avance à Festa e repita. Uma conversa ou discussão mudará internamente as relações dos NPCs envolvidos.
4. Recarregue a página: **não** surgem eventos duplicados por causa do refresh.
5. Confira as mudanças graduais na convivência nas semanas seguintes.

## Arquivos

- `includes/logica/rotina_casa.php`: motor e persistência na sessão.
- `includes/components/rotina_casa.php`: consulta dos momentos recentes.
- `assets/css/rotina_casa_2.css`: visual responsivo.
- Integração em `jogo.php`, Interações, Festa e avanço de fase.

## Observações

A rotina é simulada quando o jogo entra ou avança em fases sociais; ela **não** funciona como um relógio em segundo plano enquanto a página estiver parada. O jogador continua controlando suas próprias ações e as cenas automáticas ocorrem entre NPCs.
