# PHPstachio
Encrypted PHP web shell generator.  Requires openssl on the machine that generates the shell and on the target machine.

Usage:

```
git clone https://github.com/bismuthsalamander/PHPstachio/
cd PHPstachio
php generate.php #enter password when asked
#copy web shell to target web server, visit page in browser and type password
```

The web shell is encrypted using AES-256 in CTR mode.  The encryption key is derived from your password and a random salt.

It's inconvenient for the user to retype their password on every request, but it's a bit dangerous to send the password back to the browser in an `<input type="hidden">` element.  Therefore, the password is stored in the browser in sessionStorage and automatically populated into the form when the web shell is rendered.

Possible to-dos include adding some more obfuscation to the final shell and supporting file downloads over HTTP (i.e., built-in wget).