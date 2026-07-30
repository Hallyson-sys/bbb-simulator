<?php

// Ativa a exibição de erros do PHP para facilitar a identificação de problemas durante o desenvolvimento.
// Essa configuração ajuda na fase de testes do sistema.
ini_set('display_errors',1);
error_reporting(E_ALL);


// Inicia a sessão para conseguir acessar e salvar informações do jogador durante o jogo.
session_start();



/* ===============================
   VERIFICA SESSÃO
================================= */


// Verifica se existe uma lista de jogadores criada na sessão.
// Caso não exista, significa que o usuário tentou acessar essa página sem iniciar uma partida.
if(!isset($_SESSION['jogadores'])){

    // Redireciona o usuário novamente para a página inicial.
    header("Location: index.php");

    // Encerra a execução do código para evitar que continue carregando a página.
    exit;
}


// Recupera os jogadores que foram criados no arquivo de configuração.
$jogadores = $_SESSION['jogadores'];


// Recupera o nome do jogador principal.
// Caso não exista, define "Jogador" como valor padrão.
$meuNome = $_SESSION['meu_nome'] ?? 'Jogador';




/* ===============================
   LISTA DE PROVAS
================================= */


// Array que guarda todas as provas possíveis da liderança.
// Cada prova possui título, descrição, quantidade de opções e um tipo,
// permitindo que o sistema escolha diferentes formas de desafio.
$provas = [



    // Primeira prova disponível
    1 => [

        // Nome que será exibido para o jogador
        'titulo' => '🎯 Mira do Líder',

        // Explicação de como funciona a prova
        'texto'  => 'Escolha um número entre 1 e 5. Quem chegar mais perto vence.',

        // Quantidade máxima de escolhas disponíveis
        'max'    => 5,

        // Identifica o tipo de prova para o sistema saber como executar
        'tipo'   => 'numero'
    ],



    // Segunda prova disponível
    2 => [

        'titulo' => '⚡ Reflexo BBB',

        'texto'  => 'Escolha rapidamente uma opção secreta.',

        'max'    => 3,

        'tipo'   => 'numero'
    ],



    // Terceira prova disponível
    3 => [

        'titulo' => '🧩 Memória da Casa',

        'texto'  => 'Escolha uma porta de 1 a 3.',

        'max'    => 3,

        'tipo'   => 'numero'
    ],



    // As próximas provas seguem a mesma estrutura,
    // mudando apenas o tema visual e a quantidade de escolhas.
    4 => [

        'titulo' => '🎲 Dado da Sorte',

        'texto'  => 'Escolha um número de 1 a 6. Se seu número for o sorteado, você vence.',

        'max'    => 6,

        'tipo'   => 'numero'
    ],



    5 => [

        'titulo' => '🏹 Alvo Premiado',

        'texto'  => 'Escolha um alvo. Um deles esconde a liderança.',

        'max'    => 5,

        'tipo'   => 'alvo'
    ],



    6 => [

        'titulo' => '💎 Cofre Misterioso',

        'texto'  => 'Escolha um cofre. Apenas um guarda a chave do quarto do Líder.',

        'max'    => 4,

        'tipo'   => 'cofre'
    ],



    7 => [

        'titulo' => '🚀 Decolagem BBB',

        'texto'  => 'Escolha uma nave. A nave certa dispara rumo à liderança.',

        'max'    => 3,

        'tipo'   => 'nave'
    ],



    8 => [

        'titulo' => '🎰 Slot da Sorte',

        'texto'  => 'Escolha um símbolo. Se ele aparecer no sorteio, você vence.',

        'max'    => 4,

        'tipo'   => 'simbolo'
    ],



    9 => [

        'titulo' => '📦 Caixa Surpresa',

        'texto'  => 'Escolha uma caixa. Uma delas contém o poder da liderança.',

        'max'    => 5,

        'tipo'   => 'caixa'
    ],



    10 => [

        'titulo' => '🌪️ Giro da Liderança',

        'texto'  => 'Escolha uma cor. A roleta vai decidir quem leva a liderança.',

        'max'    => 4,

        'tipo'   => 'cor'
    ],



    11 => [

        'titulo' => '🔥 Totem do Líder',

        'texto'  => 'Escolha um totem. O totem correto acende e garante a liderança.',

        'max'    => 5,

        'tipo'   => 'totem'
    ]

];




