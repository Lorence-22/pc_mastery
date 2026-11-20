(function () {
  /* ================= Illustrations (SVG) ================= */
  const blade = (n, fill, sc) => { let s = ''; for (let k = 0; k < n; k++) s += `<path d="M0 0C8 -14 24 -30 12 -44C-4 -38 -6 -16 0 0Z" transform="rotate(${k * 360 / n}) scale(${sc})" fill="${fill}" opacity=".92"/>`; return s; };
  const pins = (x, y, cols, rows, gap) => { let s = ''; for (let r = 0; r < rows; r++) for (let c = 0; c < cols; c++) s += `<circle cx="${x + c * gap}" cy="${y + r * gap}" r="1.6" fill="#facc15"/>`; return s; };

  // Motherboard drawn in 400x400 units; "inst-*" layers appear when the matching part is installed.
  function boardGroup() {
    let usb = ''; [44, 60, 76, 92].forEach(y => { usb += `<rect x="9" y="${y}" width="15" height="9" fill="#2563eb"/><rect x="27" y="${y}" width="15" height="9" fill="#2563eb"/>`; });
    let ram = ''; for (let i = 0; i < 4; i++) ram += `<rect x="${275 + i * 18}" y="40" width="12" height="190" rx="2" fill="#0f172a" stroke="#475569"/><rect x="${275 + i * 18}" y="35" width="12" height="7" fill="#94a3b8"/>`;
    let fins = ''; for (let i = 0; i < 9; i++) fins += `<line x1="${62 + i * 22}" y1="42" x2="${62 + i * 22}" y2="64" stroke="#374151"/>`;
    const stick = x => `<rect x="${x}" y="34" width="16" height="200" rx="2" fill="#0e7490" stroke="#164e63"/>` + [0, 1, 2, 3, 4, 5].map(k => `<rect x="${x + 2}" y="${48 + k * 28}" width="12" height="18" fill="#111827"/>`).join('') + `<rect x="${x}" y="228" width="16" height="5" fill="#facc15"/>`;
    return `
<rect width="400" height="400" rx="8" fill="#15803d" stroke="#052e16" stroke-width="3"/>
<g stroke="#4ade80" stroke-opacity=".35" fill="none" stroke-width="1.5"><path d="M20 230H120V300H200"/><path d="M60 210V330H160"/><path d="M250 240H330V300"/><path d="M20 130H100"/><path d="M200 220V250H260"/><path d="M340 200V300"/></g>
${[[14, 14], [386, 14], [14, 386], [386, 386], [200, 14], [200, 386], [14, 200]].map(([x, y]) => `<circle cx="${x}" cy="${y}" r="5" fill="#fde68a" stroke="#713f12"/>`).join('')}
<rect x="4" y="34" width="46" height="170" fill="#9ca3af" stroke="#4b5563"/>${usb}
<rect x="9" y="110" width="30" height="10" fill="#111827"/><rect x="9" y="126" width="30" height="10" fill="#1e3a8a"/>
<circle cx="16" cy="152" r="5" fill="#22c55e"/><circle cx="34" cy="152" r="5" fill="#ec4899"/><circle cx="16" cy="170" r="5" fill="#3b82f6"/><circle cx="34" cy="170" r="5" fill="#f59e0b"/><rect x="9" y="182" width="30" height="16" fill="#cbd5e1" stroke="#475569"/>
<rect x="56" y="38" width="200" height="28" fill="#111827" stroke="#374151"/>${fins}<rect x="56" y="38" width="26" height="160" fill="#111827" stroke="#374151"/>
<rect x="100" y="14" width="46" height="14" fill="#e5e7eb" stroke="#6b7280"/>
<rect x="120" y="80" width="120" height="120" rx="4" fill="#475569" stroke="#1f2937" stroke-width="2"/><rect x="132" y="92" width="96" height="96" fill="#64748b"/>${pins(140, 100, 12, 12, 7.8)}
${ram}
<rect x="270" y="250" width="60" height="60" rx="4" fill="#1e293b" stroke="#475569"/><g stroke="#475569">${[0, 1, 2, 3, 4].map(i => `<line x1="276" y1="${258 + i * 11}" x2="324" y2="${258 + i * 11}"/>`).join('')}</g>
<rect x="50" y="250" width="200" height="12" rx="2" fill="#0f172a" stroke="#475569"/><rect x="50" y="290" width="100" height="12" rx="2" fill="#0f172a" stroke="#475569"/><rect x="50" y="330" width="200" height="12" rx="2" fill="#0f172a" stroke="#475569"/>
${[[330, 320], [356, 320], [330, 340], [356, 340]].map(([x, y]) => `<rect x="${x}" y="${y}" width="22" height="14" rx="2" fill="#111827" stroke="#ef4444"/>`).join('')}
<rect x="360" y="50" width="16" height="100" fill="#e5e7eb" stroke="#6b7280"/>${pins(364, 56, 2, 12, 8)}
<rect x="230" y="372" width="80" height="16" fill="#111827" stroke="#374151"/>${pins(238, 376, 8, 2, 9)}
<circle cx="296" cy="358" r="13" fill="#cbd5e1" stroke="#64748b"/>
<g class="inst inst-cpu_socket"><rect x="132" y="92" width="96" height="96" rx="4" fill="#cbd5e1" stroke="#64748b"/><rect x="146" y="106" width="68" height="68" rx="3" fill="#e2e8f0" stroke="#94a3b8"/><text x="180" y="146" text-anchor="middle" font-size="15" font-weight="700" fill="#334155" font-family="sans-serif">CPU</text><path d="M133 93h14l-14 14z" fill="#facc15"/></g>
<g class="inst inst-thermal"><circle cx="180" cy="140" r="13" fill="#f8fafc" stroke="#94a3b8" stroke-dasharray="2 2"/></g>
<g class="inst inst-cpu_cooler"><rect x="104" y="64" width="152" height="152" rx="14" fill="#111827" stroke="#06b6d4" stroke-width="3"/><circle cx="180" cy="140" r="58" fill="#1f2937" stroke="#334155"/><g transform="translate(180 140)">${blade(7, '#06b6d4', 1.1)}<circle r="10" fill="#0f172a"/></g></g>
<g class="inst inst-ram_slots">${stick(291)}${stick(327)}</g>`;
  }
  const BENCH_SVG = `<svg viewBox="0 0 480 560" xmlns="http://www.w3.org/2000/svg" font-family="sans-serif">
<rect width="480" height="560" rx="14" fill="#e2e8f0"/><rect x="14" y="142" width="452" height="404" rx="12" fill="#cbd5e1"/>
<text x="24" y="30" font-size="12" font-weight="700" fill="#475569">WORKSPACE &amp; TOOLS</text><rect x="20" y="14" width="270" height="112" rx="10" fill="none" stroke="#94a3b8" stroke-dasharray="6 4"/>
<text x="310" y="30" font-size="12" font-weight="700" fill="#475569">ESD PROTECTION</text><rect x="300" y="14" width="160" height="112" rx="10" fill="none" stroke="#94a3b8" stroke-dasharray="6 4"/>
<text x="24" y="158" font-size="12" font-weight="700" fill="#475569">MOTHERBOARD (on the workbench)</text>
<g class="inst inst-workspace"><rect x="36" y="60" width="100" height="12" rx="4" fill="#ef4444"/><rect x="134" y="63" width="64" height="5" fill="#9ca3af"/><rect x="206" y="44" width="76" height="66" rx="8" fill="#475569"/>${[0, 1, 2, 3, 4].map(i => `<circle cx="${220 + (i % 3) * 22}" cy="${64 + Math.floor(i / 3) * 24}" r="5" fill="#cbd5e1"/>`).join('')}</g>
<g class="inst inst-esd"><circle cx="360" cy="76" r="26" fill="none" stroke="#2563eb" stroke-width="9"/><rect x="346" y="46" width="28" height="12" rx="3" fill="#1e3a8a"/><path d="M384 90C420 100 438 112 440 138" fill="none" stroke="#111827" stroke-width="3"/></g>
<g class="bench-board" transform="translate(40 160) scale(.95)">${boardGroup()}</g></svg>`;

  const CASE_SVG = `<svg viewBox="0 0 880 560" xmlns="http://www.w3.org/2000/svg" font-family="sans-serif">
<rect x="110" y="10" width="760" height="540" rx="16" fill="#1f2937" stroke="#0f172a" stroke-width="3"/><rect x="124" y="24" width="732" height="512" rx="8" fill="#0b1220"/>
<text x="8" y="70" font-size="11" font-weight="700" fill="#64748b">PERIPHERALS</text><text x="128" y="552" font-size="11" fill="#64748b">REAR</text><text x="820" y="552" font-size="11" fill="#64748b">FRONT</text>
${[[204, 64], [576, 64], [204, 436], [576, 436], [390, 64], [390, 436], [204, 250]].map(([x, y]) => `<circle cx="${x}" cy="${y}" r="6" fill="#d4a373" stroke="#78350f"/>`).join('')}
<rect x="190" y="50" width="400" height="400" fill="none" stroke="#1e293b" stroke-dasharray="6 6"/>
<rect x="130" y="462" width="330" height="66" rx="6" fill="#0f172a" stroke="#334155" stroke-dasharray="5 4"/>
<rect x="630" y="60" width="170" height="150" rx="6" fill="#0f172a" stroke="#334155"/><line x1="630" y1="110" x2="800" y2="110" stroke="#334155"/><line x1="630" y1="160" x2="800" y2="160" stroke="#334155"/><text x="640" y="76" font-size="10" fill="#64748b">DRIVE BAYS</text>
<rect x="640" y="225" width="190" height="60" rx="8" fill="#0f172a" stroke="#475569" stroke-dasharray="5 4"/><text x="735" y="259" text-anchor="middle" font-size="11" font-weight="700" fill="#94a3b8">INSPECTION CHECKPOINT</text>
${[350, 450].map(y => `<rect x="758" y="${y - 42}" width="84" height="84" rx="8" fill="none" stroke="#334155" stroke-dasharray="5 4"/><circle cx="800" cy="${y}" r="38" fill="none" stroke="#334155" stroke-dasharray="5 4"/>`).join('')}
<circle cx="150" cy="110" r="24" fill="none" stroke="#334155" stroke-dasharray="5 4"/>
<circle cx="830" cy="50" r="17" fill="#0f172a" stroke="#94a3b8" stroke-width="2"/><g class="boot-btn" fill="none" stroke="#94a3b8" stroke-width="3" stroke-linecap="round"><path d="M830 38v10"/><path d="M822 43a11 11 0 1 0 16 0"/></g>
<g class="inst inst-mb_tray" transform="translate(190 50)">${boardGroup()}</g>
<g class="inst inst-pcie_slot" transform="translate(190 50)"><rect x="30" y="236" width="300" height="56" rx="6" fill="#111827" stroke="#06b6d4" stroke-width="2"/><rect x="26" y="236" width="6" height="60" fill="#9ca3af"/><g transform="translate(110 264)">${blade(7, '#94a3b8', .45)}</g><g transform="translate(220 264)">${blade(7, '#94a3b8', .45)}</g><rect x="60" y="288" width="40" height="6" fill="#facc15"/></g>
<g class="inst inst-psu_bay"><rect x="140" y="470" width="310" height="50" rx="5" fill="#374151" stroke="#9ca3af"/><circle cx="200" cy="495" r="20" fill="#111827" stroke="#9ca3af"/><g transform="translate(200 495)">${blade(5, '#6b7280', .35)}</g><text x="300" y="500" font-size="14" font-weight="700" fill="#e5e7eb">PSU 650W 80+</text><rect x="420" y="476" width="22" height="38" fill="#16a34a"/></g>
<g class="inst inst-drive_bay"><rect x="645" y="120" width="140" height="34" rx="4" fill="#64748b" stroke="#e2e8f0"/><text x="715" y="142" text-anchor="middle" font-size="12" font-weight="700" fill="#f8fafc">SSD / HDD</text><rect x="645" y="126" width="8" height="22" fill="#111827"/></g>
<g class="inst inst-atx_power" fill="none" stroke-linecap="round"><path d="M440 470V462H608V130H566" stroke="#111827" stroke-width="10"/><path d="M440 470V462H608V130H566" stroke="#facc15" stroke-width="3" stroke-dasharray="6 6"/><path d="M178 462V42H300V66" stroke="#111827" stroke-width="8"/><path d="M178 462V42H300V66" stroke="#facc15" stroke-width="2.5" stroke-dasharray="5 5"/></g>
<g class="inst inst-sata_ports" fill="none" stroke-linecap="round"><path d="M550 372H614V150H645" stroke="#ef4444" stroke-width="4"/><path d="M450 480H622V138H645" stroke="#111827" stroke-width="4"/></g>
<g class="inst inst-front_panel" fill="none" stroke-linecap="round"><path d="M446 438V456H846V80" stroke="#f8fafc" stroke-width="2.5"/><path d="M452 438V458H850V80" stroke="#3b82f6" stroke-width="2.5"/><path d="M458 438V460H854V80" stroke="#ef4444" stroke-width="2.5"/></g>
<g class="inst inst-fan_mounts">${[350, 450].map(y => `<circle cx="800" cy="${y}" r="38" fill="#111827" stroke="#06b6d4" stroke-width="2"/><g transform="translate(800 ${y})">${blade(7, '#06b6d4', .8)}<circle r="6" fill="#0f172a"/></g>`).join('')}<circle cx="150" cy="110" r="24" fill="#111827" stroke="#06b6d4" stroke-width="2"/><g transform="translate(150 110)">${blade(7, '#06b6d4', .5)}<circle r="4" fill="#0f172a"/></g><rect x="184" y="292" width="22" height="8" rx="2" fill="#f8fafc"/><rect x="590" y="360" width="8" height="22" rx="2" fill="#f8fafc"/></g>
<g class="inst inst-inspection"><rect x="640" y="225" width="190" height="60" rx="8" fill="#14532d" stroke="#22c55e" stroke-width="2"/><path d="M660 256l14 14 26-30" fill="none" stroke="#bbf7d0" stroke-width="6" stroke-linecap="round"/><text x="712" y="260" font-size="13" font-weight="700" fill="#dcfce7">All checks passed</text></g>
<g class="inst inst-io_ports"><g fill="none" stroke="#94a3b8" stroke-width="2"><path d="M200 150H104V122"/><path d="M200 172H104V192"/><path d="M200 184H70V216"/></g><rect x="10" y="86" width="92" height="62" rx="4" fill="#0f172a" stroke="#94a3b8" stroke-width="3"/><rect x="42" y="150" width="28" height="6" fill="#94a3b8"/><rect x="12" y="192" width="92" height="18" rx="3" fill="#475569" stroke="#cbd5e1"/><ellipse cx="68" cy="228" rx="10" ry="14" fill="#cbd5e1" stroke="#475569"/></g>
<g class="inst inst-boot"><circle cx="830" cy="50" r="22" fill="#22c55e" opacity=".28"/><rect x="14" y="90" width="84" height="54" fill="#0e7490"/><text x="56" y="122" text-anchor="middle" font-size="13" font-weight="700" fill="#ecfeff">POST OK</text></g>
<g class="side-panel"><rect x="124" y="24" width="732" height="512" rx="8" fill="#64748b" opacity=".95"/><text x="490" y="285" text-anchor="middle" font-size="24" font-weight="700" fill="#e2e8f0">SIDE PANEL INSTALLED</text><text x="490" y="315" text-anchor="middle" font-size="14" fill="#cbd5e1">The case interior is not accessible yet.</text>${[[150, 50], [830, 50], [150, 510], [830, 510]].map(([x, y]) => `<circle cx="${x}" cy="${y}" r="7" fill="#94a3b8" stroke="#334155"/>`).join('')}</g>
</svg>`;

  /* ================= Drop zones (positions in each panel's viewBox) ================= */
  const P = { bench: { w: 480, h: 560 }, case: { w: 880, h: 560 } };
  const ZONES = [ // key, panel, x, y, w, h, label, z
    ['workspace', 'bench', 20, 14, 270, 112, 'Workspace', 2], ['esd', 'bench', 300, 14, 160, 112, 'ESD protection', 2],
    ['cpu_socket', 'bench', 154, 236, 114, 114, 'CPU socket', 2], ['thermal', 'bench', 173, 255, 76, 76, 'CPU surface', 3], ['cpu_cooler', 'bench', 139, 221, 144, 144, 'Cooler mount', 4], ['ram_slots', 'bench', 296, 194, 76, 186, 'RAM slots', 2],
    ['case', 'case', 124, 24, 732, 512, 'Computer case', 1], ['mb_tray', 'case', 190, 50, 400, 400, 'Motherboard tray', 2],
    ['io_ports', 'case', 0, 80, 240, 170, 'Rear I/O + peripherals', 4], ['pcie_slot', 'case', 236, 288, 230, 36, 'PCIe x16', 6], ['atx_power', 'case', 540, 96, 44, 114, 'ATX / EPS power', 6],
    ['sata_ports', 'case', 508, 348, 70, 60, 'SATA ports', 6], ['front_panel', 'case', 410, 414, 90, 34, 'F_PANEL header', 6],
    ['psu_bay', 'case', 130, 462, 330, 66, 'PSU bay', 3], ['drive_bay', 'case', 630, 60, 170, 150, 'Drive bay', 3], ['fan_mounts', 'case', 750, 300, 106, 230, 'Fan mounts', 3],
    ['inspection', 'case', 640, 225, 190, 60, 'Inspection', 3], ['boot', 'case', 806, 26, 48, 48, 'Power button', 7]
  ];
  // A zone only accepts drops once its physical prerequisite is done (you cannot reach inside a closed case, etc.)
  const PREREQ = { thermal: 'cpu_socket', cpu_cooler: 'thermal', mb_tray: 'case', psu_bay: 'case', drive_bay: 'case', fan_mounts: 'case', inspection: 'case', io_ports: 'mb_tray', pcie_slot: 'mb_tray', atx_power: 'mb_tray', sata_ports: 'mb_tray', front_panel: 'mb_tray' };

  const $ = id => document.getElementById(id);
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  let attemptId = null, state = null, selected = null, busy = false; const filled = new Set();

  async function api(action, extra = {}) {
    const fd = new FormData(); fd.append('action', action); if (attemptId) fd.append('attempt_id', attemptId); Object.entries(extra).forEach(([k, v]) => fd.append(k, v));
    const r = await fetch(APP.base + '/api/assembly.php', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF-Token': APP.csrf } });
    const j = await r.json().catch(() => ({ error: 'Server error' })); if (!r.ok && !j.error) j.error = 'Request failed'; return j;
  }

  function buildStage() {
    const st = $('stage'); st.classList.add(window.ASM.mode === 'practice' ? 'practice' : 'assess');
    const panel = (name, title, svg) => `<div class="panel"><div class="panel-title">${title}</div><div class="panel-inner" style="aspect-ratio:${P[name].w}/${P[name].h}">${svg}<div class="zones" data-panel="${name}"></div></div></div>`;
    st.innerHTML = panel('bench', '<i class="fa fa-table"></i> Workbench', BENCH_SVG) + panel('case', '<i class="fa fa-computer"></i> Computer case', CASE_SVG);
    ZONES.forEach(([k, p, x, y, w, h, label, z]) => {
      const d = document.createElement('div'); d.className = 'zone'; d.dataset.zone = k; d.title = window.ASM.mode === 'practice' ? label : '';
      d.style.cssText = `left:${x / P[p].w * 100}%;top:${y / P[p].h * 100}%;width:${w / P[p].w * 100}%;height:${h / P[p].h * 100}%;z-index:${z}`;
      d.innerHTML = `<span class="zl">${esc(label)}</span>`; st.querySelector(`.zones[data-panel="${p}"]`).appendChild(d);
    });
    document.querySelectorAll('.zone').forEach(z => {
      z.addEventListener('dragover', e => { e.preventDefault(); z.classList.add('over'); });
      z.addEventListener('dragleave', () => z.classList.remove('over'));
      z.addEventListener('drop', e => { e.preventDefault(); z.classList.remove('over'); st.classList.remove('dragging'); drop(e.dataTransfer.getData('text/plain'), z); });
      z.addEventListener('click', () => { if (selected) drop(selected, z); });
    });
  }
  function mark(zone) {
    filled.add(zone); document.querySelectorAll('.inst-' + zone).forEach(e => e.classList.add('on'));
    const st = $('stage'); if (zone === 'case') st.classList.add('case-open'); if (zone === 'mb_tray') st.classList.add('mb-in'); if (zone === 'boot') st.classList.add('booted');
  }
  function refreshZones() {
    document.querySelectorAll('.zone').forEach(z => {
      const k = z.dataset.zone, done = filled.has(k), pre = PREREQ[k], ok = !done && (!pre || filled.has(pre));
      z.classList.toggle('filled', done); z.classList.toggle('active', ok); z.style.pointerEvents = ok ? 'auto' : 'none';
    });
  }
  function resetStage() { filled.clear(); document.querySelectorAll('.inst.on').forEach(e => e.classList.remove('on')); $('stage').classList.remove('case-open', 'mb-in', 'booted'); }

  function render() {
    $('stepTotal').textContent = state.total; $('stepNo').textContent = Math.min(state.current, state.total); $('pbar').style.width = ((state.current - 1) / state.total * 100) + '%';
    $('stepList').innerHTML = state.steps.map(s => `<li class="${s.no < state.current ? 'text-success' : s.no === state.current ? 'fw-bold' : 'text-muted'}">${esc(s.title)}</li>`).join('');
    resetStage(); state.placed.forEach(p => mark(p.zone)); refreshZones();
    $('hintBox').classList.add('d-none'); selected = null;
    $('stepTitle').textContent = state.step.title; $('stepIns').textContent = state.step.instruction; $('stepType').textContent = state.step.type;
    $('tray').innerHTML = state.components.map(c => `<div class="comp" draggable="true" data-id="${c.id}"><i class="fa ${esc(c.icon || 'fa-cube')}"></i><span>${esc(c.label)}</span></div>`).join('');
    document.querySelectorAll('.comp').forEach(el => {
      el.addEventListener('dragstart', e => { e.dataTransfer.setData('text/plain', el.dataset.id); el.classList.add('dragging'); $('stage').classList.add('dragging'); });
      el.addEventListener('dragend', () => { el.classList.remove('dragging'); $('stage').classList.remove('dragging'); });
      el.addEventListener('click', () => { document.querySelectorAll('.comp').forEach(c => c.classList.remove('sel')); selected = el.dataset.id; el.classList.add('sel'); $('stage').classList.add('dragging'); });
    });
  }
  async function drop(compId, zoneEl) {
    if (busy || !compId) return; busy = true;
    const j = await api('drop', { component_id: compId, zone: zoneEl.dataset.zone }); busy = false; $('stage').classList.remove('dragging');
    if (j.error) { Swal.fire('Error', j.error, 'error'); return; }
    if (!j.correct) { zoneEl.classList.add('bad'); setTimeout(() => zoneEl.classList.remove('bad'), 700); $('feedback').innerHTML = `<div class="alert alert-danger py-2 small mb-0"><i class="fa fa-circle-xmark"></i> ${esc(j.message)}</div>`; return; }
    mark(j.zone); refreshZones(); selected = null;
    $('feedback').innerHTML = `<div class="alert alert-success py-2 small mb-0"><i class="fa fa-circle-check"></i> ${esc(j.message)}</div>`;
    if (j.finished) return finish(j);
    setTimeout(load, window.ASM.mode === 'practice' ? 1600 : 700);
  }
  function finish(j) {
    Swal.fire({ icon: j.passed ? 'success' : 'info', title: 'Assembly complete!', html: `Your score: <b>${j.percentage}%</b><br>${j.passed ? 'You passed.' : 'You did not reach the passing score.'}` + (j.certificate_id ? '<br><b>Your certificate has been issued!</b>' : (window.ASM.mode === 'practice' && j.passed ? '<br>Take the Assessment to earn a certificate.' : '')),
      confirmButtonText: 'View results', allowOutsideClick: false }).then(() => { location.href = window.ASM.resultUrl + attemptId; });
  }
  async function load() {
    state = await api('state'); if (state.error) { Swal.fire('Error', state.error, 'error'); return; }
    if (state.status === 'completed') { location.href = window.ASM.resultUrl + attemptId; return; } render();
  }
  async function init() {
    const s = await api('start', { module_id: window.ASM.module, mode: window.ASM.mode });
    if (s.error) { Swal.fire('Error', s.error, 'error').then(() => history.back()); return; } attemptId = s.attempt_id; load();
  }
  buildStage();
  $('hintBtn')?.addEventListener('click', async () => { const j = await api('hint'); const b = $('hintBox'); b.textContent = j.hint || j.error; b.classList.remove('d-none'); });
  $('restartBtn').addEventListener('click', () => Swal.fire({ title: 'Restart this attempt?', text: 'Your progress in this attempt will be discarded.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#DC2626', confirmButtonText: 'Restart' })
    .then(async r => { if (r.isConfirmed) { await api('restart'); attemptId = null; resetStage(); init(); } }));
  init();
})();
