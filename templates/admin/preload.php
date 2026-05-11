<?php
/**
 * Preload template.
 *
 * @package WPXCache
 *
 * @var array<string, mixed> $settings
 * @var array<string, mixed> $state
 * @var array{type: string, message: string}|null $notice
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$queued = is_array($state['queued'] ?? null) ? count($state['queued']) : 0;
$processed = is_array($state['processed'] ?? null) ? count($state['processed']) : 0;
$failed = is_array($state['failed'] ?? null) ? count($state['failed']) : 0;
$total = max(0, (int) ($state['total'] ?? 0));
$percent = $total > 0 ? min(100, (int) floor(($processed + $failed) * 100 / $total)) : 0;
?>
<div class="wrap wpxcache-admin">
	<?php if (is_array($notice)) : ?>
		<div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
			<p><?php echo esc_html($notice['message']); ?></p>
		</div>
	<?php endif; ?>

	<div class="wpxcache-header">
		<div>
			<h1><?php echo esc_html__('Preload', 'wpxcache'); ?></h1>
			<p><?php echo esc_html__('Cache temizlendikten sonra önemli sayfaları kontrollü şekilde yeniden ısıt.', 'wpxcache'); ?></p>
		</div>
		<span class="wpxcache-version"><?php echo esc_html(WPXCACHE_VERSION); ?></span>
	</div>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Preload Status', 'wpxcache'); ?></h2>
		<div class="wpxcache-status <?php echo esc_attr('running' === ($state['status'] ?? '') ? 'is-green' : 'is-yellow'); ?>">
			<span><?php echo esc_html(ucfirst((string) ($state['status'] ?? 'idle'))); ?></span>
		</div>
		<div class="wpxcache-progress" aria-label="<?php echo esc_attr__('Preload progress', 'wpxcache'); ?>">
			<span style="width: <?php echo esc_attr((string) $percent); ?>%"></span>
		</div>
		<ul class="wpxcache-metrics">
			<li><strong><?php echo esc_html(number_format_i18n($total)); ?></strong><span><?php echo esc_html__('Total', 'wpxcache'); ?></span></li>
			<li><strong><?php echo esc_html(number_format_i18n($queued)); ?></strong><span><?php echo esc_html__('Queued', 'wpxcache'); ?></span></li>
			<li><strong><?php echo esc_html(number_format_i18n($processed)); ?></strong><span><?php echo esc_html__('Processed', 'wpxcache'); ?></span></li>
			<li><strong><?php echo esc_html(number_format_i18n($failed)); ?></strong><span><?php echo esc_html__('Failed', 'wpxcache'); ?></span></li>
		</ul>
	</section>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Manual Preload', 'wpxcache'); ?></h2>
		<form method="post">
			<?php \WPXCache\Security\Nonce::field(); ?>
			<p><?php echo esc_html__('Her satıra bir URL yazın. Yalnızca bu sitenin URL’leri kabul edilir.', 'wpxcache'); ?></p>
			<textarea class="large-text code" rows="7" name="wpxcache_preload_urls" placeholder="<?php echo esc_attr(home_url('/')); ?>"></textarea>
			<div class="wpxcache-actions">
				<button class="button button-primary" type="submit" name="wpxcache_action" value="start_preload"><?php echo esc_html__('Start preload', 'wpxcache'); ?></button>
				<button class="button" type="submit" name="wpxcache_action" value="pause_preload"><?php echo esc_html__('Pause', 'wpxcache'); ?></button>
				<button class="button" type="submit" name="wpxcache_action" value="resume_preload"><?php echo esc_html__('Resume', 'wpxcache'); ?></button>
				<button class="button" type="submit" name="wpxcache_action" value="reset_preload"><?php echo esc_html__('Reset', 'wpxcache'); ?></button>
			</div>
		</form>
	</section>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Safe Limits', 'wpxcache'); ?></h2>
		<ul class="wpxcache-list">
			<li><?php echo esc_html__('Batch size is intentionally small to protect shared hosting.', 'wpxcache'); ?></li>
			<li><?php echo esc_html__('Requests are processed by WP-Cron with delay between batches.', 'wpxcache'); ?></li>
			<li><?php echo esc_html__('External domains are rejected during URL normalization.', 'wpxcache'); ?></li>
		</ul>
	</section>
</div>
