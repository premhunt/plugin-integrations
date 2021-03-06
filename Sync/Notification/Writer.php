<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\Notification;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\UserBundle\Entity\User;

class Writer
{
    /**
     * @var NotificationModel
     */
    private $notificationModel;

    /**
     * @var AuditLogModel
     */
    private $auditLogModel;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param NotificationModel      $notificationModel
     * @param AuditLogModel          $auditLogModel
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        NotificationModel $notificationModel,
        AuditLogModel $auditLogModel,
        EntityManagerInterface $entityManager
    ) {
        $this->notificationModel   = $notificationModel;
        $this->auditLogModel       = $auditLogModel;
        $this->em                  = $entityManager;
    }

    /**
     * @param string $header
     * @param string $message
     * @param int    $userId
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function writeUserNotification(string $header, string $message, int $userId): void
    {
        $this->notificationModel->addNotification(
            $message,
            null,
            false,
            $header,
            'fa-refresh',
            null,
            $this->em->getReference(User::class, $userId)
        );
    }

    /**
     * @param string   $bundle
     * @param string   $object
     * @param int|null $objectId
     * @param string   $action
     * @param array    $details
     */
    public function writeAuditLogEntry(string $bundle, string $object, ?int $objectId, string $action, array $details): void
    {
        $log = [
            'bundle'   => $bundle,
            'object'   => $object,
            'objectId' => $objectId,
            'action'   => $action,
            'details'  => $details,
        ];

        $this->auditLogModel->writeToLog($log);
    }
}
