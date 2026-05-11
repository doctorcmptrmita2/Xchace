<?php
/**
 * Dashboard template.
 *
 * @package WPXCache
 *
 * @var array<string, mixed> $settings
 * @var array<int, array{id: string, label: string, status: string, problem: string, why: string, fix: string, auto_fix: bool}> $checks
 * @var array{count: int, size: string, last_purge: string, last_preload: string} $stats
 * @var array{exists: bool, owned: bool, wp_cache: bool, path: string, config_exists: bool, writable: bool} $dropin
 * @var array<int, array{level: string, message: string}> $conflicts
 * @var array<int, array<string, mixed>> $logs
 * @var array{type: string, message: string}|null $notice
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$cache_enabled       = ! empty($settings['cache']['enabled']);
$woocommerce_safe    = ! empty($settings['woocommerce']['safe_mode']);
$optimization_safe   = ! empty($settings['optimization']['safe_mode']);
$cache_status_label  = $cache_enabled ? __('Enabled', 'wpxcache') : __('Disabled', 'wpxcache');
$cache_status_class  = $cache_enabled ? 'is-green' : 'is-yellow';
?>
<div class="wrap wpxcache-admin">
	<?php if (is_array($notice)) : ?>
		<div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
			<p><?php echo esc_html($notice['message']); ?></p>
		</div>
	<?php endif; ?>

	<div class="wpxcache-header">
		<div>
			<h1><?php echo esc_html__('WP XCache Pro', 'wpxcache'); ?></h1>
			<p><?php echo esc_html__('WordPress siteni hızlandır, ama siteni bozma.', 'wpxcache'); ?></p>
		</div>
		<span class="wpxcache-version"><?php echo esc_html(WPXCACHE_VERSION); ?></span>
	</div>

	<div class="wpxcache-grid">
		<section class="wpxcache-panel">
			<h2><?php echo esc_html__('Cache Status', 'wpxcache'); ?></h2>
			<div class="wpxcache-status <?php echo esc_attr($cache_status_class); ?>">
				<span><?php echo esc_html($cache_status_label); ?></span>
			</div>
			<p><?php echo esc_html__('Page cache motoru hazır. Varsayılan olarak kapalıdır; güvenli ayarlar ekranı Part 3 sonrası genişletilecek.', 'wpxcache'); ?></p>
		</section>

		<section class="wpxcache-panel">
			<h2><?php echo esc_html__('Safe Defaults', 'wpxcache'); ?></h2>
			<ul class="wpxcache-list">
				<li><?php echo esc_html__('Logged-in kullanıcı cache’i varsayılan olarak kapalı.', 'wpxcache'); ?></li>
				<li><?php echo esc_html__('REST API cache’i varsayılan olarak kapalı.', 'wpxcache'); ?></li>
				<li><?php echo esc_html__('Agresif CSS/JS optimizasyonları varsayılan olarak kapalı.', 'wpxcache'); ?></li>
				<li><?php echo esc_html__('WooCommerce Safe Mode varsayılan olarak açık.', 'wpxcache'); ?></li>
			</ul>
		</section>

		<section class="wpxcache-panel">
			<h2><?php echo esc_html__('Smart Optimize', 'wpxcache'); ?></h2>
			<p><?php echo esc_html__('Akıllı profil motoru ileriki partlarda site tipini analiz edip güvenli öneriler sunacak.', 'wpxcache'); ?></p>
			<button class="button button-primary" type="button" disabled><?php echo esc_html__('Smart Optimize', 'wpxcache'); ?></button>
		</section>

		<section class="wpxcache-panel">
			<h2><?php echo esc_html__('Performance Snapshot', 'wpxcache'); ?></h2>
			<ul class="wpxcache-metrics">
				<li><strong><?php echo esc_html(number_format_i18n($stats['count'])); ?></strong><span><?php echo esc_html__('Cached pages', 'wpxcache'); ?></span></li>
				<li><strong><?php echo esc_html($stats['size']); ?></strong><span><?php echo esc_html__('Cache size', 'wpxcache'); ?></span></li>
				<li><strong><?php echo esc_html($stats['last_purge']); ?></strong><span><?php echo esc_html__('Last purge', 'wpxcache'); ?></span></li>
				<li><strong><?php echo esc_html($stats['last_preload']); ?></strong><span><?php echo esc_html__('Last preload', 'wpxcache'); ?></span></li>
			</ul>
		</section>

		<section class="wpxcache-panel">
			<h2><?php echo esc_html__('Advanced Cache Drop-in', 'wpxcache'); ?></h2>
			<div class="wpxcache-status <?php echo esc_attr($dropin['exists'] && $dropin['owned'] ? 'is-green' : 'is-yellow'); ?>">
				<span>
					<?php
					echo esc_html(
						$dropin['exists'] && $dropin['owned']
							? __('Installed', 'wpxcache')
							: __('Not installed', 'wpxcache')
					);
					?>
				</span>
			</div>
			<p><?php echo esc_html($dropin['wp_cache'] ? __('WP_CACHE is enabled.', 'wpxcache') : __('WP_CACHE is not enabled yet.', 'wpxcache')); ?></p>
			<form method="post" class="wpxcache-actions">
				<?php \WPXCache\Security\Nonce::field(); ?>
				<button class="button button-primary" type="submit" name="wpxcache_action" value="<?php echo esc_attr($dropin['exists'] && $dropin['owned'] ? 'regenerate_dropin' : 'install_dropin'); ?>">
					<?php echo esc_html($dropin['exists'] && $dropin['owned'] ? __('Regenerate drop-in', 'wpxcache') : __('Install drop-in', 'wpxcache')); ?>
				</button>
				<button class="button" type="submit" name="wpxcache_action" value="remove_dropin">
					<?php echo esc_html__('Remove drop-in', 'wpxcache'); ?>
				</button>
			</form>
		</section>

		<section class="wpxcache-panel">
			<h2><?php echo esc_html__('WooCommerce Safety', 'wpxcache'); ?></h2>
			<div class="wpxcache-status <?php echo esc_attr($woocommerce_safe ? 'is-green' : 'is-yellow'); ?>">
				<span><?php echo esc_html($woocommerce_safe ? __('Safe Mode On', 'wpxcache') : __('Safe Mode Off', 'wpxcache')); ?></span>
			</div>
			<p><?php echo esc_html__('Sepet, ödeme ve kullanıcıya özel sayfalar cache dışında tutulacak şekilde tasarlanıyor.', 'wpxcache'); ?></p>
		</section>
	</div>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Health Check', 'wpxcache'); ?></h2>
		<div class="wpxcache-health-list">
			<?php foreach ($checks as $check) : ?>
				<article class="wpxcache-health-item">
					<div class="wpxcache-health-heading">
						<span class="wpxcache-dot is-<?php echo esc_attr($check['status']); ?>"></span>
						<strong><?php echo esc_html($check['label']); ?></strong>
					</div>
					<p><?php echo esc_html($check['problem']); ?></p>
					<small><?php echo esc_html($check['why']); ?></small>
					<small><?php echo esc_html($check['fix']); ?></small>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Warnings', 'wpxcache'); ?></h2>
		<?php if ([] === $conflicts) : ?>
			<p><?php echo esc_html__('No cache conflicts detected in the current foundation checks.', 'wpxcache'); ?></p>
		<?php else : ?>
			<ul class="wpxcache-warning-list">
				<?php foreach ($conflicts as $conflict) : ?>
					<li><span class="wpxcache-dot is-<?php echo esc_attr($conflict['level']); ?>"></span><?php echo esc_html($conflict['message']); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Recent Logs', 'wpxcache'); ?></h2>
		<?php if ([] === $logs) : ?>
			<p><?php echo esc_html__('No log entries yet.', 'wpxcache'); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php echo esc_html__('Time', 'wpxcache'); ?></th>
						<th scope="col"><?php echo esc_html__('Level', 'wpxcache'); ?></th>
						<th scope="col"><?php echo esc_html__('Message', 'wpxcache'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($logs as $log) : ?>
						<tr>
							<td><?php echo esc_html(isset($log['time']) && is_scalar($log['time']) ? (string) $log['time'] : ''); ?></td>
							<td><?php echo esc_html(isset($log['level']) && is_scalar($log['level']) ? (string) $log['level'] : ''); ?></td>
							<td><?php echo esc_html(isset($log['message']) && is_scalar($log['message']) ? (string) $log['message'] : ''); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</section>
</div>
