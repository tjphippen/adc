<?php namespace Tjphippen\Adc;

class Adc
{
    protected $config;
    protected $authentication;
    protected $customerClient;
    protected $dealerClient;
    protected $validateClient;

    /**
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function dealer($login)
    {
        $this->authentication = [
            'User' => $login['username'],
            'Password' => $login['password']
        ];
        return $this;
    }

    protected function dealerClient()
    {
        $this->dealerClient = new \SoapClient($this->config['wsdls']['dealer'], ['trace' => 1]);
        $dealerHeaders = new \SoapHeader($this->config['namespace'], 'Authentication', $this->authentication);
        $this->dealerClient->__setSoapHeaders($dealerHeaders);
        return $this->dealerClient;
    }

    protected function customerClient()
    {
        $this->customerClient = new \SoapClient($this->config['wsdls']['customer'], ['trace' => 1]);
        $customerHeaders = new \SoapHeader($this->config['namespace'], 'Authentication', $this->authentication);
        $this->customerClient->__setSoapHeaders($customerHeaders);
        return $this->customerClient;
    }

    protected function validateClient()
    {
        $this->validateClient = new \SoapClient($this->config['wsdls']['validate'], ['trace' => 1]);
        $validateHeaders = new \SoapHeader($this->config['namespace'], 'Authentication', $this->authentication);
        $this->validateClient->__setSoapHeaders($validateHeaders);
        return $this->validateClient;
    }

    /**
     * returns all customer ids
     *
     * @return array
     */
    public function getCustomers()
    {
        return (array)$this->customerClient()->GetCustomerList()->GetCustomerListResult->int;
    }

    /**
     * returns customer info
     *
     * @param int $id
     * @return array
     */
    public function getCustomer($id)
    {
        return (array)$this->customerClient()->GetCustomerInfo(['customerId' => $id])->GetCustomerInfoResult;
    }


    /**
     * returns customer info by email
     *
     * @param string $email
     * @return array
     */
    public function getCustomerByEmail($email)
    {
        return (array)$this->customerClient()->GetCustomerListByEmail(compact($email))->GetCustomerListByEmailResult;
    }

    /**
     * creates a customer
     *
     * @param $data
     * @return array
     */
    public function createCustomer($data)
    {
        $result1 = $this->customerClient()->CreateCustomer(['input' => $data])->CreateCustomerResult;
        if(isset($data['UnitDescription'])) {
            $result2 = $this->updateDescription($result1->CustomerId, $data['UnitDescription']);
            $results = [
                $result1,
                $result2
            ];
            return (array)$results;
        }else return (array)$result1;
    }

    /**
     * updates customer's unit description, email, primary phone number, login, password, address,
     * central service forwarding info, and/or service plan
     *
     * @param $id
     * @param $data
     * @return array
     */
    public function updateCustomer($id, $data)
    {
        $results = array();
        if(isset($data['description'])){
            array_push($results,(array)$this->updateDescription($id, $data['description']));
        }
        if(isset($data['email'])){
            array_push($results, (array)$this->updateEmail($id, $data['email']));
        }
        if(isset($data['phone'])){
            array_push($results, (array)$this->updatePhone($id, $data['phone']));
        }
        if(isset($data['username'])){
            array_push($results, (array)$this->updateUsername($id, $data['username']));
        }
        if(isset($data['password'])){
            array_push($results, (array)$this->updatePassword($id, $data['password']));
        }
        if(isset($data['address'])){
            array_push($results, (array)$this->updateAddress($id, $data['address']));
        }
        if(isset($data['cs_info'])){
            array_push($results, (array)$this->updateCSInfo($id, $data['cs_info']));
        }
        if(isset($data['service'])){
            array_push($results, (array)$this->updateServicePlan($id, $data['service']));
        }
        return $results;
    }

