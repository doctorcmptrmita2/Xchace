<?php
/**
 * Health check registry.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Diagnostics;

use WPXCache\Cache\AdvancedCacheInstaller;
use WPXCache\Compatibility\WooCommerce;
use WPXCache\Profile\ProfileEngine;

if (! defined('ABSPATH')) {
	exit;
}

final class HealthCheck {
	/**
	 * @return array<int, array{id: string, label: string, status: string, problem: string, why: string, fix: string, auto_fix: bool, action: string, action_label: string}>
	 */
	public function checks(): array {
		$env = (new EnvironmentScanner())->scan();
		$dropin = (new AdvancedCacheInstaller())->status();
		$woocommerce = new WooCommerce();
		$profile = (new ProfileEngine())->detect();
		$checks = [];
		$cache_dir_fixable = ! empty($env['cache_can_create']) || ! empty($env['cache_writable']);
		$log_dir_fixable = ! empty($env['log_can_create']) || ! empty($env['log_writable']);

		$checks[] = $this->item(
			'smart_profile',
			__('Akıllı profil', 'wpxcache'),
			'green',
			sprintf(
				/* translators: 1: profile label, 2: confidence percent */
				__('Önerilen profil: %1$s (%2$d%% güven).', 'wpxcache'),
				$profile['label'],
				$profile['confidence']
			),
			__('Profil yalnızca güvenli öneriler için kullanılır; riskli optimizasyonları otomatik açmaz.', 'wpxcache'),
			__('Bu profile uygun korumalı ayarları uygulamak için Smart Optimize kullanabilirsiniz.', 'wpxcache'),
			true,
			'apply_smart_optimize',
			__('Smart Optimize uygula', 'wpxcache')
		);

		$checks[] = $this->item(
			'wp_cache',
			__('WP_CACHE sabiti', 'wpxcache'),
			$env['wp_cache'] ? 'green' : 'yellow',
			$env['wp_cache'] ? __('WP_CACHE aktif.', 'wpxcache') : __('WP_CACHE henüz aktif değil.', 'wpxcache'),
			__('advanced-cache.php drop-in dosyası WordPress yüklenmeden önce yalnızca WP_CACHE true ise çalışır.', 'wpxcache'),
			__('wp-config.php dosyasında değişiklik yapmadan önce yedek alın; ardından WP_CACHE değerini true yapın.', 'wpxcache'),
			false
		);

		$checks[] = $this->item(
			'dropin',
			__('advanced-cache.php sahipliği', 'wpxcache'),
			! $dropin['exists'] ? 'yellow' : ($dropin['owned'] ? 'green' : 'red'),
			$this->dropin_problem($dropin),
			__('Erken cache servisini aynı anda yalnızca bir full-page cache drop-in kontrol etmelidir.', 'wpxcache'),
			__('Dosya yoksa WP XCache drop-in dosyasını kurun. Başka bir eklentiye aitse önce çakışan cache eklentisini devre dışı bırakın.', 'wpxcache'),
			! $dropin['exists'] || $dropin['owned'],
			! $dropin['exists'] || $dropin['owned'] ? 'install_dropin' : '',
			! $dropin['exists'] || $dropin['owned'] ? __('Drop-in kur / yenile', 'wpxcache') : ''
		);

		$checks[] = $this->item(
			'cache_dir',
			__('Cache dizini yazılabilirliği', 'wpxcache'),
			$env['cache_writable'] ? 'green' : ($cache_dir_fixable ? 'yellow' : 'red'),
			$env['cache_writable'] ? __('Cache dizini hazır ve yazılabilir.', 'wpxcache') : __('Cache dizini eksik veya yazılamıyor.', 'wpxcache'),
			__('WP XCache statik HTML ve optimize dosyaları kaydedebilmek için bu dizine yazabilmelidir.', 'wpxcache'),
			__('Dizin oluşturulabiliyorsa otomatik hazırlayın; değilse wp-content/cache/wpxcache izinlerini kontrol edin.', 'wpxcache'),
			$cache_dir_fixable,
			$cache_dir_fixable ? 'prepare_cache_directories' : '',
			$cache_dir_fixable ? __('Cache dizinlerini hazırla', 'wpxcache') : ''
		);

		$checks[] = $this->item(
			'log_dir',
			__('Log dizini yazılabilirliği', 'wpxcache'),
			$env['log_writable'] ? 'green' : ($log_dir_fixable ? 'yellow' : 'red'),
			$env['log_writable'] ? __('Log dizini hazır ve yazılabilir.', 'wpxcache') : __('Log dizini eksik veya yazılamıyor.', 'wpxcache'),
			__('Tanılama, purge geçmişi ve hata kayıtları için özel bir log dizini gerekir.', 'wpxcache'),
			__('Dizin oluşturulabiliyorsa otomatik hazırlayın; değilse wp-content/cache/wpxcache/logs izinlerini kontrol edin.', 'wpxcache'),
			$log_dir_fixable,
			$log_dir_fixable ? 'prepare_cache_directories' : '',
			$log_dir_fixable ? __('Cache dizinlerini hazırla', 'wpxcache') : ''
		);

		$checks[] = $this->item(
			'gzip',
			__('Gzip desteği', 'wpxcache'),
			$env['gzip'] ? 'green' : 'yellow',
			$env['gzip'] ? __('PHP gzip desteği aktif.', 'wpxcache') : __('PHP gzip desteği kullanılamıyor.', 'wpxcache'),
			__('Gzip olmadan HTML cache çalışır; ancak sıkıştırılmış kopyalar üretilemez.', 'wpxcache'),
			__('Hosting destekliyorsa PHP zlib eklentisini aktif edin.', 'wpxcache'),
			false
		);

		$checks[] = $this->item(
			'permalinks',
			__('Kalıcı bağlantılar', 'wpxcache'),
			$env['permalink_enabled'] ? 'green' : 'yellow',
			$env['permalink_enabled'] ? __('Güzel kalıcı bağlantılar aktif.', 'wpxcache') : __('Güzel kalıcı bağlantılar kapalı.', 'wpxcache'),
			__('Sorgu parametreli plain URL yapıları güvenli ve öngörülebilir cache için daha zordur.', 'wpxcache'),
			__('WordPress Kalıcı Bağlantılar ekranından yazı adı gibi temiz bir yapı seçin.', 'wpxcache'),
			false
		);

		$checks[] = $this->item(
			'disk_space',
			__('Disk alanı', 'wpxcache'),
			$this->disk_status((int) $env['disk_free_bytes']),
			$this->disk_problem((int) $env['disk_free_bytes']),
			__('Page cache ve optimize asset dosyaları disk alanı kullanır; düşük alan yarım yazmalara neden olabilir.', 'wpxcache'),
			__('Disk alanı düşükse cache ömrünü kısaltın veya sunucuda yer açın.', 'wpxcache'),
			false
		);

		$checks[] = $this->item(
			'woocommerce',
			__('WooCommerce güvenli istisnaları', 'wpxcache'),
			$woocommerce->safe_mode_enabled() ? 'green' : 'red',
			$woocommerce->safe_mode_enabled() ? __('WooCommerce Safe Mode aktif.', 'wpxcache') : __('WooCommerce Safe Mode kapalı.', 'wpxcache'),
			__('Sepet, ödeme, hesap ve oturum cookie verileri kullanıcıya özel bilgi içerir.', 'wpxcache'),
			__('Mağaza bir geliştirici tarafından denetlenmediyse WooCommerce Safe Mode açık kalmalıdır.', 'wpxcache'),
			false
		);

		return $checks;
	}

	/**
	 * @param array{exists: bool, owned: bool, wp_cache: bool, path: string, config_exists: bool, writable: bool} $dropin
	 */
	private function dropin_problem(array $dropin): string {
		if (empty($dropin['exists'])) {
			return __('advanced-cache.php henüz kurulu değil.', 'wpxcache');
		}

		if (! empty($dropin['owned'])) {
			return __('advanced-cache.php WP XCache tarafından yönetiliyor.', 'wpxcache');
		}

		return __('Mevcut advanced-cache.php başka bir sistem tarafından yönetiliyor.', 'wpxcache');
	}

	private function disk_status(int $disk_free_bytes): string {
		if ($disk_free_bytes < 0) {
			return 'yellow';
		}

		return $disk_free_bytes > 100 * MB_IN_BYTES ? 'green' : 'red';
	}

	private function disk_problem(int $disk_free_bytes): string {
		if ($disk_free_bytes < 0) {
			return __('Kullanılabilir disk alanı okunamadı.', 'wpxcache');
		}

		if ($disk_free_bytes > 100 * MB_IN_BYTES) {
			return sprintf(
				/* translators: %s: available disk size */
				__('Kullanılabilir disk alanı yeterli görünüyor: %s.', 'wpxcache'),
				size_format($disk_free_bytes)
			);
		}

		return sprintf(
			/* translators: %s: available disk size */
			__('Kullanılabilir disk alanı düşük: %s.', 'wpxcache'),
			size_format(max(0, $disk_free_bytes))
		);
	}

	/**
	 * @return array{id: string, label: string, status: string, problem: string, why: string, fix: string, auto_fix: bool, action: string, action_label: string}
	 */
	private function item(string $id, string $label, string $status, string $problem, string $why, string $fix, bool $auto_fix, string $action = '', string $action_label = ''): array {
		return compact('id', 'label', 'status', 'problem', 'why', 'fix', 'auto_fix', 'action', 'action_label');
	}
}
