# Journal reference layout — 2026-11-01

- Owner request: match the JLAR reference layout, add the screenshot sections, deploy when ready.
- Agent: Codex. Final health/parity verification: 17:38 Asia/Tehran, 2026-11-01.
- Live page: https://raazpublishnp.com/djas
- Code release: `e94f98d` (includes initial layout commit `66a6ae4`).
- Target: Bluehost `129.121.135.96`, `/opt/raazpublishnp`. This production directory is a file checkout without `.git`; upload only scoped files and rebuild there. Do not assume `git pull` works.
- Existing SSH identity: project `.secrets/ssh/bluehost_journal_ed25519`, root, port 22. Never copy private-key contents into logs or source control.
- Final OJS image: `sha256:64bbf041ed6987d7635737a9619b1b6ece8850ca658b5b69ef751bc160d5d34a`.

## Deployment and recovery

1. Complete database, manuscript/public files and encrypted configuration backup:
   `/opt/raazpublishnp/deploy/backups/2026-11-01_1401` (73 MB). All four checksums and gzip integrity checks passed.
2. The first backup at `2026-10-31_2102` stopped because scheduled task logs changed during archiving; it is incomplete. The successful backup above briefly paused worker and scheduler, then restarted both. The underlying backup-script behavior is unchanged.
3. Build only `ojs` once; all three application services use that image. A localhost-only preview container verified the template and full OJS CSS compilation before final activation; the preview was subsequently removed.
4. Activate with `docker compose up -d --no-build --wait --wait-timeout 60 ojs worker scheduler`, run `scripts/clear-cache.sh`, reload nginx. No database migration or journal-content replacement was performed.
5. Final checks: OJS and database healthy; worker, scheduler, nginx and certbot running. Public smoke tests and responsive browser assertions passed. Template, reference stylesheet and footer stylesheet hashes match between source and running OJS container.

Recovery image: `journal-ojs:before-layout-final-20261101` (the first layout deployment).
Recovery sources: `backups/2026-11-01_1401/theme-before-final/` contains that deployment's homepage template, reference styles and footer styles.
To revert the application image, retag the recovery image as `journal-ojs:3_5_0-5`, recreate the application services with `--no-build`, clear caches and reload nginx. Restore the saved sources before any later rebuild. Use the verified data backup only if data recovery is actually necessary; this release did not migrate data.

## Pending editorial inputs

The owner confirmed these are unavailable: publication frequency, DOI details, editorial statistics and approved Word template. Unknown values are explicitly pending. Indexing links go to the services' public websites and do not claim membership or acceptance.

Statistics and frequency placeholders currently live in the homepage template; update them only with verified editorial values. Template download must remain unavailable until the journal's approved file is provided. The journal's ISSN, DOI prefix, acronym, abbreviation and publisher continue to come from OJS settings.
