<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\CodeQuality\Rector\FuncCall\SortCallLikeNamedArgsRector;
use Rector\CodeQuality\Rector\Identical\StrlenZeroToIdenticalEmptyStringRector;
use Rector\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector;
use Rector\CodingStyle\Rector\Closure\ClosureDelegatingCallToFirstClassCallableRector;
use Rector\CodingStyle\Rector\FuncCall\ClosureFromCallableToFirstClassCallableRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRector;
use Rector\Config\RectorConfig;
use Rector\Php55\Rector\Class_\ClassConstantToSelfClassRector;
use Rector\Php55\Rector\ClassConstFetch\StaticToSelfOnFinalClassRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php56\Rector\FuncCall\PowToExpRector;
use Rector\Php70\Rector\FuncCall\RandomFunctionRector;
use Rector\Php70\Rector\MethodCall\ThisCallOnStaticMethodToStaticCallRector;
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
use Rector\Php84\Rector\Class_\DeprecatedAnnotationToDeprecatedAttributeRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\NarrowUnusedSetUpDefinedPropertyRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\AddInstanceofAssertForNullableArgumentRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\AddInstanceofAssertForNullableInstanceRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\DataProviderArrayItemsNewLinedRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\NoSetupWithParentCallOverrideRector;
use Rector\PHPUnit\CodeQuality\Rector\Expression\AssertArrayCastedObjectToAssertSameRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\AssertCompareOnCountableWithMethodToAssertCountRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\AssertComparisonToSpecificMethodRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\AssertEmptyNullableObjectToAssertInstanceofRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\AssertEqualsOrAssertSameFloatParameterToSpecificMethodsTypeRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\AssertEqualsToSameRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\AssertIssetToSpecificMethodRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\ScalarArgumentToExpectedParamTypeRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\StringCastAssertStringContainsStringRector;
use Rector\PHPUnit\CodeQuality\Rector\StmtsAwareInterface\DeclareStrictTypesTestsRector;
use Rector\PHPUnit\PHPUnit120\Rector\CallLike\CreateStubOverCreateMockArgRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Privatization\Rector\Class_\FinalizeTestCaseClassRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;

$skipPHPUnitSetList = [
    AddInstanceofAssertForNullableArgumentRector::class,
    AddInstanceofAssertForNullableInstanceRector::class,
    AssertArrayCastedObjectToAssertSameRector::class,
    AssertCompareOnCountableWithMethodToAssertCountRector::class,
    AssertComparisonToSpecificMethodRector::class,
    AssertEmptyNullableObjectToAssertInstanceofRector::class,
    AssertEqualsOrAssertSameFloatParameterToSpecificMethodsTypeRector::class,
    AssertEqualsToSameRector::class,
    AssertIssetToSpecificMethodRector::class,
    CreateStubOverCreateMockArgRector::class,
    DataProviderArrayItemsNewLinedRector::class,
    DeclareStrictTypesTestsRector::class,
    FinalizeTestCaseClassRector::class,
    NarrowUnusedSetUpDefinedPropertyRector::class,
    NoSetupWithParentCallOverrideRector::class,
    ScalarArgumentToExpectedParamTypeRector::class,
    StaticToSelfOnFinalClassRector::class,
    StringCastAssertStringContainsStringRector::class,
    YieldDataProviderRector::class,
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
        ...$skipPHPUnitSetList,
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
        DeprecatedAnnotationToDeprecatedAttributeRector::class,
        DynamicClassConstFetchRector::class,
        FunctionFirstClassCallableRector::class,
        GetDebugTypeRector::class,
        NullToStrictStringFuncCallArgRector::class,
        PowToExpRector::class,
        RandomFunctionRector::class,
        ReadOnlyClassRector::class,
        ReadOnlyPropertyRector::class,
        RemoveExtraParametersRector::class,
        ReturnNeverTypeRector::class,
        StringClassNameToClassConstantRector::class,
        StringableForToStringRector::class,
        TernaryToNullCoalescingRector::class,
        ThisCallOnStaticMethodToStaticCallRector::class,
        'tests/Foundation/fixtures/bad-syntax-strategy.php',
    ])
    ->withRules([
        CompactToVariablesRector::class,
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
    ->withSets([
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
        PHPUnitSetList::PHPUNIT_110,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        PHPUnitSetList::PHPUNIT_MOCK_TO_STUB,
    ])
    ->withPhpSets(php83: true);
