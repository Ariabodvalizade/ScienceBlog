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
cp .env.example .env && nano .env        # domain, emails, DB passwords, SMTP, secrets
./scripts/render-config.sh               # builds config/config.inc.php from .env
./scripts/init-ssl.sh                    # first Let's Encrypt certificate (HTTP-01 webroot)
docker compose up -d --build             # web, ojs, cron, db, certbot
```

Then open `https://[domain]/index/install`, which is prefilled from `.env`:

1. Administrator account: use a strong password and store it in a password manager.
2. Database: host `db`, name, user and password from `.env`; **uncheck** "create new database" if prompted.
3. Files directory: `/var/www/files`.
4. OAI repository identifier: `[domain]`.

After the installer:

```bash
./scripts/post-install.sh      # sets installed = On, job/task runners Off, enables our plugins
```

Then follow [ojs-configuration.md](ojs-configuration.md) in the admin UI.

## 5. What runs where

| Service | Image | Purpose |
|---|---|---|
| `web` | `nginx:1.27-alpine` | TLS termination, redirects, security headers, rate limits, static caching |
| `ojs` | built from `deploy/ojs/Dockerfile` (`pkpofficial/ojs:3_5_0-5` + our plugins) | OJS application |
| `cron` | same image as `ojs` | `scheduler.php run` + `jobs.php work` every minute |
| `db` | `mariadb:11.4` | Database |
| `certbot` | `certbot/certbot` | Certificate renewal (every 12 h check) |

Volumes: `db-data`, `ojs-files`, `ojs-public`, `certbot-etc`, `certbot-www`.

## 6. Backups and restore

- `scripts/backup.sh` (cron on the host, daily at 02:30):
  - `mariadb-dump` (single-transaction) → `backups/YYYY-MM-DD/db.sql.gz`
  - `ojs-files` and `ojs-public` → `files.tar.gz`, `public.tar.gz`
  - `config.inc.php` + `.env` → `config.tar.gz.gpg` (encrypted with `BACKUP_GPG_PASSPHRASE`)
  - keeps 14 days; optional `rclone copy` to an off-site remote if `RCLONE_REMOTE` is set
- `scripts/restore.sh backups/YYYY-MM-DD` restores the database and volumes. **Test a restore on staging once before go-live.**

Host crontab (`crontab -e` as `deploy`):
```
30 2 * * * cd ~/journal/deploy && ./scripts/backup.sh >> ~/backup.log 2>&1
```

## 7. Updating

- **Our theme/plugins:** `git pull && docker compose up -d --build ojs cron`
- **OJS patch release** (3.5.0-x):
  1. `./scripts/backup.sh`
  2. bump `OJS_VERSION` in `.env`
  3. `./scripts/update-ojs.sh`, which builds, runs `tools/upgrade.php upgrade` and restarts the stack
  4. run the smoke test (§8)
- **OS:** unattended security upgrades; reboot monthly during a quiet window.

## 8. Post-deploy smoke test

- [ ] `https://[domain]` loads with a valid certificate; `http://` redirects to `https://`
- [ ] Log in as admin; *Administration › System Information* shows no warnings
- [ ] Register a test author → validation email received
- [ ] Submit a test manuscript → acknowledgement email received
- [ ] `https://[domain]/index.php/[journal]/oai?verb=Identify` (or the clean-URL equivalent) returns XML
- [ ] Article page source contains `citation_title` and `application/ld+json`
- [ ] `docker compose ps` shows everything healthy; `docker compose logs cron` shows the scheduler running
- [ ] SSL Labs ≥ A; securityheaders.com ≥ A

## 9. Security checklist

- SSH keys only; ufw open to 22/80/443 only; fail2ban on sshd.
- Secrets exist only in `.env` on the server (never committed); `config.inc.php` is read-only in the container.
- nginx: HSTS (6 months, add preload later), `X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options SAMEORIGIN`,
  `Permissions-Policy`, and rate limits on `/login`, `/user/register` and `/api/`.
- OJS: `force_ssl`, ALTCHA captcha, `display_errors = Off`, `allowed_hosts` set, admin accounts use unique emails,
  and rotated passwords after UAT.
- Remove the demo content and test accounts before go-live.
