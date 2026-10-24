(() => {
 const dialog=document.querySelector('[data-password-reset-dialog]');
 if(!dialog)return;
 const close=dialog.querySelector('[data-close-password-reset]');
 close.addEventListener('click',()=>dialog.close());
 dialog.addEventListener('close',()=>{
  dialog.querySelector('[data-temporary-password]').textContent='';
  dialog.remove();
 });
 dialog.showModal();
 close.focus();
})();
