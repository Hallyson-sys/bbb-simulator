<?php
/**
 * Relatos públicos das interações. Não lê popularidade, voto secreto ou segredos.
 * Descreve somente ações que de fato foram executadas na simulação.
 */
function es3RelatoAoVivo($tipo, $ator, $alvo = '', $contexto = 'interacoes', $estado = '') {
    $ator = trim((string)$ator);
    $alvo = trim((string)$alvo);
    if ($ator === '') return '';
    $festa = ($contexto === 'festa');
    $locais = $festa
        ? ['perto da pista de dança', 'na área externa da festa', 'ao lado do bar', 'perto da piscina', 'num canto mais tranquilo da festa']
        : ['na cozinha', 'à beira da piscina', 'no jardim', 'na varanda', 'no quarto', 'na sala', 'perto da academia'];
    $local = $locais[array_rand($locais)];
    $dois = $alvo !== '' ? "$ator e $alvo" : $ator;
    $modelos = [
        'conversar' => [
            "💬 $dois se encontraram $local e conversaram sobre os últimos acontecimentos da casa.",
            "🗣️ $ator chamou $alvo para um papo $local. O assunto passou pelas estratégias da semana.",
            "🏠 $dois ficaram um tempo $local falando sobre a convivência e os rumos do jogo.",
            "💬 $dois aproveitaram um momento $local para trocar impressões sobre a próxima dinâmica.",
            "👀 $ator e $alvo conversaram $local sobre como anda o clima entre os participantes."
        ],
        'fofoca' => [
            "🗣️ $ator procurou $alvo $local para trocar impressões sobre o clima na casa.",
            "👀 $dois trocaram comentários $local sobre acontecimentos recentes; nem tudo foi dito em voz alta.",
            "🤫 $ator e $alvo conversaram discretamente $local. Outras pessoas perceberam os cochichos.",
            "🐍 $ator puxou um assunto delicado com $alvo $local, movimentando a convivência."
        ],
        'intriga' => [
            "🔥 $ator espalhou versões diferentes de uma conversa e o clima entre os envolvidos ficou tenso.",
            "👀 Uma conversa iniciada por $ator gerou desconfiança entre participantes da casa.",
            "🐍 $ator tentou provocar um mal-entendido, mas a reação da casa foi imprevisível."
        ],
        'intriga_falhou' => [
            "👀 $ator tentou criar um mal-entendido, mas os participantes perceberam as contradições.",
            "🫢 Uma conversa iniciada por $ator levantou suspeitas, mas a intriga não convenceu.",
            "🐍 $ator tentou colocar participantes uns contra os outros, porém a história não se sustentou."
        ],
        'reconciliar' => [
            "🤝 $ator procurou $alvo $local para amenizar uma tensão antiga.",
            "💙 $dois tiveram uma conversa mais leve $local depois de dias de clima estranho.",
            "💬 $ator e $alvo tentaram deixar de lado as diferenças durante um papo $local."
        ],
        'discutir' => [
            "🔥 $dois elevaram o tom $local ao discordarem sobre atitudes recentes.",
            "💥 Uma conversa entre $ator e $alvo $local virou bate-boca sobre o jogo.",
            "😤 $ator cobrou explicações de $alvo $local, e o clima pesou.",
            "📺 $dois protagonizaram uma troca de farpas $local após uma divergência."
        ],
        'alianca' => [
            "🤝 $dois se aproximaram $local e conversaram sobre jogar com mais confiança.",
            "👀 $ator e $alvo tiveram uma conversa estratégica $local. A proximidade chamou atenção.",
            "🤝 $dois demonstraram sintonia em um papo $local sobre proteção no jogo."
        ],
        'festa_aproximar' => [
            "🥂 $dois passaram parte da festa conversando perto da pista sobre a semana.",
            "🎉 $ator encontrou $alvo $local e os dois aproveitaram um momento descontraído.",
            "💬 $dois conversaram $local sobre a convivência enquanto a música tocava."
        ],
        'flertar' => [
            "💕 $ator tentou se aproximar de $alvo $local, e o clima entre eles chamou atenção.",
            "😘 $ator flertou com $alvo $local durante a festa.",
            "💘 Uma troca de olhares entre $dois $local virou assunto entre quem estava por perto."
        ],
        'dancar' => [
            "🕺 $ator chamou $alvo para dançar, e os dois aproveitaram algumas músicas juntos.",
            "💃 $dois dividiram a pista de dança e entraram no clima da festa.",
            "🎶 $dois dançaram na festa e acabaram atraindo alguns olhares."
        ],
        'vt' => [
            "📸 $ator aproveitou a animação da festa para fazer uma cena diante das câmeras.",
            "📺 $ator criou um momento chamativo $local, movimentando os comentários sobre a festa.",
            "🎉 $ator entrou no clima da festa e protagonizou um momento que rendeu assunto."
        ],
        'cozinhar' => [
            "🍳 $ator preparou uma refeição na cozinha e aproveitou para conversar com quem apareceu por lá.",
            "🥘 $ator assumiu o fogão e ajudou a reunir alguns participantes ao redor da mesa.",
            "🍽️ $ator cuidou de uma refeição e o assunto na cozinha rendeu por um bom tempo."
        ],
        'observar' => [
            "👀 $ator ficou mais na sua durante a tarde, observando a movimentação da casa.",
            "🌿 $ator aproveitou um momento tranquilo no jardim para pensar no jogo.",
            "🤫 $ator preferiu acompanhar as conversas sem se envolver diretamente."
        ],
        'lider' => [
            "👑 $ator procurou $alvo $local para conversar sobre os rumos da liderança.",
            "💬 $ator tentou se aproximar do Líder $alvo durante uma conversa $local."
        ],
    ];
    $opcoes = $modelos[$tipo] ?? [];
    if (!$opcoes) return '';
    // Sem revelar variáveis ocultas: só a consequência observável.
    $usados = $_SESSION['es3_relatos_usados'] ?? [];
    if (!is_array($usados)) $usados = [];
    $novos = array_values(array_diff($opcoes, $usados));
    $pool = $novos ?: $opcoes;
    $saida = $pool[array_rand($pool)];
    if ($alvo !== '' && function_exists('generoParticipanteBBB')
        && generoParticipanteBBB($ator) === 'f' && generoParticipanteBBB($alvo) === 'f') {
        $saida = str_replace(['os dois', 'juntos', 'entre eles'], ['as duas', 'juntas', 'entre elas'], $saida);
    }
    $_SESSION['es3_relatos_usados'] = array_slice(array_merge($usados, [$saida]), -36);
    return $saida;
}

