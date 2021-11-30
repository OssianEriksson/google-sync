<?php

namespace Ftek\GoogleSync;

class Sync {
    public static function update() {
        $credentials_path = Settings::get('credentials_path');
        $domain = Settings::get('domain');
        $customer_id = Settings::get('customer_id');
        $admin_email = Settings::get('admin_email');
        if (
            !empty($credentials_path) &&
            !empty($domain) &&
            !empty($customer_id) &&
            !empty($admin_email)
        ) {
            $client = new \Google\Client();
            $client->setApplicationName('Google Sync Wordpress plugin');
            $client->setAuthConfig($credentials_path);
            $client->setSubject($admin_email);
            $client->setScopes([
                \Google\Service\Directory::ADMIN_DIRECTORY_USER_READONLY,
                \Google\Service\Directory::ADMIN_DIRECTORY_ORGUNIT_READONLY
            ]);

            $directory_api = new \Google\Service\Directory($client);

            $org_units = $directory_api->orgunits->listOrgunits($customer_id);
            $users = $directory_api->users->listUsers([
                'domain' => $domain,
                'maxResults' => 500
            ]);
        }
    }
}