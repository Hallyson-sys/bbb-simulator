# BBB Simulator — Interações 4.0: Conversas Vivas & Estratégia Social

Base: atualização **Dilemas de Jogo 2.0**, incluindo Modo DEV 3.0, Objetivos Pessoais dos NPCs, Bate-Papo com Eliminado e Liderança em Dupla.

## Novidades

- Interface de Interações organizada em **Social**, **Estratégia**, **Conflitos** e **Alianças e ações clássicas**, com layout responsivo.
- Onze ações com diálogo em duas etapas: **Conversar de verdade**, **Conversa pessoal**, **Demonstrar interesse romântico**, **Convidar para atividade**, **Sondar jogo**, **Pedir proteção**, **Propor acordo individual**, **Compartilhar informação**, **Resolver desentendimento**, **Tirar satisfação** e **Mediar duas pessoas**.
- Escolha primeiro um NPC, assunto e abordagem. O NPC reage de acordo com a relação e a personalidade; só depois de escolher uma resposta o jogador consome uma interação.
- Cada NPC só participa de uma interação aprofundada por semana, inclusive em mediações. Respostas repetidas não reaplicam efeitos.
- Promessas aceitas são registradas por até duas semanas, com bônus **moderado** na escolha de votos e proteções; elas **não garantem** lealdade, imunidade ou voto.
- Flerte pode ser recusado; não inicia namoro automático nem interfere nos relacionamentos atuais.
- Sondagem pode revelar preocupações **baseadas em rivalidades reais**, nunca inventa votos secretos. Revelações válidas podem entrar no painel privado de conhecimentos.
- Informações compartilhadas precisam existir no inventário do jogador e não estar expiradas. O NPC recebe a informação e ela pode circular em fofocas futuras.
- Efeitos de relações respeitam limites; memórias NPC e traços evolutivos são atualizados somente quando a decisão é concluída.
- **Ao Vivo** recebe no máximo um resumo observável da interação, sem expor pactos nem segredos; as respostas completas ficam no **histórico privado de conversas**.
- Compatibilidade com saves antigos: estados novos opcionais e limpeza de cenas pendentes que pertençam a semanas/fases antigas.
- Bloqueios contra avanço de fase e ações concorrentes enquanto houver diálogo/desfecho pendente. Reuso da finalização original de ações garante a execução de eventos e NPCs quando as interações acabam.
- Modo DEV 3.0 ampliado de 32 para **38 cenários automáticos**; duas inspeções adicionais do save.

## Como testar

1. Execute uma temporada com participantes ativos na fase **Interações 1, 2 ou 3**.
2. Em **Social**, escolha **Conversar de verdade**. Selecione NPC, assunto e abordagem, inicie o diálogo e escolha uma resposta.
3. Veja a reação do NPC e o histórico. Confirme que apenas **uma** ação foi consumida e que o mesmo NPC não aparece habilitado em outra conversa aprofundada naquela semana.
4. Em **Estratégia**, experimente **Pedir proteção** ou **Propor acordo**; NPCs podem recusar. Um pacto aceito tem validade até a semana seguinte.
5. Teste **Sondar jogo** com um NPC que tenha rival declarado. Se ele compartilhar uma preocupação, ela ficará registrada como opinião, não como voto confirmado.
6. No **Modo DEV**, execute a bateria automática. Pelo terminal, utilize:

```bash
php testes/interacoes_4.php
php testes/dev3_regressao.php
```

## Arquivos principais

`includes/logica/interacoes_4.php`, `includes/components/interacoes_4.php`, `includes/components/interacoes_4_form.php`, `assets/css/interacoes_4.css`, `includes/actions/interacoes.php`, `includes/components/interacoes.php`, `includes/logica/inteligencia_npc.php`, `includes/logica/dev_diagnosticos.php`, `testes/interacoes_4.php`.

## Limitações

- As falas são geradas por regras e variantes locais, **não** por IA generativa externa; é possível encontrar assuntos semelhantes em semanas diferentes.
- Ações em momentos de Festa seguem o sistema anterior. Interações 4.0 atua nas fases Interações.
- Testes CLI usam apenas fixtures, não substituem uma partida inteira em dispositivo móvel. O jogo original depende da extensão PHP `mbstring` no ambiente de produção.
