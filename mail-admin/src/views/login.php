<?php ob_start(); ?>
<div class="card" style="max-width:420px;margin:40px auto">
  <h2>Iniciar sesión</h2>
  <form method="post" action="/?page=login">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <div style="margin-bottom:12px">
      <label>Usuario</label>
      <input type="text" name="username" autofocus required>
    </div>
    <div style="margin-bottom:16px">
      <label>Contraseña</label>
      <input type="password" name="password" required>
    </div>
    <button class="btn btn-primary" type="submit" style="width:100%">Entrar</button>
  </form>
</div>
<?php $body = ob_get_clean(); $title = 'Login'; include __DIR__ . '/layout.php'; ?>
