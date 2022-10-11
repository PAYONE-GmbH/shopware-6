<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay\DeviceFingerprint;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DeviceFingerprintService implements DeviceFingerprintServiceInterface
{
    /** @var SessionInterface */
    protected $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function isDeviceIdentTokenAlreadyGenerated(): bool
    {
        return $this->session->get(self::SESSION_VAR_NAME) !== null;
    }

    public function getDeviceIdentToken(): string
    {
        $sessionValue = $this->session->get(self::SESSION_VAR_NAME);

        if ($sessionValue) {
            $token = $sessionValue;
        } else {
            $sessionId = $this->session->get('sessionId');
            $token     = md5($sessionId . '_' . microtime());
            $this->session->set(self::SESSION_VAR_NAME, $token);
        }

        return $token;
    }

    public function deleteDeviceIdentToken(): void
    {
        $this->session->remove(self::SESSION_VAR_NAME);
    }

    public function getDeviceIdentSnippet(string $snippetId, string $deviceIdentToken): string
    {
        $location = 'Checkout';

        $snippet = sprintf(
            '<script language="JavaScript">var di = %s;</script>',
            json_encode([
                'v' => $snippetId,
                't' => $deviceIdentToken,
                'l' => $location,
            ])
        );

        $snippet .= sprintf(
            '<script type="text/javascript" src="//d.ratepay.com/%1$s/di.js"></script>
             <noscript><link rel="stylesheet" type="text/css" href="//d.ratepay.com/di.css?v=%1$s&t=%2$s&l=%3$s" /></noscript>',
            $snippetId,
            $deviceIdentToken,
            $location
        );

        return $snippet;
    }
}
