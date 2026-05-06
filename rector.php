<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\FuncCall\SortCallLikeNamedArgsRector;
use Rector\CodeQuality\Rector\Identical\StrlenZeroToIdenticalEmptyStringRector;
use Rector\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector;
use Rector\CodingStyle\Rector\Closure\ClosureDelegatingCallToFirstClassCallableRector;
use Rector\CodingStyle\Rector\FuncCall\ClosureFromCallableToFirstClassCallableRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRector;
use Rector\Config\RectorConfig;
use Rector\Php55\Rector\Class_\ClassConstantToSelfClassRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php56\Rector\FuncCall\PowToExpRector;
use Rector\Php70\Rector\FuncCall\RandomFunctionRector;
use Rector\Php70\Rector\If_\IfToSpaceshipRector;
use Rector\Php70\Rector\MethodCall\ThisCallOnStaticMethodToStaticCallRector;
use Rector\Php70\Rector\StaticCall\StaticCallOnNonStaticToInstanceCallRector;
use Rector\Php70\Rector\Ternary\TernaryToNullCoalescingRector;
use Rector\Php71\Rector\BinaryOp\BinaryOpBetweenNumberAndStringRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Class_\StringableForToStringRector;
use Rector\Php80\Rector\ClassConstFetch\ClassOnThisVariableObjectRector;
use Rector\Php80\Rector\FuncCall\ClassOnObjectRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php80\Rector\Ternary\GetDebugTypeRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Php83\Rector\ClassConst\AddTypeToConstRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Php83\Rector\FuncCall\DynamicClassConstFetchRector;
use Rector\PHPUnit\CodeQuality\Rector\CallLike\DirectInstanceOverMockArgRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\ConstructClassMethodToSetUpTestCaseRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\InlineStubPropertyToCreateStubMethodCallRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\NarrowUnusedSetUpDefinedPropertyRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\RemoveNeverUsedMockPropertyRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\EntityDocumentCreateMockToDirectNewRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\RemoveEmptyTestMethodRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\RemoveStandaloneCreateMockRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\ReplaceTestAnnotationWithPrefixedFunctionRector;
use Rector\PHPUnit\CodeQuality\Rector\Foreach_\SimplifyForeachInstanceOfRector;
use Rector\PHPUnit\CodeQuality\Rector\FuncCall\AssertFuncCallToPHPUnitAssertRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\MergeWithCallableAndWillReturnRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\NarrowIdenticalWithConsecutiveRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\NarrowSingleWillReturnCallbackRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\RemoveExpectAnyFromMockRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\SimplerWithIsInstanceOfRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\SingleWithConsecutiveToWithRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\UseSpecificWillMethodRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\UseSpecificWithMethodRector;
use Rector\PHPUnit\PHPUnit60\Rector\MethodCall\GetMockBuilderGetMockToCreateMockRector;
use Rector\PHPUnit\PHPUnit90\Rector\MethodCall\ReplaceAtMethodWithDesiredMatcherRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;

$testsuiteRules = [
    AssertFuncCallToPHPUnitAssertRector::class,
    ConstructClassMethodToSetUpTestCaseRector::class,
    DirectInstanceOverMockArgRector::class,
    EntityDocumentCreateMockToDirectNewRector::class,
    GetMockBuilderGetMockToCreateMockRector::class,
    InlineStubPropertyToCreateStubMethodCallRector::class,
    MergeWithCallableAndWillReturnRector::class,
    NarrowIdenticalWithConsecutiveRector::class,
    NarrowSingleWillReturnCallbackRector::class,
    NarrowUnusedSetUpDefinedPropertyRector::class,
    PreferPHPUnitThisCallRector::class,
    RemoveEmptyTestMethodRector::class,
    RemoveExpectAnyFromMockRector::class,
    RemoveNeverUsedMockPropertyRector::class,
    RemoveStandaloneCreateMockRector::class,
    ReplaceAtMethodWithDesiredMatcherRector::class,
    ReplaceTestAnnotationWithPrefixedFunctionRector::class,
    SimplerWithIsInstanceOfRector::class,
    SimplifyForeachInstanceOfRector::class,
    SingleWithConsecutiveToWithRector::class,
    UseSpecificWillMethodRector::class,
    UseSpecificWithMethodRector::class,
];

return RectorConfig::configure()
    ->withRootFiles()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/types',
    ])
    ->withSkip([
        AddOverrideAttributeToOverriddenMethodsRector::class,
        AddTypeToConstRector::class,
        ArrayToFirstClassCallableRector::class,
        ArrowFunctionDelegatingCallToFirstClassCallableRector::class,
        BinaryOpBetweenNumberAndStringRector::class,
        ChangeSwitchToMatchRector::class,
        ClassConstantToSelfClassRector::class,
        ClassOnObjectRector::class,
        ClassOnThisVariableObjectRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        ClosureDelegatingCallToFirstClassCallableRector::class,
        ClosureFromCallableToFirstClassCallableRector::class,
        ClosureToArrowFunctionRector::class,
        DynamicClassConstFetchRector::class,
        FunctionFirstClassCallableRector::class,
        GetDebugTypeRector::class,
        IfToSpaceshipRector::class,
        NullToStrictStringFuncCallArgRector::class,
        PowToExpRector::class,
        RandomFunctionRector::class,
        ReadOnlyClassRector::class,
        ReadOnlyPropertyRector::class,
        RemoveExtraParametersRector::class,
        ReturnNeverTypeRector::class,
        StaticCallOnNonStaticToInstanceCallRector::class,
        StringClassNameToClassConstantRector::class,
        StringableForToStringRector::class,
        TernaryToNullCoalescingRector::class,
        ThisCallOnStaticMethodToStaticCallRector::class,
        'tests/Foundation/fixtures/bad-syntax-strategy.php',
    ])
    ->withRules([
        ...$testsuiteRules,
        CountArrayToEmptyArrayComparisonRector::class,
        SortCallLikeNamedArgsRector::class,
        StrlenZeroToIdenticalEmptyStringRector::class,
    ])
    ->withPreparedSets(
        deadCode: false,
        codeQuality: false,
        codingStyle: false,
        typeDeclarations: false,
        typeDeclarationDocblocks: false,
        privatization: false,
        naming: false,
        instanceOf: false,
        earlyReturn: false,
    )
    ->withPhpSets(php83: true);
