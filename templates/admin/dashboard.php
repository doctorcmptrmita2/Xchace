<?php
/**
 * Dashboard template.
 *
 * @package WPXCache
 *
 * @var array<string, mixed> $settings
 * @var array<int, array{label: string, status: string, message: string}> $checks
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
			<p><?php echo esc_html__('Page cache Part 2’de eklenecek. Part 1 güvenli temel ve yönetim ekranını hazırlar.', 'wpxcache'); ?></p>
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
			<h2><?php echo esc_html__('WooCommerce Safety', 'wpxcache'); ?></h2>
			<div class="wpxcache-status <?php echo esc_attr($woocommerce_safe ? 'is-green' : 'is-yellow'); ?>">
				<span><?php echo esc_html($woocommerce_safe ? __('Safe Mode On', 'wpxcache') : __('Safe Mode Off', 'wpxcache')); ?></span>
			</div>
			<p><?php echo esc_html__('Sepet, ödeme ve kullanıcıya özel sayfalar cache dışında tutulacak şekilde tasarlanıyor.', 'wpxcache'); ?></p>
		</section>
	</div>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Foundation Health', 'wpxcache'); ?></h2>
		<table class="widefat striped">
			<tbody>
				<?php foreach ($checks as $check) : ?>
					<tr>
						<th scope="row"><?php echo esc_html($check['label']); ?></th>
						<td><span class="wpxcache-dot is-<?php echo esc_attr($check['status']); ?>"></span><?php echo esc_html($check['message']); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</section>
</div>
