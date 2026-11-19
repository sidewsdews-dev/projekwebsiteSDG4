/* Login DEMO: pilihan peran hanya untuk memperlihatkan tampilan.
   Login sungguhan harus dicek di server, bukan di JavaScript. */
var form = document.getElementById('form-login');
function go(role) { window.location.href = 'dashboard-' + role + '.html'; }
if (form) {
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var u = document.getElementById('username');
    var p = document.getElementById('password');
    var pesan = document.getElementById('pesan');
    if (!u.value.trim() || !p.value) {
      pesan.textContent = 'Isi username dan kata sandi.';
      (u.value.trim() ? p : u).focus();
      return;
    }
    go(document.getElementById('peran').value);
  });
}
