// SweetAlert2 confirm wrapper (Rule 38). Falls back to native confirm() if the CDN is blocked.
window.confirmThen = function (text, onYes, labels) {
  labels = labels || {};
  if (!window.Swal) { if (confirm(text)) onYes(); return; }
  Swal.fire({
    title: labels.title || '', text: text, icon: 'warning', showCancelButton: true,
    confirmButtonText: labels.yes || 'OK', cancelButtonText: labels.no || 'Cancel',
    confirmButtonColor: '#0F766E', reverseButtons: document.dir === 'rtl',
  }).then(function (r) { if (r.isConfirmed) onYes(); });
};

// Collapsible sidebar state is a pure UI preference -> localStorage (no PHI).
window.navState = function () {
  return {
    collapsed: localStorage.getItem('nav-collapsed') === '1', open: false,
    toggle() { if (window.innerWidth <= 900) { this.open = !this.open; } else { this.collapsed = !this.collapsed; localStorage.setItem('nav-collapsed', this.collapsed ? '1' : '0'); } },
  };
};

// Toast stack fed by Livewire `dispatch('toast', message: ...)`.
window.toasts = function () {
  return { list: [], push(m) { const id = Date.now() + Math.random(); this.list.push({ id, m }); setTimeout(() => { this.list = this.list.filter(t => t.id !== id); }, 3500); } };
};

/*
SYSTEM DESIGN:
Tiny UI helpers: confirm dialog, sidebar state, toast queue. No PHI is ever written to browser storage.

DATA STRUCTURE:
Toast queue = array (FIFO) with timed removal. Sidebar state = boolean in localStorage.
*/
