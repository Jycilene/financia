<?php
// 1. Inicia e valida a sessão sincronizada com o banco
require_once __DIR__ . '/includes/auth_check.php';

$conta_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$mensagem_erro = '';

if ($conta_id <= 0) {
    header("Location: contas_fixas.php");
    exit;
}

// 2. Busca os dados atuais do compromisso
$stmt = $pdo->prepare("SELECT * FROM despesas_fixas WHERE id = :id AND usuario_id = :uid");
$stmt->execute([':id' => $conta_id, ':uid' => $usuario_id]);
$conta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conta) {
    header("Location: contas_fixas.php");
    exit;
}

// Busca categorias para o select
$stmt_cat = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// 3. Processa o formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $categoria_id = (int) ($_POST['categoria_id'] ?? 0);
    $dia_vencimento = (int) ($_POST['dia_vencimento'] ?? 1);
    $descricao = trim($_POST['descricao'] ?? '');
    $ativo = isset($_POST['ativo']) ? TRUE : FALSE;

    if (empty($nome) || $categoria_id <= 0) {
        $mensagem_erro = "Preencha o nome da conta e selecione uma categoria válida.";
    } else {
        try {
            // Se for conta recorrente, permite alterar o valor base também
            if ($conta['tipo_compromisso'] === 'recorrente') {
                $valor = (float) str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor'] ?? '0');
                if ($valor <= 0) {
                    throw new Exception("O valor deve ser maior que zero.");
                }

                $stmt_up = $pdo->prepare("
                    UPDATE despesas_fixas 
                    SET nome = :nome, categoria_id = :cid, valor = :val, dia_vencimento = :dia, descricao = :desc, ativo = :ativo 
                    WHERE id = :id AND usuario_id = :uid
                ");
                $stmt_up->execute([
                    ':nome' => $nome,
                    ':cid' => $categoria_id,
                    ':val' => $valor,
                    ':dia' => $dia_vencimento,
                    ':desc' => $descricao,
                    ':ativo' => $ativo ? 1 : 0,
                    ':id' => $conta_id,
                    ':uid' => $usuario_id
                ]);
            } else {
                // Para parceladas, preserva o valor total e parcelas, atualizando metadados
                $stmt_up = $pdo->prepare("
                    UPDATE despesas_fixas 
                    SET nome = :nome, categoria_id = :cid, dia_vencimento = :dia, descricao = :desc, ativo = :ativo 
                    WHERE id = :id AND usuario_id = :uid
                ");
                $stmt_up->execute([
                    ':nome' => $nome,
                    ':cid' => $categoria_id,
                    ':dia' => $dia_vencimento,
                    ':desc' => $descricao,
                    ':ativo' => $ativo ? 1 : 0,
                    ':id' => $conta_id,
                    ':uid' => $usuario_id
                ]);
            }

            header("Location: detalhes_conta_fixa.php?id=" . $conta_id);
            exit;

        } catch (Exception $e) {
            $mensagem_erro = "Erro ao atualizar compromisso: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanCia - Editar Compromisso</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="app-wrapper">
        <aside class="app-sidebar">
            <div class="sidebar-top">
                <a href="dashboard.php" class="brand brand-dark sidebar-brand" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
                    <div class="brand-icon"><i data-lucide="check"></i></div>
                    <span class="brand-text">FinanCia</span>
                </a>
                <div class="sidebar-section-title">Menu Principal</div>
                <nav class="sidebar-nav">
                    <a href="dashboard.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="layout-dashboard"></i></span>
                        <span>Visão geral</span>
                    </a>
                    <a href="movimentacoes.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="arrow-left-right"></i></span>
                        <span>Movimentações</span>
                    </a>
                    <a href="nova_movimentacao.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="circle-plus"></i></span>
                        <span>Nova movimentação</span>
                    </a>
                    <a href="contas_fixas.php" class="nav-item active">
                        <span class="nav-item-icon"><i data-lucide="calendar-days"></i></span>
                        <span>Contas fixas</span>
                    </a>
                    <a href="notificacoes.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="bell"></i></span>
                        <span>Notificações</span>
                    </a>
                    <a href="perfil.php" class="nav-item">
                        <span class="nav-item-icon"><i data-lucide="user-round"></i></span>
                        <span>Meu perfil</span>
                    </a>

                    <?php if ($eh_admin): ?>
                        <div class="sidebar-section-title" style="margin-top: 15px;">Administração</div>
                        <a href="admin.php" class="nav-item">
                            <span class="nav-item-icon"><i data-lucide="shield-check"></i></span>
                            <span>Painel Admin</span>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>

            <div class="sidebar-bottom">
                <div class="sidebar-user-card">
                    <a href="perfil.php" title="Meu perfil" style="text-decoration: none; display: flex; align-items: center; gap: 10px; width: 100%;">
                        <?php if (!empty($foto_usuario)): ?>
                            <img src="<?php echo htmlspecialchars($foto_usuario); ?>" alt="Foto" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid var(--emerald-main);">
                        <?php else: ?>
                            <div class="user-avatar-circle" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; background: var(--navy-main); color: white; border-radius: 50%; font-weight: bold; font-size: 14px;"><?php echo $iniciais; ?></div>
                        <?php endif; ?>
                        <div class="user-meta-info" style="overflow: hidden; flex: 1;">
                            <div class="user-name-text" style="font-weight: 600; font-size: 13px; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($usuario_nome); ?></div>
                        </div>
                    </a>
                    <a href="logout.php" style="font-size: 11px; color: #dc2626; text-decoration: none; margin-top: 4px; display: inline-block;">Sair</a>
                </div>
            </div>
        </aside>

        <main class="app-main-content">
            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">
                <a href="contas_fixas.php" style="text-decoration: none; color: inherit;">Contas fixas</a> / 
                <a href="detalhes_conta_fixa.php?id=<?php echo $conta_id; ?>" style="text-decoration: none; color: inherit;">Detalhes</a> / 
                <strong>Editar</strong>
            </div>
            
            <h1 class="page-title" style="margin-bottom: 6px;">Editar Compromisso</h1>
            <p class="page-subtitle" style="margin-bottom: 24px;">Atualize as informações do seu compromisso financeiro.</p>

            <?php if (!empty($mensagem_erro)): ?>
                <div style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="alert-circle" class="lucide-sm"></i> <?php echo $mensagem_erro; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-card-box" style="max-width: 700px;">
                <form action="editar_conta_fixa.php?id=<?php echo $conta_id; ?>" method="POST">
                    
                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy-main);">Nome do Compromisso / Dívida</label>
                        <input type="text" name="nome" required value="<?php echo htmlspecialchars($conta['nome']); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy-main);">Categoria</label>
                        <select name="categoria_id" required class="select-filter-compact" style="width: 100%; padding: 10px;">
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $conta['categoria_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($conta['tipo_compromisso'] === 'recorrente'): ?>
                        <div style="margin-bottom: 18px;">
                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy-main);">Valor Mensal (R$)</label>
                            <input type="text" name="valor" value="<?php echo number_format($conta['valor'], 2, ',', '.'); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 18px; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <span style="font-size: 12px; color: var(--text-muted); display: block;">Tipo de Compromisso: <strong>Dívida Parcelada</strong></span>
                            <span style="font-size: 12px; color: var(--text-muted); display: block;">Valor Total: <strong>R$ <?php echo number_format($conta['valor_total'] ?? 0, 2, ',', '.'); ?> (<?php echo $conta['quantidade_parcelas']; ?> parcelas)</strong></span>
                            <span style="font-size: 11px; color: #0369a1; display: flex; align-items: center; gap: 4px; margin-top: 4px;">
                                <i data-lucide="info" class="lucide-xs"></i> O valor total e o número de parcelas não podem ser alterados diretamente para preservar o histórico financeiro.
                            </span>
                        </div>
                    <?php endif; ?>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy-main);">Dia de Vencimento</label>
                            <input type="number" name="dia_vencimento" min="1" max="31" required value="<?php echo $conta['dia_vencimento']; ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                        <div style="display: flex; align-items: center; padding-top: 24px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 600; color: var(--navy-main);">
                                <input type="checkbox" name="ativo" value="1" <?php echo $conta['ativo'] ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                                Compromisso Ativo
                            </label>
                        </div>
                    </div>

                    <div style="margin-bottom: 22px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy-main);">Descrição Opcional</label>
                        <textarea name="descricao" rows="3" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;"><?php echo htmlspecialchars($conta['descricao']); ?></textarea>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" style="background: var(--emerald-main); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="check" class="lucide-sm"></i> Salvar Alterações
                        </button>
                        <a href="detalhes_conta_fixa.php?id=<?php echo $conta_id; ?>" style="background: #e2e8f0; color: #475569; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="x" class="lucide-sm"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </main>
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