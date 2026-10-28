<?php
/**
 * Reta final: a partir de 8 participantes ainda na disputa não há mais
 * Prova do Anjo, Castigo do Monstro, imunização do Anjo nem Jogo da Discórdia.
 * Big Fone, imunidades especiais e as demais fases seguem normalmente.
 */
function bbbParticipantesRestantes(array $jogadores): int
{
    $total = count($jogadores);
    // Um falso eliminado ainda está concorrendo. Não antecipar o Top 8.
    if (!empty($_SESSION['paredao_falso_ativo']) && empty($_SESSION['paredao_falso_retorno_realizado'])) {
        $falso = trim((string)($_SESSION['falso_eliminado'] ?? ''));
        if ($falso !== '') {
            $encontrado = false;
            foreach ($jogadores as $j) {
                if (strcasecmp(trim((string)($j['nome'] ?? '')), $falso) === 0) {
                    $encontrado = true;
                    break;
                }
            }
            if (!$encontrado) $total++;
        }
    }
    return $total;
}

function bbbRetaFinalSemAnjo(array $jogadores): bool
{
    $restantes = bbbParticipantesRestantes($jogadores);
    return $restantes > 0 && $restantes <= 8;
}

/** Na reta final as discussões continuam nas Interações, mas o Jogo da Discórdia acaba. */
function bbbRetaFinalSemDiscordia(array $jogadores): bool
{
    return bbbRetaFinalSemAnjo($jogadores);
}

/** Depois da votação / Bate-Volta, a fase seguinte depende do Top 8. */
function bbbFaseAposParedao(array $jogadores): string
{
    if (bbbRetaFinalSemDiscordia($jogadores)) {
        $_SESSION['acoes_restantes'] = 3;
        unset($_SESSION['discordia_feito'], $_SESSION['tema_discordia']);
        return 'interacoes_3';
    }
    return 'discordia';
}

function bbbFaseAposVip(array $jogadores): string
{
    return bbbRetaFinalSemAnjo($jogadores) ? 'bigfone' : 'anjo';
}

function bbbFaseAposFesta(array $jogadores): string
{
    if (bbbRetaFinalSemAnjo($jogadores) || !empty($_SESSION['anjo_autoimune'])) {
        return 'paredao';
    }
    return 'imunizacao_anjo';
}

/**
 * Recupera saves antigos ou URLs já abertas na fase do Anjo.
 * Só remove dados do Anjo/Monstro, preservando imunidades de outros poderes.
 */
function bbbSincronizarRetaFinal(array &$jogadores): void
{
    if (!bbbRetaFinalSemAnjo($jogadores)) return;
    $fase = (string)($_SESSION['fase_semana'] ?? '');
    if ($fase === 'anjo' || $fase === 'monstro') {
        $_SESSION['fase_semana'] = 'bigfone';
    } elseif ($fase === 'imunizacao_anjo') {
        $_SESSION['fase_semana'] = 'paredao';
    } elseif ($fase === 'discordia') {
        $_SESSION['fase_semana'] = 'interacoes_3';
        $_SESSION['acoes_restantes'] = 3;
        unset($_SESSION['discordia_feito'], $_SESSION['tema_discordia']);
        // Não há etapas de Discórdia após o Top 8, mesmo em saves antigos.
        return;
    } else {
        return;
    }

    unset($_SESSION['anjo'], $_SESSION['anjos_dupla'], $_SESSION['anjo_dupla_semana'],
        $_SESSION['prova_anjo_finalizada'], $_SESSION['prova_anjo_finalizada_rodada'],
        $_SESSION['prova_anjo_tipo'], $_SESSION['prova_anjo_dados'],
        $_SESSION['resultado_prova_anjo'], $_SESSION['pn_estado_anjo'],
        $_SESSION['monstro'], $_SESSION['monstro_definido'],
        $_SESSION['imunizacao_anjo_feita'], $_SESSION['anjo_autoimune'],
        $_SESSION['anjo_autoimune_estat_contada']);
    foreach ($jogadores as &$j) {
        if (!isset($j['status']) || !is_array($j['status'])) continue;
        $j['status']['anjo'] = false;
        $j['status']['monstro'] = false;
        // status.imune NÃO é apagado: pode vir do Big Fone/produção.
    }
    unset($j);
    $_SESSION['jogadores'] = $jogadores;
}

function bbbAnunciarTop8(array $jogadores): void
{
    if (!bbbRetaFinalSemAnjo($jogadores) || !empty($_SESSION['top8_reta_final_anunciada'])) return;
    $_SESSION['top8_reta_final_anunciada'] = true;
    if (!isset($_SESSION['evento_extra']) || !is_array($_SESSION['evento_extra'])) {
        $_SESSION['evento_extra'] = [];
    }
    $_SESSION['evento_extra'][] = '🏁 RETA FINAL! A casa chegou ao Top 8. Não haverá mais Prova do Anjo, Monstro nem Jogo da Discórdia. As Interações e o Líder continuam valendo!';
}


/** Reta final sem novas dinâmicas surpresa a partir de cinco participantes. */
function bbbAnunciarTop5(array $jogadores): void
{
    if (bbbParticipantesRestantes($jogadores) > 5
        || !empty($_SESSION['top5_sem_reviravoltas_anunciado'])
        || !empty($_SESSION['paredao_falso_ativo'])) return;
    $_SESSION['top5_sem_reviravoltas_anunciado'] = true;
    if (!isset($_SESSION['evento_extra']) || !is_array($_SESSION['evento_extra'])) $_SESSION['evento_extra'] = [];
    $_SESSION['evento_extra'][] = '🏁 TOP 5 — A produção encerrou as Dinâmicas Surpresa. Na próxima etapa, o Top 4 disputará a última vaga direta na Grande Final!';
}
