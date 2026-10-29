/* Filtros do Feed BBB 3.0 — não recarrega a temporada nem altera votos. */
(function () {
    const modal = document.getElementById('feedPublicoModal');
    if (!modal) return;
    const botoes = Array.from(modal.querySelectorAll('.feed3-filtro'));
    const posts = Array.from(modal.querySelectorAll('.feed-post[data-feed3-tipo]'));
    const vazio = modal.querySelector('#feed3SemResultados');
    function filtrar(tipo) {
        let visiveis = 0;
        for (const post of posts) {
            const mostrar = tipo === 'todos' || post.dataset.feed3Tipo === tipo;
            post.hidden = !mostrar;
            if (mostrar) visiveis++;
        }
        for (const botao of botoes) {
            const ativo = botao.dataset.filtro === tipo;
            botao.classList.toggle('ativo', ativo);
            botao.setAttribute('aria-pressed', String(ativo));
        }
        if (vazio) vazio.hidden = visiveis !== 0;
    }
    for (const botao of botoes) {
        botao.addEventListener('click', () => filtrar(botao.dataset.filtro || 'todos'));
    }
    filtrar('todos');
})();
