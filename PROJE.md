# WP XCache Pro Proje Dokumani

## Proje Ozeti

**WP XCache Pro**, WordPress siteleri icin guvenli varsayimlara dayanan, WooCommerce uyumlu, moduler ve profesyonel seviyede bir cache ve performans optimizasyon eklentisidir.

Ana slogan:

> WordPress siteni hizlandir, ama siteni bozma.

Ingilizce slogan:

> One-click safe speed optimization for WordPress.

Bu projenin hedefi yalnizca hizli bir cache sistemi kurmak degil; yanlis cache nedeniyle sepet, odeme, uye alani, formlar, dinamik fiyatlar, kullanici oturumlari ve admin deneyimi bozulmayan bir performans platformu gelistirmektir.

## Urun Felsefesi

WP XCache Pro, "cok ayar = profesyonel eklenti" yaklasimini izlemeyecek. Gercek WordPress kullanicilarinin buyuk kismi cache, minify, preload, object cache, transient cache, database cache, CDN purge ve WebP gibi kavramlari teknik olarak bilmez. Buna ragmen yanlis bir ayar sitenin kritik is akislarini bozabilir.

Bu nedenle urunun temel ilkeleri sunlardir:

- Guvenli varsayilanlar.
- Tek tikla akilli optimizasyon.
- Site tipine gore profil onerisi.
- Risk seviyeli ayarlar.
- WooCommerce, uyelik, form ve odeme akislarini koruma.
- Cache eklentisi ve drop-in cakisma tespiti.
- Ajanslar icin export/import ve tekrar kullanilabilir ayar profilleri.
- Gelistiriciler icin hook/filter, WP-CLI ve diagnostics raporlari.
- Her kritik islemde nonce, capability ve path guvenligi.

## Gzip Support Neden Yok Gorunur?

Health Check ekranindaki **Gzip desteği yok** uyarisi eklentinin bozuk oldugu anlamina gelmez. WP XCache Pro su an PHP tarafinda `gzencode()` fonksiyonunun var olup olmadigini kontrol eder. Bu fonksiyon PHP `zlib` eklentisi aktifse bulunur.

Gzip uyarisinin muhtemel nedenleri:

- Kullanilan WAMP/PHP ortaminda `zlib` eklentisi kapali olabilir.
- Apache'nin kullandigi PHP ile CLI'nin kullandigi PHP farkli olabilir.
- `php.ini` icinde `extension=zlib` aktif olmayabilir.
- Hosting saglayicisi gzip sikistirmayi PHP uzerinden degil web server uzerinden yapiyor olabilir.

Bu durumda WP XCache Pro yine HTML cache uretebilir. Sadece `.gz` sikistirilmis cache kopyalari PHP tarafinda uretilemez. Daha profesyonel bir sonraki adimda Health Check su ayrimi yapmalidir:

- PHP `gzencode()` var mi?
- Apache/Nginx/LiteSpeed seviyesinde gzip aktif mi?
- Brotli destekleniyor mu?
- `.gz` cache dosyasi uretilebilir mi?
- Browser'a `Content-Encoding: gzip` dogru servis edilebiliyor mu?

## Mevcut Teknik Durum

Proje namespace:

```text
WPXCache
```

Minimum hedef:

```text
PHP 8.1+
WordPress 6.4+
OOP mimari
Options API
Transients API
wp-content/cache/wpxcache cache dizini
wp-content/advanced-cache.php drop-in
```

## Eklenen Ozellikler

### 1. Core Foundation

Tamamlananlar:

- Ana plugin dosyasi.
- Plugin header.
- Namespace mimarisi.
- Autoload sistemi.
- Minimum PHP ve WordPress gereksinim kontrolu.
- Activation hook.
- Deactivation hook.
- Uninstall temeli.
- Default settings.
- Admin menu.
- Basic dashboard.
- Admin CSS/JS enqueue.
- Security helper temeli.
- Capability kontrolu.
- Nonce helper.
- Sanitizer/Escaper temeli.
- FileGuard ve PathValidator.

### 2. Page Cache Core

Tamamlananlar:

- Public page cache temeli.
- RequestContext.
- CacheKey.
- CacheRules.
- CacheStorage.
- CacheReader.
- CacheWriter.
- PageCache buffer akisi.
- GET disi request bypass.
- Logged-in user bypass.
- Admin/login/REST/AJAX bypass.
- WooCommerce cookie/session bypass altyapisi.
- Query string hassas parametre bypass.
- Cache dosya yolu guvenligi.

