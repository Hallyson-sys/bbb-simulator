# BBB Simulator — Beta 10: Dinâmicas Especiais Multiplayer

Atualização baseada nas funções especiais do **modo solo** do projeto, mantendo os modos separados. Requer os arquivos da Beta 9 já publicados. Para atualizar pelo GitHub no iPhone, **extraia os ZIPs antes de enviar os arquivos**.

## O que entra na Beta 10

- **Big Fone**: quando há mais de 5 jogadores ativos, segue a chance de toque de 60% usada no solo. Uma fase curta de 40 segundos aparece entre VIP/Xepa e Anjo. Somente o primeiro pedido aceito pelo **servidor**, sob bloqueio da sala, atende. A mensagem privada pode ser um poder ou um trote — atender não garante poder.
- Poderes baseados no solo: **imunidade de quem atendeu**, **imunidade extra para alvo escolhido**, **indicação adicional**, **voto em dobro**, **anular voto**, **espiar voto**, **contragolpe se for emparedado** e **trocar um emparedado da casa** (sem trocar a indicação do Líder). Um trote também é possível.
- Poderes que exigem alvo são resolvidos **depois da Festa/Discórdia e antes do Confessionário**, na fase `poder_bigfone`. Poderes condicionais são resolvidos **após a formação do Paredão**. Se vários efeitos estiverem pendentes, uma fila resolve as decisões uma por vez. Se o jogador não responder dentro do tempo, o benefício expira.
- **Dinâmicas surpresa do solo**: Votação Aberta, Imunidade Surpresa, Votação Turbinada, Carta Misteriosa, Contragolpe Especial e Paredão Especial. A primeira só pode surgir a partir da semana 2, com pelo menos 6 jogadores ativos, com probabilidade de 42% e intervalo mínimo de duas semanas. Não há novas dinâmicas surpresa no Top 5. A Carta Misteriosa só revela seu efeito ao ser entregue depois da Festa.
- **Paredão Falso / Quarto Secreto**: tentativa de 18% a partir da semana 3, somente com pelo menos 7 ativos, no máximo uma vez por temporada. Para a casa, ocorre uma eliminação normal; só o falso eliminado vê a informação do Quarto Secreto. Ele acompanha o Ao Vivo como observador e retorna antes da Festa da semana seguinte. Nenhum jogador vê o segredo antecipadamente no painel comum.
- **Ao Vivo e Feed BBB** registram anúncios e repercussões públicas, sem expor mensagens particulares, votos sigilosos ou a identidade do Quarto Secreto antes do retorno.
- **Pausa/retomada e backup do anfitrião** continuam funcionando com o novo estado serializável das dinâmicas.

## Regras de segurança e justiça

- O servidor valida alvo, imunidades, fase, disponibilidade de poder, participante real e cronômetro. Ações concorrentes são processadas sob a mesma trava de arquivo da sala. Não existe poder duplicado quando duas pessoas atendem o Big Fone.
- Um voto anulado não conta como abstenção. Os efeitos de voto duplo não se acumulam; o peso máximo de um voto individual é 2. Espiar voto revela **somente um** voto à pessoa que recebeu o poder. A Votação Aberta é uma exceção anunciada publicamente antes do voto.
- O escolhido para o Quarto Secreto não vota enquanto estiver temporariamente fora da casa. No retorno, volta a participar das ações comuns.

## Arquivos do GitHub

**Raiz**: `multiplayer_api.php`, `multiplayer_engine.php`, `multiplayer_paredao.php`, `multiplayer_especiais.php` (**novo**), `multiplayer_jogo.php` e `MULTIPLAYER_BETA_10.md` (documentação).

**assets/css**: `multiplayer_integrado.css`.

**assets/js**: `multiplayer_jogo.js`.

O pacote completo também contém `testes/multiplayer_beta_10.php` para testes em PHP local.

## Testes executados

- Validação de sintaxe PHP e JavaScript.
- Suítes de regressão Beta 6, Beta 8 e Beta 9.
- Suíte Beta 10 com salas de 10, 15 e 20, sigilo dos poderes, carta/imunidade, Top 5, contragolpe, troca de emparedado, cruzamento de dinâmicas, Quarto Secreto, apuração, pausa e estado serializável.
- API local: criar sala, convidado entrar, confirmar, iniciar, pausar, exportar, retomar e duas pessoas tentando atender o telefone ao mesmo tempo.

## Limitações e cuidados

- A interface visual **ainda precisa de testes reais no Safari e no Render**. O protótipo opera por atualizações periódicas, não por WebSockets.
- O Quarto Secreto da Beta 10 é uma adaptação para acompanhamento dos **acontecimentos públicos** e retorno; não inclui interações privadas adicionais dentro do quarto.
- As salas ficam em arquivos temporários no servidor. **O anfitrião deve pausar e baixar o backup protegido por senha para continuar outro dia**. O backup completo é sensível e pode conter segredos da sala; mantenha-o privado. Um reinício do Render pode apagar a sala temporária.
- Uma temporada já iniciada na Beta 9 pode não ter os novos eventos e chaves inicializados. Para testar a Beta 10, **crie uma sala nova**.
- A final completa, Casa de Vidro e outras funcionalidades fora do escopo das dinâmicas especiais **não foram adicionadas nesta Beta 10**. O modo solo permanece intacto.
