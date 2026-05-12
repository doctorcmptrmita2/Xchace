<?php
/**
 * CDN template.
 *
 * @package WPXCache
 *
 * @var array<string, mixed> $cdn
 * @var array<string, array{key: string, label: string, level: string, risk_label: string, risk_class: string, message: string, enabled: bool}> $risk_items
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
			<h1><?php echo esc_html__('CDN', 'wpxcache'); ?></h1>
			<p><?php echo esc_html__('Statik dosyaları CDN üzerinden sun ve purge entegrasyonunu güvenli şekilde yönet.', 'wpxcache'); ?></p>
		</div>
		<span class="wpxcache-version"><?php echo esc_html(WPXCACHE_VERSION); ?></span>
	</div>

	<form method="post">
		<?php \WPXCache\Security\Nonce::field(); ?>
		<div class="wpxcache-grid">
			<section class="wpxcache-panel">
				<h2><?php echo esc_html__('CDN Rewrite', 'wpxcache'); ?></h2>
				<?php echo \WPXCache\Admin\RiskRegistry::badge($risk_items['enabled']); ?>
				<label><input type="checkbox" name="cdn_enabled" <?php checked(! empty($cdn['enabled'])); ?>> <?php echo esc_html__('Enable CDN rewrite', 'wpxcache'); ?></label>
				<p class="wpxcache-risk-note"><?php echo esc_html($risk_items['enabled']['message']); ?></p>
				<p><label><?php echo esc_html__('CDN base URL', 'wpxcache'); ?></label></p>
				<input class="regular-text" type="url" name="cdn_base_url" value="<?php echo esc_attr((string) ($cdn['base_url'] ?? '')); ?>" placeholder="https://cdn.example.com">
				<p><label><?php echo esc_html__('Included file types', 'wpxcache'); ?></label></p>
				<input class="regular-text" type="text" name="cdn_included_file_types" value="<?php echo esc_attr(implode(',', is_array($cdn['included_file_types'] ?? null) ? $cdn['included_file_types'] : [])); ?>">
				<p><label><?php echo esc_html__('Excluded paths', 'wpxcache'); ?></label></p>
				<textarea class="large-text code" rows="5" name="cdn_excluded_paths"><?php echo esc_textarea(implode("\n", is_array($cdn['excluded_paths'] ?? null) ? $cdn['excluded_paths'] : [])); ?></textarea>
			</section>

			<section class="wpxcache-panel">
				<h2><?php echo esc_html__('Cloudflare', 'wpxcache'); ?></h2>
				<?php echo \WPXCache\Admin\RiskRegistry::badge($risk_items['cloudflare_enabled']); ?>
				<label><input type="checkbox" name="cloudflare_enabled" <?php checked(! empty($cdn['cloudflare_enabled'])); ?>> <?php echo esc_html__('Enable Cloudflare integration', 'wpxcache'); ?></label>
				<p class="wpxcache-risk-note"><?php echo esc_html($risk_items['cloudflare_enabled']['message']); ?></p>
				<p><label><?php echo esc_html__('API token', 'wpxcache'); ?></label></p>
				<input class="regular-text" type="password" name="cloudflare_api_token" value="<?php echo esc_attr((string) ($cdn['cloudflare_api_token'] ?? '')); ?>" autocomplete="off">
				<p><label><?php echo esc_html__('Zone ID', 'wpxcache'); ?></label></p>
				<input class="regular-text" type="text" name="cloudflare_zone_id" value="<?php echo esc_attr((string) ($cdn['cloudflare_zone_id'] ?? '')); ?>">
				<p>
					<?php echo \WPXCache\Admin\RiskRegistry::badge($risk_items['purge_cloudflare_on_purge']); ?>
					<label><input type="checkbox" name="purge_cloudflare_on_purge" <?php checked(! empty($cdn['purge_cloudflare_on_purge'])); ?>> <?php echo esc_html__('Purge Cloudflare when local cache is purged', 'wpxcache'); ?></label>
				</p>
				<p class="wpxcache-risk-note"><?php echo esc_html($risk_items['purge_cloudflare_on_purge']['message']); ?></p>
			</section>
		</div>

		<section class="wpxcache-panel wpxcache-panel-wide">
			<h2><?php echo esc_html__('Safety Notes', 'wpxcache'); ?></h2>
			<ul class="wpxcache-list">
				<li><?php echo esc_html__('API tokens are never written to logs or diagnostics reports.', 'wpxcache'); ?></li>
				<li><?php echo esc_html__('CDN rewrite only affects same-domain static asset URLs with included file extensions.', 'wpxcache'); ?></li>
				<li><?php echo esc_html__('HTML page cache and CDN cache should be purged together only after testing.', 'wpxcache'); ?></li>
			</ul>
			<div class="wpxcache-actions">
				<button class="button button-primary" type="submit" name="wpxcache_action" value="save_cdn"><?php echo esc_html__('Save CDN settings', 'wpxcache'); ?></button>
			</div>
		</section>
	</form>
</div>
