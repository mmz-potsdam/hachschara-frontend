<?php

namespace App\Utils;

class MysqlFulltextSimpleParser
{
    var $min_length = 0; // you might have to turn this up to ft_min_word_len
    var $output = '';

    const LEXER_ENTER = 1;
    const LEXER_MATCHED = 2;
    const LEXER_UNMATCHED = 3;
    const LEXER_EXIT = 4;
    const LEXER_SPECIAL = 5;

    /**
     * Callback function (or mode / state), called by the Lexer. This one
     * deals with text outside of a variable reference.
     * @param string the matched text
     * @param int lexer state (ignored here)
     */
    function accept($match, $state)
    {
        // echo "$state: -$match-<br />";
        if ($state == self::LEXER_UNMATCHED
            && strlen($match) < $this->min_length && strpos($match, '*') === false) {
            return true;
        }

        if ($state != self::LEXER_MATCHED) {
            $this->output .= '+';
        }

        $this->output .= $match;

        return true;
    }

    function writeQuoted($match, $state)
    {
        static $words;

        switch ($state) {
            // Entering the variable reference
            case self::LEXER_ENTER:
                $words = [];
                break;

                // Contents of the variable reference
            case self::LEXER_MATCHED:
                break;

            case self::LEXER_UNMATCHED:
                if (strlen($match) >= $this->min_length) {
                    $words[] = $match;
                }
                break;

                // Exiting the variable reference
            case self::LEXER_EXIT:
                if (count($words) > 0) {
                    $this->output .= '+"' . implode(' ', $words) . '"';
                }
                break;
        }

        return true;
    }

    static function parseFulltextBoolean($search, $append_wildcard = false)
    {
        if (preg_match("/[\+\-][\b\"]/", $search)) {
            return $search;
        }

        $parser = new MysqlFulltextSimpleParser();
        $lexer = new SimpleLexer($parser);
        $lexer->addPattern("\\s+");
        $lexer->addEntryPattern('"', 'accept', 'writeQuoted');
        $lexer->addPattern("\\s+", 'writeQuoted');
        $lexer->addExitPattern('"', 'writeQuoted');

        // do it
        $lexer->parse($search);

        $ret = $parser->output ?? '';

        if ($append_wildcard && preg_match('/^\+[^\'\*\s]+$/', $ret)) {
            // TODO: maybe improve this
            $ret .= '*';
        }

        return $ret;
    }
}

/**
 *  base include file for SimpleTest
 *  @package    SimpleTest
 *  @subpackage MockObjects
 *  @version    $Id: parser.php,v 1.58 2004/06/02 01:25:25 lastcraft Exp $
 */

/**#@+
 * Lexer mode stack constants
 */
define("LEXER_ENTER", 1);
define("LEXER_MATCHED", 2);
define("LEXER_UNMATCHED", 3);
define("LEXER_EXIT", 4);
define("LEXER_SPECIAL", 5);
/**#@-*/

/**
 *    Compounded regular expression. Any of
 *    the contained patterns could match and
 *    when one does it's label is returned.
 *    @package SimpleTest
 *    @subpackage WebTester
 */
class ParallelRegex
{
    var $_patterns;
    var $_labels;
    var $_regex;
    var $_case;

    /**
     *    Constructor. Starts with no patterns.
     *    @param boolean $case    True for case sensitive, false
     *                            for insensitive.
     *    @access public
     */
    function __construct($case)
    {
        $this->_case = $case;
        $this->_patterns = [];
        $this->_labels = [];
        $this->_regex = null;
    }

    function ParallelRegex($case)
    {
        self::__construct($case);
    }

    /**
     *    Adds a pattern with an optional label.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $label        Label of regex to be returned
     *                                on a match.
     *    @access public
     */
    function addPattern($pattern, $label = true)
    {
        $count = count($this->_patterns);
        $this->_patterns[$count] = $pattern;
        $this->_labels[$count] = $label;
        $this->_regex = null;
    }

