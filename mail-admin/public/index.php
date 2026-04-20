<?php
declare(strict_types=1);

require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/MailManager.php';

start_session();

$accountsFile = env('ACCOUNTS_FILE', '/mailconfig/postfix-accounts.cf');
$aliasesFile  = env('ALIASES_FILE',  '/mailconfig/postfix-virtual.cf');
$quotaFile    = env('QUOTA_FILE',    '/mailconfig/dovecot-quotas.cf');

$adminUser = env('ADMIN_USER', 'admin');
$adminPass = env('ADMIN_PASSWORD', 'admin');

$manager = new MailManager($accountsFile, $aliasesFile, $quotaFile);

$page = $_GET['page'] ?? ($_SESSION['auth'] ?? false ? 'accounts' : 'login');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    switch ($page) {
        case 'login':
            if ($method === 'POST') {
                csrf_check();
                $u = $_POST['username'] ?? '';
                $p = $_POST['password'] ?? '';
                if (hash_equals($adminUser, (string)$u) && hash_equals($adminPass, (string)$p)) {
                    session_regenerate_id(true);
                    $_SESSION['auth'] = true;
                    flash('ok', 'Bienvenido.');
                    redirect('/?page=accounts');
                }
                flash('error', 'Usuario o contraseña incorrectos.');
                redirect('/?page=login');
            }
            include __DIR__ . '/../src/views/login.php';
            break;

        case 'logout':
            $_SESSION = [];
            session_destroy();
            redirect('/?page=login');
            break;

        case 'accounts':
            require_auth();
            $accounts = $manager->listAccounts();
            include __DIR__ . '/../src/views/accounts.php';
            break;

        case 'accounts_create':
            require_auth();
            if ($method !== 'POST') redirect('/?page=accounts');
            csrf_check();
            $local   = trim($_POST['local'] ?? '');
            $domain  = trim($_POST['domain'] ?? '');
            $password= (string)($_POST['password'] ?? '');
            $quota   = trim($_POST['quota'] ?? '');
            $manager->addAccount("$local@$domain", $password, $quota === '' ? null : $quota);
            flash('ok', "Cuenta $local@$domain creada.");
            redirect('/?page=accounts');
            break;

        case 'accounts_password':
            require_auth();
            if ($method !== 'POST') redirect('/?page=accounts');
            csrf_check();
            $manager->updatePassword((string)$_POST['email'], (string)$_POST['password']);
            flash('ok', 'Contraseña actualizada.');
            redirect('/?page=accounts');
            break;

        case 'accounts_quota':
            require_auth();
            if ($method !== 'POST') redirect('/?page=accounts');
            csrf_check();
            $email = (string)$_POST['email'];
            $q     = trim((string)$_POST['quota']);
            if ($q === '' || $q === '0') {
                $manager->removeQuota($email);
                flash('ok', "Cuota eliminada para $email.");
            } else {
                $manager->setQuota($email, (int)$q);
                flash('ok', "Cuota actualizada para $email.");
            }
            redirect('/?page=accounts');
            break;

        case 'accounts_delete':
            require_auth();
            if ($method !== 'POST') redirect('/?page=accounts');
            csrf_check();
            $manager->deleteAccount((string)$_POST['email']);
            flash('ok', 'Cuenta eliminada.');
            redirect('/?page=accounts');
            break;

        case 'aliases':
            require_auth();
            $aliases  = $manager->listAliases();
            $accounts = $manager->listAccounts();
            include __DIR__ . '/../src/views/aliases.php';
            break;

        case 'aliases_create':
            require_auth();
            if ($method !== 'POST') redirect('/?page=aliases');
            csrf_check();
            $manager->addAlias((string)$_POST['source'], (string)$_POST['target']);
            flash('ok', 'Alias creado.');
            redirect('/?page=aliases');
            break;

        case 'aliases_delete':
            require_auth();
            if ($method !== 'POST') redirect('/?page=aliases');
            csrf_check();
            $manager->deleteAlias((string)$_POST['source'], (string)$_POST['target']);
            flash('ok', 'Alias eliminado.');
            redirect('/?page=aliases');
            break;

        default:
            http_response_code(404);
            echo 'No encontrado.';
    }
} catch (Throwable $e) {
    flash('error', $e->getMessage());
    redirect('/?page=' . ($page === 'login' ? 'login' : (str_starts_with($page, 'aliases') ? 'aliases' : 'accounts')));
}
