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
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app)
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
        $id = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id, 'name' => 'A table']);
        $p = $model->find($id);
        $this->assertEquals($id, $p->id);

        $p = DB::table('test_model')->where('id', $id)->first();
        $this->assertNull($p);
    }

    public function test_array_of_wheres()
    {
        $id = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id, 'name' => 'A table']);
        $p = $model->where([
            'id' => $id,
            'name' => 'A table',
        ])->first();
        $this->assertEquals($id, $p->id);
    }

    public function test_where_with_operator_like_value()
    {
        foreach (['IS', 'is', 'like', '='] as $value) {
            $id = Uuid::uuid1()->toString();
            (new TestModel())->create(['id' => $id, 'name' => $value]);
            (new TestModel())->create(['id' => Uuid::uuid1()->toString(), 'name' => 'A chair']);

            $p = TestModel::where('name', $value)->get();
            $this->assertEquals(1, $p->count(), "Failed asserting where() matched name '$value'");
            $this->assertEquals($id, $p->first()->id);

            TestModel::query()->delete();
        }
    }

    public function test_array_of_wheres_with_operator_like_value()
    {
        $id = Uuid::uuid1()->toString();
        (new TestModel())->create(['id' => $id, 'name' => 'IS']);
        (new TestModel())->create(['id' => Uuid::uuid1()->toString(), 'name' => 'A table']);

        $p = TestModel::where([
            'id' => $id,
            'name' => 'IS',
        ])->get();
        $this->assertEquals(1, $p->count());
        $this->assertEquals($id, $p->first()->id);
    }

    public function test_where_closure_with_null_shorthand()
    {
        $id = Uuid::uuid1()->toString();
        (new TestModel())->create(['id' => $id, 'name' => 'A table']);
        (new TestModel())->create(['id' => Uuid::uuid1()->toString(), 'name' => 'A chair']);

        $p = TestModel::where(function ($query) {
            $query->where('name', 'A table');
        }, null)->get();
        $this->assertEquals(1, $p->count());
        $this->assertEquals($id, $p->first()->id);
    }

    public function test_where_not_null_shorthand()
    {
        $id = Uuid::uuid1()->toString();
        (new TestModel())->create(['id' => $id, 'name' => 'A table', 'location' => 'One']);
        (new TestModel())->create(['id' => Uuid::uuid1()->toString(), 'name' => 'A chair']);

        $p = TestModel::where('location', '!=', null)->get();
        $this->assertEquals(1, $p->count());
        $this->assertEquals($id, $p->first()->id);
    }

    public function test_find()
    {
        $id = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id, 'name' => 'A table']);
        $p = $model->find($id);
        $this->assertEquals($id, $p->id);
    }

    public function test_array_of_find()
    {
        $id = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id, 'name' => 'Name A']);
        $id2 = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id2, 'name' => 'Name B']);
        $p = $model->find([$id, $id2]);
        $this->assertEquals(2, $p->count());
        $this->assertEquals($id, $p[0]->id);
        $this->assertEquals($id2, $p[1]->id);
    }

    public function test_non_mutated_columns()
    {
        $id = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id, 'name' => 'abc']);
        $p = $model->where('name', 'abc')->first();
        $this->assertEquals($id, $p->id);
    }

    public function test_where_in()
    {
        $id = Uuid::uuid1()->toString();
        $id2 = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id, 'name' => 'A chair']);
        $model2 = (new TestModel())->create(['id' => $id2, 'name' => 'A table']);
        $p = $model->whereIn('id', [$id, $id2])->get();
        $this->assertEquals(2, $p->count());
    }

    public function test_where_in_subquery()
    {
        $id = Uuid::uuid1()->toString();
        $id2 = Uuid::uuid1()->toString();
        (new TestModel())->create(['id' => $id, 'name' => 'A chair']);
        (new TestModel())->create(['id' => $id2, 'name' => 'A table']);
        $p = TestModel::whereIn('id', function ($query) {
            $query->select('id')->from('test_model')->whereNull('location');
        })->get();
        $this->assertEquals(2, $p->count());
        $this->assertEquals($id, $p->first()->id);
        $this->assertEquals($id2, $p->last()->id);
    }

    public function test_where_in_subquery_with_bindings()
    {
        $id = Uuid::uuid1()->toString();
        $id2 = Uuid::uuid1()->toString();
        (new TestModel())->create(['id' => $id, 'name' => 'A chair', 'location' => 'One']);
        (new TestModel())->create(['id' => $id2, 'name' => 'A table', 'location' => 'Two']);

        $query = TestModel::query()->where('name', '!=', 'A lamp')->whereIn('id', function ($query) {
            $query->select('id')->from('test_model')->where('name', 'A lamp')->orWhere('name', 'A chair');
        });

        $p = $query->get();
        $this->assertEquals(1, $p->count());
        $this->assertEquals($id, $p->first()->id);
    }

    public function test_where_in_wherein_subquery()
    {
        $id = Uuid::uuid1()->toString();
        $id2 = Uuid::uuid1()->toString();
        (new TestModel())->create(['id' => $id, 'name' => 'A chair']);
        (new TestModel())->create(['id' => $id2, 'name' => 'A table']);
        $p = TestModel::withWherein()->get();
        $this->assertEquals(2, $p->count());
        $this->assertEquals($id, $p->first()->id);
        $this->assertEquals($id2, $p->last()->id);
    }

    public function test_where_not_in()
    {
        $id = Uuid::uuid1()->toString();
        $id2 = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id, 'name' => 'A chair']);
        $model2 = (new TestModel())->create(['id' => $id2, 'name' => 'A table']);
        $p = $model->whereNotIn('id', [$id])->get();
        $this->assertEquals(1, $p->count());
    }

    public function test_where_key()
    {
        $id = Uuid::uuid1()->toString();
        $id2 = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id, 'name' => 'A chair']);
        $model2 = (new TestModel())->create(['id' => $id2, 'name' => 'A table']);
        $p = $model->whereKey([$id, $id2])->get();
        $this->assertEquals(2, $p->count());
        $this->assertEquals($id, $p[0]->id);
        $this->assertEquals($id2, $p[1]->id);
    }

    public function test_update()
    {
        $id = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id, 'name' => 'A chair', 'location' => 'Foo']);
        $p1 = (new TestModel())->whereIn('id', [$id])->first();

        // Update the attribute
        $model->location = 'Bar';
        $model->save();

        $this->assertEquals('Foo', $p1->location);
        $this->assertEquals('Bar', (new TestModel())->whereIn('id', [$id])->first()->location);
    }

    public function test_pluck()
    {
        $id = Uuid::uuid1()->toString();
        $id2 = Uuid::uuid1()->toString();
        (new TestModel())->create(['id' => $id, 'name' => 'A chair', 'location' => 'Foo'])->save();
        (new TestModel())->create(['id' => $id2, 'name' => 'A chair', 'location' => 'Foo'])->save();
        $ids = TestModel::whereIn('id', [$id, $id2])->pluck('id')->toArray();
        $this->assertEquals([$id, $id2], $ids);
    }

    public function test_pluck_with_key()
    {
        $id = Uuid::uuid1()->toString();
        $id2 = Uuid::uuid1()->toString();
        (new TestModel())->create(['id' => $id, 'name' => 'A chair', 'location' => 'Foo'])->save();
        (new TestModel())->create(['id' => $id2, 'name' => 'A chair', 'location' => 'Bar'])->save();
        $ids = TestModel::whereIn('id', [$id, $id2])->pluck('id', 'location')->toArray();

        $expected = [
            'Foo' => $id,
            'Bar' => $id2,
        ];
        $this->assertEquals($expected, $ids);
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

    public function test_where_closure()
    {
        $id = Uuid::uuid1()->toString();
        $location = 'Foo';
        (new TestModel())->create(['id' => $id, 'name' => 'A chair', 'location' => $location])->save();

        $model = TestModel::where('name', '=', 'A chair')
            ->where(function ($q) use ($id) {
                $q->where('id', '=', $id)
                    ->orWhere('id', '=', Uuid::uuid1()->toString());
            })->get();

        $this->assertEquals(1, count($model));
        $this->assertEquals($id, $model[0]->id);
    }

    public function test_where_not()
    {
        $id = Uuid::uuid1()->toString();
        $id2 = Uuid::uuid1()->toString();
        (new TestModel())->create(['id' => $id, 'name' => 'A chair']);
        (new TestModel())->create(['id' => $id2, 'name' => 'A table']);

        $p = TestModel::whereNot('id', $id)->get();
        $this->assertEquals(1, $p->count());
        $this->assertEquals($id2, $p->first()->id);
    }

    public function test_where_not_closure()
    {
        $id = Uuid::uuid1()->toString();
        $id2 = Uuid::uuid1()->toString();
        (new TestModel())->create(['id' => $id, 'name' => 'A chair']);
        (new TestModel())->create(['id' => $id2, 'name' => 'A table']);

        $p = TestModel::whereNot(function ($q) use ($id) {
            $q->where('id', $id);
        })->get();
        $this->assertEquals(1, $p->count());
        $this->assertEquals($id2, $p->first()->id);
    }

    public function test_where_not_empty_array()
    {
        $id = Uuid::uuid1()->toString();
        $id2 = Uuid::uuid1()->toString();
        (new TestModel())->create(['id' => $id, 'name' => 'A chair']);
        (new TestModel())->create(['id' => $id2, 'name' => 'A table']);

        $this->assertEquals(1, TestModel::where('id', $id)->whereNot([])->count());
        $this->assertEquals(2, TestModel::whereNot([])->count());
    }

    public function test_refresh()
    {
        $id = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id, 'name' => 'A chair', 'location' => 'Foo']);
        $this->assertEquals('Foo', $model->location);

        TestModel::where('id', $id)->update(['location' => 'Bar']);

        $this->assertEquals('Bar', $model->refresh()->location);
    }

    public function test_where_closure_with_global_scope()
    {
        $id = Uuid::uuid1()->toString();
        (new ScopedTestModel())->create(['id' => $id, 'name' => 'Hidden']);

        $this->assertEquals(0, ScopedTestModel::where(function ($q) use ($id) {
            $q->where('id', $id);
        })->count());

        $p = ScopedTestModel::withoutGlobalScope('hide')->where(function ($q) use ($id) {
            $q->where('id', $id);
        })->get();
        $this->assertEquals(1, $p->count());
        $this->assertEquals($id, $p->first()->id);
    }

    public function test_where_closure_removing_global_scope()
    {
        $id = Uuid::uuid1()->toString();
        (new ScopedTestModel())->create(['id' => $id, 'name' => 'Hidden']);

        $count = ScopedTestModel::where(function ($q) use ($id) {
            $q->withoutGlobalScope('hide')->where('id', $id);
        })->count();

        // Laravel 13 propagates scopes removed inside a nested where to the outer query.
        $this->assertEquals(version_compare(app()->version(), '13.0.0', '>=') ? 1 : 0, $count);
    }

    public function test_where_not_array()
    {
        $id = Uuid::uuid1()->toString();
        $id2 = Uuid::uuid1()->toString();
        (new TestModel())->create(['id' => $id, 'name' => 'A chair']);
        (new TestModel())->create(['id' => $id2, 'name' => 'A table']);

        $p = TestModel::whereNot(['id' => $id])->get();
        $this->assertEquals(1, $p->count());
        $this->assertEquals($id2, $p->first()->id);
    }

    public function test_where_not_array_of_conditions()
    {
        $id = Uuid::uuid1()->toString();
        $id2 = Uuid::uuid1()->toString();
        (new TestModel())->create(['id' => $id, 'name' => 'A chair']);
        (new TestModel())->create(['id' => $id2, 'name' => 'A table']);

        $p = TestModel::whereNot([['id', '=', $id]])->get();
        $this->assertEquals(1, $p->count());
        $this->assertEquals($id2, $p->first()->id);
    }

    public function test_or_where_not_array()
    {
        $id = Uuid::uuid1()->toString();
        $id2 = Uuid::uuid1()->toString();
        $id3 = Uuid::uuid1()->toString();
        (new TestModel())->create(['id' => $id, 'name' => 'A chair']);
        (new TestModel())->create(['id' => $id2, 'name' => 'A table']);
        (new TestModel())->create(['id' => $id3, 'name' => 'A lamp']);

        $query = TestModel::where('name', 'A lamp')->orWhereNot(['id' => $id, 'name' => 'A table']);

        $this->assertEquals(
            'select * from "test_model" where "name" = ? or not ("id" = ? or "name" = ?)',
            $query->toSql()
        );
        $this->assertEquals([$id3], $query->get()->pluck('id')->all());
    }

    public function test_refresh_for_update()
    {
        if (! method_exists(TestModel::class, 'refreshForUpdate')) {
            $this->markTestSkipped('refreshForUpdate() was added in Laravel 13.');
        }

        $id = Uuid::uuid1()->toString();
        $model = (new TestModel())->create(['id' => $id, 'name' => 'A chair', 'location' => 'Foo']);
        $this->assertEquals('Foo', $model->location);

        TestModel::where('id', $id)->update(['location' => 'Bar']);

        $this->assertEquals('Bar', $model->refreshForUpdate()->location);
    }

    public function test_refreshes_attributes_after_save()
    {
        if (! property_exists(\Illuminate\Database\Eloquent\Model::class, 'refreshes')) {
            $this->markTestSkipped('Refreshing attributes after save was added in Laravel 13.');
        }

        $encrypted = app('encrypter')->encrypt('Changed by trigger', false);
        DB::connection()->getPdo()->exec(
            "CREATE TRIGGER set_location AFTER UPDATE OF name ON test_model BEGIN
                UPDATE test_model SET location = '{$encrypted}' WHERE id = NEW.id;
            END;"
        );

        $model = (new RefreshingTestModel())->create(['id' => Uuid::uuid1()->toString(), 'name' => 'A chair', 'location' => 'Foo']);
        $this->assertEquals('Foo', $model->location);

        $model->name = 'A table';
        $model->save();

        $this->assertEquals('Changed by trigger', $model->location);
    }
}

class TestModel extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'test_model';

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
        'location' => 'encrypt_string',
    ];

    public function scopeWithWherein($builder)
    {
        $builder->whereIn('id', function ($subquery) {
            $subquery->select('id')->from('test_model')
                ->whereNull('location');
        });
    }
}

class ScopedTestModel extends TestModel
{
    protected static function booted()
    {
        static::addGlobalScope('hide', function ($builder) {
            $builder->where('name', '!=', 'Hidden');
        });
    }
}

class RefreshingTestModel extends TestModel
{
    protected array $refreshes = ['location'];
}

class TimestampedModel extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'timestamped_model';

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    protected $mutate = [
        'created_at' => 'unix_timestamp',
        'updated_at' => 'unix_timestamp',
    ];
}
