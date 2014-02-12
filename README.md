# Cover website
This is the source code of the Cover website.

It is mostly undocumented and some parts are not very well written.

## License
Note that all the code in this repository is property of Cover. You are allowed to learn from it, play with it and contribute fixes and features to it. You are not allowed to use (parts of) the code or resources (e.g. documents, images) for other projects unrelated to Cover. Unless of course you contributed those parts to this project yourself of course.

## Security
Please, if you find a bug or security issue, please report it by making an issue on the Bitbucket repository or by notifying the WebCie at webcie@svcover.nl.

## Contribute
If you want to contribute code please fork this repository, create a new branch in which you implement your fix or feature, make sure it merges with the most up to date code in our master branch. (i.e. Just `git rebase master` when your master branch is in sync.)

## Running locally
To run the Cover site you need a webserver with PHP (at least 5.2 I guess) compiled with imagick, libgd and PostgresSQL support. You will also need a PostgresSQL database (8.2 and 9.3 both seem to work so I guess it doesn't really matter which version.)

Run the `include/data/structure.sql` script on your database. This should give you the basic database structure and content necessary to run the website. Copy the contents of the file `include/data/DBIds.php.default` file to a file named `include/data/DBIds.php` and input your own database configuration data.

Do the same for `config/config.inc.default`. Copy its contents to `config/config.php` and adjust the values where needed.

That should be it, the website should work now. You can log in with:  
email: `user@example.com`  
password: `password`

Have fun!