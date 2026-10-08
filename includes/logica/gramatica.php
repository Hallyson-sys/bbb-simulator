<?php

/* =========================================================
   🗣️ GRAMÁTICA DOS PARTICIPANTES

   Mantém artigos e pronomes coerentes com o gênero usado
   pelo personagem. Saves antigos continuam compatíveis:
   primeiro usa o campo "genero" quando existir; depois usa
   a lista de nomes do próprio jogo; por fim aplica apenas
   heurísticas conservadoras. Se ainda houver dúvida, evita
   forçar artigo/pronome em vez de chutar.
   ========================================================= */

function normalizarNomeGramaticaBBB($nome)
{
    $nome = trim((string)$nome);
    $nome = function_exists('mb_strtolower')
        ? mb_strtolower($nome, 'UTF-8')
        : strtolower($nome);

    $mapa = [
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
        'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
        'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
        'ç'=>'c'
    ];

    return strtr($nome, $mapa);
}

function normalizarGeneroBBB($valor)
{
    $v = normalizarNomeGramaticaBBB($valor);

    if (in_array($v, ['f','fem','feminino','feminina','mulher'], true)) {
        return 'f';
    }

    if (in_array($v, ['m','masc','masculino','masculina','homem'], true)) {
        return 'm';
    }

    return null;
}

function generoNomeConhecidoBBB($nome)
{
    $primeiro = preg_split('/\s+/u', normalizarNomeGramaticaBBB($nome))[0] ?? '';

    $femininos = [
        'ana','julia','marina','fernanda','bianca','camila','larissa','aline',
        'vanessa','beatriz','nicole','debora','rayssa','giulia','samira','mariana',
        'luiza','paula','eduarda','vitoria','amanda','talita','raissa','brenda',
        'maria','yasmin','malu','francisca','isabelly','carolina','zoe','clara',
        'taina','helena','priscila','rauanny'
    ];

    $masculinos = [
        'carlos','lucas','pedro','rafael','gustavo','bruno','diego','igor','renan',
        'felipe','alberto','yago','nathan','allan','theo','henrique','vinicius',
        'leandro','matheus','caio','murilo','thiago','joao','matteo','gabriel',
        'otto','zeca','patrick','camilo','heitor','henry'
    ];

    if (in_array($primeiro, $femininos, true)) return 'f';
    if (in_array($primeiro, $masculinos, true)) return 'm';

    /* Heurísticas conservadoras para nomes personalizados. */
    if (preg_match('/(son|ton|sonny|aldo|ardo|erto|icio|icio|us|os)$/u', $primeiro)) {
        return 'm';
    }

    if (preg_match('/(ela|elle|issa|essa|ara|ana|ina|iane|iane|ine|ete|ete)$/u', $primeiro)) {
        return 'f';
    }

    return null;
}

function generoParticipanteBBB($nome, $jogadores = null)
{
    if ($jogadores === null) {
        $jogadores = $_SESSION['jogadores'] ?? [];
    }

    if (is_array($jogadores)) {
        foreach ($jogadores as $j) {
            if (!is_array($j)) continue;

            $nomeJ = trim((string)($j['nome'] ?? ''));
            if ($nomeJ === '') continue;

            $iguais = function_exists('nomeIgual')
                ? nomeIgual($nomeJ, $nome)
                : normalizarNomeGramaticaBBB($nomeJ) === normalizarNomeGramaticaBBB($nome);

            if (!$iguais) continue;

            $g = normalizarGeneroBBB($j['genero'] ?? ($j['sexo'] ?? ''));
            if ($g !== null) return $g;
            break;
        }
    }

    return generoNomeConhecidoBBB($nome);
}

function artigoParticipanteBBB($nome, $jogadores = null, $maiusculo = false)
{
    $g = generoParticipanteBBB($nome, $jogadores);
    if ($g === null) return '';

    if ($g === 'f') return $maiusculo ? 'A' : 'a';
    return $maiusculo ? 'O' : 'o';
}

function deParticipanteBBB($nome, $jogadores = null)
{
    $g = generoParticipanteBBB($nome, $jogadores);
    if ($g === 'f') return 'da';
    if ($g === 'm') return 'do';
    return 'de';
}

