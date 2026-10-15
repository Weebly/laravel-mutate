<?php

namespace Weebly\Mutate;

use DB;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use Ramsey\Uuid\Uuid;
use Weebly\Mutate\Database\Eloquent\Relations\Pivot;
use Weebly\Mutate\Database\Model;

class PivotTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
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

    public function setUp(): void
    {
        parent::setUp();

        Schema::create('first_model', function ($table) {
            $table->string('name');
        });
        Schema::create('second_model', function ($table) {
            $table->string('name');
        });
        Schema::create('first_model_second_model', function ($table) {
            $table->string('extra')->nullable();
        });

        DB::connection()->getPdo()->exec('ALTER TABLE first_model ADD id BINARY(16);');
        DB::connection()->getPdo()->exec('ALTER TABLE second_model ADD id BINARY(16);');
        DB::connection()->getPdo()->exec('ALTER TABLE first_model_second_model ADD first_model_id BINARY(16);');
        DB::connection()->getPdo()->exec('ALTER TABLE first_model_second_model ADD second_model_id BINARY(16);');
    }

    public function testAttach()
    {
        $id1 = (string) Uuid::uuid1()->getHex();
        $id2 = (string) Uuid::uuid1()->getHex();
        $first = (new FirstModel())->create(['id' => $id1, 'name' => 'First']);
        $second = (new SecondModel())->create(['id' => $id2, 'name' => 'Second']);

        $first->second_models()->attach($second);

        $this->assertEquals(1, $first->second_models()->count());
        $this->assertEquals($second->id, $first->second_models()->first()->id);
        $this->assertEquals(1, $second->first_models()->count());
        $this->assertEquals($first->id, $second->first_models()->first()->id);
    }

    public function testDetach()
    {
        $id1 = (string) Uuid::uuid1()->getHex();
        $id2 = (string) Uuid::uuid1()->getHex();
        $first = (new FirstModel())->create(['id' => $id1, 'name' => 'First']);
        $second = (new SecondModel())->create(['id' => $id2, 'name' => 'Second']);

        $first->second_models()->attach($second);
        $this->assertEquals(1, $first->second_models()->count());

        $first->second_models()->detach($second);

        $this->assertEquals(0, $first->second_models()->count());
    }

    public function testDetachRespectsWherePivotNull()
    {
        $first = $this->attachTwoModelsWithoutPivotClass();

        $first->second_models_without_pivot_class()->wherePivotNull('extra')->detach();

        $this->assertEquals(['Second m'], $first->second_models_without_pivot_class()->pluck('name')->all());
    }

    public function testUpdateExistingPivotRespectsWherePivotNull()
    {
        $first = $this->attachTwoModelsWithoutPivotClass();
        $secondM = SecondModel::where('name', 'Second m')->first();

        $updated = $first->second_models_without_pivot_class()->wherePivotNull('extra')
            ->updateExistingPivot($secondM, ['extra' => 'changed']);

        $this->assertEquals(0, $updated);
        $this->assertEquals(1, DB::table('first_model_second_model')->where('extra', 'm')->count());
    }

    private function attachTwoModelsWithoutPivotClass(): FirstModel
    {
        $first = (new FirstModel())->create(['id' => (string) Uuid::uuid1()->getHex(), 'name' => 'First']);
        $secondNull = (new SecondModel())->create(['id' => (string) Uuid::uuid1()->getHex(), 'name' => 'Second null']);
        $secondM = (new SecondModel())->create(['id' => (string) Uuid::uuid1()->getHex(), 'name' => 'Second m']);

        $first->second_models_without_pivot_class()->attach($secondNull, ['extra' => null]);
        $first->second_models_without_pivot_class()->attach($secondM, ['extra' => 'm']);
        $this->assertEquals(2, $first->second_models_without_pivot_class()->count());

        return $first;
    }
}

class FirstModel extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'first_model';

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
        'id' => 'hex_binary',
    ];

    public function second_models()
    {
        return $this->belongsToMany(
            SecondModel::class
        )->using(FirstModelSecondModelPivot::class);
    }

    public function second_models_without_pivot_class()
    {
        return $this->belongsToMany(SecondModel::class);
    }
}

class SecondModel extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'second_model';

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
        'id' => 'hex_binary',
    ];

    public function first_models()
    {
        return $this->belongsToMany(
            FirstModel::class
        )->using(FirstModelSecondModelPivot::class);
    }
}

class FirstModelSecondModelPivot extends Pivot
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'first_model_second_model';

    /**
     * {@inheritdoc}
     */
    protected $mutate = [
        'first_model_id' => 'hex_binary',
        'second_model_id' => 'hex_binary',
    ];
}
