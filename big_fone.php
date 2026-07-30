<?php


// Ativa a exibição de erros do PHP.
// Essa configuração ajuda durante o desenvolvimento,
// pois mostra possíveis problemas no código.
ini_set('display_errors',1);


// Define que todos os tipos de erros devem aparecer.
error_reporting(E_ALL);



// Inicia a sessão do PHP.
// Ela permite guardar informações do jogo entre diferentes páginas.
session_start();




// Verifica se existe uma lista de jogadores criada na sessão.
// Caso o usuário tente acessar essa página sem iniciar o jogo,
// ele será enviado novamente para a tela inicial.
if(!isset($_SESSION['jogadores'])){


    // Redireciona para a página inicial.
    header("Location: index.php");


    // Encerra o código para impedir que continue executando.
    exit;
}




// Recupera os jogadores salvos na sessão.
$jogadores = $_SESSION['jogadores'];


// Recupera o nome do jogador principal.
// Caso não exista nenhum nome salvo, deixa vazio.
$meuNome = $_SESSION['meu_nome'] ?? '';





// Verifica se a lista de eventos extras já existe.
if(!isset($_SESSION['evento_extra'])){


    // Caso não exista, cria um array vazio.
// Esse array será utilizado para guardar acontecimentos da temporada.
    $_SESSION['evento_extra'] = [];
}





/*
=================================
VERIFICAÇÃO DO BIG FONE
=================================
*/


// Verifica se o Big Fone já aconteceu nessa rodada.
// Isso impede que o evento seja ativado várias vezes.
if(isset($_SESSION['bigfone_feito'])){


    // Retorna o jogador para o jogo principal.
    header("Location: jogo.php");


    exit;
}





// Decide se o Big Fone irá tocar ou não.
// O sistema sorteia um número entre 1 e 100.
// Caso seja menor ou igual a 60, o telefone toca.
// Isso cria uma chance de 60% de acontecer.
if(!isset($_SESSION['bigfone_tocou'])){


    $_SESSION['bigfone_tocou'] = rand(1,100) <= 60;
}




// Caso o sorteio determine que o Big Fone não tocou:
if($_SESSION['bigfone_tocou'] == false){



    // Adiciona uma mensagem informando o resultado.
    $_SESSION['evento_extra'][] = 
    "☎️ O Big Fone não tocou nesta semana.";



    // Marca o evento como realizado.
    $_SESSION['bigfone_feito'] = true;



    // Volta para a tela principal.
    header("Location: jogo.php");


    exit;
}

/* Funções auxiliares do Big Fone */


// Função criada para comparar nomes ignorando diferenças
// entre letras maiúsculas e minúsculas.
// Exemplo: "João" e "joão" serão considerados iguais.
function nomeIgualBigfone($a, $b){


    return mb_strtolower(trim((string)$a), 'UTF-8') 
    === 
    mb_strtolower(trim((string)$b), 'UTF-8');
}

// Função responsável por escolher um participante aleatório
// que poderá receber o Big Fone.
//
// Ela evita escolher jogadores que já possuem vantagens,
// como Líder ou Imune.

function escolherParticipanteBigFone($jogadores, $bloqueados = []){


    // Array que armazenará os participantes disponíveis.
    $opcoes = [];



    // Percorre todos os jogadores.
    foreach($jogadores as $j){


        // Pega o nome do jogador atual.
        $nome = $j['nome'] ?? '';



        // Caso não tenha nome, ignora.
        if($nome == '') continue;



        // Caso o jogador esteja bloqueado, ignora.
        if(in_array($nome, $bloqueados)) continue;



        // O Líder não pode receber esse poder.
        if(!empty($j['status']['lider'])) continue;



        // Jogadores imunes também são ignorados.
        if(!empty($j['status']['imune'])) continue;



        // Adiciona o participante válido na lista.
        $opcoes[] = $nome;
    }




    // Caso não exista nenhum participante disponível,
    // retorna vazio.
    if(empty($opcoes)) return null;



    // Escolhe aleatoriamente um participante da lista.
    return $opcoes[array_rand($opcoes)];
}

