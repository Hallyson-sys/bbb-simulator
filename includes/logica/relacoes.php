<?php

/* =========================================================
   ❤️ LÓGICA DE RELAÇÕES
   Amizade, rivalidade, confiança, aliados e rivais
   ========================================================= */


/* =========================================================
   ❤️ ALTERAR AFINIDADE
   ========================================================= */

   function alterarAfinidade(
    &$jogadores,
    $nomeA,
    $nomeB,
    $amizade = 0,
    $rivalidade = 0,
    $confianca = 0
) {

    foreach ($jogadores as &$j) {

        if (($j['nome'] ?? '') == $nomeA) {

            if (!isset($j['relacoes'][$nomeB])) {

                $j['relacoes'][$nomeB] = [
                    "amizade" => 0,
                    "rivalidade" => 0,
                    "confianca" => 0
                ];
            }

            $j['relacoes'][$nomeB]['amizade'] =
                ($j['relacoes'][$nomeB]['amizade'] ?? 0)
                + $amizade;

            $j['relacoes'][$nomeB]['rivalidade'] =
                ($j['relacoes'][$nomeB]['rivalidade'] ?? 0)
                + $rivalidade;

            $j['relacoes'][$nomeB]['confianca'] =
                ($j['relacoes'][$nomeB]['confianca'] ?? 0)
                + $confianca;
        }
    }

    unset($j);
}


/* =========================================================
   👤 RELAÇÃO DO JOGADOR COM OS PARTICIPANTES
   ========================================================= */

   function ajustarRelacaoJogador($nome, $valor)
   {
       if ($nome == '') {
           return;
       }
   
       if (!isset($_SESSION['relacoes_jogador'])) {
           $_SESSION['relacoes_jogador'] = [];
       }
   
       if (!isset($_SESSION['relacoes_jogador'][$nome])) {
           $_SESSION['relacoes_jogador'][$nome] = 0;
       }
   
       $_SESSION['relacoes_jogador'][$nome] += $valor;
   }


/* =========================================================
   🤖 CALCULAR RELAÇÃO PARA IA
   ========================================================= */

function calcularRelacaoIA(
    $jogadores,
    $de,
    $para,
    $meuNome
) {

    if ($de == '' || $para == '') {
        return 0;
    }


    /*
     * Quando a IA está avaliando o próprio jogador,
     * utiliza o valor que aparece nos cards.
     */
    if ($para == $meuNome) {

        return
            $_SESSION['relacoes_jogador'][$de] ?? 0;
    }


    foreach ($jogadores as $j) {

        if (($j['nome'] ?? '') == $de) {

            $rel =
                $j['relacoes'][$para] ?? [];


            $amizade =
                $rel['amizade'] ?? 0;

            $rivalidade =
                $rel['rivalidade'] ?? 0;

            $confianca =
                $rel['confianca'] ?? 0;


            return (int) round(
                $amizade +
                ($confianca * 0.5) -
                ($rivalidade * 1.2)
            );
        }
    }


    return 0;
}


/* =========================================================
   📊 OBTER RELAÇÃO COMPLETA
   ========================================================= */

function obterRelacaoCompleta(
    $jogadores,
    $de,
    $para,
    $meuNome
) {

    $rel = [
        "amizade" => 0,
        "rivalidade" => 0,
        "confianca" => 0,
        "score" => 0
    ];


    if ($de == '' || $para == '') {
        return $rel;
    }


    foreach ($jogadores as $j) {

        if (($j['nome'] ?? '') == $de) {

            $dados =
                $j['relacoes'][$para] ?? [];


            $rel['amizade'] =
                $dados['amizade'] ?? 0;

            $rel['rivalidade'] =
                $dados['rivalidade'] ?? 0;

            $rel['confianca'] =
                $dados['confianca'] ?? 0;


            break;
        }
    }


    /*
     * Quando a relação envolve o jogador,
     * considera também o placar exibido no card.
     */
    if ($para == $meuNome) {

        $rel['score'] =
            $_SESSION['relacoes_jogador'][$de] ?? 0;

    } else {

        $rel['score'] = (int) round(

            $rel['amizade'] +

            ($rel['confianca'] * 0.5) -

            ($rel['rivalidade'] * 1.2)
        );
    }


    return $rel;
}


/* =========================================================
   🤝 VERIFICAR ALIANÇA SOCIAL
   ========================================================= */

function saoAliados(
    $jogadores,
    $nomeA,
    $nomeB,
    $meuNome
) {

    $rel =
        obterRelacaoCompleta(
            $jogadores,
            $nomeA,
            $nomeB,
            $meuNome
        );


    return (
        $rel['score'] >= 35 ||
        (
            $rel['amizade'] >= 45 &&
            $rel['confianca'] >= 30
        )
    );
}


/* =========================================================
   😡 VERIFICAR RIVALIDADE
   ========================================================= */

function saoRivais(
    $jogadores,
    $nomeA,
    $nomeB,
    $meuNome
) {

    $rel =
        obterRelacaoCompleta(
            $jogadores,
            $nomeA,
            $nomeB,
            $meuNome
        );


    return (
        $rel['score'] <= -25 ||

        $rel['rivalidade'] >= 35 ||

        (
            $rel['amizade'] <= 10 &&
            $rel['rivalidade'] >= 20
        )
    );
}


/* =========================================================
   🤝 LISTAR ALIADOS DO NPC
   ========================================================= */

