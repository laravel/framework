<?php namespace spec\Illuminate\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use stdClass;

class CollectionSpec extends ObjectBehavior {

	protected $items = ['foo', 'bar'];

	function let()
	{
		$this->beConstructedWith($this->items);
	}

	function it_is_initializable()
	{
		$this->shouldHaveType('Illuminate\Support\Collection');
	}

	function it_returns_all_items()
	{
		$this->all()->shouldReturn($this->items);
	}

	function it_returns_the_first_item()
	{
		$this->first()->shouldReturn('foo');
	}

	function it_returns_the_last_item()
	{
		$this->last()->shouldReturn('bar');
	}

	function it_can_return_and_remove_the_last_item()
	{
		$this->pop()->shouldReturn('bar');
		$this->last()->shouldReturn('foo');
	}

	function it_can_determine_if_its_empty()
	{
		$this->beConstructedWith([]);

		$this->isEmpty()->shouldReturn(true);
	}

	function it_calls_toArray_on_each_item(Arrayable $item1, Arrayable $item2)
	{
		$item1->toArray()->willReturn('foo');
		$item2->toArray()->willReturn('bar');

		$this->beConstructedWith([$item1, $item2]);

		$this->toArray()->shouldReturn(['foo', 'bar']);
	}

	function it_json_encodes_the_array_result()
	{
		$this->toJson()->shouldReturn(json_encode(['foo', 'bar']));
	}

	function it_should_typecast_to_string()
	{
		$this->shouldTypecastToString(json_encode(['foo', 'bar']));
	}

	function it_should_have_offset_access()
	{
		$this->beConstructedWith(['name' => 'taylor']);

		// It can access array keys
		$this['name']->shouldReturn('taylor');

		// It can overwrite specific items
		$this['name'] = 'dayle';
		$this['name']->shouldReturn('dayle');

		// It can assume an item is set
		$this->shouldHaveArrayKeySet('name');

		// It can unset an item
		unset($this['name']);
		$this->shouldNotHaveArrayKeySet('name');

		// It can add a new item
		$this[] = 'jason';
		$this[0]->shouldReturn('jason');
	}

	function it_is_countable()
	{
		$this->shouldHaveCount(2);
	}

	function it_is_iterable()
	{
		$this->getIterator()->shouldBeAnInstanceOf('ArrayIterator');
		$this->getIterator()->getArrayCopy()->shouldReturn(['foo', 'bar']);
	}

	function it_implements_the_cache_iterator()
	{
		$this->getCachingIterator()->shouldBeAnInstanceOf('CachingIterator');
	}

	function it_can_filter_items()
	{
		$item1 = ['id' => 1, 'name' => 'Hello'];
		$item2 = ['id' => 2, 'name' => 'World'];

		$this->beConstructedWith([$item1, $item2]);

		$this->filter(function($item)
		{
			return $item['id'] == 2;
		})->all()->shouldReturn([1 => $item2]);
	}

	function it_returns_filtered_values()
	{
		$item1 = ['id' => 1, 'name' => 'Hello'];
		$item2 = ['id' => 2, 'name' => 'World'];

		$this->beConstructedWith([$item1, $item2]);

		$this->filter(function($item)
		{
			return $item['id'] == 2;
		})->values()->all()->shouldReturn([$item2]);
	}

	function it_can_flatten_items()
	{
		$this->beConstructedWith([['#foo', '#bar'], ['#baz']]);

		$this->flatten()->all()->shouldReturn(['#foo', '#bar', '#baz']);
	}

	function it_can_merge_a_new_collection()
	{
		$this->beConstructedWith(['name' => 'Hello']);

		$this->merge(new Collection(['name' => 'World', 'id' => 1]))
			->all()
			->shouldReturn(['name' => 'World', 'id' => 1]);
	}

	function it_can_diff_a_collection()
	{
		$this->beConstructedWith(['id' => 1, 'first_word' => 'Hello']);

		$this->diff(new Collection(['first_word' => 'Hello', 'last_word' => 'World']))
			->all()
			->shouldReturn(['id' => 1]);
	}

