<?php
namespace WCGQL\Mobile;

use WCGQL\Mobile\Contracts\MobileDriverInterface;

class MobileManager
{
    private $mobileDriver;

    public function __construct()
    {
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
        if ($mobileDriver === "" || !class_exists('WCGQL\\Mobile\\Providers\\' . $providerClassName)) {
            $providerClassName = get_option('mobile_provider');
        }
        $providerClass = 'WCGQL\\Mobile\\Providers\\' . $providerClassName;
        $provider  = new $providerClass;
        if(!($provider instanceof MobileDriverInterface)){
            throw new \Exception("Provider Not Valid");
        }
        $this->mobileDriver =  $provider;
    }
}
