<?php

// Inicia uma sessão PHP para permitir o armazenamento de informações do jogador durante o jogo
session_start();


// Verifica se o formulário enviou um nome.
// Caso exista um novo nome, remove dados antigos da sessão para iniciar uma nova partida.
if(isset($_POST['nome'])){
    session_unset();
}


/* ==================================
   RECEBER DADOS DO FORM
================================== */

// Recebe o nome informado pelo usuário e remove espaços desnecessários no início e no final
$nomeUser = trim($_POST['nome']);

// Recebe a idade enviada pelo formulário e transforma o valor para inteiro
$idade = (int) $_POST['idade'];

// Armazena a profissão escolhida pelo usuário
$profissao = $_POST['profissao'];

// Armazena o estado escolhido pelo usuário
$estado = $_POST['estado'];

// Armazena a personalidade escolhida pelo usuário
$personalidadeUser = $_POST['personalidade'];

// Define a quantidade de participantes da partida.
// Caso nenhuma quantidade seja enviada, o sistema utiliza 20 jogadores como padrão.
$qtd = (int) ($_POST['qtd'] ?? 20);



/* ==================================
   NOMES DISPONÍVEIS
================================== */

// Array contendo os nomes que podem ser utilizados pelos NPCs (personagens controlados pelo sistema)
$nomes = [
"Ana","Carlos","Julia","Lucas","Marina",
"Pedro","Fernanda","Rafael","Bianca","Gustavo",
"Camila","Bruno","Larissa","Diego","Aline",
"Igor","Vanessa","Renan","Beatriz","Felipe",
"Alberto","Yago","Nicole","Débora","Rayssa",
"Giulia","Nathan","Allan","Samira","Theo",
"Mariana","Luiza","Henrique","Paula","Vinicius",
"Eduarda","Leandro","Vitória","Matheus","Amanda",
"Caio","Murilo","Talita","Raissa","Brenda",
"Maria Eduarda","Thiago","Yasmin","Malu","João",
"Francisca","Isabelly","Carolina","Zoe","Matteo",
"Gabriel","Otto","Clara","Zeca","Patrick","Camilo",
"Tainá","Helena","Heitor","Priscila","Henry","Rauanny"
];


// Remove da lista de NPCs o nome escolhido pelo jogador,
// evitando que exista outro participante com o mesmo nome.
$nomes = array_filter($nomes, function($nome) use ($nomeUser){

    // Compara os nomes ignorando diferença entre letras maiúsculas e minúsculas
    return mb_strtolower($nome) != mb_strtolower($nomeUser);
});


// Reorganiza os índices do array após remover um nome
$nomes = array_values($nomes);

// Mistura a ordem dos nomes para gerar participantes diferentes em cada partida
shuffle($nomes);



/* ==================================
   PERSONALIDADES
================================== */

// Lista das personalidades possíveis para os participantes do jogo
// Cada personalidade influencia o comportamento e eventos durante a simulação.
$personalidades = [
"Estrategista",
"Explosivo",
"Planta",
"Manipulador",
"Emocional",
"Barraqueiro",
"Fofo",
"Líder Nato",
"Influencer",
"Falso",
"Neutro"
];



/* ==================================
   PROFISSÕES
================================== */

// Lista de profissões utilizadas para gerar personagens automaticamente (NPCs)
$profissoesNPC = [
"Influencer",
"Professor(a)",
"Youtuber",
"Advogado(a)",
"Policial",
"Médico(a)",
"Enfermeiro(a)",
"Balconista",
"Desempregado",
"DJ",
"Terapeuta",
"Ator/Atriz",
"Bombeiro(a)",
"Personal Trainer",
"Maquiador(a)",
"Motorista de Aplicativo",
"Nutricionista",
"Barbeiro(a)",
"Cabeleleiro(a)",
"Cantor(a)",
"Modelo",
"Vendedor(a)",
"Engenheiro(a)",
"Arquiteto(a)",
"Empresário",
"Psicólogo",
"Tatuador(a)",
"Veterinário(a)",
"Streamer",
"Fotógrafo(a)",
"Comissário(a) de Bordo",
"Assistente Social",
"Esteticista",
"Radialista"
];



/* ==================================
   ESTADOS
================================== */

// Lista dos estados brasileiros que podem ser sorteados para os NPCs
$estados = [
"SP","RJ","MG","BA","RS","SC","PR","PE","CE","GO",
"DF","ES","PA","AM","MT","MS","RN","PB","AL","SE",
"MA","PI","TO","RO","AC","AP","RR"
];

