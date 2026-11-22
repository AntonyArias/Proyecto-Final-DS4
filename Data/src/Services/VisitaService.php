<?php
namespace App\Services;
use App\Models\Visita;
use App\Models\Evidencia;
use App\Repositories\ProyectoRepository;
use App\Services\ValidacionService as v;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\UploadedFileInterface;

class VisitaService {
    public static function normalizar(array $data): array {
        $result = ['uuid' => v::uuid($data['uuid'] ?? null), 'proyecto_id' => v::id($data['proyecto_id'] ?? null),
            'fecha_captura' => v::fecha($data['fecha_captura'] ?? null), 'observaciones' => v::texto($data, 'observaciones', 10000)];
        foreach (['latitud' => 90, 'longitud' => 180] as $campo => $max) {
            $valor = $data[$campo] ?? null;
            if ((!is_int($valor) && !is_float($valor) && !is_string($valor)) || !is_numeric($valor)
                || !is_finite((float) $valor) || abs((float) $valor) > $max) throw new \InvalidArgumentException('Coordenada inválida: ' . $campo, 422);
            $result[$campo] = number_format((float) $valor, 7, '.', '');
        }
        return $result;
    }
    public static function huella(array $data): string {
        $result = [];
        foreach (['uuid', 'proyecto_id', 'fecha_captura', 'observaciones', 'latitud', 'longitud'] as $key) $result[$key] = (string) $data[$key];
        return hash('sha256', json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
    public function sincronizar(array $actor, array $data): array {
        AutorizacionService::roles($actor, 2);
        $data = self::normalizar($data);
        return DB::connection()->transaction(function() use ($actor, $data) {
            (new ProyectoRepository())->find($data['proyecto_id'], $actor, true);
            $visita = Visita::query()->where('uuid', $data['uuid'])->first();
            if ($visita) {
                if ($visita->tecnico_id !== $actor['id'] || !hash_equals($visita->huella, self::huella($data))) throw new \RuntimeException('El UUID ya corresponde a otra visita o contenido.', 409);
                return ['data' => $this->detalle($actor, $visita->id), 'repetido' => true];
            }
            $visita = Visita::create($data + ['tecnico_id' => $actor['id'], 'huella' => self::huella($data)]);
            return ['data' => $this->detalle($actor, $visita->id), 'repetido' => false];
        });
    }
    public function detalle(array $actor, int $id): array {
        $visita = Visita::find($id);
        if (!$visita) throw new \RuntimeException('Visita no encontrada.', 404);
        (new ProyectoRepository())->find($visita->proyecto_id, $actor);
        $data = $visita->toArray();
        $data['evidencias'] = Evidencia::query()->where('visita_id', $id)->orderBy('id')->get()->toArray();
        return $data;
    }
    public static function almacenamiento(): string {
        return dirname(__DIR__, 3) . '/storage';
    }
    public function foto(array $actor, int $id, array $data, ?UploadedFileInterface $file): array {
        AutorizacionService::roles($actor, 2);
        $visita = $this->detalle($actor, $id);
        if ($visita['tecnico_id'] !== $actor['id']) throw new \RuntimeException('Solo el autor puede adjuntar fotos a la visita.', 403);
        $uuid = v::uuid($data['uuid'] ?? null);
        if (!$file || $file->getError() !== UPLOAD_ERR_OK || !$file->getSize() || $file->getSize() > 5242880) throw new \InvalidArgumentException('Foto requerida, máximo 5 MiB.', 422);
        $stream = $file->getStream(); $stream->rewind(); $bytes = $stream->getContents();
        if (strlen($bytes) > 5242880) throw new \InvalidArgumentException('La foto supera 5 MiB.', 422);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $imagen = @getimagesizefromstring($bytes);
        if (!isset($extensiones[$mime]) || !$imagen || $imagen[0] * $imagen[1] > 25000000) throw new \InvalidArgumentException('Foto JPEG, PNG o WebP válida hasta 25 megapíxeles requerida.', 422);
        $sha = hash('sha256', $bytes); $archivoCreado = null;
        try {
            return DB::connection()->transaction(function() use ($actor, $id, $uuid, $visita, $bytes, $sha, $mime, $extensiones, &$archivoCreado) {
                Visita::query()->where('id', $id)->lockForUpdate()->firstOrFail();
                $evidencia = Evidencia::query()->where('visita_id', $id)->where('uuid', $uuid)->first();
                if ($evidencia) {
                    if (!hash_equals($evidencia->sha256, $sha)) throw new \RuntimeException('El UUID de la evidencia tiene otros bytes.', 409);
                    if (!is_file(self::almacenamiento() . '/' . $evidencia->ruta)) throw new \RuntimeException('Evidencia temporalmente no disponible.', 503);
                    return ['data' => $this->detalle($actor, $id), 'repetido' => true];
                }
                if (Evidencia::query()->where('visita_id', $id)->count() >= 5) throw new \InvalidArgumentException('Máximo cinco fotos por visita.', 422);
                $ruta = $visita['uuid'] . '/' . $uuid . '.' . $extensiones[$mime];
                $path = self::almacenamiento() . '/' . $ruta;
                if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0770, true) && !is_dir(dirname($path))) throw new \RuntimeException('No se pudo crear almacenamiento.', 503);
                $temporal = tempnam(dirname($path), 'foto-');
                if ($temporal === false) throw new \RuntimeException('No se pudo preparar la foto.', 503);
                try {
                    if (file_put_contents($temporal, $bytes) !== strlen($bytes) || !rename($temporal, $path)) throw new \RuntimeException('No se pudo guardar la foto.', 503);
                    $archivoCreado = $path;
                    Evidencia::create(['visita_id' => $id, 'uuid' => $uuid, 'nombre' => $uuid . '.' . $extensiones[$mime],
                        'mime' => $mime, 'bytes' => strlen($bytes), 'sha256' => $sha, 'ruta' => $ruta]);
                } finally { if (is_file($temporal)) unlink($temporal); }
                return ['data' => $this->detalle($actor, $id), 'repetido' => false];
            });
        } catch (\Throwable $exception) {
            if ($archivoCreado && is_file($archivoCreado)) unlink($archivoCreado);
            throw $exception;
        }
    }
    public function archivo(array $actor, int $id): array {
        $evidencia = Evidencia::find($id);
        if (!$evidencia) throw new \RuntimeException('Evidencia no encontrada.', 404);
        $this->detalle($actor, $evidencia->visita_id);
        $data = $evidencia->toArray();
        $data['path'] = self::almacenamiento() . '/' . $evidencia->ruta;
        if (!is_file($data['path'])) throw new \RuntimeException('Evidencia temporalmente no disponible.', 503);
        return $data;
    }
}
