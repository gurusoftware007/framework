<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use PHPUnit\Framework\TestCase;

/**
 * @group one-of-many
 */
class DatabaseEloquentMorphOneOfManyTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('products', function ($table) {
            $table->increments('id');
        });

        $this->schema()->create('states', function ($table) {
            $table->increments('id');
            $table->morphs('stateful');
            $table->string('state');
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('products');
        $this->schema()->drop('states');
    }

    public function testReceivingModel()
    {
        $product = MorphOneOfManyTestProduct::create();
        $product->states()->create([
            'state' => 'draft',
        ]);
        $product->states()->create([
            'state' => 'active',
        ]);

        $this->assertNotNull($product->current_state);
        $this->assertSame('active', $product->current_state->state);
    }

    public function testMorphType()
    {
        $product = MorphOneOfManyTestProduct::create();
        $product->states()->create([
            'state' => 'draft',
        ]);
        $product->states()->create([
            'state' => 'active',
        ]);
        $state = $product->states()->make([
            'state' => 'foo',
        ]);
        $state->stateful_type = 'bar';
        $state->save();

        $this->assertNotNull($product->current_state);
        $this->assertSame('active', $product->current_state->state);
    }

    public function testExists()
    {
        $product = MorphOneOfManyTestProduct::create();
        $previousState = $product->states()->create([
            'state' => 'draft',
        ]);
        $currentState = $product->states()->create([
            'state' => 'active',
        ]);

        $exists = MorphOneOfManyTestProduct::whereHas('current_state', function ($q) use ($previousState) {
            $q->whereKey($previousState->getKey());
        })->exists();
        $this->assertFalse($exists);

        $exists = MorphOneOfManyTestProduct::whereHas('current_state', function ($q) use ($currentState) {
            $q->whereKey($currentState->getKey());
        })->exists();
        $this->assertTrue($exists);
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
}

/**
 * Eloquent Models...
 */
class MorphOneOfManyTestProduct extends Eloquent
{
    protected $table = 'products';
    protected $guarded = [];
    public $timestamps = false;

    public function states()
    {
        return $this->morphMany(MorphOneOfManyTestState::class, 'stateful');
    }

    public function current_state()
    {
        return $this->morphOne(MorphOneOfManyTestState::class, 'stateful')->ofMany();
    }
}

class MorphOneOfManyTestState extends Eloquent
{
    protected $table = 'states';
    protected $guarded = [];
    public $timestamps = false;
    protected $fillable = ['state'];
}
