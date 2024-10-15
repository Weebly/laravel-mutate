<?php

namespace Weebly\Mutate\Database;

use Mockery as M;
use PHPUnit\Framework\TestCase;
use Weebly\Mutate\Mutators\MutatorContract;

class HasMutatorsTest extends TestCase
{
    public function testSerializeAttribute()
    {
        $uuid = 'cf98906e-9074-11e7-9c8e-437b4bab8527';
        $mutator = M::mock(MutatorContract::class)
            ->shouldReceive('get')
            ->with('test_mutator')
            ->andReturnSelf()
            ->once()
            ->shouldReceive('serializeAttribute')
            ->with($uuid)
            ->once()
            ->getMock();

        app()['mutator'] = $mutator;

        $model = new SampleModel();
        $model->id = $uuid;

        $this->assertEquals($uuid, $model->id);
    }

    public function testUnserializeAttribute()
    {
        $mutator = M::mock(MutatorContract::class)
            ->shouldReceive('get')
            ->with('test_mutator')
            ->andReturnSelf()
            ->once()
            ->shouldReceive('unserializeAttribute')
            ->with('unserialized_attribute')
            ->andReturn('serialized_attribute')
            ->once()
            ->getMock();

        app()['mutator'] = $mutator;

        $model = new SampleModel();
        $this->assertEquals('serialized_attribute', $model->id);

        // Verify twice to ensure the second run is using cache
        $this->assertEquals('serialized_attribute', $model->id);
    }

    public function testGetOriginal()
    {
        $mutator = M::mock(MutatorContract::class)
            ->shouldReceive('get')
            ->with('test_mutator')
            ->andReturnSelf()
            ->once()
            ->shouldReceive('unserializeAttribute')
            ->with('unserialized_attribute')
            ->andReturn('serialized_attribute')
            ->once()
            ->getMock();

        app()['mutator'] = $mutator;

        $model = new SampleModel();
        $original = $model->getOriginal();

        $this->assertIsArray($original);
        $this->assertEquals('serialized_attribute', $original['id']);
    }

    public function testGetOriginalProperty()
    {
        $mutator = M::mock(MutatorContract::class)
            ->shouldReceive('get')
            ->with('test_mutator')
            ->andReturnSelf()
            ->once()
            ->shouldReceive('unserializeAttribute')
            ->with('unserialized_attribute')
            ->andReturn('serialized_attribute')
            ->once()
            ->getMock();

        app()['mutator'] = $mutator;

        $model = new SampleModel();
        $this->assertEquals('serialized_attribute', $model->getOriginal('id'));
    }

    public function testGetMutators()
    {
        $this->assertEquals(['id' => 'test_mutator'], (new SampleModel())->getMutators());
    }

    public function testGetMutator()
    {
        $this->assertEquals('test_mutator', (new SampleModel())->getMutator('id'));
    }

    public function testHasMutator()
    {
        $this->assertTrue((new SampleModel())->hasMutator('id'));
        $this->assertFalse((new SampleModel())->hasMutator('foo'));
    }
}

class SampleModel extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $mutate = [
        'id' => 'test_mutator',
    ];

    /**
     * {@inheritdoc}
     */
    protected $attributes = [
        'id' => 'unserialized_attribute',
    ];

    /**
     * {@inheritdoc}
     */
    protected $keyType = 'string';

    /**
     * {@inheritdoc}
     */
    public $incrementing = false;
}
