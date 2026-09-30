(function () {
    const STORAGE_KEY = 'bbb_simulator_saves_v2';
    const LEGACY_KEY = 'bbb_simulator_save_v1';

    function estruturaVazia() {
        return {
            version: 2,
            slots: {
                '1': null,
                '2': null,
                '3': null
            },
            recovery: null
        };
    }

    function normalizar(dados) {
        const base = estruturaVazia();

        if (!dados || typeof dados !== 'object') {
            return base;
        }

        if (dados.slots && typeof dados.slots === 'object') {
            ['1', '2', '3'].forEach((slot) => {
                const item = dados.slots[slot];
                base.slots[slot] = item && item.snapshot ? item : null;
            });
        }

        if (dados.recovery && dados.recovery.snapshot) {
            base.recovery = dados.recovery;
        }

        return base;
    }

    function migrarLegadoSeNecessario() {
        if (localStorage.getItem(STORAGE_KEY)) {
            return;
        }

        const bruto = localStorage.getItem(LEGACY_KEY);
        if (!bruto) return;

        try {
            const antigo = JSON.parse(bruto);
            if (!antigo || !antigo.snapshot) return;

            const dados = estruturaVazia();
            const resumo = antigo.resumo || {};
            const seasonId =
                antigo.snapshot.id_temporada ||
                ('legacy_' + Date.now());

            dados.slots['1'] = {
                version: 2,
                season_id: seasonId,
                name: resumo.nome
                    ? `Temporada de ${resumo.nome}`
                    : 'Temporada importada',
                saved_at: antigo.saved_at || new Date().toISOString(),
                resumo,
                snapshot: antigo.snapshot
            };

            localStorage.setItem(STORAGE_KEY, JSON.stringify(dados));
            localStorage.removeItem(LEGACY_KEY);
        } catch (erro) {
            console.warn('Não foi possível migrar o save antigo.', erro);
        }
    }

    function ler() {
        migrarLegadoSeNecessario();

        try {
            const bruto = localStorage.getItem(STORAGE_KEY);
            if (!bruto) return estruturaVazia();
            return normalizar(JSON.parse(bruto));
        } catch (erro) {
            console.warn('Não foi possível ler os saves.', erro);
            return estruturaVazia();
        }
    }

    function gravar(dados) {
        localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify(normalizar(dados))
        );
    }

    function formatarData(dataIso, comData = true) {
        if (!dataIso) return 'Ainda não salvo';

        const data = new Date(dataIso);
        if (Number.isNaN(data.getTime())) return 'Save recente';

        if (!comData) {
            return data.toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        return data.toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function nomePadrao(resumo = {}) {
        const nome = (resumo.nome || 'Jogador').trim();
        return `Temporada de ${nome}`;
    }

    function acharSlotDaTemporada(dados, seasonId) {
        if (!seasonId) return null;

        for (const slot of ['1', '2', '3']) {
            if (dados.slots[slot]?.season_id === seasonId) {
                return slot;
            }
        }

        return null;
    }

    function primeiroSlotVazio(dados) {
        return ['1', '2', '3'].find((slot) => !dados.slots[slot]) || null;
    }

    function montarSave(payload, nome = '') {
        const resumo = payload.resumo || {};
        const snapshot = payload.snapshot || {};
        const seasonId =
            payload.season_id ||
            snapshot.id_temporada ||
            ('season_' + Date.now());

        return {
            version: 2,
            season_id: seasonId,
            name: (nome || nomePadrao(resumo)).trim(),
            saved_at: new Date().toISOString(),
            resumo,
            snapshot
        };
    }

    function removerSlot(slot) {
        const dados = ler();

        if (slot === 'recovery') {
            dados.recovery = null;
        } else if (['1', '2', '3'].includes(String(slot))) {
            dados.slots[String(slot)] = null;
        }

        gravar(dados);
        return dados;
    }

    function renomearSlot(slot, nome) {
        const dados = ler();
        const alvo = dados.slots[String(slot)];

        if (!alvo) return false;

        const limpo = String(nome || '').trim().slice(0, 60);
        if (!limpo) return false;

        alvo.name = limpo;
        gravar(dados);
        return true;
    }

    window.BBBSave2 = {
        STORAGE_KEY,
        LEGACY_KEY,
        ler,
        gravar,
        formatarData,
        nomePadrao,
        acharSlotDaTemporada,
        primeiroSlotVazio,
        montarSave,
        removerSlot,
        renomearSlot
    };
})();
