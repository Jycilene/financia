<?php
// 1. Inicia a sessão
session_start();

// 2. Se o usuário já estiver logado, redireciona para o dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanCia - Tenha o controle das suas finanças em suas mãos</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="landing-body">

    <div class="landing-container">

        <!-- 1. BARRA DE NAVEGAÇÃO -->
        <header class="landing-navbar">
            <div class="brand">
                <div class="brand-icon"><i data-lucide="check"></i></div>
                <span class="brand-text">FinanCia</span>
            </div>

            <nav class="landing-menu">
                <a href="#funcionalidades">Produto</a>
                <a href="#funcionalidades">Recursos</a>
                <a href="#metricas">Benefícios</a>
                <a href="#faq">FAQ</a>
            </nav>

            <div class="landing-nav-actions">
                <a href="login.php" class="btn-entrar">Entrar</a>
                <a href="cadastro.php" class="btn-criar-conta-navy">Criar minha conta</a>
            </div>
        </header>

        <!-- 2. HERO SECTION -->
        <section class="hero-section">
            <div class="hero-left">
                <div class="hero-pill-badge" style="display: inline-flex; align-items: center; gap: 6px;">
                    <i data-lucide="sparkles" class="lucide-sm" style="color: var(--emerald-main);"></i>
                    <span>Novo - relatórios mensais automáticos</span>
                </div>
                <h1 class="hero-title">
                    Tenha o controle das suas finanças em suas mãos.
                </h1>
                <p class="hero-desc">
                    Organize suas receitas, acompanhe suas despesas e tenha uma visão mais clara da sua vida financeira.
                </p>

                <div class="hero-buttons">
                    <a href="cadastro.php" class="btn-hero-emerald" style="display: inline-flex; align-items: center; gap: 8px;">
                        <span>Começar agora</span>
                        <i data-lucide="arrow-right" class="lucide-sm"></i>
                    </a>
                    <a href="login.php" class="btn-hero-white">Já tenho uma conta</a>
                </div>

                <div class="hero-checkmarks">
                    <span>Focado em segurança</span>
                    <span>Focado em facilidade</span>
                    <span>Focado em controle</span>
                </div>
            </div>

            <!-- MOCKUP CARD ILUSTRATIVO DO HERO -->
            <div class="hero-right">
                <div class="hero-mockup-card">
                    <div class="mockup-header-flex">
                        <span class="mockup-label">Saldo total - outubro</span>
                        <span class="mockup-tag-green">+12,4% vs. setembro</span>
                    </div>

                    <div class="mockup-saldo-grande">R$ 12.480,90</div>

                    <div class="mockup-mini-cards">
                        <div class="mockup-mini-box">
                            <span class="mockup-mini-label" style="color: var(--emerald-main); display: inline-flex; align-items: center; gap: 4px;">
                                <i data-lucide="trending-up" class="lucide-sm"></i>
                                <span>Receitas</span>
                            </span>
                            <span class="mockup-mini-valor">R$ 9.700,00</span>
                        </div>
                        <div class="mockup-mini-box">
                            <span class="mockup-mini-label" style="color: var(--red-main); display: inline-flex; align-items: center; gap: 4px;">
                                <i data-lucide="trending-down" class="lucide-sm"></i>
                                <span>Despesas</span>
                            </span>
                            <span class="mockup-mini-valor">R$ 4.280,30</span>
                        </div>
                    </div>

                    <!-- Mini gráfico de barras estilizado -->
                    <div class="mockup-bar-chart">
                        <div class="mockup-bar" style="height: 35%;"></div>
                        <div class="mockup-bar" style="height: 50%;"></div>
                        <div class="mockup-bar" style="height: 45%;"></div>
                        <div class="mockup-bar" style="height: 60%;"></div>
                        <div class="mockup-bar" style="height: 75%;"></div>
                        <div class="mockup-bar" style="height: 65%;"></div>
                        <div class="mockup-bar active" style="height: 85%;"></div>
                        <div class="mockup-bar active" style="height: 95%;"></div>
                    </div>

                    <div class="mockup-footer-caption">
                        Dados fictícios ilustrativos - prévia do dashboard
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. FUNCIONALIDADES -->
        <section class="features-section" id="funcionalidades">
            <div class="section-tag-emerald">Funcionalidades</div>
            <h2 class="section-main-title">Tudo para organizar sua vida financeira</h2>

            <div class="features-4col">
                <div class="feature-pill-card">
                    <div class="feature-icon-square" style="display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="arrow-left-right" class="lucide-md"></i>
                    </div>
                    <h3>Receitas e despesas</h3>
                    <p>Registre movimentações com descrição, valor, tipo e data com poucos cliques.</p>
                </div>

                <div class="feature-pill-card">
                    <div class="feature-icon-square" style="display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="wallet" class="lucide-md"></i>
                    </div>
                    <h3>Acompanhamento de saldo</h3>
                    <p>Seu saldo atualizado em tempo real. Esqueça as contas manuais. O sistema calcula seu saldo instantaneamente a cada nova movimentação</p>
                </div>

                <div class="feature-pill-card">
                    <div class="feature-icon-square" style="display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="tag" class="lucide-md"></i>
                    </div>
                    <h3>Organização por categorias</h3>
                    <p>Moradia, alimentação, transporte e muito mais para classificar cada gasto.</p>
                </div>

                <div class="feature-pill-card">
                    <div class="feature-icon-square" style="display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="bar-chart-3" class="lucide-md"></i>
                    </div>
                    <h3>Visualização clara</h3>
                    <p>Relatórios e gráficos intuitivos. Visualize a saúde do seu dinheiro por meio de gráficos práticos que simplificam a sua tomada de decisão.</p>
                </div>
            </div>

            <!-- Bloco Dividido Foto + Métricas Navy -->
            <div class="split-feature-box" id="metricas">
                <div class="split-photo"></div>
                <div class="split-navy-content">
                    <h3>Acompanhe sua evolução mês a mês</h3>
                    <p>
                        Filtros por período, tipo e categoria mostram onde o dinheiro entra e sai — e ajudam a criar hábitos melhores com pequenas metas.
                    </p>

                    <div class="split-metrics-row">
                        <div class="metric-item">
                            <div class="val">+18%</div>
                            <div class="lbl">economia média em 3 meses</div>
                        </div>
                        <div class="metric-item">
                            <div class="val">12,4k</div>
                            <div class="lbl">usuários organizados</div>
                        </div>
                        <div class="metric-item">
                            <div class="val">99,9%</div>
                            <div class="lbl">disponibilidade</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. PERGUNTAS FREQUENTES (FAQ) -->
        <section class="faq-section" id="faq">
            <h2>Perguntas frequentes</h2>

            <div class="faq-item">
                <div class="faq-header">
                    <span>O FinanCia é gratuito?</span>
                    <span class="faq-arrow" style="display: flex; align-items: center;"><i data-lucide="chevron-down" class="lucide-sm"></i></span>
                </div>
                <div class="faq-answer">
                    "Sim, O plano básico do FinanCia é 100% gratuito. Você terá acesso completo ao controle de receitas, dispesas, organização por categorias e ao seu dashboard financeiro sem pagar nada."
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-header">
                    <span>Como meus dados ficam protegidos?</span>
                    <span class="faq-arrow" style="display: flex; align-items: center;"><i data-lucide="chevron-down" class="lucide-sm"></i></span>
                </div>
                <div class="faq-answer">
                    "Seus dados estão totalmente protegidos. Ultilizamos criptografia avançada para o armazenamento de senhs e dados sensíveis, além de protocolos rigorosos de autenticação de sessão e controle de acesso, garantindo que apenas você visualize suas informações financeiras."
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-header">
                    <span>Posso acessar pelo celular?</span>
                    <span class="faq-arrow" style="display: flex; align-items: center;"><i data-lucide="chevron-down" class="lucide-sm"></i></span>
                </div>
                <div class="faq-answer">
                   "Sim, o FinanCia é totalmente otimizado para dispositivos móveis. Nossa interface é responsiva, adaptando-se perfeitamente ao seu smartphone ou tablet para que você gerencie suas finanças de onde estiver, com a mesma facilidade do computador."
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-header">
                    <span>Existe um painel para administradores?</span>
                    <span class="faq-arrow" style="display: flex; align-items: center;"><i data-lucide="chevron-down" class="lucide-sm"></i></span>
                </div>
                <div class="faq-answer">
                    "Sim, a plataforma possui um painel administrativo. Este painel é restrito aos administradores do sistema apenas para gerenciamento técnico de usuários e anáise de indicadores gerais da plataforma, mantendo o sigilo absoluto e sem qualquer acesso aos dados financeiros privados dos clientes."
                </div>
            </div>
        </section>

        <!-- 5. BANNER CTA -->
        <div class="cta-navy-banner">
            <h3>Pronto para organizar suas finanças?</h3>
            <div class="cta-banner-buttons">
                <a href="cadastro.php" class="btn-cta-white">Criar minha conta</a>
                <a href="login.php" class="btn-cta-outline">Falar com suporte</a>
            </div>
        </div>

        <!-- 6. RODAPÉ INSTITUCIONAL -->
        <footer class="landing-footer-main">
            <div class="footer-top-grid">
                <div class="footer-brand-col">
                    <div class="brand">
                        <div class="brand-icon"><i data-lucide="check"></i></div>
                        <span class="brand-text">FinanCia</span>
                    </div>
                    <p>
                        Controle financeiro pessoal simples e seguro. 
                    </p>
                </div>

                <div class="footer-links-cols">
                    <div class="footer-group">
                        <h4>Produto</h4>
                        <a href="#funcionalidades">Recursos</a>
                        <a href="#metricas">Preços</a>
                        <a href="#faq">Segurança</a>
                    </div>
                    <div class="footer-group">
                        <h4>Institucional</h4>
                        <a href="#">Sobre</a>
                        <a href="#">Contato</a>
                        <a href="#">Privacidade</a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom-bar">
                <span>© <?php echo date('Y'); ?> FinanCia. Todos os direitos reservados.</span>
            </div>
        </footer>

    </div>

    <!-- Scripts e interatividade -->
    <script src="js/lucide.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        document.querySelectorAll('.faq-header').forEach(header => {
            header.addEventListener('click', () => {
                const item = header.parentElement;
                item.classList.toggle('active');
            });
        });

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
