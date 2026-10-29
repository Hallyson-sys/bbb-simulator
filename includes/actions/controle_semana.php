<?php

require_once __DIR__ . '/../logica/consequencias_sociais.php';

/** @var array $jogadores */
/** @var string $meuNome */
/** @var int $qtdVIP */


/* =========================
   👑 DEFINIR VIP / XEPA
========================= */

if (isset($_POST['definir_vip']) || isset($_POST['ld_resolver_vip'])) {
    $lider = (string)($_SESSION['lider'] ?? '');
    $dupla = ldLideresAtuais($jogadores);
    $ehDupla = count($dupla) === 2;
    $podeDecidir = $ehDupla ? ldParticipa($jogadores, $meuNome) : nomeIgual($lider, $meuNome);
    if (($_SESSION['fase_semana'] ?? '') !== 'vip_xepa' || isset($_SESSION['vip_definido']) || !$podeDecidir) {
        header('Location: jogo.php'); exit;
    }
    garantirMeuJogadorNaLista($jogadores);
    $elegiveis = $ehDupla ? ldElegiveisVIP($jogadores) : array_values(array_diff(array_column($jogadores, 'nome'), [$lider]));
    $vagas = $ehDupla ? ldVagasExtrasVIP($jogadores, (int)$qtdVIP) : (int)$qtdVIP;
    $pendente = (array)($_SESSION['ld_vip_pendente'] ?? []);
    if ($pendente && (int)($pendente['semana'] ?? 0) !== (int)($_SESSION['rodada'] ?? 1)) {
        unset($_SESSION['ld_vip_pendente']); $pendente = [];
    }
    $resolvendo = isset($_POST['ld_resolver_vip']);
    if ($resolvendo) {
        if (!$ehDupla || (int)($pendente['semana'] ?? 0) !== (int)($_SESSION['rodada'] ?? 1)) {
            header('Location: jogo.php'); exit;
        }
        $modo = (string)($_POST['ld_decisao'] ?? '');
        if ($modo === 'concordar') $selecionados = (array)($pendente['preferencia'] ?? []);
        elseif ($modo === 'negociar') $selecionados = ldAcordoVIP((array)($pendente['proposta'] ?? []), (array)($pendente['preferencia'] ?? []), $vagas);
        else { header('Location: jogo.php'); exit; }
    } else {
        if ($pendente) { header('Location: jogo.php'); exit; }
        $selecionados = $_POST['vip'] ?? [];
    }
    if (!is_array($selecionados)) $selecionados = [];
    $selecionados = array_values(array_unique(array_map('strval', $selecionados)));
    if (count($selecionados) !== $vagas || array_diff($selecionados, $elegiveis)) {
        $_SESSION['evento_extra'][] = "⚠️ Escolha exatamente $vagas pessoa(s) elegíveis para o VIP.";
        header('Location: jogo.php'); exit;
    }
    if ($ehDupla && !$resolvendo) {
        $outro = ldOutroLider($jogadores, $meuNome);
        $preferencia = ldPreferenciasVIP($jogadores, $outro, $vagas);
        $intersecao = count(array_intersect($selecionados, $preferencia));
        if ($intersecao < max(1, $vagas - 1) && $vagas > 0) {
            $_SESSION['ld_vip_pendente'] = [
                'semana'=>(int)($_SESSION['rodada']??1), 'proposta'=>$selecionados,
                'preferencia'=>$preferencia, 'parceiro'=>$outro
            ];
            header('Location: jogo.php'); exit;
        }
    }
    unset($_SESSION['ld_vip_pendente']);
    $lideresVIP = $ehDupla ? $dupla : [$lider];
    foreach ($jogadores as &$j) {
        if (!isset($j['status']) || !is_array($j['status'])) $j['status'] = [];
        if (!isset($j['estatisticas']) || !is_array($j['estatisticas'])) $j['estatisticas'] = [];
        $nome = (string)($j['nome'] ?? '');
        $vip = in_array($nome, $lideresVIP, true) || in_array($nome, $selecionados, true);
        $j['status']['vip'] = $vip;
        $j['status']['xepa'] = !$vip;
        $chave = $vip ? 'vip' : 'xepa';
        $j['estatisticas'][$chave] = ($j['estatisticas'][$chave] ?? 0) + 1;
    }
    unset($j);
    foreach ($selecionados as $nomeVIP) {
        foreach ($lideresVIP as $responsavel) {
            aplicarConsequenciaSocial(
                $jogadores, $responsavel, $nomeVIP, 'vip',
                'vip|' . ($_SESSION['rodada'] ?? 1) . '|' . $responsavel . '|' . $nomeVIP,
                "$responsavel colocou $nomeVIP no VIP."
            );
        }
    }
    garantirMeuJogadorNaLista($jogadores);
    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['vip_definido'] = true;
    $_SESSION['fase_semana'] = bbbFaseAposVip($jogadores);
    $vipLista = []; $xepaLista = [];
    foreach ($jogadores as $j) {
        if (!empty($j['status']['vip'])) $vipLista[] = $j['nome'];
        if (!empty($j['status']['xepa'])) $xepaLista[] = $j['nome'];
    }
    $_SESSION['evento_extra'][] = $ehDupla
        ? '👑 ' . implode(' e ', $dupla) . ' decidiram juntos o VIP.'
        : "👑 O líder $lider definiu o VIP.";
    $_SESSION['evento_extra'][] = '🟡 VIP: ' . implode(', ', $vipLista) . '.';
    $_SESSION['evento_extra'][] = '🍞 Xepa: ' . implode(', ', $xepaLista) . '.';
    if ($ehDupla && $resolvendo) {
        $_SESSION['evento_extra'][] = '🤝 Os dois Líderes conversaram e chegaram a um acordo sobre o VIP.';
    }
    if (function_exists('registrarHistoricoTemporada')) {
        registrarHistoricoTemporada('vip_xepa', 'VIP e Xepa',
            'VIP: ' . implode(', ', $vipLista) . '. Xepa: ' . implode(', ', $xepaLista) . '.',
            array_merge($vipLista, $xepaLista), '🍽️', null, 'vip_xepa');
    }
    header('Location: jogo.php'); exit;
}


