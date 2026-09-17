/* =========================================================
   📱 FEED BBB
   ========================================================= */


/* =========================================================
   ABRIR
   ========================================================= */

   window.abrirFeedPublico = function(){

    const modal =
        document.getElementById(
            'feedPublicoModal'
        );

    if(!modal){

        console.error(
            'Feed BBB: #feedPublicoModal não foi encontrado.'
        );

        return;
    }


    modal.classList.add(
        'ativo'
    );


    modal.setAttribute(
        'aria-hidden',
        'false'
    );


    document.body.classList.add(
        'feed-publico-aberto'
    );
};



/* =========================================================
   FECHAR
   ========================================================= */

window.fecharFeedPublico = function(){

    const modal =
        document.getElementById(
            'feedPublicoModal'
        );

    if(!modal){
        return;
    }


    modal.classList.remove(
        'ativo'
    );


    modal.setAttribute(
        'aria-hidden',
        'true'
    );


    document.body.classList.remove(
        'feed-publico-aberto'
    );
};



/* =========================================================
   ESC
   ========================================================= */

document.addEventListener(
    'keydown',
    function(event){

        if(event.key === 'Escape'){

            window.fecharFeedPublico();
        }
    }
);