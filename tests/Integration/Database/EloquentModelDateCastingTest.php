<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelDateCastingTest;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentModelDateCastingTest extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('test_model1', function ($table) {
            $table->increments('id');
            $table->date('date_field')->nullable();
            $table->datetime('datetime_field')->nullable();
        });
    }

    public function test_user_can_update_nullable_date()
    {
        $user = TestModel1::create([
            'date_field' => '2019-10-01',
            'datetime_field' => '2019-10-01 10:15:20',
        ]);

        $this->assertEquals('2019-10', $user->toArray()['date_field']);
        $this->assertEquals('2019-10 10:15', $user->toArray()['datetime_field']);
        $this->assertInstanceOf(Carbon::class, $user->date_field);
        $this->assertInstanceOf(Carbon::class, $user->datetime_field);
    }
}

class TestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $dates = ['date_field', 'datetime_field'];

    public $casts = [
        'date_field' => 'date:Y-m',
        'datetime_field' => 'date:Y-m H:i',
    ];
}
