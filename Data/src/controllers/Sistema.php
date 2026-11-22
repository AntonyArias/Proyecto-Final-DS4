<?php
namespace App\controllers;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
class Sistema {
    public function health(Request $request, Response $response): Response {
        DB::table('roles')->count();
        $response->getBody()->write(json_encode(['status' => 'ok', 'data' => 'ok', 'database' => 'ok']));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
