# BBB Simulator — Relações Sociais 4.0

## Novidades
- Evolução independente de amizades, rivalidades e reconciliações entre NPCs durante Interações e Festa.
- Aproximação romântica gradual **somente** para personagens que já possuem interesse mútuo; possibilidade de assumir ou encerrar relacionamentos sob condições coerentes.
- Duplas especialmente próximas dentro de uma mesma aliança podem fortalecer uma parceria interna temporária (subgrupo), que influencia de forma moderada a proteção em votos/indicações e a lealdade.
- Crises internas entre membros que perderam a confiança podem desgastar alianças. As regras de saída e recrutamento existentes permanecem em uso.
- Novo painel recolhível **💞 Relações em Movimento** nas telas de Interações e Festa, além de acontecimentos observáveis no Ao Vivo e no histórico.
- Influência da personalidade evolutiva nos tipos de acontecimentos.

## Segurança da lógica
- No máximo uma oportunidade em Interações 1 e outra na Festa por semana, com sorteio e prevenção de repetição no mesmo período.
- Não inventa votos secretos ou expõe o conteúdo de conversas reservadas.
- Não escolhe NPCs eliminados, fora da casa ou no Quarto Secreto.
- A identidade original e a personalidade-base de cada participante permanecem intactas.
- Popularidade e pesos internos permanecem ocultos.
- Acompanhamento persistido em `$_SESSION['r4_estado']`, compatível com saves anteriores.

## Testes sugeridos
1. Começar na Semana 2 e avançar Interações e Festa; conferir o Ao Vivo e o novo painel.
2. Verificar se recarregar a página não repete eventos nem altera relações outra vez.
3. Acompanhar uma dupla de aliados nas semanas seguintes e testar se a lealdade influencia escolhas sem garantir votos.
4. Acompanhar um casal em crise e observar possíveis reconciliações, afastamento e rompimento.
5. Testar saves antigos e eliminados, incluindo Paredão Falso e modo espectador.
