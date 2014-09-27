<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DatabaseEloquentMorphToTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testLookupDictionaryIsProperlyConstructed()
	{
		$relation = $this->getRelation();
		$relation->addEagerConstraints([
			$one = (object) ['morph_type' => 'morph_type_1', 'foreign_key' => 'foreign_key_1'],
			$two = (object) ['morph_type' => 'morph_type_1', 'foreign_key' => 'foreign_key_1'],
			$three = (object) ['morph_type' => 'morph_type_2', 'foreign_key' => 'foreign_key_2'],
		]);

		$dictionary = $relation->getDictionary();

		$this->assertEquals([
			'morph_type_1' => [
				'foreign_key_1' => [
					$one,
					$two
				]
			],
			'morph_type_2' => [
				'foreign_key_2' => [
					$three
				]
			],
		], $dictionary);
	}


	public function testModelsAreProperlyPulledAndMatched()
	{
		$relation = $this->getRelation();

		$one = m::mock('StdClass');
		$one->morph_type = 'morph_type_1';
		$one->foreign_key = 'foreign_key_1';

		$two = m::mock('StdClass');
		$two->morph_type = 'morph_type_1';
		$two->foreign_key = 'foreign_key_1';

		$three = m::mock('StdClass');
		$three->morph_type = 'morph_type_2';
		$three->foreign_key = 'foreign_key_2';

		$relation->addEagerConstraints([$one, $two, $three]);

		$relation->shouldReceive('createModelByType')->once()->with('morph_type_1')->andReturn($firstQuery = m::mock('Illuminate\Database\Eloquent\Builder'));
		$relation->shouldReceive('createModelByType')->once()->with('morph_type_2')->andReturn($secondQuery = m::mock('Illuminate\Database\Eloquent\Builder'));
		$firstQuery->shouldReceive('getKeyName')->andReturn('id');
		$secondQuery->shouldReceive('getKeyName')->andReturn('id');

		$firstQuery->shouldReceive('newQuery')->once()->andReturn($firstQuery);
		$secondQuery->shouldReceive('newQuery')->once()->andReturn($secondQuery);

		$firstQuery->shouldReceive('whereIn')->once()->with('id', ['foreign_key_1'])->andReturn($firstQuery);
		$firstQuery->shouldReceive('get')->once()->andReturn(Collection::make([$resultOne = m::mock('StdClass')]));
		$resultOne->shouldReceive('getKey')->andReturn('foreign_key_1');

		$secondQuery->shouldReceive('whereIn')->once()->with('id', ['foreign_key_2'])->andReturn($secondQuery);
		$secondQuery->shouldReceive('get')->once()->andReturn(Collection::make([$resultTwo = m::mock('StdClass')]));
		$resultTwo->shouldReceive('getKey')->andReturn('foreign_key_2');

		$one->shouldReceive('setRelation')->once()->with('relation', $resultOne);
		$two->shouldReceive('setRelation')->once()->with('relation', $resultOne);
		$three->shouldReceive('setRelation')->once()->with('relation', $resultTwo);

		$relation->getEager();
	}

	public function testModelsWithSoftDeleteAreProperlyPulled()
	{
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');

		$relation = $this->getRelation(null, $builder);

		$builder->shouldReceive('getMacro')->once()->with('withTrashed')->andReturn(function() { return true; });
		$builder->shouldReceive('withTrashed')->once();

		$relation->withTrashed();
	}

	public function testAssociateMethodSetsForeignKeyAndTypeOnModel()
	{
		$parent = m::mock('Illuminate\Database\Eloquent\Model');
		$parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');

		$relation = $this->getRelationAssociate($parent);

		$associate = m::mock('Illuminate\Database\Eloquent\Model');
		$associate->shouldReceive('getKey')->once()->andReturn(1);
		$associate->shouldReceive('getMorphClass')->once()->andReturn('Model');

		$parent->shouldReceive('setAttribute')->once()->with('foreign_key', 1);
		$parent->shouldReceive('setAttribute')->once()->with('morph_type', 'Model');
		$parent->shouldReceive('setRelation')->once()->with('relation', $associate);

		$relation->associate($associate);
	}


	protected function getRelationAssociate($parent)
	{
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$builder->shouldReceive('where')->with('relation.id', '=', 'foreign.value');
		$related = m::mock('Illuminate\Database\Eloquent\Model');
		$related->shouldReceive('getKey')->andReturn(1);
		$related->shouldReceive('getTable')->andReturn('relation');
		$builder->shouldReceive('getModel')->andReturn($related);
		return new MorphTo($builder, $parent, 'foreign_key', 'id', 'morph_type', 'relation');
	}


	public function getRelation($parent = null, $builder = null)
	{
		$builder = $builder ?: m::mock('Illuminate\Database\Eloquent\Builder');
		$builder->shouldReceive('where')->with('relation.id', '=', 'foreign.value');
		$related = m::mock('Illuminate\Database\Eloquent\Model');
		$related->shouldReceive('getKeyName')->andReturn('id');
		$related->shouldReceive('getTable')->andReturn('relation');
		$builder->shouldReceive('getModel')->andReturn($related);
		$parent = $parent ?: new EloquentMorphToModelStub;
		$morphTo = m::mock('Illuminate\Database\Eloquent\Relations\MorphTo[createModelByType]', [$builder, $parent, 'foreign_key', 'id', 'morph_type', 'relation']);
		return $morphTo;
	}

}


class EloquentMorphToModelStub extends Illuminate\Database\Eloquent\Model {
	public $foreign_key = 'foreign.value';
}
