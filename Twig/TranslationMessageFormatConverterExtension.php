<?php

namespace Bazinga\Bundle\JsTranslationBundle\Twig;

use Bazinga\Bundle\JsTranslationBundle\Converter\TranslationToIcuConverter;

class TranslationMessageFormatConverterExtension extends \Twig_Extension
{
    /**
     * @var TranslationToIcuConverter
     */
    private $toIcuConverter;

    public function __construct(TranslationToIcuConverter $translationToIcuConverter)
    {
        $this->toIcuConverter = $translationToIcuConverter;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('translation2icu', [$this, 'convertTranslationToIcu']),
        ];
    }

    /**
     * @param string|array $input
     *
     * @return string|array
     */
    public function convertTranslationToIcu($input)
    {
        return $this->convertRecursive($input, function ($message) {
            return $this->toIcuConverter->convert($message);
        });
    }

    /**
     * @param string|array $input
     * @param callable $conversion
     *
     * @return string|array
     */
    private function convertRecursive($input, callable $conversion)
    {
        if (is_array($input)) {
            $converted = array_map(function ($message) {
                return $this->convertTranslationToIcu($message);
            }, $input);
        } else {
            $converted = $conversion($input);
        }

        return $converted;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'bazinga.jstranslation.twig.translation_message_format_converter';
    }
}
