<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$base_url = admin_url( 'admin.php?page=sitepilot-ai-issues' );
?>
<div class="wrap sitepilot-wrap" id="sitepilot-issues-page">
    <header class="sitepilot-header">
        <div>
            <h1><?php esc_html_e( 'Website Issues', 'sitepilot-ai' ); ?></h1>
            <p><?php esc_html_e( 'Prioritized findings from the latest SitePilot AI scan.', 'sitepilot-ai' ); ?></p>
        </div>
        <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=sitepilot-ai' ) ); ?>"><?php esc_html_e( 'Back to Dashboard', 'sitepilot-ai' ); ?></a>
    </header>

    <div class="sitepilot-severity-summary">
        <?php foreach ( array( 'all', 'critical', 'high', 'medium', 'low' ) as $level ) : ?>
            <?php
            $url = 'all' === $level ? $base_url : add_query_arg( 'severity', $level, $base_url );
            $active = ( 'all' === $level && ! $severity ) || $severity === $level;
            ?>
            <a class="sitepilot-summary-card sitepilot-summary-card--<?php echo esc_attr( $level ); ?><?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>">
                <span><?php echo esc_html( ucfirst( $level ) ); ?></span>
                <strong><?php echo esc_html( (string) ( $issue_counts[ $level ] ?? 0 ) ); ?></strong>
            </a>
        <?php endforeach; ?>
    </div>

    <form class="sitepilot-filters" method="get">
        <input type="hidden" name="page" value="sitepilot-ai-issues">
        <label>
            <span class="screen-reader-text"><?php esc_html_e( 'Severity', 'sitepilot-ai' ); ?></span>
            <select name="severity">
                <option value=""><?php esc_html_e( 'All severities', 'sitepilot-ai' ); ?></option>
                <?php foreach ( array( 'critical', 'high', 'medium', 'low' ) as $level ) : ?>
                    <option value="<?php echo esc_attr( $level ); ?>" <?php selected( $severity, $level ); ?>><?php echo esc_html( ucfirst( $level ) ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span class="screen-reader-text"><?php esc_html_e( 'Category', 'sitepilot-ai' ); ?></span>
            <select name="category">
                <option value=""><?php esc_html_e( 'All categories', 'sitepilot-ai' ); ?></option>
                <?php foreach ( $categories as $key => $label ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $category, $key ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="button"><?php esc_html_e( 'Filter', 'sitepilot-ai' ); ?></button>
        <a href="<?php echo esc_url( $base_url ); ?>"><?php esc_html_e( 'Reset', 'sitepilot-ai' ); ?></a>
    </form>

    <div class="sitepilot-notice" data-sitepilot-action-status hidden></div>

    <?php if ( empty( $issues ) ) : ?>
        <div class="sitepilot-panel sitepilot-empty-state"><?php esc_html_e( 'No issues match the selected filters.', 'sitepilot-ai' ); ?></div>
    <?php else : ?>
        <div class="sitepilot-issue-list">
            <?php foreach ( $issues as $issue ) : ?>
                <article class="sitepilot-issue-row sitepilot-issue--<?php echo esc_attr( $issue['severity'] ); ?>" data-sitepilot-issue-id="<?php echo esc_attr( $issue['id'] ); ?>">
                    <div class="sitepilot-issue-marker"></div>
                    <div class="sitepilot-issue-content">
                        <div class="sitepilot-issue-meta">
                            <span class="sitepilot-severity"><?php echo esc_html( strtoupper( $issue['severity'] ) ); ?></span>
                            <span><?php echo esc_html( ucfirst( $issue['category'] ) ); ?></span>
                            <span><?php echo esc_html( sprintf( __( '+%d score potential', 'sitepilot-ai' ), (int) $issue['impact'] ) ); ?></span>
                        </div>
                        <h2><?php echo esc_html( $issue['title'] ); ?></h2>
                        <p><?php echo esc_html( $issue['description'] ); ?></p>
                        <div class="sitepilot-fix-copy"><strong><?php esc_html_e( 'Recommended fix:', 'sitepilot-ai' ); ?></strong> <?php echo esc_html( $issue['fix'] ); ?></div>
                    </div>
                    <div class="sitepilot-issue-actions">
                        <?php if ( $issue['fixable'] && $issue['fix_action'] ) : ?>
                            <button type="button" class="button button-primary" data-sitepilot-fix="<?php echo esc_attr( $issue['fix_action'] ); ?>"><?php esc_html_e( 'Fix Now', 'sitepilot-ai' ); ?></button>
                        <?php else : ?>
                            <span class="sitepilot-manual-badge"><?php esc_html_e( 'Manual fix', 'sitepilot-ai' ); ?></span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
