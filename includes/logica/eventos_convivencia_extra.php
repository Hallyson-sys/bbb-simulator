<?php
/* Biblioteca adicional de cenas condicionadas por relação e fase. */
function es2BibliotecaExtra(){
 return [
  'muito_alta' => [
    ["es2_abraco", "🫂", "Acolhimento depois de um dia difícil", "QUARTO", "{npc} percebe que você está mais quieto e pergunta se quer conversar longe das câmeras.", [
      ["💙 Desabafar de verdade", 9, 5, 0, "es2_apoio"],
      ["😌 Agradecer e mudar de assunto", 3, 2, 0, null],
      ["🎯 Pedir um conselho sobre o jogo", 5, 3, 0, "segredo_contado"],
    ]],
    ["es2_promessa", "🛡️", "Proteção mútua", "VARANDA", "{npc} lembra dos momentos em que vocês se ajudaram e pergunta se ainda existe lealdade entre vocês.", [
      ["🤝 Reafirmar a parceria", 8, 6, 0, "es2_pacto"],
      ["🧠 Dizer que nada é garantido", 0, 0, 0, null],
      ["🚫 Não prometer proteção", -9, -5, 0, "es2_rancor"],
    ]],
    ["es2_saudade", "💭", "Saudade de casa", "JARDIM", "{npc} senta perto de você e admite que está sentindo falta das pessoas de fora.", [
      ["🫶 Ficar ali e escutar", 10, 5, 0, "es2_apoio"],
      ["🙂 Tentar animar com uma história", 6, 3, 0, "es2_apoio"],
      ["🏃 Sair para evitar o assunto", -5, -2, 0, null],
    ]],
    ["es2_duvida_aliado", "🤔", "Um aliado em dúvida", "ACADEMIA", "{npc} diz que gosta de você, mas não sabe como agir se o grupo tiver que escolher entre amigos.", [
      ["❤️ Dizer que entende a situação", 7, 4, 0, "es2_apoio"],
      ["🛡️ Pedir prioridade na votação", 8, 5, 0, "es2_pacto"],
      ["💢 Cobrar fidelidade absoluta", -7, -3, 0, "es2_rancor"],
    ]],
    ["es2_revelacao", "🔐", "Coisa que ninguém sabe", "QUARTO", "{npc} confessa que está preocupado com uma possível movimentação de votos e quer ouvir sua opinião.", [
      ["👂 Ouvir sem interromper", 8, 4, 0, "segredo_contado"],
      ["🎯 Perguntar o nome envolvido", 4, 2, 0, "segredo_contado"],
      ["🗣️ Contar a outra pessoa depois", -6, -3, 0, "es2_espalhar"],
    ]],
    ["es2_credito", "🏆", "Um reconhecimento sincero", "SALA", "{npc} diz que uma atitude sua durante esta semana fez diferença na convivência.", [
      ["💞 Reconhecer a ajuda recebida", 9, 5, 0, "es2_apoio"],
      ["😄 Brincar para aliviar o clima", 5, 2, 0, null],
      ["😏 Dizer que não fez mais que o esperado", -5, -2, 0, null],
    ]],
  ],
  'alta' => [
    ["es2_grupo_novo", "🧩", "O grupo está mudando", "VARANDA", "{npc} quer saber se vale se aproximar de outras pessoas para não ficar isolado.", [
      ["🧠 Pensar juntos sobre alternativas", 5, 3, 0, "segredo_contado"],
      ["🤝 Convidar a manter a parceria", 7, 5, 0, "es2_pacto"],
      ["🙅 Dizer que cada um cuida de si", -5, -3, 0, null],
    ]],
    ["es2_desabafo", "🍃", "Conversa ao fim do dia", "JARDIM", "{npc} comenta que teve um dia difícil na casa e gostaria de uma opinião sincera.", [
      ["🫂 Oferecer apoio", 9, 4, 0, "es2_apoio"],
      ["🎮 Falar apenas de estratégia", 3, 1, 0, null],
      ["😶 Evitar se envolver", -4, -2, 0, null],
    ]],
    ["es2_voto_sugestao", "🗳️", "Uma sondagem discreta", "COZINHA", "{npc} comenta que a casa está dividida e pergunta se você já tem alguma leitura de voto.", [
      ["🎯 Trocar uma leitura de jogo", 5, 3, 0, "segredo_contado"],
      ["🤐 Evitar falar de nomes", 1, 0, 0, null],
      ["🤝 Prometer conversar antes do Paredão", 7, 5, 0, "es2_pacto"],
    ]],
    ["es2_foto", "📸", "Um momento leve", "SALA", "{npc} sugere registrar um momento divertido para tirar um pouco da tensão desta semana.", [
      ["😄 Entrar na brincadeira", 7, 3, 0, null],
      ["🙂 Aceitar por educação", 3, 1, 0, null],
      ["🧊 Preferir ficar sozinho", -4, -1, 0, null],
    ]],
    ["es2_ciumes_amigo", "👀", "Uma aproximação inesperada", "QUARTO", "{npc} pergunta se sua proximidade com outras pessoas significa que vocês estão se afastando.", [
      ["🫶 Reafirmar a amizade", 9, 6, 0, "es2_pacto"],
      ["💬 Explicar que você conversa com todos", 4, 2, 0, null],
      ["🔥 Dizer que não deve satisfação", -8, -4, 0, "es2_rancor"],
    ]],
    ["es2_prova_grupo", "🏅", "Preparação para a próxima prova", "ACADEMIA", "{npc} pergunta se vocês deveriam treinar juntos e compartilhar dicas antes da próxima prova.", [
      ["💪 Treinar com a pessoa", 7, 4, 0, "es2_apoio"],
      ["🧠 Trocar dicas sem promessas", 4, 2, 0, null],
      ["😏 Dizer que cada um por si", -5, -2, 0, null],
    ]],
  ],
  'neutra' => [
    ["es2_primeira_prosa", "☕", "O assunto surgiu do nada", "COZINHA", "{npc} puxa um papo enquanto vocês preparam algo para comer.", [
      ["🍳 Ajudar e conversar", 6, 3, 0, "es2_apoio"],
      ["🎯 Perguntar como está o jogo", 2, 1, 0, "segredo_contado"],
      ["🙃 Responder só por educação", 0, 0, 0, null],
    ]],
    ["es2_piscina", "🏊", "Um papo à beira da piscina", "PISCINA", "{npc} senta perto de você e comenta que quase nunca tiveram uma conversa só dos dois.", [
      ["🙂 Perguntar sobre a vida fora", 7, 4, 0, "es2_apoio"],
      ["🧠 Falar do clima da casa", 4, 2, 0, null],
      ["📱 Voltar para a área comum", -2, 0, 0, null],
    ]],
    ["es2_pessoa_nova", "👥", "Um convite para se aproximar", "VARANDA", "{npc} propõe que vocês convivam mais para se conhecer sem depender de opiniões alheias.", [
      ["🤝 Aceitar a aproximação", 8, 4, 0, "es2_apoio"],
      ["🔎 Perguntar o que mudou", 3, 1, 0, null],
      ["🚪 Preferir não se aproximar", -5, -2, 0, null],
    ]],
    ["es2_quarto", "🌙", "Papo antes de dormir", "QUARTO", "{npc} comenta sobre a pressão do confinamento enquanto a casa começa a silenciar.", [
      ["💬 Compartilhar um sentimento", 7, 4, 0, "es2_apoio"],
      ["😌 Ouvir em silêncio", 4, 2, 0, null],
      ["🎯 Tentar descobrir um alvo", 2, 1, 0, "segredo_contado"],
    ]],
    ["es2_tarefa", "🧹", "Dividindo as tarefas", "COZINHA", "{npc} pede ajuda para arrumar uma área comum e comenta como é difícil agradar todo mundo.", [
      ["🧹 Ajudar sem reclamar", 6, 3, 0, "es2_apoio"],
      ["🗣️ Aproveitar para conversar", 5, 2, 0, null],
      ["🙄 Dizer que não é seu problema", -7, -3, 0, "es2_rancor"],
    ]],
    ["es2_observacao", "👁️", "Cada um tem sua leitura", "SALA", "{npc} nota que você tem observado bastante o jogo e tenta entender como você enxerga os outros.", [
      ["🧩 Compartilhar uma leitura moderada", 4, 2, 0, "segredo_contado"],
      ["🔒 Manter a estratégia em segredo", 1, 0, 0, null],
      ["💥 Criticar alguém sem provas", -6, -2, 0, "es2_espalhar"],
    ]],
  ],
  'baixa' => [
    ["es2_talher", "⚡", "O climão no almoço", "COZINHA", "{npc} faz um comentário atravessado sobre as atitudes recentes de algumas pessoas enquanto vocês almoçam.", [
      ["🗣️ Perguntar o que incomodou", 3, 1, 0, null],
      ["😏 Responder com ironia", -8, -3, 1, "es2_rancor"],
      ["🧊 Ignorar a provocação", 1, 0, 0, null],
    ]],
    ["es2_desconfia", "🕵️", "Alguém está de olho", "QUARTO", "{npc} pergunta se você anda falando sobre a pessoa para outros participantes.", [
      ["🤝 Tentar esclarecer a desconfiança", 6, 4, 0, "es2_apoio"],
      ["🙄 Dizer que é paranoia", -8, -4, 0, "es2_rancor"],
      ["🤐 Não confirmar nem negar", -2, -1, 0, null],
    ]],
    ["es2_brincadeira", "😬", "Uma brincadeira que não caiu bem", "SALA", "{npc} faz uma piada sobre seu jogo que parece ter um fundo de provocação.", [
      ["💬 Dizer que não gostou", 3, 2, 0, null],
      ["😂 Devolver com humor", 4, 1, 0, null],
      ["💥 Transformar em discussão", -10, -5, 2, "es2_rancor"],
    ]],
    ["es2_limites", "🛑", "Acertando limites", "VARANDA", "{npc} diz que prefere distância e pergunta se você consegue respeitar esse espaço.", [
      ["🧊 Respeitar a distância", 2, 1, 0, null],
      ["🫱 Oferecer uma trégua", 8, 4, 0, "es2_pacto"],
      ["🔥 Exigir explicações", -10, -5, 1, "es2_rancor"],
    ]],
    ["es2_fofoca_ressentida", "🗣️", "Seu nome foi citado", "JARDIM", "{npc} pergunta por que andaram comentando sobre vocês dois e não parece acreditar em tudo que ouviu.", [
      ["🔎 Perguntar quem contou", 2, 1, 0, "fofoca"],
      ["🤝 Explicar seu lado", 6, 3, 0, "es2_apoio"],
      ["😠 Acusar a pessoa de espalhar tudo", -9, -4, 1, "es2_rancor"],
    ]],
    ["es2_prova_tensa", "🏆", "Rivais até nas provas", "ACADEMIA", "{npc} diz que gostaria muito de ganhar uma prova antes de você.", [
      ["💪 Levar na esportiva", 4, 1, 0, null],
      ["🎮 Dizer que competição é competição", 0, 0, 0, null],
      ["🔥 Provocar de volta", -8, -3, 2, "es2_rancor"],
    ]],
  ],
  'muito_baixa' => [
    ["es2_vinganca", "🔥", "Feridas abertas", "VARANDA", "{npc} diz que ainda não esqueceu as provocações entre vocês e não quer fingir amizade.", [
      ["🙏 Reconhecer a tensão", 7, 4, 0, "es2_apoio"],
      ["🎯 Admitir que são rivais", -3, 0, 0, null],
      ["💥 Dizer que vai revidar", -12, -5, 2, "es2_rancor"],
    ]],
    ["es2_porta", "🚪", "Uma porta fechada", "QUARTO", "{npc} corta uma tentativa de conversa e pergunta o que você realmente quer depois de tantas rusgas.", [
      ["🤝 Propor uma trégua", 9, 5, 0, "es2_pacto"],
      ["🧠 Pedir só um papo objetivo", 2, 0, 0, null],
      ["😡 Retrucar no mesmo tom", -11, -4, 2, "es2_rancor"],
    ]],
    ["es2_mesa", "💢", "Discussão à mesa", "COZINHA", "{npc} questiona uma atitude sua na frente de outros participantes.", [
      ["🧘 Responder sem elevar a voz", 2, 1, 1, null],
      ["🗣️ Pedir para conversarem a sós", 4, 2, 0, "es2_apoio"],
      ["🔥 Discutir na frente de todos", -14, -6, 4, "es2_rancor"],
    ]],
    ["es2_fofoca_grave", "📢", "Acusação no corredor", "SALA", "{npc} diz ter ouvido que você está montando votos contra a pessoa, mas ainda não apresenta provas.", [
      ["🔍 Perguntar de onde veio o boato", 2, 1, 0, "fofoca"],
      ["🤐 Recusar falar sobre voto", -2, 0, 0, null],
      ["💥 Dizer que vai votar na pessoa mesmo", -11, -4, 2, "es2_rancor"],
    ]],
    ["es2_perdao", "🌤️", "Uma chance de recomeçar", "JARDIM", "{npc} pergunta se as diferenças realmente precisam definir a relação de vocês até o final.", [
      ["🫂 Propor reconciliação", 12, 7, 0, "es2_pacto"],
      ["🧊 Aceitar apenas respeito", 5, 2, 0, null],
      ["🚫 Manter a rivalidade", -9, -4, 0, "es2_rancor"],
    ]],
    ["es2_ameaca", "🎯", "Jogo declarado", "ACADEMIA", "{npc} deixa claro que considera você uma ameaça importante dentro da casa.", [
      ["🧠 Aceitar que faz parte do jogo", 0, 0, 0, "ameaca_direta"],
      ["🔥 Declarar que também mira a pessoa", -12, -5, 1, "ameaca_direta"],
      ["🤝 Tentar entender o conflito", 5, 2, 0, "ameaca_direta"],
    ]],
  ],
  'festa' => [
    ["es2_festa_piscina", "🌊", "Papo na piscina", "PISCINA", "Entre uma música e outra, {npc} chama você para perto da piscina e comenta sobre os climas da casa.", [
      ["🥂 Conversar sem falar de votos", 7, 3, 0, "es2_apoio"],
      ["👂 Perguntar se há algum movimento", 3, 1, 0, "segredo_contado"],
      ["😏 Provocar de brincadeira", -4, -1, 0, null],
    ]],
    ["es2_festa_treino", "💃", "Desafio de dança", "PISTA", "{npc} propõe uma disputa divertida de passos de dança diante do restante da casa.", [
      ["🎶 Entrar na brincadeira", 8, 3, 1, "es2_apoio"],
      ["😂 Incentivar de longe", 3, 1, 0, null],
      ["🙄 Debochar", -7, -3, 0, "es2_rancor"],
    ]],
    ["es2_festa_segredo", "🤫", "Conversa atrás da música", "FESTA", "{npc} baixa a voz perto da caixa de som: tem gente sondando nomes, e a pessoa quer saber em quem você confia.", [
      ["🧠 Compartilhar uma leitura cautelosa", 4, 2, 0, "segredo_contado"],
      ["🔐 Pedir detalhes em troca", 6, 3, 0, "segredo_contado"],
      ["🚫 Não entrar no assunto", -3, -1, 0, null],
    ]],
    ["es2_festa_rancor", "🧨", "Uma provocação na pista", "PISTA", "{npc} lembra uma situação mal resolvida e aproveita a festa para cobrar sua postura.", [
      ["🙏 Tentar baixar o clima", 7, 4, 0, "es2_apoio"],
      ["😏 Responder com ironia", -5, -2, 0, "es2_rancor"],
      ["🔥 Discutir na frente da casa", -13, -6, 3, "es2_rancor"],
    ]],
    ["es2_festa_apoio", "🌟", "Conversa ao pé do ouvido", "FESTA", "{npc} comenta que admira sua forma de lidar com os últimos acontecimentos.", [
      ["❤️ Agradecer com carinho", 8, 4, 0, "es2_apoio"],
      ["🤝 Reafirmar que pode contar com você", 9, 6, 0, "es2_pacto"],
      ["🧊 Dizer que prefere distância", -7, -2, 0, null],
    ]],
    ["es2_festa_ciumes", "👀", "Ciúmes na festa", "PISTA", "{npc} pergunta por que você passou tanto tempo conversando com outras pessoas e parece incomodado.", [
      ["🫶 Acolher o sentimento", 8, 4, 0, "es2_apoio"],
      ["💬 Explicar sem prometer nada", 3, 1, 0, null],
      ["😤 Dizer que não deve explicações", -10, -5, 1, "es2_rancor"],
    ]],
    ["es2_festa_pedido", "🤝", "Um pedido inesperado", "QUARTO", "{npc} diz que está receoso com a próxima votação e pergunta se você conseguiria apoiar a pessoa caso a situação aperte.", [
      ["🛡️ Prometer apoio condicional", 10, 6, 0, "es2_pacto"],
      ["🧠 Pedir primeiro os motivos", 4, 2, 0, "segredo_contado"],
      ["🚫 Recusar qualquer compromisso", -6, -2, 0, null],
    ]],
    ["es2_festa_eco", "📣", "Comentário que repercutiu", "SALA", "{npc} pergunta o que você achou de uma conversa polêmica que dominou parte da festa.", [
      ["🎯 Dar uma opinião cuidadosa", 5, 2, 0, null],
      ["🤐 Evitar escolher lado", 1, 0, 0, null],
      ["🔥 Tomar partido com força", -8, -3, 2, "es2_rancor"],
    ]],
    ["es2_festa_alianca", "🧩", "Movimento entre grupos", "VARANDA", "{npc} pergunta se vale a pena construir pontes com pessoas fora do próprio círculo.", [
      ["🤝 Incentivar diálogo", 6, 3, 0, "es2_apoio"],
      ["🎲 Sugerir cautela", 3, 1, 0, null],
      ["🙅 Desestimular qualquer acordo", -5, -2, 0, null],
    ]],
    ["es2_festa_madrugada", "🌙", "Os últimos na varanda", "VARANDA", "Já está tarde quando {npc} senta ao seu lado e fala sobre o que gostaria de levar dessa experiência.", [
      ["💭 Contar um sonho pessoal", 9, 5, 0, "es2_apoio"],
      ["🤫 Falar dos medos no jogo", 6, 3, 0, "segredo_contado"],
      ["🚶 Encerrar a conversa", -3, -1, 0, null],
    ]],
  ],
 ];
}

