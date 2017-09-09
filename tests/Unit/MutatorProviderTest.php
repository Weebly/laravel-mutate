<?php

namespace Weebly\Mutator;

use Mockery as M;
use PHPUnit\Framework\TestCase;
use Weebly\Mutate\MutatorProvider;
use Weebly\Mutate\Mutators\MutatorContract;

class MutatorProviderTest extends TestCase
{
    public function testGet()
    {
        $mutator = M::mock(MutatorContract::class);

        $provider = new MutatorProvider();
        $provider->set('test_mutator', $mutator);

        $this->assertSame($mutator, $provider->get('test_mutator'));
    }

    public function testSet()
    {
        $mutator = M::mock(MutatorContract::class);

        $provider = new MutatorProvider();
        $this->assertSame($provider, $provider->set('test_mutator', $mutator));
    }

    public function testExists()
    {
        $mutator = M::mock(MutatorContract::class);

        $provider = new MutatorProvider();
        $provider->set('test_mutator', $mutator);

        $this->assertTrue($provider->exists('test_mutator'));
    }

    public function testRegisterMutators()
    {
        $provider = new MutatorProvider();
        $provider->registerMutators(['test_mutator' => new SampleMutator()]);

        $this->assertTrue($provider->exists('test_mutator'));
    }

    public function testOffsetExists()
    {
        $provider = new MutatorProvider();
        $provider->set('test_mutator', M::mock(MutatorContract::class));

        $this->assertTrue(isset($provider['test_mutator']));
    }

    public function testOffsetGet()
    {
        $provider = new MutatorProvider();
        $provider->set('test_mutator', M::mock(MutatorContract::class));

        $this->assertTrue(isset($provider['test_mutator']));
    }

    public function testOffsetSet()
    {
        $provider = new MutatorProvider();
        $provider['test_mutator'] = M::mock(MutatorContract::class);

        $this->assertTrue(isset($provider['test_mutator']));
    }

    public function testOffsetUnset()
    {
        $provider = new MutatorProvider();
        $provider->set('test_mutator', M::mock(MutatorContract::class));

        unset($provider['test_mutator']);

        $this->assertFalse(isset($provider['test_mutator']));
    }
}

class SampleMutator implements MutatorContract
{
    /**
     * {@inheritdoc}
     */
    public function serializeAttribute($value)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function unserializeAttribute($value)
    {
    }
}
