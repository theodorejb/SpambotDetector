Spambot Detector
================

Prevent form spam without a CAPTCHA with this easy-to-use PHP class. 
Spambot Detector works by generating a unique token which must be 
requested via Ajax and inserted into the form prior to submission. 
Many spambots attempt to post data directly to a page without requesting 
it first or executing JavaScript, and this class prevents this behavior.

Note: Spambot Detector requires users to have JavaScript enabled. However,
it is possible to use Spambot Detector alongside a CAPTCHA for fallback if
JavaScript is not enabled. See [CAPTCHA fallback](#captcha-fallback) for an example.

Usage guide
-----------

1. Include the SpambotDetect class and initialize it with a secret key of 
   your choosing (preferably something random which can't be easily guessed)

   ```php
   require 'includes/SpambotDetector/SpambotDetect.php';
   $botDetect = new SpambotDetect("This is my super awesome secret key!");
   ```

2. Call the `insertToken()` method after the form you wish to protect

   ```html
   <form id="myForm" method="post">
      <input type="text" name="username" />
      <input type="password" name="password" />
      <input type="submit" value="Submit" />
   </form>
   ```
   ```php
   <?php $botDetect->insertToken('myForm', '/includes/SpambotDetector/SpambotAjax.php') ?>
   ```

   This method accepts the form ID and relative path to SpambotAjax.php as parameters.

3. When the form is submitted, call the `validate()` method within a try...catch block

   ```php
   try {
      $botDetect->validate();
      // code to run if the validation passes
   } catch (Exception $exc) {
      // validation failed
      // display this error message in your form
      $errorMessage = $exc->getMessage();
   }
   ```

How it works
------------

1. When the form is requested, the current timestamp is stored in a session variable.
2. When the token is requested via Ajax, the stored timestamp is used to salt the secret
   key before it is hashed and returned, making the token unlikely to be guessed.
3. The token is inserted into a hidden input field in the form via JavaScript
4. When the form is submitted, if the token is not present or does not match the stored
   timestamp and secret key, validation will fail.

CAPTCHA fallback
----------------

If you'd like to use Spambot Detector, but still support users who have disabled JavaScript,
you can fall back to a CAPTCHA in the following way (this example uses [Responsive Captcha]
(https://github.com/theodorejb/Responsive-Captcha)):

1. Insert the fallback CAPTCHA inside `<noscript>` tags within your form

    ```html
   <form id="myForm" method="post">
      <input type="text" name="username" />
      <input type="password" name="password" />
      <noscript>
        <label for="captcha-field">
            <?php echo $captcha->getNewQuestion() ?>
        </label>
        <input type="text" name="captcha" id="captcha-field" />
      </noscript>
      <input type="submit" value="Submit" />
   </form>
   ```

2. When the form is submitted, check whether the captcha input field is included in the GET/POST
   request

    ```php
    if (isset($_POST['captcha'])) {
        // validate the captcha response
    } else {
        // the user has JavaScript enabled,
        // validate using Spambot Detector
    }
    ```
    
