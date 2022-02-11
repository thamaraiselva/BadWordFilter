<?php

namespace JCrowe\BadWordFilter\Tests;

use JCrowe\BadWordFilter\Facades\BadWordFilter;

class BadWordFilterTest extends TestCase
{


    /**
     * Test that you can clean an html wrapped string and return html that
     * has not been replaced with '*' as per bug report
     * https://github.com/jcrowe206/BadWordFilter/issues/2
     */
    public function testHtmlWrapper()
    {
        $filter = new BadWordFilter(['also_check' => ['bad word']]);

        $this->assertEquals('<h3>b******d</h3>some text', $filter->clean('<h3>bad word</h3>some text'));
    }


    /**
     * Default cleaning works
     */
    public function testBadWordsAreCleaned()
    {
        $filter = new BadWordFilter();

        $this->assertEquals('s**t', $filter->clean('shit'));
        $this->assertEquals('f**k', $filter->clean('fuck'));
        $this->assertEquals('d******d', $filter->clean('dickhead'));
        $this->assertEquals('a**', $filter->clean('ass'));
    }


    /**
     * Should prefer the supplied replacement string instead of asterisks
     */
    public function testCustomReplace()
    {
        $filter = new BadWordFilter(['also_check' => ['replace me']]);
        $replaceWith = '#!<>*&';

        $this->assertEquals($replaceWith, $filter->clean('replace me', $replaceWith));
    }


    /**
     * Words that have special characters touching them should be treated
     * the same as words with spaces surrounding them
     */
    public function testSpecialCharactersAreIgnored()
    {
        $filter = new BadWordFilter(['also_check' => ['replace me']]);

        $this->assertEquals('#r********e', $filter->clean('#replace me'));
        $this->assertEquals('^r********e', $filter->clean('^replace me'));
        $this->assertEquals('%r********e', $filter->clean('%replace me'));
        $this->assertEquals('$r********e', $filter->clean('$replace me'));
        $this->assertEquals('@r********e', $filter->clean('@replace me'));
        $this->assertEquals('!r********e', $filter->clean('!replace me'));
        $this->assertEquals('r********e!', $filter->clean('replace me!'));
        $this->assertEquals('(r********e)', $filter->clean('(replace me)'));
        $this->assertEquals('<r********e>', $filter->clean('<replace me>'));
    }

    /**
     * Words that contain bad words should not match
     */
    public function testPartialMatchesDontGetCleaned()
    {
        $filter = new BadWordFilter();
        $myString = 'I am an ASSociative professor';

        $this->assertEquals($myString, $filter->clean($myString));
    }


    /**
     * Different words should be flagged based on the strictness level
     */
    public function testChangingStrictnessChangesWhichWordsAreCaught()
    {
        // very_strict
        $filter = new BadWordFilter();

        // misspellings
        $this->assertEquals('ahole', $filter->clean('ahole'));
        // strictest
        $this->assertEquals('anus', $filter->clean('anus'));
        // very_strict
        $this->assertEquals('d***o', $filter->clean('dildo'));
        // lenient
        $this->assertEquals('b***h', $filter->clean('bitch'));
        // permissive
        $this->assertEquals('c**k', $filter->clean('cock'));

        $filter = new BadWordFilter(['strictness' => 'misspellings']);

        $this->assertEquals('a***e', $filter->clean('ahole'));
        $this->assertEquals('a**s', $filter->clean('anus'));
        $this->assertEquals('d***o', $filter->clean('dildo'));
        $this->assertEquals('b***h', $filter->clean('bitch'));
        $this->assertEquals('c**k', $filter->clean('cock'));

        $filter = new BadWordFilter(['strictness' => 'strictest']);

        $this->assertEquals('ahole', $filter->clean('ahole'));
        $this->assertEquals('a**s', $filter->clean('anus'));
        $this->assertEquals('d***o', $filter->clean('dildo'));
        $this->assertEquals('b***h', $filter->clean('bitch'));
        $this->assertEquals('c**k', $filter->clean('cock'));

        $filter = new BadWordFilter(['strictness' => 'very_strict']);

        $this->assertEquals('ahole', $filter->clean('ahole'));
        $this->assertEquals('anus', $filter->clean('anus'));
        $this->assertEquals('d***o', $filter->clean('dildo'));
        $this->assertEquals('b***h', $filter->clean('bitch'));
        $this->assertEquals('c**k', $filter->clean('cock'));


        $filter = new BadWordFilter(['strictness' => 'lenient']);

        $this->assertEquals('ahole', $filter->clean('ahole'));
        $this->assertEquals('anus', $filter->clean('anus'));
        $this->assertEquals('dildo', $filter->clean('dildo'));
        $this->assertEquals('b***h', $filter->clean('bitch'));
        $this->assertEquals('c**k', $filter->clean('cock'));

        $filter = new BadWordFilter(['strictness' => 'permissive']);

        $this->assertEquals('ahole', $filter->clean('ahole'));
        $this->assertEquals('anus', $filter->clean('anus'));
        $this->assertEquals('dildo', $filter->clean('dildo'));
        $this->assertEquals('bitch', $filter->clean('bitch'));
        $this->assertEquals('c**k', $filter->clean('cock'));
    }


