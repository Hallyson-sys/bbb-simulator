<?php

session_start();

require_once __DIR__ . '/includes/logica/gramatica.php';

/* =========================================================
   📥 RECEBER E VALIDAR FORMULÁRIO
   ========================================================= */

$nomeUser = trim($_POST['nome'] ?? '');
$idade = (int)($_POST['idade'] ?? 0);
$profissao = trim($_POST['profissao'] ?? '');
$estado = trim($_POST['estado'] ?? '');
$personalidadeUser = trim($_POST['personalidade'] ?? 'Neutro');
$qtd = (int)($_POST['qtd'] ?? 20);
$tipoElenco = $_POST['tipo_elenco'] ?? 'automatico';

if ($nomeUser === '') {
    header('Location: index.php?erro=nome');
    exit;
}

if ($idade < 18 || $idade > 70) {
    header('Location: index.php?erro=idade');
    exit;
}

if ($profissao === '') {
    header('Location: index.php?erro=profissao');
    exit;
}

if (mb_strlen($profissao, 'UTF-8') > 60) {
    $profissao = mb_substr($profissao, 0, 60, 'UTF-8');
}

$quantidadesPermitidas = [10, 15, 20];

if (!in_array($qtd, $quantidadesPermitidas, true)) {
    $qtd = 20;
}

if (!in_array($tipoElenco, ['automatico', 'personalizado'], true)) {
    $tipoElenco = 'automatico';
}

/*
 * Só limpa a sessão DEPOIS que os dados foram validados.
 * Assim um formulário inválido não destrói um jogo em andamento.
 */
session_unset();

/* =========================================================
   📚 DADOS DISPONÍVEIS
   ========================================================= */

$nomes = [
    'Ana','Carlos','Julia','Lucas','Marina',
    'Pedro','Fernanda','Rafael','Bianca','Gustavo',
    'Camila','Bruno','Larissa','Diego','Aline',
    'Igor','Vanessa','Renan','Beatriz','Felipe',
    'Alberto','Yago','Nicole','Débora','Rayssa',
    'Giulia','Nathan','Allan','Samira','Theo',
    'Mariana','Luiza','Henrique','Paula','Vinicius',
    'Eduarda','Leandro','Vitória','Matheus','Amanda',
    'Caio','Murilo','Talita','Raissa','Brenda',
    'Maria Eduarda','Thiago','Yasmin','Malu','João',
    'Francisca','Isabelly','Carolina','Zoe','Matteo',
    'Gabriel','Otto','Clara','Zeca','Patrick',
    'Camilo','Tainá','Helena','Heitor','Priscila',
    'Henry','Rauanny'
];

$personalidades = [
    'Estrategista','Explosivo','Planta','Manipulador',
    'Emocional','Barraqueiro','Fofo','Líder Nato',
    'Influencer','Falso','Neutro'
];

$profissoesNPC = [
    'Influencer','Professor(a)','Youtuber','Advogado(a)',
    'Policial','Médico(a)','Enfermeiro(a)','Balconista',
    'Desempregado','DJ','Terapeuta','Ator/Atriz',
    'Bombeiro(a)','Personal Trainer','Maquiador(a)',
    'Motorista de Aplicativo','Nutricionista','Barbeiro(a)',
    'Cabeleireiro(a)','Cantor(a)','Modelo','Vendedor(a)',
    'Engenheiro(a)','Arquiteto(a)','Empresário','Psicólogo',
    'Tatuador(a)','Veterinário(a)','Streamer','Fotógrafo(a)',
    'Comissário(a) de Bordo','Assistente Social','Esteticista',
    'Radialista','Estudante','Cineasta','Roteirista','Designer'
];

$estados = [
    'SP','RJ','MG','BA','RS','SC','PR','PE','CE','GO',
    'DF','ES','PA','AM','MT','MS','RN','PB','AL','SE',
    'MA','PI','TO','RO','AC','AP','RR'
];

/* =========================================================
   🧑 JOGADOR PRINCIPAL
   ========================================================= */

$meuJogador = [
    'nome' => $nomeUser,
    'idade' => $idade,
    'profissao' => $profissao,
    'estado' => $estado,
    'personalidade' => $personalidadeUser,
    'genero' => generoNomeConhecidoBBB($nomeUser) ?? 'nao_informado',
    'popularidade' => rand(60, 80),
    'humor' => 60,
    'origem' => 'elenco_inicial',
    'status' => [
        'lider' => false,
        'anjo' => false,
        'imune' => false,
        'vip' => false,
        'xepa' => true,
        'monstro' => false
    ],
    'relacoes' => [],
    'romances' => [],
    'confessionarios' => []
];

$_SESSION['meu_nome'] = $nomeUser;
$_SESSION['meu_jogador_snapshot'] = $meuJogador;
$_SESSION['qtd_participantes'] = $qtd;
$_SESSION['tipo_elenco'] = $tipoElenco;
$_SESSION['rodada'] = 1;
$_SESSION['modo_espectador'] = false;

/* =========================================================
   ✏️ ELENCO PERSONALIZADO
   ========================================================= */

if ($tipoElenco === 'personalizado') {
    $_SESSION['elenco_personalizado'] = [$meuJogador];
    $_SESSION['jogadores'] = [$meuJogador];

    header('Location: montar_elenco.php');
    exit;
}

/* =========================================================
   🎲 ELENCO AUTOMÁTICO
   ========================================================= */

$nomes = array_values(
    array_filter(
        $nomes,
        function ($nome) use ($nomeUser) {
            return mb_strtolower(trim($nome), 'UTF-8')
                !== mb_strtolower(trim($nomeUser), 'UTF-8');
        }
    )
);

shuffle($nomes);

$jogadores = [];

for ($i = 0; $i < $qtd - 1; $i++) {
    $nomeAleatorio =
        $nomes[$i] ?? ('Participante ' . ($i + 1));

    $jogadores[] = [
        'nome' => $nomeAleatorio,
        'idade' => rand(18, 55),
        'profissao' => $profissoesNPC[array_rand($profissoesNPC)],
        'estado' => $estados[array_rand($estados)],
        'personalidade' => $personalidades[array_rand($personalidades)],
        'genero' => generoNomeConhecidoBBB($nomeAleatorio) ?? 'nao_informado',
        'popularidade' => rand(40, 60),
        'humor' => rand(40, 60),
        'origem' => 'elenco_inicial',
        'status' => [
            'lider' => false,
            'anjo' => false,
            'imune' => false,
            'vip' => false,
            'xepa' => true,
            'monstro' => false
        ],
        'relacoes' => [],
        'romances' => [],
        'confessionarios' => []
    ];
}

$jogadores[] = $meuJogador;

shuffle($jogadores);

/* =========================================================
   ❤️ RELAÇÕES INICIAIS
   ========================================================= */

foreach ($jogadores as &$j1) {
    foreach ($jogadores as $j2) {
        if (
            mb_strtolower($j1['nome'], 'UTF-8')
            ===
            mb_strtolower($j2['nome'], 'UTF-8')
        ) {
            continue;
        }

        $j1['relacoes'][$j2['nome']] = [
            'amizade' => rand(20, 80),
            'rivalidade' => rand(0, 50),
            'confianca' => rand(20, 80)
        ];
    }
}

unset($j1);

$_SESSION['jogadores'] = array_values($jogadores);

header('Location: jogo.php');
exit;
