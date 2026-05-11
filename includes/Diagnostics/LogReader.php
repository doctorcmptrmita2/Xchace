<?php
/**
 * Diagnostics log reader.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Diagnostics;

use WPXCache\Logger\Logger;

if (! defined('ABSPATH')) {
	exit;
}

final class LogReader {
	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function recent(int $limit = 10): array {
		return (new Logger())->recent($limit);
	}
}
