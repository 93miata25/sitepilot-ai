<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$chart_rows = array_reverse( array_slice( $history, 0, 20 ) );
$points = array();
$count = count( $chart_rows );
foreach ( $chart_rows as $index => $row ) {
    $x = $count > 1 ? 20 + ( $index * ( 760 / ( $count - 1 ) ) ) : 400;
    $y = 220 - ( (int) $row['overall_score'] * 1.8 );
    $points[] = round( $x, 2 ) . ',' . round( $y, 2 );
}
?>
<div class="wrap sitepilot-wrap">
<header class="sitepilot-header"><div><h1><?php esc_html_e( 'Site Health History', 'sitepilot-ai' ); ?></h1><p><?php esc_html_e( 'Track score changes, issue trends, and scheduled scans.', 'sitepilot-ai' ); ?></p></div><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=sitepilot-ai' ) ); ?>"><?php esc_html_e( 'View Dashboard', 'sitepilot-ai' ); ?></a></header>
<?php if ( isset( $_GET['updated'] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'History settings saved.', 'sitepilot-ai' ); ?></p></div><?php endif; ?>
<?php if ( isset( $_GET['cleared'] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Scan history cleared.', 'sitepilot-ai' ); ?></p></div><?php endif; ?>
<div class="sitepilot-grid sitepilot-grid--stats">
<article class="sitepilot-card"><span><?php esc_html_e( 'Total Scans', 'sitepilot-ai' ); ?></span><strong><?php echo esc_html( (string) $scan_count ); ?></strong></article>
<article class="sitepilot-card"><span><?php esc_html_e( 'Schedule', 'sitepilot-ai' ); ?></span><strong><?php echo esc_html( ucfirst( $frequency ) ); ?></strong></article>
<article class="sitepilot-card"><span><?php esc_html_e( 'Latest Score', 'sitepilot-ai' ); ?></span><strong><?php echo esc_html( $history ? (string) $history[0]['overall_score'] : '—' ); ?></strong></article>
<article class="sitepilot-card"><span><?php esc_html_e( 'Latest Issues', 'sitepilot-ai' ); ?></span><strong><?php echo esc_html( $history ? (string) $history[0]['issue_count'] : '—' ); ?></strong></article>
</div>
<section class="sitepilot-panel sitepilot-history-chart"><div class="sitepilot-panel-heading"><div><h2><?php esc_html_e( 'Health Score Trend', 'sitepilot-ai' ); ?></h2><p><?php esc_html_e( 'Most recent 20 scans.', 'sitepilot-ai' ); ?></p></div></div>
<?php if ( empty( $chart_rows ) ) : ?><div class="sitepilot-empty-state"><?php esc_html_e( 'Run a scan to begin tracking history.', 'sitepilot-ai' ); ?></div><?php else : ?>
<svg viewBox="0 0 800 240" role="img" aria-label="<?php esc_attr_e( 'Website health score history chart', 'sitepilot-ai' ); ?>">
<line x1="20" y1="40" x2="780" y2="40"></line><line x1="20" y1="130" x2="780" y2="130"></line><line x1="20" y1="220" x2="780" y2="220"></line>
<polyline points="<?php echo esc_attr( implode( ' ', $points ) ); ?>"></polyline>
<?php foreach ( $chart_rows as $index => $row ) : $x = $count > 1 ? 20 + ( $index * ( 760 / ( $count - 1 ) ) ) : 400; $y = 220 - ( (int) $row['overall_score'] * 1.8 ); ?><circle cx="<?php echo esc_attr( (string) $x ); ?>" cy="<?php echo esc_attr( (string) $y ); ?>" r="5"><title><?php echo esc_html( $row['scanned_at'] . ': ' . $row['overall_score'] ); ?></title></circle><?php endforeach; ?>
</svg><?php endif; ?></section>
<div class="sitepilot-grid sitepilot-grid--two">
<section class="sitepilot-panel"><h2><?php esc_html_e( 'Automatic Scans', 'sitepilot-ai' ); ?></h2><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="sitepilot_ai_save_history_settings"><?php wp_nonce_field( 'sitepilot_ai_history_settings' ); ?><label for="sitepilot-scan-frequency"><strong><?php esc_html_e( 'Frequency', 'sitepilot-ai' ); ?></strong></label><select id="sitepilot-scan-frequency" name="scan_frequency"><option value="disabled" <?php selected( $frequency, 'disabled' ); ?>><?php esc_html_e( 'Disabled', 'sitepilot-ai' ); ?></option><option value="daily" <?php selected( $frequency, 'daily' ); ?>><?php esc_html_e( 'Daily', 'sitepilot-ai' ); ?></option><option value="weekly" <?php selected( $frequency, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'sitepilot-ai' ); ?></option><option value="monthly" <?php selected( $frequency, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'sitepilot-ai' ); ?></option></select><?php submit_button( __( 'Save Schedule', 'sitepilot-ai' ), 'primary', 'submit', false ); ?></form></section>
<section class="sitepilot-panel"><h2><?php esc_html_e( 'History Storage', 'sitepilot-ai' ); ?></h2><p><?php esc_html_e( 'Clear saved history without changing the latest dashboard scan.', 'sitepilot-ai' ); ?></p><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Delete all SitePilot AI scan history?', 'sitepilot-ai' ) ); ?>');"><input type="hidden" name="action" value="sitepilot_ai_clear_history"><?php wp_nonce_field( 'sitepilot_ai_clear_history' ); ?><?php submit_button( __( 'Clear History', 'sitepilot-ai' ), 'delete', 'submit', false ); ?></form></section>
</div>
<section class="sitepilot-panel"><div class="sitepilot-panel-heading"><div><h2><?php esc_html_e( 'Scan Records', 'sitepilot-ai' ); ?></h2><p><?php esc_html_e( 'Newest scans appear first.', 'sitepilot-ai' ); ?></p></div></div><div class="sitepilot-table-wrap"><table class="widefat striped sitepilot-history-table"><thead><tr><th><?php esc_html_e( 'Date', 'sitepilot-ai' ); ?></th><th><?php esc_html_e( 'Health', 'sitepilot-ai' ); ?></th><th><?php esc_html_e( 'Performance', 'sitepilot-ai' ); ?></th><th><?php esc_html_e( 'Security', 'sitepilot-ai' ); ?></th><th><?php esc_html_e( 'SEO', 'sitepilot-ai' ); ?></th><th><?php esc_html_e( 'Issues', 'sitepilot-ai' ); ?></th><th><?php esc_html_e( 'Duration', 'sitepilot-ai' ); ?></th></tr></thead><tbody><?php if ( empty( $history ) ) : ?><tr><td colspan="7"><?php esc_html_e( 'No history records yet.', 'sitepilot-ai' ); ?></td></tr><?php else : foreach ( $history as $row ) : ?><tr><td><?php echo esc_html( $row['scanned_at'] ); ?></td><td><strong><?php echo esc_html( $row['overall_score'] ); ?></strong></td><td><?php echo esc_html( $row['performance_score'] ); ?></td><td><?php echo esc_html( $row['security_score'] ); ?></td><td><?php echo esc_html( $row['seo_score'] ); ?></td><td><?php echo esc_html( $row['issue_count'] ); ?></td><td><?php echo esc_html( $row['scan_duration'] ); ?>s</td></tr><?php endforeach; endif; ?></tbody></table></div></section>
</div>
