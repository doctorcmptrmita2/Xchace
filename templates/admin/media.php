<?php
/**
 * Media template.
 *
 * @package WPXCache
 *
 * @var array<string, mixed> $settings
 * @var array{type: string, message: string}|null $notice
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$media = is_array($settings['media'] ?? null) ? $settings['media'] : [];
$items = [
	['key' => 'lazy_load_images', 'label' => __('Lazy load images', 'wpxcache'), 'enabled' => ! empty($media['lazy_load_images']), 'risk' => 'Safe', 'message' => __('Adds loading="lazy" and decoding="async" to content images.', 'wpxcache')],
	['key' => 'lazy_load_iframes', 'label' => __('Lazy load iframes', 'wpxcache'), 'enabled' => ! empty($media['lazy_load_iframes']), 'risk' => 'Safe', 'message' => __('Adds loading="lazy" to content iframe output.', 'wpxcache')],
	['key' => 'youtube_placeholder', 'label' => __('YouTube placeholder', 'wpxcache'), 'enabled' => ! empty($media['youtube_placeholder']), 'risk' => 'Medium', 'message' => __('Saved as a setting; placeholder runtime will be expanded later.', 'wpxcache')],
	['key' => 'disable_emoji', 'label' => __('Disable emoji', 'wpxcache'), 'enabled' => ! empty($media['disable_emoji']), 'risk' => 'Safe', 'message' => __('Disables WordPress emoji script and style output.', 'wpxcache')],
	['key' => 'disable_embeds', 'label' => __('Disable embeds', 'wpxcache'), 'enabled' => ! empty($media['disable_embeds']), 'risk' => 'Medium', 'message' => __('Reduces oEmbed discovery and embed script output; test sites that use embeds.', 'wpxcache')],
];
?>
<div class="wrap wpxcache-admin">
	<?php if (is_array($notice)) : ?>
		<div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
			<p><?php echo esc_html($notice['message']); ?></p>
		</div>
	<?php endif; ?>

	<div class="wpxcache-header">
		<div>
			<h1><?php echo esc_html__('Media', 'wpxcache'); ?></h1>
			<p><?php echo esc_html__('Görsel ve iframe optimizasyonları güvenli varsayılanlarla kapalı gelir.', 'wpxcache'); ?></p>
		</div>
		<span class="wpxcache-version"><?php echo esc_html(WPXCACHE_VERSION); ?></span>
	</div>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Media Optimization Status', 'wpxcache'); ?></h2>
		<form method="post">
			<?php \WPXCache\Security\Nonce::field(); ?>
			<div class="wpxcache-health-list">
				<?php foreach ($items as $item) : ?>
					<label class="wpxcache-health-item">
						<span class="wpxcache-health-heading">
							<span class="wpxcache-risk is-<?php echo esc_attr(strtolower($item['risk'])); ?>"><?php echo esc_html($item['risk']); ?></span>
							<strong><?php echo esc_html($item['label']); ?></strong>
						</span>
						<input type="checkbox" name="<?php echo esc_attr($item['key']); ?>" <?php checked(! empty($item['enabled'])); ?>>
						<span><?php echo esc_html(! empty($item['enabled']) ? __('Enabled', 'wpxcache') : __('Disabled', 'wpxcache')); ?></span>
						<p><?php echo esc_html($item['message']); ?></p>
					</label>
				<?php endforeach; ?>
			</div>
			<div class="wpxcache-actions">
				<button class="button button-primary" type="submit" name="wpxcache_action" value="save_media_settings"><?php echo esc_html__('Save media settings', 'wpxcache'); ?></button>
			</div>
		</form>
	</section>
</div>
