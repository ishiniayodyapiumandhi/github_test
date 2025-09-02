(() => {
  const calendarGrid = document.getElementById('calendarGrid');
  const currentMonthLabel = document.getElementById('currentMonthLabel');
  const prevMonthBtn = document.getElementById('prevMonthBtn');
  const nextMonthBtn = document.getElementById('nextMonthBtn');
  const todayBtn = document.getElementById('todayBtn');

  const dayModal = document.getElementById('dayModal');
  const closeModalBtn = document.getElementById('closeModalBtn');
  const modalDateLabel = document.getElementById('modalDateLabel');
  const addApptForm = document.getElementById('addApptForm');
  const apptTime = document.getElementById('apptTime');
  const apptDuration = document.getElementById('apptDuration');
  const apptTitle = document.getElementById('apptTitle');
  const apptNotes = document.getElementById('apptNotes');
  const apptList = document.getElementById('apptList');
  const apptItemTemplate = document.getElementById('apptItemTemplate');

  let viewYear, viewMonth; // month: 1..12
  let selectedDate = null; // YYYY-MM-DD

  function pad(n) { return n.toString().padStart(2, '0'); }

  function formatMonthLabel(y, m) {
    const dt = new Date(y, m - 1, 1);
    return dt.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
  }

  async function apiGet(params) {
    const url = new URL(window.API_BASE, window.location.origin);
    Object.keys(params).forEach(k => url.searchParams.set(k, params[k]));
    const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error('Request failed');
    return res.json();
  }

  async function apiPost(params, body) {
    const url = new URL(window.API_BASE, window.location.origin);
    Object.keys(params).forEach(k => url.searchParams.set(k, params[k]));
    const res = await fetch(url.toString(), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(body || {})
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(json.error || 'Request failed');
    return json;
  }

  async function renderMonth(y, m) {
    currentMonthLabel.textContent = formatMonthLabel(y, m);
    calendarGrid.innerHTML = '';
    const firstDow = (new Date(y, m - 1, 1)).getDay();
    const daysInMonth = new Date(y, m, 0).getDate();
    const daysInPrev = new Date(y, m - 1, 0).getDate();
    const cells = [];

    // leading days
    for (let i = firstDow - 1; i >= 0; i--) {
      const dayNum = daysInPrev - i;
      const date = new Date(y, m - 2, dayNum);
      cells.push({ date, inMonth: false });
    }
    // month days
    for (let d = 1; d <= daysInMonth; d++) {
      const date = new Date(y, m - 1, d);
      cells.push({ date, inMonth: true });
    }
    // trailing to fill 6 weeks (42 cells)
    while (cells.length % 7 !== 0 || cells.length < 42) {
      const last = cells[cells.length - 1].date;
      const next = new Date(last);
      next.setDate(last.getDate() + 1);
      cells.push({ date: next, inMonth: false });
    }

    let summary = {};
    try {
      const res = await apiGet({ action: 'month', year: y, month: m });
      summary = res.days || {};
    } catch (e) {
      summary = {};
    }

    const todayStr = new Date().toISOString().slice(0, 10);
    for (const cell of cells) {
      const d = cell.date;
      const dateStr = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
      const el = document.createElement('div');
      el.className = 'day' + (cell.inMonth ? '' : ' outside') + (dateStr === todayStr ? ' today' : '');
      el.setAttribute('data-date', dateStr);

      const dateLabel = document.createElement('div');
      dateLabel.className = 'date';
      dateLabel.textContent = d.getDate();
      el.appendChild(dateLabel);

      const count = summary[dateStr] || 0;
      if (count > 0) {
        const badge = document.createElement('div');
        badge.className = 'badge';
        badge.textContent = `${count} appt` + (count > 1 ? 's' : '');
        el.appendChild(badge);
      }

      el.addEventListener('click', () => openDay(dateStr));
      calendarGrid.appendChild(el);
    }
  }

  async function openDay(dateStr) {
    selectedDate = dateStr;
    modalDateLabel.textContent = new Date(dateStr + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    apptTime.value = '';
    apptTitle.value = '';
    apptNotes.value = '';
    await refreshApptList();
    showModal(true);
  }

  function showModal(show) {
    dayModal.classList.toggle('show', show);
    dayModal.setAttribute('aria-hidden', show ? 'false' : 'true');
  }

  async function refreshApptList() {
    apptList.innerHTML = '';
    try {
      const res = await apiGet({ action: 'list', date: selectedDate });
      const items = res.items || [];
      for (const it of items) {
        const li = apptItemTemplate.content.firstElementChild.cloneNode(true);
        li.querySelector('.time').textContent = `${it.time} (${it.duration}m)`;
        li.querySelector('.title').textContent = it.title;
        li.querySelector('.delete-btn').addEventListener('click', async (ev) => {
          ev.stopPropagation();
          try {
            await apiPost({ action: 'delete' }, { date: selectedDate, id: it.id });
            await refreshApptList();
            await renderMonth(viewYear, viewMonth);
          } catch (e) {
            alert(e.message || 'Delete failed');
          }
        });
        apptList.appendChild(li);
      }
    } catch (e) {
      const li = document.createElement('li');
      li.textContent = 'Failed to load appointments';
      apptList.appendChild(li);
    }
  }

  addApptForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const time = apptTime.value;
    const duration = parseInt(apptDuration.value, 10) || 0;
    const title = apptTitle.value.trim();
    const notes = apptNotes.value.trim();
    if (!time || !title || duration < 5) {
      alert('Please enter time, duration >= 5, and title.');
      return;
    }
    try {
      await apiPost({ action: 'add' }, { date: selectedDate, time, duration, title, notes });
      await refreshApptList();
      await renderMonth(viewYear, viewMonth);
      apptTime.value = '';
      apptTitle.value = '';
      apptNotes.value = '';
    } catch (e) {
      alert(e.message || 'Add failed');
    }
  });

  closeModalBtn.addEventListener('click', () => showModal(false));
  dayModal.addEventListener('click', (e) => { if (e.target === dayModal) showModal(false); });

  prevMonthBtn.addEventListener('click', async () => {
    const d = new Date(viewYear, viewMonth - 2, 1);
    viewYear = d.getFullYear();
    viewMonth = d.getMonth() + 1;
    await renderMonth(viewYear, viewMonth);
  });

  nextMonthBtn.addEventListener('click', async () => {
    const d = new Date(viewYear, viewMonth, 1);
    viewYear = d.getFullYear();
    viewMonth = d.getMonth() + 1;
    await renderMonth(viewYear, viewMonth);
  });

  todayBtn.addEventListener('click', async () => {
    const now = new Date();
    viewYear = now.getFullYear();
    viewMonth = now.getMonth() + 1;
    await renderMonth(viewYear, viewMonth);
  });

  // init
  const now = new Date();
  viewYear = now.getFullYear();
  viewMonth = now.getMonth() + 1;
  renderMonth(viewYear, viewMonth);
})();

