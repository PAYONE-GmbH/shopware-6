<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Struct;

use Shopware\Core\Framework\Struct\Struct;

class SystemRequestData extends Struct
{
    protected $aid;

    protected $mid;

    protected $portalid;

    protected $key;

    protected $api_version;

    protected $mode;

    protected $encoding;

    protected $integrator_name;

    protected $integrator_version;

    protected $solution_name;

    protected $solution_version;

    public function getAid()
    {
        return $this->aid;
    }

    public function getMid()
    {
        return $this->mid;
    }

    public function getPortalid()
    {
        return $this->portalid;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getApiVersion()
    {
        return $this->api_version;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public function getIntegratorName()
    {
        return $this->integrator_name;
    }

    public function getIntegratorVersion()
    {
        return $this->integrator_version;
    }

    public function getSolutionName()
    {
        return $this->solution_name;
    }

    public function getSolutionVersion()
    {
        return $this->solution_version;
    }
}