	function it_can_intersect_a_collection()
	{
		$this->beConstructedWith(['id' => 1, 'first_word' => 'Hello']);

		$this->intersect(new Collection(['first_world' => 'Hello', 'last_word' => 'World']))
			->all()
			->shouldReturn(['first_word' => 'Hello']);
	}

	function it_can_return_unique_values()
	{
		$this->beConstructedWith(['Hello', 'World', 'World']);

		$this->unique()->all()->shouldReturn(['Hello', 'World']);
	}

	function it_can_collapse_items()
	{
		$this->beConstructedWith([[$object1 = new StdClass], [$object2 = new StdClass]]);

		$this->collapse()->all()->shouldReturn([$object1, $object2]);
	}

	function it_can_collapse_nested_collections()
	{
		$this->beConstructedWith([new Collection([1, 2, 3]), new Collection([4, 5, 6])]);

		$this->collapse()->all()->shouldReturn([1, 2, 3, 4, 5, 6]);
	}

	function it_can_sort_items()
	{
		$this->beConstructedWith([5, 3, 1, 2, 4]);

		$this->sort(function($a, $b)
		{
			if ($a === $b)
			{
				return 0;
			}
			return ($a < $b) ? -1 : 1;
		})->all()->shouldHaveArrayValues(range(1, 5));
	}

	function it_can_sort_items_by()
	{
		$this->beConstructedWith(['taylor', 'dayle']);

		$this->sortBy(function($x) { return $x; })->all()->shouldHaveArrayValues(['dayle', 'taylor']);
	}

	function it_can_sort_items_by_desc()
	{
		$this->beConstructedWith(['dayle', 'taylor']);

		$this->sortByDesc(function($x) { return $x; })->all()->shouldHaveArrayValues(['taylor', 'dayle']);
	}

	function it_can_sort_by_string()
	{
		$this->beConstructedWith([['name' => 'taylor'], ['name' => 'dayle']]);

		$this->sortBy('name')->all()->shouldHaveArrayValues([['name' => 'dayle'], ['name' => 'taylor']]);
	}

	function it_can_reverse_items()
	{
		$this->beConstructedWith(['zaeed', 'alan']);

		$this->reverse()->all()->shouldHaveArrayValues(['alan', 'zaeed']);
	}

	function it_can_flip_items()
	{
		$this->beConstructedWith(['name' => 'taylor', 'framework' => 'laravel']);

		$this->flip()->toArray()->shouldReturn(['taylor' => 'name', 'laravel' => 'framework']);
	}