### 3. Advanced Cache Drop-in

Tamamlananlar:

- `advanced-cache.php` kaynak drop-in dosyasi.
- Drop-in installer.
- Drop-in remove/regenerate.
- Baska eklentiye ait drop-in varsa ezmeme.
- Drop-in sahiplik kontrolu.
- Drop-in config dosyasi.
- Path traversal korumasi.
- Cache hit/miss header altyapisi.
- Sessiz fallback mantigi.

### 4. Smart Purge

Tamamlananlar:

- Full cache purge.
- URL purge.
- Post purge.
- Homepage purge.
- Category/tag/archive related purge temeli.
- SmartPurge service.
- Purge hook'lari.
- Purge loglama.
- FileGuard destekli guvenli silme iyilestirmesi.

### 5. Preload

Tamamlananlar:

- CachePreloader service.
- Manual preload temeli.
- Sitemap URL okuma.
- Homepage priority.
- Recent posts/pages preload.
- Batch processing.
- WP-Cron batch preload.
- Pause/resume/reset altyapisi.
- Preload state option.
- Failed URL loglama.
- Server load korumasi icin batch size/delay ayarlari.

### 6. File Optimization

Tamamlananlar:

- HTML minify.
- WordPress generator meta kaldirma.
- Emoji disable.
- Embed disable.
- Lazy load images.
- Lazy load iframes.
- CSS minify runtime.
- JS compact/minify runtime.
- CSS defer runtime.
- JS defer runtime.
- CSS/JS exclusion list.
- Safe Mode pattern list.
- WooCommerce, form, payment ve jQuery script korumalari.
- Optimize asset cache dizini:

```text
wp-content/cache/wpxcache/assets/css
wp-content/cache/wpxcache/assets/js
```

### 7. WooCommerce Safe Mode

Tamamlananlar:

- WooCommerce detection.
- Safe Mode default ON.
- Cart/checkout/account URL exclusion temeli.
- WooCommerce session cookie bypass.
- Cart fragment koruma pattern'leri.
- Product/shop archive cache icin guvenli zemin.
- Stock/price update purge hook temeli.

### 8. Compatibility Layer

Tamamlananlar:

- WooCommerce compatibility.
- ConflictDetector temeli.
- WP Rocket, LiteSpeed Cache, W3 Total Cache, Autoptimize gibi cache/optimization eklentileri icin cakisma tespiti temeli.
- Cloudflare detection temeli.
- LiteSpeed server detection temeli.
- Redis object cache detection temeli.

### 9. Diagnostics / Health Check

Tamamlananlar:

- WP_CACHE kontrolu.
- advanced-cache.php varlik/sahiplik kontrolu.
- Cache dizini yazilabilirlik kontrolu.
- Log dizini yazilabilirlik kontrolu.
- Gzip/Brotli/OPcache/Object Cache tespiti.
- Pretty permalink kontrolu.
- Disk alan kontrolu.
- WooCommerce safe exclusion kontrolu.
- Smart profile bilgisi.
- Health Check metinlerinin Turkcelestirilmesi.
- Dashboard icinde otomatik aksiyon butonlari:
  - Smart Optimize uygula.
  - Drop-in kur/yenile.
  - Cache dizinlerini hazirla.

### 10. Dashboard

Tamamlananlar:

- Cache Status.
- Safe Defaults.
- Smart Optimize.
- Performance Snapshot.
- File Optimization status.
- Optimized asset count/size.
- Advanced Cache Drop-in status.
- WooCommerce Safety.
- Health Check.
- Warnings.
- Recent Logs.

### 11. Tools

Tamamlananlar:

- Export settings.
- Import settings.
- Reset settings.
- Clear all cache.
- Clear logs.
- Clear optimized assets.
- Regenerate drop-in.
- Remove drop-in.
- Download diagnostics report.

### 12. CDN / Cloudflare Foundation

Tamamlananlar:

- CDN rewrite settings.
- CDN base URL.
- Included file types.
- Excluded paths.
- Cloudflare API token/zone settings.
- Cloudflare purge foundation.
- CDN diagnostics report alanlari.
- Token/secret maskeleme.

### 13. Database Tools

Tamamlananlar:

- DatabaseCleaner temeli.
- Revisions count.
- Auto drafts count.
- Trashed posts count.
- Spam comments count.
- Expired transients count.
- Orphaned post meta count.
- Cleanup preview.
- Kontrollu cleanup aksiyonlari.

