<?php
/**
 * Feed BBB 3.0 — torcidas, hashtags e mutirões.
 * Fonte exclusiva: acontecimentos públicos já publicados no Feed e Paredão formado.
 * Popularidade e votos secretos nunca são usados para ordenar/rotular torcidas.
 */

function feed3Estado(): array
{
    if (!isset($_SESSION['feed3']) || !is_array($_SESSION['feed3'])) {
        $_SESSION['feed3'] = [];
    }
    $_SESSION['feed3'] += ['processados' => [], 'movimentos' => [], 'torcidas' => [], 'quantidade_semana' => []];
    return $_SESSION['feed3'];
}

function feed3Escolha(array $opcoes, string $chave): string
{
    return $opcoes[(int)(sprintf('%u', crc32($chave)) % count($opcoes))];
}

function feed3Tag(string $nome, string $tipo = 'Team'): string
{
    return '#' . $tipo . hashtagNomeFeed($nome);
}

function feed3Post(array $jogadores, string $texto, string $categoria, array $nomes, string $tipo, array $tags): bool
{
    if (!adicionarPostFeedPublico($jogadores, $texto, $categoria, $nomes)) {
        return false;
    }
    $indice = array_key_last($_SESSION['feed_publico']);
    if ($indice === null) return false;
    $_SESSION['feed_publico'][$indice]['feed3_tipo'] = $tipo;
    $_SESSION['feed_publico'][$indice]['feed3_tags'] = array_values(array_unique($tags));
    $autores = $tipo === 'mutirao'
        ? [['Central de Mutirões', '@centralmutiroes'], ['Plantão das Torcidas', '@plantaotorcidas']]
        : [['Radar das Torcidas', '@radartorcida'], ['Arquibancada BBB', '@arquibancadabbb'], ['Central BBB', '@centralbbb']];
    [$autor, $arroba] = $autores[(int)(sprintf('%u', crc32($texto)) % count($autores))];
    $_SESSION['feed_publico'][$indice]['autor'] = $autor;
    $_SESSION['feed_publico'][$indice]['arroba'] = $arroba;
    return true;
}

function feed3TemParticipante(array $jogadores, string $nome): bool
{
    foreach ($jogadores as $j) {
        if (($j['nome'] ?? '') === $nome && empty($j['eliminado'])) return true;
    }
    return false;
}

function feed3MovimentosAtivos(int $semana): array
{
    $estado = feed3Estado();
    $valor = $estado['movimentos'][$semana] ?? [];
    return is_array($valor) ? $valor : [];
}

