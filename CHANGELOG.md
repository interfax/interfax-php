# Changelog

All notable changes to this project will be documented in this file. This project adheres to [Semantic Versioning](http://semver.org/).

1.1.4
=====
+ accept any 2xx status responses from interfax api
+ minor improvement to response exception generation

1.1.3
=====
+ Enhancement: Make fax id available when first created

1.1.2
=====
+ [BUG] Fix bug in marking inbound faxes as read/unread

1.1.1
=====
+ Bugfix: urlencode filename attribute for multipart fax document upload.

1.1.0
=====
+ Fluent method signatures where appropriate on Fax and Document objects.
+ Distinct Test namespace to ensure individual test file running is supported.

1.0.3
=====
+ Documentation correction for the outbound fax status retrieval.

1.0.2
=====
+ Update to project structure to support automatic package generation.

1.0.1
=====
+ Added INSTALLATION.md file to document how to use the library outside of a composer managed framework.
+ Added support for using stream resources to send faxes.

1.0.0
=====
+ Initial release