// Função responsável por salvar qual poder foi recebido
// e quem foi o participante que atendeu o Big Fone.
function registrarPoderBigFoneBase($atendente, $poder){


    // Guarda o tipo de poder recebido.
    $_SESSION['bigfone_poder'] = $poder;



    // Guarda o dono desse poder.
    $_SESSION['bigfone_dono_poder'] = $atendente;
}

/* Função aplicar poder */

// Função responsável por definir e aplicar o poder recebido pelo participante
// que atendeu o Big Fone.
//
// O "&" antes de $jogadores permite alterar diretamente os dados dos jogadores
// sem precisar criar uma nova cópia do array.
function aplicarPoderBigFone(&$jogadores, $atendente){



    // Lista contendo todos os poderes possíveis que podem sair no Big Fone.
    $poderes = [

        "imunidade",

        "indicacao",

        "voto_duplo",

        "anular_voto",

        "espiar_voto",

        "contragolpe",

        "trocar_emparedado"
    ];



    // Escolhe aleatoriamente um dos poderes disponíveis.
    $poder = $poderes[array_rand($poderes)];



    // Salva na sessão qual poder foi sorteado
    // e quem recebeu esse poder.
    registrarPoderBigFoneBase($atendente, $poder);



    // Recupera o nome do jogador principal.
    // Ele será utilizado para diferenciar ações do usuário e NPCs.
    $meuNome = $_SESSION['meu_nome'] ?? '';



    // Variável que armazenará a mensagem do evento.
    $texto = "";





    /*
    =====================================
    PODER DE IMUNIDADE
    =====================================
    */


    // Verifica se o poder sorteado foi imunidade.
    if($poder == "imunidade"){



        // Percorre todos os jogadores.
        foreach($jogadores as &$j){



            // Procura o participante que atendeu o Big Fone.
            if(nomeIgualBigfone(($j['nome'] ?? ''), $atendente)){



                // Define o jogador como protegido contra eliminação.
                $j['status']['imune'] = true;



                // Verifica se o jogador possui estatísticas.
                if(!isset($j['estatisticas'])){


                    // Caso não exista, cria o espaço.
                    $j['estatisticas'] = [];
                }




                // Soma uma imunidade recebida no histórico do participante.
                $j['estatisticas']['imune'] =
                ($j['estatisticas']['imune'] ?? 0) + 1;
            }
        }



        // Remove a referência criada pelo foreach.
        unset($j);



        // Mensagem que será exibida no jogo.
        $texto = 
        "🛡️ $atendente ganhou imunidade pelo Big Fone.";
    }







    /*
    =====================================
    PODER DE INDICAÇÃO
    =====================================
    */


    // Caso o poder seja indicar alguém diretamente ao paredão.
    if($poder == "indicacao"){



        // Cria uma pendência para o sistema saber
        // que o participante ainda precisa escolher alguém.
        $_SESSION['bigfone_indicacao_pendente'] = true;



        // Salva quem possui esse poder.
        $_SESSION['bigfone_dono_poder'] = $atendente;



        // Mensagem do evento.
        $texto =
        "🎯 $atendente ganhou o poder de indicar alguém direto ao paredão.";
    }







    /*
    =====================================
    PODER DE VOTO DUPLO
    =====================================
    */


    // Caso o poder sorteado seja voto duplo.
    if($poder == "voto_duplo"){



        // Salva o participante que poderá votar duas vezes
        // na próxima votação da casa.
        $_SESSION['curinga_voto_duplo_ativo'] = $atendente;



        // Mensagem informativa.
        $texto =
        "🗳️ $atendente ganhou voto duplo na próxima votação da casa.";
    }







    /*
    =====================================
    PODER DE ANULAR VOTO
    =====================================
    */


    // Poder que permite retirar um voto da votação.
    if($poder == "anular_voto"){



        // Verifica se quem recebeu o poder foi o próprio jogador.
        if(nomeIgualBigfone($atendente, $meuNome)){



            // Cria uma pendência para o jogador escolher
            // qual voto deseja anular.
            $_SESSION['bigfone_anular_voto_pendente'] = true;



            $texto =
            "🚫 $atendente ganhou o poder de anular o voto de um participante na próxima votação.";
        }


        else{


            // Caso seja NPC, o sistema escolhe automaticamente um alvo.
            $alvo = escolherParticipanteBigFone($jogadores, [$atendente]);



            // Verifica se encontrou alguém válido.
            if($alvo != null){



                // Salva quem terá o voto anulado.
                $_SESSION['curinga_anular_voto_de'] = $alvo;



                $texto =
                "🚫 $atendente ganhou o poder de anular voto e escolheu anular o voto de $alvo.";
            }


            else{


                // Caso não exista nenhum alvo disponível.
                $texto =
                "🚫 $atendente ganhou o poder de anular voto, mas não havia alvo válido.";
            }
        }
    }

    
    /*
    =====================================
    PODER DE ESPIAR VOTO
    =====================================
    */


    // Poder que permite descobrir o voto de outro participante.
    if($poder == "espiar_voto"){



        // Verifica se quem recebeu o poder foi o jogador principal.
        if(nomeIgualBigfone($atendente, $meuNome)){



            // Cria uma pendência para que o jogador escolha
            // quem deseja observar na votação.
            $_SESSION['bigfone_espiar_voto_pendente'] = true;



            // Mensagem informando o poder recebido.
            $texto =
            "👁️ $atendente ganhou o poder de espiar o voto de um participante.";
        }


        else{


            // Caso seja um NPC, o sistema escolhe automaticamente
            // alguém para ele observar.
            $alvo = escolherParticipanteBigFone($jogadores, [$atendente]);



            // Verifica se encontrou algum participante válido.
            if($alvo != null){



                // Salva o participante que terá o voto observado.
                $_SESSION['curinga_espiar_voto_de'] = $alvo;



                $texto =
                "👁️ $atendente ganhou o poder de espiar voto e escolheu observar o voto de $alvo.";
            }


            else{


                // Caso não exista nenhum participante disponível.
                $texto =
                "👁️ $atendente ganhou o poder de espiar voto, mas não havia alvo válido.";
            }
        }
    }







    /*
    =====================================
    PODER CONTRA-GOLPE
    =====================================
    */


    // Poder que permite ao participante puxar outra pessoa caso seja indicado ao paredão.
    if($poder == "contragolpe"){



        // Cria um array com as informações do poder.
        // Ele será consultado durante outras etapas do jogo.
        $_SESSION['poder_curinga'] = [


            // Define qual é o tipo do poder.
            "tipo" => "contra_golpe",



            // Guarda quem possui esse poder.
            "dono" => $atendente,



            // Define que o poder ainda está disponível.
            "usado" => true,



            // Guarda em qual rodada o poder foi recebido.
            "rodada" => $_SESSION['rodada'] ?? 1,



            // Identifica a origem do poder.
            // Neste caso, veio do Big Fone.
            "origem" => "bigfone"
        ];



        // Cria uma marcação para o sistema saber
        // que existe um contra-golpe aguardando uso.
        $_SESSION['bigfone_contragolpe_pendente'] = $atendente;



        // Mensagem exibida no jogo.
        $texto =
        "⚔️ $atendente ganhou o Contra-Golpe pelo Big Fone. Se cair no paredão, poderá puxar alguém.";
    }







    /*
    =====================================
    PODER DE TROCAR EMPAREDADO
    =====================================
    */


    // Poder que permite substituir alguém indicado ao paredão.
    if($poder == "trocar_emparedado"){



        // Salva as informações do poder dentro da sessão.
        $_SESSION['poder_curinga'] = [


            "tipo" => "trocar_emparedado",


            // Participante que recebeu o poder.
            "dono" => $atendente,


            // Define que o poder está disponível.
            "usado" => true,


            // Guarda a rodada em que foi recebido.
            "rodada" => $_SESSION['rodada'] ?? 1,


            // Identifica que veio do Big Fone.
            "origem" => "bigfone"
        ];



        // Marca que existe uma troca de emparedado pendente.
        $_SESSION['bigfone_troca_emparedado_pendente'] = $atendente;



        // Mensagem informando o funcionamento do poder.
        $texto =
        "🔁 $atendente ganhou o poder de trocar um emparedado pelo Big Fone. A indicação do líder não pode ser trocada.";
    }




    // Retorna a mensagem criada para ser adicionada
    // nos eventos da temporada.
    return $texto;
}






