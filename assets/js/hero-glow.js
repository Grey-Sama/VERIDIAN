(() => {
 'use strict';
 const hero=document.querySelector('.hero');
 if(!hero)return;
 const enabled=window.matchMedia('(hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference)');
 let frame=0, x=50, y=30, targetX=x, targetY=y;
 const paint=()=>{
  x+=(targetX-x)*.14;y+=(targetY-y)*.14;
  hero.style.setProperty('--glow-x',`${x.toFixed(2)}%`);
  hero.style.setProperty('--glow-y',`${y.toFixed(2)}%`);
  frame=Math.abs(targetX-x)+Math.abs(targetY-y)>.08?requestAnimationFrame(paint):0;
 };
 const move=event=>{
  if(!enabled.matches||event.pointerType!=='mouse')return;
  const bounds=hero.getBoundingClientRect();
  if(!bounds.width||!bounds.height)return;
  targetX=Math.max(0,Math.min(100,(event.clientX-bounds.left)/bounds.width*100));
  targetY=Math.max(0,Math.min(100,(event.clientY-bounds.top)/bounds.height*100));
  if(!hero.classList.contains('hero-glow-active')){x=targetX;y=targetY;}
  hero.classList.add('hero-glow-active');
  if(!frame)frame=requestAnimationFrame(paint);
 };
 const stop=()=>{
  cancelAnimationFrame(frame);frame=0;
  hero.classList.remove('hero-glow-active');
 };
 hero.addEventListener('pointermove',move,{passive:true});
 hero.addEventListener('pointerleave',stop);
 hero.addEventListener('pointercancel',stop);
 enabled.addEventListener('change',stop);
 document.addEventListener('visibilitychange',()=>{if(document.hidden)stop();});
 window.addEventListener('pagehide',stop);
})();
