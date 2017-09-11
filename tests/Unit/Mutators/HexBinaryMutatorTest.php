<?php

namespace Weebly\Mutate\Mutators;

use stdClass;
use PHPUnit\Framework\TestCase;

class HexBinaryMutatorTest extends TestCase
{
    /**
     * @param string $hex
     * @param string $expected
     * @dataProvider hexProvider
     */
    public function testSerialize($hex, $expected)
    {
        $this->assertEquals($expected, (new HexBinaryMutator())->serializeAttribute($hex));
    }

    /**
     * @param string $expected
     * @param string $binary
     * @dataProvider hexProvider
     */
    public function testUnserialize($expected, $binary)
    {
        $this->assertEquals($expected, (new HexBinaryMutator())->unserializeAttribute($binary));
    }

    /**
     * @return \Iterator
     */
    public function hexProvider()
    {
        $hexes = [
            'e7',
            '232f',
            '0b76e0',
            '65c1b7b0',
            'e2f9995c0b',
            'cfc46177b9cd',
            '01f4890a5996c1',
            'd8d3a96d2a441b39',
            '5d87fca1c8f5c2ec21',
            'c334821e50532bd40227',
        ];

        return array_map(function ($hex) {
            return [$hex, hex2bin($hex)];
        }, $hexes);
    }

    /**
     * @param mixed $value
     * @dataProvider notHexProvider
     * @expectedException \Weebly\Mutate\Exceptions\MutateException
     */
    public function testWrongFormat($value)
    {
        (new HexBinaryMutator())->serializeAttribute($value);
    }

    /**
     * @return array
     */
    public function notHexProvider()
    {
        return [
            [new stdClass], // Cannot serialize an object
            [0], // Cannot serialize an int
            [0.3], // Cannot serialize a float
            ['YzMzNDgyMWU1MDUzMmJkNDAy'], // Can't serialize a string that contains non-hex digits
            [null], // Null cannot pass
            [true], // Bools also not ok as hex data
            ['a'], // Length must be even for a hex string to be representing bytes
         ];
    }
}
