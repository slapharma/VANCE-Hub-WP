    <!-- Dashboard Minimal Footer -->
    <footer class="main-footer" style="margin-top: 0; padding: 24px 0; border-top: 1px solid #e2e8f0; background: #fff;">
        <div class="container">
            <div class="footer-bottom" style="margin-top: 0; padding-top: 0; border-top: none; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div style="display: flex; align-items: center; gap: 24px;">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:block;">
                        <?php
                        $f_logo = vance_get_theme_mod('vance_footer_logo');
                        if($f_logo): ?>
                            <img src="<?php echo esc_url($f_logo); ?>" alt="Vance Medical" style="height: 30px; opacity: 0.6; transition: 0.3s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.6">
                        <?php else: ?>
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/logo.png" alt="Vance Medical" style="height: 30px; opacity: 0.6; transition: 0.3s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.6">
                        <?php endif; ?>
                    </a>
                    <div class="copyright" style="color: #64748b; font-size: 13px;"><?php echo esc_html(vance_get_theme_mod('vance_footer_copyright', '© ' . date('Y') . ' Vance Medical Foods Ltd. All rights reserved.')); ?></div>
                </div>

                <div class="footer-links" style="font-size: 13px;">
                    <a href="https://gastrohealthhub.com/privacy-policy/" style="color: #64748b; text-decoration: none; margin-left: 16px;" onmouseover="this.style.color='#008080'" onmouseout="this.style.color='#64748b'">Privacy Policy</a>
                    <a href="https://gastrohealthhub.com/terms-of-use/" style="color: #64748b; text-decoration: none; margin-left: 16px;" onmouseover="this.style.color='#008080'" onmouseout="this.style.color='#64748b'">Terms of Use</a>
                    <a href="https://gastrohealthhub.com/cookie-policy-uk/" style="color: #64748b; text-decoration: none; margin-left: 16px;" onmouseover="this.style.color='#008080'" onmouseout="this.style.color='#64748b'">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>
    <?php include get_template_directory() . '/inc/quiz-modal.php'; ?>
    <?php include get_template_directory() . '/inc/clinical-info-modal.php'; ?>

    <script>
    function openGuestModal() {
        if(document.getElementById('guest-save-modal')) {
            document.getElementById('guest-save-modal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }
    function closeGuestModal() {
        if(document.getElementById('guest-save-modal')) {
            document.getElementById('guest-save-modal').style.display = 'none';
            document.body.style.overflow = '';
        }
    }
    </script>

    <?php wp_footer(); ?>
</body>
</html>
