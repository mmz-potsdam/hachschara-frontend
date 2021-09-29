<?php

namespace App\Service;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 */
class DateFormatter
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /*
     * Split YYYY-MM-DD and remove the ones that are equal to 0
     */
    protected function buildDatePartsReduced($datestr)
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


    protected function formatDateparts($year, $month, $day, $datetime_style, $incomplete = false) {
        if (intval($month) == 0 && intval($day) == 0 && intval($year) == 0) {
            return '';
        }

        $value = $datetime_style;
        if ($incomplete) {
            if ($day == 0) {
                $value = preg_replace('/D+[\.\/]*/i', '', $value);
            }

            if ($month == 0) {
                $value = preg_replace('/M+[\.\/]*/i', '', $value);
            }
        }

        $value = preg_replace([ '/(M+)/i', '/(D+)/i', '/(Y+)/i' ],
                              [ sprintf('%02d', $month), sprintf('%02d', $day), sprintf('%d', $year) ],
                              $value);

        return $value;
    }

    protected function parseEdtfDate($date)
    {
        $ret = [];

        $match_modifier = '/([\?~<>])$/';
        if (preg_match($match_modifier, $date, $matches)) {
            $ret['modifier'] = $matches[1];
            $date = preg_replace($match_modifier, '', $date);
        }

        $parts = explode('-', $date);
        $fill_zero = false;
        foreach ([ 'year', 'month', 'day' ] as $i => $key) {
            if ($i >= count($parts) || !preg_match('/^\d+$/', $parts[$i])) {
                $fill_zero = true;
            }

            $ret[$key] = $fill_zero ? 0 : $parts[$i];
        }

        return $ret;
    }

    public function formatDateExtended($datestr)
    {
        // todo: use translator to make this local-dependent
        $datetimeStyle = 'DD.MM.YYYY';

        $parts = explode('/', $datestr, 2);

        $valueInternal = $this->parseEdtfDate($parts[0]);
        $valueAppend = '';
        if (count($parts) > 1) {
            // range
            $valueInternal['modifier'] = '/';
            $valueInternalEnd = $this->parseEdtfDate($parts[1]);
            $valueAppend = $valueInternal['modifier'] . $this->formatDateparts($valueInternalEnd['year'], $valueInternalEnd['month'], $valueInternalEnd['day'], $datetimeStyle, true);
        }

        $fieldValue = $this->formatDateparts($valueInternal['year'], $valueInternal['month'], $valueInternal['day'], $datetimeStyle, true);

        if (!empty($valueInternal['modifier'])) {
            switch ($valueInternal['modifier']) {
              case '~':
                $fieldValue = $this->translator->trans('circa') . ' ' . $fieldValue;
                break;

              case '<':
                $fieldValue = $this->translator->trans('before') . ' ' . $fieldValue;
                break;

              case '>':
                $fieldValue = $this->translator->trans('after') . ' ' . $fieldValue;
                break;

              case '?':
                $fieldValue .= '?';
                break;

              default:
                // var_dump($valueInternal['modifier']);
            }
        }

        return $fieldValue . $valueAppend;
    }

    public static function formatDateIncomplete($datestr, $locale = 'en', $ommit = '')
    {
        $datePartsReduced = $this->buildDatePartsReduced($datestr);

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

    public function formatDaterangeIncomplete($datestrFrom, $datestrUntil, $locale = 'en')
    {
        if (empty($datestrUntil) || $datestrFrom === $datestrUntil) {
            return $this->dateIncomplete($datestrFrom, $locale);
        }

        // from is shortened if check if year or year and month are the same
        $ommit = '';
        $fromReduced = $this->buildDatePartsReduced($datestrFrom);
        $untilReduced = $this->buildDatePartsReduced($datestrUntil);
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

        $from = $this->formatDateIncomplete($datestrFrom, $locale, !empty($ommit) ? 'year' : '');
        $until = $this->formatDateIncomplete($datestrUntil, $locale, 'month' === $ommit ? $ommit : '');

        return $from
            // . "\u{2012}" // PHP >= 7
            . hex2bin('e28092')
            . $until;
    }
}
