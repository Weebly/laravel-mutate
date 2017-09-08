<?php

namespace Weebly\Mutate\Mutators;

use PHPUnit\Framework\TestCase;
use Weebly\Mutate\Exceptions\MutateException;

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
        for ($i = 0; $i < 10; $i++) {
            $bytes = random_bytes(20);
            yield [bin2hex($bytes), $bytes];
        }
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
            [new \stdClass],
            [0],
            [base64_encode(random_bytes(20))],
            [null],
            [true],
         ];
    }
}
