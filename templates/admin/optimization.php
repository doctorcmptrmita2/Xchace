<?php
/**
 * Optimization template.
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

$optimization = is_array($settings['optimization'] ?? null) ? $settings['optimization'] : [];
$exclude_css = is_array($optimization['exclude_css'] ?? null) ? implode("\n", array_map('strval', $optimization['exclude_css'])) : '';
$exclude_js = is_array($optimization['exclude_js'] ?? null) ? implode("\n", array_map('strval', $optimization['exclude_js'])) : '';
$items = [
	['key' => 'minify_html', 'label' => __('Minify HTML', 'wpxcache'), 'enabled' => ! empty($optimization['minify_html']), 'risk' => 'Safe', 'message' => __('HTML comments and unnecessary whitespace are reduced conservatively.', 'wpxcache')],
	['key' => 'minify_css', 'label' => __('Minify CSS', 'wpxcache'), 'enabled' => ! empty($optimization['minify_css']), 'risk' => 'Medium', 'message' => __('Saved as a setting. Runtime CSS rewriting stays disabled by default until public asset delivery is verified.', 'wpxcache')],
	['key' => 'combine_css', 'label' => __('Combine CSS', 'wpxcache'), 'enabled' => ! empty($optimization['combine_css']), 'risk' => 'Risky', 'message' => __('Saved for compatibility planning; combine runtime will be added after stronger exclusions.', 'wpxcache')],
	['key' => 'defer_css', 'label' => __('Defer CSS', 'wpxcache'), 'enabled' => ! empty($optimization['defer_css']), 'risk' => 'Risky', 'message' => __('Saved as a setting. Runtime is disabled by default because it can break layout without Critical CSS.', 'wpxcache')],
	['key' => 'minify_js', 'label' => __('Minify JS', 'wpxcache'), 'enabled' => ! empty($optimization['minify_js']), 'risk' => 'Medium', 'message' => __('Local JS files are compacted conservatively; protected scripts are skipped in Safe Mode.', 'wpxcache')],
	['key' => 'defer_js', 'label' => __('Defer JS', 'wpxcache'), 'enabled' => ! empty($optimization['defer_js']), 'risk' => 'Risky', 'message' => __('Adds defer only to eligible scripts. Checkout, cart, forms and payment scripts stay protected.', 'wpxcache')],
	['key' => 'delay_js', 'label' => __('Delay JS execution', 'wpxcache'), 'enabled' => ! empty($optimization['delay_js']), 'risk' => 'Risky', 'message' => __('Saved as a setting; delay runtime needs per-plugin compatibility rules before activation.', 'wpxcache')],
	['key' => 'remove_generator', 'label' => __('Remove generator meta', 'wpxcache'), 'enabled' => ! empty($optimization['remove_generator']), 'risk' => 'Safe', 'message' => __('Removes the WordPress generator meta output.', 'wpxcache')],
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
				<strong><?php echo esc_html__('Safe Mode', 'wpxcache'); ?></strong>
				<span><?php echo esc_html__('Keeps risky optimization conservative for WooCommerce, forms and payment flows.', 'wpxcache'); ?></span>
			</label>
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
				<button class="button button-primary" type="submit" name="wpxcache_action" value="save_optimization_settings"><?php echo esc_html__('Save file optimization settings', 'wpxcache'); ?></button>
			</div>
		</form>
	</section>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Exclusions', 'wpxcache'); ?></h2>
		<form method="post">
			<?php \WPXCache\Security\Nonce::field(); ?>
			<?php foreach ($items as $item) : ?>
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
