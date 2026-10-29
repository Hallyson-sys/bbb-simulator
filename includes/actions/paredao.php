<?php



/** @var string $fase */

/** @var array $jogadores */

/** @var string $meuNome */

require_once __DIR__ . '/../logica/consequencias_sociais.php';





/* =========================================================

   🎁/☎️ EFEITOS DE VOTAÇÃO SEPARADOS

   ========================================================= */



function origensAnulacaoVotoEspecial($votante)
{
    return votoEstaAnuladoPeloBigFone($votante) ? ['Big Fone'] : [];
}

function pesoVotoComPoderes($votante)
{
    $peso = pesoVotoBigFone($votante);
    return function_exists('dsPesoVoto') ? dsPesoVoto($peso, (string)$votante) : $peso;
}

function origensVotoDuploEspecial($votante)
{
    return pesoVotoBigFone($votante) > 1 ? ['Big Fone'] : [];
}

/* =========================

   👑 INDICAÇÃO DO LÍDER

========================= */



if (isset($_POST['indicar_lider']) || isset($_POST['ld_resolver_indicacao'])) {
    $liderDaVez = (string)($_SESSION['lider'] ?? '');
    $dupla = ldLideresAtuais($jogadores);
    $ehDupla = count($dupla) === 2;
    $podeDecidir = $ehDupla ? ldParticipa($jogadores, $meuNome) : nomeIgual($liderDaVez, $meuNome);
    if (($_SESSION['fase_semana'] ?? '') !== 'paredao' || isset($_SESSION['indicacao_lider']) || !$podeDecidir) {
        header('Location: jogo.php'); exit;
    }
    $resolvendo = isset($_POST['ld_resolver_indicacao']);
    $pendente = (array)($_SESSION['ld_indicacao_pendente'] ?? []);
    if ($pendente && (int)($pendente['semana'] ?? 0) !== (int)($_SESSION['rodada'] ?? 1)) {
        unset($_SESSION['ld_indicacao_pendente']); $pendente = [];
    }
    $parceiro = $ehDupla ? ldOutroLider($jogadores, $meuNome) : '';
    if ($resolvendo) {
        if (!$ehDupla || (int)($pendente['semana'] ?? 0) !== (int)($_SESSION['rodada'] ?? 1)) {
            header('Location: jogo.php'); exit;
        }
        $modo = (string)($_POST['ld_decisao_indicacao'] ?? '');
        if ($modo === 'concordar') {
            $indicadoLider = (string)($pendente['sugestao'] ?? '');
        } elseif ($modo === 'negociar') {
            $indicadoLider = ldCompromissoParedao($jogadores, $meuNome, $parceiro,
                (string)($pendente['proposta'] ?? ''), (string)($pendente['sugestao'] ?? ''));
        } else {
            header('Location: jogo.php'); exit;
        }
    } else {
        if ($pendente) { header('Location: jogo.php'); exit; }
        $indicadoLider = (string)($_POST['indicado_lider'] ?? '');
    }
    $elegiveis = $ehDupla ? ldElegiveisParedao($jogadores) : array_values(array_filter(
        array_column($jogadores, 'nome'), static fn($n) => $n !== $liderDaVez && !estaImune($jogadores, $n)
    ));
    if ($indicadoLider === '' || !in_array($indicadoLider, $elegiveis, true)) {
        $_SESSION['evento_extra'][] = '⚠️ A indicação é inválida ou o participante está imune.';
        header('Location: jogo.php'); exit;
    }
    if ($ehDupla && !$resolvendo) {
        $preferido = ldPreferenciaParedao($jogadores, $parceiro);
        if ($preferido !== '' && $indicadoLider !== $preferido) {
            $_SESSION['ld_indicacao_pendente'] = [
                'semana'=>(int)($_SESSION['rodada']??1), 'proposta'=>$indicadoLider,
                'sugestao'=>$preferido, 'parceiro'=>$parceiro
            ];
            header('Location: jogo.php'); exit;
        }
    }
    unset($_SESSION['ld_indicacao_pendente']);
    $_SESSION['indicacao_lider'] = $indicadoLider;
    aplicarConsequenciaSocial(
        $jogadores, $liderDaVez, $indicadoLider, 'indicacao_paredao',
        'indicacao_lider|' . ($_SESSION['rodada'] ?? 1) . '|' . $liderDaVez . '|' . $indicadoLider,
        "$liderDaVez indicou $indicadoLider ao paredão."
    );
    $_SESSION['jogadores'] = $jogadores;
    if ($ehDupla) {
        $_SESSION['evento_extra'][] = '🤝 '.implode(' e ', $dupla).' definiram em conjunto a indicação de '.$indicadoLider.' ao Paredão.';
        if ($resolvendo) {
            $_SESSION['evento_extra'][] = '🗣️ A dupla conversou sobre as opções antes de chegar a um nome.';
        }
    } else {
        $_SESSION['evento_extra'][] = "👑 O líder $liderDaVez indicou $indicadoLider ao paredão.";
    }
    header('Location: jogo.php'); exit;
}


