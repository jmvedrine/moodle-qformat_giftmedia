WARNING :
I am now retired and I stopped all Moodle related activities.
This repository is here just for history and this work is not maintained any more.
Feel free to fork it and modify it to suit your needs or improve compatibility with recent Moodle versions.
Additionaly you can consider contacting the Moodle team and become the new maintainer of this plugin. Thanks

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

## Example of media files inclusion

### Example 1

For instance if a mymedia.mp3 file is in the myfolder/mysubfolder/  subfolder, the correct reference in the gift file is
```
@@PLUGINFILE@@/myfolder/mysubfolder/mymedia.mp3
```

### Example 2

If you want to include an image named logo.jpg into your question , you can write:
```
<img src\="@@PLUGINFILE@@/logo.jpg" alt\="logo" />
```
and put the logo.jpg file into your zip file


### Example 3

If in your question you have the following html fragment to display an image
```
<img src\="images/flower.png" alt\="a flower" />
```
You should edit it to
```
<img src\="@@PLUGINFILE@@/images/flower.png" alt\="a flower" />
```
An put the flower.png file into a folder named images inside your zip file


## KNOWN LIMITATIONS

Currently the text file containing the questions must be at the root level of the zip archive,
and it must have a .txt extension to be correctly found and parsed.

## NOTE
Don't forget the GIFT syntax ! the `=` character is a special character for GIFT so each `=` in your HTML 
has to be written as `\=` (see examples 2 and 3 above). I made this mistake numerous time while writting 
GIFT with medias questions, so you have been warned.
