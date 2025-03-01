<?php

/**
 *
 * Shared methods
 */

namespace App\Entity;

trait AddressesTrait
{
    static $ADDRESS_KEYS = [
        'from', 'until', 'note',
        'address', 'place', 'place_tgn',
        'street', 'zip', 'country',
        'geo',
    ];

    static function splitAddresses($entries)
    {
        $ret = [];
        if (empty($entries)) {
            return $ret;
        }

        foreach ($entries as $entry) {
            $idx = empty($ret) ? 0 : count($ret['place']);
            foreach (self::$ADDRESS_KEYS as $key) {
                $val = '';
                if (array_key_exists($key, $entry)) {
                    $val = $entry[$key];
                }
                $ret[$key][$idx] = $val;
            }
        }

        return $ret;
    }

    /*
     *
     */
    function buildAddresses($entries, $showCountry = false, $linkPlace = false, $returnStructure = false)
    {
        $addresses = self::splitAddresses($entries);
        if (empty($addresses)) {
            return [];
        }

        if ($returnStructure) {
            $keys = array_keys($addresses);
        }

        $numAddresses = empty($addresses) ? 0 : count($addresses['place']);
        $fields = [];
        for ($i = 0; $i < $numAddresses; $i++) {
            if ($returnStructure) {
                $entry = [];

                foreach ($keys as $key) {
                    $entry[$key] = $addresses[$key][$i];
                }

                $fields[] = $entry;
                continue;
            }


            $lines = [];

            if ($showCountry) {
                $range = join('-', [ $addresses['from'][$i], $addresses['until'][$i] ]);

                if ('-' != $range) {
                    $lines[] = $range;
                }
            }

            foreach ([ [ 'address', 'place' ],
                $showCountry ? [ 'street', 'zip', 'country' ] : [ 'street', 'zip' ],
                // [ 'geo' ],
            ] as $keys) {
                $parts = [];
                foreach ($keys as $key) {
                    if (!empty($addresses[$key][$i])) {
                        if ($linkPlace && 'place' == $key && !empty($addresses['place_tgn'][$i])) {
                            $parts[] = sprintf(
                                '<a href="%%basepath%%/place/tgn/%s">%s</a>',
                                $addresses['place_tgn'][$i],
                                htmlspecialchars($addresses[$key][$i], ENT_COMPAT, 'utf-8')
                            );
                        }
                        else {
                            $parts[] = $linkPlace
                                ? htmlspecialchars($addresses[$key][$i], ENT_COMPAT, 'utf-8')
                                : $addresses[$key][$i];
                        }
                    }
                }

                if (!empty($parts)) {
                    $lines[] = join(', ', $parts);
                }
            }

            if (!empty($addresses['note'][$i])) {
                $lines[] = '[' . $addresses['note'][$i] . ']';
            }

            if (!empty($lines)) {
                $fields[] = [
                    'info' => join("\n", $lines),
                ];
            }
        }

        return $fields;
    }
}
