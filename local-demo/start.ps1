# Run the demo journal locally with Docker only (Windows PowerShell).
#   powershell -ExecutionPolicy Bypass -File .\start.ps1   → http://localhost:8080/djas
$ErrorActionPreference = 'Stop'
$Here = $PSScriptRoot
$Root = (Resolve-Path (Join-Path $Here '..')).Path
$ComposeFile = Join-Path $Root 'deploy\docker-compose.dev.yml'
$EnvFile = Join-Path $Here 'snapshot\env.local'
$Snapshot = Join-Path $Here 'snapshot'
$Url = 'http://localhost:8080/djas'
function Compose { & docker compose -f $ComposeFile --env-file $EnvFile @args; if ($LASTEXITCODE -ne 0) { throw "docker compose $args failed" } }

& docker info *> $null
if ($LASTEXITCODE -ne 0) { Write-Error 'Docker is not running. Start Docker Desktop and try again.'; exit 1 }

Write-Host '-> Building the OJS image (first run downloads ~1 GB)'
Compose build ojs

Write-Host '-> Starting the database'
Compose up -d db mailpit
for ($i = 0; $i -lt 90; $i++) {
  $health = & docker compose -f $ComposeFile --env-file $EnvFile ps db --format '{{.Health}}'
  if ($health -eq 'healthy') { break }
  Start-Sleep -Seconds 2
}

# Note: no double quotes inside arguments (Windows PowerShell 5.1 mangles them)
$tables = (& docker compose -f $ComposeFile --env-file $EnvFile exec -T db sh -c 'mariadb -uroot -p$MARIADB_ROOT_PASSWORD -N -e ''SHOW TABLES'' $MARIADB_DATABASE | wc -l') -replace '\D', ''
if ($tables -eq '0') {
  Write-Host '-> First run: restoring the demo database and files'
  # Decompress and import inside the container (avoids PowerShell pipe encoding issues)
  Compose run --rm --no-deps --user root -v "${Snapshot}:/snapshot:ro" --entrypoint sh ojs -c 'tar xzf /snapshot/files.tar.gz -C /var/www && tar xzf /snapshot/public.tar.gz -C /var/www/html && chown -R www-data:www-data /var/www/files /var/www/html/public'
  & docker compose -f $ComposeFile --env-file $EnvFile cp (Join-Path $Snapshot 'db.sql.gz') db:/tmp/db.sql.gz
  Compose exec -T db sh -c 'gunzip -c /tmp/db.sql.gz | mariadb -uroot -p$MARIADB_ROOT_PASSWORD $MARIADB_DATABASE && rm /tmp/db.sql.gz'
}

Write-Host '-> Starting OJS'
Compose up -d ojs
for ($i = 0; $i -lt 90; $i++) {
  try { if ((Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 5).StatusCode -eq 200) { break } } catch { }
  Start-Sleep -Seconds 2
}

Write-Host ''
Write-Host 'The demo journal is running'
Write-Host "  Website        $Url"
Write-Host '  Admin login    http://localhost:8080/index/login   user: admin   password: admin-dev-Password1'
Write-Host '  Emails (test)  http://localhost:8025'
Write-Host '  Stop: .\stop.ps1     Reset to the original demo: .\reset.ps1'
