<?php

use Symfony\Component\Process\Process;

use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Avifenc;
use Spatie\ImageOptimizer\Optimizers\Cwebp;
use Spatie\ImageOptimizer\Optimizers\Gifsicle;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\ImageOptimizer\Optimizers\Pngquant;
use Spatie\ImageOptimizer\Optimizers\Svgo;

class AnotherSimpleImageOptimizer {

    /**
     * Run Spatie\ImageOptimizer
     *
     * @param string $file image file path
     * @return false|array
     */
    public static function optimize(string $file): bool|array {

        if (!file_exists($file)) return false;

        $isOptimized = false;

        $ext        = pathinfo($file, PATHINFO_EXTENSION);
        $fileName   = substr($file, 0, - (strlen($ext) + 1));
        $outputPath = "{$fileName}_opt.{$ext}";

        // TODO: add config options
        $optimizerChain = self::createOptimizerChain();
        $optimizerChain->optimize($file, $outputPath);

        $oldFileSize = filesize($file);
        $newFileSize = filesize($outputPath);

        if ($newFileSize >= $oldFileSize) {
            unlink($outputPath);
            $newFileSize = $oldFileSize;
        } else {
            rename($outputPath, $file);
            clearstatcache(true, $file);
            $isOptimized = true;
        }

        return [
            'filesize'     => $newFileSize,
            'filesize_old' => $oldFileSize,
            'optimized'    => $isOptimized,
        ];

    }

    /**
     * Callback for `wp_generate_attachment_metadata` hook
     */
    public static function hook_wp_generate_attachment_metadata(array $metadata, ?int $postId = null, ?string $context = null): array {
        return self::run($metadata);
    }

    /**
     * Run optimizer
     *
     * Skips attachments, that are already optimized
     *
     * @param array $metadata Attachment meta data
     * @param bool $force Don't skip optimized attachments
     * @return array Updated meta data
     */
    public static function run(array $metadata, bool $force = false): array {

        $supportedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/svg+xml',
            'image/webp',
            'image/avif',
        ];

        $file    = $metadata['file'] ?? null;

        // e g. when uploading a pdf, the `file` key is missing
        if (!$file) return $metadata;
        // avoid unknown edge cases
        if (!is_string($file)) return $metadata;

        $baseDir = wp_upload_dir()['basedir'] . '/' . dirname($file) . '/';
        $file    = $baseDir . basename($file);

        // TODO: Throw Exception?
        if (!file_exists($file)) return $metadata;

        $mimeType = mime_content_type($file);

        $isOptimized = isset($metadata['simple-image-optimizer']['optimized']) &&
            $metadata['simple-image-optimizer']['optimized'] === true;

        $needsOptimization = !$isOptimized || $force;

        // optimize file
        if (in_array($mimeType, $supportedTypes) && $needsOptimization) {

            $res = self::optimize($file);

            if ($res) {
                $metadata['filesize'] = $res['filesize'];
                $metadata['simple-image-optimizer'] = [
                    'filesize_old' => $res['filesize_old'],
                    'optimized'    => $res['optimized'],
                ];
            }

            // optimize full size if scaled - don't store additional meta data
            if (isset($metadata['original_image'])) {
                $file = $baseDir . $metadata['original_image'];
                $res = self::optimize($file);
            }

        }

        // optimize sizes
        if (isset($metadata['sizes'])) {

            foreach ($metadata['sizes'] as &$size) {

                $isOptimized = isset($size['simple-image-optimizer']['optimized']) &&
                    $size['simple-image-optimizer']['optimized'] === true;

                $needsOptimization = !$isOptimized || $force;

                if (!in_array($size['mime-type'], $supportedTypes) || !$needsOptimization) continue;

                $file = $baseDir . $size['file'];

                $res = self::optimize($file);

                if ($res) {
                    $size['filesize'] = $res['filesize'];
                    $size['simple-image-optimizer'] = [
                        'filesize_old' => $res['filesize_old'],
                        'optimized'    => $res['optimized'],
                    ];
                }

            }
        }

