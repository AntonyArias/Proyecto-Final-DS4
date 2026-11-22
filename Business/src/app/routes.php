<?php
namespace App\controllers;
use Slim\Routing\RouteCollectorProxy;

$app->group('/api', function(RouteCollectorProxy $api) {
    $api->get('/health', Sistema::class . ':health');
    $api->get('/docs', Sistema::class . ':docs');
    $api->get('/openapi.json', Sistema::class . ':openapi');
    $api->group('/auth', function(RouteCollectorProxy $endpoint) {
        $endpoint->post('/login', Auth::class . ':login');
        $endpoint->post('/refresh', Auth::class . ':refresh');
        $endpoint->get('/me', Auth::class . ':read');
        $endpoint->delete('/logout/{id}', Auth::class . ':logout');
    });
    $api->group('/public', function(RouteCollectorProxy $endpoint) {
        $endpoint->get('/servicio', Servicio::class . ':publicRead');
        $endpoint->post('/solicitud', Solicitud::class . ':create');
    });
    $api->group('/usuario', function(RouteCollectorProxy $endpoint) {
        $endpoint->get('[/{id}]', Usuario::class . ':read');
        $endpoint->get('/filter/{offset}/{limit}', Usuario::class . ':filter');
        $endpoint->post('', Usuario::class . ':create');
        $endpoint->put('/{id}', Usuario::class . ':update');
        $endpoint->delete('/{id}', Usuario::class . ':delete');
    });
    $api->group('/cliente', function(RouteCollectorProxy $endpoint) {
        $endpoint->get('[/{id}]', Cliente::class . ':read');
        $endpoint->get('/filter/{offset}/{limit}', Cliente::class . ':filter');
        $endpoint->post('', Cliente::class . ':create');
        $endpoint->put('/{id}', Cliente::class . ':update');
        $endpoint->delete('/{id}', Cliente::class . ':delete');
    });
    $api->group('/servicio', function(RouteCollectorProxy $endpoint) {
        $endpoint->get('[/{id}]', Servicio::class . ':read');
        $endpoint->get('/filter/{offset}/{limit}', Servicio::class . ':filter');
        $endpoint->post('', Servicio::class . ':create');
        $endpoint->put('/{id}', Servicio::class . ':update');
        $endpoint->delete('/{id}', Servicio::class . ':delete');
    });
    $api->group('/solicitud', function(RouteCollectorProxy $endpoint) {
        $endpoint->get('[/{id}]', Solicitud::class . ':read');
        $endpoint->get('/filter/{offset}/{limit}', Solicitud::class . ':filter');
    });
    $api->group('/cotizacion', function(RouteCollectorProxy $endpoint) {
        $endpoint->get('[/{id}]', Cotizacion::class . ':read');
        $endpoint->get('/filter/{offset}/{limit}', Cotizacion::class . ':filter');
        $endpoint->post('', Cotizacion::class . ':create');
        $endpoint->put('/{id}', Cotizacion::class . ':update');
        $endpoint->delete('/{id}', Cotizacion::class . ':delete');
        $endpoint->post('/{id}/estado', Cotizacion::class . ':changeState');
    });
    $api->group('/proyecto', function(RouteCollectorProxy $endpoint) {
        $endpoint->get('[/{id}]', Proyecto::class . ':read');
        $endpoint->get('/filter/{offset}/{limit}', Proyecto::class . ':filter');
        $endpoint->post('/{id}/estado', Proyecto::class . ':changeState');
        $endpoint->put('/{id}', Proyecto::class . ':update');
        $endpoint->post('/{id}/tecnico', Proyecto::class . ':assign');
    });
    $api->group('/pago', function(RouteCollectorProxy $endpoint) {
        $endpoint->get('[/{id}]', Pago::class . ':read');
        $endpoint->get('/filter/{offset}/{limit}', Pago::class . ':filter');
        $endpoint->post('', Pago::class . ':create');
        $endpoint->put('/{id}', Pago::class . ':update');
        $endpoint->delete('/{id}', Pago::class . ':delete');
    });
    $api->group('/visita', function(RouteCollectorProxy $endpoint) {
        $endpoint->get('[/{id}]', Visita::class . ':read');
        $endpoint->get('/filter/{offset}/{limit}', Visita::class . ':filter');
        $endpoint->post('', Visita::class . ':create');
        $endpoint->get('/uuid/{uuid}', Visita::class . ':readUuid');
        $endpoint->post('/{id}/evidencia', Evidencia::class . ':create');
    });
    $api->get('/evidencia/{id}', Evidencia::class . ':read');
    $api->group('/reporte', function(RouteCollectorProxy $endpoint) {
        $endpoint->get('/ingresos', Reporte::class . ':ingresos');
        $endpoint->get('/proyectos-estado', Reporte::class . ':estados');
        $endpoint->get('/visitas', Reporte::class . ':visitas');
        $endpoint->get('/mapa', Reporte::class . ':mapa');
    });
});
