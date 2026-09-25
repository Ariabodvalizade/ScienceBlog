# Stop the local demo (data is kept for the next .\start.ps1).
$ComposeFile = Join-Path $PSScriptRoot '..\deploy\docker-compose.dev.yml'
& docker compose -f $ComposeFile --env-file (Join-Path $PSScriptRoot 'snapshot\env.local') down
Write-Host 'Stopped. Start again with .\start.ps1'
