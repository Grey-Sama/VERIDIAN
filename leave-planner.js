(() => {
 const form = document.querySelector('[data-leave-planner]');
 if (!form) return;
 const count = form.elements.leave_days, start = form.elements.start_date, end = form.elements.end_date;
 const message = form.querySelector('[data-planner-message]');
 function update() {
  const fast = form.elements.leave_type.value === 'Fast';
  form.querySelector('[data-duration-label]').textContent = fast ? 'Fast Leave Uses' : 'Number of Leave Days';
  count.readOnly = fast;
  if(fast)count.value='1';
  form.querySelector('#leave-days-hint').textContent = fast ? 'One half-day per use. Up to 3 uses per year.' : 'Whole weekdays. Subject to your available balance.';
  end.value = ''; start.setCustomValidity('');
  if (!start.value || !count.value) { message.textContent = 'Choose days and a start date.'; return; }
  const day = new Date(start.value + 'T00:00:00Z'), total = Number(count.value);
  if (!Number.isInteger(total) || total < 1 || total > 260 || Number.isNaN(day.getTime())) { message.textContent = 'Enter 1–260 whole days.'; return; }
  if ([0,6].includes(day.getUTCDay())) { start.setCustomValidity('Choose Monday–Friday.'); message.textContent = 'Choose Monday–Friday.'; return; }
  let left = total - 1;
  while (left) { day.setUTCDate(day.getUTCDate() + 1); if (![0,6].includes(day.getUTCDay())) left--; }
  end.value = day.toISOString().slice(0,10);
  message.textContent = fast ? 'Half-day · 0.5 day · 1 use.' : total + (total === 1 ? ' weekday' : ' weekdays') + ' · Ends ' + day.toLocaleDateString('en-PH', {year:'numeric',month:'short',day:'numeric',timeZone:'UTC'}) + '.';
 }
 form.elements.leave_type.addEventListener('change',update);count.addEventListener('input',update); start.addEventListener('input',update); update();
})();
