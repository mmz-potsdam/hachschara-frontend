<?php

namespace App\Service;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Initial attempt to format extended date format strings
 * depending on the current locale
 */
class DateFormatter
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
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

    protected function formatDateparts($year, $month, $day, $datetimeStyle, $incomplete = false)
    {
        if (intval($month) == 0 && intval($day) == 0 && intval($year) == 0) {
            return '';
        }

        if ($incomplete) {
            if ($day == 0) {
                $datetimeStyle = trim(preg_replace('/D+[\.\/,]*/i', '', $datetimeStyle));
            }

            if ($month == 0) {
                $datetimeStyle = trim(preg_replace('/[FM]+[\.\/,]*/i', '', $datetimeStyle));
            }
        }

        $date = new \DateTime();
        $date->setDate($year, $month > 0 ? $month : 1, $day > 0 ? $day : 1);

        return $date->format($datetimeStyle);
    }

    /**
     * parse and extended $datestr
     * into an associative array
     * with keys 'year', 'month', 'day' and 'modifier'
     */
    protected function parseEdtfDate($datestr)
    {
        $ret = [];

        $match_modifier = '/([\?~<>])$/';
        if (preg_match($match_modifier, $datestr, $matches)) {
            $ret['modifier'] = $matches[1];
            $datestr = preg_replace($match_modifier, '', $datestr);
        }

        $parts = explode('-', $datestr);
        $fill_zero = false;
        foreach ([ 'year', 'month', 'day' ] as $i => $key) {
            if ($i >= count($parts) || !preg_match('/^\d+$/', $parts[$i])) {
                $fill_zero = true;
            }

            $ret[$key] = $fill_zero ? 0 : $parts[$i];
        }

        return $ret;
    }

    /**
     * Take an extended $datestr and format it
     * according to a locale-dependent (hard-wired)
     * format
     */
    public function formatDateExtended($datestr)
    {
        // TODO: use translator for localization
        $datetimeStyle = 'en' == $this->translator->getLocale()
            ? 'F d, Y'
            : 'd.m.Y';

        $parts = explode('/', $datestr, 2);

        $valueInternal = $this->parseEdtfDate($parts[0]);
        $valueAppend = '';
        if (count($parts) > 1) {
            // range
            $valueInternal['modifier'] = '/';
            $valueInternalEnd = $this->parseEdtfDate($parts[1]);
            $valueAppend = $valueInternal['modifier'] . $this->formatDateparts(
                $valueInternalEnd['year'],
                $valueInternalEnd['month'],
                $valueInternalEnd['day'],
                $datetimeStyle,
                true
            );
        }

        $fieldValue = $this->formatDateparts(
            $valueInternal['year'],
            $valueInternal['month'],
            $valueInternal['day'],
            $datetimeStyle,
            true
        );

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
}