/* =========================

   ☎️ INDICAÇÃO DO BIG FONE

========================= */



if (isset($_POST['indicar_bigfone'])) {



    $indicadoBigfone =

        $_POST['indicado_bigfone'] ?? '';



    if (

        $indicadoBigfone == ($_SESSION['lider'] ?? '') ||

        $indicadoBigfone == ($_SESSION['indicacao_lider'] ?? '') ||

        $indicadoBigfone == $meuNome ||

        estaImune($jogadores, $indicadoBigfone)

    ) {



        $_SESSION['evento_extra'][] =

            "⚠️ Indicação inválida do Big Fone.";



        header("Location: jogo.php");

        exit;

    }



    $_SESSION['indicacao_bigfone'] =

        $indicadoBigfone;



    $donoBigFone = $_SESSION['bigfone_dono_poder'] ?? '';

    aplicarConsequenciaSocial(
        $jogadores,
        $donoBigFone,
        $indicadoBigfone,
        'indicacao_bigfone',
        'indicacao_bigfone|' .
        ($_SESSION['rodada'] ?? 1) .
        '|' . $donoBigFone .
        '|' . $indicadoBigfone,
        "$donoBigFone indicou $indicadoBigfone pelo Big Fone."
    );



    unset(

        $_SESSION['bigfone_indicacao_pendente']

    );



    $_SESSION['evento_extra'][] =

        "☎️ Pelo poder do Big Fone, " .

        $_SESSION['bigfone_dono_poder'] .

        " indicou " .

        $_SESSION['indicacao_bigfone'] .

        " ao paredão.";



    header("Location: jogo.php");

    exit;

}





