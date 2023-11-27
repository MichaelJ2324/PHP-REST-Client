<?php

namespace MRussell\REST\Exception\Client;

class EndpointProviderMissing extends ClientException
{
    protected $message = 'Endpoint Provider not configured on Client object.';
}
