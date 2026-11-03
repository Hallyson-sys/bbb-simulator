<?php
/**
 * Jogo da Discórdia 3.0 — rodada argumentativa em etapas.
 * Nunca consulta votos privados, intenções ocultas ou popularidade para criar 'provas'.
 * Não altera a estrutura nem os efeitos dos temas clássicos (podio/categorias/etc).
 */

function d3Semana(): int { return max(1, (int)($_SESSION['rodada'] ?? 1)); }

function d3Ativo(array $jogadores, string $nome): bool {
    foreach ($jogadores as $j) {
        if (!is_array($j) || !nomeIgual((string)($j['nome'] ?? ''), $nome)) continue;
        return empty($j['eliminado']) && empty($j['fora_da_casa']);
    }
    return false;
}

function d3ValidaEscolha(array $post, array $jogadores, string $meuNome, string $tema): ?array {
    $config = configuracaoTemaDiscordia($tema);
    $tipo = (string)($config['tipo'] ?? '');
    $dados = [];
    $alvoPrincipal = '';
    $positivo = $tipo === 'simples_positivo' || $tipo === 'podio';
    if ($tipo === 'categorias') {
        $usados = [];
        foreach ((array)($config['categorias'] ?? []) as $chave => $cat) {
            $alvo = trim((string)($post['categoria_'.$chave] ?? ''));
            if ($alvo === '' || !d3Ativo($jogadores, $alvo)) return null;
            if (nomeIgual($alvo, $meuNome) && empty($cat['permite_si'])) return null;
            foreach ($usados as $anterior) if (nomeIgual($anterior, $alvo)) return null;
            $usados[] = $alvo;
            $dados['categoria_'.$chave] = $alvo;
            if (!nomeIgual($alvo, $meuNome) && in_array((string)($cat['efeito'] ?? ''), ['negativo','forte_negativo'], true) && $alvoPrincipal === '') {
                $alvoPrincipal = $alvo;
                $positivo = false;
            }
        }
        if ($alvoPrincipal === '') {
            foreach ($usados as $alvo) if (!nomeIgual($alvo, $meuNome)) { $alvoPrincipal = $alvo; break; }
            $positivo = true;
        }
    } elseif ($tipo === 'podio') {
        $a = trim((string)($post['podio_2'] ?? ''));
        $b = trim((string)($post['podio_3'] ?? ''));
        if (!d3Ativo($jogadores,$a) || !d3Ativo($jogadores,$b)
            || nomeIgual($a,$b) || nomeIgual($a,$meuNome) || nomeIgual($b,$meuNome)) return null;
        $dados = ['podio_2'=>$a,'podio_3'=>$b,'intensidade'=>'leve'];
        $alvoPrincipal = $a;
    } else {
        $alvoPrincipal = trim((string)($post['alvo_discordia'] ?? ''));
        if ($alvoPrincipal === '' || !d3Ativo($jogadores, $alvoPrincipal) || nomeIgual($meuNome,$alvoPrincipal)) return null;
        $intensidade = (string)($post['intensidade'] ?? 'leve');
        if (!in_array($intensidade,['leve','com_tudo','saboneteiro'],true)) return null;
        $dados = ['alvo_discordia'=>$alvoPrincipal, 'intensidade'=>$intensidade];
    }
    if ($alvoPrincipal === '' || !d3Ativo($jogadores,$alvoPrincipal)) return null;
    return ['dados'=>$dados, 'alvo'=>$alvoPrincipal, 'positivo'=>$positivo];
}

/** Somente registros de acontecimentos públicos envolvendo os dois participantes. */
function d3FatosPublicos(string $autor, string $alvo, int $semana): array {
    $tipos = ['discussao','treta','discordia','indicacao_lider','indicacao_paredao',
        'paredao','monstro','vip_xepa','votacao_aberta','voto_revelado','evolucao_social'];
    $fatos = [];
    foreach (array_reverse((array)($_SESSION['historico_temporada'] ?? [])) as $evento) {
        if (!is_array($evento) || !in_array((string)($evento['tipo'] ?? ''),$tipos,true)) continue;
        if ((int)($evento['rodada'] ?? 0) > $semana) continue;
        $nomes = (array)($evento['participantes'] ?? []);
        if (!in_array($autor,$nomes,true) || !in_array($alvo,$nomes,true)) continue;
        $descricao = trim((string)($evento['descricao'] ?? ''));
        if ($descricao === '') continue;
        // Texto de histórico público, nunca usa confissões, objetivos ou votos secretos.
        $fatos[] = [
            'titulo'=>trim((string)($evento['titulo'] ?? 'Acontecimento da casa')),
            'semana'=>(int)$evento['rodada'],
            'descricao'=>function_exists('mb_substr') ? mb_substr($descricao,0,180) : substr($descricao,0,180)
        ];
        if (count($fatos) >= 2) break;
    }
    return $fatos;
}

