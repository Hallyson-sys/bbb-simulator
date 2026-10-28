<?php
/** Convite feito por NPC, exige decisão explícita do jogador. */
if (isset($_POST['a3_responder_convite'])) {
    $convite=$_SESSION['a3_convite_pendente']??null;
    $escolha=(string)($_POST['a3_responder_convite']??'');
    if(is_array($convite) && in_array($escolha,['aceitar','recusar'],true) &&
       empty($_SESSION['modo_espectador']) && ($_SESSION['fase_semana']??'')==='interacoes_1' &&
       (int)($convite['rodada']??0)===(int)($_SESSION['rodada']??1)) {
        $autor=(string)($convite['autor']??'');
        $grupo=(string)($convite['grupo']??'');
        $autorAtivo=false;
        foreach($jogadores as $j) if(nomeIgual((string)($j['nome']??''),$autor) && empty($j['eliminado'])) {
            $autorAtivo=true;
            if($grupo!=='' && (string)($j['alianca']??'')!==$grupo)$autorAtivo=false;
        }
        if($escolha==='aceitar' && $autorAtivo && !obterAliancaJogador($jogadores,$meuNome)) {
            if($grupo!=='' && tamanhoAlianca($jogadores,$grupo)<5)
                $res=entrarEmAlianca($jogadores,$meuNome,$grupo);
            elseif($grupo==='') $res=criarAliancaEntre($jogadores,$autor,$meuNome);
            else $res='O grupo já está completo.';
            $_SESSION['a3_convite_resposta']=$res ?: 'O convite não pôde ser concluído.';
        } else {
            $_SESSION['a3_convite_resposta']=$escolha==='recusar'?'Você recusou o convite de '.$autor.'.':'O convite não está mais disponível.';
        }
        unset($_SESSION['a3_convite_pendente']);
        $_SESSION['jogadores']=$jogadores;
    }
    header('Location: jogo.php'); exit;
}
