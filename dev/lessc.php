<?php
// Compile the parent stylesheet + Meridian LESS inside the OJS container and
// report errors precisely (dev only):
//   docker compose ... exec -T ojs php /var/www/html/plugins/themes/meridian/../../../dev-lessc.php
require '/var/www/html/lib/pkp/lib/vendor/autoload.php';
$less = new Less_Parser(['compress' => true, 'relativeUrls' => false]);
try {
    $less->parseFile('/var/www/html/plugins/themes/default/styles/index.less');
    $less->parseFile('/var/www/html/plugins/themes/meridian/styles/meridian.less');
    $less->parse("@baseUrl: '';");
    $css = $less->getCss();
    echo "OK " . strlen($css) . " bytes\n";
} catch (Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
    exit(1);
}
