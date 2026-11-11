<?php
require_once __DIR__ . '/config.php';

// Em desenvolvimento (Laragon/XAMPP) valem os valores abaixo: usuário root, senha vazia.
// Em produção, defina as variáveis de ambiente GOA_DB_HOST, GOA_DB_NAME, GOA_DB_USER e GOA_DB_PASS
// no servidor (assim a senha real nunca vai para o GitHub).
$host    = getenv('GOA_DB_HOST') ?: 'localhost';
$db      = getenv('GOA_DB_NAME') ?: 'goa_reservas';
$user    = getenv('GOA_DB_USER') ?: 'root';
$pass    = getenv('GOA_DB_PASS') !== false ? getenv('GOA_DB_PASS') : '';
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