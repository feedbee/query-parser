Query Parser
============

**Project is currently is in heavy active development.**

Parse, filter  and repair text search queries to adapt them for [Sphinx extended query syntax](http://sphinxsearch.com/docs/manual-2.2.2.html#extended-syntax). Currently supported only small subset of operators, but it covers common most popular cases. Supported OR `|`, NOT `-` operators, phrase search `"xxx"`and brackets `(yyy)` for grouping.

Parser parse query, fix non-pair brackets and quotes, filters non-sense expressions. Result expression can be safely passed to Sphinx (or other search engine).
