<?php
namespace SitePilotAI\Modules\Performance;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PerformanceModule {
    private PerformanceScanner $scanner;

    public function __construct() {
        $this->scanner = new PerformanceScanner();
    }

    public function report(): array {
        $cached = get_transient( 'sitepilot_ai_performance_report' );
        if ( is_array( $cached ) ) {
            return $cached;
        }
        return $this->refresh();
    }

    public function refresh(): array {
        $report = $this->scanner->scan();
        set_transient( 'sitepilot_ai_performance_report', $report, HOUR_IN_SECONDS );
        return $report;
    }
}
