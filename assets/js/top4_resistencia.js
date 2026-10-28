(() => {
  'use strict';
  const jogo = document.getElementById('jogoEquilibrio');
  if (!jogo) return;
  const ponteiro = document.getElementById('ponteiro');
  const travar = document.getElementById('travar');
  const repetir = document.getElementById('repetir');
  const status = document.getElementById('statusMarcador');
  const form = document.getElementById('formTop4');
  const confirmar = document.getElementById('confirmar');
  const campo = document.getElementById('marcadorFinal');
  const alvo = Number(jogo.dataset.alvo);
  let travado = false, valor = 0, inicio = null, quadro = 0, repeticoes = 0;
  const desenhar = t => {
    if (travado) return;
    if (inicio === null) inicio = t;
    const fase = ((t - inicio) % 2500) / 2500;
    // Movimento contínuo de ida e volta, sincronizado com requestAnimationFrame.
    valor = Math.round(100 * (fase < .5 ? fase * 2 : (1 - fase) * 2));
    ponteiro.style.left = valor + '%';
    quadro = requestAnimationFrame(desenhar);
  };
  travar.addEventListener('click', () => {
    if (travado) return;
    travado = true;
    cancelAnimationFrame(quadro);
    campo.value = String(valor);
    const diferenca = Math.abs(alvo - valor);
    status.textContent = diferenca <= 5 ? '✨ Excelente equilíbrio!' : diferenca <= 12 ? '✅ Você conseguiu se segurar!' : '⚠️ O movimento exigirá mais energia.';
    jogo.classList.add('travado');
    travar.hidden = true;
    repetir.hidden = repeticoes >= 1;
    confirmar.disabled = false;
  });
  repetir.addEventListener('click', () => {
    if (repeticoes >= 1) return;
    repeticoes++;
    travado = false; campo.value = ''; inicio = null;
    jogo.classList.remove('travado');
    status.textContent = 'O marcador está em movimento...';
    travar.hidden = false; repetir.hidden = true; confirmar.disabled = true;
    quadro = requestAnimationFrame(desenhar);
  });
  form.addEventListener('submit', e => {
    if (!travado || !form.querySelector('input[name="postura"]:checked')) {
      e.preventDefault();
      status.textContent = 'Escolha uma postura e trave o marcador primeiro.';
      return;
    }
    confirmar.disabled = true;
  });
  quadro = requestAnimationFrame(desenhar);
})();
