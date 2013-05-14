<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseEloquentBelongsToTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testUpdateMethodRetrievesModelAndUpdates()
	{
		$relation = $this->getRelation();
		$mock = m::mock('Illuminate\Database\Eloquent\Model');
		$mock->shouldReceive('fill')->once()->with(array('attributes'))->andReturn($mock);
		$mock->shouldReceive('save')->once()->andReturn(true);
		$relation->getQuery()->shouldReceive('first')->once()->andReturn($mock);

		$this->assertTrue($relation->update(array('attributes')));
	}


	public function testEagerConstraintsAreProperlyAdded()
	{
		$relation = $this->getRelation();
		$relation->getQuery()->shouldReceive('whereIn')->once()->with('relation.id', array('foreign.value', 'foreign.value.two'));
		$models = array(new EloquentBelongsToModelStub, new EloquentBelongsToModelStub, new AnotherEloquentBelongsToModelStub);
		$relation->addEagerConstraints($models);
	}


	public function testRelationIsProperlyInitialized()
	{
		$relation = $this->getRelation();
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('setRelation')->once()->with('foo', null);
		$models = $relation->initRelation(array($model), 'foo');

		$this->assertEquals(array($model), $models);
	}


	public function testModelsAreProperlyMatchedToParents()
	{
		$relation = $this->getRelation();
		$result1 = m::mock('stdClass');
		$result1->shouldReceive('getKey')->andReturn(1);
		$result2 = m::mock('stdClass');
		$result2->shouldReceive('getKey')->andReturn(2);
		$model1 = new EloquentBelongsToModelStub;
		$model1->foreign_key = 1;
		$model2 = new EloquentBelongsToModelStub;
		$model2->foreign_key = 2;
		$models = $relation->match(array($model1, $model2), new Collection(array($result1, $result2)), 'foo');

		$this->assertEquals(1, $models[0]->foo->getKey());
		$this->assertEquals(2, $models[1]->foo->getKey());
	}


	protected function getRelation()
	{
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$builder->shouldReceive('where')->with('relation.id', '=', 'foreign.value');
		$related = m::mock('Illuminate\Database\Eloquent\Model');
		$related->shouldReceive('getKeyName')->andReturn('id');
		$related->shouldReceive('getTable')->andReturn('relation');
		$builder->shouldReceive('getModel')->andReturn($related);
		$parent = new EloquentBelongsToModelStub;
		return new BelongsTo($builder, $parent, 'foreign_key');
	}

	public function testAssociateMethodSetsForeignKeyOnModel()
	{
		$mockOwner = $this->getMock('Illuminate\Database\Eloquent\Model', array('getTable'));
		$mockOwner->expects($this->atLeastOnce())->method('getTable')->will($this->returnValue('owner_table'));

		$mockBelonger = $this->getMock('Illuminate\Database\Eloquent\Model', array('save', 'getAttribute', 'setAttribute', 'getTable'));
		$mockBelonger->expects($this->once())->method('getAttribute')->with($this->equalTo('owner_key'))->will($this->returnValue(321));
		$mockBelonger->expects($this->once())->method('setAttribute')->with($this->equalTo('owner_key'), $this->equalTo(123));

		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$builder->shouldReceive('getModel')->andReturn($mockOwner);
		$builder->shouldReceive('where')->with("owner_table.id", "=", 321);
		
		$relation = new BelongsTo($builder, $mockBelonger, 'owner_key');

		$newOwner = m::mock('Illuminate\Database\Eloquent\Model');
		$newOwner->shouldReceive('getKey')->andReturn(123);

		$relation->associate($newOwner);
	}

}

class EloquentBelongsToModelStub extends Illuminate\Database\Eloquent\Model {

	public $foreign_key = 'foreign.value';

}

class AnotherEloquentBelongsToModelStub extends Illuminate\Database\Eloquent\Model {

	public $foreign_key = 'foreign.value.two';

}