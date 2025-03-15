<?php

namespace Tests\E2E;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FileStorageTest extends TestCase
{
    protected static array $store = [];

    #[Test]
    #[Depends('it_should_return_file_when_pulling_existing_file')]
    public function it_should_delete_a_file_successfully()
    {
        [$filename] = self::$store;

        $response = self::$http->delete('/delete', [
            'query' => ['filename' => $filename]
        ]);

        $this->assertEquals(201, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('removed', $body);
        $this->assertArrayHasKey('filename', $body['removed']);
        $this->assertArrayHasKey('datetime', $body['removed']);
    }


    #[Test]
    public function it_should_return_error_when_file_not_found_in_pull()
    {
        $response = self::$http->get('/pull', [
            'query' => ['filename' => 'non_existing_file.jpg']
        ]);

        $this->assertEquals(404, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('error', $body);
        $this->assertEquals('File not found', $body['error']['message']);
        $this->assertEquals('NOT_FOUND', $body['error']['code']);
    }

    #[Test]
    #[Depends('it_should_upload_a_file_successfully')]
    public function it_should_return_file_when_pulling_existing_file()
    {
        [$filename] = self::$store;

        $response = self::$http->get('/pull', [
            'query' => ['filename' => $filename]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('image/webp', $response->getHeaderLine('Content-Type'));
    }

    #[Test]
    public function it_should_upload_a_file_successfully()
    {
        $response = self::$http->post('/push', [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen(__DIR__ . '/image/cat.webp', 'r'),
                    'filename' => 'test.jpg'
                ],
                [
                    'name' => 'visibility',
                    'contents' => 'public'
                ],
                [
                    'name' => 'sizes[]',
                    'contents' => '400x400'
                ]
            ]
        ]);

        $this->assertEquals(201, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('file_url', $body['data']);
        $this->assertArrayHasKey('filename', $body['data']);
        $this->assertArrayHasKey('datetime', $body['data']);

        self::$store[] = $body['data']['filename'];
    }

    #[Test]
    #[Depends('it_should_upload_a_file_successfully')]
    public function it_should_return_file_info_successfully()
    {
        [$filename] = self::$store;

        $response =  self::$http->get('/info', [
            'query' => ['filename' => $filename]
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('mime_type', $body);
        $this->assertEquals('image/webp', $body['mime_type']);

        $this->assertArrayHasKey('meta', $body);
        $this->assertArrayHasKey('Device', $body['meta']);
        $this->assertArrayHasKey('Inode', $body['meta']);
        $this->assertArrayHasKey('Permissions', $body['meta']);
        $this->assertArrayHasKey('Links', $body['meta']);
        $this->assertArrayHasKey('Owner (UID)', $body['meta']);
        $this->assertArrayHasKey('Group (GID)', $body['meta']);
        $this->assertArrayHasKey('Device Type', $body['meta']);
        $this->assertArrayHasKey('Size (bytes)', $body['meta']);
        $this->assertArrayHasKey('Last Access', $body['meta']);
        $this->assertArrayHasKey('Last Modification', $body['meta']);
        $this->assertArrayHasKey('Last Status Change', $body['meta']);

        $this->assertArrayHasKey('size', $body);
        $this->assertStringEndsWith('bytes', $body['size']);

        $this->assertArrayHasKey('extra', $body);
        $this->assertArrayHasKey('dirname', $body['extra']);
        $this->assertArrayHasKey('basename', $body['extra']);
        $this->assertArrayHasKey('extension', $body['extra']);
        $this->assertArrayHasKey('filename', $body['extra']);
    }
}
