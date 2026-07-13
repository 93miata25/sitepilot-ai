<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$format_next = static function ( int $timestamp ): string {
    return $timestamp ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) : __( 'Not scheduled', 'sitepilot-ai' );
};
$tasks = $status['database_tasks'] ?? array();
?>
<div class="wrap sitepilot-wrap" id="sitepilot-automation">
    <div class="sitepilot-header">
        <div>
            <h1><?php esc_html_e( 'Automation Center', 'sitepilot-ai' ); ?></h1>
            <p><?php esc_html_e( 'Schedule safe maintenance jobs and run them on demand.', 'sitepilot-ai' ); ?></p>
        </div>
        <span class="sitepilot-version">v<?php echo esc_html( SITEPILOT_AI_VERSION ); ?></span>
    </div>

    <?php if ( isset( $_GET['updated'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Automation settings saved.', 'sitepilot-ai' ); ?></p></div>
    <?php endif; ?>

    <div class="sitepilot-automation-grid">
        <section class="sitepilot-panel sitepilot-automation-card">
            <div class="sitepilot-panel-heading">
                <div>
                    <span class="dashicons dashicons-search"></span>
                    <h2><?php esc_html_e( 'Website Health Scan', 'sitepilot-ai' ); ?></h2>
                </div>
                <button type="button" class="button button-secondary" data-sitepilot-automation-job="health_scan"><?php esc_html_e( 'Run Now', 'sitepilot-ai' ); ?></button>
            </div>
            <p><?php esc_html_e( 'Runs the full scanner, records the health score, and updates issue history.', 'sitepilot-ai' ); ?></p>
            <dl class="sitepilot-details">
                <div><dt><?php esc_html_e( 'Frequency', 'sitepilot-ai' ); ?></dt><dd><?php echo esc_html( ucfirst( $status['scan_frequency'] ) ); ?></dd></div>
                <div><dt><?php esc_html_e( 'Next run', 'sitepilot-ai' ); ?></dt><dd><?php echo esc_html( $format_next( (int) $status['next_scan'] ) ); ?></dd></div>
                <div><dt><?php esc_html_e( 'Last manual run', 'sitepilot-ai' ); ?></dt><dd><?php echo esc_html( $status['automation_last_scan'] ?: __( 'Never', 'sitepilot-ai' ) ); ?></dd></div>
            </dl>
        </section>

        <section class="sitepilot-panel sitepilot-automation-card">
            <div class="sitepilot-panel-heading">
                <div>
                    <span class="dashicons dashicons-database"></span>
                    <h2><?php esc_html_e( 'Database Maintenance', 'sitepilot-ai' ); ?></h2>
                </div>
                <button type="button" class="button button-secondary" data-sitepilot-automation-job="database_cleanup"><?php esc_html_e( 'Run Now', 'sitepilot-ai' ); ?></button>
            </div>
            <p><?php esc_html_e( 'Safely removes selected clutter and optimizes WordPress database tables.', 'sitepilot-ai' ); ?></p>
            <dl class="sitepilot-details">
                <div><dt><?php esc_html_e( 'Frequency', 'sitepilot-ai' ); ?></dt><dd><?php echo esc_html( ucfirst( $status['database_frequency'] ) ); ?></dd></div>
                <div><dt><?php esc_html_e( 'Next run', 'sitepilot-ai' ); ?></dt><dd><?php echo esc_html( $format_next( (int) $status['next_database'] ) ); ?></dd></div>
                <div><dt><?php esc_html_e( 'Last run', 'sitepilot-ai' ); ?></dt><dd><?php echo esc_html( $status['database_last_run'] ?: __( 'Never', 'sitepilot-ai' ) ); ?></dd></div>
            </dl>
        </section>
    </div>

    <div class="sitepilot-action-status" data-sitepilot-automation-status hidden></div>

    <section class="sitepilot-panel">
        <h2><?php esc_html_e( 'Automation Schedule', 'sitepilot-ai' ); ?></h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="sitepilot_ai_save_automation">
            <?php wp_nonce_field( 'sitepilot_ai_automation_settings' ); ?>

            <div class="sitepilot-settings-grid">
                <label>
                    <span><?php esc_html_e( 'Health scan frequency', 'sitepilot-ai' ); ?></span>
                    <select name="scan_frequency">
                        <?php foreach ( array( 'disabled' => __( 'Disabled', 'sitepilot-ai' ), 'daily' => __( 'Daily', 'sitepilot-ai' ), 'weekly' => __( 'Weekly', 'sitepilot-ai' ), 'monthly' => __( 'Monthly', 'sitepilot-ai' ) ) as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status['scan_frequency'], $value ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span><?php esc_html_e( 'Database maintenance frequency', 'sitepilot-ai' ); ?></span>
                    <select name="database_frequency">
                        <?php foreach ( array( 'disabled' => __( 'Disabled', 'sitepilot-ai' ), 'weekly' => __( 'Weekly', 'sitepilot-ai' ), 'monthly' => __( 'Monthly', 'sitepilot-ai' ) ) as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status['database_frequency'], $value ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <h3><?php esc_html_e( 'Automatic database tasks', 'sitepilot-ai' ); ?></h3>
            <div class="sitepilot-task-checks">
                <?php
                $task_labels = array(
                    'expired_transients' => __( 'Delete expired transients', 'sitepilot-ai' ),
                    'spam_comments'      => __( 'Delete spam comments', 'sitepilot-ai' ),
                    'trashed_comments'   => __( 'Delete trashed comments', 'sitepilot-ai' ),
                    'trashed_posts'      => __( 'Delete trashed posts and pages', 'sitepilot-ai' ),
                    'optimize_tables'    => __( 'Optimize WordPress database tables', 'sitepilot-ai' ),
                );
                foreach ( $task_labels as $value => $label ) :
                ?>
                    <label><input type="checkbox" name="database_tasks[]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $tasks, true ) ); ?>> <?php echo esc_html( $label ); ?></label>
                <?php endforeach; ?>
            </div>
            <p class="description"><?php esc_html_e( 'Post revisions are intentionally excluded from unattended cleanup.', 'sitepilot-ai' ); ?></p>
            <?php submit_button( __( 'Save Automation Settings', 'sitepilot-ai' ) ); ?>
        </form>
    </section>

    <section class="sitepilot-panel">
        <h2><?php esc_html_e( 'Recent Maintenance Activity', 'sitepilot-ai' ); ?></h2>
        <div class="sitepilot-activity-list">
            <?php if ( empty( $activity ) ) : ?>
                <div class="sitepilot-empty-state"><?php esc_html_e( 'No maintenance activity has been recorded yet.', 'sitepilot-ai' ); ?></div>
            <?php else : ?>
                <?php foreach ( $activity as $item ) : ?>
                    <article>
                        <span class="dashicons dashicons-<?php echo 'success' === $item['status'] ? 'yes-alt' : 'warning'; ?>"></span>
                        <div><strong><?php echo esc_html( $item['title'] ); ?></strong><p><?php echo esc_html( $item['details'] ); ?></p></div>
                        <time><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item['created_at'] ) ); ?></time>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
