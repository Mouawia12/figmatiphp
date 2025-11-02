<?php
// partials/seo.php

// Ensure helper functions are available
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('app_href')) { function app_href(string $p){ return rtrim(str_replace('\\ ', '/', dirname($_SERVER['PHP_SELF'] ?? '/')), '/') . '/' . ltrim($p,'/'); } }

// Include the new SEO library
require_once __DIR__ . '/../inc/seo.php';

// --- Get All Settings from DB ---
try {
    $db_path = __DIR__ . '/../forms.db'; // Assuming settings are in the forms DB
    if (file_exists($db_path)) {
        $db = new PDO('sqlite:' . $db_path);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $all_settings_raw = $db->query("SELECT k, v FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    } else {
        $all_settings_raw = [];
    }
} catch (Throwable $e) {
    error_log("SEO Settings DB Error: " . $e->getMessage());
    $all_settings_raw = [];
}

// --- Define Variables ---
// Page-specific variables (can be set before including this file)
// $page_title, $page_description, $canonical_url, $og_image, $no_index

// Default settings from database
$site_name = $all_settings_raw['site_name'] ?? 'الموقع';
$default_desc = $all_settings_raw['seo_description'] ?? '';
$default_keys = $all_settings_raw['seo_keywords'] ?? '';
$twitter_handle = $all_settings_raw['twitter_handle'] ?? '';

// Determine final values
$final_title = !empty($page_title) ? ($page_title . ' – ' . $site_name) : $site_name;
$final_desc = !empty($page_description) ? $page_description : $default_desc;

// Build Canonical URL
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base   = rtrim($scheme . '://' . $host, '/');
$path   = $_SERVER['REQUEST_URI'] ?? '/';
$final_canonical = $canonical_url ?? ($base . $path);

// --- Instantiate and Configure SEO Class ---
$meta = new MetaTagGenerator();

$meta->setTitle($final_title)
     ->setDescription($final_desc)
     ->setKeywords($default_keys)
     ->setCanonical($final_canonical)
     ->set('author', $site_name);

// Robots tag
if (!empty($no_index)) {
    $meta->set('robots', 'noindex, nofollow');
}

// Open Graph and Twitter Cards
$meta->setOg('type', 'website')
     ->setOg('locale', 'ar_SA')
     ->setOg('site_name', $site_name);

$meta->setTwitter('card', 'summary_large_image');
if (!empty($twitter_handle)) {
    $meta->setTwitter('site', '@' . ltrim($twitter_handle, '@'));
}

// --- Render Meta Tags ---
// Store meta tags in variable instead of echoing (to prevent headers issue)
$meta_tags_output = (string)$meta;

// --- JSON-LD Schema (Structured Data) ---
$logo_path = app_href('assets/img/logo.svg');
$logo_abs_url = (substr($logo_path, 0, 4) === 'http') ? $logo_path : ($base . $logo_path);

// Store JSON-LD in variable
$json_ld_output = '<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": ' . json_encode($site_name, JSON_UNESCAPED_UNICODE) . ',
  "url": ' . json_encode($base, JSON_UNESCAPED_SLASHES) . ',
  "logo": ' . json_encode($logo_abs_url, JSON_UNESCAPED_SLASHES) . '
}
</script>';