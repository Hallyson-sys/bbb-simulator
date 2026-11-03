# BBB Simulator — Objetivos Pessoais dos NPCs V1

Atualização cumulativa, aplicada sobre a versão **Modo DEV 3.0 / Liderança em Dupla**.

## Como funciona

- Ao entrar no jogo, cada NPC ativo recebe uma **motivação privada** adequada às suas relações, alianças, personalidade evolutiva e situação da casa. O jogador não recebe um objetivo automático.
- Tipos: **proteger aliado, superar rival, reconciliar, construir aliança, fortalecer aliança, vencer prova, evitar Paredão e chegar à final**. Um objetivo com alvo só é atribuído se existir um participante ativo compatível.
- As metas influenciam, com bônus limitados, a pontuação de alvos para **voto, indicação, VIP, imunidade, proteção e interações**, a disposição nas alianças e, no caso de querer vencer prova, o desempenho simulado em provas.
- Nada disso força um voto, vitória, romance ou aliança. As regras de imunidade, alianças, popularidade oculta e Paredão continuam valendo.
- Os objetivos são reavaliados **uma vez por semana**, ou quando o alvo é eliminado. Vitórias de prova, mudança de aliança, reconciliação ou decepções importantes podem encerrar um objetivo. O histórico privado fica armazenado no save.
- O Confessionário pode apresentar falas **genéricas sobre a motivação**, sem citar quem é alvo de um plano secreto. A motivação e a pontuação não são publicadas no Ao Vivo, Feed ou Histórico público.
- Dados persistem em `$_SESSION['on1_objetivos']` e `$_SESSION['on1_historico']`, e portanto são incluídos pelo sistema atual de saves (snapshot da sessão). Saves antigos inicializam as metas de forma segura na primeira visita ao jogo.

## Modo DEV 3.0

- Um painel de diagnóstico no `modo_teste.php` exibe os objetivos internos **somente em localhost**.
- A bateria automatizada agora conta com **27 testes sintéticos**, incluindo estabilidade das metas, efeito nos votos, proteção e exclusão de eliminados.
- Diagnóstico do save identifica objetivos com tipos ou alvos inválidos, sem alterar o save.

## Testes no terminal

```bash
php testes/dev3_regressao.php
php testes/objetivos_pessoais_npc.php
```

## Limitações

- A motivação interna não é uma tarefa controlada pelo usuário, nem um poder adicional: apenas orienta a tomada de decisão probabilística dos NPCs.
- Ainda é necessário testar uma temporada completa no navegador e no iPhone, especialmente o equilíbrio do bônus no decorrer de várias semanas.
