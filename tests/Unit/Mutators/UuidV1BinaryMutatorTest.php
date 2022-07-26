<?php

namespace Weebly\Mutate\Mutators;

use PHPUnit\Framework\TestCase;
use Weebly\Mutate\Exceptions\MutateException;

class UuidV1BinaryMutatorTest extends TestCase
{
    /**
     * @param  string  $uuid
     * @param  string  $expected
     * @dataProvider validUuidDataProvider
     */
    public function testSerializeValidUuid($uuid, $expected)
    {
        $this->assertEquals($expected, (new UuidV1BinaryMutator())->serializeAttribute($uuid));
    }

    /**
     * @param  string  $uuid
     * @param  string  $exception
     * @param  string  $exceptionMessage
     * @dataProvider invalidUuidDataProvider
     */
    public function testSerializeInvalidUuid($uuid, $exception, $exceptionMessage)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);

        (new UuidV1BinaryMutator())->serializeAttribute($uuid);
    }

    /**
     * @param  string  $value
     * @param  mixed  $expected
     * @dataProvider validOrderedUuidDataProvider
     */
    public function testUnserializeAttribute($value, $expected)
    {
        $this->assertEquals($expected, (new UuidV1BinaryMutator())->unserializeAttribute($value));
    }

    /**
     * @return array
     */
    public function validUuidDataProvider()
    {
        return [
            'uuidv1 hex with dashes' => [
                '61d10c04-86c5-11e7-b2db-807de02e3838',          // UUID
                hex2bin('11e786c561d10c04b2db807de02e3838'), // Expected
            ],
            'uuidv1 hex without dashes' => [
                '61d10c0486c511e7b2db807de02e3838',
                hex2bin('11e786c561d10c04b2db807de02e3838'),
            ],
            'uuidv1 binary' => [
                hex2bin('61d10c0486c511e7b2db807de02e3838'),
                hex2bin('11e786c561d10c04b2db807de02e3838'),
            ],
        ];
    }

    /**
     * @return array
     */
    public function validOrderedUuidDataProvider()
    {
        return [
            'ordered binary uuidv1' => [
                hex2bin('11e786c561d10c04b2db807de02e3838'), // UUID
                '61d10c04-86c5-11e7-b2db-807de02e3838',           // Expected
            ],
            'unordered binary uuidv1' => [
                hex2bin('61d10c0486c511e7b2db807de02e3838'),
                null,
            ],
            'unordered hex uuidv1 with dashes' => [
                '61d10c04-86c5-11e7-b2db-807de02e3838',
                null,
            ],
            'unordered hex uuidv1 without dashes' => [
                '61d10c0486c511e7b2db807de02e3838',
                null,
            ],
            'invalid binary string' => [
                hex2bin('666f6f626172'),
                null,
            ],
            'null uuid' => [
                null,
                null,
            ],
        ];
    }

    /**
     * @return array
     */
    public function invalidUuidDataProvider()
    {
        return [
            'uuidv3 hex with dashes' => [
                '5df41881-3aed-3515-88a7-2f4a814cf09e', // UUID
                MutateException::class,                 // Expected exception
                'Incorrect UUID version',               // Expected exception message
            ],
            'uuidv3 hex without dashes' => [
                '5df418813aed351588a72f4a814cf09e',
                MutateException::class,
                'Incorrect UUID version',
            ],
            'uuidv3 binary' => [
                hex2bin('5df418813aed351588a72f4a814cf09e'),
                MutateException::class,
                'Incorrect UUID version',
            ],
            'uuidv4' => [
                'b86d458c-a811-478b-96d6-ba41c8fdbab8',
                MutateException::class,
                'Incorrect UUID version',
            ],
            'uuidv4 hex without dashes' => [
                'b86d458ca811478b96d6ba41c8fdbab8',
                MutateException::class,
                'Incorrect UUID version',
            ],
            'uuidv4 binary' => [
                hex2bin('b86d458ca811478b96d6ba41c8fdbab8'),
                MutateException::class,
                'Incorrect UUID version',
            ],
            'uuidv5' => [
                '2ed6657d-e927-568b-95e1-2665a8aea6a2',
                MutateException::class,
                'Incorrect UUID version',
            ],
            'uuidv5 hex without dashes' => [
                '2ed6657de927568b95e12665a8aea6a2',
                MutateException::class,
                'Incorrect UUID version',
            ],
            'uuidv5 binary' => [
                hex2bin('2ed6657de927568b95e12665a8aea6a2'),
                MutateException::class,
                'Incorrect UUID version',
            ],
            'invalid UUID' => [
                'foobar',
                MutateException::class,
                'Cannot serialize invalid UUID: foobar',
            ],
        ];
    }
}
