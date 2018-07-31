<?php

namespace MauticPlugin\MauticIntegraionsBundle\Auth;

use MauticPlugin\MauticIntegraionsBundle\Auth\Provider\AuthProviderInterface;
use MauticPlugin\MauticIntegraionsBundle\Exception\InvalidProviderException;

class Factory
{
    /**
     * @var AuthProviderInterface[]
     */
    protected $providers = [];

    /**
     * Register an auth provider.
     *
     * @param AuthProviderInterface $provider
     */
    public function registerAuthProvider(AuthProviderInterface $provider)
    {
        $this->providers[$provider->getAuthType()] = $provider;
    }

    /**
     * Get a registered auth provider.
     *
     * @param string $provider
     *
     * @return AuthProviderInterface
     */
    public function getAuthProvider($provider)
    {
        if (array_key_exists($provider, $this->providers)) {
            return $this->providers[$provider];
        }

        throw new InvalidProviderException($provider);
    }
}