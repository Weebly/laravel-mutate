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
 * @group mysql
 */
class BelongsToManyMutatedTest extends TestCase
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
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'circle-test'),
            'username' => env('DB_USERNAME', 'mutate'),
            'password' => env('DB_PASSWORD', 'secret'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
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
        DB::connection()->getPdo()->exec('DROP TABLE IF EXISTS test_model_a');
        DB::connection()->getPdo()->exec('DROP TABLE IF EXISTS test_model_b');
        DB::connection()->getPdo()->exec('DROP TABLE IF EXISTS pivot_table');

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
        $idC = Uuid::uuid1()->toString();
        $modelA = (new TestModelA())->create(['id' => $idA, 'name' => 'A table']);
        $modelB = (new TestModelB())->create(['id' => $idB, 'name' => 'B table']);
        $modelC = (new TestModelB())->create(['id' => $idC, 'name' => 'B table']);

        $modelA->testModelBs()->attach(TestModelB::all(), ['extra' => 'Something Extra']);
        $this->assertEquals(2, $modelA->testModelBs()->count());
    }

    public function testRelationAccessorOnNewRecord()
    {
        $modelA = new TestModelA();

        $this->assertEquals(0, $modelA->testModelBs->count());
    }

    public function testEagerLoadRelationsMatchesProperly()
    {
        $idA1 = Uuid::uuid1()->toString();
        $idA2 = Uuid::uuid1()->toString();
        $idB1 = Uuid::uuid1()->toString();
        $idB2 = Uuid::uuid1()->toString();
        $modelA1 = (new TestModelA())->create(['id' => $idA1, 'name' => 'A table 1']);
        $modelA2 = (new TestModelA())->create(['id' => $idA2, 'name' => 'A table 2']);
        $modelB1 = (new TestModelB())->create(['id' => $idB1, 'name' => 'B table 1']);
        $modelB2 = (new TestModelB())->create(['id' => $idB2, 'name' => 'B table 2']);

        $modelA1->testModelBs()->attach($modelB1);
        $modelA2->testModelBs()->attach($modelB2);

        $results = TestModelA::where('name', 'LIKE', 'A table %')->with('testModelBs')->get()->toArray();
        $this->assertEquals($idB1, $results[0]['test_model_bs'][0]['id']);
        $this->assertEquals($idB2, $results[1]['test_model_bs'][0]['id']);
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
    protected $table = 'test_model_b';

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