function d3Relacao(array $jogadores, string $autor, string $alvo): int {
    if (function_exists('calcularRelacaoIA')) return (int)calcularRelacaoIA($jogadores,$autor,$alvo,(string)($_SESSION['meu_nome'] ?? ''));
    return 0;
}

function d3OpcoesArgumento(array $pendente): array {
    $alvo = (string)$pendente['alvo'];
    $positivo = !empty($pendente['positivo']);
    $opcoes = $positivo ? [
        'convivencia'=>['nome'=>'💜 Destacar a convivência','texto'=>'Minha escolha vem da convivência e da forma como você me tratou.'],
        'jogo'=>['nome'=>'🎯 Reconhecer o jogo','texto'=>'Eu respeito a maneira como você está se posicionando nesta edição.'],
    ] : [
        'convivencia'=>['nome'=>'💬 Questionar uma atitude','texto'=>'Eu quero falar de como nossa convivência ficou depois das últimas situações.'],
        'jogo'=>['nome'=>'🎯 Cobrar posicionamento','texto'=>'Eu discordo de algumas decisões suas dentro do jogo e quero me posicionar.'],
    ];
    if (!empty($pendente['fatos'])) {
        $f = $pendente['fatos'][0];
        $opcoes['fato'] = ['nome'=>'📼 Citar acontecimento registrado','texto'=>'Quero retomar o que aconteceu na Semana '.(int)$f['semana'].': '.$f['descricao']];
    }
    return $opcoes;
}

/** Resposta determinística por etapa, guardada no save ao escolher o argumento. */
function d3RespostaNPC(array $jogadores, array $pendente, string $argumento): array {
    $nome = (string)$pendente['alvo'];
    $positivo = !empty($pendente['positivo']);
    $r = d3Relacao($jogadores,$nome,(string)($_SESSION['meu_nome'] ?? ''));
    $tracos = function_exists('e3Tracos') ? e3Tracos($nome) : [];
    $reatividade = (int)($tracos['reatividade'] ?? 50);
    if ($positivo) {
        $fala = $r >= 15
            ? 'Eu fico feliz de ouvir isso. A gente sabe o que construiu aqui dentro, mesmo com as pressões.'
            : 'Obrigado por reconhecer isso. A gente pode discordar em algumas coisas, mas eu respeito sua visão.';
        $tom = 'acolhedor';
    } elseif ($argumento === 'fato' && !empty($pendente['fatos'])) {
        $fala = $reatividade > 65
            ? 'Eu lembro dessa situação. Mas não acho justo resumir meu jogo só àquele momento!'
            : 'Aquilo aconteceu, sim. Só que eu também tenho meu lado da história e quero poder explicar.';
        $tom = $reatividade > 65 ? 'reativo' : 'defensivo';
    } elseif ($r <= -18 || $reatividade >= 70) {
        $fala = $argumento === 'jogo'
            ? 'Você pode discordar do meu jogo, mas eu não vou aceitar que digam que eu não me posiciono.'
            : 'Eu ouvi o que você falou. Só não concordo com essa leitura da nossa convivência.';
        $tom = 'reativo';
    } else {
        $fala = $argumento === 'jogo'
            ? 'Eu respeito que você tenha outra estratégia. Mas minhas escolhas também têm uma razão.'
            : 'Eu entendo sua cobrança. Só acho que a gente podia ter conversado sobre isso antes.';
        $tom = 'defensivo';
    }
    return ['fala'=>$fala,'tom'=>$tom];
}

function d3OpcoesTreplica(bool $positivo): array {
    return $positivo ? [
        'reforcar'=>['nome'=>'🤝 Reafirmar o reconhecimento','fala'=>'Eu mantenho o que disse. É importante reconhecer quem esteve presente.'],
        'escutar'=>['nome'=>'💜 Agradecer e ouvir','fala'=>'Obrigado por falar também. Queria que a gente levasse esse respeito para a convivência.'],
        'encerrar'=>['nome'=>'🎤 Encerrar com respeito','fala'=>'Era isso que eu queria dizer hoje.'],
    ] : [
        'sustentar'=>['nome'=>'🔥 Sustentar a crítica','fala'=>'Ouvi sua resposta, mas continuo discordando dessa atitude.'],
        'escutar'=>['nome'=>'🤝 Reconhecer o outro lado','fala'=>'Eu mantenho meu ponto, mas aceito que você tenha outra visão.'],
        'recuar'=>['nome'=>'🕊️ Evitar aumentar a briga','fala'=>'Não quero transformar isso numa guerra pessoal. Prefiro encerrar aqui.'],
    ];
}

