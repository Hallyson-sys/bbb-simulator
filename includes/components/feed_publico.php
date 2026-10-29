<?php

$postsFeed =
    $_SESSION['feed_publico'] ?? [];

$postsFeed =
    array_reverse(
        $postsFeed
    );

$trendingFeed = feed3Trending($jogadores, (int)$rodada, (string)$meuNome);
$movimentosFeed3 = feed3MovimentosAtivos((int)$rodada);
$torcidasFeed3 = array_values((array)($_SESSION['feed3']['torcidas'][(int)$rodada] ?? []));

$ultimoPost =
    $postsFeed[0] ?? null;

?>

<!-- =======================================================
     📱 MODAL COMPLETO
======================================================= -->

<div
    class="feed-publico-modal"
    id="feedPublicoModal"
    aria-hidden="true"
>

    <div
        class="feed-publico-overlay"
        onclick="fecharFeedPublico()"
    ></div>

    <div class="feed-publico-janela">

        <!-- =================================================
             CABEÇALHO
        ================================================== -->

        <header class="feed-publico-header">

            <div>

                <span class="feed-publico-selo">
                    SOCIAL
                </span>

                <h1>
                    📱 Feed BBB
                </h1>

                <p>
                    Veja o que o público está
                    comentando sobre a casa.
                </p>

            </div>

            <button
                type="button"
                class="feed-fechar"
                onclick="fecharFeedPublico()"
                aria-label="Fechar Feed BBB"
            >
                ✕
            </button>

        </header>

        <!-- =================================================
             CONTEÚDO
        ================================================== -->

        <div class="feed-publico-conteudo">

            <!-- =============================================
                 POSTS
            ============================================== -->

            <section class="feed-publico-posts">

                <div class="feed-publico-titulo-area">

                    <span>
                        PARA VOCÊ
                    </span>

                    <strong>
                        <?= count($postsFeed) ?>
                        publicações
                    </strong>

                </div>

                <nav class="feed3-filtros" aria-label="Filtrar publicações do Feed">
                    <button type="button" class="feed3-filtro ativo" data-filtro="todos" aria-pressed="true">🌐 Para você</button>
                    <button type="button" class="feed3-filtro" data-filtro="torcida" aria-pressed="false">📣 Torcidas</button>
                    <button type="button" class="feed3-filtro" data-filtro="mutirao" aria-pressed="false">🗳️ Mutirões</button>
                </nav>
                <p id="feed3SemResultados" class="feed3-sem-resultados" hidden>Não há publicações dessa categoria nesta semana. Novos acontecimentos podem movimentar o Feed.</p>

                <?php if (!empty($postsFeed)): ?>

                    <?php foreach ($postsFeed as $post): ?>

                        <article class="feed-post" data-feed3-tipo="<?= htmlspecialchars((string)($post['feed3_tipo'] ?? 'geral'), ENT_QUOTES, 'UTF-8') ?>">

                            <div class="feed-post-avatar">

                                <?= mb_strtoupper(
                                    mb_substr(
                                        $post['autor'],
                                        0,
                                        1,
                                        'UTF-8'
                                    ),
                                    'UTF-8'
                                ) ?>

                            </div>

                            <div class="feed-post-conteudo">

                                <div class="feed-post-autor">

                                    <div>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $post['autor']
                                            ) ?>
                                        </strong>

                                        <span class="feed-verificado">
                                            ✓
                                        </span>

                                    </div>

                                    <span>

                                        <?= htmlspecialchars(
                                            $post['arroba']
                                        ) ?>

                                        · Semana

                                        <?= (int)
                                            $post['rodada']
                                        ?>

                                    </span>

                                </div>

                                <p class="feed-post-texto">

                                    <?= htmlspecialchars(
                                        function_exists('ajustarGeneroTextoComElencoBBB')
                                            ? ajustarGeneroTextoComElencoBBB(
                                                $post['texto'],
                                                $jogadores ?? ($_SESSION['jogadores'] ?? [])
                                            )
                                            : $post['texto']
                                    ) ?>

                                </p>

                                <?php if (!empty($post['feed3_tags']) && is_array($post['feed3_tags'])): ?>
                                    <div class="feed3-tags">
                                        <?php foreach ($post['feed3_tags'] as $tag): ?>
                                            <span><?= htmlspecialchars((string)$tag, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="feed-post-acoes">

                                    <span>
                                        💬
                                        <?= formatarNumeroFeed(
                                            $post[
                                                'comentarios'
                                            ]
                                        ) ?>
                                    </span>

                                    <span>
                                        🔁
                                        <?= formatarNumeroFeed(
                                            $post[
                                                'reposts'
                                            ]
                                        ) ?>
                                    </span>

                                    <span>
                                        ❤️
                                        <?= formatarNumeroFeed(
                                            $post[
                                                'curtidas'
                                            ]
                                        ) ?>
                                    </span>

                                    <span>
                                        📊
                                    </span>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="feed-vazio">

                        <div>
                            📡
                        </div>

                        <h3>
                            O público está observando...
                        </h3>

                        <p>
                            As primeiras opiniões vão
                            aparecer conforme a temporada
                            avança.
                        </p>

                    </div>

                <?php endif; ?>

            </section>

            <!-- =============================================
                 TRENDING
            ============================================== -->

            <aside class="feed-publico-trending">

                <div class="feed3-painel">
                    <div class="feed3-painel-titulo">📣 Torcidas em movimento <span>SEMANA <?= (int)$rodada ?></span></div>
                    <p>As torcidas comentam acontecimentos públicos da casa. A movimentação não indica quem está à frente na votação.</p>
                    <?php if ($torcidasFeed3): ?>
                        <div class="feed3-torcidas">
                            <?php foreach (array_slice($torcidasFeed3, -4) as $torcida): ?>
                                <div class="feed3-torcida">
                                    <strong><?= htmlspecialchars((string)$torcida['nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars((string)$torcida['tag'], ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="feed3-espera">👀 As torcidas começam a se organizar conforme a edição avança.</p>
                    <?php endif; ?>
                </div>

                <div class="feed3-painel">
                    <div class="feed3-painel-titulo">🗳️ Central de Mutirões</div>
                    <?php if ($movimentosFeed3): ?>
                        <p>Com o Paredão formado, campanhas para ficar e sair começam a circular nas redes.</p>
                        <?php foreach ($movimentosFeed3 as $movimento): ?>
                            <div class="feed3-mutirao">
                                <strong><?= htmlspecialchars((string)$movimento['nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <div class="feed3-dupla-tags">
                                    <span><?= htmlspecialchars((string)$movimento['ficar'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span><?= htmlspecialchars((string)$movimento['sair'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="feed3-espera">⏳ Os mutirões aparecem quando o Paredão desta semana estiver formado.</p>
                    <?php endif; ?>
                </div>

                <div class="trending-card">

                    <div class="trending-cabecalho">

                        <span>
                            🔥
                        </span>

                        <div>

                            <small>
                                BRASIL · REALITY SHOW
                            </small>

                            <h2>
                                Assuntos do momento
                            </h2>

                        </div>

                    </div>

                    <div class="trending-lista">

                        <?php
                        foreach (
                            $trendingFeed
                            as $indice => $topic
                        ):
                        ?>

                            <div class="trending-item">

                                <span class="trending-posicao">

                                    <?= $indice + 1 ?>

                                </span>

                                <div>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $topic['tag']
                                        ) ?>
                                    </strong>

                                    <small>

                                        <?= formatarNumeroFeed(
                                            $topic['posts']
                                        ) ?>

                                        posts

                                    </small>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

                <div class="feed-info-card">

                    <span>
                        📺
                    </span>

                    <div>

                        <strong>
                            O Brasil está de olho
                        </strong>

                        <p>
                            Provas, alianças, rivalidades,
                            romances e acontecimentos da
                            casa influenciam o que aparece aqui.
                        </p>

                    </div>

                </div>

            </aside>

        </div>

    </div>

</div>
