<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/includes/logica/utilitarios.php';
require_once __DIR__ . '/includes/logica/modo_teste.php';
require_once __DIR__ . '/includes/logica/relacoes.php';
require_once __DIR__ . '/includes/logica/aliancas.php';
require_once __DIR__ . '/includes/logica/aliancas_3.php';
require_once __DIR__ . '/includes/logica/objetivos_npc.php';
require_once __DIR__ . '/includes/logica/dilemas_jogo.php';
require_once __DIR__ . '/includes/logica/dinamicas_surpresa.php';
require_once __DIR__ . '/includes/logica/paredao.php';
require_once __DIR__ . '/includes/logica/provas_novas.php';
require_once __DIR__ . '/includes/logica/reta_final.php';
require_once __DIR__ . '/includes/logica/top4.php';
require_once __DIR__ . '/includes/logica/fofoca_vt.php';
require_once __DIR__ . '/includes/logica/dev_diagnosticos.php';


/* =========================================================
   🔒 SOMENTE LOCALHOST
   ========================================================= */
if (!modoTestePermitido()) {

    http_response_code(403);

    exit(
        '🧪 O Modo de Testes está disponível apenas no ambiente local.'
    );
}


/* =========================================================
   🎮 EXIGIR TEMPORADA
   ========================================================= */
if (
    !isset($_SESSION['jogadores']) ||
    !is_array($_SESSION['jogadores'])
) {

    header('Location: index.php');
    exit;
}


$jogadores =
    array_values(
        $_SESSION['jogadores']
    );

$meuNome =
    $_SESSION['meu_nome']
    ?? '';

$rodada =
    (int)(
        $_SESSION['rodada']
        ?? 1
    );

$fase =
    $_SESSION['fase_semana']
    ?? 'queridometro';

$moedas =
    (int)(
        $_SESSION['moedas_publico']
        ?? 0
    );

$nomes =
    nomesParticipantesModoTeste(
        $jogadores
    );

$fases =
    fasesDisponiveisModoTeste();

// Um token para todos os formulários de diagnóstico (somente localhost).
if (empty($_SESSION['dev_csrf'])) {
    $_SESSION['dev_csrf'] = bin2hex(random_bytes(24));
}
$devToken = (string)$_SESSION['dev_csrf'];


require_once __DIR__ . '/includes/actions/modo_teste.php';

$dsOpcoes = dsDescricoes();
$dsInfo = dsAtual();
$dsAtiva = (string)($dsInfo['tipo'] ?? '');
$devGrupo = (string)(obterAliancaJogador($jogadores, $meuNome) ?? '');
$devMembros = $devGrupo !== '' ? membrosDaAlianca($jogadores, $devGrupo) : [];
$devCandidatosCrise = array_values(array_filter($devMembros, fn($n) => !nomeIgual($n, $meuNome)));
$dev3Relatorio = $_SESSION['dev3_relatorio'] ?? null;
$devAviso = (string)($_SESSION['dev_aviso'] ?? '');
unset($_SESSION['dev_aviso']);


