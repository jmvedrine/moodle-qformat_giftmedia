<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for the Moodle GIFT with medias format.
 *
 * @package    qformat_giftmedia
 * @copyright  2017 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/format/gift/format.php');
require_once($CFG->dirroot . '/question/format/giftmedia/format.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');


/**
 * Unit tests for the GIFT with medias import format.
 * This test is in fact similar to the import tests made for the GIFT
 * format to ensure that giftmedia and gift produce the same results
 * parsing the same gift strings.
 * Note that there are completely useless empty files arrays in some
 * places because qformat_gift methods keep adding them.
 *
 * @copyright 2017 Jean-Michel Vedrine
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qformat_giftmedia_test extends question_testcase {
    public function assert_same_gift($expectedtext, $text) {
        $this->assertEquals(str_replace("\r\n", "\n", $expectedtext),
                str_replace("\r\n", "\n", $text));
    }

    public function test_import_essay() {
        $gift = '
// essay
::Q8:: How are you? {}';
        $lines = preg_split('/[\\n\\r]/', str_replace("\r\n", "\n", $gift));

        $importer = new qformat_giftmedia();
        $q = $importer->readquestion($lines);

        $expectedq = (object) array(
            'name' => 'Q8',
            'questiontext' => 'How are you?',
            'questiontextformat' => FORMAT_HTML,
            'generalfeedback' => '',
            'generalfeedbackformat' => FORMAT_HTML,
            'qtype' => 'essay',
            'defaultmark' => 1,
            'penalty' => 0.3333333,
            'length' => 1,
            'responseformat' => 'editor',
            'responsefieldlines' => 15,
            'attachments' => 0,
            'graderinfo' => array(
                'text' => '',
                'format' => FORMAT_HTML,
            ),
        );

        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }

    public function test_import_match() {
        $gift = '
// question: 2  name: Moodle activities
::Moodle activities::[html]Match the <b>activity</b> to the description.{
    =[html]An activity supporting asynchronous discussions. -> Forum
    =[moodle]A teacher asks a question and specifies a choice of multiple responses. -> Choice
    =[plain]A bank of record entries which participants can add to. -> Database
    =[markdown]A collection of web pages that anyone can add to or edit. -> Wiki
    = -> Chat
}';
        $lines = preg_split('/[\\n\\r]/', str_replace("\r\n", "\n", $gift));

        $importer = new qformat_giftmedia();
        $q = $importer->readquestion($lines);

        $expectedq = (object) array(
            'name' => 'Moodle activities',
            'questiontext' => 'Match the <b>activity</b> to the description.',
            'questiontextformat' => FORMAT_HTML,
            'generalfeedback' => '',
            'generalfeedbackformat' => FORMAT_HTML,
            'qtype' => 'match',
            'defaultmark' => 1,
            'penalty' => 0.3333333,
            'length' => 1,
            'shuffleanswers' => '1',
            'correctfeedback' => array(
                'text' => '',
                'format' => FORMAT_HTML,
                'files' => array(),
            ),
            'partiallycorrectfeedback' => array(
                'text' => '',
                'format' => FORMAT_HTML,
                'files' => array(),
            ),
            'incorrectfeedback' => array(
                'text' => '',
                'format' => FORMAT_HTML,
                'files' => array(),
            ),
            'subquestions' => array(
                0 => array(
                    'text' => 'An activity supporting asynchronous discussions.',
                    'format' => FORMAT_HTML,
                ),
                1 => array(
                    'text' => 'A teacher asks a question and specifies a choice of multiple responses.',
                    'format' => FORMAT_HTML,
                ),
                2 => array(
                    'text' => 'A bank of record entries which participants can add to.',
                    'format' => FORMAT_HTML,
                ),
                3 => array(
                    'text' => 'A collection of web pages that anyone can add to or edit.',
                    'format' => FORMAT_HTML,
                ),
                4 => array(
                    'text' => '',
                    'format' => FORMAT_HTML,
                ),
            ),
            'subanswers' => array(
                0 => 'Forum',
                1 => 'Choice',
                2 => 'Database',
                3 => 'Wiki',
                4 => 'Chat',
            ),
        );

        // Repeated test for better failure messages.
        $this->assertEquals($expectedq->subquestions, $q->subquestions);
        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }

    public function test_import_multichoice() {
        $gift = "
// multiple choice with specified feedback for right and wrong answers
::Q2:: What's between orange and green in the spectrum?
{
    =yellow # right; good!
    ~red # [html]wrong, it's yellow
    ~[plain]blue # wrong, it's yellow
}";
        $lines = preg_split('/[\\n\\r]/', str_replace("\r\n", "\n", $gift));

        $importer = new qformat_giftmedia();
        $q = $importer->readquestion($lines);

        $expectedq = (object) array(
            'name' => 'Q2',
            'questiontext' => "What's between orange and green in the spectrum?",
            'questiontextformat' => FORMAT_HTML,
            'generalfeedback' => '',
            'generalfeedbackformat' => FORMAT_HTML,
            'qtype' => 'multichoice',
            'defaultmark' => 1,
            'penalty' => 0.3333333,
            'length' => 1,
            'single' => 1,
            'shuffleanswers' => '1',
            'answernumbering' => 'abc',
            'correctfeedback' => array(
                'text' => '',
                'format' => FORMAT_HTML,
                'files' => array(),
            ),
            'partiallycorrectfeedback' => array(
                'text' => '',
                'format' => FORMAT_HTML,
                'files' => array(),
            ),
            'incorrectfeedback' => array(
                'text' => '',
                'format' => FORMAT_HTML,
                'files' => array(),
            ),
            'answer' => array(
                0 => array(
                    'text' => 'yellow',
                    'format' => FORMAT_HTML,
                ),
                1 => array(
                    'text' => 'red',
                    'format' => FORMAT_HTML,
                ),
                2 => array(
                    'text' => 'blue',
                    'format' => FORMAT_HTML,
                ),
            ),
            'fraction' => array(1, 0, 0),
            'feedback' => array(
                0 => array(
                    'text' => 'right; good!',
                    'format' => FORMAT_HTML,
                ),
                1 => array(
                    'text' => "wrong, it's yellow",
                    'format' => FORMAT_HTML,
                ),
                2 => array(
                    'text' => "wrong, it's yellow",
                    'format' => FORMAT_HTML,
                ),
            ),
        );

        // Repeated test for better failure messages.
        $this->assertEquals($expectedq->answer, $q->answer);
        $this->assertEquals($expectedq->feedback, $q->feedback);
        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }

    public function test_import_multichoice_multi() {
        $gift = "
// multiple choice, multiple response with specified feedback for right and wrong answers
::colours:: What's between orange and green in the spectrum?
{
    ~%50%yellow # right; good!
    ~%-100%red # [html]wrong
    ~%50%off-beige # right; good!
    ~%-100%[plain]blue # wrong
}";
        $lines = preg_split('/[\\n\\r]/', str_replace("\r\n", "\n", $gift));

        $importer = new qformat_giftmedia();
        $q = $importer->readquestion($lines);

        $expectedq = (object) array(
            'name' => 'colours',
            'questiontext' => "What's between orange and green in the spectrum?",
            'questiontextformat' => FORMAT_HTML,
            'generalfeedback' => '',
            'generalfeedbackformat' => FORMAT_HTML,
            'qtype' => 'multichoice',
            'defaultmark' => 1,
            'penalty' => 0.3333333,
            'length' => 1,
            'single' => 0,
            'shuffleanswers' => '1',
            'answernumbering' => 'abc',
            'correctfeedback' => array(
                'text' => '',
                'format' => FORMAT_HTML,
                'files' => array(),
            ),
            'partiallycorrectfeedback' => array(
                'text' => '',
                'format' => FORMAT_HTML,
                'files' => array(),
            ),
            'incorrectfeedback' => array(
                'text' => '',
                'format' => FORMAT_HTML,
                'files' => array(),
            ),
            'answer' => array(
                0 => array(
                    'text' => 'yellow',
                    'format' => FORMAT_HTML,
                ),
                1 => array(
                    'text' => 'red',
                    'format' => FORMAT_HTML,
                ),
                2 => array(
                    'text' => 'off-beige',
                    'format' => FORMAT_HTML,
                ),
                3 => array(
                    'text' => 'blue',
                    'format' => FORMAT_HTML,
                ),
            ),
            'fraction' => array(0.5, -1, 0.5, -1),
            'feedback' => array(
                0 => array(
                    'text' => 'right; good!',
                    'format' => FORMAT_HTML,
                ),
                1 => array(
                    'text' => "wrong",
                    'format' => FORMAT_HTML,
                ),
                2 => array(
                    'text' => "right; good!",
                    'format' => FORMAT_HTML,
                ),
                3 => array(
                    'text' => "wrong",
                    'format' => FORMAT_HTML,
                ),
            ),
        );

        // Repeated test for better failure messages.
        $this->assertEquals($expectedq->answer, $q->answer);
        $this->assertEquals($expectedq->feedback, $q->feedback);
        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }

    public function test_import_multichoice_multi_tricky() {
        $gift = "
// multiple choice, multiple response with specified feedback for right and wrong answers
::colours:: What's between orange and green in the spectrum?
{
    ~%100%yellow # right; good!
    ~%-50%red # wrong
    ~%-50%blue # wrong
}";
        $lines = preg_split('/[\\n\\r]/', str_replace("\r\n", "\n", $gift));

        $importer = new qformat_giftmedia();
        $q = $importer->readquestion($lines);

        $expectedq = (object) array(
                'name' => 'colours',
                'questiontext' => "What's between orange and green in the spectrum?",
                'questiontextformat' => FORMAT_HTML,
                'generalfeedback' => '',
                'generalfeedbackformat' => FORMAT_HTML,
                'qtype' => 'multichoice',
                'defaultmark' => 1,
                'penalty' => 0.3333333,
                'length' => 1,
                'single' => 0,
                'shuffleanswers' => '1',
                'answernumbering' => 'abc',
                'correctfeedback' => array(
                        'text' => '',
                        'format' => FORMAT_HTML,
                        'files' => array(),
                ),
                'partiallycorrectfeedback' => array(
                        'text' => '',
                        'format' => FORMAT_HTML,
                        'files' => array(),
                ),
                'incorrectfeedback' => array(
                        'text' => '',
                        'format' => FORMAT_HTML,
                        'files' => array(),
                ),
                'answer' => array(
                        0 => array(
                                'text' => 'yellow',
                                'format' => FORMAT_HTML,
                        ),
                        1 => array(
                                'text' => 'red',
                                'format' => FORMAT_HTML,
                        ),
                        2 => array(
                                'text' => 'blue',
                                'format' => FORMAT_HTML,
                        ),
                ),
                'fraction' => array(1, -0.5, -0.5),
                'feedback' => array(
                        0 => array(
                                'text' => 'right; good!',
                                'format' => FORMAT_HTML,
                        ),
                        1 => array(
                                'text' => "wrong",
                                'format' => FORMAT_HTML,
                        ),
                        2 => array(
                                'text' => "wrong",
                                'format' => FORMAT_HTML,
                        ),
                ),
        );

        // Repeated test for better failure messages.
        $this->assertEquals($expectedq->answer, $q->answer);
        $this->assertEquals($expectedq->feedback, $q->feedback);
        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }

    public function test_import_numerical() {
        $gift = "
// math range question
::Q5:: What is a number from 1 to 5? {#3:2~#Completely wrong}";
        $lines = preg_split('/[\\n\\r]/', str_replace("\r\n", "\n", $gift));

        $importer = new qformat_giftmedia();
        $q = $importer->readquestion($lines);

        $expectedq = (object) array(
            'name' => 'Q5',
            'questiontext' => "What is a number from 1 to 5?",
            'questiontextformat' => FORMAT_HTML,
            'generalfeedback' => '',
            'generalfeedbackformat' => FORMAT_HTML,
            'qtype' => 'numerical',
            'defaultmark' => 1,
            'penalty' => 0.3333333,
            'length' => 1,
            'answer' => array(
                '3',
                '*',
            ),
            'fraction' => array(1, 0),
            'feedback' => array(
                0 => array(
                    'text' => '',
                    'format' => FORMAT_HTML,
                    'files' => array(),
                ),
                1 => array(
                    'text' => "Completely wrong",
                    'format' => FORMAT_HTML,
                ),
            ),
            'tolerance' => array(2, 0),
        );

        // Repeated test for better failure messages.
        $this->assertEquals($expectedq->answer, $q->answer);
        $this->assertEquals($expectedq->fraction, $q->fraction);
        $this->assertEquals($expectedq->feedback, $q->feedback);
        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }

    public function test_import_shortanswer() {
        $gift = "
// question: 666  name: Shortanswer
::Shortanswer::Which is the best animal?{
    =Frog#Good!
    =%50%Cat#What is it with Moodlers and cats?
    =%0%*#Completely wrong
}";
        $lines = preg_split('/[\\n\\r]/', str_replace("\r\n", "\n", $gift));

        $importer = new qformat_giftmedia();
        $q = $importer->readquestion($lines);

        $expectedq = (object) array(
            'name' => 'Shortanswer',
            'questiontext' => "Which is the best animal?",
            'questiontextformat' => FORMAT_HTML,
            'generalfeedback' => '',
            'generalfeedbackformat' => FORMAT_HTML,
            'qtype' => 'shortanswer',
            'defaultmark' => 1,
            'penalty' => 0.3333333,
            'length' => 1,
            'answer' => array(
                'Frog',
                'Cat',
                '*',
            ),
            'fraction' => array(1, 0.5, 0),
            'feedback' => array(
                0 => array(
                    'text' => 'Good!',
                    'format' => FORMAT_HTML,
                ),
                1 => array(
                    'text' => "What is it with Moodlers and cats?",
                    'format' => FORMAT_HTML,
                ),
                2 => array(
                    'text' => "Completely wrong",
                    'format' => FORMAT_HTML,
                ),
            ),
        );

        // Repeated test for better failure messages.
        $this->assertEquals($expectedq->answer, $q->answer);
        $this->assertEquals($expectedq->fraction, $q->fraction);
        $this->assertEquals($expectedq->feedback, $q->feedback);
        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }

    public function test_import_shortanswer_with_general_feedback() {
        $gift = "
// question: 666  name: Shortanswer
::Shortanswer::Which is the best animal?{
    =Frog#Good!
    =%50%Cat#What is it with Moodlers and cats?
    =%0%*#Completely wrong
    ####[html]Here is some general feedback!
}";
        $lines = preg_split('/[\\n\\r]/', str_replace("\r\n", "\n", $gift));

        $importer = new qformat_giftmedia();
        $q = $importer->readquestion($lines);

        $expectedq = (object) array(
            'name' => 'Shortanswer',
            'questiontext' => "Which is the best animal?",
            'questiontextformat' => FORMAT_HTML,
            'generalfeedback' => 'Here is some general feedback!',
            'generalfeedbackformat' => FORMAT_HTML,
            'qtype' => 'shortanswer',
            'defaultmark' => 1,
            'penalty' => 0.3333333,
            'length' => 1,
            'answer' => array(
                'Frog',
                'Cat',
                '*',
            ),
            'fraction' => array(1, 0.5, 0),
            'feedback' => array(
                0 => array(
                    'text' => 'Good!',
                    'format' => FORMAT_HTML,

                ),
                1 => array(
                    'text' => "What is it with Moodlers and cats?",
                    'format' => FORMAT_HTML,

                ),
                2 => array(
                    'text' => "Completely wrong",
                    'format' => FORMAT_HTML,

                ),
            ),
        );

        // Repeated test for better failure messages.
        $this->assertEquals($expectedq->answer, $q->answer);
        $this->assertEquals($expectedq->fraction, $q->fraction);
        $this->assertEquals($expectedq->feedback, $q->feedback);
        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }

    public function test_import_truefalse() {
        $gift = "
// true/false
::Q1:: 42 is the Absolute Answer to everything.{
FALSE#42 is the Ultimate Answer.#You gave the right answer.}";
        $lines = preg_split('/[\\n\\r]/', str_replace("\r\n", "\n", $gift));

        $importer = new qformat_giftmedia();
        $q = $importer->readquestion($lines);

        $expectedq = (object) array(
            'name' => 'Q1',
            'questiontext' => "42 is the Absolute Answer to everything.",
            'questiontextformat' => FORMAT_HTML,
            'generalfeedback' => '',
            'generalfeedbackformat' => FORMAT_HTML,
            'qtype' => 'truefalse',
            'defaultmark' => 1,
            'penalty' => 1,
            'length' => 1,
            'correctanswer' => 0,
            'feedbacktrue' => array(
                'text' => '42 is the Ultimate Answer.',
                'format' => FORMAT_HTML,
            ),
            'feedbackfalse' => array(
                'text' => 'You gave the right answer.',
                'format' => FORMAT_HTML,
            ),
        );

        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }

    public function test_import_truefalse_true_answer1() {
        $gift = "// name 0-11
::2-08 TSL::TSL is blablabla.{T}";
        $lines = preg_split('/[\\n\\r]/', str_replace("\r\n", "\n", $gift));

        $importer = new qformat_giftmedia();
        $q = $importer->readquestion($lines);

        $expectedq = (object) array(
            'name' => '2-08 TSL',
            'questiontext' => "TSL is blablabla.",
            'questiontextformat' => FORMAT_HTML,
            'generalfeedback' => '',
            'generalfeedbackformat' => FORMAT_HTML,
            'qtype' => 'truefalse',
            'defaultmark' => 1,
            'penalty' => 1,
            'length' => 1,
            'correctanswer' => 1,
            'feedbacktrue' => array(
                'text' => '',
                'format' => FORMAT_HTML,
                'files' => array(),
            ),
            'feedbackfalse' => array(
                'text' => '',
                'format' => FORMAT_HTML,
                'files' => array(),

            ),
        );

        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }

    public function test_import_truefalse_true_answer2() {
        $gift = "// name 0-11
::2-08 TSL::TSL is blablabla.{TRUE}";
        $lines = preg_split('/[\\n\\r]/', str_replace("\r\n", "\n", $gift));

        $importer = new qformat_giftmedia();
        $q = $importer->readquestion($lines);

        $expectedq = (object) array(
            'name' => '2-08 TSL',
            'questiontext' => "TSL is blablabla.",
            'questiontextformat' => FORMAT_HTML,
            'generalfeedback' => '',
            'generalfeedbackformat' => FORMAT_HTML,
            'qtype' => 'truefalse',
            'defaultmark' => 1,
            'penalty' => 1,
            'length' => 1,
            'correctanswer' => 1,
            'feedbacktrue' => array(
                'text' => '',
                'format' => FORMAT_HTML,
                'files' => array(),
            ),
            'feedbackfalse' => array(
                'text' => '',
                'format' => FORMAT_HTML,
                'files' => array(),
            ),
        );

        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }

    public function test_import_backslash() {
        // There was a bug (MDL-34171) where \\ in the import was getting changed
        // to \. This test checks for that.
        // We need \\\\ in the test code, because of PHPs string escaping rules.
        $gift = '
// essay
::double backslash:: A \\\\ B \\\\\\\\ C{}';
        $lines = preg_split('/[\\n\\r]/', str_replace("\r\n", "\n", $gift));

        $importer = new qformat_giftmedia();
        $q = $importer->readquestion($lines);

        $expectedq = (object) array(
            'name' => 'double backslash',
            'questiontext' => 'A \\ B \\\\ C',
            'questiontextformat' => FORMAT_HTML,
            'generalfeedback' => '',
            'generalfeedbackformat' => FORMAT_HTML,
            'qtype' => 'essay',
            'defaultmark' => 1,
            'penalty' => 0.3333333,
            'length' => 1,
            'responseformat' => 'editor',
            'responsefieldlines' => 15,
            'attachments' => 0,
            'graderinfo' => array(
                'text' => '',
                'format' => FORMAT_HTML,
            ),
        );

        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }

    public function test_import_pre_content() {
        $gift = '
::Q001::[html]<p>What would running the test method print?</p>
<pre>
    public void test() \{
        method1();
        method2();
        method3();
    \}
</pre>
{}';
        $lines = preg_split('/[\\n\\r]/', str_replace("\r\n", "\n", $gift));

        $importer = new qformat_giftmedia();
        $q = $importer->readquestion($lines);

        $expectedq = (object) array(
            'name' => 'Q001',
            'questiontext' => '<p>What would running the test method print?</p>
<pre>
    public void test() {
        method1();
        method2();
        method3();
    }
</pre>',
            'questiontextformat' => FORMAT_HTML,
            'generalfeedback' => '',
            'generalfeedbackformat' => FORMAT_HTML,
            'qtype' => 'essay',
            'defaultmark' => 1,
            'penalty' => 0.3333333,
            'length' => 1,
            'responseformat' => 'editor',
            'responsefieldlines' => 15,
            'attachments' => 0,
            'graderinfo' => array(
                'text' => '',
                'format' => FORMAT_HTML,
            ),
        );

        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }
}
