<?php

if (!modoTestePermitido()) {
    http_response_code(403);
    exit('Modo de testes disponível apenas localmente.');
}


/* Somente ações autenticadas e originadas da central local. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenRecebido = (string)($_POST['dev_token'] ?? '');
    if ($tokenRecebido === '' || !hash_equals((string)($_SESSION['dev_csrf'] ?? ''), $tokenRecebido)) {
        http_response_code(403);
        exit('Sessão de testes inválida. Recarregue a página DEV.');
    }
}

function devAvisar(string $mensagem): void {
    $_SESSION['dev_aviso'] = $mensagem;
}

function devVoltar(): never {
    header('Location: modo_teste.php');
    exit;
}

// Bateria DEV 3.0: opera sobre um snapshot e não altera o andamento do jogo.
if (isset($_POST['dev3_executar'])) {
    $escopo = (string)($_POST['dev3_escopo'] ?? 'completo');
    if (!in_array($escopo, ['completo','save','simulacoes'], true)) $escopo='completo';
    $antes = $_SESSION;
    try {
        $relatorio = dev3Bateria($antes, $escopo);
        // O único dado persistido é o relatório resumido, nunca estados simulados.
        $_SESSION['dev3_relatorio'] = $relatorio;
        devAvisar('Diagnóstico finalizado. Nenhuma fase, voto ou recompensa foi modificada.');
    } catch (Throwable $e) {
        $_SESSION = $antes;
        devAvisar('Não foi possível concluir os diagnósticos: '.get_class($e).' — '.$e->getMessage());
    }
    header('Location: modo_teste.php#dev3-testes'); exit;
}

// Iniciativa NPC 2.0: convite forçado na fase social atual, sem iniciar diálogo pelo jogador.
if (isset($_POST['dev_in2_forcar'])) {
    $faseI=(string)($_SESSION['fase_semana']??'');
    $semI=(int)($_SESSION['rodada']??1);
    if (!in_array($faseI,['interacoes_1','interacoes_2','interacoes_3'],true)) {
        devAvisar('Abra uma fase de Interações antes de testar a iniciativa de um NPC.'); devVoltar();
    }
    foreach (['in2_convite','in2_conversa','in2_resultado','i4_pendente','i4_resultado',
        'dj_ativo','dj_resultado','evento_convivencia_ativo','evento_convivencia_resultado'] as $c) {
        if (!empty($_SESSION[$c])) {
            devAvisar('Conclua ou recuse a conversa ou o evento aberto antes de gerar outro convite.'); devVoltar();
        }
    }
    $nomeD=(string)($_POST['dev_in2_npc']??'');
    $validos=array_values(array_filter($jogadores,fn($j)=>($j['nome']??'')===$nomeD &&
        $nomeD!==$meuNome && empty($j['eliminado']) && empty($j['fora_da_casa'])));
    if (!$validos) {devAvisar('Escolha um NPC ativo e diferente do jogador.'); devVoltar();}
    $_SESSION['dev_in2_forcar']=['fase'=>$faseI,'semana'=>$semI,'npc'=>$nomeD];
    unset($_SESSION['in2_usados_npc'][$semI][$nomeD]);
    devAvisar('Convite NPC preparado para esta fase. O próximo acesso ao jogo criará o convite.');
    header('Location: jogo.php#iniciativa-npc'); exit;
}

// Teste destrutivo de Dilemas 2.0 — somente localhost e com token DEV.
if (isset($_POST['dev_dilema_forcar'])) {
    $faseD = (string)($_POST['dev_dilema_fase'] ?? 'interacoes_1');
    if (!in_array($faseD, ['interacoes_1','festa'], true)) $faseD='interacoes_1';
    if (!empty($_SESSION['dj_ativo']) || !empty($_SESSION['dj_resultado']) ||
        !empty($_SESSION['evento_convivencia_ativo']) || !empty($_SESSION['evento_convivencia_resultado'])) {
        devAvisar('Conclua o evento já aberto antes de forçar outro dilema.'); devVoltar();
    }
    $_SESSION['fase_semana'] = $faseD;
    $_SESSION['dev_dj_forcar'] = true;
    unset($_SESSION['dj_gerado_semana'], $_SESSION['dj_tentado_'.(int)($_SESSION['rodada']??1).'_'.$faseD]);
    devAvisar('O próximo acesso às fases sociais irá gerar um dilema. Este teste altera o andamento do save.');
    header('Location: jogo.php'); exit;
}

// Testes das novas provas. A seleção NÃO concede vitória automaticamente.
if(isset($_POST['dev_prova_interativa'])){
    $valor=(string)($_POST['dev_tipo_prova']??'');
    $partes=explode(':',$valor,2);$classe=$partes[0]??'';$tipo=(int)($partes[1]??0);
    if(!in_array($classe,['lider','anjo'],true)||!pnEstaNova($classe,$tipo)){
        devAvisar('Escolha uma prova válida.');devVoltar();
    }
    if($classe==='anjo' && bbbRetaFinalSemAnjo($jogadores)){
        devAvisar('No Top 8 não há mais Prova do Anjo. Escolha uma Prova do Líder para testar.');devVoltar();
    }
    if($classe==='anjo'&&empty($_SESSION['lider'])){
        devAvisar('Defina um Líder no modo DEV antes de testar uma Prova do Anjo.');devVoltar();
    }
    if($classe==='lider'){
        $_SESSION['fase_semana']='lider';$_SESSION['prova_tipo']=$tipo;
        unset($_SESSION['pn_estado_lider'],$_SESSION['resultado_prova_lider_interativa']);
    }else{
        $_SESSION['fase_semana']='anjo';$_SESSION['prova_anjo_rodada']=(int)($_SESSION['rodada']??1);
        $_SESSION['prova_anjo_tipo']=$tipo;
        unset($_SESSION['pn_estado_anjo'],$_SESSION['resultado_prova_anjo'],$_SESSION['prova_anjo_finalizada'],$_SESSION['prova_anjo_finalizada_rodada']);
    }
    $_SESSION['dev_aviso']='Prova preparada no ambiente local.';
    header('Location: prova_'.$classe.'.php');exit;
}

// Forçar dinâmicas não confere vitória, voto nem poderes além do que o motor já implementa.
if (isset($_POST['dev_dinamica'])) {
    $tipo = (string)($_POST['tipo'] ?? '');
    $lista = dsDescricoes();
    $semana = (int)($_SESSION['rodada'] ?? 1);
    if (!isset($lista[$tipo])) devAvisar('Escolha uma dinâmica válida.');
    elseif ($semana < 2) devAvisar('Avance para a Semana 2 ou posterior antes de testar dinâmicas surpresa.');
    elseif (count($jogadores) <= 5) devAvisar('No Top 5 não existem mais dinâmicas surpresa, nem mesmo pelo modo DEV.');
    elseif (count($jogadores) < ($tipo === 'paredao_especial' ? 8 : 6)) devAvisar('Participantes insuficientes para essa dinâmica.');
    elseif (!empty($_SESSION['paredao_formado']) || in_array((string)($_SESSION['fase_semana'] ?? ''), ['eliminacao','resultado'], true)) devAvisar('Esta semana já tem um Paredão em andamento. Teste a regra antes da formação.');
    else {
        $_SESSION['ds3'] = ['rodada'=>$semana, 'tipo'=>$tipo, 'imunidade_aplicada'=>false, 'carta_aplicada'=>false];
        $_SESSION['ds3_ultimo_sorteio'] = $semana;
        $_SESSION['ds3_ultima_ocorrencia'] = $semana;
        unset($_SESSION['ds3_imune'],$_SESSION['ds3_carta'],$_SESSION['ds3_contra_resolvido']);
        $desc = $lista[$tipo];
        registrarEventoModoTeste('Produção: '.$desc['titulo'].' — '.$desc['descricao']);
        devAvisar('Dinâmica ativada: '.$desc['titulo'].'. O efeito será aplicado pela regra da fase correspondente.');
    }
    devVoltar();
}

if (isset($_POST['dev_aplicar_dinamica'])) {
    $tipo = (string)(dsAtual()['tipo'] ?? '');
    if ($tipo === 'imunidade_surpresa') {
        dsAplicarImunidade($jogadores);
        $_SESSION['jogadores'] = $jogadores;
        devAvisar('Imunidade testada: '.(string)($_SESSION['ds3_imune']['nome'] ?? 'sem elegíveis').'. Verifique a proteção no Paredão.');
    } elseif ($tipo === 'carta_misteriosa') {
        dsAplicarCarta($jogadores);
        devAvisar('Carta testada: '.(string)($_SESSION['ds3_carta']['nome'] ?? 'sem elegíveis').'. Confira o voto de peso dois na votação.');
    } else devAvisar('Esta dinâmica é testada na formação do Paredão e não possui efeito imediato.');
    devVoltar();
}

if (isset($_POST['dev_limpar_dinamica'])) {
    $semana = (int)($_SESSION['rodada'] ?? 1);
    $_SESSION['ds3'] = ['rodada'=>$semana, 'tipo'=>''];
    $_SESSION['ds3_ultimo_sorteio'] = $semana; // Não sortear outra ao reabrir o jogo.
    unset($_SESSION['ds3_imune'], $_SESSION['ds3_carta']);
    devAvisar('Dinâmica desativada. Ações já finalizadas não foram revertidas.');
    devVoltar();
}

if (isset($_POST['dev_crise_alianca'])) {
    $nome = (string)($_POST['membro_crise'] ?? '');
    $meu = (string)($_SESSION['meu_nome'] ?? '');
    $grupo = (string)(obterAliancaJogador($jogadores,$meu) ?? '');
    $membros = $grupo !== '' ? membrosDaAlianca($jogadores,$grupo) : [];
    if ($grupo !== '' && !nomeIgual($nome,$meu) && in_array($nome,$membros,true)) {
        $_SESSION['a3_ajustes'][$nome] = -85;
        $_SESSION['a3_tensoes'][$grupo] = ['rodada'=>(int)$_SESSION['rodada'], 'nomes'=>[$nome]];
        $_SESSION['a3_aviso'] = 'Há desconfiança dentro da aliança. Uma reunião pode ajudar.';
        devAvisar('Crise preparada com '.$nome.'. Lealdade atual: '.a3Lealdade($jogadores,$nome).' / 100.');
    } else devAvisar('Selecione um NPC da sua aliança atual.');
    devVoltar();
}

if (isset($_POST['dev_reuniao_alianca'])) {
    $grupo = (string)(obterAliancaJogador($jogadores,(string)($_SESSION['meu_nome']??'')) ?? '');
    if ($grupo && count(membrosDaAlianca($jogadores,$grupo)) >= 2) {
        unset($_SESSION['a3_reuniao_realizada'][$grupo]);
        $_SESSION['fase_semana'] = 'interacoes_1';
        $_SESSION['acoes_restantes'] = max(1,(int)($_SESSION['acoes_restantes']??0));
        devAvisar('Reunião liberada em Interações 1.');
        header('Location: jogo.php'); exit;
    }
    devAvisar('Forme uma aliança antes de repetir reuniões.');
    devVoltar();
}

if (isset($_POST['dev_cena_especial'])) {
    $fase = (string)($_POST['dev_cena_fase']??'interacoes_1');
    if (!in_array($fase,['interacoes_1','festa'],true)) $fase = 'interacoes_1';
    $_SESSION['fase_semana'] = $fase;
    $_SESSION['dev_ec_forcar'] = true;
    unset($_SESSION['evento_convivencia_ativo']);
    unset($_SESSION['ec_tentado_'.$fase.'_'.(int)($_SESSION['rodada']??1)]);
    $_SESSION['acoes_restantes'] = max(1,(int)($_SESSION['acoes_restantes']??0));
    header('Location: jogo.php'); exit;
}

/* =========================================================
   🔥 RODADA
   ========================================================= */
