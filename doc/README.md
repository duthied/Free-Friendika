# About the docs of the Friendica Project

**Note**: It is expected that some of the links in these files wont work in the Friendica repository as they are supposed to work on an installed Friendica node.

## User and Admin documentation

Every Friendica node has the _current_ version of the user and admin documentation available in the `/help` location.
The documentation is mainly done in English, but the pages can be translated and some are already to German.
If you want to help expanding the documentation or the translation, please register an account at the [Friendica wiki](https://wiki.friendi.ca) where the [texts are maintained](https://wiki.friendi.ca/docs).
The documentation is periodically merged back from there to the _development_ branch of Friendica.

Images that you use in the documentation should be located in the `img` sub-directory of this directory.
Translations are located in sub-directories named after the language codes, e.g. `de`.
Depending on the selected interface language the different translations will be applied, or the `en` original will be used as a fall-back.

## Developers Documentation

We provide a configuration file for [Doxygen](https://www.doxygen.nl/index.html) in the root of the Friendica repository.
With that you should be able to extract some documentation from the source code.

In addition there are some documentation files about the database structure in `doc`db`.