	function it_can_chunk_items()
	{
		$this->beConstructedWith([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

		$data = $this->chunk(3);

		$data->shouldBeAnInstanceOf('Illuminate\Support\Collection');
		$data[0]->shouldBeAnInstanceOf('Illuminate\Support\Collection');
		$data->count()->shouldReturn(4);
		$data[0]->toArray()->shouldReturn([1, 2, 3]);
		$data[3]->toArray()->shouldReturn([10]);
	}

	function it_can_list_object_and_array_values()
	{
		$this->beConstructedWith([
			(object) ['name' => 'taylor', 'email' => 'foo'],
			['name' => 'dayle', 'email' => 'bar']
		]);

		$this->lists('email', 'name')->shouldReturn(['taylor' => 'foo', 'dayle' => 'bar']);
		$this->lists('email')->shouldReturn(['foo', 'bar']);
	}

	function it_can_implode_items()
	{
		$this->beConstructedWith([['name' => 'taylor', 'email' => 'foo'], ['name' => 'dayle', 'email' => 'bar']]);

		$this->implode('email')->shouldReturn('foobar');
		$this->implode('email', ',')->shouldReturn('foo,bar');
	}

	function it_can_take_items()
	{
		$this->beConstructedWith(['taylor', 'dayle', 'shawn']);

		$this->take(2)->all()->shouldReturn(['taylor', 'dayle']);
	}

	function it_can_return_random_items()
	{
		$this->beConstructedWith([1, 2, 3, 4, 5, 6]);
		$data = new Collection([1, 2, 3, 4, 5, 6]);

		$this->random()->shouldBeInteger();
		$this->random()->shouldBeInArray($data->all());
		$this->random(3)->shouldHaveCount(3);
	}

	function it_returns_null_when_random_on_empty()
	{
		$this->beConstructedWith();

		$this->random()->shouldBeNull();
	}

	function it_can_take_the_last_items()
	{
		$this->beConstructedWith(['taylor', 'dayle', 'shawn']);

		$this->take(-2)->all()->shouldReturn(['dayle', 'shawn']);
	}

	function it_can_take_all_items()
	{
		$this->take()->all()->shouldReturn(['foo', 'bar']);
	}

	function it_can_make_items()
	{
		$this->shouldMakeItems('foo', ['foo']);
	}

	function it_can_make_from_null()
	{
		$this->shouldMakeItems(null, []);
	}

	function it_can_make_from_empty()
	{
		$this->shouldMakeItems([], []);
	}

	function it_can_make_from_collection()
	{
		$collection = new Collection(['foo' => 'bar']);

		$this->shouldMakeItems($collection, ['foo' => 'bar']);
	}

	function it_can_make_from_array()
	{
		$this->shouldMakeItems(['foo' => 'bar'], ['foo' => 'bar']);
	}

	function it_can_make_from_object()
	{
		$object = new stdClass;
		$object->foo = 'bar';

		$this->shouldMakeItems($object, ['foo' => 'bar']);
	}

	function it_can_construct_items()
	{
		$this->beConstructedWith('foo');

		$this->all()->shouldReturn(['foo']);
	}

	function it_can_construct_from_null()
	{
		$this->beConstructedWith(null);

		$this->all()->shouldReturn([]);
	}

	function it_can_construct_from_empty()
	{
		$this->beConstructedWith();

		$this->all()->shouldReturn([]);
	}

	function it_can_construct_from_collection()
	{
		$this->beConstructedWith(new Collection(['foo' => 'bar']));

		$this->all()->shouldReturn(['foo' => 'bar']);
	}

	function it_can_construct_from_an_array()
	{
		$this->beConstructedWith(['foo' => 'bar']);

		$this->all()->shouldReturn(['foo' => 'bar']);
	}

	function it_can_construct_from_an_object()
	{
		$object = new stdClass;
		$object->foo = 'bar';
		$this->beConstructedWith($object);

		$this->all()->shouldReturn(['foo' => 'bar']);
	}

	function it_can_splice_items()
	{
		$this->splice(1, 0, 'baz');
		$this->all()->shouldReturn(['foo', 'baz', 'bar']);
	}

	function it_can_splice_items_off()
	{
		$this->splice(1, 1);
		$this->all()->shouldReturn(['foo']);
	}

	function it_can_splice_a_specific_item_off()
	{
		// The return collection should only contain the spliced-off item
		$this->splice(1, 1, 'baz')->all()->shouldReturn(['bar']);

		// The current collection should contain the old item and the new one
		$this->all()->shouldReturn(['foo', 'baz']);
	}

	function it_can_get_list_values_with_accessors()
	{
		$model1 = new EloquentModelStub(['some' => 'foo']);
		$model2 = new EloquentModelStub(['some' => 'bar']);

		$this->beConstructedWith([$model1, $model2]);

		$this->lists('some')->shouldReturn(['foo', 'bar']);
	}

	function it_can_transform_items()
	{
		$this->beConstructedWith(['taylor', 'colin', 'shawn']);

		$this->transform(function($item) {
			return strrev($item);
		})->all()->shouldHaveArrayValues(['rolyat', 'niloc', 'nwahs']);
	}

	function it_can_use_first_with_a_callback()
	{
		$this->first(function($key, $value) { return $value === 'bar'; })->shouldReturn('bar');
	}

	function it_can_use_first_with_a_callback_and_default()
	{
		$this->first(function($key, $value) { return $value === 'baz'; }, 'default')->shouldReturn('default');
	}

	function it_can_group_by_attribute()
	{
		$this->beConstructedWith([
			['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1'], ['rating' => 2, 'url' => '2']
		]);

		$this->groupBy('rating')->toArray()->shouldReturn([
			1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']],
			2 => [['rating' => 2, 'url' => '2']]
		]);
	}

	function it_can_group_by_another_attribute()
	{
		$this->beConstructedWith([
			['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1'], ['rating' => 2, 'url' => '2']
		]);

		$this->groupBy('url')->toArray()->shouldReturn([
			1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']],
			2 => [['rating' => 2, 'url' => '2']]
		]);
	}

	function it_can_key_by_attribute()
	{
		$this->beConstructedWith([
			['rating' => 1, 'name' => '1'], ['rating' => 2, 'name' => '2'], ['rating' => 3, 'name' => '3']
		]);

		$this->keyBy('rating')->all()->shouldReturn([
			1 => ['rating' => 1, 'name' => '1'],
			2 => ['rating' => 2, 'name' => '2'],
			3 => ['rating' => 3, 'name' => '3']
		]);
	}

	function it_can_assert_contains()
	{
		$this->beConstructedWith([1, 3, 5]);

		$this->contains(1)->shouldReturn(true);
		$this->contains(2)->shouldReturn(false);
		$this->contains(function($value) { return $value < 5; })->shouldReturn(true);
		$this->contains(function($value) { return $value > 5; })->shouldReturn(false);
	}

	function it_can_get_the_sum_from_items()
	{
		$this->beConstructedWith([(object) ['foo' => 50], (object) ['foo' => 50]]);

		$this->sum('foo')->shouldReturn(100);
		$this->sum(function($i) { return $i->foo; })->shouldReturn(100);
	}

	function it_can_get_the_sum_from_an_empty_collection()
	{
		$this->beConstructedWith([]);

		$this->sum('foo')->shouldReturn(0);
	}

	function it_can_retrieve_items_with_dot_notation()
	{
		$this->beConstructedWith([
			(object) ['id' => 1, 'foo' => ['bar' => 'B']],
			(object) ['id' => 2, 'foo' => ['bar' => 'A']],
		]);

		$this->sortBy('foo.be')->lists('id')->shouldReturn([2, 1]);
	}

	function it_can_pull_items()
	{
		$this->pull(0)->shouldReturn('foo');
	}

	function it_can_remove_items_with_pull()
	{
		$this->pull(0);
		$this->all()->shouldReturn([1 => 'bar']);
	}

	function it_can_return_a_default_with_pull()
	{
		$this->beConstructedWith([]);

		$this->pull(0, 'foo')->shouldReturn('foo');
	}

	function it_can_reject_items_which_pass_through_a_truth_test()
	{
		$this->reject('bar')->values()->all()->shouldReturn(['foo']);
	}

	function it_can_reject_items_which_pass_through_a_callback_truth_test()
	{
		$this->reject(function($v) { return $v == 'bar'; })->values()->all()->shouldReturn(['foo']);
	}

	function it_can_reject_an_null_item_which_pass_through_a_truth_test()
	{
		$this->beConstructedWith(['foo', null]);

		$this->reject(null)->values()->all()->shouldReturn(['foo']);
	}

	function it_can_return_items_which_dont_pass_through_a_truth_test()
	{
		$this->reject('baz')->values()->all()->shouldReturn(['foo', 'bar']);
	}

	function it_can_return_items_which_dont_pass_through_a_callback_truth_test()
	{
		$this->reject(function($v) { return $v == 'baz'; })->values()->all()->shouldReturn(['foo', 'bar']);
	}

	function it_returns_the_item_keys()
	{
		$this->beConstructedWith(['name' => 'taylor', 'framework' => 'laravel']);

		$this->keys()->shouldReturn(['name', 'framework']);
	}

	/**
	 * Custom matchers for this spec
	 *
	 * @return array
	 */
	public function getMatchers()
	{
		return [
			'typecastToString' => function($subject, $expected) {
				return (string) $subject === $expected;
			},
			'haveArrayKeySet' => function($subject, $key) {
				return isset($subject[$key]);
			},
			'notHaveArrayKeySet' => function($subject, $key) {
				return ! isset($subject[$key]);
			},
			'haveArrayValues' => function($subject, $values) {
				return array_values($subject) === $values;
			},
			'beInArray' => function($value, $array) {
				return in_array($value, $array);
			},
			'makeItems' => function($subject, $input, $result) {
				return Collection::make($input)->all() === $result;
			},
		];
	}
}

class EloquentModelStub extends Model {

	protected $fillable = ['some'];

	public function getSomeAttribute()
	{
		return $this->attributes['some'];
	}

}
