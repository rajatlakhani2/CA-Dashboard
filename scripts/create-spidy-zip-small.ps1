# Small upload zip (~25-40 MB) — run composer on server after extract.
$root = Split-Path -Parent $PSScriptRoot
$zip = Join-Path ([Environment]::GetFolderPath("Desktop")) "spidy-upload-small.zip"

if (Test-Path $zip) { Remove-Item $zip -Force }

$exclude = @(
    'node_modules', '.git', '.tools', 'tests', '.cursor', 'agent-transcripts',
    'vendor', 'storage\logs', 'storage\framework\cache', 'storage\framework\sessions',
    'storage\framework\views', 'bootstrap\cache', 'cpanel-build-upload.zip'
)

$temp = Join-Path $env:TEMP "spidy-pack"
if (Test-Path $temp) { Remove-Item $temp -Recurse -Force }
New-Item -ItemType Directory -Path $temp | Out-Null

Get-ChildItem -Path $root -Force | Where-Object {
    $_.Name -notin $exclude
} | ForEach-Object {
    Copy-Item -Path $_.FullName -Destination (Join-Path $temp $_.Name) -Recurse -Force -ErrorAction SilentlyContinue
}

# Ensure public/build is included
if (Test-Path "$root\public\build") {
    New-Item -ItemType Directory -Path "$temp\public" -Force | Out-Null
    Copy-Item -Path "$root\public\build" -Destination "$temp\public\build" -Recurse -Force
}

Compress-Archive -Path "$temp\*" -DestinationPath $zip -CompressionLevel Optimal
Remove-Item $temp -Recurse -Force

$mb = [math]::Round((Get-Item $zip).Length / 1MB, 1)
Write-Host "Created: $zip ($mb MB)"
Write-Host "After upload, run: composer install --no-dev && bash scripts/spidy-install.sh"
