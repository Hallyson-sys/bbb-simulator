<?php

/* =========================================================
   🤝 LÓGICA DE ALIANÇAS
   ========================================================= */

   /* =========================
   🤝 SISTEMA DE ALIANÇAS COMPATÍVEL COM LOGICA_JOGO.PHP
   Usa a chave $j['alianca'] dentro de cada participante.
========================= */

function nomesBaseAliancas()
{
    return [
        "Fadas Sensatas",
        "Camarote Raiz",
        "Pipoca de Ouro",
        "Quarto Eclipse",
        "Quarto Maré",
        "Os Visionários",
        "Panelinha VIP",
        "Os Protagonistas",
        "Baile da Xepa",
        "Equipe Eclipse",
        "Laços Fortes",
        "Modo Turbo",
        "Tribo do Jogo",
        "Conselho Secreto",
        "Operação Paredão",
        "Tropa da Resenha",
        "Pódio Fechado",
        "Central da Treta",
        "Bonde dos Imunes",
        "Xadrez da Casa"
    ];
}

function indiceJogadorPorNome($jogadores, $nome)
{
    foreach ($jogadores as $i => $j) {
        if (nomeIgual(($j['nome'] ?? ''), $nome)) {
            return $i;
        }
    }
    return null;
}

function nomeAliancaDisponivel($jogadores)
{
    $usadas = [];

    foreach ($jogadores as $j) {
        if (!empty($j['alianca'])) {
            $usadas[] = $j['alianca'];
        }
    }

    $bases = nomesBaseAliancas();
    shuffle($bases);

    foreach ($bases as $base) {
        if (!in_array($base, $usadas)) {
            return $base;
        }
    }

    return "Aliança " . rand(100, 999);
}

function membrosDaAlianca($jogadores, $alianca)
{
    $membros = [];

    foreach ($jogadores as $j) {
        if (!empty($j['alianca']) && $j['alianca'] == $alianca) {
            $membros[] = $j['nome'];
        }
    }

    return $membros;
}

function tamanhoAlianca($jogadores, $alianca)
{
    return count(membrosDaAlianca($jogadores, $alianca));
}

function mesmaAliancaNomes($jogadores, $nomeA, $nomeB)
{
    $aliancaA = null;
    $aliancaB = null;

    foreach ($jogadores as $j) {
        if (nomeIgual(($j['nome'] ?? ''), $nomeA)) {
            $aliancaA = $j['alianca'] ?? null;
        }

        if (nomeIgual(($j['nome'] ?? ''), $nomeB)) {
            $aliancaB = $j['alianca'] ?? null;
        }
    }

    return !empty($aliancaA) && !empty($aliancaB) && $aliancaA == $aliancaB;
}

function registrarHistoricoAlianca(&$jogadores, $nome, $mensagem)
{
    foreach ($jogadores as &$j) {
        if (nomeIgual(($j['nome'] ?? ''), $nome)) {
            if (!isset($j['historico_aliancas']) || !is_array($j['historico_aliancas'])) {
                $j['historico_aliancas'] = [];
            }

            $j['historico_aliancas'][] = $mensagem;

            if (count($j['historico_aliancas']) > 15) {
                $j['historico_aliancas'] = array_slice($j['historico_aliancas'], -15);
            }

            break;
        }
    }
    unset($j);
}