function eDev($texto)
{
    return htmlspecialchars(
        (string)$texto,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Modo de Testes — BBB Simulator
    </title>

    <link
        rel="stylesheet"
        href="assets/css/modo_teste.css?v=3.0"
    >

</head>

<body>

<div class="dev-page">

    <header class="dev-header">

        <div>

            <span class="dev-badge">
                🧪 DESENVOLVIMENTO
            </span>

            <h1>
                Central de Testes 3.0
            </h1>

            <p>
                BBB Simulator • Ferramentas locais
            </p>

        </div>

        <a
            href="jogo.php"
            class="voltar"
        >
            ← Voltar ao jogo
        </a>

    </header>


    <div class="dev-resumo">

        <div>
            <span>🔥 Semana</span>
            <strong><?= $rodada ?></strong>
        </div>

        <div>
            <span>🎮 Fase</span>
            <strong>
                <?= eDev(
                    $fases[$fase]
                    ?? $fase
                ) ?>
            </strong>
        </div>

        <div>
            <span>👥 Jogadores</span>
            <strong>
                <?= count($jogadores) ?>
            </strong>
        </div>

        <div>
            <span>🪙 Moedas</span>
            <strong>
                <?= $moedas ?>
            </strong>
        </div>

    </div>


    <?php if ($devAviso !== ''): ?><div class="dev-flash" role="status"><?= eDev($devAviso) ?></div><?php endif; ?>

    <main class="dev-grid">

      <!-- MODO DEV 3.0 — Diagnósticos automáticos, somente leitura -->
      <section class="dev-card dev-card-wide dev3-panel" id="dev3-testes">
        <div class="dev3-title-row"><div>
          <span class="dev3-eyebrow">VERIFICAÇÃO AUTOMÁTICA • LOCALHOST</span>
          <h2>🧪 Modo DEV 3.0 — Diagnósticos</h2>
          <p>Analise seu save e execute cenários simulados sem avançar semanas, alterar votos ou gastar moedas. Os testes não alteram os dados da partida.</p>
        </div><span class="dev3-pill">SEGURO PARA SAVES</span></div>
        <div class="dev3-actions">
          <?php foreach (['completo'=>'🔍 Executar bateria completa','save'=>'🩺 Verificar save atual','simulacoes'=>'🎲 Simular 27 regras'] as $tipo=>$texto): ?>
          <form method="POST" action="modo_teste.php#dev3-testes">
            <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">
            <input type="hidden" name="dev3_escopo" value="<?= eDev($tipo) ?>">
            <button type="submit" name="dev3_executar" value="1"><?= eDev($texto) ?></button>
          </form>
          <?php endforeach; ?>
        </div>
        <?php if (is_array($dev3Relatorio) && isset($dev3Relatorio['linhas'], $dev3Relatorio['resumo'])): ?>
          <div class="dev3-report" role="region" aria-label="Relatório de testes">
            <div class="dev3-report-header">
              <h3>Resultado da execução</h3>
              <small><?= eDev($dev3Relatorio['executado_em'] ?? '') ?> • Semana <?= (int)($dev3Relatorio['semana'] ?? 0) ?></small>
            </div>
            <div class="dev3-kpis">
              <div class="dev3-ok"><strong><?= (int)($dev3Relatorio['resumo']['ok'] ?? 0) ?></strong><span>Passaram</span></div>
              <div class="dev3-warn"><strong><?= (int)($dev3Relatorio['resumo']['aviso'] ?? 0) ?></strong><span>Avisos</span></div>
              <div class="dev3-fail"><strong><?= (int)($dev3Relatorio['resumo']['falha'] ?? 0) ?></strong><span>Problemas</span></div>
            </div>
            <p class="dev3-explainer">Avisos podem indicar um estado incompleto criado manualmente no DEV. Problemas precisam de conferência antes do deploy. Uma bateria verde não substitui uma partida completa no navegador.</p>
            <div class="dev3-check-list">
              <?php foreach ($dev3Relatorio['linhas'] as $linha): ?>
                <div class="dev3-check dev3-<?= eDev($linha['status']) ?>">
                  <span class="dev3-symbol" aria-hidden="true"><?= $linha['status']==='ok'?'✓':($linha['status']==='aviso'?'!':'×') ?></span>
                  <div><strong><?= eDev($linha['titulo']) ?></strong><small><?= eDev($linha['codigo'].' — '.$linha['detalhe']) ?></small></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php else: ?>
          <div class="dev3-tip">Comece por <strong>Executar bateria completa</strong>. Os testes sintéticos usam cópias isoladas de cenários Top 8, Paredão Falso, Carta Misteriosa, imunidades e pesos de voto.</div>
        <?php endif; ?>
        <details class="dev3-help"><summary>🧾 Também quero verificar a sintaxe de todos os arquivos PHP</summary>
          <p>No terminal do VS Code, na raiz do projeto, execute:</p>
          <code>php testes/dev3_regressao.php</code>
          <p>O script roda os mesmos testes isolados e a verificação <code>php -l</code> em todos os PHPs. Não modifica nenhum save.</p>
        </details>
      </section>

      <section class="dev-card dev-card-wide" id="dev-dilemas">
        <h2>⚖️ Dilemas de Jogo 2.0 — laboratório</h2>
        <p class="dev-help">Força um dilema em uma fase social para conferir as escolhas e os efeitos. <strong>Modifica a temporada atual</strong>; faça isso num save de teste. A bateria de diagnósticos acima continua não destrutiva.</p>
        <div class="dev3-actions">
          <form method="POST" action="modo_teste.php">
            <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">
            <select name="dev_dilema_fase" aria-label="Fase para testar dilema"><option value="interacoes_1">Interações</option><option value="festa">Festa</option></select>
            <button type="submit" name="dev_dilema_forcar" value="1">⚖️ Forçar dilema de jogo</button>
          </form>
        </div>
      </section>

      <section class="dev-card dev-card-wide" id="dev-iniciativa-npc">
        <h2>💬 Iniciativa dos NPCs 2.0 — laboratório</h2>
        <p class="dev-help">Na fase atual de Interações, escolha quem vai procurar seu personagem. Teste o convite, o adiamento e as respostas. <strong>Altera a temporada atual</strong>: use um save de teste.</p>
        <div class="dev3-actions">
          <form method="POST" action="modo_teste.php">
            <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">
            <select name="dev_in2_npc" aria-label="NPC que iniciará a conversa" required>
              <option value="">Escolha um participante</option>
              <?php foreach($jogadores as $npcDev): $nomeNpcDev=(string)($npcDev['nome']??'');
                if($nomeNpcDev==='' || $nomeNpcDev===$meuNome || !empty($npcDev['eliminado']) || !empty($npcDev['fora_da_casa'])) continue; ?>
                <option value="<?= eDev($nomeNpcDev) ?>"><?= eDev($nomeNpcDev) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" name="dev_in2_forcar" value="1">💬 Forçar convite de NPC</button>
          </form>
        </div>
        <p class="dev-help">Teste sem alterar o save: <code>php testes/iniciativa_npc_2.php</code></p>
      </section>

      <section class="dev-card dev-card-wide" id="dev-objetivos-npc">
        <h2>🎯 Objetivos Pessoais dos NPCs — diagnóstico</h2>
        <p class="dev-help">Somente localhost: mostra motivações internas para verificar a IA. Elas não ficam disponíveis para jogadores confinados.</p>
        <div class="dev-aliados">
          <?php
          $tiposObjetivosDev = [
            'proteger_aliado'=>'Proteger aliado', 'superar_rival'=>'Superar rival',
            'reconciliar'=>'Buscar reconciliação', 'construir_alianca'=>'Construir aliança',
            'fortalecer_alianca'=>'Fortalecer aliança', 'vencer_prova'=>'Vencer prova',
            'evitar_paredao'=>'Evitar Paredão', 'chegar_final'=>'Chegar à final'
          ];
          $totalMetasDev = 0;
          foreach ($jogadores as $npcObjetivoDev):
            $nomeObjetivoDev = (string)($npcObjetivoDev['nome'] ?? '');
            if ($nomeObjetivoDev === '' || $nomeObjetivoDev === $meuNome || !empty($npcObjetivoDev['eliminado'])) continue;
            $metaObjetivoDev = on1Objetivo($nomeObjetivoDev);
            if (!$metaObjetivoDev) continue;
            $totalMetasDev++;
          ?>
          <div class="dev-aliado">
            <span><?= eDev($nomeObjetivoDev) ?></span>
            <strong><?= eDev(($tiposObjetivosDev[$metaObjetivoDev['tipo']] ?? 'Objetivo desconhecido') .
              (!empty($metaObjetivoDev['alvo']) ? ' • '.$metaObjetivoDev['alvo'] : '')) ?></strong>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if ($totalMetasDev===0): ?>
          <p class="dev-help">Nenhuma meta inicializada. Entre em uma semana de jogo para que os NPCs definam seus objetivos.</p>
        <?php endif; ?>
        <div class="dev-tip">Objetivos alteram pesos da IA; não obrigam votos nem garantem provas. A bateria automática também verifica estabilidade das metas e exclusão de eliminados.</div>
      </section>

      <section class="dev-card">
        <h2>🎮 Laboratório de Provas 4.0</h2>
        <p>Forçar uma prova individual ou cooperativa. Modifica o save de testes (somente localhost).</p>
        <form method="POST">
          <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">
          <label>Prova a testar</label>
          <select name="dev_tipo_prova">
            <?php foreach (['lider'=>'👑 Líder', 'anjo'=>'😇 Anjo'] as $classe=>$rotulo):
              foreach(pnCatalogo($classe) as $id=>$meta): ?>
                <option value="<?=eDev($classe.':'.$id)?>"><?=eDev($rotulo.' — '.$meta['titulo'])?></option>
            <?php endforeach;endforeach; ?>
          </select>
          <button type="submit" name="dev_prova_interativa">▶ Testar prova</button>
        </form>
      </section>



        <!-- =============================================
             TEMPORADA
        ============================================== -->

        <section class="dev-card">

            <h2>
                🔥 Temporada
            </h2>

            <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                <label>
                    Semana
                </label>

                <input
                    type="number"
                    name="rodada"
                    min="1"
                    value="<?= $rodada ?>"
                >

                <button
                    name="dev_rodada"
                >
                    Aplicar rodada
                </button>

            </form>


            <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                <label>
                    Moedas
                </label>

                <input
                    type="number"
                    name="moedas"
                    min="0"
                    value="<?= $moedas ?>"
                >

                <button
                    name="dev_moedas"
                >
                    Aplicar moedas
                </button>

            </form>

            <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                <button
                    name="dev_add_moedas"
                >
                    🪙 +100 moedas
                </button>

            </form>

        </section>


        <!-- =============================================
             FASES
        ============================================== -->

        <section class="dev-card dev-card-wide">

            <h2>
                🎮 Ir para fase
            </h2>

            <div class="fase-grid">

                <?php
                foreach (
                    $fases
                    as $valor => $label
                ):
                ?>

                    <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                        <input
                            type="hidden"
                            name="fase"
                            value="<?= eDev($valor) ?>"
                        >

                        <button
                            name="dev_fase"
                            class="<?=
                                $fase === $valor
                                    ? 'ativo'
                                    : ''
                            ?>"
                        >
                            <?= eDev($label) ?>
                        </button>

                    </form>

                <?php endforeach; ?>

            </div>

        </section>


        <!-- =============================================
             PODERES / STATUS
        ============================================== -->

        <section class="dev-card">

            <h2>
                👑 Status
            </h2>

            <?php
            $statusForms = [
                'dev_lider' =>
                    '👑 Definir Líder',

                'dev_anjo' =>
                    '😇 Definir Anjo',

                'dev_imune' =>
                    '🛡️ Definir Imune'
            ];
            ?>

            <?php
            foreach (
                $statusForms
                as $acao => $titulo
            ):
            ?>

                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                    <label>
                        <?= eDev($titulo) ?>
                    </label>

                    <select
                        name="participante"
                        required
                    >

                        <option value="">
                            Escolher participante
                        </option>

                        <?php foreach ($nomes as $nome): ?>

                            <option
                                value="<?= eDev($nome) ?>"
                            >
                                <?= eDev($nome) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <button
                        name="<?= eDev($acao) ?>"
                    >
                        Aplicar
                    </button>

                </form>

            <?php endforeach; ?>

        </section>


        <!-- =============================================
             PAREDÃO
        ============================================== -->

        <section class="dev-card">

            <h2>
                🚨 Paredão
            </h2>

            <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                <select
                    name="participante"
                    required
                >

                    <option value="">
                        Participante
                    </option>

                    <?php foreach ($nomes as $nome): ?>

                        <option
                            value="<?= eDev($nome) ?>"
                        >
                            <?= eDev($nome) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <button
                    name="dev_add_paredao"
                >
                    + Adicionar
                </button>

            </form>


            <div class="paredao-atual">

                <?php
                $paredaoAtual =
                    $_SESSION['paredao']
                    ?? [];
                ?>

                <?php if ($paredaoAtual): ?>

                    <?php foreach ($paredaoAtual as $nome): ?>

                        <span>
                            🚨 <?= eDev($nome) ?>
                        </span>

                    <?php endforeach; ?>

                <?php else: ?>

                    <small>
                        Nenhum participante no Paredão.
                    </small>

                <?php endif; ?>

            </div>


            <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                <button
                    name="dev_limpar_paredao"
                    class="danger"
                >
                    🧹 Limpar Paredão
                </button>

            </form>

        </section>


        <!-- =============================================
             DINÂMICAS SURPRESA 3.0 — TESTE REAL
        ============================================== -->

        <section class="dev-card dev-card-wide" id="dev-dinamicas">
            <h2>🎲 Laboratório de dinâmicas surpresa</h2>
            <p class="dev-help">Força a regra desta semana sem depender de sorteio. Em seguida, avance até a fase correspondente. As ações DEV modificam a temporada aberta: faça um save de teste antes.</p>
            <div class="dev-status-grid">
                <div class="dev-state"><small>Regra atual</small><strong><?= eDev($dsOpcoes[$dsAtiva]['titulo'] ?? 'Nenhuma dinâmica ativa') ?></strong></div>
                <div class="dev-state"><small>Semana</small><strong><?= (int)($dsInfo['rodada'] ?? $rodada) ?></strong></div>
                <div class="dev-state"><small>Imunidade extra</small><strong><?= eDev((int)($_SESSION['ds3_imune']['rodada'] ?? 0) === $rodada ? ($_SESSION['ds3_imune']['nome'] ?? 'Não concedida') : 'Não concedida') ?></strong></div>
                <div class="dev-state"><small>Carta Misteriosa</small><strong><?= eDev((int)($_SESSION['ds3_carta']['rodada'] ?? 0) === $rodada ? ($_SESSION['ds3_carta']['nome'] ?? 'Não entregue') : 'Não entregue') ?></strong></div>
            </div>
            <div class="dev-tool-columns">
                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">
                    <label for="dev_tipo">Escolha a dinâmica que deseja testar</label>
                    <select name="tipo" id="dev_tipo" required>
                        <?php foreach ($dsOpcoes as $codigo => $desc): ?>
                            <option value="<?= eDev($codigo) ?>" <?= $dsAtiva === $codigo ? 'selected' : '' ?>><?= eDev($desc['titulo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button name="dev_dinamica" value="1">🚨 Ativar para esta semana</button>
                </form>
                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">
                    <label>Testar benefício imediatamente</label>
                    <p class="dev-help">Disponível apenas para Imunidade Surpresa e Carta Misteriosa. As outras regras funcionam durante a formação do Paredão.</p>
                    <button name="dev_aplicar_dinamica" value="1" <?= !in_array($dsAtiva, ['imunidade_surpresa','carta_misteriosa'], true) ? 'disabled' : '' ?>>⚡ Conceder efeito agora</button>
                </form>
            </div>
            <form method="POST" class="dev-inline-form">
                <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">
                <button name="dev_limpar_dinamica" value="1" class="danger">🧹 Desativar regra de teste</button>
            </form>
            <div class="dev-tip"><strong>Quando testar:</strong> Votação Aberta, Paredão Especial e Votação Turbinada → Formação do Paredão; Contragolpe → indicação do Líder; Imunidade/Carta → após a Festa ou botão de teste acima. Desativar uma regra não desfaz votos nem Paredões já finalizados.</div>
        </section>

        <section class="dev-card dev-card-wide" id="dev-aliancas">
            <h2>🤝 Laboratório de Alianças 3.0</h2>
            <?php if ($devGrupo === '' || count($devMembros) < 2): ?>
                <p class="dev-help">Seu personagem precisa estar em uma aliança com pelo menos 2 membros para testar crises, lealdade e reuniões. Forme uma aliança no jogo e retorne aqui.</p>
            <?php else: ?>
                <p class="dev-help"><strong><?= eDev($devGrupo) ?></strong> • Lealdade interna estimada dos integrantes (visível apenas no modo DEV):</p>
                <div class="dev-aliados">
                    <?php foreach ($devMembros as $membro): ?>
                    <div class="dev-aliado"><span><?= eDev($membro) ?></span><strong><?= a3Lealdade($jogadores, $membro) ?> / 100</strong></div>
                    <?php endforeach; ?>
                </div>
                <div class="dev-tool-columns">
                    <form method="POST">
                        <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">
                        <label for="dev_membro_crise">Preparar uma crise de lealdade</label>
                        <select name="membro_crise" id="dev_membro_crise" required>
                            <?php foreach ($devCandidatosCrise as $membro): ?>
                            <option value="<?= eDev($membro) ?>"><?= eDev($membro) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button name="dev_crise_alianca" value="1">💥 Gerar tensão interna</button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">
                        <label>Repetir reunião estratégica nesta semana</label>
                        <p class="dev-help">Libera novamente a reunião e leva para Interações 1, sem zerar os acordos anteriores.</p>
                        <button name="dev_reuniao_alianca" value="1">🎙️ Testar reunião</button>
                    </form>
                </div>
                <div class="dev-tip">Crises não forçam um voto nem revelam segredos: afetam a lealdade e liberam cenários para observar a reação dos NPCs.</div>
            <?php endif; ?>
        </section>

        <section class="dev-card dev-card-wide" id="dev-eventos">
            <h2>🎬 Eventos de Convivência</h2>
            <p class="dev-help">Preparar uma cena especial para a próxima visita a Interações ou Festa. A cena continua respeitando as relações e personagens disponíveis.</p>
            <form method="POST" class="dev-inline-form">
                <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">
                <select name="dev_cena_fase" aria-label="Fase da cena especial"><option value="interacoes_1">💬 Interações</option><option value="festa">🎉 Festa</option></select>
                <button name="dev_cena_especial" value="1">🎥 Gerar evento para testar</button>
            </form>
        </section>

        <!-- =============================================
             DINÂMICAS
        ============================================== -->

        <section class="dev-card dev-card-wide">

            <h2>
                ⚡ Dinâmicas especiais
            </h2>

            <div class="acoes-grid">

                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                    <button
                        name="dev_paredao_falso"
                    >
                        🚨 Forçar próximo
                        Paredão Falso
                    </button>

                </form>


                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                    <button
                        name="dev_casa_vidro"
                    >
                        🏠 Preparar Casa de Vidro
                    </button>

                </form>


                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                    <button
                        name="dev_bigfone"
                    >
                        ☎️ Ir para Big Fone
                    </button>

                </form>


                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                    <button
                        name="dev_final"
                        class="especial"
                    >
                        🏆 Simular Grande Final
                    </button>

                </form>

            </div>

        </section>


        <!-- =============================================
             UTILIDADES
        ============================================== -->

        <section class="dev-card dev-card-wide">

            <h2>
                🛠 Utilidades
            </h2>

            <div class="acoes-grid">

                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                    <button
                        name="dev_limpar_ao_vivo"
                    >
                        📣 Limpar Ao Vivo
                    </button>

                </form>


                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                    <button
                        name="dev_limpar_feed"
                    >
                        📱 Limpar Feed BBB
                    </button>

                </form>


                <form method="POST">
                    <input type="hidden" name="dev_token" value="<?= eDev($devToken) ?>">

                    <button
                        name="dev_reset_semana"
                        class="danger"
                    >
                        ♻️ Resetar semana
                    </button>

                </form>


                <a
                    href="jogo.php"
                    class="button-link"
                >
                    🎮 Voltar ao jogo
                </a>

            </div>

        </section>

    </main>

</div>

</body>
</html>