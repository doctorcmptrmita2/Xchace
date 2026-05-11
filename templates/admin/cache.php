<?php
/**
 * Cache template.
 *
 * @package WPXCache
 *
 * @var array<string, mixed> $cache
 * @var array{count: int, size: string, last_purge: string} $stats
 * @var array{exists: bool, owned: bool, wp_cache: bool, path: string, config_exists: bool, writable: bool} $dropin
 * @var array{type: string, message: string}|null $notice
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$enabled = ! empty($cache['enabled']);
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
						<td><label><input type="checkbox" name="cache_enabled" <?php checked($enabled); ?>> <?php echo esc_html__('Cache anonymous public pages.', 'wpxcache'); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__('Cache lifespan TTL', 'wpxcache'); ?></th>
						<td><input class="small-text" type="number" min="60" max="<?php echo esc_attr((string) WEEK_IN_SECONDS); ?>" name="cache_ttl" value="<?php echo esc_attr((string) ($cache['ttl'] ?? 3600)); ?>"> <?php echo esc_html__('seconds', 'wpxcache'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__('Separate mobile cache', 'wpxcache'); ?></th>
						<td><label><input type="checkbox" name="separate_mobile_cache" <?php checked(! empty($cache['separate_mobile_cache'])); ?>> <?php echo esc_html__('Use a separate cache variant for mobile visitors.', 'wpxcache'); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__('Purge behavior', 'wpxcache'); ?></th>
						<td>
							<label><input type="checkbox" name="purge_home_on_update" <?php checked(! empty($cache['purge_home_on_update'])); ?>> <?php echo esc_html__('Purge homepage on content update.', 'wpxcache'); ?></label><br>
							<label><input type="checkbox" name="purge_archives_on_update" <?php checked(! empty($cache['purge_archives_on_update'])); ?>> <?php echo esc_html__('Purge related archives on content update.', 'wpxcache'); ?></label>
						</td>
					</tr>
				</tbody>
			</table>
		</section>

		<section class="wpxcache-panel wpxcache-panel-wide">
			<h2><?php echo esc_html__('Risky Cache Options', 'wpxcache'); ?></h2>
			<p><?php echo esc_html__('Bu ayarlar otomatik açılmaz. Sepet, ödeme, formlar ve kullanıcıya özel içerikler için dikkatli test gerekir.', 'wpxcache'); ?></p>
			<div class="wpxcache-health-list">
				<label class="wpxcache-health-item"><span class="wpxcache-risk is-risky"><?php echo esc_html__('Risky', 'wpxcache'); ?></span><br><input type="checkbox" name="cache_logged_in_users" <?php checked(! empty($cache['cache_logged_in_users'])); ?>> <?php echo esc_html__('Cache logged-in users', 'wpxcache'); ?></label>
				<label class="wpxcache-health-item"><span class="wpxcache-risk is-medium"><?php echo esc_html__('Medium', 'wpxcache'); ?></span><br><input type="checkbox" name="cache_404" <?php checked(! empty($cache['cache_404'])); ?>> <?php echo esc_html__('Cache 404 pages', 'wpxcache'); ?></label>
				<label class="wpxcache-health-item"><span class="wpxcache-risk is-medium"><?php echo esc_html__('Medium', 'wpxcache'); ?></span><br><input type="checkbox" name="cache_search" <?php checked(! empty($cache['cache_search'])); ?>> <?php echo esc_html__('Cache search pages', 'wpxcache'); ?></label>
				<label class="wpxcache-health-item"><span class="wpxcache-risk is-medium"><?php echo esc_html__('Medium', 'wpxcache'); ?></span><br><input type="checkbox" name="cache_feeds" <?php checked(! empty($cache['cache_feeds'])); ?>> <?php echo esc_html__('Cache feeds', 'wpxcache'); ?></label>
				<label class="wpxcache-health-item"><span class="wpxcache-risk is-risky"><?php echo esc_html__('Risky', 'wpxcache'); ?></span><br><input type="checkbox" name="cache_rest_api" <?php checked(! empty($cache['cache_rest_api'])); ?>> <?php echo esc_html__('Cache REST API', 'wpxcache'); ?></label>
			</div>
			<div class="wpxcache-actions">
				<button class="button button-primary" type="submit" name="wpxcache_action" value="save_cache_settings"><?php echo esc_html__('Save cache settings', 'wpxcache'); ?></button>
			</div>
		</section>
	</form>
</div>
