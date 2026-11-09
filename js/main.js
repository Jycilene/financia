// Aguarda o HTML carregar completamente
document.addEventListener('DOMContentLoaded', () => {

    /* ========================================================
       1. FILTRO INTELIGENTE DE CATEGORIAS POR TIPO
       ======================================================== */
    const tipoSelect = document.getElementById('tipo');
    const categoriaSelect = document.getElementById('categoria_id');

    if (tipoSelect && categoriaSelect) {
        function filtrarCategorias() {
            const tipoSelecionado = tipoSelect.value;
            const options = categoriaSelect.querySelectorAll('option');

            options.forEach(option => {
                const tipoCategoria = option.getAttribute('data-tipo');
                
                // Se for a primeira opção padrão ("Selecione uma categoria..."), mantém visível
                if (!tipoCategoria) return;

                // Se o tipo da categoria for igual ao tipo selecionado, mostra; senão, esconde
                if (tipoCategoria === tipoSelecionado) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });

            // Se a opção que estava selecionada for escondida, reseta para a opção inicial
            const selectedOption = categoriaSelect.selectedOptions[0];
            if (selectedOption && selectedOption.style.display === 'none') {
                categoriaSelect.value = '';
            }
        }

        // Executa quando o usuário muda o tipo
        tipoSelect.addEventListener('change', filtrarCategorias);

        // Executa logo que a página carrega
        filtrarCategorias();
    }

    /* ========================================================
       2. FECHAMENTO SUAVE DE MENSAGENS DE SUCESSO (TOAST)
       ======================================================== */
    const alertas = document.querySelectorAll('.alerta-sucesso');
    alertas.forEach(alerta => {
        setTimeout(() => {
            alerta.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            alerta.style.opacity = '0';
            alerta.style.transform = 'translateY(-10px)';
            setTimeout(() => alerta.remove(), 600);
        }, 4000); // 4 segundos
    });

    /* ========================================================
       3. INICIALIZAÇÃO DE ÍCONES PROFISSIONAIS LUCIDE
       ======================================================== */
    if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
        lucide.createIcons();
    }
});

// Helper global para re-renderizar ícones se elementos forem inseridos dinamicamente
window.renderLucideIcons = function() {
    if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
        lucide.createIcons();
    }
};