### 14. REST API

Tamamlananlar:

- REST route foundation.
- Status endpoint.
- Purge endpoint.
- Preload endpoint.
- Diagnostics endpoint.
- `permission_callback` ile `manage_options` kontrolu.

### 15. WP-CLI

Tamamlananlar:

```bash
wp wpxcache status
wp wpxcache purge all
wp wpxcache purge url <url>
wp wpxcache preload
wp wpxcache dropin status
wp wpxcache dropin install
wp wpxcache dropin remove
wp wpxcache diagnostics
wp wpxcache settings export
wp wpxcache settings import <file>
wp wpxcache assets status
wp wpxcache assets clear
```

### 16. Logging

Tamamlananlar:

- Logger service.
- Error/warning/info/debug seviyeleri.
- Purge loglari.
- Preload loglari.
- Settings import/export loglari.
- Drop-in install/remove loglari.
- Optimized asset clear loglari.
- Hassas verileri loglamama prensibi.

## Cache Turleri ve Strateji

### Page Cache

Mevcut durum:

- Implementasyon basladi ve calisir temel mevcut.
- Anonymous GET request'ler icin statik HTML cache hedefleniyor.
- Drop-in ile WordPress yuklenmeden servis mimarisi kuruldu.

Gelistirilecekler:

- Cache varyasyonlari: mobile, language, currency.
- Cache hit/miss istatistikleri.
- Browser cache header yonetimi.
- ETag / Last-Modified.
- Gzip/Brotli cache kopyalari.
- Nginx/Apache/LiteSpeed server rule rehberleri.

### Object Cache

Mevcut durum:

- Redis/Memcached/Object Cache detection var.
- Henuz object-cache.php drop-in yonetimi yok.

Neden dikkatli olunmali:

Object cache, WordPress object cache API'sini Redis/Memcached gibi persistent storage ile kullanir. Yanlis uygulama:

- Stale options gosterebilir.
- Transient davranisini bozabilir.
- WooCommerce session/cart riskleri dogurabilir.
- Multisite'da blog prefix hatalarina yol acabilir.

Eklenecekler:

- Redis/Memcached detection.
- Object cache health check.
- Existing `object-cache.php` sahiplik kontrolu.
- Kendi object-cache drop-in yerine ilk asamada "compatible mode".
- Redis Object Cache gibi mevcut eklentilerle cakisma uyarisi.
- Object cache flush butonu.
- Object cache stats.
- WooCommerce ve membership safe key exclusion.

### Database Cache

Mevcut durum:

- Database cleaner var.
- Database query cache henuz yok.

Neden dikkatli olunmali:

Database cache her sorguyu cache'lemek degildir. Yanlis database cache:

- Kullaniciya ozel sorgulari saklayabilir.
- Stale fiyat/stok/durum gosterebilir.
- Shared hosting disk I/O'yu artirabilir.
- Object cache ile birlikte ters etki yapabilir.

Eklenecekler:

- Database cache'i varsayilan kapali.
- Sadece read-only, guvenli query pattern'leri.
- Admin, logged-in, WooCommerce checkout/cart bypass.
- Slow query analyzer.
- Query cache yerine once "database optimization + transient cleanup" odakli yaklasim.
- Redis/Memcached varsa database cache yerine object cache onermesi.

### Transient Cache

Mevcut durum:

- Expired transient cleanup count mevcut.

Eklenecekler:

- Expired transient cleanup.
- Oversized transient detection.
- Autoloaded transient warning.
- Transient bloat report.
- WooCommerce transient purge uyumlulugu.
- Transient cache health card.

### Preload

Mevcut durum:

- WP-Cron batch preload var.
- Sitemap ve recent content preload var.

Eklenecekler:

- Preload progress UI.
- Queue viewer.
- Failed URL retry.
- Server load adaptive delay.
- Sitemap index parser.
- WooCommerce product/category preload.
- Multilingual preload.
- WP-CLI detayli preload stats.

### Purge

Mevcut durum:

- Full, URL, post related purge temeli var.

Eklenecekler:

- Purge queue.
- Rate limit.
- CDN/Cloudflare purge queue.
- WooCommerce product/category targeted purge.
- Elementor save purge.
- Menu/widget/theme/customizer purge.
- Rank Math/Yoast sitemap change purge.
- Purge history UI.

### File Optimization

Mevcut durum:

