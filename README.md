moodle-qformat_giftmedia
========================

Moodle import format similar to gift, but for a zip file with media files

This plugin allow import of questions with images using the GIFT format syntax.

Written by Jean-Michel Védrine

To install using git, type this command in the root of your Moodle install
    git clone git://github.com//jmvedrine/moodle-qformat_giftmedia.git question/format/giftmedia
Another way to install is to download the zip file, unzip it, and place it in the directory
moodle/question/format/. (You will need to rename the directory moodle-qformat_giftmedia-master to giftmedia).

WARNING : This version of the report is compatible with Moodle 2.5 or later.
There are differents version of this plugin available for older Moodle versions.
Be sure to install the right version for your Moodle version.

The zip file used for import should have the following structure :
- a text file with all questions in gift form at the root level of the zip archive with a .txt extension
- one or more folders with the media files, each folder can have any subfolders levels as long as
the complete path is specified in file's references in the gift questions text
For instance if a mymedia.mp3 file is in the myfolder/mysubfolder/  subfolder, the correct reference in the gift file is
@@PLUGINFILE@@/myfolder/mysubfolder/mymedia.mp3

KNOWN LIMITATIONS
Currently the text file containing the questions must be at the root level of the zip archive,
and it must have a .txt extension to be correctly found and parsed.
