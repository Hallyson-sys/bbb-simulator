<?php

require_once __DIR__ . '/gramatica.php';

/* =========================================================
   🚀 INICIALIZAÇÃO DO JOGO
   ========================================================= */


/* =========================================================
   🔒 IDENTIDADE FIXA DOS PARTICIPANTES

   Nome, idade, profissão, estado e personalidade são dados
   definidos quando o participante entra na temporada.

   Depois disso, esses campos NÃO podem mudar entre rodadas.
   O registro fica guardado na sessão e é restaurado caso
   algum fluxo altere esses dados por engano.
   ========================================================= */
function chaveIdentidadeParticipante($nome)
{
    return mb_strtolower(
        trim((string)$nome),
        'UTF-8'
    );
}


function garantirIdentidadeFixaParticipante(&$jogador)
{
    if (
        !isset($_SESSION['identidades_participantes']) ||
        !is_array($_SESSION['identidades_participantes'])
    ) {
        $_SESSION['identidades_participantes'] = [];
    }

    $nome = trim($jogador['nome'] ?? '');

    if ($nome === '') {
        return;
    }

    $chave = chaveIdentidadeParticipante($nome);

    $camposFixos = [
        'nome',
        'idade',
        'profissao',
        'estado',
        'personalidade',
        'genero',
        'origem'
    ];

    /*
     * Primeira vez que o participante aparece:
     * registra sua identidade oficial da temporada.
     */
    if (
        !isset($_SESSION['identidades_participantes'][$chave]) ||
        !is_array($_SESSION['identidades_participantes'][$chave])
    ) {
        $identidade = [];

        foreach ($camposFixos as $campo) {
            if (array_key_exists($campo, $jogador)) {
                $identidade[$campo] = $jogador[$campo];
            }
        }

        $_SESSION['identidades_participantes'][$chave] =
            $identidade;

        return;
    }

    /*
     * Nas próximas rodadas, restaura os dados originais.
     * Popularidade, humor, relações, status etc. NÃO entram
     * aqui porque esses campos devem mudar durante o jogo.
     */
    $identidade =
        $_SESSION['identidades_participantes'][$chave];

    foreach ($camposFixos as $campo) {
        if (array_key_exists($campo, $identidade)) {
            $jogador[$campo] = $identidade[$campo];
        }
    }
}


/* =========================================================
   👥 REMOVER PARTICIPANTES DUPLICADOS
   ========================================================= */
function removerParticipantesDuplicados(
    &$jogadores,
    $meuNome
) {
    $nomesUsados = [];
    $jogadoresUnicos = [];

    foreach ($jogadores as $j) {
        $nome = trim($j['nome'] ?? '');

        if ($nome == '') {
            continue;
        }

        $chaveNome = mb_strtolower($nome, 'UTF-8');

        if (isset($nomesUsados[$chaveNome])) {
            /* Se o duplicado for o jogador, prioriza o snapshot mais recente. */
            if (nomeIgual($nome, $meuNome)) {
                $idx = $nomesUsados[$chaveNome];
                $jogadoresUnicos[$idx] =
                    $_SESSION['meu_jogador_snapshot'] ?? $j;
            }

            continue;
        }

        $nomesUsados[$chaveNome] = count($jogadoresUnicos);
        $jogadoresUnicos[] = $j;
    }

    $jogadores = array_values($jogadoresUnicos);

    garantirMeuJogadorNaLista($jogadores);
}


/* =========================================================
   🧱 GARANTIR ESTRUTURA DOS PARTICIPANTES
   Compatibilidade com saves antigos
   ========================================================= */
function garantirEstruturaParticipantes(&$jogadores)
{
    foreach ($jogadores as &$j) {

        /* =========================
           🔒 IDENTIDADE IMUTÁVEL
           ========================= */
        /* Gênero gramatical: compatibilidade com saves anteriores. */
        if (!isset($j['genero']) || normalizarGeneroBBB($j['genero']) === null) {
            $j['genero'] = generoNomeConhecidoBBB($j['nome'] ?? '') ?? 'nao_informado';
        }

        garantirIdentidadeFixaParticipante($j);


        /* =========================
           📈 POPULARIDADE
           ========================= */
        if (!isset($j['popularidade'])) {
            $j['popularidade'] = 50;
        }

        $j['popularidade'] = limitar(
            $j['popularidade'],
            0,
            100
        );

        if (
            !isset($j['historico_popularidade']) ||
            !is_array($j['historico_popularidade'])
        ) {
            $j['historico_popularidade'] = [];
        }


        /* =========================
           💕 ROMANCES
           ========================= */
        if (
            !isset($j['romances']) ||
            !is_array($j['romances'])
        ) {
            $j['romances'] = [];
        }


        /* =========================
           🎥 CONFESSIONÁRIOS
           ========================= */
        if (
            !isset($j['confessionarios']) ||
            !is_array($j['confessionarios'])
        ) {
            $j['confessionarios'] = [];
        }


        /* =========================
           🤝 ALIANÇAS
           ========================= */
        if (!array_key_exists('alianca', $j)) {
            $j['alianca'] = null;
        }

        if (
            !isset($j['historico_aliancas']) ||
            !is_array($j['historico_aliancas'])
        ) {
            $j['historico_aliancas'] = [];
        }
    }

    unset($j);
}


