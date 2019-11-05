<?php
namespace GQL\Mobile;

use GQL\Mobile\Contracts\MobileDriverInterface;
use GQL\Helpers;

class MobileManager
{
    private $mobileDriver;
    private $ctx;
    public function __construct(&$ctx)
    {
        $this->ctx = $ctx;
        $this->setMobileProvider();
    }

    public function sendOTP(array $to, array $options)
    {
        return $this->mobileDriver->sendOTP($to, $options);
    }

    public function sendForgetPassword(array $to, array $options)
    {
        return $this->mobileDriver->sendForgetPassword($to, $options);
    }

    public function verifyOTP(array $to, string $token)
    {
        return $this->mobileDriver->verifyOTP($to, $token);
    }

    /**
     * Get Current Provider
     * @return MobileDriverInterface $provider
     */
    function getMobileProvider()
    {
        return $this->mobileDriver;
    }

    public function setMobileProvider($mobileDriver = "")
    {
        $providerClassName = $mobileDriver;
        if ($mobileDriver === "" || !class_exists('GQL\\Mobile\\Providers\\' . $providerClassName)) {
            $providerClassName = (new DBManager($this->ctx))->getSettingByKey('config_mobile', 'config_mobile_mobile_provider')['value'];
        }
        $providerClass = 'GQL\\Mobile\\Providers\\' . $providerClassName;
        $provider  = new $providerClass($this->ctx);
        if(!($provider instanceof MobileDriverInterface)){
            throw new \Exception("Provider Not Valid");
        }
        $this->mobileDriver =  $provider;
    }
}
