<?php

namespace MRussell\REST\Tests\Endpoint\Traits;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Stream;
use MRussell\REST\Exception\Endpoint\InvalidFileData;
use MRussell\REST\Tests\Stubs\Endpoint\FileUploadEndpoint;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \MRussell\REST\Endpoint\Traits\FileUploadsTrait
 */
class FileUploadEndpointTest extends TestCase
{
    /**
     * @covers ::buildMultiPartFileData
     * @return void
     */
    public function testBuildMultiPartFileData()
    {
        $Endpoint = new FileUploadEndpoint();
        $Reflection = new \ReflectionClass($Endpoint);
        $buildMultiPartFileData = $Reflection->getMethod('buildMultiPartFileData');
        $buildMultiPartFileData->setAccessible(true);

        $this->assertEquals([
            'name' => 'foobar',
            'contents' => 'foobar'
        ], $buildMultiPartFileData->invoke($Endpoint, [
            'name' => 'foobar',
            'contents' => 'foobar'
        ]));
        $data = $buildMultiPartFileData->invoke($Endpoint, [
            'name' => 'foobar',
            'path' => __FILE__,
            'contents' => ''
        ]);
        $this->assertInstanceOf(Stream::class, $data['contents']);
        $this->assertEquals('foobar', $data['name']);
        $this->assertTrue(!isset($data['path']));
        $data = $buildMultiPartFileData->invoke($Endpoint, [
            'name' => 'foobar',
            'path' => __FILE__,
            'filename' => 'foobar.txt'
        ]);
        $this->assertInstanceOf(Stream::class, $data['contents']);
        $this->assertEquals('foobar', $data['name']);
        $this->assertTrue(!isset($data['path']));
        $this->assertTrue(array_key_exists('filename', $data));
        $this->assertEquals('foobar.txt', $data['filename']);

        $this->expectException(InvalidFileData::class);
        $buildMultiPartFileData->invoke($Endpoint, [
            'contents' => 'foobar'
        ]);
    }

    /**
     * @covers ::configureFileUploadRequest
     * @covers ::resetUpload
     * @depends testBuildMultiPartFileData
     * @return void
     */
    public function testConfigureFileUploadRequest()
    {
        $Request = new Request('POST', 'localhost/foobar');

        $Endpoint = new FileUploadEndpoint();
        $Reflection = new \ReflectionClass($Endpoint);
        $upload = $Reflection->getProperty('_upload');
        $upload->setAccessible(true);
        $upload->setValue($Endpoint, true);
        $configureFileUploadRequest = $Reflection->getMethod('configureFileUploadRequest');
        $configureFileUploadRequest->setAccessible(true);

        $request = $configureFileUploadRequest->invoke($Endpoint, $Request, [
            [
                'name' => 'foobar',
                'contents' => 'foobar'
            ]
        ]);
        $this->assertInstanceOf(Request::class, $request);
        $this->assertEmpty($request->getUri()->getQuery());
        $this->assertInstanceOf(MultipartStream::class, $request->getBody());
        $Endpoint->_queryParams = [
            'foo' => 'bar',
            'filter' => [
                [
                    'foo' => 'bar'
                ]
            ]
        ];
        $request = $configureFileUploadRequest->invoke($Endpoint, $Request, [
            'foobar' => __FILE__
        ]);
        $this->assertInstanceOf(Request::class, $request);
        $this->assertNotEmpty($request->getUri()->getQuery());
        $this->assertInstanceOf(MultipartStream::class, $request->getBody());
        $this->assertEquals($Endpoint, $Endpoint->resetUpload());
        $this->assertEquals(false, $upload->getValue($Endpoint));
    }


}
