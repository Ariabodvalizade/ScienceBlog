<?php

/**
 * Download the free DB-IP "IP to City Lite" database that OJS uses to count
 * visits by country, if it isn't there yet (or always, with --force).
 * The OJS scheduler refreshes it every month after that.
 *
 *   php /usr/local/bin/journal-update-geodb.php [--force]
 */

require '/var/www/html/tools/bootstrap.php';

use PKP\cliTool\CommandLineTool;
use PKP\statistics\PKPStatisticsHelper;
use PKP\task\UpdateIPGeoDB;

class UpdateGeoDbTool extends CommandLineTool
{
    public function execute(): void
    {
        $path = PKPStatisticsHelper::getGeoDBPath();
        if (is_file($path) && !in_array('--force', $this->argv, true)) {
            echo "GeoIP database present: {$path}\n";
            return;
        }
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        (new UpdateIPGeoDB())->execute();
        if (is_file($path)) {
            echo "GeoIP database downloaded: {$path}\n";
            return;
        }
        fwrite(STDERR, "Could not download the GeoIP database (statistics by country will start once it is available).\n");
        exit(1);
    }
}

(new UpdateGeoDbTool($argv ?? []))->execute();
