# Draw.io GitHub Integration

Please read <a href="https://github.com/jgraph/drawio-html5" target="_blank">this</a> for a high-level introduction.

GitHub support is now available https://www.draw.io/?mode=github

An example for integration into GitHub wikis is available here: https://github.com/jgraph/draw.io/wiki/Embed-Diagrams!

--

![Diagram](http://jgraph.github.io/drawio-github/diagram.png)

<a href="http://jgraph.github.io/drawio-github/edit-diagram.html?repo=drawio-github&path=diagram.png" target="_blank">Edit</a> | <a href="https://www.draw.io/#Uhttps%3A%2F%2Fjgraph.github.io%2Fdrawio-github%2Fdiagram.png" target="_blank">Edit As New</a>

<a href="http://jgraph.github.io/drawio-github/edit-diagram.html" target="_blank">edit-diagram.html</a> does the I/O with GitHub and uses draw.io in embed mode for diagram editing. The page supports the following URL parameters: user, pass, repo, path, ref and action=open (the Edit link above is an example). Using action=open, links for immediate diagram editing in GitHub can be created (requires user and pass parameters). You can also use files on GitHub as templates in draw.io via the url parameter (see Edit As New above).

Supported file formats: .png, .svg, .html and .xml (default)

## Self-editing SVG file

![Self-editing Diagram](http://jgraph.github.io/drawio-github/self-editing.svg)

<a href="http://jgraph.github.io/drawio-github/self-editing.svg" target="_blank">self-editing.svg</a> is an SVG file with embedded PNG data (as a workaround for missing foreignObject support in Internet Explorer). This combines an image format (eg. for <img src="...") with scripting for GitHub integration. (Click on the link, not the image to enable editing.)

## Self-editing HTML file

<a href="http://jgraph.github.io/drawio-github/self-editing.html" target="_blank">self-editing.html</a> is a HTML file with embedded diagrams. The file uses nanocms.js for GitHub I/O and diagram editing, and Bootstrap and nanocms.css for some fancy CSS styles. HTML is used as a container for mutiple diagrams in different formats, including inline SVG with links.