    /**
     * lists all devices for a customer
     *
     * @param $id
     * @return array
     */
    public function getDevices($id)
    {
        $result = $this->customerClient()->GetDeviceList(['customerId' => $id])->GetDeviceListResult;
        if(isset($result->PanelDevice)){
            return (array) $result->PanelDevice;
        }
        return null;
    }

    /**
     * terminates a customer account
     *
     * @param $id
     * @return array
     */
    public function deleteCustomer($id)
    {
        return (array)$this->customerClient()->TerminateCustomer(['customerId' => $id]);
    }

    /**
     * updates customer's unit description
     *
     * @param $id
     * @param $description
     * @return array
     */
    public function updateDescription($id, $description)
    {
        $data = [
            'CustomerId' => $id,
            'Description' => $description
        ];
        return (array)$this->customerClient()->UpdateUnitDescription(['input' => $data]);
    }

    /**
     * update's customer's primary
     *
     * @param $id
     * @param $email
     * @return array
     */
    public function updateEmail($id, $email)
    {
        $data = [
            'customerId' => $id,
            'newEmailAddress' => $email
        ];
        return (array)$this->customerClient()->UpdatePrimaryEmail($data);
    }

    /**
     * updates customer's primary phone number
     *
     * @param $id
     * @param $phone
     * @return array
     */
    public function updatePhone($id, $phone)
    {
        $data = [
            'customerId' => $id,
            'newPhone' => $phone
        ];
        return (array)$this->customerClient()->UpdatePrimaryPhone($data);
    }

    /**
     * updates customer's login username
     *
     * @param $id
     * @param $username
     * @return array
     */
    public function updateUsername($id, $username)
    {
        $data = [
            'customerId' => $id,
            'newLogin' => $username
        ];
        return (array)$this->customerClient()->UpdateCustomerLogin($data);
    }

    /**
     * updates customer's login password
     *
     * @param $id
     * @param $password
     * @return array
     */
    public function updatePassword($id, $password)
    {
        $data = [
            'customerId' => $id,
            'newPassword' => $password
        ];
        return (array)$this->customerClient()->UpdateCustomerPassword($data);
    }

    /**
     * updates customer's installation and customer address
     *
     * @param $id
     * @param $address
     * @return array
     */
    public function updateAddress($id, $address)
    {
        $data = [
            'CustomerId' => $id,
            'InstallAddress' => $address['InstallAddress'],
            'CustomerAddress' => $address['CustomerAddress'],
            'UseGivenAddressForBoth' => 0
        ];
        return (array)$this->customerClient()->UpdateCustomerAddress(['input' => $data]);
    }

    /**
     * updates customer's central station forwarding information
     *
     * @param $id
     * @param $cs_info
     * @return array
     */
    public function updateCSInfo($id, $cs_info)
    {
        $data = [
            'customerId' => $id,
            'forwardingOption' => $cs_info['forwardingOption'],
            'phoneLinePresent' => $cs_info['phoneLinePresent'],
            'eventGroupsToForward' => $cs_info['eventGroupsToForward'],
            'accountNumber' => $cs_info['accountNumber'],
            'receiverNumber' => $cs_info['receiverNumber']
        ];
        return (array)$this->customerClient()->UpdateCentralStationInfo($data);
    }

    /**
     * updates customer's service plan
     *
     * @param $id
     * @param $service
     * @return array
     */
    public function updateServicePlan($id, $service)
    {
        $data = [
            'customerId' => $id,
            'newPackageId' => $service['newPackageId'],
            'addOnFeatures' => $service['addOnFeatures']
        ];
        return (array)$this->customerClient()->ChangeServicePlan($data);
    }

    /**
     * returns all packages for certain account type
     *
     * @param $data
     * @return array
     */
    public function getPackages($data)
    {
        return (array)$this->dealerClient()->GetPackageIds(['input' => $data])->GetPackageIdsResult->Packages->PackageInfo;
    }

