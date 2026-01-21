/**
 * Admin Dashboard JavaScript
 * 
 * Handles:
 * - Mobile sidebar toggle
 * - Form validation
 * - Confirmation dialogs
 * - Dynamic interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    
    /**
     * Mobile Sidebar Toggle
     */
    const sidebarToggle = document.getElementById('mobileSidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                const isClickInsideSidebar = sidebar.contains(event.target);
                const isClickOnToggle = sidebarToggle.contains(event.target);
                
                if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            }
        });
    }
    
    /**
     * Delete Confirmation
     */
    const deleteButtons = document.querySelectorAll('.btn-delete, [data-action="delete"]');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const itemName = this.getAttribute('data-item') || 'this item';
            const confirmMessage = `Are you sure you want to delete ${itemName}? This action cannot be undone.`;
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    /**
     * Form Validation
     */
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'var(--admin-danger)';
                    
                    // Show error message
                    let errorMsg = field.parentElement.querySelector('.error-message');
                    if (!errorMsg) {
                        errorMsg = document.createElement('small');
                        errorMsg.className = 'error-message';
                        errorMsg.style.color = 'var(--admin-danger)';
                        errorMsg.style.display = 'block';
                        errorMsg.style.marginTop = '0.25rem';
                        errorMsg.textContent = 'This field is required';
                        field.parentElement.appendChild(errorMsg);
                    }
                } else {
                    field.style.borderColor = 'var(--admin-border)';
                    const errorMsg = field.parentElement.querySelector('.error-message');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            } else {
                // Form is valid - show loading state on submit button
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    
                    // Re-enable after 10 seconds as fallback (in case of error)
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }, 10000);
                }
                // Let form submit naturally
            }
        });
        
        // Remove error styling on input
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.style.borderColor = 'var(--admin-border)';
                const errorMsg = this.parentElement.querySelector('.error-message');
                if (errorMsg) {
                    errorMsg.remove();
                }
            });
        });
    });
    
    /**
     * Auto-hide alerts after 5 seconds
     */
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    /**
     * Character counter for textareas
     */
    const textareas = document.querySelectorAll('textarea[maxlength]');
    textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        counter.style.textAlign = 'right';
        counter.style.fontSize = '0.85rem';
        counter.style.color = 'var(--admin-text-light)';
        counter.style.marginTop = '0.25rem';
        
        textarea.parentElement.appendChild(counter);
        
        const updateCounter = () => {
            const currentLength = textarea.value.length;
            counter.textContent = `${currentLength} / ${maxLength} characters`;
            
            if (currentLength >= maxLength * 0.9) {
                counter.style.color = 'var(--admin-danger)';
            } else {
                counter.style.color = 'var(--admin-text-light)';
            }
        };
        
        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });
    
    /**
     * Toggle Active/Inactive Status
     */
    const toggleButtons = document.querySelectorAll('.btn-toggle-status');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const status = this.getAttribute('data-status');
            const newStatus = status === '1' ? 'inactive' : 'active';
            
            if (confirm(`Are you sure you want to mark this as ${newStatus}?`)) {
                // Submit the form or redirect
                window.location.href = this.href;
            }
        });
    });
    
    /**
     * Preview Image Upload
     */
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.getElementById('image-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.id = 'image-preview';
                        preview.style.maxWidth = '200px';
                        preview.style.marginTop = '1rem';
                        preview.style.borderRadius = '4px';
                        preview.style.border = '1px solid var(--admin-border)';
                        input.parentElement.appendChild(preview);
                    }
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    /**
     * Data Tables Enhancement (Simple sorting)
     */
    const tables = document.querySelectorAll('.admin-table');
    tables.forEach(table => {
        const headers = table.querySelectorAll('th[data-sortable]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.style.userSelect = 'none';
            
            header.addEventListener('click', function() {
                // Simple client-side sorting
                const column = this.cellIndex;
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                
                const isAscending = this.classList.contains('sort-asc');
                
                rows.sort((a, b) => {
                    const aValue = a.cells[column].textContent.trim();
                    const bValue = b.cells[column].textContent.trim();
                    
                    if (isAscending) {
                        return bValue.localeCompare(aValue);
                    } else {
                        return aValue.localeCompare(bValue);
                    }
                });
                
                // Remove sort classes from all headers
                headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
                
                // Add sort class to current header
                this.classList.toggle('sort-asc', !isAscending);
                this.classList.toggle('sort-desc', isAscending);
                
                // Reorder rows
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    });
    
    console.log('Admin Dashboard JavaScript Loaded Successfully');
});