- HTML minify.
- CSS minify.
- JS compact.
- Defer CSS/JS.
- Lazy load.
- Emoji/embed/generator cleanup.
- Exclusion list.

Eklenecekler:

- CSS combine, riskli ve manuel.
- JS combine, riskli ve manuel.
- Delay JS execution, riskli ve plugin-aware.
- Critical CSS.
- Remove unused CSS, ileri seviye.
- Font optimization.
- Google Fonts localize/preload.
- Preconnect/DNS prefetch UI.
- Page builder aware exclusions.

### Image Cache, WebP ve Compression

Mevcut durum:

- Lazy load images.
- Missing image dimensions warning hedefi var ama tam runtime henuz yok.
- WebP conversion yok.
- Image compression yok.

Eklenecekler:

- Image optimization module.
- Attachment scan.
- Image metadata table veya custom post meta strategy.
- Lossless compression.
- Lossy compression.
- WebP conversion.
- AVIF conversion opsiyonel.
- Original image backup.
- Restore original image.
- Bulk optimization queue.
- WP-Cron batch image optimization.
- CLI image optimization.
- Image optimization status column in Media Library.
- WebP serving strategy:
  - `<picture>` rewrite.
  - Rewrite rule mode.
  - CDN-compatible mode.
  - Cache vary by Accept header.
- EXIF stripping option.
- Max dimension resize.
- Thumbnail regeneration integration.
- WooCommerce product image safety.

## Ultra Profesyonel Olmasi Icin Gereken Ek Moduller

### 1. Setup Wizard

- Ilk kurulum sihirbazi.
- Site tipi algilama.
- WooCommerce ve uyelik risk kontrolu.
- Cakisan cache eklentisi uyarisi.
- Smart Optimize akisi.

Durum: Cok adimli ilk kurulum akisi uygulandi. Ilk aktivasyonda yetkili kullanici Setup Wizard'a yonlendirilir. Wizard; site analizi, page cache, file optimization, media, preload, WooCommerce, CDN, advanced rules ve finish adimlarini sirayla gosterir. Her adimda Kaydet ve devam et, Gec ve kurulumu atla aksiyonlari bulunur. Riskli ayarlar kullanici secmeden otomatik acilmaz.

### 2. Risk Level System

Her ayar icin:

- Safe
- Medium
- Risky

Riskli ayarlar:

- Otomatik acilmaz.
- Uyari metni gosterir.
- Geri alma onerisi sunar.
- WooCommerce varsa ekstra uyari verir.

### 3. Safe Rollback

- Ayar degisikligi snapshot'i.
- Son stabil ayara don.
- Optimization rollback.
- Drop-in rollback.
- htaccess/wp-config backup bilgisi.

### 4. Advanced Conflict Detector

Tespit edilecekler:

- WP Rocket.
- LiteSpeed Cache.
- W3 Total Cache.
- WP Fastest Cache.
- Autoptimize.
- SiteGround Optimizer.
- Cloudflare APO.
- Hosting-level cache.
- Varnish.
- Nginx FastCGI cache.
- Redis object-cache.php.
- Baska advanced-cache.php.

### 5. Performance Proof Dashboard

- Cache hit/miss oranlari.
- Ortalama HTML generation time.
- Cache size trend.
- Preload queue trend.
- Purge events.
- Optimized asset stats.
- Image optimization savings.
- Before/after lab test notlari.

### 6. Hosting Compatibility Profiles

- LiteSpeed server.
- Apache.
- Nginx.
- Cloudflare arkasinda site.
- Shared hosting safe mode.
- VPS mode.
- Managed WordPress host mode.

### 7. WooCommerce Pro Safety

- Cart item cookie detection.
- Dynamic pricing detection.
- Coupon/session bypass.
- Payment gateway script protection.
- Product stock/price targeted purge.
- Product variation purge.
- Recently viewed products protection.
- Mini cart compatibility mode.

### 8. Developer API

Gelistirilecek hook/filter seti:

```php
apply_filters('wpxcache_should_cache_request', $should_cache, $request);
apply_filters('wpxcache_cache_key', $key, $request);
apply_filters('wpxcache_cache_ttl', $ttl, $url);
apply_filters('wpxcache_never_cache_urls', $urls);
apply_filters('wpxcache_never_cache_cookies', $cookies);
apply_filters('wpxcache_optimization_exclude_patterns', $patterns, $type);
apply_filters('wpxcache_asset_optimization_excluded', $excluded, $type, $handle, $src);

do_action('wpxcache_before_cache_save', $url);
do_action('wpxcache_after_cache_save', $url, $file);
do_action('wpxcache_before_purge', $urls);
do_action('wpxcache_after_purge', $urls);
do_action('wpxcache_before_preload', $urls);
do_action('wpxcache_after_preload', $result);
```

