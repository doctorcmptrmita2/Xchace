<?php
/**
 * Advanced rules template.
 *
 * @package WPXCache
 *
 * @var array<string, mixed> $cache
 * @var array{type: string, message: string}|null $notice
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$custom_ttl_lines = [];

if (is_array($cache['custom_ttl'] ?? null)) {
	foreach ($cache['custom_ttl'] as $rule) {
		if (is_array($rule) && isset($rule['pattern'], $rule['ttl'])) {
			$custom_ttl_lines[] = (string) $rule['pattern'] . '|' . (string) $rule['ttl'];
		}
	}
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
			<h1><?php echo esc_html__('Advanced Rules', 'wpxcache'); ?></h1>
			<p><?php echo esc_html__('Cache dışlama ve varyasyon kurallarını dikkatli yönetin.', 'wpxcache'); ?></p>
		</div>
		<span class="wpxcache-version"><?php echo esc_html(WPXCACHE_VERSION); ?></span>
	</div>

	<form method="post">
		<?php \WPXCache\Security\Nonce::field(); ?>
		<div class="wpxcache-grid">
			<section class="wpxcache-panel">
				<h2><?php echo esc_html__('Never Cache URLs', 'wpxcache'); ?></h2>
				<p><?php echo esc_html__('Her satıra bir path yazın. Örnek: /checkout', 'wpxcache'); ?></p>
				<textarea class="large-text code" rows="8" name="never_cache_urls"><?php echo esc_textarea(implode("\n", is_array($cache['never_cache_urls'] ?? null) ? $cache['never_cache_urls'] : [])); ?></textarea>
			</section>

			<section class="wpxcache-panel">
				<h2><?php echo esc_html__('Never Cache Cookies', 'wpxcache'); ?></h2>
				<p><?php echo esc_html__('Cookie prefix listesi. Eşleşen cookie varsa cache bypass edilir.', 'wpxcache'); ?></p>
				<textarea class="large-text code" rows="8" name="never_cache_cookies"><?php echo esc_textarea(implode("\n", is_array($cache['never_cache_cookies'] ?? null) ? $cache['never_cache_cookies'] : [])); ?></textarea>
			</section>

			<section class="wpxcache-panel">
				<h2><?php echo esc_html__('Never Cache User Agents', 'wpxcache'); ?></h2>
				<p><?php echo esc_html__('Her satıra user-agent parçası yazın.', 'wpxcache'); ?></p>
				<textarea class="large-text code" rows="8" name="never_cache_user_agents"><?php echo esc_textarea(implode("\n", is_array($cache['never_cache_user_agents'] ?? null) ? $cache['never_cache_user_agents'] : [])); ?></textarea>
			</section>

			<section class="wpxcache-panel">
				<h2><?php echo esc_html__('Query String Whitelist', 'wpxcache'); ?></h2>
				<p><?php echo esc_html__('Cache key’e dahil edilecek güvenli query parametreleri.', 'wpxcache'); ?></p>
				<textarea class="large-text code" rows="8" name="query_string_whitelist"><?php echo esc_textarea(implode("\n", is_array($cache['query_string_whitelist'] ?? null) ? $cache['query_string_whitelist'] : [])); ?></textarea>
			</section>
		</div>

		<section class="wpxcache-panel wpxcache-panel-wide">
			<h2><?php echo esc_html__('Custom TTL Per Path', 'wpxcache'); ?></h2>
			<p><?php echo esc_html__('Format: /path|seconds. Örnek: /blog|900', 'wpxcache'); ?></p>
			<textarea class="large-text code" rows="8" name="custom_ttl"><?php echo esc_textarea(implode("\n", $custom_ttl_lines)); ?></textarea>
			<div class="wpxcache-actions">
				<button class="button button-primary" type="submit" name="wpxcache_action" value="save_advanced_rules"><?php echo esc_html__('Save advanced rules', 'wpxcache'); ?></button>
			</div>
		</section>
	</form>
</div>
