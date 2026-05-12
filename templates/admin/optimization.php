<?php
/**
 * Optimization template.
 *
 * @package WPXCache
 *
 * @var array<string, mixed> $settings
 * @var array<string, mixed> $optimization
 * @var array<string, array{key: string, label: string, level: string, risk_label: string, risk_class: string, message: string, enabled: bool}> $risk_items
 * @var array{type: string, message: string}|null $notice
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$exclude_css = is_array($optimization['exclude_css'] ?? null) ? implode("\n", array_map('strval', $optimization['exclude_css'])) : '';
$exclude_js = is_array($optimization['exclude_js'] ?? null) ? implode("\n", array_map('strval', $optimization['exclude_js'])) : '';
$safe_mode_item = $risk_items['safe_mode'];
?>
<div class="wrap wpxcache-admin">
	<?php if (is_array($notice)) : ?>
		<div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
			<p><?php echo esc_html($notice['message']); ?></p>
		</div>
	<?php endif; ?>

	<div class="wpxcache-header">
		<div>
			<h1><?php echo esc_html__('File Optimization', 'wpxcache'); ?></h1>
			<p><?php echo esc_html__('Riskli dosya optimizasyonları otomatik açılmaz; önce test etmek gerekir.', 'wpxcache'); ?></p>
		</div>
		<span class="wpxcache-version"><?php echo esc_html(WPXCACHE_VERSION); ?></span>
	</div>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Optimization Status', 'wpxcache'); ?></h2>
		<form method="post">
			<?php \WPXCache\Security\Nonce::field(); ?>
			<label class="wpxcache-toggle-row">
				<input type="checkbox" name="safe_mode" <?php checked(! empty($optimization['safe_mode'])); ?>>
				<?php echo \WPXCache\Admin\RiskRegistry::badge($safe_mode_item); ?>
				<strong><?php echo esc_html__('Safe Mode', 'wpxcache'); ?></strong>
				<span><?php echo esc_html($safe_mode_item['message']); ?></span>
			</label>
			<div class="wpxcache-health-list">
				<?php foreach ($risk_items as $item) : ?>
					<?php if ('safe_mode' === $item['key']) : ?>
						<?php continue; ?>
					<?php endif; ?>
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
				<button class="button button-primary" type="submit" name="wpxcache_action" value="save_optimization_settings"><?php echo esc_html__('Save file optimization settings', 'wpxcache'); ?></button>
			</div>
		</form>
	</section>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Exclusions', 'wpxcache'); ?></h2>
			<form method="post">
			<?php \WPXCache\Security\Nonce::field(); ?>
			<?php foreach ($risk_items as $item) : ?>
				<?php if ('safe_mode' === $item['key']) : ?>
					<?php continue; ?>
				<?php endif; ?>
				<input type="hidden" name="<?php echo esc_attr($item['key']); ?>" value="<?php echo ! empty($item['enabled']) ? '1' : ''; ?>">
			<?php endforeach; ?>
			<input type="hidden" name="safe_mode" value="<?php echo ! empty($optimization['safe_mode']) ? '1' : ''; ?>">
			<div class="wpxcache-field-grid">
				<label>
					<strong><?php echo esc_html__('Never optimize CSS containing', 'wpxcache'); ?></strong>
					<textarea class="large-text code" rows="6" name="exclude_css"><?php echo esc_textarea($exclude_css); ?></textarea>
					<span><?php echo esc_html__('One handle, URL part, or filename per line.', 'wpxcache'); ?></span>
				</label>
				<label>
					<strong><?php echo esc_html__('Never optimize JS containing', 'wpxcache'); ?></strong>
					<textarea class="large-text code" rows="6" name="exclude_js"><?php echo esc_textarea($exclude_js); ?></textarea>
					<span><?php echo esc_html__('Use this for sliders, forms, checkout scripts, ads, or custom dynamic scripts.', 'wpxcache'); ?></span>
				</label>
			</div>
			<div class="wpxcache-actions">
				<button class="button button-primary" type="submit" name="wpxcache_action" value="save_optimization_settings"><?php echo esc_html__('Save exclusions', 'wpxcache'); ?></button>
			</div>
		</form>
	</section>
</div>
