<?php
/**
 * Dilemas de Jogo 2.0 — decisões episódicas, sem alterar a fase do BBB.
 * A escolha é do protagonista; NPCs conservam autonomia e privacidade.
 */

function djSemana(): int { return max(1, (int)($_SESSION['rodada'] ?? 1)); }
function djEsc($texto): string { return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8'); }

function djAtivos(array $jogadores): array {
    $nomes = [];
    $oculto = !empty($_SESSION['paredao_falso_ativo']) ? (string)($_SESSION['falso_eliminado'] ?? '') : '';
    foreach ($jogadores as $p) {
        if (!is_array($p)) continue;
        $nome = trim((string)($p['nome'] ?? ''));
        if ($nome === '' || !empty($p['eliminado']) || !empty($p['fora_da_casa']) || $nome === $oculto) continue;
        $nomes[$nome] = $p;
    }
    return $nomes;
}
function djRelacao(array $jogadores, string $npc, string $meuNome): int {
    $r = (int)($_SESSION['relacoes_jogador'][$npc] ?? 0);
    if (function_exists('obterRelacaoCompleta')) {
        $dados = obterRelacaoCompleta($jogadores, $npc, $meuNome, $meuNome);
        if (is_array($dados)) $r += (int)round(((float)($dados['amizade'] ?? 0) + (float)($dados['confianca'] ?? 0) - (float)($dados['rivalidade'] ?? 0)) / 4);
    }
    return $r;
}
function djTemCompromisso(string $nome): bool {
    $p = $_SESSION['dj_compromissos'][$nome] ?? null;
    return is_array($p) && (int)($p['ate'] ?? 0) >= djSemana() && !empty($p['aceito']);
}
/** Bônus moderado na mesma função de pontuação que decide voto, Líder, VIP e Anjo. */
function djBonusAlvo(array $jogadores, string $npc, string $alvo, string $contexto): float {
    if ($alvo !== (string)($_SESSION['meu_nome'] ?? '')) return 0.0;
    $ativos = djAtivos($jogadores);
    if (!isset($ativos[$npc])) return 0.0;
    $p = $_SESSION['dj_compromissos'][$npc] ?? null;
    if (!is_array($p) || (int)($p['ate'] ?? 0) < djSemana() || empty($p['aceito'])) return 0.0;
    $positivos = ['vip', 'imunidade', 'curinga_imunidade', 'salvar_paredao', 'interacao_aliado', 'festa_aliado', 'podio'];
    $negativos = ['voto', 'indicacao_lider', 'indicacao_bigfone', 'contra_golpe', 'discordia_negativo'];
    $tipo = (string)($p['tipo'] ?? 'protecao');
    if ($tipo === 'protecao' || $tipo === 'tregua') {
        if (in_array($contexto, $positivos, true)) return 14.0;
        if (in_array($contexto, $negativos, true)) return -27.0;
    } elseif ($tipo === 'desconfianca') {
        if (in_array($contexto, $negativos, true)) return 11.0;
        if (in_array($contexto, $positivos, true)) return -7.0;
    }
    return 0.0;
}

/** Situações e respostas fixas no servidor, jamais lidas diretamente do POST. */
function djBiblioteca(): array {
    return [
        'promessa' => ['icone'=>'🤝','titulo'=>'Uma promessa difícil','local'=>'VARANDA','texto'=>'{npc} procura você longe dos outros: “Se meu nome ficar ameaçado, eu posso contar com você? Não quero promessa vazia.”','opcoes'=>[
            ['rotulo'=>'🤝 Prometer que vai tentar proteger','reacao'=>'Você assume um compromisso. {npc} pondera se pode confiar nessa promessa.','delta'=>7,'conf'=>8,'efeito'=>'protecao','memoria'=>'me_apoiou'],
            ['rotulo'=>'🎯 Ser sincero: não pode garantir nada','reacao'=>'Você explica que não pretende mentir sobre os riscos do jogo. {npc} respeita a sinceridade, mesmo sem garantia.','delta'=>2,'conf'=>4,'efeito'=>'observacao','memoria'=>'conversou_comigo'],
            ['rotulo'=>'🧊 Evitar se comprometer','reacao'=>'Você desconversa. {npc} fica com dúvidas sobre até onde pode contar com você.','delta'=>-5,'conf'=>-4,'efeito'=>'desconfianca','memoria'=>'me_decepcionou'],
        ]],
        'alvo_dividido' => ['icone'=>'🎯','titulo'=>'A escolha que divide','local'=>'COZINHA','texto'=>'{npc} quer priorizar {outro} como alvo, mas você não sabe se concordar agora prejudicaria suas outras relações. A decisão é sua.','opcoes'=>[
            ['rotulo'=>'🎯 Concordar em considerar o alvo','reacao'=>'Vocês consideram {outro} como possível alvo, mas ninguém confirmou um voto definitivo.','delta'=>5,'conf'=>3,'efeito'=>'plano','memoria'=>'fechou_pacto_comigo'],
            ['rotulo'=>'🛡️ Defender {outro} e buscar alternativa','reacao'=>'Você deixa claro que não quer atacar {outro} sem um motivo melhor. {npc} não gosta de ter a estratégia questionada.','delta'=>-5,'conf'=>-2,'efeito'=>'observacao','memoria'=>'discordou_de_mim'],
            ['rotulo'=>'🤐 Não revelar suas prioridades','reacao'=>'Você ouve e não promete nada. {npc} percebe que precisa negociar melhor com você.','delta'=>0,'conf'=>0,'efeito'=>'observacao','memoria'=>'conversou_comigo'],
        ]],
        'defender_ausente' => ['icone'=>'⚖️','titulo'=>'Defender ou ficar em silêncio','local'=>'SALA','texto'=>'{npc} critica {outro} diante de outras pessoas. Você pode reagir, mas sabe que alguém pode se indispor com sua decisão.','opcoes'=>[
            ['rotulo'=>'🛡️ Defender {outro} na frente de todos','reacao'=>'Você interrompe a crítica. {outro} percebe seu gesto; {npc} fica contrariado.','delta'=>-7,'outro_delta'=>8,'conf'=>-3,'efeito'=>'publico','memoria'=>'me_confrontou'],
            ['rotulo'=>'🔥 Concordar com {npc}','reacao'=>'Você concorda com a crítica publicamente. {npc} gosta do apoio, mas {outro} pode guardar mágoa.','delta'=>6,'outro_delta'=>-7,'conf'=>3,'efeito'=>'publico','memoria'=>'me_apoiou'],
            ['rotulo'=>'🧊 Pedir calma e encerrar a discussão','reacao'=>'Você tenta reduzir a tensão sem tomar partido. Nem todos ficam satisfeitos.','delta'=>2,'outro_delta'=>2,'conf'=>2,'efeito'=>'publico','memoria'=>'conversou_comigo'],
        ]],
        'traição_suspeita' => ['icone'=>'👀','titulo'=>'Desconfiança na aliança','local'=>'QUARTO','texto'=>'{npc} diz que está desconfiando de {outro}, alguém do seu grupo. Não há prova concreta de traição, mas {npc} quer uma reação sua.','opcoes'=>[
            ['rotulo'=>'🧠 Pedir fatos antes de acusar','reacao'=>'Você evita criar uma crise baseada apenas em suspeita. A conversa termina sem acusações públicas.','delta'=>3,'conf'=>5,'efeito'=>'observacao','memoria'=>'conversou_comigo'],
            ['rotulo'=>'🤝 Reafirmar confiança em {outro}','reacao'=>'Você deixa claro que não pretende abandonar um aliado só por desconfiança. {npc} fica frustrado.','delta'=>-6,'conf'=>-2,'efeito'=>'observacao','memoria'=>'discordou_de_mim'],
            ['rotulo'=>'🤫 Concordar em observar {outro}','reacao'=>'Vocês resolvem acompanhar a convivência antes de tomar qualquer decisão.','delta'=>4,'conf'=>3,'efeito'=>'observacao','memoria'=>'fechou_pacto_comigo'],
        ]],
        'reconciliacao' => ['icone'=>'💜','titulo'=>'Deixar o passado pra trás?','local'=>'JARDIM','texto'=>'Depois de dias tensos, {npc} se aproxima: “A gente vai continuar se evitando ou consegue conversar de verdade?”','opcoes'=>[
            ['rotulo'=>'🤝 Aceitar uma trégua com sinceridade','reacao'=>'Vocês tentam recomeçar. {npc} pode levar algum tempo até confiar, mas a disposição foi importante.','delta'=>10,'conf'=>8,'efeito'=>'tregua','memoria'=>'me_pediu_desculpas'],
            ['rotulo'=>'🧠 Aceitar conversar, sem fazer promessas','reacao'=>'Vocês conversam, porém mantêm cautela. O desgaste não desaparece imediatamente.','delta'=>5,'conf'=>3,'efeito'=>'observacao','memoria'=>'conversou_comigo'],
            ['rotulo'=>'🚫 Recusar a aproximação','reacao'=>'Você prefere manter distância. {npc} entende que a relação continua desgastada.','delta'=>-8,'conf'=>-5,'efeito'=>'desconfianca','memoria'=>'me_decepcionou'],
        ]],
        'pedido_de_desculpas' => ['icone'=>'🙏','titulo'=>'Uma desculpa inesperada','local'=>'ACADEMIA','texto'=>'{npc} admite que exagerou numa discussão recente e pergunta se vocês podem tentar se respeitar mais.','opcoes'=>[
            ['rotulo'=>'💜 Aceitar o pedido','reacao'=>'Você reconhece a tentativa de aproximação. {npc} demonstra alívio, mesmo sabendo que tudo levará tempo.','delta'=>9,'conf'=>8,'efeito'=>'tregua','memoria'=>'me_pediu_desculpas'],
            ['rotulo'=>'🎯 Pedir mudança de atitude','reacao'=>'Você aceita conversar, mas deixa claro que as atitudes futuras serão mais importantes que palavras.','delta'=>4,'conf'=>4,'efeito'=>'observacao','memoria'=>'conversou_comigo'],
            ['rotulo'=>'🧊 Dizer que ainda não superou','reacao'=>'Você admite que ainda está magoado e que precisa de tempo.','delta'=>-3,'conf'=>-1,'efeito'=>'observacao','memoria'=>'conversou_comigo'],
        ]],
        'proteger_na_festa' => ['icone'=>'🥂','titulo'=>'Uma lealdade colocada à prova','local'=>'FESTA','texto'=>'Durante a Festa, {npc} conta que está com receio de ser deixado de lado na próxima formação. Prometer proteção pode mudar suas prioridades.','opcoes'=>[
            ['rotulo'=>'🤝 Oferecer apoio e tentar um pacto','reacao'=>'Você oferece apoio. {npc} decide se está disposto a retribuir.','delta'=>7,'conf'=>7,'efeito'=>'protecao','memoria'=>'me_apoiou'],
            ['rotulo'=>'💬 Ouvir sem garantir voto','reacao'=>'Você acolhe o desabafo, sem interferir nas decisões da semana.','delta'=>3,'conf'=>2,'efeito'=>'observacao','memoria'=>'conversou_comigo'],
            ['rotulo'=>'🎯 Dizer que cada um cuida do próprio jogo','reacao'=>'Você fala de forma direta, e {npc} parece menos inclinado a pedir sua ajuda.','delta'=>-5,'conf'=>-4,'efeito'=>'desconfianca','memoria'=>'me_decepcionou'],
        ]],
        'fofoca_festa' => ['icone'=>'🤫','titulo'=>'Guardar ou espalhar?','local'=>'FESTA','texto'=>'{npc} pergunta se você ouviu alguma novidade importante da semana. Dividir um segredo pode te aproximar, mas também aumenta o risco de ele circular.','opcoes'=>[
            ['rotulo'=>'🤫 Contar uma informação que você descobriu','reacao'=>'Você compartilha uma informação de origem conhecida, sem saber se ela será mantida em segredo.','delta'=>5,'conf'=>5,'efeito'=>'compartilhar','memoria'=>'me_contou_segredo'],
            ['rotulo'=>'🔒 Guardar o que sabe','reacao'=>'Você prefere não expor sua fonte. {npc} entende, mas fica curioso.','delta'=>0,'conf'=>1,'efeito'=>'observacao','memoria'=>'conversou_comigo'],
            ['rotulo'=>'🧠 Perguntar o que {npc} sabe primeiro','reacao'=>'Você muda o foco da conversa. {npc} não entrega uma certeza, mas vocês trocam percepções.','delta'=>2,'conf'=>2,'efeito'=>'observacao','memoria'=>'conversou_comigo'],
        ]],
        'pressionado' => ['icone'=>'🔥','titulo'=>'Uma provocação na frente de todos','local'=>'COZINHA','texto'=>'{npc} faz uma provocação durante uma conversa da casa. Reagir pode piorar o clima, mas o silêncio também será interpretado.','opcoes'=>[
            ['rotulo'=>'🔥 Confrontar e defender seu ponto','reacao'=>'Você rebate a provocação publicamente e a tensão aumenta.','delta'=>-10,'conf'=>-5,'efeito'=>'publico','memoria'=>'me_confrontou'],
            ['rotulo'=>'🧠 Responder com tranquilidade','reacao'=>'Você responde sem levantar a voz. O assunto perde parte da força.','delta'=>-1,'conf'=>1,'efeito'=>'publico','memoria'=>'conversou_comigo'],
            ['rotulo'=>'🚶 Sair para não alimentar a briga','reacao'=>'Você se afasta. {npc} não consegue prolongar o confronto.','delta'=>1,'conf'=>0,'efeito'=>'observacao','memoria'=>'conversou_comigo'],
        ]],
        'segredo_da_alianca' => ['icone'=>'🤝','titulo'=>'Até onde vai a lealdade?','local'=>'QUARTO','texto'=>'{npc}, integrante da sua aliança, pede que você coloque o grupo acima de uma amizade com {outro}.','opcoes'=>[
            ['rotulo'=>'🤝 Prometer priorizar o grupo','reacao'=>'Você se compromete a conversar com a aliança antes de agir. {npc} avalia sua sinceridade.','delta'=>7,'conf'=>6,'efeito'=>'protecao','memoria'=>'fechou_pacto_comigo'],
            ['rotulo'=>'💜 Dizer que não vai abandonar {outro}','reacao'=>'Você defende o direito de manter amizades fora da aliança. {npc} discorda, mas ouviu seus limites.','delta'=>-5,'conf'=>-3,'efeito'=>'observacao','memoria'=>'discordou_de_mim'],
            ['rotulo'=>'🧠 Propor que o grupo converse antes','reacao'=>'Você sugere uma reunião e evita decidir sozinho sobre {outro}.','delta'=>4,'conf'=>5,'efeito'=>'observacao','memoria'=>'conversou_comigo'],
        ]],
        'diferenca_de_interesses' => ['icone'=>'⚖️','titulo'=>'Aliança ou jogo individual?','local'=>'VARANDA','texto'=>'{npc} quer tomar uma decisão estratégica para o grupo, mas você tem dúvidas sobre o custo pessoal dessa escolha.','opcoes'=>[
            ['rotulo'=>'🤝 Priorizar a união do grupo','reacao'=>'Você busca uma decisão conjunta, mesmo que isso limite sua liberdade.','delta'=>6,'conf'=>6,'efeito'=>'protecao','memoria'=>'fechou_pacto_comigo'],
            ['rotulo'=>'🎯 Defender sua autonomia','reacao'=>'Você deixa claro que a aliança não significa obedecer sem questionar.','delta'=>-3,'conf'=>-2,'efeito'=>'observacao','memoria'=>'discordou_de_mim'],
            ['rotulo'=>'🧠 Propor uma saída intermediária','reacao'=>'Vocês tentam equilibrar o interesse coletivo e as escolhas pessoais.','delta'=>4,'conf'=>4,'efeito'=>'observacao','memoria'=>'conversou_comigo'],
        ]],
        'promessa_antiga' => ['icone'=>'🧠','titulo'=>'A palavra dada','local'=>'VARANDA','texto'=>'{npc} lembra da promessa de proteção que vocês conversaram na Semana {semana_promessa} e quer saber se ainda pode confiar nela.','opcoes'=>[
            ['rotulo'=>'🤝 Reafirmar a palavra','reacao'=>'Você renova sua disposição de proteger {npc}, mas as próximas decisões ainda terão consequências.','delta'=>7,'conf'=>7,'efeito'=>'protecao','memoria'=>'fechou_pacto_comigo'],
            ['rotulo'=>'🎯 Explicar que o jogo mudou','reacao'=>'Você avisa que a situação mudou e se recusa a continuar prometendo algo incerto. {npc} fica decepcionado.','delta'=>-6,'conf'=>-5,'efeito'=>'romper','memoria'=>'quebrou_tregua'],
            ['rotulo'=>'🧊 Fugir do assunto','reacao'=>'Você evita responder. {npc} passa a desconfiar da firmeza do compromisso.','delta'=>-4,'conf'=>-4,'efeito'=>'desconfianca','memoria'=>'me_decepcionou'],
        ]],
        'prova_ou_amizade' => ['icone'=>'👑','titulo'=>'Quando a disputa fica pessoal','local'=>'SALA','texto'=>'{npc} admite que tem medo de enfrentar você numa próxima prova. A amizade não elimina o desejo de vencer.','opcoes'=>[
            ['rotulo'=>'🏆 Dizer que cada um deve dar seu máximo','reacao'=>'Vocês concordam que a disputa precisa ser justa e que amizade não significa facilitar uma prova.','delta'=>5,'conf'=>5,'efeito'=>'observacao','memoria'=>'me_apoiou'],
            ['rotulo'=>'🤝 Prometer que evitará prejudicar {npc}','reacao'=>'Você oferece apoio, mas deixa claro que não pode garantir um resultado.','delta'=>5,'conf'=>4,'efeito'=>'protecao','memoria'=>'me_apoiou'],
            ['rotulo'=>'🔥 Dizer que vencerá custe o que custar','reacao'=>'Você assume uma postura competitiva. {npc} percebe que a rivalidade pode aumentar.','delta'=>-5,'conf'=>-3,'efeito'=>'observacao','memoria'=>'me_confrontou'],
        ]],
        'amizade_em_risco' => ['icone'=>'💔','titulo'=>'Duas relações, uma escolha','local'=>'QUARTO','texto'=>'{npc} quer que você se afaste de {outro} para provar sua lealdade. A decisão pode custar uma das duas relações.','opcoes'=>[
            ['rotulo'=>'🤝 Apoiar {npc} e se afastar de {outro}','reacao'=>'Você demonstra preferência por {npc}; {outro} percebe o afastamento.','delta'=>7,'outro_delta'=>-6,'conf'=>5,'efeito'=>'publico','memoria'=>'me_apoiou'],
            ['rotulo'=>'💬 Recusar a exigência','reacao'=>'Você diz que não vai romper uma amizade por pressão. {npc} se incomoda.','delta'=>-6,'conf'=>-2,'efeito'=>'observacao','memoria'=>'discordou_de_mim'],
            ['rotulo'=>'🧠 Propor uma conversa entre os três','reacao'=>'Você tenta construir uma saída sem escolher inimigos.','delta'=>3,'outro_delta'=>3,'conf'=>3,'efeito'=>'publico','memoria'=>'conversou_comigo'],
        ]],
    ];
}

/** Cenas usam somente pessoas presentes e fatos já conhecidos pelo jogador. */
function djCandidatos(array $jogadores, string $meuNome, string $fase): array {
    $ativos = djAtivos($jogadores);
    if (!isset($ativos[$meuNome]) || count($ativos) < 3) return [];
    $np = array_diff(array_keys($ativos), [$meuNome]);
    $rel = [];
    foreach ($np as $nome) $rel[$nome] = djRelacao($jogadores, $nome, $meuNome);
    arsort($rel);
    $fortes = array_keys(array_filter($rel, fn($v) => $v >= 12));
    $poucos = array_keys(array_filter($rel, fn($v) => $v <= -12));
    $todos = array_keys($rel);
    $out = [];
    $add = static function ($id, $npc, $outro='') use (&$out, $meuNome, $ativos) {
        if ($npc === '' || $npc === $meuNome || !isset($ativos[$npc])) return;
        if ($outro !== '' && ($outro === $npc || $outro === $meuNome || !isset($ativos[$outro]))) return;
        $out[] = ['id'=>$id,'npc'=>$npc,'outro'=>$outro];
    };
    $primeiro = static fn($a) => $a ? $a[array_rand($a)] : '';
    $pessoa = $primeiro($todos);
    $outro = static function ($npc, $todos) use ($primeiro): string {
        return $primeiro(array_values(array_diff($todos, [$npc])));
    };
    $add('defender_ausente', $pessoa, $outro($pessoa, $todos));
    $add('pressionado', $primeiro($poucos) ?: $pessoa);
    $add('prova_ou_amizade', $primeiro($fortes) ?: $pessoa);
    if ($fortes) {
        $aliado = $primeiro($fortes);
        $add('promessa', $aliado);
        $add('alvo_dividido', $aliado, $outro($aliado, $todos));
        $add('amizade_em_risco', $aliado, $outro($aliado, $todos));
        if ($fase === 'festa') $add('proteger_na_festa', $aliado);
    }
    if ($poucos) {
        $adversario = $primeiro($poucos);
        $add('reconciliacao', $adversario);
        $add('pedido_de_desculpas', $adversario);
    }
    $grupo = (string)($ativos[$meuNome]['alianca'] ?? '');
    // As metas privadas dos NPCs alteram quais propostas eles podem trazer,
    // mas não são reveladas ao protagonista pela interface.
    if (function_exists('on1Objetivo')) foreach ($todos as $nome) {
        $meta = on1Objetivo($nome);
        if (!is_array($meta)) continue;
        if (($meta['alvo'] ?? '') === $meuNome) {
            if (($meta['tipo'] ?? '') === 'proteger_aliado') $add('promessa', $nome);
            if (($meta['tipo'] ?? '') === 'reconciliar') $add('reconciliacao', $nome);
            if (($meta['tipo'] ?? '') === 'superar_rival') $add('pressionado', $nome);
        }
    }
    if ($grupo !== '') {
        $membros = array_values(array_filter($todos, fn($n) => (string)($ativos[$n]['alianca'] ?? '') === $grupo));
        $aliado = $primeiro($membros);
        if ($aliado) {
            $add('diferenca_de_interesses', $aliado);
            $outgrupo = array_values(array_diff($membros, [$aliado]));
            $mais = $primeiro($outgrupo);
            if ($mais) $add('traição_suspeita', $aliado, $mais);
            $fora = array_values(array_filter($todos, fn($n) => (string)($ativos[$n]['alianca'] ?? '') !== $grupo));
            $outroAlvo = $primeiro($fora);
            if ($outroAlvo) $add('segredo_da_alianca', $aliado, $outroAlvo);
        }
    }
    foreach ((array)($_SESSION['dj_compromissos'] ?? []) as $n=>$p) {
        if (isset($ativos[$n]) && (int)($p['semana'] ?? 0) < djSemana() && (int)($p['ate'] ?? 0) >= djSemana() && !empty($p['aceito'])) $add('promessa_antiga', (string)$n);
    }
    if ($fase === 'festa' && function_exists('ecConhecimentosAtivos') && ecConhecimentosAtivos()) $add('fofoca_festa', $pessoa);
    return $out;
}

function djTentarGerar(array $jogadores, string $meuNome, string $fase): void {
    // Recuperação segura de saves alterados pelo DEV ou de episódios de semana antiga.
    $pendente = $_SESSION['dj_ativo'] ?? null;
    if (is_array($pendente) && ((int)($pendente['semana'] ?? 0) !== djSemana() || (string)($pendente['fase'] ?? '') !== $fase)) unset($_SESSION['dj_ativo']);
    $resultado = $_SESSION['dj_resultado'] ?? null;
    if (is_array($resultado) && ((int)($resultado['semana'] ?? 0) !== djSemana() || (string)($resultado['fase'] ?? '') !== $fase)) unset($_SESSION['dj_resultado']);
    if (!preg_match('/^interacoes_/', $fase) && $fase !== 'festa') return;
    // Nunca criar uma cena nova no meio do POST que executa outra ação da fase.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
    if (!empty($_SESSION['modo_espectador']) || !empty($_SESSION['evento_convivencia_ativo']) || !empty($_SESSION['evento_convivencia_resultado'])) return;
    if (!empty($_SESSION['i4_pendente']) || !empty($_SESSION['i4_resultado'])) return;
    if (!empty($_SESSION['dj_ativo']) || !empty($_SESSION['dj_resultado'])) return;
    $s = djSemana();
    if ((int)($_SESSION['dj_gerado_semana'] ?? 0) === $s) return;
    $ch = 'dj_tentado_' . $s . '_' . $fase;
    if (!empty($_SESSION[$ch])) return;
    $_SESSION[$ch] = true;
    $forcar = !empty($_SESSION['dev_dj_forcar']);
    if (!$forcar && random_int(1, 100) > ($fase === 'festa' ? 48 : 42)) return;
    $candidatos = djCandidatos($jogadores, $meuNome, $fase);
    if (!$candidatos) return;
    $recentes = (array)($_SESSION['dj_ultimos_tipos'] ?? []);
    $validos = array_values(array_filter($candidatos, fn($c)=>!in_array($c['id'], array_slice($recentes,-6), true)));
    $escolha = ($validos ?: $candidatos)[array_rand($validos ?: $candidatos)];
    $catalogo = djBiblioteca();
    $cena = $catalogo[$escolha['id']];
    $semanaPromessa = (int)(($_SESSION['dj_compromissos'][$escolha['npc']]['semana'] ?? $s));
    $repl = ['{npc}'=>$escolha['npc'],'{outro}'=>$escolha['outro'],'{semana_promessa}'=>$semanaPromessa];
    $cena['texto'] = strtr($cena['texto'], $repl);
    foreach ($cena['opcoes'] as &$op) { $op['rotulo'] = strtr($op['rotulo'],$repl); $op['reacao'] = strtr($op['reacao'],$repl); }
    unset($op);
    $cena += $escolha;
    $_SESSION['dj_ativo'] = $cena + ['semana'=>$s, 'fase'=>$fase, 'token'=>bin2hex(random_bytes(16))];
    $_SESSION['dj_gerado_semana'] = $s;
    $_SESSION['dj_ultimos_tipos'] = array_slice(array_merge($recentes, [$escolha['id']]), -12);
    // Evita duas cenas especiais sobrepostas no mesmo momento.
    $_SESSION['ec_tentado_'.$fase.'_'.$s] = true;
    unset($_SESSION['dev_dj_forcar']);
}

function djConvidadoAceita(array $jogadores, string $npc, string $meuNome): bool {
    $r = djRelacao($jogadores, $npc, $meuNome);
    $dados = djAtivos($jogadores)[$npc] ?? [];
    $perfil = (string)($dados['personalidade'] ?? 'Neutro');
    $chance = max(12, min(87, 50 + $r * .45 + (in_array($perfil,['Manipulador','Falso'],true) ? -16 : 0)));
    return random_int(1,100) <= $chance;
}
function djAfetar(array &$jogadores, string $npc, string $meuNome, int $amizade, int $confianca): void {
    if (!isset(djAtivos($jogadores)[$npc])) return;
    if (function_exists('ajustarRelacaoJogador')) ajustarRelacaoJogador($npc,$amizade);
    if (function_exists('alterarAfinidade')) {
        alterarAfinidade($jogadores, $npc,$meuNome,$amizade, $amizade < 0 ? min(8, abs($amizade)) : -min(3,$amizade), $confianca);
        alterarAfinidade($jogadores, $meuNome,$npc,$amizade, $amizade < 0 ? min(8, abs($amizade)) : -min(3,$amizade), $confianca);
    }
}
function djResolver(array &$jogadores, string $meuNome, string $fase, string $token, int $indice): bool {
    $cena = $_SESSION['dj_ativo'] ?? null;
    if (!is_array($cena) || !hash_equals((string)($cena['token']??''), $token) || $fase !== ($cena['fase']??'') || djSemana() !== (int)($cena['semana']??0)) return false;
    if (!array_key_exists($indice,(array)($cena['opcoes']??[]))) return false;
    $npc = (string)($cena['npc'] ?? '');
    $ativos = djAtivos($jogadores);
    if (!isset($ativos[$meuNome]) || !isset($ativos[$npc])) return false;
    $op = $cena['opcoes'][$indice];
    // Marcar o episódio como resolvido ANTES de aplicar mudanças para bloquear duplo envio.
    unset($_SESSION['dj_ativo']);
    $delta = (int)($op['delta']??0);
    djAfetar($jogadores, $npc, $meuNome, $delta, (int)($op['conf']??0));
    $outro = (string)($cena['outro'] ?? '');
    if (($op['efeito']??'') === 'publico' && $outro !== '' && isset($ativos[$outro])) {
        djAfetar($jogadores, $outro, $meuNome, (int)($op['outro_delta']??0), (int)round(((int)($op['outro_delta']??0))/2));
        if (function_exists('registrarMemoriaSocialNPC') && (int)($op['outro_delta']??0) !== 0) {
            $tipo = (int)$op['outro_delta'] > 0 ? 'me_defendeu' : 'me_expos';
            registrarMemoriaSocialNPC($outro, $meuNome,$tipo, 2, "$meuNome se posicionou em público numa situação envolvendo $outro.", 'dj|'.djSemana().'|'.$cena['id'].'|'.$outro.'|'.$indice);
        }
    }
    $resultado = (string)($op['reacao'] ?? 'Você tomou uma decisão.');
    $efeito = (string)($op['efeito'] ?? '');
    $mTipo = (string)($op['memoria'] ?? 'conversou_comigo');
    if ($efeito === 'protecao' || $efeito === 'tregua') {
        $aceitou = djConvidadoAceita($jogadores,$npc,$meuNome);
        if ($aceitou) {
            $_SESSION['dj_compromissos'][$npc] = ['tipo'=>$efeito,'semana'=>djSemana(),'ate'=>djSemana()+1,'aceito'=>true,'protagonista'=>$meuNome];
            $resultado .= ' 🤝 O combinado poderá pesar nas escolhas de proteção e voto, sem garanti-las.';
        } else {
            $resultado .= " 🧊 $npc preferiu não firmar um compromisso. A relação mudou, mas não houve pacto.";
        }
    } elseif ($efeito === 'desconfianca' || $efeito === 'romper') {
        unset($_SESSION['dj_compromissos'][$npc]);
        if ($efeito === 'romper') $resultado .= ' A promessa anterior deixou de valer.';
    } elseif ($efeito === 'compartilhar' && function_exists('ecConhecimentosAtivos')) {
        $conhecimentos = ecConhecimentosAtivos();
        if ($conhecimentos) {
            $info = $conhecimentos[array_key_first($conhecimentos)];
            if (!empty($info['chave'])) {
                $_SESSION['fofocas_casa'][] = ['origem'=>$meuNome, 'portador'=>$npc,'texto'=>(string)($info['texto']??''), 'verdadeira'=>array_key_exists('verdade_interna', $info) ? (bool)$info['verdade_interna'] : null, 'rodada'=>djSemana(), 'expira_rodada'=>djSemana()];
                $resultado .= ' 🤫 A informação entrou numa conversa privada e poderá circular pela casa.';
            }
        }
    }
    // O NPC lembra apenas de ações que presenciou, nunca de votos secretos não revelados.
    if (function_exists('registrarMemoriaSocialNPC')) registrarMemoriaSocialNPC($npc,$meuNome,$mTipo, max(1,min(3,(int)ceil(abs($delta)/5))), "$meuNome tomou uma decisão em uma conversa com $npc.", 'dj|'.djSemana().'|'.$cena['id'].'|'.$npc.'|'.$indice);
    if (function_exists('on1RegistrarMemoria')) on1RegistrarMemoria($npc,$meuNome,$mTipo);
    if ($efeito === 'publico') {
        $_SESSION['evento_extra'][] = '⚖️ '.djEsc($meuNome).' se posicionou em uma situação envolvendo '.djEsc($npc).' na casa.';
    }
    $_SESSION['dj_historico'][] = ['semana'=>djSemana(), 'id'=>(string)$cena['id'],'titulo'=>(string)$cena['titulo'], 'npc'=>$npc, 'outro'=>$outro, 'escolha'=>(string)($op['rotulo']??''),'desfecho'=>$resultado,'privado'=>$efeito!=='publico'];
    $_SESSION['dj_historico'] = array_slice($_SESSION['dj_historico'], -24);
    $_SESSION['dj_resultado'] = ['icone'=>(string)$cena['icone'],'titulo'=>'Sua decisão teve consequências','texto'=>$resultado,'semana'=>djSemana(),'fase'=>$fase];
    $_SESSION['jogadores'] = $jogadores;
    return true;
}