function criarAliancaEntre(&$jogadores, $nomeA, $nomeB, $nomeAlianca = null)
{
    if (($_SESSION['rodada'] ?? 1) < 2) return "";
    if ($nomeA == '' || $nomeB == '' || nomeIgual($nomeA, $nomeB)) return "";

    $idxA = indiceJogadorPorNome($jogadores, $nomeA);
    $idxB = indiceJogadorPorNome($jogadores, $nomeB);

    if ($idxA === null || $idxB === null) return "";

    if (!empty($jogadores[$idxA]['alianca']) && !empty($jogadores[$idxB]['alianca'])) {
        return "";
    }

    if ($nomeAlianca == null) {
        $nomeAlianca = !empty($jogadores[$idxA]['alianca'])
            ? $jogadores[$idxA]['alianca']
            : (!empty($jogadores[$idxB]['alianca']) ? $jogadores[$idxB]['alianca'] : nomeAliancaDisponivel($jogadores));
    }

    $jogadores[$idxA]['alianca'] = $nomeAlianca;
    $jogadores[$idxB]['alianca'] = $nomeAlianca;

    registrarHistoricoAlianca($jogadores, $nomeA, "Entrou na aliança $nomeAlianca com $nomeB.");
    registrarHistoricoAlianca($jogadores, $nomeB, "Entrou na aliança $nomeAlianca com $nomeA.");

    alterarAfinidade($jogadores, $nomeA, $nomeB, 8, -4, 10);
    alterarAfinidade($jogadores, $nomeB, $nomeA, 8, -4, 10);

    return "🤝 $nomeA e $nomeB oficializaram a aliança <b>$nomeAlianca</b>.";
}

function entrarEmAlianca(&$jogadores, $nome, $alianca)
{
    if ($nome == '' || $alianca == '') return "";

    $idx = indiceJogadorPorNome($jogadores, $nome);
    if ($idx === null) return "";

    if (($jogadores[$idx]['alianca'] ?? null) == $alianca) return "";

    $jogadores[$idx]['alianca'] = $alianca;
    registrarHistoricoAlianca($jogadores, $nome, "Entrou na aliança $alianca.");

    foreach ($jogadores as $membro) {
        if (!nomeIgual(($membro['nome'] ?? ''), $nome) && ($membro['alianca'] ?? null) == $alianca) {
            alterarAfinidade($jogadores, $nome, $membro['nome'], 5, -2, 6);
            alterarAfinidade($jogadores, $membro['nome'], $nome, 4, -2, 5);
        }
    }

    return "🤝 $nome entrou para a aliança <b>$alianca</b>.";
}

function romperAlianca(&$jogadores, $nome, $motivo = "a confiança desmoronou")
{
    if ($nome == '') return "";

    $idx = indiceJogadorPorNome($jogadores, $nome);
    if ($idx === null) return "";

    $alianca = $jogadores[$idx]['alianca'] ?? null;
    if (empty($alianca)) return "";

    $jogadores[$idx]['alianca'] = null;
    registrarHistoricoAlianca($jogadores, $nome, "Saiu da aliança $alianca porque $motivo.");

    foreach ($jogadores as $membro) {
        if (!nomeIgual(($membro['nome'] ?? ''), $nome) && ($membro['alianca'] ?? null) == $alianca) {
            alterarAfinidade($jogadores, $nome, $membro['nome'], -8, 8, -10);
            alterarAfinidade($jogadores, $membro['nome'], $nome, -6, 6, -8);
        }
    }

    return "💥 $nome rompeu com a aliança <b>$alianca</b>: $motivo.";
}

function relacaoMediaComAlianca($jogadores, $nome, $alianca, $meuNome = '')
{
    $total = 0;
    $qtd = 0;

    foreach ($jogadores as $membro) {
        if (nomeIgual(($membro['nome'] ?? ''), $nome)) continue;
        if (($membro['alianca'] ?? null) != $alianca) continue;

        $rel = obterRelacaoCompleta($jogadores, $nome, $membro['nome'], $meuNome);
        $score = ($rel['amizade'] ?? 0) + (($rel['confianca'] ?? 0) * 0.7) - (($rel['rivalidade'] ?? 0) * 1.2);
        $total += $score;
        $qtd++;
    }

    return $qtd == 0 ? 0 : ($total / $qtd);
}

