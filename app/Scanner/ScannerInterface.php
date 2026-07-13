<?php
namespace SitePilotAI\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface ScannerInterface {
    public function scan(): array;
}
