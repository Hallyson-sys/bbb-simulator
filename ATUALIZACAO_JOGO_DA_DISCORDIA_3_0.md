# 🔥 BBB Simulator — Jogo da Discórdia 3.0

## Base
Continuação da versão **Iniciativa dos NPCs 2.0**, com todas as funcionalidades prévias preservadas (Interações 4.0.4, Dilemas, Relações Sociais, Objetivos dos NPCs, Bate-Papo com Eliminado, Liderança em Dupla e Modo DEV).

## Nova mecânica
Em qualquer tema da Discórdia, a escolha principal leva a uma sequência de debate:

1. **Apontamento:** escolha normalmente os participantes, categorias ou pódio.
2. **Argumento:** escolha entre cobrança estratégica, convivência e — se existir — um **fato público registrado** entre você e o alvo.
3. **Resposta do NPC:** fala contextual, baseada no tipo de tema, na relação e na reatividade.
4. **Tréplica:** sustentar, ouvir/reconhecer ou encerrar. Temas positivos têm escolhas diferentes.
5. **Repercussões:** são aplicados os efeitos tradicionais do tema, mais um impacto **moderado** da tréplica, memória social no NPC, registro público no Ao Vivo e Histórico da Temporada e uma reação de testemunha. Nas Interações seguintes, há um card com a conversa registrada.

## Compatibilidade e segurança
- Os **12 temas anteriores** permanecem disponíveis, incluindo Filme BBB, Quem É Quem, Pódio e prêmios.
- Os NPCs continuam participando da dinâmica com o mecanismo já existente.
- **Top 8:** sem Jogo da Discórdia, conforme regras atuais.
- Acontecimentos privados (confessionário, votos secretos e planos pessoais) **não** são usados como provas na fala do jogador.
- Só são aceitos participantes ativos e opções válidas; os formulários intermediários usam token de sessão.
- Ao atualizar a página, a etapa corrente permanece; ao tentar enviar resposta novamente, não há efeitos duplicados.
- Saves anteriores são compatíveis e podem começar a nova sequência numa fase de Discórdia que já estava aberta.
- A mudança de semana/fase descarta apenas a etapa pendente; o histórico já registrado é preservado.

## Testes
- `php testes/discordia_3.php` — cenários específicos da Discórdia 3.0.
- `php testes/dev3_regressao.php` — bateria de **44 cenários** mais verificação de sintaxe.
- Realizados testes HTTP locais com PHP 8.4 para apontamento, argumento factual, resposta, tréplica, transição para Interações 3 e reenvio sem duplicar o resultado.
- Durante testes HTTP, como o PHP local não contém `mbstring`, foi utilizado um fallback **somente no processo de teste**. O projeto de distribuição não recebe esse fallback; o XAMPP deve manter `mbstring` habilitado.

**Observação:** a experiência visual em dispositivos móveis e uma temporada inteira ainda exigem validação manual.
