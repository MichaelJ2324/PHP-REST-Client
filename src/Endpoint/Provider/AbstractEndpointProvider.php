<?php

namespace MRussell\REST\Endpoint\Provider;

use MRussell\REST\Endpoint\Interfaces\EndpointInterface;
use MRussell\REST\Exception\Endpoint\InvalidRegistration;
use MRussell\REST\Exception\Endpoint\UnknownEndpoint;

abstract class AbstractEndpointProvider implements EndpointProviderInterface
{
    /**
     * @var array
     */
    protected $registry = array();

    /**
     * @inheritdoc
     * @throws InvalidRegistration
     */
    public function registerEndpoint($name, $className, array $properties = array())
    {
        try{
            $implements = class_implements($className);
            if (is_array($implements) && isset($implements['MRussell\REST\Endpoint\Interfaces\EndpointInterface'])) {
                $this->registry[$name] = array(
                    'class' => $className,
                    'properties' => $properties
                );
                return $this;
            }
        } catch(\Exception $ex){
            //Class Implements failed to Load Class completely
        }
        throw new InvalidRegistration($className);
    }

    /**
     * @inheritdoc
     */
    public function hasEndpoint($name, $version = NULL) {
        return array_key_exists($name,$this->registry);
    }

    /**
     * @inheritdoc
     * @throws UnknownEndpoint
     */
    public function getEndpoint($name, $version = NULL) {
        if ($this->hasEndpoint($name,$version)){
            return $this->buildEndpoint($name,$version);
        }else{
            throw new UnknownEndpoint($name);
        }
    }

    /**
     * @param $name
     * @param null $version
     * @return EndpointInterface
     */
    protected function buildEndpoint($name,$version = NULL){
        $endPointArray = $this->registry[$name];
        $Class = $endPointArray['class'];
        $properties = $endPointArray['properties'];
        $Endpoint = new $Class();
        if (!empty($properties)){
            foreach($properties as $prop => $value){
                $Endpoint->setProperty($prop,$value);
            }
        }
        return $Endpoint;
    }
}