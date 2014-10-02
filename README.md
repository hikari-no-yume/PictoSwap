What's this?
============

PictoSwap is a web app intended to be used in the Nintendo 3DS Browser. It's essentially a clone of Nintendo Letter Box (or "Swapnote" in the US), which let you scribble little drawings and send them to other 3DS users. Unfortunately, Nintendo suddenly stopped the "SpotPass" feature of the app in 2013, due to abuse of the service. Hence I decided to clone it.

Where does the name come from?
==============================

I posted a thread on reddit's /r/3DS subreddit to try and find a name. I liked [/u/TengenToppa's "PictoPass"](http://www.reddit.com/r/3DS/comments/1u0w3t/im_making_a_3ds_web_browser_clone_of_nintendo/cedho9v) suggestion, and changed it slightly to "PictoSwap". The suggestion was, presumably, a reference to note passing and PictoChat (a feature on the original Nintendo DS).

How do I set it up on the server?
=================================

Basic requirements
------------------

PHP 5.4 with Gd, PDO and SQLite3 (for Ubuntu/Debian users: `sudo apt-get install php5 php5-gd php5-sqlite3`)

PHP 5.5 preferred, as it can use the native versions of the `password_hash` function family. For 5.4 support, the password_compat library is included at `/include/password_compat.php`, and is used if `password_hash` is not natively supported.

Setup
-----

1. Create the SQLite3 database at `/pictoswap.sqlite` using the schema in `/schema.sql`. This should also be writeable.
2. Point your webserver (nginx preferred ;) at `/htdocs/`, and make sure `/htdocs/previews/` is writeable.
