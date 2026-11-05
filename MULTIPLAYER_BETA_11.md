# BBB Simulator — Beta 11: Reta Final e Grande Final Multiplayer

Esta atualização **preserva os arquivos do modo solo** e acrescenta ao modo online os eventos finais do modo solo adaptados para jogadores reais. A sala e suas decisões continuam armazenadas no servidor; o resultado do público é **simulado**, não representa votos externos.

## Regras da reta final

- **Top 8:** a partir de oito concorrentes na temporada, não haverá mais Prova do Anjo, Monstro nem Jogo da Discórdia. Interações, Líder, Queridômetro, Festa e Paredão continuam. O Big Fone pode continuar até o Top 6, conforme as regras já existentes.
- **Top 5:** não há novas dinâmicas surpresa ou Big Fone. A temporada preserva as fases regulares compatíveis.
- **Top 4:** logo após a eliminação que deixa quatro participantes, começa a **Última Resistência**. São até oito ciclos de postura, precisão, energia e foco. Cada pessoa responde no próprio dispositivo; o servidor valida uma resposta por ciclo. O tempo de chegada da resposta **não é critério de desempate**.
- A pessoa com melhor desempenho na resistência garante **vaga direta na Grande Final**. Os outros três enfrentam o **último Paredão**, sem Bate e Volta. Uma pessoa é eliminada e os três finalistas restantes vão à cerimônia.
- **Grande Final:** cada finalista escolhe um de quatro pedidos de voto existentes no modo solo (coração, estratégia, superação ou gratidão). Participantes eliminados podem declarar quem apoiam na entrevista do apresentador. Pessoas ausentes recebem falas e torcidas automáticas ao fim do cronômetro para que a partida não trave.
- A coroação mostra a pessoa campeã e porcentagens de voto **SIMULADAS**, construídas a partir da popularidade do Feed, relações da casa, apoios dos eliminados, pedido de voto e pequeno fator aleatório. Porcentagens somam 100%.

## Segurança e sincronização

- Apenas quatro jogadores ainda ativos participam da Última Resistência; quem já caiu da plataforma acompanha o restante.
- O ponto ideal em cada ciclo fica no servidor até o fim da rodada. Todos os semifinalistas recebem a mesma faixa de precisão. Ausências não bloqueiam a sessão: o cronômetro avança.
- Resultados da final não são enviados antes da coroação. Cada discurso e torcida é registrado uma vez por participante.
- O anfitrião continua podendo **pausar, retomar e exportar backup protegido**, inclusive durante a reta final. Partidas não persistem indefinidamente no Render gratuito: faça backup antes de fechar.
- Corrigida a contagem da reta final para levar em conta participantes temporariamente no Quarto Secreto, sem revelar a situação a outros jogadores.

## Arquivos a atualizar no GitHub

| Local | Arquivos |
|---|---|
| Raiz | `multiplayer_api.php`, `multiplayer_engine.php`, `multiplayer_especiais.php`, `multiplayer_jogo.php`, **`multiplayer_final.php` (NOVO)**, `MULTIPLAYER_BETA_11.md` |
| `assets/css` | `multiplayer_integrado.css` |
| `assets/js` | `multiplayer_jogo.js` |

**Importante:** não envie o ZIP fechado ao GitHub; extraia e envie cada arquivo na pasta certa. Faça primeiro em branch de testes ou guarde a Beta 10 como backup. Recomendamos criar sala nova para testar as regras finais.

## Testes

Rode `php testes/multiplayer_beta11.php` na versão completa. Também foram executadas as suítes existentes `multiplayer_beta_10.php`, `multiplayer_beta9.php`, `multiplayer_beta8.php` e `provas_online_beta6.php`.

A lógica PHP e as chamadas HTTP locais foram verificadas. **A interface visual mobile no Render/Safari precisa de teste manual após o deploy.**

## Limites conhecidos

O minigame da Última Resistência é uma adaptação da fórmula de energia/foco do modo solo para uma escolha simultânea com faixa segura; não é uma cópia visual 1:1 nem um teste de reflexos em tempo real. O resultado final do público é simulado. O armazenamento de salas continua temporário e depende de backup manual do anfitrião.
