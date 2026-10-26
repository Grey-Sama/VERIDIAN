(() => {
 document.querySelectorAll('select[name="job_title"],select[name="department"]').forEach(select=>{
  const extra=select.closest('label').nextElementSibling;
  if(!extra?.classList.contains('personnel-other'))return;
  const update=()=>{extra.hidden=select.value!=='Other';extra.querySelector('input').required=!extra.hidden;};
  select.addEventListener('change',update);update();
 });
})();
