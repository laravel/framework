<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Validator;

class ValidationConditionalRuleErrorMessagesTest extends ValidationValidatorTest
{
    public function testRequiredIfPreservesCaseOfActualValue()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');

        // Test uppercase preservation
        $v = new Validator($trans, ['field1' => 'AA'], ['field2' => 'required_if:field1,AA']);
        $this->assertTrue($v->fails());
        $this->assertSame('The field2 field is required when field1 is AA.', $v->messages()->first('field2'));

        // Test mixed case preservation
        $v = new Validator($trans, ['status' => 'Active'], ['reason' => 'required_if:status,Active']);
        $this->assertTrue($v->fails());
        $this->assertSame('The reason field is required when status is Active.', $v->messages()->first('reason'));

        // Test lowercase (should still work)
        $v = new Validator($trans, ['status' => 'inactive'], ['reason' => 'required_if:status,inactive']);
        $this->assertTrue($v->fails());
        $this->assertSame('The reason field is required when status is inactive.', $v->messages()->first('reason'));
    }

    public function testRequiredIfWithMultipleValuesShowsActualSubmittedValue()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');

        // When user submits 'review', message should show 'review' (not 'pending')
        $v = new Validator($trans, ['status' => 'review'], ['reason' => 'required_if:status,pending,active,review']);
        $this->assertTrue($v->fails());
        $this->assertSame('The reason field is required when status is review.', $v->messages()->first('reason'));

        // When user submits 'active', message should show 'active'
        $v = new Validator($trans, ['status' => 'active'], ['reason' => 'required_if:status,pending,active,review']);
        $this->assertTrue($v->fails());
        $this->assertSame('The reason field is required when status is active.', $v->messages()->first('reason'));
    }

    public function testRequiredIfWithBooleanValuesShowsCorrectBoolean()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');

        // When user submits true (as boolean or string)
        $v = new Validator($trans, ['is_active' => true], ['reason' => 'required_if:is_active,true']);
        $this->assertTrue($v->fails());
        $this->assertSame('The reason field is required when is active is true.', $v->messages()->first('reason'));

        $v = new Validator($trans, ['is_active' => 'true'], ['reason' => 'required_if:is_active,true']);
        $this->assertTrue($v->fails());
        $this->assertSame('The reason field is required when is active is true.', $v->messages()->first('reason'));

        // When user submits false
        $v = new Validator($trans, ['is_active' => false], ['reason' => 'required_if:is_active,false']);
        $this->assertTrue($v->fails());
        $this->assertSame('The reason field is required when is active is false.', $v->messages()->first('reason'));

        // When user submits string '0' with boolean validation (converts to false)
        $v = new Validator($trans, ['is_active' => '0', 'reason' => ''], ['is_active' => 'required|boolean', 'reason' => 'required_if:is_active,false']);
        $this->assertTrue($v->fails());
        $this->assertSame('The reason field is required when is active is 0.', $v->messages()->first('reason'));
    }

    public function testRequiredIfWithNumericValues()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');

        $v = new Validator($trans, ['count' => 0], ['description' => 'required_if:count,0']);
        $this->assertTrue($v->fails());
        $this->assertSame('The description field is required when count is 0.', $v->messages()->first('description'));

        $v = new Validator($trans, ['count' => 10], ['description' => 'required_if:count,10']);
        $this->assertTrue($v->fails());
        $this->assertSame('The description field is required when count is 10.', $v->messages()->first('description'));

        $v = new Validator($trans, ['count' => -5], ['description' => 'required_if:count,-5']);
        $this->assertTrue($v->fails());
        $this->assertSame('The description field is required when count is -5.', $v->messages()->first('description'));
    }

    public function testProhibitedIfPreservesCaseOfActualValue()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.prohibited_if' => 'The :attribute field is prohibited when :other is :value.'], 'en');

        $v = new Validator($trans, ['role' => 'Admin', 'restricted' => 'value'], ['restricted' => 'prohibited_if:role,Admin']);
        $this->assertTrue($v->fails());
        $this->assertSame('The restricted field is prohibited when role is Admin.', $v->messages()->first('restricted'));
    }

    public function testAcceptedIfPreservesCaseOfActualValue()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.accepted_if' => 'The :attribute field must be accepted when :other is :value.'], 'en');

        $v = new Validator($trans, ['age' => '15', 'consent' => 0], ['consent' => 'accepted_if:age,15']);
        $this->assertTrue($v->fails());
        $this->assertSame('The consent field must be accepted when age is 15.', $v->messages()->first('consent'));
    }

    public function testDeclinedIfPreservesCaseOfActualValue()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.declined_if' => 'The :attribute field must be declined when :other is :value.'], 'en');

        $v = new Validator($trans, ['opt_in' => 'No', 'marketing' => 1], ['marketing' => 'declined_if:opt_in,No']);
        $this->assertTrue($v->fails());
        $this->assertSame('The marketing field must be declined when opt in is No.', $v->messages()->first('marketing'));
    }

    public function testMissingIfPreservesCaseOfActualValue()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.missing_if' => 'The :attribute field must be missing when :other is :value.'], 'en');

        $v = new Validator($trans, ['type' => 'Guest', 'member_id' => '123'], ['member_id' => 'missing_if:type,Guest']);
        $this->assertTrue($v->fails());
        $this->assertSame('The member id field must be missing when type is Guest.', $v->messages()->first('member_id'));
    }

    public function testPresentIfPreservesCaseOfActualValue()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.present_if' => 'The :attribute field must be present when :other is :value.'], 'en');

        $v = new Validator($trans, ['payment' => 'Card'], ['card_number' => 'present_if:payment,Card']);
        $this->assertTrue($v->fails());
        $this->assertSame('The card number field must be present when payment is Card.', $v->messages()->first('card_number'));
    }

    public function testUppercaseAndUcfirstPlaceholdersWork()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute is required when :other is :VALUE.'], 'en');

        $v = new Validator($trans, ['status' => 'active'], ['reason' => 'required_if:status,active']);
        $this->assertTrue($v->fails());
        $this->assertSame('The reason is required when status is ACTIVE.', $v->messages()->first('reason'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute is required when :other is :Value.'], 'en');

        $v = new Validator($trans, ['status' => 'active'], ['reason' => 'required_if:status,active']);
        $this->assertTrue($v->fails());
        $this->assertSame('The reason is required when status is Active.', $v->messages()->first('reason'));
    }

    public function testEdgeCaseWithEmptyString()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');

        $v = new Validator($trans, ['field1' => ''], ['field2' => 'required_if:field1,']);
        $this->assertTrue($v->fails());
        // Empty string should be preserved in message
        $this->assertStringContainsString('when field1 is', $v->messages()->first('field2'));
    }

    public function testOriginalIssueScenarios()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');

        // Original issue scenario 1: User submits 'AA', rule expects 'AA'
        $v = new Validator($trans, ['field1' => 'AA'], ['field2' => 'required_if:field1,AA']);
        $this->assertTrue($v->fails());
        $this->assertSame('The field2 field is required when field1 is AA.', $v->messages()->first('field2'));

        // Original issue scenario 2: Boolean edge case
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines([
            'validation.required_if' => 'The :attribute field is required when :other is :value.',
            'validation.boolean' => 'The :attribute field must be true or false.',
        ], 'en');

        $v = new Validator($trans, ['field1' => 'False'], ['field1' => ['required', 'boolean'], 'field2' => 'required_if:field1,true']);
        $this->assertTrue($v->fails());

        // The boolean validation will fail, but field2 should not be required (field1 is false, not true)
        $field2Error = $v->errors()->get('field2');
        if (! empty($field2Error)) {
            // If there's an error for field2, it should mention 'false' (the actual value), not 'true'
            $this->assertStringNotContainsString('when field1 is true', $field2Error[0]);
        }
    }
}