    /**
     *    Attempts to match all patterns at once against
     *    a string.
     *    @param string $subject      String to match against.
     *    @param string $match        First matched portion of
     *                                subject.
     *    @return boolean             True on success.
     *    @access public
     */
    function match($subject, &$match)
    {
        if (count($this->_patterns) == 0) {
            return false;
        }

        if (! preg_match($this->_getCompoundedRegex(), $subject, $matches)) {
            $match = "";
            return false;
        }

        $match = $matches[0];
        for ($i = 1; $i < count($matches); $i++) {
            if ($matches[$i]) {
                return $this->_labels[$i - 1];
            }
        }

        return true;
    }

    /**
     *    Compounds the patterns into a single
     *    regular expression separated with the
     *    "or" operator. Caches the regex.
     *    Will automatically escape (, ) and / tokens.
     *    @param array $patterns    List of patterns in order.
     *    @access private
     */
    function _getCompoundedRegex()
    {
        if ($this->_regex == null) {
            for ($i = 0; $i < count($this->_patterns); $i++) {
                $this->_patterns[$i] = '(' . str_replace(
                    [ '/', '(', ')' ],
                    [ '\/', '\(', '\)' ],
                    $this->_patterns[$i]
                ) . ')';
            }
            $this->_regex = "/" . implode("|", $this->_patterns) . "/" . $this->_getPerlMatchingFlags();
        }

        return $this->_regex;
    }

    /**
     *    Accessor for perl regex mode flags to use.
     *    @return string       Perl regex flags.
     *    @access private
     */
    function _getPerlMatchingFlags()
    {
        return ($this->_case ? "msS" : "msSi");
    }
}

/**
 *    States for a stack machine.
 *    @package SimpleTest
 *    @subpackage WebTester
 */
class SimpleStateStack
{
    var $_stack;

    /**
     *    Constructor. Starts in named state.
     *    @param string $start        Starting state name.
     *    @access public
     */
    function __construct($start)
    {
        $this->_stack = [ $start ];
    }

    function SimpleStateStack($start)
    {
        self::__construct($start);
    }

    /**
     *    Accessor for current state.
     *    @return string       State.
     *    @access public
     */
    function getCurrent()
    {
        return $this->_stack[count($this->_stack) - 1];
    }

    /**
     *    Adds a state to the stack and sets it
     *    to be the current state.
     *    @param string $state        New state.
     *    @access public
     */
    function enter($state)
    {
        array_push($this->_stack, $state);
    }

    /**
     *    Leaves the current state and reverts
     *    to the previous one.
     *    @return boolean    False if we drop off
     *                       the bottom of the list.
     *    @access public
     */
    function leave()
    {
        if (count($this->_stack) == 1) {
            return false;
        }

        array_pop($this->_stack);

        return true;
    }
}

/**
 *    Accepts text and breaks it into tokens.
 *    Some optimisation to make the sure the
 *    content is only scanned by the PHP regex
 *    parser once. Lexer modes must not start
 *    with leading underscores.
 *    @package SimpleTest
 *    @subpackage WebTester
 */
class SimpleLexer
{
    var $_regexes;
    var $_parser;
    var $_mode;
    var $_mode_handlers;
    var $_case;

    /**
     *    Sets up the lexer in case insensitive matching
     *    by default.
     *    @param SimpleSaxParser $parser  Handling strategy by
     *                                    reference.
     *    @param string $start            Starting handler.
     *    @param boolean $case            True for case sensitive.
     *    @access public
     */
    function __construct(&$parser, $start = "accept", $case = false)
    {
        $this->_case = $case;
        $this->_regexes = [];
        $this->_parser = &$parser;
        $this->_mode = new SimpleStateStack($start);
        $this->_mode_handlers = [];
    }

    function SimpleLexer(&$parser, $start = "accept", $case = false)
    {
        self::__construct($parser, $start, $case);
    }

