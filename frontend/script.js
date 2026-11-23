function bukaTab(nama) {
  const tabs = document.querySelectorAll('.tabs a[data-tab]');
  const panels = document.querySelectorAll('.panel[data-panel]');
  if (!tabs.length || !panels.length) return;

  let ketemu = false;
  tabs.forEach(t => {
    const aktif = t.dataset.tab === nama;
    t.classList.toggle('active', aktif);
    if (aktif) ketemu = true;
  });

  if (!ketemu) {
    nama = tabs[0].dataset.tab;
    tabs[0].classList.add('active');
  }

  panels.forEach(p => {
    p.hidden = (p.dataset.panel !== nama);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const tabs = document.querySelectorAll('.tabs a[data-tab]');
  tabs.forEach(t => {
    t.addEventListener('click', (e) => {
      e.preventDefault();
      const target = t.dataset.tab;
      history.replaceState(null, '', '#' + target);
      bukaTab(target);
    });
  });

  const awal = location.hash ? location.hash.replace('#', '') : '';
  bukaTab(awal);
});
