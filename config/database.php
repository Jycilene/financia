<?php
// Configuração de Conexão com o Banco de Dados PostgreSQL (Local XAMPP e Produção Render/Supabase)

$host     = getenv('DB_HOST') ?: '127.0.0.1';
$port     = getenv('DB_PORT') ?: '5432';
$dbname   = getenv('DB_NAME') ?: 'financia_db';
$user     = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '878175';

// Se o host não for local, exige conexão segura com SSL (exigência do Supabase)
$sslmode  = (!in_array($host, ['localhost', '127.0.0.1', '::1'])) ? ';sslmode=require' : '';

try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}{$sslmode};";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}
