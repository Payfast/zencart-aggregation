<?php

class PayfastConfig
{
    private string $server;
    private string $passphrase;
    private string $debugEmail;
    private array $moduleInfo;

    public function __construct()
    {
        $this->server     = (strcasecmp(MODULE_PAYMENT_PF_SERVER, 'live') == 0) ?
            MODULE_PAYMENT_PF_SERVER_LIVE : MODULE_PAYMENT_PF_SERVER_TEST;
        $this->passphrase = MODULE_PAYMENT_PF_PASSPHRASE;
        $this->debugEmail = defined('MODULE_PAYMENT_PF_DEBUG_EMAIL_ADDRESS') ?
            MODULE_PAYMENT_PF_DEBUG_EMAIL_ADDRESS : STORE_OWNER_EMAIL_ADDRESS;
        $this->moduleInfo = [
            'pfSoftwareName'       => PF_SOFTWARE_NAME,
            'pfSoftwareVer'        => PF_SOFTWARE_VER,
            'pfSoftwareModuleName' => PF_MODULE_NAME,
            'pfModuleVer'          => PF_MODULE_VER,
        ];
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function getPassphrase(): string
    {
        return $this->passphrase;
    }

    public function getDebugEmail(): string
    {
        return $this->debugEmail;
    }

    public function getModuleInfo(): array
    {
        return $this->moduleInfo;
    }
}
