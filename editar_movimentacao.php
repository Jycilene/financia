<?php
// 1. Inicia a sessão e protege a rota
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/database.php';

$usuario_id = $_SESSION['usuario_id'];
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
    header("Location: index.php");
    exit;
}

// 4. Busca as categorias cadastradas
$stmt_cat = $pdo->query("SELECT * FROM categorias ORDER BY tipo DESC, nome ASC");
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// 5. Salva as alterações (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao         = trim($_POST['descricao'] ?? '');
    $tipo              = trim($_POST['tipo'] ?? 'despesa');
    $categoria_id      = (int) ($_POST['categoria_id'] ?? 0);
    $data_movimentacao = trim($_POST['data_movimentacao'] ?? '');

    // Tratamento robusto para valores numéricos e formatação de moeda brasileira
    $valor_raw = trim($_POST['valor'] ?? '0');
    $valor_limpo = preg_replace('/[^\d,\.]/', '', $valor_raw);
    if (strpos($valor_limpo, ',') !== false) {
        $valor_limpo = str_replace('.', '', $valor_limpo);
        $valor_limpo = str_replace(',', '.', $valor_limpo);
    }
    $valor = (float) $valor_limpo;

    if (empty($descricao) || $categoria_id <= 0 || empty($data_movimentacao)) {
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

            header("Location: index.php");
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
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="container">
        <div class="form-card">

            <div class="form-card-header">
                <h2>Editar Movimentação ✏️</h2>
                <a href="index.php" class="btn-cancelar" style="padding: 6px 12px; font-size: 13px;">✕ Fechar</a>
            </div>

            <?php if (!empty($mensagem)): ?>
                <div class="alerta alerta-erro"><?php echo $mensagem; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <input type="text" id="descricao" name="descricao" class="form-control" value="<?php echo htmlspecialchars($movimentacao['descricao']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="valor">Valor (R$)</label>
                    <input type="number" id="valor" step="0.01" min="0.01" name="valor" class="form-control" value="<?php echo $movimentacao['valor']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="tipo">Tipo de Movimentação</label>
                    <select id="tipo" name="tipo" class="form-control" required>
                        <option value="despesa" <?php echo $movimentacao['tipo'] === 'despesa' ? 'selected' : ''; ?>>🔴 Despesa (Saída)</option>
                        <option value="receita" <?php echo $movimentacao['tipo'] === 'receita' ? 'selected' : ''; ?>>🟢 Receita (Entrada)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="categoria_id">Categoria</label>
                    <select id="categoria_id" name="categoria_id" class="form-control" required>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $movimentacao['categoria_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo $cat['nome']; ?> (<?php echo ucfirst($cat['tipo']); ?>)
                            </option>
                            <option value="<?php echo $cat['id']; ?>" data-tipo="<?php echo $cat['tipo']; ?>" <?php echo $movimentacao['categoria_id'] == $cat['id'] ? 'selected' : ''; ?>>
    <?php echo $cat['nome']; ?>
</option>

                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="data_movimentacao">Data da Movimentação</label>
                    <input type="date" id="data_movimentacao" name="data_movimentacao" class="form-control" value="<?php echo $movimentacao['data_movimentacao']; ?>" required>
                </div>

                <div class="btn-grupo">
                    <button type="submit" class="btn-salvar">💾 Salvar Alterações</button>
                    <a href="index.php" class="btn-cancelar">Cancelar</a>
                </div>
            </form>

        </div>
    </div>

       <script src="js/main.js"></script>
                     
</body>
</html>