/* =========================
   👹 DEFINIR MONSTRO
========================= */

if (isset($_POST['definir_monstro'])) {

    $anjo = $_SESSION['anjo'] ?? '';
    $selecionados = $_POST['monstro'] ?? [];

    $selecionados = array_values(
        array_unique(
            array_filter(
                $selecionados,
                function ($nome) use ($anjo) {
                    return
                        trim((string)$nome) !== '' &&
                        !nomeIgual($nome, $anjo);
                }
            )
        )
    );

    if (count($selecionados) > 2) {

        $_SESSION['evento_extra'][] =
            "⚠️ Você só pode escolher até 2 participantes para o Monstro.";

        header("Location: jogo.php");
        exit;
    }

    foreach ($jogadores as &$j) {

        if (!isset($j['status']) || !is_array($j['status'])) {
            $j['status'] = [];
        }

        $j['status']['monstro'] = false;

        if (
            in_array(
                $j['nome'] ?? '',
                $selecionados,
                true
            )
        ) {
            $nomeAlvo = $j['nome'] ?? '';

            $j['status']['monstro'] = true;

            if (!isset($j['estatisticas'])) {
                $j['estatisticas'] = [];
            }

            $j['estatisticas']['monstro'] =
                ($j['estatisticas']['monstro'] ?? 0) + 1;

            $j['popularidade'] =
                max(
                    0,
                    ($j['popularidade'] ?? 50) - rand(3, 8)
                );

            aplicarConsequenciaSocial(
                $jogadores,
                $anjo,
                $nomeAlvo,
                'monstro',
                'monstro|' .
                ($_SESSION['rodada'] ?? 1) .
                '|' . $anjo .
                '|' . $nomeAlvo,
                "$anjo colocou $nomeAlvo no Monstro."
            );
        }
    }

    unset($j);

    garantirMeuJogadorNaLista($jogadores);

    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['monstro'] = $selecionados;
    $_SESSION['monstro_definido'] = true;
    $_SESSION['fase_semana'] = 'bigfone';

    $_SESSION['evento_extra'][] =
        "👹 $anjo colocou no Monstro: " .
        implode(" e ", $selecionados) .
        ".";

    if (function_exists('registrarHistoricoTemporada')) {
        registrarHistoricoTemporada(
            'monstro',
            'Castigo do Monstro',
            "$anjo escolheu " . implode(' e ', $selecionados) . ' para o Monstro.',
            array_merge([$anjo], $selecionados),
            '👹',
            null,
            'monstro'
        );
    }

    header("Location: jogo.php");
    exit;
}


