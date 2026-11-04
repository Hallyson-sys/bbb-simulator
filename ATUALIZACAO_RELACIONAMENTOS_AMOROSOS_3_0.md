# BBB Simulator — Relacionamentos Amorosos 3.0

Base: Jogo da Discórdia 3.0 + Iniciativa dos NPCs 2.0 + Interações 4.0.4.

## O que foi adicionado

- **Vida a Dois:** painel recolhível próprio nas Interações, somente se o protagonista tiver um namoro oficial com participante ativo. Compatível com saves que gravaram o casal apenas em um dos sentidos.
- **Conversa em etapas, opcional e com respostas do jogador:** carinho, apoio durante o jogo, planos para depois do programa, equilíbrio entre namoro e estratégia, confiança. Inseguranças e reconciliação só aparecem com tensão existente na relação; não são criadas acusações de traição.
- **Decisões importantes:** conversa sobre o futuro do namoro com possibilidade de reafirmar a relação, pedir espaço ou propor término. O término exige **uma segunda confirmação explícita**. Uma recusa/cancelamento preserva o casal.
- **Reações e efeitos moderados:** relação, confiança, romance, memórias sociais e traços como lealdade e reatividade influenciam como as conversas repercutem. Nada força votos ou alianças.
- **NPCs namorando NPCs:** até uma tentativa semanal de cena observável, com apoio, conversa estratégica, atritos, tentativa de reconciliação ou eventual separação com desgaste real. Não afeta automaticamente o namoro do protagonista.
- **Relações em Movimento:** acontecimentos observáveis de casais NPCs aparecem também nesse painel, no Ao Vivo e no histórico; o conteúdo das conversas privadas do jogador permanece confidencial.
- **Histórico privado da relação:** até 30 momentos registrados. Diálogos têm token e proteção contra resposta duplicada, expiram ao mudar de fase/semana ou perder parceiro.
- **Frequência:** uma cena especial de casal por semana, sem consumir ações normais; não há limite por parceiro nas ações clássicas e Interações 4.0.
- **Compatibilidade:** sem estrutura obrigatória nova em saves; as chaves `rom3_*` são criadas só quando utilizadas. O romance inicial e os pedidos de namoro existentes continuam como antes.

## Integração técnica

- `includes/logica/romance_3.php`: motor, ações do jogador, simulador de NPCs.
- `includes/actions/romance_3.php`: roteamento POST/Redirect/GET e proteção de ações pendentes.
- `includes/components/romance_3.php`: cenas, escolhas, confirmação, histórico.
- `jogo.php`: carregamento, sincronização, gatilhos e roteamento.
- `includes/components/interacoes.php`: apresenta o painel sem remover as quatro categorias atuais.
- `includes/components/rotina_casa.php`: reúne observações públicas das relações e dos casais.
- `includes/logica/iniciativa_npc_2.php` / `includes/actions/avancar_fase.php`: impedem cenas simultâneas e avanço com diálogo pendente.
- `includes/logica/dev_diagnosticos.php` / `testes/dev3_regressao.php`: checagens novas de saves e cenários 45–53.
- `testes/relacionamentos_amorosos_3.php`: teste dedicado com 32 verificações.
- `assets/css/jogo.css`: estilos responsivos do painel.

## Testes para executar no XAMPP

1. Abra uma temporada com namoro oficial, entre em **Interações**, expanda **❤️ Vida a Dois** e escolha uma cena. Confirme o desfecho e o contador de Interações.
2. Abra **Romance e relacionamento** da categoria Social: ela permanece disponível e conversa com o namorado sem limite individual.
3. Durante um conflito real, confira a opção **Conversar sobre inseguranças**; num casal tranquilo ela não deve surgir.
4. Teste **Conversar sobre o futuro do namoro → pensar em terminar → Não, quero continuar**. O casal deve continuar oficial.
5. Em um save separado de teste, confirme o término e verifique o desaparecimento do painel, o registro público do fim e o desfecho privado.
6. Teste no Modo DEV com **Executar bateria completa**. No terminal: `php testes/dev3_regressao.php` e `php testes/relacionamentos_amorosos_3.php`.

A bateria automatizada e as verificações locais não substituem uma temporada inteira jogada no navegador/celular.
