<?php
/**
 * Database template.
 *
 * @package WPXCache
 *
 * @var array<string, int> $counts
 * @var array{type: string, message: string}|null $notice
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$items = [
	'revisions'          => [
		'label' => __('Post revisions', 'wpxcache'),
		'help'  => __('Eski yazı revizyonlarını kalıcı olarak siler.', 'wpxcache'),
		'risk'  => 'Medium',
	],
	'auto_drafts'        => [
		'label' => __('Auto drafts', 'wpxcache'),
		'help'  => __('Otomatik taslak kayıtlarını temizler.', 'wpxcache'),
		'risk'  => 'Safe',
	],
	'trashed_posts'      => [
		'label' => __('Trashed posts', 'wpxcache'),
		'help'  => __('Çöpteki yazı ve sayfaları kalıcı olarak siler.', 'wpxcache'),
		'risk'  => 'Medium',
	],
	'spam_comments'      => [
		'label' => __('Spam comments', 'wpxcache'),
		'help'  => __('Spam olarak işaretlenmiş yorumları kalıcı olarak siler.', 'wpxcache'),
		'risk'  => 'Safe',
	],
	'expired_transients' => [
		'label' => __('Expired transients', 'wpxcache'),
		'help'  => __('Süresi dolmuş geçici option kayıtlarını temizler.', 'wpxcache'),
		'risk'  => 'Safe',
	],
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
			<h1><?php echo esc_html__('Database', 'wpxcache'); ?></h1>
			<p><?php echo esc_html__('Önce sayıları gör, sonra yalnızca seçtiğin güvenli temizliği çalıştır.', 'wpxcache'); ?></p>
		</div>
		<span class="wpxcache-version"><?php echo esc_html(WPXCACHE_VERSION); ?></span>
	</div>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Preview Cleanup Counts', 'wpxcache'); ?></h2>
		<div class="wpxcache-health-list">
			<?php foreach ($items as $key => $item) : ?>
				<article class="wpxcache-health-item">
					<div class="wpxcache-health-heading">
						<span class="wpxcache-risk is-<?php echo esc_attr(strtolower($item['risk'])); ?>"><?php echo esc_html($item['risk']); ?></span>
						<strong><?php echo esc_html($item['label']); ?></strong>
					</div>
					<p><?php echo esc_html($item['help']); ?></p>
					<strong><?php echo esc_html(number_format_i18n((int) ($counts[$key] ?? 0))); ?></strong>
					<form method="post" class="wpxcache-actions">
						<?php \WPXCache\Security\Nonce::field(); ?>
						<input type="hidden" name="cleanup_target" value="<?php echo esc_attr($key); ?>">
						<button class="button" type="submit" name="wpxcache_action" value="clean_database" <?php disabled(empty($counts[$key])); ?>>
							<?php echo esc_html__('Clean selected', 'wpxcache'); ?>
						</button>
					</form>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="wpxcache-panel wpxcache-panel-wide">
		<h2><?php echo esc_html__('Safety Notes', 'wpxcache'); ?></h2>
		<ul class="wpxcache-list">
			<li><?php echo esc_html__('Her işlem en fazla 500 kayıtlık batch ile çalışır.', 'wpxcache'); ?></li>
			<li><?php echo esc_html__('Orphan meta ve tablo optimize gibi daha riskli işlemler bu sürümde otomatik çalıştırılmaz.', 'wpxcache'); ?></li>
			<li><?php echo esc_html__('Temizlikten önce veritabanı yedeği almak her zaman önerilir.', 'wpxcache'); ?></li>
		</ul>
	</section>
</div>