/* ===============================
   ESCOLHE PROVA DA SEMANA
================================= */


// Verifica se ainda não existe uma prova escolhida.
// Isso impede que a prova mude toda vez que a página for atualizada.
if(!isset($_SESSION['prova_tipo'])){


    // Sorteia uma prova entre as 11 disponíveis.
    $_SESSION['prova_tipo'] = rand(1,11);
}


// Guarda o número da prova atual.
$tipoProva = $_SESSION['prova_tipo'];


// Busca os dados da prova escolhida.
// Caso aconteça algum erro, utiliza a primeira prova como padrão.
$prova = $provas[$tipoProva] ?? $provas[1];

/* ===============================
   FUNÇÕES
================================= */


// Função responsável por criar uma lista apenas com os participantes NPCs.
// Ela remove o jogador principal da lista para que ele não possa competir contra ele mesmo.
function nomesNPCsProva($jogadores, $meuNome){


    // Cria um array vazio que receberá somente os nomes dos NPCs.
    $npcs = [];


    // Percorre todos os jogadores existentes na partida.
    foreach($jogadores as $j){


        // Verifica se o jogador atual não é o usuário principal.
        if(($j['nome'] ?? '') != $meuNome){


            // Adiciona o nome do NPC dentro da lista.
            $npcs[] = $j['nome'];
        }
    }


    // Retorna a lista final contendo apenas NPCs.
    return $npcs;
}




// Função que escolhe aleatoriamente um NPC vencedor.
// Ela é utilizada quando a prova depende apenas de sorte.
function sortearNPCVencedor($jogadores, $meuNome){


    // Busca todos os NPCs participantes da prova.
    $npcs = nomesNPCsProva($jogadores, $meuNome);


    // Caso não existam NPCs disponíveis, retorna o próprio jogador.
    if(empty($npcs)){

        return $meuNome;
    }


    // Escolhe aleatoriamente um NPC da lista.
    return $npcs[array_rand($npcs)];
}




// Função utilizada nas provas que funcionam através de sorte.
// O jogador escolhe uma opção e o sistema sorteia uma opção secreta.
function disputaPorSorte($jogadores, $meuNome, $escolha, $maximo){


    // Sorteia um número secreto de acordo com a quantidade máxima da prova.
    $segredo = rand(1, $maximo);



    // Compara a escolha do jogador com o número sorteado.
    if((int)$escolha === $segredo){


        // Caso acerte, o próprio jogador vence.
        return $meuNome;
    }



    // Caso erre, um NPC é escolhido como vencedor.
    return sortearNPCVencedor($jogadores, $meuNome);
}






/* ===============================
   PROCESSAR JOGADA
================================= */


