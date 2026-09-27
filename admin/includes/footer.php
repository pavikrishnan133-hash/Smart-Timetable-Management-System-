    </div><!-- /app-content -->
</main>
</div><!-- /app-container -->

<!-- Toast Notification Container -->
<div class="toast-container"></div>

<!-- Core Scripts -->
<script src="<?= $baseUrl ?>/assets/js/app.js"></script>

<?php if (isset($pageScript)): ?>
    <script src="<?= $baseUrl ?>/assets/js/<?= $pageScript ?>"></script>
<?php endif; ?>

<script>
    // Show mobile menu toggle on mobile screens
    function checkMobile() {
        const toggle = document.getElementById('mobileSidebarToggle');
        if (toggle) {
            toggle.style.display = window.innerWidth <= 768 ? 'inline-flex' : 'none';
        }
    }
    window.addEventListener('resize', checkMobile);
    document.addEventListener('DOMContentLoaded', checkMobile);
</script>
</body>
</html>
