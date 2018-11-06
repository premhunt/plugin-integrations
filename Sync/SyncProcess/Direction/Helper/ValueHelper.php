<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncProcess\Direction\Helper;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Symfony\Component\Translation\TranslatorInterface;

class ValueHelper
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var NormalizedValueDAO
     */
    private $normalizedValueDAO;

    /**
     * @var string
     */
    private $fieldState;

    /**
     * @var string
     */
    private $syncDirection;

    /**
     * ValueHelper constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param NormalizedValueDAO $normalizedValueDAO
     * @param string             $fieldState
     * @param string             $syncDirection
     *
     * @return NormalizedValueDAO
     */
    public function getValueForIntegration(NormalizedValueDAO $normalizedValueDAO, string $fieldState, string $syncDirection): NormalizedValueDAO
    {
        $this->normalizedValueDAO = $normalizedValueDAO;
        $this->fieldState         = $fieldState;
        $this->syncDirection      = $syncDirection;

        $newValue = $this->getValue(ObjectMappingDAO::SYNC_TO_MAUTIC);

        return new NormalizedValueDAO($normalizedValueDAO->getType(), $normalizedValueDAO->getNormalizedValue(), $newValue);
    }

    /**
     * @param NormalizedValueDAO $normalizedValueDAO
     * @param string             $fieldState
     * @param string             $syncDirection
     *
     * @return NormalizedValueDAO
     */
    public function getValueForMautic(NormalizedValueDAO $normalizedValueDAO, string $fieldState, string $syncDirection): NormalizedValueDAO
    {
        $this->normalizedValueDAO = $normalizedValueDAO;
        $this->fieldState         = $fieldState;
        $this->syncDirection      = $syncDirection;

        $newValue = $this->getValue(ObjectMappingDAO::SYNC_TO_INTEGRATION);

        return new NormalizedValueDAO($normalizedValueDAO->getType(), $normalizedValueDAO->getNormalizedValue(), $newValue);
    }

    /**
     * @param string $directionToIgnore
     *
     * @return float|int|mixed|string
     */
    private function getValue(string $directionToIgnore)
    {
        $value = $this->normalizedValueDAO->getNormalizedValue();

        // If the field is not required, do not force a value
        if (FieldDAO::FIELD_REQUIRED !== $this->fieldState) {
            return $value;
        }

        // If the field is not configured to update the Integration, do not force a value
        if ($directionToIgnore === $this->syncDirection) {
            return $value;
        }

        // If the value is not empty (including 0 or false), do not force a value
        if (null !== $value && $value !== '') {
            return $value;
        }

        switch ($this->normalizedValueDAO->getType()) {
            case NormalizedValueDAO::EMAIL_TYPE:
            case NormalizedValueDAO::DATE_TYPE:
            case NormalizedValueDAO::DATETIME_TYPE:
            case NormalizedValueDAO::BOOLEAN_TYPE:
                // we can't assume anything with these so just return null and let the integration handle the error
                return $this->normalizedValueDAO->getOriginalValue();
            case NormalizedValueDAO::INT_TYPE:
                return 0;
            case NormalizedValueDAO::DOUBLE_TYPE:
            case NormalizedValueDAO::FLOAT_TYPE:
                return 1.0;
            default:
                return $this->translator->trans('mautic.core.unknown');
        }
    }
}