/* =========================================================
   ❤️ GARANTIR RELAÇÕES INICIAIS DO JOGADOR
   ========================================================= */
function garantirRelacoesIniciaisJogador(
    $jogadores,
    $meuNome
) {
    if (
        !isset($_SESSION['relacoes_jogador']) ||
        !is_array($_SESSION['relacoes_jogador'])
    ) {
        $_SESSION['relacoes_jogador'] = [];
    }

    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';

        if (
            $nome == '' ||
            nomeIgual($nome, $meuNome)
        ) {
            continue;
        }

        if (!isset($_SESSION['relacoes_jogador'][$nome])) {
            /* A afinidade começa em 0 e NÃO é limitada a 100. */
            $_SESSION['relacoes_jogador'][$nome] = 0;
        }
    }
}


/* =========================================================
   🚫 ATUALIZAR ESTADO DO JOGADOR
   ========================================================= */
   function atualizarEstadoDoMeuJogador(
    $jogadores,
    $meuNome
) {
    $meuJogadorAtual = null;

    /* =====================================================
       👤 PROCURAR JOGADOR NA LISTA ATIVA
       ===================================================== */
    foreach ($jogadores as $j) {

        if (
            nomeIgual(
                $j['nome'] ?? '',
                $meuNome
            )
        ) {
            $meuJogadorAtual = $j;
            break;
        }
    }


    /* =====================================================
       🚪 VERIFICAR QUARTO SECRETO
       ===================================================== */

    $falsoEliminado =
        trim(
            (string)(
                $_SESSION['falso_eliminado']
                ?? ''
            )
        );

    $estaNoQuartoSecreto =
        !empty($_SESSION['paredao_falso_ativo']) &&
        $falsoEliminado !== '' &&
        $meuNome !== '' &&
        nomeIgual(
            $falsoEliminado,
            $meuNome
        );


    /*
     * Se estou no Quarto Secreto,
     * NÃO posso ser considerado realmente eliminado.
     */
    if ($estaNoQuartoSecreto) {
        unset(
            $_SESSION['jogador_eliminado']
        );
    }


    /* =====================================================
       💾 ATUALIZAR SNAPSHOT ENQUANTO ESTIVER NA CASA
       ===================================================== */

    if ($meuJogadorAtual != null) {

        $_SESSION['meu_jogador_snapshot'] =
            $meuJogadorAtual;

        $_SESSION['minha_popularidade_final'] =
            $meuJogadorAtual['popularidade']
            ?? 50;
    }


    /* =====================================================
       ❌ DESCOBRIR ELIMINAÇÃO REAL
       ===================================================== */

    $eliminadoSessao =
        $_SESSION['eliminado']
        ?? null;

    $nomeEliminadoSessao = '';

    if (is_array($eliminadoSessao)) {

        $nomeEliminadoSessao =
            $eliminadoSessao['nome']
            ?? '';

    } else {

        $nomeEliminadoSessao =
            (string)$eliminadoSessao;
    }


    /*
     * IMPORTANTE:
     *
     * Se o jogador estiver no Quarto Secreto,
     * ele está temporariamente fora de $jogadores.
     *
     * Isso NÃO significa eliminação real.
     */
    if (
        $meuNome != '' &&
        !$estaNoQuartoSecreto &&
        (
            $meuJogadorAtual == null ||
            nomeIgual(
                $nomeEliminadoSessao,
                $meuNome
            )
        ) &&
        (
            $_SESSION['fase_semana']
            ?? ''
        ) != 'jogador_eliminado'
    ) {

        $_SESSION['jogador_eliminado'] =
            true;

        $_SESSION['fase_semana'] =
            'jogador_eliminado';

        $_SESSION['minha_colocacao_final'] =
            count($jogadores) + 1;


        if (
            !isset($_SESSION['evento_extra']) ||
            !is_array($_SESSION['evento_extra'])
        ) {
            $_SESSION['evento_extra'] = [];
        }


        $_SESSION['evento_extra'][] =
            "🚫 $meuNome foi eliminado. Sua participação na temporada chegou ao fim.";
    }


    /* =====================================================
       🔒 MANTER ESTADO DE ELIMINAÇÃO REAL
       ===================================================== */

    if (
        isset($_SESSION['jogador_eliminado']) &&
        !$estaNoQuartoSecreto &&
        !isset($_POST['novo_jogo'])
    ) {

        $_SESSION['fase_semana'] =
            'jogador_eliminado';
    }


    return $meuJogadorAtual;
}
