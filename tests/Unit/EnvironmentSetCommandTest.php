<?php

namespace Tests\Unit;

use Carbon\Carbon;
use ImLiam\EnvironmentSetCommand\EnvironmentSetCommand;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\ReflectionHelper;

class EnvironmentSetCommandTest extends TestCase
{
    use ReflectionHelper;

    /**
     * @var EnvironmentSetCommand
     */
    protected $command;

    public function setUp()
    {
        parent::setUp();
        $this->command = new EnvironmentSetCommand();
    }

    /**
     * @covers       EnvironmentSetCommand::setEnvVariable
     * @dataProvider setEnvVariableDataProvider
     *
     * @param string $originalEnvFileContent
     * @param string $key
     * @param string $value
     * @param string $expectedNewEnvFile
     */
    public function testSetEnvVariable(
        string $originalEnvFileContent,
        string $key,
        string $value,
        string $expectedNewEnvFile
    ): void {
        // envFile gets new value:
        [$newEnvFileContent, $isNewVariableSet] = $this->command->setEnvVariable($originalEnvFileContent, $key, $value);
        $this->assertEquals($expectedNewEnvFile, $newEnvFileContent);
    }

    /**
     * @covers       EnvironmentSetCommand::readKeyValuePair
     * @dataProvider readKeyValuePairDataProvider
     *
     * @param string      $envFileContent
     * @param string      $key
     * @param string|null $expectedKeyValuePair
     */
    public function testReadKeyValuePair(string $envFileContent, string $key, ?string $expectedKeyValuePair): void
    {
        $realPair = $this->command->readKeyValuePair($envFileContent, $key);
        $this->assertEquals($expectedKeyValuePair, $realPair);
    }

