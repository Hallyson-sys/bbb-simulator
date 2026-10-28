<?php

/* =========================================================
   🎥 LÓGICA DO CONFESSIONÁRIO
   ========================================================= */

   /* =========================
   🎥 CONFESSIONÁRIO INTELIGENTE
   Gera falas com base em afinidade, rivalidade, confiança, romance e personalidade.
========================= */

function falaNormalizadaConf($fala): string {
    $texto = trim(strip_tags((string)$fala));
    // Desconsiderar emoji e nome antes dos dois-pontos: a frase é o que importa.
    $texto = preg_replace('/^.*?:\s*["“]?/u', '', $texto, 1);
    return strtolower(trim(preg_replace('/\s+/u', ' ', $texto)));
}

function escolherFalaNaoRepetida($opcoes, $historico, $falasNestaSessao = [])
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

    $jaUsadas = array_map('falaNormalizadaConf', $falasNestaSessao);
    shuffle($opcoes);
    foreach ($opcoes as $fala) {
        $normal = falaNormalizadaConf($fala);
        if (!in_array($normal, $jaUsadas, true) &&
            !in_array(trim(strip_tags((string)$fala)), $historicoLimpo, true)) return $fala;
    }
    // Repetição de uma mesma pessoa em semanas diferentes é menos grave do que
    // duplicar a MESMA frase entre dois NPCs na mesma sessão.
    foreach ($opcoes as $fala) {
        if (!in_array(falaNormalizadaConf($fala), $jaUsadas, true)) return $fala;
    }
    return '';
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

        // Temas cotidianos e estratégicos independentes de segredos.
        foreach (['rotina','limites','saudade','responsabilidade','festa','isolamento',
                  'foco_provas','convivencia','autocritica','incerteza','coragem','expectativas'] as $extra) {
            $tiposPossiveis[]=$extra;
        }
        if($melhorAliado && $melhorAliadoScore>=65)$tiposPossiveis[]='protecao';
        if($maiorRival && $maiorRivalScore>=75)$tiposPossiveis[]='tensao_dia';
        if(($nome===($_SESSION['lider']??'')))$tiposPossiveis[]='peso_lider';
        if(($nome===($_SESSION['anjo']??'')))$tiposPossiveis[]='peso_anjo';
        $tiposPossiveis[] = 'observacao';
        $tiposPossiveis[] = 'sonho_vitoria';
        $tiposPossiveis[] = 'neutro';

        // Evita repetir o mesmo assunto nas últimas três aparições daquele NPC.
        $temasAnteriores=(array)($_SESSION['conf4_temas'][$nome]??[]);
        $disponiveis=array_values(array_diff($tiposPossiveis,array_slice($temasAnteriores,-3)));
        if(!$disponiveis)$disponiveis=$tiposPossiveis;
        $tipo=$disponiveis[array_rand($disponiveis)];
        $_SESSION['conf4_temas'][$nome]=array_slice(array_merge($temasAnteriores,[$tipo]),-6);


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
        } elseif ($tipo == 'rotina') {
            $opcoes = [
                "☕ <b>$nome</b>: \"A cozinha parece pequena quando a gente precisa dividir tudo. Às vezes uma louça fora do lugar já vira discussão.\"",
                "☕ <b>$nome</b>: \"Hoje eu fiquei pensando como as coisas mais simples revelam o jeito de cada um.\"",
                "☕ <b>$nome</b>: \"Quando eu preciso de silêncio e a casa inteira resolve conversar, lembro que esse jogo também é convivência.\"",
                "☕ <b>$nome</b>: \"Eu estou aprendendo a dividir espaço com pessoas completamente diferentes de mim.\"",
                "☕ <b>$nome</b>: \"É estranho como uma conversa na mesa pode mudar o clima de um dia inteiro.\""
            ];
        } elseif ($tipo == 'limites') {
            $opcoes = [
                "🧊 <b>$nome</b>: \"Eu não quero brigar por qualquer coisa, mas também não vou deixar passarem por cima de mim.\"",
                "🧊 <b>$nome</b>: \"Tem uma linha entre ser flexível e aceitar algo que te machuca. Estou tentando descobrir onde fica a minha.\"",
                "🧊 <b>$nome</b>: \"Falar de boa não é ser fraco. Às vezes é a maneira mais difícil de resolver as coisas.\"",
                "🧊 <b>$nome</b>: \"Nem sempre responder na hora é o melhor. Estou tentando escolher minhas batalhas.\"",
                "🧊 <b>$nome</b>: \"Eu quero defender meu espaço sem transformar a casa numa guerra.\""
            ];
        } elseif ($tipo == 'saudade') {
            $opcoes = [
                "🏠 <b>$nome</b>: \"À noite a saudade aperta mais. Você deita e lembra que todo mundo lá fora continua vivendo.\"",
                "🏠 <b>$nome</b>: \"Hoje eu queria contar pra alguém de casa como foi meu dia. Parece bobagem, mas faz falta.\"",
                "🏠 <b>$nome</b>: \"Uma música na Festa me pegou de surpresa. Por alguns segundos esqueci que estava num jogo.\"",
                "🏠 <b>$nome</b>: \"Tem horas que eu sinto falta de coisas tão pequenas... Até do silêncio do meu quarto.\"",
                "🏠 <b>$nome</b>: \"Não é só ganhar prova. É aguentar os dias em que o coração pede pra estar em outro lugar.\""
            ];
        } elseif ($tipo == 'responsabilidade') {
            $opcoes = [
                "⚖️ <b>$nome</b>: \"Estou tentando ser justo com as pessoas sem esquecer que todo mundo precisa votar.\"",
                "⚖️ <b>$nome</b>: \"Eu queria que minhas atitudes falassem mais alto que minhas promessas.\"",
                "⚖️ <b>$nome</b>: \"Às vezes eu fico pensando se fui duro demais naquela conversa. Aqui uma frase pesa muito.\"",
                "⚖️ <b>$nome</b>: \"Eu não quero prometer proteção que talvez eu não consiga cumprir.\"",
                "⚖️ <b>$nome</b>: \"Assumir uma decisão é mais difícil do que explicar ela depois.\""
            ];
        } elseif ($tipo == 'festa') {
            $opcoes = [
                "🎉 <b>$nome</b>: \"Eu precisava dançar e rir um pouco. Por umas horas o clima de votação saiu da cabeça.\"",
                "🎉 <b>$nome</b>: \"A Festa junta gente que normalmente nem se olha, e isso muda muita coisa.\"",
                "🎉 <b>$nome</b>: \"Ontem uma conversa leve me fez lembrar que nem tudo é estratégia.\"",
                "🎉 <b>$nome</b>: \"Durante a Festa eu consegui relaxar, mas percebi que ninguém desliga totalmente do jogo.\"",
                "🎉 <b>$nome</b>: \"A música acaba, mas as conversas ficam na cabeça da gente por muito tempo.\""
            ];
        } elseif ($tipo == 'isolamento') {
            $opcoes = [
                "🌧️ <b>$nome</b>: \"Hoje eu me senti meio de fora de algumas conversas. Não sei se foi impressão minha.\"",
                "🌧️ <b>$nome</b>: \"Às vezes eu estou cercado de gente e mesmo assim sinto uma solidão danada.\"",
                "🌧️ <b>$nome</b>: \"Não estou dizendo que alguém me excluiu. Só que tem dias que parece difícil encontrar meu lugar.\"",
                "🌧️ <b>$nome</b>: \"Eu queria me aproximar de mais gente sem parecer que é tudo interesse.\"",
                "🌧️ <b>$nome</b>: \"O silêncio entre duas pessoas pode dizer mais que uma discussão inteira.\""
            ];
        } elseif ($tipo == 'foco_provas') {
            $opcoes = [
                "🏆 <b>$nome</b>: \"Na próxima prova eu quero ir além. Não dá pra controlar a sorte, mas dá pra me preparar.\"",
                "🏆 <b>$nome</b>: \"Perder mexe comigo. Estou tentando não deixar um resultado estragar a semana toda.\"",
                "🏆 <b>$nome</b>: \"Vejo gente que encara prova como brincadeira. Pra mim é chance real de mudar a semana.\"",
                "🏆 <b>$nome</b>: \"Eu treino na cabeça mesmo quando não tem desafio: tento imaginar como vou reagir sob pressão.\"",
                "🏆 <b>$nome</b>: \"Quero descobrir se consigo manter a calma quando o cronômetro começar.\""
            ];
        } elseif ($tipo == 'convivencia') {
            $opcoes = [
                "💬 <b>$nome</b>: \"Hoje uma conversa curta me deixou pensando. Tem gente aqui que eu ainda não conheço direito.\"",
                "💬 <b>$nome</b>: \"O jeito que cada um reage a um problema diz muito mais do que qualquer discurso.\"",
                "💬 <b>$nome</b>: \"Eu vim pra competir, mas seria mentira dizer que não me apeguei a algumas pessoas.\"",
                "💬 <b>$nome</b>: \"Parece que um dia aqui rende uma semana inteira de sentimentos.\"",
                "💬 <b>$nome</b>: \"Estou tentando ouvir mais, mesmo quando não concordo com tudo.\""
            ];
        } elseif ($tipo == 'autocritica') {
            $opcoes = [
                "🔄 <b>$nome</b>: \"Já falei coisas que, pensando melhor, poderia ter dito de outro jeito.\"",
                "🔄 <b>$nome</b>: \"Eu não gosto de admitir erro, mas estou revendo algumas decisões minhas.\"",
                "🔄 <b>$nome</b>: \"Quando as coisas dão errado, minha primeira reação é procurar um culpado. Estou tentando mudar isso.\"",
                "🔄 <b>$nome</b>: \"Não quero que orgulho me impeça de pedir desculpas quando for necessário.\"",
                "🔄 <b>$nome</b>: \"Às vezes minha leitura da casa falha. Tudo bem, preciso aprender com isso.\""
            ];
        } elseif ($tipo == 'incerteza') {
            $opcoes = [
                "❔ <b>$nome</b>: \"Não sei exatamente onde eu estou nessa casa hoje. E talvez ninguém saiba.\"",
                "❔ <b>$nome</b>: \"As conversas mudam tão rápido que um plano de manhã pode não servir à noite.\"",
                "❔ <b>$nome</b>: \"Eu fico tentando juntar as peças, mas falta informação e não vou fingir certeza.\"",
                "❔ <b>$nome</b>: \"Tenho medo de interpretar tudo errado e afastar quem realmente está comigo.\"",
                "❔ <b>$nome</b>: \"Estou começando a aceitar que nem sempre vou ter todas as respostas.\""
            ];
        } elseif ($tipo == 'coragem') {
            $opcoes = [
                "🔥 <b>$nome</b>: \"Não quero chegar ao final pensando que tive medo de me posicionar.\"",
                "🔥 <b>$nome</b>: \"Coragem pra mim não é gritar mais alto. É bancar as próprias escolhas.\"",
                "🔥 <b>$nome</b>: \"Eu posso até perder uma prova, mas não quero me perder de mim mesmo.\"",
                "🔥 <b>$nome</b>: \"Hoje eu precisei respirar fundo antes de tomar uma atitude. Foi difícil.\"",
                "🔥 <b>$nome</b>: \"Sei que algumas decisões vão desagradar, mas fazer nada também é uma escolha.\""
            ];
        } elseif ($tipo == 'expectativas') {
            $opcoes = [
                "🌟 <b>$nome</b>: \"Entrei aqui imaginando uma coisa e a convivência está me mostrando outra completamente diferente.\"",
                "🌟 <b>$nome</b>: \"Eu pensava que conseguiria separar sentimento e voto com facilidade. Não é tão simples.\"",
                "🌟 <b>$nome</b>: \"Às vezes tenho expectativas grandes demais das pessoas e acabo me frustrando.\"",
                "🌟 <b>$nome</b>: \"Não sei se o público vai entender todas as minhas escolhas, mas eu preciso fazer o que acredito.\"",
                "🌟 <b>$nome</b>: \"Eu queria sair daqui sentindo que vivi de verdade, não só que calculei cada passo.\""
            ];
        } elseif ($tipo == 'protecao') {
            $opcoes = [
                "🤝 <b>$nome</b>: \"Com $melhorAliado eu sinto que consigo conversar sem estar sempre me defendendo.\"",
                "🤝 <b>$nome</b>: \"Eu gosto muito de $melhorAliado, mas sei que uma aliança exige atitudes, não só palavras.\"",
                "🤝 <b>$nome</b>: \"Se $melhorAliado precisar de mim, vou tentar ajudar. Só não quero prometer algo impossível.\"",
                "🤝 <b>$nome</b>: \"Eu espero não ter que escolher entre proteger $melhorAliado e me proteger.\"",
                "🤝 <b>$nome</b>: \"O que construí com $melhorAliado foi aos poucos. Não quero jogar isso fora por impulso.\""
            ];
        } elseif ($tipo == 'tensao_dia') {
            $opcoes = [
                "🔥 <b>$nome</b>: \"Quando $maiorRival chega perto, eu fico desconfortável. Não sei se vamos nos entender tão cedo.\"",
                "🔥 <b>$nome</b>: \"Eu não quero uma briga diária com $maiorRival. Só preciso que haja respeito.\"",
                "🔥 <b>$nome</b>: \"Quero tentar escutar o lado de $maiorRival, mesmo com tudo que aconteceu.\"",
                "🔥 <b>$nome</b>: \"Essa tensão com $maiorRival está me cansando. Eu não queria passar a semana inteira nisso.\"",
                "🔥 <b>$nome</b>: \"Não consigo dizer que confio em $maiorRival hoje. Mas também não sei o que vai acontecer amanhã.\""
            ];
        } elseif ($tipo == 'peso_lider') {
            $opcoes = [
                "👑 <b>$nome</b>: \"Ser Líder parece ótimo até lembrar que você precisa escolher quem vai correr risco.\"",
                "👑 <b>$nome</b>: \"Eu queria comemorar sem pensar na indicação, mas a responsabilidade vem junto.\"",
                "👑 <b>$nome</b>: \"Não dá pra colocar todo mundo no VIP. Sei que alguém vai ficar chateado comigo.\"",
                "👑 <b>$nome</b>: \"Hoje eu sinto que cada conversa tem um peso diferente porque estou com o poder.\"",
                "👑 <b>$nome</b>: \"Quero usar a liderança com firmeza, sem humilhar ninguém.\""
            ];
        } elseif ($tipo == 'peso_anjo') {
            $opcoes = [
                "😇 <b>$nome</b>: \"Acho que o colar do Anjo pesa mais do que parece. Uma imunidade muda muita coisa.\"",
                "😇 <b>$nome</b>: \"Eu queria poder proteger todo mundo de quem gosto, mas não funciona assim.\"",
                "😇 <b>$nome</b>: \"Fico imaginando como alguém vai reagir se eu escolher outra pessoa pra imunizar.\"",
                "😇 <b>$nome</b>: \"Não quero que minha decisão no Anjo seja interpretada como falta de carinho.\"",
                "😇 <b>$nome</b>: \"Ganhar uma prova é bom. Ter que escolher quem salvar é outra história.\""
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

        $fala = escolherFalaNaoRepetida($opcoes, $historicoConfessionario, $falas);

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