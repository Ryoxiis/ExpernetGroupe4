param([int]$Port = 8093)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
Set-Location -LiteralPath $projectRoot
$databaseProcess = $null
$php = (Get-Command php -ErrorAction Stop).Source
$phpOptions = @()
$modules = & $php -m
if ($modules -notcontains 'sodium') { $phpOptions = @('-d', 'extension=sodium') }

# La même vérification est utilisée après chaque commande PHP importante.
function Assert-PhpSuccess {
    if ($LASTEXITCODE -ne 0) { throw 'La commande PHP a échoué. Consultez le message ci-dessus.' }
}
function Test-DatabasePort {
    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $connection = $client.ConnectAsync('127.0.0.1', 33306)
        if (-not $connection.Wait(500)) { return $false }
        return $client.Connected
    } catch { return $false }
    finally { $client.Dispose() }
}

try {
    & $php @phpOptions bin/check-local.php
    Assert-PhpSuccess
    if ($Port -lt 1024 -or $Port -gt 65535) { throw 'Choisissez un port entre 1024 et 65535.' }
    $databaseRunning = Test-DatabasePort
    if ($databaseRunning -and -not (Test-Path -LiteralPath 'storage/runtime/local-mysql.txt')) {
        throw 'Le port 33306 est déjà utilisé par une autre instance. Utilisez le lancement manuel pour conserver sa configuration.'
    }
    if (-not $databaseRunning) {
        $mysql = Get-Command mysqld -ErrorAction SilentlyContinue
        $mysqlPath = if ($mysql) { $mysql.Source } else { 'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqld.exe' }
        if (-not (Test-Path -LiteralPath $mysqlPath)) { throw 'MySQL 8 est nécessaire. Installez-le ou utilisez le lancement manuel décrit dans docs/LOCAL.md.' }
        $mysqlBase = Split-Path (Split-Path $mysqlPath -Parent) -Parent
        $dataPath = Join-Path $projectRoot 'storage/mysql'
        New-Item -ItemType Directory -Force -Path $dataPath, 'storage/runtime' | Out-Null
        $mysqlOptions = @('--no-defaults', "--basedir=`"$mysqlBase`"", "--datadir=`"$dataPath`"")
        if (-not (Test-Path -LiteralPath (Join-Path $dataPath 'mysql'))) {
            & $mysqlPath --no-defaults "--basedir=$mysqlBase" "--datadir=$dataPath" --initialize-insecure --console
            if ($LASTEXITCODE -ne 0) { throw 'Impossible de préparer les fichiers MySQL.' }
        }
        Set-Content -LiteralPath 'storage/runtime/local-mysql.txt' -Value 'Instance MySQL locale Expernet sur le port 33306.'
        $databaseProcess = Start-Process -FilePath $mysqlPath -ArgumentList ($mysqlOptions + @('--port=33306', '--bind-address=127.0.0.1', '--mysqlx=OFF')) -WindowStyle Hidden -PassThru
        for ($attempt = 0; $attempt -lt 30; $attempt++) {
            if (Test-DatabasePort) { break }
            if ($databaseProcess.HasExited) { throw 'MySQL ne démarre pas. Consultez storage/mysql/*.err.' }
            Start-Sleep -Milliseconds 500
        }
        if (-not (Test-DatabasePort)) { throw 'MySQL ne répond pas sur le port 33306.' }
    }
    & $php @phpOptions bin/local-database.php
    Assert-PhpSuccess
    & $php @phpOptions bin/serve-portal.php migrate
    Assert-PhpSuccess
    # Le jeu fictif est créé seulement si la base ne contient aucun compte.
    & $php @phpOptions bin/local-demo.php
    Assert-PhpSuccess
    Write-Host "Ouvrez http://127.0.0.1:$Port/connexion/ et gardez ce terminal ouvert."
    & $php @phpOptions bin/serve-portal.php $Port
    Assert-PhpSuccess
} catch {
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
} finally {
    if ($databaseProcess -and -not $databaseProcess.HasExited) {
        # On arrête uniquement l'instance lancée ici, après l'arrêt du serveur PHP.
        & $php @phpOptions bin/local-database.php stop
    }
}
