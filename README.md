# Cover website
This is the source code of the Cover website.

It is mostly undocumented and some parts are not very well written.

## License
Note that all the code in this repository is property of Cover. You are allowed to learn from it, play with it and contribute fixes and features to it. You are not allowed to use (parts of) the code or resources (e.g. documents, images) for other projects unrelated to Cover. Unless of course you contributed those parts to this project yourself in the first place.

## Security
Please, if you find a bug or security issue, please report it by making an issue on the Bitbucket repository or by notifying the AC/DCee at webcie@rug.nl

## Contribute
If you want to contribute code please fork this repository, create a new branch in which you implement your fix or feature, make sure it merges with the most up to date code in our master branch. (i.e. Just `git rebase master` when your master branch is in sync.) When the code is ready to be set live create a pull request and the WebCie will test and review your contribution.

## Install
For development, it's easiest to setup a local install of the website. To do so, you can follow the instructions in [quick_install.md](/docs/quick_install.md). This guide has been written for Ubuntu 18.04 (stand alone or on Windows Subsystem for Linux). There's also the [old installation instructions](/docs/install.old.md), which might contain helpful information.

## Documentation
The codebase is largely custom, but we're in the process of migrating it to the Symfony framework. Why Symfony? It's highly modular so easy to migrate step-by-step. Additionally, we already used some of its components before. 

Here's a list of the Symfony components that have currently been integrated:
- [Forms](/docs/forms.md)
- [Routing](https://symfony.com/doc/current/create_framework/routing.html)
- [Twig](https://twig.symfony.com/doc/2.x/)
- There's more to come ;)
