<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\TestCase;
use stdClass;

class SupportLazyCollectionIsLazyTest extends TestCase
{
    public function testEagerEnumeratesOnce()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            $collection = $collection->eager();

            $collection->count();
            $collection->all();
        });
    }

    public function testChunkIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->chunk(3);
        });

        $this->assertEnumerates(15, static function ($collection) {
            $collection->chunk(5)->take(3)->all();
        });
    }

    public function testCollapseIsLazy()
    {
        $collection = LazyCollection::make([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ]);

        $this->assertDoesNotEnumerateCollection($collection, static function ($collection) {
            $collection->collapse();
        });

        $this->assertEnumeratesCollection($collection, 1, static function ($collection) {
            $collection->collapse()->take(3)->all();
        });
    }

    public function testCombineIsLazy()
    {
        $firstEnumerations = 0;
        $secondEnumerations = 0;
        $first = $this->countEnumerations($this->make([1, 2]), $firstEnumerations);
        $second = $this->countEnumerations($this->make([1, 2]), $secondEnumerations);

        $first->combine($second);

        $this->assertEnumerations(0, $firstEnumerations);
        $this->assertEnumerations(0, $secondEnumerations);

        $first->combine($second)->take(1)->all();

        $this->assertEnumerations(1, $firstEnumerations);
        $this->assertEnumerations(1, $secondEnumerations);
    }

    public function testConcatIsLazy()
    {
        $firstEnumerations = 0;
        $secondEnumerations = 0;
        $first = $this->countEnumerations($this->make([1, 2]), $firstEnumerations);
        $second = $this->countEnumerations($this->make([1, 2]), $secondEnumerations);

        $first->concat($second);

        $this->assertEnumerations(0, $firstEnumerations);
        $this->assertEnumerations(0, $secondEnumerations);

        $first->concat($second)->take(2)->all();

        $this->assertEnumerations(2, $firstEnumerations);
        $this->assertEnumerations(0, $secondEnumerations);

        $firstEnumerations = 0;
        $secondEnumerations = 0;

        $first->concat($second)->take(3)->all();

        $this->assertEnumerations(2, $firstEnumerations);
        $this->assertEnumerations(1, $secondEnumerations);
    }

    public function testContainsIsLazy()
    {
        $this->assertEnumerates(5, static function ($collection) {
            $collection->contains(5);
        });
    }

    public function testContainsStrictIsLazy()
    {
        $this->assertEnumerates(5, static function ($collection) {
            $collection->containsStrict(5);
        });
    }

    public function testCountEnumeratesOnce()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->count();
        });
    }

    public function testCountByIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->countBy();
        });

        $this->assertEnumeratesCollectionOnce(
            $this->make([1, 2, 2, 3]),
            static function ($collection) {
                $collection->countBy()->all();
            }
        );
    }

    public function testCrossJoinIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->crossJoin([1]);
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->crossJoin([1], [2])->all();
        });
    }

    public function testDiffIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->diff([1, 2]);
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->diff([1, 2])->all();
        });
    }

    public function testDiffAssocIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->diffAssoc([1, 2]);
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->diffAssoc([1, 2])->all();
        });
    }

    public function testDiffAssocUsingIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->diffAssocUsing([1, 2], 'strcasecmp');
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->diffAssocUsing([1, 2], 'strcasecmp')->all();
        });
    }

    public function testDiffKeysIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->diffKeys([1, 2]);
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->diffKeys([1, 2])->all();
        });
    }

    public function testDiffKeysUsingIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->diffKeysUsing([1, 2], 'strcasecmp');
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->diffKeysUsing([1, 2], 'strcasecmp')->all();
        });
    }

    public function testDiffUsingIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->diffUsing([1, 2], 'strcasecmp');
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->diffUsing([1, 2], 'strcasecmp')->all();
        });
    }

    public function testDuplicatesIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->duplicates();
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->duplicates()->all();
        });
    }

    public function testDuplicatesStrictIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->duplicatesStrict();
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->duplicatesStrict()->all();
        });
    }

    public function testEachIsLazy()
    {
        $this->assertEnumerates(5, static function ($collection) {
            $collection->each(static function ($value, $key) {
                if ($value == 5) {
                    return false;
                }
            });
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->each(static function ($value, $key) {
                // Silence is golden!
            });
        });

        $this->assertEnumerates(5, static function ($collection) {
            foreach ($collection as $key => $value) {
                if ($value == 5) {
                    return false;
                }
            }
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            foreach ($collection as $key => $value) {
                // Silence is golden!
            }
        });
    }

    public function testEachSpreadIsLazy()
    {
        $data = $this->make([[1, 2], [3, 4], [5, 6], [7, 8]]);

        $this->assertEnumeratesCollection($data, 2, static function ($collection) {
            $collection->eachSpread(static function ($first, $second, $key) {
                if ($first == 3) {
                    return false;
                }
            });
        });

        $this->assertEnumeratesCollectionOnce($data, static function ($collection) {
            $collection->eachSpread(static function ($first, $second, $key) {
                // Silence is golden!
            });
        });
    }

    public function testEveryIsLazy()
    {
        $this->assertEnumerates(2, static function ($collection) {
            $collection->every(static function ($value) {
                return $value == 1;
            });
        });

        $data = $this->make([['a' => 1], ['a' => 2], ['a' => 3]]);

        $this->assertEnumeratesCollection($data, 2, static function ($collection) {
            $collection->every('a', 1);
        });
    }

    public function testExceptIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->except([1, 2]);
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->except([1, 2])->all();
        });
    }

    public function testFilterIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->filter(static function ($value) {
                return $value > 5;
            });
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->filter(static function ($value) {
                return $value > 5;
            })->all();
        });
    }

    public function testFirstIsLazy()
    {
        $this->assertEnumerates(1, static function ($collection) {
            $collection->first();
        });

        $this->assertEnumerates(2, static function ($collection) {
            $collection->first(static function ($value) {
                return $value == 2;
            });
        });
    }

    public function testFirstWhereIsLazy()
    {
        $data = $this->make([['a' => 1], ['a' => 2], ['a' => 3]]);

        $this->assertEnumeratesCollection($data, 2, static function ($collection) {
            $collection->firstWhere('a', 2);
        });
    }

    public function testFlatMapIsLazy()
    {
        $data = $this->make([1, 2, 3, 4, 5]);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->flatMap(static function ($values) {
                return array_sum($values);
            });
        });

        $this->assertEnumeratesCollection($data, 3, static function ($collection) {
            $collection->flatMap(static function ($value) {
                return range(1, $value);
            })->take(5)->all();
        });
    }

    public function testFlattenIsLazy()
    {
        $data = $this->make([1, [2, 3], [4, 5], [6, 7]]);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->flatten();
        });

        $this->assertEnumeratesCollection($data, 2, static function ($collection) {
            $collection->flatten()->take(3)->all();
        });
    }

    public function testFlipIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->flip();
        });

        $this->assertEnumerates(2, static function ($collection) {
            $collection->flip()->take(2)->all();
        });
    }

    public function testForPageIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->forPage(2, 10);
        });

        $this->assertEnumerates(20, static function ($collection) {
            $collection->forPage(2, 10)->all();
        });
    }

    public function testGetIsLazy()
    {
        $this->assertEnumerates(5, static function ($collection) {
            $collection->get(4);
        });
    }

    public function testGroupByIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->groupBy(static function ($value) {
                return $value % 5;
            });
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->groupBy(static function ($value) {
                return $value % 5;
            })->all();
        });
    }

    public function testHasIsLazy()
    {
        $this->assertEnumerates(5, static function ($collection) {
            $collection->has(4);
        });
    }

    public function testImplodeEnumeratesOnce()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->implode(', ');
        });
    }

    public function testIntersectIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->intersect([1, 2, 3]);
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->intersect([1, 2, 3])->all();
        });
    }

    public function testIntersectByKeysIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->intersectByKeys([1, 2, 3]);
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->intersectByKeys([1, 2, 3])->all();
        });
    }

    public function testIsEmptyIsLazy()
    {
        $this->assertEnumerates(1, static function ($collection) {
            $collection->isEmpty();
        });
    }

    public function testIsNotEmptyIsLazy()
    {
        $this->assertEnumerates(1, static function ($collection) {
            $collection->isNotEmpty();
        });
    }

    public function testJoinIsLazy()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->join(', ', ' and ');
        });
    }

    public function testJsonSerializeEnumeratesOnce()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->jsonSerialize();
        });
    }

    public function testKeyByIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->keyBy(static function ($value) {
                return "key-of-{$value}";
            });
        });

        $this->assertEnumerates(2, static function ($collection) {
            $collection->keyBy(static function ($value) {
                return "key-of-{$value}";
            })->take(2)->all();
        });
    }

    public function testKeysIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->keys();
        });

        $this->assertEnumerates(2, static function ($collection) {
            $collection->keys()->take(2)->all();
        });
    }

    public function testLastEnumeratesOnce()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->last();
        });
    }

    public function testMapIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->map(static function ($value) {
                return $value + 1;
            });
        });

        $this->assertEnumerates(2, static function ($collection) {
            $collection->map(static function ($value) {
                return $value + 1;
            })->take(2)->all();
        });
    }

    public function testMapIntoIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->mapInto(stdClass::class);
        });

        $this->assertEnumerates(2, static function ($collection) {
            $collection->mapInto(stdClass::class)->take(2)->all();
        });
    }

    public function testMapSpreadIsLazy()
    {
        $data = $this->make([[1, 2], [3, 4], [5, 6], [7, 8]]);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->mapSpread(static function ($first, $second, $key) {
                return $first + $second + $key;
            });
        });

        $this->assertEnumeratesCollection($data, 2, static function ($collection) {
            $collection->mapSpread(static function ($first, $second, $key) {
                return $first + $second + $key;
            })->take(2)->all();
        });
    }

    public function testMapToDictionaryIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->mapToDictionary(static function ($value, $key) {
                return [$value => $key];
            });
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->mapToDictionary(static function ($value, $key) {
                return [$value => $key];
            })->all();
        });
    }

    public function testMapToGroupsIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->mapToGroups(static function ($value, $key) {
                return [$value => $key];
            });
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->mapToGroups(static function ($value, $key) {
                return [$value => $key];
            })->all();
        });
    }

    public function testMapWithKeysIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->mapWithKeys(static function ($value, $key) {
                return [$value => $key];
            });
        });

        $this->assertEnumerates(2, static function ($collection) {
            $collection->mapWithKeys(static function ($value, $key) {
                return [$value => $key];
            })->take(2)->all();
        });
    }

    public function testMaxEnumeratesOnce()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->max();
        });
    }

    public function testMedianEnumeratesOnce()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->median();
        });
    }

    public function testMergeIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->merge([1, 2, 3]);
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->merge([1, 2, 3])->all();
        });
    }

    public function testMergeRecursiveIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->mergeRecursive([1, 2, 3]);
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->mergeRecursive([1, 2, 3])->all();
        });
    }

    public function testMinEnumeratesOnce()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->min();
        });
    }

    public function testModeEnumeratesOnce()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->mode();
        });
    }

    public function testNthIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->nth(5);
        });

        $this->assertEnumerates(11, static function ($collection) {
            $collection->nth(5)->take(3)->all();
        });
    }

    public function testOnlyIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->only(5, 6, 7);
        });

        $this->assertEnumerates(8, static function ($collection) {
            $collection->only(5, 6, 7)->all();
        });
    }

    public function testPadIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->pad(200, null);
            $collection->pad(-200, null);
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->pad(20, null)->all();
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->pad(-20, null)->all();
        });
    }

    public function testPartitionEnumeratesOnce()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->partition(static function ($value) {
                return $value > 10;
            });
        });
    }

    public function testPipeDoesNotEnumerate()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->pipe(static function () {
                // Silence is golden!
            });
        });
    }

    public function testPluckIsLazy()
    {
        $data = $this->make([['a' => 1], ['a' => 2], ['a' => 3], ['a' => 4]]);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->pluck('a');
        });

        $this->assertEnumeratesCollectionOnce($data, static function ($collection) {
            $collection->pluck('a')->all();
        });
    }

    public function testRandomEnumeratesOnce()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->random();
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->random(5);
        });
    }

    public function testRangeIsLazy()
    {
        $data = LazyCollection::range(10, 1000);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->take(50);
        });

        $this->assertEnumeratesCollection($data, 5, static function ($collection) {
            $collection->take(5)->all();
        });
    }

    public function testReduceEnumeratesOnce()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->reduce(static function ($total, $value) {
                return $total + $value;
            }, 0);
        });
    }

    public function testRejectIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->reject(static function ($value) {
                return $value % 2;
            });
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->reject(static function ($value) {
                return $value % 2;
            })->all();
        });
    }

    public function testRememberIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->remember();
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection = $collection->remember();

            $collection->all();
            $collection->all();
        });

        $this->assertEnumerates(5, static function ($collection) {
            $collection = $collection->remember();

            $collection->take(5)->all();
            $collection->take(5)->all();
        });
    }

    public function testReplaceIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->replace([5 => 'a', 10 => 'b']);
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->replace([5 => 'a', 10 => 'b'])->all();
        });
    }

    public function testReplaceRecursiveIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->replaceRecursive([5 => 'a', 10 => 'b']);
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->replaceRecursive([5 => 'a', 10 => 'b'])->all();
        });
    }

    public function testReverseIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->reverse();
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->reverse()->all();
        });
    }

    public function testSearchIsLazy()
    {
        $this->assertEnumerates(5, static function ($collection) {
            $collection->search(5);
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->search('missing');
        });
    }

    public function testShuffleIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->shuffle();
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->shuffle()->all();
        });
    }

    public function testSkipIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->skip(10);
        });

        $this->assertEnumerates(12, static function ($collection) {
            $collection->skip(10)->take(2)->all();
        });
    }

    public function testSkipUntilIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->skipUntil(INF);
        });

        $this->assertEnumerates(10, static function ($collection) {
            $collection->skipUntil(10)->first();
        });

        $this->assertEnumerates(10, static function ($collection) {
            $collection->skipUntil(static function ($item) {
                return $item === 10;
            })->first();
        });
    }

    public function testSkipWhileIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->skipWhile(1);
        });

        $this->assertEnumerates(2, static function ($collection) {
            $collection->skipWhile(1)->first();
        });

        $this->assertEnumerates(10, static function ($collection) {
            $collection->skipWhile(static function ($item) {
                return $item < 10;
            })->first();
        });
    }

    public function testSliceIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->slice(2);
            $collection->slice(2, 2);
            $collection->slice(-2, 2);
        });

        $this->assertEnumerates(4, static function ($collection) {
            $collection->slice(2)->take(2)->all();
        });

        $this->assertEnumerates(4, static function ($collection) {
            $collection->slice(2, 2)->all();
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->slice(-2, 2)->all();
        });
    }

    public function testSomeIsLazy()
    {
        $this->assertEnumerates(5, static function ($collection) {
            $collection->some(static function ($value) {
                return $value == 5;
            });
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->some(static function ($value) {
                return false;
            });
        });
    }

    public function testSortIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->sort();
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->sort()->all();
        });
    }

    public function testSortDescIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->sortDesc();
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->sortDesc()->all();
        });
    }

    public function testSortByIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->sortBy(static function ($value) {
                return $value;
            });
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->sortBy(static function ($value) {
                return $value;
            })->all();
        });
    }

    public function testSortByDescIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->sortByDesc(static function ($value) {
                return $value;
            });
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->sortByDesc(static function ($value) {
                return $value;
            })->all();
        });
    }

    public function testSortKeysIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->sortKeys();
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->sortKeys()->all();
        });
    }

    public function testSortKeysDescIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->sortKeysDesc();
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->sortKeysDesc()->all();
        });
    }

    public function testSplitIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->split(4);
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->split(4)->all();
        });
    }

    public function testSumEnumeratesOnce()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->sum();
        });
    }

    public function testTakeIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->take(10);
        });

        $this->assertEnumerates(10, static function ($collection) {
            $collection->take(10)->all();
        });
    }

    public function testTakeUntilIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->takeUntil(INF);
        });

        $this->assertEnumerates(10, static function ($collection) {
            $collection->takeUntil(10)->all();
        });

        $this->assertEnumerates(10, static function ($collection) {
            $collection->takeUntil(static function ($item) {
                return $item === 10;
            })->all();
        });
    }

    public function testTakeWhileIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->takeWhile(0);
        });

        $this->assertEnumerates(1, static function ($collection) {
            $collection->takeWhile(0)->all();
        });

        $this->assertEnumerates(10, static function ($collection) {
            $collection->takeWhile(static function ($item) {
                return $item < 10;
            })->all();
        });
    }

    public function testTapDoesNotEnumerate()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->tap(static function ($collection) {
                // Silence is golden!
            });
        });
    }

    public function testTapEachIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->tapEach(static function ($value) {
                // Silence is golden!
            });
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->tapEach(static function ($value) {
                // Silence is golden!
            })->all();
        });
    }

    public function testTimesIsLazy()
    {
        $data = LazyCollection::times(INF);

        $this->assertEnumeratesCollection($data, 2, static function ($collection) {
            $collection->take(2)->all();
        });
    }

    public function testToArrayEnumeratesOnce()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->toArray();
        });
    }

    public function testToJsonEnumeratesOnce()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->toJson();
        });
    }

    public function testUnionIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->union([4, 5, 6]);
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->union([4, 5, 6])->all();
        });
    }

    public function testUniqueIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->unique();
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->unique()->all();
        });
    }

    public function testUniqueStrictIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->uniqueStrict();
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            $collection->uniqueStrict()->all();
        });
    }

    public function testUnlessDoesNotEnumerate()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->unless(true, static function ($collection) {
                // Silence is golden!
            });

            $collection->unless(false, static function ($collection) {
                // Silence is golden!
            });
        });
    }

    public function testUnlessEmptyIsLazy()
    {
        $this->assertEnumerates(1, static function ($collection) {
            $collection->unlessEmpty(static function ($collection) {
                // Silence is golden!
            });
        });
    }

    public function testUnlessNotEmptyIsLazy()
    {
        $this->assertEnumerates(1, static function ($collection) {
            $collection->unlessNotEmpty(static function ($collection) {
                // Silence is golden!
            });
        });
    }

    public function testUnwrapEnumeratesOne()
    {
        $this->assertEnumeratesOnce(static function ($collection) {
            LazyCollection::unwrap($collection);
        });
    }

    public function testValuesIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->values();
        });

        $this->assertEnumerates(2, static function ($collection) {
            $collection->values()->take(2)->all();
        });
    }

    public function testWhenDoesNotEnumerate()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            $collection->when(true, static function ($collection) {
                // Silence is golden!
            });

            $collection->when(false, static function ($collection) {
                // Silence is golden!
            });
        });
    }

    public function testWhenEmptyIsLazy()
    {
        $this->assertEnumerates(1, static function ($collection) {
            $collection->whenEmpty(static function ($collection) {
                // Silence is golden!
            });
        });
    }

    public function testWhenNotEmptyIsLazy()
    {
        $this->assertEnumerates(1, static function ($collection) {
            $collection->whenNotEmpty(static function ($collection) {
                // Silence is golden!
            });
        });
    }

    public function testWhereIsLazy()
    {
        $data = $this->make([['a' => 1], ['a' => 2], ['a' => 3], ['a' => 4]]);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->where('a', '<', 3);
        });

        $this->assertEnumeratesCollection($data, 1, static function ($collection) {
            $collection->where('a', '<', 3)->take(1)->all();
        });
    }

    public function testWhereBetweenIsLazy()
    {
        $data = $this->make([['a' => 1], ['a' => 2], ['a' => 3], ['a' => 4]]);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->whereBetween('a', [2, 4]);
        });

        $this->assertEnumeratesCollection($data, 2, static function ($collection) {
            $collection->whereBetween('a', [2, 4])->take(1)->all();
        });
    }

    public function testWhereInIsLazy()
    {
        $data = $this->make([['a' => 1], ['a' => 2], ['a' => 3], ['a' => 4]]);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->whereIn('a', [2, 3]);
        });

        $this->assertEnumeratesCollection($data, 2, static function ($collection) {
            $collection->whereIn('a', [2, 3])->take(1)->all();
        });
    }

    public function testWhereInstanceOfIsLazy()
    {
        $data = $this->make(['a' => 0])->concat(
            $this->make([['a' => 1], ['a' => 2], ['a' => 3], ['a' => 4]])
                 ->mapInto(stdClass::class)
         );

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->whereInstanceOf(stdClass::class);
        });

        $this->assertEnumeratesCollection($data, 2, static function ($collection) {
            $collection->whereInstanceOf(stdClass::class)->take(1)->all();
        });
    }

    public function testWhereInStrictIsLazy()
    {
        $data = $this->make([['a' => 1], ['a' => 2], ['a' => 3], ['a' => 4]]);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->whereInStrict('a', ['2', 3]);
        });

        $this->assertEnumeratesCollection($data, 3, static function ($collection) {
            $collection->whereInStrict('a', ['2', 3])->take(1)->all();
        });
    }

    public function testWhereNotBetweenIsLazy()
    {
        $data = $this->make([['a' => 1], ['a' => 2], ['a' => 3], ['a' => 4]]);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->whereNotBetween('a', [1, 2]);
        });

        $this->assertEnumeratesCollection($data, 3, static function ($collection) {
            $collection->whereNotBetween('a', [1, 2])->take(1)->all();
        });
    }

    public function testWhereNotInIsLazy()
    {
        $data = $this->make([['a' => 1], ['a' => 2], ['a' => 3], ['a' => 4]]);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->whereNotIn('a', [1, 2]);
        });

        $this->assertEnumeratesCollection($data, 3, static function ($collection) {
            $collection->whereNotIn('a', [1, 2])->take(1)->all();
        });
    }

    public function testWhereNotInStrictIsLazy()
    {
        $data = $this->make([['a' => 1], ['a' => 2], ['a' => 3], ['a' => 4]]);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->whereNotInStrict('a', ['1', 2]);
        });

        $this->assertEnumeratesCollection($data, 2, static function ($collection) {
            $collection->whereNotInStrict('a', [1, '2'])->take(1)->all();
        });
    }

    public function testWhereNotNullIsLazy()
    {
        $data = $this->make([['a' => 1], ['a' => null], ['a' => 2], ['a' => 3]]);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->whereNotNull('a');
        });

        $this->assertEnumeratesCollectionOnce($data, static function ($collection) {
            $collection->whereNotNull('a')->all();
        });

        $data = $this->make([1, null, 2, null, 3]);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->whereNotNull();
        });

        $this->assertEnumeratesCollectionOnce($data, static function ($collection) {
            $collection->whereNotNull()->all();
        });
    }

    public function testWhereNullIsLazy()
    {
        $data = $this->make([['a' => 1], ['a' => null], ['a' => 2], ['a' => 3]]);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->whereNull('a');
        });

        $this->assertEnumeratesCollectionOnce($data, static function ($collection) {
            $collection->whereNull('a')->all();
        });

        $data = $this->make([1, null, 2, null, 3]);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->whereNull();
        });

        $this->assertEnumeratesCollectionOnce($data, static function ($collection) {
            $collection->whereNull()->all();
        });
    }

    public function testWhereStrictIsLazy()
    {
        $data = $this->make([['a' => 1], ['a' => 2], ['a' => 3], ['a' => 4]]);

        $this->assertDoesNotEnumerateCollection($data, static function ($collection) {
            $collection->whereStrict('a', 2);
        });

        $this->assertEnumeratesCollection($data, 2, static function ($collection) {
            $collection->whereStrict('a', 2)->take(1)->all();
        });
    }

    public function testWrapIsLazy()
    {
        $this->assertDoesNotEnumerate(static function ($collection) {
            LazyCollection::wrap($collection);
        });

        $this->assertEnumeratesOnce(static function ($collection) {
            LazyCollection::wrap($collection)->all();
        });
    }

    public function testZipIsLazy()
    {
        $firstEnumerations = 0;
        $secondEnumerations = 0;
        $first = $this->countEnumerations($this->make([1, 2]), $firstEnumerations);
        $second = $this->countEnumerations($this->make([1, 2]), $secondEnumerations);

        $first->zip($second);

        $this->assertEnumerations(0, $firstEnumerations);
        $this->assertEnumerations(0, $secondEnumerations);

        $first->zip($second)->take(1)->all();

        $this->assertEnumerations(1, $firstEnumerations);
        $this->assertEnumerations(1, $secondEnumerations);
    }

    protected function make($source)
    {
        return new LazyCollection($source);
    }

    protected function assertDoesNotEnumerate(callable $executor)
    {
        $this->assertEnumerates(0, $executor);
    }

    protected function assertDoesNotEnumerateCollection(
        LazyCollection $collection,
        callable $executor
    ) {
        $this->assertEnumeratesCollection($collection, 0, $executor);
    }

    protected function assertEnumerates($count, callable $executor)
    {
        $this->assertEnumeratesCollection(
            LazyCollection::times(100),
            $count,
            $executor
        );
    }

    protected function assertEnumeratesCollection(
        LazyCollection $collection,
        $count,
        callable $executor
    ) {
        $enumerated = 0;

        $data = $this->countEnumerations($collection, $enumerated);

        $executor($data);

        $this->assertEnumerations($count, $enumerated);
    }

    protected function assertEnumeratesOnce(callable $executor)
    {
        $this->assertEnumeratesCollectionOnce(LazyCollection::times(10), $executor);
    }

    protected function assertEnumeratesCollectionOnce(
        LazyCollection $collection,
        callable $executor
    ) {
        $enumerated = 0;
        $count = $collection->count();
        $collection = $this->countEnumerations($collection, $enumerated);

        $executor($collection);

        $this->assertEquals(
            $count,
            $enumerated,
            $count > $enumerated ? 'Failed to enumerate in full.' : 'Enumerated more than once.'
        );
    }

    protected function assertEnumerations($expected, $actual)
    {
        $this->assertEquals(
            $expected,
            $actual,
            "Failed asserting that {$actual} items that were enumerated matches expected {$expected}."
        );
    }

    protected function countEnumerations(LazyCollection $collection, &$count)
    {
        return $collection->tapEach(static function () use (&$count) {
            $count++;
        });
    }
}
