<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\UserIdentitySanitizer;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UserIdentitySanitizerTest extends TestCase
{
    public function test_names_remove_special_characters_and_normalize_spaces(): void
    {
        $this->assertSame('John Doe', UserIdentitySanitizer::name('  Jo(hn) # Doe123  '));
        $this->assertSame('José María', UserIdentitySanitizer::name('José  María!'));
    }

    public function test_username_is_lowercase_and_removes_disallowed_characters(): void
    {
        $this->assertSame('john_doe01', UserIdentitySanitizer::username('John_(Doe)-01!'));
    }

    public function test_cleaned_identity_values_pass_the_shared_rules(): void
    {
        $identity = UserIdentitySanitizer::sanitize([
            'firstname' => 'Jo(hn)',
            'lastname' => 'D@oe',
            'username' => 'John_(Doe)-01!',
        ]);

        $validator = Validator::make($identity, [
            'firstname' => UserIdentitySanitizer::nameRules(),
            'lastname' => UserIdentitySanitizer::nameRules(),
            'username' => UserIdentitySanitizer::usernameRules(),
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_values_containing_only_special_characters_fail_after_cleaning(): void
    {
        $identity = UserIdentitySanitizer::sanitize([
            'firstname' => '()',
            'lastname' => '#!',
            'username' => '()---',
        ]);

        $validator = Validator::make($identity, [
            'firstname' => UserIdentitySanitizer::nameRules(),
            'lastname' => UserIdentitySanitizer::nameRules(),
            'username' => UserIdentitySanitizer::usernameRules(),
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_non_scalar_user_input_is_cleaned_to_an_invalid_empty_value(): void
    {
        $this->assertSame('', UserIdentitySanitizer::name(['John']));
        $this->assertSame('', UserIdentitySanitizer::username(['john_doe']));
    }

    public function test_user_model_applies_sanitization_as_a_final_safety_net(): void
    {
        $user = new User();
        $user->firstname = 'Jo(hn)';
        $user->lastname = 'D@oe';
        $user->username = 'John_(Doe)-01!';

        $this->assertSame('John', $user->firstname);
        $this->assertSame('Doe', $user->lastname);
        $this->assertSame('john_doe01', $user->username);
    }
}