    /**
     *    Adds a token search pattern for a particular
     *    parsing mode. The pattern does not change the
     *    current mode.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $mode         Should only apply this
     *                                pattern when dealing with
     *                                this type of input.
     *    @access public
     */
    function addPattern($pattern, $mode = "accept")
    {
        if (! isset($this->_regexes[$mode])) {
            $this->_regexes[$mode] = new ParallelRegex($this->_case);
        }

        $this->_regexes[$mode]->addPattern($pattern);
    }

    /**
     *    Adds a pattern that will enter a new parsing
     *    mode. Useful for entering parenthesis, strings,
     *    tags, etc.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $mode         Should only apply this
     *                                pattern when dealing with
     *                                this type of input.
     *    @param string $new_mode     Change parsing to this new
     *                                nested mode.
     *    @access public
     */
    function addEntryPattern($pattern, $mode, $new_mode)
    {
        if (! isset($this->_regexes[$mode])) {
            $this->_regexes[$mode] = new ParallelRegex($this->_case);
        }

        $this->_regexes[$mode]->addPattern($pattern, $new_mode);
    }

    /**
     *    Adds a pattern that will exit the current mode
     *    and re-enter the previous one.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $mode         Mode to leave.
     *    @access public
     */
    function addExitPattern($pattern, $mode)
    {
        if (! isset($this->_regexes[$mode])) {
            $this->_regexes[$mode] = new ParallelRegex($this->_case);
        }

        $this->_regexes[$mode]->addPattern($pattern, "__exit");
    }

    /**
     *    Adds a pattern that has a special mode. Acts as an entry
     *    and exit pattern in one go, effectively calling a special
     *    parser handler for this token only.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $mode         Should only apply this
     *                                pattern when dealing with
     *                                this type of input.
     *    @param string $special      Use this mode for this one token.
     *    @access public
     */
    function addSpecialPattern($pattern, $mode, $special)
    {
        if (! isset($this->_regexes[$mode])) {
            $this->_regexes[$mode] = new ParallelRegex($this->_case);
        }

        $this->_regexes[$mode]->addPattern($pattern, "_$special");
    }

    /**
     *    Adds a mapping from a mode to another handler.
     *    @param string $mode        Mode to be remapped.
     *    @param string $handler     New target handler.
     *    @access public
     */
    function mapHandler($mode, $handler)
    {
        $this->_mode_handlers[$mode] = $handler;
    }

    /**
     *    Splits the page text into tokens. Will fail
     *    if the handlers report an error or if no
     *    content is consumed. If successful then each
     *    unparsed and parsed token invokes a call to the
     *    held listener.
     *    @param string $raw        Raw HTML text.
     *    @return boolean           True on success, else false.
     *    @access public
     */
    function parse($raw)
    {
        if (! isset($this->_parser)) {
            return false;
        }

        $length = strlen($raw);
        while (is_array($parsed = $this->_reduce($raw))) {
            [$unmatched, $matched, $mode] = $parsed;
            if (! $this->_dispatchTokens($unmatched, $matched, $mode)) {
                return false;
            }
            if (strlen($raw) == $length) {
                return false;
            }
            $length = strlen($raw);
        }

        if (!$parsed) {
            return false;
        }
        return $this->_invokeParser($raw, LEXER_UNMATCHED);
    }

    /**
     *    Sends the matched token and any leading unmatched
     *    text to the parser changing the lexer to a new
     *    mode if one is listed.
     *    @param string $unmatched    Unmatched leading portion.
     *    @param string $matched      Actual token match.
     *    @param string $mode         Mode after match. A boolean
     *                                false mode causes no change.
     *    @return boolean             False if there was any error
     *                                from the parser.
     *    @access private
     */
    function _dispatchTokens($unmatched, $matched, $mode = false)
    {
        if (! $this->_invokeParser($unmatched, LEXER_UNMATCHED)) {
            return false;
        }

        if ($this->_isModeEnd($mode)) {
            if (! $this->_invokeParser($matched, LEXER_EXIT)) {
                return false;
            }

            return $this->_mode->leave();
        }

        if ($this->_isSpecialMode($mode)) {
            $this->_mode->enter($this->_decodeSpecial($mode));
            if (! $this->_invokeParser($matched, LEXER_SPECIAL)) {
                return false;
            }

            return $this->_mode->leave();
        }

        if (is_string($mode)) {
            $this->_mode->enter($mode);
            return $this->_invokeParser($matched, LEXER_ENTER);
        }

        return $this->_invokeParser($matched, LEXER_MATCHED);
    }

