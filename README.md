sausage-bun
===========

A wrapper script that checks system readiness for [Sausage](http://github.com/jlipps/sausage) and gets it all installed

License
-------
Sausage is available under the Apache 2 license. See `LICENSE.APACHE2` for more
details.

Get the sausage
---
Get into a fresh directory and try this baby. You might want to have the
environment variables `SAUCE_USERNAME` and `SAUCE_ACCESS_KEY` set beforehand
for the smoothest experience.

```
curl -s https://raw.github.com/jlipps/sausage-bun/master/givememysausage.php | php
```

Or (to automatically configure Sauce as part of the installation):

```
curl -s https://raw.github.com/jlipps/sausage-bun/master/givememysausage.php | \
SAUCE_USERNAME=xxxx \
SAUCE_ACCESS_KEY=yyyy \
php
```

More info, requirements, usage, etc
------------
Check out the [Sausage](http://github.com/jlipps/sausage) page

Sausage in a bun
-------
```
   
    ( \                 / )
     \ \.-------------./ /
      \(  im in a bun  )/
        `.___________.'

```
