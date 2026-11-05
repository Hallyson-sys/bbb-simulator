/* Backup privado da temporada: criptografia com senha local, sem serviço externo. */
window.BBBBackup=(()=>{
'use strict';
const enc=new TextEncoder(),dec=new TextDecoder();
const to64=b=>{const bytes=new Uint8Array(b);let s='';for(let i=0;i<bytes.length;i+=8192)s+=String.fromCharCode(...bytes.subarray(i,i+8192));return btoa(s);};
const from64=s=>Uint8Array.from(atob(s),x=>x.charCodeAt(0));
async function key(password,salt){
 const base=await crypto.subtle.importKey('raw',enc.encode(password),'PBKDF2',false,['deriveKey']);
 return crypto.subtle.deriveKey({name:'PBKDF2',salt,iterations:200000,hash:'SHA-256'},base,{name:'AES-GCM',length:256},false,['encrypt','decrypt']);
}
async function seal(room,token,password){
 if(!crypto?.subtle)throw Error('Este navegador não oferece criptografia segura. Use o Safari com HTTPS.');
 if(password.length<8)throw Error('Escolha uma senha de pelo menos oito caracteres para proteger seu backup.');
 const salt=crypto.getRandomValues(new Uint8Array(16)),iv=crypto.getRandomValues(new Uint8Array(12));
 const encrypted=await crypto.subtle.encrypt({name:'AES-GCM',iv},await key(password,salt),enc.encode(JSON.stringify({room,token})));
 return JSON.stringify({format:'bbb-multiplayer-backup',version:1,algorithm:'PBKDF2-AES-GCM',salt:to64(salt),iv:to64(iv),data:to64(encrypted)},null,2);
}
async function open(text,password){
 if(!crypto?.subtle)throw Error('Abra esta página em uma conexão HTTPS segura.');
 if(text.length>800000)throw Error('Backup muito grande.');
 let packet;try{packet=JSON.parse(text);}catch(e){throw Error('O arquivo escolhido não é um JSON válido.');}
 if(packet?.format!=='bbb-multiplayer-backup'||packet.version!==1)throw Error('Este arquivo não é um backup da Beta 5.');
 try{
   const decrypted=await crypto.subtle.decrypt({name:'AES-GCM',iv:from64(packet.iv)},await key(password,from64(packet.salt)),from64(packet.data));
   const payload=JSON.parse(dec.decode(decrypted));
   if(!payload?.room?.code||!payload?.token)throw Error('Arquivo inválido.');
   return payload;
 }catch(e){throw Error('Senha incorreta ou backup danificado.');}
}
function download(text,filename){
 const url=URL.createObjectURL(new Blob([text],{type:'application/json;charset=utf-8'}));
 const a=document.createElement('a');a.href=url;a.download=filename;document.body.appendChild(a);a.click();a.remove();
 setTimeout(()=>URL.revokeObjectURL(url),30000);
}
return {seal,open,download};
})();
