<?php
/**
 * Media template.
 *
 * @package WPXCache
 *
 * @var array<string, mixed> $settings
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$media = is_array($settings['media'] ?? null) ? $settings['media'] : [];
$items = [
	['label' => __('Lazy load images', 'wpxcache'), 'enabled' => ! empty($media['lazy_load_images']), 'risk' => 'Safe', 'message' => __('İçerikteki görsellere loading="lazy" ve decoding="async" ekler.', 'wpxcache')],
	['label' => __('Lazy load iframes', 'wpxcache'), 'enabled' => ! empty($media['lazy_load_iframes']), 'risk' => 'Safe', 'message' => __('İçerikteki iframe çıktısına loading="lazy" ekler.', 'wpxcache')],
	['label' => __('YouTube placeholder', 'wpxcache'), 'enabled' => ! empty($media['youtube_placeholder']), 'risk' => 'Medium', 'message' => __('YouTube placeholder ileriki sürümde eklenecek.', 'wpxcache')],
	['label' => __('Disable emoji', 'wpxcache'), 'enabled' => ! empty($media['disable_emoji']), 'risk' => 'Safe', 'message' => __('WordPress emoji script ve style çıktısını kapatır.', 'wpxcache')],
	['label' => __('Disable embeds', 'wpxcache'), 'enabled' => ! empty($media['disable_embeds']), 'risk' => 'Medium', 'message' => __('oEmbed discovery ve embed script çıktısını azaltır; embed kullanan sitelerde test edilmelidir.', 'wpxcache')],
];
?>
<div class="wrap wpxcache-admin">
	<div class="wpxcache-header">
		<div>
			<h1><?php echo esc_html__('Media', 'wpxcache'); ?></h1>
			<p><?php echo esc_html__('Görsel ve iframe optimizasyonları güvenli varsayılanlarla kapalı gelir.', 'wpxcache'); ?></p>
		</div>
		<span class="wpxcache-version"><?php echo esc_html(WPXCACHE_VERSION); ?></span>
	</div>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Media Optimization Status', 'wpxcache'); ?></h2>
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
