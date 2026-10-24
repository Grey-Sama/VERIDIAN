(() => {
 'use strict';
 // Workspace navigation keeps the session. An unplanned page exit records a close
 // intent; PHP allows a short reload grace period and ignores obsolete documents.
 const body=document.body;
 if(body.dataset.sessionDocument && body.dataset.sessionMode==='ordinary'){
  let navigating=false;
  document.addEventListener('click',event=>{
   const link=event.target.closest('a[href]');
   if(!link || event.defaultPrevented || event.button!==0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target==='_blank')return;
   const url=new URL(link.href,location.href);
   if(url.origin===location.origin && url.pathname===location.pathname && url.searchParams.get('auth')!=='login' && (url.search!==location.search || !url.hash))navigating=true;
  });
  document.addEventListener('submit',event=>{if(!event.defaultPrevented)navigating=true;});
  window.addEventListener('pageshow',()=>{navigating=false;});
  window.addEventListener('pagehide',()=>{
   if(navigating)return;
   const data=new FormData();data.set('csrf',body.dataset.sessionCsrf);data.set('document',body.dataset.sessionDocument);
   navigator.sendBeacon('session-state.php',data);
  });
 }
 window.addEventListener('pageshow', event => {
  if(event.persisted){window.location.reload();return;}
  // History restoration must not leave previously typed credentials in the form.
  const action=document.querySelector('form input[name="action"][value="login"]');
  if(action)action.form.reset();
 });
})();
