<?php
// 1. Inicia a sessão e protege a rota
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

// 2. Conexão com o banco de dados
require_once __DIR__ . '/config/database.php';

$mensagem = '';
$tipo_mensagem = '';

// 3. Busca todas as categorias cadastradas no PostgreSQL para o <select>
$stmt_cat = $pdo->query("SELECT * FROM categorias ORDER BY tipo DESC, nome ASC");
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// 4. Quando o formulário for enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao         = trim($_POST['descricao'] ?? '');
    $tipo              = isset($_POST['tipo']) ? trim($_POST['tipo']) : 'receita';
    $categoria_id      = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
    $data_movimentacao = trim($_POST['data_movimentacao'] ?? '');
    $usuario_id        = $_SESSION['usuario_id'];

    // Tratamento robusto para valores numéricos e formatação de moeda brasileira
    $valor_raw = trim($_POST['valor'] ?? '0');
    $valor_limpo = preg_replace('/[^\d,\.]/', '', $valor_raw);
    if (strpos($valor_limpo, ',') !== false) {
        $valor_limpo = str_replace('.', '', $valor_limpo);
        $valor_limpo = str_replace(',', '.', $valor_limpo);
    }
    $valor = (float) $valor_limpo;

    if (empty($descricao) || $categoria_id <= 0 || empty($data_movimentacao)) {
        $mensagem_erro = "Por favor, preencha todos os campos obrigatórios!";
    } elseif ($valor <= 0) {
        $mensagem_erro = "Informe um valor maior que zero!";
    } else {
        try {
            $sql = "INSERT INTO movimentacoes (usuario_id, categoria_id, descricao, valor, tipo, data_movimentacao) 
                    VALUES (:usuario_id, :categoria_id, :descricao, :valor, :tipo, :data_movimentacao)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':usuario_id'        => $usuario_id,
                ':categoria_id'      => $categoria_id,
                ':descricao'         => $descricao,
                ':valor'             => $valor,
                ':tipo'              => $tipo,
                ':data_movimentacao' => $data_movimentacao
            ]);

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {
            $mensagem = "Erro ao cadastrar: " . $e->getMessage();
            $tipo_mensagem = "erro";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanCia - Nova Movimentação</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="container">
        <div class="form-card">

            <div class="form-card-header">
                <h2>Nova Movimentação 💵</h2>
                <a href="index.php" class="btn-cancelar" style="padding: 6px 12px; font-size: 13px;">✕ Fechar</a>
            </div>

            <?php if (!empty($mensagem)): ?>
                <div class="alerta alerta-erro">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <input type="text" id="descricao" name="descricao" class="form-control" placeholder="Ex: Supermercado, Salário, Internet..." required autofocus>
                </div>

                <div class="form-group">
                    <label for="valor">Valor (R$)</label>
                    <input type="number" id="valor" step="0.01" min="0.01" name="valor" class="form-control" placeholder="0,00" required>
                </div>

                <div class="form-group">
                    <label for="tipo">Tipo de Movimentação</label>
                    <select id="tipo" name="tipo" class="form-control" required>
                        <option value="despesa">🔴 Despesa (Saída)</option>
                        <option value="receita">🟢 Receita (Entrada)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="categoria_id">Categoria</label>
                    <select id="categoria_id" name="categoria_id" class="form-control" required>
                        <option value="">Selecione uma categoria...</option>
                        <?php foreach ($categorias as $cat): ?>
                            <!-- data-tipo adicionado para o JavaScript filtrar! -->
                            <option value="<?php echo $cat['id']; ?>" data-tipo="<?php echo $cat['tipo']; ?>">
                                <?php echo $cat['nome']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="data_movimentacao">Data da Movimentação</label>
                    <input type="date" id="data_movimentacao" name="data_movimentacao" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="btn-grupo">
                    <button type="submit" class="btn-salvar">💾 Salvar Movimentação</button>
                    <a href="index.php" class="btn-cancelar">Cancelar</a>
                </div>
            </form>

        </div>
    </div>

    <!-- NOSSO JAVASCRIPT EM AÇÃO -->
    <script src="js/main.js"></script>

</body>
</html>
