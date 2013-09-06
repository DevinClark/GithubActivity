# GithubActivity

The only public method is `username()` which is an accessor/mutator method that optionally accepts a new username.

The output is done by overloading the `__toString()` method of the class so once you instantiate the class with a username, all you have to do it echo the object. If you really want, you can do it all in one line of code like this `echo new GithubActivity("DevinClark", 3);`. It's up to you.