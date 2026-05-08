$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$baseSql = 'C:\Users\youss\Downloads\hirely (2).sql'
$patchSql = Join-Path $PSScriptRoot 'onboarding_schema_patch.sql'
$mysql = (Get-Command mysql -ErrorAction SilentlyContinue).Source

if (-not $mysql) {
    $xamppMysql = 'C:\xampp\mysql\bin\mysql.exe'
    if (Test-Path $xamppMysql) {
        $mysql = $xamppMysql
    }
}

if (-not $mysql) {
    throw 'mysql.exe was not found. Start XAMPP/MySQL and make sure mysql is available.'
}

if (-not (Test-Path $baseSql)) {
    throw "Base SQL dump not found: $baseSql"
}

& $mysql -uroot -e 'DROP DATABASE IF EXISTS hirely; CREATE DATABASE hirely CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
Get-Content -LiteralPath $baseSql -Raw | & $mysql --force -uroot hirely
Get-Content -LiteralPath $patchSql -Raw | & $mysql -uroot hirely

Write-Host 'hirely database imported and onboarding schema patched.'
