<?php

namespace App\Utils;

/**
 * Override \Seboettg\CiteProc\CiteProc
 * in order to post-process render-method
 */
class CiteProc extends \Seboettg\CiteProc\CiteProc
{
    private $lang;

    /**
     * CiteProc constructor.
     * @param string $styleSheet xml formatted csl stylesheet
     * @param string $lang
     * @param array $markupExtension
     */
    public function __construct($styleSheet, $lang = "en-US", $markupExtension = [])
    {
        parent::__construct($styleSheet, $lang, $markupExtension);

        // this property is private in parent-class, but we won't to access
        $this->lang = $lang;
    }

    public function render($data, $mode = 'bibliography', $citationItems = [], $citationAsArray = false): string
    {
        $bibliography = @ parent::render($data, $mode, $citationItems, $citationAsArray);

        if ($citationAsArray) {
            // TODO: maybe process each entry individually
            return $bibliography;
        }

        /* vertical-align: super doesn't render nicely:
           http://stackoverflow.com/a/1530819/2114681
        */
        $bibliography = preg_replace(
            '/style="([^"]*)vertical\-align\:\s*super;([^"]*)"/',
            'style="\1vertical-align: super; font-size: 66%;\2"',
            $bibliography
        );

        if ('de' != $this->lang) {
            // hack for pages
            $bibliography = preg_replace('/S\.\s*' . '/', 'pp. ', $bibliography);
        }

        // avoid space followed by non-breaking-space leading to double spacing
        $bibliography = preg_replace(
            '/\s(\x{00a0})/u',
            '\1',
            $bibliography
        );

        return $bibliography;
    }
}