// Verifica se o jogador clicou em uma opção da prova.
if(isset($_POST['jogar'])){


    // Cria uma variável inicialmente vazia para armazenar o vencedor.
    $lider = null;


    // Recebe a escolha enviada pelo botão do formulário.
    // Caso não exista nenhuma escolha, deixa vazio.
    $escolha = $_POST['escolha'] ?? '';




    /* ===========================
       PROVA 1 - MIRA
    =========================== */


    // A primeira prova possui uma lógica diferente,
    // pois compara qual participante chegou mais perto do número sorteado.
    if($tipoProva == 1){


        // Define um número secreto que representa o alvo.
        $alvo = rand(1,5);


        // Converte a escolha do jogador para número inteiro.
        $escolhaNumero = (int)$escolha;



        // Calcula a distância entre a escolha do jogador e o alvo.
        // Quanto menor a distância, melhor o resultado.
        $distPlayer = abs($alvo - $escolhaNumero);



        // Guarda inicialmente a maior distância possível.
        // Ela será substituída quando encontrar um NPC melhor.
        $melhorNpc = 99;


        // Variável que armazenará o nome do NPC vencedor.
        $npcNome = '';



        // Percorre todos os jogadores para comparar os resultados.
        foreach($jogadores as $j){


            // Ignora o próprio jogador na comparação.
            if(($j['nome'] ?? '') == $meuNome) continue;



            // Cada NPC recebe uma escolha aleatória.
            $npcEscolha = rand(1,5);



            // Calcula a distância do NPC até o alvo.
            $distNpc = abs($alvo - $npcEscolha);



            // Verifica se esse NPC teve um resultado melhor.
            if($distNpc < $melhorNpc){


                // Atualiza a menor distância encontrada.
                $melhorNpc = $distNpc;


                // Guarda o nome do NPC vencedor.
                $npcNome = $j['nome'];
            }
        }



        // Compara o resultado do jogador com o melhor NPC.
        // Caso o jogador tenha chegado mais perto ou empatado, ele vence.
        $lider = ($distPlayer <= $melhorNpc) ? $meuNome : $npcNome;
    }




    /* ===========================
       PROVAS DE SORTE SIMPLES
    =========================== */


    // As provas 2 até 11 utilizam a função de sorte.
    if($tipoProva >= 2 && $tipoProva <= 11){


        // Realiza a disputa e retorna o vencedor.
        $lider = disputaPorSorte(
            $jogadores,
            $meuNome,
            $escolha,
            $prova['max']
        );
    }




    // Caso por algum motivo nenhum vencedor seja definido,
    // escolhe automaticamente um NPC para evitar erro no sistema.
    if($lider == null || $lider == ''){

        $lider = sortearNPCVencedor($jogadores, $meuNome);
    }

/* ===============================
   SALVAR LÍDER
================================= */


// Salva na sessão o nome do participante que venceu a Prova do Líder.
// Essa informação será utilizada em outras páginas do jogo,
// como na formação do paredão e exibição do líder atual.
$_SESSION['lider'] = $lider;



// Percorre todos os jogadores da partida para atualizar quem possui a liderança.
foreach($_SESSION['jogadores'] as &$j){


    // Primeiro remove a liderança de todos os participantes.
    // Isso garante que exista apenas um líder por rodada.
    $j['status']['lider'] = false;



    // Verifica se o jogador atual é o vencedor da prova.
    if(($j['nome'] ?? '') == $lider){


        // Define o participante vencedor como líder.
        $j['status']['lider'] = true;



        // Verifica se o jogador já possui um espaço para guardar estatísticas.
        if(!isset($j['estatisticas'])){

            // Caso não exista, cria um novo array de estatísticas.
            $j['estatisticas'] = [];
        }



        // Adiciona uma vitória na Prova do Líder para esse participante.
        // O operador ?? garante que, caso seja a primeira vitória,
        // o valor inicial será 0.
        $j['estatisticas']['lider'] =
        ($j['estatisticas']['lider'] ?? 0) + 1;
    }
}


// Remove a referência criada pelo foreach.
// Isso evita problemas caso a variável $j seja usada novamente depois.
unset($j);




// Cria uma mensagem que será exibida no jogo informando o vencedor.
// O operador ternário permite escolher entre duas mensagens diferentes.
$_SESSION['mensagem_lider'] =

($lider == $meuNome)


// Caso o próprio jogador vença a prova.
? "🏆 Você venceu a Prova do Líder!"

// Caso um NPC vença a prova.
: "👑 $lider venceu a Prova do Líder!";




// Remove a prova atual da sessão.
// Assim, quando uma nova rodada começar,
// uma nova prova poderá ser sorteada.
unset($_SESSION['prova_tipo']);




// Verifica se a área de eventos extras já foi criada.
if(!isset($_SESSION['evento_extra'])){


    // Cria um array vazio para armazenar mensagens dos acontecimentos.
    $_SESSION['evento_extra'] = [];
}




// Adiciona um evento informando quem ganhou a liderança.
$_SESSION['evento_extra'][] =
"👑 ".$lider." venceu a Prova do Líder.";




// Adiciona uma mensagem de ambientação para deixar o jogo mais parecido
// com uma temporada real de reality show.
$_SESSION['evento_extra'][] =
"🗣️ \"Parabéns! O reinado começou.\"";




// Depois que todas as informações são salvas,
// o jogador retorna para a página principal do jogo.
header("Location: jogo.php");


// Finaliza o código para evitar que continue executando.
exit;
}






