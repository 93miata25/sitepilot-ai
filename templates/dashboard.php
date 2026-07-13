<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$health    = $results['health'];
$wordpress = $results['wordpress'];
$server    = $results['server'];
$theme     = $results['theme'];
$plugins   = $results['plugins'];
$content   = $results['content'];
$security  = $results['security'];
?>
<div class="wrap sitepilot-wrap" id="sitepilot-dashboard">
    <div class="sitepilot-header">
        <div>
            <h1><?php esc_html_e( 'SitePilot AI', 'sitepilot-ai' ); ?></h1>
            <p><?php esc_html_e( 'Website health and optimization dashboard', 'sitepilot-ai' ); ?></p>
        </div>
        <button type="button" class="button button-primary sitepilot-scan-button" id="sitepilot-run-scan">
            <?php esc_html_e( 'Run New Scan', 'sitepilot-ai' ); ?>
        </button>
    </div>

    <div class="sitepilot-notice" id="sitepilot-scan-status" hidden></div>

    <section class="sitepilot-hero-card">
        <div class="sitepilot-score-ring" style="--sitepilot-score: <?php echo esc_attr( (string) $health['score'] ); ?>;">
            <span class="sitepilot-score-value" data-sitepilot-field="health.score"><?php echo esc_html( (string) $health['score'] ); ?></span>
            <small>/100</small>
        </div>
        <div>
            <span class="sitepilot-eyebrow"><?php esc_html_e( 'Website Health', 'sitepilot-ai' ); ?></span>
            <h2 data-sitepilot-field="health.label"><?php echo esc_html( $health['label'] ); ?></h2>
            <p>
                <?php esc_html_e( 'Last scan:', 'sitepilot-ai' ); ?>
                <span data-sitepilot-field="scanned_at"><?php echo esc_html( $results['scanned_at'] ); ?></span>
            </p>
        </div>
    </section>

    <div class="sitepilot-grid sitepilot-grid--stats">
        <article class="sitepilot-card"><span><?php esc_html_e( 'WordPress', 'sitepilot-ai' ); ?></span><strong data-sitepilot-field="wordpress.version"><?php echo esc_html( $wordpress['version'] ); ?></strong></article>
        <article class="sitepilot-card"><span><?php esc_html_e( 'PHP', 'sitepilot-ai' ); ?></span><strong data-sitepilot-field="server.php_version"><?php echo esc_html( $server['php_version'] ); ?></strong></article>
        <article class="sitepilot-card"><span><?php esc_html_e( 'Active Plugins', 'sitepilot-ai' ); ?></span><strong data-sitepilot-field="plugins.active"><?php echo esc_html( (string) $plugins['active'] ); ?></strong></article>
        <article class="sitepilot-card"><span><?php esc_html_e( 'Plugin Updates', 'sitepilot-ai' ); ?></span><strong data-sitepilot-field="plugins.update_count"><?php echo esc_html( (string) $plugins['update_count'] ); ?></strong></article>
    </div>

    <div class="sitepilot-grid sitepilot-grid--two">
        <section class="sitepilot-panel">
            <h2><?php esc_html_e( 'Website', 'sitepilot-ai' ); ?></h2>
            <dl class="sitepilot-details">
                <div><dt><?php esc_html_e( 'Theme', 'sitepilot-ai' ); ?></dt><dd data-sitepilot-field="theme.name"><?php echo esc_html( $theme['name'] ); ?></dd></div>
                <div><dt><?php esc_html_e( 'Theme version', 'sitepilot-ai' ); ?></dt><dd data-sitepilot-field="theme.version"><?php echo esc_html( $theme['version'] ); ?></dd></div>
                <div><dt><?php esc_html_e( 'Published posts', 'sitepilot-ai' ); ?></dt><dd data-sitepilot-field="content.published_posts"><?php echo esc_html( (string) $content['published_posts'] ); ?></dd></div>
                <div><dt><?php esc_html_e( 'Published pages', 'sitepilot-ai' ); ?></dt><dd data-sitepilot-field="content.published_pages"><?php echo esc_html( (string) $content['published_pages'] ); ?></dd></div>
                <div><dt><?php esc_html_e( 'Media items', 'sitepilot-ai' ); ?></dt><dd data-sitepilot-field="content.media_items"><?php echo esc_html( (string) $content['media_items'] ); ?></dd></div>
            </dl>
        </section>

        <section class="sitepilot-panel">
            <h2><?php esc_html_e( 'Server', 'sitepilot-ai' ); ?></h2>
            <dl class="sitepilot-details">
                <div><dt><?php esc_html_e( 'Database', 'sitepilot-ai' ); ?></dt><dd data-sitepilot-field="server.database_version"><?php echo esc_html( $server['database_version'] ); ?></dd></div>
                <div><dt><?php esc_html_e( 'Memory limit', 'sitepilot-ai' ); ?></dt><dd data-sitepilot-field="server.memory_limit"><?php echo esc_html( $server['memory_limit'] ); ?></dd></div>
                <div><dt><?php esc_html_e( 'WordPress memory', 'sitepilot-ai' ); ?></dt><dd data-sitepilot-field="server.wp_memory_limit"><?php echo esc_html( $server['wp_memory_limit'] ); ?></dd></div>
                <div><dt><?php esc_html_e( 'Upload limit', 'sitepilot-ai' ); ?></dt><dd data-sitepilot-field="server.upload_limit"><?php echo esc_html( $server['upload_limit'] ); ?></dd></div>
                <div><dt><?php esc_html_e( 'HTTPS', 'sitepilot-ai' ); ?></dt><dd data-sitepilot-boolean="server.https"><?php echo $server['https'] ? esc_html__( 'Enabled', 'sitepilot-ai' ) : esc_html__( 'Disabled', 'sitepilot-ai' ); ?></dd></div>
            </dl>
        </section>
    </div>

    <section class="sitepilot-panel">
        <h2><?php esc_html_e( 'Recommendations', 'sitepilot-ai' ); ?></h2>
        <div id="sitepilot-recommendations">
            <?php if ( empty( $health['issues'] ) ) : ?>
                <div class="sitepilot-empty-state"><?php esc_html_e( 'No important issues were found.', 'sitepilot-ai' ); ?></div>
            <?php else : ?>
                <ul class="sitepilot-issues">
                    <?php foreach ( $health['issues'] as $issue ) : ?>
                        <li class="sitepilot-issue sitepilot-issue--<?php echo esc_attr( $issue['severity'] ); ?>">
                            <?php echo esc_html( $issue['message'] ); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</div>
