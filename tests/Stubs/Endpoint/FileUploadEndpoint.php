<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\SmartEndpoint;
use MRussell\REST\Endpoint\Traits\FileUploadsTrait;

class FileUploadEndpoint extends SmartEndpoint
{
    use FileUploadsTrait;

    public array $_queryParams = [];

    protected function configureFileUploadQueryParams(): array
    {
        return $this->_queryParams;
    }
}
