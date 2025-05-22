</main>
        </div>
    </div>
    
    <script>
    // Script común para el menú móvil
    document.addEventListener('DOMContentLoaded', function() {
        const menuButton = document.getElementById('menuButton');
        const mobileMenu = document.getElementById('mobileMenu');
        
        if (menuButton && mobileMenu) {
            menuButton.addEventListener('click', function() {
                if (mobileMenu.classList.contains('hidden')) {
                    mobileMenu.classList.remove('hidden');
                } else {
                    mobileMenu.classList.add('hidden');
                }
            });
        }
    });
    </script>
    
    <?php if (isset($pageScript)): ?>
    <script src="assets/js/<?php echo $pageScript; ?>.js"></script>
    <?php endif; ?>
</body>
</html>