// Função responsável por criar os textos que aparecem nos botões das provas.
// Como existem vários tipos de provas, cada uma possui uma identificação diferente.
function textoBotaoProva($tipo, $i){



    // Retorna o nome do botão caso a prova seja de escolher um alvo.
    if($tipo == 'alvo') return "🎯 Alvo $i";



    // Retorna o nome do botão caso a prova seja de escolher um cofre.
    if($tipo == 'cofre') return "💎 Cofre $i";



    // Caso seja uma prova de nave, transforma os números em letras.
    if($tipo == 'nave'){


        // Lista de letras que serão usadas nas naves.
        $letras = ["A","B","C","D","E"];


        // Retorna a letra correspondente ao botão escolhido.
        return "🚀 Nave ".$letras[$i-1];
    }



    // Caso seja uma prova de símbolos,
    // cria opções visuais para o jogador escolher.
    if($tipo == 'simbolo'){

        $simbolos = ["🍒","⭐","💎","🎯"];


        // Retorna o símbolo correspondente.
        // Caso não exista, mostra um texto padrão.
        return $simbolos[$i-1] ?? "Símbolo $i";
    }



    // Botões da prova de caixas.
    if($tipo == 'caixa') return "📦 Caixa $i";



    // Caso seja uma prova de cores.
    if($tipo == 'cor'){

        // Lista de cores disponíveis.
        $cores = ["Rosa","Azul","Dourado","Roxo"];


        // Retorna a cor correspondente.
        return "🌪️ ".$cores[$i-1];
    }



    // Botões da prova de totens.
    if($tipo == 'totem') return "🔥 Totem $i";



    // Caso seja uma prova numérica simples,
    // retorna apenas o número da opção.
    return (string)$i;
}

?>

<!DOCTYPE html>

<!-- Define que o documento utiliza a versão HTML5 -->
<html lang="pt-br">

<!-- Define que o idioma principal da página é português brasileiro -->
<head>


<!-- Define a codificação dos caracteres para aceitar acentos e emojis -->
<meta charset="UTF-8">


<!-- Título que aparece na aba do navegador -->
<title>Prova do Líder</title>



<style>

/* 
Remove os espaçamentos padrões do navegador
e define o cálculo correto do tamanho dos elementos.
*/
*{
margin:0;
padding:0;
box-sizing:border-box;
}




