# Ajuste de interface — Interações 4.0.2

- Restauradas todas as quatro categorias de ações: Social, Estratégia, Conflitos e Alianças/Ações Clássicas.
- Removido **somente** o painel/cabeçalho duplicado “Interações 4.0”, mantendo o painel "Interações — restantes" e todas as ações disponíveis logo abaixo.
- Corrigido o efeito visual da grade CSS que esticava um card fechado à altura de outro aberto (`align-items: start`).
- Os cards HTML `<details>` compartilham um `name` para que uma única categoria fique aberta de cada vez. A primeira inicia aberta.
- Conversar clássico permanece acessível em "Alianças e ações clássicas"; Conversar de verdade e outras ações novas continuam na categoria Social.
- Removido o bloqueio por NPC das ações novas (incluindo mediação). É possível interagir com a mesma pessoa em sequência, respeitando apenas o contador geral de interações.
- O controle de resposta duplicada e o bloqueio de avanço durante uma decisão pendente permanecem ativos.
- Saves e históricos antigos são preservados.

## Testes

Execute `php testes/interacoes_4.php` e `php testes/dev3_regressao.php` para verificar os cenários.
