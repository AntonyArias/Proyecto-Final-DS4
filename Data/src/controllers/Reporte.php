<?php
namespace App\controllers;
use App\Repositories\ReporteRepository;
use App\Services\AutorizacionService;
use App\Services\ValidacionService as v;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Reporte {
    public function __construct(private ContainerInterface $container) {}
    public function ingresos(Request $request, Response $response): Response {
        AutorizacionService::roles($request->getAttribute('usuario'), 1, 3);
        return $this->pagina($response, (new ReporteRepository())->ingresos($request->getAttribute('usuario'), $request->getQueryParams()), $request->getQueryParams());
    }
    public function estados(Request $request, Response $response): Response {
        return $this->pagina($response, (new ReporteRepository())->estados($request->getAttribute('usuario'), $request->getQueryParams()), $request->getQueryParams());
    }
    public function visitas(Request $request, Response $response): Response {
        return $this->pagina($response, (new ReporteRepository())->visitas($request->getAttribute('usuario'), $request->getQueryParams()), $request->getQueryParams());
    }
    public function mapa(Request $request, Response $response): Response {
        return $this->pagina($response, (new ReporteRepository())->mapa($request->getAttribute('usuario'), $request->getQueryParams()), $request->getQueryParams());
    }
    private function pagina(Response $response, $query, array $params): Response {
        [$offset, $limit] = v::pagina($params);
        $total = DB::query()->fromSub((clone $query)->reorder(), 'grupos')->count();
        $data = array_map(fn($fila) => (array) $fila, $query->offset($offset)->limit($limit)->get()->all());
        $response->getBody()->write(json_encode(v::salida(['data' => $data, 'pagination' => compact('offset', 'limit', 'total')]), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
