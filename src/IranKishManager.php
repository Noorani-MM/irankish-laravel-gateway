<?php

namespace IranKish;

/**
 * Simple manager to expose gateway instance(s).
 * If you later support multiple profiles, this class can select between them.
 */
class IranKishManager
{
    public function __construct(protected array $config) {}

    public function gateway(): IranKishGateway
    {
        return new IranKishGateway($this->config);
    }
}
