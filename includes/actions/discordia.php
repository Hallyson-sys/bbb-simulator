<?php

/** @var array $jogadores */
/** @var string $meuNome */

require_once __DIR__ . '/../logica/consequencias_sociais.php';


/* =========================
   🔥 PROCESSAR JOGO DA DISCÓRDIA
========================= */

if (isset($_POST['fazer_discordia'])) {

    $tema =
        $_SESSION['tema_discordia']
        ?? ($_POST['tema_discordia'] ?? 'sonso');

    $intensidade =
        $_POST['intensidade'] ?? 'leve';

    $evento = "";


    /* =========================
       😡 TEMAS NEGATIVOS
       Sonso / Falso / Saboneteiro
    ========================= */

    if (
        $tema == "sonso" ||
        $tema == "falso" ||
        $tema == "saboneteiro"
    ) {
        $alvo = $_POST['alvo_discordia'] ?? '';

        if ($alvo != '') {

            if ($intensidade == "com_tudo") {

                aplicarConsequenciaSocial(
                    $jogadores,
                    $meuNome,
                    $alvo,
                    'discordia_negativa_forte',
                    'discordia|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|forte|' .
                    $meuNome . '|' . $alvo,
                    "$meuNome atacou $alvo com tudo no Jogo da Discórdia."
                );

                ajustarPopularidadePorAlvo(
                    $jogadores,
                    $meuNome,
                    $alvo,
                    "bateu de frente com alguém rejeitado pelo público",
                    "passou do ponto contra alguém querido"
                );

                $evento =
                    "🔥 $meuNome chamou $alvo de $tema COM TUDO no Jogo da Discórdia. Afinidade com $alvo caiu 15 pontos.";
            }

            elseif ($intensidade == "leve") {

                aplicarConsequenciaSocial(
                    $jogadores,
                    $meuNome,
                    $alvo,
                    'discordia_negativa_leve',
                    'discordia|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|leve|' .
                    $meuNome . '|' . $alvo,
                    "$meuNome atacou $alvo de forma leve no Jogo da Discórdia."
                );

                alterarPopularidadePublica(
                    $jogadores,
                    $meuNome,
                    -3,
                    4,
                    "participou do Jogo da Discórdia sem exagerar",
                    true
                );

                $evento =
                    "😶 $meuNome chamou $alvo de $tema de forma mais leve. Afinidade com $alvo caiu 6 pontos.";
            }

            else {

                aplicarConsequenciaSocial(
                    $jogadores,
                    $meuNome,
                    $alvo,
                    'discordia_sabonete',
                    'discordia|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|sabonete|' .
                    $meuNome . '|' . $alvo,
                    "$meuNome sabonetou ao falar de $alvo no Jogo da Discórdia."
                );

                alterarPopularidadeMotivo(
                    $jogadores,
                    $meuNome,
                    -5,
                    -5,
                    "saboneteou no Jogo da Discórdia"
                );

                $evento =
                    "🧼 $meuNome sabonetou ao falar sobre $alvo. Afinidade com $alvo caiu 2 pontos.";
            }
        }
    }


    /* =========================
       🤝 TEMA POSITIVO — ALIADO
    ========================= */

    if ($tema == "aliado") {
        $alvo = $_POST['alvo_discordia'] ?? '';

        if ($alvo != '') {

            aplicarConsequenciaSocial(
                $jogadores,
                $meuNome,
                $alvo,
                'discordia_aliado',
                'discordia|' .
                ($_SESSION['rodada'] ?? 1) .
                '|aliado|' .
                $meuNome . '|' . $alvo,
                "$meuNome declarou $alvo como aliado no Jogo da Discórdia."
            );

            alterarPopularidadePublica(
                $jogadores,
                $meuNome,
                1,
                4,
                "defendeu um aliado no Jogo da Discórdia",
                true
            );

            $evento =
                "🤝 $meuNome declarou que $alvo é seu maior aliado. Afinidade com $alvo subiu 12 pontos.";
        }
    }


    /* =========================
       🎬/🎭 TEMAS COM CATEGORIAS
    ========================= */

    if (function_exists('temaDiscordiaEhCategorias') && temaDiscordiaEhCategorias($tema)) {
        $configTema = configuracaoTemaDiscordia($tema);
        $categorias = $configTema['categorias'] ?? [];
        $escolhas = [];
        $usados = [];
        $erroCategorias = '';

        foreach ($categorias as $chaveCategoria => $categoria) {
            $campo = 'categoria_' . $chaveCategoria;
            $alvo = trim((string)($_POST[$campo] ?? ''));
            $permiteSi = !empty($categoria['permite_si']);

            if ($alvo === '') {
                $erroCategorias = 'Escolha um participante para todas as categorias.';
                break;
            }

            if (nomeIgual($alvo, $meuNome) && !$permiteSi) {
                $erroCategorias = 'Você não pode escolher a si mesmo nessa categoria.';
                break;
            }

            if (in_array($alvo, $usados, true)) {
                $erroCategorias = 'Use participantes diferentes em cada categoria.';
                break;
            }

            $escolhas[$chaveCategoria] = $alvo;
            $usados[] = $alvo;
        }

        if ($erroCategorias !== '') {
            $_SESSION['evento_extra'][] = '⚠️ ' . $erroCategorias;
            header('Location: jogo.php');
            exit;
        }

        $partesEvento = [];

        foreach ($escolhas as $chaveCategoria => $alvo) {
            $categoria = $categorias[$chaveCategoria] ?? [];
            $efeito = $categoria['efeito'] ?? 'negativo';

            if (!nomeIgual($alvo, $meuNome)) {
                aplicarEfeitoCategoriaDiscordia(
                    $jogadores,
                    $meuNome,
                    $alvo,
                    $efeito,
                    'discordia|' .
                    ($_SESSION['rodada'] ?? 1) .
                    '|' . $tema . '|' . $chaveCategoria . '|' .
                    $meuNome . '|' . $alvo,
                    $meuNome . ' colocou ' . $alvo . ' como ' . ($categoria['nome'] ?? $chaveCategoria) . ' no Jogo da Discórdia.'
                );

                if (function_exists('registrarRelacaoMarcante')) {
                    registrarRelacaoMarcante($jogadores, $meuNome, $alvo);
                    registrarRelacaoMarcante($jogadores, $alvo, $meuNome);
                }
            }

            $partesEvento[] = ($categoria['emoji'] ?? '•') . ' ' .
                ($categoria['nome'] ?? $chaveCategoria) . ': ' . $alvo;
        }

        $evento = ($configTema['titulo'] ?? '🔥 Jogo da Discórdia') .
            ' — ' . $meuNome . ': ' . implode(' | ', $partesEvento) . '.';
    }


    /* =========================
       🏆 PÓDIO
    ========================= */

    if ($tema == "podio") {
        $primeiro = $meuNome;
        $segundo = $_POST['podio_2'] ?? '';
        $terceiro = $_POST['podio_3'] ?? '';

        if (
            $segundo != '' &&
            $terceiro != '' &&
            $segundo != $terceiro &&
            !nomeIgual($segundo, $primeiro) &&
            !nomeIgual($terceiro, $primeiro)
        ) {

            aplicarConsequenciaSocial(
                $jogadores,
                $meuNome,
                $segundo,
                'discordia_podio_2',
                'discordia|' .
                ($_SESSION['rodada'] ?? 1) .
                '|podio2|' .
                $meuNome . '|' . $segundo,
                "$meuNome colocou $segundo em segundo lugar no pódio."
            );

            aplicarConsequenciaSocial(
                $jogadores,
                $meuNome,
                $terceiro,
                'discordia_podio_3',
                'discordia|' .
                ($_SESSION['rodada'] ?? 1) .
                '|podio3|' .
                $meuNome . '|' . $terceiro,
                "$meuNome colocou $terceiro em terceiro lugar no pódio."
            );

            $evento =
                "🏆 $meuNome montou seu pódio: 🥇 $primeiro, 🥈 $segundo e 🥉 $terceiro. Afinidades subiram.";

        } else {
            $evento =
                "⚠️ O 2º e o 3º lugar precisam ser participantes diferentes.";

            $_SESSION['evento_extra'][] = $evento;

            header("Location: jogo.php");
            exit;
        }
    }


    if ($evento == "") {
        $evento =
            "🔥 O Jogo da Discórdia aconteceu, mas nenhuma escolha válida foi registrada.";
    }

    $_SESSION['evento_extra'][] = $evento;

    /* NPCs também participam — mantém sua lógica atual. */
    $eventosNPC = gerarDiscordiaNPC(
        $jogadores,
        $meuNome,
        $tema
    );

    foreach ($eventosNPC as $ev) {
        $_SESSION['evento_extra'][] = $ev;
    }

    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['discordia_feito'] = true;

    unset($_SESSION['tema_discordia']);

    header("Location: jogo.php");
    exit;
}
