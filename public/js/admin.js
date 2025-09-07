// Admin Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert.classList.contains('alert-dismissible')) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            }
        }, 5000);
    });
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // Dynamic slug generation
    const titleField = document.querySelector('#title');
    const slugField = document.querySelector('#slug');
    
    if (titleField && slugField) {
        titleField.addEventListener('input', function() {
            const title = this.value;
            const slug = title.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
            slugField.value = slug;
        });
    }
    
    // Character counter for meta description
    const metaDescription = document.querySelector('#meta_description');
    if (metaDescription) {
        const counter = document.createElement('small');
        counter.className = 'text-muted';
        metaDescription.parentNode.appendChild(counter);
        
        function updateCounter() {
            const length = metaDescription.value.length;
            const recommended = 160;
            counter.textContent = `${length}/${recommended} characters`;
            
            if (length > recommended) {
                counter.className = 'text-warning';
            } else if (length > recommended - 20) {
                counter.className = 'text-info';
            } else {
                counter.className = 'text-muted';
            }
        }
        
        metaDescription.addEventListener('input', updateCounter);
        updateCounter();
    }
    
    // Preview functionality
    const previewButtons = document.querySelectorAll('.preview-btn');
    previewButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            window.open(url, 'preview', 'width=1200,height=800,scrollbars=yes');
        });
    });
    
    // Auto-save functionality for forms
    const autoSaveForms = document.querySelectorAll('[data-autosave]');
    autoSaveForms.forEach(function(form) {
        let autoSaveTimer;
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(function(input) {
            input.addEventListener('input', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(function() {
                    // Auto-save logic here
                    console.log('Auto-saving...');
                }, 3000);
            });
        });
    });
    
    // Image upload preview
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = input.parentNode.querySelector('.image-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'image-preview mt-2';
                        input.parentNode.appendChild(preview);
                    }
                    preview.innerHTML = `<img src="${e.target.result}" style="max-width: 200px; max-height: 200px; border-radius: 8px;">`;
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Sortable functionality for layout management
    if (typeof Sortable !== 'undefined') {
        const sortableElements = document.querySelectorAll('.sortable');
        sortableElements.forEach(function(element) {
            new Sortable(element, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function() {
                    // Update order logic here
                    console.log('Order updated');
                }
            });
        });
    }
    
    // Rich text editor integration
    const editorElements = document.querySelectorAll('.rich-editor');
    editorElements.forEach(function(element) {
        // Initialize rich text editor here
        // This will be replaced with the CodePen editor
    });
    
    // Toast notifications
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} toast-notification`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideIn 0.3s ease;
        `;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(function() {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(function() {
                toast.remove();
            }, 300);
        }, 3000);
    }
    
    // Add CSS animations for toast
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    
    // Expose toast function globally
    window.showToast = showToast;
    
    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showToast('Please fill in all required fields', 'error');
            }
        });
    });
    
    // Search functionality
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(function(input) {
        let searchTimer;
        input.addEventListener('input', function() {
            clearTimeout(searchTimer);
            const query = this.value;
            const target = this.getAttribute('data-target');
            
            searchTimer = setTimeout(function() {
                if (target) {
                    // Perform search
                    console.log(`Searching for: ${query} in ${target}`);
                }
            }, 500);
        });
    });
});