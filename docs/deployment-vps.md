# VPS Deployment Guide

Target: a VPS **outside Iran** (e.g. Hetzner, DigitalOcean, OVH, Contabo) running **Ubuntu 24.04 LTS**.

## 1. Server sizing

| | Minimum | Recommended |
|---|---|---|
| vCPU | 2 | 2–4 |
| RAM | 4 GB | 8 GB |
| Disk | 40 GB SSD | 80 GB SSD (PDFs grow over time) |
| Backups | provider snapshots weekly | + off-site copy (S3/Backblaze via rclone) |

## 2. DNS

- `A` record: `[domain]` → VPS IPv4 (plus `AAAA` for IPv6 if available). Optionally `www` → CNAME `[domain]`.
- Email deliverability for the SMTP provider: **SPF**, **DKIM** and **DMARC** records (values from the SMTP provider).

## 3. One-time server preparation

```bash
# as root on a fresh Ubuntu 24.04
adduser deploy && usermod -aG sudo deploy
# copy your SSH key to deploy, then disable password login:
sed -i 's/^#\?PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config && systemctl restart ssh

apt update && apt -y upgrade && apt -y install ufw unattended-upgrades fail2ban git
ufw allow OpenSSH && ufw allow 80/tcp && ufw allow 443/tcp && ufw --force enable
dpkg-reconfigure -plow unattended-upgrades

# Docker Engine + compose plugin (official repository)
curl -fsSL https://get.docker.com | sh
usermod -aG docker deploy
```

## 4. Deploy the application

```bash
su - deploy
git clone https://github.com/Ariabodvalizade/ScienceBlog.git journal && cd journal/deploy
cp .env.example .env
nano .env                          # JOURNAL_DOMAIN, LETSENCRYPT_EMAIL, admin email, SMTP_*
./scripts/generate-secrets.sh      # fills DB passwords, OJS secrets, admin & backup passwords
./scripts/init-ssl.sh --staging    # optional dry run against Let's Encrypt staging
./scripts/init-ssl.sh              # first real certificate (port 80 must be free)
docker compose up -d --build db ojs web
./scripts/install-ojs.sh           # runs the OJS installer, stores app key, starts everything
```

`install-ojs.sh` creates the site administrator from `OJS_ADMIN_USER` / `OJS_ADMIN_EMAIL` / `OJS_ADMIN_PASSWORD`,
writes `OJS_APP_KEY` and `OJS_INSTALLED=On` into `.env`, then starts the worker and scheduler.
**Remove `OJS_ADMIN_PASSWORD` from `.env` afterwards** (keep it in a password manager).

Next:
1. Log in at `https://[domain]/index/login` → *Administration › Hosted Journals › Create Journal*.
2. Follow [ojs-configuration.md](ojs-configuration.md). The Meridian theme and scholarlyReader plugin are already
   in the image; enable them under *Settings › Website › Plugins* and select Meridian under *Appearance*.

`config.inc.php` is **generated at every container start** from `.env` by `deploy/ojs/configure.php`, starting from the
stock template of the OJS version in use. To change a setting, edit `.env` and run `docker compose up -d`.

## 5. What runs where

| Service | Image | Purpose |
|---|---|---|
| `web` | `nginx:1.27-alpine` | TLS, HTTP→HTTPS, security headers, rate limits on login/registration/API, gzip, static asset caching |
| `ojs` | `journal-ojs` (built from `deploy/ojs/Dockerfile`: `pkpofficial/ojs:3_5_0-5` + Meridian + scholarlyReader) | OJS web application |
| `worker` | same image | Job queue: `php lib/pkp/tools/jobs.php work` (emails, deposits, indexing) |
| `scheduler` | same image | `php lib/pkp/tools/scheduler.php work` (reminders, statistics, scheduled tasks) |
| `db` | `mariadb:11.4` | Database (utf8mb4) |
| `certbot` | `certbot/certbot` | Renews certificates every 12 h (webroot); nginx reloads every 6 h |

Volumes: `db-data`, `ojs-files` (private uploads), `ojs-public` (logos, covers), `certbot-etc`, `certbot-www`.

## 6. Backups and restore

`./scripts/backup.sh` writes `backups/YYYY-MM-DD_HHMM/`:
- `db.sql.gz`: a consistent MariaDB dump (`--single-transaction`)
- `files.tar.gz` and `public.tar.gz`: uploaded manuscripts/galleys and public files
- `env.tar.gz.gpg`: `.env`, encrypted with `BACKUP_GPG_PASSPHRASE`
- `SHA256SUMS`

It keeps `BACKUP_RETENTION_DAYS` days and copies off-site with rclone when `RCLONE_REMOTE` is set. Configure rclone
once with `rclone config`.

Host crontab (`crontab -e` as `deploy`):
```
30 2 * * * cd ~/journal/deploy && ./scripts/backup.sh >> ~/backup.log 2>&1
```

Restore (asks you to type `RESTORE`, verifies checksums):
```bash
./scripts/restore.sh backups/2026-09-25_0230
```
**Test a restore on staging once before go-live.** Both scripts were verified end to end on the development machine.

## 7. Updating

| Task | Command |
|---|---|
| Deploy new theme/plugin code | `./scripts/deploy.sh` (git pull, rebuild, restart, clear caches) |
| OJS patch release (3.5.0-x) | set `OJS_VERSION` in `.env`, then `./scripts/update-ojs.sh` (backup → build → `tools/upgrade.php upgrade` → restart) |
| Clear caches | `./scripts/clear-cache.sh` |
| Logs | `docker compose logs -f ojs` (also `web`, `worker`, `scheduler`) |
| OS | unattended security upgrades; reboot monthly in a quiet window |

After an OJS upgrade, compare the templates Meridian overrides with the new core versions (list in
[architecture.md §3](architecture.md#3-theme-meridian-child-theme)) and run the screenshot suite.

## 8. Post-deploy smoke test

- [ ] `https://[domain]` loads with a valid certificate; `http://` redirects to `https://`
- [ ] Log in as admin; *Administration › System Information* shows no warnings
- [ ] Register a test author → validation email received
- [ ] Submit a test manuscript → acknowledgement email received
- [ ] `https://[domain]/index.php/[journal]/oai?verb=Identify` (or the clean-URL equivalent) returns XML
- [ ] Article page source contains `citation_title` and `application/ld+json`
- [ ] `docker compose ps` shows everything healthy; `docker compose logs scheduler` shows "Running scheduled tasks every minute"
- [ ] SSL Labs ≥ A; securityheaders.com ≥ A

## 9. Security checklist

- SSH keys only; ufw open to 22/80/443 only; fail2ban on sshd.
- Secrets exist only in `.env` on the server (never committed); `config.inc.php` is read-only in the container.
- nginx: HSTS (6 months, add preload later), `X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options SAMEORIGIN`,
  `Permissions-Policy`, and rate limits on `/login`, `/user/register` and `/api/`.
- OJS: `force_ssl`, ALTCHA captcha, `display_errors = Off`, `allowed_hosts` set, admin accounts use unique emails,
  and rotated passwords after UAT.
- Remove the demo content and test accounts before go-live.
