<?php /** @var string $title */ /** @var string $body */ ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? 'Mail Admin') ?></title>
<style>
:root{--bg:#0f172a;--card:#1e293b;--muted:#94a3b8;--fg:#e2e8f0;--accent:#3b82f6;--danger:#ef4444;--ok:#10b981;--warn:#f59e0b;--border:#334155}
*{box-sizing:border-box}
body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:var(--bg);color:var(--fg)}
a{color:var(--accent);text-decoration:none}
a:hover{text-decoration:underline}
header{background:#111827;border-bottom:1px solid var(--border);padding:12px 24px;display:flex;align-items:center;justify-content:space-between}
header h1{font-size:18px;margin:0;font-weight:600}
header nav a{margin-left:16px;color:var(--muted)}
header nav a.active,header nav a:hover{color:var(--fg)}
main{max-width:1100px;margin:24px auto;padding:0 16px}
.card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:20px;margin-bottom:20px}
.card h2{margin:0 0 16px 0;font-size:16px;font-weight:600}
table{width:100%;border-collapse:collapse}
th,td{padding:10px 12px;text-align:left;border-bottom:1px solid var(--border);font-size:14px}
th{color:var(--muted);font-weight:500;text-transform:uppercase;font-size:11px;letter-spacing:.5px}
tr:hover td{background:rgba(255,255,255,.03)}
.btn{display:inline-block;padding:8px 14px;border-radius:6px;border:1px solid var(--border);background:#334155;color:var(--fg);cursor:pointer;font-size:14px;font-family:inherit}
.btn:hover{background:#475569}
.btn-primary{background:var(--accent);border-color:var(--accent)}
.btn-primary:hover{background:#2563eb}
.btn-danger{background:var(--danger);border-color:var(--danger)}
.btn-danger:hover{background:#dc2626}
.btn-sm{padding:5px 10px;font-size:12px}
input,select{width:100%;padding:9px 12px;border-radius:6px;border:1px solid var(--border);background:#0f172a;color:var(--fg);font-size:14px;font-family:inherit}
input:focus,select:focus{outline:none;border-color:var(--accent)}
label{display:block;margin-bottom:6px;font-size:13px;color:var(--muted)}
.form-row{display:grid;gap:12px;grid-template-columns:1fr 1fr 140px 140px auto;align-items:end}
.form-row .actions{display:flex;gap:8px}
.flash{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px}
.flash.ok{background:rgba(16,185,129,.15);border:1px solid var(--ok);color:#6ee7b7}
.flash.error{background:rgba(239,68,68,.15);border:1px solid var(--danger);color:#fca5a5}
.flash.info{background:rgba(59,130,246,.15);border:1px solid var(--accent);color:#93c5fd}
.muted{color:var(--muted);font-size:12px}
.inline-form{display:inline}
.empty{text-align:center;padding:40px;color:var(--muted)}
.domain{color:var(--muted)}
.badge{display:inline-block;padding:2px 8px;border-radius:4px;background:#334155;font-size:11px;color:var(--muted)}
@media (max-width:768px){.form-row{grid-template-columns:1fr}}
</style>
</head>
<body>
<header>
  <h1>Panel de Correo · <?= e(env('DEFAULT_DOMAIN', '')) ?></h1>
  <nav>
    <?php if (!empty($_SESSION['auth'])): ?>
      <a href="/?page=accounts" class="<?= ($page ?? '') === 'accounts' ? 'active' : '' ?>">Cuentas</a>
      <a href="/?page=aliases"  class="<?= ($page ?? '') === 'aliases'  ? 'active' : '' ?>">Alias</a>
      <a href="/?page=logout">Salir</a>
    <?php endif; ?>
  </nav>
</header>
<main>
  <?php foreach (flash_pull() as $f): ?>
    <div class="flash <?= e($f['type']) ?>"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
  <?= $body ?>
</main>
</body>
</html>
