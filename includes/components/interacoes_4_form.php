<?php
/** Campos usados somente para ações i4; formAcao é o form pai. */
$i4Config=i4Catalogo()[$acaoSelecionada];$i4Ativos=i4Ativos($jogadores);
?>
<div class="i4-selecao">
    <div class="i4-kicker">CONVERSAS VIVAS • ESCOLHA COMO AGIR</div>
    <h3><?= i4Esc($i4Config[0].' '.$i4Config[1]) ?></h3>
    <p><?= i4Esc($i4Config[2]) ?></p>
    <label for="i4_alvo">Com quem você quer interagir?</label>
    <select name="alvo" id="i4_alvo" required>
        <option value="">Escolha o participante</option>
        <?php foreach($i4Ativos as $nome=>$j): if($nome===$meuNome)continue; ?>
            <option value="<?= i4Esc($nome) ?>"<?= $acaoSelecionada==='i4_romance'?' data-romance-etapa="'.i4Esc(i4EtapaRomance($jogadores,$meuNome,$nome)).'"':'' ?>><?= i4Esc($nome) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if($acaoSelecionada==='i4_mediar'): ?>
        <label for="i4_outro">Com quem a pessoa precisa conversar?</label>
        <select name="alvo2" id="i4_outro" required>
            <option value="">Escolha outro participante</option>
            <?php foreach($i4Ativos as $nome=>$j): if($nome===$meuNome)continue; ?>
                <option value="<?= i4Esc($nome) ?>"><?= i4Esc($nome) ?></option>
            <?php endforeach; ?>
        </select>
        <p class="i4-dica">O jogo só permite duas pessoas diferentes e não garante que elas façam as pazes.</p>
    <?php endif; ?>
    <?php if($acaoSelecionada==='i4_romance'): ?>
    <p id="i4_estado_romance" class="i4-dica" role="status" aria-live="polite">Escolha uma pessoa para ver opções adequadas à relação de vocês.</p>
    <?php endif; ?>
    <label for="i4_assunto">Qual assunto ou atividade?</label>
    <select name="i4_assunto" id="i4_assunto" required>
        <?php foreach(i4Assuntos($acaoSelecionada) as $id=>$texto): ?>
            <option value="<?= i4Esc($id) ?>"><?= i4Esc($texto) ?></option>
        <?php endforeach; ?>
    </select>
    <label for="i4_abordagem">Como você vai abordar?</label>
    <select name="i4_abordagem" id="i4_abordagem" required>
        <?php foreach(i4Abordagens() as $id=>$texto): ?>
            <option value="<?= i4Esc($id) ?>"><?= i4Esc($texto) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if($acaoSelecionada==='i4_informacao'): ?>
        <label for="i4_conhecimento">Qual descoberta você vai compartilhar?</label>
        <select name="i4_conhecimento" id="i4_conhecimento" required>
            <option value="">Escolha uma descoberta real</option>
            <?php foreach(i4Conhecimentos() as $id=>$c): ?>
                <option value="<?= i4Esc($id) ?>"><?= i4Esc($c['titulo']??$c['texto']??'Descoberta') ?></option>
            <?php endforeach; ?>
        </select>
        <p class="i4-dica">Você só pode compartilhar conhecimentos já descobertos e ainda válidos.</p>
    <?php endif; ?>
    <?php if($acaoSelecionada==='i4_romance'): ?>
    <script>
    (function(){
      const pessoa=document.getElementById('i4_alvo');
      const assunto=document.getElementById('i4_assunto');
      const aviso=document.getElementById('i4_estado_romance');
      if(!pessoa||!assunto||!aviso)return;
      const temas=<?= json_encode([
          'inicio'=>i4AssuntosRomance('inicio'),
          'aproximacao'=>i4AssuntosRomance('aproximacao'),
          'namoro'=>i4AssuntosRomance('namoro'),
          'comprometido'=>i4AssuntosRomance('comprometido')
      ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
      const mensagens={
        inicio:'💗 Vocês ainda estão se conhecendo. Você pode demonstrar interesse.',
        aproximacao:'💕 Já existe uma aproximação. A conversa pode aprofundar os sentimentos.',
        namoro:'💍 Vocês já estão namorando! As ações agora são de carinho e cuidado com o relacionamento.',
        comprometido:'🤝 Já existe outro relacionamento. A conversa respeitará esses limites.'
      };
      function atualizar(){
        const etapa=pessoa.selectedOptions[0]?.dataset?.romanceEtapa||'inicio';
        const dados=temas[etapa]||temas.inicio;
        const valorAnterior=assunto.value;
        assunto.replaceChildren();
        Object.entries(dados).forEach(([valor,legenda])=>{
          const opc=document.createElement('option');opc.value=valor;opc.textContent=legenda;assunto.appendChild(opc);
        });
        if(dados[valorAnterior])assunto.value=valorAnterior;
        aviso.textContent=pessoa.value?(mensagens[etapa]||mensagens.inicio):'Escolha uma pessoa para ver opções adequadas à relação de vocês.';
      }
      pessoa.addEventListener('change',atualizar);atualizar();
    })();
    </script>
    <?php endif; ?>
    <button class="btn" type="submit">💬 Iniciar conversa</button>
    <small>A decisão não é automática: você poderá responder depois de ouvir o NPC.</small>
</div>
