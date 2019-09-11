<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncService;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;

interface SyncServiceInterface
{
    /**
     * @param InputOptionsDAO $inputOptionsDAO
     */
    public function processIntegrationSync(InputOptionsDAO $inputOptionsDAO);
}
