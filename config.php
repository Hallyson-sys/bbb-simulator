<?php
session_start();

if(isset($_POST['nome'])){
    session_unset();
}

/* ==================================
   RECEBER DADOS DO FORM
================================== */
$nomeUser = trim($_POST['nome']);
$idade = (int) $_POST['idade'];
$profissao = $_POST['profissao'];
$estado = $_POST['estado'];
$personalidadeUser = $_POST['personalidade'];
$qtd = (int) ($_POST['qtd'] ?? 20);

/* ==================================
   NOMES DISPONÍVEIS
================================== */
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
"Maria Eduarda", "Thiago","Yasmin","Malu","João",
"Francisca","Isabelly","Carolina","Zoe","Matteo",
"Gabriel","Otto","Clara","Zeca","Patrick","Camilo",
"Tainá","Helena","Heitor","Priscila","Henry","Rauanny"
];

/* REMOVE SEU NOME DA LISTA */
$nomes = array_filter($nomes, function($nome) use ($nomeUser){
    return mb_strtolower($nome) != mb_strtolower($nomeUser);
});

$nomes = array_values($nomes);
shuffle($nomes);

/* ==================================
   PERSONALIDADES
================================== */
$personalidades = [
"Estrategista","Explosivo","Planta","Manipulador",
"Emocional","Barraqueiro","Fofo","Líder Nato",
"Influencer","Falso","Neutro"
];

/* ==================================
   PROFISSÕES
================================== */
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
$estados = [
"SP","RJ","MG","BA","RS","SC","PR","PE","CE","GO",
"DF","ES","PA","AM","MT","MS","RN","PB","AL","SE",
"MA","PI","TO","RO","AC","AP","RR"
];

/* ==================================
   CRIAR JOGADORES
================================== */
$jogadores = [];

/* NPCS */
for($i = 0; $i < $qtd - 1; $i++){

    if(!isset($nomes[$i])){
        $nomeAleatorio = "Participante".($i+1);
    }else{
        $nomeAleatorio = $nomes[$i];
    }

    $jogadores[] = [
        "nome" => $nomeAleatorio,
        "idade" => rand(18,55),
        "profissao" => $profissoesNPC[array_rand($profissoesNPC)],
        "estado" => $estados[array_rand($estados)],
        "personalidade" => $personalidades[array_rand($personalidades)],
        "popularidade" => rand(40,60),
        "humor" => rand(40,60),

        "status" => [
            "lider" => false,
            "anjo" => false,
            "imune" => false,
            "vip" => false,
            "xepa" => true
        ],

        "relacoes" => []
    ];
}

/* VOCÊ */
$jogadores[] = [
    "nome" => $nomeUser,
    "idade" => $idade,
    "profissao" => $profissao,
    "estado" => $estado,
    "personalidade" => $personalidadeUser,
    "popularidade" => rand(60,80),
    "humor" => 60,

    "status" => [
        "lider" => false,
        "anjo" => false,
        "imune" => false,
        "vip" => false,
        "xepa" => true
    ],

    "relacoes" => []
];

/* EMBARALHAR ELENCO */
shuffle($jogadores);

/* ==================================
   RELAÇÕES ENTRE TODOS
================================== */
foreach($jogadores as &$j1){

    foreach($jogadores as $j2){

        if($j1['nome'] == $j2['nome']) continue;

        $j1['relacoes'][$j2['nome']] = [
            "amizade" => rand(20,80),
            "rivalidade" => rand(0,50),
            "confianca" => rand(20,80)
        ];
    }
}

/* ==================================
   RESET TEMPORADA
================================== */
unset(
$_SESSION['lider'],
$_SESSION['anjo'],
$_SESSION['imune'],
$_SESSION['paredao'],
$_SESSION['eliminado'],
$_SESSION['votos']
);

/* ==================================
   SALVAR
================================== */
$_SESSION['jogadores'] = $jogadores;
$_SESSION['rodada'] = 1;
$_SESSION['meu_nome'] = $nomeUser;

/* ==================================
   REDIRECIONAR
================================== */
header("Location: jogo.php");
exit;
?>