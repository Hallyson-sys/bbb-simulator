<?php

/* =========================================================
   🎥 LÓGICA DO CONFESSIONÁRIO
   ========================================================= */

   /* =========================
   🎥 CONFESSIONÁRIO INTELIGENTE
   Gera falas com base em afinidade, rivalidade, confiança, romance e personalidade.
========================= */

function escolherFalaNaoRepetida($opcoes, $historico)
{

    if (empty($opcoes)) {
        return "";
    }

    if (!is_array($historico)) {
        $historico = [];
    }

    $historicoLimpo = array_map(function ($fala) {
        return trim(strip_tags((string)$fala));
    }, $historico);

    shuffle($opcoes);

    foreach ($opcoes as $fala) {
        $falaLimpa = trim(strip_tags((string)$fala));

        if (!in_array($falaLimpa, $historicoLimpo)) {
            return $fala;
        }
    }

    /* Se já usou todas, permite repetir para não travar o jogo */
    return $opcoes[array_rand($opcoes)];
}

/* =========================
   🎥 CONFESSIONÁRIO INTELIGENTE TURBINADO
   Mais categorias + sistema anti-repetição.
========================= */

function gerarConfessionarioInteligente(&$jogadores, $meuNome)
{

    $falas = [];

    atualizarRelacoesMarcantes($jogadores);

    foreach ($jogadores as $jogador) {

        $nome = $jogador['nome'] ?? '';
        if ($nome == '') continue;

        $personalidade = $jogador['personalidade'] ?? 'Neutro';
        $perfil = perfilPersonalidadeCompleto($personalidade);

        $historicoConfessionario = $jogador['confessionarios'] ?? [];

        $melhorAliado = null;
        $melhorAliadoScore = -999;
        $maiorRival = null;
        $maiorRivalScore = -999;
        $maiorRomance = null;
        $maiorRomanceScore = -999;
        $alvoEstrategico = null;
        $alvoEstrategicoScore = -999;
        $pessoaFalsa = null;
        $pessoaFalsaScore = -999;
        $pessoaDecepcao = null;
        $pessoaDecepcaoScore = -999;

        foreach ($jogadores as $alvo) {

            $nomeAlvo = $alvo['nome'] ?? '';
            if ($nomeAlvo == '' || $nomeAlvo == $nome) continue;

            $rel = obterRelacaoCompleta($jogadores, $nome, $nomeAlvo, $meuNome);
            $popularidadeAlvo = $alvo['popularidade'] ?? 50;
            $romance = obterRomance($jogadores, $nome, $nomeAlvo);

            $scoreAliado = $rel['amizade'] + $rel['confianca'] - $rel['rivalidade'];
            $scoreRival = $rel['rivalidade'] + (100 - $rel['amizade']) - $rel['confianca'];
            $scoreEstrategico = (100 - $popularidadeAlvo) + $rel['rivalidade'] - ($rel['confianca'] * 0.4);
            $scoreFalsidade = $rel['rivalidade'] + (100 - $rel['confianca']);
            $scoreDecepcao = (100 - $rel['confianca']) + max(0, 40 - $rel['amizade']) + $rel['rivalidade'];

            if ($scoreAliado > $melhorAliadoScore) {
                $melhorAliadoScore = $scoreAliado;
                $melhorAliado = $nomeAlvo;
            }

            if ($scoreRival > $maiorRivalScore) {
                $maiorRivalScore = $scoreRival;
                $maiorRival = $nomeAlvo;
            }

            if ($romance > $maiorRomanceScore) {
                $maiorRomanceScore = $romance;
                $maiorRomance = $nomeAlvo;
            }

            if ($scoreEstrategico > $alvoEstrategicoScore) {
                $alvoEstrategicoScore = $scoreEstrategico;
                $alvoEstrategico = $nomeAlvo;
            }

            if ($scoreFalsidade > $pessoaFalsaScore) {
                $pessoaFalsaScore = $scoreFalsidade;
                $pessoaFalsa = $nomeAlvo;
            }

            if ($scoreDecepcao > $pessoaDecepcaoScore) {
                $pessoaDecepcaoScore = $scoreDecepcao;
                $pessoaDecepcao = $nomeAlvo;
            }
        }

        $tiposPossiveis = [];

        if ($maiorRomanceScore >= 30 && $maiorRomance) {
            $tiposPossiveis = array_merge($tiposPossiveis, array_fill(0, max(1, (int)(($perfil['romance'] ?? 50) / 18)), 'romance'));
        }

        if ($maiorRivalScore >= 75 && $maiorRival) {
            $tiposPossiveis = array_merge($tiposPossiveis, array_fill(0, max(1, (int)(($perfil['treta'] ?? 50) / 18)), 'rival'));
            $tiposPossiveis[] = 'vinganca';
        }

        if ($melhorAliadoScore >= 65 && $melhorAliado) {
            $tiposPossiveis = array_merge($tiposPossiveis, array_fill(0, max(1, (int)(($perfil['alianca'] ?? 50) / 18)), 'aliado'));
        }

        if ($pessoaFalsaScore >= 85 && $pessoaFalsa) {
            $tiposPossiveis[] = 'falsidade';
        }

        if ($pessoaDecepcaoScore >= 90 && $pessoaDecepcao) {
            $tiposPossiveis[] = 'decepcao';
        }

        if ($alvoEstrategico != null && ($personalidade == 'Estrategista' || $personalidade == 'Manipulador' || $personalidade == 'Falso' || ($perfil['alianca'] ?? 0) >= 80)) {
            $tiposPossiveis[] = 'estrategia';
        }

        if (($perfil['vt'] ?? 50) >= 45) {
            $tiposPossiveis[] = 'vt';
        }

        if (($perfil['emocao'] ?? 50) >= 55) {
            $tiposPossiveis[] = 'medo_paredao';
            $tiposPossiveis[] = 'desabafo';
        }

        $tiposPossiveis[] = 'observacao';
        $tiposPossiveis[] = 'sonho_vitoria';
        $tiposPossiveis[] = 'neutro';

        $tipo = $tiposPossiveis[array_rand($tiposPossiveis)];

        if ($tipo == 'romance' && $maiorRomance) {
            $opcoes = [
                "💘 <b>$nome</b>: \"Eu tento disfarçar, mas está ficando difícil esconder que eu tenho um carinho diferente por $maiorRomance.\"",
                "💕 <b>$nome</b>: \"Quando $maiorRomance chega perto, eu esqueço por alguns segundos que isso aqui também é um jogo.\"",
                "😍 <b>$nome</b>: \"Não sei se é só convivência ou se está virando sentimento, mas $maiorRomance mexe comigo.\"",
                "🌙 <b>$nome</b>: \"Às vezes uma conversa boba com $maiorRomance muda meu dia inteiro aqui dentro.\"",
                "🫶 <b>$nome</b>: \"Eu não quero me precipitar, mas minha conexão com $maiorRomance está ficando cada vez mais forte.\"",
                "💗 <b>$nome</b>: \"Se isso é estratégia ou sentimento, eu ainda não sei. Só sei que $maiorRomance virou alguém especial.\""
            ];

            alterarPopularidadeMotivo($jogadores, $nome, 0, 3, "rendeu um momento romântico no confessionário", false);
        } elseif ($tipo == 'rival' && $maiorRival) {
            $opcoes = [
                "🐍 <b>$nome</b>: \"Eu não consigo confiar em $maiorRival. Tem alguma coisa ali que não me passa verdade.\"",
                "🔥 <b>$nome</b>: \"Se eu ganhar poder nessa casa, $maiorRival precisa se preocupar.\"",
                "🎯 <b>$nome</b>: \"Meu alvo hoje tem nome: $maiorRival. Não vou fingir que está tudo bem.\"",
                "😤 <b>$nome</b>: \"Toda vez que $maiorRival fala, eu sinto que tem um jogo escondido por trás.\"",
                "⚡ <b>$nome</b>: \"Minha paciência com $maiorRival está acabando, e eu acho que a casa já percebeu.\"",
                "🧨 <b>$nome</b>: \"Eu estou tentando manter a calma, mas $maiorRival sabe exatamente como me tirar do sério.\""
            ];

            alterarPopularidadeMotivo($jogadores, $nome, -2, 4, "foi direto no confessionário sobre um rival", false);
        } elseif ($tipo == 'aliado' && $melhorAliado) {
            $opcoes = [
                "🤝 <b>$nome</b>: \"Eu confio muito em $melhorAliado. É alguém que eu quero levar longe no jogo.\"",
                "❤️ <b>$nome</b>: \"$melhorAliado é uma das pessoas que mais me passa segurança aqui dentro.\"",
                "🛡️ <b>$nome</b>: \"Se depender de mim, $melhorAliado não vai sozinho nessa casa.\"",
                "👥 <b>$nome</b>: \"Minha troca com $melhorAliado é muito verdadeira. Aqui dentro isso vale ouro.\"",
                "🔐 <b>$nome</b>: \"Eu conto coisas para $melhorAliado que não conto para mais ninguém nessa casa.\"",
                "🌟 <b>$nome</b>: \"Ter $melhorAliado por perto me deixa mais forte para enfrentar o jogo.\""
            ];

            alterarAfinidade($jogadores, $nome, $melhorAliado, 3, -1, 4);
            alterarAfinidade($jogadores, $melhorAliado, $nome, 2, -1, 3);
        } elseif ($tipo == 'estrategia' && $alvoEstrategico) {
            $opcoes = [
                "🧠 <b>$nome</b>: \"Eu estou observando $alvoEstrategico. Às vezes, eliminar a pessoa certa muda o jogo inteiro.\"",
                "🎮 <b>$nome</b>: \"Aqui ninguém sobrevive só sendo legal. Eu preciso pensar nos próximos passos, e $alvoEstrategico está no meu radar.\"",
                "👀 <b>$nome</b>: \"Tem gente que acha que eu não percebo, mas eu estou calculando tudo. $alvoEstrategico pode virar alvo.\"",
                "♟️ <b>$nome</b>: \"Esse jogo é como xadrez. Se eu mexer uma peça errada, posso cair junto.\"",
                "📌 <b>$nome</b>: \"Meu plano agora é parecer tranquilo enquanto observo quem está se aproximando de quem.\"",
                "🗺️ <b>$nome</b>: \"Eu já estou pensando duas rodadas à frente. Quem não fizer isso vai ser engolido pelo jogo.\""
            ];
        } elseif ($tipo == 'falsidade' && $pessoaFalsa) {
            $opcoes = [
                "🐍 <b>$nome</b>: \"Tem gente aqui que fala uma coisa na minha frente e outra pelas costas. $pessoaFalsa me deixa com um pé atrás.\"",
                "🎭 <b>$nome</b>: \"Algumas máscaras estão começando a cair, e a de $pessoaFalsa está escorregando.\"",
                "👀 <b>$nome</b>: \"Eu percebo os olhares, os cochichos e as mudanças de postura. $pessoaFalsa não me engana tanto assim.\"",
                "🕵️ <b>$nome</b>: \"Eu ainda não tenho certeza, mas sinto que $pessoaFalsa joga dos dois lados.\"",
                "😒 <b>$nome</b>: \"O problema não é jogar. O problema é fingir que não joga. E $pessoaFalsa faz muito isso.\""
            ];
        } elseif ($tipo == 'vinganca' && $maiorRival) {
            $opcoes = [
                "⚔️ <b>$nome</b>: \"Eu não esqueço quem tentou me prejudicar. $maiorRival ainda vai ouvir meu nome nessa casa.\"",
                "🎯 <b>$nome</b>: \"Se eu ganhar poder, algumas contas vão ser cobradas, principalmente com $maiorRival.\"",
                "🔥 <b>$nome</b>: \"$maiorRival acha que eu esqueci. Não esqueci. Só estou esperando o momento certo.\"",
                "💣 <b>$nome</b>: \"Eu posso até sorrir na sala, mas no jogo eu sei exatamente quem me feriu.\"",
                "🧊 <b>$nome</b>: \"A melhor resposta para $maiorRival vai ser no jogo, não no grito.\""
            ];
        } elseif ($tipo == 'decepcao' && $pessoaDecepcao) {
            $opcoes = [
                "💔 <b>$nome</b>: \"Eu esperava mais de $pessoaDecepcao. Talvez eu tenha confiado rápido demais.\"",
                "😞 <b>$nome</b>: \"Confiei em quem não deveria, e isso está começando a pesar.\"",
                "🥀 <b>$nome</b>: \"Algumas alianças não eram tão verdadeiras quanto pareciam. $pessoaDecepcao me mostrou isso.\"",
                "🌧️ <b>$nome</b>: \"O pior não é ser votado. É sentir que alguém que você protegia não faria o mesmo por você.\"",
                "🫤 <b>$nome</b>: \"Eu não estou com raiva, estou decepcionado. E isso às vezes é pior.\""
            ];
        } elseif ($tipo == 'medo_paredao') {
            $opcoes = [
                "😰 <b>$nome</b>: \"Eu tento parecer tranquilo, mas estou sentindo que meu nome está circulando pela casa.\"",
                "🚨 <b>$nome</b>: \"Essa semana pode mudar tudo. Estou com medo de bater no paredão.\"",
                "😟 <b>$nome</b>: \"Não sei em quem confiar. Qualquer voto pode cair em mim.\"",
                "🫣 <b>$nome</b>: \"Às vezes eu entro no quarto e sinto que a conversa muda. Isso me deixa alerta.\"",
                "⏳ <b>$nome</b>: \"A pior parte é esperar. Você nunca sabe se está seguro de verdade.\"",
                "🫥 <b>$nome</b>: \"Eu sinto que estou pisando em ovos. Qualquer escolha errada pode me colocar no paredão.\""
            ];
        } elseif ($tipo == 'desabafo') {
            $opcoes = [
                "😭 <b>$nome</b>: \"Hoje bateu um cansaço. Essa casa exige muito da cabeça da gente.\"",
                "💭 <b>$nome</b>: \"Tem dias em que eu me sinto forte, e tem dias em que eu só queria respirar sem pensar em voto.\"",
                "🏠 <b>$nome</b>: \"A saudade de casa aparece do nada, principalmente quando o clima pesa aqui dentro.\"",
                "🌫️ <b>$nome</b>: \"Eu estou tentando não me perder no meio de tanta estratégia, treta e julgamento.\"",
                "🤍 <b>$nome</b>: \"Eu entrei achando que seria só jogo, mas aqui tudo fica intenso muito rápido.\""
            ];
        } elseif ($tipo == 'vt') {
            $opcoes = [
                "📺 <b>$nome</b>: \"Eu vim para jogar, me entregar e aparecer. Quem não quer ser visto nem deveria estar aqui.\"",
                "✨ <b>$nome</b>: \"O público ainda vai entender meu jeito. Eu sei que posso crescer muito nessa casa.\"",
                "🎥 <b>$nome</b>: \"Cada dia aqui é uma chance de mostrar quem eu sou de verdade.\"",
                "😎 <b>$nome</b>: \"Eu não nasci para passar despercebido. Se a câmera me procura, eu entrego.\"",
                "🌟 <b>$nome</b>: \"Tem gente que chama de VT. Eu chamo de protagonismo.\"",
                "🔥 <b>$nome</b>: \"Se for para jogar, eu vou jogar aparecendo. Planta eu não sou.\""
            ];

            alterarPopularidadeMotivo($jogadores, $nome, -2, 5, "tentou se vender bem no confessionário", false);
        } elseif ($tipo == 'observacao') {
            $opcoes = [
                "☕ <b>$nome</b>: \"A cozinha está mais movimentada que o jogo hoje. É ali que muita coisa nasce.\"",
                "🛏️ <b>$nome</b>: \"Tem gente dormindo enquanto o jogo acontece. Depois não entende quando vira alvo.\"",
                "🎉 <b>$nome</b>: \"A festa mostrou lados que eu ainda não conhecia de algumas pessoas.\"",
                "👀 <b>$nome</b>: \"Eu observo muito mais do que as pessoas imaginam.\"",
                "🛋️ <b>$nome</b>: \"Na sala todo mundo sorri, mas eu sinto tensão no ar.\"",
                "🚿 <b>$nome</b>: \"Até conversa de banheiro aqui pode virar estratégia. Nada passa despercebido.\""
            ];
        } elseif ($tipo == 'sonho_vitoria') {
            $opcoes = [
                "🏆 <b>$nome</b>: \"Eu me imagino chegando na final todos os dias. Esse sonho me mantém firme.\"",
                "✨ <b>$nome</b>: \"Não entrei aqui para ser coadjuvante. Quero deixar minha marca.\"",
                "🎯 <b>$nome</b>: \"Meu objetivo continua o mesmo: vencer. Todo o resto é caminho.\"",
                "👑 <b>$nome</b>: \"Eu sei que ainda falta muito, mas eu consigo me ver no último dia.\"",
                "💫 <b>$nome</b>: \"Se eu sobreviver às próximas semanas, ninguém mais me segura.\"",
                "📣 <b>$nome</b>: \"Eu quero que o público olhe para mim e veja alguém que merece chegar longe.\""
            ];
        } else {
            $opcoes = [
                "😶 <b>$nome</b>: \"Hoje eu prefiro observar. Às vezes, o silêncio entrega mais do que uma briga.\"",
                "💭 <b>$nome</b>: \"Essa casa muda muito rápido. Quem é aliado hoje pode ser rival amanhã.\"",
                "🏠 <b>$nome</b>: \"Eu estou tentando entender meu lugar no jogo sem me perder no caminho.\"",
                "🌪️ <b>$nome</b>: \"Quando eu acho que entendi a casa, alguma coisa muda de novo.\"",
                "🧩 <b>$nome</b>: \"Cada pessoa aqui é uma peça diferente. O difícil é descobrir onde cada uma se encaixa.\"",
                "🕯️ <b>$nome</b>: \"Tem horas que o melhor movimento é não se mexer.\""
            ];
        }

        $fala = escolherFalaNaoRepetida($opcoes, $historicoConfessionario);

        if ($fala == '') {
            continue;
        }

        $falas[] = $fala;

        foreach ($jogadores as &$jAtualizar) {
            if (($jAtualizar['nome'] ?? '') == $nome) {
                if (!isset($jAtualizar['confessionarios']) || !is_array($jAtualizar['confessionarios'])) {
                    $jAtualizar['confessionarios'] = [];
                }

                $jAtualizar['confessionarios'][] = strip_tags($fala);

                aplicarImpactoPublicoConfessionario($jogadores, $nome, $tipo);

                /* Evita deixar a sessão enorme com muitas rodadas */
                if (count($jAtualizar['confessionarios']) > 30) {
                    $jAtualizar['confessionarios'] = array_slice($jAtualizar['confessionarios'], -30);
                }

                break;
            }
        }
        unset($jAtualizar);
    }

    shuffle($falas);

    return $falas;
}
function prepararConfessionarioDaRodada(&$jogadores, $meuNome)
{

    if (isset($_SESSION['confessionario_feito'])) return;

    $_SESSION['confessionario_falas'] = gerarConfessionarioInteligente($jogadores, $meuNome);
    $_SESSION['confessionario_feito'] = true;
    $_SESSION['jogadores'] = $jogadores;

    /* O Confessionário deve aparecer apenas na tela da fase confessionario,
       então não enviamos mensagem para o Ao Vivo aqui. */
}