    /**
     * @covers       EnvironmentSetCommand::parseCommandArguments
     * @dataProvider parseKeyValueArgumentsDataProvider
     *
     * @param array $params
     * @param array $expectedResult
     */
    public function testParseCommandArguments(array $params, array $expectedResult): void
    {
        $result = $this->command->parseCommandArguments(...$params);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @covers       EnvironmentSetCommand::assertKeyIsValid
     * @dataProvider assertKeyIsValidDataProvider
     *
     * @param string $key
     * @param bool   $isGood
     */
    public function testAssertKeyIsValid(string $key, bool $isGood): void
    {
        if (!$isGood) {
            $this->expectException(InvalidArgumentException::class);
        }

        $this->assertTrue($this->command->assertKeyIsValid($key));
    }

    /**
     * @covers EnvironmentSetCommand::getHistoryString
     */
    public function testHistoryString(): void
    {
        $this->assertEquals('', $this->command->getHistoryString('old_key=old_value'));

        putenv('ENVSET_HISTORY=true');

        Carbon::setTestNow('2020-01-01 12:00:00');

        $this->assertEquals(
            "# old_key=old_value # Edited: 2020-01-01 12:00:00\n",
            $this->command->getHistoryString('old_key=old_value')
        );

        putenv('ENVSET_HISTORY');
    }

    /**
     * @return array
     * @see EnvironmentSetCommandTest::testSetEnvVariable
     */
    public function setEnvVariableDataProvider(): array
    {
        $envFileContent = $this->getTestEnvFile();
        return [
            [
                &$envFileContent,
                'some_key',
                'new_value',
                str_replace('some_key=some_value', 'some_key=new_value', $envFileContent),
            ],
            [
                &$envFileContent,
                'spaces_at_the_beginning_of_the_line',
                '@#$#@R(@#R',
                str_replace('   spaces_at_the_beginning_of_the_line=42442',
                    'spaces_at_the_beginning_of_the_line=@#$#@R(@#R', $envFileContent),
            ],
            [
                &$envFileContent,
                'CASE_SENSITIVITY_TEST',
                '%@#ddddd',
                str_replace('case_Sensitivity_Test=^^^$#$%#==#%$#',
                    'CASE_SENSITIVITY_TEST=%@#ddddd', $envFileContent),
            ],
            [
                &$envFileContent,
                'new_not_existed_key',
                'value',
                $envFileContent . "\n" . 'new_not_existed_key=value' . "\n",
            ],
            // Test of assign of empty values.
            [
                &$envFileContent,
                'some_key',
                '',
                str_replace('some_key=some_value', 'some_key=', $envFileContent),
            ],
            // Test of update of empty values.
            [
                &$envFileContent,
                'empty_key_one',
                'new_value',
                str_replace('empty_key_one=', 'empty_key_one=new_value', $envFileContent),
            ],
            // Test of creation of empty value.
            [
                &$envFileContent,
                'new_not_existed_key',
                '',
                $envFileContent . "\n" . 'new_not_existed_key=' . "\n",
            ],
            // Test of equals signs.
            [
                &$envFileContent,
                'a_lot_of_equals_signs_one',
                'new_value',
                str_replace('a_lot_of_equals_signs_one=======',
                    'a_lot_of_equals_signs_one=new_value', $envFileContent),
            ],
            [
                &$envFileContent,
                'a_lot_of_equals_signs_three',
                'new_value',
                str_replace('a_lot_of_equals_signs_three    =    "======"    ',
                    'a_lot_of_equals_signs_three=new_value', $envFileContent),
            ],
            [
                &$envFileContent,
                'some_key',
                '=========',
                str_replace('some_key=some_value',
                    'some_key==========', $envFileContent),
            ],
        ];
    }

    /**
     * @return array
     * @see EnvironmentSetCommandTest::testReadKeyValuePair
     */
    public function readKeyValuePairDataProvider(): array
    {
        $envFileContent = $this->getTestEnvFile();
        return [
            [&$envFileContent, 'not_existed_key', null],
            [&$envFileContent, 'some_key', 'some_key=some_value'],
            [&$envFileContent, 'spaces_at_the_beginning_of_the_line', '   spaces_at_the_beginning_of_the_line=42442'],
            [&$envFileContent, 'spaces_at_the_end_of_the_line', 'spaces_at_the_end_of_the_line=afd@R@3fSD%^    '],
            [&$envFileContent, 'spaces_around_equals_sign', 'spaces_around_equals_sign = some_value'],
            [&$envFileContent, 'UPPERCASE_KEY', 'UPPERCASE_KEY=%4t2423528$!'],
            [&$envFileContent, 'case_Sensitivity_Test', 'case_Sensitivity_Test=^^^$#$%#==#%$#'],
            [&$envFileContent, 'case_sensitivity_test', 'case_Sensitivity_Test=^^^$#$%#==#%$#'],
            [&$envFileContent, 'CASE_SENSITIVITY_TEST', 'case_Sensitivity_Test=^^^$#$%#==#%$#'],
            [&$envFileContent, 'empty_key_one', 'empty_key_one='],
            [&$envFileContent, 'empty_key_two', '    empty_key_two=    '],
            [&$envFileContent, 'spaces_in_the_quotes', '    spaces_in_the_quotes    =    "    "    '],
            [&$envFileContent, 'a_lot_of_equals_signs_one', 'a_lot_of_equals_signs_one======='],
            [&$envFileContent, 'a_lot_of_equals_signs_two', 'a_lot_of_equals_signs_two    =    ======    '],
            [&$envFileContent, 'a_lot_of_equals_signs_three', 'a_lot_of_equals_signs_three    =    "======"    '],
        ];
    }

    /**
     * @return array
     * @see EnvironmentSetCommandTest::testAssertKeyIsValid
     */
    public function assertKeyIsValidDataProvider(): array
    {
        return [
            // Wrong keys
            ['wrong_key=', false],
            ['wrong key', false],
            ['1test', false],
            ['test_1', false],
            ['111', false],
            ['test!', false],
            ['!!!!', false],
            ['$', false],

            // Good keys
            ['_', true],
            ['a', true],
            ['test', true],
            ['thisIsTest', true],
            ['UPPERCASE_TEST', true],
        ];
    }

    /**
     * @return array
     * @see EnvironmentSetCommandTest::testParseCommandArguments
     */
    public function parseKeyValueArgumentsDataProvider(): array
    {
        return [
            // Normal syntax.
            [
                ['SOME_KEY', 'some_value', null],
                ['SOME_KEY', 'some_value', null],
            ],
            [
                ['SOME_KEY', 'some_value', __FILE__],
                ['SOME_KEY', 'some_value', __FILE__],
            ],
            // key=value syntax.
            [
                ['SOME_KEY=some_value', null, null],
                ['SOME_KEY', 'some_value', null],
            ],
            [
                ['SOME_KEY=some_value', __FILE__, null],
                ['SOME_KEY', 'some_value', __FILE__],
            ],
            [
                ['SOME_KEY=some_value', __FILE__, 'ambiguous_third_parameter'],
                ['SOME_KEY', 'some_value', __FILE__],
            ],
            // Equals signs in the key=value parameter.
            [
                ['SOME_KEY==some=value===', null, null],
                ['SOME_KEY', '=some=value===', null],
            ],
            // Test without neither value argument nor value in the key.
            [
                ['some_key=', null, null],
                ['SOME_KEY', '', null],
            ],
            [
                ['some_key', null, null],
                ['SOME_KEY', '', null],
            ],
            // Test lowercase in the key.
            [
                ['some_key=some_value', null, null],
                ['SOME_KEY', 'some_value', null],
            ],
            [
                ['some_key=some_value', null, null],
                ['SOME_KEY', 'some_value', null],
            ],
            // Test double quotes in value.
            [
                ['some_key="some_value"', null, null],
                ['SOME_KEY', '"some_value"', null],
            ],
            [
                ['some_key', '"some_value"', null],
                ['SOME_KEY', '"some_value"', null],
            ],
            // Test single quotes in value.
            [
                ["some_key='some_value'", null, null],
                ['SOME_KEY', "'some_value'", null],
            ],
            [
                ['some_key', "'some_value'", null],
                ['SOME_KEY', "'some_value'", null],
            ],
            // Test spaces in value.
            [
                ['some_key=some value', null, null],
                ['SOME_KEY', '"some value"', null],
            ],
            [
                ['some_key', 'some value', null],
                ['SOME_KEY', '"some value"', null],
            ],
            // Test spaces in value that are enclosed in quotes.
            [
                ['some_key="some value"', null, null],
                ['SOME_KEY', '"some value"', null],
            ],
            [
                ['some_key', '"some value"', null],
                ['SOME_KEY', '"some value"', null],
            ],
            [
                ["some_key='some value'", null, null],
                ['SOME_KEY', "'some value'", null],
            ],
            [
                ['some_key', "'some value'", null],
                ['SOME_KEY', "'some value'", null],
            ],
        ];
    }

    /**
     * @return string
     */
    protected function getTestEnvFile(): string
    {
        return 'some_key=some_value' . "\n"
            . '   spaces_at_the_beginning_of_the_line=42442' . "\n"
            . 'spaces_at_the_end_of_the_line=afd@R@3fSD%^    ' . "\n"
            . 'spaces_around_equals_sign = some_value' . "\n"
            . 'UPPERCASE_KEY=%4t2423528$!' . "\n"
            . 'case_Sensitivity_Test=^^^$#$%#==#%$#' . "\n"
            . 'empty_key_one=' . "\n"
            . '    empty_key_two=    ' . "\n"
            . '    spaces_in_the_quotes    =    "    "    ' . "\n"
            . 'a_lot_of_equals_signs_one=======' . "\n"
            . 'a_lot_of_equals_signs_two    =    ======    ' . "\n"
            . 'a_lot_of_equals_signs_three    =    "======"    ' . "\n";
    }
}
