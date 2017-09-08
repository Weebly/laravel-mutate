<?php

namespace Weebly\Mutate;

use DB;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use Ramsey\Uuid\Uuid;
use Weebly\Mutate\Database\Model;

/**
 * @group integration
 */
class MutatorTest extends TestCase
{
    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:+oDiXVEBRapl1N6RWgVx/xdzJ4aXgSAsD6QbAYmNE8A=');
        $app['config']->set('app.cipher', 'AES-256-CBC');
        $app['config']->set('app.debug', 'true');
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app->singleton('mutator', function (Application $app) {
            $mutator  = new MutatorProvider();
            $default_mutators = require(realpath(__DIR__.'/../../config/config.php'));
            $default_mutators = $default_mutators['enabled'];

            $mutator->registerMutators($default_mutators);

            return $mutator;
        });

    }

    public function setUp()
    {
        parent::setUp();

        Schema::create('test_model', function ($table) {
            $table->string('name');
            $table->string('location')->nullable();
        });

        DB::connection()->getPdo()->exec('ALTER TABLE test_model ADD id BINARY(16);');

        Schema::create('timestamped_model', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('created_at');
            $table->integer('updated_at');
        });
    }

    public function test_where()
    {
        $id    = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id, 'name' => 'A table']);
        $p     = $model->find($id);
        $this->assertEquals($id, $p->id);

        $p = DB::table('test_model')->where('id', $id)->first();
        $this->assertNull($p);
    }

    public function test_find()
    {
        $id    = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id, 'name' => 'A table']);
        $p     = $model->find($id);
        $this->assertEquals($id, $p->id);
    }

    public function test_non_mutated_columns()
    {
        $id    = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id, 'name' => 'abc']);
        $p     = $model->where('name', 'abc')->first();
        $this->assertEquals($id, $p->id);
    }

    public function test_where_in()
    {
        $id    = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id, 'name' => 'A chair']);
        $p     = $model->whereIn('id', [$id])->first();
        $this->assertEquals($id, $p->id);
    }

    public function test_update()
    {
        $id    = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id, 'name' => 'A chair', 'location' => 'Foo']);
        $p1    = (new TestModel())->whereIn('id', [$id])->first();

        // Update the attribute
        $model->location = 'Bar';
        $model->save();

        $this->assertEquals('Foo', $p1->location);
        $this->assertEquals('Bar', (new TestModel())->whereIn('id', [$id])->first()->location);
    }

    public function test_pluck()
    {
        $id = Uuid::uuid1()->toString();
        (new TestModel())->create(['id' => $id, 'name' => 'A chair', 'location' => 'Foo'])->save();
        $ids = TestModel::where('id', $id)->pluck('id')->toArray();
        $this->assertEquals([$id], $ids);
    }

    public function test_pluck_with_key()
    {
        $id = Uuid::uuid1()->toString();
        $location = 'Foo';
        (new TestModel())->create(['id' => $id, 'name' => 'A chair', 'location' => $location])->save();
        $ids = TestModel::where('id', $id)->pluck('id', 'location')->toArray();
        $this->assertEquals([$location => $id], $ids);
    }

    public function test_timestamps()
    {
        (new TimestampedModel())->create(['name' => 'Model']);
        $model = TimestampedModel::first();
        $this->assertInstanceOf('Carbon\Carbon', $model->created_at);
        $this->assertInstanceOf('Carbon\Carbon', $model->updated_at);

        $values = \DB::table('timestamped_model')->first();
        $this->assertTrue(ctype_digit($values->created_at));
        $this->assertTrue(ctype_digit($values->updated_at));
    }
}

class TestModel extends Model
{
    /**
     * {@inheritDoc}
     */
    protected $table = 'test_model';

    /**
     * {@inheritDoc}
     */
    protected $guarded = [];

    /**
     * {@inheritDoc}
     */
    public $timestamps = false;

    /**
     * {@inheritDoc}
     */
    public $incrementing = false;

    /**
     * {@inheritDoc}
     */
    protected $keyType = 'string';

    /**
     * {@inheritDoc}
     */
    protected $mutate = [
        'id'       => 'uuid_v1_binary',
        'location' => 'encrypt_string',
    ];
}

class TimestampedModel extends Model
{
    protected $table = 'timestamped_model';

    protected $guarded = [];

    protected $mutate = [
        'created_at' => 'unix_timestamp',
        'updated_at' => 'unix_timestamp',
    ];
}