function atualizarAliancasAutomaticas(&$jogadores, $meuNome = '')
{
    $eventos = [];

    /* As alianças só começam a se formar a partir da Rodada 2,
       quando a casa já teve tempo de criar afinidades e rivalidades. */
    if (($_SESSION['rodada'] ?? 1) < 2) {
        return $eventos;
    }

    foreach ($jogadores as &$j) {
        if (!array_key_exists('alianca', $j)) {
            $j['alianca'] = null;
        }
        if (!isset($j['historico_aliancas']) || !is_array($j['historico_aliancas'])) {
            $j['historico_aliancas'] = [];
        }
    }
    unset($j);

    /* Rompimentos */
    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';
        $alianca = $j['alianca'] ?? null;

        if ($nome == '' || empty($alianca)) continue;

        $media = relacaoMediaComAlianca($jogadores, $nome, $alianca, $meuNome);

        if ($media < 8 && rand(1, 100) <= 35) {
            $ev = romperAlianca($jogadores, $nome, "a relação com o grupo ficou muito desgastada");
            if ($ev != '') $eventos[] = $ev;
        }
    }

    /* Entrada em alianças existentes */
    foreach ($jogadores as $j) {
        $nome = $j['nome'] ?? '';
        if ($nome == '' || !empty($j['alianca'])) continue;

        $melhorAlianca = null;
        $melhorScore = -999;

        foreach ($jogadores as $outro) {
            if (nomeIgual(($outro['nome'] ?? ''), $nome)) continue;
            if (empty($outro['alianca'])) continue;

            $rel = obterRelacaoCompleta($jogadores, $nome, $outro['nome'], $meuNome);
            $score = ($rel['amizade'] ?? 0) + ($rel['confianca'] ?? 0) - (($rel['rivalidade'] ?? 0) * 1.5);

            if ($score > $melhorScore) {
                $melhorScore = $score;
                $melhorAlianca = $outro['alianca'];
            }
        }

        if ($melhorAlianca != null && $melhorScore >= 90 && tamanhoAlianca($jogadores, $melhorAlianca) < 5 && rand(1, 100) <= 35) {
            $ev = entrarEmAlianca($jogadores, $nome, $melhorAlianca);
            if ($ev != '') $eventos[] = $ev;
        }
    }

    /* Criação de novas alianças */
    for ($i = 0; $i < count($jogadores); $i++) {
        for ($k = $i + 1; $k < count($jogadores); $k++) {
            $a = $jogadores[$i];
            $b = $jogadores[$k];

            if (!empty($a['alianca']) || !empty($b['alianca'])) continue;

            $relAB = obterRelacaoCompleta($jogadores, $a['nome'], $b['nome'], $meuNome);
            $relBA = obterRelacaoCompleta($jogadores, $b['nome'], $a['nome'], $meuNome);

            $score =
                ($relAB['amizade'] ?? 0) +
                ($relAB['confianca'] ?? 0) +
                ($relBA['amizade'] ?? 0) +
                ($relBA['confianca'] ?? 0) -
                (($relAB['rivalidade'] ?? 0) + ($relBA['rivalidade'] ?? 0));

            if ($score >= 170 && rand(1, 100) <= 25) {
                $ev = criarAliancaEntre($jogadores, $a['nome'], $b['nome']);
                if ($ev != '') $eventos[] = $ev;
                break 2;
            }
        }
    }

    return $eventos;
}

function escolherAlvoDoGrupo($jogadores, $alianca, $bloqueados = [])
{
    if (empty($alianca)) return null;

    $pontuacao = [];

    foreach ($jogadores as $membro) {
        if (($membro['alianca'] ?? null) != $alianca) continue;

        foreach ($jogadores as $alvo) {
            $nomeAlvo = $alvo['nome'] ?? '';

            if ($nomeAlvo == '') continue;
            if (($alvo['alianca'] ?? null) == $alianca) continue;
            if (nomeIgual($nomeAlvo, ($membro['nome'] ?? ''))) continue;
            if (in_array($nomeAlvo, $bloqueados)) continue;
            if (!empty($alvo['status']['lider'])) continue;
            if (estaImune($jogadores, $nomeAlvo)) continue;

            $rel = obterRelacaoCompleta($jogadores, $membro['nome'], $nomeAlvo, $_SESSION['meu_nome'] ?? '');
            $score = ($rel['rivalidade'] ?? 0) + (100 - ($rel['amizade'] ?? 0)) + rand(0, 10);

            $pontuacao[$nomeAlvo] = ($pontuacao[$nomeAlvo] ?? 0) + $score;
        }
    }

    if (empty($pontuacao)) return null;

    arsort($pontuacao);
    return array_key_first($pontuacao);
}

