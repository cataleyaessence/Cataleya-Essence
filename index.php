<?php
// Render the member home page at the project root without duplicating its UI.
// Php/home.php remains the single source of truth.
if (!defined('CATELYA_RENDER_HOME_FROM_INDEX')) {
    define('CATELYA_RENDER_HOME_FROM_INDEX', true);
}
require __DIR__ . '/Php/home.php';
