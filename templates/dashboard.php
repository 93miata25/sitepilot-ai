<?php
if (! defined('ABSPATH')) {
    exit;
}

$sections = $scan['sections'];
?>
<div class="wrap sitepilot-ai" data-sitepilot-dashboard>
    <div class="sitepilot-header">
        <div>
            <h1><?php esc_html_e('SitePilot AI', 'sitepilot-ai'); ?></h1>
            <p><?php esc_html_e('Website health scanner and optimization assistant.', 'sitepilot-ai'); ?></p>
        </div>
        <button type="button" class="button button-primary" data-sitepilot-scan><?php esc_html_e('Run New Scan', 'sitepilot-ai'); ?></button>
    </div>

    <div class="sitepilot-hero">
        <div class="sitepilot-score" data-sitepilot-score><?php echo esc_html((string) $scan['score']); ?></div>
        <div>
            <div class="sitepilot-status" data-sitepilot-status><?php echo esc_html($scan['status']); ?></div>
            <p><?php printf(esc_html__('Last scanned %1$s in %2$d ms.', 'sitepilot-ai'), esc_html($scan['generated_at']), (int) $scan['duration_ms']); ?></p>
        </div>
    </div>

    <div class="sitepilot-grid">
        <section class="sitepilot-card">
            <h2><?php esc_html_e('WordPress', 'sitepilot-ai'); ?></h2>
            <dl>
                <dt><?php esc_html_e('Version', 'sitepilot-ai'); ?></dt><dd><?php echo esc_html($sections['wordpress']['version']); ?></dd>
                <dt><?php esc_html_e('Posts', 'sitepilot-ai'); ?></dt><dd><?php echo esc_html((string) $sections['wordpress']['posts']); ?></dd>
                <dt><?php esc_html_e('Pages', 'sitepilot-ai'); ?></dt><dd><?php echo esc_html((string) $sections['wordpress']['pages']); ?></dd>
                <dt><?php esc_html_e('Media', 'sitepilot-ai'); ?></dt><dd><?php echo esc_html((string) $sections['wordpress']['media']); ?></dd>
            </dl>
        </section>

        <section class="sitepilot-card">
            <h2><?php esc_html_e('Server', 'sitepilot-ai'); ?></h2>
            <dl>
                <dt><?php esc_html_e('PHP', 'sitepilot-ai'); ?></dt><dd><?php echo esc_html($sections['server']['php_version']); ?></dd>
                <dt><?php esc_html_e('Database', 'sitepilot-ai'); ?></dt><dd><?php echo esc_html($sections['server']['database_version']); ?></dd>
                <dt><?php esc_html_e('Memory', 'sitepilot-ai'); ?></dt><dd><?php echo esc_html($sections['server']['wp_memory_limit']); ?></dd>
                <dt><?php esc_html_e('Upload Limit', 'sitepilot-ai'); ?></dt><dd><?php echo esc_html($sections['server']['upload_limit']); ?></dd>
            </dl>
        </section>

        <section class="sitepilot-card">
            <h2><?php esc_html_e('Theme & Plugins', 'sitepilot-ai'); ?></h2>
            <dl>
                <dt><?php esc_html_e('Theme', 'sitepilot-ai'); ?></dt><dd><?php echo esc_html($sections['theme']['name']); ?></dd>
                <dt><?php esc_html_e('Theme Version', 'sitepilot-ai'); ?></dt><dd><?php echo esc_html($sections['theme']['version']); ?></dd>
                <dt><?php esc_html_e('Active Plugins', 'sitepilot-ai'); ?></dt><dd><?php echo esc_html((string) $sections['plugins']['active']); ?></dd>
                <dt><?php esc_html_e('Updates', 'sitepilot-ai'); ?></dt><dd><?php echo esc_html((string) $sections['plugins']['updates']); ?></dd>
            </dl>
        </section>
    </div>

    <section class="sitepilot-card sitepilot-issues">
        <h2><?php esc_html_e('Priority Improvements', 'sitepilot-ai'); ?></h2>
        <div data-sitepilot-issues>
            <?php if (empty($scan['issues'])) : ?>
                <p class="sitepilot-empty"><?php esc_html_e('No priority issues were detected.', 'sitepilot-ai'); ?></p>
            <?php else : ?>
                <?php foreach ($scan['issues'] as $issue) : ?>
                    <article class="sitepilot-issue sitepilot-severity-<?php echo esc_attr($issue['severity']); ?>">
                        <div><strong><?php echo esc_html($issue['title']); ?></strong><p><?php echo esc_html($issue['fix']); ?></p></div>
                        <span>+<?php echo esc_html((string) $issue['impact']); ?></span>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
