$ErrorActionPreference = 'Stop'
function NuevaClave {
    $buffer = New-Object byte[] 32
    $rng = [Security.Cryptography.RandomNumberGenerator]::Create()
    try { $rng.GetBytes($buffer) } finally { $rng.Dispose() }
    return ([BitConverter]::ToString($buffer)).Replace('-', '').ToLowerInvariant()
}
$paths = @('Business/.env','Data/.env','db_password.txt')
foreach ($path in $paths) { if (Test-Path -LiteralPath (Join-Path $PSScriptRoot $path)) { throw 'La configuración local ya existe. No se reemplazaron claves ni contraseñas.' } }
$key = NuevaClave
$internal = NuevaClave
$password = NuevaClave
$business = [IO.File]::ReadAllText((Join-Path $PSScriptRoot 'Business/.env.example'))
foreach ($entry in @{KEY=$key; INTERNAL_KEY=$internal; JWT_REFRESH_KEY=(NuevaClave)}.GetEnumerator()) {
    $business = [regex]::Replace($business, ('(?m)^' + $entry.Key + '=.*$'), ($entry.Key + '=' + $entry.Value))
}
$data = [IO.File]::ReadAllText((Join-Path $PSScriptRoot 'Data/.env.example'))
foreach ($entry in @{KEY=$key; INTERNAL_KEY=$internal; DB_PASSWORD=$password}.GetEnumerator()) {
    $data = [regex]::Replace($data, ('(?m)^' + $entry.Key + '=.*$'), ($entry.Key + '=' + $entry.Value))
}
[IO.File]::WriteAllText((Join-Path $PSScriptRoot 'Business/.env'), $business, [Text.UTF8Encoding]::new($false))
[IO.File]::WriteAllText((Join-Path $PSScriptRoot 'Data/.env'), $data, [Text.UTF8Encoding]::new($false))
[IO.File]::WriteAllText((Join-Path $PSScriptRoot 'db_password.txt'), $password, [Text.UTF8Encoding]::new($false))
Write-Output 'Configuración local creada; no se copiaron secretos de CitasMedicas.'
