# Customization

## Customizing the HTML content

The HTML output of BicBucStriim is primarily based on templates.
The results of every user request will be handed to a template engine that inserts the data into the relevant templates and returns the resulting HTML.
So, to customize the HTML output means to edit these templates.

The template engine used is Twig, see [Twig for Template Designers](https://twig.symfony.com/doc/3.x/templates.html) for an overview of the template language.
The Twig template language is straightforward and can be extended with a little PHP knowledge.

Knowing that the steps for customizing the HTML output are:

1. Locate the templates you want to change
2. Identify the data you want to modify
3. Modify the template

### Locate the templates

First locate the action that you want to modify in folder `src/Application/Actions/`.
There are subfolders for every major part of the UI.
That and the names of the actions should help you to find the relevant one.
The naming pattern is `<action><object>Action.php`.

Examples:

- a list of book titles: `ViewTitlesAction.php`
- a view of one book: `ViewTitleAction.php`

At the end of an action there is typically a line starting with `return $this->respondWithPage('titles.twig' ...`.
The Twig file is the name of the template you need to look at.
The templates are stored in the folder `/templates`.

### Identify the data

There are two main data sources:

1. the Calibre library 
2. the BicBucStriim database

The latter (`data.db`) is only important if you want to play with the admin area or the author links and thumbnails, BBS adds.
Most data displayed comes from Calibres `metadata.db`.
To find the data, open `metadata.db` in SQLite and identify the names of the columns containing the data you are interested in.

It is beyond the scope of this documentation to explain the structure of the Calibre database, but if you look at the UI and know a little bit about how databases work it should be relatively easy to find the data.

### Modify the template 

The actions pipe most of the Calibre data unchanged to the templates.
There may be additional fields that contain transformed Calibre data.
To make your desired changes, enter/replace/delete the column names you located as Twig variables and modify the result with HTML or Twig expressions.

Example:

You don't like that the title lists contain the _sort title_ of the book (named *Title sort* in the Calibre UI), you would prefer to see the unchanged title there instead.
You've found the template `title_entry.twig` that is responsible for rendering the list entries.
The template contains something like:

```twig
    <h3>
        {% if series %}{{book.series_index}} - {% endif %}
        {{ book.sort }} ({{ book.pubdate|date("Y") }})
    </h3>
```

The sort title is insert with the variable `{{ book.sort }}`.
The array `book` contains the data from Calibre's `books` table.
You also found that this table contains a `title` column with the original title.
To change the fields simply replace the column name:

```twig
    <h3>
        ...
        {{ book.title }} ({{ book.pubdate|date("Y") }})
    </h3>
```

And that would be all.
All book lists should show now the original title instead of the sort title.