        return $metadata;

    }

    /**
     * Display settings page with list of available optimizers or run
     * optimizer for a single attachment based on $_GET['action'] parameter
     */
    public static function settingsPage(): void {

        // no need to sanitize because of the switch-case pattern,
        // just to pass the (automated?) review process
        $action = sanitize_key($_GET['action'] ?? 'list');

        switch ($action) {

            case 'optimize':

                $id       = isset($_GET['id']) ? abs((int) $_GET['id']) : null;
                $force    = !!($_GET['force'] ?? false);
                $verified = isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'optimize');

                $parameterMissing = !$verified || $id === null;

                $meta    = !$parameterMissing ? wp_get_attachment_metadata($id) : null;
                $newMeta = null;

                $mightBeImage = isset($meta['file']) && is_string($meta['file']);

                if ($meta && $mightBeImage) {
                    $newMeta = self::run($meta, $force);

                    wp_update_attachment_metadata($id, $newMeta);

                    $fileSizeStr = self::formatFileSize($newMeta);
                    $url = add_query_arg([
                        'post' => $id,
                        'action' => 'edit',
                    ], admin_url('post.php'));
                }

                include __DIR__ . '/inc/settings-optimize.php';
                break;

            case 'list':
            default:

                $possibleCommands = [
                    'jpegoptim',
                    'optipng',
                    'pngquant',
                    'gifsicle',
                    'cwebp',
                    'svgo',
                    'avifenc',
                    'avifdec',
                ];

                $availableCommands = [];

                foreach ($possibleCommands as $cmd) {

                    $timeout = 60;
                    $process = Process::fromShellCommandline('which "${:cmd}"');

                    $process
                        ->setTimeout($timeout)
                        ->run(null, ['cmd' => $cmd]);

                    $availableCommands[$cmd] = $process->isSuccessful();
                }

                include __DIR__ . '/inc/settings-list.php';

        }

    }

    /**
     * Get file size with two decimals
     * If image is optimized, get new and old file size
     */
    public static function formatFileSize(array $post): string {

        $fileSizeStr = '';

        if (isset($post['filesize'])) {
            $fileSizeStr .= size_format($post['filesize'], 2);
        }

        if (isset($post['simple-image-optimizer']['filesize_old'])
            && isset($post['filesize'])
            && $post['filesize'] !== $post['simple-image-optimizer']['filesize_old']
            ) {

            $fileSizeStr .= ' ('.size_format($post['simple-image-optimizer']['filesize_old'], 2).')';
        }

        return $fileSizeStr;

    }

    /**
     * Modified variant of \Spatie\ImageOptimizer\OptimizerChainFactory::create
     *
     * Fixes svgo config, also a base for config options in a future release
     *
     * @see https://github.com/spatie/image-optimizer/blob/main/src/OptimizerChainFactory.php
     * @see https://github.com/spatie/image-optimizer/blob/main/svgo.config.js
     */
    public static function createOptimizerChain(array $config = []): OptimizerChain {

        $jpegQuality = '--max=85';
        $pngQuality  = '--quality=85';
        $webpQuality = '-q 80';
        $avifQuality = '-a cq-level=23';
        if (isset($config['quality'])) {
            $jpegQuality = '--max='.$config['quality'];
            $pngQuality  = '--quality='.$config['quality'];
            $webpQuality = '-q '.$config['quality'];
            $avifQuality = '-a cq-level='.round(63 - $config['quality'] * 0.63);
        }

        // possible options: int 2, int 3
        $svgoVersion    = defined('ASIO_SVGO_VERSION') ? ASIO_SVGO_VERSION : 3;
        $svgoConfigFile = __DIR__ . "/optimizer-config/svgo{$svgoVersion}.config.js";

        return (new OptimizerChain())
            ->addOptimizer(new Jpegoptim([
                $jpegQuality,
                '--strip-all',
                '--all-progressive',
            ]))

            ->addOptimizer(new Pngquant([
                $pngQuality,
                '--force',
                '--skip-if-larger',
            ]))

            ->addOptimizer(new Optipng([
                '-i0',
                '-o2',
                '-quiet',
            ]))

            ->addOptimizer(new Svgo([
                '--config=' . $svgoConfigFile,
            ]))

            ->addOptimizer(new Gifsicle([
                '-b',
                '-O3',
            ]))
            ->addOptimizer(new Cwebp([
                $webpQuality,
                '-m 6',
                '-pass 10',
                '-mt',
            ]))
            ->addOptimizer(new Avifenc([
                $avifQuality,
                '-j all',
                '--min 0',
                '--max 63',
                '--minalpha 0',
                '--maxalpha 63',
                '-a end-usage=q',
                '-a tune=ssim',
            ]));
    }

}
