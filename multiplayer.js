/* BBB Simulator — Multiplayer Beta 2 | recepção da sala */
(() => {
'use strict';
const $ = (id) => document.getElementById(id);
let tab='create',room=null,code='',token='',busy=false,updating=false;
const lastKey='bbb_mp_beta2_last';const storeKey=(c)=>`bbb_mp_beta2_${c}`;
const storage=(key,value)=>{try {if(value===undefined)return localStorage.getItem(key);localStorage.setItem(key,value);}catch(e){return null;}};
const notice=(text,error=false)=>{const el=$('notice');el.textContent=text;el.className='mp-notice'+(error?' error':'');el.hidden=false;};
const clear=()=>$('notice').hidden=true;
const goGame=()=>{const url=new URL('multiplayer_jogo.php',location.href);url.searchParams.set('sala',code);location.assign(url.href);};
function setTab(t){tab=t;const create=t==='create';$('createFields').hidden=!create;$('joinFields').hidden=create;$('roomCode').required=!create;$('tabCreate').classList.toggle('active',create);$('tabJoin').classList.toggle('active',!create);$('submitRoom').textContent=create?'CRIAR MINHA SALA →':'ENTRAR NA SALA →';}
async function req(action,fields={}){const res=await fetch('multiplayer_api.php',{method:'POST',headers:{'Content-Type':'application/json'},cache:'no-store',body:JSON.stringify({action,...fields})});let data;try{data=await res.json();}catch(e){throw Error('Servidor não respondeu corretamente.');}if(!res.ok||!data.ok)throw Error(data.erro||'Ocorreu um erro.');return data;}
function save(c,t,name){code=c;token=t;storage(storeKey(c),JSON.stringify({token:t,name}));storage(lastKey,c);const u=new URL(location.href);u.searchParams.set('sala',c);history.replaceState({},'',u.href);}
function render(r){room=r;if(r.phase!=='lobby'){goGame();return;}
$('entry').hidden=true;$('room').hidden=false;$('roomCodeDisplay').textContent=r.code;
const ready=r.players.filter(p=>p.ready).length;const isHost=r.myId===r.hostId;const me=r.players.find(p=>p.id===r.myId);
$('countLabel').textContent=`${r.players.length}/${r.capacity}`;$('progressLabel').textContent=`${ready}/${r.players.length} prontos`;
$('progressBar').style.width=`${Math.round(100*ready/Math.max(1,r.players.length))}%`;
$('playersList').replaceChildren();r.players.forEach(p=>{const el=document.createElement('div');el.className='mp-lobby-person';const title=document.createElement('strong');title.textContent=`${p.id===r.hostId?'👑 ':''}${p.name}${p.id===r.myId?' ⭐':''}`;const st=document.createElement('span');st.textContent=p.ready?'✓ Pronto':'◷ Aguardando';el.append(title,st);$('playersList').appendChild(el);});
$('readyButton').textContent=me?.ready?'✓ ESTOU PRONTO — DESFAZER':'✓ ESTOU PRONTO';$('readyButton').classList.toggle('mp-secondary',!!me?.ready);
$('hostControls').hidden=!isHost;$('startButton').disabled=!isHost||r.players.length<2||ready!==r.players.length;
$('lobbyHint').textContent=isHost?'Você é o anfitrião. Inicie quando todos marcarem “Estou pronto”.':'Espere o anfitrião iniciar a temporada.';
}
async function poll(){if(!code||!token||updating||busy)return;updating=true;try{render((await req('status',{code,token})).room);}catch(e){notice('Conexão com a sala: '+e.message,true);}finally{updating=false;}}
async function act(action,fields={}){if(!code||!token||busy)return;busy=true;try{render((await req(action,{code,token,...fields})).room);clear();}catch(e){notice(e.message,true);}finally{busy=false;}}
async function reconnect(c,quiet=false){const entry=storage(storeKey(c));if(!entry)return false;let parsed;try{parsed=JSON.parse(entry);}catch(e){return false;}if(!parsed?.token)return false;code=c;token=parsed.token;
try{const data=await req('status',{code,token});render(data.room);save(code,token,parsed.name);return true;}catch(e){if(!quiet)notice(e.message,true);code='';token='';return false;}}
$('tabCreate').onclick=()=>setTab('create');$('tabJoin').onclick=()=>setTab('join');
$('roomForm').addEventListener('submit',async(e)=>{e.preventDefault();if(busy)return;busy=true;$('submitRoom').disabled=true;try{
const name=$('displayName').value.trim();if(tab==='join'){
const c=$('roomCode').value.trim().toUpperCase();if(await reconnect(c,true)){clear();return;}
const d=await req('join',{code:c,name});save(d.room.code,d.accessToken,name);render(d.room);
}else{const d=await req('create',{name,capacity:Number($('capacity').value)});save(d.room.code,d.accessToken,name);render(d.room);}clear();
}catch(err){notice(err.message,true);}finally{busy=false;$('submitRoom').disabled=false;}});
$('readyButton').onclick=()=>act('ready');$('startButton').onclick=()=>act('start',{duration:Number($('duration').value)});
$('copyCode').onclick=async()=>{try{await navigator.clipboard.writeText(code);notice('Código copiado!');}catch(e){notice('Código: '+code);}};
$('shareLink').onclick=async()=>{const u=new URL('multiplayer.php',location.href);u.searchParams.set('sala',code);if(navigator.share){try{await navigator.share({title:'BBB Simulator',url:u.href,text:'Entre na minha sala!'});return;}catch(e){return;}}
try{await navigator.clipboard.writeText(u.href);notice('Link copiado!');}catch(e){notice('Link: '+u.href);}};
$('leaveView').onclick=()=>{room=null;code='';token='';$('entry').hidden=false;$('room').hidden=true;storage(lastKey,'');const u=new URL(location.href);u.searchParams.delete('sala');history.replaceState({},'',u.href);setTab('join');notice('Você saiu da tela. A vaga continua reservada para reconexão.');};
setTab('create');const q=new URLSearchParams(location.search).get('sala');const c=(q||storage(lastKey)||'').toUpperCase();if(/^[A-HJ-NP-Z2-9]{6}$/.test(c)){$('roomCode').value=c;setTab('join');reconnect(c,true).then(ok=>{if(!ok&&q)notice('Digite seu nome para entrar na sala.');});}
setInterval(poll,2500);
})();