function gerarResumoAliancas($jogadores)
{
    $aliancas = [];

    foreach ($jogadores as $j) {
        if (!empty($j['alianca'])) {
            if (!isset($aliancas[$j['alianca']])) {
                $aliancas[$j['alianca']] = [];
            }
            $aliancas[$j['alianca']][] = $j['nome'];
        }
    }

    ksort($aliancas);
    return $aliancas;
}

function obterAliancaJogador($jogadores, $nome)
{
    foreach ($jogadores as $j) {
        if (nomeIgual(($j['nome'] ?? ''), $nome)) {
            return $j['alianca'] ?? null;
        }
    }

    return null;
}

function calcularAceitacaoNaAlianca($jogadores, $nome, $alianca, $meuNome = '')
{
    if ($nome == '' || $alianca == '') return 0;

    $membros = membrosDaAlianca($jogadores, $alianca);
    if (empty($membros)) return 0;

    $total = 0;
    $qtd = 0;

    foreach ($membros as $membro) {
        if (nomeIgual($membro, $nome)) continue;

        $rel = obterRelacaoCompleta($jogadores, $membro, $nome, $meuNome);
        $score = ($rel['amizade'] ?? 0) + (($rel['confianca'] ?? 0) * 0.8) - (($rel['rivalidade'] ?? 0) * 1.4);
        $total += $score;
        $qtd++;
    }

    if ($qtd == 0) return 0;

    return round($total / $qtd);
}

function jogadorEntrarEmAlianca(&$jogadores, $nome, $alianca)
{
    if (($_SESSION['rodada'] ?? 1) < 2) {
        return "⏳ As alianças ainda não começaram oficialmente. Elas só abrem a partir da Rodada 2.";
    }

    if ($nome == '' || $alianca == '') {
        return "⚠️ Escolha uma aliança válida.";
    }

    if (!empty(obterAliancaJogador($jogadores, $nome))) {
        return "⚠️ $nome já está em uma aliança. Para entrar em outra, primeiro precisa sair da atual.";
    }

    if (tamanhoAlianca($jogadores, $alianca) <= 0) {
        return "⚠️ Essa aliança não existe mais.";
    }

    if (tamanhoAlianca($jogadores, $alianca) >= 5) {
        return "🚫 A aliança <b>$alianca</b> recusou a entrada de $nome porque o grupo já está cheio.";
    }

    $aceitacao = calcularAceitacaoNaAlianca($jogadores, $nome, $alianca, $_SESSION['meu_nome'] ?? '');
    $chance = max(15, min(90, $aceitacao));

    if (rand(1, 100) <= $chance) {
        $ev = entrarEmAlianca($jogadores, $nome, $alianca);
        alterarPopularidadePublica($jogadores, $nome, 1, 4, "entrou em uma aliança estratégica", true);
        return $ev != '' ? $ev : "🤝 $nome entrou para a aliança <b>$alianca</b>.";
    }

    foreach (membrosDaAlianca($jogadores, $alianca) as $membro) {
        alterarAfinidade($jogadores, $membro, $nome, -4, 3, -6);
        alterarAfinidade($jogadores, $nome, $membro, -3, 2, -4);
    }

    alterarPopularidadePublica($jogadores, $nome, -3, 0, "tentou entrar em uma aliança e foi recusado", true);
    return "🚫 $nome tentou entrar na aliança <b>$alianca</b>, mas o grupo recusou por falta de confiança.";
}

