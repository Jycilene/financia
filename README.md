<div align="center">

# 💰 FinanCia

### Sistema de Gestão Financeira Pessoal

Controle suas receitas, despesas, contas fixas e dívidas parceladas em um só lugar, com gráficos, alertas de vencimento e painel administrativo.

![PHP](https://img.shields.io/badge/PHP-8.1-777BB4?logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-316192?logo=postgresql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?logo=javascript&logoColor=black)
![Chart.js](https://img.shields.io/badge/Chart.js-FF6384?logo=chartdotjs&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-ready-2496ED?logo=docker&logoColor=white)
![Status](https://img.shields.io/badge/status-em%20desenvolvimento-yellow)

</div>

---

## 📑 Sumário

- [Sobre o projeto](#-sobre-o-projeto)
- [Funcionalidades](#-funcionalidades)
- [Tecnologias](#-tecnologias)
- [Arquitetura e estrutura](#-arquitetura-e-estrutura)
- [Modelo de dados](#-modelo-de-dados)
- [Como executar](#-como-executar)
- [Variáveis de ambiente](#-variáveis-de-ambiente)
- [Segurança](#-segurança)
- [Roadmap](#-roadmap)
- [Contribuindo](#-contribuindo)
- [Autora](#-autora)

---

## 📖 Sobre o projeto

O **FinanCia** é uma aplicação web completa e intuitiva para o controle de finanças pessoais. Ela permite registrar movimentações de receitas e despesas, acompanhar contas fixas recorrentes e dívidas parceladas com barra de progresso visual, além de oferecer relatórios em gráficos e notificações automáticas de vencimento.

O objetivo é dar ao usuário uma visão clara de para onde o dinheiro está indo, ajudando-o a se organizar e a nunca perder um vencimento.

---

## 📌 Funcionalidades

### 📊 Visão Geral (Dashboard)
- KPIs de **saldo atual**, **receitas**, **despesas** e resumo do mês
- **Gráfico de rosca** com a distribuição de gastos por categoria
- Lista das **movimentações recentes**

### 🔁 Contas Fixas e Dívidas Parceladas
- Despesas **recorrentes mensais** com dia de vencimento
- Dívidas **parceladas** com cálculo automático do valor das parcelas
- **Barra de progresso** em percentual do quanto já foi quitado
- **Histórico individual** de pagamentos por conta (status: pendente, paga ou atrasada)

### 💸 Movimentações Financeiras
- Lançamento completo de **entradas e saídas**
- Edição e exclusão de lançamentos
- Filtros por **tipo**, **categoria** e **palavra-chave**

### 🔔 Notificações
- Alertas automáticos de vencimento: **5 dias antes**, **2 dias antes**, **no dia** e **em atraso**
- Controle de leitura e exclusão das notificações

### 👤 Perfil e Administração
- **Upload de foto de perfil**
- Alteração de dados e de senha
- **Painel administrativo** com gestão de usuários e controle de acessos (perfis `admin` e `user`)

---

## 🛠️ Tecnologias

| Camada | Tecnologia |
|---|---|
| **Backend** | PHP 8 com PDO (acesso a dados via *prepared statements*) |
| **Banco de dados** | PostgreSQL (compatível com Supabase) |
| **Frontend** | HTML5, CSS3 (design customizado e responsivo), JavaScript Vanilla |
| **Bibliotecas** | [Chart.js](https://www.chartjs.org/) (gráficos), [Lucide Icons](https://lucide.dev/) (ícones vetoriais) |
| **Infraestrutura** | Docker (`php:8.1-apache`), deploy compatível com Render + Supabase |

---

## 🗂️ Arquitetura e estrutura

```
FinanCia/
├── config/
│   └── database.php            # Conexão PDO com PostgreSQL (via variáveis de ambiente)
├── includes/
│   ├── auth_check.php          # Proteção de rotas e validação de sessão
│   └── gerador_notificacoes.php# Geração automática de alertas de vencimento
├── css/style.css               # Estilos e design responsivo
├── js/main.js                  # Interações do frontend
├── uploads/perfil/             # Fotos de perfil dos usuários
├── index.php                   # Página inicial
├── login.php / cadastro.php / logout.php
├── dashboard.php               # Visão geral
├── movimentacoes.php           # Listagem e filtros
├── nova_movimentacao.php / editar_movimentacao.php / excluir_movimentacao.php
├── contas_fixas.php            # Listagem de contas fixas e parceladas
├── nova_conta_fixa.php / editar_conta_fixa.php / detalhes_conta_fixa.php
├── funcoes_fixas.php           # Regras de negócio das contas fixas
├── notificacoes.php            # Central de notificações
├── perfil.php                  # Perfil do usuário
├── admin.php                   # Painel administrativo
├── supabase_setup.sql          # Script de criação das tabelas e carga inicial
└── Dockerfile
```

---

## 🧩 Modelo de dados

| Tabela | Descrição |
|---|---|
| `usuarios` | Dados de acesso, perfil (`admin`/`user`), status e foto |
| `categorias` | Categorias de receita e despesa (já vem com categorias padrão) |
| `movimentacoes` | Lançamentos de entradas e saídas |
| `despesas_fixas` | Contas recorrentes e dívidas parceladas |
| `pagamentos_fixas` | Pagamentos mensais de cada conta fixa, com status |
| `notificacoes` | Alertas de vencimento por usuário |

O script completo está em [`supabase_setup.sql`](supabase_setup.sql).

---

## 🚀 Como executar

### Pré-requisitos

- PHP 8.1+ com as extensões `pdo` e `pdo_pgsql`
- PostgreSQL 13+ (local ou Supabase)
- Git (e, opcionalmente, Docker)

### 1. Clonar o repositório

```bash
git clone https://github.com/seu-usuario/financia.git
cd financia
```

### 2. Criar o banco de dados

Crie um banco chamado `financia_db` e execute o script de estrutura:

```bash
createdb financia_db
psql -d financia_db -f supabase_setup.sql
```

> No Supabase, basta colar o conteúdo de `supabase_setup.sql` no **SQL Editor** e executar.

### 3. Configurar as variáveis de ambiente

Veja a [tabela abaixo](#-variáveis-de-ambiente). Sem nenhuma variável definida, o sistema usa os valores padrão para ambiente local (`127.0.0.1:5432`, banco `financia_db`, usuário `postgres`).

### 4. Iniciar a aplicação

**Opção A: servidor embutido do PHP**

```bash
php -S localhost:8000
```

**Opção B: XAMPP**

Copie a pasta do projeto para `htdocs/` e acesse `http://localhost/financia`.

**Opção C: Docker**

```bash
docker build -t financia .
docker run -p 8080:80 \
  -e DB_HOST=seu-host \
  -e DB_PORT=5432 \
  -e DB_NAME=financia_db \
  -e DB_USER=postgres \
  -e DB_PASS=sua-senha \
  financia
```

Acesse `http://localhost:8080`.

---

## ⚙️ Variáveis de ambiente

| Variável | Descrição | Padrão |
|---|---|---|
| `DB_HOST` | Host do PostgreSQL | `127.0.0.1` |
| `DB_PORT` | Porta | `5432` |
| `DB_NAME` | Nome do banco | `financia_db` |
| `DB_USER` | Usuário | `postgres` |
| `DB_PASS` | Senha | definida em `config/database.php` (troque!) |

Quando o host não é local, a conexão exige **SSL** automaticamente (requisito do Supabase).

---

## 🔒 Segurança

- Senhas armazenadas com `password_hash()` e verificadas com `password_verify()`
- Consultas com **PDO e prepared statements** (proteção contra SQL Injection)
- Rotas protegidas por verificação de sessão e perfil de acesso
- Credenciais de banco lidas de variáveis de ambiente (`DB_*`)
- Uploads de usuários e arquivos sensíveis ignorados pelo versionamento (`.gitignore`)

---

## 🗺️ Roadmap

Melhorias planejadas para as próximas versões:

- [ ] 📱 **Versão mobile responsiva:** aprimorar a experiência de uso em smartphones e tablets
- [ ] 💬 **Notificações por WhatsApp:** alertas externos sobre vencimentos de contas
- [ ] ✉️ **Notificações por e-mail:** lembretes automáticos de compromissos financeiros
- [ ] ⚙️ **Preferências de notificação:** permitir que o usuário configure como e quando deseja receber seus lembretes
- [ ] 📈 **Aprimoramento dos relatórios:** ampliar as possibilidades de análise e acompanhamento financeiro

---

## 🤝 Contribuindo

Contribuições são bem-vindas!

1. Faça um *fork* do projeto
2. Crie uma branch para sua feature: `git checkout -b feature/minha-feature`
3. Faça o commit: `git commit -m "feat: minha feature"`
4. Envie para a branch: `git push origin feature/minha-feature`
5. Abra um *Pull Request*

---

## 👩‍💻 Autora

Desenvolvido por **Jycilene Oliveira**.

[![GitHub](https://img.shields.io/badge/GitHub-seu--usuario-181717?logo=github&logoColor=white)](https://github.com/seu-usuario)

---

<div align="center">

Feito com 💙 para ajudar pessoas a organizarem suas finanças.

</div>
