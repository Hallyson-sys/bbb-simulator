<?php

/* =========================================================
   💕 LÓGICA DE ROMANCE / CRUSH / CASAL
   ========================================================= */


/* =========================================================
   💗 ALTERAR ROMANCE
   ========================================================= */

function alterarRomance(
    &$jogadores,
    $nomeA,
    $nomeB,
    $valor
) {

    foreach ($jogadores as &$j) {

        if (($j['nome'] ?? '') == $nomeA) {

            if (!isset($j['romances'][$nomeB])) {
                $j['romances'][$nomeB] = 0;
            }

            $j['romances'][$nomeB] =
                limitar(
                    $j['romances'][$nomeB] + $valor,
                    0,
                    100
                );
        }
    }

    unset($j);
}


/* =========================================================
   💍 GARANTIR SISTEMA DE CASAIS
   ========================================================= */

if (!isset($_SESSION['casais'])) {
    $_SESSION['casais'] = [];
}


/* =========================================================
   💕 OBTER ROMANCE ENTRE DUAS PESSOAS
   ========================================================= */

function obterRomance(
    $jogadores,
    $nomeA,
    $nomeB
) {

    foreach ($jogadores as $j) {

        if (($j['nome'] ?? '') == $nomeA) {

            return
                $j['romances'][$nomeB] ?? 0;
        }
    }

    return 0;
}


/* =========================================================
   💘 STATUS VISUAL DO ROMANCE
   ========================================================= */

function statusRomance(
    $romance,
    $nome,
    $meuNome
) {

    if (
        isset($_SESSION['casais'][$meuNome]) &&
        $_SESSION['casais'][$meuNome] == $nome
    ) {
        return "💍 Namorando";
    }


    if ($romance >= 60) {
        return "💘 Quase casal";
    }


    if ($romance >= 30) {
        return "💕 Crush";
    }


    if ($romance > 0) {
        return "💗 Interesse";
    }


    return "";
}


/* =========================================================
   💍 PARCEIRO ATUAL
   ========================================================= */

function parceiroAtual($nome)
{
    return
        $_SESSION['casais'][$nome] ?? '';
}


/* =========================================================
   💞 VERIFICAR SE ESTÃO NAMORANDO
   ========================================================= */

function estaNamorandoCom(
    $nomeA,
    $nomeB
) {

    return (
        isset($_SESSION['casais'][$nomeA]) &&
        $_SESSION['casais'][$nomeA] == $nomeB
    );
}


/* =========================================================
   💍 REGISTRAR CASAL
   ========================================================= */

function registrarCasal(
    $nomeA,
    $nomeB
) {

    $_SESSION['casais'][$nomeA] =
        $nomeB;

    $_SESSION['casais'][$nomeB] =
        $nomeA;
}


/* =========================================================
   💔 TERMINAR CASAL
   ========================================================= */

function terminarCasal(
    $nomeA,
    $nomeB
) {

    if (
        isset($_SESSION['casais'][$nomeA]) &&
        $_SESSION['casais'][$nomeA] == $nomeB
    ) {

        unset(
            $_SESSION['casais'][$nomeA]
        );
    }


    if (
        isset($_SESSION['casais'][$nomeB]) &&
        $_SESSION['casais'][$nomeB] == $nomeA
    ) {

        unset(
            $_SESSION['casais'][$nomeB]
        );
    }
}


/* =========================================================
   💌 CHANCE DE ACEITAR NAMORO
   ========================================================= */

function chanceAceitarNamoro(
    $afinidade,
    $romance
) {

    $chance = 25;


    if ($afinidade >= 20) {
        $chance += 20;
    }


    if ($afinidade >= 40) {
        $chance += 15;
    }


    if ($romance >= 60) {
        $chance += 25;
    }


    if ($romance >= 80) {
        $chance += 10;
    }


    return min(
        90,
        max(
            10,
            $chance
        )
    );
}


/* =========================================================
   😤 CIÚMES DE UM CASAL
   ========================================================= */

function aplicarCiumesSeTiverCasal(
    &$jogadores,
    $meuNome,
    $alvoFlerte,
    &$eventoExtra
) {

    $parceiro =
        parceiroAtual($meuNome);


    if (
        $parceiro != '' &&
        $parceiro != $alvoFlerte
    ) {

        ajustarRelacaoJogador(
            $parceiro,
            -8
        );


        alterarRomance(
            $jogadores,
            $meuNome,
            $parceiro,
            -8
        );


        alterarRomance(
            $jogadores,
            $parceiro,
            $meuNome,
            -8
        );


        alterarAfinidade(
            $jogadores,
            $parceiro,
            $meuNome,
            -8,
            8,
            -5
        );


        $eventoExtra[] =
            "💔 $parceiro viu o clima de flerte e ficou com ciúmes de $meuNome.";
    }
}


/* =========================================================
   🤖 PEDIDO DE NAMORO ENTRE NPCs
   ========================================================= */

function tentarPedidoNamoroNPC(
    &$jogadores,
    $nomeNPC,
    $nomeAlvo
) {

    if (
        $nomeNPC == '' ||
        $nomeAlvo == ''
    ) {
        return "";
    }


    /*
     * Não tenta formar outro casal
     * se uma das pessoas já estiver namorando.
     */
    if (
        parceiroAtual($nomeNPC) != '' ||
        parceiroAtual($nomeAlvo) != ''
    ) {
        return "";
    }


    $romance = min(

        obterRomance(
            $jogadores,
            $nomeNPC,
            $nomeAlvo
        ),

        obterRomance(
            $jogadores,
            $nomeAlvo,
            $nomeNPC
        )
    );


    /* Romance ainda insuficiente */

    if ($romance < 60) {
        return "";
    }


    /*
     * Mesmo com romance suficiente,
     * o NPC não pede namoro toda hora.
     */
    if (rand(1, 100) > 25) {
        return "";
    }


    $afinidade = 50;


    foreach ($jogadores as $j) {

        if (
            ($j['nome'] ?? '') == $nomeAlvo
        ) {

            $afinidade =
                $j['relacoes'][$nomeNPC]['amizade'] ?? 50;
        }
    }


    $chance =
        chanceAceitarNamoro(
            $afinidade,
            $romance
        );


    /* 💍 PEDIDO ACEITO */

    if (
        rand(1, 100) <= $chance
    ) {

        registrarCasal(
            $nomeNPC,
            $nomeAlvo
        );


        impactoTorcidaOculto(
            $jogadores,
            $nomeNPC,
            'casal'
        );


        impactoTorcidaOculto(
            $jogadores,
            $nomeAlvo,
            'casal'
        );


        alterarRomance(
            $jogadores,
            $nomeNPC,
            $nomeAlvo,
            8
        );


        alterarRomance(
            $jogadores,
            $nomeAlvo,
            $nomeNPC,
            8
        );


        return
            "💍 $nomeNPC pediu $nomeAlvo em namoro, e o pedido foi aceito!";
    }


    /* 💔 PEDIDO RECUSADO */

    alterarRomance(
        $jogadores,
        $nomeNPC,
        $nomeAlvo,
        -5
    );


    alterarRomance(
        $jogadores,
        $nomeAlvo,
        $nomeNPC,
        -5
    );


    return
        "💔 $nomeNPC tentou pedir $nomeAlvo em namoro, mas recebeu um 'vamos com calma'.";
}