/*
=====================================
NPC ATENDE BIG FONE
=====================================
*/


// Função responsável por escolher automaticamente
// qual NPC atende o Big Fone quando o jogador decide não atender.
function npcAtendeBigFone($jogadores, $meuNome){



    // Array que armazenará apenas os NPCs.
    $npcs = [];



    // Percorre todos os jogadores.
    foreach($jogadores as $j){



        // Verifica se o participante não é o jogador principal.
        if($j['nome'] != $meuNome){



            // Adiciona o NPC na lista de possíveis atendentes.
            $npcs[] = $j['nome'];
        }
    }



    // Sorteia um NPC para atender o Big Fone.
    return $npcs[array_rand($npcs)];
}

/*
=====================================
JOGADOR NÃO ATENDE O BIG FONE
=====================================
*/


// Verifica se o botão "não atender" foi enviado pelo formulário.
if(isset($_POST['nao_atender'])){


    // Como o jogador não atendeu,
    // o sistema escolhe um NPC aleatório para correr até o telefone.
    $atendente = npcAtendeBigFone($jogadores, $meuNome);



    // Adiciona no histórico do jogo que o Big Fone tocou.
    $_SESSION['evento_extra'][] = 
    "☎️ O Big Fone tocou!";



    // Registra qual participante conseguiu atender.
    $_SESSION['evento_extra'][] =
    "🏃 $atendente correu e atendeu antes de todo mundo.";



    // Aplica o poder sorteado para o participante.
    $textoPoder = aplicarPoderBigFone($jogadores, $atendente);



    // Adiciona a consequência do poder no histórico.
    $_SESSION['evento_extra'][] = $textoPoder;



    // Atualiza os jogadores na sessão após aplicar alterações.
    $_SESSION['jogadores'] = $jogadores;



    // Guarda quem atendeu o Big Fone.
    $_SESSION['bigfone_atendente'] = $atendente;



    // Marca que o evento já aconteceu nessa rodada.
    $_SESSION['bigfone_feito'] = true;



    // Retorna para a página principal do jogo.
    header("Location: jogo.php");



    // Finaliza o código.
    exit;
}







