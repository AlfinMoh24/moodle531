<?php
// Regenerates the pretty URL rules inside public/.htaccess from the course table.
// Slugs match shortname, idnumber and fullname (slugified). Matching URLs are
// rewritten internally to course/view.php?id=N, so the address bar keeps the slug.
//
// Run: php tools/generate-pretty-urls.php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');

global $DB;

function pretty_slugify(string $text): string {
    $text = core_text::strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

$courses = $DB->get_records('course', ['visible' => 1], 'id', 'id, shortname, idnumber, fullname');
$rules = [];
foreach ($courses as $course) {
    $slugs = array_unique(array_filter([
        pretty_slugify($course->shortname ?? ''),
        pretty_slugify($course->idnumber ?? ''),
        pretty_slugify($course->fullname ?? ''),
    ]));
    foreach ($slugs as $slug) {
        $rules[] = "RewriteRule ^course/" . preg_quote($slug, '/') . "/?$ course/view.php?id={$course->id} [L,QSA]";
    }
}

$htaccess = __DIR__ . '/../public/.htaccess';
$content = file_get_contents($htaccess);
$block = "# BEGIN PRETTY URLS\n" . implode("\n", $rules) . "\n# END PRETTY URLS";

if (preg_match('/# BEGIN PRETTY URLS.*?# END PRETTY URLS/s', $content)) {
    $content = preg_replace('/# BEGIN PRETTY URLS.*?# END PRETTY URLS/s', $block, $content);
} else {
    // Insert before the generic fallback rewrite.
    $marker = "# Course slug router.";
    $pos = strpos($content, $marker);
    if ($pos === false) {
        $content .= "\n\n" . $block . "\n";
    } else {
        $content = substr($content, 0, $pos) . $block . "\n\n" . substr($content, $pos);
    }
}

file_put_contents($htaccess, $content);
echo "Generated " . count($rules) . " rewrite rules for " . count($courses) . " course(s).\n";
