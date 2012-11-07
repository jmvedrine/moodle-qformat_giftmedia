moodle-qformat_giftmedia
========================

Moodle import format similar to gift but for a zip file with media files

The zip file should have the following structure :
- a text file with all questions in gift form at the root level with a .txt extension
- one or more folders with the media files, each folder can have subfolders levels as long as
the complete path is specified in file's references in the gift questions text
For instance if a mymedia.mp3 file is in the myfolder/mysubfolder/  subfolder, the correct reference in the gift file is
@@PLUGINFILE@@/myfolder/mysubfolder/mymedia.mp3


Currently your zip file must respect some constraints to be correctly parsed:
- the text file containing the questions must be at the root level and must have a .txt extension

WARNING : This format depends on changes that I have made to question/format.php in MDL-36243 tracker issue, 
so until these changes have been integrated in Moodle, media import will not work unless you apply these changes
yourself to your Moodle server.

