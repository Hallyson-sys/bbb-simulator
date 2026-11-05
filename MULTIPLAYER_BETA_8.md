# BBB Simulator — Multiplayer Beta 8

## Objetivo

Adaptar a **formação do Paredão e a eliminação** do modo solo para uma sala de pessoas reais, sem alterar os arquivos do modo solo e preservando as funcionalidades das Betas 5–7.

## Regras da Beta 8

- O Líder faz uma indicação definitiva. **O Líder não vota** no Confessionário.
- Os outros participantes votam uma vez, em segredo. É proibido votar em si, no Líder, no indicado do Líder e em participante imune.
- A apuração divulga **totais por candidato**, nunca o eleitor associado a um voto. O Líder resolve empates pelas vagas da casa; quando o tempo de desempate se esgota, o servidor sorteia apenas entre empatados.
- Quantidade de indicados: **3 no Top 4** (quando possível), **2 no Top 5–6**, **3 com 7–10 pessoas**. Com mais de 10, a semana tem **45% de chance** de Bate e Volta: forma-se um Paredão de quatro pessoas, uma pode escapar, e permanecem três. Sem Bate e Volta, forma-se um Paredão de três.
- **Bate e Volta:** os indicados pela casa escolhem simultaneamente um número de 1 a 6 em um dos três temas (*Portas da Sorte*, *Urna Misteriosa*, *Dado da Virada*). O indicado do Líder não disputa. O servidor já tem um número secreto; vence quem mais se aproxima. Empates são resolvidos por sorteio apenas entre empatados; velocidade da conexão não conta.
- A eliminação usa **simulação de votação do público**, não votos de visitantes reais. A rejeição simulada considera as relações registradas e uma componente de sorte. O resultado é revelado após a contagem regressiva, com porcentagens somando 100%.
- O eliminado passa ao modo espectador. A semana avança normalmente. Não existe mais encerramento artificial depois de oito semanas; a versão experimental atinge a fase final quando restam até três participantes.
- A pausa/retomada do anfitrião também congela o desempate, o Bate e Volta e a noite de eliminação. O backup protegido da Beta 5 continua disponível.

## Limitações conhecidas

**Isso não é, ainda, uma cópia completa de todos os eventos do modo solo.** Big Fone, contragolpe, Paredão Falso, Quarto Secreto, poderes especiais, dinâmicas surpresa, votação real de espectadores, prova especial do Top 4 e grande final com suas variações continuam fora do escopo da Beta 8. As ações e decisões normais do jogo online das Betas 5–7 continuam presentes.

A lógica foi testada com 10, 15 e 20 jogadores, mas a interface ainda precisa ser conferida no Safari e no Render. Comece com uma sala nova. O armazenamento permanece temporário; para continuar no dia seguinte, pause e baixe o backup criptografado antes de fechar.

## Publicação no GitHub pelo celular

Envie os arquivos **extraídos** para suas pastas, preservando a estrutura:

- **Raiz**: `multiplayer_api.php`, `multiplayer_engine.php`, `multiplayer_jogo.php`, **novo** `multiplayer_paredao.php`, `MULTIPLAYER_BETA_8.md`.
- **assets/css**: `multiplayer_integrado.css`.
- **assets/js**: `multiplayer_jogo.js`.

Não envie ZIPs fechados ao GitHub e não coloque arquivos CSS/JS na raiz. Use uma branch de testes antes de fazer deploy na branch principal.

## Testes recomendados no celular

1. Com 10 jogadores, Líder indica, os demais votam, um empate é resolvido pelo Líder e a tabela mostra somente os totais.
2. Com 15/20, confirmar as duas situações possíveis (com e sem Bate e Volta), se o indicado do Líder não participa da prova e se apenas um se salva.
3. Pausar durante o Bate e Volta, retomar e confirmar que ninguém consegue jogar duas vezes.
4. Conferir se a eliminação revela percentuais depois do tempo, muda o status de um jogador e libera a próxima semana.
5. Entrar no modo solo para confirmar que continua intacto.
