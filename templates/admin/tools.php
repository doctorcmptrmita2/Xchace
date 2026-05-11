<?php
/**
 * Tools template.
 *
 * @package WPXCache
 *
 * @var string $export
 * @var array<string, mixed> $settings
 * @var array{type: string, message: string}|null $notice
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}
?>
<div class="wrap wpxcache-admin">
	<?php if (is_array($notice)) : ?>
		<div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
			<p><?php echo esc_html($notice['message']); ?></p>
		</div>
	<?php endif; ?>

	<div class="wpxcache-header">
		<div>
			<h1><?php echo esc_html__('Tools', 'wpxcache'); ?></h1>
			<p><?php echo esc_html__('Ayarları taşı, cache’i temizle ve güvenli tanılama raporu indir.', 'wpxcache'); ?></p>
		</div>
		<span class="wpxcache-version"><?php echo esc_html(WPXCACHE_VERSION); ?></span>
	</div>

	<div class="wpxcache-grid">
		<section class="wpxcache-panel">
			<h2><?php echo esc_html__('Export Settings', 'wpxcache'); ?></h2>
			<p><?php echo esc_html__('Ayarları başka bir siteye taşımak için JSON olarak kullanabilirsiniz.', 'wpxcache'); ?></p>
			<textarea class="large-text code" rows="10" readonly><?php echo esc_textarea($export); ?></textarea>
		</section>

		<section class="wpxcache-panel">
			<h2><?php echo esc_html__('Import Settings', 'wpxcache'); ?></h2>
			<form method="post">
				<?php \WPXCache\Security\Nonce::field(); ?>
				<textarea class="large-text code" rows="10" name="wpxcache_import_settings"></textarea>
				<div class="wpxcache-actions">
					<button class="button button-primary" type="submit" name="wpxcache_action" value="import_settings"><?php echo esc_html__('Import settings', 'wpxcache'); ?></button>
				</div>
			</form>
		</section>
	</div>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Maintenance', 'wpxcache'); ?></h2>
		<form method="post" class="wpxcache-actions">
			<?php \WPXCache\Security\Nonce::field(); ?>
			<button class="button" type="submit" name="wpxcache_action" value="clear_cache"><?php echo esc_html__('Clear all cache', 'wpxcache'); ?></button>
			<button class="button" type="submit" name="wpxcache_action" value="clear_logs"><?php echo esc_html__('Clear logs', 'wpxcache'); ?></button>
			<button class="button" type="submit" name="wpxcache_action" value="regenerate_dropin"><?php echo esc_html__('Regenerate drop-in', 'wpxcache'); ?></button>
			<button class="button" type="submit" name="wpxcache_action" value="remove_dropin"><?php echo esc_html__('Remove drop-in', 'wpxcache'); ?></button>
			<button class="button button-secondary" type="submit" name="wpxcache_action" value="download_diagnostics"><?php echo esc_html__('Download diagnostics report', 'wpxcache'); ?></button>
			<button class="button wpxcache-danger" type="submit" name="wpxcache_action" value="reset_settings"><?php echo esc_html__('Reset settings', 'wpxcache'); ?></button>
		</form>
	</section>
</div>
