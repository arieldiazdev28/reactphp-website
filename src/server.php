<?php

require __DIR__ . '/../vendor/autoload.php';

use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\MySQL\Factory as MySQLFactory;
//use Clue\React\MySQL\Factory as MySQLFactory;

$loop = Loop::get();

// Conexión a la base de datos
$mysqlFactory = new MySQLFactory($loop);
//$db = $mysqlFactory->createLazyConnection('user:password@localhost/tiendareactphp'); // Cambia user y password
//$db = $mysqlFactory->createLazyConnection('root:@localhost/tiendareactphp');
$db = $mysqlFactory->createLazyConnection('reactphp:@localhost/tiendareactphp');


$db->query('SELECT 1')->then(function () {
    echo "✅ Conexión a la base de datos exitosa\n";
}, function (Exception $e) {
    echo "❌ Error de conexión a la base de datos: " . $e->getMessage() . "\n";
});

$headers = [
    'Access-Control-Allow-Origin' => '*',
    'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE',
    'Access-Control-Allow-Headers' => 'Content-Type',
];

$server = new HttpServer(function (ServerRequestInterface $request) use ($db, $headers) {
    $path = $request->getUri()->getPath();
    $method = $request->getMethod();

    if ($method === 'OPTIONS') {
        return new Response(200, $headers);
    }

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
            if ($method === 'GET')
            {   return handleRead($db, $headers); }
            if ($method === 'POST')
            {   return handleCreate($request, $db, $headers); }
            break;

        case '/data/update':
            if ($method === 'PUT')
            {   return handleUpdate($request, $db, $headers); }
            break;

        case '/data/delete':
            if ($method === 'DELETE')
            {   return handleDelete($request, $db, $headers); }
            break;

        default:
            return new Response(404, ['Content-Type' => 'text/plain'] + $headers, '404 - Página no encontrada');
    }
});

// ======================= Funciones =======================

function handleRead($db, $headers)
{
    return $db->query('SELECT * FROM productos')->then(function ($result) use ($headers) {
        return new Response(200, $headers, json_encode($result->resultRows));
    });
}

function handleCreate($request, $db, $headers)
{
    $data = json_decode($request->getBody()->getContents(), true);
    if (!isset($data['nombre'], $data['precio'])) {
        return new Response(400, $headers, 'Nombre y precio requeridos');
    }

    $nombre = $data['nombre'];
    $precio = $data['precio'];

    return $db->query('INSERT INTO productos (nombre, precio) VALUES (?, ?)', [$nombre, $precio])
        ->then(function () use ($headers, $nombre, $precio) {
            return new Response(201, $headers, json_encode(['nombre' => $nombre, 'precio' => $precio]));
        });
}

function handleUpdate($request, $db, $headers)
{
    $data = json_decode($request->getBody()->getContents(), true);
    if (!isset($data['id'], $data['nombre'], $data['precio'])) {
        return new Response(400, $headers, 'ID, nombre y precio requeridos');
    }

    return $db->query(
        'UPDATE productos SET nombre = ?, precio = ? WHERE id = ?',
        [$data['nombre'], $data['precio'], $data['id']]
    )->then(function () use ($headers, $data) {
        return new Response(200, $headers, json_encode($data));
    });
}

function handleDelete($request, $db, $headers)
{
    $data = json_decode($request->getBody()->getContents(), true);
    if (!isset($data['id'])) {
        return new Response(400, $headers, 'ID requerido');
    }

    return $db->query('DELETE FROM productos WHERE id = ?', [$data['id']])
        ->then(function () use ($headers) {
            return new Response(200, $headers, 'Producto eliminado');
        });
}

// ======================= Iniciar servidor =======================

$socket = new SocketServer("0.0.0.0:8080", [], $loop);
$server->listen($socket);

echo "✅ Servidor corriendo en http://localhost:8080\n";
