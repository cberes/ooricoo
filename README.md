# ooricoo

PHP MySQL object-relational mapping (ORM) library

## Usage

Use the `ObjectMapper` class to generate your ORM classes. You must specify the database connection options and the output directory.

```bash
./ObjectMapper.php <database host> <database user> <database password> \
    <database name> <output directory>
```

## Examples

```bash
./ObjectMapper.php localhost root password123 my_database output_dir
```

## License

Copyright © 2015 Corey Beres

Distributed under the GNU General Public License either version 3.0 or (at your option) any later version.