/* 👑 Desempate conjunto preserva a apuração sem recalcular os votos. */
if ((isset($_POST['pd_desempatar']) || isset($_POST['ld_resolver_desempate']))
    && !empty($_SESSION['pd_pendente']) && ($_SESSION['fase_semana'] ?? '') === 'paredao') {
    $pendente = (array)$_SESSION['pd_pendente'];
    $empatados = array_values((array)($pendente['empatados'] ?? []));
    $vagasEmpate = max(1, (int)($pendente['vagas_empate'] ?? 1));
    $dupla = ldLideresAtuais($jogadores);
    $podeDecidir = nomeIgual((string)($_SESSION['lider'] ?? ''), $meuNome) || ldParticipa($jogadores, $meuNome);
    $resolvendo = isset($_POST['ld_resolver_desempate']);
    if ($resolvendo) {
        $negociacao = (array)($_SESSION['ld_pd_pendente'] ?? []);
        if (count($dupla) !== 2 || (int)($negociacao['semana'] ?? 0) !== (int)($_SESSION['rodada'] ?? 1)) {
            header('Location: jogo.php'); exit;
        }
        $modo = (string)($_POST['ld_decisao_desempate'] ?? '');
        $escolhidos = $modo === 'concordar' ? (array)($negociacao['preferencia'] ?? [])
            : ($modo === 'negociar' ? ldAcordoVIP((array)($negociacao['proposta'] ?? []), (array)($negociacao['preferencia'] ?? []), $vagasEmpate) : []);
    } else {
        $escolhidos = $_POST['pd_candidatos'] ?? [];
        if (!is_array($escolhidos)) $escolhidos = [];
        if (!$escolhidos && !empty($_POST['pd_candidato'])) $escolhidos = [(string)$_POST['pd_candidato']];
    }
    $escolhidos = array_values(array_unique(array_map('strval', $escolhidos)));
    $validos = count($escolhidos) === $vagasEmpate && !array_diff($escolhidos, $empatados);
    if ((int)($pendente['rodada'] ?? 0) === (int)($_SESSION['rodada'] ?? 1)
        && $podeDecidir && $validos) {
        if (count($dupla) === 2 && !$resolvendo) {
            $parceiro = ldOutroLider($jogadores, $meuNome);
            $pref = ldPreferenciaDesempate($jogadores, $parceiro, $empatados, $vagasEmpate);
            if (array_diff($pref, $escolhidos)) {
                $_SESSION['ld_pd_pendente'] = [
                    'semana'=>(int)($_SESSION['rodada']??1), 'parceiro'=>$parceiro,
                    'proposta'=>$escolhidos, 'preferencia'=>$pref
                ];
                header('Location: jogo.php'); exit;
            }
        }
        unset($_SESSION['ld_pd_pendente']);
        $_SESSION['pd_escolhidos'] = $escolhidos;
        $_SESSION['pd_escolhido'] = $escolhidos[0];
    } else {
        $_SESSION['evento_extra'][] = '⚠️ Selecione exatamente '.$vagasEmpate.' participante(s) entre os empatados.';
    }
    header('Location: jogo.php'); exit;
}

/* =========================

   🗳️ VOTO DO JOGADOR

========================= */



if (isset($_POST['votar_paredao'])) {



    $votoParedao =

        $_POST['voto_paredao'] ?? '';



    if (

        $votoParedao == ($_SESSION['indicacao_lider'] ?? '') ||

        $votoParedao == ($_SESSION['indicacao_bigfone'] ?? '') ||

        $votoParedao == ($_SESSION['lider'] ?? '') ||

        estaImune($jogadores, $votoParedao)

    ) {



        $_SESSION['evento_extra'][] =

            "⚠️ Voto inválido. Escolha outro participante.";



        header("Location: jogo.php");

        exit;

    }



    $_SESSION['meu_voto_paredao'] =

        $votoParedao;



    /* Voto secreto: não gera consequência social até ser revelado. */



    $_SESSION['evento_extra'][] =

        "🗳️ $meuNome votou no confessionário.";



    header("Location: jogo.php");

    exit;

}





/* =========================

   🚨 PROCESSAR PAREDÃO

========================= */