function listarAliadosNPC(
    $jogadores,
    $nomeNPC,
    $meuNome
) {

    $aliados = [];


    foreach ($jogadores as $j) {

        $nome =
            $j['nome'] ?? '';


        if (
            $nome == '' ||
            $nome == $nomeNPC
        ) {
            continue;
        }


        if (
            saoAliados(
                $jogadores,
                $nomeNPC,
                $nome,
                $meuNome
            )
        ) {

            $aliados[] = $nome;
        }
    }


    return $aliados;
}


/* =========================================================
   🔥 LISTAR RIVAIS DO NPC
   ========================================================= */

function listarRivaisNPC(
    $jogadores,
    $nomeNPC,
    $meuNome
) {

    $rivais = [];


    foreach ($jogadores as $j) {

        $nome =
            $j['nome'] ?? '';


        if (
            $nome == '' ||
            $nome == $nomeNPC
        ) {
            continue;
        }


        if (
            saoRivais(
                $jogadores,
                $nomeNPC,
                $nome,
                $meuNome
            )
        ) {

            $rivais[] = $nome;
        }
    }


    return $rivais;
}


/* =========================================================
   🔎 BUSCAR PARTICIPANTE PELO NOME
   ========================================================= */

function buscarJogadorPorNome(
    $jogadores,
    $nome
) {

    foreach ($jogadores as $j) {

        if (($j['nome'] ?? '') == $nome) {
            return $j;
        }
    }


    return null;
}


/* =========================================================
   🎯 ESCOLHER ALVO DO NPC PELA RELAÇÃO
   ========================================================= */

function escolherAlvoNPCPorRelacao(
    $jogadores,
    $nomeNPC,
    $meuNome,
    $tipo = 'qualquer'
) {

    $opcoes = [];


    foreach ($jogadores as $j) {

        $nome =
            $j['nome'] ?? '';


        if (
            $nome == '' ||
            $nome == $nomeNPC
        ) {
            continue;
        }


        $rel =
            obterRelacaoCompleta(
                $jogadores,
                $nomeNPC,
                $nome,
                $meuNome
            );


        /* Procurando aliado */

        if (
            $tipo == 'aliado' &&
            saoAliados(
                $jogadores,
                $nomeNPC,
                $nome,
                $meuNome
            )
        ) {

            $opcoes[$nome] =
                $rel['score'] + rand(1, 15);
        }


        /* Procurando rival */

        if (
            $tipo == 'rival' &&
            saoRivais(
                $jogadores,
                $nomeNPC,
                $nome,
                $meuNome
            )
        ) {

            $opcoes[$nome] =
                abs($rel['score']) +
                ($rel['rivalidade'] ?? 0) +
                rand(1, 15);
        }


        /* Qualquer participante */

        if ($tipo == 'qualquer') {

            $opcoes[$nome] =
                rand(1, 100);
        }
    }


    if (empty($opcoes)) {
        return null;
    }


    arsort($opcoes);


    return array_key_first($opcoes);
}


/* =========================================================
   ⭐ REGISTRAR RELAÇÃO MARCANTE
   ========================================================= */

function registrarRelacaoMarcante(
    &$jogadores,
    $nomeA,
    $nomeB
) {

    if (
        $nomeA == '' ||
        $nomeB == '' ||
        $nomeA == $nomeB
    ) {
        return;
    }


    foreach ($jogadores as &$j) {

        if (($j['nome'] ?? '') == $nomeA) {


            if (!isset($j['relacoes'][$nomeB])) {

                $j['relacoes'][$nomeB] = [

                    "amizade" => 0,

                    "rivalidade" => 0,

                    "confianca" => 0

                ];
            }


            $amizade =
                $j['relacoes'][$nomeB]['amizade'] ?? 0;

            $rivalidade =
                $j['relacoes'][$nomeB]['rivalidade'] ?? 0;

            $confianca =
                $j['relacoes'][$nomeB]['confianca'] ?? 0;


            if (!isset($j['marcadores'])) {
                $j['marcadores'] = [];
            }


            if (!isset($j['marcadores']['aliados'])) {
                $j['marcadores']['aliados'] = [];
            }


            if (!isset($j['marcadores']['rivais'])) {
                $j['marcadores']['rivais'] = [];
            }


            if (
                $amizade >= 50 &&
                $confianca >= 35 &&
                !in_array(
                    $nomeB,
                    $j['marcadores']['aliados']
                )
            ) {

                $j['marcadores']['aliados'][] =
                    $nomeB;
            }


            if (
                (
                    $rivalidade >= 45 ||
                    $amizade <= 5
                ) &&
                !in_array(
                    $nomeB,
                    $j['marcadores']['rivais']
                )
            ) {

                $j['marcadores']['rivais'][] =
                    $nomeB;
            }
        }
    }


    unset($j);
}


/* =========================================================
   🔄 ATUALIZAR RELAÇÕES MARCANTES
   ========================================================= */

function atualizarRelacoesMarcantes(
    &$jogadores
) {

    $nomes = [];


    foreach ($jogadores as $j) {

        if (($j['nome'] ?? '') != '') {

            $nomes[] =
                $j['nome'];
        }
    }


    foreach ($nomes as $nomeA) {

        foreach ($nomes as $nomeB) {

            if ($nomeA != $nomeB) {

                registrarRelacaoMarcante(
                    $jogadores,
                    $nomeA,
                    $nomeB
                );
            }
        }
    }
}