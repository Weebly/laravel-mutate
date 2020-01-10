<?php

namespace Weebly\Mutate\Mutators;

use Illuminate\Contracts\Encryption\Encrypter;
use Mockery as M;
use PHPUnit\Framework\TestCase;

class EncryptStringMutatorTest extends TestCase
{
    public function testSerializeAttribute()
    {
        $plainText = 'foo string';
        $encrypted = 'eyJpdiI6Ikk1UEdLam9pOUs3M0V1dDZqZlJQd1E9PSIsInZhbHVlIjoicFcxVnFPME8xQkptekN6XC94QlpcL3lvSFFrcGVSVU9HaGhOSVh3UFdybVVRPSIsIm1hYyI6IjkwYWFlZjVlMTE1YTkyNmIyNDA4NzQ4MjViNzE1NzA2MTQ4NjE0OGRmMTc0ZmVlZGJlNjczNTdiYzBlZDkxOTcifQ==';
        $encrypt = M::mock(Encrypter::class)
            ->shouldReceive('encrypt')
            ->with($plainText, false)
            ->andReturn($encrypted)
            ->once()
            ->getMock();

        $this->assertEquals($encrypted, (new EncryptStringMutator($encrypt))->serializeAttribute($plainText));
    }

    public function testUnserializeAttribute()
    {
        $plainText = 'foo string';
        $encrypted = 'eyJpdiI6Ikk1UEdLam9pOUs3M0V1dDZqZlJQd1E9PSIsInZhbHVlIjoicFcxVnFPME8xQkptekN6XC94QlpcL3lvSFFrcGVSVU9HaGhOSVh3UFdybVVRPSIsIm1hYyI6IjkwYWFlZjVlMTE1YTkyNmIyNDA4NzQ4MjViNzE1NzA2MTQ4NjE0OGRmMTc0ZmVlZGJlNjczNTdiYzBlZDkxOTcifQ==';
        $encrypt = M::mock(Encrypter::class)
            ->shouldReceive('decrypt')
            ->with($encrypted, false)
            ->andReturn($plainText)
            ->once()
            ->getMock();

        $this->assertEquals($plainText, (new EncryptStringMutator($encrypt))->unserializeAttribute($encrypted));
    }
}
