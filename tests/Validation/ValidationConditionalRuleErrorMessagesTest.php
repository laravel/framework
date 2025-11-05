<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Validator;

class ValidationConditionalRuleErrorMessagesTest extends ValidationValidatorTest
{
    public function testRequiredIfShowsExpectedValueInMessage()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');

        $v = new Validator($trans, ['status' => 'active'], ['reason' => 'required_if:status,inactive']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['status' => 'inactive'], ['reason' => 'required_if:status,inactive']);
        $this->assertTrue($v->fails());
        $this->assertSame('The reason field is required when status is inactive.', $v->messages()->first('reason'));
    }

    public function testRequiredIfWithBooleanShowsExpectedValue()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');

        $v = new Validator($trans, ['is_active' => 'false'], ['reason' => 'required_if:is_active,true']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['is_active' => true], ['reason' => 'required_if:is_active,true']);
        $this->assertTrue($v->fails());
        $this->assertSame('The reason field is required when is active is true.', $v->messages()->first('reason'));

        $v = new Validator($trans, ['is_active' => false], ['reason' => 'required_if:is_active,false']);
        $this->assertTrue($v->fails());
        $this->assertSame('The reason field is required when is active is false.', $v->messages()->first('reason'));
    }

    public function testRequiredIfWithCaseSensitivityShowsExpectedValue()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');

        $v = new Validator($trans, ['field1' => 'AA', 'field2' => ''], ['field2' => 'required_if:field1,AA']);
        $this->assertTrue($v->fails());
        $this->assertSame('The field2 field is required when field1 is AA.', $v->messages()->first('field2'));

        $v = new Validator($trans, ['field1' => 'Active', 'field2' => ''], ['field2' => 'required_if:field1,Active']);
        $this->assertTrue($v->fails());
        $this->assertSame('The field2 field is required when field1 is Active.', $v->messages()->first('field2'));
    }

    public function testRequiredIfWithNumericValues()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');

        $v = new Validator($trans, ['count' => 0], ['description' => 'required_if:count,0']);
        $this->assertTrue($v->fails());
        $this->assertSame('The description field is required when count is 0.', $v->messages()->first('description'));

        $v = new Validator($trans, ['count' => '5'], ['description' => 'required_if:count,10']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['count' => 10], ['description' => 'required_if:count,10']);
        $this->assertTrue($v->fails());
        $this->assertSame('The description field is required when count is 10.', $v->messages()->first('description'));
    }

    public function testProhibitedIfShowsExpectedValueInMessage()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.prohibited_if' => 'The :attribute field is prohibited when :other is :value.'], 'en');

        $v = new Validator($trans, ['role' => 'admin', 'restricted_field' => 'value'], ['restricted_field' => 'prohibited_if:role,admin']);
        $this->assertTrue($v->fails());
        $this->assertSame('The restricted field field is prohibited when role is admin.', $v->messages()->first('restricted_field'));
    }

    public function testProhibitedIfWithBooleanShowsExpectedValue()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.prohibited_if' => 'The :attribute field is prohibited when :other is :value.'], 'en');

        $v = new Validator($trans, ['is_public' => false, 'private_data' => 'secret'], ['private_data' => 'prohibited_if:is_public,false']);
        $this->assertTrue($v->fails());
        $this->assertSame('The private data field is prohibited when is public is false.', $v->messages()->first('private_data'));
    }

    public function testAcceptedIfShowsExpectedValueInMessage()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.accepted_if' => 'The :attribute field must be accepted when :other is :value.'], 'en');

        $v = new Validator($trans, ['age' => '15', 'parental_consent' => 0], ['parental_consent' => 'accepted_if:age,15']);
        $this->assertTrue($v->fails());
        $this->assertSame('The parental consent field must be accepted when age is 15.', $v->messages()->first('parental_consent'));
    }

    public function testDeclinedIfShowsExpectedValueInMessage()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.declined_if' => 'The :attribute field must be declined when :other is :value.'], 'en');

        $v = new Validator($trans, ['opt_in' => 'yes', 'marketing' => 1], ['marketing' => 'declined_if:opt_in,no']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['opt_in' => 'no', 'marketing' => 1], ['marketing' => 'declined_if:opt_in,no']);
        $this->assertTrue($v->fails());
        $this->assertSame('The marketing field must be declined when opt in is no.', $v->messages()->first('marketing'));
    }

    public function testMissingIfShowsExpectedValueInMessage()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.missing_if' => 'The :attribute field must be missing when :other is :value.'], 'en');

        $v = new Validator($trans, ['type' => 'guest', 'member_id' => '123'], ['member_id' => 'missing_if:type,guest']);
        $this->assertTrue($v->fails());
        $this->assertSame('The member id field must be missing when type is guest.', $v->messages()->first('member_id'));
    }

    public function testPresentIfShowsExpectedValueInMessage()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.present_if' => 'The :attribute field must be present when :other is :value.'], 'en');

        $v = new Validator($trans, ['payment_type' => 'card'], ['card_number' => 'present_if:payment_type,card']);
        $this->assertTrue($v->fails());
        $this->assertSame('The card number field must be present when payment type is card.', $v->messages()->first('card_number'));
    }

    public function testMultipleConditionalRulesShowCorrectMessages()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');

        $v = new Validator($trans, [
            'role' => 'admin',
            'status' => 'inactive',
        ], [
            'reason' => 'required_if:status,inactive',
            'admin_field' => 'required_if:role,admin',
        ]);

        $this->assertTrue($v->fails());
        $messages = $v->messages();

        $this->assertSame('The reason field is required when status is inactive.', $messages->first('reason'));
        $this->assertSame('The admin field field is required when role is admin.', $messages->first('admin_field'));
    }

    public function testRequiredIfWithNullValue()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');

        $v = new Validator($trans, ['field1' => null], ['field2' => 'required_if:field1,null']);
        $this->assertTrue($v->fails());
        $this->assertSame('The field2 field is required when field1 is null.', $v->messages()->first('field2'));
    }

    public function testRequiredIfWithEmptyStringValue()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');

        $v = new Validator($trans, ['field1' => ''], ['field2' => 'required_if:field1,']);
        $this->assertTrue($v->fails());
        $this->assertStringContainsString('when field1 is', $v->messages()->first('field2'));
    }

    public function testBooleanConversionBug()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');

        $v = new Validator($trans, [
            'field1' => 'false',
        ], [
            'field2' => 'required_if:field1,true',
        ]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, [
            'field1' => 'true',
        ], [
            'field2' => 'required_if:field1,true',
        ]);
        $this->assertTrue($v->fails());
        $this->assertSame('The field2 field is required when field1 is true.', $v->messages()->first('field2'));
    }

    public function testComplexScenarioWithMultipleFields()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');

        $v = new Validator($trans, [
            'subscription_type' => 'premium',
            'payment_method' => 'card',
            'is_active' => true,
        ], [
            'card_details' => 'required_if:payment_method,card',
            'premium_features' => 'required_if:subscription_type,premium',
            'activation_date' => 'required_if:is_active,true',
        ]);

        $this->assertTrue($v->fails());
        $messages = $v->messages();

        $this->assertSame('The card details field is required when payment method is card.', $messages->first('card_details'));
        $this->assertSame('The premium features field is required when subscription type is premium.', $messages->first('premium_features'));
        $this->assertSame('The activation date field is required when is active is true.', $messages->first('activation_date'));
    }
}
