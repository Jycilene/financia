<?php
// 1. Inicia a sessão
session_start();

// 2. Se já estiver logado, redireciona para o dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . '/config/database.php';

$mensagem_erro = '';

// 3. Processa o login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    // Busca o usuário no PostgreSQL
    $sql = "SELECT * FROM usuarios WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Valida a senha criptografada e status
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Guarda na sessão (incluindo o perfil: admin ou user)
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_perfil'] = !empty($usuario['perfil']) ? $usuario['perfil'] : 'user';
        $_SESSION['usuario_foto'] = $usuario['foto_perfil'];

        header("Location: dashboard.php");
        exit;
    } else {
        $mensagem_erro = "E-mail ou senha incorretos. Verifique e tente novamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanCia - Bem-vindo de volta</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page-wrapper">

    <div class="auth-split-card login-variant">

        <!-- LADO ESQUERDO: DESTAQUES AZUL ESCURO -->
        <div class="auth-info-side">
            <div>
                <div class="brand brand-dark" style="margin-bottom: 30px;">
                    <div class="brand-icon"><i data-lucide="check"></i></div>
                    <span class="brand-text">FinanCia</span>
                </div>

                <h1 class="auth-info-title">Bem-vindo de volta ao seu dinheiro.</h1>

                <ul class="auth-info-list" style="margin-top: 30px; list-style: none; padding: 0;">
                    <li style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                        <i data-lucide="lock" class="lucide-sm" style="color: var(--emerald-main);"></i>
                        <span>Ambiente protegido e seguro</span>
                    </li>
                    <li style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                        <i data-lucide="line-chart" class="lucide-sm" style="color: var(--emerald-main);"></i>
                        <span>Seus gráficos e relatórios sempre atualizados</span>
                    </li>
                    <li style="display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="shield-check" class="lucide-sm" style="color: var(--emerald-main);"></i>
                        <span>Privacidade e proteção de dados garantidas</span>
                    </li>
                </ul>
            </div>

            <div class="auth-info-footer">
                © 2026 FinanCia. Todos os direitos reservados
            </div>
        </div>

        <!-- LADO DIREITO: FORMULÁRIO DE LOGIN -->
        <div class="auth-form-side">
            <h2 class="auth-title" style="margin-top: 0;">Bem-vindo de volta</h2>
            <p class="auth-subtitle">Entre para acompanhar suas finanças</p>

            <!-- Alerta de Erro -->
            <?php if (!empty($mensagem_erro)): ?>
                <div style="background-color: var(--red-light); color: var(--red-main); padding: 12px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; margin-bottom: 20px; border: 1px solid var(--red-border); display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="alert-circle" class="lucide-sm"></i>
                    <span><?php echo $mensagem_erro; ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="input-container">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" id="email" name="email" class="input-field" placeholder="mariana@email.com" required autofocus>
                </div>

                <div class="input-container">
                    <label class="form-label" for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" class="input-field" placeholder="••••••••" required>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; font-size: 13px;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; color: var(--text-body);">
                        <input type="checkbox" name="lembrar" checked> Lembrar acesso
                    </label>
                    <a href="#" style="color: var(--emerald-main); font-weight: 600;">Esqueci a senha</a>
                </div>

                <button type="submit" class="btn-submit-navy" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span>Entrar</span>
                    <i data-lucide="arrow-right" class="lucide-sm"></i>
                </button>
            </form>

            <div style="margin-top: 24px; font-size: 13px; color: var(--text-muted); text-align: center;">
                Ainda não tem conta? <a href="cadastro.php" style="color: var(--emerald-main); font-weight: 700;">Criar conta</a>
            </div>
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
