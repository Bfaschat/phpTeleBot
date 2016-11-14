# PhpTeleBot
*Really simple Telegram bot script*
## System requirements
* PHP version > 5
* `php_openssl` module enabled in `php.ini`
* Granting write permission to the script for the script directory
* Your server must support `file_get_contents` and `file_put_contents` functions

## Installation
1. Just copy the script in any directory that meets the specified requirements.
2. Contact `@BotFather` on Telegram and create your bot.
3. Open the script page in your browser.
3. Enter your bot name and Telegram API Token in the respective fileds and hit `Link` button.
4. Done. Now you cant edit `processUpdate ($update)` method to your taste. By default this method sends MD5 hash of a recieved message in response.