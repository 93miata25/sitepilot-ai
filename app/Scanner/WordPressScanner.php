<?php
namespace SitePilotAI\Scanner;

if (! defined('ABSPATH')) {
    exit;
}

final class WordPressScanner
{
    public function scan(): array
    {
        global $wp_version;

        return array(
            'version'          => (string) $wp_version,
            'multisite'        => is_multisite(),
            'site_language'    => get_locale(),
            'timezone'         => wp_timezone_string(),
            'permalink_set'    => get_option('permalink_structure') !== '',
            'search_visibility'=> (int) get_option('blog_public', 1) === 1,
            'posts'            => (int) wp_count_posts('post')->publish,
            'pages'            => (int) wp_count_posts('page')->publish,
            'media'            => (int) wp_count_attachments()->inherit,
        );
    }
}
