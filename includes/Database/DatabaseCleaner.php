<?php
/**
 * Conservative database cleanup tools.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Database;

use WPXCache\Logger\Logger;

if (! defined('ABSPATH')) {
	exit;
}

final class DatabaseCleaner {
	private const BATCH_LIMIT = 500;

	public function __construct(private ?Logger $logger = null) {
		$this->logger = $logger ?: new Logger();
	}

	/**
	 * @return array<string, int>
	 */
	public function counts(): array {
		return [
			'revisions'          => $this->count_posts('revision'),
			'auto_drafts'        => $this->count_posts('', 'auto-draft'),
			'trashed_posts'      => $this->count_posts('', 'trash'),
			'spam_comments'      => $this->count_comments('spam'),
			'expired_transients' => $this->count_expired_transients(),
		];
	}

	/**
	 * @return array{success: bool, message: string, cleaned: int}
	 */
	public function clean(string $target): array {
		$allowed = ['revisions', 'auto_drafts', 'trashed_posts', 'spam_comments', 'expired_transients'];

		if (! in_array($target, $allowed, true)) {
			return $this->result(false, __('Unknown database cleanup target.', 'wpxcache'), 0);
		}

		$cleaned = match ($target) {
			'revisions'          => $this->delete_posts('revision'),
			'auto_drafts'        => $this->delete_posts('', 'auto-draft'),
			'trashed_posts'      => $this->delete_posts('', 'trash'),
			'spam_comments'      => $this->delete_comments('spam'),
			'expired_transients' => $this->delete_expired_transients(),
			default              => 0,
		};

		$this->logger->info('Database cleanup completed.', ['target' => $target, 'cleaned' => $cleaned]);

		return $this->result(true, __('Database cleanup completed.', 'wpxcache'), $cleaned);
	}

	private function count_posts(string $post_type = '', string $post_status = ''): int {
		global $wpdb;

		if ('' !== $post_type) {
			return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = %s", $post_type));
		}

		return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = %s", $post_status));
	}

	private function count_comments(string $status): int {
		global $wpdb;

		return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(comment_ID) FROM {$wpdb->comments} WHERE comment_approved = %s", $status));
	}

	private function count_expired_transients(): int {
		global $wpdb;

		$prefix = $wpdb->esc_like('_transient_timeout_') . '%';

		return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(option_id) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d", $prefix, time()));
	}

	private function delete_posts(string $post_type = '', string $post_status = ''): int {
		global $wpdb;

		if ('' !== $post_type) {
			$ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s LIMIT %d", $post_type, self::BATCH_LIMIT));
		} else {
			$ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_status = %s LIMIT %d", $post_status, self::BATCH_LIMIT));
		}

		if (! is_array($ids)) {
			return 0;
		}

		$cleaned = 0;

		foreach ($ids as $id) {
			if (wp_delete_post((int) $id, true)) {
				++$cleaned;
			}
		}

		return $cleaned;
	}

	private function delete_comments(string $status): int {
		global $wpdb;

		$ids = $wpdb->get_col($wpdb->prepare("SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved = %s LIMIT %d", $status, self::BATCH_LIMIT));

		if (! is_array($ids)) {
			return 0;
		}

		$cleaned = 0;

		foreach ($ids as $id) {
			if (wp_delete_comment((int) $id, true)) {
				++$cleaned;
			}
		}

		return $cleaned;
	}

	private function delete_expired_transients(): int {
		global $wpdb;

		$prefix = $wpdb->esc_like('_transient_timeout_') . '%';
		$timeouts = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d LIMIT %d", $prefix, time(), self::BATCH_LIMIT));

		if (! is_array($timeouts)) {
			return 0;
		}

		$cleaned = 0;

		foreach ($timeouts as $timeout_option) {
			$timeout_option = (string) $timeout_option;
			$transient = substr($timeout_option, strlen('_transient_timeout_'));

			if ('' === $transient) {
				continue;
			}

			if (delete_transient($transient)) {
				++$cleaned;
			} else {
				$wpdb->delete($wpdb->options, ['option_name' => $timeout_option], ['%s']);
				$wpdb->delete($wpdb->options, ['option_name' => '_transient_' . $transient], ['%s']);
				++$cleaned;
			}
		}

		return $cleaned;
	}

	/**
	 * @return array{success: bool, message: string, cleaned: int}
	 */
	private function result(bool $success, string $message, int $cleaned): array {
		return [
			'success' => $success,
			'message' => $message,
			'cleaned' => $cleaned,
		];
	}
}