/*
Estilização principal da página.
Define fonte, fundo, alinhamento e tamanho mínimo.
*/
body{


/* Define a fonte utilizada no sistema */
font-family:Arial,sans-serif;


/*
Cria um fundo com efeitos de gradiente radial.
Foi utilizado para deixar a interface com aparência mais próxima
de um programa de reality show.
*/
background:

radial-gradient(circle at top left, rgba(255,0,120,.22), transparent 35%),

radial-gradient(circle at top right, rgba(255,204,0,.18), transparent 35%),

radial-gradient(circle at bottom, #1b1438, #050510 75%);



/* Define a cor padrão dos textos como branca */
color:white;



/* Garante que a página ocupe toda a altura da tela */
min-height:100vh;



/*
Utiliza flexbox para centralizar o conteúdo
horizontalmente e verticalmente.
*/
display:flex;

justify-content:center;

align-items:center;



/* Cria um espaço interno para evitar que o conteúdo encoste nas bordas */
padding:30px;

}





/*
Classe responsável pelo card principal da prova.
Dentro dela ficam título, descrição e botões.
*/
.box{


/* Define largura máxima da área principal */
width:760px;



/* Permite que o elemento diminua em telas menores */
max-width:100%;



/* Cria um fundo transparente para dar efeito de vidro */
background:rgba(255,255,255,.06);



/* Adiciona uma borda discreta ao redor do card */
border:1px solid rgba(255,255,255,.12);



/* Arredonda os cantos do card */
border-radius:28px;



/* Cria espaço interno entre o conteúdo e a borda */
padding:36px;



/* Adiciona sombra para dar profundidade ao elemento */
box-shadow:0 0 40px rgba(0,0,0,.48);



/* Centraliza todos os textos e elementos internos */
text-align:center;



/*
Cria um efeito de desfoque no fundo,
dando aparência de vidro.
*/
backdrop-filter:blur(14px);

}





/*
Estilização do título principal da prova.
*/
h1{


/* Define o tamanho da fonte */
font-size:38px;



/* Espaço inferior antes do próximo elemento */
margin-bottom:15px;



/*
Cria um degradê colorido no texto.
*/
background:linear-gradient(
90deg,
#ff0077,
#ffcc00,
#00d9ff
);



/*
Faz o degradê aparecer somente dentro do texto.
*/
-webkit-background-clip:text;

background-clip:text;



/* Deixa a cor do texto transparente para mostrar o degradê */
-webkit-text-fill-color:transparent;

color:transparent;

}




/*
Estilização dos textos de descrição da prova.
*/
p{


/* Tamanho do texto */
font-size:18px;



/* Deixa o texto levemente transparente */
opacity:.92;



/* Espaçamento inferior */
margin-bottom:25px;



/* Melhora a leitura em textos maiores */
line-height:1.6;

}




/*
Área onde ficam os botões das escolhas.
Utiliza CSS Grid para organizar automaticamente.
*/
.grid{


/* Ativa o sistema de grade */
display:grid;



/*
Cria três colunas de mesmo tamanho.
*/
grid-template-columns:repeat(3,1fr);



/* Espaçamento entre os botões */
gap:14px;



/* Espaço acima da grade */
margin-top:15px;

}




/*
Estilo dos botões de escolha da prova.
*/
button{


/* Espaço interno do botão */
padding:18px;



/* Remove a borda padrão */
border:none;



/* Arredonda os cantos */
border-radius:16px;



/* Tamanho do texto */
font-size:18px;



/* Deixa o texto em negrito */
font-weight:bold;



/* Mostra o cursor de clique */
cursor:pointer;



/* Cor do texto */
color:white;



/*
Cria um degradê no fundo dos botões.
*/
background:linear-gradient(
135deg,
#ff0066,
#6a00ff
);



/*
Cria uma animação suave quando o botão muda.
*/
transition:.25s;



/* Define uma altura mínima para manter todos iguais */
min-height:62px;

}





/*
Efeito aplicado quando o mouse passa sobre o botão.
*/
button:hover{


/*
Move o botão levemente para cima
e aumenta um pouco o tamanho.
*/
transform:translateY(-3px) scale(1.02);



/*
Adiciona brilho ao redor do botão.
*/
box-shadow:0 0 22px rgba(255,0,140,.38);

}





/*
Área inferior com informações da prova.
*/
.info{


/* Espaço acima do texto */
margin-top:22px;



/* Tamanho menor para informações secundárias */
font-size:14px;



/* Deixa o texto menos destacado */
opacity:.76;



/* Melhora organização das linhas */
line-height:1.5;

}





/*
Responsividade:
quando a tela tiver menos de 700px,
os botões passam a ocupar uma coluna.
*/
@media(max-width:700px){


.grid{


/* Altera a grade para uma coluna em celulares */
grid-template-columns:1fr;

}

}


</style>

</head>



<body>


<!--
Div principal que engloba toda a tela da prova.
-->
<div class="box">



<!--
Exibe o título da prova.
O PHP substitui automaticamente pelo nome da prova escolhida.
-->
<h1>

<?php echo $prova['titulo']; ?>

</h1>




<!--
Mostra a descrição da prova escolhida.
-->
<p>

<?php echo $prova['texto']; ?>

</p>




<!--
Formulário responsável por enviar a escolha do jogador
para o processamento PHP.
-->
<form method="POST">



<!--
Container que organiza os botões das escolhas.
-->
<div class="grid">




<!--
Laço PHP que cria os botões automaticamente.

A quantidade de botões depende do valor máximo
definido na prova.
-->
<?php for($i=1;$i<=$prova['max'];$i++): ?>



<!--
Botão responsável por enviar a escolha do jogador.

O value guarda o número escolhido.
-->
<button 
type="submit" 
name="escolha" 
value="<?php echo $i; ?>"
>


<!--
Chama a função PHP que define o texto do botão.
Exemplo:
Alvo 1, Cofre 2, Nave A, etc.
-->
<?php echo textoBotaoProva($prova['tipo'], $i); ?>


</button>



<!-- Finaliza o laço de criação dos botões -->
<?php endfor; ?>



</div>




<!--
Campo invisível utilizado para avisar ao PHP
que uma jogada foi realizada.
-->
<input 
type="hidden" 
name="jogar" 
value="1"
>



</form>





<!--
Área de explicação das regras básicas da prova.
-->
<div class="info">


👑 O vencedor assume a liderança da rodada.

<br>


🎲 Algumas provas são de sorte, outras comparam sua escolha com a dos participantes.


</div>



</div>


</body>

</html>