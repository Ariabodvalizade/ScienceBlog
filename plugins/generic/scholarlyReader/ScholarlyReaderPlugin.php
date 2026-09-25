<?php

/**
 * @file plugins/generic/scholarlyReader/ScholarlyReaderPlugin.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class ScholarlyReaderPlugin
 *
 * @brief Reference linking, inline HTML full text and scholarly metadata.
 */

namespace APP\plugins\generic\scholarlyReader;

use PKP\plugins\GenericPlugin;

class ScholarlyReaderPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        return parent::register($category, $path, $mainContextId);
    }

    public function getDisplayName()
    {
        return __('plugins.generic.scholarlyReader.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.scholarlyReader.description');
    }
}
