<?php
/**
 * Plugin Name: Google Sync
 * Plugin URI: https://github.com/OssianEriksson/google-sync
 * Description: Wordpress Google Workspace integration plugin
 * Version: 1.0.0
 * Author: Spidera
 * Licence: GLP-3.0
 */

namespace Ftek\GoogleSync;

if (!defined('WPINC')) {
    die;
}

require_once __DIR__ . '/vendor/autoload.php';

Settings::init();
Login::init();
// Sync::update();
