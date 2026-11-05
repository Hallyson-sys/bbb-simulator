# BBB Simulator — Multiplayer Online Beta 7: convivência e semana

Esta versão foi criada **sobre a Beta 6** e não altera o motor nem os arquivos do **modo solo**.

## Incluído nesta Beta 7

- **VIP/Xepa**: a escolha do Líder continua sincronizada. A divulgação passa a identificar o elenco VIP e os demais na Xepa.
- **Monstro do Anjo (novo)**: após a Prova do Anjo, o Anjo pode selecionar até duas pessoas (menos, em salas pequenas). O castigo aparece nos cards e no Ao Vivo. Cada Monstro pode cumprir uma atividade extra, sem gastar suas interações, durante a segunda rodada de interações.
- **Queridômetro coletivo (novo)**: todos escolhem um dos **11 emojis originais** para **cada outro participante ativo**, com preenchimento sugerido a partir das relações e revisão antes de confirmar. O servidor valida o conjunto inteiro; ao final, apresenta por participante os totais de emojis recebidos sem expor os autores. Quem não enviar a tempo simplesmente não vota, sem imputar decisões falsas.
- **Festa da Semana (ampliada)**: três ações por participante, compartilhadas com as Interações 4.0 que continuam disponíveis. Aproximar, Fazer VT, Romance, Provocar, Sondar voto, Articular votos com até **três convidados**, Dançar e Exagerar na bebida. Convites dirigidos dependem de resposta real. Acordos e mensagens particulares só aparecem para os envolvidos.
- **Jogo da Discórdia (ampliado)**: tema da semana alterna; o jogador escolhe alvo e argumento. Quem foi apontado pode responder; o autor faz a tréplica. O ciclo aparece no Ao Vivo. Aguardamos respostas pendentes antes de avançar, até o limite do relógio.
- **Rotina da casa (inicial)**: pequenos anúncios de convivência na casa surgem em etapas sociais. Não atribuímos automaticamente escolhas pessoais a jogadores reais.
- **Ao Vivo e Feed**: as novas ações geram relatos com contexto e hashtags; o Feed BBB avançado permanece planejado para a Beta 9.
- **Pausa/retomada e backup criptografado da Beta 5**, e **Provas do Líder/Anjo da Beta 6**, permanecem.

## Instalação no celular (somente arquivos modificados)

1. **Raiz do repositório**: `multiplayer_api.php`, `multiplayer_engine.php`, `multiplayer_jogo.php` e **`multiplayer_convivencia.php` (NOVO)**.
2. **assets/css**: `multiplayer_integrado.css`.
3. **assets/js**: `multiplayer_jogo.js`.
4. Confirme os commits e o deploy no Render. Crie uma **sala nova** para testar: salas antigas em outra fase podem não ter os campos de convivência novos.

**Não envie os arquivos ZIP fechados.** Extraia-os no iPhone e envie os arquivos dentro das pastas corretas. Faça primeiro em uma branch de testes, se possível.

## Validação executada

- PHP sem erros de sintaxe; JS sem erros de sintaxe no Node.
- API com 10 jogadores: avanço de fases, VIP, Monstro, castigo, 90 emojis, três ações de Festa, convite coletivo de votos, sigilo das mensagens, pausa/retomada, debate público com resposta e tréplica.
- API com 15 e 20 jogadores: apuração de 210 e 380 emojis, respectivamente.
- Sala de 20 participantes aceita sem ultrapassar o limite de vagas.
- **A validação da interface no Safari/Render ainda é necessária**: o navegador automatizado deste ambiente bloqueou o endereço do servidor local.

## O que NÃO é a equivalência completa do solo (ainda)

Estas funções da Beta 7 adaptam a estrutura do modo solo, mas o modo online **não reutiliza todas as cenas/variações do motor de NPCs**. Também ainda faltam: Prova Bate e Volta, formação completa do Paredão, votação do público, Big Fone, quarto secreto, dinâmicas surpresa, romances oficiais, provas de resistência e duplas, Feed BBB 3.0 avançado e Top 8/final originais. Elas exigem próximas adaptações, para não quebrar a temporada online.

**Hospedagem gratuita:** estados das salas seguem em JSON temporário; pausas só sobrevivem a reinício se o anfitrião tiver baixado o backup e restaurá-lo. Não é uma solução para lançamento público em larga escala.
