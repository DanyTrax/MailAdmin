<?php

class MailManager
{
    private string $accountsFile;
    private string $aliasesFile;
    private string $quotaFile;

    public function __construct(string $accountsFile, string $aliasesFile, string $quotaFile)
    {
        $this->accountsFile = $accountsFile;
        $this->aliasesFile  = $aliasesFile;
        $this->quotaFile    = $quotaFile;

        foreach ([$this->accountsFile, $this->aliasesFile, $this->quotaFile] as $f) {
            if (!file_exists($f)) {
                @mkdir(dirname($f), 0755, true);
                @touch($f);
                @chmod($f, 0644);
            }
        }
    }

    /** @return array<int,array{email:string,domain:string,local:string,hash:string,quota:?string}> */
    public function listAccounts(): array
    {
        $accounts = [];
        $quotas = $this->readQuotas();
        $lines = @file($this->accountsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = explode('|', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$email, $hash] = $parts;
            [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
            $accounts[] = [
                'email'  => $email,
                'domain' => $domain,
                'local'  => $local,
                'hash'   => $hash,
                'quota'  => $quotas[$email] ?? null,
            ];
        }

        usort($accounts, fn($a, $b) => strcmp($a['email'], $b['email']));
        return $accounts;
    }

    public function accountExists(string $email): bool
    {
        foreach ($this->listAccounts() as $acc) {
            if (strcasecmp($acc['email'], $email) === 0) {
                return true;
            }
        }
        return false;
    }

    public function addAccount(string $email, string $password, ?string $quotaMb = null): void
    {
        $email = strtolower(trim($email));
        $this->validateEmail($email);
        $this->validatePassword($password);

        if ($this->accountExists($email)) {
            throw new RuntimeException("La cuenta $email ya existe.");
        }

        $hash = $this->hashPassword($password);
        $this->appendLine($this->accountsFile, "$email|$hash");

        if ($quotaMb !== null && $quotaMb !== '') {
            $this->setQuota($email, (int)$quotaMb);
        }
    }

    public function updatePassword(string $email, string $password): void
    {
        $this->validatePassword($password);
        $email = strtolower(trim($email));
        $hash = $this->hashPassword($password);

        $found = false;
        $lines = @file($this->accountsFile, FILE_IGNORE_NEW_LINES) ?: [];
        foreach ($lines as $i => $line) {
            if ($line === '' || str_starts_with(trim($line), '#')) {
                continue;
            }
            $parts = explode('|', $line, 2);
            if (count($parts) === 2 && strcasecmp($parts[0], $email) === 0) {
                $lines[$i] = "$email|$hash";
                $found = true;
            }
        }
        if (!$found) {
            throw new RuntimeException("La cuenta $email no existe.");
        }
        $this->writeFile($this->accountsFile, implode("\n", $lines) . "\n");
    }

    public function deleteAccount(string $email): void
    {
        $email = strtolower(trim($email));
        $lines = @file($this->accountsFile, FILE_IGNORE_NEW_LINES) ?: [];
        $out = [];
        $removed = false;
        foreach ($lines as $line) {
            $parts = explode('|', $line, 2);
            if (count($parts) === 2 && strcasecmp($parts[0], $email) === 0) {
                $removed = true;
                continue;
            }
            $out[] = $line;
        }
        if (!$removed) {
            throw new RuntimeException("La cuenta $email no existe.");
        }
        $this->writeFile($this->accountsFile, implode("\n", $out) . "\n");
        $this->removeQuota($email);
    }

    public function setQuota(string $email, int $quotaMb): void
    {
        $email = strtolower(trim($email));
        if ($quotaMb < 0) {
            throw new RuntimeException("La cuota no puede ser negativa.");
        }
        $lines = @file($this->quotaFile, FILE_IGNORE_NEW_LINES) ?: [];
        $out = [];
        $found = false;
        foreach ($lines as $line) {
            if ($line === '') continue;
            $parts = explode(':', $line, 2);
            if (strcasecmp($parts[0] ?? '', $email) === 0) {
                $out[] = "$email:{$quotaMb}M";
                $found = true;
            } else {
                $out[] = $line;
            }
        }
        if (!$found) {
            $out[] = "$email:{$quotaMb}M";
        }
        $this->writeFile($this->quotaFile, implode("\n", $out) . "\n");
    }

    public function removeQuota(string $email): void
    {
        $lines = @file($this->quotaFile, FILE_IGNORE_NEW_LINES) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (strcasecmp($parts[0] ?? '', $email) !== 0) {
                $out[] = $line;
            }
        }
        $this->writeFile($this->quotaFile, implode("\n", $out) . "\n");
    }

