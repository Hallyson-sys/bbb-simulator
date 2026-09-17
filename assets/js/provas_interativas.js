document.addEventListener('DOMContentLoaded',()=>{iniciarProvaReflexo();iniciarProvaMemoria();});

function iniciarProvaReflexo(){
    const botao=document.getElementById('btnReflexo');
    const status=document.getElementById('reflexoStatus');
    const instrucao=document.getElementById('reflexoInstrucao');
    const campo=document.getElementById('tempoReacaoMs');
    const form=document.getElementById('formReflexo');
    if(!botao||!status||!campo||!form) return;

    let liberado=false;
    let inicio=0;
    const espera=1800+Math.floor(Math.random()*2700);

    setTimeout(()=>{
        liberado=true;
        inicio=performance.now();
        status.textContent='CLIQUE AGORA!';
        status.classList.remove('aguardando');
        status.classList.add('liberado');
        botao.disabled=false;
        botao.textContent='⚡ CLIQUE!';
        if(instrucao) instrucao.textContent='VALENDO! Clique o mais rápido possível.';
    },espera);

    botao.addEventListener('click',()=>{
        if(!liberado) return;
        liberado=false;
        const tempo=Math.max(1,Math.round(performance.now()-inicio));
        campo.value=String(tempo);
        botao.disabled=true;
        botao.textContent=`${tempo} ms`;
        status.textContent='TEMPO REGISTRADO';
        status.classList.remove('liberado');
        status.classList.add('finalizado');
        if(instrucao) instrucao.textContent='Comparando seu tempo com os outros participantes...';
        setTimeout(()=>form.submit(),900);
    });
}

function iniciarProvaMemoria(){
    const seq=document.getElementById('memoriaSequencia');
    const contador=document.getElementById('memoriaContador');
    const fase=document.getElementById('memoriaFase');
    const form=document.getElementById('formMemoria');
    if(!seq||!contador||!fase||!form) return;

    let tempo=5;
    contador.textContent=String(tempo);

    const intervalo=setInterval(()=>{
        tempo--;
        contador.textContent=String(Math.max(tempo,0));
        if(tempo<=0){
            clearInterval(intervalo);
            seq.classList.add('sumiu');
            contador.classList.add('escondido');
            fase.textContent='AGORA RESPONDA';
            form.classList.remove('escondido');
            const primeiro=form.querySelector('input[type="text"]');
            if(primeiro) primeiro.focus();
        }
    },1000);
}
