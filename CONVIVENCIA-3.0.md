# Convivência 3.0 — Personalidades Evolutivas

## O que mudou

- Cinco traços internos por NPC: confiança, reatividade, lealdade, estratégia e sociabilidade.
- A personalidade de origem permanece fixa; os traços evoluem com acontecimentos reais registrados na Memória Social.
- Líder e Anjo podem reforçar confiança; Monstro pode aumentar reatividade.
- Efeitos limitados por semana/tipo, com estabilização gradual ao iniciar uma nova semana.
- A IA usa esses traços no cálculo de alvos, votos, proteção de aliados, probabilidade de ações sociais e lealdade às alianças.
- O Confessionário pode abordar mudanças de comportamento quando elas são relevantes.
- Viradas observáveis de comportamento podem aparecer no Ao Vivo e no Perfil, sem mostrar números internos ou segredos.

## Compatibilidade e privacidade

- Dados persistidos em `$_SESSION['e3_personalidades']`, junto com os saves existentes.
- Para saves antigos, os traços são criados na primeira abertura sem inventar acontecimentos passados.
- Registros de memórias secretas podem influenciar o próprio NPC, mas não são anunciados na casa.
- Emojis anônimos do Queridômetro não evoluem traços identificando um remetente.
- Popularidade continua oculta; o nome da personalidade original não é substituído.

## Testes recomendados

1. Em uma temporada de teste, faça um NPC receber uma indicação ao Paredão e depois um gesto de proteção.
2. Observe futuras decisões de voto, interações e eventuais reflexões no Confessionário.
3. Avance uma semana, salve e recarregue: a evolução não deve ser aplicada novamente.
4. Confira o Perfil: apenas uma mudança de postura observável pode aparecer, nunca traços internos ou porcentagens.