function jogadorSairDaAlianca(&$jogadores, $nome)
{
    $alianca = obterAliancaJogador($jogadores, $nome);

    if (empty($alianca)) {
        return "⚠️ $nome não está em nenhuma aliança no momento.";
    }

    $ev = romperAlianca($jogadores, $nome, "decidiu jogar sozinho e não seguir mais o grupo");
    alterarPopularidadePublica($jogadores, $nome, -5, 3, "rompeu com uma aliança e dividiu o público", true);

    return $ev != '' ? $ev : "💥 $nome saiu da aliança <b>$alianca</b>.";
}


function nomeAliancaJaExiste($jogadores, $nomeAlianca)
{
    foreach ($jogadores as $j) {
        if (!empty($j['alianca']) && mb_strtolower(trim($j['alianca']), 'UTF-8') === mb_strtolower(trim($nomeAlianca), 'UTF-8')) {
            return true;
        }
    }
    return false;
}

function calcularChanceAceitarConviteAlianca($jogadores, $convidado, $criador, $meuNome = '')
{
    $relConvidado = obterRelacaoCompleta($jogadores, $convidado, $criador, $meuNome);
    $relCriador = obterRelacaoCompleta($jogadores, $criador, $convidado, $meuNome);

    $scoreConvidado = $relConvidado['score'] ?? 0;
    $scoreCriador = $relCriador['score'] ?? 0;

    $amizade = (($relConvidado['amizade'] ?? 0) + ($relCriador['amizade'] ?? 0)) / 2;
    $confianca = (($relConvidado['confianca'] ?? 0) + ($relCriador['confianca'] ?? 0)) / 2;
    $rivalidade = (($relConvidado['rivalidade'] ?? 0) + ($relCriador['rivalidade'] ?? 0)) / 2;
    $romance = max(obterRomance($jogadores, $convidado, $criador), obterRomance($jogadores, $criador, $convidado));

    $chance = 25;
    $chance += (int)round(($scoreConvidado + $scoreCriador) / 5);
    $chance += (int)round($amizade / 5);
    $chance += (int)round($confianca / 6);
    $chance -= (int)round($rivalidade / 3);

    if ($romance >= 30) $chance += 8;
    if ($romance >= 60) $chance += 12;

    if (saoAliados($jogadores, $convidado, $criador, $meuNome)) $chance += 18;
    if (saoRivais($jogadores, $convidado, $criador, $meuNome)) $chance -= 30;

    return max(8, min(92, $chance));
}

