# BBB Simulator Multiplayer — Beta 9: Feed BBB 3.0 Online

## Objetivo

Adaptar **somente as funcionalidades do Feed BBB 3.0 existentes no modo solo** para partidas de pessoas reais, preservando o restante das Betas 5, 6, 7 e 8 e o modo solo. Não há contas de espectadores reais, mensagens públicas dos fãs reais, nem votação pública online nesta versão.

## Funcionalidades integradas

- Publicações automáticas no Feed a partir dos **acontecimentos públicos** da casa; nomes, temas, semanas e categorias são reutilizados no texto.
- Repercussão simulada de **torcidas** e hashtags `#TeamNome`, com até **seis reações de torcidas por semana**, seguindo a lógica do Feed BBB 3.0 solo.
- **Trending topics** da semana, com até oito hashtags específicas.
- **Mutirões** `#FicaNome` e `#ForaNome` após a formação *definitiva* do Paredão, inclusive após eventual Bate e Volta; não aparecem antes.
- Filtros **Para você / Torcidas / Mutirões** e contagem dinâmica de publicações, mantendo o filtro selecionado entre atualizações.
- Autor e perfil do Feed, hashtags, curtidas, comentários e compartilhamentos **simulados** (sem participação de espectadores externos).
- Repercussão pública tem efeito **pequeno, oculto e limitado** na popularidade simulada, como no solo. A eliminação Beta 8 continua sendo uma simulação; público e votos reais não existem.
- O servidor registra o Feed **uma única vez por evento**. A ordem e o conteúdo são sincronizados entre os dispositivos da sala.
- Pausa/retomada e backup criptografado da Beta 5 incluem os posts e dados do Feed; o processo de restauração é preservado.

## Privacidade

Somente `events` (log público) alimenta o Feed. `privateLogs`, `pending`, mensagens particulares, alvos dos acordos, voto de cada jogador e token de acesso **nunca** entram nos posts. As campanhas de Paredão são simulações e **não permitem inferir o resultado**.

## Limitações

- O **Feed BBB 3.0** do modo solo é a referência desta Beta 9. Não foram incluídos Big Fone, poderes, Quarto Secreto, final, eventos extraordinários ou outros sistemas não relacionados ao Feed.
- Como não há espectadores externos participando da partida, posts, torcidas, métricas e campanhas são simulados, e isso é informado na interface.
- O servidor gratuito pode apagar salas em reinícios. Para retomar outro dia, o anfitrião deve pausar e baixar o backup com senha antes de encerrar.
- O modo solo não foi alterado. A interface precisa ser testada no Safari/iPhone e após o deploy no Render; a lógica e a API foram testadas localmente.

## Arquivos e instalação pelo iPhone

Descompacte os ZIPs antes do envio, navegue até a pasta correta no GitHub e **substitua os arquivos anteriores**:

- **Raiz**: `multiplayer_api.php`, `multiplayer_engine.php`, `multiplayer_paredao.php`, `multiplayer_jogo.php`, **novo** `multiplayer_feed.php`, `MULTIPLAYER_BETA_9.md`.
- **assets/css**: `multiplayer_integrado.css`.
- **assets/js**: `multiplayer_jogo.js`.

Crie e teste a Beta 9 em uma branch separada. Se passar, faça commit na branch utilizada pelo Render, verifique o deploy e abra uma sala nova.

## Testes sugeridos

1. Jogar com dois celulares: faça uma interação e confirme que o Feed mostra posts e torcida sem mensagens particulares.
2. Abra os três filtros, espere o status sincronizar e confirme que não muda de filtro nem volta a página para o início.
3. Chegue ao Paredão de 10 pessoas e confira mutirões **somente após** a definição dos indicados; depois repita com Bate e Volta.
4. Pause, baixe o backup, restaure, confira que posts e campanhas não foram duplicados.
5. Teste uma temporada solo para confirmar que ela permaneceu intacta.

## Validações locais

- Sintaxe PHP e JavaScript.
- Testes do Feed com 10, 15 e 20 jogadores: torcidas, mutirões, hashtags, privacidade e ausência de duplicatas.
- Testes API: sala com dois celulares simulados, sincronização, conversa privada, pausa e exportação.
- Suítes anteriores Beta 6 (Líder/Anjo) e Beta 8 (Paredão/eliminação) passaram sem regressões detectadas.
