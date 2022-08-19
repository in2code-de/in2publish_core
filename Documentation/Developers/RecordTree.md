# The RecordTree object

The `RecordTree` itself does not represent any record, but the beginning of the tree as a single node. It contains the
root records. Notice the plural "records".

The multiple root records paradoxon is explained by the new way the record tree is structured. Previously, and defined
by TYPO3's very old translation behavior, translated pages were different from pages. They had their own table, they
were never pages themselves but `pages_language_overlay` and they were just records that lived on pages like content
did. Since then, TYPO3 tried to get rid of
the ["translations in different tables"](https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/8.5/Breaking-78191-RemoveSupportForTransForeignTableInTCA.html)
feature and did so, by removing the feature and migrating all overlays to `pages`. This also changed how translations
were stored in TYPO3. The PID of a translation ultimately became the UID of the actual parent, not the translation
original. The former record tree did, mostly for backwards compatibility and API stability reasons, not reflect this
change. Since we are required to break a lot of stuff to achieve QUAG, we are also required to change the record tree in
a manner that reflects the new nature of translated pages or rather any translated record. **Translations to not live
under the translation original, but next to it**. This results in multiple roots, namely one page for each language.

Having these language based root records allows us to easily extract a part of the record tree by language and still
maintain a consistent selection. It allows language aware record traversal, calculating language dependencies and much
more.
