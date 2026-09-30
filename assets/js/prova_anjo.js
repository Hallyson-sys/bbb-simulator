(() => {
    const slug = document.body.dataset.minigame || '';
    const scoreInput = document.getElementById('pontuacao-anjo');
    const doneInput = document.getElementById('minigame-concluido');
    const finalArea = document.getElementById('finalizar-area');
    const finalText = document.getElementById('pontuacao-final-texto');

    function clamp(n, min = 0, max = 100) {
        return Math.max(min, Math.min(max, Math.round(n)));
    }

    function embaralhar(lista) {
        const arr = [...lista];
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]];
        }
        return arr;
    }

    function concluir(pontos) {
        pontos = clamp(pontos);
        scoreInput.value = String(pontos);
        doneInput.value = '1';
        finalText.textContent = String(pontos);
        finalArea.classList.remove('escondido');
        finalArea.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /* =====================================================
       🧠 MEMÓRIA DO ANJO
       ===================================================== */
    function iniciarMemoria() {
        const grade = document.getElementById('memoria-grade');
        const btn = document.getElementById('memoria-iniciar');
        const tempoEl = document.getElementById('memoria-tempo');
        const paresEl = document.getElementById('memoria-pares');
        const tentativasEl = document.getElementById('memoria-tentativas');
        if (!grade || !btn) return;

        const simbolos = ['🪽', '💎', '⭐', '💙', '🌙', '☁️'];
        let cartas = [];
        let primeira = null;
        let travado = false;
        let pares = 0;
        let tentativas = 0;
        let inicio = 0;
        let timer = null;

        btn.addEventListener('click', () => {
            if (btn.disabled) return;
            btn.disabled = true;
            btn.classList.add('sumiu');
            grade.innerHTML = '';
            cartas = embaralhar([...simbolos, ...simbolos]);
            inicio = Date.now();

            timer = setInterval(() => {
                const s = Math.floor((Date.now() - inicio) / 1000);
                tempoEl.textContent = `00:${String(s).padStart(2, '0')}`;
            }, 250);

            cartas.forEach((simbolo, index) => {
                const card = document.createElement('button');
                card.type = 'button';
                card.className = 'memoria-carta';
                card.dataset.valor = simbolo;
                card.dataset.index = String(index);
                card.innerHTML = '<span class="verso">?</span><span class="frente"></span>';
                card.querySelector('.frente').textContent = simbolo;
                grade.appendChild(card);

                card.addEventListener('click', () => {
                    if (travado || card.classList.contains('virada') || card.classList.contains('encontrada')) return;

                    card.classList.add('virada');

                    if (!primeira) {
                        primeira = card;
                        return;
                    }

                    tentativas++;
                    tentativasEl.textContent = String(tentativas);

                    if (primeira.dataset.valor === card.dataset.valor) {
                        primeira.classList.add('encontrada');
                        card.classList.add('encontrada');
                        primeira = null;
                        pares++;
                        paresEl.textContent = String(pares);

                        if (pares === simbolos.length) {
                            clearInterval(timer);
                            const segundos = (Date.now() - inicio) / 1000;
                            const erros = Math.max(0, tentativas - simbolos.length);
                            const score = 100 - erros * 7 - Math.max(0, segundos - 18) * 1.2;
                            concluir(score);
                        }
                    } else {
                        travado = true;
                        const anterior = primeira;
                        primeira = null;
                        setTimeout(() => {
                            anterior.classList.remove('virada');
                            card.classList.remove('virada');
                            travado = false;
                        }, 650);
                    }
                });
            });
        });
    }

    /* =====================================================
       🎁 CAIXAS MISTERIOSAS
       ===================================================== */
    function iniciarCaixas() {
        const grade = document.getElementById('caixas-grade');
        const pontosEl = document.getElementById('caixas-pontos');
        const escolhasEl = document.getElementById('caixas-escolhas');
        if (!grade) return;

        const valores = embaralhar([-15, 0, 10, 15, 20, 25, 30, 35]);
        let pontos = 40;
        let escolhas = 0;

        grade.querySelectorAll('.caixa-misteriosa').forEach((caixa, index) => {
            caixa.addEventListener('click', () => {
                if (caixa.classList.contains('aberta') || escolhas >= 3) return;

                const valor = valores[index] ?? 0;
                escolhas++;
                pontos = clamp(pontos + valor);

                caixa.classList.add('aberta');
                if (valor > 0) caixa.classList.add('positiva');
                if (valor < 0) caixa.classList.add('negativa');
                if (valor === 0) caixa.classList.add('neutra');

                caixa.querySelector('.caixa-icone').textContent = valor < 0 ? '💥' : valor === 0 ? '☁️' : '✨';
                caixa.querySelector('.caixa-valor').textContent = valor > 0 ? `+${valor}` : `${valor}`;

                pontosEl.textContent = String(pontos);
                escolhasEl.textContent = String(escolhas);

                if (escolhas === 3) {
                    grade.querySelectorAll('.caixa-misteriosa:not(.aberta)').forEach(el => el.disabled = true);
                    setTimeout(() => concluir(pontos), 550);
                }
            });
        });
    }

    /* =====================================================
       ⚡ SEQUÊNCIA RELÂMPAGO
       ===================================================== */
    function iniciarSequencia() {
        const btnStart = document.getElementById('sequencia-iniciar');
        const display = document.getElementById('sequencia-display');
        const opcoes = document.getElementById('sequencia-opcoes');
        const rodadaEl = document.getElementById('sequencia-rodada');
        const statusEl = document.getElementById('sequencia-status');
        if (!btnStart || !display || !opcoes) return;

        const sinais = ['🪽', '💎', '⭐', '💙'];
        let rodada = 1;
        let score = 0;
        let sequencia = [];
        let indiceClique = 0;
        let rodadaAtiva = false;

        function gerarSequencia(tamanho) {
            return Array.from({ length: tamanho }, () => Math.floor(Math.random() * sinais.length));
        }

        async function mostrarSequencia() {
            rodadaAtiva = false;
            opcoes.classList.add('bloqueado');
            rodadaEl.textContent = `RODADA ${rodada} DE 3`;
            statusEl.textContent = 'Memorize a ordem...';
            display.innerHTML = '';

            sequencia = gerarSequencia(rodada + 2);
            indiceClique = 0;

            for (const idx of sequencia) {
                const flash = document.createElement('span');
                flash.className = 'sequencia-flash';
                flash.textContent = sinais[idx];
                display.innerHTML = '';
                display.appendChild(flash);
                await new Promise(r => setTimeout(r, Math.max(430, 720 - rodada * 90)));
                display.innerHTML = '<span class="sequencia-placeholder">•</span>';
                await new Promise(r => setTimeout(r, 150));
            }

            display.innerHTML = '<span class="sequencia-placeholder">SUA VEZ</span>';
            statusEl.textContent = 'Repita exatamente a sequência.';
            opcoes.classList.remove('bloqueado');
            rodadaAtiva = true;
        }

        function finalizarRodada(acertou) {
            rodadaAtiva = false;
            opcoes.classList.add('bloqueado');

            if (acertou) {
                score += rodada === 1 ? 30 : rodada === 2 ? 33 : 37;
                statusEl.textContent = '✅ Sequência correta!';
            } else {
                statusEl.textContent = '❌ Sequência quebrada.';
            }

            if (rodada >= 3) {
                setTimeout(() => concluir(score), 700);
                return;
            }

            rodada++;
            setTimeout(mostrarSequencia, 900);
        }

        btnStart.addEventListener('click', () => {
            btnStart.disabled = true;
            btnStart.classList.add('sumiu');
            mostrarSequencia();
        });

        opcoes.querySelectorAll('button[data-sinal]').forEach(botao => {
            botao.addEventListener('click', () => {
                if (!rodadaAtiva) return;

                const escolhido = Number(botao.dataset.sinal);
                botao.classList.add('pressionado');
                setTimeout(() => botao.classList.remove('pressionado'), 180);

                if (escolhido !== sequencia[indiceClique]) {
                    finalizarRodada(false);
                    return;
                }

                indiceClique++;
                if (indiceClique >= sequencia.length) {
                    finalizarRodada(true);
                }
            });
        });
    }

    /* =====================================================
       🎯 MIRA DO ANJO
       ===================================================== */
    function iniciarMira() {
        const pista = document.getElementById('mira-pista');
        const marcador = document.getElementById('mira-marcador');
        const btn = document.getElementById('mira-botao');
        const tentativaEl = document.getElementById('mira-tentativa');
        const mediaEl = document.getElementById('mira-media');
        const ultimoEl = document.getElementById('mira-ultimo');
        if (!pista || !marcador || !btn) return;

        let tentativa = 1;
        let rodando = false;
        let animacao = null;
        let inicio = 0;
        let scores = [];

        function mover(ts) {
            if (!rodando) return;
            if (!inicio) inicio = ts;
            const tempo = (ts - inicio) / 1000;
            const x = 50 + Math.sin(tempo * 3.25) * 48;
            marcador.style.left = `${x}%`;
            animacao = requestAnimationFrame(mover);
        }

        function calcularPontos() {
            const pos = parseFloat(marcador.style.left || '50');
            const distancia = Math.abs(50 - pos);
            return clamp(100 - distancia * 2.05);
        }

        function iniciarTentativa() {
            rodando = true;
            inicio = 0;
            btn.textContent = '🪽 PARAR MARCADOR';
            btn.classList.add('parar');
            ultimoEl.textContent = 'Agora! Pare o marcador o mais perto possível do centro.';
            animacao = requestAnimationFrame(mover);
        }

        function pararTentativa() {
            rodando = false;
            cancelAnimationFrame(animacao);
            const pts = calcularPontos();
            scores.push(pts);
            const media = Math.round(scores.reduce((a, b) => a + b, 0) / scores.length);
            mediaEl.textContent = String(media);
            ultimoEl.textContent = pts >= 90
                ? `🔥 Perfeito! ${pts} pontos.`
                : pts >= 70
                    ? `✨ Muito perto! ${pts} pontos.`
                    : `🎯 Você marcou ${pts} pontos.`;

            btn.classList.remove('parar');

            if (tentativa >= 3) {
                btn.disabled = true;
                btn.textContent = '✅ TENTATIVAS CONCLUÍDAS';
                setTimeout(() => concluir(media), 650);
                return;
            }

            tentativa++;
            tentativaEl.textContent = String(tentativa);
            btn.textContent = '🎯 PRÓXIMA TENTATIVA';
        }

        btn.addEventListener('click', () => {
            if (btn.disabled) return;
            if (rodando) pararTentativa();
            else iniciarTentativa();
        });
    }

    if (slug === 'memoria') iniciarMemoria();
    if (slug === 'caixas') iniciarCaixas();
    if (slug === 'sequencia') iniciarSequencia();
    if (slug === 'mira') iniciarMira();
})();