### 9. Multisite Support

- Network settings.
- Site-level override.
- Per-site cache directory.
- Network purge.
- Network diagnostics.
- Domain mapping support.

### 10. Security Audit Layer

- FileGuard-only filesystem operations.
- Symlink protection.
- Path traversal protection.
- Nonce/capability on every admin action.
- REST permission callbacks.
- Sensitive log redaction.
- Token masking.
- Debug mode isolation.

### 11. Testing Infrastructure

- PHPUnit.
- WordPress test suite.
- WooCommerce test site fixtures.
- Playwright admin smoke tests.
- WP-CLI command tests.
- Static analysis with PHPStan/Psalm.
- PHPCS WordPress Coding Standards.
- GitHub Actions CI.

## Rakip Analizi

### WP Rocket

Resmi dokumanlara gore WP Rocket otomatik olarak page cache, mobile cache, preload, browser caching, GZIP compression, WooCommerce cart fragments cache, Google Fonts optimization ve emoji disable gibi ozellikler sunar. Ayrica WebP konusunda kendi WebP uretmez; WebP dosyalarini ureten baska bir eklentiyle uyumluluk saglar.

WP Rocket guclu yanlari:

- Cok iyi son kullanici deneyimi.
- Varsayilanlari kullanmasi kolay.
- Preload sistemi olgun.
- WooCommerce uyumlulugu guclu.
- Ticari destek ve dokumantasyon guclu.

WP XCache Pro farki:

- Risk seviyeli ayar mimarisi urunun merkezinde olacak.
- Health Check ve Proof Dashboard daha acik ve denetlenebilir olacak.
- Ajans ve gelistirici icin ayar profili, diagnostics report, WP-CLI ve hook mimarisi daha gorunur olacak.
- WooCommerce "bozmama" felsefesi daha agresif bicimde uygulanacak.
- Kullaniciya "neden bu ayar riskli" aciklanacak.

### LiteSpeed Cache

Resmi WordPress.org ve LiteSpeed dokumanlarina gore LiteSpeed Cache cok genis bir all-in-one performans eklentisidir. Object Cache Redis/Memcached destegi, image optimization, WebP/AVIF, minify, combine, critical CSS, lazy load, CDN, Cloudflare API, database cleaner ve LiteSpeed server-level cache gibi ozelliklere sahiptir.

LiteSpeed Cache guclu yanlari:

- LiteSpeed/OpenLiteSpeed sunucularda server-level cache cok guclu.
- QUIC.cloud ekosistemi.
- Image optimization ve WebP/AVIF destegi.
- Cok genis ozellik seti.
- Ucretsiz ve yaygin.

WP XCache Pro farki:

- LiteSpeed'e bagimli olmadan guvenli UX hedefler.
- Server-level cache yerine WordPress tarafinda daha anlasilir ve kontrollu akilli profil sistemi sunar.
- Riskli ayarlari varsayilan olarak acmama politikasi daha net urun prensibidir.
- Ajans/client sitelerinde "bozmadan hizlandir" deneyimi merkezdedir.
- Cakisma tespiti ve rollback stratejisi urunun ana parcasi olacaktir.

### W3 Total Cache

Resmi WordPress.org sayfasina gore W3 Total Cache page cache, database cache, object cache, fragment cache, browser cache, CDN entegrasyonu, minify, lazy load, reverse proxy ve WP-CLI gibi cok genis bir performans framework'u sunar.

W3 Total Cache guclu yanlari:

- Cok kapsamli ve esnek.
- Hosting agnostic WPO framework iddiasi.
- Page/database/object/fragment cache gibi cok katmanli cache.
- CDN ve reverse proxy entegrasyonlari.
- Gelistiriciler ve ileri seviye kullanicilar icin guclu.

WP XCache Pro farki:

- W3 Total Cache kadar karmasik olmadan profesyonel kontrol hedefler.
- Ayar kalabaligi yerine profil ve risk rehberi.
- WooCommerce safe defaults daha merkezi.
- Kullaniciya "hangi ayar neyi bozabilir" bilgisini daha net gosterir.
- Ajanslar icin export/import, rapor ve client-safe modlar daha sade tasarlanir.

