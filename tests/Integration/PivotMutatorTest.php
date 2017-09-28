<?php

namespace Weebly\Mutate;

use DB;
use Ramsey\Uuid\Uuid;
use Orchestra\Testbench\TestCase;
use Weebly\Mutate\Database\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;

/**
 * @group integration
 */
class PivotMutatorTest extends TestCase
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
            $mutator = new MutatorProvider();
            $default_mutators = (require realpath(__DIR__.'/../../config/config.php'))['enabled'];

            $mutator->registerMutators($default_mutators);

            return $mutator;
        });
    }

    public function setUp()
    {
        parent::setUp();

        Schema::create('test_model_a', function ($table) {
            $table->string('name');
        });
        DB::connection()->getPdo()->exec('ALTER TABLE test_model_a ADD id BINARY(16);');

        Schema::create('test_model_b', function ($table) {
            $table->string('name');
        });
        DB::connection()->getPdo()->exec('ALTER TABLE test_model_b ADD id BINARY(16);');

        Schema::create('pivot_table', function ($table) {
            $table->string('extra')->nullable();
        });
        DB::connection()->getPdo()->exec('ALTER TABLE pivot_table ADD a_id BINARY(16);');
        DB::connection()->getPdo()->exec('ALTER TABLE pivot_table ADD b_id BINARY(16);');
    }

    public function testPivotRecordsGetCreated()
    {
        $idA = Uuid::uuid1()->toString();
        $idB = Uuid::uuid1()->toString();
        $modelA = (new TestModelA())->create(['id' => $idA, 'name' => 'A table']);
        $modelB = (new TestModelB())->create(['id' => $idB, 'name' => 'B table']);

        $modelA->testModelBs()->attach($modelB, ['extra' => 'Something Extra']);

        $this->assertEquals(1, $modelA->testModelBs()->count());
    }
}

class TestModelA extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'test_model_a';

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    public $incrementing = false;

    /**
     * {@inheritdoc}
     */
    protected $keyType = 'string';

    /**
     * {@inheritdoc}
     */
    protected $mutate = [
        'id' => 'uuid_v1_binary',
    ];

    public function testModelBs()
    {
        return $this->belongsToMany(TestModelB::class, 'pivot_table', 'a_id', 'b_id')->withPivot('extra');
    }
}

class TestModelB extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'test_model_a';

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    public $incrementing = false;

    /**
     * {@inheritdoc}
     */
    protected $keyType = 'string';

    /**
     * {@inheritdoc}
     */
    protected $mutate = [
        'id' => 'uuid_v1_binary',
    ];

    public function testModelAs()
    {
        return $this->belongsToMany(TestModelA::class, 'pivot_table', 'b_id', 'a_id')->withPivot('extra');
    }
}
