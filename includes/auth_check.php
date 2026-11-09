<?php
// includes/auth_check.php
// Centraliza a autenticação, segurança e sincronização de dados do usuário logado

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// 1. Trava de rota autenticada
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Garante a conexão PDO
require_once __DIR__ . '/../config/database.php';

$usuario_id = (int) $_SESSION['usuario_id'];

// 3. Sincroniza sempre os dados mais recentes do PostgreSQL
$stmt_auth_u = $pdo->prepare("SELECT id, nome, email, perfil, foto_perfil, status FROM usuarios WHERE id = :id");
$stmt_auth_u->execute([':id' => $usuario_id]);
$dados_auth_u = $stmt_auth_u->fetch(PDO::FETCH_ASSOC);

if (!$dados_auth_u) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// 4. Atualiza as variáveis de sessão
$_SESSION['usuario_nome'] = $dados_auth_u['nome'];
$_SESSION['usuario_email'] = $dados_auth_u['email'];
$_SESSION['usuario_perfil'] = !empty($dados_auth_u['perfil']) ? $dados_auth_u['perfil'] : 'user';
$_SESSION['usuario_foto'] = $dados_auth_u['foto_perfil'];

// 5. Variáveis utilitárias padronizadas para todas as páginas
$usuario_nome = $_SESSION['usuario_nome'];
$usuario_perfil = $_SESSION['usuario_perfil'];
$eh_admin = ($usuario_perfil === 'admin');

// Valida existência física da foto
$foto_usuario = null;
if (!empty($dados_auth_u['foto_perfil'])) {
    $caminho_fisico = __DIR__ . '/../' . $dados_auth_u['foto_perfil'];
    if (file_exists($caminho_fisico)) {
        $foto_usuario = $dados_auth_u['foto_perfil'];
    }
}

// Iniciais e primeiro nome
$partes_nome = explode(' ', trim($usuario_nome));
$primeiro_nome = $partes_nome[0] ?? 'Usuário';
$iniciais = strtoupper(substr($primeiro_nome, 0, 1) . (isset($partes_nome[1]) ? substr($partes_nome[1], 0, 1) : ''));
