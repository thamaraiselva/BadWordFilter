<?php

namespace JCrowe\BadWordFilter;

use Illuminate\Support\Arr;
use Throwable;

class BadWordFilter
{
    public const SEPARATOR_PLACEHOLDER = '{!!}';

    /**
     * The default configurations for this package
     *
     * @var array|mixed
     */
    private $defaults = [];


    /**
     * All the configurations for this package. Created by merging user provided configurations
     * with the default configurations
     *
     * @var array
     */
    private $config = [];

    /**
     * Manages state of the object, if we are using a custom
     * word list this will be set to true
     *
     * @var bool
     */
    private $isUsingCustomDefinedWordList = false;

    /**
    * Escaped separator characters
    */
    protected $escapedSeparatorCharacters = [
        '\s',
    ];


    /**
     * A list of bad words to check for
     *
     * @var array
     */
    private $badWords = [];


    /**
     * The start of the regex we will build to check for bad word matches
     *
     * @var string
     */
    // private $regexStart = '/\b([-!$%^&*()_+|~=`{}\[\]:";\'?,.\/])?';


    /**
     * The end of the regex we ill build to check for bad word matches
     *
     * @var string
     */
    // private $regexEnd = '([-!$%^&*()_+|~=`{}\[\]:\";\'?,.\/])?\b/i';

    /**
     * Unescaped separator characters.
     * @var array
     */
    protected $separatorCharacters = [
        '@',
        '#',
        '%',
        '&',
        '_',
        ';',
        "'",
        '"',
        ',',
        '~',
        '`',
        '|',
        '!',
        '$',
        '^',
        '*',
        '(',
        ')',
        '-',
        '+',
        '=',
        '{',
        '}',
        '[',
        ']',
        ':',
        '<',
        '>',
        '?',
        '.',
        '/',
    ];


    /**
     * List of potential character substitutions as a regular expression.
     *
     * @var array
     */
    protected $characterSubstitutions = [
        '/a/' => [
            'a',
            '4',
            '@',
            'Á',
            'á',
            'À',
            'Â',
            'à',
            'Â',
            'â',
            'Ä',
            'ä',
            'Ã',
            'ã',
            'Å',
            'å',
            'æ',
            'Æ',
            'α',
            'Δ',
            'Λ',
            'λ',
        ],
        '/b/' => ['b', '8', '\\', '3', 'ß', 'Β', 'β'],
        '/c/' => ['c', 'Ç', 'ç', 'ć', 'Ć', 'č', 'Č', '¢', '€', '<', '(', '{', '©'],
        '/d/' => ['d', '\\', ')', 'Þ', 'þ', 'Ð', 'ð'],
        '/e/' => ['e', '3', '€', 'È', 'è', 'É', 'é', 'Ê', 'ê', 'ë', 'Ë', 'ē', 'Ē', 'ė', 'Ė', 'ę', 'Ę', '∑'],
        '/f/' => ['f', 'ƒ'],
        '/g/' => ['g', '6', '9'],
        '/h/' => ['h', 'Η'],
        '/i/' => ['i', '!', '|', ']', '[', '1', '∫', 'Ì', 'Í', 'Î', 'Ï', 'ì', 'í', 'î', 'ï', 'ī', 'Ī', 'į', 'Į'],
        '/j/' => ['j'],
        '/k/' => ['k', 'Κ', 'κ'],
        '/l/' => ['l', '!', '|', ']', '[', '£', '∫', 'Ì', 'Í', 'Î', 'Ï', 'ł', 'Ł'],
        '/m/' => ['m'],
        '/n/' => ['n', 'η', 'Ν', 'Π', 'ñ', 'Ñ', 'ń', 'Ń'],
        '/o/' => [
            'o',
            '0',
            'Ο',
            'ο',
            'Φ',
            '¤',
            '°',
            'ø',
            'ô',
            'Ô',
            'ö',
            'Ö',
            'ò',
            'Ò',
            'ó',
            'Ó',
            'œ',
            'Œ',
            'ø',
            'Ø',
            'ō',
            'Ō',
            'õ',
            'Õ',
        ],
        '/p/' => ['p', 'ρ', 'Ρ', '¶', 'þ'],
        '/q/' => ['q'],
        '/r/' => ['r', '®'],
        '/s/' => ['s', '5', '$', '§', 'ß', 'Ś', 'ś', 'Š', 'š'],
        '/t/' => ['t', 'Τ', 'τ'],
        '/u/' => ['u', 'υ', 'µ', 'û', 'ü', 'ù', 'ú', 'ū', 'Û', 'Ü', 'Ù', 'Ú', 'Ū'],
        '/v/' => ['v', 'υ', 'ν'],
        '/w/' => ['w', 'ω', 'ψ', 'Ψ'],
        '/x/' => ['x', 'Χ', 'χ'],
        '/y/' => ['y', '¥', 'γ', 'ÿ', 'ý', 'Ÿ', 'Ý'],
        '/z/' => ['z', 'Ζ', 'ž', 'Ž', 'ź', 'Ź', 'ż', 'Ż'],
    ];

