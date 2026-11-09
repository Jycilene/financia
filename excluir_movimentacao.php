<?php
// 1. Inicia e valida a sessão sincronizada com o banco
require_once __DIR__ . '/includes/auth_check.php';

// 2. Verifica se o id da movimentação foi passado na URL
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // 3. SEGURANÇA MÁXIMA: Só apaga se o ID pertencer ao usuário logado!
    $sql = "DELETE FROM movimentacoes WHERE id = :id AND usuario_id = :usuario_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $id,
        ':usuario_id' => $usuario_id
    ]);
}

// 4. Redireciona de volta para o dashboard atualizado
header("Location: dashboard.php");
exit;
