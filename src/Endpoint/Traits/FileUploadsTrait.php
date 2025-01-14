<?php

namespace MRussell\REST\Endpoint\Traits;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use MRussell\REST\Exception\Endpoint\InvalidFileData;

trait FileUploadsTrait
{
    /**
     * Whether or not a file upload is occurring
     */
    protected bool $_upload = false;

    /**
     * @return $this
     */
    public function resetUpload()
    {
        $this->_upload = false;
        return $this;
    }

    /**
     * Configure File Upload Request using Multipart Data
     */
    protected function configureFileUploadRequest(Request $request, array $filesData = []): Request
    {
        $uri = $request->getUri();
        $request = $request->withUri($uri->withQuery(\http_build_query($this->configureFileUploadQueryParams(), '', '&', \PHP_QUERY_RFC3986)));
        $multiPartOptions = [];
        foreach ($filesData as $key => $fileData) {
            if (is_string($key) && is_string($fileData)) {
                $fileData = [
                    'name' => $key,
                    'path' => $fileData,
                ];
            }

            $multiPartOptions[] = $this->buildMultiPartFileData($fileData);
        }

        $data = new MultipartStream($multiPartOptions);
        $request = $request->withBody($data);
        return $request->withHeader('Content-Type', 'multipart/form-data; boundary=' . $data->getBoundary());
    }

    /**
     * Method to override to
     */
    abstract protected function configureFileUploadQueryParams(): array;

    /**
     * Array containing preformatted multipart options for file upload, or containing at least 'path'
     */
    protected function buildMultiPartFileData(array $fileData): array
    {
        if (!empty($fileData['name']) && (isset($fileData['path']) || isset($fileData['contents']))) {
            $data = [];
            if (isset($fileData['path']) && file_exists($fileData['path'])) {
                if (isset($fileData['contents'])) {
                    unset($fileData['contents']);
                }

                $data['contents'] = Utils::streamFor(fopen($fileData['path'], 'r', true));
                unset($fileData['path']);
            }

            return array_merge($data, $fileData);
        }

        throw new InvalidFileData([print_r($fileData, true)]);
    }
}
