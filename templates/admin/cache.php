<?php
/**
 * Cache template.
 *
 * @package WPXCache
 *
 * @var array<string, mixed> $cache
 * @var array<string, array{key: string, label: string, level: string, risk_label: string, risk_class: string, message: string, enabled: bool}> $risk_items
 * @var array{count: int, size: string, last_purge: string} $stats
 * @var array{exists: bool, owned: bool, wp_cache: bool, path: string, config_exists: bool, writable: bool} $dropin
 * @var array{type: string, message: string}|null $notice
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$enabled = ! empty($cache['enabled']);
$risky_cache_keys = ['cache_logged_in_users', 'cache_404', 'cache_search', 'cache_feeds', 'cache_rest_api'];
?>
<div class="wrap wpxcache-admin">
	<?php if (is_array($notice)) : ?>
		<div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
			<p><?php echo esc_html($notice['message']); ?></p>
		</div>
	<?php endif; ?>

	<div class="wpxcache-header">
		<div>
			<h1><?php echo esc_html__('Cache', 'wpxcache'); ?></h1>
			<p><?php echo esc_html__('Page cache davranışını güvenli varsayılanlarla yönetin.', 'wpxcache'); ?></p>
		</div>
		<span class="wpxcache-version"><?php echo esc_html(WPXCACHE_VERSION); ?></span>
	</div>

	<div class="wpxcache-grid">
		<section class="wpxcache-panel">
			<h2><?php echo esc_html__('Cache Status', 'wpxcache'); ?></h2>
			<div class="wpxcache-status <?php echo esc_attr($enabled ? 'is-green' : 'is-yellow'); ?>">
				<span><?php echo esc_html($enabled ? __('Enabled', 'wpxcache') : __('Disabled', 'wpxcache')); ?></span>
			</div>
			<ul class="wpxcache-metrics">
				<li><strong><?php echo esc_html(number_format_i18n($stats['count'])); ?></strong><span><?php echo esc_html__('Cached pages', 'wpxcache'); ?></span></li>
				<li><strong><?php echo esc_html($stats['size']); ?></strong><span><?php echo esc_html__('Cache size', 'wpxcache'); ?></span></li>
				<li><strong><?php echo esc_html($stats['last_purge']); ?></strong><span><?php echo esc_html__('Last purge', 'wpxcache'); ?></span></li>
			</ul>
		</section>

		<section class="wpxcache-panel">
			<h2><?php echo esc_html__('Drop-in', 'wpxcache'); ?></h2>
			<div class="wpxcache-status <?php echo esc_attr($dropin['exists'] && $dropin['owned'] ? 'is-green' : 'is-yellow'); ?>">
				<span><?php echo esc_html($dropin['exists'] && $dropin['owned'] ? __('Installed', 'wpxcache') : __('Not installed', 'wpxcache')); ?></span>
			</div>
			<p><?php echo esc_html($dropin['wp_cache'] ? __('WP_CACHE is enabled.', 'wpxcache') : __('WP_CACHE is not enabled yet.', 'wpxcache')); ?></p>
			<form method="post" class="wpxcache-actions">
				<?php \WPXCache\Security\Nonce::field(); ?>
				<button class="button" type="submit" name="wpxcache_action" value="regenerate_dropin"><?php echo esc_html__('Regenerate drop-in', 'wpxcache'); ?></button>
				<button class="button" type="submit" name="wpxcache_action" value="clear_cache"><?php echo esc_html__('Clear all cache', 'wpxcache'); ?></button>
			</form>
		</section>
	</div>

	<form method="post">
		<?php \WPXCache\Security\Nonce::field(); ?>
		<section class="wpxcache-panel wpxcache-panel-wide">
			<h2><?php echo esc_html__('Page Cache Settings', 'wpxcache'); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php echo esc_html__('Enable page cache', 'wpxcache'); ?></th>
						<td>
							<?php echo \WPXCache\Admin\RiskRegistry::badge($risk_items['cache_enabled']); ?>
							<label><input type="checkbox" name="cache_enabled" <?php checked($enabled); ?>> <?php echo esc_html__('Cache anonymous public pages.', 'wpxcache'); ?></label>
							<p class="wpxcache-risk-note"><?php echo esc_html($risk_items['cache_enabled']['message']); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__('Cache lifespan TTL', 'wpxcache'); ?></th>
						<td>
							<?php echo \WPXCache\Admin\RiskRegistry::badge($risk_items['ttl']); ?>
							<input class="small-text" type="number" min="60" max="<?php echo esc_attr((string) WEEK_IN_SECONDS); ?>" name="cache_ttl" value="<?php echo esc_attr((string) ($cache['ttl'] ?? 3600)); ?>"> <?php echo esc_html__('seconds', 'wpxcache'); ?>
							<p class="wpxcache-risk-note"><?php echo esc_html($risk_items['ttl']['message']); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__('Separate mobile cache', 'wpxcache'); ?></th>
						<td>
							<?php echo \WPXCache\Admin\RiskRegistry::badge($risk_items['separate_mobile_cache']); ?>
							<label><input type="checkbox" name="separate_mobile_cache" <?php checked(! empty($cache['separate_mobile_cache'])); ?>> <?php echo esc_html__('Use a separate cache variant for mobile visitors.', 'wpxcache'); ?></label>
							<p class="wpxcache-risk-note"><?php echo esc_html($risk_items['separate_mobile_cache']['message']); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__('Purge behavior', 'wpxcache'); ?></th>
						<td>
							<?php echo \WPXCache\Admin\RiskRegistry::badge($risk_items['purge_home_on_update']); ?>
							<label><input type="checkbox" name="purge_home_on_update" <?php checked(! empty($cache['purge_home_on_update'])); ?>> <?php echo esc_html__('Purge homepage on content update.', 'wpxcache'); ?></label>
							<p class="wpxcache-risk-note"><?php echo esc_html($risk_items['purge_home_on_update']['message']); ?></p>
							<?php echo \WPXCache\Admin\RiskRegistry::badge($risk_items['purge_archives_on_update']); ?>
							<label><input type="checkbox" name="purge_archives_on_update" <?php checked(! empty($cache['purge_archives_on_update'])); ?>> <?php echo esc_html__('Purge related archives on content update.', 'wpxcache'); ?></label>
							<p class="wpxcache-risk-note"><?php echo esc_html($risk_items['purge_archives_on_update']['message']); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</section>

		<section class="wpxcache-panel wpxcache-panel-wide">
			<h2><?php echo esc_html__('Risky Cache Options', 'wpxcache'); ?></h2>
			<p><?php echo esc_html__('Bu ayarlar otomatik açılmaz. Sepet, ödeme, formlar ve kullanıcıya özel içerikler için dikkatli test gerekir.', 'wpxcache'); ?></p>
			<div class="wpxcache-health-list">
				<?php foreach ($risky_cache_keys as $cache_key) : ?>
					<?php $item = $risk_items[$cache_key]; ?>
					<label class="wpxcache-health-item">
						<span class="wpxcache-health-heading">
							<?php echo \WPXCache\Admin\RiskRegistry::badge($item); ?>
							<strong><?php echo esc_html($item['label']); ?></strong>
						</span>
						<input type="checkbox" name="<?php echo esc_attr($item['key']); ?>" <?php checked(! empty($cache[$item['key']])); ?>>
						<span><?php echo esc_html(! empty($cache[$item['key']]) ? __('Enabled', 'wpxcache') : __('Disabled', 'wpxcache')); ?></span>
						<p><?php echo esc_html($item['message']); ?></p>
					</label>
				<?php endforeach; ?>
			</div>
			<div class="wpxcache-actions">
				<button class="button button-primary" type="submit" name="wpxcache_action" value="save_cache_settings"><?php echo esc_html__('Save cache settings', 'wpxcache'); ?></button>
			</div>
		</section>
	</form>
</div>
