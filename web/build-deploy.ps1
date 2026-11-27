# Đóng gói ứng dụng để upload lên cPanel: tạo thuchi-deploy.zip (đã có vendor/ và public/build/).
# Chạy trong thư mục web:  powershell -ExecutionPolicy Bypass -File .\build-deploy.ps1
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$composer = "$env:USERPROFILE\tools\composer.phar"
Write-Host '1/4 Cài thư viện PHP (bỏ gói dev)...'
php $composer install --no-dev --optimize-autoloader --no-interaction

Write-Host '2/4 Build CSS/JS...'
npm ci
npm run build

Write-Host '3/4 Tạo file zip...'
$out = Join-Path $PSScriptRoot 'thuchi-deploy.zip'
if (Test-Path $out) { Remove-Item $out -Confirm:$false }
$staging = Join-Path ([IO.Path]::GetTempPath()) ('thuchi-' + [guid]::NewGuid())
robocopy $PSScriptRoot $staging /E /XD node_modules .git tests .claude deploy /XF .env database.sqlite thuchi-deploy.zip build-deploy.ps1 .phpunit.result.cache *.log /NFL /NDL /NJH /NJS | Out-Null

# Xoá dữ liệu sinh ra khi chạy thử ở máy (giữ các file .gitignore để thư mục không bị mất trong zip)
$cleanDirs = @(
    "$staging\storage\app\private",
    "$staging\storage\framework\sessions",
    "$staging\storage\framework\views",
    "$staging\storage\framework\cache\data",
    "$staging\storage\framework\testing",
    "$staging\bootstrap\cache"
)
Get-ChildItem $cleanDirs -File -Recurse -ErrorAction SilentlyContinue |
    Where-Object Name -ne '.gitignore' | Remove-Item -Confirm:$false
Remove-Item "$staging\storage\app\mpdf" -Recurse -Confirm:$false -ErrorAction SilentlyContinue
# Tự tạo zip với đường dẫn dùng "/" (Compress-Archive của Windows PowerShell 5 dùng "\" => giải nén trên Linux bị lỗi tên file)
Add-Type -AssemblyName System.IO.Compression, System.IO.Compression.FileSystem
$zip = [IO.Compression.ZipFile]::Open($out, 'Create')
try {
    Get-ChildItem $staging -Recurse -File -Force | ForEach-Object {
        $entry = $_.FullName.Substring($staging.Length + 1).Replace('\', '/')
        [void][IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $entry, 'Optimal')
    }
} finally {
    $zip.Dispose()
}
Remove-Item $staging -Recurse -Confirm:$false

Write-Host '4/4 Cài lại gói dev cho máy phát triển...'
php $composer install --no-interaction

Write-Host "Xong: $out"
