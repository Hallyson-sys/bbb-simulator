(function () {
    const mq = window.matchMedia('(max-width: 768px)');

    function isMobile() {
        return mq.matches;
    }

    function painelAoVivo() {
        return document.getElementById('mobileAoVivo') || document.querySelector('[data-mobile-panel="aovivo"]');
    }

    function logAoVivo() {
        return document.getElementById('aoVivoLog');
    }

    function ultimoAcontecimentoAoVivo() {
        const log = logAoVivo();
        if (!log) return null;
        return log.querySelector('[data-ao-vivo-ultimo="1"]') || log.lastElementChild;
    }

    function marcarNavAtiva(navEl) {
        document.querySelectorAll('.mobile-nav-item').forEach(item => item.classList.remove('ativo'));
        if (navEl) navEl.classList.add('ativo');
    }

    function abrirPainelAoVivo() {
        const painel = painelAoVivo();
        if (!painel) return false;

        painel.classList.remove('mobile-collapsed');
        const toggle = painel.querySelector('[data-mobile-toggle="aovivo"]');
        if (toggle) toggle.setAttribute('aria-expanded', 'true');
        return true;
    }

    function rolarFeedAoVivoParaUltimo(comAnimacao) {
        const log = logAoVivo();
        if (!log) return;

        // No mobile o Ao Vivo possui uma área de rolagem própria. Usar scrollHeight
        // é mais confiável que scrollIntoView, pois não mistura o scroll da página
        // com o scroll interno do feed.
        const destino = Math.max(0, log.scrollHeight - log.clientHeight);

        try {
            log.scrollTo({
                top: destino,
                left: 0,
                behavior: comAnimacao ? 'smooth' : 'auto'
            });
        } catch (e) {
            log.scrollTop = destino;
        }

        // Garante o valor mesmo em Safari/iOS, que pode ignorar scrollTo durante
        // a mesma etapa em que um elemento acabou de sair de display:none.
        if (!comAnimacao) log.scrollTop = log.scrollHeight;
    }

    function rolarPaginaAteAoVivo(comAnimacao) {
        const painel = painelAoVivo();
        if (!painel) return;

        const topo = Math.max(0, painel.getBoundingClientRect().top + window.scrollY - 76);
        try {
            window.scrollTo({
                top: topo,
                behavior: comAnimacao ? 'smooth' : 'auto'
            });
        } catch (e) {
            window.scrollTo(0, topo);
        }
    }

    function sincronizarAoVivo({ rolarPagina = false, paginaSuave = false, feedSuave = false } = {}) {
        if (!abrirPainelAoVivo()) return;

        // Primeira tentativa imediatamente.
        rolarFeedAoVivoParaUltimo(false);
        if (rolarPagina && isMobile()) rolarPaginaAteAoVivo(paginaSuave);

        // O painel recolhível, fontes e cards podem alterar a altura depois do clique.
        // Repetimos em momentos curtos para deixar o Safari/iOS e o Chrome consistentes.
        const tentativas = [0, 60, 180, 360, 650];
        tentativas.forEach((atraso, indice) => {
            setTimeout(() => {
                rolarFeedAoVivoParaUltimo(feedSuave && indice === 0);
                if (rolarPagina && indice === 1 && isMobile()) rolarPaginaAteAoVivo(false);
            }, atraso);
        });
    }

    window.mobileAbrirAoVivo = function (navEl) {
        if (!abrirPainelAoVivo()) return;
        marcarNavAtiva(navEl);
        sincronizarAoVivo({ rolarPagina: true, paginaSuave: true, feedSuave: false });
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
        marcarNavAtiva(navEl);
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

    function configurarAtalhoAoVivo() {
        const nav = document.querySelector('[data-mobile-nav="aovivo"]');
        if (!nav || nav.dataset.liveReady === '1') return;
        nav.dataset.liveReady = '1';

        // Listener próprio em vez de depender apenas de onclick inline. Isso também
        // evita falhas em navegadores móveis que restauram páginas do cache (bfcache).
        nav.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            window.mobileAbrirAoVivo(nav);
        });
    }

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

                if (!fechando && nome === 'aovivo') {
                    sincronizarAoVivo({ rolarPagina: true, paginaSuave: true, feedSuave: false });
                }
            });
        });

        if (isMobile()) {
            const casa = document.querySelector('[data-mobile-panel="casa"]');
            const aoVivo = painelAoVivo();
            if (casa && !casa.dataset.mobileInitial) {
                casa.classList.add('mobile-collapsed');
                casa.dataset.mobileInitial = '1';
            }
            if (aoVivo && !aoVivo.dataset.mobileInitial) {
                aoVivo.classList.add('mobile-collapsed');
                aoVivo.dataset.mobileInitial = '1';
            }
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
            if (!menu.hidden && !menu.contains(e.target) && e.target !== btn) window.fecharMenuMobile();
        });
    }

    function observarNovosAcontecimentos() {
        const log = logAoVivo();
        if (!log || typeof MutationObserver === 'undefined') return;

        const observer = new MutationObserver(() => {
            const painel = painelAoVivo();
            if (!isMobile() || !painel || painel.classList.contains('mobile-collapsed')) return;
            setTimeout(() => rolarFeedAoVivoParaUltimo(false), 0);
        });
        observer.observe(log, { childList: true, subtree: false });
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

    function iniciar() {
        configurarPaineis();
        configurarAtalhoAoVivo();
        configurarMenu();
        configurarAcaoFixa();
        observarNovosAcontecimentos();

        // No desktop, o Ao Vivo inicia no acontecimento mais recente.
        if (!isMobile()) setTimeout(() => rolarFeedAoVivoParaUltimo(false), 60);
    }

    document.addEventListener('DOMContentLoaded', iniciar);

    // Safari/iOS pode restaurar a página via bfcache sem um novo DOMContentLoaded.
    window.addEventListener('pageshow', () => {
        configurarAtalhoAoVivo();
    });
})();