    /**
     * returns specific package by it's package description
     *
     * @param $data
     * @param $description
     * @return array|string
     */
    public function getPackageByDescription($data, $description)
    {
        $packages = $this->getPackages($data);
        foreach($packages as $package){
            if($package->PackageDescription == $description) return (array)$package;
        }
        return 'Package Description Not Found';
    }

    public function getPackageById($data, $packageid)
    {
        $packages = $this->getPackages($data);
        foreach($packages as $package){
            if($package->PackageId == $packageid) return (array)$package;
        }
        return 'Package Id Not Found';
    }

    /**
     * returns network coverage for address
     *
     * @param $network
     * @param $address
     * @return array
     */
    public function checkCoverage($network, $address)
    {
        $data = array_merge(compact('network'), compact('address'));
        return (array)$this->validateClient()->CheckCoverage($data)->CheckCoverageResult;
    }

    /**
     * returns modem info
     *
     * @param $serialNumber
     * @return array
     */
    public function checkModemAndCoverage($modemSerialNumber, $address)
    {
        $data = array_merge(compact('modemSerialNumber'), compact('address'));
        return (array)$this->validateClient()->CheckModemAndCoverage($data)->CheckModemAndCoverageResult;
    }

    /**
     * returns modem info
     *
     * @param $serialNumber
     * @return array
     */
    public function validateSerialNumber($serialNumber)
    {
        return (array)$this->validateClient()->ValidateSerialNumber(compact('serialNumber'))->ValidateSerialNumberResult;
    }

    /**
     * returns bool
     *
     * @param $customerId
     * @param $maxZones
     * @return array
     */
    public function updateEquipment($customerId, $maxZones = 20)
    {
        return (array)$this->customerClient()->RequestUpdatedEquipmentList(compact('customerId', 'maxZones'))->RequestUpdatedEquipmentListResult;
    }


    /**
     * returns equipment list from panel
     *
     * @param $customerId
     * @return array
     */
    public function getEquipmentList($customerId)
    {
        return (array)$this->customerClient()->GetFullEquipmentList(compact('customerId'))->GetFullEquipmentListResult;
    }

    /**
     * requests sensor names from panel
     *
     * @param $customerId
     * @param $waitUntilPanelConnects
     * @return boolean
     */
    public function updateSensorNames($customerId, $waitUntilPanelConnects = true)
    {
        return (array)$this->customerClient()->RequestSensorNames(compact('customerId', 'waitUntilPanelConnects'))->RequestSensorNamesResult;
    }

    /**
     * requests signal history
     *
     * @param $customerId
     * @return array
     */
    public function getSignalHistory($customerId)
    {
        return (array)$this->customerClient()->GetSignalStrengthHistory(compact('customerId'))->GetSignalStrengthHistoryResult;
    }

    /**
     * returns panel version mappings
     *
     * @param $data
     * @return array
     */
    public function getPanelVersions()
    {
        return (array)$this->dealerClient()->GetPanelVersionMappings()->GetPanelVersionMappingsResult->PanelVersion;
    }

    /**
     * returns supported panel types
     *
     * @param $data
     * @return array
     */
    public function getPanelTypes()
    {
        return (array)$this->dealerClient()->GetSupportedPanelTypes()->GetSupportedPanelTypesResult->PanelTypeEnum;
    }

    /**
     * returns branches
     *
     * @param $data
     * @return array
     */
    public function getBranches()
    {
        return (array)$this->dealerClient()->GetBranches();
    }
    
    
//    public function getOptions()
//    {
//        $factory = new \Meng\AsyncSoap\Guzzle\Factory();
//        $client = $factory->create(new \GuzzleHttp\Client(), $this->config['wsdls']['dealer']);
//        return $client->call(
//            'GetPackageIds',
//            ['Security'],
//            [
//                'headers' => new \SoapHeader($this->config['namespace'], 'Authentication', $this->authentication),
//            ]
//        );
//    }


}
