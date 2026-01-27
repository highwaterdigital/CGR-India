<?php

namespace BitApps\Social\HTTP\Services\Factories;

class AuthServiceFactory
{
    public function createAuthService($platform, $authType)
    {
        $proClassName = 'BitApps\\SocialPro\\HTTP\\Services\\Social\\' . ucfirst($platform) . 'Service\\' . ucfirst($platform) . $authType . 'Service';
        $freeClassName = 'BitApps\\Social\\HTTP\\Services\\Social\\' . ucfirst($platform) . 'Service\\' . ucfirst($platform) . $authType . 'Service';

        if (class_exists($proClassName)) {
            return new $proClassName();
        } elseif (class_exists($freeClassName)) {
            return new $freeClassName();
        }

        return (object) ['status' => 'error', 'message' => 'File should be created like: ' . ucfirst($platform) . $authType . 'Service'];
    }

    public function createAiAuthService($platform, $authType)
    {
        $proClassName = 'BitApps\\SocialPro\\HTTP\\Services\\Ai\\' . ucfirst($platform) . 'Service\\' . ucfirst($platform) . ucfirst($authType) . 'Service';

        if (class_exists($proClassName)) {
            return new $proClassName();
        }

        return (object) ['status' => 'error', 'message' => 'File should be created like: ' . ucfirst($platform) . ucfirst($authType) . 'Service'];
    }
}
