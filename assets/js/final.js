/* =========================================================
   🏆 GRANDE FINAL — BBB SIMULATOR
   Revelação de 3º lugar, 2º lugar e campeão
   ========================================================= */

   document.addEventListener('DOMContentLoaded', function () {

    let etapa = 0;

    const dadosFinal =
        window.FINAL_DATA || {};

    const percentuais =
        dadosFinal.percentuais || {};

    const nomes =
        dadosFinal.nomes || {};


    const btnRevelar =
        document.getElementById('btnRevelar');

    const btnNovo =
        document.getElementById('btnNovo');

    const fala =
        document.getElementById('fala');


    /* =====================================================
       📊 FORMATAR PORCENTAGEM
       ===================================================== */

    function formatarPercentual(valor) {

        const numero =
            Number(valor || 0);

        return numero
            .toFixed(2)
            .replace('.', ',');
    }


    /* =====================================================
       🔘 ATUALIZAR PONTOS DA REVELAÇÃO
       ===================================================== */

    function atualizarPontos() {

        for (let i = 0; i <= 3; i++) {

            const ponto =
                document.getElementById(
                    'ponto' + i
                );

            if (ponto) {

                ponto.classList.toggle(
                    'ativo',
                    i <= etapa
                );
            }
        }
    }


    /* =====================================================
       ✨ EFEITO DE REVELAÇÃO DO CARD
       ===================================================== */

    function efeitoRevelacao(id) {

        const card =
            document.getElementById(id);

        if (!card) {
            return;
        }


        card.classList.add(
            'revelando'
        );


        setTimeout(
            function () {

                card.classList.remove(
                    'revelando'
                );

            },
            1200
        );


        card.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }


    /* =====================================================
       🎊 CONFETES DO CAMPEÃO
       ===================================================== */

    function soltarConfete() {

        const cores = [
            '#ffd700',
            '#ff008c',
            '#00d9ff',
            '#7a00ff',
            '#ffffff',
            '#00ff99'
        ];


        for (
            let i = 0;
            i < 90;
            i++
        ) {

            const confete =
                document.createElement(
                    'div'
                );


            confete.className =
                'confete';


            confete.style.left =
                Math.random() * 100
                + 'vw';


            confete.style.background =
                cores[
                    Math.floor(
                        Math.random()
                        * cores.length
                    )
                ];


            confete.style.animationDelay =
                (
                    Math.random()
                    * 0.9
                )
                + 's';


            confete.style.transform =
                'rotate('
                + (
                    Math.random()
                    * 360
                )
                + 'deg';


            document.body.appendChild(
                confete
            );


            setTimeout(
                function () {

                    confete.remove();

                },
                4800
            );
        }
    }


    /* =====================================================
       🥉 REVELAR TERCEIRO LUGAR
       ===================================================== */

    function revelarTerceiro() {

        const nome =
            nomes.terceiro || '';

        const percentual =
            percentuais[nome] || 0;


        fala.innerHTML =
            '🎤 “O terceiro lugar fez história, resistiu, lutou... mas hoje para por aqui.”';


        const tag =
            document.getElementById(
                'tag-terceiro'
            );


        if (tag) {

            tag.innerHTML =
                '🥉 3º LUGAR'
                + '<br>'
                + '<small>'
                + formatarPercentual(
                    percentual
                )
                + '% dos votos'
                + '</small>';
        }


        const card =
            document.getElementById(
                'terceiro'
            );


        if (card) {

            card.classList.add(
                'revelado-terceiro'
            );
        }


        efeitoRevelacao(
            'terceiro'
        );


        if (btnRevelar) {

            btnRevelar.innerHTML =
                '🥈 Revelar 2º Lugar';
        }
    }


    /* =====================================================
       🥈 REVELAR SEGUNDO LUGAR
       ===================================================== */

    function revelarSegundo() {

        const nome =
            nomes.segundo || '';

        const percentual =
            percentuais[nome] || 0;


        fala.innerHTML =
            '🎤 “Entre o sonho e a vitória, alguém ficou muito perto. Em segundo lugar...”';


        const tag =
            document.getElementById(
                'tag-segundo'
            );


        if (tag) {

            tag.innerHTML =
                '🥈 2º LUGAR'
                + '<br>'
                + '<small>'
                + formatarPercentual(
                    percentual
                )
                + '% dos votos'
                + '</small>';
        }


        const card =
            document.getElementById(
                'segundo'
            );


        if (card) {

            card.classList.add(
                'revelado-segundo'
            );
        }


        efeitoRevelacao(
            'segundo'
        );


        if (btnRevelar) {

            btnRevelar.innerHTML =
                '👑 Revelar Campeão';
        }
    }


    /* =====================================================
       👑 REVELAR CAMPEÃO
       ===================================================== */

    function revelarCampeao() {

        const nome =
            nomes.primeiro || '';

        const percentual =
            percentuais[nome] || 0;


        fala.innerHTML =
            '🎤 “O público decidiu. O grande campeão da temporada é...”';


        const tag =
            document.getElementById(
                'tag-primeiro'
            );


        if (tag) {

            tag.innerHTML =
                '👑 CAMPEÃO'
                + '<br>'
                + '<small>'
                + formatarPercentual(
                    percentual
                )
                + '% dos votos'
                + '</small>';
        }


        const card =
            document.getElementById(
                'primeiro'
            );


        if (card) {

            card.classList.add(
                'revelado-campeao'
            );
        }


        efeitoRevelacao(
            'primeiro'
        );


        soltarConfete();


        if (btnRevelar) {

            btnRevelar.style.display =
                'none';
        }


        if (btnNovo) {

            btnNovo.style.display =
                'inline-block';
        }
    }


    /* =====================================================
       📺 CONTROLADOR DA REVELAÇÃO
       ===================================================== */

    function revelar() {

        if (etapa >= 3) {
            return;
        }


        etapa++;

        atualizarPontos();


        if (etapa === 1) {

            revelarTerceiro();
            return;
        }


        if (etapa === 2) {

            revelarSegundo();
            return;
        }


        if (etapa === 3) {

            revelarCampeao();
        }
    }


    /* =====================================================
       🖱️ EVENTOS
       ===================================================== */

    if (btnRevelar) {

        btnRevelar.addEventListener(
            'click',
            revelar
        );
    }

});