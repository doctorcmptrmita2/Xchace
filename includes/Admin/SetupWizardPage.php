<?php
/**
 * First-run setup wizard.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Admin;

use WPXCache\Cache\AdvancedCacheInstaller;
use WPXCache\Core\Config;
use WPXCache\Diagnostics\ConflictDetector;
use WPXCache\Diagnostics\EnvironmentScanner;
use WPXCache\Diagnostics\HealthCheck;
use WPXCache\Logger\Logger;
use WPXCache\Profile\ProfileEngine;
use WPXCache\Profile\SafeSettingsApplier;
use WPXCache\Security\Capability;
use WPXCache\Security\FileGuard;
use WPXCache\Security\Nonce;

if (! defined('ABSPATH')) {
	exit;
}

final class SetupWizardPage {
	private const COMPLETED_OPTION = 'wpxcache_setup_wizard_completed';
	private const CURRENT_STEP_OPTION = 'wpxcache_setup_wizard_step';
	private const PROFILE_OPTION = 'wpxcache_setup_profile_choice';

	/**
	 * @var array<int, string>
	 */
	private const STEP_ORDER = [
		'analysis',
		'cache',
		'optimization',
		'media',
		'preload',
		'woocommerce',
		'cdn',
		'advanced',
		'finish',
	];

	private string $current_step = 'analysis';

	public function render(): void {
		Capability::require_manage();

		$this->current_step = $this->requested_step();
		$notice = $this->handle_action();

		$settings = Config::settings();
		$cache = is_array($settings['cache'] ?? null) ? $settings['cache'] : [];
		$optimization = is_array($settings['optimization'] ?? null) ? $settings['optimization'] : [];
		$media = is_array($settings['media'] ?? null) ? $settings['media'] : [];
		$preload = is_array($settings['preload'] ?? null) ? $settings['preload'] : [];
		$woocommerce_settings = is_array($settings['woocommerce'] ?? null) ? $settings['woocommerce'] : [];
		$cdn = is_array($settings['cdn'] ?? null) ? $settings['cdn'] : [];
		$cache_risk_items = RiskRegistry::items('cache', $cache);
		$optimization_items = RiskRegistry::items('optimization', $optimization);
		$media_items = RiskRegistry::items('media', $media);
		$preload_risk_items = RiskRegistry::items('preload', $preload);
		$woocommerce_risk_items = RiskRegistry::items('woocommerce', $woocommerce_settings);
		$cdn_risk_items = RiskRegistry::items('cdn', $cdn);
		$advanced_risk_items = RiskRegistry::items('advanced', $cache);

		$profile = (new ProfileEngine())->detect();
		$selected_profile = $this->selected_profile_id($profile['id']);
		$environment = (new EnvironmentScanner())->scan();
		$health_checks = (new HealthCheck())->checks();
		$conflicts = (new ConflictDetector())->detect();
		$dropin = (new AdvancedCacheInstaller())->status();
		$wizard_completed = (bool) get_option(self::COMPLETED_OPTION, false);
		$wizard_steps = $this->wizard_steps($profile);
		$current_step = $this->current_step;
		$current_step_index = $this->step_index($current_step);
		$previous_step = $this->previous_step($current_step);
		$next_step = $this->next_step($current_step);
		$profile_options = $this->profile_options();
		$safe_settings = $this->safe_settings_preview($selected_profile, $environment);
		$advanced_text = $this->advanced_text($cache);

		require WPXCACHE_PATH . 'templates/admin/setup-wizard.php';
	}

	private function handle_action(): ?array {
		$action = filter_input(INPUT_POST, 'wpxcache_action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		if (! is_string($action) || '' === $action) {
			return null;
		}

		if (! Nonce::verify_request()) {
			return [
				'type'    => 'error',
				'message' => __('Security check failed. Please refresh the page and try again.', 'wpxcache'),
			];
		}

		if ('setup_save_step' === $action) {
			$step = $this->posted_step('wpxcache_current_step', $this->current_step);
			$target = $this->posted_step('wpxcache_next_step', $this->next_step($step));
			$this->save_step($step);
			$this->move_to_step($target);
			(new Logger())->info('Setup wizard step saved.', ['step' => $step, 'next' => $target]);

			return [
				'type'    => 'success',
				'message' => __('Settings saved. Continue with the next setup step.', 'wpxcache'),
			];
		}

		if ('setup_skip_step' === $action) {
			$step = $this->posted_step('wpxcache_current_step', $this->current_step);
			$target = $this->posted_step('wpxcache_next_step', $this->next_step($step));
			$this->move_to_step($target);
			(new Logger())->info('Setup wizard step skipped.', ['step' => $step, 'next' => $target]);

			return [
				'type'    => 'warning',
				'message' => __('Step skipped. No setting was changed for that step.', 'wpxcache'),
			];
		}

		if ('setup_skip_wizard' === $action) {
			update_option(self::COMPLETED_OPTION, true, false);
			update_option(self::CURRENT_STEP_OPTION, 'finish', false);
			(new Logger())->info('Setup wizard skipped by user.');
			wp_safe_redirect(admin_url('admin.php?page=wpxcache'));
			exit;
		}

		if ('setup_apply_safe_settings' === $action) {
			$profile_id = $this->selected_profile_id((new ProfileEngine())->detect()['id']);
			$result = (new SafeSettingsApplier())->apply($profile_id);
			(new Logger())->info('Setup wizard applied safe settings.', ['profile' => $profile_id]);

			return $this->notice_from_result($result);
		}

		if ('setup_prepare_directories' === $action) {
			$this->move_to_step('finish');

			return $this->prepare_cache_directories();
		}

		if ('setup_install_dropin' === $action) {
			$this->move_to_step('finish');
			$result = (new AdvancedCacheInstaller())->install();

			return $this->notice_from_result($result);
		}

		if ('setup_mark_complete' === $action) {
			update_option(self::COMPLETED_OPTION, true, false);
			update_option(self::CURRENT_STEP_OPTION, 'finish', false);
			(new Logger())->info('Setup wizard marked as completed.');

			return [
				'type'    => 'success',
				'message' => __('Setup wizard completed. You can revisit it from the WP XCache menu.', 'wpxcache'),
			];
		}

		return [
			'type'    => 'error',
			'message' => __('Unknown setup wizard action.', 'wpxcache'),
		];
	}

	private function save_step(string $step): void {
		$settings = Config::settings();

		if ('analysis' === $step) {
			update_option(self::PROFILE_OPTION, $this->posted_profile_choice(), false);
			return;
		}

		if ('cache' === $step) {
			$settings['cache']['enabled'] = $this->posted_checkbox('cache_enabled');
			$settings['cache']['ttl'] = $this->posted_int('cache_ttl', 3600, 60, WEEK_IN_SECONDS);
			$settings['cache']['separate_mobile_cache'] = $this->posted_checkbox('separate_mobile_cache');
			$settings['cache']['cache_logged_in_users'] = $this->posted_checkbox('cache_logged_in_users');
			$settings['cache']['cache_404'] = $this->posted_checkbox('cache_404');
			$settings['cache']['cache_search'] = $this->posted_checkbox('cache_search');
			$settings['cache']['cache_feeds'] = $this->posted_checkbox('cache_feeds');
			$settings['cache']['cache_rest_api'] = $this->posted_checkbox('cache_rest_api');
			$settings['cache']['purge_home_on_update'] = $this->posted_checkbox('purge_home_on_update');
			$settings['cache']['purge_archives_on_update'] = $this->posted_checkbox('purge_archives_on_update');
		}

		if ('optimization' === $step) {
			$settings['optimization']['safe_mode'] = $this->posted_checkbox('safe_mode');
			$settings['optimization']['minify_html'] = $this->posted_checkbox('minify_html');
			$settings['optimization']['minify_css'] = $this->posted_checkbox('minify_css');
			$settings['optimization']['combine_css'] = $this->posted_checkbox('combine_css');
			$settings['optimization']['defer_css'] = $this->posted_checkbox('defer_css');
			$settings['optimization']['minify_js'] = $this->posted_checkbox('minify_js');
			$settings['optimization']['defer_js'] = $this->posted_checkbox('defer_js');
			$settings['optimization']['delay_js'] = $this->posted_checkbox('delay_js');
			$settings['optimization']['remove_generator'] = $this->posted_checkbox('remove_generator');
			$settings['optimization']['exclude_css'] = $this->posted_lines('exclude_css');
			$settings['optimization']['exclude_js'] = $this->posted_lines('exclude_js');
		}

		if ('media' === $step) {
			$settings['media']['lazy_load_images'] = $this->posted_checkbox('lazy_load_images');
			$settings['media']['lazy_load_iframes'] = $this->posted_checkbox('lazy_load_iframes');
			$settings['media']['youtube_placeholder'] = $this->posted_checkbox('youtube_placeholder');
			$settings['media']['disable_emoji'] = $this->posted_checkbox('disable_emoji');
			$settings['media']['disable_embeds'] = $this->posted_checkbox('disable_embeds');
		}

		if ('preload' === $step) {
			$settings['preload']['enabled'] = $this->posted_checkbox('preload_enabled');
			$settings['preload']['sitemap_url'] = esc_url_raw($this->posted_string('sitemap_url'));
			$settings['preload']['preload_homepage'] = $this->posted_checkbox('preload_homepage');
			$settings['preload']['preload_posts'] = $this->posted_checkbox('preload_posts');
			$settings['preload']['preload_pages'] = $this->posted_checkbox('preload_pages');
			$settings['preload']['preload_products'] = $this->posted_checkbox('preload_products');
			$settings['preload']['batch_size'] = $this->posted_int('batch_size', 3, 1, 10);
			$settings['preload']['delay'] = $this->posted_int('delay', 10, 1, 120);
			$settings['preload']['auto_after_purge'] = $this->posted_checkbox('auto_after_purge');
		}

		if ('woocommerce' === $step) {
			$settings['woocommerce']['safe_mode'] = $this->posted_checkbox('woocommerce_safe_mode');
			$settings['woocommerce']['product_cache_ttl'] = $this->posted_int('product_cache_ttl', 3600, 60, WEEK_IN_SECONDS);
			$settings['woocommerce']['shop_archive_cache_ttl'] = $this->posted_int('shop_archive_cache_ttl', 3600, 60, WEEK_IN_SECONDS);
			$settings['woocommerce']['stock_update_purge'] = $this->posted_checkbox('stock_update_purge');
			$settings['woocommerce']['price_update_purge'] = $this->posted_checkbox('price_update_purge');
			$settings['woocommerce']['cart_fragment_safe_mode'] = $this->posted_checkbox('cart_fragment_safe_mode');
		}

		if ('cdn' === $step) {
			$settings['cdn']['enabled'] = $this->posted_checkbox('cdn_enabled');
			$settings['cdn']['base_url'] = esc_url_raw($this->posted_string('cdn_base_url'));
			$settings['cdn']['included_file_types'] = $this->posted_csv('cdn_included_file_types');
			$settings['cdn']['excluded_paths'] = $this->posted_lines('cdn_excluded_paths');
			$settings['cdn']['cloudflare_enabled'] = $this->posted_checkbox('cloudflare_enabled');
			$settings['cdn']['cloudflare_api_token'] = sanitize_text_field($this->posted_string('cloudflare_api_token'));
			$settings['cdn']['cloudflare_zone_id'] = sanitize_text_field($this->posted_string('cloudflare_zone_id'));
			$settings['cdn']['purge_cloudflare_on_purge'] = $this->posted_checkbox('purge_cloudflare_on_purge');
		}

		if ('advanced' === $step) {
			$settings['cache']['never_cache_urls'] = $this->posted_lines('never_cache_urls');
			$settings['cache']['never_cache_cookies'] = $this->posted_lines('never_cache_cookies');
			$settings['cache']['never_cache_user_agents'] = $this->posted_lines('never_cache_user_agents');
			$settings['cache']['query_string_whitelist'] = array_map('sanitize_key', $this->posted_lines('query_string_whitelist'));
			$settings['cache']['custom_ttl'] = $this->posted_custom_ttl('custom_ttl');
		}

		update_option(Config::OPTION_NAME, $settings, false);
		(new AdvancedCacheInstaller())->write_config();
	}

	/**
	 * @param array{id: string, label: string, confidence: int, signals: array<int, string>} $profile
	 * @return array<string, array{number: int, title: string, summary: string, state: string}>
	 */
	private function wizard_steps(array $profile): array {
		$definitions = [
			'analysis'     => [
				'title'   => __('Site analysis', 'wpxcache'),
				'summary' => sprintf(
					/* translators: 1: profile label, 2: confidence percent */
					__('Recommended: %1$s (%2$d%%).', 'wpxcache'),
					$profile['label'],
					absint($profile['confidence'])
				),
			],
			'cache'        => [
				'title'   => __('Page cache', 'wpxcache'),
				'summary' => __('Anonymous page cache and purge behavior.', 'wpxcache'),
			],
			'optimization' => [
				'title'   => __('File optimization', 'wpxcache'),
				'summary' => __('HTML, CSS and JS settings with risk labels.', 'wpxcache'),
			],
			'media'        => [
				'title'   => __('Media', 'wpxcache'),
				'summary' => __('Lazy load, embeds and lightweight media options.', 'wpxcache'),
			],
			'preload'      => [
				'title'   => __('Preload', 'wpxcache'),
				'summary' => __('Warm cache carefully with small batches.', 'wpxcache'),
			],
			'woocommerce'  => [
				'title'   => __('WooCommerce', 'wpxcache'),
				'summary' => __('Protect cart, checkout, account and sessions.', 'wpxcache'),
			],
			'cdn'          => [
				'title'   => __('CDN', 'wpxcache'),
				'summary' => __('Optional static asset CDN and Cloudflare settings.', 'wpxcache'),
			],
			'advanced'     => [
				'title'   => __('Advanced rules', 'wpxcache'),
				'summary' => __('URL, cookie, user-agent and query rules.', 'wpxcache'),
			],
			'finish'       => [
				'title'   => __('Finish', 'wpxcache'),
				'summary' => __('Prepare files, apply safe settings or complete setup.', 'wpxcache'),
			],
		];

		$current_index = $this->step_index($this->current_step);
		$steps = [];

		foreach (self::STEP_ORDER as $index => $step) {
			$state = 'pending';

			if ($index < $current_index) {
				$state = 'done';
			} elseif ($index === $current_index) {
				$state = 'active';
			}

			$steps[$step] = [
				'number'  => $index + 1,
				'title'   => $definitions[$step]['title'],
				'summary' => $definitions[$step]['summary'],
				'state'   => $state,
			];
		}

		return $steps;
	}

	/**
	 * @return array<string, string>
	 */
	private function profile_options(): array {
		return [
			'blog'        => __('Blog', 'wpxcache'),
			'business'    => __('Business site', 'wpxcache'),
			'woocommerce' => __('WooCommerce store', 'wpxcache'),
			'news'        => __('News site', 'wpxcache'),
			'membership'  => __('Membership site', 'wpxcache'),
			'agency'      => __('Agency/client site', 'wpxcache'),
			'developer'   => __('Developer/manual mode', 'wpxcache'),
		];
	}

	/**
	 * @param array<string, mixed> $environment
	 * @return array<int, array{label: string, status: string, detail: string}>
	 */
	private function safe_settings_preview(string $profile_id, array $environment): array {
		$is_woocommerce = 'woocommerce' === $profile_id || ! empty($environment['woocommerce']);

		return [
			[
				'label'  => __('Page cache', 'wpxcache'),
				'status' => 'Safe',
				'detail' => __('Enabled only for anonymous safe GET requests.', 'wpxcache'),
			],
			[
				'label'  => __('Logged-in users', 'wpxcache'),
				'status' => 'Safe',
				'detail' => __('Never cached by default.', 'wpxcache'),
			],
			[
				'label'  => __('WooCommerce Safe Mode', 'wpxcache'),
				'status' => 'Safe',
				'detail' => $is_woocommerce ? __('Cart, checkout, account and session cookies stay protected.', 'wpxcache') : __('Kept enabled even when WooCommerce is not active.', 'wpxcache'),
			],
			[
				'label'  => __('CSS/JS risky optimization', 'wpxcache'),
				'status' => 'Risky',
				'detail' => __('Combine, delay and CSS runtime rewriting are not enabled automatically.', 'wpxcache'),
			],
			[
				'label'  => __('Preload', 'wpxcache'),
				'status' => 'Safe',
				'detail' => __('Homepage preload settings are prepared with small batches.', 'wpxcache'),
			],
		];
	}

	/**
	 * @param array<string, mixed> $cache
	 * @return array<string, string>
	 */
	private function advanced_text(array $cache): array {
		$custom_ttl_lines = [];

		if (is_array($cache['custom_ttl'] ?? null)) {
			foreach ($cache['custom_ttl'] as $rule) {
				if (is_array($rule) && isset($rule['pattern'], $rule['ttl'])) {
					$custom_ttl_lines[] = (string) $rule['pattern'] . '|' . (string) $rule['ttl'];
				}
			}
		}

		return [
			'never_cache_urls'        => implode("\n", is_array($cache['never_cache_urls'] ?? null) ? $cache['never_cache_urls'] : []),
			'never_cache_cookies'     => implode("\n", is_array($cache['never_cache_cookies'] ?? null) ? $cache['never_cache_cookies'] : []),
			'never_cache_user_agents' => implode("\n", is_array($cache['never_cache_user_agents'] ?? null) ? $cache['never_cache_user_agents'] : []),
			'query_string_whitelist'  => implode("\n", is_array($cache['query_string_whitelist'] ?? null) ? $cache['query_string_whitelist'] : []),
			'custom_ttl'              => implode("\n", $custom_ttl_lines),
		];
	}

	/**
	 * @return array{type: string, message: string}
	 */
	private function prepare_cache_directories(): array {
		$file_guard = new FileGuard();
		$cache_ready = $file_guard->ensure_directory(WPXCACHE_CACHE_DIR);
		$log_ready = $file_guard->ensure_directory(WPXCACHE_LOG_DIR);

		if ($cache_ready) {
			$this->write_index(WPXCACHE_CACHE_DIR);
		}

		if ($log_ready) {
			$this->write_index(WPXCACHE_LOG_DIR);
			$file_guard->write_cache_file(trailingslashit(WPXCACHE_LOG_DIR) . '.htaccess', "Deny from all\n");
		}

		$success = $cache_ready && $log_ready;
		(new Logger())->info('Setup wizard prepared cache directories.', ['success' => $success]);

		return [
			'type'    => $success ? 'success' : 'error',
			'message' => $success ? __('Cache and log directories are ready.', 'wpxcache') : __('Cache or log directory could not be prepared. Check file permissions.', 'wpxcache'),
		];
	}

	private function write_index(string $directory): void {
		$file = trailingslashit($directory) . 'index.php';

		if (! is_file($file)) {
			(new FileGuard())->write_cache_file($file, "<?php\n// Silence is golden.\n");
		}
	}

	private function requested_step(): string {
		$requested = filter_input(INPUT_GET, 'wpxcache_step', FILTER_UNSAFE_RAW);
		$requested = is_string($requested) ? sanitize_key(wp_unslash($requested)) : '';

		if ($this->is_valid_step($requested)) {
			return $requested;
		}

		$saved = get_option(self::CURRENT_STEP_OPTION, 'analysis');

		return is_string($saved) && $this->is_valid_step($saved) ? $saved : 'analysis';
	}

	private function selected_profile_id(string $fallback): string {
		$selected = get_option(self::PROFILE_OPTION, $fallback);

		return is_string($selected) && array_key_exists($selected, $this->profile_options()) ? $selected : $fallback;
	}

	private function posted_profile_choice(): string {
		$value = filter_input(INPUT_POST, 'profile_choice', FILTER_UNSAFE_RAW);
		$value = is_string($value) ? sanitize_key(wp_unslash($value)) : '';

		return array_key_exists($value, $this->profile_options()) ? $value : 'business';
	}

	private function posted_step(string $key, string $fallback): string {
		$value = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW);
		$value = is_string($value) ? sanitize_key(wp_unslash($value)) : '';

		return $this->is_valid_step($value) ? $value : $fallback;
	}

	private function posted_checkbox(string $key): bool {
		$value = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW);

		if (! is_string($value)) {
			return false;
		}

		return (bool) rest_sanitize_boolean(wp_unslash($value));
	}

	private function posted_int(string $key, int $default, int $min, int $max): int {
		$value = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW);
		$value = is_string($value) ? absint(wp_unslash($value)) : $default;

		return max($min, min($max, $value));
	}

	private function posted_string(string $key): string {
		$value = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW);

		return is_string($value) ? sanitize_text_field(wp_unslash($value)) : '';
	}

	/**
	 * @return array<int, string>
	 */
	private function posted_csv(string $key): array {
		$value = $this->posted_string($key);
		$items = array_map('trim', explode(',', $value));

		return array_values(array_unique(array_filter(array_map('sanitize_key', $items))));
	}

	/**
	 * @return array<int, string>
	 */
	private function posted_lines(string $key): array {
		$value = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW);

		if (! is_string($value)) {
			return [];
		}

		$value = sanitize_textarea_field(wp_unslash($value));
		$lines = preg_split('/\R/', $value) ?: [];
		$lines = array_map('trim', $lines);
		$lines = array_filter($lines, static fn (string $line): bool => '' !== $line);

		return array_values(array_unique(array_map('sanitize_text_field', $lines)));
	}

	/**
	 * @return array<int, array{pattern: string, ttl: int}>
	 */
	private function posted_custom_ttl(string $key): array {
		$rules = [];

		foreach ($this->posted_lines($key) as $line) {
			$parts = array_map('trim', explode('|', $line, 2));

			if (2 !== count($parts)) {
				continue;
			}

			$ttl = absint($parts[1]);

			if ($ttl <= 0) {
				continue;
			}

			$rules[] = [
				'pattern' => sanitize_text_field('/' . trim($parts[0], '/')),
				'ttl'     => min($ttl, WEEK_IN_SECONDS),
			];
		}

		return $rules;
	}

	private function is_valid_step(string $step): bool {
		return in_array($step, self::STEP_ORDER, true);
	}

	private function move_to_step(string $step): void {
		if (! $this->is_valid_step($step)) {
			$step = 'analysis';
		}

		$this->current_step = $step;
		update_option(self::CURRENT_STEP_OPTION, $step, false);
	}

	private function step_index(string $step): int {
		$index = array_search($step, self::STEP_ORDER, true);

		return is_int($index) ? $index : 0;
	}

	private function next_step(string $step): string {
		$index = min(count(self::STEP_ORDER) - 1, $this->step_index($step) + 1);

		return self::STEP_ORDER[$index];
	}

	private function previous_step(string $step): string {
		$index = max(0, $this->step_index($step) - 1);

		return self::STEP_ORDER[$index];
	}

	private function wizard_url(string $step): string {
		return admin_url('admin.php?page=wpxcache-setup&wpxcache_step=' . rawurlencode($step));
	}

	/**
	 * @param array{success: bool, message: string} $result
	 * @return array{type: string, message: string}
	 */
	private function notice_from_result(array $result): array {
		return [
			'type'    => $result['success'] ? 'success' : 'error',
			'message' => $result['message'],
		];
	}
}
