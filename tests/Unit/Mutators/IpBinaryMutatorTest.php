<?php

namespace Weebly\Mutate\Mutators;

use PHPUnit\Framework\TestCase;

class IpBinaryMutatorTest extends TestCase
{
    /**
     * @param  string  $ip
     * @param  string  $expected
     * @dataProvider readableIpDataProvider
     */
    public function testSerializeAttribute($ip, $expected)
    {
        $this->assertEquals($expected, (new IpBinaryMutator())->serializeAttribute($ip));
    }

    /**
     * @param  string  $ip
     * @param  string  $expected
     * @dataProvider packedIpDataProvider
     */
    public function testUnserializeAttribute($ip, $expected)
    {
        $this->assertEquals($expected, (new IpBinaryMutator())->unserializeAttribute($ip));
    }

    /**
     * @return array
     */
    public function readableIpDataProvider()
    {
        return [
            'valid ipv4' => [
                '127.0.0.1',                    // IP
                inet_pton('127.0.0.1'),  // Expected
            ],
            'valid ipv6' => [
                'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff',
                inet_pton('ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff'),
            ],
        ];
    }

    /**
     * @return array
     */
    public function packedIpDataProvider()
    {
        return [
            'valid ipv4' => [
                inet_pton('127.0.0.1'),  // IP
                '127.0.0.1',                    // Expected
            ],
            'valid ipv6' => [
                inet_pton('ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff'),
                'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff',
            ],
        ];
    }
}
