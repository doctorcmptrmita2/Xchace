<?php
/**
 * Setup wizard template.
 *
 * @package WPXCache
 *
 * @var array<string, mixed> $settings
 * @var array{id: string, label: string, confidence: int, signals: array<int, string>} $profile
 * @var array<string, mixed> $environment
 * @var array<int, array{id: string, label: string, status: string, problem: string, why: string, fix: string, auto_fix: bool, action: string, action_label: string}> $health_checks
 * @var array<int, array{level: string, message: string}> $conflicts
 * @var array{exists: bool, owned: bool, wp_cache: bool, path: string, config_exists: bool, writable: bool} $dropin
 * @var bool $wizard_completed
 * @var array<int, array{number: int, title: string, status: string, summary: string}> $steps
 * @var array<int, array{label: string, status: string, detail: string}> $safe_settings
 * @var array{type: string, message: string}|null $notice
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$cache_enabled = ! empty($settings['cache']['enabled']);
$woocommerce_safe = ! empty($settings['woocommerce']['safe_mode']);
$has_conflicts = [] !== $conflicts;
?>
<div class="wrap wpxcache-admin">
	<?php if (is_array($notice)) : ?>
		<div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
			<p><?php echo esc_html($notice['message']); ?></p>
		</div>
	<?php endif; ?>

	<div class="wpxcache-header">
		<div>
			<h1><?php echo esc_html__('Setup Wizard', 'wpxcache'); ?></h1>
			<p><?php echo esc_html__('Analyze the site, review risks, then apply only conservative settings.', 'wpxcache'); ?></p>
		</div>
		<span class="wpxcache-version"><?php echo esc_html(WPXCACHE_VERSION); ?></span>
	</div>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Setup progress', 'wpxcache'); ?></h2>
		<div class="wpxcache-wizard-steps">
			<?php foreach ($steps as $step) : ?>
				<article class="wpxcache-wizard-step">
					<span class="wpxcache-step-number"><?php echo esc_html(number_format_i18n($step['number'])); ?></span>
					<div>
						<strong><?php echo esc_html($step['title']); ?></strong>
						<p><?php echo esc_html($step['summary']); ?></p>
					</div>
					<span class="wpxcache-dot is-<?php echo esc_attr($step['status']); ?>"></span>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<div class="wpxcache-grid">
		<section class="wpxcache-panel">
			<h2><?php echo esc_html__('Detected profile', 'wpxcache'); ?></h2>
			<div class="wpxcache-status is-green">
				<span><?php echo esc_html($profile['label']); ?></span>
			</div>
			<p>
				<?php
				printf(
					/* translators: %d: profile confidence */
					esc_html__('Confidence: %d%%', 'wpxcache'),
					absint($profile['confidence'])
				);
				?>
			</p>
			<?php if ([] !== $profile['signals']) : ?>
				<ul class="wpxcache-list">
					<?php foreach ($profile['signals'] as $signal) : ?>
						<li><?php echo esc_html($signal); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p><?php echo esc_html__('No strong plugin signals were detected. Business-safe defaults are recommended.', 'wpxcache'); ?></p>
			<?php endif; ?>
		</section>

		<section class="wpxcache-panel">
			<h2><?php echo esc_html__('Current safety state', 'wpxcache'); ?></h2>
			<ul class="wpxcache-metrics">
				<li><strong><?php echo esc_html($cache_enabled ? __('On', 'wpxcache') : __('Off', 'wpxcache')); ?></strong><span><?php echo esc_html__('Page cache', 'wpxcache'); ?></span></li>
				<li><strong><?php echo esc_html($woocommerce_safe ? __('On', 'wpxcache') : __('Off', 'wpxcache')); ?></strong><span><?php echo esc_html__('Woo Safe Mode', 'wpxcache'); ?></span></li>
				<li><strong><?php echo esc_html($dropin['exists'] && $dropin['owned'] ? __('Ready', 'wpxcache') : __('Pending', 'wpxcache')); ?></strong><span><?php echo esc_html__('Drop-in', 'wpxcache'); ?></span></li>
				<li><strong><?php echo esc_html(! empty($environment['wp_cache']) ? __('On', 'wpxcache') : __('Off', 'wpxcache')); ?></strong><span><?php echo esc_html__('WP_CACHE', 'wpxcache'); ?></span></li>
			</ul>
		</section>

		<section class="wpxcache-panel">
			<h2><?php echo esc_html__('Safe settings to apply', 'wpxcache'); ?></h2>
			<div class="wpxcache-health-list is-compact">
				<?php foreach ($safe_settings as $item) : ?>
					<article class="wpxcache-health-item">
						<div class="wpxcache-health-heading">
							<span class="wpxcache-risk is-<?php echo esc_attr(strtolower($item['status'])); ?>"><?php echo esc_html($item['status']); ?></span>
							<strong><?php echo esc_html($item['label']); ?></strong>
						</div>
						<p><?php echo esc_html($item['detail']); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="wpxcache-panel">
			<h2><?php echo esc_html__('Actions', 'wpxcache'); ?></h2>
			<p><?php echo esc_html__('These actions are explicit. Risky CSS/JS, image conversion and database cache are not enabled here.', 'wpxcache'); ?></p>
			<form method="post" class="wpxcache-actions">
				<?php \WPXCache\Security\Nonce::field(); ?>
				<button class="button" type="submit" name="wpxcache_action" value="setup_prepare_directories"><?php echo esc_html__('Prepare cache directories', 'wpxcache'); ?></button>
				<button class="button" type="submit" name="wpxcache_action" value="setup_install_dropin"><?php echo esc_html__('Install drop-in', 'wpxcache'); ?></button>
				<button class="button button-primary" type="submit" name="wpxcache_action" value="setup_apply_safe_settings"><?php echo esc_html__('Apply safe settings', 'wpxcache'); ?></button>
				<button class="button button-secondary" type="submit" name="wpxcache_action" value="setup_mark_complete"><?php echo esc_html__('Mark complete', 'wpxcache'); ?></button>
			</form>
		</section>
	</div>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Conflict and health review', 'wpxcache'); ?></h2>
		<?php if (! $has_conflicts) : ?>
			<p><?php echo esc_html__('No active cache conflict was detected by the current checks.', 'wpxcache'); ?></p>
		<?php else : ?>
			<ul class="wpxcache-warning-list">
				<?php foreach ($conflicts as $conflict) : ?>
					<li><span class="wpxcache-dot is-<?php echo esc_attr($conflict['level']); ?>"></span><?php echo esc_html($conflict['message']); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Detailed health checks', 'wpxcache'); ?></h2>
		<div class="wpxcache-health-list">
			<?php foreach ($health_checks as $check) : ?>
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
</div>
