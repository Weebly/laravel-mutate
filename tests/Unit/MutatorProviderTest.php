<?php

namespace Weebly\Mutator;

use Mockery as M;
use PHPUnit\Framework\TestCase;
use Weebly\Mutate\Mutators\AbstractMutator;
use Weebly\Mutate\MutatorProvider;

class MutatorProviderTest extends TestCase
{
    public function testGet()
    {
        $mutator = M::mock(AbstractMutator::class);

        $provider = new MutatorProvider();
        $provider->set('test_mutator', $mutator);

        $this->assertSame($mutator, $provider->get('test_mutator'));
    }

    public function testSet()
    {
        $mutator = M::mock(AbstractMutator::class);

        $provider = new MutatorProvider();
        $this->assertSame($provider, $provider->set('test_mutator', $mutator));
    }

    public function testExists()
    {
        $mutator = M::mock(AbstractMutator::class);

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
        $provider->set('test_mutator', M::mock(AbstractMutator::class));

        $this->assertTrue(isset($provider['test_mutator']));
    }

    public function testOffsetGet()
    {
        $provider = new MutatorProvider();
        $provider->set('test_mutator', M::mock(AbstractMutator::class));

        $this->assertTrue(isset($provider['test_mutator']));
    }

    public function testOffsetSet()
    {
        $provider = new MutatorProvider();
        $provider['test_mutator'] = M::mock(AbstractMutator::class);

        $this->assertTrue(isset($provider['test_mutator']));
    }

    public function testOffsetUnset()
    {
        $provider = new MutatorProvider();
        $provider->set('test_mutator', M::mock(AbstractMutator::class));

        unset($provider['test_mutator']);

        $this->assertFalse(isset($provider['test_mutator']));
    }
}

class SampleMutator extends AbstractMutator
{
    /**
     * {@inheritDoc}
     */
    protected $name = 'test_mutator';

    /**
     * @inheritDoc
     */
    public function serializeAttribute($value)
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function unserializeAttribute($value)
    {
        return;
    }
}