    /**
     *    Tests to see if the new mode is actually to leave
     *    the current mode and pop an item from the matching
     *    mode stack.
     *    @param string $mode    Mode to test.
     *    @return boolean        True if this is the exit mode.
     *    @access private
     */
    function _isModeEnd($mode)
    {
        return ($mode === "__exit");
    }

    /**
     *    Test to see if the mode is one where this mode
     *    is entered for this token only and automatically
     *    leaves immediately afterwoods.
     *    @param string $mode    Mode to test.
     *    @return boolean        True if this is the exit mode.
     *    @access private
     */
    function _isSpecialMode($mode)
    {
        return (strncmp($mode, "_", 1) == 0);
    }

    /**
     *    Strips the magic underscore marking single token
     *    modes.
     *    @param string $mode    Mode to decode.
     *    @return string         Underlying mode name.
     *    @access private
     */
    function _decodeSpecial($mode)
    {
        return substr($mode, 1);
    }

    /**
     *    Calls the parser method named after the current
     *    mode. Empty content will be ignored. The lexer
     *    has a parser handler for each mode in the lexer.
     *    @param string $content        Text parsed.
     *    @param boolean $is_match      Token is recognised rather
     *                                  than unparsed data.
     *    @access private
     */
    function _invokeParser($content, $is_match)
    {
        if (($content === "") || ($content === false)) {
            return true;
        }

        $handler = $this->_mode->getCurrent();

        if (isset($this->_mode_handlers[$handler])) {
            $handler = $this->_mode_handlers[$handler];
        }

        return $this->_parser->$handler($content, $is_match);
    }

    /**
     *    Tries to match a chunk of text and if successful
     *    removes the recognised chunk and any leading
     *    unparsed data. Empty strings will not be matched.
     *    @param string $raw         The subject to parse. This is the
     *                               content that will be eaten.
     *    @return array              Three item list of unparsed
     *                               content followed by the
     *                               recognised token and finally the
     *                               action the parser is to take.
     *                               True if no match, false if there
     *                               is a parsing error.
     *    @access private
     */
    function _reduce(&$raw)
    {
        if (! isset($this->_regexes[$this->_mode->getCurrent()])) {
            return false;
        }

        if ($raw === "") {
            return true;
        }

        $match = '';
        if ($action = $this->_regexes[$this->_mode->getCurrent()]->match($raw, $match)) {
            $unparsed_character_count = strpos($raw, $match);
            $unparsed = substr($raw, 0, $unparsed_character_count);
            $raw = substr($raw, $unparsed_character_count + strlen($match));
            return [ $unparsed, $match, $action ];
        }

        return true;
    }
}

/**
 *    Converts HTML tokens into selected SAX events.
 *    @package SimpleTest
 *    @subpackage WebTester
 */
class SimpleSaxParser
{
    var $_lexer;
    var $_listener;
    var $_tag;
    var $_attributes;
    var $_current_attribute;

    /**
     *    Sets the listener.
     *    @param SimpleSaxListener $listener    SAX event handler.
     *    @access public
     */
    function __construct(&$listener)
    {
        $this->_listener = &$listener;
        $this->_lexer = &$this->createLexer($this);
        $this->_tag = '';
        $this->_attributes = [];
        $this->_current_attribute = '';
    }

    function SimpleSaxParser(&$listener)
    {
        self::__construct($listener);
    }

    /**
     *    Runs the content through the lexer which
     *    should call back to the acceptors.
     *    @param string $raw      Page text to parse.
     *    @return boolean         False if parse error.
     *    @access public
     */
    function parse($raw)
    {
        return $this->_lexer->parse($raw);
    }

