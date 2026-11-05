/* BBB Simulator • Multiplayer Beta 7 — Semana e Convivência Online. */
(() => {
'use strict';
const $=(id)=>document.getElementById(id);
const params=new URLSearchParams(location.search);
let code=(params.get('sala')||'').toUpperCase();
let token='',room=null,busy=false,polling=false,serverOffset=0,seq=0,lastApplied=0;
let currentPhase='',selectedType='',controlKey='',catalogBuilt=false,playersKey='',eventsKey='',feedKey='',sentKey='',privateKey='',proofResultsKey='';
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
async function act(action,extra={}){if(busy)return false;busy=true;try{await request(action,extra);clear();if(action==='pause')notice('Temporada pausada! Antes de fechar, baixe o backup protegido por senha.');return true;}catch(e){notice(e.message,true);return false;}finally{busy=false;}}
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
function timer(){if(!room)return;const t=room.paused?room.pauseRemaining:remaining();$('timer').textContent=room.phase==='final'?'—':`${String(Math.floor(t/60)).padStart(2,'0')}:${String(t%60).padStart(2,'0')}`;$('timer').classList.toggle('low',t<=15);if(room.proof?.type==='memoria' && !room.paused && (room.phase==='lider'||room.phase==='anjo'))renderControls(false);}
function renderPause(){
 const host=room.myId===room.hostId;
 $('pauseBoard').hidden=!room.paused;
 $('hostPauseControls').hidden=!host||room.phase==='final'||room.phase==='lobby';
 $('pauseGame').hidden=!!room.paused;
 $('resumeGame').hidden=!room.paused;
 $('backupControls').hidden=!room.paused;
 $('phaseReady').textContent=room.paused?'⏸️ Cronômetro congelado':`${active().filter(p=>p.done).length}/${active().length} concluíram`;
}
async function exportBackup(){
 const password=$('backupPassword').value;
 if(password.length<8)return notice('Escolha uma senha de 8 caracteres ou mais para criptografar o arquivo.',true);
 const button=$('downloadBackup');button.disabled=true;button.textContent='CRIPTOGRAFANDO...';
 try{
  const response=await fetch('multiplayer_api.php',{method:'POST',headers:{'Content-Type':'application/json'},cache:'no-store',body:JSON.stringify({action:'export',code,token})});
  const data=await response.json();if(!response.ok||!data.ok)throw Error(data.erro||'Falha ao preparar o backup.');
  const secured=await BBBBackup.seal(data.backup,token,password);
  BBBBackup.download(secured,`BBB-ONLINE-${code}-SEMANA-${room.week}.json`);
  notice('Backup criptografado preparado. Salve o arquivo em Arquivos e guarde sua senha. Se perder os dois, não conseguiremos recuperar a sala.');
 }catch(e){notice(e.message,true);}finally{button.disabled=false;button.textContent='💾 Baixar backup protegido';}
}
function renderPrivate(){
 const contacts=room.contacts||[],agreements=room.agreements||[],logs=room.privateLogs||[];
 const key=JSON.stringify({contacts,agreements,logs});if(key===privateKey)return;privateKey=key;
 $('privatePanel').hidden=!contacts.length&&!agreements.length&&!logs.length;
 const rel=$('relationsList');rel.replaceChildren();
 for(const c of contacts){const tags=[c.alliance?'🤝 Aliança':'',c.romanceMoments?`💗 Aproximação (${c.romanceMoments})`:'',c.protection?'🛡️ Pacto de proteção':''].filter(Boolean);rel.appendChild(elem('p','mp-relation-row',`${c.name} • ${tags.join(' • ')}`));}
 for(const a of agreements){const partner=label(a.a===room.myId?a.b:a.a),target=label(a.target);rel.appendChild(elem('p','mp-relation-row',`🎯 Acordo com ${partner} para votar em ${target} (semana ${a.week}) • ${a.status}`));}
 const messages=$('privateMessages');messages.replaceChildren();
 for(const item of [...logs].reverse()){const row=elem('div','mp-private-item');row.appendChild(elem('small','',`SEMANA ${item.week}`));row.appendChild(elem('p','',item.text));messages.appendChild(row);}
}
function render(){
if(!room)return;if(room.phase==='lobby'){here();return;}
const mine=me();
$('headWeek').textContent=`🔥 Semana ${room.week}`;$('headCount').textContent=`👥 ${active().length} participantes restantes`;
const phase=room.phases[room.phase]||['🎮 Fase',''];$('phaseTitle').textContent=phase[0];$('phaseSubtitle').textContent=`Semana ${room.week} • ${phase[1]}`;
$('weekLabel').textContent=`🔥 Semana ${room.week}`;
const done=active().filter(p=>p.done).length;$('phaseReady').textContent=`${done}/${active().length} concluíram`;
$('phaseProgress').style.width=`${Math.round(done*100/Math.max(1,active().length))}%`;
renderPause();renderPrivate();renderProofResults();renderQueridometroResults();renderMonstroTarefa();
const changed=currentPhase!==room.phase;
if(changed){selectedType='';selectedFesta='';currentPhase=room.phase;controlKey='';festaKey='';$('monstroTarefa').dataset.loaded='';renderTimeline();catalogBuilt=false;}
renderPlayers();renderEvents();renderInvites();
const social=isSocial()&&!mine?.eliminated;
$('interactionsSection').hidden=!social;$('spectatorSection').hidden=!mine?.eliminated;
$('phaseControls').hidden=social||!!mine?.eliminated;
if(social){
 if(!catalogBuilt){renderCategories();catalogBuilt=true;}
 const left=3-(mine?.actionsUsed||0);
 $('actionsTitle').textContent=room.phase==='festa'?`🎉 Festa — ${left} ações restantes`:`💬 Interações — ${left} restantes`;
 $('categories').hidden=!!mine?.done||!!selectedType;
 $('festaBoard').hidden=room.phase!=='festa'||!!selectedType;if(room.phase==='festa')renderFesta();
 $('interactionForm').hidden=!!mine?.done||!selectedType;
 $('finishButton').hidden=!!mine?.done;
 $('waitingHint').hidden=!mine?.done;
 if(selectedType){$('interactButton').disabled=!!mine?.done||left<=0;}
 renderSent();
}else if(!mine?.eliminated){renderControls(changed);}
 if(room.paused){$('interactionsSection').hidden=true;$('phaseControls').hidden=true;$('pendingSection').hidden=true;}
 timer();
}
function renderTimeline(){const phases=['interacoes_1','lider','vip_xepa','anjo','monstro','imunidade','queridometro','interacoes_2','festa','discordia','paredao','eliminacao'];const at=phases.indexOf(room.phase);
$('timeline').replaceChildren();for(let i=0;i<phases.length;i++){const p=phases[i],step=elem('div','mp-step'+(i===at?' current':i<at||room.phase==='final'?' done':''),`${i<at||room.phase==='final'?'✓ ':i===at?'● ':''}${room.phases[p][0]}`);$('timeline').appendChild(step);}}
function renderPlayers(){const key=JSON.stringify(room.players.map(p=>[p.id,p.name,p.profile,p.eliminated,p.isLeader,p.isAngel,p.isVIP,p.isImmune,p.isMonster,p.monsterTaskDone,p.relation]));if(playersKey===key)return;playersKey=key;const grid=$('playersGrid');grid.replaceChildren();
room.players.forEach(p=>{
const card=elem('article','card'+(p.id===room.myId?' voce':'')+(p.eliminated?' eliminated':'')+(p.relation>=15?' positiva':p.relation<=-10?' negativa':''));
const avatar=elem('div','avatar mp-avatar-initial',p.name.trim().charAt(0).toUpperCase());card.appendChild(avatar);card.appendChild(elem('h3','',p.name+(p.id===room.myId?' ⭐':'')));const prof=p.profile||{};if(prof.idade){card.appendChild(elem('div','mp-profile-line',`${prof.idade} anos • ${prof.estado}`));card.appendChild(elem('div','mp-profile-line mp-job',prof.profissao));card.appendChild(elem('div','mp-profile-tag',`✦ ${prof.personalidade}`));}
let status=p.eliminated?'🚪 Eliminado':p.isLeader?'👑 Líder':p.isAngel?'😇 Anjo':p.isImmune?'🛡️ Imune':p.isVIP?'🟡 VIP':'🍞 Xepa'; if(p.isMonster)status+=' • 👹 Monstro'+(p.monsterTaskDone?' ✓':'');
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
 const tag=({interacao:'#Convivencia',festa:'#FestaBBB',discordia:'#JogoDaDiscordia',queridometro:'#Queridometro',monstro:'#CastigoDoMonstro',vip:'#VipEXepa',convivencia:'#RotinaDaCasa'})[ev.type]||(ev.text.includes('Paredão')?'#Paredao':ev.text.includes('Líder')?'#ProvaDoLider':'#BBBOnline');
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
$('interactionForm').hidden=false;$('categories').hidden=true;if(room.phase==='festa')$('festaBoard').hidden=true;
$('actionMessage').value='';
 $('voteAgreementField').hidden=type!=='i4_acordo';
 if(type==='i4_acordo'){
  const refresh=()=>{const s=$('voteTarget');s.replaceChildren();s.appendChild(new Option('Sem alvo definido',''));
   for(const p of eligible({exclude:[$('targetPlayer').value]}))s.appendChild(new Option(p.text,p.value));};
  refresh();target.onchange=refresh;
 }
 $('interactionForm').scrollIntoView({behavior:'smooth',block:'center'});
}
function renderInvites(){
 const pending=room.pending||[];$('pendingSection').hidden=room.paused||pending.length===0;
 const list=$('pendingList');const previous=list.dataset.key;
 const key=JSON.stringify(pending.map(p=>[p.id,p.status,p.options,p.message,p.proposedTarget]));if(previous===key)return;list.dataset.key=key;
 list.replaceChildren();for(const q of pending){const block=elem('div','mp-request');
  block.appendChild(elem('strong','',`💬 ${label(q.from)} quer conversar com você`));
  block.appendChild(elem('p','',`${q.label} • ${q.subject} • Abordagem ${q.approach}`));
  if(q.message)block.appendChild(elem('p','mp-invite-message',`✉️ “${q.message}”`));
  if(q.proposedTarget)block.appendChild(elem('p','mp-invite-message',`🎯 Proposta de voto: ${label(q.proposedTarget)}`));
  const reply=elem('textarea','mp-reply-text');reply.rows=2;reply.maxLength=160;reply.placeholder='Sua resposta particular (opcional, até 160 caracteres)';block.appendChild(reply);
  let counter=null;
  if(q.type==='i4_acordo' && q.options?.some(o=>o.value==='contrapropor')){
   counter=makeSelect('counter-'+q.id,eligible({exclude:[q.from,q.proposedTarget].filter(Boolean)}),'Escolha outro alvo para contrapropor');
   block.appendChild(elem('label','', 'Se quiser propor outro alvo:'));block.appendChild(counter);
  }
  const responses=elem('div','mp-responses');
  for(const o of q.options||[]){const b=makeButton(o.label,()=>{
   if(o.value==='contrapropor'&&!counter?.value)return notice('Escolha outro participante para a contraproposta.',true);
   act('respond',{requestId:q.id,answer:o.value,reply:reply.value,...(o.value==='contrapropor'?{counterTarget:counter.value}:{})});
  },'mp-choice-button');responses.appendChild(b);}
  block.appendChild(responses);list.appendChild(block);
 }
}
function renderSent(){const outgoing=room.outgoing||[];$('sentSection').hidden=!outgoing.length;
const key=JSON.stringify(outgoing.map(q=>[q.id,q.status,q.answer,q.reply,q.message]));if(sentKey===key)return;sentKey=key;
const list=$('sentList');list.replaceChildren();for(const q of [...outgoing].reverse()){
const status=q.status==='pendente'?'⏳ Aguardando resposta':q.status==='expirou'?'⌛ Tempo esgotado':q.answer==='recusar'?'🧊 Recusou':q.answer==='aceitar'||q.answer==='retribuir'?'💜 Aceitou':q.answer==='contrapropor'?'🔄 Contrapropôs':'💬 Respondeu';
const block=elem('div','mp-sent-item',`${label(q.to)} • ${q.label} — ${status}`);
if(q.reply)block.appendChild(elem('p','mp-private-reply',`✉️ ${q.reply}`));list.appendChild(block);
}}
function renderProofResults(){
  const info=room.lastProof;
  const panel=$('proofResults');if(!panel)return;
  panel.hidden=!info || info.week!==room.week || ['lider','anjo'].includes(room.phase);
  if(panel.hidden)return;
  const key=JSON.stringify(info);if(key===proofResultsKey)return;proofResultsKey=key;
  const heading=$('proofResultsTitle');const list=$('proofRanking');list.replaceChildren();
  heading.textContent=`${info.phase==='lider'?'👑 Prova do Líder':'😇 Prova do Anjo'} • ${info.title}`;
  const winner=room.players.find(p=>p.id===info.winner);
  $('proofWinner').textContent=`🏆 ${winner?.name||'Participante'} venceu!`;
  $('proofDraw').textContent=info.noSubmissions?'Ninguém jogou a prova: vencedor definido por sorteio.':info.tiebreakDraw?'Houve empate na pontuação. Sorteio entre os participantes empatados.':'Maior pontuação venceu a prova.';
  for(const [i,p] of info.ranking.entries()){
    const row=elem('div','mp-proof-ranking-row');
    row.appendChild(elem('span','',`${i+1}º · ${p.name}${p.id===info.winner?' 🏆':''}`));
    row.appendChild(elem('strong','',p.submitted?`${p.score} pontos`:'Não participou'));
    list.appendChild(row);
  }
}
function proofIntro(title,desc){
    const sec=controlsCard(title,desc);
    const progress=elem('div','mp-proof-topline');
    progress.appendChild(elem('span','',`${room.proof.submitted} respostas recebidas`));
    progress.appendChild(elem('span','',`Até ${room.proof.numberOfPlayers} na casa`));
    sec.appendChild(progress);
    return sec;
}
function proofButtons(container,choices,selection,max){
  const buttons=[];
  const redraw=()=>buttons.forEach((b,i)=>{b.classList.toggle('selected',selection.includes(i));b.setAttribute('aria-pressed',String(selection.includes(i)));});
  choices.forEach((label,i)=>{
    const b=makeButton(label,()=>{
      if(selection.includes(i))selection.splice(selection.indexOf(i),1);
      else if(selection.length<max)selection.push(i);
      redraw();
    },'mp-proof-option');
    b.setAttribute('aria-pressed','false');container.appendChild(b);buttons.push(b);
  });
  return redraw;
}
function renderProof(){
  const p=room.proof;
  if(!p){controlsCard('Preparando prova','O servidor está organizando o desafio para todos os participantes.');return;}
  const mine=me();
  const titles={memoria:'🧠 Memória da Casa',logica:'🔢 Sequência Lógica',caixas:'🎁 Caixas Misteriosas'};
  const descriptions={memoria:'Memorize cinco símbolos durante 14 segundos. Depois repita a sequência na ordem exata. Cada posição certa vale 20 pontos.',logica:'Resolva cinco sequências numéricas. Cada resposta certa vale 20 pontos. Todos recebem as mesmas questões.',caixas:'Escolha três entre 20 caixas. O conteúdo é idêntico para toda a sala, mas fica escondido até o fim. O maior total vence.'};
  const sec=proofIntro(titles[p.type]||p.title,descriptions[p.type]||'Desafio online.');
  const rules=elem('div','mp-proof-rules','✅ Mesma prova para todos • Pontuação conferida pelo servidor • Sem bônus por velocidade');sec.appendChild(rules);
  if(!p.eligible){sec.appendChild(elem('div','mp-proof-done','👑 Você é o Líder e não disputa a Prova do Anjo nesta semana. Acompanhe seus colegas.'));return;}
  if(p.mySubmitted||mine?.done){sec.appendChild(elem('div','mp-proof-done',`✅ Resposta registrada! ${mine?.choice?.score??'—'} pontos. Aguarde os participantes restantes ou o fim do relógio.`));return;}
  if(p.type==='memoria'){
    const now=Math.floor((Date.now()+serverOffset)/1000);
    if(now < p.revealUntil){
      sec.appendChild(elem('div','mp-proof-phase-caption',`👀 OBSERVE E MEMORIZE • ${Math.max(0,p.revealUntil-now)} segundos`));
      const seqBox=elem('div','mp-proof-memory-show');
      (p.sequence||[]).forEach((icon,i)=>seqBox.appendChild(elem('div','mp-proof-symbol',icon)));
      sec.appendChild(seqBox);
      sec.appendChild(elem('p','mp-proof-hint','Quando os símbolos sumirem, marque a sequência na mesma ordem. Não precisa clicar agora.'));
      return;
    }
    sec.appendChild(elem('div','mp-proof-phase-caption','🧠 RECONSTRUA A ORDEM DOS CINCO SÍMBOLOS'));
    const selected=[];
    const slots=elem('div','mp-proof-memory-slots');
    const board=elem('div','mp-proof-memory-options');
    const buttonList=[];
    const repaint=()=>{
      slots.replaceChildren();for(let i=0;i<5;i++)slots.appendChild(elem('span','mp-proof-memory-slot',selected[i]??'•'));
      buttonList.forEach(b=>{b.disabled=selected.includes(b.dataset.icon);b.setAttribute('aria-pressed',String(selected.includes(b.dataset.icon)));});
    };
    p.symbols.forEach((icon)=>{const b=makeButton(icon,()=>{if(selected.length<5){selected.push(icon);repaint();}},'mp-proof-symbol-button');b.dataset.icon=icon;buttonList.push(b);board.appendChild(b);});
    repaint();sec.appendChild(slots);sec.appendChild(board);
    sec.appendChild(makeButton('↶ Desfazer último',()=>{selected.pop();repaint();},'btn mp-secondary'));
    sec.appendChild(makeButton('✅ Confirmar sequência',()=>{if(selected.length!==5)return notice('Escolha os cinco símbolos para enviar.',true);act('prova',{answers:selected.slice()});},'btn mp-main-btn'));
  }else if(p.type==='logica'){
    const choices=[];
    for(const [i,q] of p.questions.entries()){
      const question=elem('fieldset','mp-proof-question');
      const legend=elem('legend','',`${i+1}. ${q.q}`);question.appendChild(legend);
      const options=elem('div','mp-proof-answers');
      q.o.forEach((choice,j)=>{
        const label=elem('label','mp-proof-radio');
        const input=elem('input');input.type='radio';input.name=`mp-proof-q${i}`;input.value=String(j);input.onchange=()=>{choices[i]=j;};
        label.append(input,document.createTextNode(choice));options.appendChild(label);
      });
      question.appendChild(options);sec.appendChild(question);
    }
    sec.appendChild(makeButton('✅ Enviar respostas',()=>{if(choices.length!==5||choices.some(a=>a===undefined))return notice('Responda às cinco sequências antes de enviar.',true);act('prova',{answers:choices});},'btn mp-main-btn'));
  }else if(p.type==='caixas'){
    sec.appendChild(elem('p','mp-proof-hint','As 20 caixas podem ser escolhidas por qualquer jogador. Cada caixa tem um valor secreto, e todos concorrem em condições iguais.'));
    const selected=[];const count=elem('strong','mp-proof-boxcount','0/3 caixas escolhidas');sec.appendChild(count);
    const boxGrid=elem('div','mp-proof-box-grid');
    const labels=Array.from({length:20},(_,i)=>`🎁 ${i+1}`);
    const redraw=proofButtons(boxGrid,labels,selected,3);
    const paint=()=>{redraw();count.textContent=`${selected.length}/3 caixas escolhidas`;};
    boxGrid.querySelectorAll('button').forEach(b=>{const old=b.onclick;b.onclick=()=>{old();paint();};});
    sec.appendChild(boxGrid);
    sec.appendChild(makeButton('🎁 Abrir minhas três caixas',()=>{if(selected.length!==3)return notice('Escolha exatamente três caixas.',true);act('prova',{answers:selected.slice()});},'btn mp-main-btn'));
  }
}
function controlsCard(title,desc){const c=$('phaseControls');c.replaceChildren();const sec=elem('div','i4-selecao');sec.appendChild(elem('div','i4-kicker',`● REC • ${room.phases[room.phase]?.[0]||'SEMANA'}`));sec.appendChild(elem('h3','',title));sec.appendChild(elem('p','',desc));c.appendChild(sec);return sec;}

function renderQueridometroResults(){
 const root=$('queridometroResults'),data=room.queridometroResult;
 root.hidden=!data||data.week!==room.week;if(root.hidden)return;
 const key=JSON.stringify(data);if(key===queridoKey)return;queridoKey=key;
 const list=$('queridometroResultsList');list.replaceChildren();
 for(const p of room.players.filter(p=>!p.eliminated)){
  const votes=data.received?.[p.id]||{};
  const all=Object.entries(votes).sort((a,b)=>b[1]-a[1]);
  const card=elem('article','mp-querido-result-item');card.appendChild(elem('h4','',p.name));
  card.appendChild(elem('p','',all.length?all.map(([emoji,count])=>`${emoji} × ${count}`).join('   '):'Nenhum emoji recebido nesta rodada.'));
  list.appendChild(card);
 }
}
function renderQueridometroControls(mine){
 const sec=controlsCard('💟 Queridômetro da Casa','Escolha um emoji para cada participante — igual ao modo solo. Você também pode preencher sugestões pelas relações e revisar antes de enviar. Os autores não serão revelados.');
 if(mine.done){sec.appendChild(elem('div','mp-proof-done','✅ Seus emojis foram registrados. Aguarde o restante da casa.'));return;}
 const board=elem('div','mp-querido-grid'),values={...(room.myQueridometro||{})};const targets=active().filter(p=>p.id!==room.myId);
 for(const p of targets){
  const row=elem('div','mp-querido-card');row.appendChild(elem('h4','',p.name));
  row.appendChild(elem('small','',`${p.profile?.personalidade||'Participante'} • Relação: ${p.relation}`));
  const select=elem('select','mp-querido-select');select.dataset.target=p.id;select.appendChild(new Option('Escolha um emoji',''));
  for(const [emoji,labelText] of Object.entries(room.emojis||{}))select.appendChild(new Option(`${emoji} ${labelText}`,emoji));
  select.value=values[p.id]||'';select.onchange=()=>{values[p.id]=select.value;};row.appendChild(select);board.appendChild(row);
 }
 sec.appendChild(board);
 sec.appendChild(makeButton('✨ Sugerir emojis pelas relações',()=>{
   for(const p of targets){const emoji=p.relation>=20?'❤️':p.relation>=8?'🤝':p.relation<=-20?'🐍':p.relation<=-8?'🙄':'😄';
    values[p.id]=emoji;const select=board.querySelector(`select[data-target="${p.id}"]`);if(select)select.value=emoji;}
 },'btn mp-secondary'));
 sec.appendChild(makeButton('💟 Confirmar Queridômetro completo',()=>{
  if(targets.some(p=>!values[p.id]))return notice('Escolha um emoji para cada participante antes de confirmar.',true);
  act('queridometro',{emojis:values});
 },'btn mp-main-btn'));
}
function renderDiscordiaControls(mine){
 const t=room.discordiaTema||['🔥 Jogo da Discórdia','Escolha alguém para apontar'];
 const sec=controlsCard(t[0],t[1]+' Você faz seu apontamento, a outra pessoa responde e você pode dar uma tréplica. As falas são públicas.');
 if(!mine.done){
  sec.appendChild(elem('label','mp-form-label','🎯 Quem você quer apontar?'));
  const target=makeSelect('mp-discordia-alvo',eligible());sec.appendChild(target);
  sec.appendChild(elem('label','mp-form-label','🎬 Motivo do apontamento'));
  const motives=makeSelect('mp-disc-motivo',[{value:'jogo',text:'🎭 Joga dos dois lados'},{value:'convivencia',text:'🏠 Convivência'},{value:'confianca',text:'🤝 Confiança'},{value:'estrategia',text:'🎯 Estratégia'}],'Escolha um motivo');sec.appendChild(motives);
  sec.appendChild(elem('label','mp-form-label','🎙️ Seu argumento'));
  const args=makeSelect('mp-disc-argumento',Object.entries(room.discordiaArgumentos||{}).map(([value,text])=>({value,text})),'Escolha o argumento');sec.appendChild(args);
  sec.appendChild(makeButton('🔥 Fazer apontamento',()=>{
    if(!target.value||!motives.value||!args.value)return notice('Escolha participante, motivo e argumento.',true);
    act('discordia',{id:target.value,reason:motives.value,argument:args.value});
  },'btn mp-main-btn'));
 }else sec.appendChild(elem('div','mp-proof-done','✅ Seu apontamento foi registrado. Você ainda pode responder aos debates que te envolvem.'));
 const related=(room.debates||[]).filter(d=>d.from===room.myId||d.target===room.myId);
 for(const d of related){
   const b=elem('div','mp-debate');const other=d.from===room.myId?label(d.target):label(d.from);
   b.appendChild(elem('h4','',`🎙️ Debate com ${other}`));
   b.appendChild(elem('p','',room.discordiaArgumentos[d.argument]||'Argumento registrado'));
   if(d.target===room.myId && d.status==='aguardando'){
     b.appendChild(elem('p','','Você foi apontado. Escolha sua resposta:'));
     for(const [value,text] of Object.entries(room.discordiaRespostas||{}))b.appendChild(makeButton(text,()=>act('discordia_responder',{debateId:d.id,choice:value}),'mp-debate-btn'));
   }else if(d.from===room.myId && d.status==='treplica'){
     b.appendChild(elem('p','',`${other} respondeu: ${room.discordiaRespostas[d.response]||d.response}`));
     b.appendChild(elem('p','','Sua tréplica:'));
     for(const [value,text] of Object.entries(room.discordiaTreplicas||{}))b.appendChild(makeButton(text,()=>act('discordia_treplica',{debateId:d.id,choice:value}),'mp-debate-btn'));
   }else b.appendChild(elem('p','mp-mini-hint',d.status==='aguardando'?'⏳ Aguardando a resposta.':d.status==='treplica'?'⏳ Aguardando a tréplica.':d.status==='concluido'?'✅ Debate concluído.':'⌛ Debate encerrado pelo tempo.'));
   sec.appendChild(b);
 }
}
function renderMonstroTarefa(){
 const root=$('monstroTarefa');if(!root)return;
 const mine=me(),activeTask=room.phase==='interacoes_2'&&mine?.isMonster&&!mine?.monsterTaskDone&&!room.paused;
 root.hidden=!activeTask;
 if(!activeTask||root.dataset.loaded==='1')return;
 root.dataset.loaded='1';root.replaceChildren();
 root.appendChild(elem('h3','','👹 Castigo do Monstro'));
 root.appendChild(elem('p','','Como Monstro, cumpra uma tarefa extra na casa. Não gasta suas interações.'));
 for(const [value,labelText] of [['cozinha','🍳 Organizar a cozinha'],['limpeza','🧽 Limpar a área externa'],['roupas','🧺 Organizar o figurino']])root.appendChild(makeButton(labelText,()=>act('monstro_task',{task:value}),'mp-debate-btn'));
}
function renderFesta(){
 const mine=me(),key=room.phase+'|'+mine?.actionsUsed+'|'+mine?.done;
 if(key===festaKey)return;festaKey=key;selectedFesta='';
 const root=$('festaBoard');root.replaceChildren();
 const card=elem('section','i4-selecao mp-festa-card');root.appendChild(card);
 card.appendChild(elem('div','i4-kicker','● REC • FESTA DA SEMANA'));
 card.appendChild(elem('h3','','🎉 Sua festa, suas escolhas'));
 card.appendChild(elem('p','',`Você tem ${Math.max(0,3-(mine?.actionsUsed||0))} ações restantes. Os convites precisam da resposta dos outros jogadores; acordos de voto ficam em segredo.`));
 if(mine?.done){card.appendChild(elem('p','mp-proof-done','✅ Você terminou suas ações. Pode responder os convites recebidos.'));return;}
 const options=elem('div','mp-festa-grid');card.appendChild(options);
 const form=elem('div','mp-festa-form');card.appendChild(form);
 const types=room.festaOptions||{};
 for(const [id,c] of Object.entries(types)){
  options.appendChild(makeButton(c[0],()=>{
   selectedFesta=id;form.replaceChildren();form.appendChild(elem('h4','',c[0]));form.appendChild(elem('p','',c[1]));
   let target=null,checklist=null,vote=null,message=null;
   if(c[3]){
     if(id==='negociar'){
       checklist=elem('div','mp-festa-targets');for(const p of active().filter(p=>p.id!==room.myId)){
         const l=elem('label','mp-festa-target');const cb=elem('input');cb.type='checkbox';cb.name='festa-convidado';cb.value=p.id;l.append(cb,document.createTextNode(p.name));checklist.appendChild(l);
       }
       form.appendChild(elem('label','','Convide de 1 a 3 colegas (sem incluir o alvo)'));form.appendChild(checklist);
       form.appendChild(elem('label','','Qual participante vocês pretendem votar?'));
       vote=makeSelect('mp-festa-vote',eligible(),'Escolha o alvo do possível acordo');form.appendChild(vote);
     }else{
       form.appendChild(elem('label','','Com quem?'));target=makeSelect('mp-festa-target',eligible());form.appendChild(target);
     }
     form.appendChild(elem('label','','Mensagem privada (opcional)'));
     message=elem('textarea','mp-festa-message');message.maxLength=160;message.rows=2;message.placeholder='Escreva sua mensagem para o convidado...';form.appendChild(message);
   }
   form.appendChild(makeButton('🎉 Confirmar ação da festa',()=>{
     let targetIds=[];if(checklist){targetIds=[...checklist.querySelectorAll('input:checked')].map(e=>e.value);
       if(targetIds.length<1||targetIds.length>3)return notice('Selecione de um a três convidados.',true);
       if(!vote.value||targetIds.includes(vote.value))return notice('Escolha um alvo diferente dos convidados.',true);
     }else if(target&&!target.value)return notice('Escolha o participante.',true);
     act('festa_action',{festaType:id,...(checklist?{targetIds,voteTarget:vote.value}:target?{targetId:target.value}:{}),message:message?.value||''});
   },'btn mp-main-btn'));
  },'mp-festa-choice'));
 }
}
function renderControls(force){const mine=me();const proofStage=room.proof?.type==='memoria'?(Math.floor((Date.now()+serverOffset)/1000)<room.proof.revealUntil?'reveal':'play'):'';
const relevant=(room.debates||[]).filter(d=>d.from===room.myId||d.target===room.myId);
const key=room.phase+'|'+!!mine?.done+'|'+JSON.stringify(mine?.choice)+'|'+room.leaderPick+'|'+JSON.stringify(room.nominees)+'|'+(room.proof?.type||'')+'|'+(room.proof?.mySubmitted||false)+'|'+(room.proof?.eligible||false)+'|'+proofStage+'|'+JSON.stringify(relevant)+'|'+JSON.stringify(room.monsterIds)+'|'+JSON.stringify(room.myQueridometro);
if(!force&&controlKey===key)return;controlKey=key;
const phase=room.phase;
if(phase==='lider'||phase==='anjo'){renderProof();return;}
if(phase==='vip_xepa'){
const sec=controlsCard('🍽️ VIP & Xepa',`O Líder escolhe até ${Math.max(1,Math.floor(active().length/3))} pessoas para o VIP, contando com ele próprio.`);
if(room.myId!==room.leaderId){sec.appendChild(elem('p','','⏳ Aguarde a escolha do Líder.'));return;}
if(mine.done){sec.appendChild(elem('p','','✅ Você já definiu o VIP.'));return;}
const max=Math.max(1,Math.floor(active().length/3))-1;
for(const p of active().filter(p=>p.id!==room.myId)){const labelEl=elem('label','mp-choice');const ch=elem('input');ch.type='checkbox';ch.name='vip';ch.value=p.id;labelEl.append(ch,document.createTextNode(p.name));sec.appendChild(labelEl);}
sec.appendChild(makeButton('🍽️ Confirmar VIP',()=>{const ids=[...sec.querySelectorAll('input[name="vip"]:checked')].map(x=>x.value);if(ids.length>max){notice('Escolha no máximo '+max+' colegas para acompanhar você no VIP.',true);return;}act('vip',{ids});},'btn mp-main-btn'));return;
}
if(phase==='monstro'){
const sec=controlsCard('👹 Monstro do Anjo','O Anjo pode escolher até duas pessoas para o Castigo do Monstro. Elas terão uma tarefa extra de convivência após o Queridômetro.');
if(room.myId!==room.angelId){sec.appendChild(elem('p','','⏳ Aguarde a decisão do Anjo.'));return;}
if(mine.done){sec.appendChild(elem('p','','✅ Sua escolha de Monstros foi registrada.'));return;}
const choices=active().filter(p=>p.id!==room.myId),limit=Math.min(2,choices.length);
for(const p of choices){const l=elem('label','mp-choice');const input=elem('input');input.type='checkbox';input.name='mp-monstro';input.value=p.id;l.append(input,document.createTextNode(p.name));sec.appendChild(l);}
sec.appendChild(makeButton('👹 Confirmar Monstros',()=>{const ids=[...sec.querySelectorAll('input[name="mp-monstro"]:checked')].map(x=>x.value);if(ids.length>limit)return notice('Escolha até '+limit+' pessoas.',true);act('monstro',{ids});},'btn mp-main-btn'));return;
}
if(phase==='imunidade'){
const sec=controlsCard('🛡️ Presente do Anjo','O Anjo escolhe uma pessoa para receber imunidade. Nesta beta, não é possível imunizar a si próprio.');
if(room.myId!==room.angelId){sec.appendChild(elem('p','','⏳ Aguarde a decisão do Anjo.'));return;}
if(mine.done){sec.appendChild(elem('p','','✅ Imunidade escolhida.'));return;}
const s=makeSelect('mp-target',eligible());sec.appendChild(s);sec.appendChild(makeButton('🛡️ Conceder imunidade',()=>{if(!s.value){notice('Escolha alguém.',true);return;}act('imune',{id:s.value});},'btn mp-main-btn'));return;
}
if(phase==='queridometro'){renderQueridometroControls(mine);return;}
if(phase==='discordia'){renderDiscordiaControls(mine);return;}
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
act('interact',{type:selectedType,targetId:$('targetPlayer').value,subject:$('actionSubject').value,approach:$('actionApproach').value,message:$('actionMessage').value,voteTarget:selectedType==='i4_acordo' ? $('voteTarget').value : undefined}).then(ok=>{
 if(ok){selectedType='';$('interactionForm').hidden=true;$('categories').hidden=!!me()?.done;}
});
});
$('cancelAction').onclick=()=>{selectedType='';$('interactionForm').hidden=true;$('categories').hidden=false;if(room?.phase==='festa')$('festaBoard').hidden=false;};
$('finishButton').onclick=()=>act('finish');
$('pauseGame').onclick=()=>{if(confirm('Pausar a temporada e congelar o relógio para todos?'))act('pause');};
$('resumeGame').onclick=()=>{if(confirm('Retomar agora? Todos voltarão a jogar e o cronômetro continuará.'))act('resume');};
$('downloadBackup').onclick=exportBackup;
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