/** Efeito complementar pequeno: o impacto principal segue no sistema clássico. */
function d3AplicarReplica(array &$jogadores, array $pendente, string $replica, string $meuNome): array {
    $alvo = (string)$pendente['alvo'];
    $semana = (int)$pendente['semana'];
    $positivo = !empty($pendente['positivo']);
    $mapa = [
        'sustentar'=>[-3,4,-3,-3,'me_atacou_discordia'],
        'escutar'=>[3,-2,3,3,'me_elogiou_discordia'],
        'recuar'=>[1,-1,1,1,'me_elogiou_discordia'],
        'reforcar'=>[4,-2,4,4,'me_elogiou_discordia'],
        'encerrar'=>[0,0,0,0,'me_elogiou_discordia'],
    ];
    [$amizade,$rivalidade,$confianca,$visivel,$memoria] = $mapa[$replica] ?? [0,0,0,0,''];
    $chave = 'discordia3|'.$semana.'|'.$meuNome.'|'.$alvo.'|'.(string)$pendente['token'];
    if (!empty($_SESSION['d3_efeitos'][$chave])) return ['aplicado'=>false];
    $_SESSION['d3_efeitos'][$chave] = true;
    if ($amizade || $rivalidade || $confianca) {
        if (function_exists('alterarAfinidade')) alterarAfinidade($jogadores,$alvo,$meuNome,$amizade,$rivalidade,$confianca);
        if (function_exists('ajustarRelacaoJogador') && $visivel) ajustarRelacaoJogador($alvo,$visivel);
        if (function_exists('registrarMemoriaSocialNPC') && $memoria !== '') {
            registrarMemoriaSocialNPC($alvo,$meuNome,$memoria,1,
                $meuNome.' respondeu no debate público do Jogo da Discórdia.', 'mem|'.$chave);
        }
    }
    if (function_exists('registrarHistoricoTemporada')) {
        $titulo = '🔥 Debate no Jogo da Discórdia';
        $resumo = $meuNome.' e '.$alvo.' debateram publicamente: '.($pendente['resposta']['fala'] ?? 'O participante respondeu à fala.');
        registrarHistoricoTemporada('discordia',$titulo,$resumo,[$meuNome,$alvo],'🔥',$semana,$chave,
            ['tema'=>$pendente['tema'],'replica'=>$replica]);
    }
    return ['aplicado'=>true];
}

/** Reação pública de uma testemunha — somente quem está na casa, nunca votos secretos. */
function d3ReacaoTestemunha(array $jogadores, string $meuNome, array $embate): string {
    $alvo = (string)($embate['alvo'] ?? '');
    if ($alvo === '' || !d3Ativo($jogadores,$alvo)) return '';
    $testemunhas=[];
    foreach ($jogadores as $j) {
        $nome=trim((string)($j['nome']??''));
        if ($nome==='' || nomeIgual($nome,$meuNome) || nomeIgual($nome,$alvo) || !d3Ativo($jogadores,$nome)) continue;
        $relacao=d3Relacao($jogadores,$nome,$alvo);
        $testemunhas[]=['nome'=>$nome,'relacao'=>$relacao];
    }
    if (!$testemunhas) return '';
    // Testemunha mais próxima do alvo tende a se manifestar; não garantimos solidariedade.
    usort($testemunhas,static fn($a,$b)=>abs((int)$b['relacao']) <=> abs((int)$a['relacao']));
    $observador=$testemunhas[0];
    $nome=$observador['nome'];
    $relacao=(int)$observador['relacao'];
    if ($relacao>=24) return '🎙️ '.$nome.' ouviu o debate e comentou: “Acho que '.$alvo.' também merece ser ouvido nessa história.”';
    if ($relacao<=-18) return '🎙️ '.$nome.' acompanhou a discussão e disse: “Tem pontos aí que ainda precisam ser esclarecidos.”';
    return '🎙️ '.$nome.' acompanhou o debate e comentou: “Essa conversa ainda pode render muito aqui dentro.”';
}

/** Limpa diálogos de outra rodada/fase sem alterar o histórico. */
function d3Sincronizar(array $jogadores): void {
    if ((int)($_SESSION['d3_ultimo_embate']['semana'] ?? 0) < d3Semana()) unset($_SESSION['d3_ultimo_embate']);
    $p = $_SESSION['d3_pendente'] ?? null;
    if (!is_array($p)) return;
    if (($_SESSION['fase_semana'] ?? '') !== 'discordia'
        || d3Semana() !== (int)($p['semana'] ?? -1)
        || bbbRetaFinalSemDiscordia($jogadores)
        || !d3Ativo($jogadores,(string)($p['alvo'] ?? ''))) unset($_SESSION['d3_pendente']);
}
