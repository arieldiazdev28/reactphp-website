<?php

require __DIR__ . '/../vendor/autoload.php';

use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;

$loop = Loop::get();
$productosPath = __DIR__ . '/../data/productos.json'; // Ruta del archivo JSON

$server = new HttpServer(function (ServerRequestInterface $request) use ($productosPath) {
    $path = $request->getUri()->getPath();
    $method = $request->getMethod();

    // Agregar encabezados CORS
    $headers = [
        'Access-Control-Allow-Origin' => '*', // Permitir solicitudes de cualquier origen
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE', // Métodos permitidos
        'Access-Control-Allow-Headers' => 'Content-Type', // Encabezados permitidos
    ];

    // Manejar la solicitud OPTIONS
    if ($method === 'OPTIONS') {
        return new Response(200, $headers);
    }

    // Manejar otras rutas
    switch ($path) {
        case '/':
            return new Response(
                200,
                ['Content-Type' => 'text/html'] + $headers,
                file_get_contents(__DIR__ . '/../public/index.html')
            );
        case '/contact':
            return new Response(
                200,
                ['Content-Type' => 'text/html'] + $headers,
                file_get_contents(__DIR__ . '/../public/contact.html')
            );
        case '/administrarproductos':
            return new Response(
                200,
                ['Content-Type' => 'text/html'] + $headers,
                file_get_contents(__DIR__ . '/../public/administrar_productos.html')
            );
        case '/style.css':
            return new Response(
                200,
                ['Content-Type' => 'text/css'] + $headers,
                file_get_contents(__DIR__ . '/../public/style.css')
            );
        case '/data':
            if ($method === 'GET') {
                return handleRead($productosPath, $headers);
            }
            if ($method === 'POST') {
                return handleCreate($request, $productosPath, $headers);
            }
            break;
        case '/data/update':
            if ($method === 'PUT') {
                return handleUpdate($request, $productosPath, $headers);
            }
            break;
        case '/data/delete':
            if ($method === 'DELETE') {
                return handleDelete($request, $productosPath, $headers);
            }
            break;
        default:
            return new Response(
                404,
                ['Content-Type' => 'text/plain'] + $headers,
                '404 - Página no encontrada'
            );
    }
});

function handleRead($path, $headers)
{
    if (!file_exists($path)) {
        return new Response(500, $headers, 'Error cargando datos');
    }

    $data = file_get_contents($path);
    return new Response(200, $headers, $data);
}

function handleCreate($request, $path, $headers)
{
    $data = json_decode($request->getBody()->getContents(), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return new Response(400, $headers, 'Datos inválidos');
    }

    if (!isset($data['nombre'], $data['precio'])) {
        return new Response(400, $headers, 'Nombre y precio son requeridos');
    }

    // Obtener productos existentes
    $productos = json_decode(file_get_contents($path), true);
    $newId = count($productos) > 0 ? max(array_column($productos, 'id')) + 1 : 1; // Asignar un nuevo ID
    $data['id'] = $newId;
    $productos[] = $data;

    // Guardar de nuevo en el archivo
    file_put_contents($path, json_encode($productos, JSON_PRETTY_PRINT));

    return new Response(201, $headers, json_encode($data));
}

function handleUpdate($request, $path, $headers)
{
    $data = json_decode($request->getBody()->getContents(), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return new Response(400, $headers, 'Datos inválidos');
    }

    if (!isset($data['id'], $data['nombre'], $data['precio'])) {
        return new Response(400, $headers, 'ID, nombre y precio son requeridos');
    }

    // Obtener productos existentes
    $productos = json_decode(file_get_contents($path), true);
    foreach ($productos as &$producto) {
        if ($producto['id'] == $data['id']) {
            $producto['nombre'] = $data['nombre'];
            $producto['precio'] = $data['precio'];
            file_put_contents($path, json_encode($productos, JSON_PRETTY_PRINT));
            return new Response(200, $headers, json_encode($producto));
        }
    }

    return new Response(404, $headers, 'Producto no encontrado');
}

function handleDelete($request, $path, $headers)
{
    $data = json_decode($request->getBody()->getContents(), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return new Response(400, $headers, 'Datos inválidos');
    }

    if (!isset($data['id'])) {
        return new Response(400, $headers, 'ID es requerido');
    }

    // Obtener productos existentes
    $productos = json_decode(file_get_contents($path), true);
    $productos = array_filter($productos, function ($producto) use ($data) {
        return $producto['id'] != $data['id'];
    });
    $productos = array_values($productos); // Reindexar el array

    // Guardar de nuevo en el archivo
    file_put_contents($path, json_encode($productos, JSON_PRETTY_PRINT));

    return new Response(200, $headers, 'Producto eliminado');
}


$socket = new SocketServer("0.0.0.0:8080", [], $loop);
$server->listen($socket);

echo "Servidor corriendo en http://0.0.0.0:8080\n";
