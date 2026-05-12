<?php
/**
 * Setup wizard template.
 *
 * @package WPXCache
 *
 * @var array<string, mixed> $settings
 * @var array<string, mixed> $cache
 * @var array<string, mixed> $optimization
 * @var array<string, mixed> $media
 * @var array<string, mixed> $preload
 * @var array<string, mixed> $woocommerce_settings
 * @var array<string, mixed> $cdn
 * @var array{id: string, label: string, confidence: int, signals: array<int, string>} $profile
 * @var string $selected_profile
 * @var array<string, mixed> $environment
 * @var array<int, array{id: string, label: string, status: string, problem: string, why: string, fix: string, auto_fix: bool, action: string, action_label: string}> $health_checks
 * @var array<int, array{level: string, message: string}> $conflicts
 * @var array{exists: bool, owned: bool, wp_cache: bool, path: string, config_exists: bool, writable: bool} $dropin
 * @var bool $wizard_completed
 * @var array<string, array{number: int, title: string, summary: string, state: string}> $wizard_steps
 * @var string $current_step
 * @var int $current_step_index
 * @var string $previous_step
 * @var string $next_step
 * @var array<string, string> $profile_options
 * @var array<int, array{label: string, status: string, detail: string}> $safe_settings
 * @var array<string, string> $advanced_text
 * @var array{type: string, message: string}|null $notice
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$wizard_url = static function (string $step): string {
	return admin_url('admin.php?page=wpxcache-setup&wpxcache_step=' . rawurlencode($step));
};

$optimization_items = [
	['key' => 'minify_html', 'label' => __('Minify HTML', 'wpxcache'), 'risk' => 'Safe', 'message' => __('Conservative HTML cleanup.', 'wpxcache')],
	['key' => 'remove_generator', 'label' => __('Remove generator meta', 'wpxcache'), 'risk' => 'Safe', 'message' => __('Removes the WordPress generator meta tag.', 'wpxcache')],
	['key' => 'minify_css', 'label' => __('Minify CSS', 'wpxcache'), 'risk' => 'Medium', 'message' => __('Saved as a setting; runtime CSS rewriting remains protected by default.', 'wpxcache')],
	['key' => 'minify_js', 'label' => __('Minify JS', 'wpxcache'), 'risk' => 'Medium', 'message' => __('Protected scripts are skipped in Safe Mode.', 'wpxcache')],
	['key' => 'combine_css', 'label' => __('Combine CSS', 'wpxcache'), 'risk' => 'Risky', 'message' => __('Can break load order on complex themes.', 'wpxcache')],
	['key' => 'defer_css', 'label' => __('Defer CSS', 'wpxcache'), 'risk' => 'Risky', 'message' => __('Can change first paint without Critical CSS.', 'wpxcache')],
	['key' => 'defer_js', 'label' => __('Defer JS', 'wpxcache'), 'risk' => 'Risky', 'message' => __('Can break menus, forms or checkout scripts if exclusions are missing.', 'wpxcache')],
	['key' => 'delay_js', 'label' => __('Delay JS execution', 'wpxcache'), 'risk' => 'Risky', 'message' => __('Requires careful plugin compatibility testing.', 'wpxcache')],
];

$media_items = [
	['key' => 'lazy_load_images', 'label' => __('Lazy load images', 'wpxcache'), 'risk' => 'Safe', 'message' => __('Adds native lazy loading to content images.', 'wpxcache')],
	['key' => 'lazy_load_iframes', 'label' => __('Lazy load iframes', 'wpxcache'), 'risk' => 'Safe', 'message' => __('Defers iframe loading with native browser behavior.', 'wpxcache')],
	['key' => 'disable_emoji', 'label' => __('Disable emoji', 'wpxcache'), 'risk' => 'Safe', 'message' => __('Removes WordPress emoji assets.', 'wpxcache')],
	['key' => 'youtube_placeholder', 'label' => __('YouTube placeholder', 'wpxcache'), 'risk' => 'Medium', 'message' => __('Useful later for video-heavy pages; test embeds before enabling.', 'wpxcache')],
	['key' => 'disable_embeds', 'label' => __('Disable embeds', 'wpxcache'), 'risk' => 'Medium', 'message' => __('Can affect sites that rely on oEmbed previews.', 'wpxcache')],
];

$exclude_css = is_array($optimization['exclude_css'] ?? null) ? implode("\n", array_map('strval', $optimization['exclude_css'])) : '';
$exclude_js = is_array($optimization['exclude_js'] ?? null) ? implode("\n", array_map('strval', $optimization['exclude_js'])) : '';
$cdn_file_types = is_array($cdn['included_file_types'] ?? null) ? implode(',', array_map('strval', $cdn['included_file_types'])) : '';
$cdn_excluded_paths = is_array($cdn['excluded_paths'] ?? null) ? implode("\n", array_map('strval', $cdn['excluded_paths'])) : '';
?>
<div class="wrap wpxcache-admin wpxcache-setup">
	<?php if (is_array($notice)) : ?>
		<div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
			<p><?php echo esc_html($notice['message']); ?></p>
		</div>
	<?php endif; ?>

	<div class="wpxcache-header">
		<div>
			<h1><?php echo esc_html__('Setup Wizard', 'wpxcache'); ?></h1>
			<p><?php echo esc_html__('Run the first setup step by step. You can skip any step without changing that setting group.', 'wpxcache'); ?></p>
		</div>
		<span class="wpxcache-version"><?php echo esc_html(WPXCACHE_VERSION); ?></span>
	</div>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Setup steps', 'wpxcache'); ?></h2>
		<div class="wpxcache-wizard-steps is-flow">
			<?php foreach ($wizard_steps as $step_key => $step) : ?>
				<a class="wpxcache-wizard-step is-<?php echo esc_attr($step['state']); ?>" href="<?php echo esc_url($wizard_url($step_key)); ?>">
					<span class="wpxcache-step-number"><?php echo esc_html(number_format_i18n($step['number'])); ?></span>
					<span>
						<strong><?php echo esc_html($step['title']); ?></strong>
						<small><?php echo esc_html($step['summary']); ?></small>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="wpxcache-panel wpxcache-panel-wide wpxcache-wizard-stage">
		<?php if ('analysis' === $current_step) : ?>
			<h2><?php echo esc_html__('1. Site analysis', 'wpxcache'); ?></h2>
			<p><?php echo esc_html__('WP XCache detected your site profile and current cache risks. Pick the closest profile, then continue.', 'wpxcache'); ?></p>
			<form method="post">
				<?php \WPXCache\Security\Nonce::field(); ?>
				<input type="hidden" name="wpxcache_current_step" value="analysis">
				<input type="hidden" name="wpxcache_next_step" value="cache">

				<div class="wpxcache-field-grid">
					<section>
						<h3><?php echo esc_html__('Recommended profile', 'wpxcache'); ?></h3>
						<div class="wpxcache-status is-green"><span><?php echo esc_html($profile['label']); ?></span></div>
						<p>
							<?php
							printf(
								/* translators: %d: confidence percent */
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
						<?php endif; ?>
					</section>

					<section>
						<h3><?php echo esc_html__('Choose site type', 'wpxcache'); ?></h3>
						<div class="wpxcache-choice-list">
							<?php foreach ($profile_options as $profile_key => $profile_label) : ?>
								<label class="wpxcache-choice-card">
									<input type="radio" name="profile_choice" value="<?php echo esc_attr($profile_key); ?>" <?php checked($selected_profile, $profile_key); ?>>
									<strong><?php echo esc_html($profile_label); ?></strong>
								</label>
							<?php endforeach; ?>
						</div>
					</section>
				</div>

				<div class="wpxcache-health-list is-compact">
					<?php foreach (array_slice($health_checks, 0, 6) as $check) : ?>
						<article class="wpxcache-health-item">
							<div class="wpxcache-health-heading">
								<span class="wpxcache-dot is-<?php echo esc_attr($check['status']); ?>"></span>
								<strong><?php echo esc_html($check['label']); ?></strong>
							</div>
							<p><?php echo esc_html($check['problem']); ?></p>
						</article>
					<?php endforeach; ?>
				</div>

				<?php if ([] !== $conflicts) : ?>
					<h3><?php echo esc_html__('Detected warnings', 'wpxcache'); ?></h3>
					<ul class="wpxcache-warning-list">
						<?php foreach ($conflicts as $conflict) : ?>
							<li><span class="wpxcache-dot is-<?php echo esc_attr($conflict['level']); ?>"></span><?php echo esc_html($conflict['message']); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<div class="wpxcache-actions wpxcache-wizard-actions">
					<button class="button button-primary" type="submit" name="wpxcache_action" value="setup_save_step"><?php echo esc_html__('Save and continue', 'wpxcache'); ?></button>
					<button class="button" type="submit" name="wpxcache_action" value="setup_skip_step"><?php echo esc_html__('Geç', 'wpxcache'); ?></button>
					<button class="button-link" type="submit" name="wpxcache_action" value="setup_skip_wizard"><?php echo esc_html__('Skip setup', 'wpxcache'); ?></button>
				</div>
			</form>
		<?php endif; ?>

		<?php if ('cache' === $current_step) : ?>
			<h2><?php echo esc_html__('2. Page cache settings', 'wpxcache'); ?></h2>
			<p><?php echo esc_html__('Only anonymous safe GET requests are cached. Logged-in users, REST, AJAX and WooCommerce dynamic pages stay protected by rules.', 'wpxcache'); ?></p>
			<form method="post">
				<?php \WPXCache\Security\Nonce::field(); ?>
				<input type="hidden" name="wpxcache_current_step" value="cache">
				<input type="hidden" name="wpxcache_next_step" value="optimization">

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><?php echo esc_html__('Enable page cache', 'wpxcache'); ?></th>
							<td><label><input type="checkbox" name="cache_enabled" <?php checked(! empty($cache['enabled'])); ?>> <?php echo esc_html__('Cache anonymous public pages.', 'wpxcache'); ?></label></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__('Cache lifespan TTL', 'wpxcache'); ?></th>
							<td><input class="small-text" type="number" min="60" max="<?php echo esc_attr((string) WEEK_IN_SECONDS); ?>" name="cache_ttl" value="<?php echo esc_attr((string) ($cache['ttl'] ?? 3600)); ?>"> <?php echo esc_html__('seconds', 'wpxcache'); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__('Safe purge behavior', 'wpxcache'); ?></th>
							<td>
								<label><input type="checkbox" name="purge_home_on_update" <?php checked(! empty($cache['purge_home_on_update'])); ?>> <?php echo esc_html__('Purge homepage on content update.', 'wpxcache'); ?></label><br>
								<label><input type="checkbox" name="purge_archives_on_update" <?php checked(! empty($cache['purge_archives_on_update'])); ?>> <?php echo esc_html__('Purge related archives on content update.', 'wpxcache'); ?></label><br>
								<label><input type="checkbox" name="separate_mobile_cache" <?php checked(! empty($cache['separate_mobile_cache'])); ?>> <?php echo esc_html__('Create separate mobile cache when needed.', 'wpxcache'); ?></label>
							</td>
						</tr>
					</tbody>
				</table>

				<h3><?php echo esc_html__('Risky cache options', 'wpxcache'); ?></h3>
				<div class="wpxcache-health-list">
					<label class="wpxcache-health-item"><span class="wpxcache-risk is-risky"><?php echo esc_html__('Risky', 'wpxcache'); ?></span><br><input type="checkbox" name="cache_logged_in_users" <?php checked(! empty($cache['cache_logged_in_users'])); ?>> <?php echo esc_html__('Cache logged-in users', 'wpxcache'); ?></label>
					<label class="wpxcache-health-item"><span class="wpxcache-risk is-medium"><?php echo esc_html__('Medium', 'wpxcache'); ?></span><br><input type="checkbox" name="cache_404" <?php checked(! empty($cache['cache_404'])); ?>> <?php echo esc_html__('Cache 404 pages', 'wpxcache'); ?></label>
					<label class="wpxcache-health-item"><span class="wpxcache-risk is-medium"><?php echo esc_html__('Medium', 'wpxcache'); ?></span><br><input type="checkbox" name="cache_search" <?php checked(! empty($cache['cache_search'])); ?>> <?php echo esc_html__('Cache search pages', 'wpxcache'); ?></label>
					<label class="wpxcache-health-item"><span class="wpxcache-risk is-medium"><?php echo esc_html__('Medium', 'wpxcache'); ?></span><br><input type="checkbox" name="cache_feeds" <?php checked(! empty($cache['cache_feeds'])); ?>> <?php echo esc_html__('Cache feeds', 'wpxcache'); ?></label>
					<label class="wpxcache-health-item"><span class="wpxcache-risk is-risky"><?php echo esc_html__('Risky', 'wpxcache'); ?></span><br><input type="checkbox" name="cache_rest_api" <?php checked(! empty($cache['cache_rest_api'])); ?>> <?php echo esc_html__('Cache REST API', 'wpxcache'); ?></label>
				</div>

				<div class="wpxcache-actions wpxcache-wizard-actions">
					<a class="button" href="<?php echo esc_url($wizard_url($previous_step)); ?>"><?php echo esc_html__('Back', 'wpxcache'); ?></a>
					<button class="button button-primary" type="submit" name="wpxcache_action" value="setup_save_step"><?php echo esc_html__('Save and continue', 'wpxcache'); ?></button>
					<button class="button" type="submit" name="wpxcache_action" value="setup_skip_step"><?php echo esc_html__('Geç', 'wpxcache'); ?></button>
					<button class="button-link" type="submit" name="wpxcache_action" value="setup_skip_wizard"><?php echo esc_html__('Skip setup', 'wpxcache'); ?></button>
				</div>
			</form>
		<?php endif; ?>

		<?php if ('optimization' === $current_step) : ?>
			<h2><?php echo esc_html__('3. File optimization', 'wpxcache'); ?></h2>
			<p><?php echo esc_html__('Safe Mode should stay enabled. Risky CSS/JS options are saved only when you explicitly select them.', 'wpxcache'); ?></p>
			<form method="post">
				<?php \WPXCache\Security\Nonce::field(); ?>
				<input type="hidden" name="wpxcache_current_step" value="optimization">
				<input type="hidden" name="wpxcache_next_step" value="media">
				<label class="wpxcache-toggle-row">
					<input type="checkbox" name="safe_mode" <?php checked(! empty($optimization['safe_mode'])); ?>>
					<strong><?php echo esc_html__('Safe Mode', 'wpxcache'); ?></strong>
					<span><?php echo esc_html__('Protect WooCommerce, forms, checkout and payment scripts from aggressive optimization.', 'wpxcache'); ?></span>
				</label>
				<div class="wpxcache-health-list">
					<?php foreach ($optimization_items as $item) : ?>
						<label class="wpxcache-health-item">
							<span class="wpxcache-health-heading">
								<span class="wpxcache-risk is-<?php echo esc_attr(strtolower($item['risk'])); ?>"><?php echo esc_html($item['risk']); ?></span>
								<strong><?php echo esc_html($item['label']); ?></strong>
							</span>
							<input type="checkbox" name="<?php echo esc_attr($item['key']); ?>" <?php checked(! empty($optimization[$item['key']])); ?>>
							<span><?php echo esc_html(! empty($optimization[$item['key']]) ? __('Enabled', 'wpxcache') : __('Disabled', 'wpxcache')); ?></span>
							<p><?php echo esc_html($item['message']); ?></p>
						</label>
					<?php endforeach; ?>
				</div>
				<div class="wpxcache-field-grid">
					<label>
						<strong><?php echo esc_html__('Never optimize CSS containing', 'wpxcache'); ?></strong>
						<textarea class="large-text code" rows="5" name="exclude_css"><?php echo esc_textarea($exclude_css); ?></textarea>
					</label>
					<label>
						<strong><?php echo esc_html__('Never optimize JS containing', 'wpxcache'); ?></strong>
						<textarea class="large-text code" rows="5" name="exclude_js"><?php echo esc_textarea($exclude_js); ?></textarea>
					</label>
				</div>
				<div class="wpxcache-actions wpxcache-wizard-actions">
					<a class="button" href="<?php echo esc_url($wizard_url($previous_step)); ?>"><?php echo esc_html__('Back', 'wpxcache'); ?></a>
					<button class="button button-primary" type="submit" name="wpxcache_action" value="setup_save_step"><?php echo esc_html__('Save and continue', 'wpxcache'); ?></button>
					<button class="button" type="submit" name="wpxcache_action" value="setup_skip_step"><?php echo esc_html__('Geç', 'wpxcache'); ?></button>
					<button class="button-link" type="submit" name="wpxcache_action" value="setup_skip_wizard"><?php echo esc_html__('Skip setup', 'wpxcache'); ?></button>
				</div>
			</form>
		<?php endif; ?>

		<?php if ('media' === $current_step) : ?>
			<h2><?php echo esc_html__('4. Media optimization', 'wpxcache'); ?></h2>
			<p><?php echo esc_html__('Choose lightweight media settings. Image conversion and compression are not enabled in this wizard yet.', 'wpxcache'); ?></p>
			<form method="post">
				<?php \WPXCache\Security\Nonce::field(); ?>
				<input type="hidden" name="wpxcache_current_step" value="media">
				<input type="hidden" name="wpxcache_next_step" value="preload">
				<div class="wpxcache-health-list">
					<?php foreach ($media_items as $item) : ?>
						<label class="wpxcache-health-item">
							<span class="wpxcache-health-heading">
								<span class="wpxcache-risk is-<?php echo esc_attr(strtolower($item['risk'])); ?>"><?php echo esc_html($item['risk']); ?></span>
								<strong><?php echo esc_html($item['label']); ?></strong>
							</span>
							<input type="checkbox" name="<?php echo esc_attr($item['key']); ?>" <?php checked(! empty($media[$item['key']])); ?>>
							<span><?php echo esc_html(! empty($media[$item['key']]) ? __('Enabled', 'wpxcache') : __('Disabled', 'wpxcache')); ?></span>
							<p><?php echo esc_html($item['message']); ?></p>
						</label>
					<?php endforeach; ?>
				</div>
				<div class="wpxcache-actions wpxcache-wizard-actions">
					<a class="button" href="<?php echo esc_url($wizard_url($previous_step)); ?>"><?php echo esc_html__('Back', 'wpxcache'); ?></a>
					<button class="button button-primary" type="submit" name="wpxcache_action" value="setup_save_step"><?php echo esc_html__('Save and continue', 'wpxcache'); ?></button>
					<button class="button" type="submit" name="wpxcache_action" value="setup_skip_step"><?php echo esc_html__('Geç', 'wpxcache'); ?></button>
					<button class="button-link" type="submit" name="wpxcache_action" value="setup_skip_wizard"><?php echo esc_html__('Skip setup', 'wpxcache'); ?></button>
				</div>
			</form>
		<?php endif; ?>

		<?php if ('preload' === $current_step) : ?>
			<h2><?php echo esc_html__('5. Preload', 'wpxcache'); ?></h2>
			<p><?php echo esc_html__('Keep batches small on shared hosting. Preload can be skipped and enabled later.', 'wpxcache'); ?></p>
			<form method="post">
				<?php \WPXCache\Security\Nonce::field(); ?>
				<input type="hidden" name="wpxcache_current_step" value="preload">
				<input type="hidden" name="wpxcache_next_step" value="woocommerce">
				<table class="form-table" role="presentation">
					<tbody>
						<tr><th scope="row"><?php echo esc_html__('Enable preload', 'wpxcache'); ?></th><td><label><input type="checkbox" name="preload_enabled" <?php checked(! empty($preload['enabled'])); ?>> <?php echo esc_html__('Allow WP-Cron warmup queue.', 'wpxcache'); ?></label></td></tr>
						<tr><th scope="row"><?php echo esc_html__('Sitemap URL', 'wpxcache'); ?></th><td><input class="regular-text" type="url" name="sitemap_url" value="<?php echo esc_attr((string) ($preload['sitemap_url'] ?? '')); ?>" placeholder="<?php echo esc_attr(home_url('/sitemap.xml')); ?>"></td></tr>
						<tr>
							<th scope="row"><?php echo esc_html__('URLs to preload', 'wpxcache'); ?></th>
							<td>
								<label><input type="checkbox" name="preload_homepage" <?php checked(! empty($preload['preload_homepage'])); ?>> <?php echo esc_html__('Homepage', 'wpxcache'); ?></label><br>
								<label><input type="checkbox" name="preload_posts" <?php checked(! empty($preload['preload_posts'])); ?>> <?php echo esc_html__('Recent posts', 'wpxcache'); ?></label><br>
								<label><input type="checkbox" name="preload_pages" <?php checked(! empty($preload['preload_pages'])); ?>> <?php echo esc_html__('Pages', 'wpxcache'); ?></label><br>
								<label><input type="checkbox" name="preload_products" <?php checked(! empty($preload['preload_products'])); ?>> <?php echo esc_html__('Products', 'wpxcache'); ?></label>
							</td>
						</tr>
						<tr><th scope="row"><?php echo esc_html__('Batch size', 'wpxcache'); ?></th><td><input class="small-text" type="number" min="1" max="10" name="batch_size" value="<?php echo esc_attr((string) ($preload['batch_size'] ?? 3)); ?>"></td></tr>
						<tr><th scope="row"><?php echo esc_html__('Delay between batches', 'wpxcache'); ?></th><td><input class="small-text" type="number" min="1" max="120" name="delay" value="<?php echo esc_attr((string) ($preload['delay'] ?? 10)); ?>"> <?php echo esc_html__('seconds', 'wpxcache'); ?></td></tr>
						<tr><th scope="row"><?php echo esc_html__('Auto preload after purge', 'wpxcache'); ?></th><td><label><input type="checkbox" name="auto_after_purge" <?php checked(! empty($preload['auto_after_purge'])); ?>> <?php echo esc_html__('Start warmup after cache purge.', 'wpxcache'); ?></label></td></tr>
					</tbody>
				</table>
				<div class="wpxcache-actions wpxcache-wizard-actions">
					<a class="button" href="<?php echo esc_url($wizard_url($previous_step)); ?>"><?php echo esc_html__('Back', 'wpxcache'); ?></a>
					<button class="button button-primary" type="submit" name="wpxcache_action" value="setup_save_step"><?php echo esc_html__('Save and continue', 'wpxcache'); ?></button>
					<button class="button" type="submit" name="wpxcache_action" value="setup_skip_step"><?php echo esc_html__('Geç', 'wpxcache'); ?></button>
					<button class="button-link" type="submit" name="wpxcache_action" value="setup_skip_wizard"><?php echo esc_html__('Skip setup', 'wpxcache'); ?></button>
				</div>
			</form>
		<?php endif; ?>

		<?php if ('woocommerce' === $current_step) : ?>
			<h2><?php echo esc_html__('6. WooCommerce safety', 'wpxcache'); ?></h2>
			<p><?php echo esc_html__('Cart, checkout, account and session-aware requests must stay excluded from page cache.', 'wpxcache'); ?></p>
			<form method="post">
				<?php \WPXCache\Security\Nonce::field(); ?>
				<input type="hidden" name="wpxcache_current_step" value="woocommerce">
				<input type="hidden" name="wpxcache_next_step" value="cdn">
				<table class="form-table" role="presentation">
					<tbody>
						<tr><th scope="row"><?php echo esc_html__('WooCommerce Safe Mode', 'wpxcache'); ?></th><td><label><input type="checkbox" name="woocommerce_safe_mode" <?php checked(! empty($woocommerce_settings['safe_mode'])); ?>> <?php echo esc_html__('Keep cart, checkout, account and session cookies protected.', 'wpxcache'); ?></label></td></tr>
						<tr><th scope="row"><?php echo esc_html__('Product cache TTL', 'wpxcache'); ?></th><td><input class="small-text" type="number" min="60" max="<?php echo esc_attr((string) WEEK_IN_SECONDS); ?>" name="product_cache_ttl" value="<?php echo esc_attr((string) ($woocommerce_settings['product_cache_ttl'] ?? 3600)); ?>"> <?php echo esc_html__('seconds', 'wpxcache'); ?></td></tr>
						<tr><th scope="row"><?php echo esc_html__('Shop archive cache TTL', 'wpxcache'); ?></th><td><input class="small-text" type="number" min="60" max="<?php echo esc_attr((string) WEEK_IN_SECONDS); ?>" name="shop_archive_cache_ttl" value="<?php echo esc_attr((string) ($woocommerce_settings['shop_archive_cache_ttl'] ?? 3600)); ?>"> <?php echo esc_html__('seconds', 'wpxcache'); ?></td></tr>
						<tr>
							<th scope="row"><?php echo esc_html__('Purge rules', 'wpxcache'); ?></th>
							<td>
								<label><input type="checkbox" name="stock_update_purge" <?php checked(! empty($woocommerce_settings['stock_update_purge'])); ?>> <?php echo esc_html__('Purge product cache after stock updates.', 'wpxcache'); ?></label><br>
								<label><input type="checkbox" name="price_update_purge" <?php checked(! empty($woocommerce_settings['price_update_purge'])); ?>> <?php echo esc_html__('Purge product cache after price updates.', 'wpxcache'); ?></label><br>
								<label><input type="checkbox" name="cart_fragment_safe_mode" <?php checked(! empty($woocommerce_settings['cart_fragment_safe_mode'])); ?>> <?php echo esc_html__('Mini cart compatibility mode.', 'wpxcache'); ?></label>
							</td>
						</tr>
					</tbody>
				</table>
				<div class="wpxcache-actions wpxcache-wizard-actions">
					<a class="button" href="<?php echo esc_url($wizard_url($previous_step)); ?>"><?php echo esc_html__('Back', 'wpxcache'); ?></a>
					<button class="button button-primary" type="submit" name="wpxcache_action" value="setup_save_step"><?php echo esc_html__('Save and continue', 'wpxcache'); ?></button>
					<button class="button" type="submit" name="wpxcache_action" value="setup_skip_step"><?php echo esc_html__('Geç', 'wpxcache'); ?></button>
					<button class="button-link" type="submit" name="wpxcache_action" value="setup_skip_wizard"><?php echo esc_html__('Skip setup', 'wpxcache'); ?></button>
				</div>
			</form>
		<?php endif; ?>

		<?php if ('cdn' === $current_step) : ?>
			<h2><?php echo esc_html__('7. CDN and Cloudflare', 'wpxcache'); ?></h2>
			<p><?php echo esc_html__('CDN is optional. Leave it disabled unless your CDN URL and purge flow are ready.', 'wpxcache'); ?></p>
			<form method="post">
				<?php \WPXCache\Security\Nonce::field(); ?>
				<input type="hidden" name="wpxcache_current_step" value="cdn">
				<input type="hidden" name="wpxcache_next_step" value="advanced">
				<div class="wpxcache-field-grid">
					<section>
						<h3><?php echo esc_html__('CDN rewrite', 'wpxcache'); ?></h3>
						<label><input type="checkbox" name="cdn_enabled" <?php checked(! empty($cdn['enabled'])); ?>> <?php echo esc_html__('Enable CDN rewrite for static files.', 'wpxcache'); ?></label>
						<p><label><?php echo esc_html__('CDN base URL', 'wpxcache'); ?></label></p>
						<input class="regular-text" type="url" name="cdn_base_url" value="<?php echo esc_attr((string) ($cdn['base_url'] ?? '')); ?>" placeholder="https://cdn.example.com">
						<p><label><?php echo esc_html__('Included file types', 'wpxcache'); ?></label></p>
						<input class="regular-text" type="text" name="cdn_included_file_types" value="<?php echo esc_attr($cdn_file_types); ?>">
						<p><label><?php echo esc_html__('Excluded paths', 'wpxcache'); ?></label></p>
						<textarea class="large-text code" rows="5" name="cdn_excluded_paths"><?php echo esc_textarea($cdn_excluded_paths); ?></textarea>
					</section>
					<section>
						<h3><?php echo esc_html__('Cloudflare', 'wpxcache'); ?></h3>
						<label><input type="checkbox" name="cloudflare_enabled" <?php checked(! empty($cdn['cloudflare_enabled'])); ?>> <?php echo esc_html__('Enable Cloudflare integration.', 'wpxcache'); ?></label>
						<p><label><?php echo esc_html__('API token', 'wpxcache'); ?></label></p>
						<input class="regular-text" type="password" name="cloudflare_api_token" value="<?php echo esc_attr((string) ($cdn['cloudflare_api_token'] ?? '')); ?>" autocomplete="off">
						<p><label><?php echo esc_html__('Zone ID', 'wpxcache'); ?></label></p>
						<input class="regular-text" type="text" name="cloudflare_zone_id" value="<?php echo esc_attr((string) ($cdn['cloudflare_zone_id'] ?? '')); ?>">
						<p><label><input type="checkbox" name="purge_cloudflare_on_purge" <?php checked(! empty($cdn['purge_cloudflare_on_purge'])); ?>> <?php echo esc_html__('Purge Cloudflare when local cache is purged.', 'wpxcache'); ?></label></p>
					</section>
				</div>
				<div class="wpxcache-actions wpxcache-wizard-actions">
					<a class="button" href="<?php echo esc_url($wizard_url($previous_step)); ?>"><?php echo esc_html__('Back', 'wpxcache'); ?></a>
					<button class="button button-primary" type="submit" name="wpxcache_action" value="setup_save_step"><?php echo esc_html__('Save and continue', 'wpxcache'); ?></button>
					<button class="button" type="submit" name="wpxcache_action" value="setup_skip_step"><?php echo esc_html__('Geç', 'wpxcache'); ?></button>
					<button class="button-link" type="submit" name="wpxcache_action" value="setup_skip_wizard"><?php echo esc_html__('Skip setup', 'wpxcache'); ?></button>
				</div>
			</form>
		<?php endif; ?>

		<?php if ('advanced' === $current_step) : ?>
			<h2><?php echo esc_html__('8. Advanced cache rules', 'wpxcache'); ?></h2>
			<p><?php echo esc_html__('These rules control exclusions and cache variations. The defaults already protect WordPress login, REST and WooCommerce sessions.', 'wpxcache'); ?></p>
			<form method="post">
				<?php \WPXCache\Security\Nonce::field(); ?>
				<input type="hidden" name="wpxcache_current_step" value="advanced">
				<input type="hidden" name="wpxcache_next_step" value="finish">
				<div class="wpxcache-field-grid">
					<label><strong><?php echo esc_html__('Never cache URLs', 'wpxcache'); ?></strong><textarea class="large-text code" rows="7" name="never_cache_urls"><?php echo esc_textarea($advanced_text['never_cache_urls']); ?></textarea></label>
					<label><strong><?php echo esc_html__('Never cache cookies', 'wpxcache'); ?></strong><textarea class="large-text code" rows="7" name="never_cache_cookies"><?php echo esc_textarea($advanced_text['never_cache_cookies']); ?></textarea></label>
					<label><strong><?php echo esc_html__('Never cache user agents', 'wpxcache'); ?></strong><textarea class="large-text code" rows="7" name="never_cache_user_agents"><?php echo esc_textarea($advanced_text['never_cache_user_agents']); ?></textarea></label>
					<label><strong><?php echo esc_html__('Query string whitelist', 'wpxcache'); ?></strong><textarea class="large-text code" rows="7" name="query_string_whitelist"><?php echo esc_textarea($advanced_text['query_string_whitelist']); ?></textarea></label>
				</div>
				<p><label><strong><?php echo esc_html__('Custom TTL per path', 'wpxcache'); ?></strong></label></p>
				<textarea class="large-text code" rows="6" name="custom_ttl" placeholder="/blog|900"><?php echo esc_textarea($advanced_text['custom_ttl']); ?></textarea>
				<div class="wpxcache-actions wpxcache-wizard-actions">
					<a class="button" href="<?php echo esc_url($wizard_url($previous_step)); ?>"><?php echo esc_html__('Back', 'wpxcache'); ?></a>
					<button class="button button-primary" type="submit" name="wpxcache_action" value="setup_save_step"><?php echo esc_html__('Save and continue', 'wpxcache'); ?></button>
					<button class="button" type="submit" name="wpxcache_action" value="setup_skip_step"><?php echo esc_html__('Geç', 'wpxcache'); ?></button>
					<button class="button-link" type="submit" name="wpxcache_action" value="setup_skip_wizard"><?php echo esc_html__('Skip setup', 'wpxcache'); ?></button>
				</div>
			</form>
		<?php endif; ?>

		<?php if ('finish' === $current_step) : ?>
			<h2><?php echo esc_html__('9. Finish setup', 'wpxcache'); ?></h2>
			<p><?php echo esc_html__('Review the final safety state. You can prepare directories, install the drop-in, apply conservative settings or simply complete the wizard.', 'wpxcache'); ?></p>
			<div class="wpxcache-grid">
				<div class="wpxcache-summary-box">
					<h3><?php echo esc_html__('Current state', 'wpxcache'); ?></h3>
					<ul class="wpxcache-metrics">
						<li><strong><?php echo esc_html(! empty($cache['enabled']) ? __('On', 'wpxcache') : __('Off', 'wpxcache')); ?></strong><span><?php echo esc_html__('Page cache', 'wpxcache'); ?></span></li>
						<li><strong><?php echo esc_html(! empty($woocommerce_settings['safe_mode']) ? __('On', 'wpxcache') : __('Off', 'wpxcache')); ?></strong><span><?php echo esc_html__('Woo Safe Mode', 'wpxcache'); ?></span></li>
						<li><strong><?php echo esc_html($dropin['exists'] && $dropin['owned'] ? __('Ready', 'wpxcache') : __('Pending', 'wpxcache')); ?></strong><span><?php echo esc_html__('Drop-in', 'wpxcache'); ?></span></li>
						<li><strong><?php echo esc_html(! empty($environment['wp_cache']) ? __('On', 'wpxcache') : __('Off', 'wpxcache')); ?></strong><span><?php echo esc_html__('WP_CACHE', 'wpxcache'); ?></span></li>
					</ul>
				</div>
				<div class="wpxcache-summary-box">
					<h3><?php echo esc_html__('Safe settings summary', 'wpxcache'); ?></h3>
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
				</div>
			</div>
			<form method="post">
				<?php \WPXCache\Security\Nonce::field(); ?>
				<div class="wpxcache-actions wpxcache-wizard-actions">
					<a class="button" href="<?php echo esc_url($wizard_url($previous_step)); ?>"><?php echo esc_html__('Back', 'wpxcache'); ?></a>
					<button class="button" type="submit" name="wpxcache_action" value="setup_prepare_directories"><?php echo esc_html__('Prepare cache directories', 'wpxcache'); ?></button>
					<button class="button" type="submit" name="wpxcache_action" value="setup_install_dropin"><?php echo esc_html__('Install drop-in', 'wpxcache'); ?></button>
					<button class="button" type="submit" name="wpxcache_action" value="setup_apply_safe_settings"><?php echo esc_html__('Apply safe settings', 'wpxcache'); ?></button>
					<button class="button button-primary" type="submit" name="wpxcache_action" value="setup_mark_complete"><?php echo esc_html__('Complete setup', 'wpxcache'); ?></button>
					<button class="button-link" type="submit" name="wpxcache_action" value="setup_skip_wizard"><?php echo esc_html__('Geç', 'wpxcache'); ?></button>
				</div>
			</form>
			<?php if ($wizard_completed) : ?>
				<p><a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=wpxcache')); ?>"><?php echo esc_html__('Go to Dashboard', 'wpxcache'); ?></a></p>
			<?php endif; ?>
		<?php endif; ?>
	</section>
</div>