/* =========================
   😇 DEFINIR IMUNIDADE DO ANJO
========================= */

if (isset($_POST['definir_imunidade_anjo'])) {

    $imunizado =
        $_POST['imunizado_anjo'] ?? '';

    foreach ($jogadores as &$j) {

        $j['status']['imune'] = false;

        if ($j['nome'] == $imunizado) {

            $j['status']['imune'] = true;

            if (!isset($j['estatisticas'])) {
                $j['estatisticas'] = [];
            }

            $j['estatisticas']['imune'] =
                ($j['estatisticas']['imune'] ?? 0) + 1;
        }
    }

    unset($j);

    garantirMeuJogadorNaLista($jogadores);

    $anjoAtual = $_SESSION['anjo'] ?? '';

    aplicarConsequenciaSocial(
        $jogadores,
        $anjoAtual,
        $imunizado,
        'imunidade',
        'imunidade_anjo|' .
        ($_SESSION['rodada'] ?? 1) .
        '|' . $anjoAtual .
        '|' . $imunizado,
        "$anjoAtual imunizou $imunizado."
    );

    if((int)($_SESSION['anjo_dupla_semana']??0)===(int)($_SESSION['rodada']??1) && !empty($_SESSION['anjos_dupla'][1])){
        $pnParceiro=(string)$_SESSION['anjos_dupla'][1];
        $pnPreferido=pnPreferenciaParceiro($jogadores,$pnParceiro,'anjo');
        if($pnPreferido!=='' && $pnPreferido!==$imunizado){
            alterarAfinidade($jogadores,$pnParceiro,$meuNome,-2,1,-2);
            $_SESSION['evento_extra'][]="🤝 $pnParceiro preferia salvar $pnPreferido, mas o Anjo titular optou por $imunizado.";
        } elseif($pnPreferido!==''){
            alterarAfinidade($jogadores,$pnParceiro,$meuNome,2,-1,2);
            $_SESSION['evento_extra'][]="🤝 $pnParceiro apoiou a imunidade de $imunizado.";
        }
    }
    $_SESSION['jogadores'] = $jogadores;
    $_SESSION['imune'] = $imunizado;
    $_SESSION['imunizacao_anjo_feita'] = true;

    $_SESSION['evento_extra'][] =
        "🛡️ O Anjo " .
        $_SESSION['anjo'] .
        " imunizou $imunizado antes da formação do paredão.";

    if (function_exists('registrarHistoricoTemporada')) {
        registrarHistoricoTemporada(
            'imunidade',
            'Imunidade do Anjo',
            ($_SESSION['anjo'] ?? 'O Anjo') . " imunizou $imunizado.",
            [$_SESSION['anjo'] ?? '', $imunizado],
            '🛡️',
            null,
            'imunidade_anjo'
        );
    }

    $_SESSION['fase_semana'] = 'paredao';

    header("Location: jogo.php");
    exit;
}
