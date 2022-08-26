<?php

namespace Weebly\Mutate\Mutators;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class UnixTimestampMutatorTest extends TestCase
{
    /**
     * @param  string  $carbon
     * @param  string  $expected
     * @dataProvider carbonProvider
     */
    public function testSerialize($carbon, $expected)
    {
        $this->assertEquals($expected, (new UnixTimestampMutator())->serializeAttribute($carbon));
    }

    /**
     * @param  string  $expected
     * @param  string  $binary
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
        $now = time();

        return [
            'Now' => [Carbon::createFromTimestamp($now), $now],
            'Timestamp 1' => [Carbon::createFromTimestamp(1), 1],
            'Timestamp for max uint32' => [Carbon::createFromTimestamp(pow(2, 32) - 1), pow(2, 32) - 1],
            'Negative timestamp' => [Carbon::createFromTimestamp(-200), -200],
            'Timestamp 0' => [Carbon::createFromTimestamp(0), 0],
        ];
    }
}
