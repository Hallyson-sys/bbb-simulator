/* BBB Simulator • Multiplayer Beta 2 — interface original adaptada para jogadores reais. */
(() => {
'use strict';
const $=(id)=>document.getElementById(id);
const params=new URLSearchParams(location.search);
let code=(params.get('sala')||'').toUpperCase();
let token='',room=null,busy=false,polling=false,serverOffset=0,seq=0,lastApplied=0;
let currentPhase='',selectedType='',controlKey='',catalogBuilt=false,playersKey='',eventsKey='',feedKey='',sentKey='';
const storeKey=c=>`bbb_mp_beta2_${c}`;
const stored=()=>{try{return JSON.parse(localStorage.getItem(storeKey(code))||'null');}catch(e){return null;}};
const notice=(text,error=false)=>{const n=$('notice');n.textContent=text;n.className='mp-notice mp-global-notice'+(error?' error':'');n.hidden=false;};
const clear=()=>$('notice').hidden=true;
const here=()=>{location.assign('multiplayer.php'+(code?'?sala='+encodeURIComponent(code):''));};
if(!/^[A-HJ-NP-Z2-9]{6}$/.test(code)){here();return;}
token=stored()?.token||'';if(!/^[a-f0-9]{48}$/.test(token)){here();return;}
async function request(action,extra={}){
const num=++seq;
const res=await fetch('multiplayer_api.php',{method:'POST',headers:{'Content-Type':'application/json'},cache:'no-store',body:JSON.stringify({action,code,token,...extra})});
let data;try{data=await res.json();}catch(e){throw Error('Resposta inválida do servidor.');}
if(!res.ok||!data.ok)throw Error(data.erro||'Não foi possível realizar a ação.');
if(num>=lastApplied){lastApplied=num;serverOffset=data.serverTime*1000-Date.now();room=data.room;render();}
return data;
}
async function act(action,extra={}){if(busy)return;busy=true;try{await request(action,extra);clear();}catch(e){notice(e.message,true);}finally{busy=false;}}
const me=()=>room?.players.find(p=>p.id===room.myId);
const byId=id=>room?.players.find(p=>p.id===id);
const label=id=>byId(id)?.name||'Participante';
const active=()=>room.players.filter(p=>!p.eliminated);
const isSocial=()=>['interacoes_1','interacoes_2','festa'].includes(room?.phase);
function elem(tag,cls,text){const e=document.createElement(tag);if(cls)e.className=cls;if(text!==undefined)e.textContent=text;return e;}
function makeButton(text,fn,cls='btn mp-secondary'){const b=elem('button',cls,text);b.type='button';b.onclick=fn;return b;}
function makeSelect(id,choices,placeholder='Escolha o participante'){
const select=elem('select');select.id=id;
const empty=elem('option','',placeholder);empty.value='';select.appendChild(empty);
choices.forEach(c=>{const opt=elem('option','',c.text);opt.value=c.value;select.appendChild(opt);});return select;
}
function eligible(extras={}){
return active().filter(p=>extras.allowSelf||p.id!==room.myId).filter(p=>!extras.exclude?.includes(p.id)).map(p=>({value:p.id,text:p.name}));
}
function remaining(){return Math.max(0,Math.ceil((room.endsAt*1000-(Date.now()+serverOffset))/1000));}
function timer(){if(!room)return;const t=remaining();$('timer').textContent=room.phase==='final'?'—':`${String(Math.floor(t/60)).padStart(2,'0')}:${String(t%60).padStart(2,'0')}`;$('timer').classList.toggle('low',t<=15);}
function render(){
if(!room)return;if(room.phase==='lobby'){here();return;}
const mine=me();
$('headWeek').textContent=`🔥 Semana ${room.week}`;$('headCount').textContent=`👥 ${active().length} participantes restantes`;
const phase=room.phases[room.phase]||['🎮 Fase',''];$('phaseTitle').textContent=phase[0];$('phaseSubtitle').textContent=`Semana ${room.week} • ${phase[1]}`;
$('weekLabel').textContent=`🔥 Semana ${room.week}`;
const done=active().filter(p=>p.done).length;$('phaseReady').textContent=`${done}/${active().length} concluíram`;
$('phaseProgress').style.width=`${Math.round(done*100/Math.max(1,active().length))}%`;
const changed=currentPhase!==room.phase;
if(changed){selectedType='';currentPhase=room.phase;controlKey='';renderTimeline();catalogBuilt=false;}
renderPlayers();renderEvents();renderInvites();
const social=isSocial()&&!mine?.eliminated;
$('interactionsSection').hidden=!social;$('spectatorSection').hidden=!mine?.eliminated;
$('phaseControls').hidden=social||!!mine?.eliminated;
if(social){
 if(!catalogBuilt){renderCategories();catalogBuilt=true;}
 const left=3-(mine?.actionsUsed||0);
 $('actionsTitle').textContent=`💬 Interações — ${left} restantes`;
 $('categories').hidden=!!mine?.done||!!selectedType;
 $('interactionForm').hidden=!!mine?.done||!selectedType;
 $('finishButton').hidden=!!mine?.done;
 $('waitingHint').hidden=!mine?.done;
 if(selectedType){$('interactButton').disabled=!!mine?.done||left<=0;}
 renderSent();
}else if(!mine?.eliminated){renderControls(changed);}
 timer();
}
function renderTimeline(){const phases=['interacoes_1','lider','vip_xepa','anjo','imunidade','queridometro','interacoes_2','festa','discordia','paredao','eliminacao'];const at=phases.indexOf(room.phase);
$('timeline').replaceChildren();for(let i=0;i<phases.length;i++){const p=phases[i],step=elem('div','mp-step'+(i===at?' current':i<at||room.phase==='final'?' done':''),`${i<at||room.phase==='final'?'✓ ':i===at?'● ':''}${room.phases[p][0]}`);$('timeline').appendChild(step);}}
function renderPlayers(){const key=JSON.stringify(room.players.map(p=>[p.id,p.name,p.profile,p.eliminated,p.isLeader,p.isAngel,p.isVIP,p.isImmune,p.relation]));if(playersKey===key)return;playersKey=key;const grid=$('playersGrid');grid.replaceChildren();
room.players.forEach(p=>{
const card=elem('article','card'+(p.id===room.myId?' voce':'')+(p.eliminated?' eliminated':'')+(p.relation>=15?' positiva':p.relation<=-10?' negativa':''));
const avatar=elem('div','avatar mp-avatar-initial',p.name.trim().charAt(0).toUpperCase());card.appendChild(avatar);card.appendChild(elem('h3','',p.name+(p.id===room.myId?' ⭐':'')));const prof=p.profile||{};if(prof.idade){card.appendChild(elem('div','mp-profile-line',`${prof.idade} anos • ${prof.estado}`));card.appendChild(elem('div','mp-profile-line mp-job',prof.profissao));card.appendChild(elem('div','mp-profile-tag',`✦ ${prof.personalidade}`));}
let status=p.eliminated?'🚪 Eliminado':p.isLeader?'👑 Líder':p.isAngel?'😇 Anjo':p.isImmune?'🛡️ Imune':p.isVIP?'🟡 VIP':'🍞 Xepa';
card.appendChild(elem('div','mp-state',status));if(p.id!==room.myId&&!p.eliminated)card.appendChild(elem('div','mp-state',`💜 Relação: ${p.relation>0?'+':''}${p.relation}`));
grid.appendChild(card);
});$('playersStatus').textContent=`Sala ${room.code} • ${room.players.length}/${room.capacity} inscritos • ${active().length} na casa`;
}
function renderEvents(){const key=(room.events||[]).map(ev=>ev.id).join('|');if(eventsKey===key)return;eventsKey=key;const list=$('eventsList');list.replaceChildren();const events=[...room.events].reverse();
if(!events.length){list.appendChild(elem('li','','Os primeiros acontecimentos da casa vão aparecer aqui.'));renderFeed();return;}
for(const ev of events){const li=elem('li',ev.type==='interacao'?'mp-event-story':'');li.appendChild(elem('small','',`SEMANA ${ev.week} • ${new Date(ev.at*1000).toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'})}`));if(ev.title)li.appendChild(elem('strong','mp-event-title',ev.title));if(ev.place)li.appendChild(elem('div','mp-event-place',`📍 ${ev.place} • ${ev.topic||'Convivência'}`));li.appendChild(elem('span','',ev.text));list.appendChild(li);}renderFeed();
}
function renderFeed(){
 const entries=(room.events||[]).filter(ev=>ev.type!=='convite').slice(-24).reverse();
 const key=entries.map(ev=>ev.id).join('|');if(feedKey===key)return;feedKey=key;
 const list=$('feedPosts');if(!list)return;list.replaceChildren();
 if(!entries.length){list.appendChild(elem('p','mp-feed-empty','Quando a casa começar a se movimentar, o Feed mostrará os principais acontecimentos.'));return;}
 entries.forEach(ev=>{const post=elem('article','mp-feed-post');const top=elem('div','mp-feed-head');top.append(elem('span','','📱 FEED BBB • ONLINE'),elem('small','',`SEMANA ${ev.week}`));post.appendChild(top);
 const tag=(ev.type==='interacao'?'#Convivencia':ev.text.includes('Paredão')?'#Paredao':ev.text.includes('Líder')?'#ProvaDoLider':'#BBBOnline');
 post.appendChild(elem('h4','',ev.title||'🎥 Aconteceu na casa'));post.appendChild(elem('p','',ev.text));post.appendChild(elem('div','mp-feed-tags',`${tag}  #BBBSimulator`));list.appendChild(post);});
}
function renderCategories(){
const c=$('categoryGroups');c.replaceChildren();const groups=[['social','💜 Social','Convivência, sentimentos e romance'],['estrategia','🎯 Estratégia','Sondagem e acordos reservados'],['conflitos','🔥 Conflitos','Esclarecer, confrontar ou mediar'],['aliancas','👥 Alianças e ações clássicas','Conversar, fofocas, VT e convites']];
for(const [id,title,desc] of groups){const details=elem('details','i4-grupo');details.open=id==='social';
const sum=elem('summary','',title);details.appendChild(sum);details.appendChild(elem('p','',desc));
const buttons=elem('div','i4-botoes');
for(const [key,cat] of Object.entries(room.catalog))if(cat[1]===id){const b=makeButton(cat[0],()=>selectAction(key),'');buttons.appendChild(b);}
details.appendChild(buttons);c.appendChild(details);}
}
function selectAction(type){selectedType=type;const cat=room.catalog[type];if(!cat)return;
$('chosenTitle').textContent=cat[0];
const target=$('targetPlayer');target.replaceChildren();target.appendChild(new Option('Escolha o participante',''));
for(const p of eligible())target.appendChild(new Option(p.text,p.value));
const subject=$('actionSubject');subject.replaceChildren();for(const [id,title] of Object.entries(cat[2]))subject.appendChild(new Option(title,id));
$('interactionForm').hidden=false;$('categories').hidden=true;
$('interactionForm').scrollIntoView({behavior:'smooth',block:'center'});
}
function renderInvites(){const pending=room.pending||[];$('pendingSection').hidden=pending.length===0;
const list=$('pendingList');const previous=list.dataset.key;const key=JSON.stringify(pending.map(p=>[p.id,p.status]));if(previous===key)return;list.dataset.key=key;
list.replaceChildren();for(const q of pending){const block=elem('div','mp-request');
block.appendChild(elem('strong','',`💬 ${label(q.from)} te chamou para conversar`));
block.appendChild(elem('p','',`${q.label} • ${q.subject} • Abordagem ${q.approach}`));
const responses=elem('div','mp-responses');
for(const [value,text] of [['aceitar','💜 Aceitar'],['conversar','💬 Responder com cautela'],['recusar','🧊 Recusar']])responses.appendChild(makeButton(text,()=>act('respond',{requestId:q.id,answer:value}),'mp-choice-button'));
block.appendChild(responses);list.appendChild(block);
}
}
function renderSent(){const outgoing=room.outgoing||[];$('sentSection').hidden=!outgoing.length;const key=JSON.stringify(outgoing.map(q=>[q.id,q.status,q.answer]));if(sentKey===key)return;sentKey=key;
const list=$('sentList');list.replaceChildren();for(const q of [...outgoing].reverse()){
const status=q.status==='pendente'?'⏳ Aguardando resposta':q.status==='expirou'?'⌛ Tempo esgotado':q.answer==='recusar'?'🧊 Recusou':q.answer==='aceitar'?'💜 Aceitou':'💬 Respondeu';
list.appendChild(elem('div','mp-sent-item',`${label(q.to)} • ${q.label} — ${status}`));
}}
function controlsCard(title,desc){const c=$('phaseControls');c.replaceChildren();const sec=elem('div','i4-selecao');sec.appendChild(elem('div','i4-kicker',`● REC • ${room.phases[room.phase]?.[0]||'SEMANA'}`));sec.appendChild(elem('h3','',title));sec.appendChild(elem('p','',desc));c.appendChild(sec);return sec;}
function renderControls(force){const mine=me();const key=room.phase+'|'+!!mine?.done+'|'+JSON.stringify(mine?.choice)+'|'+room.leaderPick+'|'+JSON.stringify(room.nominees);
if(!force&&controlKey===key)return;controlKey=key;
const phase=room.phase;
if(phase==='lider'||phase==='anjo'){
const sec=controlsCard(phase==='lider'?'👑 Prova do Líder':'😇 Prova do Anjo','Prova rápida experimental: escolha uma opção. O servidor sorteia uma pontuação para cada participante e anuncia o vencedor. As provas interativas do modo solo serão adaptadas depois.');
if(mine.done){sec.appendChild(elem('p','',`✅ Sua escolha foi registrada. Aguarde os demais jogadores. Pontuação: ${mine.choice?.score??'—'}`));return;}
for(const [n,text] of [[1,'🔴 Caixa vermelha'],[2,'🟣 Caixa roxa'],[3,'🔵 Caixa azul']])sec.appendChild(makeButton(text,()=>act('prova',{number:n}),'btn mp-secondary'));
return;
}
if(phase==='vip_xepa'){
const sec=controlsCard('🍽️ VIP & Xepa',`O Líder escolhe até ${Math.max(1,Math.floor(active().length/3))} pessoas para o VIP, contando com ele próprio.`);
if(room.myId!==room.leaderId){sec.appendChild(elem('p','','⏳ Aguarde a escolha do Líder.'));return;}
if(mine.done){sec.appendChild(elem('p','','✅ Você já definiu o VIP.'));return;}
const max=Math.max(1,Math.floor(active().length/3))-1;
for(const p of active().filter(p=>p.id!==room.myId)){const labelEl=elem('label','mp-choice');const ch=elem('input');ch.type='checkbox';ch.name='vip';ch.value=p.id;labelEl.append(ch,document.createTextNode(p.name));sec.appendChild(labelEl);}
sec.appendChild(makeButton('🍽️ Confirmar VIP',()=>{const ids=[...sec.querySelectorAll('input[name="vip"]:checked')].map(x=>x.value);if(ids.length>max){notice('Escolha no máximo '+max+' colegas para acompanhar você no VIP.',true);return;}act('vip',{ids});},'btn mp-main-btn'));return;
}
if(phase==='imunidade'){
const sec=controlsCard('🛡️ Presente do Anjo','O Anjo escolhe uma pessoa para receber imunidade. Nesta beta, não é possível imunizar a si próprio.');
if(room.myId!==room.angelId){sec.appendChild(elem('p','','⏳ Aguarde a decisão do Anjo.'));return;}
if(mine.done){sec.appendChild(elem('p','','✅ Imunidade escolhida.'));return;}
const s=makeSelect('mp-target',eligible());sec.appendChild(s);sec.appendChild(makeButton('🛡️ Conceder imunidade',()=>{if(!s.value){notice('Escolha alguém.',true);return;}act('imune',{id:s.value});},'btn mp-main-btn'));return;
}
if(phase==='queridometro'){
const sec=controlsCard('💟 Queridômetro','Escolha alguém e envie um emoji. O acontecimento aparece na casa.');
if(mine.done){sec.appendChild(elem('p','','✅ Emoji enviado. Aguarde os outros.'));return;}
const s=makeSelect('mp-target',eligible());sec.appendChild(s);
const emojis=makeSelect('mp-emoji',[{value:'💜',text:'💜 Coração'},{value:'🙂',text:'🙂 Sorriso'},{value:'🤝',text:'🤝 Aperto de mão'},{value:'🔥',text:'🔥 Fogo'},{value:'😶',text:'😶 Indiferente'}],'Escolha o emoji');sec.appendChild(emojis);
sec.appendChild(makeButton('💟 Enviar Queridômetro',()=>{if(!s.value||!emojis.value){notice('Escolha um participante e emoji.',true);return;}act('queridometro',{id:s.value,emoji:emojis.value});},'btn mp-main-btn'));return;
}
if(phase==='discordia'){
const sec=controlsCard('🔥 Jogo da Discórdia','Como no jogo solo, você escolhe um participante e faz um posicionamento. Nesta beta, as falas são pré-definidas.');
if(mine.done){sec.appendChild(elem('p','','✅ Seu posicionamento foi registrado.'));return;}
const s=makeSelect('mp-target',eligible());sec.appendChild(s);
const reasons=makeSelect('mp-reason',[{value:'jogo',text:'🎭 Joga dos dois lados'},{value:'convivencia',text:'🏠 Não sabe conviver'},{value:'confianca',text:'🤝 Quebrou minha confiança'}],'Escolha o motivo');sec.appendChild(reasons);
sec.appendChild(makeButton('🔥 Fazer posicionamento',()=>{if(!s.value||!reasons.value){notice('Escolha participante e motivo.',true);return;}act('discordia',{id:s.value,reason:reasons.value});},'btn mp-main-btn'));return;
}
if(phase==='paredao'){
const sec=controlsCard('🔥 Formação do Paredão','O Líder indica uma pessoa. Todos os participantes ativos fazem um voto secreto no confessionário.');
if(room.myId===room.leaderId){
if(!room.leaderPick){sec.appendChild(elem('h3','','👑 Indicação do Líder'));
const s=makeSelect('mp-indicar',eligible({exclude:[room.immuneId].filter(Boolean)}));sec.appendChild(s);
sec.appendChild(makeButton('👑 Confirmar indicação',()=>{if(!s.value){notice('Escolha a indicação.',true);return;}act('indicar',{id:s.value});},'btn mp-main-btn'));}
else sec.appendChild(elem('p','',`👑 Você indicou ${label(room.leaderPick)}.`));
}
if(mine.done){sec.appendChild(elem('p','','🗳️ Seu voto foi registrado em segredo.'));return;}
if(room.myId===room.leaderId&&!room.leaderPick){sec.appendChild(elem('p','','Indique alguém antes de votar no confessionário.'));return;}
sec.appendChild(elem('h3','','🗳️ Confessionário'));
const s=makeSelect('mp-voto',eligible({exclude:[room.immuneId].filter(Boolean)}));sec.appendChild(s);
sec.appendChild(makeButton('🗳️ Confirmar voto secreto',()=>{if(!s.value){notice('Escolha alguém para votar.',true);return;}act('vote',{id:s.value});},'btn mp-main-btn'));return;
}
if(phase==='eliminacao'){
const sec=controlsCard('🚪 Noite de eliminação','Paredão definido! Este protótipo calcula a saída por um placar simplificado de convivência, não por uma votação pública real.');
const names=room.nominees.map(label);sec.appendChild(elem('h3','',names.join(' × ')||'Paredão em apuração'));sec.appendChild(elem('p','','⏳ O resultado aparece no Ao Vivo quando o cronômetro terminar.'));return;
}
if(phase==='final'){
const sec=controlsCard('🏁 Protótipo concluído','A temporada experimental chegou à fase final. A final completa e as dinâmicas especiais do jogo solo ainda não foram adaptadas.');
sec.appendChild(elem('h3','',`Participantes restantes: ${active().map(p=>p.name).join(', ')}`));sec.appendChild(makeButton('🌐 Voltar à sala',here,'btn mp-secondary'));return;
}
controlsCard('Dinâmica em andamento','Acompanhe os acontecimentos no Ao Vivo.');
}
$('interactButton').addEventListener('click',()=>{
if(!selectedType||!$('targetPlayer').value)return notice('Escolha com quem conversar.',true);
act('interact',{type:selectedType,targetId:$('targetPlayer').value,subject:$('actionSubject').value,approach:$('actionApproach').value}).then(()=>{
 selectedType='';$('interactionForm').hidden=true;$('categories').hidden=!!me()?.done;
});
});
$('cancelAction').onclick=()=>{selectedType='';$('interactionForm').hidden=true;$('categories').hidden=false;};
$('finishButton').onclick=()=>act('finish');
function openPanel(id){const target=$(id);if(!target)return;
if(target.classList.contains('mobile-collapsed')){target.classList.remove('mobile-collapsed');target.querySelector('.mobile-panel-toggle')?.setAttribute('aria-expanded','true');}
target.scrollIntoView({behavior:'smooth',block:'start'});
for(const b of document.querySelectorAll('[data-view]'))b.classList.toggle('ativo',b.dataset.view===id);
}
for(const btn of document.querySelectorAll('[data-view]'))btn.addEventListener('click',()=>{
const id=btn.dataset.view;if(id==='timeline'){openPanel('mobileJogo');$('timeline').scrollIntoView({behavior:'smooth'});}else if(id==='eventsList'){openPanel('mobileAoVivo');}else openPanel(id);
});
for(const b of document.querySelectorAll('.mobile-panel-toggle'))b.onclick=()=>{const p=$(b.dataset.target);p.classList.toggle('mobile-collapsed');b.setAttribute('aria-expanded',String(!p.classList.contains('mobile-collapsed')));};
$('mobileMenuBtn').onclick=()=>{const menu=$('mobileHeaderMenu');menu.hidden=!menu.hidden;$('mobileMenuBtn').setAttribute('aria-expanded',String(!menu.hidden));};
$('menuCasa').onclick=()=>{openPanel('mobileCasa');$('mobileHeaderMenu').hidden=true;};
$('menuAoVivo').onclick=()=>{openPanel('mobileAoVivo');$('mobileHeaderMenu').hidden=true;};
request('status').catch(e=>{notice('Não foi possível entrar na sala: '+e.message,true);});
setInterval(()=>{if(!room||busy||polling)return;polling=true;request('status').catch(e=>notice('Conexão interrompida: '+e.message,true)).finally(()=>polling=false);},2500);
setInterval(timer,250);
})();
