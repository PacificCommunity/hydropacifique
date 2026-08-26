# HydroPacifique — Installation and Deployment

Hydrological data management platform (PHP 8.2 + Apache + MySQL), deployed as a
two-container Docker Compose stack.

- **`web`** — PHP 8.2 / Apache, listening on container port **8080**, published on the host as `${WEB_PORT}`.
- **`db`** — MySQL **8.4** LTS, reachable only on the Compose network (no published port).

---

## Table of contents

1. [Requirements](#1-requirements)
2. [Install Docker](#2-install-docker)
3. [Copy the code to the server](#3-copy-the-code-to-the-server)
4. [Configure `.env`](#4-configure-env)
5. [Prepare host directories](#5-prepare-host-directories)
6. [Build and start](#6-build-and-start)
7. [Load the database](#7-load-the-database)
8. [Verify the installation](#8-verify-the-installation)
9. [Running locally without Docker](#9-running-locally-without-docker)
10. [Routine operations](#10-routine-operations)
11. [Troubleshooting](#11-troubleshooting)
12. [Reference](#12-reference)

---

## 1. Requirements

| | |
|---|---|
| Host OS | Linux (RHEL/Rocky/Alma 8+, Debian 11+, Ubuntu 20.04+) |
| Docker Engine | 20.10 or newer |
| Docker Compose | v2 (`docker compose`) or the v2 standalone binary (`docker-compose`) |
| Disk | ~2 GB for images, plus database growth |
| Ports | one host port for the web UI (default 8080) |
| Transfer tools | `rsync` and `ssh` on your workstation |

Nothing else is needed on the host — PHP, Composer, Apache and MySQL all live
inside the containers. The host never needs a PHP or MySQL install.

---

## 2. Install Docker

Run **one** of the following on the server.

### RHEL / Rocky / AlmaLinux

```bash
sudo dnf -y install dnf-plugins-core
sudo dnf config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
sudo dnf -y install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo systemctl enable --now docker
```

### Debian / Ubuntu

```bash
sudo apt-get update
sudo apt-get -y install ca-certificates curl gnupg
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/$(. /etc/os-release && echo "$ID")/gpg \
  | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
https://download.docker.com/linux/$(. /etc/os-release && echo "$ID") \
$(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
  | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt-get update
sudo apt-get -y install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo systemctl enable --now docker
```

### Run Docker without `sudo` (optional but recommended)

`scripts/restore-db.sh` calls `docker` directly, so either add yourself to the
`docker` group or run the script with `sudo`.

```bash
sudo usermod -aG docker "$USER"
newgrp docker        # or log out and back in
```

### Verify

```bash
docker --version
docker compose version || docker-compose --version
docker run --rm hello-world
```

---

## 3. Copy the code to the server

> **Do not use `git clone` for this.** Three things Compose needs are gitignored
> or untracked, so a clone produces a stack that will not start:
>
> | Needed by Compose | Git status |
> |---|---|
> | `20260814-HP-DB-Structure.sql` (bind-mounted as the init schema) | ignored by `*.sql` |
> | `map/` (bind-mounted read-only, ~7 MB of tiles) | `/map/` ignored |
> | `Dockerfile`, `docker-compose.yml`, `docker/`, `scripts/`, `.env.example`, `include/config_plateform.docker.php` | untracked |
>
> If the schema `.sql` is missing on the host, Docker silently creates a
> *directory* at that mount path and the MySQL entrypoint fails on startup.

Use `rsync` from your workstation:

```bash
SERVER=user@your-server.example.org
DEST=/srv/hydropacifique

ssh "$SERVER" "mkdir -p $DEST"

rsync -avz --delete \
  --exclude='.git/' \
  --exclude='vendor/' \
  --exclude='.env' \
  --exclude='include/config_plateform.php' \
  --exclude='.DS_Store' \
  --exclude='*.log' \
  ./ "$SERVER:$DEST/"
```

Why those exclusions:

- **`vendor/`** (~124 MB) — `composer install` runs inside the image during build.
- **`.env`** — server credentials are created on the server, never copied.
- **`include/config_plateform.php`** — your *local* DB/URL config. Compose mounts
  `include/config_plateform.docker.php` over that path; shipping the local one
  would fight the mount.

Expect roughly **770 files / ~22 MB** transferred, most of it `map/`.

---

## 4. Configure `.env`

On the server:

```bash
cd /srv/hydropacifique
cp .env.example .env
chmod 600 .env

openssl rand -base64 24        # run twice — one per password
nano .env
```

| Variable | Notes |
|---|---|
| `MYSQL_ROOT_PASSWORD` | Required. Used only by admin/restore operations. |
| `MYSQL_PASSWORD` | Required. The password the application connects with. |
| `MYSQL_USER` | Default `hydro`. The app never connects as root. |
| `MYSQL_DATABASE` | Default `hp-data-fj`. Also drives dump auto-selection. |
| `HP_HTTP_SERVER` | **The real public URL**, trailing slash included. |
| `HP_HTTPS_SERVER` | Same. |
| `WEB_PORT` | Host port. Default `8080`. |
| `WEB_BIND` | Set to `127.0.0.1` when a reverse proxy terminates TLS in front. |

`HP_HTTP_SERVER` / `HP_HTTPS_SERVER` matter more than they look: the application
builds **absolute** links and redirects from them. Leaving `127.0.0.1:8080` in
place on a real deployment sends users to their own machine after login, which
presents as "login does nothing".

`docker-compose.yml` deliberately does **not** use `env_file` for the `web`
service — that would inject `MYSQL_ROOT_PASSWORD` into the web container. Only
the variables the app needs are passed explicitly.

---

## 5. Prepare host directories

```bash
cd /srv/hydropacifique
sudo chown -R 33:33 ./data      # 33 = www-data inside the container
```

`./data` holds uploads and generated exports and is bind-mounted read-write so
it outlives the container. Everything else in the image stays root-owned and
read-only.

### SELinux hosts (RHEL / Rocky / Alma)

```bash
getenforce
```

If this prints `Enforcing`, label the bind-mounted paths or the container will
be denied access to them:

```bash
sudo chcon -Rt svirt_sandbox_file_t \
  /srv/hydropacifique/20260814-HP-DB-Structure.sql \
  /srv/hydropacifique/data \
  /srv/hydropacifique/map
```

Alternatively add `z` to the volume lines in `docker-compose.yml`
(`./data:/var/www/html/data:z`, and `:ro,z` for the two read-only mounts).

---

## 6. Build and start

```bash
cd /srv/hydropacifique
docker compose build
```

The build installs the PHP extensions (`gd`, `mysqli`, `pdo_mysql`, `zip`,
`intl`, `opcache`), runs `composer install --no-dev`, and bakes the application
code into the image. First build takes a few minutes.

Do **not** start `web` yet if you are restoring a database — go straight to the
next section, which brings the stack up in the correct order.

If you are *not* restoring a dump and want the empty schema instead:

```bash
docker compose up -d
```

On first boot against an empty volume, MySQL imports
`20260814-HP-DB-Structure.sql` — **63 tables, structure only, zero rows**. With
no rows in `ad_user`, nobody can log in, which is why a restore is normally the
right path.

---

## 7. Load the database

### 7.1 Create a dump from the source database

Run this wherever the source database lives:

```bash
mysqldump -h 127.0.0.1 -u root -p \
  --lock-all-tables \
  --set-gtid-purged=OFF \
  --routines --triggers --events \
  --hex-blob \
  --default-character-set=utf8mb4 \
  'hp-data-fj' \
  | gzip > "db_backup/hp-data-fj_$(date +%Y%m%d_%H%M%S).sql.gz"
```

Each flag is load-bearing:

| Flag | Why |
|---|---|
| `--lock-all-tables` | The schema mixes MyISAM and InnoDB; `--single-transaction` does not give MyISAM a consistent snapshot. |
| `--set-gtid-purged=OFF` | Without it the dump carries `GTID_PURGED` statements that abort the import. |
| `--routines --triggers --events` | Not included by default. |
| `--hex-blob` | Keeps binary columns intact through the text dump. |
| `--default-character-set=utf8mb4` | The schema uses `utf8mb4_0900_ai_ci` and `utf8mb3`. |

**Check your `mysqldump` version first.** A client older than the server
produces a broken or incomplete dump:

```bash
which -a mysqldump
mysqldump --version
mysql -h 127.0.0.1 -u root -e "SELECT VERSION();"
```

The client version must be **≥** the server version. A stray old `mysqldump` on
`PATH` (Anaconda ships a 5.7 one) is a common trap — call the correct binary by
full path if needed, e.g. `/opt/homebrew/bin/mysqldump` or `/usr/bin/mysqldump`.

Name the file `<database>_<YYYYMMDD_HHMMSS>.sql.gz` so the restore script can
auto-select the newest one.

### 7.2 Copy the dump to the server

```bash
rsync -avz db_backup/ "$SERVER:$DEST/db_backup/"
```

`db_backup/` is in `.dockerignore`, so dumps never enter an image layer, and the
Apache vhost denies `.sql`/`.sql.gz`/`.dump` plus the whole `db_backup/`
directory — they are not web-reachable even if copied into the tree by mistake.

### 7.3 Restore

```bash
cd /srv/hydropacifique
./scripts/restore-db.sh
```

With no argument the script selects the newest `<database>_*.sql[.gz]` in
`db_backup/`. To restore a specific file:

```bash
./scripts/restore-db.sh db_backup/hp-data-fj_20260824_112411.sql.gz
./scripts/restore-db.sh -y <dump>        # skip the confirmation prompt
./scripts/restore-db.sh --help
```

What it does, in order:

1. Validates the dump — real MySQL dump, no `GTID_PURGED`, has the
   `Dump completed` trailer (so a truncated file is rejected), counts tables.
2. Starts `db` and waits for it to accept connections.
3. **Stops `web`** — downtime starts here, so nothing writes to a half-populated database.
4. Snapshots the current database to `db_backup/pre-restore_<timestamp>.sql.gz`
   and verifies that snapshot is complete.
5. `DROP DATABASE` / `CREATE DATABASE`, then imports the dump.
6. Re-applies the app user's grants.
7. Verifies restored table count against the dump, and warns if `station` or
   `ad_user` came back empty.
8. **Starts `web`** only on success.

On failure `web` is deliberately left **stopped** — serving a half-restored
database is worse than serving nothing — and the rollback command is printed.

Selection details worth knowing: candidates are sorted by **filename**, not
mtime, because `scp`/`rsync` rewrite mtimes and would make an old dump look
newest right after a transfer. `pre-restore_*` snapshots are never
auto-selected; rolling back to one is always explicit.

---

### 7.4 Reference snapshot

`db_backup/hp-data-fj_20260824_051059.sql.gz` (68 KB) is a known-good snapshot
of the UAT database, taken from the running stack with the section 7.1 flags and
verified by restoring it into a scratch database — 63 tables and every row count
matched the source.

Use it to stand up a working instance without access to the production server.
It is a complete database, not a structure-only file: the schema from
`20260814-HP-DB-Structure.sql` plus reference data and a demonstration dataset.

| | |
|---|---|
| Tables | 63 |
| Stations | 11 — 6 surface-water gauges (`station_type=11`), 5 piezometric wells (`station_type=5`) |
| Time series | 5 series (`data_meta`), 9,360 hourly points (`data_all`) |
| Piezometry | 30 diagraphies (`data_ra`), 419 depth-profile points (`data_ra_piezo_profil`) |
| Gaugings | 1 (`data_jge`), 3 vertical points (`data_jge_points`) |
| Reference geography | 1 territoire, 5 regions, 8 watercourses |
| Accounts | 1 (`ad_user`) — the platform administrator |

Every station is in **Tonga** — Tongatapu, Vava'u, Ha'apai and 'Eua — and all
codes use the `TGA-<site>-<type>` form (`TGA-EUA-WLR`, `TGA-MUA-GW01`, …).

The station data is **synthetic**, generated for testing: water levels carry a
seasonal recession with storm hydrographs, and the well profiles model a
freshwater lens with a saltwater interface. It is demonstration data, not
observations — do not publish figures derived from it.

Restore it like any other dump:

```bash
./scripts/restore-db.sh db_backup/hp-data-fj_20260824_051059.sql.gz
```

Do not rely on the bare `./scripts/restore-db.sh` form to pick this file: with no
argument the script selects the **newest filename**, which will be a later dump
once the instance has been running.

Note that `.gitignore` excludes `*.sql` and `*.sql.gz`, so this snapshot is not
in the repository — it holds account rows, including the password hash and email
of the administrator. Transfer it with the `rsync` in section 7.2, over the same
channel you would use for any credential.

---

## 8. Verify the installation

```bash
docker compose ps
curl -fsS -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8080/   # expect 200
docker compose logs --tail=50 web
docker compose logs --tail=50 db
```

Then open `HP_HTTP_SERVER` in a browser and log in.

Confirm data actually arrived:

```bash
docker compose exec db mysql -u root -p'<root-pw>' 'hp-data-fj' -e "
  SELECT COUNT(*) AS tables_
  FROM information_schema.tables
  WHERE table_schema='hp-data-fj' AND table_type='BASE TABLE';
  SELECT COUNT(*) AS stations FROM station;
  SELECT COUNT(*) AS users    FROM ad_user;"
```

`users = 0` means nobody can log in — you restored a structure-only dump.

---

## 9. Running locally without Docker

For development against a MySQL already running on your machine.

```bash
composer install
```

`include/config_plateform.php` is gitignored, so create it with literal values.
Do **not** copy `config_plateform.docker.php` for this — that file reads its
values from `getenv()` and throws `RuntimeException` at boot when the variables
are absent, which is exactly the case under `php -S`.

```bash
cat > include/config_plateform.php <<'PHP'
<?php
define('DB_SERVER', '127.0.0.1');
define('DB_SERVER_USERNAME', 'root');
define('DB_SERVER_PASSWORD', '');
define('DB_DATABASE', 'hp-data-fj');
define('INIT_T', 'Pacific');
define('HP_VERSION', 'Serveur');
define('HP_ACCES', 'Open');
define('HP_SERVEUR', 'Hydro Pacifique');
define('TITRE_SMALL', 'Hydro Pacifique');
define('HTTP_SERVER', 'http://127.0.0.1:8080/');
define('HTTPS_SERVER', 'http://127.0.0.1:8080/');
define('BACKGROUND_LOG', 'image/fond_index_fj.jpg');
define('BACKGROUND_LOG_NOMAD', 'image/fond_index_fj.jpg');
define('BACKGROUND_LOG_FOOTER', 'image/bkgd_footer.jpg');
define('LOGO_IMG', '');
PHP
```

Load a dump into your local MySQL, then start the built-in server:

```bash
gzip -dc db_backup/hp-data-fj_*.sql.gz | mysql -h 127.0.0.1 -u root 'hp-data-fj'
php -S 127.0.0.1:8080 -t .
```

Two things must line up:

**Port.** `HTTP_SERVER` / `HTTPS_SERVER` above must match the port you pass to
`php -S`. A mismatch shows up as a redirect bouncing you back to the login page.

**`sql_mode`.** MySQL 8+ enables `ONLY_FULL_GROUP_BY` by default and this
codebase predates it — 225 files use `SELECT DISTINCT` and 35 use `GROUP BY` in
the older, looser style. The `db` container sets the mode via `--sql-mode`, but a
host MySQL needs it applied manually:

```bash
mysql -h 127.0.0.1 -u root -e \
  "SET PERSIST sql_mode='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';"
```

`SET PERSIST` (MySQL 8.0+) survives a restart and takes effect on new
connections; PHP opens one per request, so just reload the page. Without it you
get:

```
Expression #1 of ORDER BY clause is not in SELECT list ...
this is incompatible with DISTINCT
```

`NO_ZERO_DATE` / `NO_ZERO_IN_DATE` are dropped for the same reason — the schema
declares columns as `date NOT NULL DEFAULT '0000-00-00'`. `STRICT_TRANS_TABLES`
is kept deliberately: it turns over-long or invalid values into errors instead of
silently truncating measurement data.

---

## 10. Routine operations

```bash
# Logs
docker compose logs -f web
docker compose logs -f db

# Restart / stop
docker compose restart web
docker compose down                  # stop, keep the database volume
docker compose down -v               # stop and DESTROY the database volume

# Deploy a code change (code is baked into the image, so rebuild)
rsync -avz --delete --exclude='.git/' --exclude='vendor/' --exclude='.env' \
  --exclude='include/config_plateform.php' ./ "$SERVER:$DEST/"
ssh "$SERVER" "cd $DEST && docker compose up -d --build web"

# Shell access
docker compose exec web bash
docker compose exec db mysql -u root -p 'hp-data-fj'

# Manual backup from the running stack
docker compose exec -T -e MYSQL_PWD='<root-pw>' db \
  mysqldump -u root --lock-all-tables --set-gtid-purged=OFF \
    --routines --triggers --events --hex-blob \
    --default-character-set=utf8mb4 'hp-data-fj' \
  | gzip > "db_backup/hp-data-fj_$(date +%Y%m%d_%H%M%S).sql.gz"
```

### Scheduled backups

```cron
15 2 * * * cd /srv/hydropacifique && /usr/bin/docker compose exec -T -e MYSQL_PWD="$(sed -n 's/^MYSQL_ROOT_PASSWORD=//p' .env)" db mysqldump -u root --lock-all-tables --set-gtid-purged=OFF --routines --triggers --events --hex-blob --default-character-set=utf8mb4 'hp-data-fj' | gzip > "db_backup/hp-data-fj_$(date +\%Y\%m\%d_\%H\%M\%S).sql.gz"
```

Prune old dumps separately — nothing rotates `db_backup/` automatically, and the
restore script adds a `pre-restore_*` snapshot on every run.

### Reverse proxy

Set `WEB_BIND=127.0.0.1` in `.env` so the container is not exposed directly,
point your proxy at `127.0.0.1:${WEB_PORT}`, and set `HP_HTTP_SERVER` /
`HP_HTTPS_SERVER` to the public HTTPS URL. The app builds absolute URLs from
those constants rather than from request headers, so no `X-Forwarded-*`
configuration is required inside the container.

---

## 11. Troubleshooting

### `Waiting for the database to accept connections` hangs, then times out at 120s

Get the logs first — they identify which of these it is:

```bash
docker compose logs db | tail -60
docker compose ps -a
```

**Stale volume with a different root password.** The most common cause. If
`db_data` was created under a different `MYSQL_ROOT_PASSWORD`, the entrypoint
skips initialization and root still has the *old* password, so the readiness
probe never authenticates. Logs show a normal startup with no init messages.

```bash
docker volume ls | grep hydropacifique
docker compose down -v && ./scripts/restore-db.sh
```

`down -v` destroys the volume — safe only when nothing has been restored yet.

**SELinux denials.** See [§5](#selinux-hosts-rhel--rocky--alma). Check with
`getenforce` and `sudo ausearch -m avc -ts recent | tail -20`.

**The schema file became a directory.** If `20260814-HP-DB-Structure.sql` never
reached the host, Docker created a directory at the mount path:

```bash
ls -la 20260814-HP-DB-Structure.sql            # must be a ~51 KB file
docker compose exec db ls -la /docker-entrypoint-initdb.d/
```

Fix: `docker compose down -v`, `rmdir` the stray directory, re-run the rsync.

**Genuinely slow first boot.** MySQL 8.4 initialization plus a 63-table schema
can exceed 120 s on slow storage. If the logs reach `ready for connections`
shortly after the timeout, just re-run the script — the volume is already
initialized and it will succeed immediately.

### `Expression #1 of ORDER BY clause is not in SELECT list ... incompatible with DISTINCT`

`ONLY_FULL_GROUP_BY` is active. Inside Docker this is handled by `--sql-mode` in
`docker-compose.yml`; on a host MySQL apply the `SET PERSIST` from
[§9](#9-running-locally-without-docker).

### `@@GLOBAL.GTID_PURGED can only be set when GTID_EXECUTED is empty`

The dump carries GTID statements. Re-dump with `--set-gtid-purged=OFF`. The
restore script rejects such dumps in preflight rather than failing mid-import.

### `<dump> has no 'Dump completed' trailer`

The file is truncated or still being written — a partial transfer, a full disk,
or a dump read while `mysqldump` was still running. Re-create and re-copy it.

### `Table count mismatch (N restored vs M expected)`

The import hit errors partway. `web` is left stopped on purpose. Read
`docker compose logs db`, then roll back with the printed command:

```bash
./scripts/restore-db.sh db_backup/pre-restore_<timestamp>.sql.gz
```

### `ERROR 1273 (HY000): Unknown collation: 'utf8mb4_0900_ai_ci'`

The dump is being imported into MySQL 5.7 or MariaDB. This schema needs
**MySQL 8.0+**; the stack pins 8.4. Check `docker compose config | grep image`.

### Login shows "An active session was detected for this account"

Fixed — `login.php` now supersedes a stale session instead of refusing the
login, and `SESSION_TIMEOUT` in `include/config.php` is 7200 **seconds** (it was
previously documented as milliseconds, giving an unintended ~2h46m window). If
you still see this, the server is running pre-fix code: re-rsync and
`docker compose up -d --build web`.

Old accumulated rows are harmless; the first login per account clears them. To
clear them by hand:

```bash
docker compose exec db mysql -u root -p 'hp-data-fj' -e "DELETE FROM ad_session WHERE sid = '';"
```

### A page renders blank / login redirects to the wrong host

`HP_HTTP_SERVER` / `HP_HTTPS_SERVER` do not match how users actually reach the
site. Fix `.env`, then `docker compose up -d web`.

### A page shows no data even though rows exist

Filters are **persisted per user** in `ad_user_filter` and survive logout, so a
single-station filter set once keeps applying everywhere. Inspect and clear:

```bash
docker compose exec db mysql -u root -p 'hp-data-fj' -e "SELECT * FROM ad_user_filter;"
docker compose exec db mysql -u root -p 'hp-data-fj' -e "DELETE FROM ad_user_filter WHERE filter_id='select_station';"
```

Also check that the station's `id_region` points at a `geo_region` row whose
`id_territoire` matches the logged-in territory — list pages join on
`WHERE r.id_territoire = ...` and silently drop stations that fail it.

### Uploads or exports fail with a permission error

```bash
sudo chown -R 33:33 /srv/hydropacifique/data
docker compose restart web
```

### `Neither 'docker compose' nor 'docker-compose' is available`

The Compose plugin is missing, or you are running the script as a user outside
the `docker` group. See [§2](#2-install-docker).

### Port already in use

```bash
sudo ss -lntp | grep :8080
```

Change `WEB_PORT` in `.env` and `docker compose up -d web`.

### Schema changes to `20260814-HP-DB-Structure.sql` are not applied

That file runs **only** on first boot against an empty `db_data` volume. Either
`docker compose down -v` (destroys all data) or apply the change through a dump
and `./scripts/restore-db.sh`.

---

## 12. Reference

### Layout

| Path | Purpose |
|---|---|
| `Dockerfile` | PHP 8.2 + Apache image; runs `composer install`, bakes in the code |
| `docker-compose.yml` | `web` + `db` services, volumes, healthchecks |
| `docker/php.ini` | PHP overrides — 64 M uploads, 512 M memory, OPcache, session hardening |
| `docker/apache-vhost.conf` | vhost on :8080, security headers, denies `.sql`/`db_backup/`/`.git` |
| `.env` / `.env.example` | Credentials and public URLs (`.env` is gitignored) |
| `include/config_plateform.docker.php` | Container DB/URL config, mounted over `config_plateform.php` |
| `20260814-HP-DB-Structure.sql` | Structure-only schema, 63 tables, first-boot init |
| `db_backup/` | Dumps and `pre-restore_*` safety snapshots (never web-served) |
| `scripts/restore-db.sh` | Guarded destructive restore |
| `data/` | Uploads and generated exports (bind-mounted, owned by uid 33) |
| `map/` | Static GeoJSON map assets (bind-mounted read-only) |

### Versions

| Component | Version | Note |
|---|---|---|
| PHP | 8.2 | `--build-arg PHP_VERSION=8.3` to bump after a smoke test |
| MySQL | 8.4 LTS | 5.7 cannot read this schema's collations |
| Apache | Debian default | container port 8080, needs no root-bound port |
| Composer | 2 | `--no-dev --optimize-autoloader` at build time |

`composer.json` requires `php >=8.0.30` but pins dependency *resolution* to
8.0.30, so a newer runtime is allowed. PHP 8.0 is end-of-life (no security
patches since Nov 2023); 8.2 is still supported and within
`phpspreadsheet` 1.28's tested range.

### MySQL `sql_mode`

The `db` service runs with:

```
--sql-mode=STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION
```

`ONLY_FULL_GROUP_BY`, `NO_ZERO_DATE` and `NO_ZERO_IN_DATE` are dropped for the
reasons in [§9](#9-running-locally-without-docker). If `STRICT_TRANS_TABLES`
later blocks legitimate writes ("Data too long", "Incorrect date value"), reduce
it to just `NO_ENGINE_SUBSTITUTION` — but prefer fixing the offending write,
since strict mode is what stops silent truncation of measurement data.

### Security notes

- `.env` is `chmod 600` and excluded from both git and Docker images.
- The `web` container receives only the variables it needs — never `MYSQL_ROOT_PASSWORD`.
- `db` publishes no host port; it is reachable only on the Compose network.
- Application code is root-owned and read-only inside the container; only `data/` is writable.
- `display_errors` is off; errors go to the container log.
- Apache denies `.sql`, `.dump`, `.log`, `.bak`, `.md`, `db_backup/` and `.git/`.
- `login.php` does **not** call `session_regenerate_id()` on successful
  authentication, so it is open to session fixation — an attacker able to set a
  victim's cookie pre-login inherits the authenticated session. `logout.php` does
  regenerate, which suggests this is an oversight. Not yet fixed.
