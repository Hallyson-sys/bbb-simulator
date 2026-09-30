<div class="save2-overlay" id="popupSave2" onclick="if(event.target === this) fecharSave2()">
    <section class="save2-modal" role="dialog" aria-modal="true" aria-labelledby="save2Titulo">
        <div class="save2-cabecalho">
            <div>
                <span class="save2-kicker">💾 SAVE 2.0</span>
                <h2 id="save2Titulo">Suas temporadas</h2>
                <p>Escolha um slot para salvar, carregar ou substituir uma temporada.</p>
            </div>

            <button type="button" class="save2-fechar" onclick="fecharSave2()" aria-label="Fechar">✕</button>
        </div>

        <div class="save2-slots" id="save2Slots"></div>

        <div class="save2-recovery" id="save2Recovery" hidden></div>

        <div class="save2-rodape">
            <span>🔄 Autosave a cada 15 segundos</span>
            <span>🛡️ Os 3 slots ficam salvos neste navegador</span>
        </div>
    </section>
</div>

<div class="save2-toast" id="save2Toast">✅ Jogo salvo com sucesso!</div>
