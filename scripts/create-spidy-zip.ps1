# Creates spidy-upload.zip on Desktop (excludes heavy dev folders).
$root = Split-Path -Parent $PSScriptRoot
$zip = Join-Path ([Environment]::GetFolderPath("Desktop")) "spidy-upload.zip"

if (Test-Path $zip) { Remove-Item $zip -Force }

$exclude = @('node_modules', '.git', '.tools', 'tests', '.cursor', 'agent-transcripts')
$items = Get-ChildItem -Path $root -Force | Where-Object {
    $name = $_.Name
    $name -notin $exclude
}

Compress-Archive -Path ($items.FullName) -DestinationPath $zip -CompressionLevel Optimal
Write-Host "Created: $zip"
Write-Host "Upload via cPanel, extract into public_html/app.kuhu.org.in"
Write-Host "Read SPIDY_UPLOAD.txt in the zip for steps."
