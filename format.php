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
 * GIFT with media format question importer.
 *
 * @package    qformat_giftmedia
 * @copyright  2013 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * The GIFT import filter was designed as an easy to use method
 * for teachers writing questions as a text file. It supports most
 * question types and the missing word format.
 *
 * Multiple Choice / Missing Word
 *     Who's buried in Grant's tomb?{~Grant ~Jefferson =no one}
 *     Grant is {~buried =entombed ~living} in Grant's tomb.
 * True-False:
 *     Grant is buried in Grant's tomb.{FALSE}
 * Short-Answer.
 *     Who's buried in Grant's tomb?{=no one =nobody}
 * Numerical
 *     When was Ulysses S. Grant born?{#1822:5}
 * Matching
 *     Match the following countries with their corresponding
 *     capitals.{=Canada->Ottawa =Italy->Rome =Japan->Tokyo}
 *
 * Comment lines start with a double backslash (//).
 * Optional question names are enclosed in double colon(::).
 * Answer feedback is indicated with hash mark (#).
 * Percentage answer weights immediately follow the tilde (for
 * multiple choice) or equal sign (for short answer and numerical),
 * and are enclosed in percent signs (% %). See docs and examples.txt for more.
 *
 * This filter was written through the collaboration of numerous
 * members of the Moodle community. It was originally based on
 * the missingword format, which included code from Thomas Robb
 * and others. Paul Tsuchido Shew wrote this filter in December 2003.
 *
 * @copyright  2003 Paul Tsuchido Shew
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qformat_giftmedia extends qformat_gift {
    /** @var string path to the temporary directory. */
    public $tempdir = '';

    /**
     * This plugin provide import
     * @return bool true
     */
    public function provide_import() {
        return true;
    }

    /**
     * This plugin doesn't provide import
     * @return bool false
     */
    public function provide_export() {
        return false;
    }

    /**
     * This plugin takes a zip archive
     * @return string mime-type of the files that this plugin reads
     */
    public function mime_type() {
        return mimeinfo('type', '.zip');
    }

    /**
     * Does any post-processing that may be desired
     * Clean the temporary directory if a zip file was imported
     * @return bool success
     */
    public function importpostprocess() {
        if ($this->tempdir != '') {
            fulldelete($this->tempdir);
        }
        return true;
    }

    /**
     * Store an image file in a draft filearea
     * @param array $text, if itemid element doesn't exist it will be created
     * @param string $tempdir path to root of image tree
     * @param string $filepathinsidetempdir path to image in the tree
     * @param string $filename image's name
     * @return string new name of the image as it was stored
     */
    protected function store_file_for_text_field(&$text, $tempdir, $filepathinsidetempdir, $filename) {
        global $USER;
        $fs = get_file_storage();
        if (empty($text['itemid'])) {
            $text['itemid'] = file_get_unused_draft_itemid();
        }
        // As question file areas don't support subdirs,
        // convert path to filename.
        // So that medias with same name can be imported.
        if ($filepathinsidetempdir == '.') {
            $newfilename = clean_param($filename, PARAM_FILE);
        } else {
            $newfilename = clean_param(str_replace('/', '__', $filepathinsidetempdir . '__' . $filename), PARAM_FILE);
        }
        $filerecord = array(
            'contextid' => context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $text['itemid'],
            'filepath'  => '/',
            'filename'  => $newfilename,
        );
        if ($filepathinsidetempdir == '.') {
            $fs->create_file_from_pathname($filerecord, $tempdir . '/' . $filename);
        } else {
            $fs->create_file_from_pathname($filerecord, $tempdir . '/' . $filepathinsidetempdir . '/' . $filename);
        }
        return $newfilename;
    }

    /**
     * Given an HTML text with references to media files,
     * store all medias in a draft filearea,
     * and return an array with all urls in text recoded,
     * format set to FORMAT_HTML, and itemid set to filearea itemid
     * @param string $text text to parse and recode
     * @return array with keys text, format, itemid.
     */
    public function text_field($text) {
        $data = array();

        preg_match_all('|"@@PLUGINFILE@@/([^"]*)"|i', $text, $out); // Find all pluginfile refs.
        $filepaths = array();
        foreach ($out[1] as $path) {
            $fullpath = $this->tempdir . '/' . $path;
            if (is_readable($fullpath) && !in_array($path, $filepaths)) {
                $dirpath = dirname($path);
                $filename = basename($path);
                $newfilename = $this->store_file_for_text_field($data, $this->tempdir, $dirpath, $filename);
                $text = preg_replace("|@@PLUGINFILE@@/$path|", "@@PLUGINFILE@@/" . $newfilename, $text);
                $filepaths[] = $path;
            }
        }
        $data['text'] = $text;
        $data['format'] = FORMAT_HTML;
        return $data;
    }

    /**
     * The file extension (including .) that is normally used by this plugin
     * @return string extension
     */
    public function export_file_extension() {
        return '.zip';
    }


    /**
     * Parse the text
     * @param string $text the text to parse
     * @param integer $defaultformat text format
     * @return array with keys text, format, itemid.
     *
     */
    protected function parse_text_with_format($text, $defaultformat = FORMAT_MOODLE) {
        // Parameter defaultformat is ignored we set format to be html in all cases.

        if (strpos($text, '[') === 0) {
            $formatend = strpos($text, ']');
            if ($formatend) {
                $text = substr($text, $formatend + 1);
            }
        }
        return $this->text_field(trim($this->escapedchar_post($text)));
    }

    /**
     * Return content of all files containing questions,
     * as an array one element for each file found,
     * For each file, the corresponding element is an array of lines.
     * @param string $filename name of file
     * @return mixed contents array or false on failure
     */
    public function readdata($filename) {
        $uniquecode = time();
        $this->tempdir = make_temp_directory('giftmedia/' . $uniquecode);
        if (is_readable($filename)) {
            if (!copy($filename, $this->tempdir . '/gift.zip')) {
                $this->error(get_string('cannotcopybackup', 'question'));
                fulldelete($this->tempdir);
                return false;
            }
            $packer = get_file_packer('application/zip');
            if ($packer->extract_to_pathname($this->tempdir . '/gift.zip', $this->tempdir)) {
                // Search for a text file in the zip archive.
                // TODO ? search it, even if it is not a root level ?
                $filenames = array();
                $iterator = new DirectoryIterator($this->tempdir);
                foreach ($iterator as $fileinfo) {
                    if ($fileinfo->isFile() && strtolower(pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION)) == 'txt') {
                        $filenames[] = $fileinfo->getFilename();
                    }
                }
                if ($filenames) {
                    return file($this->tempdir . '/' . $filenames[0]);
                } else {
                    $this->error(get_string('nogiftfile', 'giftmedia'));
                    fulldelete($this->temp_dir);
                }
            } else {
                $this->error(get_string('cannotunzip', 'question'));
                fulldelete($this->temp_dir);
            }
        } else {
            $this->error(get_string('cannotreaduploadfile', 'error'));
            fulldelete($this->tempdir);
        }
        return false;
    }

    public function readquestion($lines) {
        // Given an array of lines known to define a question in this format, this function
        // converts it into a question object suitable for processing and insertion into Moodle.

        $question = $this->defaultquestion();
        $comment = null;
        // Define replaced by simple assignment, stop redefine notices.
        $giftanswerweightregex = '/^%\-*([0-9]{1,2})\.?([0-9]*)%/';

        // Remove commented lines and implode.
        foreach ($lines as $key => $line) {
            $line = trim($line);
            if (substr($line, 0, 2) == '//') {
                $lines[$key] = ' ';
            }
        }

        $text = trim(implode("\n", $lines));

        if ($text == '') {
            return false;
        }

        // Substitute escaped control characters with placeholders.
        $text = $this->escapedchar_pre($text);

        // Look for category modifier.
        if (preg_match('~^\$CATEGORY:~', $text)) {
            $newcategory = trim(substr($text, 10));

            // Build fake question to contain category.
            $question->qtype = 'category';
            $question->category = $newcategory;
            return $question;
        }

        // Question name parser.
        if (substr($text, 0, 2) == '::') {
            $text = substr($text, 2);

            $namefinish = strpos($text, '::');
            if ($namefinish === false) {
                $question->name = false;
                // Name will be assigned after processing question text below.
            } else {
                $questionname = substr($text, 0, $namefinish);
                $question->name = $this->clean_question_name($this->escapedchar_post($questionname));
                $text = trim(substr($text, $namefinish + 2)); // Remove name from text.
            }
        } else {
            $question->name = false;
        }

        // Find the answer section.
        $answerstart = strpos($text, '{');
        $answerfinish = strpos($text, '}');

        $description = false;
        if ($answerstart === false && $answerfinish === false) {
            // No answer means it's a description.
            $description = true;
            $answertext = '';
            $answerlength = 0;

        } else if ($answerstart === false || $answerfinish === false) {
            $this->error(get_string('braceerror', 'qformat_gift'), $text);
            return false;

        } else {
            $answerlength = $answerfinish - $answerstart;
            $answertext = trim(substr($text, $answerstart + 1, $answerlength - 1));
        }

        // Format the question text, without answer, inserting "_____" as necessary.
        if ($description) {
            $questiontext = $text;
        } else if (substr($text, -1) == "}") {
            // No blank line if answers follow question, outside of closing punctuation.
            $questiontext = substr_replace($text, "", $answerstart, $answerlength + 1);
        } else {
            // Inserts blank line for missing word format.
            $questiontext = substr_replace($text, "_____", $answerstart, $answerlength + 1);
        }

        // Look to see if there is any general feedback.
        $gfseparator = strrpos($answertext, '####');
        if ($gfseparator === false) {
            $generalfeedback = '';
        } else {
            $generalfeedback = substr($answertext, $gfseparator + 4);
            $answertext = trim(substr($answertext, 0, $gfseparator));
        }

        // Get questiontext format from questiontext.
        $text = $this->parse_text_with_format($questiontext);
        $question->questiontextformat = $text['format'];
        $question->questiontext = $text['text'];
        if (!empty($text['itemid'])) {
            $question->questiontextitemid = $text['itemid'];
        }

        // Get generalfeedback format from questiontext.
        $text = $this->parse_text_with_format($generalfeedback, $question->questiontextformat);
        $question->generalfeedback = $text['text'];
        $question->generalfeedbackformat = $text['format'];
        if (!empty($text['itemid'])) {
            $question->generalfeedbackitemid = $text['itemid'];
        }

        // Set question name if not already set.
        if ($question->name === false) {
            $question->name = $this->create_default_question_name($question->questiontext, get_string('questionname', 'question'));
        }

        // Determine question type.
        $question->qtype = null;

        // Give plugins first try.
        // Plugins must promise not to intercept standard qtypes
        // MDL-12346, this could be called from lesson mod which has its own base class =(.
        if (method_exists($this, 'try_importing_using_qtypes')
                && ($tryquestion = $this->try_importing_using_qtypes($lines, $question, $answertext))) {
            return $tryquestion;
        }

        if ($description) {
            $question->qtype = 'description';

        } else if ($answertext == '') {
            $question->qtype = 'essay';

        } else if ($answertext{0} == '#') {
            $question->qtype = 'numerical';

        } else if (strpos($answertext, '~') !== false) {
            // Only Multiplechoice questions contain tilde ~.
            $question->qtype = 'multichoice';

        } else if (strpos($answertext, '=') !== false
                && strpos($answertext, '->') !== false) {
            // Only Matching contains both = and ->.
            $question->qtype = 'match';

        } else { // Either truefalse or shortanswer.

            // Truefalse question check.
            $truefalsecheck = $answertext;
            if (strpos($answertext, '#') > 0) {
                // Strip comments to check for TrueFalse question.
                $truefalsecheck = trim(substr($answertext, 0, strpos($answertext, "#")));
            }

            $validtfanswers = array('T', 'TRUE', 'F', 'FALSE');
            if (in_array($truefalsecheck, $validtfanswers)) {
                $question->qtype = 'truefalse';

            } else { // Must be shortanswer.
                $question->qtype = 'shortanswer';
            }
        }

        if (!isset($question->qtype)) {
            $giftqtypenotset = get_string('giftqtypenotset', 'qformat_gift');
            $this->error($giftqtypenotset, $text);
            return false;
        }

        switch ($question->qtype) {
            case 'description':
                $question->defaultmark = 0;
                $question->length = 0;
                return $question;

            case 'essay':
                $question->responseformat = 'editor';
                $question->responserequired = 1;
                $question->responsefieldlines = 15;
                $question->attachments = 0;
                $question->attachmentsrequired = 0;
                $question->graderinfo = array(
                        'text' => '', 'format' => FORMAT_HTML);
                $question->responsetemplate = array(
                        'text' => '', 'format' => FORMAT_HTML);
                return $question;

            case 'multichoice':
                if (strpos($answertext, "=") === false) {
                    $question->single = 0; // Multiple answers are enabled if no single answer is 100% correct.
                } else {
                    $question->single = 1; // Only one answer allowed (the default).
                }
                $question = $this->add_blank_combined_feedback($question);

                $answertext = str_replace("=", "~=", $answertext);
                $answers = explode("~", $answertext);
                if (isset($answers[0])) {
                    $answers[0] = trim($answers[0]);
                }
                if (empty($answers[0])) {
                    array_shift($answers);
                }

                $countanswers = count($answers);

                if (!$this->check_answer_count(2, $answers, $text)) {
                    return false;
                }

                foreach ($answers as $key => $answer) {
                    $answer = trim($answer);

                    // Determine answer weight.
                    if ($answer[0] == '=') {
                        $answerweight = 1;
                        $answer = substr($answer, 1);

                    } else if (preg_match($giftanswerweightregex, $answer)) {    // Check for properly formatted answer weight.
                        $answerweight = $this->answerweightparser($answer);

                    } else {     // Default, i.e., wrong anwer.
                        $answerweight = 0;
                    }
                    list($question->answer[$key], $question->feedback[$key])
                            = $this->commentparser($answer, $question->questiontextformat);
                    $question->fraction[$key] = $answerweight;
                }  // End foreach answer.

                return $question;

            case 'match':
                $question = $this->add_blank_combined_feedback($question);

                $answers = explode('=', $answertext);
                if (isset($answers[0])) {
                    $answers[0] = trim($answers[0]);
                }
                if (empty($answers[0])) {
                    array_shift($answers);
                }

                if (!$this->check_answer_count(2, $answers, $text)) {
                    return false;
                }

                foreach ($answers as $key => $answer) {
                    $answer = trim($answer);
                    if (strpos($answer, "->") === false) {
                        $this->error(get_string('giftmatchingformat', 'qformat_gift'), $answer);
                        return false;
                    }

                    $marker = strpos($answer, '->');
                    $question->subquestions[$key] = $this->parse_text_with_format(
                            substr($answer, 0, $marker), $question->questiontextformat);
                    $question->subanswers[$key] = trim($this->escapedchar_post(
                            substr($answer, $marker + 2)));
                }

                return $question;

            case 'truefalse':
                list($answer, $wrongfeedback, $rightfeedback)
                        = $this->split_truefalse_comment($answertext, $question->questiontextformat);

                if ($answer['text'] == "T" || $answer['text'] == "TRUE") {
                    $question->correctanswer = 1;
                    $question->feedbacktrue = $rightfeedback;
                    $question->feedbackfalse = $wrongfeedback;
                } else {
                    $question->correctanswer = 0;
                    $question->feedbacktrue = $wrongfeedback;
                    $question->feedbackfalse = $rightfeedback;
                }

                $question->penalty = 1;

                return $question;

            case 'shortanswer':
                // Shortanswer question.
                $answers = explode("=", $answertext);
                if (isset($answers[0])) {
                    $answers[0] = trim($answers[0]);
                }
                if (empty($answers[0])) {
                    array_shift($answers);
                }

                if (!$this->check_answer_count(1, $answers, $text)) {
                    return false;
                }

                foreach ($answers as $key => $answer) {
                    $answer = trim($answer);

                    // Answer weight.
                    if (preg_match($giftanswerweightregex, $answer)) {    // Check for properly formatted answer weight.
                        $answerweight = $this->answerweightparser($answer);
                    } else {     // Default, i.e., full-credit anwer.
                        $answerweight = 1;
                    }

                    list($answer, $question->feedback[$key]) = $this->commentparser(
                            $answer, $question->questiontextformat);

                    $question->answer[$key] = $answer['text'];
                    $question->fraction[$key] = $answerweight;
                }

                return $question;

            case 'numerical':
                // Note similarities to ShortAnswer.
                $answertext = substr($answertext, 1); // Remove leading "#".

                // If there is feedback for a wrong answer, store it for now.
                if (($pos = strpos($answertext, '~')) !== false) {
                    $wrongfeedback = substr($answertext, $pos);
                    $answertext = substr($answertext, 0, $pos);
                } else {
                    $wrongfeedback = '';
                }

                $answers = explode("=", $answertext);
                if (isset($answers[0])) {
                    $answers[0] = trim($answers[0]);
                }
                if (empty($answers[0])) {
                    array_shift($answers);
                }

                if (count($answers) == 0) {
                    // Invalid question.
                    $giftnonumericalanswers = get_string('giftnonumericalanswers', 'qformat_gift');
                    $this->error($giftnonumericalanswers, $text);
                    return false;
                }

                foreach ($answers as $key => $answer) {
                    $answer = trim($answer);

                    // Answer weight.
                    if (preg_match($giftanswerweightregex, $answer)) {    // Check for properly formatted answer weight.
                        $answerweight = $this->answerweightparser($answer);
                    } else {     // Default, i.e., full-credit anwer.
                        $answerweight = 1;
                    }

                    list($answer, $question->feedback[$key]) = $this->commentparser(
                            $answer, $question->questiontextformat);
                    $question->fraction[$key] = $answerweight;
                    $answer = $answer['text'];

                    // Calculate Answer and Min/Max values.
                    if (strpos($answer, "..") > 0) { // Optional [min]..[max] format.
                        $marker = strpos($answer, "..");
                        $max = trim(substr($answer, $marker + 2));
                        $min = trim(substr($answer, 0, $marker));
                        $ans = ($max + $min) / 2;
                        $tol = $max - $ans;
                    } else if (strpos($answer, ':') > 0) { // Standard [answer]:[errormargin] format.
                        $marker = strpos($answer, ':');
                        $tol = trim(substr($answer, $marker + 1));
                        $ans = trim(substr($answer, 0, $marker));
                    } else { // Only one valid answer (zero errormargin).
                        $tol = 0;
                        $ans = trim($answer);
                    }

                    if (!(is_numeric($ans) || $ans = '*') || !is_numeric($tol)) {
                            $errornotnumbers = get_string('errornotnumbers');
                            $this->error($errornotnumbers, $text);
                        return false;
                    }

                    // Store results.
                    $question->answer[$key] = $ans;
                    $question->tolerance[$key] = $tol;
                }

                if ($wrongfeedback) {
                    $key += 1;
                    $question->fraction[$key] = 0;
                    list($notused, $question->feedback[$key]) = $this->commentparser(
                            $wrongfeedback, $question->questiontextformat);
                    $question->answer[$key] = '*';
                    $question->tolerance[$key] = '';
                }

                return $question;

            default:
                $this->error(get_string('giftnovalidquestion', 'qformat_gift'), $text);
                return false;

        }
    }
}
