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

    $origens = [];



    if (

        votoEstaAnuladoPeloCuringa(

            $votante

        )

    ) {

        $origens[] = 'Poder Curinga';

    }



    if (

        votoEstaAnuladoPeloBigFone(

            $votante

        )

    ) {

        $origens[] = 'Big Fone';

    }



    return $origens;

}





function pesoVotoComPoderes($votante)

{

    /*

     * Caso a mesma pessoa receba Voto Duplo pelas duas fontes,

     * o efeito não acumula para quatro votos.

     * O voto continua valendo por dois.

     */

    $peso = max(

        pesoVotoCuringa(

            $votante

        ),

        pesoVotoBigFone(

            $votante

        )

    );
    return function_exists('dsPesoVoto') ? dsPesoVoto($peso, (string)$votante) : $peso;
}



function origensVotoDuploEspecial($votante)

{

    $origens = [];



    if (

        pesoVotoCuringa(

            $votante

        ) > 1

    ) {

        $origens[] = 'Poder Curinga';

    }



    if (

        pesoVotoBigFone(

            $votante

        ) > 1

    ) {

        $origens[] = 'Big Fone';

    }



    return $origens;

}





/* =========================

   👑 INDICAÇÃO DO LÍDER

========================= */



if (isset($_POST['indicar_lider'])) {



    $indicadoLider = $_POST['indicado_lider'] ?? '';



    if (estaImune($jogadores, $indicadoLider)) {



        $_SESSION['evento_extra'][] =

            "🛡️ Indicação inválida: $indicadoLider está imune e não pode ir ao paredão.";



        header("Location: jogo.php");

        exit;

    }



    $_SESSION['indicacao_lider'] = $indicadoLider;



    $liderDaVez = $_SESSION['lider'] ?? '';

    aplicarConsequenciaSocial(
        $jogadores,
        $liderDaVez,
        $indicadoLider,
        'indicacao_paredao',
        'indicacao_lider|' .
        ($_SESSION['rodada'] ?? 1) .
        '|' . $liderDaVez .
        '|' . $indicadoLider,
        "$liderDaVez indicou $indicadoLider ao paredão."
    );



    $_SESSION['evento_extra'][] =

        "👑 O líder " .

        $_SESSION['lider'] .

        " indicou " .

        $_SESSION['indicacao_lider'] .

        " ao paredão.";



    header("Location: jogo.php");

    exit;

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





/* 👑 Desempate público da votação da casa: decisão do Líder, nunca sorteio. */
if (isset($_POST['pd_desempatar']) && !empty($_SESSION['pd_pendente'])
    && ($_SESSION['fase_semana'] ?? '') === 'paredao') {
    $pendente=$_SESSION['pd_pendente'];
    $escolha=(string)($_POST['pd_candidato']??'');
    if ((int)($pendente['rodada']??0)===(int)($_SESSION['rodada']??1)
        && nomeIgual((string)($_SESSION['lider']??''),(string)$meuNome)
        && in_array($escolha,$pendente['empatados']??[],true)) {
        $_SESSION['pd_escolhido']=$escolha;
    } else {
        $_SESSION['evento_extra'][]='⚠️ Escolha inválida. Selecione um dos participantes empatados.';
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



    reaplicarImunidadeCuringa(

        $jogadores

    );



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



        if ($lider == $meuNome) {



            /*

             * Jogador é o líder.

             * Espera a escolha feita pelo HTML.

             */



            goto fim_paredao;

        }





        /* Líder NPC */



        $alvo =

            escolherAlvoInteligente(

                $jogadores,

                $lider,

                [],

                'indicacao_lider'

            );



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
    if($pdRetomando && empty($_SESSION['pd_escolhido'])) goto fim_paredao;
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





    // Tamanho do paredão é sorteado uma só vez, não muda com reload ou desempate.
    $limiteParaDesempate=(int)($_SESSION['pd_limite_paredao']??0);
    if($limiteParaDesempate<1){
        $limiteParaDesempate=definirTamanhoParedao($jogadores);
        $_SESSION['pd_limite_paredao']=$limiteParaDesempate;
    }
    $fixos=[];
    foreach(['indicacao_lider','indicacao_bigfone'] as $chave){
        $nomeFixo=(string)($_SESSION[$chave]??'');
        if($nomeFixo!=='' && !in_array($nomeFixo,$fixos,true) && !estaImune($jogadores,$nomeFixo)) $fixos[]=$nomeFixo;
    }
    $votaveis=[];
    foreach($votos as $nome=>$peso){
        if(!in_array($nome,$fixos,true) && !estaImune($jogadores,$nome)) $votaveis[$nome]=$peso;
    }
    $vagas=max(0,$limiteParaDesempate-count($fixos));
    $selecionados=array_slice(array_keys($votaveis),0,$vagas);
    $corte=$selecionados ? (int)($votaveis[end($selecionados)]??-1) : -1;
    $empatados=[];
    if($vagas>0 && $corte>=0){
        foreach($votaveis as $nome=>$peso){if((int)$peso===$corte) $empatados[]=$nome;}
    }
    $nAcima=count(array_filter($votaveis,fn($v)=>(int)$v>$corte));
    $haEmpate=count($empatados)>max(0,$vagas-$nAcima);
    if($haEmpate && !$pdRetomando){
        if(nomeIgual((string)$lider,(string)$meuNome)) {
            $_SESSION['pd_pendente']=['rodada'=>(int)($_SESSION['rodada']??1),'empatados'=>$empatados,
                'votos'=>$votos,'detalhes'=>$votosDetalhados];
            $_SESSION['evento_extra'][]='👑 A votação da casa empatou. O Líder precisa decidir quem ocupará a vaga disputada no Paredão.';
            header('Location: jogo.php');exit;
        }
        $bloqueados=array_values(array_diff(array_map(fn($j)=>(string)($j['nome']??''),$jogadores),$empatados));
        $escolha=escolherAlvoInteligente($jogadores,$lider,$bloqueados,'voto');
        if(!in_array($escolha,$empatados,true))$escolha=$empatados[0];
        $_SESSION['pd_escolhido']=$escolha;
    }
    if($haEmpate && !empty($_SESSION['pd_escolhido'])) {
        $escolha=(string)$_SESSION['pd_escolhido'];
        if(in_array($escolha,$empatados,true)){
            // Ordenar primeiro o voto escolhido pelo Líder dentro do grupo empatado.
            $ordem=array_keys($votos);
            $ordem=array_values(array_filter($ordem,fn($n)=>$n!==$escolha));
            $pos=0;
            while($pos<count($ordem) && (int)($votos[$ordem[$pos]]??0)>$corte)$pos++;
            array_splice($ordem,$pos,0,[$escolha]);
            $ordenados=[];foreach($ordem as $n)$ordenados[$n]=$votos[$n];$votos=$ordenados;
            $_SESSION['evento_extra'][]="👑 Desempate do Líder: $lider escolheu $escolha entre os participantes empatados.";
        }
    }
    unset($_SESSION['pd_pendente'],$_SESSION['pd_escolhido']);

    if (function_exists('dsRegistrarVotosPublicos')) dsRegistrarVotosPublicos($jogadores, $votosDetalhados);
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





    aplicarEspiaoCuringaNoResultado(

        $votosDetalhados

    );



    aplicarEspiaoBigFoneNoResultado(

        $votosDetalhados

    );





    $maisVotados =

        array_keys(

            $votos

        );





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





    /* =====================================================

       🎁 PODER CURINGA — NPC

       ===================================================== */



    $eventoContraGolpeNPC =

        aplicarContraGolpeCuringaNPC(

            $jogadores,

            $_SESSION['paredao']

        );



    if (

        $eventoContraGolpeNPC != ""

    ) {



        $_SESSION['evento_extra'][] =

            $eventoContraGolpeNPC;

    }





    $eventoTrocaNPC =

        aplicarTrocaEmparedadoCuringaNPC(

            $jogadores,

            $_SESSION['paredao']

        );



    if (

        $eventoTrocaNPC != ""

    ) {



        $_SESSION['evento_extra'][] =

            $eventoTrocaNPC;

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