if (isset($_POST['dev_rodada'])) {

    $novaSemana =
        max(
            1,
            (int)($_POST['rodada'] ?? 1)
        );

    $_SESSION['rodada'] =
        $novaSemana;

    registrarEventoModoTeste(
        "Semana alterada para $novaSemana."
    );

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   🪙 MOEDAS
   ========================================================= */
if (isset($_POST['dev_moedas'])) {

    $_SESSION['moedas_publico'] =
        max(
            0,
            (int)(
                $_POST['moedas']
                ?? 0
            )
        );

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   ➕ 100 MOEDAS
   ========================================================= */
if (isset($_POST['dev_add_moedas'])) {

    $_SESSION['moedas_publico'] =
        max(
            0,
            (int)(
                $_SESSION['moedas_publico']
                ?? 0
            )
        ) + 100;

    registrarEventoModoTeste(
        '100 moedas adicionadas.'
    );

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   🎮 TROCAR FASE
   ========================================================= */
if (isset($_POST['dev_fase'])) {

    $fase =
        $_POST['fase'] ?? '';

    $permitidas =
        fasesDisponiveisModoTeste();

    if (isset($permitidas[$fase])) {

        $_SESSION['fase_semana'] =
            $fase;

        if (
            str_contains(
                $fase,
                'interacoes'
            )
        ) {
            $_SESSION['acoes_restantes'] =
                3;
        }

        registrarEventoModoTeste(
            "Fase alterada para $fase."
        );
    }

    header('Location: jogo.php');
    exit;
}


/* =========================================================
   👑 DEFINIR LÍDER
   ========================================================= */
if (isset($_POST['dev_lider'])) {

    $nome =
        trim(
            $_POST['participante']
            ?? ''
        );

    if ($nome !== '') {

        definirStatusParticipanteModoTeste(
            $jogadores,
            'lider',
            $nome
        );

        $_SESSION['lider'] =
            $nome;

        $_SESSION['jogadores'] =
            $jogadores;

        registrarEventoModoTeste(
            "$nome foi definido como Líder."
        );
    }

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   😇 DEFINIR ANJO
   ========================================================= */
if (isset($_POST['dev_anjo'])) {

    $nome =
        trim(
            $_POST['participante']
            ?? ''
        );

    if ($nome !== '') {

        definirStatusParticipanteModoTeste(
            $jogadores,
            'anjo',
            $nome
        );

        $_SESSION['anjo'] =
            $nome;

        $_SESSION['prova_anjo_finalizada'] =
            true;

        $_SESSION['jogadores'] =
            $jogadores;

        registrarEventoModoTeste(
            "$nome foi definido como Anjo."
        );
    }

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   🛡️ DEFINIR IMUNE
   ========================================================= */
if (isset($_POST['dev_imune'])) {

    $nome =
        trim(
            $_POST['participante']
            ?? ''
        );

    if ($nome !== '') {

        definirStatusParticipanteModoTeste(
            $jogadores,
            'imune',
            $nome
        );

        $_SESSION['imune'] =
            $nome;

        $_SESSION['jogadores'] =
            $jogadores;

        registrarEventoModoTeste(
            "$nome foi definido como imune."
        );
    }

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   🚨 ADICIONAR AO PAREDÃO
   ========================================================= */
if (isset($_POST['dev_add_paredao'])) {

    $nome =
        trim(
            $_POST['participante']
            ?? ''
        );

    if (!isset($_SESSION['paredao'])) {
        $_SESSION['paredao'] = [];
    }

    if (
        $nome !== '' &&
        !in_array(
            $nome,
            $_SESSION['paredao'],
            true
        )
    ) {
        $_SESSION['paredao'][] =
            $nome;
    }

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   🧹 LIMPAR PAREDÃO
   ========================================================= */
if (isset($_POST['dev_limpar_paredao'])) {

    unset(
        $_SESSION['paredao'],
        $_SESSION['paredao_formado'],
        $_SESSION['votos_paredao']
    );

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   🚨 FORÇAR PAREDÃO FALSO
   ========================================================= */
if (isset($_POST['dev_paredao_falso'])) {

    $_SESSION['forcar_paredao_falso'] =
        true;

    registrarEventoModoTeste(
        'O próximo resultado será um Paredão Falso.'
    );

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   🏠 FORÇAR CASA DE VIDRO 2.0
   ========================================================= */
if (isset($_POST['dev_casa_vidro'])) {

    $_SESSION['rodada'] = 3;
    $_SESSION['fase_semana'] = 'queridometro';
    $_SESSION['acoes_restantes'] = 3;

    foreach (
        array_keys($_SESSION)
        as $chaveSessao
    ) {
        if (
            strpos(
                (string)$chaveSessao,
                'casa_vidro_'
            ) === 0
        ) {
            unset($_SESSION[$chaveSessao]);
        }
    }

    $_SESSION['casa_vidro_versao'] = 2;
    $_SESSION['casa_vidro_estado'] = 'nao_decidida';
    $_SESSION['forcar_casa_vidro'] = true;

    unset(
        $_SESSION['queridometro_feito'],
        $_SESSION['queridometro_resultado'],
        $_SESSION['confessionario_feito'],
        $_SESSION['confessionario_falas']
    );

    registrarEventoModoTeste(
        'Casa de Vidro 2.0 forçada para o início da Semana 3.'
    );

    header('Location: jogo.php');
    exit;
}


/* =========================================================
   ☎️ BIG FONE
   ========================================================= */
if (isset($_POST['dev_bigfone'])) {

    $_SESSION['fase_semana'] =
        'bigfone';

    unset(
        $_SESSION['bigfone_feito']
    );

    header('Location: big_fone.php');
    exit;
}


/* =========================================================
   🏆 IR PARA FINAL
   ========================================================= */
if (isset($_POST['dev_final'])) {

    $jogadores =
        array_values(
            $_SESSION['jogadores']
            ?? []
        );

    if (count($jogadores) > 3) {

        $jogadores =
            array_slice(
                $jogadores,
                0,
                3
            );

        $_SESSION['jogadores'] =
            $jogadores;
    }

    unset(
        $_SESSION['ranking_final'],
        $_SESSION['percentuais_final'],
        $_SESSION['pontuacoes_final'],
        $_SESSION['ordem_visual_final']
    );

    $_SESSION['fase_semana'] =
        'finalistas';

    header('Location: final.php');
    exit;
}


/* =========================================================
   📣 LIMPAR AO VIVO
   ========================================================= */
if (isset($_POST['dev_limpar_ao_vivo'])) {

    $_SESSION['evento_extra'] = [];

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   📱 LIMPAR FEED
   ========================================================= */
if (isset($_POST['dev_limpar_feed'])) {

    $_SESSION['feed_publico'] = [];

    unset(
        $_SESSION['feed_publico_historico_textos'],
        $_SESSION['feed_publico_historico_contextos']
    );

    header('Location: modo_teste.php');
    exit;
}


/* =========================================================
   🧹 RESETAR ESTADO DA SEMANA
   ========================================================= */
if (isset($_POST['dev_reset_semana'])) {

    limparEstadoSemanaModoTeste();

    $_SESSION['fase_semana'] =
        'queridometro';

    $_SESSION['acoes_restantes'] =
        3;

    header('Location: modo_teste.php');
    exit;
}
