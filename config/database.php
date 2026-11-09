<?php
// Configuração da Base de Dados PostgreSQL (Local / Produção)

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'financia_db';
$user = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASS') ?: '878175';

try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
    
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    // Em produção ou desenvolvimento, exibe a mensagem de erro com segurança
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}