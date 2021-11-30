<?php

namespace Ftek\GoogleSync;

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

Settings::uninstall();
Login::uninstall();