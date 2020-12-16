# DFG Viewer OCR-on-Demand


## Installation

1. Install DFG-Viewer und Kitodo.Presentation, [ Guide ](https://github.com/UB-Mannheim/kitodo-presentation/wiki)
2. Install tesseract for command line [Tesseract-Wiki](https://tesseract-ocr.github.io/tessdoc/)
3. Install language data, for example [UB-Mannheim Fraktur-test](https://ub-backup.bib.uni-mannheim.de/~stweil/ocrd-train/data/Fraktur_5000000/tessdata_fast/Fraktur-fast.traineddata) or [tessdata](https://github.com/tesseract-ocr/tessdata)
    - It has to be moved to the tessdata folder. By default it must be in /usr/share/
    - Tessdata folder can also be set by TESSDATA_PREFIX environment variable 
    - More info and language data: <https://tesseract-ocr.github.io/tessdoc/Data-Files.html>
4. Set configuration variables in dlf extension ext_conf_template.txt or direct in TYPO3 Admin panel (Settings -> Extension settings -> dlf -> Fulltext OCR) 
    There are some advises for setting those variables:
    - fulltextFolder, fulltextTempFolder, fulltextImagesFolder: need to be located somewhere in fileadmin folder, so that user had access to read them 
    - ocrDummy : advised to be set to 'true' because there is no another way of saving the information that text's OCR is in progress (of course when user notification and muötithreading features are not enabled. In case they are, this variable can be set to false)
    - ocrEngine : in our example it is 'tesseract' 
    - ocrLanguages : list of language data, that must be used for OCR, separated by spaces
    - ocrOptions: in case of DFG-Viewer must be at least 'alto' 

Warning: in some versions of tesseract there is no support for ALTO format. During development was used a compiled version, which sources are located in Tesseract's GitHub master branch. This version does support ALTO format
  - Delay: is used for delaying the tesseract execution, for example when OCR for book is in progress. It is needed because Tesseract doesn't fully support multithreading (it supports maximal 2 threads and if running from console, running tesseract more than twice causes freezing of all tesseract processes. This is a [known issue](#issues), that has to be fixed) 
  Not only 'Delay' is hardward-dependent, but also it depends on size of input images, so it's better to test it on sme examples and find the lowest bound. Tested on a decent computer, 6 seemed to be a reasonable value. 


## Implementation details

Most implementation was done in dlf extenstion. 

### FulltextGenerator.php
New class for generating OCR. Functions `createPageFulltext` and `createBookFulltext` are used to perform OCR on one page and on whole book accordingly
It has also functions to check, whether local fulltext exists and to check whether it is still in work. 
It has several function like `getDocLocalPath` which manage paths and ways of identifying document's fulltext in filesystem. It is used for generating OCR and later retrieving of fulltext path

### PageView.php 
PageView is used to generate response for page displaying. It also includes some information like URL of page images and fulltext paths. There is `getFulltext` function, which returns path to fulltext. This function was changed in order to check for local fulltext (`FulltextGenerator::checkLocal` function) and in this case sets fulltext path to value from `FulltextGenerator::getPageLocalPath` 

### PageView.js
Some lines were added in `dlfViewer.prototype.addCustomControls` to remove fulltext control when fulltext is not present and to remove OCR creation controls otherwise

Next changes were done in 'dfgviewer' extension

### PageView.tmpl
PageView template was updated in order to show buttons for ocr creation

## Feature branches:
### FulltextGenerator as a tool
**Branch:** `fulltextgenerator_as_tool` for dlf and dfgviewer
**Description:** As Sebastian Meyer has said on 3 Milestone meeting, it is actually very useful to have OCR controls included as _Tool_ in Kitodo. In this branch a tool FulltextGeneratorTool is added, so that displaying of OCR controls is controlled from backend.
**What can be improved:** This feature is not completely done, because request for OCR is still handled in PageView module, which should be changed.

### Notification feature
**Branch:** `notification_feature` for dlf and `wip_notification_feature` and dfgviewer
**Description:** This feature changes frontend behaviour, adds a modal window with information, that OCR is right now in progress. With that feature, user doesn't need to reload the page in order to see generated text, but it will be displayed automatically as soon as it is ready (Well, user still has to press the "Show Fulltext" button)
**What can be improved:** This feature is changing some of the frontend modules and so has to be reviewed, in order to not to damage the existing modules 

## Issues:
1. **Issue:** There are several problems with parallelization due to that project uses parallelity only by using shell execution.

Parallel execution of several (typically > 2) tesseract processes could cause freeze of all processes on some machines. It is critical in two scenaries: 1. when several users request ocr for different pages 2. When OCR for a book is called. (To notice, if two users request OCR for same book, it will be created only once, because a dummy file will be created for each page and FulltextGenerator won't create OCR for pages with corresponding dummy files. That is another point, why dummy files are important in current implementation)

Furthermore, the backend downloads images needed for OCR using `wget`
**Possible Solution:** There are several solutions for this problem: 
    1. If there is no problem with adding more dependncies to project, you can update php in order to support parallelity and use some packages, that allow to create more threads. Then you can either start OCR as it was, from shell, but in separate threads, count running threads and use something like semaphore to handle amount of current running threads.
    2. Another possible solution would be a bash script, that will take all input data and run in background. It must check for already running instances of this script/ocr engine and start new processes accordingly

2. **Issue:** By some browsers there is a redirect window as we reload the page, while OCR is being in Progress.
  **Solution:** Actually there is already a solution, which involves more changes from JS (Frontend) side and because of that it is now on feature branch called 'wip_notification_feature'. Furthermore, it can be changed by changing PageView ViewHelper's behaviour, so that it does not return the current page, but provides a GET redirect, so that POST request data is reset

## Possible features:

1. There is a possibility that OCR process will be interrupted or some kind of error can happen, so that WIP file will not be overwritten by actual OCR. In this case, it must be checked and WIP file should be either deleted and user should have an opportunity to start OCR process again or OCR should be performed automatically

2. A progress bar should be shown, when OCR for a book is already in progress. For that we have to either send information about created/wip pages from backend, which is not so flexible because user has to reload page to see changes. Another solution would be provide some way to check existence of these pages from frontend, i.e. using AJAX. For example we can send a folder, where texts will be saved. Actually this solution also brings some drawbacks.

