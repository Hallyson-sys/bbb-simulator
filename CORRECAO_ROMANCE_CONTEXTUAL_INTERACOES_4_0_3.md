# BBB Simulator — Interações 4.0.3: romance contextual

## O que foi corrigido

- A ação **❤️ Romance e relacionamento** adapta o diálogo ao estado oficial de `$_SESSION['casais']`, em vez de depender apenas da pontuação de romance.
- Para um namoro já existente, os assuntos passam a ser **Demonstrar carinho**, **Cuidar um do outro** e **Conversar sobre o namoro**. As respostas deixam de ser "Admitir que tem interesse", "Flertar sem pressa" e "Respeitar o tempo".
- O namoro pode ser fortalecido por carinho, apoio e conversas; se houver tensão real registrada, a cena reconhece as dificuldades sem terminar ou reiniciar o namoro automaticamente.
- Para quem está se aproximando sem um namoro oficial, permanecem flerte e conversas sobre sentimentos, sem transformar a aproximação num pedido de namoro automático.
- Se o jogador ou o NPC já namora outra pessoa, a interação evita flerte e oferece conversa sobre limites, sem aumentar o romance.
- O formulário atualiza os assuntos ao selecionar o participante; as verificações definitivas acontecem no PHP, não no navegador.
- Relatos do Ao Vivo sobre conversas de namoro deixam de tratar automaticamente o tema como "amizade entre eles". Conteúdo privado não é publicado.
- Diálogos românticos pendentes de saves anteriores são convertidos em cena coerente com o estado atual do relacionamento, preservando o token e os efeitos já aplicados.
- Mantidas as quatro categorias (Social, Estratégia, Conflitos, Alianças/Ações Clássicas) e a possibilidade de interagir repetidamente com a mesma pessoa enquanto houver ações gerais.

## Testes

A partir da pasta `bbb-simulator`:

```bash
php testes/romance_interacoes_contextual.php
php testes/interacoes_4.php
php testes/categorias_interacoes.php
php testes/dev3_regressao.php
```

Os testes de romance verificam casais oficiais, romance sem namoro, compromisso com outra pessoa, não duplicação de efeitos, diálogos antigos, alterações no Ao Vivo e formulário contextual.
