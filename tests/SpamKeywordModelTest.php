<?php

namespace JCrowe\BadWordFilter\Tests;

use JCrowe\BadWordFilter\BadWordFilter;
use JCrowe\BadWordFilter\Tests\TestCase;
use JCrowe\BadWordFilter\Models\SpamKeyword;

class SpamKeywordModelTest extends TestCase
{
    public function test_spam_keyword_model()
    {
        $key = SpamKeyword::create([
            'title' => 'Earn extra cash'
        ]);
   
        $this->assertTrue(true);
        $this->assertDatabaseCount('spam_keywords',1);
    }

}