/*
=====================================
JOGADOR ATENDE O BIG FONE
=====================================
*/


// Verifica se o botão "atender" foi pressionado.
if(isset($_POST['atender'])){


    // Nesse caso o próprio jogador recebe o telefone.
    $atendente = $meuNome;



    // Adiciona o evento no histórico.
    $_SESSION['evento_extra'][] =
    "☎️ O Big Fone tocou!";



    // Mostra que o jogador conseguiu atender.
    $_SESSION['evento_extra'][] =
    "🏃 $atendente correu e atendeu o Big Fone.";



    // Aplica um poder aleatório.
    $textoPoder = aplicarPoderBigFone($jogadores, $atendente);



    // Salva a consequência do poder.
    $_SESSION['evento_extra'][] = $textoPoder;



    // Atualiza os jogadores.
    $_SESSION['jogadores'] = $jogadores;



    // Guarda o nome de quem atendeu.
    $_SESSION['bigfone_atendente'] = $atendente;



    // Marca o evento como concluído.
    $_SESSION['bigfone_feito'] = true;



    // Volta para o jogo.
    header("Location: jogo.php");



    exit;
}

?>

<!DOCTYPE html>

<!-- Define que a página utiliza HTML5 -->
<html lang="pt-br">


<head>


<!-- Permite utilizar caracteres especiais e emojis -->
<meta charset="UTF-8">


<!-- Nome exibido na aba do navegador -->
<title>Big Fone</title>

<style>


/*
Remove configurações padrões do navegador.
*/
*{

    margin:0;

    padding:0;

    box-sizing:border-box;

}





