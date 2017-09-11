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
            'Hex string of length 2' => 'e7',
            'Hex string of length 4' => '232f',
            'Hex string of length 6' => '0b76e0',
            'Hex string of length 8' => '65c1b7b0',
            'Hex string of length 10' => 'e2f9995c0b',
            'Hex string of length 12' => 'cfc46177b9cd',
            'Hex string of length 14' => '01f4890a5996c1',
            'Hex string of length 16' => 'd8d3a96d2a441b39',
            'Hex string of length 18' => '5d87fca1c8f5c2ec21',
            'Hex string of length 20' => 'c334821e50532bd40227',
        ];

        foreach ($hexes as $label => $hex) {
            $hexes[$label] = [$hex, hex2bin($hex)];
        }

        return $hexes;
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
            'An object cannot be serialized' => [new stdClass],
            'An int cannot be serialized' => [0],
            'Floats cannot be serialized' => [0.3],
            'Only hex strings can be serialiazed' => ['YzMzNDgyMWU1MDUzMmJkNDAy'],
            'Null cannot be serialized' => [null],
            'Boolas cannot be serialized' => [true],
            'Hex strings should have even lengths to be valid bytes representations' => ['a'],
         ];
    }
}
