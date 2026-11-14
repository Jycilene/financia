-- ================================================================
-- FINANCIA — SCRIPT COMPLETO DE ESTRUTURA E DADOS PARA O SUPABASE
-- Execute este script no SQL Editor do Supabase (supabase.com)
-- ================================================================

-- 1. TABELA: categorias
CREATE TABLE IF NOT EXISTS public.categorias (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('receita', 'despesa')),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. TABELA: usuarios
CREATE TABLE IF NOT EXISTS public.usuarios (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil VARCHAR(20) DEFAULT 'user' CHECK (perfil IN ('admin', 'user')),
    status VARCHAR(20) DEFAULT 'ativa',
    ativo BOOLEAN DEFAULT TRUE,
    foto_perfil VARCHAR(255) DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. TABELA: despesas_fixas
CREATE TABLE IF NOT EXISTS public.despesas_fixas (
    id SERIAL PRIMARY KEY,
    usuario_id INT NOT NULL REFERENCES public.usuarios(id) ON DELETE CASCADE,
    categoria_id INT REFERENCES public.categorias(id) ON DELETE SET NULL,
    nome VARCHAR(150) NOT NULL,
    valor NUMERIC(10,2) NOT NULL,
    dia_vencimento INT NOT NULL CHECK (dia_vencimento >= 1 AND dia_vencimento <= 31),
    data_inicio DATE NOT NULL,
    recorrencia VARCHAR(20) DEFAULT 'mensal',
    tipo_compromisso VARCHAR(20) DEFAULT 'recorrente',
    valor_total NUMERIC(10,2) DEFAULT NULL,
    quantidade_parcelas INT DEFAULT NULL,
    descricao TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. TABELA: movimentacoes
CREATE TABLE IF NOT EXISTS public.movimentacoes (
    id SERIAL PRIMARY KEY,
    usuario_id INT NOT NULL REFERENCES public.usuarios(id) ON DELETE CASCADE,
    categoria_id INT NOT NULL REFERENCES public.categorias(id),
    descricao VARCHAR(255) NOT NULL,
    valor NUMERIC(10,2) NOT NULL,
    tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('receita', 'despesa')),
    data_movimentacao DATE NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. TABELA: pagamentos_fixas
CREATE TABLE IF NOT EXISTS public.pagamentos_fixas (
    id SERIAL PRIMARY KEY,
    despesa_fixa_id INT NOT NULL REFERENCES public.despesas_fixas(id) ON DELETE CASCADE,
    usuario_id INT NOT NULL REFERENCES public.usuarios(id) ON DELETE CASCADE,
    mes_referencia INT NOT NULL CHECK (mes_referencia >= 1 AND mes_referencia <= 12),
    ano_referencia INT NOT NULL,
    valor NUMERIC(10,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'pendente' CHECK (status IN ('pendente', 'paga', 'atrasada')),
    numero_parcela INT DEFAULT NULL,
    observacao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. TABELA: notificacoes
CREATE TABLE IF NOT EXISTS public.notificacoes (
    id SERIAL PRIMARY KEY,
    usuario_id INT NOT NULL REFERENCES public.usuarios(id) ON DELETE CASCADE,
    pagamento_fixo_id INT REFERENCES public.pagamentos_fixas(id) ON DELETE CASCADE,
    tipo VARCHAR(50) NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    mensagem TEXT NOT NULL,
    lida BOOLEAN DEFAULT FALSE,
    excluida BOOLEAN DEFAULT FALSE,
    data_leitura TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ================================================================
-- CARGA INICIAL DE CATEGORIAS PADRÃO (Sem duplicar)
-- ================================================================
INSERT INTO public.categorias (id, nome, tipo) VALUES (1, 'Salário', 'receita') ON CONFLICT (id) DO NOTHING;
INSERT INTO public.categorias (id, nome, tipo) VALUES (2, 'Serviços', 'receita') ON CONFLICT (id) DO NOTHING;
INSERT INTO public.categorias (id, nome, tipo) VALUES (3, 'Outras receitas', 'receita') ON CONFLICT (id) DO NOTHING;
INSERT INTO public.categorias (id, nome, tipo) VALUES (4, 'Alimentação', 'despesa') ON CONFLICT (id) DO NOTHING;
INSERT INTO public.categorias (id, nome, tipo) VALUES (5, 'Transporte', 'despesa') ON CONFLICT (id) DO NOTHING;
INSERT INTO public.categorias (id, nome, tipo) VALUES (6, 'Moradia', 'despesa') ON CONFLICT (id) DO NOTHING;
INSERT INTO public.categorias (id, nome, tipo) VALUES (7, 'Saúde', 'despesa') ON CONFLICT (id) DO NOTHING;
INSERT INTO public.categorias (id, nome, tipo) VALUES (8, 'Educação', 'despesa') ON CONFLICT (id) DO NOTHING;
INSERT INTO public.categorias (id, nome, tipo) VALUES (9, 'Lazer', 'despesa') ON CONFLICT (id) DO NOTHING;
INSERT INTO public.categorias (id, nome, tipo) VALUES (10, 'Outras despesas', 'despesa') ON CONFLICT (id) DO NOTHING;

-- ================================================================
-- CARGA INICIAL DE USUÁRIOS (Admin e Pessoal - Senha padrão: 123456)
-- ================================================================
INSERT INTO public.usuarios (id, nome, email, senha, perfil, status, ativo, foto_perfil) VALUES (1, 'Administrador', 'admin@financia.com', '$2y$10$2VFpUf6Pt4NRNCbU6nkeaOcjWMrf3txhCNyB6QOvlYDy2MRkKLRaK', 'admin', 'ativa', TRUE, NULL) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.usuarios (id, nome, email, senha, perfil, status, ativo, foto_perfil) VALUES (2, 'jycilene', 'oliveirajycilene@gmail.com', '$2y$10$okzWnlBt0OCVa8gWE1aGe.tVt0Wzw/JZNLsLfz6XZatkqcdH3j.ja', 'user', 'ativa', TRUE, 'uploads/perfil/perfil_2_1793898294.jpeg') ON CONFLICT (id) DO NOTHING;

-- ================================================================
-- CARGA DAS DESPESAS FIXAS ATUAIS
-- ================================================================
INSERT INTO public.despesas_fixas (id, usuario_id, categoria_id, nome, valor, dia_vencimento, data_inicio, recorrencia, tipo_compromisso, valor_total, quantidade_parcelas, descricao, ativo) VALUES (1, 2, 10, 'plano celular', 60.00, 24, '2026-11-01', 'mensal', 'recorrente', NULL, NULL, '', TRUE) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.despesas_fixas (id, usuario_id, categoria_id, nome, valor, dia_vencimento, data_inicio, recorrencia, tipo_compromisso, valor_total, quantidade_parcelas, descricao, ativo) VALUES (2, 2, 10, 'internet', 100.00, 31, '2026-10-31', 'mensal', 'recorrente', NULL, NULL, '', TRUE) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.despesas_fixas (id, usuario_id, categoria_id, nome, valor, dia_vencimento, data_inicio, recorrencia, tipo_compromisso, valor_total, quantidade_parcelas, descricao, ativo) VALUES (3, 2, 10, 'cartão', 1000.00, 5, '2026-11-01', 'mensal', 'recorrente', NULL, NULL, '', TRUE) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.despesas_fixas (id, usuario_id, categoria_id, nome, valor, dia_vencimento, data_inicio, recorrencia, tipo_compromisso, valor_total, quantidade_parcelas, descricao, ativo) VALUES (4, 2, 8, 'escola', 300.00, 9, '2026-11-05', 'mensal', 'recorrente', NULL, NULL, '', TRUE) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.despesas_fixas (id, usuario_id, categoria_id, nome, valor, dia_vencimento, data_inicio, recorrencia, tipo_compromisso, valor_total, quantidade_parcelas, descricao, ativo) VALUES (5, 2, 8, 'facudade', 580.00, 28, '2026-11-05', 'mensal', 'recorrente', NULL, NULL, '', TRUE) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.despesas_fixas (id, usuario_id, categoria_id, nome, valor, dia_vencimento, data_inicio, recorrencia, tipo_compromisso, valor_total, quantidade_parcelas, descricao, ativo) VALUES (6, 2, 10, 'empretimo', 200.00, 6, '2026-11-01', 'mensal', 'recorrente', NULL, NULL, 'sao 12 parcelas de 200', TRUE) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.despesas_fixas (id, usuario_id, categoria_id, nome, valor, dia_vencimento, data_inicio, recorrencia, tipo_compromisso, valor_total, quantidade_parcelas, descricao, ativo) VALUES (8, 2, 10, 'celular', 416.67, 10, '2026-11-05', 'mensal', 'parcelada', 5000.00, 12, '', TRUE) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.despesas_fixas (id, usuario_id, categoria_id, nome, valor, dia_vencimento, data_inicio, recorrencia, tipo_compromisso, valor_total, quantidade_parcelas, descricao, ativo) VALUES (9, 2, 10, 'celular', 416.67, 10, '2026-12-05', 'mensal', 'parcelada', 5000.00, 12, '', TRUE) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.despesas_fixas (id, usuario_id, categoria_id, nome, valor, dia_vencimento, data_inicio, recorrencia, tipo_compromisso, valor_total, quantidade_parcelas, descricao, ativo) VALUES (10, 2, 10, 'camera', 250.00, 10, '2026-12-05', 'mensal', 'parcelada', 3000.00, 12, '', TRUE) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.despesas_fixas (id, usuario_id, categoria_id, nome, valor, dia_vencimento, data_inicio, recorrencia, tipo_compromisso, valor_total, quantidade_parcelas, descricao, ativo) VALUES (11, 2, 10, 'luz', 100.00, 10, '2026-11-07', 'mensal', 'recorrente', NULL, NULL, '', TRUE) ON CONFLICT (id) DO NOTHING;

-- ================================================================
-- CARGA DAS MOVIMENTAÇÕES ATUAIS
-- ================================================================
INSERT INTO public.movimentacoes (id, usuario_id, categoria_id, descricao, valor, tipo, data_movimentacao) VALUES (2, 2, 4, 'supermercado', 600.00, 'despesa', '2026-10-28') ON CONFLICT (id) DO NOTHING;
INSERT INTO public.movimentacoes (id, usuario_id, categoria_id, descricao, valor, tipo, data_movimentacao) VALUES (3, 1, 4, 'supermercado', 100.00, 'despesa', '2026-10-30') ON CONFLICT (id) DO NOTHING;
INSERT INTO public.movimentacoes (id, usuario_id, categoria_id, descricao, valor, tipo, data_movimentacao) VALUES (4, 1, 1, 'recebimento', 1000.00, 'receita', '2026-10-30') ON CONFLICT (id) DO NOTHING;
INSERT INTO public.movimentacoes (id, usuario_id, categoria_id, descricao, valor, tipo, data_movimentacao) VALUES (6, 2, 10, 'plano celular', 60.00, 'despesa', '2026-11-05') ON CONFLICT (id) DO NOTHING;
INSERT INTO public.movimentacoes (id, usuario_id, categoria_id, descricao, valor, tipo, data_movimentacao) VALUES (7, 2, 8, 'facudade', 580.00, 'despesa', '2026-11-30') ON CONFLICT (id) DO NOTHING;
INSERT INTO public.movimentacoes (id, usuario_id, categoria_id, descricao, valor, tipo, data_movimentacao) VALUES (8, 2, 1, 'salario', 5.00, 'receita', '2026-11-06') ON CONFLICT (id) DO NOTHING;
INSERT INTO public.movimentacoes (id, usuario_id, categoria_id, descricao, valor, tipo, data_movimentacao) VALUES (9, 2, 1, 'caixa', 10.00, 'receita', '2026-11-05') ON CONFLICT (id) DO NOTHING;

-- ================================================================
-- CARGA DOS PAGAMENTOS FIXOS / PARCELAS ATUAIS
-- ================================================================
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (1, 1, 2, 10, 2026, 60.00, '2026-10-24', '2026-10-31', 'paga', NULL) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (2, 2, 2, 10, 2026, 100.00, '2026-10-31', NULL, 'pendente', NULL) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (3, 1, 2, 11, 2026, 60.00, '2026-11-24', '2026-11-01', 'paga', NULL) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (4, 2, 2, 11, 2026, 100.00, '2026-11-30', '2026-11-01', 'paga', NULL) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (5, 3, 2, 10, 2026, 1000.00, '2026-10-05', NULL, 'pendente', NULL) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (6, 3, 2, 11, 2026, 1000.00, '2026-11-05', '2026-11-01', 'paga', NULL) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (7, 4, 2, 11, 2026, 300.00, '2026-11-09', '2026-11-05', 'paga', NULL) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (8, 5, 2, 11, 2026, 580.00, '2026-11-28', NULL, 'pendente', NULL) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (9, 6, 2, 11, 2026, 200.00, '2026-11-06', NULL, 'pendente', NULL) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (10, 8, 2, 11, 2026, 416.67, '2026-11-05', NULL, 'pendente', 1) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (11, 8, 2, 12, 2026, 416.67, '2026-12-05', NULL, 'pendente', 2) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (12, 8, 2, 1, 2027, 416.67, '2027-01-05', NULL, 'pendente', 3) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (13, 8, 2, 2, 2027, 416.67, '2027-02-05', NULL, 'pendente', 4) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (14, 8, 2, 3, 2027, 416.67, '2027-03-05', NULL, 'pendente', 5) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (15, 8, 2, 4, 2027, 416.67, '2027-04-05', NULL, 'pendente', 6) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (16, 8, 2, 5, 2027, 416.67, '2027-05-05', NULL, 'pendente', 7) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (17, 8, 2, 6, 2027, 416.67, '2027-06-05', NULL, 'pendente', 8) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (18, 8, 2, 7, 2027, 416.67, '2027-07-05', NULL, 'pendente', 9) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (19, 8, 2, 8, 2027, 416.67, '2027-08-05', NULL, 'pendente', 10) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (20, 8, 2, 9, 2027, 416.67, '2027-09-05', NULL, 'pendente', 11) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (21, 8, 2, 10, 2027, 416.67, '2027-10-05', NULL, 'pendente', 12) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (22, 9, 2, 12, 2026, 416.67, '2026-12-05', NULL, 'pendente', 1) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (23, 9, 2, 1, 2027, 416.67, '2027-01-05', NULL, 'pendente', 2) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (24, 9, 2, 2, 2027, 416.67, '2027-02-05', NULL, 'pendente', 3) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (25, 9, 2, 3, 2027, 416.67, '2027-03-05', NULL, 'pendente', 4) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (26, 9, 2, 4, 2027, 416.67, '2027-04-05', NULL, 'pendente', 5) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (27, 9, 2, 5, 2027, 416.67, '2027-05-05', NULL, 'pendente', 6) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (28, 9, 2, 6, 2027, 416.67, '2027-06-05', NULL, 'pendente', 7) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (29, 9, 2, 7, 2027, 416.67, '2027-07-05', NULL, 'pendente', 8) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (30, 9, 2, 8, 2027, 416.67, '2027-08-05', NULL, 'pendente', 9) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (31, 9, 2, 9, 2027, 416.67, '2027-09-05', NULL, 'pendente', 10) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (32, 9, 2, 10, 2027, 416.67, '2027-10-05', NULL, 'pendente', 11) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (33, 9, 2, 11, 2027, 416.67, '2027-11-05', NULL, 'pendente', 12) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (34, 9, 2, 11, 2026, 416.67, '2026-11-10', NULL, 'pendente', NULL) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (35, 10, 2, 12, 2026, 250.00, '2026-12-05', NULL, 'pendente', 1) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (36, 10, 2, 1, 2027, 250.00, '2027-01-05', NULL, 'pendente', 2) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (37, 10, 2, 2, 2027, 250.00, '2027-02-05', NULL, 'pendente', 3) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (38, 10, 2, 3, 2027, 250.00, '2027-03-05', NULL, 'pendente', 4) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (39, 10, 2, 4, 2027, 250.00, '2027-04-05', NULL, 'pendente', 5) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (40, 10, 2, 5, 2027, 250.00, '2027-05-05', NULL, 'pendente', 6) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (41, 10, 2, 6, 2027, 250.00, '2027-06-05', NULL, 'pendente', 7) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (42, 10, 2, 7, 2027, 250.00, '2027-07-05', NULL, 'pendente', 8) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (43, 10, 2, 8, 2027, 250.00, '2027-08-05', NULL, 'pendente', 9) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (44, 10, 2, 9, 2027, 250.00, '2027-09-05', NULL, 'pendente', 10) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (45, 10, 2, 10, 2027, 250.00, '2027-10-05', NULL, 'pendente', 11) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (46, 10, 2, 11, 2027, 250.00, '2027-11-05', NULL, 'pendente', 12) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (47, 10, 2, 11, 2026, 250.00, '2026-11-10', '2026-11-07', 'paga', NULL) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.pagamentos_fixas (id, despesa_fixa_id, usuario_id, mes_referencia, ano_referencia, valor, data_vencimento, data_pagamento, status, numero_parcela) VALUES (48, 11, 2, 11, 2026, 100.00, '2026-11-10', NULL, 'pendente', NULL) ON CONFLICT (id) DO NOTHING;

-- ================================================================
-- ALINHAMENTO E RESSINCRONIZAÇÃO DAS SEQUÊNCIAS (ESSENCIAL!)
-- Garante que novos cadastros no Supabase recebam IDs válidos sem colisão
-- ================================================================
SELECT setval('public.usuarios_id_seq', COALESCE((SELECT MAX(id) FROM public.usuarios), 0) + 1, false);
SELECT setval('public.categorias_id_seq', COALESCE((SELECT MAX(id) FROM public.categorias), 0) + 1, false);
SELECT setval('public.despesas_fixas_id_seq', COALESCE((SELECT MAX(id) FROM public.despesas_fixas), 0) + 1, false);
SELECT setval('public.movimentacoes_id_seq', COALESCE((SELECT MAX(id) FROM public.movimentacoes), 0) + 1, false);
SELECT setval('public.pagamentos_fixas_id_seq', COALESCE((SELECT MAX(id) FROM public.pagamentos_fixas), 0) + 1, false);
SELECT setval('public.notificacoes_id_seq', COALESCE((SELECT MAX(id) FROM public.notificacoes), 0) + 1, false);
