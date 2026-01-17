<?php

namespace Illuminate\Tests\Validation;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\AiModerationResult;
use Illuminate\Contracts\Validation\AiModerationVerifier;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\AiModerated;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationAiModeratedRuleTest extends TestCase
{
    public function testItPassesWhenContentIsSafe()
    {
        $this->registerMockVerifier(flagged: false);

        $this->passes(Rule::aiModerated(), ['Hello world', 'This is safe content']);
    }

    public function testItFailsWhenContentIsFlagged()
    {
        $this->registerMockVerifier(flagged: true, categories: ['hate']);

        $this->fails(Rule::aiModerated(), ['harmful content'], [
            'validation.ai_moderated.categories',
        ]);
    }

    public function testItFailsWithGenericMessageWhenNoCategories()
    {
        $this->registerMockVerifier(flagged: true, categories: []);

        $this->fails(Rule::aiModerated(), ['harmful content'], [
            'validation.ai_moderated.flagged',
        ]);
    }

    public function testItPassesForEmptyValues()
    {
        $this->registerMockVerifier(flagged: true);

        $this->passes(Rule::aiModerated(), ['', null]);
    }

    public function testItPassesForNonStringValues()
    {
        $this->registerMockVerifier(flagged: true);

        $v = new Validator(
            resolve('translator'),
            ['content' => 123],
            ['content' => Rule::aiModerated()]
        );

        $this->assertTrue($v->passes());
    }

    public function testCategoriesConfiguration()
    {
        $capturedData = null;
        $this->registerMockVerifier(flagged: false, captureData: function ($data) use (&$capturedData) {
            $capturedData = $data;
        });

        $rule = Rule::aiModerated()->categories(['hate', 'violence']);

        $v = new Validator(
            resolve('translator'),
            ['content' => 'test'],
            ['content' => $rule]
        );

        $v->passes();

        $this->assertEquals(['hate', 'violence'], $capturedData['categories']);
    }

    public function testFluentCategoryMethods()
    {
        $rule = Rule::aiModerated()
            ->noHate()
            ->noViolence()
            ->noSexual()
            ->noHarassment();

        $appliedRules = $rule->appliedRules();

        $this->assertContains('hate', $appliedRules['categories']);
        $this->assertContains('violence', $appliedRules['categories']);
        $this->assertContains('sexual', $appliedRules['categories']);
        $this->assertContains('harassment', $appliedRules['categories']);
    }

    public function testStrictModeration()
    {
        $rule = Rule::aiModerated()->strict();

        $appliedRules = $rule->appliedRules();

        $this->assertContains('hate', $appliedRules['categories']);
        $this->assertContains('hate/threatening', $appliedRules['categories']);
        $this->assertContains('harassment', $appliedRules['categories']);
        $this->assertContains('harassment/threatening', $appliedRules['categories']);
        $this->assertContains('self-harm', $appliedRules['categories']);
        $this->assertContains('self-harm/intent', $appliedRules['categories']);
        $this->assertContains('self-harm/instructions', $appliedRules['categories']);
        $this->assertContains('sexual', $appliedRules['categories']);
        $this->assertContains('sexual/minors', $appliedRules['categories']);
        $this->assertContains('violence', $appliedRules['categories']);
        $this->assertContains('violence/graphic', $appliedRules['categories']);
    }

    public function testThresholdConfiguration()
    {
        $capturedData = null;
        $this->registerMockVerifier(flagged: false, captureData: function ($data) use (&$capturedData) {
            $capturedData = $data;
        });

        $rule = Rule::aiModerated()->threshold(0.8);

        $v = new Validator(
            resolve('translator'),
            ['content' => 'test'],
            ['content' => $rule]
        );

        $v->passes();

        $this->assertEquals(0.8, $capturedData['threshold']);
    }

    public function testThresholdIsClamped()
    {
        $rule = Rule::aiModerated()->threshold(1.5);
        $this->assertEquals(1.0, $rule->appliedRules()['threshold']);

        $rule = Rule::aiModerated()->threshold(-0.5);
        $this->assertEquals(0.0, $rule->appliedRules()['threshold']);
    }

    public function testStrictThreshold()
    {
        $rule = Rule::aiModerated()->strictThreshold();

        $this->assertEquals(0.2, $rule->appliedRules()['threshold']);
    }

    public function testLenientThreshold()
    {
        $rule = Rule::aiModerated()->lenientThreshold();

        $this->assertEquals(0.8, $rule->appliedRules()['threshold']);
    }

    public function testProviderConfiguration()
    {
        $capturedData = null;
        $this->registerMockVerifier(flagged: false, captureData: function ($data) use (&$capturedData) {
            $capturedData = $data;
        });

        $rule = Rule::aiModerated()->using('openai');

        $v = new Validator(
            resolve('translator'),
            ['content' => 'test'],
            ['content' => $rule]
        );

        $v->passes();

        $this->assertEquals('openai', $capturedData['provider']);
    }

    public function testCacheConfiguration()
    {
        $capturedData = null;
        $this->registerMockVerifier(flagged: false, captureData: function ($data) use (&$capturedData) {
            $capturedData = $data;
        });

        $rule = Rule::aiModerated()->remember(3600);

        $v = new Validator(
            resolve('translator'),
            ['content' => 'test'],
            ['content' => $rule]
        );

        $v->passes();

        $this->assertEquals(3600, $capturedData['cacheFor']);
    }

    public function testCustomVerifier()
    {
        $customResult = new class implements AiModerationResult 
        {
            public function flagged(): bool
            {
                return true;
            }

            public function flaggedCategories(): array
            {
                return ['custom-category'];
            }

            public function scores(): array
            {
                return ['custom-category' => 0.9];
            }
        };

        $rule = Rule::aiModerated()->verifyUsing(function ($data) use ($customResult) {
            return $customResult;
        });

        $v = new Validator(
            resolve('translator'),
            ['content' => 'test'],
            ['content' => $rule]
        );

        $this->assertTrue($v->fails());
    }

    public function testDefaultCallback()
    {
        AiModerated::defaults(function () {
            return (new AiModerated)->strict()->threshold(0.3);
        });

        $rule = AiModerated::default();
        $appliedRules = $rule->appliedRules();

        $this->assertContains('hate', $appliedRules['categories']);
        $this->assertEquals(0.3, $appliedRules['threshold']);
    }

    public function testConditionalConfiguration()
    {
        $isStrict = true;

        $rule = Rule::aiModerated()
            ->when($isStrict, function ($rule) {
                $rule->strict()->strictThreshold();
            });

        $appliedRules = $rule->appliedRules();

        $this->assertContains('hate', $appliedRules['categories']);
        $this->assertEquals(0.2, $appliedRules['threshold']);

        $isStrict = false;

        $rule = Rule::aiModerated()
            ->when($isStrict, function ($rule) {
                $rule->strict();
            });

        $appliedRules = $rule->appliedRules();

        $this->assertEmpty($appliedRules['categories']);
    }

    public function testDataAwareRule()
    {
        $this->registerMockVerifier(flagged: false);

        $rule = Rule::aiModerated();

        $v = new Validator(
            resolve('translator'),
            ['content' => 'test', 'other_field' => 'value'],
            ['content' => $rule]
        );

        $v->passes();

        // The rule should have access to all data
        $this->assertIsArray($rule->appliedRules());
    }

    public function testMultipleFlaggedCategories()
    {
        $this->registerMockVerifier(flagged: true, categories: ['hate', 'violence', 'harassment']);

        $v = new Validator(
            resolve('translator'),
            ['content' => 'harmful content'],
            ['content' => Rule::aiModerated()]
        );

        $this->assertTrue($v->fails());

        // The message key should include 'categories' indicating multiple categories were flagged
        $message = $v->messages()->first('content');
        $this->assertStringContainsString('ai_moderated.categories', $message);
    }

    public function testAllFluentCategoryMethods()
    {
        $rule = Rule::aiModerated()
            ->noHate()
            ->noHateThreatening()
            ->noHarassment()
            ->noHarassmentThreatening()
            ->noSelfHarm()
            ->noSelfHarmIntent()
            ->noSelfHarmInstructions()
            ->noSexual()
            ->noSexualMinors()
            ->noViolence()
            ->noViolenceGraphic();

        $appliedRules = $rule->appliedRules();

        $this->assertCount(11, $appliedRules['categories']);
        $this->assertContains('hate', $appliedRules['categories']);
        $this->assertContains('hate/threatening', $appliedRules['categories']);
        $this->assertContains('harassment', $appliedRules['categories']);
        $this->assertContains('harassment/threatening', $appliedRules['categories']);
        $this->assertContains('self-harm', $appliedRules['categories']);
        $this->assertContains('self-harm/intent', $appliedRules['categories']);
        $this->assertContains('self-harm/instructions', $appliedRules['categories']);
        $this->assertContains('sexual', $appliedRules['categories']);
        $this->assertContains('sexual/minors', $appliedRules['categories']);
        $this->assertContains('violence', $appliedRules['categories']);
        $this->assertContains('violence/graphic', $appliedRules['categories']);
    }

    public function testCategoriesCanBePassedAsVariadicArguments()
    {
        $rule = Rule::aiModerated()->categories('hate', 'violence', 'sexual');

        $appliedRules = $rule->appliedRules();

        $this->assertCount(3, $appliedRules['categories']);
        $this->assertContains('hate', $appliedRules['categories']);
        $this->assertContains('violence', $appliedRules['categories']);
        $this->assertContains('sexual', $appliedRules['categories']);
    }

    public function testRuleCanBeChainedFluentlyInAnyOrder()
    {
        $rule = Rule::aiModerated()
            ->using('anthropic')
            ->threshold(0.7)
            ->remember(1800)
            ->noHate()
            ->noViolence();

        $appliedRules = $rule->appliedRules();

        $this->assertEquals('anthropic', $appliedRules['provider']);
        $this->assertEquals(0.7, $appliedRules['threshold']);
        $this->assertEquals(1800, $appliedRules['cacheFor']);
        $this->assertContains('hate', $appliedRules['categories']);
        $this->assertContains('violence', $appliedRules['categories']);
    }

    protected function passes($rule, $values)
    {
        $this->assertValidationRules($rule, $values, true, []);
    }

    protected function fails($rule, $values, $messages)
    {
        $this->assertValidationRules($rule, $values, false, $messages);
    }

    protected function assertValidationRules($rule, $values, $result, $messages)
    {
        foreach ($values as $value) {
            $v = new Validator(
                resolve('translator'),
                ['content' => $value],
                ['content' => is_object($rule) ? clone $rule : $rule]
            );

            $this->assertSame($result, $v->passes());

            $this->assertSame(
                $result ? [] : ['content' => $messages],
                $v->messages()->toArray()
            );
        }
    }

    protected function registerMockVerifier(bool $flagged, array $categories = [], ?Closure $captureData = null)
    {
        $container = Container::getInstance();

        $mockResult = new class($flagged, $categories) implements AiModerationResult 
        {
            public function __construct(
                private bool $flagged,
                private array $categories
            ) {
            }

            public function flagged(): bool
            {
                return $this->flagged;
            }

            public function flaggedCategories(): array
            {
                return $this->categories;
            }

            public function scores(): array
            {
                return array_fill_keys($this->categories, 0.9);
            }
        };

        $mockVerifier = new class($mockResult, $captureData) implements AiModerationVerifier 
        {
            public function __construct(
                private AiModerationResult $result,
                private ?Closure $captureData
            ) {
            }

            public function verify(array $data): AiModerationResult
            {
                if ($this->captureData) {
                    ($this->captureData)($data);
                }

                return $this->result;
            }
        };

        $container->instance(AiModerationVerifier::class, $mockVerifier);
    }

    protected function setUp(): void
    {
        $container = Container::getInstance();

        $container->bind('translator', function () {
            return new Translator(
                new ArrayLoader, 'en'
            );
        });

        Facade::setFacadeApplication($container);

        (new ValidationServiceProvider($container))->register();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        Facade::clearResolvedInstances();

        Facade::setFacadeApplication(null);

        AiModerated::$defaultCallback = null;

        parent::tearDown();
    }
}
