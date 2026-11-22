<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Services\ValidacionService as v;
use App\Services\ProyectoService;
use App\Services\PagoService;
use App\Services\VisitaService;
use App\Services\AutorizacionService;

$total = 0;
function comprobar(bool $valor, string $message): void { global $total; if (!$valor) throw new RuntimeException($message); $total++; }
function rechaza(callable $fn, int $status): void {
    try { $fn(); } catch (RuntimeException | InvalidArgumentException $error) { comprobar($error->getCode() === $status, $error->getMessage()); return; }
    throw new RuntimeException('Se esperaba rechazo ' . $status);
}
comprobar(v::centimos('0.10') + v::centimos('0.20') === 30, 'Dinero exacto.');
comprobar(v::monto(123456) === '1234.56', 'Formato decimal.');
foreach (['-1', '1e3', '1.234', 'NaN', 1.5, '10000000000'] as $bad) rechaza(fn() => v::centimos($bad), 422);
foreach (ProyectoService::TRANSICIONES as $from => $to) { ProyectoService::validarEstado($from, $to); comprobar(true, 'Transición válida.'); }
rechaza(fn() => ProyectoService::validarEstado('Aprobada', 'Facturada'), 409);
rechaza(fn() => ProyectoService::validarEstado('Facturada', 'En campo'), 409);
rechaza(fn() => PagoService::validarTotal(30, 80, 100), 409);
rechaza(fn() => PagoService::validarTotal(0, 0, 100), 409);
PagoService::validarTotal(30, 70, 100); comprobar(true, 'Saldo exacto.');
rechaza(fn() => AutorizacionService::roles(['rol_id' => 2], 1), 403);
AutorizacionService::roles(['rol_id' => 3], 1, 3); comprobar(true, 'Cliente permitido.');
comprobar(v::fecha('2026-11-22T08:30:00-06:00') === '2026-11-22 14:30:00', 'Zona UTC.');
comprobar(v::fecha('2026-11-22T08:30:00.123-06:00') === '2026-11-22 14:30:00', 'Milisegundos de Android normalizados a segundos.');
rechaza(fn() => v::id(true), 422);
rechaza(fn() => v::fecha('2026-02-30T00:00:00Z'), 422);
rechaza(fn() => v::fecha('2026-11-22'), 422);
rechaza(fn() => v::pagina(['limit' => 101]), 422);
$data = ['uuid' => 'c3c5263c-3abc-4dbf-a8ab-281b00f6c621', 'proyecto_id' => 1, 'fecha_captura' => '2026-11-22T08:30:00-06:00',
    'observaciones' => 'Muestra de árboles', 'latitud' => 9.9, 'longitud' => -84.1];
$a = VisitaService::normalizar($data);
$b = VisitaService::normalizar(array_replace($data, ['fecha_captura' => '2026-11-22T14:30:00Z', 'latitud' => '9.9000000']));
comprobar(VisitaService::huella($a) === VisitaService::huella($b), 'UUID y contenido equivalentes.');
comprobar(VisitaService::huella($a) !== VisitaService::huella(array_replace($a, ['observaciones' => 'Otro contenido'])), 'Contenido diferente.');
rechaza(fn() => VisitaService::normalizar(array_replace($data, ['latitud' => 91])), 422);
rechaza(fn() => VisitaService::normalizar(array_replace($data, ['longitud' => true])), 422);
rechaza(fn() => v::uuid('otro'), 422);
echo "OK: $total comprobaciones unitarias de servicios.\n";
