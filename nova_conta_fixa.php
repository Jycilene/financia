<?php
// 1. Inicia e valida a sessão sincronizada com o banco
require_once __DIR__ . '/includes/auth_check.php';
date_default_timezone_set('America/Recife');

$mensagem_erro = '';

// Busca categorias para o select
$stmt_cat = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $categoria_id = (int) ($_POST['categoria_id'] ?? 0);
    $tipo_compromisso = trim($_POST['tipo_compromisso'] ?? 'recorrente');
    $dia_vencimento = (int) ($_POST['dia_vencimento'] ?? 1);
    $descricao = trim($_POST['descricao'] ?? '');
    $data_hoje = date('Y-m-d');

    if (empty($nome) || $categoria_id <= 0) {
        $mensagem_erro = "Preencha o nome da conta e selecione uma categoria válida.";
    } else {
        try {
            $pdo->beginTransaction();

            if ($tipo_compromisso === 'recorrente') {
                $valor = (float) str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor'] ?? '0');
                
                if ($valor <= 0) {
                    throw new Exception("O valor da conta recorrente deve ser maior que zero.");
                }

                // Insere a despesa recorrente (incluindo data_inicio)
                $stmt = $pdo->prepare("
                    INSERT INTO despesas_fixas (usuario_id, categoria_id, nome, valor, dia_vencimento, data_inicio, ativo, tipo_compromisso, descricao) 
                    VALUES (:uid, :cid, :nome, :val, :dia, :dinicio, TRUE, 'recorrente', :desc)
                    RETURNING id
                ");
                $stmt->execute([
                    ':uid' => $usuario_id,
                    ':cid' => $categoria_id,
                    ':nome' => $nome,
                    ':val' => $valor,
                    ':dia' => $dia_vencimento,
                    ':dinicio' => $data_hoje,
                    ':desc' => $descricao
                ]);
                $despesa_id = $stmt->fetchColumn();

                // Gera a ocorrência para o mês atual imediatamente
                $mesAtual = (int) date('m');
                $anoAtual = (int) date('Y');
                $ultimoDiaMes = (int) date('t', strtotime("{$anoAtual}-{$mesAtual}-01"));
                $diaReal = min($dia_vencimento, $ultimoDiaMes);
                $dataVencimento = sprintf('%04d-%02d-%02d', $anoAtual, $mesAtual, $diaReal);

                $stmt_oc = $pdo->prepare("
                    INSERT INTO pagamentos_fixas (despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, status, numero_parcela) 
                    VALUES (:did, :uid, :mes, :ano, :val, :venc, 'pendente', NULL)
                ");
                $stmt_oc->execute([
                    ':did' => $despesa_id,
                    ':uid' => $usuario_id,
                    ':mes' => $mesAtual,
                    ':ano' => $anoAtual,
                    ':val' => $valor,
                    ':venc' => $dataVencimento
                ]);

            } else {
                // Dívida Parcelada
                $valor_total = (float) str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_total'] ?? '0');
                $quantidade_parcelas = (int) ($_POST['quantidade_parcelas'] ?? 1);
                $data_primeira = trim($_POST['data_primeira_parcela'] ?? date('Y-m-d'));

                if ($valor_total <= 0 || $quantidade_parcelas <= 0) {
                    throw new Exception("O valor total e a quantidade de parcelas devem ser válidos.");
                }

                $valor_parcela = round($valor_total / $quantidade_parcelas, 2);

                // Insere a dívida parcelada principal (incluindo data_inicio com a data da primeira parcela)
                $stmt = $pdo->prepare("
                    INSERT INTO despesas_fixas (usuario_id, categoria_id, nome, valor, valor_total, quantidade_parcelas, dia_vencimento, data_inicio, ativo, tipo_compromisso, descricao) 
                    VALUES (:uid, :cid, :nome, :vparc, :vtot, :qparc, :dia, :dinicio, TRUE, 'parcelada', :desc)
                    RETURNING id
                ");
                $stmt->execute([
                    ':uid' => $usuario_id,
                    ':cid' => $categoria_id,
                    ':nome' => $nome,
                    ':vparc' => $valor_parcela,
                    ':vtot' => $valor_total,
                    ':qparc' => $quantidade_parcelas,
                    ':dia' => $dia_vencimento,
                    ':dinicio' => $data_primeira,
                    ':desc' => $descricao
                ]);
                $despesa_id = $stmt->fetchColumn();

                // Gera todas as parcelas individualmente com base na data da primeira parcela
                $timestampBase = strtotime($data_primeira);
                for ($i = 1; $i <= $quantidade_parcelas; $i++) {
                    $dataVencObj = strtotime("+" . ($i - 1) . " month", $timestampBase);
                    $mesRef = (int) date('m', $dataVencObj);
                    $anoRef = (int) date('Y', $dataVencObj);
                    $dataVencStr = date('Y-m-d', $dataVencObj);

                    $stmt_parc = $pdo->prepare("
                        INSERT INTO pagamentos_fixas (despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, status, numero_parcela) 
                        VALUES (:did, :uid, :mes, :ano, :val, :venc, 'pendente', :num)
                    ");
                    $stmt_parc->execute([
                        ':did' => $despesa_id,
                        ':uid' => $usuario_id,
                        ':mes' => $mesRef,
                        ':ano' => $anoRef,
                        ':val' => $valor_parcela,
                        ':venc' => $dataVencStr,
                        ':num' => $i
                    ]);
                }
            }

            $pdo->commit();
            header("Location: contas_fixas.php?sucesso=1");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem_erro = "Erro ao cadastrar compromisso: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanCia - Novo Compromisso</title>
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
                <a href="contas_fixas.php" style="text-decoration: none; color: inherit;">Contas fixas</a> / <strong>Novo compromisso</strong>
            </div>
            
            <h1 class="page-title" style="margin-bottom: 6px;">Cadastrar Novo Compromisso</h1>
            <p class="page-subtitle" style="margin-bottom: 24px;">Adicione uma conta recorrente (ex: aluguel) ou uma dívida parcelada (ex: compra de celular).</p>

            <?php if (!empty($mensagem_erro)): ?>
                <div style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="alert-circle" class="lucide-sm"></i> <?php echo $mensagem_erro; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-card-box" style="max-width: 700px;">
                <form action="nova_conta_fixa.php" method="POST">
                    
                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy-main);">Tipo de Compromisso</label>
                        <select name="tipo_compromisso" id="tipo_compromisso" class="select-filter-compact" style="width: 100%; padding: 10px;" onchange="alternarCamposCompromisso()">
                            <option value="recorrente">Conta Recorrente (Ex: Aluguel, Luz, Internet)</option>
                            <option value="parcelada">Dívida Parcelada (Ex: Celular, Empréstimo, Cartão)</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy-main);">Nome do Compromisso / Dívida</label>
                        <input type="text" name="nome" required placeholder="Ex: Conta de Luz ou Parcela Celular" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy-main);">Categoria</label>
                        <select name="categoria_id" required class="select-filter-compact" style="width: 100%; padding: 10px;">
                            <option value="">Selecione uma categoria...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Campos exclusivos para Recorrente -->
                    <div id="bloco_recorrente" style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy-main);">Valor Mensal (R$)</label>
                        <input type="text" name="valor" placeholder="0,00" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <!-- Campos exclusivos para Parcelada -->
                    <div id="bloco_parcelado" style="display: none; background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 18px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--navy-main);">Valor Total da Dívida (R$)</label>
                                <input type="text" name="valor_total" id="valor_total" placeholder="1200,00" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;" oninput="calcularParcela()">
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--navy-main);">Quantidade de Parcelas</label>
                                <input type="number" name="quantidade_parcelas" id="quantidade_parcelas" min="2" max="120" value="12" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;" oninput="calcularParcela()">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--navy-main);">Data da 1ª Parcela</label>
                                <input type="date" name="data_primeira_parcela" value="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: var(--navy-main);">Valor Estimado da Parcela</label>
                                <div id="preview_parcela" style="padding: 9px; background: #e2e8f0; border-radius: 6px; font-weight: 700; font-size: 14px; color: var(--navy-main);">R$ 0,00</div>
                            </div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy-main);">Dia de Vencimento</label>
                            <input type="number" name="dia_vencimento" min="1" max="31" required value="10" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                    </div>

                    <div style="margin-bottom: 22px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy-main);">Descrição Opcional</label>
                        <textarea name="descricao" rows="3" placeholder="Detalhes adicionais sobre o compromisso..." style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;"></textarea>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" style="background: var(--emerald-main); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                            <i data-lucide="plus" class="lucide-sm"></i> Cadastrar Compromisso
                        </button>
                        <a href="contas_fixas.php" style="background: #e2e8f0; color: #475569; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
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
        function alternarCamposCompromisso() {
            const tipo = document.getElementById('tipo_compromisso').value;
            const blocoRecorrente = document.getElementById('bloco_recorrente');
            const blocoParcelado = document.getElementById('bloco_parcelado');

            if (tipo === 'parcelada') {
                blocoRecorrente.style.display = 'none';
                blocoParcelado.style.display = 'block';
            } else {
                blocoRecorrente.style.display = 'block';
                blocoParcelado.style.display = 'none';
            }
        }

        function calcularParcela() {
            let valorTotalStr = document.getElementById('valor_total').value.trim();
            
            // Remove pontos de milhar e substitui vírgula decimal por ponto
            valorTotalStr = valorTotalStr.replace(/\./g, '').replace(',', '.');

            const qtd = parseInt(document.getElementById('quantidade_parcelas').value) || 1;
            const valTotal = parseFloat(valorTotalStr) || 0;

            if (valTotal > 0 && qtd > 0) {
                const parc = valTotal / qtd;
                document.getElementById('preview_parcela').innerText = 'R$ ' + parc.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } else {
                document.getElementById('preview_parcela').innerText = 'R$ 0,00';
            }
        }

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>