# MailAdmin

Panel web (CRUD) en PHP para administrar cuentas y alias de
[`docker-mailserver`](https://github.com/docker-mailserver/docker-mailserver)
sin tener que entrar por SSH.

- Login con usuario/clave y protección CSRF
- Alta, listado, eliminación y cambio de contraseña de buzones
- Asignación/retiro de cuota por cuenta
- Alta, listado y eliminación de alias (reenvíos)
- Escribe directamente los archivos `postfix-accounts.cf`, `postfix-virtual.cf` y `dovecot-quotas.cf` → `docker-mailserver` recarga solo, sin reinicio
- Contraseñas almacenadas como `{SHA512-CRYPT}` (compatible con Dovecot)
- UI oscura, responsive, en español

![stack](https://img.shields.io/badge/PHP-8.2-777bb4?logo=php)
![stack](https://img.shields.io/badge/Docker-ready-2496ed?logo=docker)

---

## Estructura del repositorio

```
.
├── docker-compose.yml          # Stack completo (mailserver + roundcube + mail-admin)
├── .env.example                # Variables para el stack completo
└── mail-admin/
    ├── Dockerfile              # Imagen del panel
    ├── docker-compose.yml      # Despliegue STANDALONE del panel
    ├── .env.example            # Variables para uso standalone
    ├── public/index.php        # Router
    └── src/                    # Lógica + vistas
```

---

## Opción A · Desplegar solo el panel (recomendado si ya tienes docker-mailserver)

```bash
git clone https://github.com/DanyTrax/MailAdmin.git
cd MailAdmin/mail-admin
cp .env.example .env
# Edita .env y ajusta ADMIN_PASSWORD, DEFAULT_DOMAIN y MAILCONFIG_PATH
docker compose up -d --build
```

`MAILCONFIG_PATH` debe apuntar al directorio del host que ya tienes montado en
el contenedor `docker-mailserver` como `/tmp/docker-mailserver` (en el
`docker-compose.yml` original suele ser `./mailconfig`).

Ejemplo de `.env`:

```env
ADMIN_USER=admin
ADMIN_PASSWORD=MiClaveSuperSegura.2026
DEFAULT_DOMAIN=adpublicidad.co
MAIL_ADMIN_PORT=8889
MAILCONFIG_PATH=/opt/mail/mailconfig
```

Accede en `http://TU_SERVIDOR:8889`.

---

## Opción B · Stack completo (mailserver + roundcube + imapsync + panel)

```bash
git clone https://github.com/DanyTrax/MailAdmin.git
cd MailAdmin
cp .env.example .env
# Edita .env
docker network create red_sql_global 2>/dev/null || true
docker compose up -d --build
```

Servicios expuestos:

| Servicio     | Puerto        | URL                             |
|--------------|---------------|---------------------------------|
| `mailserver` | 25, 143, 587  | SMTP/IMAP                       |
| `roundcube`  | 8888          | `http://SERVIDOR:8888` (webmail) |
| `mail-admin` | 8889          | `http://SERVIDOR:8889` (panel)  |

> Antes de publicar tu propio `docker-compose.yml`, reemplaza las contraseñas
> de base de datos por variables del `.env`.

---

## Cómo funciona internamente

El panel monta el mismo volumen que `docker-mailserver` usa para su
configuración, y edita los archivos en los que se definen las cuentas:

- `postfix-accounts.cf` — `usuario@dominio|{SHA512-CRYPT}$6$salt$hash`
- `postfix-virtual.cf`  — `alias@dominio destino@dominio`
- `dovecot-quotas.cf`   — `usuario@dominio:500M`

`docker-mailserver` vigila estos archivos con change-detection y aplica los
cambios automáticamente — no necesita reiniciarse.

Las contraseñas se hashean con `crypt($password, '$6$' . salt_aleatoria_16_hex)`
y se prefija `{SHA512-CRYPT}`, exactamente el formato que espera Dovecot.

---

## Seguridad

- Cambia **siempre** `ADMIN_PASSWORD` antes del primer arranque.
- Restringe el acceso al puerto `8889` por firewall, o publícalo detrás de un
  reverse proxy (nginx/traefik) con HTTPS y autenticación adicional.
- Al eliminar una cuenta se borra del `postfix-accounts.cf` pero **no** se
  elimina el buzón físico en `maildata/`. Hazlo a mano si necesitas liberar
  espacio.
- Las sesiones del panel son cookies `HttpOnly` + `SameSite=Lax` y todos los
  formularios llevan token CSRF.

---

## Build manual de la imagen (sin compose)

```bash
cd mail-admin
docker build -t mailadmin:latest .

docker run -d \
  --name mail-admin \
  -p 8889:80 \
  -e ADMIN_USER=admin \
  -e ADMIN_PASSWORD='MiClaveSuperSegura.2026' \
  -e DEFAULT_DOMAIN=adpublicidad.co \
  -v /opt/mail/mailconfig:/mailconfig \
  --restart always \
  mailadmin:latest
```

---

## Licencia

MIT
