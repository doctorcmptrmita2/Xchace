<?php
/**
 * Smart purge hook coordinator.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Purge;

use WPXCache\Cache\CachePurger;
use WPXCache\Core\Config;
use WPXCache\Core\ServiceProvider;
use WPXCache\Logger\Logger;

if (! defined('ABSPATH')) {
	exit;
}

final class SmartPurge implements ServiceProvider {
	/**
	 * @param array<string, mixed>|null $settings
	 */
	public function __construct(private ?array $settings = null, private ?CachePurger $purger = null, private ?Logger $logger = null) {
		$this->settings = $settings ?: Config::settings();
		$this->purger = $purger ?: new CachePurger();
		$this->logger = $logger ?: new Logger();
	}

	public function register(): void {
		add_action('save_post', [$this, 'purge_post'], 20, 3);
		add_action('deleted_post', [$this, 'purge_deleted_post'], 20, 2);
		add_action('transition_post_status', [$this, 'purge_on_status_transition'], 20, 3);
		add_action('comment_post', [$this, 'purge_comment_post'], 20, 3);
		add_action('transition_comment_status', [$this, 'purge_comment_transition'], 20, 3);
		add_action('edited_terms', [$this, 'purge_term'], 20, 2);
		add_action('created_term', [$this, 'purge_term'], 20, 2);
		add_action('delete_term', [$this, 'purge_term'], 20, 2);
		add_action('wp_update_nav_menu', [$this, 'purge_all_for_global_change']);
		add_action('update_option_sidebars_widgets', [$this, 'purge_all_for_global_change']);
		add_action('switch_theme', [$this, 'purge_all_for_global_change']);
		add_action('activated_plugin', [$this, 'purge_all_for_global_change']);
		add_action('deactivated_plugin', [$this, 'purge_all_for_global_change']);
		add_action('customize_save_after', [$this, 'purge_all_for_global_change']);
		add_action('permalink_structure_changed', [$this, 'purge_all_for_global_change']);
	}

	public function purge_post(int $post_id, \WP_Post $post, bool $update): void {
		if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || 'publish' !== $post->post_status) {
			return;
		}

		$this->logger->info('Smart purge requested for post save.', ['post_id' => $post_id, 'update' => $update]);
		$this->purger->purge_post($post_id);
	}

	public function purge_deleted_post(int $post_id, \WP_Post $post): void {
		$this->logger->info('Smart purge requested for deleted post.', ['post_id' => $post_id]);
		$this->purger->purge_urls($this->global_urls());
	}

	public function purge_on_status_transition(string $new_status, string $old_status, \WP_Post $post): void {
		if ($new_status === $old_status || ('publish' !== $new_status && 'publish' !== $old_status)) {
			return;
		}

		$this->logger->info('Smart purge requested for post status transition.', ['post_id' => $post->ID, 'new' => $new_status, 'old' => $old_status]);
		$this->purger->purge_post((int) $post->ID);
	}

	public function purge_comment_post(int $comment_id, int|string $approved, array $commentdata): void {
		if (1 !== (int) $approved || empty($commentdata['comment_post_ID'])) {
			return;
		}

		$this->purger->purge_post((int) $commentdata['comment_post_ID']);
	}

	public function purge_comment_transition(string $new_status, string $old_status, \WP_Comment $comment): void {
		if ($new_status === $old_status || ('approved' !== $new_status && 'approved' !== $old_status)) {
			return;
		}

		$this->purger->purge_post((int) $comment->comment_post_ID);
	}

	public function purge_term(int $term_id, mixed $taxonomy = ''): void {
		$taxonomy = is_string($taxonomy) ? $taxonomy : '';
		$url = get_term_link($term_id, $taxonomy);
		$urls = $this->global_urls();

		if (is_string($url)) {
			$urls[] = $url;
		}

		$this->logger->info('Smart purge requested for term change.', ['term_id' => $term_id, 'taxonomy' => $taxonomy]);
		$this->purger->purge_urls($urls);
	}

	public function purge_all_for_global_change(): void {
		$this->logger->info('Full purge requested for global site change.');
		$this->purger->purge_all();
	}

	/**
	 * @return array<int, string>
	 */
	private function global_urls(): array {
		$urls = [home_url('/')];

		if (function_exists('get_post_type_archive_link')) {
			$post_archive = get_post_type_archive_link('post');

			if (is_string($post_archive)) {
				$urls[] = $post_archive;
			}
		}

		return array_values(array_unique(array_filter($urls)));
	}
}
