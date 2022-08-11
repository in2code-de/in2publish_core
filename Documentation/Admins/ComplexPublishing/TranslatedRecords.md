# Translated Records

TYPO3 is well known for its language handling capabilities, but it is quite complex under the hood. Translated pages and
content elements are only visible, if their translation original exists and is visible. This creates complicated
dependencies across different types of content (including pages, content and other records).

## Pages

A translated page, even in "Free Mode", is not visible if the translation parent is disabled.

## Content Elements

Translated content elements are not visible, if the translation parent is disabled. This is true for all content
elements which have a translation parent, even in "Mixed Mode". Only content elements with a translation parent are
always visible.
