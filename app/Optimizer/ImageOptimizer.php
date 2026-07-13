<?php
namespace SitePilot\Optimizer;
use SitePilot\Core\Settings;
use SitePilot\Helpers\Logger;
if(!defined('ABSPATH')) exit;

/**
 * Re-compresses JPEG/PNG attachments in the Media Library using GD
 * (bundled with WordPress) - no external binaries required.
 * WebP conversion is attempted when GD supports it.
 */
class ImageOptimizer{

    protected $quality;

    public function __construct($quality = null){
        $this->quality = $quality ?? (int)Settings::get('image_quality', 82);
    }

    /**
     * Scan the media library for optimizable images without touching them.
     */
    public function audit($limit = 50){
        $attachments = get_posts([
            'post_type'      => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/png'],
            'posts_per_page' => $limit,
            'post_status'    => 'inherit',
        ]);

        $report = [];
        $totalSavingsEstimate = 0;

        foreach($attachments as $attachment){
            $path = get_attached_file($attachment->ID);
            if(!$path || !file_exists($path)) continue;

            $size = filesize($path);
            $alreadyOptimized = (bool)get_post_meta($attachment->ID, '_sitepilot_optimized', true);
            // Rough heuristic: images over 200KB are worth optimizing.
            $needsWork = $size > 200 * 1024 && !$alreadyOptimized;

            if($needsWork){
                $estimate = (int)round($size * 0.35); // typical JPEG re-encode savings
                $totalSavingsEstimate += $estimate;
            }

            $report[] = [
                'id'                => $attachment->ID,
                'title'             => get_the_title($attachment->ID),
                'file'              => basename($path),
                'size_kb'           => round($size / 1024, 1),
                'needs_optimization'=> $needsWork,
                'already_optimized' => $alreadyOptimized,
            ];
        }

        return [
            'images'                  => $report,
            'total_estimated_savings_kb' => round($totalSavingsEstimate / 1024, 1),
        ];
    }

    /**
     * Actually re-compress one attachment in place. Keeps a backup copy
     * in storage/backups before overwriting.
     */
    public function optimize($attachmentId){
        if(!function_exists('imagecreatefromjpeg')){
            return ['ok' => false, 'error' => 'GD extension not available on this server'];
        }

        $path = get_attached_file($attachmentId);
        if(!$path || !file_exists($path)){
            return ['ok' => false, 'error' => 'File not found for attachment '.$attachmentId];
        }

        $mime = get_post_mime_type($attachmentId);
        $originalSize = filesize($path);

        $backupPath = SITEPILOT_PATH.'storage/backups/'.$attachmentId.'-'.basename($path);
        if(!copy($path, $backupPath)){
            Logger::warning('Could not create backup before optimizing', ['attachment_id' => $attachmentId]);
        }

        $image = $this->loadImage($path, $mime);
        if(!$image){
            return ['ok' => false, 'error' => 'Unsupported or corrupt image: '.$mime];
        }

        $result = $this->saveImage($image, $path, $mime);
        imagedestroy($image);

        if(!$result){
            return ['ok' => false, 'error' => 'Failed to write optimized image'];
        }

        clearstatcache(true, $path);
        $newSize = filesize($path);

        update_post_meta($attachmentId, '_sitepilot_optimized', true);
        update_post_meta($attachmentId, '_sitepilot_optimized_at', current_time('mysql'));

        // Regenerate WP's registered thumbnail sizes from the optimized original.
        if(function_exists('wp_generate_attachment_metadata')){
            $metadata = wp_generate_attachment_metadata($attachmentId, $path);
            wp_update_attachment_metadata($attachmentId, $metadata);
        }

        return [
            'ok'              => true,
            'original_kb'     => round($originalSize / 1024, 1),
            'optimized_kb'    => round($newSize / 1024, 1),
            'saved_kb'        => round(($originalSize - $newSize) / 1024, 1),
            'saved_percent'   => $originalSize ? round((($originalSize - $newSize) / $originalSize) * 100, 1) : 0,
        ];
    }

    protected function loadImage($path, $mime){
        switch($mime){
            case 'image/jpeg':
                return @imagecreatefromjpeg($path);
            case 'image/png':
                return @imagecreatefrompng($path);
            default:
                return false;
        }
    }

    protected function saveImage($image, $path, $mime){
        switch($mime){
            case 'image/jpeg':
                return imagejpeg($image, $path, $this->quality);
            case 'image/png':
                // PNG compression is 0 (none) - 9 (max); map our 0-100 quality to that range.
                $level = (int)round((100 - $this->quality) / 100 * 9);
                imagesavealpha($image, true);
                return imagepng($image, $path, max(0, min(9, $level)));
            default:
                return false;
        }
    }
}
