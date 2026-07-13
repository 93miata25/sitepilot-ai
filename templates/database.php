<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap sitepilot-wrap" id="sitepilot-database">
  <div class="sitepilot-header">
    <div><h1><?php esc_html_e( 'Database Optimizer', 'sitepilot-ai' ); ?></h1><p><?php esc_html_e( 'Review cleanup opportunities, choose exactly what to remove, and optimize WordPress tables.', 'sitepilot-ai' ); ?></p></div>
  </div>
  <div class="sitepilot-notice" data-sitepilot-database-status hidden></div>
  <div class="sitepilot-grid sitepilot-grid--stats">
    <div class="sitepilot-card"><span><?php esc_html_e( 'Database size', 'sitepilot-ai' ); ?></span><strong data-db-field="database_size"><?php echo esc_html( $report['database_size'] ); ?></strong></div>
    <div class="sitepilot-card"><span><?php esc_html_e( 'Cleanup items', 'sitepilot-ai' ); ?></span><strong data-db-field="total_cleanup"><?php echo esc_html( $report['total_cleanup'] ); ?></strong></div>
    <div class="sitepilot-card"><span><?php esc_html_e( 'Expired transients', 'sitepilot-ai' ); ?></span><strong data-db-field="expired_transients"><?php echo esc_html( $report['expired_transients'] ); ?></strong></div>
    <div class="sitepilot-card"><span><?php esc_html_e( 'Revisions', 'sitepilot-ai' ); ?></span><strong data-db-field="revisions"><?php echo esc_html( $report['revisions'] ); ?></strong></div>
  </div>
  <div class="sitepilot-panel">
    <h2><?php esc_html_e( 'Choose cleanup tasks', 'sitepilot-ai' ); ?></h2>
    <p><?php esc_html_e( 'Database cleanup is permanent. Create a database backup before removing content.', 'sitepilot-ai' ); ?></p>
    <div class="sitepilot-db-task-list">
      <?php
      $tasks = array(
        'expired_transients' => array( __( 'Delete expired transients', 'sitepilot-ai' ), $report['expired_transients'], true ),
        'spam_comments'      => array( __( 'Delete spam comments', 'sitepilot-ai' ), $report['spam_comments'], true ),
        'trashed_comments'   => array( __( 'Delete trashed comments', 'sitepilot-ai' ), $report['trashed_comments'], true ),
        'trashed_posts'      => array( __( 'Delete trashed posts and pages', 'sitepilot-ai' ), $report['trashed_posts'], true ),
        'revisions'          => array( __( 'Delete all post revisions', 'sitepilot-ai' ), $report['revisions'], false ),
        'optimize_tables'    => array( __( 'Optimize WordPress database tables', 'sitepilot-ai' ), '—', true ),
      );
      foreach ( $tasks as $key => $task ) : ?>
        <label class="sitepilot-db-task">
          <input type="checkbox" value="<?php echo esc_attr( $key ); ?>" <?php checked( $task[2] ); ?> />
          <span><strong><?php echo esc_html( $task[0] ); ?></strong><?php if ( 'revisions' === $key ) : ?><small><?php esc_html_e( 'Optional: revisions can be useful for restoring older content.', 'sitepilot-ai' ); ?></small><?php endif; ?></span>
          <b data-db-count="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $task[1] ); ?></b>
        </label>
      <?php endforeach; ?>
    </div>
    <div class="sitepilot-db-actions">
      <button type="button" class="button button-primary button-hero" id="sitepilot-optimize-database"><?php esc_html_e( 'Run Selected Cleanup', 'sitepilot-ai' ); ?></button>
      <span class="description"><?php esc_html_e( 'You will be asked to confirm before anything is deleted.', 'sitepilot-ai' ); ?></span>
    </div>
  </div>
  <div class="sitepilot-panel" id="sitepilot-db-result" hidden>
    <h2><?php esc_html_e( 'Optimization result', 'sitepilot-ai' ); ?></h2>
    <div class="sitepilot-grid sitepilot-grid--stats">
      <div class="sitepilot-card"><span><?php esc_html_e( 'Before', 'sitepilot-ai' ); ?></span><strong data-result="before_size">—</strong></div>
      <div class="sitepilot-card"><span><?php esc_html_e( 'After', 'sitepilot-ai' ); ?></span><strong data-result="after_size">—</strong></div>
      <div class="sitepilot-card"><span><?php esc_html_e( 'Reclaimed', 'sitepilot-ai' ); ?></span><strong data-result="reclaimed_size">—</strong></div>
      <div class="sitepilot-card"><span><?php esc_html_e( 'Items processed', 'sitepilot-ai' ); ?></span><strong data-result="processed">—</strong></div>
    </div>
  </div>
</div>