    /**
     * List of profanities to test against.
     *
     * @var array
     */
    protected array $profanities = [];
    protected string $separatorExpression;
    protected array $characterExpressions;

    /**
     * Create the object and set up the bad words list and
     *
     * @param array $options
     *
     * @throws \Exception
     */
    public function __construct(array $options = [])
    {
        $this->defaults = include __DIR__ . '/../../config/config.php';

        if ($this->hasAlternateSource($options) || $this->hasAlternateSourceFile($options)) {
            $this->isUsingCustomDefinedWordList = true;
        }

        $this->config = array_merge($this->defaults, $options);

        $this->getBadWords();

        $this->separatorExpression = $this->generateSeparatorExpression();
        $this->characterExpressions = $this->generateCharacterExpressions();
    }

    /**
     * Generates the separator regular expression.
     *
     * @return string
     */
    private function generateSeparatorExpression(): string
    {
        return $this->generateEscapedExpression($this->separatorCharacters, $this->escapedSeparatorCharacters);
    }

    /**
     * Generates the separator regex to test characters in between letters.
     *
     * @param array $characters
     * @param array $escapedCharacters
     * @param string $quantifier
     *
     * @return string
     */
    private function generateEscapedExpression(
        array $characters = [],
        array $escapedCharacters = [],
        $quantifier = '*?'
    ) {
        $regex = $escapedCharacters;
        foreach ($characters as $character) {
            $regex[] = preg_quote($character, '/');
        }

        return '[' . implode('', $regex) . ']' . $quantifier;
    }

    /**
     * Generate a regular expression for a particular word
     * @return mixed
     */
    protected function generateProfanityExpression(string $word, array $characterExpressions, string $separatorExpression)
    {
        $expression = '/' . preg_replace(
            array_keys($characterExpressions),
            array_values($characterExpressions),
            $word
        ) . '/i';

        return str_replace(self::SEPARATOR_PLACEHOLDER, $separatorExpression, $expression);
    }

    /**
     * Generates a list of regular expressions for each character substitution.
     *
     * @return array
     */
    protected function generateCharacterExpressions()
    {
        $characterExpressions = [];
        foreach ($this->characterSubstitutions as $character => $substitutions) {
            $characterExpressions[$character] = $this->generateEscapedExpression(
                $substitutions,
                [],
                '+?'
            ) . self::SEPARATOR_PLACEHOLDER;
        }

        return $characterExpressions;
    }

    /**
     * Check if the provided $input contains any bad words
     *
     * @param string|array $input
     *
     * @return bool
     */
    public function isDirty($input)
    {
        return is_array($input) ? $this->isADirtyArray($input) : $this->isADirtyString($input);
    }

    /**
     * Clean the provided $input and return the cleaned array or string
     *
     * @param string|array $input
     * @param string|array $replaceWith
     *
     * @return array|string
     */
    public function scrub(string|array $input, string|array $replaceWith = '*')
    {
        return is_array($input) ? $this->cleanArray($replaceWith, $input) : $this->cleanString($input, $replaceWith);
    }

    /**
     * Clean the $input (array or string) and replace bad words with $replaceWith
     *
     * @return array|string
     */
    public function clean(string|array $input, string $replaceWith = '*'):array|string
    {
        return $this->scrub($input, $replaceWith);
    }

