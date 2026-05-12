<?php
/**
 * Media template.
 *
 * @package WPXCache
 *
 * @var array<string, mixed> $settings
 * @var array<string, mixed> $media
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
				<?php foreach ($risk_items as $item) : ?>
					<label class="wpxcache-health-item">
						<span class="wpxcache-health-heading">
							<?php echo \WPXCache\Admin\RiskRegistry::badge($item); ?>
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
