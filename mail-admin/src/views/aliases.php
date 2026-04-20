<?php /** @var array $aliases */ /** @var array $accounts */ ?>
<?php ob_start(); ?>
<div class="card">
  <h2>Crear alias</h2>
  <p class="muted">Un alias reenvía todos los correos de una dirección a otra cuenta existente.</p>
  <form method="post" action="/?page=aliases_create">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <div class="form-row">
      <div>
        <label>Alias (origen)</label>
        <input type="email" name="source" placeholder="ventas@<?= e(env('DEFAULT_DOMAIN', '')) ?>" required>
      </div>
      <div>
        <label>Destino</label>
        <input type="email" name="target" list="account-list" placeholder="usuario@<?= e(env('DEFAULT_DOMAIN', '')) ?>" required>
        <datalist id="account-list">
          <?php foreach ($accounts as $a): ?>
            <option value="<?= e($a['email']) ?>"></option>
          <?php endforeach; ?>
        </datalist>
      </div>
      <div></div>
      <div></div>
      <div class="actions">
        <button class="btn btn-primary" type="submit">Crear alias</button>
      </div>
    </div>
  </form>
</div>

<div class="card">
  <h2>Alias existentes (<?= count($aliases) ?>)</h2>
  <?php if (empty($aliases)): ?>
    <div class="empty">No hay alias configurados.</div>
  <?php else: ?>
    <table>
      <thead><tr><th>Origen</th><th>Destino</th><th style="text-align:right">Acciones</th></tr></thead>
      <tbody>
      <?php foreach ($aliases as $a): ?>
        <tr>
          <td><?= e($a['source']) ?></td>
          <td><?= e($a['target']) ?></td>
          <td style="text-align:right">
            <form class="inline-form" method="post" action="/?page=aliases_delete" onsubmit="return confirm('¿Eliminar alias?')">
              <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="source" value="<?= e($a['source']) ?>">
              <input type="hidden" name="target" value="<?= e($a['target']) ?>">
              <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php $body = ob_get_clean(); $title = 'Alias'; $page = 'aliases'; include __DIR__ . '/layout.php'; ?>