/** VTs do jogador e NPCs, sem vazar a métrica interna de popularidade. */
function es3RelatoVT($nome, $tipo) {
    $opcoes = [
        'emocionante' => [
            "🎬 $nome aproveitou o momento a sós com as câmeras para falar da saudade de casa e do que já viveu no programa.",
            "🥺 Uma conversa sincera de $nome sobre os desafios da convivência entrou nos melhores momentos da rodada.",
            "💙 $nome se emocionou ao relembrar o caminho que percorreu desde a estreia."
        ],
        'engracado' => [
            "😂 $nome transformou uma situação banal da cozinha numa história engraçada que animou a casa.",
            "🎭 $nome fez uma imitação bem-humorada de um acontecimento recente e arrancou risadas.",
            "🤣 Uma brincadeira improvisada por $nome virou assunto durante o resto do dia."
        ],
        'forcado' => [
            "🎬 $nome ensaiou uma cena para as câmeras, mas parte da casa achou o momento artificial.",
            "🙄 Uma tentativa de $nome de chamar atenção rendeu olhares atravessados entre os participantes.",
            "👀 $nome tentou protagonizar uma situação que acabou dividindo opiniões dentro da casa."
        ],
        'vilao' => [
            "🐍 $nome fez um comentário provocador que reacendeu uma rivalidade na casa.",
            "🔥 $nome não escondeu sua posição durante uma discussão e deu assunto à edição.",
            "🎬 $nome resolveu expor sua leitura de jogo de forma direta e virou assunto entre os participantes."
        ],
        'vitima' => [
            "💭 $nome falou sobre a pressão da convivência e a sensação de que algumas pessoas não entendem suas atitudes.",
            "🥺 $nome se emocionou ao desabafar sobre uma situação que marcou sua semana.",
            "🎬 $nome relatou que tem se sentido distante dos demais em algumas conversas da casa."
        ],
        'protagonista' => [
            "🌟 $nome esteve no centro de uma movimentação importante e o momento virou destaque da edição.",
            "📺 $nome puxou uma conversa que fez vários participantes repensarem o clima da casa.",
            "🎬 Um posicionamento de $nome movimentou o dia e foi retomado nas conversas posteriores."
        ],
    ];
    $templates = $opcoes[$tipo] ?? ["📺 $nome protagonizou um momento marcante durante a convivência."];
    $vistos = $_SESSION['es3_vts_usados'] ?? [];
    $pool = array_values(array_diff($templates, is_array($vistos) ? $vistos : []));
    $frase = ($pool ?: $templates)[array_rand($pool ?: $templates)];
    $_SESSION['es3_vts_usados'] = array_slice(array_merge(is_array($vistos) ? $vistos : [], [$frase]), -24);
    return $frase;
}