## Neden WP XCache Pro Tercih Edilsin?

WP XCache Pro tercih edilmelidir cunku urunun ana hedefi yalnizca hiz degil, **guvenli hizdir**.

Temel tercih nedenleri:

- WooCommerce magazalarinda sepet/odeme risklerini merkeze alir.
- Riskli optimizasyonlari otomatik acmaz.
- Kullaniciya teknik karmasa yasatmadan net durum kartlari sunar.
- Health Check, sadece hata gostermek yerine cozum aksiyonlari sunar.
- Cakisan cache eklentilerini algilar.
- advanced-cache.php ve object-cache.php gibi kritik drop-in dosyalarinda sahiplik mantigini dikkate alir.
- Ajanslar icin export/import ve diagnostics report sunar.
- Gelistiriciler icin WP-CLI ve hook/filter mimarisi sunar.
- Shared hosting gibi kirilgan ortamlarda daha korumali hareket eder.
- "Hizlandir ama bozma" prensibini urun kararlarinin merkezine koyar.

## MVP Sonrasi Oncelikli Yol Haritasi

### Part 18 - WooCommerce Dashboard

- WooCommerce Safe Mode durum kartlari.
- Cart/checkout/account bypass kontrolu.
- WooCommerce cookie/session diagnostics.
- Product/stock/price purge gorunurlugu.

### Part 19 - Object Cache Diagnostics

- Redis/Memcached detection detaylari.
- object-cache.php sahiplik kontrolu.
- Object cache flush button.
- Object cache risk uyarilari.

### Part 20 - Transient Cache Tools

- Expired transient cleanup.
- Autoloaded transient warning.
- Transient bloat report.
- WooCommerce transient safety.

### Part 21 - Image Optimization Foundation

- Media scanner.
- Image optimization queue.
- Original backup strategy.
- WebP conversion plan.
- Compression settings UI.

### Part 22 - WebP Serving

- WebP existence detection.
- `<picture>` rewrite.
- Accept header vary support.
- CDN compatible mode.

### Part 23 - Browser Cache / Headers

- Cache-Control rules.
- Expires headers.
- ETag strategy.
- Static asset browser cache.
- Server rule generator.

### Part 24 - Setup Wizard

- Site scan.
- Recommended profile.
- Safe Optimize.
- Conflict resolution.
- WooCommerce protection checklist.

### Part 25 - CI / Test Suite

- PHPUnit.
- WordPress test suite.
- PHPCS.
- PHPStan.
- WP-CLI smoke tests.

## Bilinen Riskler

- CSS/JS combine ve Delay JS agresif ayarlardir; otomatik acilmamalidir.
- Object cache yanlis kurulumda WooCommerce ve membership sitelerinde stale data riski dogurabilir.
- Database cache shared hosting'de disk I/O nedeniyle performansi dusurebilir.
- WebP rewrite CDN ve cache varyasyonlariyla dikkatli tasarlanmalidir.
- Image compression orijinal dosya backup'i olmadan uygulanmamalidir.
- WP_CACHE ve advanced-cache.php yonetimi wp-config.php ve wp-content izinlerine baglidir.

## Basari Kriterleri

WP XCache Pro 1.0 icin basari kriterleri:

- Fatal error yok.
- PHP warning yok.
- Tum admin aksiyonlarinda nonce/capability var.
- Tum input sanitize ediliyor.
- Tum output escape ediliyor.
- Dosya silme sadece cache dizini icinde.
- Symlink takip edilmiyor.
- WooCommerce cart/checkout/my-account cache disi.
- Logged-in kullaniciya anonymous cache servis edilmiyor.
- Drop-in baska eklentiye aitse ezilmiyor.
- Deactivation guvenli.
- Uninstall kontrollu.
- Debug kapaliyken hassas hata gosterilmiyor.
- Diagnostics raporu token/cookie/API key maskelemeli.

## Kaynaklar

- WP Rocket feature overview: https://docs.wp-rocket.me/article/67-what-exactly-does-wp-rocket-do
- WP Rocket preload docs: https://docs.wp-rocket.me/article/8-preload-cache
- WP Rocket WebP compatibility: https://docs.wp-rocket.me/article/1282-webp
- LiteSpeed Cache WordPress.org: https://wordpress.org/plugins/litespeed-cache/
- LiteSpeed Cache docs: https://docs.litespeedtech.com/lscache/lscwp/
- W3 Total Cache WordPress.org: https://wordpress.org/plugins/w3-total-cache/
