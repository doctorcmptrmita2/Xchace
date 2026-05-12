<?php
/**
 * WooCommerce template.
 *
 * @package WPXCache
 *
 * @var array<string, mixed> $settings
 * @var array<int, array{label: string, status: string, message: string}> $items
 * @var array<string, array{key: string, label: string, level: string, risk_label: string, risk_class: string, message: string, enabled: bool}> $risk_items
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$safe_mode = ! empty($settings['woocommerce']['safe_mode']);
?>
<div class="wrap wpxcache-admin">
	<div class="wpxcache-header">
		<div>
			<h1><?php echo esc_html__('WooCommerce Safe Mode', 'wpxcache'); ?></h1>
			<p><?php echo esc_html__('Sepet ve ödeme sayfaları kullanıcıya özel bilgi içerdiği için cache dışında bırakılır.', 'wpxcache'); ?></p>
		</div>
		<span class="wpxcache-version"><?php echo esc_html(WPXCACHE_VERSION); ?></span>
	</div>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Protection Status', 'wpxcache'); ?></h2>
		<div class="wpxcache-status <?php echo esc_attr($safe_mode ? 'is-green' : 'is-red'); ?>">
			<span><?php echo esc_html($safe_mode ? __('Safe Mode On', 'wpxcache') : __('Safe Mode Off', 'wpxcache')); ?></span>
		</div>
		<p><?php echo esc_html__('Safe Mode açıkken sepet, ödeme, hesabım, sipariş sonucu, add-to-cart, wc-ajax ve WooCommerce session cookie istekleri cache dışı kalır.', 'wpxcache'); ?></p>
	</section>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('WooCommerce Checks', 'wpxcache'); ?></h2>
		<div class="wpxcache-health-list">
			<?php foreach ($items as $item) : ?>
				<article class="wpxcache-health-item">
					<div class="wpxcache-health-heading">
						<span class="wpxcache-dot is-<?php echo esc_attr($item['status']); ?>"></span>
						<strong><?php echo esc_html($item['label']); ?></strong>
					</div>
					<p><?php echo esc_html($item['message']); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('WooCommerce Risk Map', 'wpxcache'); ?></h2>
		<div class="wpxcache-health-list">
			<?php foreach ($risk_items as $item) : ?>
				<article class="wpxcache-health-item">
					<div class="wpxcache-health-heading">
						<?php echo \WPXCache\Admin\RiskRegistry::badge($item); ?>
						<strong><?php echo esc_html($item['label']); ?></strong>
					</div>
					<p><?php echo esc_html($item['message']); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Compatibility Notes', 'wpxcache'); ?></h2>
		<ul class="wpxcache-list">
			<li><?php echo esc_html__('Product, shop and product category pages can be cached for anonymous visitors when no cart/session cookie exists.', 'wpxcache'); ?></li>
			<li><?php echo esc_html__('Dynamic pricing, membership pricing and currency switcher plugins should be reviewed before enabling aggressive optimization.', 'wpxcache'); ?></li>
			<li><?php echo esc_html__('Mini-cart and cart fragments stay protected by cookie/session bypass rules.', 'wpxcache'); ?></li>
		</ul>
	</section>
</div>