/* ==================================
   CRIAR JOGADORES
================================== */

// Cria um array vazio que armazenará todos os participantes da partida
$jogadores = [];



/* NPCS */

// Laço responsável por criar os personagens controlados pelo sistema.
// O "-1" é utilizado porque o último participante será o próprio usuário.
for($i = 0; $i < $qtd - 1; $i++){


    // Verifica se ainda existem nomes disponíveis na lista.
    // Caso acabem os nomes, cria um nome genérico para o participante.
    if(!isset($nomes[$i])){

        $nomeAleatorio = "Participante".($i+1);

    }else{

        // Utiliza um nome disponível da lista para o NPC
        $nomeAleatorio = $nomes[$i];
    }


    // Adiciona um novo participante no array de jogadores
    $jogadores[] = [

        // Define o nome sorteado para o NPC
        "nome" => $nomeAleatorio,

        // Gera uma idade aleatória entre 18 e 55 anos para o personagem
        "idade" => rand(18,55),

        // Escolhe uma profissão aleatória da lista de profissões disponíveis
        "profissao" => $profissoesNPC[array_rand($profissoesNPC)],

        // Escolhe um estado aleatório para representar a origem do participante
        "estado" => $estados[array_rand($estados)],

        // Define uma personalidade aleatória para o comportamento do NPC
        "personalidade" => $personalidades[array_rand($personalidades)],


        // Define valores iniciais de popularidade e humor.
        // Esses valores podem mudar durante a evolução do jogo.
        "popularidade" => rand(40,60),
        "humor" => rand(40,60),



        // Guarda características especiais do participante
        "status" => [

            // Indica se o jogador possui o poder de liderança
            "lider" => false,

            // Indica se o jogador possui proteção de anjo
            "anjo" => false,

            // Indica se o jogador está protegido contra eliminação
            "imune" => false,

            // Indica se o jogador possui benefício VIP
            "vip" => false,

            // Define se o jogador está na área menos privilegiada da casa
            "xepa" => true
        ],


        // Cria arrays vazios para armazenar informações futuras
        // como amizades, romances e depoimentos.
        "relacoes" => [],
        "romances" => [],
        "confessionarios" => []
    ];
}



/* VOCÊ */

// Adiciona o jogador real dentro do elenco da partida
$jogadores[] = [

    // Utiliza os dados informados pelo usuário no formulário
    "nome" => $nomeUser,
    "idade" => $idade,
    "profissao" => $profissao,
    "estado" => $estado,
    "personalidade" => $personalidadeUser,


    // O jogador começa com uma popularidade maior que os NPCs
    // para representar a participação ativa do usuário.
    "popularidade" => rand(60,80),

    // O humor inicial do jogador começa em 60 pontos
    "humor" => 60,


    // Define os status iniciais do jogador
    "status" => [

        // No início da partida nenhum benefício está ativo
        "lider" => false,
        "anjo" => false,
        "imune" => false,
        "vip" => false,

        // Todos começam na condição inicial da casa
        "xepa" => true
    ],


    // Arrays preparados para receber interações durante o jogo
    "relacoes" => [],
    "romances" => [],
    "confessionarios" => []
];



// Mistura a ordem dos participantes para que o jogador não fique sempre
// na mesma posição dentro do elenco.
shuffle($jogadores);




/* ==================================
   RELAÇÕES ENTRE TODOS
================================== */

// Cria relações individuais entre todos os participantes da casa.
// O foreach utiliza referência (&) para permitir alterar diretamente os jogadores.
foreach($jogadores as &$j1){


    // Percorre novamente todos os jogadores para criar uma relação entre cada dupla
    foreach($jogadores as $j2){

        unset($j1);

/* ==================================
   INICIAR PARTIDA
================================== */

$_SESSION['jogadores'] = $jogadores;

$_SESSION['meu_nome'] = $nomeUser;

$_SESSION['rodada'] = 1;

header("Location: jogo.php");
exit;


        // Impede que o jogador crie uma relação consigo mesmo
        if($j1['nome'] == $j2['nome']) continue;



        // Cria uma relação inicial aleatória entre dois participantes
        $j1['relacoes'][$j2['nome']] = [

            // Nível de amizade entre 20 e 80 pontos
            "amizade" => rand(20,80),

            // Nível de rivalidade entre 0 e 50 pontos
            "rivalidade" => rand(0,50),

            // Nível de confiança entre os participantes
            "confianca" => rand(20,80)
        ];
    }
}