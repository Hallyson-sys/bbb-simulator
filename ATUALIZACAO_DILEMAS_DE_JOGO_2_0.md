# ⚖️ BBB Simulator — Dilemas de Jogo 2.0

## O que foi acrescentado

- **14 situações diferentes**, selecionadas por contexto: amizade, rivalidade, promessa de proteção, convivência, alianças, reconciliação, pressão pública e Festa.
- Até **um dilema por semana**, com tentativas durante Interações e Festa e controle de repetição por tipo. Pode haver semanas sem dilema.
- **Três respostas por situação**, com desfechos narrativos próprios e impactos verificáveis em afinidade, confiança, rivalidade ou memória social.
- Algumas promessas geram **pactos de até a semana seguinte**, desde que o NPC aceite. Esses pactos mudam moderadamente os pesos de voto e proteção da IA. Não garantem fidelidade, imunidade ou voto.
- Dilemas de segredo só são elegíveis se houver informação registrada como descoberta pelo jogador. O compartilhamento entra na rede privada de fofocas; informações com verdade desconhecida continuam sem classificação interna de verdade.
- **Histórico privado das decisões** nas telas de Interações e Festa, persistido no save.
- Painel do **Modo DEV local** para forçar um dilema em uma temporada de teste; inclui novos diagnósticos automáticos.

## Privacidade e regras

- Conversas privadas não são publicadas no Ao Vivo como estratégias conhecidas por todos. Apenas posicionamentos públicos geram acontecimentos genéricos.
- Os NPCs não sabem resultados de votos secretos ou informações que nunca presenciaram.
- Dilemas não surgem para jogadores no Modo Espectador nem com participantes eliminados.
- Respostas têm token de sessão, validação no servidor e proteção contra envio duplicado. Ao recarregar a página, não se aplica novamente o efeito.
- Não altera percentuais visíveis da popularidade.

## Testes

```bash
php testes/dilemas_jogo_2.php
php testes/dev3_regressao.php
```

O segundo comando confere também a sintaxe dos arquivos PHP. O PHP precisa das extensões normais do simulador (principalmente **mbstring**) para executar o jogo no navegador. O teste isolado usa um fallback de texto ASCII apenas no processo CLI de diagnóstico, não no projeto em execução.

## Arquivos principais

- `includes/logica/dilemas_jogo.php`
- `includes/actions/dilemas_jogo.php`
- `includes/components/dilemas_jogo.php`
- `assets/css/dilemas_jogo_2.css`
- Integração com `jogo.php`, `includes/logica/inteligencia_npc.php`, Interações, Festa e diagnósticos DEV.

Esta versão foi verificada com testes isolados e um fluxo HTTP local de exibição e resposta. Uma partida completa no Safari/mobile continua recomendada antes do deploy definitivo.
