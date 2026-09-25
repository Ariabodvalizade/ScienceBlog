# Delete all local demo data and start again from the original snapshot.
$ComposeFile = Join-Path $PSScriptRoot '..\deploy\docker-compose.dev.yml'
& docker compose -f $ComposeFile --env-file (Join-Path $PSScriptRoot 'snapshot\env.local') down -v
& (Join-Path $PSScriptRoot 'start.ps1')
