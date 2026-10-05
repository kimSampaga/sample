    </main>

    <!-- Footer -->
    <footer class="footer" style="margin-left: var(--sidebar-width); margin-top: 40px; padding: 20px 30px; transition: all 0.3s ease;">
        <p>&copy; <?php echo date('Y'); ?> Inventory Management System. All rights reserved.</p>
        <p style="font-size: 11px; margin-top: 5px;">Last updated: <?php echo date('Y-m-d H:i:s'); ?></p>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Layout Toggle Script -->
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 992) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Close sidebar when a menu item is clicked
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    document.getElementById('sidebar').classList.remove('active');
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 992) {
                sidebar.classList.remove('active');
            }
        });

        // Load alert count
        function loadAlertCount() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'api/get-alert-count.php', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const data = JSON.parse(xhr.responseText);
                    const countBadge = document.getElementById('alert-count');
                    if (data.count > 0) {
                        countBadge.textContent = data.count;
                        countBadge.style.display = 'inline-block';
                    } else {
                        countBadge.style.display = 'none';
                    }
                }
            };
            xhr.send();
        }

        // Load alert count on page load and refresh every 30 seconds
        document.addEventListener('DOMContentLoaded', function() {
            loadAlertCount();
            setInterval(loadAlertCount, 30000);
        });

        // Utility function: Format currency
        function formatCurrency(value) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(value);
        }

        // Utility function: Format number with thousand separators
        function formatNumber(value) {
            return new Intl.NumberFormat('en-US').format(value);
        }

        // Utility function: Show toast notification
        function showToast(message, type = 'info') {
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'danger' ? 'danger' : 'info'} border-0" 
                     role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            const toastContainer = document.createElement('div');
            toastContainer.innerHTML = toastHtml;
            toastContainer.style.position = 'fixed';
            toastContainer.style.bottom = '20px';
            toastContainer.style.right = '20px';
            toastContainer.style.zIndex = '9999';
            
            document.body.appendChild(toastContainer);
            
            const toast = new bootstrap.Toast(toastContainer.querySelector('.toast'));
            toast.show();
            
            setTimeout(() => toastContainer.remove(), 3500);
        }

        // Common form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form[data-validate="true"]');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });
        });
    </script>

    <!-- Additional Page Scripts -->
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo htmlspecialchars($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
