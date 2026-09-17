<?php

$postsFeed =
    $_SESSION['feed_publico'] ?? [];

$postsFeed =
    array_reverse(
        $postsFeed
    );

    $trendingFeed =
    function_exists('gerarTrendingTopicsFeedInteligente')
        ? gerarTrendingTopicsFeedInteligente(
            $jogadores,
            $rodada,
            $meuNome
        )
        : gerarTrendingTopicsFeed(
            $jogadores,
            $rodada,
            $meuNome
        );

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


                <?php if (!empty($postsFeed)): ?>

                    <?php foreach ($postsFeed as $post): ?>

                        <article class="feed-post">

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

                                        · Rodada

                                        <?= (int)
                                            $post['rodada']
                                        ?>

                                    </span>

                                </div>


                                <p class="feed-post-texto">

                                    <?= htmlspecialchars(
                                        $post['texto']
                                    ) ?>

                                </p>


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
                            Popularidade, alianças,
                            rivalidades e acontecimentos
                            da casa influenciam o que
                            aparece aqui.
                        </p>

                    </div>

                </div>

            </aside>

        </div>

    </div>

</div>