    /**
     *    Sets up the matching lexer. Starts in 'text' mode.
     *    @param SimpleSaxParser $parser    Event generator, usually $self.
     *    @return SimpleLexer               Lexer suitable for this parser.
     *    @access public
     *    @static
     */
    function &createLexer(&$parser)
    {
        $lexer = new SimpleLexer($parser, 'text');
        $lexer->mapHandler('text', 'acceptTextToken');
        SimpleSaxParser::_addSkipping($lexer);
        foreach (SimpleSaxParser::_getParsedTags() as $tag) {
            SimpleSaxParser::_addTag($lexer, $tag);
        }
        SimpleSaxParser::_addInTagTokens($lexer);

        return $lexer;
    }

    /**
     *    List of parsed tags. Others are ignored.
     *    @return array        List of searched for tags.
     *    @access private
     */
    function _getParsedTags()
    {
        return [ 'a', 'title', 'form', 'input', 'textarea', 'select',
            'option', 'frameset', 'frame' ];
    }

    /**
     *    The lexer has to skip certain sections such
     *    as server code, client code and styles.
     *    @param SimpleLexer $lexer        Lexer to add patterns to.
     *    @access private
     *    @static
     */
    function _addSkipping(&$lexer)
    {
        $lexer->mapHandler('css', 'ignore');
        $lexer->addEntryPattern('<style', 'text', 'css');
        $lexer->addExitPattern('</style>', 'css');
        $lexer->mapHandler('js', 'ignore');
        $lexer->addEntryPattern('<script', 'text', 'js');
        $lexer->addExitPattern('</script>', 'js');
        $lexer->mapHandler('comment', 'ignore');
        $lexer->addEntryPattern('<!--', 'text', 'comment');
        $lexer->addExitPattern('-->', 'comment');
    }

    /**
     *    Pattern matches to start and end a tag.
     *    @param SimpleLexer $lexer   Lexer to add patterns to.
     *    @param string $tag          Name of tag to scan for.
     *    @access private
     *    @static
     */
    function _addTag(&$lexer, $tag)
    {
        $lexer->addSpecialPattern("</$tag>", 'text', 'acceptEndToken');
        $lexer->addEntryPattern("<$tag", 'text', 'tag');
    }

    /**
     *    Pattern matches to parse the inside of a tag
     *    including the attributes and their quoting.
     *    @param SimpleLexer $lexer    Lexer to add patterns to.
     *    @access private
     *    @static
     */
    function _addInTagTokens(&$lexer)
    {
        $lexer->mapHandler('tag', 'acceptStartToken');
        $lexer->addSpecialPattern('\s+', 'tag', 'ignore');
        SimpleSaxParser::_addAttributeTokens($lexer);
        $lexer->addExitPattern('>', 'tag');
    }

    /**
     *    Matches attributes that are either single quoted,
     *    double quoted or unquoted.
     *    @param SimpleLexer $lexer     Lexer to add patterns to.
     *    @access private
     *    @static
     */
    function _addAttributeTokens(&$lexer)
    {
        $lexer->mapHandler('dq_attribute', 'acceptAttributeToken');
        $lexer->addEntryPattern('=\s*"', 'tag', 'dq_attribute');
        $lexer->addPattern("\\\\\"", 'dq_attribute');
        $lexer->addExitPattern('"', 'dq_attribute');
        $lexer->mapHandler('sq_attribute', 'acceptAttributeToken');
        $lexer->addEntryPattern("=\s*'", 'tag', 'sq_attribute');
        $lexer->addPattern("\\\\'", 'sq_attribute');
        $lexer->addExitPattern("'", 'sq_attribute');
        $lexer->mapHandler('uq_attribute', 'acceptAttributeToken');
        $lexer->addSpecialPattern('=\s*[^>\s]*', 'tag', 'uq_attribute');
    }

