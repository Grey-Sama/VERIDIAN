(() => {
 const dialog=document.querySelector('[data-delete-dialog]');
 if(!dialog)return;
 const confirmation=dialog.querySelector('[data-delete-confirm]');
 document.querySelectorAll('[data-delete-account]').forEach(button=>button.addEventListener('click',()=>{
  dialog.querySelector('[data-delete-id]').value=button.dataset.deleteAccount;
  dialog.querySelector('[data-delete-name]').textContent=button.dataset.deleteUsername;
  confirmation.value='';
  confirmation.setCustomValidity('');
  confirmation.dataset.expected=button.dataset.deleteUsername;
  dialog.showModal();confirmation.focus();
 }));
 dialog.querySelectorAll('[data-close-delete]').forEach(button=>button.addEventListener('click',()=>dialog.close()));
 confirmation.addEventListener('input',()=>confirmation.setCustomValidity(confirmation.value===confirmation.dataset.expected?'':'Enter the exact username.'));
})();
