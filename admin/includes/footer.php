        </div>
    </div>

    <!-- Argon Scripts -->
    <script src="/assets/vendor/jquery/dist/jquery.min.js"></script>
    <script src="/assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/vendor/js-cookie/js.cookie.js"></script>
    <script src="/assets/vendor/jquery.scrollbar/jquery.scrollbar.min.js"></script>
    <script src="/assets/vendor/jquery-scroll-lock/dist/jquery-scrollLock.min.js"></script>
    <script src="/assets/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="/assets/vendor/chart.js/dist/Chart.extension.js"></script>
    
    <!-- Argon JS -->
    <script src="/assets/js/argon.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Enable tooltips
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
        
        // Enable popovers
        $(function () {
            $('[data-toggle="popover"]').popover();
        });
        
        // Handle RTL
        document.documentElement.dir = 'rtl';
        
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
    
    <?php if (function_exists('custom_admin_footer')) custom_admin_footer(); ?>
    
</body>
</html>