    /**
     *    Accepts a token from the tag mode. If the
     *    starting element completes then the element
     *    is dispatched and the current attributes
     *    set back to empty. The element or attribute
     *    name is converted to lower case.
     *    @param string $token     Incoming characters.
     *    @param integer $event    Lexer event type.
     *    @return boolean          False if parse error.
     *    @access public
     */
    function acceptStartToken($token, $event)
    {
        if ($event == LEXER_ENTER) {
            $this->_tag = strtolower(substr($token, 1));
            return true;
        }

        if ($event == LEXER_EXIT) {
            $success = $this->_listener->startElement(
                $this->_tag,
                $this->_attributes
            );
            $this->_tag = "";
            $this->_attributes = [];
            return $success;
        }

        if ($token != "=") {
            $this->_current_attribute = strtolower($this->_decodeHtml($token));
            $this->_attributes[$this->_current_attribute] = "";
        }

        return true;
    }

    /**
     *    Accepts a token from the end tag mode.
     *    The element name is converted to lower case.
     *    @param string $token     Incoming characters.
     *    @param integer $event    Lexer event type.
     *    @return boolean          False if parse error.
     *    @access public
     */
    function acceptEndToken($token, $event)
    {
        if (! preg_match('/<\/(.*)>/', $token, $matches)) {
            return false;
        }

        return $this->_listener->endElement(strtolower($matches[1]));
    }

    /**
     *    Part of the tag data.
     *    @param string $token     Incoming characters.
     *    @param integer $event    Lexer event type.
     *    @return boolean          False if parse error.
     *    @access public
     */
    function acceptAttributeToken($token, $event)
    {
        if ($event == LEXER_UNMATCHED) {
            $this->_attributes[$this->_current_attribute] .=
                    $this->_decodeHtml($token);
        }

        if ($event == LEXER_SPECIAL) {
            $this->_attributes[$this->_current_attribute] .=
                    preg_replace('/^=\s*/', '', $this->_decodeHtml($token));
        }

        return true;
    }

    /**
     *    A character entity.
     *    @param string $token    Incoming characters.
     *    @param integer $event   Lexer event type.
     *    @return boolean         False if parse error.
     *    @access public
     */
    function acceptEntityToken($token, $event)
    {
        return true;
    }

    /**
     *    Character data between tags regarded as
     *    important.
     *    @param string $token     Incoming characters.
     *    @param integer $event    Lexer event type.
     *    @return boolean          False if parse error.
     *    @access public
     */
    function acceptTextToken($token, $event)
    {
        return $this->_listener->addContent($token);
    }

    /**
     *    Incoming data to be ignored.
     *    @param string $token     Incoming characters.
     *    @param integer $event    Lexer event type.
     *    @return boolean          False if parse error.
     *    @access public
     */
    function ignore($token, $event)
    {
        return true;
    }

    /**
     *    Decodes any HTML entities.
     *    @param string $html    Incoming HTML.
     *    @return string         Outgoing plain text.
     *    @access private
     */
    function _decodeHtml($html)
    {
        return strtr(
            $html,
            array_flip(get_html_translation_table(HTML_ENTITIES))
        );
    }
}

/**
 *    SAX event handler.
 *    @package SimpleTest
 *    @subpackage WebTester
 *    @abstract
 */
class SimpleSaxListener
{
    /**
     *    Sets the document to write to.
     *    @access public
     */
    function __construct() {}

    function SimpleSaxListener()
    {
        self::__construct();
    }

    /**
     *    Start of element event.
     *    @param string $name        Element name.
     *    @param hash $attributes    Name value pairs.
     *                               Attributes without content
     *                               are marked as true.
     *    @return boolean            False on parse error.
     *    @access public
     */
    function startElement($name, $attributes)
    {
        return true;
    }

    /**
     *    End of element event.
     *    @param string $name        Element name.
     *    @return boolean            False on parse error.
     *    @access public
     */
    function endElement($name)
    {
        return true;
    }

    /**
     *    Unparsed, but relevant data.
     *    @param string $text        May include unparsed tags.
     *    @return boolean            False on parse error.
     *    @access public
     */
    function addContent($text)
    {
        return true;
    }
}