    /**
     * Should be able to determine if a string has filth in it
     */
    public function testIsDirtyFindsDirtyString()
    {
        $filter = new BadWordFilter();

        $this->assertFalse($filter->isDirty('my very clean string'));
        $this->assertTrue($filter->isDirty('my very fucking dirty string'));
    }


    /**
     * able to get a list of dirty words that are in a string
     */
    public function testCanGetListOfDirtyWordsFromString()
    {
        $filter = new BadWordFilter();

        $this->assertEquals([
            'fucking',
        ], $filter->getDirtyWordsFromString('my very fucking dirty string'));

        $this->assertEquals([
            'fucking',
            'shitty'
        ], $filter->getDirtyWordsFromString('my very fucking shitty dirty string'));
    }


    /**
     * Can parse an array and get list of dirty strings and their array key
     */
    public function testCanGetListOfDirtyWordsFromArray()
    {
        $filter = new BadWordFilter();

        $this->assertEquals([
                '1',
                '2',
                'filth',
        ], $filter->getDirtyKeysFromArray(['this is a clean string', 'this shit is dirty', 'fuck yo couch', 'actually that is a nice couch!', 'filth' => 'another shitty string']));
    }


    /**
     * Should be able to access bad keys in a multidimensional array
     */
    public function testCanGetListOfDirtyWordsFromMultidimensionalArray()
    {
        $filter = new BadWordFilter();

        $this->assertEquals([
            'filth.dirty',
            'filth.clean.1',
        ], $filter->getDirtyKeysFromArray([
            'filth' => [
                'dirty' => 'this shit is dirty',
                'clean' => [
                    'this one is clean',
                    'fuck it I lied, this one is dirty'
                ]
            ]
        ]));
    }


    /**
     * Should receive a cleaned array from the filter
     */
    public function testCanCleanADirtyArray()
    {
        $filter = new BadWordFilter();

        $cleanedString = $filter->clean([
            'filth' => [
                'dirty' => 'this shit is dirty',
                'clean' => [
                    'this one is clean',
                    'fuck it I lied, this one is dirty'
                ]
            ]
        ]);

        $this->assertEquals([
            'filth' => [
                'dirty' => 'this s**t is dirty',
                'clean' => [
                    'this one is clean',
                    'f**k it I lied, this one is dirty'
                ]
            ]
        ], $cleanedString);
    }


    /**
     * Using a custom bad words array should ignore the default
     * bad words list and strictly look for those words that
     * have been included in the 'bad_words_array' option
     */
    public function testUsingCustomArrayOfFilth()
    {
        $options = [
            'source' => 'array',
            'bad_words_array' => [
                'bad',
                'ugly',
                'mean'
            ]

        ];

        $filter = new BadWordFilter($options);

        $this->assertEquals('this is a b** string that has u**y and m**n words in it. fuck.', $filter->clean('this is a bad string that has ugly and mean words in it. fuck.'));
    }
}
