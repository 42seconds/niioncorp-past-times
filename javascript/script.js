

// ─── NAVIGATION ───
function navigate(page) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  const target = document.getElementById('page-' + page);
  if (target) {
    target.classList.add('active');
    window.scrollTo(0, 0);
  }
}

// ─── HELPERS ───
function togglePw(id, btn) {
  const input = document.getElementById(id);
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = '🙈';
  } else {
    input.type = 'password';
    btn.textContent = '👁';
  }
}

function toggleSize(el) {
  document.querySelectorAll('.size-pill').forEach(p => p.classList.remove('active'));
  el.classList.add('active');
}

function filterCategory(el) {
  document.querySelectorAll('.category-chip').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
}

function switchTab(el) {
  document.querySelectorAll('.order-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
}

function switchSort(el) {
  document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
}

function switchListTab(el) {
  document.querySelectorAll('.listings-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
}

function switchSettingsNav(el) {
  document.querySelectorAll('.settings-nav-link').forEach(l => l.classList.remove('active'));
  el.classList.add('active');
}

function switchSettingsSection(el, sectionId) {
  // Update nav highlight
  document.querySelectorAll('.settings-nav-link').forEach(l => l.classList.remove('active'));
  el.classList.add('active');
  // Show/hide sections
  ['s-profile','s-delivery','s-payment','s-notifications'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = (id === sectionId) ? 'block' : 'none';
  });
}

function toggleSwitch(el) {
  el.classList.toggle('on');
}

function selectListing(el) {
  document.querySelectorAll('.table-row').forEach(r => r.classList.remove('selected'));
  el.classList.add('selected');
}

function toggleDelivery(el) {
  el.classList.toggle('checked');
  const cb = el.querySelector('.checkbox-custom');
  cb.textContent = el.classList.contains('checked') ? '✓' : '';
}

function sendMsg() {
  const input = document.getElementById('chat-input-field');
  const text = input.value.trim();
  if (!text) return;
  const msgs = document.querySelector('.chat-messages');
  const row = document.createElement('div');
  row.className = 'msg-row sent';
  row.innerHTML = `<div class="msg-avatar">Me</div><div><div class="msg-bubble sent">${text}</div><div class="msg-time" style="text-align:left;">Just now</div></div>`;
  msgs.appendChild(row);
  input.value = '';
  msgs.scrollTop = msgs.scrollHeight;
}

// Send message on Enter
document.addEventListener('DOMContentLoaded', () => {
  const chatInput = document.getElementById('chat-input-field');
  if (chatInput) {
    chatInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') sendMsg();
    });
  }
});
