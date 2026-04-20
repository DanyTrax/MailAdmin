<?php /** @var array $accounts */ ?>
<?php ob_start(); ?>
<div class="card">
  <h2>Crear nueva cuenta</h2>
  <form method="post" action="/?page=accounts_create">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <div class="form-row">
      <div>
        <label>Usuario</label>
        <input type="text" name="local" placeholder="nombre" required pattern="[a-zA-Z0-9._-]+">
      </div>
      <div>
        <label>Dominio</label>
        <input type="text" name="domain" value="<?= e(env('DEFAULT_DOMAIN', '')) ?>" required>
      </div>
      <div>
        <label>Contraseña</label>
        <input type="text" name="password" minlength="8" required>
      </div>
      <div>
        <label>Cuota (MB)</label>
        <input type="number" name="quota" min="0" placeholder="opcional">
      </div>
      <div class="actions">
        <button class="btn btn-primary" type="submit">Crear</button>
      </div>
    </div>
    <p class="muted" style="margin-top:10px">La contraseña se almacena en SHA512-CRYPT, compatible con Dovecot.</p>
  </form>
</div>

<div class="card">
  <h2>Cuentas existentes (<?= count($accounts) ?>)</h2>
  <?php if (empty($accounts)): ?>
    <div class="empty">No hay cuentas creadas todavía.</div>
  <?php else: ?>
    <table>
      <thead>
        <tr><th>Correo</th><th>Cuota</th><th style="text-align:right">Acciones</th></tr>
      </thead>
      <tbody>
      <?php foreach ($accounts as $a): ?>
        <tr>
          <td>
            <strong><?= e($a['local']) ?></strong><span class="domain">@<?= e($a['domain']) ?></span>
          </td>
          <td>
            <?php if ($a['quota']): ?>
              <span class="badge"><?= e($a['quota']) ?></span>
            <?php else: ?>
              <span class="muted">sin límite</span>
            <?php endif; ?>
          </td>
          <td style="text-align:right">
            <form class="inline-form" method="post" action="/?page=accounts_password" onsubmit="return askPassword(this)">
              <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="email" value="<?= e($a['email']) ?>">
              <input type="hidden" name="password" value="">
              <button class="btn btn-sm" type="submit">Cambiar contraseña</button>
            </form>
            <form class="inline-form" method="post" action="/?page=accounts_quota" onsubmit="return askQuota(this)">
              <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="email" value="<?= e($a['email']) ?>">
              <input type="hidden" name="quota" value="">
              <button class="btn btn-sm" type="submit">Cuota</button>
            </form>
            <form class="inline-form" method="post" action="/?page=accounts_delete" onsubmit="return confirm('¿Eliminar la cuenta <?= e($a['email']) ?>? Esta acción no borra el buzón en disco.')">
              <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="email" value="<?= e($a['email']) ?>">
              <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<script>
function askPassword(form){
  const p = prompt('Nueva contraseña (mínimo 8 caracteres):');
  if (p === null) return false;
  if (p.length < 8) { alert('Mínimo 8 caracteres'); return false; }
  form.password.value = p;
  return true;
}
function askQuota(form){
  const q = prompt('Cuota en MB (0 o vacío para eliminar el límite):');
  if (q === null) return false;
  form.quota.value = q;
  return true;
}
</script>
<?php $body = ob_get_clean(); $title = 'Cuentas'; $page = 'accounts'; include __DIR__ . '/layout.php'; ?>
