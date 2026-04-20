# MailAdmin

Panel web (CRUD) en PHP para administrar cuentas y alias de
[`docker-mailserver`](https://github.com/docker-mailserver/docker-mailserver)
sin tener que entrar por SSH.

- Login con usuario/clave y protección CSRF
- Alta, listado, eliminación y cambio de contraseña de buzones
- Asignación/retiro de cuota por cuenta
- Alta, listado y eliminación de alias (reenvíos)
- Escribe directamente `postfix-accounts.cf`, `postfix-virtual.cf` y `dovecot-quotas.cf`
- `docker-mailserver` recarga los cambios automáticamente (sin reinicio)
- Contraseñas en `{SHA512-CRYPT}` (compatible con Dovecot)
- UI oscura, responsive, en español

![stack](https://img.shields.io/badge/PHP-8.2-777bb4?logo=php)
![stack](https://img.shields.io/badge/Docker-ready-2496ed?logo=docker)

---

## Despliegue en 3 pasos (stack completo)

Se recomienda usar [Dockge](https://github.com/louislam/dockge) o `docker compose`
directamente. El stack trae **MariaDB + docker-mailserver + Roundcube + MailAdmin + imapsync**.

### 1. Crear una pila con este `docker-compose.yml`

```yaml
services:

  mariadb:
    image: mariadb:10.11
    container_name: mail-mariadb
    restart: always
    environment:
      MARIADB_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MARIADB_DATABASE: ${DB_NAME}
      MARIADB_USER: ${DB_USER}
      MARIADB_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mariadb_data:/var/lib/mysql
    networks: [mailnet]
    healthcheck:
      test: ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
      interval: 10s
      timeout: 5s
      retries: 10

  mailserver:
    image: mailserver/docker-mailserver:latest
    container_name: mailserver
    hostname: mail
    domainname: ${MAIL_DOMAIN}
    ports: ["25:25", "143:143", "587:587"]
    volumes:
      - ./maildata:/var/mail
      - ./mailstate:/var/mail-state
      - ./mailconfig:/tmp/docker-mailserver
    environment:
      ENABLE_SPAMASSASSIN: 0
      ENABLE_CLAMAV: 0
      ENABLE_FAIL2BAN: 1
      ONE_DIR: 1
      OVERRIDE_HOSTNAME: mail.${MAIL_DOMAIN}
      PERMIT_DOCKER: network
      SPOOF_PROTECTION: 0
      PERMIT_MYNETWORKS: ${PERMIT_MYNETWORKS}
      SSL_TYPE: ""
    networks: [mailnet]
    restart: always

  roundcube:
    image: roundcube/roundcubemail:latest
    container_name: roundcube-app
    ports: ["8888:80"]
    restart: always
    depends_on:
      mariadb:
        condition: service_healthy
    environment:
      ROUNDCUBEMAIL_DB_TYPE: mysql
      ROUNDCUBEMAIL_DB_HOST: mariadb
      ROUNDCUBEMAIL_DB_USER: ${DB_USER}
      ROUNDCUBEMAIL_DB_PASSWORD: ${DB_PASSWORD}
      ROUNDCUBEMAIL_DB_NAME: ${DB_NAME}
      ROUNDCUBEMAIL_DEFAULT_HOST: mailserver
      ROUNDCUBEMAIL_DEFAULT_PORT: 143
      ROUNDCUBEMAIL_SMTP_SERVER: mailserver
      ROUNDCUBEMAIL_SMTP_PORT: 587
    networks: [mailnet]

  mail-admin:
    build:
      context: https://github.com/DanyTrax/MailAdmin.git#main:mail-admin
    image: mailadmin:latest
    container_name: mail-admin
    ports: ["8889:80"]
    restart: always
    environment:
      ADMIN_USER: ${ADMIN_USER}
      ADMIN_PASSWORD: ${ADMIN_PASSWORD}
      DEFAULT_DOMAIN: ${MAIL_DOMAIN}
      ACCOUNTS_FILE: /mailconfig/postfix-accounts.cf
      ALIASES_FILE: /mailconfig/postfix-virtual.cf
      QUOTA_FILE: /mailconfig/dovecot-quotas.cf
    volumes:
      - ./mailconfig:/mailconfig
    networks: [mailnet]

  imapsync:
    image: gilleslamiral/imapsync:latest
    container_name: imapsync
    restart: always
    command: sleep infinity
    networks: [mailnet]

networks:
  mailnet:
    driver: bridge

volumes:
  mariadb_data:
```

### 2. Definir el `.env`

```env
MAIL_DOMAIN=tudominio.com
PERMIT_MYNETWORKS=192.168.1.0/24

DB_ROOT_PASSWORD=RootSuperSecreta.
DB_NAME=webmail_db
DB_USER=roundcube
DB_PASSWORD=RoundcubeSegura.

ADMIN_USER=admin
ADMIN_PASSWORD=ClaveDelPanel.
```

### 3. Desplegar

```bash
docker compose up -d
```

O desde Dockge: botón **Desplegar**.

---

## URLs resultantes

| Servicio | URL | Uso |
|---|---|---|
| **MailAdmin** | `http://SERVIDOR:8889` | Panel CRUD. Login con `ADMIN_USER` / `ADMIN_PASSWORD` |
| **Roundcube** | `http://SERVIDOR:8888` | Webmail para los usuarios |
| Mailserver | 25 / 143 / 587 | SMTP / IMAP |

Crea una cuenta en MailAdmin (ej. `soporte@tudominio.com`) y con esa misma
dirección + clave entras a Roundcube.

---

## Cómo funciona internamente

El panel monta el mismo volumen (`./mailconfig`) que usa `docker-mailserver`
y edita directamente los archivos:

- `postfix-accounts.cf` — `usuario@dominio|{SHA512-CRYPT}$6$salt$hash`
- `postfix-virtual.cf`  — `alias@dominio destino@dominio`
- `dovecot-quotas.cf`   — `usuario@dominio:500M`

`docker-mailserver` vigila estos archivos con change-detection y aplica los
cambios automáticamente.

Las contraseñas se hashean con `crypt($password, '$6$' . salt_aleatoria)` y
se prefijan con `{SHA512-CRYPT}`, formato esperado por Dovecot.

El contenedor de MailAdmin incluye un **entrypoint** que, al arrancar, crea
los archivos de configuración si no existen y ajusta los permisos (`666` para
los archivos, `777` para el directorio) — así no hay conflictos con los
archivos creados por `docker-mailserver` como root.

---

## Despliegue sólo del panel (ya tienes docker-mailserver)

Si ya tienes `docker-mailserver` corriendo en otro stack y sólo quieres añadir
el panel, clona el repo y usa el compose que está dentro de `mail-admin/`:

```bash
git clone https://github.com/DanyTrax/MailAdmin.git
cd MailAdmin/mail-admin
cp .env.example .env
# Edita .env: MAILCONFIG_PATH debe apuntar a la carpeta mailconfig del mailserver
docker compose up -d --build
```

---

## Build manual (sin compose)

```bash
git clone https://github.com/DanyTrax/MailAdmin.git
cd MailAdmin/mail-admin
docker build -t mailadmin:latest .

docker run -d \
  --name mail-admin \
  -p 8889:80 \
  -e ADMIN_USER=admin \
  -e ADMIN_PASSWORD='MiClaveSuperSegura.' \
  -e DEFAULT_DOMAIN=tudominio.com \
  -v /ruta/absoluta/a/mailconfig:/mailconfig \
  --restart always \
  mailadmin:latest
```

---

## Seguridad

- **Cambia `ADMIN_PASSWORD`** antes de exponer el panel.
- Restringe el puerto `8889` con firewall o publícalo detrás de un reverse
  proxy con HTTPS (nginx/traefik/caddy).
- Eliminar una cuenta la remueve de `postfix-accounts.cf` pero **no** borra
  el buzón físico en `maildata/`.
- Las sesiones son cookies `HttpOnly` + `SameSite=Lax` con token CSRF en
  todos los formularios.

---

## Problemas comunes

**`repository does not contain ref main`** al desplegar con `build:` desde
GitHub → el repo de tu fork está vacío o en otra rama. Verifica que exista
`main` con `git ls-remote --heads origin`.

**`container name "/xxx" is already in use`** → queda un contenedor viejo
con el mismo nombre. Elimínalo con `docker rm -f xxx` o desde Dockge borra la
pila anterior antes de desplegar la nueva.

**Nothing happens in the panel UI / "Permission denied" in PHP** → si
ocurre con una instalación muy antigua del panel sin el entrypoint nuevo,
actualiza la imagen:

```bash
docker compose build --no-cache --pull mail-admin
docker compose up -d mail-admin
```

---

## Licencia

MIT
