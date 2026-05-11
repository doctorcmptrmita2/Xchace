<?php
/**
 * Base service provider contract.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Core;

if (! defined('ABSPATH')) {
	exit;
}

interface ServiceProvider {
	public function register(): void;
}
