<?php
// 1. Puxa a conexão com o banco de dados
require_once __DIR__ . '/config/database.php';

$mensagem = '';
$tipo_mensagem = '';

// 2. Processa o envio do formulário via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    // Criptografa a senha com segurança
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    try {
        // Insere com perfil padrão 'user' e status 'ativa'
        $sql = "INSERT INTO usuarios (nome, email, senha, perfil, status) VALUES (:nome, :email, :senha, 'user', 'ativa')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome'  => $nome,
            ':email' => $email,
            ':senha' => $senha_hash
        ]);

        $mensagem = "Cadastro realizado com sucesso! Você já pode entrar.";
        $tipo_mensagem = "sucesso";

    } catch (PDOException $e) {
        // Código '23505' no PostgreSQL = violação do UNIQUE (e-mail já cadastrado)
        if ($e->getCode() == '23505') {
            $mensagem = "Este e-mail já está em uso por outro usuário!";
        } else {
            $mensagem = "Erro ao cadastrar: " . $e->getMessage();
        }
        $tipo_mensagem = "erro";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanCia - Crie sua conta</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page-wrapper">

    <div class="auth-split-card">
        
        <!-- LADO ESQUERDO: FORMULÁRIO -->
        <div class="auth-form-side">
            <div class="brand">
                <div class="brand-icon"><i data-lucide="check"></i></div>
                <span class="brand-text">FinanCia</span>
            </div>

            <h1 class="auth-title">Crie sua conta</h1>
            <p class="auth-subtitle">
                Leva menos de 1 minuto. Crie sua conta e comece a controlar suas finanças.
            </p>

            <!-- Alerta de Erro -->
            <?php if (!empty($mensagem) && $tipo_mensagem === 'erro'): ?>
                <div style="background-color: var(--red-light); color: var(--red-main); padding: 12px; border-radius: 8px; font-size: 13px; font-weight: 600; margin-bottom: 16px; border: 1px solid var(--red-border); display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="alert-circle" class="lucide-sm"></i>
                    <span><?php echo $mensagem; ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="formCadastro">
                <div class="input-container">
                    <label class="form-label" for="nome">Nome completo *</label>
                    <input type="text" id="nome" name="nome" class="input-field" placeholder="Mariana Costa" required autofocus>
                </div>

                <div class="input-container">
                    <label class="form-label" for="email">E-mail *</label>
                    <input type="email" id="email" name="email" class="input-field" placeholder="mariana@exemplo.com" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="input-container">
                        <label class="form-label" for="senha">Senha *</label>
                        <input type="password" id="senha" name="senha" class="input-field" placeholder="••••••••" required>
                    </div>
                    <div class="input-container">
                        <label class="form-label" for="confirma_senha">Confirmar senha *</label>
                        <input type="password" id="confirma_senha" name="confirma_senha" class="input-field" placeholder="••••••••" required>
                    </div>
                </div>

                <!-- Caixa Requisitos da Senha -->
                <div class="password-requirements-box">
                    <div class="requirements-title">Requisitos da senha</div>
                    <div class="requirements-grid">
                        <div class="req-item" id="req-len"><span style="display: inline-flex; align-items: center;"><i data-lucide="circle" class="lucide-sm"></i></span> Mínimo 8 caracteres</div>
                        <div class="req-item" id="req-upper"><span style="display: inline-flex; align-items: center;"><i data-lucide="circle" class="lucide-sm"></i></span> Uma letra maiúscula</div>
                        <div class="req-item" id="req-num"><span style="display: inline-flex; align-items: center;"><i data-lucide="circle" class="lucide-sm"></i></span> Um número</div>
                        <div class="req-item" id="req-match"><span style="display: inline-flex; align-items: center;"><i data-lucide="circle" class="lucide-sm"></i></span> Senhas iguais</div>
                    </div>
                </div>

                <button type="submit" class="btn-submit-emerald" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span>Criar conta</span>
                    <i data-lucide="arrow-right" class="lucide-sm"></i>
                </button>
            </form>

            <div style="margin-top: 20px; font-size: 13px; color: var(--text-muted); text-align: center;">
                Já possui uma conta? <a href="login.php" style="color: var(--emerald-main); font-weight: 700;">Entrar</a>
            </div>
        </div>

        <!-- LADO DIREITO: DESTAQUES AZUL ESCURO -->
        <div class="auth-info-side">
            <div>
                <?php if (!empty($mensagem) && $tipo_mensagem === 'sucesso'): ?>
                    <div class="auth-toast-white" style="display: flex; align-items: center; gap: 12px;">
                        <div class="toast-check-icon" style="display: flex; align-items: center; justify-content: center;">
                            <i data-lucide="check" class="lucide-sm"></i>
                        </div>
                        <div>
                            <strong><?php echo $mensagem; ?></strong><br>
                            <a href="login.php" style="color: var(--emerald-main); font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                <span>Clique aqui para entrar</span>
                                <i data-lucide="arrow-right" class="lucide-sm"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <h2 class="auth-info-title">Comece organizado desde o dia um.</h2>

                <ul class="auth-info-list" style="list-style: none; padding: 0;">
                    <li style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                        <span class="check" style="display: flex; align-items: center; justify-content: center;"><i data-lucide="check" class="lucide-sm"></i></span>
                        <span>Categorias prontas: moradia, alimentação, transporte</span>
                    </li>
                    <li style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                        <span class="check" style="display: flex; align-items: center; justify-content: center;"><i data-lucide="check" class="lucide-sm"></i></span>
                        <span>Gráfico de rosca automático no primeiro lançamento</span>
                    </li>
                    <li style="display: flex; align-items: center; gap: 10px;">
                        <span class="check" style="display: flex; align-items: center; justify-content: center;"><i data-lucide="check" class="lucide-sm"></i></span>
                        <span>Segurança de nível bancário para os seus dados</span>
                    </li>
                </ul>
            </div>

            <div class="auth-info-footer" style="display: flex; align-items: center; gap: 8px;">
                <i data-lucide="shield-check" class="lucide-sm"></i>
                <span>Dados criptografados e seguros</span>
            </div>
        </div>

    </div>

    <!-- Script de validação visual da senha em tempo real -->
    <script src="js/lucide.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        const senhaInput = document.getElementById('senha');
        const confirmaInput = document.getElementById('confirma_senha');
        
        const reqLen = document.getElementById('req-len');
        const reqUpper = document.getElementById('req-upper');
        const reqNum = document.getElementById('req-num');
        const reqMatch = document.getElementById('req-match');

        function updateReq(el, isValid) {
            const span = el.querySelector('span');
            if (isValid) {
                el.classList.add('valid');
                span.innerHTML = '<i data-lucide="check" class="lucide-sm"></i>';
            } else {
                el.classList.remove('valid');
                span.innerHTML = '<i data-lucide="circle" class="lucide-sm"></i>';
            }
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function validatePassword() {
            const val = senhaInput.value;
            const confirmVal = confirmaInput.value;

            updateReq(reqLen, val.length >= 8);
            updateReq(reqUpper, /[A-Z]/.test(val));
            updateReq(reqNum, /[0-9]/.test(val));
            updateReq(reqMatch, val.length > 0 && val === confirmVal);
        }

        senhaInput.addEventListener('input', validatePassword);
        confirmaInput.addEventListener('input', validatePassword);

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
