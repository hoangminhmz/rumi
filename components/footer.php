<?php
/**
 * RUMI - Footer Component
 * Closing tags và scripts
 */
?>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Hammer.js for swipe gestures -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js"></script>

    <!-- RUMI Scripts -->
    <script src="<?= ASSETS_URL ?>/js/app.js"></script>
    <?php if (isset($additionalScripts)): ?>
        <?= $additionalScripts ?>
    <?php endif; ?>
</body>
</html>
