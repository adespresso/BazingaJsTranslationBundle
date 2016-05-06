<?php

namespace Bazinga\Bundle\JsTranslationBundle\Converter;

use Bazinga\Bundle\JsTranslationBundle\Exception\UnsupportedMessageFormatException;
use Symfony\Component\Translation\Interval;

class TranslationToIcuConverter
{
    public function convert($message)
    {
        $mayBeTransChoice = strpos($message, '|') !== false;
        if ($mayBeTransChoice) {
            $message = $this->convertTransChoiceToPlural($message);
        } else {
            $message = $this->convertParameters($message);
        }

        return $message;
    }

    /**
     * Convert parameters from the '%param%' or '{{ param }}' syntax to the '{param}' syntax.
     *
     * @param $message
     * @return mixed
     */
    private function convertParameters($message)
    {
        $mayHaveCurlyBracesParameters = strpos($message, '{{ ') !== false && strpos($message, ' }}') !== false;
        if ($mayHaveCurlyBracesParameters) {
            $message = preg_replace('/{{ ([0-9a-zA-Z_\.-]+) }}/', '%$1%', $message);
        }

        // Escape non-matching curly braces.
        $message = strtr($message, [
            '{' => '\{',
            '}' => '\}'
        ]);

        $mayHavePercentParameters = substr_count($message, '%') > 1;
        if ($mayHavePercentParameters) {
            $message = preg_replace('/%([0-9a-zA-Z_\.-]+)%/', '{$1}', $message);
        }

        return $message;
    }

    /**
     * INFO: Variants parsing derived from Symfony\Component\Translation\MessageSelector.
     *
     * @param string $message
     *
     * @return string
     */
    private function convertTransChoiceToPlural($message)
    {
        $parts = explode('|', $message);
        $explicitRules = [];
        $standardRules = [];
        foreach ($parts as $part) {
            $part = trim($part);

            if (preg_match('/^(?P<interval>'.Interval::getIntervalRegexp().')\s*(?P<message>.*?)$/xs', $part, $matches)) {
                $explicitRules[$matches['interval']] = $matches['message'];
            } elseif (preg_match('/^\w+\:\s*(.*?)$/', $part, $matches)) {
                $standardRules[] = $matches[1];
            } else {
                $standardRules[] = $part;
            }
        }

        if (!empty($explicitRules)) {
            $pluralChoices = $this->convertExplicitRulesToPluralChoices($explicitRules, $message);
        } else {
            $pluralChoices = $this->convertStandardRulesToPluralChoices($standardRules, $message);
        }

        return sprintf('{ number, plural, %s}', implode(' ', array_map(function ($selector, $message) {
            return sprintf('%s {%s}', $selector, $this->convertParameters($message));
        }, array_keys($pluralChoices), $pluralChoices)));
    }

    private function convertExplicitRulesToPluralChoices($explicitRules, $message)
    {
        $pluralChoices = [];
        foreach ($explicitRules as $interval => $intervalMessage) {
            if (preg_match('/^{((,?\d+)+)}$/', $interval, $matches)) {
                $numbers = $matches[1];
                if (strpos($numbers, ',') !== false) {
                    $numbers = explode(',', $numbers);
                } else {
                    $numbers = [$numbers];
                }

                foreach ($numbers as $number) {
                    $pluralChoices['='.$number] = $intervalMessage;
                }
            } elseif (preg_match('/^\[(\d+),(\d+)\]$/', $interval, $matches)) {
                for ($i = $matches[1]; $i <= $matches[2]; $i++) {
                    $pluralChoices['='.$i] = $intervalMessage;
                }
            } else {
                $pluralChoices['other'][] = $intervalMessage;
            }
        }

        if (!empty($pluralChoices['other'])) {
            if (count($pluralChoices['other']) > 1) {
                throw new UnsupportedMessageFormatException('More than one open interval in "'.$message.'"');
            }
            $pluralChoices['other'] = reset($pluralChoices['other']);
        }

        return $pluralChoices;
    }

    private function convertStandardRulesToPluralChoices($standardRules, $message)
    {
        switch (count($standardRules)) {
            case 2: $keys = ['one', 'other']; break;
            case 3: $keys = ['one', 'few', 'other']; break;
            case 4: $keys = ['one', 'few', 'many', 'other']; break;
            case 5: $keys = ['one', 'two', 'few', 'many', 'other']; break;
            case 6: $keys = ['zero', 'one', 'two', 'few', 'many', 'other']; break;
            default: throw new UnsupportedMessageFormatException('Too many variants in "'.$message.'"');
        }

        return array_combine($keys, $standardRules);
    }
}