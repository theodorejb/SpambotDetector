Spambot Detector
================

CAPTCHAs, no matter how simple, put the onus on users to prove they aren't bots.
This reduces the overall quality of the user experience, and can send a message
to users that they aren't trusted. Spambot Detector takes a different approach: 
rather than requiring users to prove their identity, it checks for bot-like behavior
while remaining invisible to humans.

Spambot Detector works by generating a unique token which must be 
requested via Ajax and inserted into the form prior to submission. 
Many spambots attempt to post data directly to a page without requesting 
it first or executing JavaScript, and this class prevents this behavior.

As of version 1.1, Spambot Detector also allows a minimum submit delay to be 
specified. This can be used to block form submissions which occur, say, less 
than 1 second after the page is requested (unrealistic for real users).

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

    A minimum submit delay (in milliseconds) can optionally be passed to the constructor:

    ```php
    // block form submissions which occur less than 2 seconds after the page is requested
    $botDetect = new SpambotDetect("This is my super awesome secret key!", 2000);
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

1. When the page is requested, the secret key and current timestamp are stored in session variables.
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
