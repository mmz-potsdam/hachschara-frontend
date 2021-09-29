<?php

namespace App\Utils;

/**
 *
 */
class Formatter
{
    /** No instances */
    private function __construct() {}

    /*
     * Split YYYY-MM-DD and remove the ones that are equal to 0
     */
    protected static function buildDatePartsReduced($datestr)
    {
        $dateParts = preg_split('/\-/', $datestr);
        $datePartsReduced = [];

        for ($i = 0; $i < count($dateParts); $i++) {
            if (0 == $dateParts[$i]) {
                break;
            }
            $datePartsReduced[] = $dateParts[$i];
        }

        return $datePartsReduced;
    }

    public static function dateExtended($datestr, $locale = null)
    {
        // todo: local-dependent
        $datetimeStyle = 'DD.MM.YYYY';

        $parts = explode('/', $datestr, 2);
        var_dump($parts);
        exit;

        $value_internal = $recordSQL->parse_edtf_date($parts[0]);
        $value_append = '';
        if (count($parts) > 1) {
            // range
            $value_internal['modifier'] = '/';
            $value_internal_end = $recordSQL->parse_edtf_date($parts[1]);
            $value_append = $value_internal['modifier'] . $recordSQL->format_dateparts($value_internal_end['year'], $value_internal_end['month'], $value_internal_end['day'], $datetime_style, true);
        }
        $field_value = $recordSQL->format_dateparts($value_internal['year'], $value_internal['month'], $value_internal['day'], $datetime_style, true);

        if (!empty($value_internal['modifier'])) {
            switch ($value_internal['modifier']) {
              case '~':
                $field_value = tr('circa') . ' ' . $field_value;
                break;

              case '<':
                $field_value = tr('before') . ' ' . $field_value;
                break;

              case '>':
                $field_value = tr('after') . ' ' . $field_value;
                break;

              case '?':
                $field_value .= '?';
                break;

              default:
                // var_dump($value_internal['modifier']);
            }
        }
    }

    public static function dateIncomplete($datestr, $locale = 'en', $ommit = '')
    {
        $datePartsReduced = self::buildDatePartsReduced($datestr);

        if (empty($datePartsReduced)) {
            return '';
        }

        $separator = '.';
        if ('en' == $locale && count($datePartsReduced) > 1) {
            $ret = [];
            if ('month' !== $ommit) {
                $dateObj = \DateTime::createFromFormat('!m', $datePartsReduced[1]);
                $monthName = $dateObj->format('M'); // M: Mar F: March
                $ret[] = $monthName;
            }

            if (count($datePartsReduced) > 2) {
                $ret[] = intval($datePartsReduced[2])  // day
                    . ('year' !== $ommit ? ',' : '')
                    ;
            }

            if ('year' !== $ommit) {
                $ret[] = $datePartsReduced[0]; // year
            }

            return join(' ', $ret);
        }

        $datePartsReduced = array_reverse($datePartsReduced);

        return implode($separator, $datePartsReduced);
    }

    public static function daterangeIncomplete($datestrFrom, $datestrUntil, $locale = 'en')
    {
        if (empty($datestrUntil) || $datestrFrom === $datestrUntil) {
            return self::dateIncomplete($datestrFrom, $locale);
        }

        // from is shortened if check if year or year and month are the same
        $ommit = '';
        $fromReduced = self::buildDatePartsReduced($datestrFrom);
        $untilReduced = self::buildDatePartsReduced($datestrUntil);
        if (count($fromReduced) > 1 && count($untilReduced) > 1
            && $fromReduced[0] == $untilReduced[0])
        {
            // year is equal
            $ommit = 'year';
            if (count($fromReduced) > 2 && count($untilReduced) > 2
                && $fromReduced[1] == $untilReduced[1])
            {
                // year and month are equal
                $ommit = 'month';
            }
        }

        $from = self::dateIncomplete($datestrFrom, $locale, !empty($ommit) ? 'year' : '');
        $until = self::dateIncomplete($datestrUntil, $locale, 'month' === $ommit ? $ommit : '');

        return $from
            // . "\u{2012}" // PHP >= 7
            . hex2bin('e28092')
            . $until;
    }
}
