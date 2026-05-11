<?php
/**
 * Optimization template.
 *
 * @package WPXCache
 *
 * @var array<string, mixed> $settings
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$optimization = is_array($settings['optimization'] ?? null) ? $settings['optimization'] : [];
$items = [
	['label' => __('Minify HTML', 'wpxcache'), 'enabled' => ! empty($optimization['minify_html']), 'risk' => 'Safe', 'message' => __('HTML yorumlarını ve gereksiz boşlukları güvenli şekilde azaltır.', 'wpxcache')],
	['label' => __('Minify CSS', 'wpxcache'), 'enabled' => ! empty($optimization['minify_css']), 'risk' => 'Medium', 'message' => __('CSS optimizasyonu sonraki adımda eklenecek; bazı temalarda test gerekir.', 'wpxcache')],
	['label' => __('Combine CSS', 'wpxcache'), 'enabled' => ! empty($optimization['combine_css']), 'risk' => 'Risky', 'message' => __('Bu ayar bazı temalarda tasarım sorunlarına neden olabilir. Otomatik açılmaz.', 'wpxcache')],
	['label' => __('Minify JS', 'wpxcache'), 'enabled' => ! empty($optimization['minify_js']), 'risk' => 'Medium', 'message' => __('JavaScript minify sonraki adımda eklenecek; ödeme ve form scriptleri korunmalıdır.', 'wpxcache')],
	['label' => __('Delay JS execution', 'wpxcache'), 'enabled' => ! empty($optimization['delay_js']), 'risk' => 'Risky', 'message' => __('Menü, popup, form ve sepet davranışlarını bozabileceği için otomatik açılmaz.', 'wpxcache')],
	['label' => __('Remove generator meta', 'wpxcache'), 'enabled' => ! empty($optimization['remove_generator']), 'risk' => 'Safe', 'message' => __('WordPress generator meta çıktısını kaldırır.', 'wpxcache')],
];
?>
<div class="wrap wpxcache-admin">
	<div class="wpxcache-header">
		<div>
			<h1><?php echo esc_html__('File Optimization', 'wpxcache'); ?></h1>
			<p><?php echo esc_html__('Riskli dosya optimizasyonları otomatik açılmaz; önce test etmek gerekir.', 'wpxcache'); ?></p>
		</div>
		<span class="wpxcache-version"><?php echo esc_html(WPXCACHE_VERSION); ?></span>
	</div>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Optimization Status', 'wpxcache'); ?></h2>
		<div class="wpxcache-health-list">
			<?php foreach ($items as $item) : ?>
				<article class="wpxcache-health-item">
					<div class="wpxcache-health-heading">
						<span class="wpxcache-risk is-<?php echo esc_attr(strtolower($item['risk'])); ?>"><?php echo esc_html($item['risk']); ?></span>
						<strong><?php echo esc_html($item['label']); ?></strong>
					</div>
					<p><?php echo esc_html($item['message']); ?></p>
					<small><?php echo esc_html(! empty($item['enabled']) ? __('Enabled', 'wpxcache') : __('Disabled', 'wpxcache')); ?></small>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
</div>
