= Release instructions =

== GitHub ==

* Update changelog in `README.md`.
* Tag the master branch:

~~~
git tag v1.2.3
~~~

* Make a package:

~~~
./bin/package
~~~

* Test the package.
* Release the new version:

~~~
./bin/publish
~~~

* Edit the release on GitHub to match the changelog.
