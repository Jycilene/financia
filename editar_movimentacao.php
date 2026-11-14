<?php
// 1. Inicia e valida a sessão sincronizada com o banco
require_once __DIR__ . '/includes/auth_check.php';

$mensagem = '';

// 2. Verifica se o ID foi passado na URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$id = (int) $_GET['id'];

// 3. Busca a movimentação atual para preencher os campos do formulário
$sql = "SELECT * FROM movimentacoes WHERE id = :id AND usuario_id = :usuario_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id, ':usuario_id' => $usuario_id]);
$movimentacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movimentacao) {
    header("Location: dashboard.php");
    exit;
}

// 4. Busca as categorias cadastradas
$stmt_cat = $pdo->query("SELECT * FROM categorias ORDER BY tipo DESC, nome ASC");
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// 5. Salva as alterações (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao         = trim($_POST['descricao']);
    $valor             = str_replace(',', '.', trim($_POST['valor']));
    $tipo              = $_POST['tipo'];
    $categoria_id      = $_POST['categoria_id'];
    $data_movimentacao = $_POST['data_movimentacao'];

    if (empty($descricao) || empty($valor) || empty($tipo) || empty($categoria_id) || empty($data_movimentacao)) {
        $mensagem = "Por favor, preencha todos os campos!";
    } elseif ($valor <= 0) {
        $mensagem = "O valor deve ser maior que zero!";
    } else {
        try {
            $sql_update = "UPDATE movimentacoes 
                           SET descricao = :descricao, 
                               valor = :valor, 
                               tipo = :tipo, 
                               categoria_id = :categoria_id, 
                               data_movimentacao = :data_movimentacao 
                           WHERE id = :id AND usuario_id = :usuario_id";

            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([
                ':descricao'         => $descricao,
                ':valor'             => $valor,
                ':tipo'              => $tipo,
                ':categoria_id'      => $categoria_id,
                ':data_movimentacao' => $data_movimentacao,
                ':id'                => $id,
                ':usuario_id'        => $usuario_id
            ]);

            header("Location: dashboard.php");
            exit;

        } catch (PDOException $e) {
            $mensagem = "Erro ao atualizar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanCia - Editar Movimentação</title>
    <!-- Certifique-se de que o caminho do CSS está correto -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="container" style="max-width: 600px; margin: 40px auto; padding: 20px;">
        <div class="form-card" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">

            <div class="form-card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; color: #112d42; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="pencil" class="lucide-sm"></i>
                    <span>Editar Movimentação</span>
                </h2>
                <a href="dashboard.php" class="btn-cancelar" style="padding: 6px 12px; font-size: 13px; text-decoration: none; color: #64748b; display: inline-flex; align-items: center; gap: 6px;">
                    <i data-lucide="x" class="lucide-sm"></i>
                    <span>Fechar</span>
                </a>
            </div>

            <?php if (!empty($mensagem)): ?>
                <div class="alerta alerta-erro" style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="alert-circle" class="lucide-sm"></i>
                    <span><?php echo $mensagem; ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="descricao" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">Descrição</label>
                    <input type="text" id="descricao" name="descricao" class="form-control" value="<?php echo htmlspecialchars($movimentacao['descricao']); ?>" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="valor" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">Valor (R$)</label>
                    <input type="number" id="valor" step="0.01" min="0.01" name="valor" class="form-control" value="<?php echo $movimentacao['valor']; ?>" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="tipo" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">Tipo de Movimentação</label>
                    <select id="tipo" name="tipo" class="form-control" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="despesa" <?php echo $movimentacao['tipo'] === 'despesa' ? 'selected' : ''; ?>>Despesa (Saída)</option>
                        <option value="receita" <?php echo $movimentacao['tipo'] === 'receita' ? 'selected' : ''; ?>>Receita (Entrada)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="categoria_id" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">Categoria</label>
                    <select id="categoria_id" name="categoria_id" class="form-control" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $movimentacao['categoria_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nome']); ?> (<?php echo ucfirst($cat['tipo']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="data_movimentacao" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">Data da Movimentação</label>
                    <input type="date" id="data_movimentacao" name="data_movimentacao" class="form-control" value="<?php echo $movimentacao['data_movimentacao']; ?>" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div class="btn-grupo" style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-salvar" style="background: #059669; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                        <i data-lucide="save" class="lucide-sm"></i>
                        <span>Salvar Alterações</span>
                    </button>
                    <a href="dashboard.php" class="btn-cancelar" style="background: #e2e8f0; color: #334155; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                        <i data-lucide="x" class="lucide-sm"></i>
                        <span>Cancelar</span>
                    </a>
                </div>
            </form>

        </div>
    </div>
    
    <script src="js/lucide.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>