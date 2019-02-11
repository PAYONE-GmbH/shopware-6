<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Struct;

use ReflectionClass;
use Shopware\Core\Framework\Struct\Struct;

abstract class AbstractWebhookStruct extends Struct
{
    /**
     * Takes the provided data and sets the class properties depending on the key in the array.
     *
     * @param array $data
     *
     * @throws \ReflectionException
     */
    protected function fromArray(array $data): void
    {
        $data =$this->prepareData($data);

        $reflector = new ReflectionClass($this);

        foreach ($reflector->getProperties() as $property) {
            if (!$property->isPublic()) {
                continue;
            }

            $propertyNameLowercase = strtolower($property->getName());
            
            if (array_key_exists($propertyNameLowercase, $data)) {
                $property->setValue($this, $data[$propertyNameLowercase]);
            }
        }
    }

    private function prepareData(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            //Lowercase and remove underscore
            $key = strtolower(str_replace('_', '', $key));
            $result[$key] = $value;
        }

        return $result;
    }
}
