<?php
// Template konfigurasi lokal untuk pengembangan.
//
// CARA PAKAI:
//   1. Copy file ini menjadi config.php  (config.php TIDAK di-commit ke Git).
//   2. Sesuaikan dbname, dbuser, dbpass, dataroot, dan wwwroot dengan
//      environment lokal Anda masing-masing.
//   3. Jalankan instalasi baru atau restore dump database.
//
// Setiap developer memakai database dan folder moodledata sendiri.

unset($CFG);
global $CFG;
$CFG = new stdClass();

// ---------------------------------------------------------------------------
// Database (contoh: DB terpisah per developer)
//   dev A : moodle531_alpin
//   dev B : moodle531_nama2
// ---------------------------------------------------------------------------
$CFG->dbtype    = 'mariadb';      // 'mariadb' atau 'mysqli' untuk MySQL/MariaDB
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'moodle531_dev'; // GANTI dengan nama DB Anda
$CFG->dbuser    = 'root';          // GANTI bila user MySQL berbeda
$CFG->dbpass    = '';              // GANTI dengan password MySQL Anda
$CFG->prefix    = 'mdl_';
$CFG->dboptions = [
    'dbpersist' => 0,
    'dbport' => '',
    'dbsocket' => '',
    'dbcollation' => 'utf8mb4_unicode_ci',
];

// ---------------------------------------------------------------------------
// URL dan folder data
//   - wwwroot harus SAMA dengan URL yang dipakai di browser.
//   - dataroot HARUS di luar folder web (jangan di dalam htdocs).
// ---------------------------------------------------------------------------
$CFG->wwwroot   = 'http://localhost/moodle';      // contoh: http://moodle.test
$CFG->dataroot  = 'C:\\xampp\\moodledata-dev';    // GANTI dengan folder Anda
$CFG->admin     = 'admin';

$CFG->directorypermissions = 02777;

// Jangan ubah baris ini.
require_once(__DIR__ . '/lib/setup.php');
