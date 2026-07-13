<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$status_label = $report['score'] >= 90 ? __( 'Excellent', 'sitepilot-ai' ) : ( $report['score'] >= 75 ? __( 'Good', 'sitepilot-ai' ) : ( $report['score'] >= 55 ? __( 'Needs attention', 'sitepilot-ai' ) : __( 'Poor', 'sitepilot-ai' ) ) );
?>
<div class="wrap sitepilot-wrap" id="sitepilot-performance">
    <div class="sitepilot-header">
        <div>
            <h1><?php esc_html_e( 'Performance', 'sitepilot-ai' ); ?></h1>
            <p><?php esc_html_e( 'Server, cache, media, and database performance checks.', 'sitepilot-ai' ); ?></p>
        </div>
        <button type="button" class="button button-primary sitepilot-scan-button" id="sitepilot-run-performance-scan">
            <span class="dashicons dashicons-update" aria-hidden="true"></span>
            <?php esc_html_e( 'Run Performance Scan', 'sitepilot-ai' ); ?>
        </button>
    </div>

    <div class="sitepilot-notice" data-sitepilot-performance-status hidden></div>

    <section class="sitepilot-hero-card sitepilot-performance-hero">
        <div class="sitepilot-score-ring" style="--sitepilot-score:<?php echo esc_attr( (string) $report['score'] ); ?>">
            <span class="sitepilot-score-value" data-performance-field="score"><?php echo esc_html( (string) $report['score'] ); ?></span>
            <small>/100</small>
        </div>
        <div class="sitepilot-hero-copy">
            <span class="sitepilot-eyebrow"><?php esc_html_e( 'Performance Score', 'sitepilot-ai' ); ?></span>
            <h2 data-performance-status-label><?php echo esc_html( $status_label ); ?></h2>
            <p><?php esc_html_e( 'Prioritized checks based on the current WordPress and server configuration.', 'sitepilot-ai' ); ?></p>
        </div>
        <div class="sitepilot-performance-summary">
            <div><span><?php esc_html_e( 'Media files', 'sitepilot-ai' ); ?></span><strong data-performance-field="media.count"><?php echo esc_html( number_format_i18n( $report['media']['count'] ) ); ?></strong></div>
            <div><span><?php esc_html_e( 'Media size', 'sitepilot-ai' ); ?></span><strong data-performance-field="media.size"><?php echo esc_html( $report['media']['size'] ); ?></strong></div>
            <div><span><?php esc_html_e( 'Autoload data', 'sitepilot-ai' ); ?></span><strong data-performance-field="checks.autoload.value"><?php echo esc_html( $report['checks']['autoload']['value'] ); ?></strong></div>
            <div><span><?php esc_html_e( 'Execution limit', 'sitepilot-ai' ); ?></span><strong><span data-performance-field="max_execution"><?php echo esc_html( (string) $report['max_execution'] ); ?></span>s</strong></div>
        </div>
    </section>

    <section class="sitepilot-panel">
        <div class="sitepilot-panel-heading">
            <div>
                <h2><?php esc_html_e( 'Performance checks', 'sitepilot-ai' ); ?></h2>
                <p><?php esc_html_e( 'Green checks are configured well. Yellow and red checks should be reviewed.', 'sitepilot-ai' ); ?></p>
            </div>
        </div>
        <div class="sitepilot-performance-checks" data-performance-checks>
            <?php foreach ( $report['checks'] as $key => $check ) : ?>
                <article class="sitepilot-performance-check sitepilot-performance-check--<?php echo esc_attr( $check['status'] ); ?>" data-performance-check="<?php echo esc_attr( $key ); ?>">
                    <span class="dashicons <?php echo 'good' === $check['status'] ? 'dashicons-yes-alt' : ( 'bad' === $check['status'] ? 'dashicons-dismiss' : 'dashicons-warning' ); ?>" aria-hidden="true"></span>
                    <div>
                        <h3><?php echo esc_html( $check['label'] ); ?></h3>
                        <p><?php echo esc_html( $check['advice'] ); ?></p>
                    </div>
                    <strong data-check-value><?php echo esc_html( $check['value'] ); ?></strong>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="sitepilot-panel" style="margin-top:18px">
        <div class="sitepilot-panel-heading">
            <div>
                <h2><?php esc_html_e( 'Top improvements', 'sitepilot-ai' ); ?></h2>
                <p><?php esc_html_e( 'Highest-impact performance improvements appear first.', 'sitepilot-ai' ); ?></p>
            </div>
            <span class="sitepilot-count" data-performance-recommendation-count><?php echo esc_html( (string) count( $report['recommendations'] ) ); ?></span>
        </div>
        <div class="sitepilot-performance-recommendations" data-performance-recommendations>
            <?php if ( empty( $report['recommendations'] ) ) : ?>
                <div class="sitepilot-empty-state"><?php esc_html_e( 'No major performance configuration issues were detected.', 'sitepilot-ai' ); ?></div>
            <?php else : ?>
                <?php foreach ( $report['recommendations'] as $item ) : ?>
                    <article class="sitepilot-performance-recommendation sitepilot-performance-recommendation--<?php echo esc_attr( $item['status'] ); ?>">
                        <span>+<?php echo esc_html( (string) $item['impact'] ); ?></span>
                        <div><h3><?php echo esc_html( $item['title'] ); ?></h3><p><?php echo esc_html( $item['message'] ); ?></p></div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