/*
Configura o corpo da página.
*/
body{


    /* Fonte principal utilizada */
    font-family:Arial,sans-serif;



    /* Fundo escuro com destaque vermelho */
    background:
    radial-gradient(circle at top,#240014,#050510 75%);



    /* Cor dos textos */
    color:white;



    /* Ocupa toda altura da tela */
    min-height:100vh;



    /* Centraliza o conteúdo */
    display:flex;

    justify-content:center;

    align-items:center;



    /* Impede barras de rolagem */
    overflow:hidden;

}






/*
Cria uma camada de luz animada no fundo.
*/
body::before{


    /* Necessário para criar um elemento visual vazio */
    content:"";



    /* Fixa o efeito na tela */
    position:fixed;



    /* Expande o efeito */
    inset:-50%;



    /* Cria brilho vermelho */
    background:
    radial-gradient(circle,#ff006633,transparent 55%);



    /* Aplica animação */
    animation:mover 6s linear infinite;



    /* Coloca atrás do conteúdo */
    z-index:0;

}






/*
Animação responsável pelo movimento do brilho.
*/
@keyframes mover{


    0%{

        transform:translate(-10%,-10%);

    }


    50%{

        transform:translate(6%,6%);

    }


    100%{

        transform:translate(-10%,-10%);

    }

}





/*
Card principal onde fica o Big Fone.
*/
.box{


    /* Mantém o conteúdo acima do fundo animado */
    position:relative;

    z-index:2;



    /* Define largura */
    width:620px;



    /* Adapta para telas menores */
    max-width:92%;



    /* Espaçamento interno */
    padding:40px;



    /* Bordas arredondadas */
    border-radius:28px;



    /* Centraliza textos */
    text-align:center;



    /* Efeito transparente */
    background:rgba(255,255,255,.06);



    /* Pequena borda */
    border:1px solid rgba(255,255,255,.12);



    /* Brilho ao redor do card */
    box-shadow:0 0 45px rgba(255,0,120,.45);



    /* Animação de pulsação */
    animation:pulsar .8s infinite alternate;

}






/*
Cria o efeito de pulsar do brilho.
*/
@keyframes pulsar{


from{

box-shadow:0 0 25px rgba(255,0,120,.35);

}


to{

box-shadow:0 0 70px rgba(255,0,120,.8);

}


}

h1{
    font-size:48px;
    margin-bottom:18px;
    color:#ff2d75;
}

p{
    font-size:20px;
    line-height:1.6;
    margin-bottom:16px;
}

.timer{
    font-size:70px;
    font-weight:bold;
    color:#ffcc00;
    margin:20px 0;
}

.botoes{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
    margin-top:24px;
}

button{
    padding:16px;
    border:none;
    border-radius:15px;
    font-size:18px;
    font-weight:bold;
    cursor:pointer;
    color:white;
    transition:.25s;
}

.atender{
    background:linear-gradient(135deg,#ff0066,#6a00ff);
}

.nao{
    background:linear-gradient(135deg,#333,#111);
}

button:hover{
    transform:scale(1.04);
}

.aviso{
    opacity:.75;
    font-size:14px;
}
</style>
</head>

<body>

<div class="box">

<h1>☎️ BIG FONE TOCOU!</h1>

<p>O telefone mais temido da casa está tocando.</p>
<p>Você tem <b>3 segundos</b> para decidir se vai atender.</p>

<div class="timer" id="timer">3</div>

<form method="POST" id="formNao">
<input type="hidden" name="nao_atender" value="1">
</form>

<div class="botoes">

<form method="POST">
<button class="atender" name="atender">🏃 Atender</button>
</form>

<form method="POST">
<button class="nao" name="nao_atender">Não atender</button>
</form>

</div>

<p class="aviso">Se o tempo acabar, um participante aleatório atenderá.</p>

</div>

<script>
let tempo = 3;
const timer = document.getElementById("timer");

const intervalo = setInterval(() => {
    tempo--;
    timer.innerText = tempo;

    if(tempo <= 0){
        clearInterval(intervalo);
        document.getElementById("formNao").submit();
    }
}, 1000);
</script>

</body>
</html>