function ajustarGeneroTextoParticipanteBBB($texto, $nome, $jogadores = null, $ajustarPronomes = true)
{
    $texto = (string)$texto;
    $nome = trim((string)$nome);
    if ($texto === '' || $nome === '') return $texto;

    $g = generoParticipanteBBB($nome, $jogadores);
    if ($g === null) return $texto;

    $n = preg_quote($nome, '/');

    if ($g === 'f') {
        $regras = [
            '/\bO\s+'.$n.'\b/u' => 'A '.$nome,
            '/\bo\s+'.$n.'\b/u' => 'a '.$nome,
            '/\bDo\s+'.$n.'\b/u' => 'Da '.$nome,
            '/\bdo\s+'.$n.'\b/u' => 'da '.$nome,
            '/\bNo\s+'.$n.'\b/u' => 'Na '.$nome,
            '/\bno\s+'.$n.'\b/u' => 'na '.$nome,
            '/\bPelo\s+'.$n.'\b/u' => 'Pela '.$nome,
            '/\bpelo\s+'.$n.'\b/u' => 'pela '.$nome,
        ];
    } else {
        $regras = [
            '/\bA\s+'.$n.'\b/u' => 'O '.$nome,
            '/\ba\s+'.$n.'\b/u' => 'o '.$nome,
            '/\bDa\s+'.$n.'\b/u' => 'Do '.$nome,
            '/\bda\s+'.$n.'\b/u' => 'do '.$nome,
            '/\bNa\s+'.$n.'\b/u' => 'No '.$nome,
            '/\bna\s+'.$n.'\b/u' => 'no '.$nome,
            '/\bPela\s+'.$n.'\b/u' => 'Pelo '.$nome,
            '/\bpela\s+'.$n.'\b/u' => 'pelo '.$nome,
        ];
    }

    foreach ($regras as $padrao => $sub) {
        $texto = preg_replace($padrao, $sub, $texto);
    }

    if (!$ajustarPronomes) return $texto;

    if ($g === 'f') {
        $trocas = [
            '/\bdele\b/u' => 'dela',
            '/\bnele\b/u' => 'nela',
            '/\bcom ele\b/u' => 'com ela',
            '/\bpara ele\b/u' => 'para ela',
            '/\bpra ele\b/u' => 'pra ela',
            '/\bquietinho\b/u' => 'quietinha',
            '/\bsozinho\b/u' => 'sozinha',
            '/\bapagado\b/u' => 'apagada',
            '/\bcansado dele\b/u' => 'cansado dela',
        ];
    } else {
        $trocas = [
            '/\bdela\b/u' => 'dele',
            '/\bnela\b/u' => 'nele',
            '/\bcom ela\b/u' => 'com ele',
            '/\bpara ela\b/u' => 'para ele',
            '/\bpra ela\b/u' => 'pra ele',
            '/\bquietinha\b/u' => 'quietinho',
            '/\bsozinha\b/u' => 'sozinho',
            '/\bapagada\b/u' => 'apagado',
        ];
    }

    foreach ($trocas as $padrao => $sub) {
        $texto = preg_replace($padrao, $sub, $texto);
    }

    return $texto;
}

function ajustarGeneroTextoComElencoBBB($texto, $jogadores = null)
{
    if ($jogadores === null) {
        $jogadores = $_SESSION['jogadores'] ?? [];
    }

    if (!is_array($jogadores) || $texto === '') return (string)$texto;

    $citados = [];
    foreach ($jogadores as $j) {
        $nome = trim((string)($j['nome'] ?? ''));
        $pos = function_exists('mb_stripos')
            ? mb_stripos((string)$texto, $nome, 0, 'UTF-8')
            : stripos((string)$texto, $nome);
        if ($nome !== '' && $pos !== false) {
            $citados[] = $nome;
        }
    }

    $unico = count($citados) === 1;
    foreach ($citados as $nome) {
        $texto = ajustarGeneroTextoParticipanteBBB($texto, $nome, $jogadores, $unico);
    }

    return $texto;
}

/* =========================================================
   🗣️ FLEXÃO DO PRÓPRIO FALANTE
   Converte formas como "tranquilo(a)" / "cansado(a)"
   para o gênero conhecido do participante que está falando.
   ========================================================= */
function flexionarTextoFalanteBBB($texto, $nome, $jogadores = null)
{
    $texto = (string)$texto;
    $g = generoParticipanteBBB($nome, $jogadores);
    if ($g === null || $texto === '') return $texto;

    return preg_replace_callback('/([\p{L}]+)o\(a\)/u', function ($m) use ($g) {
        return $m[1] . ($g === 'f' ? 'a' : 'o');
    }, $texto);
}
