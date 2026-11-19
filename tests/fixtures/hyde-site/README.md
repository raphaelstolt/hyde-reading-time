# Hyde integration fixture

This minimal site contains one Markdown post, documentation page, and static page. The integration tests install the package into it through Composer, run Hyde's CLI build, and check the rendered output and configuration publishing.

The Composer lockfile pins the fixture's dependencies. Node assets and the default Hyde configuration files are intentionally omitted; Hyde supplies its own defaults.
