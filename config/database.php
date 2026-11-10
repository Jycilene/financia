<?php
// Configuração da Base de Dados PostgreSQL (Supabase / Render)

$host = getenv('DB_HOST') ?: 'db.fagcwofynoxtplwhpvaz.supabase.co';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'postgres';
$user = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASS') ?: '87817533Ra.'; // Insira a sua senha do Supabase

try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
    
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}