if (

    $fase == 'paredao' &&

    !isset($_SESSION['paredao_formado'])

) {



    $lider =

        $_SESSION['lider'] ?? '';





    sincronizarImunidadesGlobais(

        $jogadores

    );



    $_SESSION['jogadores'] =

        $jogadores;





    /* =========================

       🏆 TOP 4

    ========================= */



    if (

        count($jogadores) == 4 &&

        $lider != ''

    ) {



        $paredaoTop4 = [];



        foreach ($jogadores as $j) {



            $nomeTop4 =

                $j['nome'] ?? '';



            if (

                $nomeTop4 != $lider &&

                !estaImune(

                    $jogadores,

                    $nomeTop4

                )

            ) {



                $paredaoTop4[] =

                    $nomeTop4;

            }

        }



        $_SESSION['paredao'] =

            array_values(

                $paredaoTop4

            );



        blindarParedaoContraImunes(

            $jogadores

        );



        $_SESSION['paredao_formado'] =

            true;





        unset(

            $_SESSION['indicacao_lider']

        );



        unset(

            $_SESSION['indicacao_bigfone']

        );



        unset(

            $_SESSION['meu_voto_paredao']

        );



        unset(

            $_SESSION['votos_paredao']

        );



        unset(

            $_SESSION['dedo_duro']

        );





        $_SESSION['evento_extra'][] =

            "🏆 Reta final! O líder $lider está salvo do paredão.";



        $_SESSION['evento_extra'][] =

            "🚨 Está formado o paredão final: " .

            implode(

                " x ",

                $_SESSION['paredao']

            ) .

            ".";





        $_SESSION['fase_semana'] =

            'eliminacao';





        header("Location: jogo.php");

        exit;

    }





    /* =========================

       1️⃣ INDICAÇÃO DO LÍDER

    ========================= */



    if (

        !isset(

            $_SESSION['indicacao_lider']

        )

    ) {



        if ($lider == $meuNome || ldParticipa($jogadores, $meuNome)) {



            /*

             * Jogador é o líder.

             * Espera a escolha feita pelo HTML.

             */



            goto fim_paredao;

        }





        /* Líder NPC */



        $lideresNPC = ldLideresAtuais($jogadores);
        $alvo = count($lideresNPC) === 2
            ? ldCompromissoParedao($jogadores, $lideresNPC[0], $lideresNPC[1], '', '')
            : escolherAlvoInteligente($jogadores, $lider, [], 'indicacao_lider');



        if ($alvo) {



            $_SESSION['indicacao_lider'] =

                $alvo;



            aplicarConsequenciaSocial(
                $jogadores,
                $lider,
                $alvo,
                'indicacao_paredao',
                'indicacao_lider|' .
                ($_SESSION['rodada'] ?? 1) .
                '|' . $lider .
                '|' . $alvo,
                "$lider indicou $alvo ao paredão."
            );



            $_SESSION['evento_extra'][] =

                "👑 O líder $lider indicou $alvo ao paredão.";



        } else {



            goto fim_paredao;

        }

    }





    /* =========================

       2️⃣ BIG FONE

    ========================= */



    if (

        isset(

            $_SESSION[

                'bigfone_indicacao_pendente'

            ]

        ) &&

        isset(

            $_SESSION[

                'bigfone_dono_poder'

            ]

        ) &&

        !isset(

            $_SESSION[

                'indicacao_bigfone'

            ]

        )

    ) {



        $dono =

            $_SESSION[

                'bigfone_dono_poder'

            ];





        if ($dono == $meuNome) {



            /*

             * Poder pertence ao jogador.

             * Espera a escolha no HTML.

             */



            goto fim_paredao;

        }





        $bloqueados = [];



        $bloqueados[] =

            $_SESSION[

                'indicacao_lider'

            ];



        $bloqueados[] =

            $lider;





        $alvo =

            escolherAlvoInteligente(

                $jogadores,

                $dono,

                $bloqueados,

                'indicacao_bigfone'

            );





        if ($alvo) {



            $_SESSION[

                'indicacao_bigfone'

            ] = $alvo;



            aplicarConsequenciaSocial(
                $jogadores,
                $dono,
                $alvo,
                'indicacao_bigfone',
                'indicacao_bigfone|' .
                ($_SESSION['rodada'] ?? 1) .
                '|' . $dono .
                '|' . $alvo,
                "$dono indicou $alvo pelo Big Fone."
            );



            unset(

                $_SESSION[

                    'bigfone_indicacao_pendente'

                ]

            );





            $_SESSION['evento_extra'][] =

                "☎️ Pelo poder do Big Fone, $dono indicou $alvo ao paredão.";



        } else {



            unset(

                $_SESSION[

                    'bigfone_indicacao_pendente'

                ]

            );

        }

    }





    /* =========================

       3️⃣ VOTO DO JOGADOR

    ========================= */



    if (

        $lider != $meuNome &&

        !isset(

            $_SESSION[

                'meu_voto_paredao'

            ]

        )

    ) {



        /*

         * Jogador não é líder.

         * Espera ele votar.

         */



        goto fim_paredao;

    }





    /* =========================

       🗳️ VOTAÇÃO DA CASA

    ========================= */



    // Não recalcular votos secretos enquanto o Líder desempata.
    $pdCache=$_SESSION['pd_pendente']??null;
    $pdRetomando=is_array($pdCache) && (int)($pdCache['rodada']??0)===(int)($_SESSION['rodada']??1);
    if($pdRetomando && empty($_SESSION['pd_escolhidos']) && empty($_SESSION['pd_escolhido'])) goto fim_paredao;
    $votos=$pdRetomando ? $pdCache['votos'] : [];
    $votosDetalhados=$pdRetomando ? $pdCache['detalhes'] : [];
    if(!$pdRetomando) {





    foreach ($jogadores as $j) {



        $votante =

            $j['nome'] ?? '';



        if (empty($votante)) {

            continue;

        }



        if (

            !empty(

                $j['status']['lider']

            )

        ) {

            continue;

        }





        $bloqueados = [

            $votante

        ];





        if (

            isset(

                $_SESSION[

                    'indicacao_lider'

                ]

            )

        ) {



            $bloqueados[] =

                $_SESSION[

                    'indicacao_lider'

                ];

        }





        if (

            isset(

                $_SESSION[

                    'indicacao_bigfone'

                ]

            )

        ) {



            $bloqueados[] =

                $_SESSION[

                    'indicacao_bigfone'

                ];

        }





        /* =========================

           👤 VOTO DO JOGADOR

        ========================= */



        if (

            $votante == $meuNome &&

            isset(

                $_SESSION[

                    'meu_voto_paredao'

                ]

            )

        ) {



            $voto =

                $_SESSION[

                    'meu_voto_paredao'

                ];





            if (

                $voto ==

                    ($_SESSION['indicacao_lider'] ?? '') ||

                $voto ==

                    ($_SESSION['indicacao_bigfone'] ?? '') ||

                $voto == $lider ||

                estaImune(

                    $jogadores,

                    $voto

                )

            ) {



                continue;

            }





            $origensAnulacao =

                origensAnulacaoVotoEspecial(

                    $votante

                );



            if (!empty($origensAnulacao)) {



                $_SESSION['evento_extra'][] =

                    "🚫 " .

                    implode(

                        " + ",

                        $origensAnulacao

                    ) .

                    ": o voto de $votante foi anulado.";



                continue;

            }





            if (

                !isset(

                    $votos[$voto]

                )

            ) {



                $votos[$voto] = 0;

            }





            $pesoVoto =

                pesoVotoComPoderes(

                    $votante

                );





            $votos[$voto] +=

                $pesoVoto;





            $votosDetalhados[] = [



                "votante" =>

                    $votante,



                "voto" =>

                    $voto,



                "peso" =>

                    $pesoVoto



            ];





            if ($pesoVoto > 1) {



                $origensPeso =

                    origensVotoDuploEspecial(

                        $votante

                    );



                if (empty($origensPeso)) $origensPeso = ['Dinâmica da Produção'];

                $_SESSION['evento_extra'][] =

                    "🗳️ " .

                    implode(

                        " + ",

                        $origensPeso

                    ) .

                    ": o voto de $votante valeu por $pesoVoto.";

            }





            continue;

        }





        /* =========================

           🤖 VOTO DOS NPCs

        ========================= */



        $voto =

            escolherAlvoInteligente(

                $jogadores,

                $votante,

                $bloqueados,

                'voto'

            );





        if ($voto) {



            /* Voto secreto do NPC: só pesa socialmente se for revelado. */





            $origensAnulacao =

                origensAnulacaoVotoEspecial(

                    $votante

                );



            if (!empty($origensAnulacao)) {



                $_SESSION['evento_extra'][] =

                    "🚫 " .

                    implode(

                        " + ",

                        $origensAnulacao

                    ) .

                    ": o voto de $votante foi anulado.";



                continue;

            }





            if (

                !isset(

                    $votos[$voto]

                )

            ) {



                $votos[$voto] = 0;

            }





            $pesoVoto =

                pesoVotoComPoderes(

                    $votante

                );





            $votos[$voto] +=

                $pesoVoto;





            $votosDetalhados[] = [



                "votante" =>

                    $votante,



                "voto" =>

                    $voto,



                "peso" =>

                    $pesoVoto



            ];





            if ($pesoVoto > 1) {



                $origensPeso =

                    origensVotoDuploEspecial(

                        $votante

                    );



                if (empty($origensPeso)) $origensPeso = ['Dinâmica da Produção'];

                $_SESSION['evento_extra'][] =

                    "🗳️ " .

                    implode(

                        " + ",

                        $origensPeso

                    ) .

                    ": o voto de $votante valeu por $pesoVoto.";

            }

        }

    }





    } // fim da apuração: retomar preserva exatamente os votos originais

    /* =========================

       📊 RESULTADO DOS VOTOS

    ========================= */



    arsort(

        $votos

    );





    // Apuração persistida antes do eventual desempate do Líder.
    // Em Votação Aberta os votos já são conhecidos pela casa neste momento.
    if(function_exists('dsRegistrarVotosPublicos')) dsRegistrarVotosPublicos($jogadores,$votosDetalhados);
    if(function_exists('dsRegistrarApuracao')) dsRegistrarApuracao($votosDetalhados,$votos);
    $_SESSION['jogadores']=$jogadores;

    // As indicações fixas não são desfeitas pelo desempate da casa.
    // O Líder decide exatamente as vagas disputadas, inclusive no Paredão Especial.
    $limiteParaDesempate = (int)($_SESSION['pd_limite_paredao'] ?? 0);
    // Um limite antigo não pode contaminar uma nova semana.
    // Se a apuração estava aguardando desempate, manter o limite já definido.
    $semanaLimite = (int)($_SESSION['pd_limite_paredao_rodada'] ?? 0);
    if ($limiteParaDesempate < 1 || (!$pdRetomando && $semanaLimite !== (int)($_SESSION['rodada'] ?? 1))) {
        $limiteParaDesempate = definirTamanhoParedao($jogadores);
        $_SESSION['pd_limite_paredao'] = $limiteParaDesempate;
    }
    $_SESSION['pd_limite_paredao_rodada'] = (int)($_SESSION['rodada'] ?? 1);
    $fixos = [];
    foreach (['indicacao_lider', 'indicacao_bigfone'] as $chave) {
        $nomeFixo = (string)($_SESSION[$chave] ?? '');
        if ($nomeFixo !== '' && !in_array($nomeFixo, $fixos, true)
            && !estaImune($jogadores, $nomeFixo)) $fixos[] = $nomeFixo;
    }
    $votaveis = [];
    foreach ($votos as $nome => $peso) {
        if (!in_array($nome, $fixos, true) && !estaImune($jogadores, $nome)) {
            $votaveis[$nome] = (int)$peso;
        }
    }
    arsort($votaveis, SORT_NUMERIC);
    $vagasCasa = max(0, $limiteParaDesempate - count($fixos));
    $vagasCasa = min($vagasCasa, count($votaveis));
    $selecionadosCasa = array_slice(array_keys($votaveis), 0, $vagasCasa);
    $corte = $selecionadosCasa ? (int)$votaveis[end($selecionadosCasa)] : -1;
    $acima = []; $empatados = [];
    foreach ($votaveis as $nome => $peso) {
        if ($peso > $corte) $acima[] = $nome;
        elseif ($peso === $corte) $empatados[] = $nome;
    }
    $vagasEmpate = $vagasCasa > 0 ? $vagasCasa - count($acima) : 0;
    $haEmpate = $vagasEmpate > 0 && count($empatados) > $vagasEmpate;

    if ($haEmpate) {
        if (!$pdRetomando && (nomeIgual((string)$lider, (string)$meuNome) || ldParticipa($jogadores, $meuNome))) {
            $_SESSION['pd_pendente'] = [
                'rodada' => (int)($_SESSION['rodada'] ?? 1),
                'empatados' => $empatados, 'vagas_empate' => $vagasEmpate,
                'vagas_casa' => $vagasCasa, 'indicados_fixos' => $fixos,
                'votos' => $votos, 'detalhes' => $votosDetalhados
            ];
            $_SESSION['evento_extra'][] = '👑 A casa empatou na última faixa de votos. O Líder deve escolher '.
                $vagasEmpate.' participante(s) para preencher a(s) vaga(s) empatada(s).';
            header('Location: jogo.php'); exit;
        }
        if (nomeIgual((string)$lider, (string)$meuNome) || ldParticipa($jogadores, $meuNome)) {
            $escolhas = (array)($_SESSION['pd_escolhidos'] ?? []);
            if (!$escolhas && !empty($_SESSION['pd_escolhido'])) $escolhas = [(string)$_SESSION['pd_escolhido']];
        } else {
            $escolhas = [];
            $restantes = $empatados;
            // IA decide cada uma das vagas; não usa ordem aleatória de empate.
            for ($i = 0; $i < $vagasEmpate && $restantes; $i++) {
                $bloqueados = array_values(array_diff(array_map(
                    static fn($j) => (string)($j['nome'] ?? ''), $jogadores), $restantes));
                $escolha = function_exists('escolherAlvoInteligente')
                    ? escolherAlvoInteligente($jogadores, $lider, $bloqueados, 'voto') : '';
                if (!in_array($escolha, $restantes, true)) $escolha = $restantes[0];
                $escolhas[] = $escolha;
                $restantes = array_values(array_diff($restantes, [$escolha]));
            }
        }
        $escolhas = array_values(array_unique(array_filter(array_map('strval', $escolhas))));
        if (count($escolhas) !== $vagasEmpate || array_diff($escolhas, $empatados)) {
            if ($pdRetomando) {
                $_SESSION['evento_extra'][] = '⚠️ Selecione os participantes empatados para continuar.';
                goto fim_paredao;
            }
            $escolhas = array_slice($empatados, 0, $vagasEmpate);
        }
        $selecionadosCasa = array_merge($acima, $escolhas);
        $_SESSION['evento_extra'][] = '👑 Desempate do Líder: '.$lider.' escolheu '.
            implode(' e ', $escolhas).' para '.($vagasEmpate === 1 ? 'a vaga empatada' : 'as vagas empatadas').
            ' do Paredão. As outras indicações da semana permanecem válidas.';
    }
    // A escolha do Líder decide somente as vagas empatadas, sem recalcular os votos.
    $_SESSION['pd_votados_selecionados'] = $selecionadosCasa;
    unset($_SESSION['pd_pendente'], $_SESSION['pd_escolhido'], $_SESSION['pd_escolhidos']);

    if (function_exists('a3RegistrarVotosPrivados')) a3RegistrarVotosPrivados($jogadores, $votosDetalhados);

    /* Negociações da festa são verificadas em privado; sem spoilers no Ao Vivo. */
    if(function_exists('es2RegistrarResultadoAcordos'))es2RegistrarResultadoAcordos($votosDetalhados);

    $_SESSION['votos_paredao'] =

        $votos;





    if (

        !empty(

            $votosDetalhados

        )

    ) {



        $_SESSION['dedo_duro'] =

            $votosDetalhados[

                array_rand(

                    $votosDetalhados

                )

            ];



        $dd = $_SESSION['dedo_duro'];

        aplicarConsequenciaSocial(
            $jogadores,
            $dd['votante'] ?? '',
            $dd['voto'] ?? '',
            'voto_revelado',
            'voto_revelado|' .
            ($_SESSION['rodada'] ?? 1) .
            '|' . ($dd['votante'] ?? '') .
            '|' . ($dd['voto'] ?? ''),
            ($dd['votante'] ?? '') .
            ' teve o voto revelado em ' .
            ($dd['voto'] ?? '') . '.'
        );

    }



    aplicarEspiaoBigFoneNoResultado(

        $votosDetalhados

    );





    // Exatamente os mais votados para as vagas da casa, após eventual decisão do Líder.
    $maisVotados = array_values((array)($_SESSION['pd_votados_selecionados'] ?? []));
    unset($_SESSION['pd_votados_selecionados']);





    $paredao = [];





    if (

        isset(

            $_SESSION[

                'indicacao_lider'

            ]

        )

    ) {



        adicionarAoParedaoSeValido(

            $paredao,

            $jogadores,

            $_SESSION[

                'indicacao_lider'

            ],

            "indicação do líder"

        );

    }





    if (

        isset(

            $_SESSION[

                'indicacao_bigfone'

            ]

        )

    ) {



        adicionarAoParedaoSeValido(

            $paredao,

            $jogadores,

            $_SESSION[

                'indicacao_bigfone'

            ],

            "poder do Big Fone"

        );

    }





    foreach (

        $maisVotados as $nome

    ) {



        adicionarAoParedaoSeValido(

            $paredao,

            $jogadores,

            $nome,

            "votação da casa"

        );

    }





    $limiteParedao=(int)($_SESSION['pd_limite_paredao']??definirTamanhoParedao($jogadores));





    $_SESSION['paredao'] =

        array_slice(

            $paredao,

            0,

            $limiteParedao

        );





    blindarParedaoContraImunes(

        $jogadores

    );





    /* =====================================================

       ☎️ PODERES DO BIG FONE — NPC

       ===================================================== */



    $eventoContraBigFoneNPC =

        aplicarContraGolpeBigFoneNPC(

            $jogadores,

            $_SESSION['paredao']

        );



    if (

        $eventoContraBigFoneNPC != ""

    ) {



        $_SESSION['evento_extra'][] =

            $eventoContraBigFoneNPC;

    }





    $eventoTrocaBigFoneNPC =

        aplicarTrocaEmparedadoBigFoneNPC(

            $jogadores,

            $_SESSION['paredao']

        );



    if (

        $eventoTrocaBigFoneNPC != ""

    ) {



        $_SESSION['evento_extra'][] =

            $eventoTrocaBigFoneNPC;

    }











    $_SESSION['paredao'] =

        array_values(

            array_unique(

                $_SESSION['paredao']

            )

        );





    blindarParedaoContraImunes(

        $jogadores

    );





    if(function_exists('dsRegistrarParedao')) dsRegistrarParedao($_SESSION['paredao'] ?? []);

    $_SESSION['paredao_formado'] =

        true;





    /* =========================

       📺 AO VIVO

    ========================= */



    $_SESSION['evento_extra'][] =

        "🗳️ Resultado da Votação da Casa:";





    foreach (

        $votos as $nome => $qtd

    ) {



        $_SESSION['evento_extra'][] =

            "📊 $nome recebeu $qtd voto(s).";

    }





    if (

        isset(

            $_SESSION['dedo_duro']

        )

    ) {



        $_SESSION['evento_extra'][] =

            "🕵️ Dedo-duro: " .

            $_SESSION['dedo_duro']['votante'] .

            " votou em " .

            $_SESSION['dedo_duro']['voto'] .

            ".";

    }





    $_SESSION['evento_extra'][] =

        "🚨 Está formado o paredão: " .

        implode(

            " x ",

            $_SESSION['paredao']

        ) .

        ".";





    definirFaseDepoisDaFormacaoDoParedao(

        $jogadores

    );





    header("Location: jogo.php");

    exit;

}





/* =========================

   ⏸️ AGUARDAR ESCOLHA DO JOGADOR

========================= */



fim_paredao: