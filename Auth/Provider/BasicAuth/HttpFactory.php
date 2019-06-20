<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Auth\Provider\BasicAuth;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\AuthProviderInterface;
use MauticPlugin\IntegrationsBundle\Exception\PluginNotConfiguredException;

/**
 * Factory for building HTTP clients using basic auth
 */
class HttpFactory implements AuthProviderInterface
{
    const NAME = 'basic_auth';

    /**
     * Cache of initialized clients.
     *
     * @var Client[]
     */
    private $initializedClients = [];

    /**
     * @return string
     */
    public function getAuthType(): string
    {
        return self::NAME;
    }

    /**
     * @param CredentialsInterface $credentials
     *
     * @return ClientInterface
     * @throws PluginNotConfiguredException
     */
    public function getClient($credentials): ClientInterface
    {
        if (!$this->credentialsAreConfigured($credentials)) {
            throw new PluginNotConfiguredException('Username or password is missing');
        }

        // Return cached initialized client if there is one.
        if (!empty($this->initializedClients[$credentials->getUsername()])) {
            return $this->initializedClients[$credentials->getUsername()];
        }

        return new Client(
            [
                'auth' => [
                    $credentials->getUsername(),
                    $credentials->getPassword()
                ],
            ]
        );
    }

    /**
     * @param CredentialsInterface $credentials
     *
     * @return bool
     */
    protected function credentialsAreConfigured(CredentialsInterface $credentials): bool
    {
        return $credentials->getUsername() && $credentials->getPassword();
    }
}