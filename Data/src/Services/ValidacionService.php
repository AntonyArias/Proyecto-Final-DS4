<?php
declare(strict_types=1);
namespace App\Services;

final class ValidacionService {
    public static function salida(array $data): array {
        foreach ($data as $campo => &$valor) {
            if (is_object($valor)) $valor = self::salida((array) $valor);
            elseif (is_array($valor)) $valor = self::salida($valor);
            elseif (is_string($campo) && str_starts_with($campo, 'fecha_') && is_string($valor)
                && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $valor)) $valor = str_replace(' ', 'T', $valor) . 'Z';
        }
        return $data;
    }
    public static function id(mixed $value): int {
        if (is_bool($value)) throw new \InvalidArgumentException('Identificador entero positivo requerido.', 422);
        $id = filter_var($value, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) throw new \InvalidArgumentException('Identificador entero positivo requerido.', 422);
        return $id;
    }
    public static function texto(array $data, string $campo, int $max = 150, bool $requerido = true): ?string {
        $valor = $data[$campo] ?? null;
        if ($valor === null && !$requerido) return null;
        if (!is_string($valor) || ($requerido && trim($valor) === '') || mb_strlen(trim($valor)) > $max) {
            throw new \InvalidArgumentException('Campo inválido: ' . $campo, 422);
        }
        return trim($valor);
    }
    public static function fecha(mixed $valor): string {
        if (!is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/D', $valor)) {
            throw new \InvalidArgumentException('Use una fecha ISO 8601 con segundos y zona horaria.', 422);
        }
        try {
            $fecha = new \DateTimeImmutable($valor);
            $errores = \DateTimeImmutable::getLastErrors();
            if ($errores && ($errores['warning_count'] || $errores['error_count'])) throw new \Exception();
            return $fecha->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } catch (\Exception) { throw new \InvalidArgumentException('Fecha inválida.', 422); }
    }
    public static function uuid(mixed $valor): string {
        if (!is_string($valor) || !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/iD', $valor)) {
            throw new \InvalidArgumentException('UUID inválido.', 422);
        }
        return strtolower($valor);
    }
    public static function centimos(mixed $valor): int {
        if (!is_string($valor) || !preg_match('/^(0|[1-9]\d{0,9})(?:\.(\d{1,2}))?$/D', $valor, $partes)) {
            throw new \InvalidArgumentException('Envíe el monto como texto decimal positivo con máximo dos decimales.', 422);
        }
        return ((int) $partes[1]) * 100 + (int) str_pad($partes[2] ?? '', 2, '0');
    }
    public static function monto(int $centimos): string {
        return intdiv($centimos, 100) . '.' . str_pad((string) ($centimos % 100), 2, '0', STR_PAD_LEFT);
    }
    public static function pagina(array $args): array {
        $offset = filter_var($args['offset'] ?? 0, FILTER_VALIDATE_INT);
        $limit = filter_var($args['limit'] ?? 20, FILTER_VALIDATE_INT);
        if ($offset === false || $offset < 0 || $limit === false || $limit < 1 || $limit > 100) throw new \InvalidArgumentException('offset >= 0 y limit entre 1 y 100 requeridos.', 422);
        return [$offset, $limit];
    }
}
