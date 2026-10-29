<?php
require_once __DIR__ . '/config.php';

$host    = 'localhost';
$db      = 'goa_reservas';
$user    = 'root';
$pass    = '';          // Laragon e XAMPP: senha vazia (só em ambiente local)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // erros viram exceções
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // linhas como array associativo
    PDO::ATTR_EMULATE_PREPARES   => false,                  // prepared statements reais
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Erro ao conectar ao banco de dados.');
}
