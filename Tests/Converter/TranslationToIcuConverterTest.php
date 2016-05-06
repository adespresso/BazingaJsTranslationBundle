<?php

namespace Bazinga\Bundle\JsTranslationBundle\Tests\Converter;

use Bazinga\Bundle\JsTranslationBundle\Converter\TranslationToIcuConverter;

class TranslationToIcuConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TranslationToIcuConverter
     */
    private $converter;

    protected function setUp()
    {
        $this->converter = new TranslationToIcuConverter();
    }

    public function testConvertParameterPercent()
    {
        $this->assertSame('{param}', $this->converter->convert('%param%'));
    }

    public function testConvertParameterCurlyBraces()
    {
        $this->assertSame('{param}', $this->converter->convert('{{ param }}'));
    }

    public function testConvertParameterUnmatchedBrackets()
    {
        $this->assertSame('This is a curly brace: \\{', $this->converter->convert('This is a curly brace: {'));
    }

    public function testConvertTransChoiceExplicitInterval()
    {
        $input = '[0,2] this|[3,+Inf] that';
        $expected = '{ number, plural, =0 {this} =1 {this} =2 {this} other {that}}';

        $this->assertSame($expected, $this->converter->convert($input));
    }

    public function testConvertTransChoiceExplicitNumbers()
    {
        $input = '{0,1,3} this|{2} that|[4,+Inf] theother';
        $expected = '{ number, plural, =0 {this} =1 {this} =3 {this} =2 {that} other {theother}}';

        $this->assertSame($expected, $this->converter->convert($input));
    }

    public function testConvertTransChoiceStandard2Choices()
    {
        $input = 'first|second';
        $expected = '{ number, plural, one {first} other {second}}';

        $this->assertSame($expected, $this->converter->convert($input));
    }

    public function testConvertTransChoiceStandard3Choices()
    {
        $input = 'first|second|third';
        $expected = '{ number, plural, one {first} few {second} other {third}}';

        $this->assertSame($expected, $this->converter->convert($input));
    }

    public function testConvertTransChoiceStandard4Choices()
    {
        $input = 'first|second|third|fourth';
        $expected = '{ number, plural, one {first} few {second} many {third} other {fourth}}';

        $this->assertSame($expected, $this->converter->convert($input));
    }

    public function testConvertTransChoiceStandard5Choices()
    {
        $input = 'first|second|third|fourth|fifth';
        $expected = '{ number, plural, one {first} two {second} few {third} many {fourth} other {fifth}}';

        $this->assertSame($expected, $this->converter->convert($input));
    }

    public function testConvertTransChoiceStandard6Choices()
    {
        $input = 'first|second|third|fourth|fifth|sixth';
        $expected = '{ number, plural, zero {first} one {second} two {third} few {fourth} many {fifth} other {sixth}}';

        $this->assertSame($expected, $this->converter->convert($input));
    }
}
