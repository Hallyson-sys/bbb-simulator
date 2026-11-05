/* BBB Simulator — Multiplayer Beta 2 | recepção da sala */
(() => {
'use strict';
const $ = (id) => document.getElementById(id);
let tab='create',room=null,code='',token='',busy=false,updating=false,confirmedDraft=null,shownRoom='',playersKey='';
const lastKey='bbb_mp_beta2_last';const storeKey=(c)=>`bbb_mp_beta2_${c}`;
const storage=(key,value)=>{try {if(value===undefined)return localStorage.getItem(key);localStorage.setItem(key,value);}catch(e){return null;}};
const notice=(text,error=false)=>{const el=$('notice');el.textContent=text;el.className='mp-notice'+(error?' error':'');el.hidden=false;};
const clear=()=>$('notice').hidden=true;
const goGame=()=>{const url=new URL('multiplayer_jogo.php',location.href);url.searchParams.set('sala',code);location.assign(url.href);};
function setTab(t){tab=t;const create=t==='create';$('createFields').hidden=!create;$('joinFields').hidden=create;$('roomCode').required=!create;$('tabCreate').classList.toggle('active',create);$('tabJoin').classList.toggle('active',!create);$('submitRoom').textContent='CONFERIR MEU PERSONAGEM →';confirmedDraft=null;$('characterConfirm').hidden=true;$('roomForm').hidden=false;}
async function req(action,fields={}){const res=await fetch('multiplayer_api.php',{method:'POST',headers:{'Content-Type':'application/json'},cache:'no-store',body:JSON.stringify({action,...fields})});let data;try{data=await res.json();}catch(e){throw Error('Servidor não respondeu corretamente.');}if(!res.ok||!data.ok)throw Error(data.erro||'Ocorreu um erro.');return data;}
function save(c,t,name){code=c;token=t;storage(storeKey(c),JSON.stringify({token:t,name}));storage(lastKey,c);const u=new URL(location.href);u.searchParams.set('sala',c);history.replaceState({},'',u.href);}
function render(r){room=r;if(r.phase!=='lobby'){goGame();return;}
$('entry').hidden=true;$('backupRestoreSection').hidden=true;$('room').hidden=false;$('roomCodeDisplay').textContent=r.code;if(shownRoom!==r.code){shownRoom=r.code;window.scrollTo({top:0,behavior:'instant'});}
const ready=r.players.filter(p=>p.ready).length;const isHost=r.myId===r.hostId;const me=r.players.find(p=>p.id===r.myId);
$('countLabel').textContent=`${r.players.length}/${r.capacity}`;$('progressLabel').textContent=`${ready}/${r.players.length} prontos`;
$('progressBar').style.width=`${Math.round(100*ready/Math.max(1,r.players.length))}%`;
const nextKey=JSON.stringify(r.players.map(p=>[p.id,p.name,p.ready,p.profile]));if(playersKey!==nextKey){playersKey=nextKey;$('playersList').replaceChildren();r.players.forEach(p=>{const el=document.createElement('div');el.className='mp-lobby-person';const title=document.createElement('strong');title.textContent=`${p.id===r.hostId?'👑 ':''}${p.name}${p.id===r.myId?' ⭐':''}`;const st=document.createElement('span');st.textContent=p.ready?'✓ Pronto':'◷ Aguardando';const prof=document.createElement('small');const pr=p.profile||{};prof.textContent=pr.idade?`${pr.idade} anos • ${pr.profissao} • ${pr.estado} • ${pr.personalidade}`:'Participante da sala';const summary=document.createElement('div');summary.className='mp-lobby-identity';summary.append(title,prof);el.append(summary,st);$('playersList').appendChild(el);});}
$('readyButton').textContent=me?.ready?'✓ ESTOU PRONTO — DESFAZER':'✓ ESTOU PRONTO';$('readyButton').classList.toggle('mp-secondary',!!me?.ready);
$('hostControls').hidden=!isHost;$('startButton').disabled=!isHost||r.players.length<2||ready!==r.players.length;
$('lobbyHint').textContent=isHost?'Você é o anfitrião. Inicie quando todos marcarem “Estou pronto”.':'Espere o anfitrião iniciar a temporada.';
}
async function poll(){if(!code||!token||updating||busy)return;updating=true;try{render((await req('status',{code,token})).room);}catch(e){notice('Conexão com a sala: '+e.message,true);}finally{updating=false;}}
async function act(action,fields={}){if(!code||!token||busy)return;busy=true;try{render((await req(action,{code,token,...fields})).room);clear();}catch(e){notice(e.message,true);}finally{busy=false;}}
async function reconnect(c,quiet=false){const entry=storage(storeKey(c));if(!entry)return false;let parsed;try{parsed=JSON.parse(entry);}catch(e){return false;}if(!parsed?.token)return false;code=c;token=parsed.token;
try{const data=await req('status',{code,token});render(data.room);save(code,token,parsed.name);return true;}catch(e){if(!quiet)notice(e.message,true);code='';token='';return false;}}
$('tabCreate').onclick=()=>{clear();setTab('create');};$('tabJoin').onclick=()=>{clear();setTab('join');};
function readDraft(){return {name:$('displayName').value.trim(),profile:{idade:Number($('age').value),profissao:$('job').value.trim(),estado:$('state').value,personalidade:$('personality').value},capacity:Number($('capacity').value),code:$('roomCode').value.trim().toUpperCase()};}
function showDraft(draft){confirmedDraft=draft;const preview=$('characterPreview');preview.replaceChildren();const icon=document.createElement('div');icon.className='mp-preview-avatar';icon.textContent=draft.name.trim().charAt(0).toUpperCase();const info=document.createElement('div');const title=document.createElement('strong');title.textContent=draft.name;const detail=document.createElement('p');detail.textContent=`${draft.profile.idade} anos • ${draft.profile.estado} • ${draft.profile.profissao}`;const trait=document.createElement('span');trait.textContent=`💜 ${draft.profile.personalidade}`;info.append(title,detail,trait);preview.append(icon,info);
 $('roomForm').hidden=true;$('characterConfirm').hidden=false;$('confirmCharacter').textContent=tab==='create'?'CONFIRMAR E CRIAR SALA →':'CONFIRMAR E ENTRAR →';
 $('confirmRoomHint').textContent=tab==='create'?`Sua sala terá ${draft.capacity} vagas. Você será o anfitrião.`:`Você entrará na sala ${draft.code} depois de confirmar.`;
}
$('roomForm').addEventListener('submit',(e)=>{e.preventDefault();if(busy)return;clear();showDraft(readDraft());});
$('editCharacter').onclick=()=>{$('characterConfirm').hidden=true;$('roomForm').hidden=false;};
$('confirmCharacter').onclick=async()=>{if(busy||!confirmedDraft)return;busy=true;const btn=$('confirmCharacter');btn.disabled=true;const original=btn.textContent;btn.textContent='CONECTANDO...';
 const d=confirmedDraft;
 try{
  if(tab==='join'){
   if(await reconnect(d.code,true)){clear();return;}
   const result=await req('join',{code:d.code,name:d.name,profile:d.profile});save(result.room.code,result.accessToken,d.name);render(result.room);
  }else{
   const result=await req('create',{name:d.name,capacity:d.capacity,profile:d.profile});save(result.room.code,result.accessToken,d.name);render(result.room);
  }
  clear();
 }catch(err){notice(err.message,true);}finally{busy=false;btn.disabled=false;btn.textContent=original;}
};
$('restoreBackup').onclick=async()=>{
 if(busy)return;
 const file=$('restoreFile').files?.[0],password=$('restorePassword').value;
 if(!file)return notice('Selecione o backup da temporada.',true);
 if(!password)return notice('Digite a senha usada ao salvar o arquivo.',true);
 busy=true;const btn=$('restoreBackup');btn.disabled=true;btn.textContent='RESTAURANDO...';
 try{
  const pack=await BBBBackup.open(await file.text(),password);
  const data=await req('restore',{backup:pack.room,token:pack.token});
  save(data.room.code,pack.token,data.room.players.find(p=>p.id===data.room.myId)?.name||'Anfitrião');
  $('restorePassword').value='';clear();goGame();
 }catch(e){notice(e.message,true);}finally{busy=false;btn.disabled=false;btn.textContent='📂 RESTAURAR SALA PAUSADA';}
};
$('readyButton').onclick=()=>act('ready');$('startButton').onclick=()=>act('start',{duration:Number($('duration').value)});
$('copyCode').onclick=async()=>{try{await navigator.clipboard.writeText(code);notice('Código copiado!');}catch(e){notice('Código: '+code);}};
$('shareLink').onclick=async()=>{const u=new URL('multiplayer.php',location.href);u.searchParams.set('sala',code);if(navigator.share){try{await navigator.share({title:'BBB Simulator',url:u.href,text:'Entre na minha sala!'});return;}catch(e){return;}}
try{await navigator.clipboard.writeText(u.href);notice('Link copiado!');}catch(e){notice('Link: '+u.href);}};
$('leaveView').onclick=()=>{room=null;code='';token='';shownRoom='';playersKey='';confirmedDraft=null;$('characterConfirm').hidden=true;$('roomForm').hidden=false;$('entry').hidden=false;$('backupRestoreSection').hidden=false;$('room').hidden=true;storage(lastKey,'');const u=new URL(location.href);u.searchParams.delete('sala');history.replaceState({},'',u.href);setTab('join');notice('Você saiu da visualização da sala. Se quiser voltar, use o mesmo código.');};
setTab('create');const q=new URLSearchParams(location.search).get('sala');const c=(q||storage(lastKey)||'').toUpperCase();if(/^[A-HJ-NP-Z2-9]{6}$/.test(c)){$('roomCode').value=c;setTab('join');reconnect(c,true).then(ok=>{if(!ok&&q)notice('Digite seu nome para entrar na sala.');});}
setInterval(poll,2500);
})();
