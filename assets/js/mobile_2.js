(function () {
    const mq = window.matchMedia('(max-width: 768px)');

    function isMobile() { return mq.matches; }


    function rolarLogAoVivoParaFim(comAnimacao) {
        const log = document.getElementById('aoVivoLog');
        if (!log) return;

        const destino = Math.max(0, log.scrollHeight - log.clientHeight);
        if (typeof log.scrollTo === 'function') {
            log.scrollTo({
                top: destino,
                behavior: comAnimacao ? 'smooth' : 'auto'
            });
        } else {
            log.scrollTop = destino;
        }
    }

    function posicionarAoVivoNoUltimo({ rolarPagina = false, animarLog = false } = {}) {
        const painel = document.querySelector('[data-mobile-panel="aovivo"]');
        const log = document.getElementById('aoVivoLog');
        if (!log) return;

        // Aguarda o painel sair de display:none e o navegador recalcular as alturas.
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                rolarLogAoVivoParaFim(animarLog);

                if (rolarPagina && isMobile() && painel) {
                    painel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                // Repete depois da rolagem/animação para neutralizar mudanças tardias de layout
                // (fontes, imagens e expansão do painel).
                setTimeout(() => rolarLogAoVivoParaFim(false), 220);
            });
        });
    }

    window.mobileAbrirAoVivo = function (navEl) {
        const painel = document.querySelector('[data-mobile-panel="aovivo"]');
        if (!painel) return;

        painel.classList.remove('mobile-collapsed');
        const toggle = painel.querySelector('[data-mobile-toggle="aovivo"]');
        if (toggle) toggle.setAttribute('aria-expanded', 'true');

        document.querySelectorAll('.mobile-nav-item').forEach(n => n.classList.remove('ativo'));
        if (navEl) navEl.classList.add('ativo');

        // Primeiro leva a página ao painel; depois posiciona o feed no acontecimento mais recente.
        posicionarAoVivoNoUltimo({ rolarPagina: true, animarLog: false });
    };

    window.fecharMenuMobile = function () {
        const menu = document.getElementById('mobileHeaderMenu');
        const btn = document.getElementById('mobileMenuBtn');
        if (menu) menu.hidden = true;
        if (btn) btn.setAttribute('aria-expanded', 'false');
    };

    window.mobileIrPara = function (id, navEl) {
        const el = document.getElementById(id);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.querySelectorAll('.mobile-nav-item').forEach(n => n.classList.remove('ativo'));
        if (navEl) navEl.classList.add('ativo');
    };

    window.mobileAbrirPainel = function (nome, id, navEl) {
        const painel = document.querySelector('[data-mobile-panel="' + nome + '"]');
        if (painel) {
            painel.classList.remove('mobile-collapsed');
            const toggle = painel.querySelector('[data-mobile-toggle="' + nome + '"]');
            if (toggle) toggle.setAttribute('aria-expanded', 'true');
        }
        window.mobileIrPara(id, navEl);
    };

    function configurarPaineis() {
        document.querySelectorAll('[data-mobile-toggle]').forEach(btn => {
            if (btn.dataset.mobileReady) return;
            btn.dataset.mobileReady = '1';
            btn.addEventListener('click', () => {
                const nome = btn.dataset.mobileToggle;
                const painel = document.querySelector('[data-mobile-panel="' + nome + '"]');
                if (!painel) return;
                const fechando = !painel.classList.contains('mobile-collapsed');
                painel.classList.toggle('mobile-collapsed', fechando);
                btn.setAttribute('aria-expanded', fechando ? 'false' : 'true');
                if (!fechando && nome === 'aovivo') posicionarAoVivoNoUltimo({ rolarPagina: true, animarLog: false });
            });
        });

        if (isMobile()) {
            const casa = document.querySelector('[data-mobile-panel="casa"]');
            const aoVivo = document.querySelector('[data-mobile-panel="aovivo"]');
            if (casa && !casa.dataset.mobileInitial) { casa.classList.add('mobile-collapsed'); casa.dataset.mobileInitial = '1'; }
            if (aoVivo && !aoVivo.dataset.mobileInitial) { aoVivo.classList.add('mobile-collapsed'); aoVivo.dataset.mobileInitial = '1'; }
        }
    }

    function configurarMenu() {
        const btn = document.getElementById('mobileMenuBtn');
        const menu = document.getElementById('mobileHeaderMenu');
        if (!btn || !menu) return;
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            menu.hidden = !menu.hidden;
            btn.setAttribute('aria-expanded', menu.hidden ? 'false' : 'true');
        });
        document.addEventListener('click', (e) => {
            if (!menu.hidden && !menu.contains(e.target) && e.target !== btn) fecharMenuMobile();
        });
    }

    function candidatoAcao() {
        const center = document.querySelector('.center');
        if (!center) return null;
        const candidatos = Array.from(center.querySelectorAll(
            'button[type="submit"], input[type="submit"], form button:not([type="button"]), .btn-confirmar-querido'
        ));
        return candidatos.find(el => {
            const style = getComputedStyle(el);
            const rect = el.getBoundingClientRect();
            const texto = (el.innerText || el.value || '').trim().toLowerCase();
            const ignorar = /limpar|cancelar|voltar|perfil|temporada/.test(texto);
            return !ignorar && style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && !el.disabled;
        }) || null;
    }

    function configurarAcaoFixa() {
        const dock = document.getElementById('mobileActionDock');
        const proxy = document.getElementById('mobileActionButton');
        if (!dock || !proxy) return;

        function atualizar() {
            if (!isMobile()) {
                dock.hidden = true;
                document.body.classList.remove('mobile-has-action');
                return;
            }
            const alvo = candidatoAcao();
            if (!alvo) {
                dock.hidden = true;
                document.body.classList.remove('mobile-has-action');
                return;
            }
            const rect = alvo.getBoundingClientRect();
            const visivel = rect.top >= 70 && rect.bottom <= (window.innerHeight - 125);
            dock.hidden = visivel;
            document.body.classList.toggle('mobile-has-action', !visivel);
            proxy.textContent = (alvo.innerText || alvo.value || 'CONTINUAR →').trim();
            proxy.onclick = () => alvo.click();
        }

        atualizar();
        window.addEventListener('scroll', atualizar, { passive: true });
        window.addEventListener('resize', atualizar);
        document.addEventListener('change', () => setTimeout(atualizar, 0));
        setTimeout(atualizar, 300);
    }

    document.addEventListener('DOMContentLoaded', () => {
        configurarPaineis();
        configurarMenu();
        configurarAcaoFixa();
        // No desktop, o Ao Vivo já começa no acontecimento mais recente.
        // No mobile, ele permanece recolhido e salta ao último item ao ser aberto.
        if (!isMobile()) setTimeout(() => posicionarAoVivoNoUltimo({ rolarPagina: false, animarLog: false }), 60);
    });
})();