    /** @return array<string,string> */
    private function readQuotas(): array
    {
        $q = [];
        $lines = @file($this->quotaFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $q[strtolower($parts[0])] = $parts[1];
            }
        }
        return $q;
    }

    /** @return array<int,array{source:string,target:string}> */
    public function listAliases(): array
    {
        $aliases = [];
        $lines = @file($this->aliasesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = preg_split('/\s+/', $line, 2);
            if (count($parts) === 2) {
                $aliases[] = ['source' => $parts[0], 'target' => $parts[1]];
            }
        }
        usort($aliases, fn($a, $b) => strcmp($a['source'], $b['source']));
        return $aliases;
    }

    public function addAlias(string $source, string $target): void
    {
        $source = strtolower(trim($source));
        $target = strtolower(trim($target));
        $this->validateEmail($source);
        $this->validateEmail($target);

        foreach ($this->listAliases() as $a) {
            if (strcasecmp($a['source'], $source) === 0 && strcasecmp($a['target'], $target) === 0) {
                throw new RuntimeException("El alias $source -> $target ya existe.");
            }
        }
        $this->appendLine($this->aliasesFile, "$source $target");
    }

    public function deleteAlias(string $source, string $target): void
    {
        $source = strtolower(trim($source));
        $target = strtolower(trim($target));
        $lines = @file($this->aliasesFile, FILE_IGNORE_NEW_LINES) ?: [];
        $out = [];
        $removed = false;
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', trim($line), 2);
            if (count($parts) === 2 && strcasecmp($parts[0], $source) === 0 && strcasecmp($parts[1], $target) === 0) {
                $removed = true;
                continue;
            }
            $out[] = $line;
        }
        if (!$removed) {
            throw new RuntimeException("El alias no existe.");
        }
        $this->writeFile($this->aliasesFile, implode("\n", $out) . "\n");
    }

    private function hashPassword(string $password): string
    {
        $salt = '$6$' . bin2hex(random_bytes(8));
        $hash = crypt($password, $salt);
        if (!is_string($hash) || strlen($hash) < 20) {
            throw new RuntimeException("No se pudo generar el hash de la contraseña.");
        }
        return '{SHA512-CRYPT}' . $hash;
    }

    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Correo inválido: $email");
        }
    }

    private function validatePassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new RuntimeException("La contraseña debe tener al menos 8 caracteres.");
        }
    }

    private function appendLine(string $file, string $line): void
    {
        $fh = fopen($file, 'a');
        if (!$fh) {
            throw new RuntimeException("No se pudo abrir $file");
        }
        if (!flock($fh, LOCK_EX)) {
            fclose($fh);
            throw new RuntimeException("No se pudo bloquear $file");
        }
        fwrite($fh, $line . "\n");
        flock($fh, LOCK_UN);
        fclose($fh);
    }

    private function writeFile(string $file, string $content): void
    {
        $tmp = $file . '.tmp';
        if (file_put_contents($tmp, $content, LOCK_EX) === false) {
            throw new RuntimeException("No se pudo escribir $file");
        }
        if (!rename($tmp, $file)) {
            throw new RuntimeException("No se pudo reemplazar $file");
        }
    }
}