    /**
     * Get dirty words from the provided $string as an array of bad words
     */
    public function getDirtyWordsFromString(string $string): array
    {
        $badWords = [];
        $wordsToTest = $this->flattenArray($this->badWords);
        $profanities = [];

        foreach ($wordsToTest as $word) {
            // logger('word', [$word]);
            if (! is_string($word)) {
                continue;
            }
            $profanities[] = $this->generateProfanityExpression(
                $word,
                $this->characterExpressions,
                $this->separatorExpression
            );

            // $word = preg_quote($word);
        }
        // dump($profanities);
        foreach ($profanities as $profanity) {
            $matchedString = $this->getMatchedString($profanity, $string);
            if ($matchedString === '') {
                continue;
            }

            // logger('is string', [is_string($matchedString), $matchedString]);
            $badWords[] = $matchedString;
        }


        return $badWords;
    }

    private function getMatchedString($profanity, $string): string
    {
        try {
            preg_match($profanity, $string, $matchedString);

            return mb_convert_encoding($matchedString[0], 'UTF-8', 'UTF-8');
        } catch (Throwable $th) {
            // logger('$profanity', [$profanity]);
            return '';
        }
    }

    /**
     * Get an array of key/value pairs of dirty keys in the $input array
     *
     * @param array $input
     *
     * @return array
     */
    public function getDirtyKeysFromArray(array $input = [])
    {
        return $this->findBadWordsInArray($input);
    }

    /**
     * Check if the current model is set up to use a custom defined word list
     *
     * @return bool
     */
    private function isUsingCustomDefinedWordList()
    {
        return $this->isUsingCustomDefinedWordList;
    }

    /**
     * Check if the $input array is dirty or not
     *
     * @param array $input
     *
     * @return bool
     */
    private function isADirtyArray(array $input): bool
    {
        return $this->findBadWordsInArray($input) ? true : false;
    }

    /**
     * Return an array of bad words that were found in the $input array along with their keys
     *
     * @param array $input
     * @param bool $previousKey
     *
     * @return array
     */
    private function findBadWordsInArray(array $input = [], $previousKey = false)
    {
        $dirtyKeys = [];

        foreach ($input as $key => $value) {

            // create the "dot" notation keys
            if ($previousKey !== false) {
                $key = $previousKey . '.' . $key;
            }

            if (is_array($value)) {

                // call recursively to handle multidimensional array,
                $dirtyKeys[] = $this->findBadWordsInArray($value, $key);
            } else {
                if (is_string($value)) {
                    if ($this->isADirtyString($value)) {

                        // bad word found, add the current key to the dirtyKeys array
                        $dirtyKeys[] = (string) $key;
                    }
                } else {
                    continue;
                }
            }
        }

        return $this->flattenArray($dirtyKeys);
    }

    /**
     * Clean all the bad words from the input 
     *
     * @return mixed
     */
    private function cleanArray(string|array $replaceWith, array|string $array = [])
    {
        $dirtyKeys = $this->findBadWordsInArray($array);

        foreach ($dirtyKeys as $key) {
            $this->cleanArrayKey($key, $array, $replaceWith);
        }

        return $array;
    }

    /**
     * Clean the string stored at $key in the $array
     *
     * @return mixed
     */
    private function cleanArrayKey(string $key, array|string &$array, array|string $replaceWith)
    {
        $keys = explode('.', $key);

        foreach ($keys as $k) {
            $array = &$array[$k];
        }

        return $array = $this->cleanString($array, $replaceWith);
    }

    /**
     * Clean the input $string and replace the bad word with the $replaceWith value
     *
     * @return mixed
     */
    private function cleanString(string $string, array|string  $replaceWith)
    {
        $words = $this->getDirtyWordsFromString($string);

        if ($words) {
            foreach ($words as $word) {
                if (! strlen($word)) {
                    continue;
                }

                if ($replaceWith === '*') {
                    $fc = $word[0];
                    $lc = $word[strlen($word) - 1];
                    $len = strlen($word);

                    $newWord = $len > 3 ? $fc . str_repeat('*', $len - 2) . $lc : $fc . '**';
                } else {
                    $newWord = $replaceWith;
                }

                $string = preg_replace("/$word/", $newWord, $string);
            }
        }

        return $string;
    }

    /**
     * Check if the $input parameter is a dirty string
     *
     * @return bool
     */
    private function isADirtyString(string $input)
    {
        return $this->strContainsBadWords($input);
    }

    /**
     * Check if the input $string contains bad words
     *
     * @return bool
     */
    private function strContainsBadWords(string $string)
    {
        return $this->getDirtyWordsFromString($string) ? true : false;
    }

