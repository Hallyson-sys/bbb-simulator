<?php

/* =========================================================
   🧰 UTILITÁRIOS GERAIS
   Funções reutilizadas por vários sistemas do jogo
   ========================================================= */


/* =========================================================
   🔢 LIMITAR VALOR
   ========================================================= */

/*
 * Mantém um valor dentro de um intervalo.
 *
 * Exemplos:
 * limitar(120, 0, 100) → 100
 * limitar(-10, 0, 100) → 0
 * limitar(65, 0, 100)  → 65
 *
 * IMPORTANTE:
 * Não estamos mais usando esta função para limitar
 * a afinidade/relação, porque decidimos que a afinidade
 * pode ultrapassar 100.
 */
function limitar($valor, $min = 0, $max = 100)
{
    return max(
        $min,
        min(
            $max,
            $valor
        )
    );
}


/* =========================================================
   👤 COMPARAR NOMES COM SEGURANÇA
   ========================================================= */

/*
 * Ignora:
 * - diferenças entre maiúsculas/minúsculas;
 * - espaços extras antes/depois.
 *
 * Exemplo:
 * nomeIgual("Hally", " hally ") → true
 */
function nomeIgual($a, $b)
{
    return
        mb_strtolower(
            trim((string)$a),
            'UTF-8'
        )
        ===
        mb_strtolower(
            trim((string)$b),
            'UTF-8'
        );
}