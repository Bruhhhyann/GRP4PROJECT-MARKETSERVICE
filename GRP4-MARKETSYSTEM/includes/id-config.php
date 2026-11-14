<?php
// includes/id-config.php  (settings for the ID check at registration)
//
// OPTIONAL automatic ID reading (OCR). Install the free "Tesseract OCR" program on this computer
// (Windows installer: search "Tesseract OCR UB Mannheim"), then put the path to tesseract.exe here:
//   const TESSERACT_PATH = 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe';
// With it, the site reads the text on the uploaded photo and REJECTS it when the registrant's first and last
// name are not on it (so a random photo cannot pass). Without it, every ID is still reviewed by an admin.
const TESSERACT_PATH = '';