/** Recebe apenas posts públicos já publicados. Não lê relações ou popularidade interna. */
function feed3Atualizar(array $jogadores, string $fase, int $semana): void
{
    feed3Estado();
    $semana = max(1, $semana);
    // No máximo seis reações de torcida a acontecimentos públicos por semana.
    $limitePosts = 6;
    $emitidos = (int)($_SESSION['feed3']['quantidade_semana'][$semana] ?? 0);
    foreach (array_reverse($_SESSION['feed_publico'] ?? []) as $post) {
        if (isset($post['feed3_tipo']) || (int)($post['rodada'] ?? 0) !== $semana) continue;
        $categoria = (string)($post['categoria'] ?? '');
        if (!in_array($categoria, ['lider','anjo','monstro','treta','rivalidade','romance','paredao','eliminacao'], true)) continue;
        $id = (string)($post['id'] ?? '');
        $chave = 'reacao|' . $semana . '|' . $id;
        if ($id === '' || isset($_SESSION['feed3']['processados'][$chave])) continue;
        $nomes = [];
        foreach ($jogadores as $j) {
            $nome = (string)($j['nome'] ?? '');
            if ($nome !== '' && preg_match('/(?<![\p{L}])' . preg_quote($nome, '/') . '(?![\p{L}])/iu', (string)($post['texto'] ?? ''))) {
                $nomes[] = $nome;
            }
        }
        $_SESSION['feed3']['processados'][$chave] = true;
        if (!$nomes) continue;
        $nome = $nomes[0];
        $tag = feed3Tag($nome);
        $templates = match ($categoria) {
            'lider' => ["A torcida de $nome já separou os melhores momentos da prova. $tag", "A liderança de $nome virou assunto nos grupos da edição. $tag"],
            'anjo' => ["Tem fã de $nome revendo a Prova do Anjo inteira. $tag", "As torcidas estão comentando a vitória de $nome no Anjo. $tag"],
            'monstro' => ["Os cortes do Monstro de $nome estão circulando por toda parte. $tag", "Quem acompanha $nome tá debatendo como foi o Castigo do Monstro. $tag"],
            'treta', 'rivalidade' => ["A discussão envolvendo $nome dividiu opiniões nas redes; tem defesa e crítica por todo lado. $tag", "O assunto de hoje entre quem acompanha $nome é a treta que rolou na casa. $tag"],
            'romance' => ["A galera que acompanha $nome já montou uma pasta só com os momentos da semana. $tag", "O clima de romance envolvendo $nome tá rendendo conversa entre as torcidas. $tag"],
            'paredao' => ["A torcida de $nome começou a organizar grupos para acompanhar a berlinda. $tag", "A semana de Paredão movimentou quem acompanha $nome. $tag"],
            'eliminacao' => ["Quem acompanhava $nome está revendo os momentos marcantes da passagem pela casa. $tag", "Depois da eliminação de $nome, o público voltou a comentar a trajetória na casa. $tag"],
            default => [],
        };
        if ($templates && $emitidos < $limitePosts && feed3Post($jogadores, feed3Escolha($templates, $chave), $categoria, $nomes, 'torcida', [$tag])) {
            $emitidos++;
            $_SESSION['feed3']['quantidade_semana'][$semana] = $emitidos;
        }
        if (feed3TemParticipante($jogadores, $nome)) {
            $_SESSION['feed3']['torcidas'][$semana][$nome] = [
                'nome' => $nome, 'tag' => $tag, 'assunto' => $categoria,
            ];
        }
    }
    // Mutirões só são criados após a formação efetiva do Paredão desta semana.
    if (empty($_SESSION['paredao_formado']) || $fase !== 'paredao') return;
    $indicados = array_values(array_unique(array_filter(array_map(static function ($item) {
        return is_array($item) ? (string)($item['nome'] ?? '') : (is_string($item) ? $item : '');
    }, (array)($_SESSION['paredao'] ?? [])))));
    if (count($indicados) < 2) return;
    foreach ($indicados as $nome) {
        if (!feed3TemParticipante($jogadores, $nome)) continue;
        $chave = "mutirao|$semana|$nome";
        if (isset($_SESSION['feed3']['processados'][$chave])) continue;
        $_SESSION['feed3']['processados'][$chave] = true;
        $ficar = '#Fica' . hashtagNomeFeed($nome);
        $sair = '#Fora' . hashtagNomeFeed($nome);
        $texto = feed3Escolha([
            "Paredão formado! Grupos de fãs começaram a organizar mobilizações com $ficar e $sair. O clima é de disputa nas redes.",
            "Já tem torcida reunindo gente para o Paredão de $nome. Entre $ficar e $sair, a mobilização começou!",
            "As campanhas sobre $nome ganharam espaço hoje: tem gente puxando $ficar e gente convocando $sair."
        ], $chave);
        feed3Post($jogadores, $texto, 'paredao', [$nome], 'mutirao', [$ficar, $sair]);
        $_SESSION['feed3']['movimentos'][$semana][$nome] = [
            'nome' => $nome, 'ficar' => $ficar, 'sair' => $sair,
        ];
        // Impacto MUITO pequeno, único e oculto: a disputa nas redes não decide o Paredão sozinha.
        // Não usa a popularidade para decidir a força dos mutirões nem mostra a direção do impacto.
        $variacao = (int)(sprintf('%u', crc32('mobilizacao|' . $semana . '|' . $nome)) % 3) - 1;
        if ($variacao !== 0 && function_exists('alterarPopularidade')) {
            $atuais = $_SESSION['jogadores'] ?? $jogadores;
            alterarPopularidade($atuais, $nome, $variacao);
            $_SESSION['jogadores'] = $atuais;
        }
    }
}

function feed3Trending(array $jogadores, int $semana, string $meuNome): array
{
    // Mantém a lista pública original; não usa tendências oriundas de inteligência privada.
    $base = gerarTrendingTopicsFeed($jogadores, $semana, $meuNome);
    $tags = [];
    foreach ($base as $item) $tags[(string)$item['tag']] = (int)$item['posts'];
    foreach (feed3MovimentosAtivos($semana) as $movimento) {
        foreach (['ficar','sair'] as $tipo) {
            $tag = $movimento[$tipo] ?? '';
            if ($tag !== '') $tags[$tag] = 28000 + (sprintf('%u', crc32($tag . '|' . $semana)) % 3500);
        }
    }
    foreach ((array)($_SESSION['feed3']['torcidas'][$semana] ?? []) as $torcida) {
        $tag = (string)($torcida['tag'] ?? '');
        if ($tag !== '') $tags[$tag] = 7000 + (sprintf('%u', crc32($tag . '|' . $semana)) % 2000);
    }
    arsort($tags);
    $resultado = [];
    foreach (array_slice($tags, 0, 8, true) as $tag => $posts) $resultado[] = ['tag' => $tag, 'posts' => $posts];
    return $resultado;
}