    /**
     * Set the bad words array to the model if not already set and return it
     *
     * @return array|void
     * @throws \Exception
     */
    private function getBadWords()
    {
        if (! $this->badWords) {
            switch ($this->config['source']) {

                case 'file':
                    $this->badWords = $this->getBadWordsFromConfigFile();

                    break;

                case 'array':
                    $this->badWords = $this->getBadWordsFromArray();

                    break;

                case 'database':
                    $this->badWords = $this->getBadWordsFromDB();

                    break;

                default:
                    throw new \Exception('Config source was not a valid type. Valid types are: file, database, cache');
            }

            if (! $this->isUsingCustomDefinedWordList()) {
                switch ($this->config['strictness']) {

                    case 'permissive':
                        $this->badWords = $this->getBadWordsByKey(['permissive']);

                        break;

                    case 'lenient':
                        $this->badWords = $this->getBadWordsByKey(['permissive', 'lenient']);

                        break;

                    case 'strict':
                        $this->badWords = $this->getBadWordsByKey(['permissive', 'lenient', 'strict']);

                        break;

                    case 'very_strict':
                        $this->badWords = $this->getBadWordsByKey(['permissive', 'lenient', 'strict', 'very_strict']);

                        break;

                    case 'strictest':
                        $this->badWords = $this->getBadWordsByKey([
                            'permissive',
                            'lenient',
                            'strict',
                            'very_strict',
                            'strictest',
                        ]);

                        break;

                    case 'misspellings':
                    case 'all':
                        $this->badWords = $this->getBadWordsByKey([
                            'permissive',
                            'lenient',
                            'strict',
                            'very_strict',
                            'strictest',
                            'misspellings',
                        ]);

                        break;

                    default:
                        $this->badWords = $this->getBadWordsByKey(['permissive', 'lenient', 'strict', 'very_strict']);

                        break;

                }
            }

            if (! empty($this->config['also_check'])) {
                if (! is_array($this->config['also_check'])) {
                    $this->config['also_check'] = [$this->config['also_check']];
                }

                $this->badWords = array_merge($this->badWords, $this->config['also_check']);
            }
        }

        return $this->badWords;
    }

    /**
     * Get subset of the bad words by an array of $keys
     *
     * @param array $keys
     *
     * @return array
     */
    private function getBadWordsByKey(array $keys)
    {
        $bw = [];
        foreach ($keys as $key) {
            if (! empty($this->badWords[$key])) {
                $bw[] = $this->badWords[$key];
            }
        }

        return $bw;
    }

    /**
     * Get the bad words list from a config file
     *
     * @return array
     * @throws \Exception
     */
    private function getBadWordsFromConfigFile()
    {
        if (file_exists($this->config['source_file'])) {
            return include $this->config['source_file'];
        }

        throw new \Exception('Source was config but the config file was not set or contained an invalid path. Tried looking for it at: ' . $this->config['source_file']);
    }

    /**
     * Get the bad words from the array in the config
     *
     * @return array
     * @throws \Exception
     */
    private function getBadWordsFromArray()
    {
        if (! empty($this->config['bad_words_array']) && is_array($this->config['bad_words_array'])) {
            return $this->config['bad_words_array'];
        }

        throw new \Exception('Source is set to "array" but bad_words_array is either empty or not an array.');
    }

    /**
     * Get bad words from the database - not yet supported
     *
     * @throws \Exception
     */
    private function getBadWordsFromDB()
    {
        throw new \Exception('Bad words from db is not yet supported. If you would like to see this feature please consider submitting a pull request.');
    }

    /**
     * Flatten the input $array
     *
     * @return mixed
     */
    private function flattenArray(array $array)
    {
        $objTmp = (object)['aFlat' => []];

        /*  $callBack = function(&$v, $k, &$t) {
             $t->aFlat[] = $v;
         };

         array_walk_recursive($array, $callBack, $objTmp); */

        $objTmp->aFlat = Arr::Flatten($array);

        return $objTmp->aFlat;
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    private function hasAlternateSource(array $options)
    {
        return ! empty($options['source']) && $options['source'] !== $this->defaults['source'];
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    private function hasAlternateSourceFile(array $options)
    {
        return ! empty($options['source_file']) && $options['source_file'] !== $this->defaults['source_file'];
    }
}
