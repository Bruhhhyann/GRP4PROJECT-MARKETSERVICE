<?php
// includes/mail-config.php
// Fill these in to send REAL emails (example below uses Gmail).
// Leave SMTP_USER empty = test mode (the code is shown on the verify page instead of emailed).
//
// Gmail setup: Google Account > Security > turn on 2-Step Verification > App passwords >
// create one named "ServiceHub" > paste the 16 letters below (no spaces).

const SMTP_HOST      = 'smtp.gmail.com';
const SMTP_PORT      = 465;                 // SSL
const SMTP_USER      = '';                  // your full Gmail address, e.g. yourname@gmail.com
const SMTP_PASS      = '';                  // the 16-letter App Password (NOT your normal password)
const MAIL_FROM_NAME = 'ServiceHub';
