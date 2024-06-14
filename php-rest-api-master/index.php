<?php

header('Content-Type: application/json; charset=utf-8');

class BibliotecaAPI {
    private $pdo;

    public function __construct() {
        $host = 'localhost';
        $db = 'biblioteca';
        $user = 'root';  // Cambia esto si tu usuario es diferente
        $pass = '';  // Cambia esto si tienes una contraseña
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function getBooks() {
        $stmt = $this->pdo->query('SELECT * FROM libros');
        echo json_encode($stmt->fetchAll());
    }

    public function getBook($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM libros WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode($stmt->fetch());
    }

    public function createBook() {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $this->pdo->prepare('INSERT INTO libros (titulo, autor, num_paginas, editorial, categoria) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$data['titulo'], $data['autor'], $data['num_paginas'], $data['editorial'], $data['categoria']]);
        echo json_encode(['id' => $this->pdo->lastInsertId()]);
    }

    public function updateBook($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $this->pdo->prepare('UPDATE libros SET titulo = ?, autor = ?, num_paginas = ?, editorial = ?, categoria = ? WHERE id = ?');
        $stmt->execute([$data['titulo'], $data['autor'], $data['num_paginas'], $data['editorial'], $data['categoria'], $id]);
        echo json_encode(['status' => 'success']);
    }

    public function deleteBook($id) {
        $stmt = $this->pdo->prepare('DELETE FROM libros WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success']);
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
        $resource = $path[1];
        if ($resource != 'libros') {
            http_response_code(404);
            echo json_encode(['error' => 'Recurso no encontrado']);
            return;
        }

        $id = isset($path[2])? $path[2] : false;
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getBook($id);
                } else {
                    $this->getBooks();
                }
                break;
            case 'POST':
                $this->createBook();
                break;
            case 'PUT':
                if ($id) {
                    $this->updateBook($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID de libro no proporcionado']);
                }
                break;
            case 'DELETE':
                if ($id) {
                    $this->deleteBook($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID de libro no proporcionado']);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido']);
        }
    }
}

$api = new BibliotecaAPI();
$api->handleRequest();
?>

