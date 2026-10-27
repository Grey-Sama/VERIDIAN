(() => {
 'use strict';
 const open = dialog => {
   if (!dialog || typeof dialog.showModal !== 'function') return false;
   if (dialog.open) dialog.close();
   dialog.showModal();
   return true;
 };
 document.querySelectorAll('[data-balance-open]').forEach(link => {
   link.addEventListener('click', event => {
     if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
     if (open(document.getElementById(link.dataset.balanceOpen))) event.preventDefault();
   });
 });
 document.querySelectorAll('.balance-dialog').forEach(dialog => {
   const form = dialog.querySelector('form');
   dialog.addEventListener('close', () => { if (form) form.reset(); });
   dialog.querySelector('[data-balance-close]').addEventListener('click', event => {
     if (typeof dialog.close !== 'function') return;
     event.preventDefault();dialog.close();
   });
   if (dialog.open) open(dialog);
 });
})();
