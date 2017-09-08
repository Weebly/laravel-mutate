<?php

namespace Weebly\Mutate\Mutators;

use PHPUnit\Framework\TestCase;
use Weebly\Mutate\Exceptions\MutateException;
use Carbon\Carbon;

class UnixTimestampMutatorTest extends TestCase
{
    /**
     * @param string $carbon
     * @param string $expected
     * @dataProvider carbonProvider
     */
    public function testSerialize($carbon, $expected)
    {
        $this->assertEquals($expected, (new UnixTimestampMutator())->serializeAttribute($carbon));
    }

    /**
     * @param string $expected
     * @param string $binary
     * @dataProvider carbonProvider
     */
    public function testUnserialize($expected, $timestamp)
    {
        $unserialized = (new UnixTimestampMutator())->unserializeAttribute($timestamp);
        $this->assertEquals($expected->year, $unserialized->year);
        $this->assertEquals($expected->month, $unserialized->month);
        $this->assertEquals($expected->day, $unserialized->day);
        $this->assertEquals($expected->hour, $unserialized->hour);
        $this->assertEquals($expected->minute, $unserialized->minute);
        $this->assertEquals($expected->second, $unserialized->second);
    }

    /**
     * @return \Iterator
     */
    public function carbonProvider()
    {
        yield [Carbon::now(), time()];
        for ($i = 0; $i < 10; $i++) {
            $t = rand(1, time());
            $c = Carbon::createFromTimestamp($t);
            yield [$c, $t];
        }
    }
}
