<?php
// Pretty URL router for course slugs.
// /course/<slug> -> /course/view.php?id=<courseid>
//
// The slug matches (case-insensitive):
//   - course shortname  (ASANA-PM-2026 -> asana-pm-2026)
//   - course idnumber
//   - course fullname   (words joined with hyphens)
//
// This file is loaded directly by .htaccess, no login required.

define('NO_DEBUG_DISPLAY', true);
require(__DIR__ . '/../config.php');

function pretty_slugify(string $text): string {
    $text = core_text::strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

$type = optional_param('type', 'course', PARAM_ALPHA);
$slug = optional_param('slug', '', PARAM_TEXT);
$slug = pretty_slugify($slug);

if ($type !== 'course' || $slug === '') {
    header('HTTP/1.1 404 Not Found');
    echo 'Not found';
    exit;
}

$courses = $DB->get_records('course', null, '', 'id, shortname, idnumber, fullname, visible');
$target = null;
foreach ($courses as $course) {
    $candidates = [
        pretty_slugify($course->shortname ?? ''),
        pretty_slugify($course->idnumber ?? ''),
        pretty_slugify($course->fullname ?? ''),
    ];
    if (in_array($slug, array_filter($candidates), true)) {
        $target = $course;
        break;
    }
}

if (!$target) {
    header('HTTP/1.1 404 Not Found');
    echo 'Course not found';
    exit;
}

$url = (new moodle_url('/course/view.php', ['id' => $target->id]))->out(false);
header('HTTP/1.1 301 Moved Permanently');
header('Location: ' . $url);
exit;