function criarAliancaJogadorComConvites(&$jogadores, $criador, $nomeAlianca, $convidados)
{
    if (($_SESSION['rodada'] ?? 1) < 2) {
        return "⏳ As alianças só podem ser criadas a partir da Rodada 2.";
    }

    if ($criador == '') {
        return "⚠️ Jogador inválido para criar aliança.";
    }

    if (!empty(obterAliancaJogador($jogadores, $criador))) {
        return "⚠️ $criador já está em uma aliança. Para criar outra, primeiro precisa sair da atual.";
    }

    $nomeAlianca = trim((string)$nomeAlianca);
    if ($nomeAlianca == '') {
        $nomeAlianca = nomeAliancaDisponivel($jogadores);
    }

    $nomeAlianca = strip_tags($nomeAlianca);
    $nomeAlianca = mb_substr($nomeAlianca, 0, 35, 'UTF-8');

    if (nomeAliancaJaExiste($jogadores, $nomeAlianca)) {
        return "⚠️ Já existe uma aliança chamada <b>$nomeAlianca</b>. Escolha outro nome.";
    }

    if (!is_array($convidados)) {
        $convidados = [];
    }

    $convidados = array_values(array_unique(array_filter(array_map('trim', $convidados), function ($nome) use ($criador) {
        return $nome != '' && !nomeIgual($nome, $criador);
    })));

    if (count($convidados) == 0) {
        return "⚠️ Escolha pelo menos 1 participante para convidar para a aliança.";
    }

    if (count($convidados) > 5) {
        $convidados = array_slice($convidados, 0, 5);
    }

    $aceitos = [];
    $recusados = [];
    $ignorados = [];

    foreach ($convidados as $nomeConvidado) {
        $idx = indiceJogadorPorNome($jogadores, $nomeConvidado);

        if ($idx === null) {
            continue;
        }

        if (!empty($jogadores[$idx]['alianca'])) {
            $ignorados[] = $nomeConvidado;
            continue;
        }

        $chance = calcularChanceAceitarConviteAlianca($jogadores, $nomeConvidado, $criador, $_SESSION['meu_nome'] ?? '');

        if (rand(1, 100) <= $chance) {
            $aceitos[] = $nomeConvidado;
        } else {
            $recusados[] = $nomeConvidado;
            alterarAfinidade($jogadores, $nomeConvidado, $criador, -4, 3, -5);
            alterarAfinidade($jogadores, $criador, $nomeConvidado, -2, 2, -3);
        }
    }

    if (empty($aceitos)) {
        alterarPopularidadePublica($jogadores, $criador, -4, 0, "tentou montar uma aliança, mas ninguém aceitou", true);

        $msg = "💔 $criador tentou criar a aliança <b>$nomeAlianca</b>, mas ninguém aceitou o convite.";

        if (!empty($recusados)) {
            $msg .= " Recusaram: " . implode(', ', $recusados) . ".";
        }

        if (!empty($ignorados)) {
            $msg .= " Já estavam em outra aliança: " . implode(', ', $ignorados) . ".";
        }

        return $msg;
    }

    $idxCriador = indiceJogadorPorNome($jogadores, $criador);
    if ($idxCriador !== null) {
        $jogadores[$idxCriador]['alianca'] = $nomeAlianca;
        registrarHistoricoAlianca($jogadores, $criador, "Criou a aliança $nomeAlianca.");
    }

    foreach ($aceitos as $nomeAceito) {
        $idxAceito = indiceJogadorPorNome($jogadores, $nomeAceito);
        if ($idxAceito === null) continue;

        $jogadores[$idxAceito]['alianca'] = $nomeAlianca;
        registrarHistoricoAlianca($jogadores, $nomeAceito, "Aceitou o convite de $criador e entrou na aliança $nomeAlianca.");

        alterarAfinidade($jogadores, $criador, $nomeAceito, 10, -4, 10);
        alterarAfinidade($jogadores, $nomeAceito, $criador, 8, -4, 8);

        foreach ($aceitos as $outroAceito) {
            if (nomeIgual($nomeAceito, $outroAceito)) continue;
            alterarAfinidade($jogadores, $nomeAceito, $outroAceito, 4, -2, 5);
        }
    }

    impactoPopularidadePorPersonalidade($jogadores, $criador, "alianca", true);

    $membros = array_merge([$criador], $aceitos);
    $msg = "🤝 $criador criou a aliança <b>$nomeAlianca</b> com " . implode(', ', $aceitos) . ".";
    $msg .= "<br>👥 Membros atuais: " . implode(', ', $membros) . ".";

    if (!empty($recusados)) {
        $msg .= "<br>🚫 Recusaram o convite: " . implode(', ', $recusados) . ".";
    }

    if (!empty($ignorados)) {
        $msg .= "<br>⚠️ Não foram convidados porque já estavam em outra aliança: " . implode(', ', $ignorados) . ".";
    }

    return $msg;
}

function alvoCombinadoDaAlianca($jogadores, $alianca, $bloqueados = [])
{
    if (empty($alianca)) return null;

    if (!isset($_SESSION['alvos_aliancas_semana'])) {
        $_SESSION['alvos_aliancas_semana'] = [];
    }

    if (isset($_SESSION['alvos_aliancas_semana'][$alianca])) {
        $alvoSalvo = $_SESSION['alvos_aliancas_semana'][$alianca];
        if (!in_array($alvoSalvo, $bloqueados)) {
            return $alvoSalvo;
        }
    }

    $alvo = escolherAlvoDoGrupo($jogadores, $alianca, $bloqueados);

    if ($alvo != null) {
        $_SESSION['alvos_aliancas_semana'][$alianca] = $alvo;
    }

    return $alvo;
}