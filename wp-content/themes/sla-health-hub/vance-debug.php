<?php
/**
 * Vance Debug - REMOVE AFTER USE
 * Access: https://www.gastrohealthhub.com/wp-content/themes/sla-health-hub/vance-debug.php
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo '<h1>Vance Debug File - PHP is working</h1>';
echo '<p>PHP Version: ' . phpversion() . '</p>';
echo '<p>Time: ' . date('Y-m-d H:i:s') . '</p>';

// Try loading WordPress
$wp_load = dirname(__FILE__) . '/../../../wp-load.php';
echo '<p>wp-load.php path: ' . $wp_load . '</p>';
echo '<p>wp-load.php exists: ' . (file_exists($wp_load) ? 'YES' : 'NO') . '</p>';

if (file_exists($wp_load)) {
    echo '<hr><h2>Loading WordPress...</h2>';
    ob_start();
    try {
        require_once $wp_load;
        $output = ob_get_clean();
        echo '<p style="color:green;">WordPress loaded successfully!</p>';
        echo '<p>Active theme: ' . get_option('stylesheet') . '</p>';
        echo '<p>Active plugins:</p><ul>';
        foreach (get_option('active_plugins') as $plugin) {
            echo '<li>' . esc_html($plugin) . '</li>';
        }
        echo '</ul>';
    } catch (Throwable $e) {
        $output = ob_get_clean();
        echo '<p style="color:red;">ERROR loading WordPress:</p>';
        echo '<pre style="background:#f5f5f5; padding:15px; border:1px solid red;">';
        echo 'Type: ' . get_class($e) . "\n";
        echo 'Message: ' . $e->getMessage() . "\n";
        echo 'File: ' . $e->getFile() . "\n";
        echo 'Line: ' . $e->getLine() . "\n\n";
        echo 'Trace:' . "\n" . $e->getTraceAsString();
        echo '</pre>';
        if ($output) {
            echo '<h3>Output before error:</h3><pre>' . htmlspecialchars($output) . '</pre>';
        }
    }
} else {
    echo '<p style="color:red;">Could not find wp-load.php!</p>';
}
