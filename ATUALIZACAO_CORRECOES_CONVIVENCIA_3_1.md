# BBB Simulator — Correções de Convivência 3.1

Base: Rotina da Casa 2.0.

## 1. Concordância de gênero no Ao Vivo
- Complementado o elenco de nomes conhecidos (Lara, Allan, Samuel, etc.).
- Ajustes de concordância apenas quando a associação do nome com o adjetivo é explícita.
- Conversas entre duas mulheres usam "as duas"/"juntas"; nomes de gênero desconhecido não são forçados.
- Templates problemáticos de Rotina, VT e reações da produção foram reescritos de modo neutro.
- A correção é aplicada também ao mostrar acontecimentos já salvos no Ao Vivo.

## 2. Carta Misteriosa
- O comunicado inicial anuncia somente a existência da carta, sem revelar o efeito.
- A descrição do poder de voto em peso dois aparece somente quando a carta for entregue após a Festa.
- Cálculo do voto, apuração e registro de entrega são preservados.

## 3. Desempate do Líder
- Indicação do Líder/Big Fone ocupa vaga fixa e não é desfeita.
- As demais vagas são preenchidas por quem recebeu mais votos.
- Em empate no limite, o Líder escolhe **a quantidade exata de vagas empatadas**, inclusive nas formações com quatro ou cinco indicados.
- Se o Líder for NPC, a IA decide entre os empatados, uma vaga por vez.
- A apuração é armazenada antes da decisão para evitar trocar os votos durante um recarregamento.
- O tamanho do Paredão é reinicializado a cada semana e não herda a quantidade de vagas da semana anterior.
- A tela explica quantas vagas são da casa e quais indicações já estavam fixas.

## 4. Loja do Público — Revelar Queridômetro (30 moedas)
- Registrar em sessão o remetente e emoji de cada envio realizado a partir desta atualização.
- O anonimato do resultado geral e dos NPCs permanece; apenas quem paga e é dono dos emojis vê os remetentes.
- A compra ocorre depois do Queridômetro, custa 30 moedas e só é cobrada uma vez por semana.
- O resultado é privado, não vai para o Ao Vivo.
- Não inventa autores para saves antigos; sem registros disponíveis, a compra é recusada sem cobrança.
- As outras vantagens da Loja permanecem disponíveis.

## Testes locais
- Sintaxe de todos os PHPs.
- Compra, compra repetida, registros antigos e anonimato dos totais.
- Carta antes da entrega.
- Concordância Lara/Allan.
- Empate com uma e com duas vagas a decidir, com e sem empate.

Falta a validação completa de uma temporada e da experiência visual mobile no navegador.
