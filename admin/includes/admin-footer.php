</div>
            <!-- End Content Area -->
        </main>
        <!-- End Main Content -->
    </div>
    <!-- End Admin Wrapper -->
    
    <!-- Additional Admin Scripts (if needed) -->
    <script>
    // Any additional page-specific scripts can go here
    
    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }, 5000);
        });
    });
    
    // Confirm delete actions
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('[data-confirm]');
        deleteButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                const message = this.getAttribute('data-confirm') || 'Are you sure you want to delete this item?';
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    });
    
    // Table row click handler (if needed)
    document.addEventListener('DOMContentLoaded', function() {
        const clickableRows = document.querySelectorAll('tr[data-href]');
        clickableRows.forEach(function(row) {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function() {
                window.location.href = this.dataset.href;
            });
        });
    });
    </script>
</body>
</html>