function es2ConsequenciaEvento(&$jogadores,$meuNome,$npc,$efeito){
    if (!is_string($efeito) || strpos($efeito,'es2_')!==0) return '';
    if ($efeito==='es2_pacto') {
        $_SESSION['es2_pactos'][$npc]=['rodada'=>es2Rodada(),'ate'=>es2Rodada()+1];
        if(function_exists('registrarMemoriaSocialNPC'))registrarMemoriaSocialNPC($npc,$meuNome,'fechou_pacto_comigo',2,"$meuNome reforçou uma promessa de proteção com $npc.",'es2_pacto|'.es2Rodada().'|'.$npc);
        return ' 🤝 Vocês reforçaram um compromisso de proteção, que pode influenciar decisões futuras — mas não é garantia.';
    }
    if($efeito==='es2_apoio'){
        if(function_exists('registrarMemoriaSocialNPC'))registrarMemoriaSocialNPC($npc,$meuNome,'me_apoiou',2,"$meuNome apoiou $npc numa conversa importante.",'es2_apoio|'.es2Rodada().'|'.$npc.'|'.mt_rand(1000,9999));
        return ' 🧠 A pessoa poderá lembrar dessa atitude.';
    }
    if($efeito==='es2_rancor'){
        if(function_exists('registrarMemoriaSocialNPC'))registrarMemoriaSocialNPC($npc,$meuNome,'me_desrespeitou',2,"$meuNome e $npc tiveram mais um atrito na convivência.",'es2_rancor|'.es2Rodada().'|'.$npc.'|'.mt_rand(1000,9999));
        return ' 🔥 O episódio pode pesar na próxima votação e em novas conversas.';
    }
    if($efeito==='es2_espalhar'){
        if(function_exists('alterarAfinidade'))alterarAfinidade($jogadores,$npc,$meuNome,-4,5,-5);
        return ' 👀 Espalhar suspeitas sem provas pode abalar a confiança.';
    }
    return '';
}
