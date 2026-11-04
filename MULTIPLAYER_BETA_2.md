# BBB Simulator — Multiplayer Online Beta 2: interface integrada

**Objetivo:** preservar os arquivos e a identidade visual do BBB Simulator solo, oferecendo uma temporada **experimental** separada, sincronizada entre jogadores reais. Não substitui o modo solo nem reproduz todas as dinâmicas, regras e eventos do jogo original.

## O que funciona nesta atualização

- No `index.php`, botão de entrada **Multiplayer Online**. A tela `multiplayer.php` cria salas com código, 10/15/20 vagas, confirmações, convite por link e reconexão pelo mesmo navegador.
- Ao iniciar, todos entram em `multiplayer_jogo.php`. O layout carrega os **mesmos CSS do jogo solo** (`jogo.css`, `header.css`, `responsivo.css`, `interacoes_4.css`) com cabeçalho, participantes, controle da semana, Na Casa, categorias e barra de navegação móvel.
- Participantes reais: cards com nomes, status, Líder, Anjo, VIP, imunidade e relações positivas/negativas. No modo online, nenhuma decisão depende de NPCs.
- Interações com até **três ações** por fase. Categorias e títulos do jogo original: Social, Estratégia, Conflitos e ações clássicas/Alianças. Escolha alvo, assunto e abordagem. O alvo recebe o convite e escolhe **Aceitar / Responder com cautela / Recusar**. O servidor atualiza o placar de relação. Convites pendentes impedem o avanço automático até serem respondidos ou o tempo acabar. Quem pulou interações ainda pode responder a convites.
- Semana sincronizada: `Interações 1 → Líder → VIP/Xepa → Anjo → Imunidade → Queridômetro → Interações 2 → Festa → Jogo da Discórdia → Paredão → Eliminação`.
- Provas do Líder/Anjo **simplificadas** na beta: os jogadores escolhem uma caixa e o servidor sorteia pontuações. Líder escolhe VIP, Anjo escolhe imunizado.
- Queridômetro tem envio de um emoji para outra pessoa. Festa reutiliza as interações. Discórdia oferece três motivos. Paredão inclui indicação do Líder e voto secreto no confessionário.
- **Eliminação experimental** com placar de convivência entre os emparedados. NÃO equivale ao voto do público do BBB Simulator solo. Final beta ao alcançar 3 sobreviventes ou 8 semanas; NÃO é a final do modo solo.
- Todos os participantes compartilham semana e fase. Cada etapa termina quando todos concluíram (e os convites foram respondidos), ou no prazo do cronômetro do **servidor**. Eliminados assistem como espectadores.
- Dados das salas em JSON **temporário** no servidor, sem banco externo. Locks impedem que dois jogadores escrevam simultaneamente sobre o mesmo arquivo. A API nunca envia os tokens de outros participantes e verifica identidades por token secreto.

## O que AINDA falta para equivalência com o solo

- Reaproveitar a lógica completa e ramificada de Interações 4.0 (cenas, várias respostas, fofoca, intriga, romance contextual e alianças em grupo), e não só os rótulos e a estrutura visual.
- Adaptar as **provas interativas** (Líder, Anjo, duplas, resistência) e as regras completas de VIP/Monstro, festa, Jogo da Discórdia, dinâmicas surpresa, Big Fone, Paredão/Bate-Volta, voto do público, final/Top 4, Feed BBB e Queridômetro coletivo.
- Saves duráveis, recuperação das salas após reinício, suporte multi-instância, moderação, rate limits fortes e contas. **Ainda não indicado para torneios longos ou lançamento público em larga escala.**

## Hospedagem e limitações

O Render gratuito executando PHP funciona como prova de conceito. Armazena cada sala no diretório temporário local do serviço; qualquer reinício/redeploy ou alteração de instância pode **apagar partidas em andamento**. O jogo solo usa a própria sessão e continua independente. Para uma versão estável, é necessário mover o estado sincronizado a um armazenamento durável (ex.: Durable Objects) e ampliar as validações, principalmente para partidas longas.

## Como publicar pelo iPhone

Use o **ZIP somente com arquivos alterados**, extraia no aplicativo Arquivos e envie os arquivos aos caminhos abaixo pelo GitHub (não envie o ZIP fechado). Faça primeiro numa **branch de testes**.

**Raiz do repositório:** `index.php`, `multiplayer.php`, `multiplayer_jogo.php`, `multiplayer_api.php`, `multiplayer_engine.php`, `MULTIPLAYER_BETA_2.md`.

**assets/css:** `index.css`, `multiplayer_integrado.css`.

**assets/js:** `multiplayer.js`, `multiplayer_jogo.js`.

Se você **já publicou** a Beta 1, substitua os arquivos com os mesmos nomes pelos novos; o CSS antigo `assets/css/multiplayer.css` fica sem uso e pode ser removido depois. A versão completa ZIP contém o projeto inteiro, mas não serve como substituto de um commit de arquivos extraídos no GitHub.

## Roteiro de testes

1. Crie uma sala pelo celular. Peça a outro amigo para abrir o link em **outro aparelho ou navegador** (cada navegador guarda um token diferente).
2. Ambos cliquem em **Estou pronto**. O anfitrião pode iniciar com 2 na beta para testar; a capacidade permanece 10/15/20.
3. Na primeira fase, um manda um convite e o outro responde. Confira que a relação mudou e que ambos veem o acontecimento no Ao Vivo.
4. Quem terminar antes usa **Pular restante e finalizar**. Convites recebidos ainda podem ser respondidos; quando todos concluem, a fase avança automaticamente.
5. Teste Prova do Líder, VIP/Xepa, Anjo, imunidade, Queridômetro, segunda rodada de interações, Festa e Discórdia.
6. Teste Paredão e eliminação. Atenção: com **apenas 2 jogadores de teste**, algumas regras de indicação/imunidade ficam limitadas; para uma experiência melhor, teste com 4 ou mais.
7. Feche e reabra a página usando o mesmo navegador; a vaga deve ser recuperada enquanto o Render não tiver reiniciado.

**Os códigos, regras e testes não alteram `jogo.php`, `config.php`, `includes/logica/*` ou saves do modo solo.**
