<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap sitepilot-wrap" id="sitepilot-fix-center">
    <header class="sitepilot-header">
        <div>
            <h1><?php esc_html_e( 'Fix Center', 'sitepilot-ai' ); ?></h1>
            <p><?php esc_html_e( 'Prioritized, low-risk improvements that SitePilot AI can apply for you.', 'sitepilot-ai' ); ?></p>
        </div>
        <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sitepilot-ai-issues' ) ); ?>"><?php esc_html_e( 'View All Issues', 'sitepilot-ai' ); ?></a>
    </header>

    <div class="sitepilot-notice" data-sitepilot-action-status hidden></div>

    <section class="sitepilot-hero-card sitepilot-fix-hero">
        <div class="sitepilot-score-ring" style="--sitepilot-score:<?php echo esc_attr( (string) $results['health']['score'] ); ?>">
            <span class="sitepilot-score-value"><?php echo esc_html( (string) $results['health']['score'] ); ?></span><small>/100</small>
        </div>
        <div class="sitepilot-hero-copy">
            <span class="sitepilot-eyebrow"><?php esc_html_e( 'Website Health', 'sitepilot-ai' ); ?></span>
            <h2><?php echo esc_html( $results['health']['label'] ); ?></h2>
            <p><?php printf( esc_html__( '%1$d automatic fixes available from %2$d detected issues.', 'sitepilot-ai' ), count( $fixes ), count( $issues ) ); ?></p>
        </div>
        <div class="sitepilot-fix-summary">
            <div><strong><?php echo esc_html( (string) array_sum( array_map( static function ( array $fix ): int { return (int) $fix['impact']; }, $fixes ) ) ); ?></strong><span><?php esc_html_e( 'Potential points', 'sitepilot-ai' ); ?></span></div>
            <div><strong><?php echo esc_html( (string) count( $fixes ) ); ?></strong><span><?php esc_html_e( 'Available fixes', 'sitepilot-ai' ); ?></span></div>
            <div><strong><?php echo esc_html( (string) count( $activity ) ); ?></strong><span><?php esc_html_e( 'Recent actions', 'sitepilot-ai' ); ?></span></div>
        </div>
    </section>

    <section class="sitepilot-panel">
        <div class="sitepilot-panel-heading">
            <div><h2><?php esc_html_e( 'Recommended Fixes', 'sitepilot-ai' ); ?></h2><p><?php esc_html_e( 'Highest-impact safe actions appear first.', 'sitepilot-ai' ); ?></p></div>
        </div>
        <?php if ( empty( $fixes ) ) : ?>
            <div class="sitepilot-empty-state"><?php esc_html_e( 'No automatic fixes are currently needed.', 'sitepilot-ai' ); ?></div>
        <?php else : ?>
            <div class="sitepilot-fix-grid">
                <?php foreach ( $fixes as $fix ) : ?>
                    <article class="sitepilot-fix-card">
                        <div class="sitepilot-fix-card-top">
                            <span class="sitepilot-severity sitepilot-severity--<?php echo esc_attr( $fix['severity'] ); ?>"><?php echo esc_html( ucfirst( $fix['severity'] ) ); ?></span>
                            <span class="sitepilot-impact">+<?php echo esc_html( (string) $fix['impact'] ); ?></span>
                        </div>
                        <h3><?php echo esc_html( $fix['title'] ); ?></h3>
                        <p><?php echo esc_html( $fix['description'] ); ?></p>
                        <dl class="sitepilot-fix-meta">
                            <div><dt><?php esc_html_e( 'Risk', 'sitepilot-ai' ); ?></dt><dd><?php echo esc_html( ucfirst( $fix['risk'] ) ); ?></dd></div>
                            <div><dt><?php esc_html_e( 'Time', 'sitepilot-ai' ); ?></dt><dd><?php printf( esc_html__( '~%d min', 'sitepilot-ai' ), (int) $fix['minutes'] ); ?></dd></div>
                            <div><dt><?php esc_html_e( 'Reversible', 'sitepilot-ai' ); ?></dt><dd><?php echo $fix['reversible'] ? esc_html__( 'Yes', 'sitepilot-ai' ) : esc_html__( 'No', 'sitepilot-ai' ); ?></dd></div>
                        </dl>
                        <button type="button" class="button button-primary" data-sitepilot-fix="<?php echo esc_attr( $fix['id'] ); ?>"><?php esc_html_e( 'Fix Now', 'sitepilot-ai' ); ?></button>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="sitepilot-panel">
        <div class="sitepilot-panel-heading"><div><h2><?php esc_html_e( 'Recent Activity', 'sitepilot-ai' ); ?></h2><p><?php esc_html_e( 'A record of automatic fixes applied on this website.', 'sitepilot-ai' ); ?></p></div></div>
        <?php if ( empty( $activity ) ) : ?>
            <div class="sitepilot-empty-state"><?php esc_html_e( 'No automatic fixes have been applied yet.', 'sitepilot-ai' ); ?></div>
        <?php else : ?>
            <div class="sitepilot-activity-list">
                <?php foreach ( $activity as $item ) : ?>
                    <article>
                        <span class="dashicons dashicons-yes-alt"></span>
                        <div><strong><?php echo esc_html( $item['title'] ); ?></strong><p><?php echo esc_html( $item['details'] ); ?></p></div>
                        <div class="sitepilot-activity-score"><?php echo esc_html( (string) $item['score_before'] ); ?> → <?php echo esc_html( (string) $item['score_after'] ); ?></div>
                        <time><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item['created_at'] ) ); ?></time>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
