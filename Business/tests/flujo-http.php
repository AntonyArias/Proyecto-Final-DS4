<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use GuzzleHttp\Client;

// Ejecuta el archivo REAL de REST Client dos veces; crea únicamente datos ficticios.
// Uso en el contenedor Business:
// php tests/flujo-http.php /tmp/RestRequest/flujo.http http://localhost/api
// Se usa una base aislada: cada recorrido deja su proyecto e historial comercial.
$file = $argv[1] ?? dirname(__DIR__) . '/RestRequest/flujo.http';
$host = $argv[2] ?? 'http://localhost/api';
if (!is_file($file)) throw new RuntimeException('No se encontró flujo.http.');
$source = str_replace("\r\n", "\n", file_get_contents($file));
preg_match_all('/^@(\w+)\s*=\s*(.+)$/m', $source, $definitions, PREG_SET_ORDER);
$variables = [];
foreach ($definitions as $definition) $variables[$definition[1]] = trim($definition[2]);
$variables['host'] = rtrim($host, '/');
$blocks = preg_split('/^### /m', $source);
$http = new Client(['http_errors' => false, 'timeout' => 25]);
$contract = json_decode(file_get_contents(dirname(__DIR__) . '/public/openapi.json'), true, 512, JSON_THROW_ON_ERROR);
$covered = [];
$checks = 0;

for ($cycle = 1; $cycle <= 2; $cycle++) {
    $responses = [];
    $resolve = function(string $value, int $depth = 0) use (&$resolve, &$responses, $variables): string {
        if ($depth > 10) throw new RuntimeException('Referencia circular en las variables.');
        return preg_replace_callback('/\{\{(.*?)\}\}/', function(array $match) use (&$resolve, &$responses, $variables, $depth): string {
            $key = trim($match[1]);
            if ($key === '$guid') {
                $bytes = random_bytes(16);
                $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
                $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
                return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
            }
            if ($key === '$timestamp') return (string) time();
            if (preg_match('/^\$randomInt (\d+) (\d+)$/', $key, $range)) return (string) random_int((int) $range[1], (int) $range[2] - 1);
            if (isset($variables[$key])) return $resolve($variables[$key], $depth + 1);
            if (preg_match('/^(\w+)\.response\.body\.\$\.(.+)$/', $key, $reference)) {
                if (!isset($responses[$reference[1]])) throw new RuntimeException('Falta ejecutar: ' . $reference[1]);
                $result = $responses[$reference[1]];
                $path = preg_replace('/\[(\d+)\]/', '.$1', $reference[2]);
                foreach (explode('.', $path) as $part) {
                    if (!is_array($result) || !array_key_exists($part, $result)) throw new RuntimeException('Referencia sin valor: ' . $key);
                    $result = $result[$part];
                }
                if (!is_scalar($result)) throw new RuntimeException('Se esperaba un valor escalar: ' . $key);
                return (string) $result;
            }
            throw new RuntimeException('Variable desconocida: ' . $key);
        }, $value);
    };
    $number = 0;
    foreach ($blocks as $block) {
        if (!preg_match('/^(GET|POST|PUT|DELETE) (.+)$/m', $block, $request, PREG_OFFSET_CAPTURE)) continue;
        $number++;
        $method = $request[1][0];
        $url = $resolve(trim($request[2][0]));
        preg_match('/^# Esperado: (\d+)$/m', $block, $expected);
        if (!$expected) throw new RuntimeException('Falta el estado esperado.');
        preg_match('/^# @name (\w+)$/m', $block, $name);
        $remaining = substr($block, $request[0][1] + strlen($request[0][0]) + 1);
        [$headerText, $body] = array_pad(explode("\n\n", $remaining, 2), 2, '');
        $headers = [];
        foreach (explode("\n", trim($headerText)) as $line) {
            if ($line === '') continue;
            [$key, $value] = explode(':', $line, 2);
            $headers[$key] = $resolve(trim($value));
        }
        $body = $resolve(rtrim($body, "\n"));
        $contentType = $headers['Content-Type'] ?? '';
        if (str_starts_with($contentType, 'multipart/form-data')) {
            // REST Client sustituye las líneas < archivo por sus bytes literales.
            $body = str_replace("\n", "\r\n", $body) . "\r\n";
            $body = preg_replace_callback('/^< (.+)\r$/m', function(array $upload) use ($file): string {
                $path = dirname($file) . '/' . trim($upload[1]);
                if (!is_file($path)) throw new RuntimeException('No se encontró la foto de prueba.');
                return file_get_contents($path) . "\r";
            }, $body);
        } elseif ($contentType === 'application/json') {
            json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }
        $response = $http->request($method, $url, ['headers' => $headers, 'body' => $body]);
        $raw = (string) $response->getBody();
        $json = str_starts_with($response->getHeaderLine('Content-Type'), 'application/json') ? json_decode($raw, true, 512, JSON_THROW_ON_ERROR) : [];
        $path = substr(explode('?', $url)[0], strlen(rtrim($host, '/')));
        if ($response->getStatusCode() !== (int) $expected[1]) {
            throw new RuntimeException("Recorrido $cycle, solicitud $number: $method $path; esperado {$expected[1]}, recibido " . $response->getStatusCode() . '. ' . ($json['error'] ?? 'Respuesta inesperada.'));
        }
        if ($name) $responses[$name[1]] = $json;
        foreach ($contract['paths'] as $template => $operations) {
            $pattern = '~^' . preg_replace('/\\\{[^}]+\\\}/', '[^/]+', preg_quote($template, '~')) . '$~';
            if (isset($operations[strtolower($method)]) && preg_match($pattern, $path)) $covered[$method . ' ' . $template] = true;
        }
        if (($name[1] ?? '') === 'reintentoVisita' || ($name[1] ?? '') === 'reintentoFoto') {
            if (($json['repetido'] ?? null) !== true) throw new RuntimeException('El reintento duplicó el registro.');
        }
        if (($name[1] ?? '') === 'descargaFoto' && $raw !== file_get_contents(dirname($file) . '/archivos/foto-prueba.png')) throw new RuntimeException('La fotografía descargada difiere del archivo enviado.');
        if (($name[1] ?? '') === 'ingresos' && (count($json['data']) !== 1 || $json['data'][0]['total_recibido'] !== '150000.00' || (int) $json['data'][0]['cantidad_pagos'] !== 2)) throw new RuntimeException('Los ingresos deben ser 150000 CRC en dos pagos recibidos.');
        $checks++;
    }
    echo "OK: recorrido $cycle, $number solicitudes con sus estados esperados.\n";
}
$missing = [];
foreach ($contract['paths'] as $path => $operations) {
    foreach ($operations as $method => $operation) {
        if (in_array($method, ['get', 'post', 'put', 'delete'], true) && !isset($covered[strtoupper($method) . ' ' . $path])) $missing[] = strtoupper($method) . ' ' . $path;
    }
}
if ($missing) throw new RuntimeException('Operaciones sin cubrir: ' . implode(', ', $missing));
echo 'OK: ' . count($covered) . " operaciones del contrato cubiertas; $checks respuestas correctas; reintentos, fotografía e ingresos verificados.\n";
