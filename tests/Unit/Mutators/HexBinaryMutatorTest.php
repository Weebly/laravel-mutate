<?php

namespace Weebly\Mutate\Mutators;

use PHPUnit\Framework\TestCase;
use stdClass;

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
            'Hex string of length 2' => ['e7', hex2bin('e7')],
            'Hex string of length 4' => ['232f', hex2bin('232f')],
            'Hex string of length 6' => ['0b76e0', hex2bin('0b76e0')],
            'Hex string of length 8' => ['65c1b7b0', hex2bin('65c1b7b0')],
            'Hex string of length 10' => ['e2f9995c0b', hex2bin('e2f9995c0b')],
            'Hex string of length 12' => ['cfc46177b9cd', hex2bin('cfc46177b9cd')],
            'Hex string of length 14' => ['01f4890a5996c1', hex2bin('01f4890a5996c1')],
            'Hex string of length 16' => ['d8d3a96d2a441b39', hex2bin('d8d3a96d2a441b39')],
            'Hex string of length 18' => ['5d87fca1c8f5c2ec21', hex2bin('5d87fca1c8f5c2ec21')],
            'Hex string of length 20' => ['c334821e50532bd40227', hex2bin('c334821e50532bd40227')],
        ];

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
            'Booleans cannot be serialized' => [true],
            'Hex strings should have even lengths to be valid bytes representations' => ['a'],
        ];
    }
}
