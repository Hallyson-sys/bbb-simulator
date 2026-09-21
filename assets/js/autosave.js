const BBB_AUTOSAVE_KEY = "bbb_simulator_save_v1";

async function salvarBBBLocalmente() {
    try {
        const resposta = await fetch(
            "save_snapshot.php?_=" + Date.now(),
            {
                method: "GET",
                credentials: "same-origin",
                cache: "no-store"
            }
        );

        if (!resposta.ok) return;

        const dados = await resposta.json();

        if (!dados.ativo || !dados.snapshot) {
            return;
        }

        const save = {
            saved_at: new Date().toISOString(),
            resumo: dados.resumo || {},
            snapshot: dados.snapshot
        };

        localStorage.setItem(
            BBB_AUTOSAVE_KEY,
            JSON.stringify(save)
        );

    } catch (erro) {
        /*
         * Se a internet cair, o último save válido continua
         * guardado no navegador. Por isso não removemos nada.
         */
        console.warn("Autosave indisponível no momento.");
    }
}

document.addEventListener("DOMContentLoaded", () => {
    salvarBBBLocalmente();

    setInterval(
        salvarBBBLocalmente,
        15000
    );
});

document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "hidden") {
        salvarBBBLocalmente();
    }
});
